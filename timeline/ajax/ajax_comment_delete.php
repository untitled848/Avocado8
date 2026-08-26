<?php
/**
 * 타임라인 댓글(답글) 삭제 AJAX
 * 
 * POST 파라미터:
 * - tl_table: 타임라인 테이블명
 * - comment_id: 댓글 ID
 */
include_once(__DIR__.'/../_common.php');

$my_ch_id = ses($character, 'ch_id', 0, 'int');

// JSON 응답
header('Content-Type: application/json');

$result = array('success' => false, 'message' => '');

// 로그인 체크
if (!$is_member || !$my_ch_id) {
    $result['message'] = '로그인이 필요합니다.';
    echo json_encode($result);
    exit;
}

// 파라미터
$comment_id = ses($_POST, 'comment_id', 0, 'int');

if (!$comment_id) {
    $result['message'] = '댓글 정보가 없습니다.';
    echo json_encode($result);
    exit;
}

// 댓글 확인
$comment = sql_fetch("SELECT * FROM {$write_table} WHERE wr_id = '{$comment_id}' AND wr_is_comment = 1 AND wr_deleted = 0");
if (!$comment) {
    $result['message'] = '댓글을 찾을 수 없습니다.';
    echo json_encode($result);
    exit;
}

// 권한 체크 (본인 또는 관리자)
if ($comment['ch_id'] != $my_ch_id && !$is_admin) {
    $result['message'] = '삭제 권한이 없습니다.';
    echo json_encode($result);
    exit;
}

// 액션 로그가 있으면 관리자만 삭제 가능
if ($comment['wr_log'] && !$is_admin) {
    $result['message'] = '액션 로그가 있는 댓글은 삭제할 수 없습니다.';
    echo json_encode($result);
    exit;
}

// 원글 정보
$parent_id = $comment['wr_parent'];

// 소프트 삭제
$time = G5_TIME_YMDHIS;
sql_query("
    UPDATE {$write_table}
    SET wr_deleted = 1,
        wr_last = '{$time}'
    WHERE wr_id = '{$comment_id}'
");

// 원글 댓글 수 감소
sql_query("
    UPDATE {$write_table}
    SET wr_comment = wr_comment - 1,
        wr_reply_count = wr_reply_count - 1
    WHERE wr_id = '{$parent_id}' AND wr_comment > 0
");

$result['success'] = true;
$result['message'] = '댓글이 삭제되었습니다.';
$result['parent_id'] = $parent_id;

echo json_encode($result);
