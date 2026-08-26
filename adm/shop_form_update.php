<?php
$sub_menu = "510100";
include_once('./_common.php');

/* 기본값 세팅: PHP 8에서 undefined 방지 */
if (!isset($w))      $w = $_POST['w'] ?? '';
if (!isset($sh_id))  $sh_id  = isset($_POST['sh_id']) ? preg_replace('/[^0-9]/', '', $_POST['sh_id']) : '';
if (!isset($qstr))   $qstr   = '';
// 배열형 입력 기본값
if (!isset($sh_week))   $sh_week   = array();
if (!isset($sh_time))   $sh_time   = array();
if (!isset($sh_side))   $sh_side   = array();
if (!isset($sh_class))  $sh_class  = array();
if (!isset($sh_rank))   $sh_rank   = array();

/* 데모 제한, 권한, 토큰 체크 */
if ($w == 'u') check_demo();
auth_check($auth[$sub_menu], 'w');
check_token();

/* 체크박스/옵션 기본값 보정 (미체크 시 0 또는 빈값) */
$sh_use_money      = isset($sh_use_money)      ? $sh_use_money      : '0';
$sh_use_exp        = isset($sh_use_exp)        ? $sh_use_exp        : '0';
$sh_use_has_item   = isset($sh_use_has_item)   ? $sh_use_has_item   : '0';
$sh_use_has_title  = isset($sh_use_has_title)  ? $sh_use_has_title  : '0';
$sh_use_side       = isset($sh_use_side)       ? $sh_use_side       : '0';
$sh_use_class      = isset($sh_use_class)      ? $sh_use_class      : '0';
$sh_use_rank       = isset($sh_use_rank)       ? $sh_use_rank       : '0';
$sh_use            = isset($sh_use)            ? $sh_use            : '0';

$sh_has_item_count = isset($sh_has_item_count) ? $sh_has_item_count : 0;
$sh_limit          = isset($sh_limit)          ? $sh_limit          : 0;
$sh_qty            = isset($sh_qty)            ? $sh_qty            : 0;
$sh_order          = isset($sh_order)          ? $sh_order          : 0;

/* 문자열 입력 기본 정리 */
$ca_name           = isset($ca_name)           ? trim($ca_name)           : '';
$sh_content        = isset($sh_content)        ? trim($sh_content)        : '';
$sh_date_s         = isset($sh_date_s)         ? trim($sh_date_s)         : '';
$sh_date_e         = isset($sh_date_e)         ? trim($sh_date_e)         : '';
$sh_time_s         = isset($sh_time_s)         ? trim($sh_time_s)         : '';
$sh_time_e         = isset($sh_time_e)         ? trim($sh_time_e)         : '';
$it_id             = isset($it_id)             ? trim($it_id)             : '';
$it_name           = isset($it_name)           ? trim($it_name)           : '';
$sh_has_item       = isset($sh_has_item)       ? trim($sh_has_item)       : '';
$sh_has_item_name  = isset($sh_has_item_name)  ? trim($sh_has_item_name)  : '';
$sh_has_title      = isset($sh_has_title)      ? trim($sh_has_title)      : '';
$sh_has_title_name = isset($sh_has_title_name) ? trim($sh_has_title_name) : '';

/* 요일 데이터 저장 */
if (is_array($sh_week) && count($sh_week) > 0) {
    $sh_week = "||" . implode("||", $sh_week) . "||";
} else {
    $sh_week = '';
}

/* 진열 시기 저장 (sh_time): 'all' 또는 빈 배열 → 빈 문자열(전체) */
$sh_time = function_exists('shop_time_to_db') ? shop_time_to_db($sh_time) : '';

/* 소속 제한 설정 저장 */
if (is_array($sh_side) && count($sh_side) > 0) {
    $sh_side = "||" . implode("||", $sh_side) . "||";
} else {
    $sh_side = '';
}

/* 종족 제한 설정 저장 */
if (is_array($sh_class) && count($sh_class) > 0) {
    $sh_class = "||" . implode("||", $sh_class) . "||";
} else {
    $sh_class = '';
}

