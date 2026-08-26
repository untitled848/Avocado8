<?php
/**
 * 타임라인 기본 상세 뷰 (스킨이 없을 때 사용)
 * tl-comment-item과 동일한 구조 사용
 * 
 * 스레드 뷰 로직:
 * - is_thread_view = true: 댓글 클릭 → 원글 + 스레드(역추적된 멘션들 + 클릭한 글 + 직접 답글)
 * - is_thread_view = false: 원글 클릭 → 원글 + 원글에 직접 달린 댓글들
 */
if (!defined('_GNUBOARD_')) exit;

$profile_img = get_timeline_row_thumb($row);
$content = render_timeline_content($row['wr_content'], $tl_table);
$time_ago = get_time_ago($row['wr_datetime']);
$datetime = date('Y-m-d H:i:s', strtotime($row['wr_datetime']));
$is_comment_write = ($member['mb_level'] >= $timeline['tl_write_level']);

// 스레드 뷰 관련 변수
$is_thread_view = !empty($row['is_thread_view']);
$focused_id = ses($row, 'focused_id', 0, 'int');
$has_more_before = !empty($row['has_more_before']);
$more_before_from = ses($row, 'more_before_from', 0, 'int');
?>

<div class="tl-detail<?php echo $is_thread_view ? ' tl-thread-view' : ''; ?>">
    <!-- 원글 (tl-comment-item과 동일한 구조) -->
    <div class="tl-comment-item tl-detail-origin" id="tl_c_<?php echo $row['wr_id']; ?>" data-id="<?php echo $row['wr_id']; ?>">
        <div class="tl-comment-avatar">
            <a href="./profile.php?tl_table=<?php echo $tl_table; ?>&ch_id=<?php echo $row['ch_id']; ?>">
                <img src="<?php echo $profile_img; ?>" alt="<?php echo h(get_timeline_row_name($row)); ?>">
            </a>
        </div>
        
        <div class="tl-comment-main">
            <div class="tl-comment-header">
                <a href="./profile.php?tl_table=<?php echo $tl_table; ?>&ch_id=<?php echo $row['ch_id']; ?>" class="tl-comment-name">
                    <?php echo h(get_timeline_row_name($row)); ?>
                </a>
                <?php echo function_exists('timeline_html_men_handle') ? timeline_html_men_handle($tl_table, (int)$row['ch_id']) : ''; ?>
                <span class="tl-comment-time" title="<?php echo $datetime; ?>"><?php echo $time_ago; ?><?php if ((int)ses($row, 'wr_modified', 0, 'int')) { ?> <span class="tl-modified">(수정됨)</span><?php } ?></span>
                
                <?php if ($is_mine || $is_admin) { ?>
                <div class="tl-menu">
                    <button type="button" class="tl-menu-btn" onclick="toggleMenu(this)">⋮</button>
                    <div class="tl-dropdown">
                        <button type="button" onclick="openEditPost(<?php echo $row['wr_id']; ?>)">수정</button>
                        <button type="button" onclick="deletePost(<?php echo $row['wr_id']; ?>)">삭제</button>
                    </div>
                </div>
                <?php } ?>
            </div>
            
            <div class="tl-comment-content"><?php echo $content; ?></div>
            
            <?php if ($row['wr_log']) { ?>
            <div class="tl-log"><?php echo $row['wr_log']; ?></div>
            <?php } ?>
            
            <?php if ($row['wr_item_log']) { ?>
            <div class="tl-log"><?php echo $row['wr_item_log']; ?></div>
            <?php } ?>
            
            <?php 
            // 이미지 처리
            $images = array();
            for ($i = 1; $i <= 4; $i++) {
                $img = ses($row, 'wr_image'.$i, '', 'raw');
                if ($img && file_exists(G5_TIMELINE_PATH.'/'.$tl_table.'/'.$img)) {
                    $images[] = G5_TIMELINE_URL.'/'.$tl_table.'/'.$img;
                }
            }
            $image_count = count($images);
            if ($image_count > 0) { 
            ?>
            <div class="tl-media tl-media-<?php echo $image_count; ?>">
                <?php foreach ($images as $idx => $img_url) { ?>
                <div class="tl-media-item" onclick="viewImage('<?php echo $img_url; ?>', this)">
                    <img src="<?php echo $img_url; ?>" alt="첨부 이미지 <?php echo $idx + 1; ?>">
                </div>
                <?php } ?>
            </div>
            <?php } ?>
            
            <div class="tl-detail-stats">
                <span><?php echo number_format($row['wr_like_count']); ?> 좋아요</span>
                <span><?php echo number_format($row['wr_comment']); ?> 답글</span>
            </div>
            
            <!-- 댓글 액션 버튼 (tl-comment-item과 동일) -->
            <div class="tl-comment-actions">
                <button type="button" class="tl-action reply" onclick="showInlineReply(<?php echo $row['wr_id']; ?>, '원글', true)">
                    <i class="material-icons" aria-hidden="true">chat_bubble</i> <span>답글 <?php echo $row['wr_comment'] > 0 ? $row['wr_comment'] : ''; ?></span>
                </button>
                <button type="button" class="tl-action like<?php echo $row['my_like'] ? ' active' : ''; ?>" onclick="toggleLike(<?php echo $row['wr_id']; ?>, this)">
                    <i class="material-icons" aria-hidden="true">favorite</i> <span class="tl-action-count"><?php echo $row['wr_like_count'] > 0 ? $row['wr_like_count'] : ''; ?></span>
                </button>
                <button type="button" class="tl-action bookmark<?php echo $row['my_bookmark'] ? ' active' : ''; ?>" onclick="toggleBookmark(<?php echo $row['wr_id']; ?>, this)">
                    <i class="material-icons" aria-hidden="true">bookmark</i>
                </button>
            </div>
            
            <!-- 인라인 답글 폼 (해당 글에 대한 답글) -->
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
            <div class="tl-inline-reply" id="tl_reply_<?php echo $row['wr_id']; ?>" style="display:none;">
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
    <!-- 더 보기 박스 (원글과 스레드 사이) -->
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
    // view_comment.php에서 사용하는 변수 설정
    $list_item = $row;
    

    // 댓글 목록 스킨
    if (file_exists($timeline_skin_path.'/view_comment.php')) {
        include($timeline_skin_path.'/view_comment.php');
    } else if (!empty($row['replies'])) { 
    ?>
    <div class="tl-replies" id="tl_replies_container">
        <?php foreach ($row['replies'] as $reply) { 
            $reply_profile = get_timeline_row_thumb($reply);
            $reply_content = render_timeline_content($reply['wr_content'], $tl_table);
            $reply_time = get_time_ago($reply['wr_datetime']);
            $reply_is_mine = ($reply['ch_id'] == $my_ch_id);
            $is_focused = ($reply['wr_id'] == $focused_id);
        ?>
        <div class="tl-reply-item<?php echo $is_focused ? ' tl-focused' : ''; ?>" data-id="<?php echo $reply['wr_id']; ?>">
            <a href="./profile.php?tl_table=<?php echo $tl_table; ?>&ch_id=<?php echo $reply['ch_id']; ?>" class="tl-reply-item-avatar">
                <img src="<?php echo $reply_profile; ?>" alt="<?php echo h(get_timeline_row_name($reply)); ?>">
            </a>
            <div class="tl-reply-item-body">
                <div class="tl-reply-item-header">
                    <a href="./profile.php?tl_table=<?php echo $tl_table; ?>&ch_id=<?php echo $reply['ch_id']; ?>" class="tl-reply-item-name"><?php echo h(get_timeline_row_name($reply)); ?></a>
                    <?php echo function_exists('timeline_html_men_handle') ? timeline_html_men_handle($tl_table, (int)$reply['ch_id']) : ''; ?>
                    <span class="tl-reply-item-time"><?php echo $reply_time; ?><?php if ((int)ses($reply, 'wr_modified', 0, 'int')) { ?> <span class="tl-modified">(수정됨)</span><?php } ?></span>
                    <?php if ($reply_is_mine || $is_admin) { ?>
                    <button type="button" class="tl-reply-item-edit" onclick="openEditPost(<?php echo $reply['wr_id']; ?>)">수정</button>
                    <button class="tl-reply-item-delete" onclick="deletePost(<?php echo $reply['wr_id']; ?>)">삭제</button>
                    <?php } ?>
                </div>
                <div class="tl-reply-item-content"><?php echo $reply_content; ?></div>
                
                <?php if ($reply['wr_log']) { ?>
                <div class="tl-reply-item-log"><?php echo $reply['wr_log']; ?></div>
                <?php } ?>
                
                <div class="tl-reply-item-actions">
                    <button class="tl-action-btn tl-like-btn<?php echo $reply['my_like'] ? ' active' : ''; ?>" onclick="toggleLike(<?php echo $reply['wr_id']; ?>, this)">
                        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M16.697 5.5c-1.222-.06-2.679.51-3.89 2.16l-.805 1.09-.806-1.09C9.984 6.01 8.526 5.44 7.304 5.5c-1.243.07-2.349.78-2.91 1.91-.552 1.12-.633 2.78.479 4.82 1.074 1.97 3.257 4.27 7.129 6.61 3.87-2.34 6.052-4.64 7.126-6.61 1.111-2.04 1.03-3.7.477-4.82-.561-1.13-1.666-1.84-2.908-1.91z" fill="currentColor"/></svg>
                        <span class="tl-action-count"><?php echo $reply['wr_like_count'] > 0 ? $reply['wr_like_count'] : ''; ?></span>
                    </button>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
    <?php } ?>
</div>
