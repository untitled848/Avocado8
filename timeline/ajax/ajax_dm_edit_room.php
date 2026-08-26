<?php
/**
 * DM 방 제목 수정 API
 */
require_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');

$my_ch_id = timeline_dm_ajax_require();

$room_id = ses($_POST, 'room_id', 0, 'int');
if (!$room_id) {
    echo json_encode(array('success' => false, 'message' => '방 ID가 필요합니다.'));
    exit;
}

$room_name = ses($_POST, 'room_name', '', 'raw');
if (mb_strlen($room_name, 'UTF-8') > 50) {
    echo json_encode(array('success' => false, 'message' => '방 제목은 최대 50자까지 입력 가능합니다.'));
    exit;
}

$room = get_dm_room($tl_table, $room_id, $my_ch_id, ($is_admin));
if (!$room) {
    echo json_encode(array('success' => false, 'message' => '접근 권한이 없거나 존재하지 않는 방입니다.'));
    exit;
}

if (!$room['is_creator'] && !$is_admin) {
    echo json_encode(array('success' => false, 'message' => '방 제목 수정 권한이 없습니다.'));
    exit;
}

$tl_esc = sql_escape_string($tl_table);
$room_name_esc = sql_escape_string($room_name);
$sql = "UPDATE {$g5['timeline_dm_rooms']}
        SET room_name = '{$room_name_esc}'
        WHERE room_id = '{$room_id}'
        AND tl_table = '{$tl_esc}'";

$result = sql_query($sql);

if ($result) {
    echo json_encode(array(
        'success' => true,
        'message' => '방 제목이 변경되었습니다.',
        'room_name' => $room_name,
    ));
} else {
    echo json_encode(array('success' => false, 'message' => '방 제목 변경에 실패했습니다.'));
}
