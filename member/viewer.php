<?php
include_once('./_common.php');

perm_check_page('member');

// ---------------- ch_id 결정 ----------------
$ch_id = isset($ch_id) ? (int)$ch_id : 0;
if (!$ch_id && isset($_GET['ch_id'])) {
    $ch_id = (int)$_GET['ch_id'];
}

if ($ch_id <= 0) {
    alert('캐릭터 정보가 존재하지 않습니다.');
}

// ---------------- 캐릭터 / 회원 정보 ----------------
if (empty($g5['character_table'])) {
    alert('캐릭터 테이블이 정의되어 있지 않습니다.');
}

$ch = get_character($ch_id, $time);
if($ch['ch_state']!=='승인'&&$ch['mb_id']!==($member['mb_id'] ?? '')){ 
    perm_check_page('ready');
}
if (!is_array($ch) || empty($ch['ch_id'])) {
    alert('캐릭터 정보가 존재하지 않습니다.');
}


if (!function_exists('get_member')) {
    alert('회원 정보를 가져오는 함수가 정의되어 있지 않습니다.');
}

$mb = get_member($ch['mb_id'] ?? '');

// ---------------- 시기(time) 결정 및 권한 체크 ----------------
$time = get_current_time();
$is_owner = (!empty($member['mb_id']) && !empty($mb['mb_id']) && $member['mb_id'] === $mb['mb_id']);
if (!check_time_access($time, (int)$config['cf_time'], (bool)$is_admin, $is_owner)) {
    time_safe_redirect(G5_URL.'/member/viewer.php', array('ch_id' => $ch_id));
}

// 로그인 회원
if (!isset($member) || !is_array($member)) {
    $member = array();
}

// ---------------- article 설정 (기본값 보정) ----------------
if (!isset($article) || !is_array($article)) {
    if (!empty($g5['article_default_table'])) {
        $tmp = sql_fetch("SELECT * FROM {$g5['article_default_table']} LIMIT 1");
        $article = is_array($tmp) ? $tmp : array();
    } else {
        $article = array();
    }
}

$article += array(
    'ad_use_status' => 0,
    'ad_use_title'  => 0,
    'ad_use_closet' => 0,
    'ad_use_body'   => 0,
);

// ---------------- 추가 항목 설정값 ----------------
$ch_ar = array();
$str_secret = " WHERE 1 ";

if (!empty($member['mb_id']) && !empty($mb['mb_id']) && $member['mb_id'] === $mb['mb_id']) {
    $str_secret .= " AND ar_secret != 'H' ";
} else {
    $str_secret .= " AND ar_secret = '' ";
}

// 현재 시기 (GET ti_time 우선, 없으면 $time, 그것도 없으면 0=전체 노출)
$cur_ti = isset($_GET['ti_time']) ? (int)$_GET['ti_time'] : (int)($time ?? 0);

if (!empty($g5['article_table'])) {
    $ar_result = sql_query("SELECT * FROM {$g5['article_table']} {$str_secret} ORDER BY ar_order ASC");
    for ($i = 0, $j = 0; $row = sql_fetch_array($ar_result); $i++) {
        if ($cur_ti > 0 && function_exists('shop_time_matches') && !shop_time_matches($row['ar_time'], $cur_ti)) {
            continue;
        }
        $ch_ar[$j++] = $row;
    }
}

/*
----------------------------------------------------------------
$ch['고유코드'] 로 추가 프로필 값 접근 가능.
get_status_by_name($ch['ch_id'], '스탯명') 결과:
 - has   : 전체값
 - drop  : 차감 수치
 - now   : 현재값 (has - drop)
 - max   : 최대값
 - min   : 최소값
----------------------------------------------------------------
*/

// ---------------- 캐릭터별 추가 항목 값 (시기 적용) ----------------
$ch_article=array();

if (!empty($g5['value_table'])) {
    if ((int)$time <= 1) {
        $av_result = sql_query("SELECT * FROM {$g5['value_table']} WHERE ch_id = '{$ch['ch_id']}'");
        for ($i = 0; $row = sql_fetch_array($av_result); $i++) {
            if (!empty($row['ar_code'])) {
                $ch[$row['ar_code']] = $row['av_value'];
                $ch_article[$row['ar_code']] = $row['av_value'];
            }
        }
    } else {
        // 시기 N: 사이드 테이블 값만 사용 (없으면 빈 값, 폴백 안 함)
        foreach ($ch_ar as $ar) {
            if (!empty($ar['ar_code'])) {
                $ch[$ar['ar_code']] = get_character_time_article((int)$ch['ch_id'], $ar['ar_code'], $time);
                $ch_article[$ar['ar_code']] = $ch[$ar['ar_code']];
            }
        }
    }
}

