<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * 시기(Time) 시스템 — 코어 통합
 *
 * 저장 구조:
 *   {prefix}character_time           — 시기 메타 (ti_id, ti_name, ti_order, ti_open)
 *   {prefix}character_time_value     — 시기별 캐릭터 기본 정보 (이름/썸네일/머리/전신)
 *   {prefix}character_time_article   — 시기별 캐릭터 article 값 (av_value override)
 *   {prefix}config.cf_time           — 현재 공개 시기 (정수)
 *   {prefix}character_closthes.cl_time — 의상이 속한 시기
 *
 * 정책:
 *   - 시기 1 = 원본. {prefix}character / {prefix}article_value 그대로 사용
 *   - 시기 N(>1) = override. 사이드 테이블 값만 사용 (값 없으면 빈 값, 폴백 안 함)
 *   - 단, 이름(ch_name)만은 시기 N 저장 시 빈 칸이면 시기 1 이름을 자동 복사
 *   - 읽기 권한 (check_time_access):
 *       관리자: 항상 가능
 *       ti_open=1 (공개): 모두 가능
 *       ti_open=2 (비공개): 본인 캐릭터일 때만 가능
 *       ti_open=0 (미사용): 관리자만 가능
 *   - 쓰기 권한 (check_time_write_access):
 *       관리자: 항상 가능
 *       ti_open=1,2: 본인일 때만 가능
 *       ti_open=0: 본인이어도 불가
 *   - 권한 거부 시 ?time=cf_time 으로 redirect (alert 없음)
 */

// 테이블명은 install/install_db.php 에서 data/dbconfig.php 에 일괄 등록.
// 스키마는 install/gnuboard5.sql 에서 일괄 생성됩니다 (런타임 마이그레이션 없음).

// config.cf_time 기본값 보강
if (!isset($config['cf_time']) || (int)$config['cf_time'] < 1) {
    $config['cf_time'] = 1;
}

// ----------------------------------------------------------------------------
// 관리자 테스트 시기 오버라이드
//   - 진짜 사이트 시기는 $config['cf_time_real'] 에 백업
//   - 관리자가 마이페이지에서 mb_time_override > 0 으로 지정한 경우
//     $config['cf_time'] 를 해당 값으로 덮어씀
//   - 일반 사용자는 영향 없음
// ----------------------------------------------------------------------------
$config['cf_time_real'] = (int)$config['cf_time'];

if (!empty($is_admin) && !empty($member['mb_time_override'])) {
    $_ov = (int)$member['mb_time_override'];
    if ($_ov > 0) {
        $_ov_row = sql_fetch("SELECT ti_id FROM `{$g5['time_table']}` WHERE ti_order = '{$_ov}'", false);
        if (!empty($_ov_row['ti_id'])) {
            $config['cf_time'] = $_ov;
        }
    }
    unset($_ov, $_ov_row);
}

// ----------------------------------------------------------------------------
// 헬퍼 함수
// ----------------------------------------------------------------------------

/**
 * ti_type 라벨 (renewal/au/event)
 */
function time_type_label($type)
{
    $type = (string)$type;
    if ($type === 'au') return 'AU';
    if ($type === 'event') return '이벤트';
    return '리뉴얼';
}

/**
 * 현재 시기 이전(ti_order < 현재) 의 가장 최근 공개된 지정 타입 시기를 찾음
 *
 * @param int          $current_time ti_order
 * @param string|array $types        'renewal' | 'au' | array
 * @return array (없으면 빈 배열)
 */
