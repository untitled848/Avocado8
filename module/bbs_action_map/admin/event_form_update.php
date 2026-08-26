<?php
if (!defined('_GNUBOARD_')) exit;

$sub_menu = function_exists('mdl_admin_menu_code') ? mdl_admin_menu_code('bbs_action_map', '201') : '003201';

$w = isset($_POST['w']) ? $_POST['w'] : '';
$me_id = isset($_POST['me_id']) ? (int)$_POST['me_id'] : 0;
$ma_id = isset($_POST['ma_id']) ? (int)$_POST['ma_id'] : 0;

if ($ma_id <= 0) alert('지역정보가 없습니다.');

$me_type = isset($_POST['me_type']) ? bbs_action_map_esc_post($_POST['me_type']) : '';
$me_title = isset($_POST['me_title']) ? bbs_action_map_esc_post($_POST['me_title']) : '';
$me_content = isset($_POST['me_content']) ? bbs_action_map_esc_post($_POST['me_content']) : '';
$me_get_item = isset($_POST['me_get_item']) ? (int)$_POST['me_get_item'] : 0;
if (!empty($_POST['me_get_item_direct'])) {
    $me_get_item = (int)$_POST['me_get_item_direct'];
}
// 이름만 있고 id가 없으면 조회
if ($me_get_item <= 0 && !empty($_POST['it_name']) && !empty($g5['item_table'])) {
    $itn = bbs_action_map_esc_post(trim($_POST['it_name']));
    $it = sql_fetch("SELECT it_id FROM `{$g5['item_table']}` WHERE it_name = '{$itn}' LIMIT 1", false);
    if (!empty($it['it_id'])) $me_get_item = (int)$it['it_id'];
}

$me_get_money = isset($_POST['me_get_money']) ? (int)$_POST['me_get_money'] : 0;
$me_move_map = isset($_POST['me_move_map']) ? (int)$_POST['me_move_map'] : 0;
$me_mon_hp = isset($_POST['me_mon_hp']) ? (int)$_POST['me_mon_hp'] : 0;
$me_mon_attack = isset($_POST['me_mon_attack']) ? (int)$_POST['me_mon_attack'] : 0;
$me_per_s = isset($_POST['me_per_s']) ? (int)$_POST['me_per_s'] : 0;
$me_per_e = isset($_POST['me_per_e']) ? (int)$_POST['me_per_e'] : 0;
$me_replay_cnt = isset($_POST['me_replay_cnt']) ? (int)$_POST['me_replay_cnt'] : 0;
$me_now_cnt = isset($_POST['me_now_cnt']) ? (int)$_POST['me_now_cnt'] : 0;
$me_use = !empty($_POST['me_use']) ? 1 : 0;

$me_img = isset($_POST['me_img']) ? trim(bbs_action_map_post_unslash($_POST['me_img'])) : '';
$map_data_dir = G5_PATH.'/module/bbs_action_map/data';
$map_data_url = G5_URL.'/module/bbs_action_map/data';
if (!is_dir($map_data_dir)) @mkdir($map_data_dir, 0755, true);

if (!empty($_FILES['me_img_file']['tmp_name']) && is_uploaded_file($_FILES['me_img_file']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['me_img_file']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, array('png','jpg','jpeg','gif','webp'), true)) {
        $fname = 'me_'.date('YmdHis').'_'.mt_rand(100,999).'.'.$ext;
        $dest = $map_data_dir.'/'.$fname;
        if (move_uploaded_file($_FILES['me_img_file']['tmp_name'], $dest)) {
            @chmod($dest, 0644);
            $me_img = $map_data_url.'/'.$fname;
        }
    }
}
$me_img_e = sql_real_escape_string($me_img);

$sql_common = "
    ma_id = '{$ma_id}',
    me_type = '{$me_type}',
    me_title = '{$me_title}',
    me_img = '{$me_img_e}',
    me_content = '{$me_content}',
    me_get_item = '{$me_get_item}',
    me_get_money = '{$me_get_money}',
    me_move_map = '{$me_move_map}',
    me_mon_hp = '{$me_mon_hp}',
    me_mon_attack = '{$me_mon_attack}',
    me_per_s = '{$me_per_s}',
    me_per_e = '{$me_per_e}',
    me_replay_cnt = '{$me_replay_cnt}',
    me_now_cnt = '{$me_now_cnt}',
    me_use = '{$me_use}'
";

$tbl = $g5['module_map_event_table'];
if ($w === 'u') {
    $ex = sql_fetch("SELECT me_id FROM `{$tbl}` WHERE me_id = '{$me_id}' AND ma_id = '{$ma_id}' LIMIT 1");
    if (empty($ex['me_id'])) alert('이벤트 정보가 존재하지 않습니다.');
    // 이미지 미변경 시 기존 유지
    if ($me_img === '') {
        sql_query("UPDATE `{$tbl}` SET
            ma_id = '{$ma_id}',
            me_type = '{$me_type}',
            me_title = '{$me_title}',
            me_content = '{$me_content}',
            me_get_item = '{$me_get_item}',
            me_get_money = '{$me_get_money}',
            me_move_map = '{$me_move_map}',
            me_mon_hp = '{$me_mon_hp}',
            me_mon_attack = '{$me_mon_attack}',
            me_per_s = '{$me_per_s}',
            me_per_e = '{$me_per_e}',
            me_replay_cnt = '{$me_replay_cnt}',
            me_now_cnt = '{$me_now_cnt}',
            me_use = '{$me_use}'
            WHERE me_id = '{$me_id}' AND ma_id = '{$ma_id}'", false);
    } else {
        sql_query("UPDATE `{$tbl}` SET {$sql_common} WHERE me_id = '{$me_id}' AND ma_id = '{$ma_id}'", false);
    }
} else {
    sql_query("INSERT INTO `{$tbl}` SET {$sql_common}", false);
}

goto_url(G5_MDL_ADMIN_URL.'/module.php?m=bbs_action_map&p=event_list&ma_id='.$ma_id);
