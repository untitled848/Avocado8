<?php
/**
 * DM 새 메시지 로드 API (폴링용)
 */
require_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');

$my_ch_id = timeline_dm_ajax_require();

$room_id = ses($_GET, 'room_id', 0, 'int');
$after_msg_id = ses($_GET, 'after', 0, 'int');

if (!$room_id) {
    echo json_encode(array('success' => false, 'message' => '잘못된 요청입니다.'));
    exit;
}

$room = get_dm_room($tl_table, $room_id, $my_ch_id, ($is_admin));
if (!$room) {
    echo json_encode(array('success' => false, 'message' => '잘못된 접근입니다.'));
    exit;
}

$messages = get_dm_messages($tl_table, $room_id, $my_ch_id, 100, 0, $after_msg_id, ($is_admin));

$out = array();
foreach ($messages as $row) {
    $out[] = array(
        'msg_id' => (int)ses($row, 'msg_id', 0, 'int'),
        'ch_id' => (int)ses($row, 'ch_id', 0, 'int'),
        'ch_name' => ses($row, 'ch_name', '', 'raw'),
        'content' => ses($row, 'content', '', 'raw'),
        'avatar' => ses($row, 'avatar', '', 'raw'),
        'is_mine' => !empty($row['is_mine']),
        'created_at' => ses($row, 'created_at', '', 'raw'),
    );
}

echo json_encode(array(
    'success' => true,
    'messages' => $out,
));
