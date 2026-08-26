<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 타임라인 아이템 콘텐츠 스킨 (공통)
 * item.skin.php, item.origin.skin.php, item.comment.skin.php에서 include
 * 
 * 필수 변수:
 * - $content_type: 'post' | 'origin' | 'comment'
 * - $item_id: 글/댓글 ID (wr_id 또는 comment_id)
 * - $profile_url: 프로필 URL
 * - $ch_name: 작성자명 (이스케이프됨)
 * - $time_ago: 시간 경과 문자열
 * - $is_mine: 본인 글 여부
 * - $content: 렌더링된 내용
 * 
 * 선택 변수:
 * - $ch: 캐릭터 정보 (진영 아이콘용)
 * - $wr_datetime: 원본 datetime (툴팁용)
 * - $is_edit, $is_delete: 수정/삭제 권한 (comment)
 * - $reply_to_name: 답글 대상 이름
 * - $is_reply, $wr_reply_to: 답글 여부 (post)
 * - $images: 이미지 배열
 * - $wr_dice1, $wr_dice2: 주사위
 * - $wr_log, $wr_item_log: 액션 로그
 * - $data_log, $item_log: 파싱된 로그 (comment)
 * - $like_count, $comment_count: 카운트
 * - $my_like, $my_bookmark, $has_liked: 좋아요/북마크 상태
 * - $open_post_id, $view_url: 링크 대상
 * - $parent_id: 부모 글 ID (comment)
 * - $stop_propagation: event.stopPropagation() 사용 여부 (origin)
 */

// 기본값 설정
$content_type = isset($content_type) ? $content_type : 'post';
$stop_propagation = isset($stop_propagation) && $stop_propagation;
$sp = $stop_propagation ? 'event.stopPropagation(); ' : '';

// 이미지 처리
$images = isset($images) ? $images : array();
$image_count = count($images);

// 권한 기본값
$is_edit = isset($is_edit) ? $is_edit : false;
$is_delete = isset($is_delete) ? $is_delete : ($is_mine || $is_admin);

// 카운트 기본값
$like_count = isset($like_count) ? $like_count : 0;
$comment_count = isset($comment_count) ? $comment_count : 0;

// 좋아요 상태
$like_active = '';
if ($content_type == 'comment' && isset($has_liked)) {
    $like_active = $has_liked;
} elseif (isset($my_like) && $my_like) {
    $like_active = ' active';
}

// 접기 옵션 — 현재 아이템 row 우선 (루프 include 시 이전 값 잔존 방지)
// view 페이지에서 $row 는 원글(GET wr_id)에 고정되므로, 댓글은 $log_comment 를 써야 함
$_src_row = null;
if ($content_type === 'comment' && isset($log_comment) && is_array($log_comment)) {
    $_src_row = $log_comment;
} elseif (isset($row) && is_array($row)) {
    $_src_row = $row;
} elseif (isset($log_comment) && is_array($log_comment)) {
    $_src_row = $log_comment;
}

if ($_src_row) {
    $wr_img_hidden = (int)ses($_src_row, 'wr_img_hidden', 0, 'int');
    $wr_text_hidden = (int)ses($_src_row, 'wr_text_hidden', 0, 'int');
    $wr_waring_text = (string)ses($_src_row, 'wr_waring_text', '', 'raw');
} else {
    $wr_img_hidden = isset($wr_img_hidden) ? (int)$wr_img_hidden : 0;
    $wr_text_hidden = isset($wr_text_hidden) ? (int)$wr_text_hidden : 0;
    $wr_waring_text = isset($wr_waring_text) ? (string)$wr_waring_text : '';
}
$wr_img_hidden = $wr_img_hidden ? 1 : 0;
$wr_text_hidden = $wr_text_hidden ? 1 : 0;
$wr_waring_text = trim($wr_waring_text);

