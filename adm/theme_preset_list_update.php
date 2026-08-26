<?php
$sub_menu = "110200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');

if (!$is_admin)
    alert('관리자만 접근 가능합니다.');

$action = ses($_REQUEST, 'action', '', 'string');

if ($action === 'set_global') {
    $cf_pid = ses($_POST, 'cf_preset_id', 0, 'int');
    if ($cf_pid < 0) $cf_pid = 0;
    sql_query("UPDATE `{$g5['config_table']}` SET cf_preset_id = '{$cf_pid}'", false);
    goto_url('./theme_preset_list.php');
}

if ($action === 'create') {
    $name = ses($_POST, 'pr_name', '', 'string');
    if ($name === '') alert('이름을 입력하세요.');
    sql_query("INSERT INTO `{$g5['theme_preset_table']}` (pr_name, pr_design, pr_order) VALUES ('{$name}', 'default', '99')", false);
    $new_id = (int)sql_insert_id();
    goto_url('./theme_preset_form.php?pr_id='.$new_id);
}

if ($action === 'delete') {
    $pr_id = ses($_GET, 'pr_id', 0, 'int');
    if ($pr_id <= 1) alert('기본 프리셋은 삭제할 수 없습니다.');
    sql_query("DELETE FROM `{$g5['theme_preset_table']}` WHERE pr_id = '{$pr_id}'", false);
    sql_query("DELETE FROM `{$g5['theme_preset_slot_table']}` WHERE pr_id = '{$pr_id}'", false);
    // 시기에서 이 프리셋 사용중이었던 것 0으로
    sql_query("UPDATE `{$g5['time_table']}` SET ti_preset_id = '0' WHERE ti_preset_id = '{$pr_id}'", false);
    sql_query("UPDATE `{$g5['config_table']}` SET cf_preset_id = '0' WHERE cf_preset_id = '{$pr_id}'", false);
    goto_url('./theme_preset_list.php');
}

goto_url('./theme_preset_list.php');
