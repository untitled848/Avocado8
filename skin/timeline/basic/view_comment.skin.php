<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 타임라인 댓글 목록 스킨
 * MMB의 view_comment.skin.php와 유사한 구조
 * 
 * 변수:
 * - $comments: 댓글 배열
 * - $list_item: 원글 정보
 * - $is_comment_write: 댓글 작성 권한
 * - $is_thread_view: 스레드 뷰 여부
 * - $timeline_skin_path, $timeline_skin_url: 스킨 경로
 */

// 댓글이 없으면 종료
if (empty($comments)) {
    return;
}

// 멘션 타래 구분을 위한 변수 (스레드 뷰가 아닐 때만 사용)
$current_mention_root = -1;

for ($index = 0; $index < count($comments); $index++) {
    $log_comment = $comments[$index];
    if (!isset($log_comment['wr_id'])) {
        continue;
    }
    
    $comment_id = $log_comment['wr_id'];
    $mention_root = ses($log_comment, 'wr_mention_root', 0, 'int');
    $reply_to = ses($log_comment, 'wr_reply_to', 0, 'int');
    
    // 새 멘션 타래 시작 여부 (스레드 뷰에서는 항상 false)
    $is_new_thread = false;
    if (!$is_thread_view) {
        $is_new_thread = ($mention_root != $current_mention_root);
        if ($is_new_thread) {
            $current_mention_root = $mention_root;
        }
    }
    
    // 내용 처리 — 원문 기준 (이미 HTML화된 content를 다시 넣지 않음)
    $content = ses($log_comment, 'content1', '', 'raw');
    if ($content === '') {
        $content = ses($log_comment, 'wr_content', '', 'raw');
    }
    if ($content === '') {
        $content = ses($log_comment, 'content', '', 'raw');
    }
    $content = render_timeline_content($content, $tl_table);
    $content = emote_ev($content);
    
    // 캐릭터 정보
    $ch = array();
    $log_comment_ch_id = ses($log_comment, 'ch_id', 0);
    if ($log_comment_ch_id) {
        $ch = get_character($log_comment_ch_id);
    }
    
    $ch_name = get_timeline_row_name($log_comment, 'GUEST');
    $ch_image = get_timeline_row_thumb($log_comment);
    
    // 삭제 가능 여부
    $is_delete = !empty($log_comment['is_del']);
    $is_edit = !empty($log_comment['is_edit']);
    
    // 본인 댓글 여부
    $is_mine = ($log_comment_ch_id == $character['ch_id']);
    
    // 좋아요 상태 (이미 조회된 값 사용, 없으면 쿼리)
    $has_liked = '';
    if (isset($log_comment['my_like'])) {
        $has_liked = $log_comment['my_like'] ? ' active' : '';
    } else {
        $my_like = sql_fetch("SELECT COUNT(*) AS cnt FROM {$g5['timeline_good_table']} WHERE tl_table = '{$tl_table}' AND wr_id = '{$comment_id}' AND ch_id = '{$character['ch_id']}'");
        $has_liked = ($my_like['cnt'] > 0) ? ' active' : '';
    }
    
    // 답장 대상 캐릭터명
    $reply_to_name = '';
    if ($reply_to > 0) {
        $reply_to_info = sql_fetch("SELECT wr_name FROM {$write_table} WHERE wr_id = '{$reply_to}'");
        $reply_to_name = get_timeline_row_name($reply_to_info);
    }
    
    // 시간
    $wr_datetime = ses($log_comment, 'wr_datetime', '');
    $time_ago = ses($log_comment, 'time_ago', '');
    
    // 포커스 여부 (클릭한 글)
    $is_focused_item = !empty($log_comment['is_focused']);
    ?>
    
    <?php if (!$is_thread_view && $is_new_thread && $index > 0) { ?>
    <div class="tl-thread-divider"></div>
    <?php } ?>
    
    <?php
    // item.comment.skin.php 사용 (있으면)
    if (file_exists($timeline_skin_path.'/item.comment.skin.php')) {
        include($timeline_skin_path.'/item.comment.skin.php');
    } else {
        // 폴백: 기존 인라인 렌더링
    ?>
    <?php
        $_fb_item_class = 'tl-item tl-item-comment'.((!$is_thread_view && $is_new_thread) ? ' tl-thread-start' : ' tl-thread-continue').($is_focused_item ? ' tl-focused' : '');
        if (function_exists('timeline_item_html_class')) {
            $_fb_item_class = timeline_item_html_class($_fb_item_class, $log_comment);
        }
    ?>
    <div class="<?php echo $_fb_item_class; ?>" id="tl_c_<?php echo $comment_id; ?>" data-id="<?php echo $comment_id; ?>" data-root="<?php echo $mention_root; ?>">
        <a href="<?php echo G5_URL; ?>/timeline/profile.php?tl_table=<?php echo $tl_table; ?>&ch_id=<?php echo $log_comment_ch_id; ?>" class="tl-profile">
            <img src="<?php echo $ch_image; ?>" alt="<?php echo h($ch_name); ?>">
        </a>
        
        <div class="tl-body">
            <div class="tl-header">
                <a href="<?php echo G5_URL; ?>/timeline/profile.php?tl_table=<?php echo $tl_table; ?>&ch_id=<?php echo $log_comment_ch_id; ?>" class="tl-name">
                    <?php if (!empty($ch) && is_array($ch) && !empty($ch['ch_id'])) { ?>
                    <i><?php echo get_side_icon(ses($ch, 'ch_side', '')); ?></i>
                    <?php } ?>
                    <?php
                    if ((int)ses($timeline, 'tl_ti_use', 0, 'int') && function_exists('get_title_image')) {
                        $_fb_ti = (int)ses($log_comment, 'ti_id', 0, 'int');
                        if ($_fb_ti > 0) {
                            echo get_title_image($_fb_ti);
                        }
                    }
                    ?>
                    <?php echo h($ch_name); ?>
                </a>
                <?php echo function_exists('timeline_html_men_handle') ? timeline_html_men_handle($tl_table, (int)$log_comment_ch_id) : ''; ?>
                <span class="tl-time" title="<?php echo $wr_datetime; ?>"><?php echo $time_ago; ?><?php if ((int)ses($log_comment, 'wr_modified', 0, 'int')) { ?> <span class="tl-modified">(수정됨)</span><?php } ?></span>
                
                <?php if ($is_mine || $is_admin) { ?>
                <div class="tl-menu">
                    <button type="button" class="tl-menu-btn" onclick="toggleMenu(this)">⋮</button>
                    <div class="tl-dropdown">
                        <button type="button" onclick="openEditPost(<?php echo $comment_id; ?>)">수정</button>
                        <?php if ($is_delete) { ?>
                        <button type="button" onclick="deleteComment(<?php echo $comment_id; ?>, <?php echo $list_item['wr_id']; ?>)">삭제</button>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>
            </div>
            
            <?php if ($reply_to_name) { ?>
            <div class="tl-reply-to-indicator">
                <span class="tl-mention-wrap"><span class="tl-mention">@<?php echo h($reply_to_name); ?></span></span>
            </div>
            <?php } ?>
            
            <div class="tl-content">
                <?php
                $data_log = ses($log_comment, 'wr_log', '');
                if ($data_log && file_exists($timeline_skin_path.'/action/_action.data.php')) {
                    include($timeline_skin_path.'/action/_action.data.php');
                }
                
                $wr_dice1 = ses($log_comment, 'wr_dice1', 0, 'int');
                $wr_dice2 = ses($log_comment, 'wr_dice2', 0, 'int');
                if ($wr_dice1 > 0 && $wr_dice2 > 0) {
                    echo '<span class="tl-dice-result">🎲 <strong>'.$wr_dice1.'</strong> + <strong>'.$wr_dice2.'</strong> = <strong>'.($wr_dice1 + $wr_dice2).'</strong></span>';
                }
                echo $content;
                ?>
            </div>
            
            <?php
            $comment_images = array();
            for ($img_i = 1; $img_i <= 4; $img_i++) {
                $img_file = ses($log_comment, 'wr_image'.$img_i, '', 'raw');
                if ($img_file && file_exists(G5_TIMELINE_PATH.'/'.$tl_table.'/'.$img_file)) {
                    $comment_images[] = G5_TIMELINE_URL.'/'.$tl_table.'/'.$img_file;
                }
            }
            $comment_image_count = count($comment_images);
            if ($comment_image_count > 0) {
            ?>
            <div class="tl-media tl-media-<?php echo $comment_image_count; ?>">
                <?php foreach ($comment_images as $img_idx => $img_url) { ?>
                <div class="tl-media-item" onclick="viewImage('<?php echo $img_url; ?>', this)">
                    <img src="<?php echo $img_url; ?>" alt="첨부 이미지 <?php echo $img_idx + 1; ?>">
                </div>
                <?php } ?>
            </div>
            <?php } ?>
            
            <div class="tl-actions">
                <button type="button" class="tl-action reply" onclick="showInlineReply(<?php echo $comment_id; ?>, '<?php echo addslashes(h($ch_name)); ?>')">
                    <i class="material-icons" aria-hidden="true">chat_bubble</i> <span>답글</span>
                </button>
                <button type="button" class="tl-action like<?php echo $has_liked; ?>" onclick="toggleLike(<?php echo $comment_id; ?>, this)">
                    <i class="material-icons" aria-hidden="true">favorite</i> <span class="tl-action-count"><?php echo $log_comment['wr_like_count'] > 0 ? $log_comment['wr_like_count'] : ''; ?></span>
                </button>
            </div>
            
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
    <?php } // end fallback ?>
<?php
} // end for
?>
