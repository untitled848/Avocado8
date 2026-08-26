<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * 통합 알림(호출) 라이브러리
 *
 * - push_call()       : 알림 발송 (avo_call_board INSERT + mb_memo_call=1)
 * - load_call()       : 미확인 알림 최신 1건 조회
 * - call_check_clear(): 전체 알림 읽음 처리
 * - call_check_clear_mention_timeline(): 특정 타임라인 멘션 알림만 읽음 처리
 * - ensure_call_type(): 알림 종류(call_type_map) 없으면 등록
 *
 * avo_call_board 스키마 (기존 + 추가):
 *   기존: bc_id, bo_table, wr_id, wr_num, mb_id(발신자), mb_name(발신자명),
 *         ch_side, re_mb_id(수신자), re_mb_name(수신자명), memo, bc_datetime, bc_check
 *   추가: bc_type VARCHAR(32) DEFAULT 'board'
 *         bc_link VARCHAR(255) DEFAULT ''
 */

// $g5['call_board_table'], $g5['call_type_map_table'] 은 install/install_db.php 에서
// data/dbconfig.php 에 일괄 등록됨.

// DB 스키마 (avo_call_board.bc_type/bc_link, avo_call_type_map, cf_call_*)는
// install/gnuboard5.sql (신규) 또는 adm/migrate.php (기존 DB) 에서 생성/추가.

