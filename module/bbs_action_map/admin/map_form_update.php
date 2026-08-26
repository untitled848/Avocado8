<?php
if(!defined('_GNUBOARD_'))exit;

// 맵 추가/수정 처리 — set_update_form(form) 으로 일원화
$w = $_POST['w'] ?? '';
if ($w === 'u') check_demo();
check_token();

// 검색 상태 유지용 qstr 재구성
$qstr = http_build_query(array(
    'sfl' => $_POST['sfl'] ?? '',
    'stx' => $_POST['stx'] ?? '',
    'sst' => $_POST['sst'] ?? '',
    'sod' => $_POST['sod'] ?? '',
));

// set_insert_form(form) 의 $cols 와 동일한 컬럼 정의 (출력 전용 html 컬럼은 불필요)
$cols = array(
    'ma_use'   => array('type' => 'checkbox'),
    'ma_name'  => array('type' => 'text'),
    'ma_thumb' => array('type' => 'file'),
    'ma_text'  => array('type' => 'textarea'),
);

if (mdl_setting_get('bbs_action_map', 'map_image', '') !== '') {
    $cols['ma_x']      = array('type' => 'number');
    $cols['ma_y']      = array('type' => 'number');
    $cols['ma_width']  = array('type' => 'number');
    $cols['ma_height'] = array('type' => 'number');
}

$cols['dpt_ma_ids'] = array('type' => 'checkbox_multiple');

set_update_form('form', $g5['module_map_table'], $cols, 'ma_id', array(
    'file_path' => G5_PATH . '/module/bbs_action_map/data',
    'file_url'  => G5_URL  . '/module/bbs_action_map/data',
    'redirect'  => '?m=bbs_action_map&p=map_list&' . $qstr,
));