function find_prev_time_of_type($current_time, $types = 'renewal')
{
    global $g5;
    $current_time = (int)$current_time;
    if ($current_time <= 1) return array();

    $col = sql_fetch("SHOW COLUMNS FROM `{$g5['time_table']}` LIKE 'ti_type'", false);
    $type_clause = '';
    if (!empty($col['Field'])) {
        $arr = is_array($types) ? $types : array($types);
        $arr = array_map('sql_escape_string', $arr);
        $in  = "'".implode("','", $arr)."'";
        $type_clause = " AND ti_type IN ({$in})";
    }

    $row = sql_fetch("
        SELECT * FROM `{$g5['time_table']}`
        WHERE ti_order < '{$current_time}' AND ti_open = 1{$type_clause}
        ORDER BY ti_order DESC LIMIT 1
    ", false);
    return is_array($row) ? $row : array();
}

/**
 * 하위 호환: 기존 호출용 (renewal 타입만 검색)
 */
function find_prev_renewal_time($current_time)
{
    return find_prev_time_of_type($current_time, 'renewal');
}

/**
 * 시기 메타 단건 조회 (ti_order 기준)
 * @return array (없으면 빈 배열)
 */
function get_time_by_order($ti_order)
{
    global $g5;
    $ti_order = (int)$ti_order;
    if ($ti_order <= 0) return array();
    $row = sql_fetch("SELECT * FROM `{$g5['time_table']}` WHERE ti_order = '{$ti_order}'", false);
    return is_array($row) ? $row : array();
}

/**
 * 시기 ID(ti_id) 단건 조회
 */
function get_time_by_id($ti_id)
{
    global $g5;
    $ti_id = (int)$ti_id;
    if ($ti_id <= 0) return array();
    $row = sql_fetch("SELECT * FROM `{$g5['time_table']}` WHERE ti_id = '{$ti_id}'", false);
    return is_array($row) ? $row : array();
}

/**
 * 시기 목록 조회 (ti_order ASC)
 *
 * @param bool $filter_access 권한 필터 적용 여부
 * @param bool $is_admin
 * @param bool $is_owner 본인 캐릭터 페이지 여부
 * @return array of rows
 */
function get_time_list($filter_access = false, $is_admin = false, $is_owner = false)
{
    global $g5, $config;

    $list = array();
    $result = sql_query("SELECT * FROM `{$g5['time_table']}` ORDER BY ti_order ASC", false);
    if (!$result) return $list;

    $cf_time = (int)$config['cf_time'];

    while ($row = sql_fetch_array($result)) {
        if ($filter_access) {
            $ti_order = (int)$row['ti_order'];
            if (!check_time_access($ti_order, $cf_time, $is_admin, $is_owner)) {
                continue;
            }
        }
        $list[] = $row;
    }
    return $list;
}

/**
 * 시기 읽기(열람) 권한 체크
 *
 * - 관리자: 모든 시기
 * - ti_open=1 (공개): 전원 허용
 * - ti_open=2 (비공개): 본인만
 * - ti_open=0 (미사용): 관리자만 (즉 비관리자 거부)
 *
 * @param int  $time     ti_order
 * @param int  $cf_time  현재 사이트 기본 시기 (호환성 유지용 — 사용 안 함)
 */
function check_time_access($time, $cf_time, $is_admin, $is_owner)
{   
    $time = (int)$time;
    if ($time <= 0) return false;

    $info = get_time_by_order($time);
    if (empty($info['ti_id'])) return false;
    if ($info['ti_type'] === 'event') return false; // 이벤트 시기는 직접 접근 불가 → 직전 renewal 시기로 리다이렉트
    
    if ($is_admin) return true;
    if($time==$cf_time) return true; // 현재 시기 접근은 무조건 허용 (호환성 유지)

    $ti_open = (int)$info['ti_open'];
    if ($ti_open === 1) return true;          // 공개
    if ($ti_open === 2) return (bool)$is_owner; // 비공개
    return false;                              // 미사용
}

/**
 * 시기 쓰기(수정) 권한 체크
 *
 * - 관리자: 모든 시기
 * - ti_open=1,2: 본인만
 * - ti_open=0: 본인이어도 불가
 */
function check_time_write_access($time, $is_admin, $is_owner)
{
    $time = (int)$time;
    if ($time <= 0) return false;
    if ($is_admin) return true;
    if (!$is_owner) return false;

    $info = get_time_by_order($time);
    if (empty($info['ti_id'])) return false;

    $ti_open = (int)$info['ti_open'];
    return ($ti_open === 1 || $ti_open === 2);
}

/**
 * 현재 요청 시기 결정
 * 우선순위: GET ?time= → config.cf_time → 1
 */
function get_current_time()
{
    global $config;
    static $cache = null;
    if ($cache !== null) return $cache;

    $req = ses($_GET, 'time', 0, 'int');
    $cf_time = isset($config['cf_time']) ? (int)$config['cf_time'] : 1;
    if ($cf_time < 1) $cf_time = 1;

    if ($req > 0) {
        $info = get_time_by_order($req);
        if (!empty($info['ti_id'])) {
            $cache = $req;
            return $cache;
        }
    }
    $cache = $cf_time;
    return $cache;
}

/**
 * 시기 N의 캐릭터 사이드 정보 조회
 * @return array (없으면 빈 배열)
 */
function get_character_time_value($ch_id, $time)
{
    global $g5;
    $ch_id = (int)$ch_id;
    $time = (int)$time;
    if ($ch_id <= 0 || $time <= 0) return array();

    $info = get_time_by_order($time);
    if (empty($info['ti_id'])) return array();
    $ti_id = (int)$info['ti_id'];

    $row = sql_fetch("
        SELECT * FROM `{$g5['time_value_table']}`
        WHERE ch_id = '{$ch_id}' AND ti_id = '{$ti_id}'
    ", false);
    if (!is_array($row)) return array();

    // CDN 토큰 치환 (출력)
    if (function_exists('cdn_resolve_url')) {
        if (isset($row['ctv_thumb'])) $row['ctv_thumb'] = cdn_resolve_url($row['ctv_thumb']);
    }
    if (function_exists('cdn_resolve_content')) {
        if (isset($row['ctv_head'])) $row['ctv_head'] = cdn_resolve_content($row['ctv_head']);
        if (isset($row['ctv_body'])) $row['ctv_body'] = cdn_resolve_content($row['ctv_body']);
    }
    return $row;
}

/**
 * 시기 N의 article 값 조회 (없으면 '' — 폴백 안 함)
 */
function get_character_time_article($ch_id, $ar_code, $time)
{
    global $g5;
    $ch_id = (int)$ch_id;
    $time = (int)$time;
    if ($ch_id <= 0 || $time <= 0) return '';

    $info = get_time_by_order($time);
    if (empty($info['ti_id'])) return '';
    $ti_id = (int)$info['ti_id'];

    $ar_code_esc = sql_escape_string($ar_code);
    $row = sql_fetch("
        SELECT cta_value FROM `{$g5['time_article_table']}`
        WHERE ch_id = '{$ch_id}' AND ti_id = '{$ti_id}' AND ar_code = '{$ar_code_esc}'
    ", false);
    $val = is_array($row) ? (string)$row['cta_value'] : '';
    return function_exists('cdn_resolve_content') ? cdn_resolve_content($val) : $val;
}

/**
 * 시기 N의 캐릭터 사이드 정보 UPSERT
 *
 * @param int   $ch_id
 * @param int   $time
 * @param array $data ('ctv_name','ctv_thumb','ctv_head','ctv_body')
 */
function set_character_time_value($ch_id, $time, $data)
{
    global $g5;
    $ch_id = (int)$ch_id;
    $time = (int)$time;
    if ($ch_id <= 0 || $time <= 0) return false;

    $info = get_time_by_order($time);
    if (empty($info['ti_id'])) return false;
    $ti_id = (int)$info['ti_id'];

    // CDN: 저장 시 토큰화 (모듈 비활성이어도 안전)
    $thumb_raw = (string)ses($data, 'ctv_thumb', '');
    $head_raw  = (string)ses($data, 'ctv_head', '');
    $body_raw  = (string)ses($data, 'ctv_body', '');
    if (function_exists('cdn_tokenize_url'))     $thumb_raw = cdn_tokenize_url($thumb_raw);
    if (function_exists('cdn_tokenize_content')) {
        $head_raw = cdn_tokenize_content($head_raw);
        $body_raw = cdn_tokenize_content($body_raw);
    }

    $name  = sql_escape_string((string)ses($data, 'ctv_name', ''));
    $thumb = sql_escape_string($thumb_raw);
    $head  = sql_escape_string($head_raw);
    $body  = sql_escape_string($body_raw);
    $side  = sql_escape_string((string)ses($data, 'ctv_side', ''));
    $class = sql_escape_string((string)ses($data, 'ctv_class', ''));

    $exists = sql_fetch("
        SELECT ctv_id FROM `{$g5['time_value_table']}`
        WHERE ch_id = '{$ch_id}' AND ti_id = '{$ti_id}'
    ", false);

    if (!empty($exists['ctv_id'])) {
        sql_query("
            UPDATE `{$g5['time_value_table']}`
            SET ctv_name='{$name}', ctv_thumb='{$thumb}', ctv_head='{$head}', ctv_body='{$body}',
                ctv_side='{$side}', ctv_class='{$class}'
            WHERE ctv_id='{$exists['ctv_id']}'
        ", false);
    } else {
        sql_query("
            INSERT INTO `{$g5['time_value_table']}`
            (ch_id, ti_id, ctv_name, ctv_thumb, ctv_head, ctv_body, ctv_side, ctv_class)
            VALUES ('{$ch_id}','{$ti_id}','{$name}','{$thumb}','{$head}','{$body}','{$side}','{$class}')
        ", false);
    }

    if (function_exists('mdl_do_action')) {
        mdl_do_action('character_time_value_set_after', $ch_id, $time, $data);
    }

    return true;
}

/**
 * 시기 N의 article 값 UPSERT
 */
function set_character_time_article($ch_id, $ar_code, $time, $value)
{
    global $g5;
    $ch_id = (int)$ch_id;
    $time = (int)$time;
    if ($ch_id <= 0 || $time <= 0) return false;

    $info = get_time_by_order($time);
    if (empty($info['ti_id'])) return false;
    $ti_id = (int)$info['ti_id'];

    $ar_code_esc = sql_escape_string($ar_code);
    // CDN: 저장 시 토큰화
    $value_raw = (string)$value;
    if (function_exists('cdn_tokenize_content')) $value_raw = cdn_tokenize_content($value_raw);
    $value_esc = sql_escape_string($value_raw);

    $exists = sql_fetch("
        SELECT cta_id FROM `{$g5['time_article_table']}`
        WHERE ch_id = '{$ch_id}' AND ti_id = '{$ti_id}' AND ar_code = '{$ar_code_esc}'
    ", false);

    if (!empty($exists['cta_id'])) {
        sql_query("
            UPDATE `{$g5['time_article_table']}`
            SET cta_value='{$value_esc}'
            WHERE cta_id='{$exists['cta_id']}'
        ", false);
    } else {
        sql_query("
            INSERT INTO `{$g5['time_article_table']}`
            (ch_id, ti_id, ar_code, cta_value)
            VALUES ('{$ch_id}','{$ti_id}','{$ar_code_esc}','{$value_esc}')
        ", false);
    }
    if (function_exists('mdl_do_action')) {
        mdl_do_action('character_time_article_set_after', $ch_id, $ar_code, $time, $value);
    }
    return true;
}

/**
 * 시기별 "기본의상" UPSERT
 *   - closthes_table 의 (ch_id, cl_type='default', cl_time=$time) 1행을 보장
 *   - $ch_body 가 비어 있으면 아무 동작 안 함
 */
function set_character_default_clothing($ch_id, $time, $ch_body)
{
    global $g5;
    $ch_id = (int)$ch_id;
    $time  = (int)$time;
    if ($ch_id <= 0 || $time <= 0) return false;
    $ch_body = (string)$ch_body;
    if ($ch_body === '') return false;
    if (empty($g5['closthes_table'])) return false;

    $body_esc = sql_escape_string($ch_body);

    $row = sql_fetch("SELECT cl_id FROM `{$g5['closthes_table']}`
                       WHERE ch_id = '{$ch_id}' AND cl_type = 'default' AND cl_time = '{$time}' LIMIT 1", false);
    if (!empty($row['cl_id'])) {
        sql_query("UPDATE `{$g5['closthes_table']}`
                      SET cl_path = '{$body_esc}', cl_use = '1'
                    WHERE cl_id = '".(int)$row['cl_id']."'", false);
    } else {
        sql_query("INSERT INTO `{$g5['closthes_table']}`
                      SET ch_id = '{$ch_id}', cl_subject = '기본의상',
                          cl_path = '{$body_esc}', cl_use = '1', cl_type = 'default',
                          cl_time = '{$time}'", false);
    }
    return true;
}

/**
 * 캐릭터 row($ch)에 시기별 override 적용
 *
 * 정책 (단순화):
 *  - 시기 1: 변경 없음
 *  - 시기 N (renewal/au/event): 자체 ctv_* 사용. ctv 행이 없거나 값이 비어 있으면
 *    그대로 빈 값을 적용 (이전 시기로의 폴백 없음).
 *  - 단, 이름(ch_name)은 빈 값일 때 시기 1 값을 유지한다.
 *    (이벤트 시기는 프로필을 두지 않으므로 본 함수가 아무 값도 강제하지 않으면
 *     자연스럽게 시기 1 이름이 유지되며, 이미지 3종도 빈 값으로 표시된다.)
 */
function apply_character_time(&$ch, $time)
{
    if (!is_array($ch) || empty($ch['ch_id'])) return;
    $time = (int)$time;
    if ($time <= 1) return;

    $info = get_time_by_order($time);
    $ti_type = isset($info['ti_type']) ? (string)$info['ti_type'] : 'renewal';

    // 이벤트 시기 → 프로필 자체를 두지 않음. 이미지/이름 모두 시기 1 값을 그대로 유지.
    if ($ti_type === 'event') return;

    // renewal / au — 자체 ctv 만 사용. 폴백 없음.
    $ctv = get_character_time_value((int)$ch['ch_id'], $time);

    // 이름은 빈 값이면 시기 1 값 유지 (예외)
    $name = is_array($ctv) ? (string)$ctv['ctv_name'] : '';
    if ($name !== '') $ch['ch_name'] = $name;

    // 이미지 3종은 ctv 행 유무 관계 없이 항상 시기 N 값을 그대로 사용 (없으면 빈 값)
    $ch['ch_thumb'] = is_array($ctv) ? (string)$ctv['ctv_thumb'] : '';
    $ch['ch_head']  = is_array($ctv) ? (string)$ctv['ctv_head']  : '';
    $ch['ch_body']  = is_array($ctv) ? (string)$ctv['ctv_body']  : '';

    // ch_side / ch_class: 시기별 override 값 적용 (resolve_character_side_class — renewal 폴백 처리 포함)
    if (function_exists('resolve_character_side_class')) {
        $_side  = resolve_character_side_class($ch, $time, 'side');
        $_class = resolve_character_side_class($ch, $time, 'class');
        $ch['ch_side']  = $_side;
        $ch['ch_class'] = $_class;
    }
}

/**
 * 시기 탭 HTML 출력
 *
 * - 시기가 1개 이하 → 출력 안 함
 * - 접근 가능 시기가 1개 이하 → 출력 안 함
 * - 현재 URL의 ?time= 만 교체하여 링크 생성
 *
 * @param int  $current_time
 * @param bool $is_admin
 * @param bool $is_owner
 */
function print_time_tabs($current_time, $is_admin = false, $is_owner = false)
{
    $list = get_time_list(true, $is_admin, $is_owner);

    // ti_type 필터: 리뉴얼/AU 시기만 표시 (이벤트 시기는 탭에 노출하지 않음)
    $list = array_values(array_filter($list, function($r){
        $t = isset($r['ti_type']) ? (string)$r['ti_type'] : 'renewal';
        return $t === 'renewal' || $t === 'au';
    }));

    if (count($list) <= 1) {echo '<div class="time-tabs" style="margin:0 0 15px; text-align:center;"></div>'; return;}

    $current_time = (int)$current_time;

    // 현재 시기가 event 타입이면 가장 직전 renewal 시기를 "활성 탭" 으로 처리
    $highlight_time = $current_time;
    $cur_info = get_time_by_order($current_time);
    $cur_type = isset($cur_info['ti_type']) ? (string)$cur_info['ti_type'] : 'renewal';
    if ($cur_type === 'event') {
        $prev = find_prev_time_of_type($current_time, 'renewal');
        if (!empty($prev['ti_order'])) $highlight_time = (int)$prev['ti_order'];
    }

    // 현재 쿼리스트링에서 time 만 변경
    $qs = $_GET;
    unset($qs['time']);

    echo '<div class="time-tabs" style="margin:0 0 15px; text-align:center;">';
    foreach ($list as $row) {
        $ti_order = (int)$row['ti_order'];
        $qs2 = $qs;
        $qs2['time'] = $ti_order;
        $parts = array();
        foreach ($qs2 as $k => $v) {
            if ($v === '' || $v === null || is_array($v)) continue;
            $parts[] = urlencode($k).'='.urlencode($v);
        }
        $url = '?'.implode('&amp;', $parts);
        $cls = 'ui-btn'.(($ti_order === $highlight_time) ? ' point' : '');
        echo '<a href="'.$url.'" class="'.$cls.'" style="margin:2px;">'.h($row['ti_name']).'</a>';
    }
    echo '</div>';
}

/**
 * 시기 접근 거부 시 안전 redirect (?time=cf_time)
 *
 * @param string $base_url 현재 URL (쿼리 제외)
 * @param array  $extra_qs 유지할 쿼리 파라미터
 */
function time_safe_redirect($base_url, $extra_qs = array())
{
    global $config;
    $cf_time = isset($config['cf_time']) ? (int)$config['cf_time'] : 1;
    if ($cf_time < 1) $cf_time = 1;

    $req_time = isset($_GET['time']) ? (int)$_GET['time'] : 0;

    // 이벤트 시기 → 직전 renewal 시기로 리다이렉트
    $redirect_time = $cf_time;
    if ($req_time > 0) {
        $req_info = get_time_by_order($req_time);
        if (!empty($req_info['ti_type']) && $req_info['ti_type'] === 'event') {
            $prev = find_prev_time_of_type($req_time, 'renewal');
            if (!empty($prev['ti_order'])) {
                $redirect_time = (int)$prev['ti_order'];
            }
        }
    }

    // 이미 목적지 시기 → 무한 리다이렉트 방지
    if ($req_time > 0 && $req_time === $redirect_time) {
        exit;
    }

    $qs = is_array($extra_qs) ? $extra_qs : array();
    $qs['time'] = $redirect_time;

    $parts = array();
    foreach ($qs as $k => $v) {
        if ($v === '' || $v === null) continue;
        $parts[] = urlencode($k).'='.urlencode($v);
    }
    $url = $base_url;
    if (!empty($parts)) $url .= (strpos($base_url, '?') === false ? '?' : '&').implode('&', $parts);

    goto_url($url);
}

/**
 * 관리자 시기 오버라이드가 활성 상태인지
 */
function is_time_override_active()
{
    global $config;
    if (empty($config['cf_time_real'])) return false;
    return ((int)$config['cf_time'] !== (int)$config['cf_time_real']);
}

/**
 * 관리자 테스트 시기 오버라이드 안내 배너 출력 (하위 호환 — print_admin_status_banner 로 통합됨)
 */
function print_time_override_banner()
{
    if (function_exists('print_admin_status_banner')) { print_admin_status_banner(); return; }
    global $config;
    if (!is_time_override_active()) return;

    $real = (int)$config['cf_time_real'];
    $ov   = (int)$config['cf_time'];

    $real_info = get_time_by_order($real);
    $ov_info   = get_time_by_order($ov);

    $real_name = !empty($real_info['ti_name']) ? $real_info['ti_name'] : ('시기 '.$real);
    $ov_name   = !empty($ov_info['ti_name'])   ? $ov_info['ti_name']   : ('시기 '.$ov);

    $mypage_url = G5_URL.'/mypage/admin/time_override.php';
?>
<div id="time-override-banner" data-toplevel-only="1" style="position:relative;z-index:9999;background:#fff3cd;border-bottom:2px solid #f0ad4e;color:#7a4a00;padding:8px 16px;font-size:13px;text-align:center;line-height:1.6;">
    <strong>⚠ 테스트 시기 보기 중</strong>
    — 지금 보고 있는 시기: <strong style="color:#c0392b;"><?php echo h($ov_name); ?></strong> (#<?php echo $ov; ?>)
    / 실제 사이트 시기: <strong style="color:#0a7;"><?php echo h($real_name); ?></strong> (#<?php echo $real; ?>)
    &nbsp;<a href="<?php echo $mypage_url; ?>" style="color:#06c;text-decoration:underline;">설정 변경</a>
</div>

<?php
}
