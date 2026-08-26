<?php
/**
 * 타임라인 계정 전환 (세션)
 * 대표 캐릭터(member.ch_id)는 변경하지 않음
 *
 * POST: tl_table, ch_id
 */
require_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');

$result = array('success' => false, 'message' => '');

if (!$is_member) {
    $result['message'] = '로그인이 필요합니다.';
    echo json_encode($result);
    exit;
}

$mb_id = ses($member, 'mb_id', '');
$ch_id = ses($_POST, 'ch_id', 0, 'int');

if (!$tl_table) {
    $result['message'] = '타임라인이 지정되지 않았습니다.';
    echo json_encode($result);
    exit;
}

if (!$ch_id) {
    $result['message'] = '계정을 선택해주세요.';
    echo json_encode($result);
    exit;
}

if (!is_my_timeline_user($tl_table, $ch_id, $mb_id)) {
    $result['message'] = '전환할 수 없는 계정입니다.';
    echo json_encode($result);
    exit;
}

set_timeline_session_ch_id($tl_table, $ch_id);

echo json_encode(array(
    'success' => true,
    'ch_id' => $ch_id,
));
