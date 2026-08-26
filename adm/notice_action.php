<?php
$sub_menu = "310200";
include_once('./_common.php');

check_admin_token();

$act = trim($_POST['act'] ?? '');
$chk = isset($_POST['chk']) && is_array($_POST['chk']) ? $_POST['chk'] : array();

$ids = array();
foreach ($chk as $v) {
    $n = (int)$v;
    if ($n > 0) $ids[] = $n;
}
if (!$ids) alert('항목을 선택하세요.');

$ids_in = implode(',', $ids);
$cnt    = 0;

switch ($act) {
    case 'use_on':
        sql_query("UPDATE `{$g5['notice_table']}` SET no_use = 1 WHERE no_id IN ({$ids_in})", false);
        $cnt = count($ids);
        break;
    case 'use_off':
        sql_query("UPDATE `{$g5['notice_table']}` SET no_use = 0 WHERE no_id IN ({$ids_in})", false);
        $cnt = count($ids);
        break;
    case 'pin_on':
        sql_query("UPDATE `{$g5['notice_table']}` SET no_pinned = 1 WHERE no_id IN ({$ids_in})", false);
        $cnt = count($ids);
        break;
    case 'pin_off':
        sql_query("UPDATE `{$g5['notice_table']}` SET no_pinned = 0 WHERE no_id IN ({$ids_in})", false);
        $cnt = count($ids);
        break;
    case 'delete':
        sql_query("DELETE FROM `{$g5['notice_table']}` WHERE no_id IN ({$ids_in})", false);
        $cnt = count($ids);
        break;
    default:
        alert('알 수 없는 작업입니다.');
}

alert($cnt.'건 처리되었습니다.', './notice_list.php');
?>
