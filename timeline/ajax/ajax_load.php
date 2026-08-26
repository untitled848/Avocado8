<?php
/**
 * 타임라인 목록 로드 AJAX
 * 게시판 호환 구조
 */

require_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');

$my_ch_id = ses($character, 'ch_id', 0, 'int');
if (!$is_member || !$my_ch_id) {
    echo json_encode(array('success' => false, 'html' => '', 'has_more' => false));
    exit;
}

$tab = ses($_GET, 'tab', 'all', 'raw');
$last_id = ses($_GET, 'last_id', 0, 'int');
$limit = ses($_GET, 'limit', 20, 'int');
$target_ch_id = ses($_GET, 'ch_id', 0, 'int');
$stx = ses($_GET, 'stx', '', 'raw'); // 검색어

// timeline_user 렌더링 모드 (thread/normal) — 기본 normal
$render_mode = get_timeline_user_render_mode($tl_table, $my_ch_id);

// 유효한 탭인지 확인
$valid_tabs = array('all', 'public', 'mentions', 'bookmarks', 'profile', 'profile_public', 'profile_replies', 'profile_media');
if (!in_array($tab, $valid_tabs)) {
    $tab = 'all';
}
// 프로필 전체 탭 별칭
if ($tab === 'profile_replies') {
    $tab = 'profile';
}

// 멘션 탭 진입 시 읽음 처리 (첫 페이지 로드만 — 무한스크롤은 last_id > 0)
if ($tab === 'mentions' && $last_id <= 0 && function_exists('mark_timeline_mentions_read')) {
    mark_timeline_mentions_read($tl_table, $my_ch_id);
}

// 목록 조회
// 프로필 퍼블릭·미디어는 해당 글 자체를 보이므로 thread 확장 불필요
if ($tab === 'profile_public' || $tab === 'public' || $tab === 'profile_media') {
    $render_mode = 'normal';
}

$options = array(
    'last_id' => $last_id,
    'limit' => $limit + 1, // 더 있는지 확인용
    'target_ch_id' => $target_ch_id,
    'render_mode' => $render_mode,
    'stx' => $stx // 검색어
);

$posts = get_timeline_posts($tl_table, $tab, $my_ch_id, $options);

// has_more 체크
$has_more = count($posts) > $limit;
if ($has_more) {
    array_pop($posts);
}

// HTML 렌더링
$html = '';
$new_last_id = 0;

if (count($posts) > 0) {
    foreach ($posts as $row) {
        $new_last_id = $row['wr_id'];
        $html .= render_timeline_item($row, $tl_table, $my_ch_id, $timeline_skin_path, $render_mode);
    }
}

echo json_encode(array(
    'success' => true,
    'html' => $html,
    'has_more' => $has_more,
    'last_id' => $new_last_id,
    'count' => count($posts),
    'server_time' => (int)G5_SERVER_TIME
));


/**
 * 타임라인 아이템 렌더링 (outlogin 스타일)
 * 
 * 렌더링 모드:
 * - thread: 댓글(멘션)인 경우 원글(origin) + 커넥터 + 댓글 출력
 * - normal: 원글/커넥터 없이 해당 글만 출력
 * 
 * @param array $row 글 데이터
 * @param string $tl_table 타임라인 테이블명
 * @param int $my_ch_id 현재 캐릭터 ID
 * @param string $skin_path 스킨 경로
 * @param string $render_mode 렌더링 모드 (thread/normal)
 * @return string HTML
 */
