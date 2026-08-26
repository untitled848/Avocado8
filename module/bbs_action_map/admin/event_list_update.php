<?php
if (!defined('_GNUBOARD_')) exit;

$sub_menu = function_exists('mdl_admin_menu_code') ? mdl_admin_menu_code('bbs_action_map', '201') : '003201';

$ma_id = isset($_POST['ma_id']) ? (int)$_POST['ma_id'] : 0;
$act = isset($_POST['act_button']) ? $_POST['act_button'] : '';

if ($ma_id <= 0) {
    alert('지역정보가 없습니다.');
}

if (empty($_POST['chk']) || !is_array($_POST['chk'])) {
    alert($act.' 하실 항목을 하나 이상 체크하세요.');
}

$count = count($_POST['chk']);
$tbl = $g5['module_map_event_table'];

if ($act === '선택수정') {
    for ($i = 0; $i < $count; $i++) {
        $k = (int)$_POST['chk'][$i];
        $me_id = isset($_POST['me_id'][$k]) ? (int)$_POST['me_id'][$k] : 0;
        if ($me_id <= 0) continue;

        $me_type = isset($_POST['me_type'][$k]) ? bbs_action_map_esc_post($_POST['me_type'][$k]) : '';
        $me_title = isset($_POST['me_title'][$k]) ? bbs_action_map_esc_post($_POST['me_title'][$k]) : '';
        $me_get_money = isset($_POST['me_get_money'][$k]) ? (int)$_POST['me_get_money'][$k] : 0;
        $me_move_map = isset($_POST['me_move_map'][$k]) ? (int)$_POST['me_move_map'][$k] : 0;
        $me_mon_hp = isset($_POST['me_mon_hp'][$k]) ? (int)$_POST['me_mon_hp'][$k] : 0;
        $me_mon_attack = isset($_POST['me_mon_attack'][$k]) ? (int)$_POST['me_mon_attack'][$k] : 0;
        $me_replay_cnt = isset($_POST['me_replay_cnt'][$k]) ? (int)$_POST['me_replay_cnt'][$k] : 0;
        $me_now_cnt = isset($_POST['me_now_cnt'][$k]) ? (int)$_POST['me_now_cnt'][$k] : 0;
        $me_use = !empty($_POST['me_use'][$k]) ? 1 : 0;

        sql_query("UPDATE `{$tbl}` SET
            me_type = '{$me_type}',
            me_title = '{$me_title}',
            me_get_money = '{$me_get_money}',
            me_move_map = '{$me_move_map}',
            me_mon_hp = '{$me_mon_hp}',
            me_mon_attack = '{$me_mon_attack}',
            me_replay_cnt = '{$me_replay_cnt}',
            me_now_cnt = '{$me_now_cnt}',
            me_use = '{$me_use}'
            WHERE me_id = '{$me_id}' AND ma_id = '{$ma_id}'", false);
    }
} elseif ($act === '선택삭제') {
    for ($i = 0; $i < $count; $i++) {
        $k = (int)$_POST['chk'][$i];
        $me_id = isset($_POST['me_id'][$k]) ? (int)$_POST['me_id'][$k] : 0;
        if ($me_id <= 0) continue;
        sql_query("DELETE FROM `{$tbl}` WHERE me_id = '{$me_id}' AND ma_id = '{$ma_id}'", false);
    }
}

goto_url(G5_MDL_ADMIN_URL.'/module.php?m=bbs_action_map&p=event_list&ma_id='.$ma_id);
