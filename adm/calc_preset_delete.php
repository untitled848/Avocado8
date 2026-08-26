<?php
$sub_menu = "520020";
include_once('./_common.php');
check_admin_token();

$cp_id = isset($_GET['cp_id']) ? (int)$_GET['cp_id'] : 0;
if ($cp_id > 0) {
    sql_query("DELETE FROM `{$g5['calc_preset_table']}` WHERE cp_id='{$cp_id}'");
}
goto_url(G5_ADMIN_URL.'/calc_preset_list.php');
