<?php
/**
 * 타임라인 댓글(답글) 목록 AJAX
 * 
 * 새 멘션 타래 시스템:
 * - wr_mention_root로 그룹핑하여 표시
 * - 같은 타래는 연속으로 표시
 * 
 * GET/POST 파라미터:
 * - tl_table: 타임라인 테이블명
 * - wr_id: 원글 ID
 * - mention_root: 특정 멘션 타래만 로드 (선택)
 * - last_id: 마지막 댓글 ID (무한스크롤용)
 * - limit: 가져올 개수 (기본 20)
 */
include_once(__DIR__.'/../_common.php');

$my_ch_id = ses($character, 'ch_id', 0, 'int');

// JSON 응답
header('Content-Type: application/json');

$result = array('success' => false, 'message' => '', 'comments' => array(), 'has_more' => false, 'threads' => array());

// 파라미터
$wr_id = ses($_REQUEST, 'wr_id', 0, 'int');
$mention_root = ses($_REQUEST, 'mention_root', -1, 'int'); // -1 = 전체, 0 이상 = 특정 타래
$last_id = ses($_REQUEST, 'last_id', 0, 'int');
$limit = ses($_REQUEST, 'limit', 20, 'int');
$limit = min($limit, 50); // 최대 50개

if (!$wr_id) {
    $result['message'] = '원글 정보가 없습니다.';
    echo json_encode($result);
    exit;
}

// 원글 확인
$write = sql_fetch("SELECT wr_id FROM {$write_table} WHERE wr_id = '{$wr_id}' AND wr_deleted = 0");
if (!$write) {
    $result['message'] = '원글을 찾을 수 없습니다.';
    echo json_encode($result);
    exit;
}

// 댓글 목록 조회 (wr_parent = 원글 ID)
$where = "w.wr_parent = '{$wr_id}' AND w.wr_deleted = 0";

// 특정 멘션 타래만 로드
if ($mention_root >= 0) {
    $where .= " AND w.wr_mention_root = '{$mention_root}'";
}

if ($last_id > 0) {
    $where .= " AND w.wr_id > '{$last_id}'";
}

// 멘션 타래별로 그룹핑하여 정렬 (같은 타래 연속 표시)
$sql = "
    SELECT w.*, c.ch_name, c.ch_thumb,
           (SELECT wr_name FROM {$write_table} WHERE wr_id = w.wr_reply_to) AS reply_to_name
    FROM {$write_table} w
    LEFT JOIN {$g5['character_table']} c ON c.ch_id = w.ch_id
    WHERE {$where}
    ORDER BY w.wr_mention_root ASC, w.wr_datetime ASC
    LIMIT ".($limit + 1);

$res = sql_query($sql);

$comments = array();
$threads = array(); // 멘션 타래별 첫 댓글 ID
$count = 0;
$current_root = -1;

while ($row = sql_fetch_array($res)) {
    $count++;
    if ($count > $limit) {
        $result['has_more'] = true;
        break;
    }
    
    // 새 멘션 타래 시작
    $root = (int)$row['wr_mention_root'];
    if ($root != $current_root) {
        $threads[$root] = $row['wr_id'];
        $current_root = $root;
    }
    
    // 권한 설정
    $is_mine = ($row['ch_id'] == $my_ch_id);
    $can_edit = $is_mine || ($is_admin);
    $can_delete = $can_edit && (empty($row['wr_log']) || $is_admin);
    
    $comments[] = array(
        'wr_id' => $row['wr_id'],
        'ch_id' => $row['ch_id'],
        'ch_name' => get_timeline_row_name($row),
        'ch_image' => get_timeline_row_thumb($row),
        'wr_content' => $row['wr_content'],
        'wr_datetime' => $row['wr_datetime'],
        'wr_log' => $row['wr_log'],
        'wr_item_log' => $row['wr_item_log'],
        'wr_dice1' => $row['wr_dice1'],
        'wr_dice2' => $row['wr_dice2'],
        'wr_mention_root' => $root,
        'wr_reply_to' => $row['wr_reply_to'],
        'reply_to_name' => $row['reply_to_name'],
        'is_mine' => $is_mine,
        'can_edit' => $can_edit,
        'can_delete' => $can_delete,
        'time_ago' => get_time_ago($row['wr_datetime'])
    );
}

$result['success'] = true;
$result['comments'] = $comments;
$result['threads'] = $threads;
$result['parent_id'] = $wr_id;

echo json_encode($result);