// ---------------- 스탯 정보 ----------------
$status = array();
if (!empty($article['ad_use_status']) && !empty($g5['status_config_table']) && function_exists('get_status')) {
    $st_result = sql_query("SELECT * FROM {$g5['status_config_table']} ORDER BY st_order ASC");
    for ($i = 0; $row = sql_fetch_array($st_result); $i++) {
        $tmp = get_status($ch['ch_id'], $row['st_id']);
        if (!is_array($tmp)) $tmp = array();
        $tmp['name'] = $row['st_name'];
        $status[$i] = $tmp;
    }
}

// ---------------- 타이틀 정보 ----------------
$title = array();
if (!empty($article['ad_use_title']) && !empty($g5['title_has_table']) && !empty($g5['title_table'])) {
    $ti_sql = "
        SELECT th.*, ti.*
          FROM {$g5['title_has_table']} th
          JOIN {$g5['title_table']} ti ON th.ti_id = ti.ti_id
         WHERE th.ch_id = '{$ch['ch_id']}'
    ";
    $ti_result = sql_query($ti_sql);
    for ($i = 0; $row = sql_fetch_array($ti_result); $i++) {
        $title[$i] = $row;
    }
}

// ---------------- 의상 정보 (시기별) ----------------
if (!empty($article['ad_use_closet']) && !empty($article['ad_use_body']) && !empty($g5['closthes_table'])) {
    $time_int = (int)$time;
    $temp_cl = sql_fetch("SELECT * FROM {$g5['closthes_table']} WHERE ch_id = '{$ch_id}' AND cl_use = '1' AND cl_time = '{$time_int}'");
    if (!empty($temp_cl['cl_path'])) {
        $ch['ch_body'] = $temp_cl['cl_path'];
    }
}

// ---------------- 모듈 훅 (섹션 HTML + 추가 탭) ----------------
$viewer_ctx = array(
    'ch'      => $ch,
    'ch_id'   => (int)($ch['ch_id'] ?? 0),
    'mb'      => isset($mb) && is_array($mb) ? $mb : array(),
    'article' => isset($article) && is_array($article) ? $article : array(),
    'tabs'    => isset($_GET['tabs']) ? (string)$_GET['tabs'] : '',
);
$viewer_info = '';
$viewer_bag = '';
$viewer_relation = '';
$viewer_extra_tabs = array();
$viewer_tools = array();
if (function_exists('mdl_apply_filters')) {
    $viewer_info = mdl_apply_filters('member_viewer_info', '', $viewer_ctx);
    $viewer_bag = mdl_apply_filters('member_viewer_bag', '', $viewer_ctx);
    $viewer_relation = mdl_apply_filters('member_viewer_relation', '', $viewer_ctx);
    $viewer_extra_tabs = mdl_apply_filters('member_viewer_tabs', array(), $viewer_ctx);
    $viewer_tools = mdl_apply_filters('member_viewer_tools', array(), $viewer_ctx);
}
if (!is_string($viewer_info)) {
    $viewer_info = '';
}
if (!is_string($viewer_bag)) {
    $viewer_bag = '';
}
if (!is_string($viewer_relation)) {
    $viewer_relation = '';
}
if (!is_array($viewer_extra_tabs)) {
    $viewer_extra_tabs = array();
}

$_mv_reserved = array('info' => true, 'bag' => true, 'relation' => true, 'extra' => true);
$_mv_norm = array();
foreach ($viewer_extra_tabs as $_xtab) {
    if (!is_array($_xtab) || !isset($_xtab['label']) || (string)$_xtab['label'] === '') {
        continue;
    }
    $_xhref = isset($_xtab['href']) ? trim((string)$_xtab['href']) : '';
    if ($_xhref !== '') {
        $_mv_norm[] = array(
            'label' => (string)$_xtab['label'],
            'href'  => $_xhref,
        );
        continue;
    }
    if (empty($_xtab['id'])) {
        continue;
    }
    $_xid = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$_xtab['id']);
    if ($_xid === '' || isset($_mv_reserved[$_xid])) {
        continue;
    }
    $_xhtml = isset($_xtab['html']) ? $_xtab['html'] : '';
    if (!is_string($_xhtml) || trim($_xhtml) === '') {
        continue;
    }
    $_mv_norm[] = array(
        'id'    => $_xid,
        'label' => (string)$_xtab['label'],
        'html'  => $_xhtml,
    );
}
$viewer_extra_tabs = $_mv_norm;
unset($_mv_reserved, $_mv_norm, $_xtab, $_xhref, $_xid, $_xhtml);

if (function_exists('character_viewer_normalize_tools')) {
    $viewer_tools = character_viewer_normalize_tools($viewer_tools);
} elseif (!is_array($viewer_tools)) {
    $viewer_tools = array();
}

// ---------------- 타이틀 및 스킨 출력 ----------------
$g5['title'] = ($ch['ch_name'] ?? '').' 프로필';

include_once(G5_PATH.'/head.php');

include function_exists('theme_dir_file') ? theme_dir_file('member/skin/viewer.skin.php') : (G5_PATH.'/member/skin/viewer.skin.php');

include_once(G5_PATH.'/tail.php');
