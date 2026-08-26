<?php
/**
 * ============================================================================
 *  reset_setting / reset_manager — 주기적 리셋 카운터
 * ============================================================================
 *
 *  목적
 *    "매일 자정에 0으로 돌아가는" 같은 주기적 카운터를 도메인 테이블 (character /
 *    member 등) 컬럼이 아닌 별도 테이블로 분리해 관리.
 *    모듈이 추가될 때 ALTER TABLE 없이 행만 삽입하면 사용 가능.
 *
 *  스키마
 *    avo_reset_setting   : 정의
 *      rs_id, rs_name, rs_label, rs_last, rs_next, rs_period, rs_default,
 *      rs_plus, rs_max, rs_min, rs_type ('member'|'character'|'admin'),
 *      setting_url, rs_use
 *
 *    avo_reset_manager   : 값
 *      rm_id, rs_id, rm_subkey, rm_value
 *      UNIQUE(rs_id, rm_subkey)
 *      - rs_type='member'    : rm_subkey = mb_id
 *      - rs_type='character' : rm_subkey = ch_id
 *      - rs_type='admin'     : rm_subkey = '' (전역 1행)
 *
 *  동작
 *    누구든 사이트 접속 시 reset_tick() 1회 자동 실행 →
 *      rs_use=1 인 setting 들 중 rs_next <= now 인 것을 atomic 선점 후
 *      reset_manager 일괄 갱신:
 *        rs_plus != 0  → rm_value = clamp(rm_value + rs_plus, rs_min, rs_max)
 *        rs_plus == 0  → rm_value = rs_default
 *      rs_last/rs_next 갱신.
 *    선점은 "UPDATE ... WHERE rs_next<=now AND rs_use=1" 의 affected_rows 로 수행
 *    (트랜잭션 / MyISAM 둘 다 안전).
 *
 *  사용 (모듈)
 *    install:   reset_setting_register('character_search', [...옵션...])
 *    runtime:   reset_value_get('character_search', $ch_id)
 *               reset_value_increment('character_search', $ch_id, -1)
 *               reset_value_set('character_search', $ch_id, 5)
 * ============================================================================
 */

if (!defined('_GNUBOARD_')) exit;

/* -------------------------------------------------------------------------- */
/*  설치 (no-op — 스키마는 install/gnuboard5.sql 에서 일괄 생성됨)              */
/* -------------------------------------------------------------------------- */

// 테이블명($g5['reset_setting_table'], $g5['reset_manager_table']) 은
// install/install_db.php 에서 data/dbconfig.php 에 일괄 등록.
// 본 함수는 호출처 호환성 유지를 위해 no-op 으로 보존.

/* -------------------------------------------------------------------------- */
/*  주기 계산                                                                  */
/* -------------------------------------------------------------------------- */

/**
 * 현재 시간 기준 다음 리셋 timestamp 계산.
 *
 * 지원 period:
 *   'hourly'                    매 정각
 *   'daily'                     매일 자정
 *   'weekly'                    매주 월요일 0시
 *   'monthly'                   매월 1일 0시
 *   'yearly'                    매년 1월 1일 0시
 *   'Nh'  (예: '3h','6h','12h') N 시간 간격 (자정 기준 정렬)
 *                               예: 6h → 00,06,12,18시 정각
 */
