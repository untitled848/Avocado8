<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 인라인 답글 폼 스킨
 * 폼 필드는 write.skin.php / composer_fields.skin.php 와 동일 구조
 * 
 * 사용 변수:
 * - $reply_form_id : 답글 대상 글 ID (폼 구분용)
 * - $reply_to_name : 답글 대상자 이름 (빈 문자열이면 원글에 답글)
 * - $parent_id : 원글 ID
 * - $reply_to_id : 답글 대상 댓글 ID (0이면 원글에 직접 답글)
 * - $timeline : 타임라인 설정
 * - $character : 현재 캐릭터
 * 
 * _common.php에서 로드된 변수:
 * - $is_approved, $search_remain, $tl_make_items, $tl_items
 */

global $g5, $config, $timeline, $character, $member, $tl_table, $is_approved, $my_ch_id, $bo_table;

$tl_use_image = ses($timeline, 'tl_use_image', 0, 'int');
$content_max = ses($timeline, 'tl_content_max', 500, 'int');
if (!$content_max) $content_max = 500;
$image_max = ses($timeline, 'tl_image_count', 4, 'int');
if ($image_max < 1) $image_max = 4;
if ($image_max > 4) $image_max = 4;

// 모듈 훅 — mmb 댓글과 동일 (bbs_comment_form_*)
$form_prefix = 'ir_' . $reply_form_id;
$_ir_ctx = array(
    'bo_table'   => $bo_table,
    'w'          => 'c',
    'wr_id'      => isset($parent_id) ? (int)$parent_id : 0,
    'write'      => array(),
    'character'  => $character,
    'board'      => isset($board) ? $board : array(),
    'scope_id'   => $reply_form_id,
    'id_prefix'  => $form_prefix,
    'list_item'  => array('wr_id' => isset($reply_form_id) ? (int)$reply_form_id : 0),
    'action_ui'  => 'select',
    'ui'         => 'composer',
);
$_ir_action_options = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_comment_form_action_options', '', $_ir_ctx) : '';
$_ir_action_panels  = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_comment_form_extra', '', $_ir_ctx) : '';
$_ir_form_options   = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_comment_form_options', '', $_ir_ctx) : '';
$_ir_after_action   = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_comment_form_after_action', '', $_ir_ctx) : '';
$_ir_composer_icons = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_comment_form_composer_icons', '', $_ir_ctx) : '';
$_ir_composer_panels = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_comment_form_composer_panels', '', $_ir_ctx) : '';

$tl_cf = array(
    'prefix'             => $form_prefix,
    'content_max'        => $content_max,
    'image_max'          => $image_max,
    'use_image'          => $tl_use_image,
    'is_approved'        => $is_approved,
    'placeholder'         => '답글을 입력하세요...',
    'mention_placeholder' => '사용자 검색 (최대 6자)',
    'textarea_class'     => 'tl-comment-textarea',
    'textarea_attrs'     => 'oninput="irUpdateCharCount(\''.h($form_prefix).'\', '.$content_max.')"',
    'form_options'       => $_ir_form_options,
    'action_options'     => $_ir_action_options,
    'action_panels'      => $_ir_action_panels,
    'after_action'       => $_ir_after_action,
    'composer_icons'     => $_ir_composer_icons,
    'composer_panels'    => $_ir_composer_panels,
    'submit_label'       => '답글',
    'submit_disabled'    => false,
    'submit_class'       => 'tl-btn tl-btn-primary',
    'image_onchange'     => 'irPreviewImages(\''.h($form_prefix).'\', this, '.$image_max.')',
    'toggle_action_js'   => 'irToggleAction(\''.h($form_prefix).'\')',
    'toggle_item_js'     => 'irToggleItem(\''.h($form_prefix).'\')',
);
?>

<form class="tl-inline-reply tl-inline-reply-full" id="tl_reply_<?php echo $reply_form_id; ?>" data-parent-wr-id="<?php echo (int)$reply_form_id; ?>" ondragover="irDragOver(event, '<?php echo $form_prefix; ?>')" ondragleave="irDragLeave(event, '<?php echo $form_prefix; ?>')" ondrop="irDrop(event, '<?php echo $form_prefix; ?>')" onsubmit="return submitInlineReplyForm(this, <?php echo $parent_id; ?>);">
    <input type="hidden" name="wr_id" value="<?php echo $parent_id; ?>">
    <?php if ($reply_to_id > 0) { ?>
    <input type="hidden" name="reply_to" value="<?php echo $reply_to_id; ?>">
    <?php } ?>
    
    <div class="tl-reply-indicator">
        <span><?php echo $reply_to_name ? '<span class="tl-mention-wrap"><span class="tl-mention">@'.h($reply_to_name).'</span></span>님에게 답글' : '답글 작성'; ?></span>
        <button type="button" onclick="hideInlineReply(<?php echo $reply_form_id; ?>)">✕</button>
    </div>
    
    <div class="tl-inline-reply-form">
        <?php include __DIR__.'/composer_fields.skin.php'; ?>
    </div>
</form>
