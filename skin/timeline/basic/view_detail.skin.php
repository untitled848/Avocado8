<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 타임라인 상세 뷰 스킨
 * item.content.skin.php를 사용하여 통일된 구조
 * 
 * 스레드 뷰 로직:
 * - is_thread_view = true: 댓글 클릭 → 원글 + 스레드
 * - is_thread_view = false: 원글 클릭 → 원글 + 직접 답글들
 * 
 * 변수:
 * - $row: 글 정보
 * - $tl_table: 타임라인 테이블명
 * - $is_mine: 본인 글 여부
 * - $my_ch_id: 현재 캐릭터 ID
 */

$profile_img = get_timeline_row_thumb($row);
$is_script = function_exists('timeline_row_is_script') ? timeline_row_is_script($row) : !empty($row['wr_script']);
$wr_script = $is_script ? 1 : 0;
$content = render_timeline_content($row['wr_content'], $tl_table, $wr_script);
$time_ago = get_time_ago($row['wr_datetime']);
$datetime = date('Y-m-d H:i:s', strtotime($row['wr_datetime']));
$is_comment_write = ($member['mb_level'] >= $timeline['tl_write_level']);

// 스레드 뷰 관련 변수
$is_thread_view = !empty($row['is_thread_view']);
$focused_id = ses($row, 'focused_id', 0, 'int');
$has_more_before = !empty($row['has_more_before']);
$more_before_from = ses($row, 'more_before_from', 0, 'int');

// 이미지 처리
$images = array();
for ($i = 1; $i <= 4; $i++) {
    $img = ses($row, 'wr_image'.$i, '', 'raw');
    if ($img && file_exists(G5_TIMELINE_PATH.'/'.$tl_table.'/'.$img)) {
        $images[] = G5_TIMELINE_URL.'/'.$tl_table.'/'.$img;
    }
}

// item.content.skin.php용 변수 설정
$content_type = 'detail';
$item_id = $row['wr_id'];
$ch_id = (int)$row['ch_id'];
$ch = get_character($row['ch_id']);
$ch_name = h(get_timeline_row_name($row, 'GUEST'));
$ch_image = $profile_img;
$profile_url = './profile.php?tl_table='.$tl_table.'&ch_id='.$row['ch_id'];
$wr_datetime = $datetime;
$like_count = ses($row, 'wr_like_count', 0, 'int');
$comment_count = ses($row, 'wr_comment', 0, 'int');
$my_like = !empty($row['my_like']);
$my_bookmark = !empty($row['my_bookmark']);
$wr_log = $is_script ? '' : ses($row, 'wr_log', '');
$wr_item_log = $is_script ? '' : ses($row, 'wr_item_log', '');
$wr_dice = $is_script ? '' : ses($row, 'wr_dice', '');
$wr_dice1 = $is_script ? 0 : ses($row, 'wr_dice1', 0, 'int');
$wr_dice2 = $is_script ? 0 : ses($row, 'wr_dice2', 0, 'int');
$view_url = G5_URL.'/timeline/view.php?tl_table='.$tl_table.'&wr_id='.$row['wr_id'];
$open_post_id = $row['wr_id'];
$item_class = 'tl-item tl-item-detail'.($is_script ? ' tl-item-script' : '');
if (function_exists('timeline_item_html_class')) {
    $item_class = timeline_item_html_class($item_class, $row);
}
?>

