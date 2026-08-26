<?php
/**
 * 멤버 알림 소리 설정 저장/조회
 * POST: call_sound=1|0
 * GET : 현재 설정 JSON
 */
include_once('../common.php');

header('Content-Type: application/json; charset=utf-8');

if (!$is_member) {
    echo json_encode(array('ok' => false, 'message' => '로그인이 필요합니다.'));
    exit;
}

$mb_id = $member['mb_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sound = isset($_POST['call_sound']) ? $_POST['call_sound'] : '0';
    $enabled = ($sound === '1' || $sound === 1 || $sound === true || $sound === 'true' || $sound === 'on');
    $ok = function_exists('save_member_call_pref')
        ? save_member_call_pref($mb_id, array('call_sound' => $enabled))
        : false;
    echo json_encode(array(
        'ok' => (bool)$ok,
        'call_sound' => $enabled,
        'message' => $ok ? '저장되었습니다.' : '저장에 실패했습니다.',
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

$pref = function_exists('get_member_call_pref')
    ? get_member_call_pref($mb_id)
    : array('call_sound' => true);

echo json_encode(array(
    'ok' => true,
    'call_sound' => !empty($pref['call_sound']),
), JSON_UNESCAPED_UNICODE);