if ($_src_row) {
    $wr_modified = (int)ses($_src_row, 'wr_modified', 0, 'int') ? 1 : 0;
} else {
    $wr_modified = isset($wr_modified) ? ((int)$wr_modified ? 1 : 0) : 0;
}

// 스크립트 글: 이름/시간 숨김 (루프 잔존 값·플래그 누락 대비)
if ($_src_row && function_exists('timeline_row_is_script')) {
    $wr_script = timeline_row_is_script($_src_row) ? 1 : 0;
} elseif (!isset($wr_script)) {
    $wr_script = 0;
} else {
    $wr_script = !empty($wr_script) ? 1 : 0;
}
// se_script 등: 상단 메타만 접고 하단 메뉴·extra 는 wr_script 와 분리
$header_compact = !empty($wr_script);
if (!$header_compact && $_src_row && function_exists('timeline_item_compact_header')) {
    $header_compact = timeline_item_compact_header($_src_row);
}

$_handle_ch_id = 0;
if ($content_type === 'comment' && isset($log_comment) && is_array($log_comment)) {
    $_handle_ch_id = (int)ses($log_comment, 'ch_id', 0, 'int');
}
if ($_handle_ch_id <= 0 && $_src_row) {
    $_handle_ch_id = (int)ses($_src_row, 'ch_id', 0, 'int');
}
if ($_handle_ch_id <= 0 && isset($ch_id)) {
    $_handle_ch_id = (int)$ch_id;
}
if ($_handle_ch_id <= 0 && !empty($ch) && is_array($ch)) {
    $_handle_ch_id = (int)ses($ch, 'ch_id', 0, 'int');
}
$_men_handle = '';
if ($_handle_ch_id > 0 && function_exists('timeline_html_men_handle')) {
    $_men_handle = timeline_html_men_handle(isset($tl_table) ? $tl_table : '', $_handle_ch_id);
}

