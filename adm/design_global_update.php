<?php
$sub_menu = "110200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');
if (!$is_admin) alert('관리자만 접근 가능합니다.');
check_admin_token();

$type = trim($_POST['type'] ?? '');
$value = trim($_POST['value'] ?? '1');

$reg = isset($GLOBALS['DESIGN_PRESET_TYPES']) ? $GLOBALS['DESIGN_PRESET_TYPES'] : array();
if (!isset($reg[$type])) alert('알 수 없는 디자인 종류입니다.');
$def = $reg[$type];
$col = $def['column'];
// 컬럼명 안전 검증
if (!preg_match('#^pr_[a-z_]+$#', $col)) alert('잘못된 컬럼.');

$allow_none = !empty($def['allow_none']);

// 값 정규화 + 검증
$save = '1';
if ($allow_none && $value === 'none') {
    $save = 'none';
} else if (strpos($value, 'skin:') === 0 && !empty($def['skin_gubun']) && function_exists('get_skin_dir')) {
    $skin_dir = substr($value, 5);
    if ($skin_dir !== '' && preg_match('#^(theme/)?[A-Za-z0-9_./-]{1,100}$#', $skin_dir) && strpos($skin_dir, '..') === false) {
        $allowed = get_skin_dir($def['skin_gubun']);
        $allowed_theme = array();
        if ((is_array($allowed) && in_array($skin_dir, $allowed, true)) || in_array($skin_dir, $allowed_theme, true)) {
            $save = 'skin:'.$skin_dir;
        }
    }
} else {
    $pd = (int)$value;
    if ($pd <= 0) $pd = 1;
    if (!empty($def['table_key']) && !empty($g5[$def['table_key']])) {
        $tbl = $g5[$def['table_key']];
        $exist = sql_fetch("SELECT pd_id FROM `{$tbl}` WHERE pd_id = '{$pd}'", false);
        if (empty($exist['pd_id'])) $pd = 1;
    }
    $save = (string)$pd;
}

$save_e = sql_escape_string($save);
sql_query("UPDATE `{$g5['theme_preset_table']}` SET `{$col}` = '{$save_e}' WHERE pr_id = 1 LIMIT 1", false);

// 복귀: REFERER 우선, 없으면 해당 디자인 타입의 프리셋 목록
$back = '';
if (!empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], G5_ADMIN_URL) === 0) {
    $back = $_SERVER['HTTP_REFERER'];
}
if ($back === '') {
    switch ($type) {
        case 'main':    $back = './design_preset_list.php';  break;
        case 'world':   $back = './world_preset_list.php';   break;
        case 'content': $back = './content_preset_list.php'; break;
        default:        $back = './theme_preset_list.php';
    }
}
goto_url($back);
?>