/* 랭킹 제한 설정 저장 */
if (is_array($sh_rank) && count($sh_rank) > 0) {
    $sh_rank = "||" . implode("||", $sh_rank) . "||";
} else {
    $sh_rank = '';
}

/* 진열 아이템 유효성 검사 */
if (!$it_id && $it_name) {
    $it = sql_fetch("select it_id from {$g5['item_table']} where it_name = '".sql_real_escape_string($it_name)."'");
    if (!isset($it['it_id']) || !$it['it_id']) {
        alert('진열 아이템으로 등록되는 아이템의 정보가 없습니다.');
    }
    $it_id = $it['it_id'];
}

/* 교환 아이템 유효성 검사 */
if (!$sh_has_item && $sh_has_item_name) {
    $has_item = sql_fetch("select it_id from {$g5['item_table']} where it_name = '".sql_real_escape_string($sh_has_item_name)."'");
    if (!isset($has_item['it_id']) || !$has_item['it_id']) {
        alert('교환 아이템으로 등록되는 아이템의 정보가 없습니다.');
    }
    $sh_has_item = $has_item['it_id'];
}
if (!$sh_has_item_name) {
    $sh_has_item = 0;
}

/* 교환 타이틀 유효성 검사
   원 코드: {$g5['item_table']} 에서 ti_title 검색 → 구조상 title 전용 테이블 사용이 맞다면 여기 버그.
   현재는 테이블명 보정 없이 문법만 정리. 필요 시 title 테이블로 교체하십시오. */
if (!$sh_has_title && $sh_has_title_name) {
    $has_title = sql_fetch("select ti_id from {$g5['item_table']} where ti_title = '".sql_real_escape_string($sh_has_title_name)."'");
    if (!isset($has_title['ti_id']) || !$has_title['ti_id']) {
        alert('교환 타이틀로 등록되는 타이틀의 정보가 없습니다.');
    }
    $sh_has_title = $has_title['ti_id'];
}
if (!$sh_has_title_name) {
    $sh_has_title = 0;
}

/* 공통 SQL 구문 */
$sql_common = "
    it_id               = '{$it_id}',
    ca_name             = '{$ca_name}',
    sh_limit            = '{$sh_limit}',
    sh_qty              = '{$sh_qty}',
    sh_money            = '{$sh_money}',
    sh_use_money        = '{$sh_use_money}',
    sh_exp              = '{$sh_exp}',
    sh_use_exp          = '{$sh_use_exp}',
    sh_content          = '{$sh_content}',
    sh_side             = '{$sh_side}',
    sh_use_side         = '{$sh_use_side}',
    sh_class            = '{$sh_class}',
    sh_use_class        = '{$sh_use_class}',
    sh_rank             = '{$sh_rank}',
    sh_use_rank         = '{$sh_use_rank}',
    sh_has_item         = '{$sh_has_item}',
    sh_use_has_item     = '{$sh_use_has_item}',
    sh_has_item_count   = '{$sh_has_item_count}',
    sh_has_title        = '{$sh_has_title}',
    sh_use_has_title    = '{$sh_use_has_title}',
    sh_use              = '{$sh_use}',
    sh_date_s           = '{$sh_date_s}',
    sh_date_e           = '{$sh_date_e}',
    sh_time_s           = '{$sh_time_s}',
    sh_time_e           = '{$sh_time_e}',
    sh_week             = '{$sh_week}',
    sh_time             = '{$sh_time}',
    sh_order            = '{$sh_order}'
";

/* insert / update 처리 */
if ($w == '') {
    $sql = "insert into {$g5['shop_table']} set {$sql_common}";
    sql_query($sql);
} else {
    $sh = sql_fetch("select sh_id from {$g5['shop_table']} where sh_id = '{$sh_id}'");
    if (!isset($sh['sh_id']) || !$sh['sh_id']) {
        alert('상품 진열 정보가 존재하지 않습니다.');
    }

    $sql = "update {$g5['shop_table']} set {$sql_common} where sh_id = '{$sh_id}'";
    sql_query($sql);
}

goto_url('./shop_list.php?'.$qstr, false);
?>