// 타이틀 표시 (MMB: get_title_image(ti_id) — tl_ti_use 일 때만)
$_title_html = '';
if (!isset($timeline) || !is_array($timeline)) {
    global $timeline;
}
$_tl_ti_use = (is_array($timeline)) ? (int)ses($timeline, 'tl_ti_use', 0, 'int') : 0;
if ($_tl_ti_use && function_exists('get_title_image')) {
    $_ti_id = 0;
    if ($_src_row) {
        $_ti_id = (int)ses($_src_row, 'ti_id', 0, 'int');
    }
    if ($_ti_id > 0) {
        $_title_html = get_title_image($_ti_id);
    }
}
?>
<!-- 헤더: 이름, 시간, 메뉴 (공지 wr_script / se_script 는 수정·삭제만) -->
<?php if (empty($header_compact)) { ?>
<div class="tl-header">
    <a href="<?php echo $profile_url; ?>" class="tl-name"<?php echo $stop_propagation ? ' onclick="event.stopPropagation()"' : ''; ?>>
        <?php if (!empty($ch) && is_array($ch) && !empty($ch['ch_id'])) { ?>
        <i><?php echo get_side_icon(ses($ch, 'ch_side', '')); ?></i>
        <?php } ?>
        <?php echo $_title_html; ?>
        <?php echo $ch_name; ?>
    </a>
    <?php echo $_men_handle; ?>
    <span class="tl-time"<?php echo isset($wr_datetime) ? ' title="'.$wr_datetime.'"' : ''; ?>><?php echo $time_ago; ?><?php if ($wr_modified) { ?> <span class="tl-modified">(수정됨)</span><?php } ?></span>
    
    <?php if ($is_mine || $is_admin) { ?>
    <div class="tl-menu">
        <button type="button" class="tl-menu-btn" onclick="<?php echo $sp; ?>toggleMenu(this)">⋮</button>
        <div class="tl-dropdown">
            <button type="button" onclick="<?php echo $sp; ?>openEditPost(<?php echo $item_id; ?>)">수정</button>
            <?php if ($content_type == 'comment') { ?>
                <?php if ($is_delete) { ?>
                <button type="button" onclick="<?php echo $sp; ?>deleteComment(<?php echo $item_id; ?>, <?php echo $parent_id; ?>)">삭제</button>
                <?php } ?>
            <?php } else { ?>
            <button type="button" onclick="<?php echo $sp; ?>deletePost(<?php echo $item_id; ?>)">삭제</button>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>
<?php } else { ?>
    <?php if ($is_mine || $is_admin) { ?>
    <div class="tl-header tl-header-script">
        <div class="tl-menu">
            <button type="button" class="tl-menu-btn" onclick="<?php echo $sp; ?>toggleMenu(this)">⋮</button>
            <div class="tl-dropdown">
                <button type="button" onclick="<?php echo $sp; ?>openEditPost(<?php echo $item_id; ?>)">수정</button>
                <?php if ($content_type == 'comment') { ?>
                    <?php if ($is_delete) { ?>
                    <button type="button" onclick="<?php echo $sp; ?>deleteComment(<?php echo $item_id; ?>, <?php echo $parent_id; ?>)">삭제</button>
                    <?php } ?>
                <?php } else { ?>
                <button type="button" onclick="<?php echo $sp; ?>deletePost(<?php echo $item_id; ?>)">삭제</button>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php } ?>
<?php } ?>

<!-- 답글 표시 -->
<?php if (isset($is_reply) && $is_reply && isset($wr_reply_to) && $wr_reply_to > 0) { ?>
<div class="tl-reply-to-indicator">↩ 답글</div>
<?php } ?>

<?php if ($wr_text_hidden && $wr_waring_text !== '') { ?>
<div class="tl-warning-text"><?php echo h($wr_waring_text); ?></div>
<?php } ?>

<?php if ($wr_text_hidden) { ?>
<details class="tl-spoiler tl-spoiler-text">
    <summary class="tl-spoiler-btn">글 보기</summary>
    <div class="tl-spoiler-body">
<?php } ?>

<!-- 본문 -->
<div class="tl-content<?php echo $content_type == 'origin' ? ' tl-content-short' : ''; ?><?php echo !empty($wr_script) ? ' tl-content-script' : ''; ?>"<?php echo $content_type == 'origin' ? ' onclick="openPost('.$item_id.')"' : ''; ?>>
    <?php
    echo $content;
    ?>
</div>

<!-- 모듈 부가 렌더 (bbs_view_row_extra: 액션 로그 / 주사위 / 아이템 사용 등) -->
<?php
$_tl_extra_row = ($_src_row && is_array($_src_row)) ? $_src_row : array();
if (!isset($_tl_extra_row['wr_log'])) {
    $_tl_extra_row['wr_log'] = isset($wr_log) ? $wr_log : '';
}
if (!isset($_tl_extra_row['wr_item_log'])) {
    $_tl_extra_row['wr_item_log'] = isset($wr_item_log) ? $wr_item_log : '';
}
if (!isset($_tl_extra_row['wr_dice'])) {
    $_tl_extra_row['wr_dice'] = isset($wr_dice) ? $wr_dice : '';
}
if (!isset($_tl_extra_row['wr_dice1'])) {
    $_tl_extra_row['wr_dice1'] = isset($wr_dice1) ? $wr_dice1 : 0;
}
if (!isset($_tl_extra_row['wr_dice2'])) {
    $_tl_extra_row['wr_dice2'] = isset($wr_dice2) ? $wr_dice2 : 0;
}
if (function_exists('bbs_render_row_extra') && empty($wr_script)) {
    // 피드 item.skin.php 는 댓글도 content_type=post. wr_is_comment 로 extra scope 를 보정한다.
    $_tl_extra_scope = 'post';
    if ((isset($content_type) && $content_type === 'comment')
        || (!empty($_tl_extra_row['wr_is_comment']) && (int)$_tl_extra_row['wr_is_comment'] === 1)
    ) {
        $_tl_extra_scope = 'comment';
    }
    bbs_render_row_extra($_tl_extra_row, array(
        'bo_table'        => !empty($bo_table) ? $bo_table : (isset($tl_table) ? $tl_table : ''),
        'board_skin_url'  => isset($timeline_skin_url) ? $timeline_skin_url : (isset($board_skin_url) ? $board_skin_url : ''),
        'board_skin_path' => isset($timeline_skin_path) ? $timeline_skin_path : (isset($board_skin_path) ? $board_skin_path : ''),
        'scope'           => $_tl_extra_scope,
    ));
    unset($_tl_extra_scope);
}
unset($_tl_extra_row);
?>

<!-- 이미지 -->
<?php if ($image_count > 0) { ?>
<?php if ($wr_img_hidden) { ?>
<details class="tl-spoiler tl-spoiler-img">
    <summary class="tl-spoiler-btn">이미지 표시</summary>
    <div class="tl-spoiler-body">
<?php } ?>
<div class="tl-media tl-media-<?php echo $image_count; ?>">
    <?php foreach ($images as $idx => $img_url) { ?>
    <div class="tl-media-item" onclick="<?php echo $sp; ?>viewImage('<?php echo $img_url; ?>', this)">
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

<!-- 수정 영역 (레거시 인라인 — 모달 수정으로 대체, 미사용) -->
<?php /* if ($is_edit) { ?>
<div class="tl-edit-area" id="tl_edit_<?php echo $item_id; ?>">
    <textarea class="tl-edit-textarea"><?php echo h(ses($log_comment, 'content1', '')); ?></textarea>
    <div class="tl-edit-actions">
        <button type="button" class="tl-btn tl-btn-small tl-btn-cancel" onclick="cancelCommentEdit(<?php echo $item_id; ?>)">취소</button>
        <button type="button" class="tl-btn tl-btn-small tl-btn-primary" onclick="saveCommentEdit(<?php echo $item_id; ?>)">수정</button>
    </div>
</div>
<?php } */ ?>

<!-- 액션 버튼 (스크립트 글은 미표시) -->
<?php if (empty($wr_script)) { ?>
<div class="tl-actions">
    <?php if ($content_type == 'comment'||$content_type == 'detail') { ?>
        <button type="button" class="tl-action reply" onclick="showInlineReply(<?php echo $item_id; ?>, '<?php echo addslashes(h($ch_name)); ?>')">
    <?php } else { ?>
        <button type="button" class="tl-action reply" onclick="<?php echo $sp; ?>openPost(<?php echo isset($open_post_id) ? $open_post_id : $item_id; ?>, true)">
    <?php } ?>
    <i class="material-icons" aria-hidden="true">chat_bubble</i>
        <span class="tl-action-count"><?php echo $comment_count > 0 ? $comment_count : ''; ?></span>
    </button>


    <button type="button" class="tl-action like<?php echo $like_active; ?>" onclick="<?php echo $sp; ?>toggleLike(<?php echo $item_id; ?>, this)">
        <i class="material-icons" aria-hidden="true">favorite</i>
        <span class="tl-action-count"><?php echo $like_count > 0 ? $like_count : ''; ?></span>
    </button>
    
    <button type="button" class="tl-action bookmark<?php echo (isset($my_bookmark) && $my_bookmark) ? ' active' : ''; ?>" onclick="<?php echo $sp; ?>toggleBookmark(<?php echo $item_id; ?>, this)">
        <i class="material-icons" aria-hidden="true">bookmark</i>
    </button>
    
    <a href="<?php echo G5_URL.'/timeline/view.php?tl_table='.$tl_table.'&wr_id='.$item_id; ?>" class="tl-action view"<?php echo $stop_propagation ? ' onclick="event.stopPropagation();"' : ''; ?>>
        <i class="material-icons" aria-hidden="true">arrow_outward</i>
    </a>
</div>
<?php } ?>