<div class="tl-detail<?php echo $is_thread_view ? ' tl-thread-view' : ''; ?>">
    <!-- 원글 -->
    <div class="<?php echo $item_class; ?>" id="tl_c_<?php echo $row['wr_id']; ?>" data-id="<?php echo $row['wr_id']; ?>">
        <?php if (!$is_script) { ?>
        <a href="<?php echo $profile_url; ?>" class="tl-profile">
            <img src="<?php echo $profile_img; ?>" alt="<?php echo $ch_name; ?>">
        </a>
        <?php } ?>
        
        <div class="tl-body">
            <?php
            // item.content.skin.php 사용
            include($timeline_skin_path.'/item.content.skin.php');
            ?>
            
            <!-- 통계 -->
            <!---<div class="tl-detail-stats">
                <span><?php echo number_format($like_count); ?> 좋아요</span>
                <span><?php echo number_format($comment_count); ?> 답글</span>
            </div>---->
            
            <!-- 인라인 답글 폼 -->
            <?php 
            if ($is_comment_write) { 
                $reply_form_id = $row['wr_id'];
                $reply_to_name = '';
                $parent_id = $row['wr_id'];
                $reply_to_id = 0;
                if (file_exists($timeline_skin_path.'/inline_reply.skin.php')) {
                    include($timeline_skin_path.'/inline_reply.skin.php');
                } else {
            ?>
            <div class="tl-inline-reply" id="tl_reply_<?php echo $row['wr_id']; ?>">
                <div class="tl-reply-indicator">
                    <span>답글 작성</span>
                    <button type="button" onclick="hideInlineReply(<?php echo $row['wr_id']; ?>)">✕</button>
                </div>
                <div class="tl-inline-reply-input">
                    <textarea class="tl-comment-textarea" id="tl_reply_content_<?php echo $row['wr_id']; ?>" placeholder="답글을 입력하세요..."></textarea>
                    <button type="button" class="tl-btn tl-btn-primary" onclick="submitInlineReply(<?php echo $row['wr_id']; ?>, 0)">답글</button>
                </div>
            </div>
            <?php 
                }
            } 
            ?>
        </div>
    </div>
    
    <?php if ($is_thread_view && $has_more_before) { ?>
    <!-- 더 보기 박스 -->
    <div class="tl-thread-more-box" id="tl_thread_more" data-from="<?php echo $more_before_from; ?>">
        <button type="button" class="tl-thread-more-btn" onclick="loadMoreThread()">
            <span>이전 대화 더 보기</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 8l-6 6 1.41 1.41L12 10.83l4.59 4.58L18 14z"/></svg>
        </button>
        <button type="button" class="tl-thread-all-btn" onclick="loadMoreThread(true)">
            <span>전체 대화 보기</span>
        </button>
    </div>
    <?php } ?>
    
    <!-- 답글 목록 -->
    <?php 
    $list_item = $row;
    
    // 댓글 목록 스킨
    if (file_exists($timeline_skin_path.'/view_comment.php')) {
        include($timeline_skin_path.'/view_comment.php');
    } elseif (!empty($row['replies'])) { 
    ?>
    <div class="tl-replies" id="tl_replies_container">
        <?php 
        foreach ($row['replies'] as $reply) { 
            // item.comment.skin.php용 변수 설정
            $log_comment = $reply;
            $comment_id = $reply['wr_id'];
            $mention_root = ses($reply, 'wr_mention_root', 0, 'int');
            $reply_to = ses($reply, 'wr_reply_to', 0, 'int');
            $is_new_thread = false;
            $is_focused_item = ($reply['wr_id'] == $focused_id);
            
            // 캐릭터 정보
            $ch = get_character($reply['ch_id']);
            $ch_name = get_timeline_row_name($reply, 'GUEST');
            $ch_image = get_timeline_row_thumb($reply);
            
            // 내용 처리
            $content = render_timeline_content($reply['wr_content'], $tl_table);
            $time_ago = get_time_ago($reply['wr_datetime']);
            $wr_datetime = $reply['wr_datetime'];
            
            // 권한
            $is_mine = ($reply['ch_id'] == $my_ch_id);
            $is_edit = $is_mine || $is_admin;
            $is_delete = $is_edit;
            
            // 좋아요
            $has_liked = !empty($reply['my_like']) ? ' active' : '';
            
            // 답글 대상
            $reply_to_name = '';
            if ($reply_to > 0) {
                $reply_to_info = sql_fetch("SELECT wr_name FROM {$write_table} WHERE wr_id = '{$reply_to}'");
                $reply_to_name = get_timeline_row_name($reply_to_info);
            }
            
            // item.comment.skin.php 사용
            if (file_exists($timeline_skin_path.'/item.comment.skin.php')) {
                include($timeline_skin_path.'/item.comment.skin.php');
            }
        } 
        ?>
    </div>
    <?php } ?>
</div>
