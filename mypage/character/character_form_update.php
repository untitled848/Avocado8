<?php
include_once('./_common.php');

if (!function_exists('mypage_register_default_timeline_user')) {
    /**
     * 승인된 캐릭터를 default 타임라인 사용자로 등록 (중복이면 유지)
     * timeline/_functions.php 의 add_timeline_user('default') 와 동일 컬럼
     */
    function mypage_register_default_timeline_user($ch_id) {
        global $g5;
        $ch_id = (int)$ch_id;
        if ($ch_id <= 0) return false;

        $user_table = G5_TABLE_PREFIX.'timeline_user';
        $dup = sql_fetch("SELECT tl_user_id FROM `{$user_table}` WHERE tl_table = 'default' AND ch_id = '{$ch_id}' LIMIT 1", false);
        if (!empty($dup['tl_user_id'])) return true;

        $ch = sql_fetch("SELECT mb_id, ch_name FROM {$g5['character_table']} WHERE ch_id = '{$ch_id}'");
        if (!is_array($ch) || empty($ch)) return false;

        $mb_id = sql_escape_string((string)($ch['mb_id'] ?? ''));
        $ch_name_raw = (string)($ch['ch_name'] ?? '');
        $ch_name = sql_escape_string($ch_name_raw);
        if (function_exists('timeline_default_men_id_from_name')) {
            $tu_men_id = sql_escape_string(timeline_default_men_id_from_name($ch_name_raw));
        } else {
            $tu_men_parts = preg_split('/\s+/u', trim($ch_name_raw), 2);
            $tu_men_src = (is_array($tu_men_parts) && isset($tu_men_parts[0])) ? $tu_men_parts[0] : $ch_name_raw;
            if (function_exists('mb_substr') && mb_strlen($tu_men_src) > 6) {
                $tu_men_src = mb_substr($tu_men_src, 0, 6);
            }
            $tu_men_id = sql_escape_string($tu_men_src);
        }
        $tu_thumb = '';
        if (function_exists('get_character_head')) {
            $tu_thumb = sql_escape_string((string)get_character_head($ch_id));
        }
        $tu_profile = sql_escape_string('/member/viewer.php?ch_id='.$ch_id);

        return sql_query("
            INSERT INTO `{$user_table}`
            SET tl_table = 'default',
                mb_id = '{$mb_id}',
                ch_id = '{$ch_id}',
                tu_men_id = '{$tu_men_id}',
                tu_name = '{$ch_name}',
                tu_thumb = '{$tu_thumb}',
                tu_header = '',
                tu_bio = '',
                tu_profile = '{$tu_profile}',
                tl_render_mode = 'normal',
                tl_user_datetime = '".G5_TIME_YMDHIS."'
        ");
    }
}

// ---------------- 기본 환경 / 입력값 정리 ----------------

$member = isset($member) && is_array($member) ? $member : array();
$config = isset($config) && is_array($config) ? $config : array();
$g5     = isset($g5) && is_array($g5) ? $g5 : array();

$w = trim($_POST['w'] ?? '');
$mb_id = trim($_POST['mb_id'] ?? ($member['mb_id'] ?? ''));
$ch_id = (int)($_POST['ch_id'] ?? 0);
$in_id = (int)($_POST['in_id'] ?? 0);

// 시기: 신규(w='')는 무조건 1, 수정일 때만 ?time= 허용
$time = ($w === 'u') ? (int)ses($_POST, 'time', 1, 'int') : 1;
if ($time < 1) $time = 1;
if ($time > 1) {
    $_tinfo = get_time_by_order($time);
    if (empty($_tinfo['ti_id'])) $time = 1;
}

$ch_state = $_POST['ch_state'] ?? '';
$ch_type  = $_POST['ch_type']  ?? '';
$ch_side  = $_POST['ch_side']  ?? '';
$ch_class = $_POST['ch_class'] ?? '';
$ch_name  = $_POST['ch_name']  ?? '';
$ch_point = (int)($_POST['ch_point'] ?? 0);

$is_admin         = !empty($is_admin);          // 그누보드 기본 패턴 유지
$is_add_character = !empty($is_add_character);
$is_mod_character = !empty($is_mod_character);

// ch_type / ch_state 화이트리스트 + 권한 가드
$is_admin_real_for_form = !empty($GLOBALS['is_admin_real']) || $is_admin;
$ch_type = in_array($ch_type, array('main','sub','npc'), true) ? $ch_type : 'main';
if (!$is_admin_real_for_form && $ch_type === 'npc') $ch_type = 'main'; // 일반회원은 npc 생성 불가
$valid_states = array('수정중','대기','승인');
if (!in_array($ch_state, $valid_states, true)) $ch_state = '수정중';

// 멤버 자동승인: $ch_member_auto_approve (extend/community.config.php)
$ch_do_auto_approve = !empty($ch_member_auto_approve) && empty($is_admin);
if ($ch_do_auto_approve) {
    $ch_state = '승인';
}

// 추가 프로필 필드
$ar_code   = (isset($_POST['ar_code'])   && is_array($_POST['ar_code']))   ? $_POST['ar_code']   : array();
$av_value  = (isset($_POST['av_value'])  && is_array($_POST['av_value']))  ? $_POST['av_value']  : array();

// 스탯 필드
$st_id     = (isset($_POST['st_id'])     && is_array($_POST['st_id']))     ? $_POST['st_id']     : array();
$sc_max    = (isset($_POST['sc_max'])    && is_array($_POST['sc_max']))    ? $_POST['sc_max']    : array();
$sc_value  = (isset($_POST['sc_value'])  && is_array($_POST['sc_value']))  ? $_POST['sc_value']  : array();

// 파일 배열 방어
if (!isset($_FILES['av_value_file']) || !isset($_FILES['av_value_file']['name']) || !is_array($_FILES['av_value_file']['name'])) {
    $_FILES['av_value_file']['name'] = array();
    $_FILES['av_value_file']['tmp_name'] = array();
}

// ---------------- 유효성 / 권한 체크 ----------------

if (!($w === '' || $w === 'u')) {
    alert('w 값이 제대로 넘어오지 않았습니다.', './index.php');
}

if (empty($member['mb_id']) || $member['mb_id'] !== $mb_id) {
    if (!$is_admin) {
        alert('잘못된 접근입니다', './index.php');
    }
}

$in = array();
if ($in_id > 0 && function_exists('get_inventory_item')) {
    $in = get_inventory_item($in_id);
    if (!is_array($in)) $in = array();
}

if ($w === '' && !$is_add_character && !$is_admin) {
    alert('생성 가능 기한이 지났습니다. 더 이상 캐릭터를 생성할 수 없습니다.', './index.php');
}

if ($w === 'u' && !$is_admin && !$is_mod_character) {
    if (empty($in['it_type']) || $in['it_type'] !== '프로필수정') {
        alert('수정 가능 기한이 지났습니다. 더 이상 캐릭터를 수정할 수 없습니다.', './index.php');
    }
}

// 시기 쓰기 권한 체크 (수정 + 시기 N)
if ($w === 'u' && $time > 1) {
    $_ch_owner = sql_fetch("SELECT mb_id FROM {$g5['character_table']} WHERE ch_id = '{$ch_id}'");
    $_is_owner = !empty($_ch_owner['mb_id']) && $_ch_owner['mb_id'] === $member['mb_id'];
    if (!check_time_write_access($time, (bool)$is_admin, $_is_owner)) {
        alert('해당 시기는 수정할 수 없습니다.', './index.php');
    }
}

// ---------------- 경로 설정 ----------------

if (empty($mb_id)) {
    alert('회원 정보가 올바르지 않습니다.', './index.php');
}

$character_image_path = G5_DATA_PATH.'/character/'.$mb_id;
$character_image_url  = G5_DATA_URL.'/character/'.$mb_id;

@mkdir($character_image_path, G5_DIR_PERMISSION, true);
@chmod($character_image_path, G5_DIR_PERMISSION);

// ---------------- 기본 프로필 설정 ----------------

$ad = sql_fetch("SELECT * FROM {$g5['article_default_table']} LIMIT 1");
if (!is_array($ad)) $ad = array();

$ad += array(
    'ad_use_thumb' => 0,
    'ad_use_head'  => 0,
    'ad_use_body'  => 0,
    'ad_url_thumb' => '',
    'ad_url_head'  => '',
    'ad_url_body'  => ''
);

// ---------------- 캐릭터 기본 데이터 SQL 구성 ----------------

$sql_article = "
    ch_state = '{$ch_state}',
    ch_type  = '{$ch_type}',
    mb_id    = '{$mb_id}',
    ch_side  = '{$ch_side}',
    ch_class = '{$ch_class}',
    ch_name  = '{$ch_name}',
    ch_point = '{$ch_point}'
";

// ---------------- 이미지 처리 ----------------

// 공통: 확장자 추출
function _char_ext($filename) {
    $pos = strrpos($filename, '.');
    return $pos !== false ? strtolower(substr($filename, $pos + 1)) : '';
}

// 두상
$ch_thumb = $_POST['ch_thumb'] ?? '';
if (!empty($ad['ad_use_thumb'])) {
    if (!empty($_FILES['ch_thumb_file']['name'])) {
        $ext = _char_ext($_FILES['ch_thumb_file']['name']);
        if ($ext) {
            $image_name = 'thumb_'.time().'.'.$ext;
            upload_file($_FILES['ch_thumb_file']['tmp_name'], $image_name, $character_image_path);
            $ch_thumb = $character_image_url.'/'.$image_name;
        }
    }

    if ($ch_thumb && strpos($ch_thumb, G5_URL) !== false) {
        if (strpos($ch_thumb, $character_image_url) !== 0) {
            alert('이미지 경로가 올바르지 않습니다.');
        }
    }

    $sql_article .= " , ch_thumb = '{$ch_thumb}'";
}

// 흉상
$ch_head = $_POST['ch_head'] ?? '';
if (!empty($ad['ad_use_head'])) {
    if (!empty($_FILES['ch_head_file']['name'])) {
        $ext = _char_ext($_FILES['ch_head_file']['name']);
        if ($ext) {
            $image_name = 'head_'.time().'.'.$ext;
            upload_file($_FILES['ch_head_file']['tmp_name'], $image_name, $character_image_path);
            $ch_head = $character_image_url.'/'.$image_name;
        }
    }

    if ($ch_head && strpos($ch_head, G5_URL) !== false) {
        if (strpos($ch_head, $character_image_url) !== 0) {
            alert('이미지 경로가 올바르지 않습니다.');
        }
    }

    $sql_article .= " , ch_head = '{$ch_head}'";
}

// 전신
$ch_body = $_POST['ch_body'] ?? '';
if (!empty($ad['ad_use_body'])) {
    if (!empty($_FILES['ch_body_file']['name'])) {
        $ext = _char_ext($_FILES['ch_body_file']['name']);
        if ($ext) {
            $image_name = 'body_'.time().'.'.$ext;
            upload_file($_FILES['ch_body_file']['tmp_name'], $image_name, $character_image_path);
            $ch_body = $character_image_url.'/'.$image_name;
        }
    }

    if ($ch_body && strpos($ch_body, G5_URL) !== false) {
        if (strpos($ch_body, $character_image_url) !== 0) {
            alert('이미지 경로가 올바르지 않습니다.');
        }
    }

    $sql_article .= " , ch_body = '{$ch_body}'";
}

// ---------------- 캐릭터 기본 INSERT / UPDATE ----------------

if ($time > 1) {
    // 시기 N: character_table 공통 변경 대신 사이드 테이블 UPSERT
    if (trim($ch_name) === '') {
        $_orig = sql_fetch("SELECT ch_name FROM {$g5['character_table']} WHERE ch_id = '{$ch_id}'");
        $ch_name = isset($_orig['ch_name']) ? $_orig['ch_name'] : '';
    }
    set_character_time_value($ch_id, $time, array(
        'ctv_name'  => $ch_name,
        'ctv_thumb' => $ch_thumb,
        'ctv_head'  => $ch_head,
        'ctv_body'  => $ch_body,
        'ctv_side'  => $ch_side,
        'ctv_class' => $ch_class,
    ));
    // 시기별 기본의상 (ch_body 로 자동 UPSERT)
    if (function_exists('set_character_default_clothing')) {
        set_character_default_clothing($ch_id, $time, $ch_body);
    }
} else if ($w === '') {

    sql_query("INSERT INTO {$g5['character_table']} SET {$sql_article}");
    $ch_id = sql_insert_id();

    if (empty($member['ch_id'])) {
        sql_query("UPDATE {$g5['member_table']} SET ch_id = '{$ch_id}' WHERE mb_id = '{$member['mb_id']}'");
    }

    if ($ch_body) {
        $sql = "
            INSERT INTO {$g5['closthes_table']}
            SET ch_id      = '{$ch_id}',
                cl_subject = '기본의상',
                cl_path    = '{$ch_body}',
                cl_use     = '1',
                cl_type    = 'default',
                cl_time    = '1'
        ";
        sql_query($sql);
    }

    // 캐릭터 생성 훅 — 모듈(reset_manager 등) 연동
    if (function_exists('mdl_do_action')) {
        mdl_do_action('character_create_after', $ch_id, array('mb_id' => $member['mb_id'], 'context' => 'mypage'));
    }

} else {

    $ch = function_exists('get_character') ? get_character($ch_id) : sql_fetch("SELECT * FROM {$g5['character_table']} WHERE ch_id = '{$ch_id}'");

    if (empty($ch['ch_id'])) {
        alert('캐릭터 정보가 존재하지 않습니다.');
    }

    // 기존 이미지 삭제 처리
    if (!empty($ad['ad_use_thumb']) && empty($ad['ad_url_thumb']) && !empty($ch['ch_thumb']) && $ch['ch_thumb'] !== $ch_thumb) {
        if (strpos($ch['ch_thumb'], G5_URL) === 0) {
            $prev = str_replace(G5_URL, G5_PATH, $ch['ch_thumb']);
            @unlink($prev);
        }
    }
    if (!empty($ad['ad_use_head']) && empty($ad['ad_url_head']) && !empty($ch['ch_head']) && $ch['ch_head'] !== $ch_head) {
        if (strpos($ch['ch_head'], G5_URL) === 0) {
            $prev = str_replace(G5_URL, G5_PATH, $ch['ch_head']);
            @unlink($prev);
        }
    }
    if (!empty($ad['ad_use_body']) && empty($ad['ad_url_body']) && !empty($ch['ch_body']) && $ch['ch_body'] !== $ch_body) {
        if (strpos($ch['ch_body'], G5_URL) === 0) {
            $prev = str_replace(G5_URL, G5_PATH, $ch['ch_body']);
            @unlink($prev);
        }
    }

    sql_query("UPDATE {$g5['character_table']} SET {$sql_article} WHERE ch_id = '{$ch_id}'");

    if (empty($member['ch_id'])) {
        sql_query("UPDATE {$g5['member_table']} SET ch_id = '{$ch_id}' WHERE mb_id = '{$member['mb_id']}'");
    }

    if ($ch_body) {
        $cl = sql_fetch("SELECT cl_id FROM {$g5['closthes_table']} WHERE ch_id = '{$ch_id}' AND cl_type = 'default' AND cl_time = '1'");
        if (!empty($cl['cl_id'])) {
            sql_query("UPDATE {$g5['closthes_table']} SET cl_path = '{$ch_body}' WHERE cl_id = '{$cl['cl_id']}'");
        } else {
            sql_query("
                INSERT INTO {$g5['closthes_table']}
                SET ch_id      = '{$ch_id}',
                    cl_subject = '기본의상',
                    cl_path    = '{$ch_body}',
                    cl_use     = '1',
                    cl_type    = 'default',
                    cl_time    = '1'
            ");
        }
    }
}

// ---------------- 추가 프로필 데이터 ----------------

// ar_code 서버 사이드 허용 목록 — 현재 시기($time)에 표시되는 항목만 처리
$_ar_allowed = array();
if (!empty($g5['article_table'])) {
    $_ar_rs = sql_query("SELECT ar_code, ar_time FROM `{$g5['article_table']}`", false);
    while ($_ar_row = sql_fetch_array($_ar_rs)) {
        if (!function_exists('shop_time_matches') || shop_time_matches($_ar_row['ar_time'], $time)) {
            $_ar_allowed[$_ar_row['ar_code']] = true;
        }
    }
}

if ($time > 1) {
    // 시기 N: 사이드 테이블 UPSERT (파일 업로드는 아래 공통 로직과 동일하게 수행)
    $cnt_ar = count($ar_code);
    for ($i = 0; $i < $cnt_ar; $i++) {
        $code = trim($ar_code[$i] ?? '');
        if ($code === '') continue;
        if (!isset($_ar_allowed[$code])) continue; // 현재 시기 외 항목 차단

        if (!empty($_FILES['av_value_file']['name'][$i])) {
            $ext = _char_ext($_FILES['av_value_file']['name'][$i]);
            if ($ext) {
                $image_name = 'img_'.$code.'_t'.$time.'_'.time().'.'.$ext;
                upload_file($_FILES['av_value_file']['tmp_name'][$i], $image_name, $character_image_path);
                $av_value[$i] = $character_image_url.'/'.$image_name;
            }
        }

        $new_val = isset($av_value[$i]) ? $av_value[$i] : '';
        set_character_time_article($ch_id, $code, $time, $new_val);
    }
} else if (!empty($g5['value_table']) && !empty($g5['article_table'])) {

    // 기존 값 로드
    $ch_val = array();
    $av_result = sql_query("SELECT * FROM {$g5['value_table']} WHERE ch_id = '{$ch_id}'");
    for ($i = 0; $row = sql_fetch_array($av_result); $i++) {
        $ch_val['av_'.$row['ar_code']] = $row['av_value'];
    }

    $cnt_ar = count($ar_code);

    for ($i = 0; $i < $cnt_ar; $i++) {
        $code = trim($ar_code[$i] ?? '');
        if ($code === '') continue;
        if (!isset($_ar_allowed[$code])) continue; // 현재 시기 외 항목 차단

        $key = 'av_'.$code;

        // 파일 업로드 처리
        if (!empty($_FILES['av_value_file']['name'][$i])) {
            $ext = _char_ext($_FILES['av_value_file']['name'][$i]);
            if ($ext) {
                $image_name = 'img_'.$code.'_'.time().'.'.$ext;
                upload_file($_FILES['av_value_file']['tmp_name'][$i], $image_name, $character_image_path);
                $av_value[$i] = $character_image_url.'/'.$image_name;
            }
        }

        $new_val = $av_value[$i] ?? '';

        // 이전 파일 삭제
        if (isset($ch_val[$key]) && $ch_val[$key] !== $new_val && strpos($ch_val[$key], G5_URL) === 0) {
            $prev = str_replace(G5_URL, G5_PATH, $ch_val[$key]);
            @unlink($prev);
        }

        // CDN: 저장 시 토큰화
        if (function_exists('cdn_tokenize_url'))     $new_val = cdn_tokenize_url($new_val);
        if (function_exists('cdn_tokenize_content')) $new_val = cdn_tokenize_content($new_val);

        $sql_article_val = "
            ch_id    = '{$ch_id}',
            ar_code  = '{$code}',
            av_value = '{$new_val}'
        ";

        if (isset($ch_val[$key])) {
            sql_query("
                UPDATE {$g5['value_table']}
                   SET {$sql_article_val}
                 WHERE ar_code = '{$code}'
                   AND ch_id = '{$ch_id}'
            ");
        } else {
            sql_query("
                INSERT INTO {$g5['value_table']}
                   SET {$sql_article_val}
            ");
        }
    }
}

// ---------------- 캐릭터 스탯 등록 ----------------

if ($time <= 1 && !empty($g5['status_table']) && count($st_id) > 0) {
    $cnt_st = count($st_id);
    for ($i = 0; $i < $cnt_st; $i++) {
        $sid = trim($st_id[$i] ?? '');
        if ($sid === '') continue;

        $max = isset($sc_max[$i]) ? (int)$sc_max[$i] : 0;

        $old_sc = sql_fetch("SELECT * FROM {$g5['status_table']} WHERE ch_id = '{$ch_id}' AND st_id = '{$sid}'");
        if (!empty($old_sc['sc_id'])) {
            sql_query("
                UPDATE {$g5['status_table']}
                   SET sc_max = '{$max}'
                 WHERE sc_id = '{$old_sc['sc_id']}'
            ");
        } else {
            sql_query("
                INSERT INTO {$g5['status_table']}
                   SET st_id = '{$sid}',
                       ch_id = '{$ch_id}',
                       sc_max = '{$max}'
            ");
        }
    }
}

// ---------------- 인벤토리 사용 처리 ----------------

if ($in_id > 0 && !empty($in) && empty($in['it_use_ever']) && function_exists('delete_inventory')) {
    delete_inventory($in_id);
}

if (!empty($ch_do_auto_approve) && $ch_id > 0 && $ch_state === '승인') {
    mypage_register_default_timeline_user($ch_id);
}

if (function_exists('mdl_do_action') && $ch_id > 0) {
    mdl_do_action('character_update_after', $ch_id, array(
        'mb_id'   => isset($member['mb_id']) ? $member['mb_id'] : '',
        'w'       => isset($w) ? $w : '',
        'context' => 'mypage',
        'ch_state'=> isset($ch_state) ? $ch_state : '',
    ));
}

// ---------------- 이동 ----------------

$_redir_qs = '?ch_id='.$ch_id;
if ($time > 1) $_redir_qs .= '&time='.$time;
goto_url('./viewer.php'.$_redir_qs, false);
