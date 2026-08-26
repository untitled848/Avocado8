<?php
$sub_menu = "100500";
include_once('./_common.php');

check_admin_token();

$act   = $_REQUEST['act']   ?? '';
$ct_id = intval($_REQUEST['ct_id'] ?? 0);

if ($act === 'delete') {
    if (!$ct_id) goto_url('./call_type_list.php', false);
    $row = sql_fetch("SELECT ct_system FROM `{$g5['call_type_map_table']}` WHERE ct_id = '{$ct_id}'");
    if ($row['ct_system']) alert('시스템 기본 종류는 삭제할 수 없습니다.', './call_type_list.php');
    sql_query("DELETE FROM `{$g5['call_type_map_table']}` WHERE ct_id = '{$ct_id}'");
    goto_url('./call_type_list.php', false);
}

// insert / update
$ct_code = preg_replace('/[^a-z0-9_]/', '', strtolower($_POST['ct_code'] ?? ''));
$ct_name = sql_escape_string(trim($_POST['ct_name'] ?? ''));
$ct_memo = sql_escape_string(trim($_POST['ct_memo'] ?? ''));
$ct_link = sql_escape_string(trim($_POST['ct_link'] ?? ''));
$ct_use  = isset($_POST['ct_use']) ? 1 : 0;

if ($ct_code === '') alert('코드를 입력해 주세요.');
if ($ct_name === '') alert('이름을 입력해 주세요.');

if ($act === 'update' && $ct_id) {
    $row = sql_fetch("SELECT ct_system FROM `{$g5['call_type_map_table']}` WHERE ct_id = '{$ct_id}'");
    if ($row['ct_system']) {
        // 시스템 종류: 코드/ct_system 변경 불가
        sql_query("UPDATE `{$g5['call_type_map_table']}`
            SET ct_name = '{$ct_name}', ct_memo = '{$ct_memo}', ct_link = '{$ct_link}', ct_use = '{$ct_use}'
            WHERE ct_id = '{$ct_id}'");
    } else {
        $ct_code_esc = sql_escape_string($ct_code);
        sql_query("UPDATE `{$g5['call_type_map_table']}`
            SET ct_code = '{$ct_code_esc}', ct_name = '{$ct_name}', ct_memo = '{$ct_memo}',
                ct_link = '{$ct_link}', ct_use = '{$ct_use}'
            WHERE ct_id = '{$ct_id}'");
    }
} else {
    $ct_code_esc = sql_escape_string($ct_code);
    $dup = sql_fetch("SELECT ct_id FROM `{$g5['call_type_map_table']}` WHERE ct_code = '{$ct_code_esc}'");
    if ($dup) alert('이미 존재하는 코드입니다. (' . htmlspecialchars($ct_code) . ')', './call_type_list.php');
    sql_query("INSERT INTO `{$g5['call_type_map_table']}`
        SET ct_code = '{$ct_code_esc}', ct_name = '{$ct_name}', ct_memo = '{$ct_memo}',
            ct_link = '{$ct_link}', ct_use = '{$ct_use}', ct_system = 0");
}

goto_url('./call_type_list.php', false);
