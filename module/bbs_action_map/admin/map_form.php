<?php
if(!defined('_GNUBOARD_'))exit;

$sub_menu = function_exists('mdl_admin_menu_code') ? mdl_admin_menu_code('bbs_action_map', '200') : '003200';

// 기본값 처리
$w      = isset($w) ? $w : '';
$ma_id  = isset($ma_id) ? $ma_id : '';
$sfl    = isset($sfl) ? $sfl : '';
$stx    = isset($stx) ? $stx : '';
$sst    = isset($sst) ? $sst : '';
$sod    = isset($sod) ? $sod : '';
$page   = isset($page) ? $page : '';

$html_title = '맵';
$required = "";
$readonly = "";
$ma = array();


// 생성
if ($w === '') {

    $html_title .= ' 생성';
    $required = 'required';
    $required_valid = 'alnum_';
    $sound_only = '<strong class="sound_only">필수</strong>';

// 수정
} else if ($w === 'u') {

    $html_title .= ' 수정';

    if ($ma_id === '') {
        alert('잘못된 접근입니다.');
    }

    $ma = sql_fetch("SELECT * FROM {$g5['module_map_table']} WHERE ma_id = '{$ma_id}'");

    if (!isset($ma['ma_id']) || !$ma['ma_id']) {
        alert('존재하지 않는 맵 입니다.');
    }

    $readonly = 'readonly';
}

$g5['title'] = $html_title;

$result_list=array($ma);

$departure_map_ids = array();
$result=sql_query("SELECT ma_id, ma_name FROM {$g5['module_map_table']} ORDER BY ma_id ASC");
while ($row = sql_fetch_array($result)) {
    $departure_map_ids[$row['ma_id']]=$row['ma_name'];
}



$cols=array(
    'ma_use' => array('width' => '60px', 'label' => '사용', 'type' => 'checkbox'),
    'ma_name' => array('width' => '100px', 'label' => '이름', 'type' => 'text'),
    'ma_thumb' => array('width' => '80px', 'label' => '썸네일', 'type' => 'file'),
    'ma_text' => array('width' => '', 'label' => '설명', 'type' => 'textarea'),
);

if(mdl_setting_get('bbs_action_map', 'map_image', '') !== '') {
    $cols['ma_x'] = array('width' => '60px', 'label' => 'X좌표', 'type' => 'number');
    $cols['ma_y'] = array('width' => '60px', 'label' => 'Y좌표', 'type' => 'number');
    $cols['ma_width'] = array('width' => '60px', 'label' => '너비', 'type' => 'number');
    $cols['ma_height'] = array('width' => '60px', 'label' => '높이', 'type' => 'number');
}

$cols['dpt_ma_ids'] = array('width' => '120px', 'label' => '통행', 'type' => 'checkbox_multiple' , 'options' => $departure_map_ids);

$type='form';
$form_name="map_form";
$form_action="?m=bbs_action_map&p=map_form_update";
$hidden_id='ma_id';

$list_hidden = array(
    'w'   => $w,
    'sfl' => $sfl,
    'stx' => $stx,
    'sst' => $sst,
    'sod' => $sod,
);

echo set_insert_form($type, $form_name, $form_action, $result_list, $list_hidden, $cols, $hidden_id, array('확인'));
