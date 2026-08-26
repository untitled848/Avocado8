<?php
/**
 * DM 메시지 전송 API
 */
require_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');

$my_ch_id = timeline_dm_ajax_require();

$room_id = ses($_POST, 'room_id', 0, 'int');
$content = ses($_POST, 'content', '', 'raw');

if (!$room_id) {
    echo json_encode(array('success' => false, 'message' => '잘못된 요청입니다.'));
    exit;
}

$room = get_dm_room($tl_table, $room_id, $my_ch_id, false);
if (!$room) {
    echo json_encode(array('success' => false, 'message' => '잘못된 접근입니다.'));
    exit;
}

$result = send_dm_message($tl_table, $room_id, $my_ch_id, $content);

echo json_encode($result);
