<?php
/**
 * 타임라인 아이템 템플릿
 * 게시판 호환 구조 (wr_* 컬럼 사용)
 * 
 * 변수: $post - 글 데이터 배열, $tl_table - 타임라인 테이블명
 */
$ch_name = h(get_timeline_row_name($post));
$ch_image = get_timeline_row_thumb($post);
$content = render_timeline_content($post['wr_content'], $tl_table);
$time_ago = get_time_ago($post['wr_datetime']);
$my_like = !empty($post['my_like']) ? ' active' : '';
$my_bookmark = !empty($post['my_bookmark']) ? ' active' : '';
$is_mine = ($post['ch_id'] == $character['ch_id']);
?>
<div class="tl-item" data-id="<?php echo $post['wr_id']; ?>">
    <a href="<?php echo G5_URL; ?>/timeline/profile.php?tl_table=<?php echo $tl_table; ?>&ch_id=<?php echo $post['ch_id']; ?>" class="tl-profile">
        <img src="<?php echo $ch_image; ?>" alt="<?php echo $ch_name; ?>">
    </a>
    <div class="tl-body">
        <div class="tl-header">
            <a href="<?php echo G5_URL; ?>/timeline/profile.php?tl_table=<?php echo $tl_table; ?>&ch_id=<?php echo $post['ch_id']; ?>" class="tl-name"><?php echo $ch_name; ?></a>
            <?php echo function_exists('timeline_html_men_handle') ? timeline_html_men_handle($tl_table, (int)$post['ch_id']) : ''; ?>
            <span class="tl-time"><?php echo $time_ago; ?><?php if ((int)ses($post, 'wr_modified', 0, 'int')) { ?> <span class="tl-modified">(수정됨)</span><?php } ?></span>
            <?php if ($is_mine || $is_admin) { ?>
            <div class="tl-menu">
                <button type="button" class="tl-menu-btn" onclick="toggleMenu(this)">⋮</button>
                <div class="tl-dropdown">
                    <button type="button" onclick="openEditPost(<?php echo $post['wr_id']; ?>)">수정</button>
                    <button type="button" onclick="deletePost(<?php echo $post['wr_id']; ?>)">삭제</button>
                </div>
            </div>
            <?php } ?>
        </div>
        <?php if ($post['wr_parent']) { ?>
        <div class="tl-reply-to">
            <?php 
            $parent = sql_fetch("SELECT wr_name FROM {$write_table} WHERE wr_id = '{$post['wr_parent']}'");
            ?>
            <a href="<?php echo G5_URL; ?>/timeline/view.php?tl_table=<?php echo $tl_table; ?>&wr_id=<?php echo $post['wr_parent']; ?>">
                <span class="tl-mention-wrap"><span class="tl-mention">@<?php echo h(get_timeline_row_name($parent)); ?></span></span>님에게 답글
            </a>
        </div>
        <?php } ?>
        <?php
        $wr_img_hidden = (int)ses($post, 'wr_img_hidden', 0, 'int') ? 1 : 0;
        $wr_text_hidden = (int)ses($post, 'wr_text_hidden', 0, 'int') ? 1 : 0;
        $wr_waring_text = trim((string)ses($post, 'wr_waring_text', '', 'raw'));
        $images = array();
        for ($i = 1; $i <= 4; $i++) {
            $img = ses($post, 'wr_image'.$i, '', 'raw');
            if ($img) {
                $img_path = G5_TIMELINE_PATH.'/'.$tl_table.'/'.$img;
                if (file_exists($img_path)) {
                    $images[] = G5_TIMELINE_URL.'/'.$tl_table.'/'.$img;
                }
            }
        }
        $image_count = count($images);
        ?>
        <?php if ($wr_text_hidden && $wr_waring_text !== '') { ?>
        <div class="tl-warning-text"><?php echo h($wr_waring_text); ?></div>
        <?php } ?>
        <?php if ($wr_text_hidden) { ?>
        <details class="tl-spoiler tl-spoiler-text">
            <summary class="tl-spoiler-btn">글 보기</summary>
            <div class="tl-spoiler-body">
        <?php } ?>
        <div class="tl-content"><?php echo $content; ?></div>
        <?php if ($image_count > 0) { ?>
        <?php if ($wr_img_hidden) { ?>
        <details class="tl-spoiler tl-spoiler-img">
            <summary class="tl-spoiler-btn">이미지 표시</summary>
            <div class="tl-spoiler-body">
        <?php } ?>
        <div class="tl-media tl-media-<?php echo $image_count; ?>">
            <?php foreach ($images as $idx => $img_url) { ?>
            <div class="tl-media-item" onclick="viewImage('<?php echo $img_url; ?>')">
                <img src="<?php echo $img_url; ?>" alt="첨부 이미지 <?php echo $idx + 1; ?>">
            </div>
            <?php } ?>
        </div>
        <?php if ($wr_img_hidden) { ?>
            </div>
        </details>
        <?php } ?>
        <?php } ?>
        <?php if ($wr_text_hidden) { ?>
            </div>
        </details>
        <?php } ?>
        <?php if ($post['wr_log']) { ?>
        <div class="tl-item-log"><?php echo $post['wr_log']; ?></div>
        <?php } ?>
        <?php if ($post['wr_item_log']) { ?>
        <div class="tl-item-log"><?php echo $post['wr_item_log']; ?></div>
        <?php } ?>
        <div class="tl-actions">
            <button type="button" class="tl-action reply" onclick="openPost(<?php echo $post['wr_id']; ?>)">
                <i class="material-icons" aria-hidden="true">chat_bubble</i> <span><?php echo $post['wr_comment'] > 0 ? $post['wr_comment'] : ''; ?></span>
            </button>
            <button type="button" class="tl-action like<?php echo $my_like; ?>" onclick="toggleLike(<?php echo $post['wr_id']; ?>, this)">
                <i class="material-icons" aria-hidden="true">favorite</i> <span class="tl-action-count"><?php echo $post['wr_like_count'] > 0 ? $post['wr_like_count'] : ''; ?></span>
            </button>
            <button type="button" class="tl-action bookmark<?php echo $my_bookmark; ?>" onclick="toggleBookmark(<?php echo $post['wr_id']; ?>, this)">
                <i class="material-icons" aria-hidden="true">bookmark</i>
            </button>
            <a href="<?php echo G5_URL; ?>/timeline/view.php?tl_table=<?php echo $tl_table; ?>&wr_id=<?php echo $post['wr_id']; ?>" class="tl-action view">
                <i class="material-icons" aria-hidden="true">arrow_outward</i>
            </a>
        </div>
        <?php if (!empty($post['latest_reply'])) { 
            $reply = $post['latest_reply'];
            $reply_ch_name = h(get_timeline_row_name($reply));
            $reply_content = render_timeline_content($reply['wr_content'], $tl_table);
        ?>
        <div class="tl-latest-reply">
            <a href="<?php echo G5_URL; ?>/timeline/profile.php?tl_table=<?php echo $tl_table; ?>&ch_id=<?php echo $reply['ch_id']; ?>" class="reply-name"><?php echo $reply_ch_name; ?></a>
            <span class="reply-content"><?php echo $reply_content; ?></span>
            <?php if ($post['wr_comment'] > 1) { ?>
            <a href="<?php echo G5_URL; ?>/timeline/view.php?tl_table=<?php echo $tl_table; ?>&wr_id=<?php echo $post['wr_id']; ?>" class="reply-more">
                답글 <?php echo $post['wr_comment']; ?>개 모두 보기
            </a>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
</div>
