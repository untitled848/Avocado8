<?php
/**
 * 통합 알림 폴링 엔드포인트
 * 미로그인이면 {"ok":false} 반환
 * 미확인 알림 최신 1건을 JSON으로 반환
 *
 * 응답 type 예: board | memo | present | notice | mention
 * (call_type_map.ct_name → type_name, 예: 멘션)
 */
include_once('../common.php');

header('Content-Type: application/json; charset=utf-8');

if (!$is_member) {
    echo json_encode(['ok' => false]);
    exit;
}

$row = function_exists('load_call') ? load_call($member['mb_id']) : null;
if ($row) {
    echo json_encode([
        'ok'        => true,
        'type'      => $row['bc_type'],
        'type_name' => $row['ct_name'] ?: $row['bc_type'],
        'sender'    => $row['mb_name'],
        'memo'      => $row['memo'],
        'link'      => $row['bc_link'],
        'bo_table'  => isset($row['bo_table']) ? $row['bo_table'] : '',
        'datetime'  => $row['bc_datetime'],
    ]);
} else {
    // 폴백: mb_memo_call=1 이지만 call_board에 미확인 행 없음 (구버전 데이터)
    $m = sql_fetch("SELECT mb_memo_call FROM {$g5['member_table']}
                    WHERE mb_id = '" . sql_escape_string($member['mb_id']) . "'");
    if ($m && $m['mb_memo_call']) {
        echo json_encode(['ok' => true, 'type' => 'legacy', 'type_name' => '알림', 'sender' => '', 'memo' => '새 알림이 있습니다.', 'link' => '']);
    } else {
        echo json_encode(['ok' => false]);
    }
}
