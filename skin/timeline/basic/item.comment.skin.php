<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 타임라인 댓글 아이템 스킨
 * view_comment.skin.php에서 include하여 사용
 * item.skin.php와 동일한 스타일 구조
 * 
 * 사용 가능한 변수:
 * - $comment_id: 댓글 ID
 * - $log_comment: 댓글 원본 데이터
 * - $ch: 캐릭터 정보
 * - $ch_name: 작성자 캐릭터명
 * - $ch_image: 프로필 이미지 URL
 * - $content: 렌더링된 내용
 * - $time_ago: 시간 경과 문자열
 * - $wr_datetime: 원본 datetime
 * - $is_mine: 본인 글 여부
 * - $is_edit: 수정 권한
 * - $is_delete: 삭제 권한
 * - $has_liked: 좋아요 여부 (' active' 또는 '')
 * - $reply_to_name: 답글 대상 이름
 * - $mention_root: 멘션 타래 ID
 * - $is_new_thread: 새 타래 시작 여부
 * - $is_thread_view: 스레드 뷰 여부
 * - $is_focused_item: 포커스된 아이템 여부
 * - $list_item: 원글 정보
 * - $tl_table: 타임라인 테이블명
 * - $timeline_skin_path, $timeline_skin_url: 스킨 경로
 */

$log_comment_ch_id = ses($log_comment, 'ch_id', 0);
$item_class = 'tl-item tl-item-comment';
if (!$is_thread_view && $is_new_thread) {
    $item_class .= ' tl-thread-start';
} else {
    $item_class .= ' tl-thread-continue';
}
if ($is_focused_item) {
    $item_class .= ' tl-focused';
}
if (function_exists('timeline_item_html_class')) {
    $item_class = timeline_item_html_class($item_class, isset($log_comment) && is_array($log_comment) ? $log_comment : array());
}

$profile_url = G5_URL.'/timeline/profile.php?tl_table='.$tl_table.'&ch_id='.$log_comment_ch_id;

// 이미지 배열
$comment_images = array();
for ($img_i = 1; $img_i <= 4; $img_i++) {
    $img_file = ses($log_comment, 'wr_image'.$img_i, '', 'raw');
    if ($img_file && file_exists(G5_TIMELINE_PATH.'/'.$tl_table.'/'.$img_file)) {
        $comment_images[] = G5_TIMELINE_URL.'/'.$tl_table.'/'.$img_file;
    }
}
$image_count = count($comment_images);

// 주사위
$wr_dice1 = ses($log_comment, 'wr_dice1', 0, 'int');
$wr_dice2 = ses($log_comment, 'wr_dice2', 0, 'int');
?>
<div class="<?php echo $item_class; ?>" id="tl_c_<?php echo $comment_id; ?>" data-id="<?php echo $comment_id; ?>" data-root="<?php echo $mention_root; ?>">
    <a href="<?php echo $profile_url; ?>" class="tl-profile">
        <img src="<?php echo $ch_image; ?>" alt="<?php echo h($ch_name); ?>">
    </a>
    
    <div class="tl-body">
        <?php
        // item.content.skin.php용 변수 설정
        // view 페이지에서 원글 $row/$view_url 등이 잔존하므로 댓글 값으로 덮어씀
        $content_type = 'comment';
        $item_id = $comment_id;
        $ch_id = $log_comment_ch_id;
        $parent_id = $list_item['wr_id'];
        $images = $comment_images;
        $like_count = ses($log_comment, 'wr_like_count', 0, 'int');
        $comment_count = ses($log_comment, 'wr_comment', 0, 'int');
        $view_url = G5_URL.'/timeline/view.php?tl_table='.$tl_table.'&wr_id='.$comment_id;
        $open_post_id = $comment_id;
        $wr_log = ses($log_comment, 'wr_log', '', 'raw');
        $wr_item_log = ses($log_comment, 'wr_item_log', '', 'raw');
        $wr_dice = ses($log_comment, 'wr_dice', '', 'raw');
        $my_bookmark = !empty($log_comment['my_bookmark']);
        $is_reply = false;
        include($timeline_skin_path.'/item.content.skin.php');
        ?>
        
        <!-- 인라인 답글 폼 -->
        <?php 
        $reply_form_id = $comment_id;
        $reply_to_name = $ch_name;
        $parent_id = $list_item['wr_id'];
        $reply_to_id = $comment_id;
        if (file_exists($timeline_skin_path.'/inline_reply.skin.php')) {
            include($timeline_skin_path.'/inline_reply.skin.php');
        } else {
        ?>
        <div class="tl-inline-reply" id="tl_reply_<?php echo $comment_id; ?>">
            <div class="tl-reply-indicator">
                <span>@<?php echo h($ch_name); ?>님에게 답글</span>
                <button type="button" onclick="hideInlineReply(<?php echo $comment_id; ?>)">✕</button>
            </div>
            <div class="tl-inline-reply-input">
                <textarea class="tl-comment-textarea" id="tl_reply_content_<?php echo $comment_id; ?>" placeholder="답글을 입력하세요..."></textarea>
                <button type="button" class="tl-btn tl-btn-primary" onclick="submitInlineReply(<?php echo $list_item['wr_id']; ?>, <?php echo $comment_id; ?>)">답글</button>
            </div>
        </div>
        <?php } ?>
    </div>
</div>
