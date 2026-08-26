<?php
if (!defined('_GNUBOARD_')) exit;

$sub_menu = function_exists('mdl_admin_menu_code') ? mdl_admin_menu_code('bbs_action_map', '202') : '003202';

require_once dirname(__DIR__).'/lib.php';
if (!function_exists('mdl_db_column_exists') && defined('G5_PATH') && is_file(G5_PATH.'/module/_db.php')) {
    include_once G5_PATH.'/module/_db.php';
}

$sfl = isset($_POST['sfl']) ? trim($_POST['sfl']) : 'ch_name';
$stx = isset($_POST['stx']) ? trim($_POST['stx']) : '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;

if (isset($_POST['act_button']) && $_POST['act_button'] === '선택수정' && !empty($_POST['chk']) && is_array($_POST['chk'])) {
    foreach ($_POST['chk'] as $k) {
        $k = (int)$k;
        $ch_id = isset($_POST['ch_id'][$k]) ? (int)$_POST['ch_id'][$k] : 0;
        $ma_id = isset($_POST['ma_id'][$k]) ? (int)$_POST['ma_id'][$k] : 0;
        if ($ch_id <= 0) continue;
        bbs_action_map_set_char_ma_id($ch_id, $ma_id);
    }
}

$q = http_build_query(array(
    'm' => 'bbs_action_map',
    'p' => 'member_list',
    'sfl' => $sfl,
    'stx' => $stx,
    'page' => $page,
));
goto_url(G5_MDL_ADMIN_URL.'/module.php?'.$q);