function render_timeline_item($row, $tl_table, $my_ch_id, $skin_path, $render_mode = 'normal') {
    global $g5, $is_admin, $character, $write_table, $timeline_skin_url;
    
    $html = '';
    $is_comment = ($row['wr_parent'] > 0);
    
    // 댓글인 경우: thread 모드일 때만 원글을 먼저 렌더링
    if ($is_comment && $render_mode === 'thread') {
        $origin_id = (int)$row['wr_parent'];
        $origin = sql_fetch("
            SELECT w.*, c.ch_name, c.ch_thumb,
                (SELECT COUNT(*) FROM {$g5['timeline_good_table']} WHERE tl_table='{$tl_table}' AND wr_id=w.wr_id AND ch_id='{$my_ch_id}') as my_like,
                (SELECT COUNT(*) FROM {$g5['timeline_bookmark_table']} WHERE tl_table='{$tl_table}' AND wr_id=w.wr_id AND ch_id='{$my_ch_id}') as my_bookmark
            FROM {$write_table} w
            LEFT JOIN {$g5['character_table']} c ON c.ch_id = w.ch_id
            WHERE w.wr_id = '{$origin_id}'
        ");
        
        if ($origin && $origin['wr_id']) {
            // 원글 축약 스킨 (item.origin.skin.php)
            $html .= _render_timeline_skin($origin, $tl_table, $my_ch_id, $skin_path, 'item.origin.skin.php');
            
            // 연결선
            $html .= '<div class="tl-thread-connector"></div>';
        }
    }
    
    // 현재 글 렌더링 (item.skin.php)
    $html .= _render_timeline_skin($row, $tl_table, $my_ch_id, $skin_path, 'item.skin.php', $is_comment);
    
    return $html;
}


/**
 * 스킨 파일 include 후 HTML 반환 (outlogin 스타일)
 * 
 * @param array $row 글 데이터
 * @param string $tl_table 타임라인 테이블명
 * @param int $my_ch_id 현재 캐릭터 ID
 * @param string $skin_path 스킨 경로
 * @param string $skin_file 스킨 파일명
 * @param bool $is_reply 댓글 여부
 * @return string HTML
 */
function _render_timeline_skin($row, $tl_table, $my_ch_id, $skin_path, $skin_file, $is_reply = false) {
    global $g5, $is_admin, $character, $write_table, $timeline_skin_url, $timeline_skin_path, $bo_table, $board_skin_path, $board_skin_url, $timeline;
    
    // 스킨 파일이 없으면 기본 렌더링
    if (!file_exists($skin_path.'/'.$skin_file)) {
      
        ob_start();
        include($skin_path.'/item.skin.php');
        return ob_get_clean();
        
        return '';
    }
    
    // 스킨에서 사용할 변수들 준비
    $wr_id = $row['wr_id'];
    $ch_id = (int)ses($row, 'ch_id', 0, 'int');
    $ch_name = h(get_timeline_row_name($row));
    $ch_image = get_timeline_row_thumb($row);
    $is_script = function_exists('timeline_row_is_script') ? timeline_row_is_script($row) : !empty($row['wr_script']);
    $wr_script = $is_script ? 1 : 0;
    $content = render_timeline_content(ses($row, 'wr_content', '', 'raw'), $tl_table, $wr_script);
    $content_raw = ses($row, 'wr_content', '', 'raw');
    $time_ago = get_time_ago($row['wr_datetime']);
    $datetime = $row['wr_datetime'];
    
    $my_like = !empty($row['my_like']);
    $my_bookmark = !empty($row['my_bookmark']);
    $my_reply = !empty($row['my_reply']);
    $is_mine = ($row['ch_id'] == $my_ch_id);
    
    $like_count = (int)ses($row, 'wr_like_count', 0, 'int');
    $bookmark_count = (int)ses($row, 'wr_bookmark_count', 0, 'int');
    $comment_count = (int)ses($row, 'wr_comment', 0, 'int');
    
    $wr_log = $is_script ? '' : ses($row, 'wr_log', '', 'raw');
    $wr_item_log = $is_script ? '' : ses($row, 'wr_item_log', '', 'raw');
    $wr_dice = $is_script ? '' : ses($row, 'wr_dice', '', 'raw');
    $wr_dice1 = $is_script ? 0 : ses($row, 'wr_dice1', 0, 'int');
    $wr_dice2 = $is_script ? 0 : ses($row, 'wr_dice2', 0, 'int');
    // $wr_script 는 위에서 1/0 으로 설정됨
    $wr_reply_to = ses($row, 'wr_reply_to', 0, 'int');
    $wr_parent = ses($row, 'wr_parent', 0, 'int');
    
    // 이미지 배열
    $images = array();
    for ($i = 1; $i <= 4; $i++) {
        $img = ses($row, 'wr_image'.$i, '', 'raw');
        if ($img && file_exists(G5_TIMELINE_PATH.'/'.$tl_table.'/'.$img)) {
            $images[] = G5_TIMELINE_URL.'/'.$tl_table.'/'.$img;
        }
    }
    
    // 프로필 URL
    $profile_url = G5_URL.'/timeline/profile.php?tl_table='.$tl_table.'&ch_id='.$ch_id;
    
    // 상세보기 URL
    $view_url = G5_URL.'/timeline/view.php?tl_table='.$tl_table.'&wr_id='.$wr_id;
    
    // openPost 대상 ID
    // 멘션 탭에서는 클릭한 글 ID 그대로 사용 (스레드 표시를 위해)
    // 일반 탭에서는 댓글이면 원글 ID 사용
    $open_post_id = $wr_id;  // 기본값: 클릭한 글 ID 그대로
    
    ob_start();
    include($skin_path.'/'.$skin_file);
    return ob_get_clean();
}
