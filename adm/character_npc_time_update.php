<?php
$sub_menu = '400200';
include_once('./_common.php');
require_once G5_EXTEND_PATH.'/02_side_class_time.lib.php';

check_admin_token();

if (!function_exists('get_time_list')) alert('시기 시스템이 활성화되지 않았습니다.');

$post_m = isset($_POST['m']) && is_array($_POST['m']) ? $_POST['m'] : array();

// renewal/au/event 시기에 대해 매핑 갱신
$times = get_time_list(true, true, true);
foreach ($times as $t) {
    $tt = isset($t['ti_type']) ? $t['ti_type'] : 'renewal';
    if ($tt !== 'renewal' && $tt !== 'au' && $tt !== 'event') continue;
    $order = (int)$t['ti_order'];
    $ch_ids = isset($post_m[$order]) && is_array($post_m[$order]) ? array_map('intval', $post_m[$order]) : array();
    set_npc_time_mapping($order, $ch_ids);
}

goto_url('./character_list.php?ch_type=npc');
