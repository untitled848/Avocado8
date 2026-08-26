<?php
/**
 * DM 방 생성 API
 */
require_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');

$my_ch_id = timeline_dm_ajax_require();

$participants = isset($_POST['participants']) && is_array($_POST['participants'])
    ? $_POST['participants']
    : array();

if (empty($participants)) {
    echo json_encode(array('success' => false, 'message' => '참가자를 선택해주세요.'));
    exit;
}

$participants = array_map('intval', $participants);
$room_name = ses($_POST, 'room_name', '', 'raw');

$result = create_dm_room($tl_table, $my_ch_id, $participants, $room_name);

echo json_encode($result);
