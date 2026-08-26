<?php
/**
 * ajax/notice_call_send.php — 공지 게시글 전체 알림 발송 (관리자 전용)
 *
 * POST: wr_id, bo_table, token
 */
include_once('../common.php');

header('Content-Type: application/json; charset=utf-8');

if (!$is_admin) {
    echo json_encode(['ok' => false, 'msg' => '권한 없음']);
    exit;
}

if (!check_token()) {
    echo json_encode(['ok' => false, 'msg' => '토큰 오류']);
    exit;
}

$bo_table = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['bo_table'] ?? 'notice');
$wr_id    = (int)($_POST['wr_id'] ?? 0);

if ($bo_table === '' || $wr_id <= 0) {
    echo json_encode(['ok' => false, 'msg' => '잘못된 요청']);
    exit;
}

$write_table = $g5['write_prefix'] . $bo_table;
$write = sql_fetch("SELECT wr_id, wr_subject FROM `{$write_table}` WHERE wr_id = {$wr_id}", false);
if (!$write) {
    echo json_encode(['ok' => false, 'msg' => '게시글 없음']);
    exit;
}

if (!function_exists('push_call')) {
    echo json_encode(['ok' => false, 'msg' => 'push_call 함수 없음']);
    exit;
}

// 전체 회원 수집
$res = sql_query("SELECT mb_id FROM {$g5['member_table']} WHERE mb_leave_date = '' AND mb_intercept_date = ''", false);
$ids = [];
while ($r = sql_fetch_array($res)) {
    $ids[] = $r['mb_id'];
}

if (empty($ids)) {
    echo json_encode(['ok' => true, 'count' => 0]);
    exit;
}

$count = push_call(
    'notice',
    ['mb_id' => $member['mb_id'], 'mb_name' => '관리자'],
    $ids,
    cut_str(strip_tags($write['wr_subject']), 50),
    G5_BBS_URL . '/board.php?bo_table=' . urlencode($bo_table) . '&wr_id=' . $wr_id,
    ['bo_table' => $bo_table, 'wr_id' => $wr_id]
);

echo json_encode(['ok' => true, 'count' => (int)$count]);
