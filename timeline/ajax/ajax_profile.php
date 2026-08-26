<?php
/**
 * 타임라인 프로필 AJAX
 * 게시판 호환 구조 (wr_* 컬럼 사용)
 */
include_once(__DIR__.'/../_common.php');

header('Content-Type: application/json');

$profile_ch_id = ses($_GET, 'ch_id', 0, 'int');
$tab = ses($_GET, 'tab', 'posts', 'raw');
$last_id = ses($_GET, 'last_id', 0, 'int');
$limit = 20;

if (!$profile_ch_id) {
    echo json_encode(array('success' => false, 'message' => '잘못된 요청입니다.'));
    exit;
}

$my_ch_id = ses($character, 'ch_id', 0, 'int');
$is_mine = ($profile_ch_id == $my_ch_id);

// WHERE 조건 생성
$where = "w.wr_deleted = 0";
$last_cond = $last_id ? "AND w.wr_id < '{$last_id}'" : "";

switch ($tab) {
    case 'posts':
        // 해당 캐릭터의 원글만
        $where .= " AND w.ch_id = '{$profile_ch_id}' AND w.wr_parent = 0";
        break;
        
    case 'replies':
        // 해당 캐릭터의 답글만
        $where .= " AND w.ch_id = '{$profile_ch_id}' AND w.wr_parent > 0";
        break;
        
    case 'likes':
        // 본인만 볼 수 있음
        if (!$is_mine) {
            echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
            exit;
        }
        // 좋아요한 글 목록
        $result = sql_query("
            SELECT w.*, c.ch_name, c.ch_image,
                   (SELECT COUNT(*) FROM {$g5['timeline_good_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$my_ch_id}') as my_like,
                   (SELECT COUNT(*) FROM {$g5['timeline_bookmark_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$my_ch_id}') as my_bookmark
            FROM {$g5['timeline_good_table']} tll
            INNER JOIN {$write_table} w ON w.wr_id = tll.wr_id
            INNER JOIN {$g5['character_table']} c ON c.ch_id = w.ch_id
            WHERE tll.tl_table = '{$tl_table}'
            AND tll.ch_id = '{$profile_ch_id}'
            AND w.wr_deleted = 0
            ".($last_id ? "AND tll.tl_good_id < '{$last_id}'" : "")."
            ORDER BY tll.tl_good_id DESC
            LIMIT {$limit}
        ");
        
        $html = '';
        $new_last_id = 0;
        $count = 0;
        
        while ($post = sql_fetch_array($result)) {
            $post['latest_reply'] = null;
            $new_last_id = $post['wr_id'];
            $count++;
            ob_start();
            include(__DIR__.'/../_item.php');
            $html .= ob_get_clean();
        }
        
        echo json_encode(array(
            'success' => true,
            'html' => $html,
            'last_id' => $new_last_id,
            'has_more' => $count >= $limit
        ));
        exit;
}

// posts/replies 조회
$result = sql_query("
    SELECT w.*, c.ch_name, c.ch_image,
           (SELECT COUNT(*) FROM {$g5['timeline_good_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$my_ch_id}') as my_like,
           (SELECT COUNT(*) FROM {$g5['timeline_bookmark_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$my_ch_id}') as my_bookmark
    FROM {$write_table} w
    INNER JOIN {$g5['character_table']} c ON c.ch_id = w.ch_id
    WHERE {$where}
    {$last_cond}
    ORDER BY w.wr_id DESC
    LIMIT {$limit}
");

$html = '';
$new_last_id = 0;
$count = 0;

while ($post = sql_fetch_array($result)) {
    // 최신 답글 조회 (원글인 경우만)
    if ($tab == 'posts' && $post['wr_comment'] > 0) {
        $post['latest_reply'] = sql_fetch("
            SELECT w.*, c.ch_name
            FROM {$write_table} w
            INNER JOIN {$g5['character_table']} c ON c.ch_id = w.ch_id
            WHERE w.wr_parent = '{$post['wr_id']}'
            AND w.wr_deleted = 0
            ORDER BY w.wr_datetime DESC
            LIMIT 1
        ");
    } else {
        $post['latest_reply'] = null;
    }
    
    $new_last_id = $post['wr_id'];
    $count++;
    ob_start();
    include(__DIR__.'/../_item.php');
    $html .= ob_get_clean();
}

echo json_encode(array(
    'success' => true,
    'html' => $html,
    'last_id' => $new_last_id,
    'has_more' => $count >= $limit
));
