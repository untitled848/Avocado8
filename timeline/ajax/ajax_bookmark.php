<?php

require_once(__DIR__.'/../_common.php');

$my_ch_id = ses($character, 'ch_id', 0, 'int');

header('Content-Type: application/json; charset=utf-8');

if (!$is_member || !$my_ch_id) {
    echo json_encode(array('success' => false, 'message' => '로그인이 필요합니다.'));
    exit;
}

$wr_id = ses($_POST, 'wr_id', 0, 'int');

// 기존 tl_id 호환
if (!$wr_id) {
    $wr_id = ses($_POST, 'tl_id', 0, 'int');
}

if (!$wr_id) {
    echo json_encode(array('success' => false, 'message' => '잘못된 요청입니다.'));
    exit;
}

$result = toggle_timeline_bookmark($tl_table, $wr_id, $my_ch_id);

echo json_encode($result);