function reset_calc_next($period, $base = null) {
    if ($base === null) $base = defined('G5_SERVER_TIME') ? (int)G5_SERVER_TIME : time();

    // Nh 형식: N<=24 면 자정 기준 N시간 슬롯, N>24 면 base 시점에서 N시간 후
    if (preg_match('/^(\d+)\s*h$/i', (string)$period, $m)) {
        $n = max(1, (int)$m[1]);
        if ($n <= 24) {
            $y = (int)date('Y', $base); $mo = (int)date('n', $base); $d = (int)date('j', $base);
            $midnight = mktime(0, 0, 0, $mo, $d, $y);
            $elapsed = $base - $midnight;          // 자정 후 경과 초
            $slot = (int)floor($elapsed / ($n * 3600)) + 1;
            $next = $midnight + $slot * $n * 3600;
            return $next;
        }
        // 24시간 초과 — 단순히 base + N시간 (자정 정렬 의미 없음)
        return $base + $n * 3600;
    }

    switch ($period) {
        case 'hourly':
            return mktime((int)date('H', $base) + 1, 0, 0, (int)date('n', $base), (int)date('j', $base), (int)date('Y', $base));
        case 'daily':
            return mktime(0, 0, 0, (int)date('n', $base), (int)date('j', $base) + 1, (int)date('Y', $base));
        case 'weekly':
            $dow = (int)date('N', $base);
            return mktime(0, 0, 0, (int)date('n', $base), (int)date('j', $base) + (8 - $dow), (int)date('Y', $base));
        case 'monthly':
            return mktime(0, 0, 0, (int)date('n', $base) + 1, 1, (int)date('Y', $base));
        case 'yearly':
            return mktime(0, 0, 0, 1, 1, (int)date('Y', $base) + 1);
    }
    return mktime(0, 0, 0, (int)date('n', $base), (int)date('j', $base) + 1, (int)date('Y', $base));
}

/* -------------------------------------------------------------------------- */
/*  설정 등록 / 조회                                                            */
/* -------------------------------------------------------------------------- */

/**
 * 모듈 install.php 에서 호출. 같은 rs_name 이 있으면 비파괴적으로 보강 (rs_use,
 * rs_last, rs_next 는 보존). 신규면 INSERT.
 *
 * @param string $rs_name
 * @param array  $opts {
 *     'label'       => string,
 *     'period'      => 'hourly|daily|weekly|monthly|yearly' (기본 daily),
 *     'default'     => int (기본 0),
 *     'plus'        => int (기본 0). 0 이면 매 리셋마다 rm_value = default.
 *                                    0 이 아니면 매 리셋마다 rm_value += plus (clamp).
 *     'max'         => int (기본 2147483647),
 *     'min'         => int (기본 0),
 *     'type'        => 'member|character|admin' (기본 member),
 *     'setting_url' => string,
 *     'use'         => 1|0 (신규 INSERT 시에만 사용, 기존 행 보존),
 * }
 * @return int rs_id
 */
