<?php
/**
 * 댓글 수 조회 AJAX
 */
include_once(__DIR__.'/../_common.php');

header('Content-Type: application/json');

$result = array('success' => false, 'count' => 0);

$wr_id = ses($_GET, 'wr_id', 0, 'int');

if (!$wr_id) {
    echo json_encode($result);
    exit;
}

// 해당 글의 댓글 수 조회
$row = sql_fetch("SELECT wr_reply_count FROM {$write_table} WHERE wr_id = '{$wr_id}'");

if ($row) {
    $result['success'] = true;
    $result['count'] = (int)$row['wr_reply_count'];
}

echo json_encode($result);
