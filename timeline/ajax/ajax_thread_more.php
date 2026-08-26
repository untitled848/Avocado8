<?php
/**
 * 타임라인 스레드 더 보기 AJAX
 * wr_reply_to를 역추적하여 이전 멘션들을 가져옴
 *
 * 상세 뷰의 댓글과 동일하게 view_comment.php → item.comment.skin.php
 * → item.content.skin.php → bbs_render_row_extra 경로로 렌더한다.
 * (직접 HTML 조립 시 낚시 wr_fish_log 등 모듈 액션 로그가 빠짐)
 *
 * GET 파라미터:
 * - tl_table: 타임라인 테이블명
 * - from_wr_id: 기준 글 ID (이 글의 wr_reply_to부터 역추적)
 * - limit: 가져올 개수 (기본 5)
 * - all: 1이면 남은 대화를 한 번에 로드 (최대 500)
 */
include_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');

$from_wr_id = ses($_GET, 'from_wr_id', 0, 'int');
$load_all = ses($_GET, 'all', 0, 'int') ? 1 : 0;
$limit = ses($_GET, 'limit', 5, 'int');
if ($load_all) {
    $limit = 500;
} elseif ($limit < 1) {
    $limit = 5;
}
$my_ch_id = ses($character, 'ch_id', 0, 'int');

if (!$from_wr_id) {
    echo json_encode(array('success' => false, 'message' => '잘못된 요청입니다.'), JSON_UNESCAPED_UNICODE);
    exit;
}

$result = get_timeline_thread_more($tl_table, $from_wr_id, $my_ch_id, $limit);

$origin_id = 0;
if (!empty($result['replies'][0])) {
    $origin_id = (int)ses($result['replies'][0], 'wr_parent', 0, 'int');
}
if ($origin_id <= 0) {
    $from_row = sql_fetch("SELECT wr_parent FROM {$write_table} WHERE wr_id = '{$from_wr_id}'");
    $origin_id = (int)ses($from_row, 'wr_parent', 0, 'int');
}
if ($origin_id <= 0) {
    $origin_id = $from_wr_id;
}

$html = '';
if (!empty($result['replies']) && !empty($timeline_skin_path) && file_exists($timeline_skin_path.'/view_comment.php')) {
    $list_item = array(
        'wr_id'          => $origin_id,
        'is_thread_view' => true,
        'focused_id'     => 0,
        'replies'        => $result['replies'],
    );
    ob_start();
    include $timeline_skin_path.'/view_comment.php';
    $html = ob_get_clean();
} elseif (!empty($result['replies'])) {
    foreach ($result['replies'] as $reply) {
        $reply_profile = get_timeline_row_thumb($reply);
        $reply_content = render_timeline_content($reply['wr_content'], $tl_table);
        $reply_time = get_time_ago($reply['wr_datetime']);
        $has_liked = !empty($reply['my_like']) ? ' active' : '';
        $reply_name = get_timeline_row_name($reply);

        $reply_to_name = '';
        $reply_to_id = ses($reply, 'wr_reply_to', 0, 'int');
        if ($reply_to_id > 0) {
            $reply_to_info = sql_fetch("SELECT wr_name FROM {$write_table} WHERE wr_id = '{$reply_to_id}'");
            $reply_to_name = get_timeline_row_name($reply_to_info);
        }

        ob_start();
        if (function_exists('bbs_render_row_extra')) {
            bbs_render_row_extra($reply, array(
                'bo_table'        => $tl_table,
                'board_skin_url'  => isset($timeline_skin_url) ? $timeline_skin_url : '',
                'board_skin_path' => isset($timeline_skin_path) ? $timeline_skin_path : '',
                'scope'           => 'comment',
            ));
        }
        $extra_html = ob_get_clean();

        $_more_class = 'tl-item tl-item-comment tl-thread-continue';
        if (function_exists('timeline_item_html_class')) {
            $_more_class = timeline_item_html_class($_more_class, $reply);
        }
        $html .= '<div class="'.h($_more_class).'" id="tl_c_'.$reply['wr_id'].'" data-id="'.$reply['wr_id'].'">';
        $html .= '<div class="tl-body">';
        $html .= '<div class="tl-header">';
        $html .= '<a href="'.G5_URL.'/timeline/profile.php?tl_table='.$tl_table.'&ch_id='.$reply['ch_id'].'" class="tl-name">'.h($reply_name).'</a>';
        $html .= function_exists('timeline_html_men_handle') ? timeline_html_men_handle($tl_table, (int)$reply['ch_id']) : '';
        $html .= '<span class="tl-time" title="'.$reply['wr_datetime'].'">'.$reply_time.'</span>';
        $html .= '</div>';
        if ($reply_to_name) {
            $html .= '<div class="tl-reply-to-indicator"><span class="tl-mention-wrap"><span class="tl-mention">@'.h($reply_to_name).'</span></span></div>';
        }
        $html .= '<div class="tl-content">'.$reply_content.'</div>';
        $html .= $extra_html;
        $html .= '<div class="tl-actions">';
        $html .= '<button type="button" class="tl-action reply" onclick="showInlineReply('.$reply['wr_id'].', \''.addslashes(h($reply_name)).'\')"><i class="material-icons" aria-hidden="true">chat_bubble</i></button>';
        $html .= '<button type="button" class="tl-action like'.$has_liked.'" onclick="toggleLike('.$reply['wr_id'].', this)"><i class="material-icons" aria-hidden="true">favorite</i> <span class="tl-action-count">'.($reply['wr_like_count'] > 0 ? $reply['wr_like_count'] : '').'</span></button>';
        $html .= '</div>';
        $html .= '</div></div>';
    }
}

echo json_encode(array(
    'success' => true,
    'html' => $html,
    'has_more' => $result['has_more'],
    'next_from' => $result['next_from'],
    'count' => count($result['replies'])
), JSON_UNESCAPED_UNICODE);