// ──────────────────────────────────────────────────
// ensure_call_type() — 알림 종류 시드
// ──────────────────────────────────────────────────
if (!function_exists('ensure_call_type')) {
    /**
     * call_type_map 에 타입이 없으면 등록한다.
     * @param string $ct_code
     * @param string $ct_name
     * @param string $ct_memo
     * @param string $ct_link
     * @param int    $ct_system
     * @return bool
     */
    function ensure_call_type($ct_code, $ct_name, $ct_memo = '', $ct_link = '', $ct_system = 1) {
        global $g5;
        if (empty($g5['call_type_map_table'])) {
            return false;
        }
        $ct_code = preg_replace('/[^a-z0-9_]/', '', strtolower((string)$ct_code));
        if ($ct_code === '') {
            return false;
        }
        $esc = sql_escape_string($ct_code);
        $exists = sql_fetch("SELECT ct_id FROM `{$g5['call_type_map_table']}` WHERE ct_code = '{$esc}'");
        if ($exists && !empty($exists['ct_id'])) {
            return true;
        }
        sql_query("INSERT INTO `{$g5['call_type_map_table']}` SET
            ct_code = '{$esc}',
            ct_name = '".sql_escape_string((string)$ct_name)."',
            ct_memo = '".sql_escape_string((string)$ct_memo)."',
            ct_link = '".sql_escape_string((string)$ct_link)."',
            ct_use = 1,
            ct_system = '".(int)$ct_system."'");
        return true;
    }
}

// ──────────────────────────────────────────────────
// push_call() — 통합 알림 발송
// ──────────────────────────────────────────────────
if (!function_exists('push_call')) {
    /**
     * @param string       $bc_type   'board'|'memo'|'present'|'notice'|'mention' 또는 커스텀 ct_code
     * @param array        $sender    ['mb_id'=>..., 'mb_name'=>...] 발신자 정보
     * @param array        $receivers 수신자 mb_id 배열
     * @param string|false $memo      표시 메모 (false면 ct_memo 기본값 사용)
     * @param string|false $bc_link   링크 (false면 ct_link 패턴 사용)
     * @param array        $context   bc_link 패턴 치환용 [{bo_table}, {wr_id} 등]
     * @param array        $extra     추가 INSERT 컬럼 [컬럼명=>값] (예: bo_table, wr_id, wr_num, ch_side)
     * @return int 발송 건수
     */
    function push_call(
        string $bc_type,
        array  $sender,
        array  $receivers,
        $memo    = false,
        $bc_link = false,
        array  $context = [],
        array  $extra   = []
    ): int {
        global $g5, $config;

        // 타입 메타 조회
        $type = sql_fetch("SELECT * FROM `{$g5['call_type_map_table']}`
                           WHERE ct_code = '" . sql_escape_string($bc_type) . "'");
        if (!$type) return 0;

        // 실시간 알림(member.mb_memo_call=1)은 ct_use 결과에 따름
        $realtime_on = !empty($type['ct_use']);

        if ($memo === false)    $memo    = $type['ct_memo'];
        if ($bc_link === false) $bc_link = $type['ct_link'];

        // 링크 패턴 치환
        foreach ($context as $k => $v) {
            $bc_link = str_replace('{' . $k . '}', urlencode((string)$v), $bc_link);
        }

        $type_esc        = sql_escape_string($bc_type);
        $sender_id_esc   = sql_escape_string((string)($sender['mb_id']   ?? ''));
        $sender_name_esc = sql_escape_string((string)($sender['mb_name'] ?? ($config['cf_title'] ?? '')));
        $memo_esc        = sql_escape_string((string)$memo);
        $link_esc        = sql_escape_string((string)$bc_link);
        $sender_id_raw   = (string)($sender['mb_id'] ?? '');

        $count = 0;
        foreach ($receivers as $rid) {
            $rid = trim((string)$rid);
            if ($rid === '' || $rid === $sender_id_raw) continue; // 자기 자신 제외

            $rid_esc = sql_escape_string($rid);
            $re_mb   = sql_fetch("SELECT mb_name FROM `{$g5['member_table']}` WHERE mb_id = '{$rid_esc}'");
            $re_name_esc = sql_escape_string($re_mb['mb_name'] ?? $rid);

            // 추가 컬럼(SET 절) 빌드 — 컬럼명은 [a-zA-Z0-9_] 만 허용
            $extra_sql = '';
            foreach ($extra as $col => $val) {
                if (!preg_match('/^[a-zA-Z0-9_]+$/', (string)$col)) continue;
                $extra_sql .= ", `{$col}` = '" . sql_escape_string((string)$val) . "'";
            }

            sql_query("INSERT INTO `{$g5['call_board_table']}`
                SET bc_type      = '{$type_esc}',
                    mb_id        = '{$sender_id_esc}',
                    mb_name      = '{$sender_name_esc}',
                    re_mb_id     = '{$rid_esc}',
                    re_mb_name   = '{$re_name_esc}',
                    memo         = '{$memo_esc}',
                    bc_link      = '{$link_esc}',
                    bc_check     = 0,
                    bc_datetime  = '" . G5_TIME_YMDHIS . "'
                    {$extra_sql}");
            if ($realtime_on) {
                sql_query("UPDATE `{$g5['member_table']}` SET mb_memo_call = 1 WHERE mb_id = '{$rid_esc}'");
            }
            $count++;
        }

        if (function_exists('mdl_do_action')) {
            mdl_do_action('push_call_after', $bc_type, $sender, $receivers, $memo, $bc_link, $context, $count);
        }
        return $count;
    }
}

// ──────────────────────────────────────────────────
// load_call() — 미확인 알림 최신 1건 조회
// ──────────────────────────────────────────────────
if (!function_exists('load_call')) {
    function load_call(string $mb_id): ?array {
        global $g5;
        $esc = sql_escape_string($mb_id);
        $row = sql_fetch("SELECT cb.*, t.ct_name
                          FROM `{$g5['call_board_table']}` cb
                          LEFT JOIN `{$g5['call_type_map_table']}` t ON t.ct_code = cb.bc_type
                          WHERE cb.re_mb_id = '{$esc}' AND cb.bc_check = 0
                            AND (t.ct_id IS NULL OR t.ct_use = 1)
                          ORDER BY cb.bc_id DESC LIMIT 1");
        return $row ?: null;
    }
}

// ──────────────────────────────────────────────────
// call_check_clear() — 전체 알림 읽음 처리
// ──────────────────────────────────────────────────
if (!function_exists('call_refresh_memo_flag')) {
    /** 미확인 알림 잔여 여부로 mb_memo_call 플래그를 맞춘다. */
    function call_refresh_memo_flag(string $mb_id): void {
        global $g5;
        $esc = sql_escape_string($mb_id);
        $left = sql_fetch("SELECT COUNT(*) AS cnt
                          FROM `{$g5['call_board_table']}` cb
                          LEFT JOIN `{$g5['call_type_map_table']}` t ON t.ct_code = cb.bc_type
                          WHERE cb.re_mb_id = '{$esc}' AND cb.bc_check = 0
                            AND (t.ct_id IS NULL OR t.ct_use = 1)");
        $on = ($left && (int)$left['cnt'] > 0) ? 1 : 0;
        sql_query("UPDATE `{$g5['member_table']}` SET mb_memo_call = {$on}
                   WHERE mb_id = '{$esc}'");
    }
}

if (!function_exists('call_check_clear')) {
    function call_check_clear(string $mb_id): void {
        global $g5;
        $esc = sql_escape_string($mb_id);
        sql_query("UPDATE `{$g5['call_board_table']}` SET bc_check = 1
                   WHERE re_mb_id = '{$esc}' AND bc_check = 0");
        sql_query("UPDATE `{$g5['member_table']}` SET mb_memo_call = 0
                   WHERE mb_id = '{$esc}'");
    }
}

if (!function_exists('call_check_clear_mention_timeline')) {
    /**
     * 해당 타임라인(bo_table=tl_table)의 멘션 알림만 읽음 처리.
     * 다른 종류의 미확인 알림이 있으면 mb_memo_call 은 유지한다.
     */
    function call_check_clear_mention_timeline(string $mb_id, string $tl_table): void {
        global $g5;
        $mb_id = trim($mb_id);
        $tl_table = trim($tl_table);
        if ($mb_id === '' || $tl_table === '') {
            return;
        }
        $esc = sql_escape_string($mb_id);
        $tl  = sql_escape_string($tl_table);
        $tl_url = sql_escape_string(urlencode($tl_table));
        sql_query("UPDATE `{$g5['call_board_table']}` SET bc_check = 1
                   WHERE re_mb_id = '{$esc}' AND bc_check = 0
                     AND bc_type = 'mention'
                     AND (bo_table = '{$tl}'
                          OR bc_link LIKE '%tl_table={$tl}%'
                          OR bc_link LIKE '%tl_table={$tl_url}%')");
        if (function_exists('call_refresh_memo_flag')) {
            call_refresh_memo_flag($mb_id);
        }
    }
}

// ──────────────────────────────────────────────────
// 멤버 알림 소리 설정 (JSON)
// data/member/{mb_id 앞 2자}/{mb_id}.call_pref.json
// ──────────────────────────────────────────────────
if (!function_exists('get_call_pref_path')) {
    function get_call_pref_path(string $mb_id): string {
        $mb_id = preg_replace('/[^a-zA-Z0-9_\-]/', '', $mb_id);
        if ($mb_id === '') {
            $mb_id = '_unknown';
        }
        $dir = G5_DATA_PATH.'/member/'.substr($mb_id, 0, 2);
        return $dir.'/'.$mb_id.'.call_pref.json';
    }
}

if (!function_exists('get_member_call_pref')) {
    /**
     * @return array{call_sound:bool}
     */
    function get_member_call_pref(string $mb_id): array {
        $defaults = array('call_sound' => true);
        $path = get_call_pref_path($mb_id);
        if (!is_file($path)) {
            return $defaults;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return $defaults;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return $defaults;
        }
        return array(
            'call_sound' => array_key_exists('call_sound', $data) ? (bool)$data['call_sound'] : true,
        );
    }
}

if (!function_exists('save_member_call_pref')) {
    /**
     * @param array $pref
     * @return bool
     */
    function save_member_call_pref(string $mb_id, array $pref): bool {
        $path = get_call_pref_path($mb_id);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, G5_DIR_PERMISSION, true);
        }
        $current = get_member_call_pref($mb_id);
        if (array_key_exists('call_sound', $pref)) {
            $current['call_sound'] = (bool)$pref['call_sound'];
        }
        $json = json_encode($current, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return $json !== false && @file_put_contents($path, $json) !== false;
    }
}

if (!function_exists('is_call_sound_enabled')) {
    function is_call_sound_enabled(string $mb_id): bool {
        $pref = get_member_call_pref($mb_id);
        return !empty($pref['call_sound']);
    }
}

if (!function_exists('get_call_notify_sound_url')) {
    function get_call_notify_sound_url(): string {
        ensure_call_notify_sound();
        return G5_URL.'/sound/call_notify.wav';
    }
}

if (!function_exists('ensure_call_notify_sound')) {
    /** 짧은 알림 WAV 가 없으면 생성 */
    function ensure_call_notify_sound(): void {
        $dir = G5_PATH.'/sound';
        $path = $dir.'/call_notify.wav';
        if (is_file($path) && filesize($path) > 44) {
            return;
        }
        if (!is_dir($dir)) {
            @mkdir($dir, G5_DIR_PERMISSION, true);
        }

        $sample_rate = 22050;
        $duration = 0.28;
        $freq = 880.0;
        $num_samples = (int)($sample_rate * $duration);
        $data = '';
        for ($i = 0; $i < $num_samples; $i++) {
            $t = $i / $sample_rate;
            $env = 1.0;
            if ($t < 0.02) {
                $env = $t / 0.02;
            } elseif ($t > $duration - 0.08) {
                $env = max(0.0, ($duration - $t) / 0.08);
            }
            // 간단한 2톤 알림
            $f = ($t < $duration * 0.45) ? $freq : ($freq * 1.25);
            $sample = (int)round(sin(2.0 * M_PI * $f * $t) * $env * 20000);
            if ($sample > 32767) $sample = 32767;
            if ($sample < -32768) $sample = -32768;
            $data .= pack('v', $sample & 0xffff);
        }

        $data_size = strlen($data);
        $byte_rate = $sample_rate * 2;
        $header = 'RIFF'.pack('V', 36 + $data_size).'WAVEfmt '.pack('V', 16)
            .pack('v', 1).pack('v', 1).pack('V', $sample_rate)
            .pack('V', $byte_rate).pack('v', 2).pack('v', 16)
            .'data'.pack('V', $data_size);
        @file_put_contents($path, $header.$data);
    }
}
