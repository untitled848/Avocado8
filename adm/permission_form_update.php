<?php
$sub_menu = "100600";
include_once('./_common.php');
check_admin_token();

// 1) config 일괄 저장
$fallback_url  = trim($_POST['cf_perm_fallback_url'] ?? '');
$scope         = (string)($_POST['cf_open_scope'] ?? 'all');
if (!in_array($scope, ['all','member','admin'], true)) $scope = 'all';
$cf_1          = isset($_POST['cf_1']) ? 1 : 0;
$cf_2          = isset($_POST['cf_2']) ? 1 : 0;
$cf_character_count = max(0, intval($_POST['cf_character_count'] ?? 0));
$cf_character_edit_admin_only = isset($_POST['cf_character_edit_admin_only']) && $_POST['cf_character_edit_admin_only'] == '1' ? 1 : 0;
$cf_perm_ready  = isset($_POST['cf_perm_ready'])  ? max(1, intval($_POST['cf_perm_ready']))  : 1;
$cf_perm_member = isset($_POST['cf_perm_member']) ? max(1, intval($_POST['cf_perm_member'])) : 1;
$cf_perm_memo   = isset($_POST['cf_perm_memo'])   ? max(1, intval($_POST['cf_perm_memo']))   : 1;
$cf_perm_shop   = isset($_POST['cf_perm_shop'])   ? max(1, intval($_POST['cf_perm_shop']))   : 1;

sql_query("UPDATE {$g5['config_table']} SET
    cf_perm_fallback_url = '".addslashes($fallback_url)."',
    cf_open_scope        = '".addslashes($scope)."',
    cf_1                 = {$cf_1},
    cf_2                 = {$cf_2},
    cf_character_count   = {$cf_character_count},
    cf_character_edit_admin_only = {$cf_character_edit_admin_only},
    cf_perm_ready        = {$cf_perm_ready},
    cf_perm_member       = {$cf_perm_member},
    cf_perm_memo         = {$cf_perm_memo},
    cf_perm_shop         = {$cf_perm_shop}");

// 2) 게시판 권한 일괄 저장 (전체)
$bo_list_level    = $_POST['bo_list_level'] ?? [];
$bo_read_level    = $_POST['bo_read_level'] ?? [];
$bo_write_level   = $_POST['bo_write_level'] ?? [];
$bo_comment_level = $_POST['bo_comment_level'] ?? [];
$bo_download_level= $_POST['bo_download_level'] ?? [];

foreach (array_keys($bo_list_level) as $bo_table) {
    $bo_table = preg_replace('/[^a-zA-Z0-9_]/', '', $bo_table);
    if ($bo_table === '') continue;

    $ll = max(1, min(10, intval($bo_list_level[$bo_table] ?? 1)));
    $rl = max(1, min(10, intval($bo_read_level[$bo_table] ?? 1)));
    $wl = max(1, min(10, intval($bo_write_level[$bo_table] ?? 1)));
    $cl = max(1, min(10, intval($bo_comment_level[$bo_table] ?? 1)));
    $dl = max(1, min(10, intval($bo_download_level[$bo_table] ?? 1)));

    sql_query("UPDATE {$g5['board_table']} SET
        bo_list_level     = {$ll},
        bo_read_level     = {$rl},
        bo_write_level    = {$wl},
        bo_comment_level  = {$cl},
        bo_download_level = {$dl}
        WHERE bo_table = '".addslashes($bo_table)."'", false);
}

goto_url('./permission_form.php?saved=1');