function reset_setting_register($rs_name, $opts = array()) {
    global $g5;
    $tbl = $g5['reset_setting_table'];

    $name_e = sql_real_escape_string($rs_name);
    $row = sql_fetch("select * from `{$tbl}` where rs_name='{$name_e}' limit 1", false);

    $defaults = array(
        'label'       => $rs_name,
        'period'      => 'daily',
        'default'     => 0,
        'plus'        => 0,
        'max'         => 2147483647,
        'min'         => 0,
        'type'        => 'member',
        'setting_url' => '',
        'use'         => 1,
    );
    $opts = array_merge($defaults, $opts);

    if (!empty($row)) {
        $updates = array(
            "rs_label='".sql_real_escape_string($opts['label'])."'",
            "rs_period='".sql_real_escape_string($opts['period'])."'",
            "rs_default=".(int)$opts['default'],
            "rs_plus=".(int)$opts['plus'],
            "rs_max=".(int)$opts['max'],
            "rs_min=".(int)$opts['min'],
            "rs_type='".sql_real_escape_string($opts['type'])."'",
            "setting_url='".sql_real_escape_string($opts['setting_url'])."'",
        );
        sql_query("update `{$tbl}` set ".implode(',', $updates)." where rs_id=".(int)$row['rs_id']);
        return (int)$row['rs_id'];
    }

    $next = reset_calc_next($opts['period']);
    sql_query("insert into `{$tbl}`
        (rs_name, rs_label, rs_last, rs_next, rs_period, rs_default, rs_plus, rs_max, rs_min, rs_type, setting_url, rs_use)
        values (
            '".sql_real_escape_string($rs_name)."',
            '".sql_real_escape_string($opts['label'])."',
            0, {$next},
            '".sql_real_escape_string($opts['period'])."',
            ".(int)$opts['default'].",
            ".(int)$opts['plus'].",
            ".(int)$opts['max'].",
            ".(int)$opts['min'].",
            '".sql_real_escape_string($opts['type'])."',
            '".sql_real_escape_string($opts['setting_url'])."',
            ".((int)$opts['use'] ? 1 : 0)."
        )");
    $rs_id = (int)sql_insert_id();
    // 신규 등록 시 기존 주체에 대한 reset_manager 행 시딩 (lazy 가 아닌 즉시)
    reset_seed_existing($rs_id);
    return $rs_id;
}

/**
 * 특정 setting 의 모든 주체(캐릭터/회원)에 대해 reset_manager 행이 없으면 INSERT.
 * - rs_type='character' → avo_character.ch_id 전수
 * - rs_type='member'    → avo_member.mb_id (탈퇴/차단 제외) 전수
 * - rs_type='admin'     → 전역 1행 (subkey='')
 *
 * 신규 setting 등록 시 (reset_setting_register) 호출되며,
 * 어드민 reset_manage.php "전체 시딩" 버튼에서도 사용 가능.
 *
 * @param int $rs_id
 * @return int 새로 추가된 행 수
 */
function reset_seed_existing($rs_id) {
    global $g5;
    $rs_id = (int)$rs_id;
    if ($rs_id <= 0) return 0;

    $st = $g5['reset_setting_table'];
    $mn = $g5['reset_manager_table'];
    $set = sql_fetch("select rs_type, rs_default from `{$st}` where rs_id={$rs_id}", false);
    if (empty($set)) return 0;

    $type = (string)$set['rs_type'];
    $def  = (int)$set['rs_default'];

    if ($type === 'admin') {
        sql_query("insert ignore into `{$mn}` (rs_id, rm_subkey, rm_value)
                   values ({$rs_id}, '', {$def})", false);
        return (int)sql_affected_rows();
    }

    if ($type === 'character' && !empty($g5['character_table'])) {
        sql_query("insert ignore into `{$mn}` (rs_id, rm_subkey, rm_value)
                   select {$rs_id}, ch_id, {$def} from `{$g5['character_table']}`
                   where ch_id is not null and ch_id <> ''", false);
        return (int)sql_affected_rows();
    }

    if ($type === 'member' && !empty($g5['member_table'])) {
        sql_query("insert ignore into `{$mn}` (rs_id, rm_subkey, rm_value)
                   select {$rs_id}, mb_id, {$def} from `{$g5['member_table']}`
                   where mb_id is not null and mb_id <> ''
                     and (mb_leave_date is null or mb_leave_date = '' or mb_leave_date = '00000000')
                     and (mb_intercept_date is null or mb_intercept_date = '' or mb_intercept_date = '00000000')",
                   false);
        return (int)sql_affected_rows();
    }

    return 0;
}

/** rs_name → setting row (없으면 null). 요청 내 캐시. */
function reset_setting_get($rs_name) {
    global $g5;
    static $cache = array();
    if (array_key_exists($rs_name, $cache)) return $cache[$rs_name];
    $tbl = $g5['reset_setting_table'];
    $name_e = sql_real_escape_string($rs_name);
    $row = sql_fetch("select * from `{$tbl}` where rs_name='{$name_e}' limit 1", false);
    $cache[$rs_name] = !empty($row) ? $row : null;
    return $cache[$rs_name];
}

/* -------------------------------------------------------------------------- */
/*  값 조회 / 증감 / 세팅                                                       */
/* -------------------------------------------------------------------------- */

/** 현재 값 (없으면 rs_default 반환). */
function reset_value_get($rs_name, $subkey = '') {
    global $g5;
    $set = reset_setting_get($rs_name);
    if (!$set) return 0;
    $sub_e = sql_real_escape_string((string)$subkey);
    $row = sql_fetch("select rm_value from `{$g5['reset_manager_table']}`
                       where rs_id=".(int)$set['rs_id']." and rm_subkey='{$sub_e}'
                       limit 1", false);
    if (empty($row)) return (int)$set['rs_default'];
    return (int)$row['rm_value'];
}

/** 절대값 세팅 (clamp). */
function reset_value_set($rs_name, $subkey, $value) {
    global $g5;
    $set = reset_setting_get($rs_name);
    if (!$set) return false;
    $val = (int)$value;
    $max = (int)$set['rs_max'];
    $min = (int)$set['rs_min'];
    if ($val > $max) $val = $max;
    if ($val < $min) $val = $min;
    $sub_e = sql_real_escape_string((string)$subkey);
    sql_query("insert into `{$g5['reset_manager_table']}` (rs_id, rm_subkey, rm_value)
               values (".(int)$set['rs_id'].", '{$sub_e}', {$val})
               on duplicate key update rm_value={$val}");
    return true;
}

/** 증감 (clamp). 반환: 새로운 값. */
function reset_value_increment($rs_name, $subkey, $delta = 1) {
    global $g5;
    $set = reset_setting_get($rs_name);
    if (!$set) return 0;
    $sub_e = sql_real_escape_string((string)$subkey);
    $delta = (int)$delta;
    $max = (int)$set['rs_max'];
    $min = (int)$set['rs_min'];
    $start = (int)$set['rs_default'];
    sql_query("insert into `{$g5['reset_manager_table']}` (rs_id, rm_subkey, rm_value)
               values (".(int)$set['rs_id'].", '{$sub_e}', least({$max}, greatest({$min}, {$start} + {$delta})))
               on duplicate key update
                   rm_value = least({$max}, greatest({$min}, rm_value + {$delta}))");
    return reset_value_get($rs_name, $subkey);
}

/* -------------------------------------------------------------------------- */
/*  범용 액션 카운터 헬퍼                                                       */
/* -------------------------------------------------------------------------- */
/**
 *  탐색/조합 등 "캐릭터(또는 회원)별 주기 카운터 + plus/minus 모드" 를 쓰는
 *  모듈이 공통으로 사용하는 헬퍼. reset_setting/reset_manager 위에 얇게 얹는다.
 *
 *  모드
 *    'minus' (차감식)  : rm_value = "사용한 횟수"   → 남은 = rs_max - rm_value, 사용시 +1
 *    'plus'  (누적식)  : rm_value = "남은 행동력"   → 남은 = rm_value,          사용시 -1
 *
 *  사용 예 (모듈 bootstrap)
 *    $mode   = reset_counter_mode('my_module', 'my_mode', 'minus');
 *    $remain = reset_counter_remain('character_xxx', $ch_id, $mode);
 *    reset_counter_consume('character_xxx', $ch_id, $mode);
 */

/** 요청 내 remain 캐시 무효화. */
function reset_counter_cache_clear() {
    $GLOBALS['_reset_counter_remain_cache'] = array();
}

/** 모듈 설정에서 모드 판정: 'plus' | 'minus'. */
function reset_counter_mode($module, $setting_key, $default = 'minus') {
    $def = ($default === 'plus') ? 'plus' : 'minus';
    if (!function_exists('mdl_setting_get')) return $def;
    return mdl_setting_get($module, $setting_key, $def) === 'plus' ? 'plus' : 'minus';
}

/** rs_max (설정이 없으면 $default). */
function reset_counter_max($rs_name, $default = 0) {
    $set = reset_setting_get($rs_name);
    if ($set) return (int)$set['rs_max'];
    return (int)$default;
}

/** 새 reset_manager 행의 초기값 (plus → rs_plus, minus → 0). */
function reset_counter_initial_value($rs_name, $mode) {
    $set = reset_setting_get($rs_name);
    if (!$set) return 0;
    return ($mode === 'plus') ? (int)$set['rs_plus'] : 0;
}

/**
 * reset_manager 행이 없으면 모드별 초기값으로 생성.
 * 반환: 새로 생성하면 true, 이미 있거나 처리 불가면 false.
 */
function reset_counter_ensure_row($rs_name, $subkey, $mode) {
    global $g5;
    if ($subkey === '' || $subkey === null) return false;
    $set = reset_setting_get($rs_name);
    if (!$set) return false;
    $sub_e = sql_real_escape_string((string)$subkey);
    $row = sql_fetch("select rm_id from `{$g5['reset_manager_table']}`
                        where rs_id=".(int)$set['rs_id']."
                          and rm_subkey='{$sub_e}' limit 1", false);
    if (!empty($row)) return false;
    reset_value_set($rs_name, $subkey, reset_counter_initial_value($rs_name, $mode));
    return true;
}

/**
 * 남은 횟수 반환.
 *   $opts:
 *     'unlimited_when_no_max' => bool  설정이 없거나 rs_max<=0 이면 PHP_INT_MAX 반환 (기본 false)
 *     'clamp_max'             => bool  남은 값을 rs_max 로 상한 제한 (기본 true)
 *   요청 내 캐시.
 */
function reset_counter_remain($rs_name, $subkey, $mode, $opts = array()) {
    if (!isset($GLOBALS['_reset_counter_remain_cache'])) $GLOBALS['_reset_counter_remain_cache'] = array();

    $unlimited = !empty($opts['unlimited_when_no_max']);
    $clamp_max = array_key_exists('clamp_max', $opts) ? (bool)$opts['clamp_max'] : true;

    $ck = $rs_name.'|'.(string)$subkey.'|'.$mode.'|'.($unlimited ? '1' : '0').'|'.($clamp_max ? '1' : '0');
    if (array_key_exists($ck, $GLOBALS['_reset_counter_remain_cache'])) {
        return $GLOBALS['_reset_counter_remain_cache'][$ck];
    }

    $set = reset_setting_get($rs_name);
    if (!$set) {
        return $GLOBALS['_reset_counter_remain_cache'][$ck] = $unlimited ? PHP_INT_MAX : 0;
    }
    $max = (int)$set['rs_max'];
    if ($unlimited && $max <= 0) {
        return $GLOBALS['_reset_counter_remain_cache'][$ck] = PHP_INT_MAX;
    }
    if ($subkey === '' || $subkey === null) {
        return $GLOBALS['_reset_counter_remain_cache'][$ck] = 0;
    }

    reset_counter_ensure_row($rs_name, $subkey, $mode);

    $cur = (int)reset_value_get($rs_name, $subkey);
    $remain = ($mode === 'plus') ? $cur : ($max - $cur);
    if ($remain < 0) $remain = 0;
    if ($clamp_max && $remain > $max) $remain = $max;
    return $GLOBALS['_reset_counter_remain_cache'][$ck] = $remain;
}

/**
 * 카운터 1회(또는 $amount회) 사용.
 *   minus → rm_value += amount, plus → rm_value -= amount (reset_value_increment 가 clamp).
 */
function reset_counter_consume($rs_name, $subkey, $mode, $amount = 1) {
    if (!reset_setting_get($rs_name)) return;
    $amount = abs((int)$amount);
    if ($amount === 0) return;
    $delta = ($mode === 'plus') ? -$amount : $amount;
    reset_value_increment($rs_name, $subkey, $delta);
    reset_counter_cache_clear();
}

/**
 * "남은 횟수" 를 $delta 만큼 변경 (양수=회복, 음수=차감). 아이템 사용 등에 사용.
 *   내부적으로 모드에 맞춰 rm_value 를 환산해 [0, rs_max] 범위로 적용.
 *   반환: 실제 적용된 변화량 (한도/바닥에 막히면 0 또는 일부만).
 */
function reset_counter_apply($rs_name, $subkey, $delta, $mode) {
    $delta = (int)$delta;
    if ($delta === 0) return 0;
    $set = reset_setting_get($rs_name);
    if (!$set) return 0;

    $max = (int)$set['rs_max'];
    $cur = (int)reset_value_get($rs_name, $subkey);

    $remain     = ($mode === 'plus') ? $cur : ($max - $cur);
    $new_remain = max(0, min($max, $remain + $delta));
    $applied    = $new_remain - $remain;
    if ($applied === 0) return 0;

    if ($mode === 'plus') {
        reset_value_set($rs_name, $subkey, $new_remain);
    } else {
        reset_value_set($rs_name, $subkey, $max - $new_remain);
    }
    reset_counter_cache_clear();
    return $applied;
}

/* -------------------------------------------------------------------------- */
/*  접속 시 tick — 모든 만료 setting 일괄 처리                                  */
/* -------------------------------------------------------------------------- */

/** 사이트 접속 시 1회 호출. */
function reset_tick() {
    global $g5;
    static $ticked = false;
    if ($ticked) return;
    $ticked = true;

    $st = $g5['reset_setting_table'];
    $now = defined('G5_SERVER_TIME') ? (int)G5_SERVER_TIME : time();

    $rs = sql_query("select rs_id, rs_period from `{$st}`
                       where rs_use=1 and rs_next > 0 and rs_next <= {$now}
                       order by rs_id asc
                       limit 50", false);
    if (!$rs) return;
    while ($row = sql_fetch_array($rs)) {
        $rs_id  = (int)$row['rs_id'];
        $period = $row['rs_period'];

        // 선점: rs_next 를 임시로 30초 뒤로 옮겨 동시 요청을 차단.
        $advance = $now + 30;
        sql_query("update `{$st}` set rs_next={$advance}
                     where rs_id={$rs_id} and rs_next<={$now} and rs_use=1");

        $aff = 0;
        if (function_exists('sql_affected_rows')) {
            $aff = (int)sql_affected_rows();
        } else if (function_exists('mysqli_affected_rows') && isset($g5['connect_db'])) {
            $aff = (int)mysqli_affected_rows($g5['connect_db']);
        }
        if ($aff < 1) continue;

        _reset_apply_one($rs_id);

        $next = reset_calc_next($period, $now);
        sql_query("update `{$st}` set rs_last={$now}, rs_next={$next} where rs_id={$rs_id}");
    }
}

/** 단일 setting 의 reset_manager 일괄 갱신. */
function _reset_apply_one($rs_id) {
    global $g5;
    $st = $g5['reset_setting_table'];
    $mn = $g5['reset_manager_table'];
    $set = sql_fetch("select * from `{$st}` where rs_id=".(int)$rs_id." limit 1", false);
    if (empty($set)) return;
    $max  = (int)$set['rs_max'];
    $min  = (int)$set['rs_min'];
    $plus = (int)$set['rs_plus'];
    $def  = (int)$set['rs_default'];

    if ($plus !== 0) {
        sql_query("update `{$mn}`
                      set rm_value = least({$max}, greatest({$min}, rm_value + ({$plus})))
                    where rs_id=".(int)$rs_id, false);
    } else {
        sql_query("update `{$mn}` set rm_value={$def} where rs_id=".(int)$rs_id, false);
    }
}

/** 한 행만 강제 리셋 (어드민 수동 트리거용). */
function reset_force($rs_id) {
    global $g5;
    $rs_id = (int)$rs_id;
    if ($rs_id <= 0) return false;
    $set = sql_fetch("select rs_period from `{$g5['reset_setting_table']}` where rs_id={$rs_id}", false);
    if (empty($set)) return false;
    _reset_apply_one($rs_id);
    $now  = defined('G5_SERVER_TIME') ? (int)G5_SERVER_TIME : time();
    $next = reset_calc_next($set['rs_period'], $now);
    sql_query("update `{$g5['reset_setting_table']}`
                  set rs_last={$now}, rs_next={$next} where rs_id={$rs_id}");
    return true;
}

/* -------------------------------------------------------------------------- */
/*  주체(캐릭터/회원) 제거 시 reset_manager 정리                                  */
/* -------------------------------------------------------------------------- */

/**
 * 특정 type + subkey 의 reset_manager 행 일괄 삭제.
 *
 * @param string $type    'character' | 'member' | 'admin'
 * @param string $subkey  ch_id 또는 mb_id
 * @return int            삭제된 행 수
 */
function reset_manager_drop_subkey($type, $subkey) {
    global $g5;
    $type   = trim((string)$type);
    $subkey = trim((string)$subkey);
    if ($type === '' || $subkey === '') return 0;

    $st     = $g5['reset_setting_table'];
    $mn     = $g5['reset_manager_table'];
    $type_e = sql_real_escape_string($type);
    $sub_e  = sql_real_escape_string($subkey);

    sql_query("delete mn from `{$mn}` mn
                inner join `{$st}` st on st.rs_id = mn.rs_id
                where st.rs_type = '{$type_e}' and mn.rm_subkey = '{$sub_e}'", false);

    if (function_exists('sql_affected_rows')) return (int)sql_affected_rows();
    return 0;
}

/**
 * 회원 제거 시 호출. 회원 행 + 회원 소유 캐릭터 행 모두 삭제.
 * (caller: 회원 완전삭제 직전에 호출. 캐릭터 테이블이 아직 살아 있어야 ch_id 조회 가능)
 *
 * @param string $mb_id
 * @return int 삭제된 reset_manager 행 수 (member + character 합산)
 */
function reset_manager_drop_member($mb_id) {
    global $g5;
    $mb_id = trim((string)$mb_id);
    if ($mb_id === '') return 0;

    $total = 0;
    $total += reset_manager_drop_subkey('member', $mb_id);

    if (!empty($g5['character_table'])) {
        $mb_e = sql_real_escape_string($mb_id);
        $rs = sql_query("select ch_id from `{$g5['character_table']}` where mb_id = '{$mb_e}'", false);
        if ($rs) {
            while ($r = sql_fetch_array($rs)) {
                $total += reset_manager_drop_subkey('character', $r['ch_id']);
            }
        }
    }
    return $total;
}

// 자동 tick: 코어 로드 시점에 1회 호출.
if (!defined('G5_IS_INSTALL') && function_exists('sql_query')) {
    reset_tick();
}

/* -------------------------------------------------------------------------- */
/*  코어 리스너 — 캐릭터/회원 제거 훅에 자동 반응                                */
/*  (개별 모듈이 아니라 코어가 항상 처리. 어떤 모듈이 있든 동일하게 동작.)         */
/* -------------------------------------------------------------------------- */

if (function_exists('mdl_add_action')) {
    // 캐릭터 영구 제거 직후 — 해당 ch_id 의 모든 reset_manager 행 정리
    mdl_add_action('character_delete_after', function ($ch_id /*, $ctx */) {
        if ($ch_id === '' || $ch_id === null) return;
        reset_manager_drop_subkey('character', $ch_id);
    }, 10);

    // 회원 영구 제거 직전 — 해당 mb_id + 소유 캐릭터의 reset_manager 행 정리
    // (caller 가 character_table 행이 살아 있을 때 호출해야 ch_id 조회 가능)
    mdl_add_action('member_delete_after', function ($mb_id) {
        if ($mb_id === '' || $mb_id === null) return;
        reset_manager_drop_member($mb_id);
    }, 10, 1);
}
