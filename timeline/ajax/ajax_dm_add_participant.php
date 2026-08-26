<?php
/**
 * DM 방 참가자 추가 API
 */
require_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');

$my_ch_id = timeline_dm_ajax_require();

$room_id = ses($_POST, 'room_id', 0, 'int');
if (!$room_id) {
    echo json_encode(array('success' => false, 'message' => '방 ID가 필요합니다.'));
    exit;
}

$participants = isset($_POST['participants']) && is_array($_POST['participants'])
    ? $_POST['participants']
    : array();

if (empty($participants)) {
    echo json_encode(array('success' => false, 'message' => '초대할 참가자를 선택해주세요.'));
    exit;
}

$participants = array_map('intval', $participants);

$room = get_dm_room($tl_table, $room_id, $my_ch_id, ($is_admin));
if (!$room) {
    echo json_encode(array('success' => false, 'message' => '접근 권한이 없거나 존재하지 않는 방입니다.'));
    exit;
}

if (!$room['is_creator'] && !$is_admin) {
    echo json_encode(array('success' => false, 'message' => '참가자 초대 권한이 없습니다.'));
    exit;
}

$result = add_dm_participant($room_id, $my_ch_id, $participants, ($is_admin));
echo json_encode($result);
