<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * mmb 댓글 쓰기 폼 — 기본 템플릿
 *
 * 변수는 bbs/mmb/write_comment.controller.php 에서 준비됨.
 * 스킨에서 override 하려면 skin/board/<스킨>/template/write_comment.tpl.php 작성.
 *
 * action 은 원글과 같이 <select name="action">. ctx.action_ui=select 이므로
 * bbs_comment_form_action_options 는 <option> 을 넣는다.
 * use_item select 등 외부 모듈 주입은 bbs_comment_form_after_action 필터로 받는다.
 * (주사위 등 기타 옵션은 bbs_comment_form_options)
 */
?>
<!-- 댓글 쓰기 시작 { -->

<?php if (!empty($mmb_cmt_block_msg)) { ?>
    <div class="mmb-cmt-block theme-box">
        <?php echo $mmb_cmt_block_msg; ?>
    </div>
<?php } else { ?>
<aside class="bo_vc_w" id="bo_vc_w_<?php echo $list_item['wr_id']; ?>">
    <form name="fviewcomment" action="./write_comment_update.php" onsubmit="return fviewcomment_submit(this);" method="post" autocomplete="off">
    <input type="hidden" name="w" value="<?php echo $w; ?>" id="w">
    <input type="hidden" name="bo_table" value="<?php echo $bo_table; ?>">
    <input type="hidden" name="wr_id" value="<?php echo $list_item['wr_id']; ?>">
    <input type="hidden" name="sca" value="<?php echo $sca; ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
    <input type="hidden" name="stx" value="<?php echo $stx; ?>">
    <input type="hidden" name="spt" value="<?php echo $spt; ?>">
    <input type="hidden" name="page" value="<?php echo $page; ?>">

    <input type="hidden" name="ch_id" value="<?php echo $character['ch_id']; ?>" />
    <input type="hidden" name="ti_id" value="<?php echo $character['ch_title']; ?>" />
    <input type="hidden" name="ma_id" value="<?php echo $character['ma_id']; ?>" />
    <input type="hidden" name="wr_subject" value="<?php echo (isset($character['ch_name']) && $character['ch_name'] !== '') ? $character['ch_name'] : 'GUEST'; ?>" />

    <div class="input-comment">
        <textarea name="wr_content" id="wr_content_<?php echo $list_item['wr_id']; ?>" required class="required" title="내용"></textarea>
        <?php if (!empty($GLOBALS['mmb_climit_text']) && (int)$GLOBALS['mmb_climit_text'] > 0) { ?>
        <script>(function(){
            var ta = document.getElementById('wr_content_<?php echo (int)$list_item['wr_id']; ?>');
            if (ta && window.mmbBindCommentLimit) window.mmbBindCommentLimit(ta, <?php echo (int)$GLOBALS['mmb_climit_text']; ?>, <?php echo json_encode($GLOBALS['mmb_climit_excs'] ?? array(), JSON_UNESCAPED_UNICODE); ?>);
        })();</script>
        <?php } ?>

        <div class="action-check">
            <?php if ($character['ch_state'] === '승인') { ?>
                <select name="action" id="action_<?php echo (int)$list_item['wr_id']; ?>">
                    <option value="">일반행동</option>
                    <?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('bbs_comment_form_action_options', '', $mmb_comment_form_ctx); ?>
                </select>
                <?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('bbs_comment_form_after_action', '', $mmb_comment_form_ctx); ?>
                <?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('bbs_comment_form_options', '', $mmb_comment_form_ctx); ?>
            <?php } ?>
            <?php if (!empty($board['bo_use_noname']) && !empty($is_member)) { ?>
                <input type="checkbox" name="wr_noname" id="wr_noname_<?php echo $list_item['wr_id']; ?>" value="1" />
                <label for="wr_noname_<?php echo $list_item['wr_id']; ?>">익명</label>
            <?php } ?>
        </div>
      <?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('bbs_comment_form_extra', '', $mmb_comment_form_ctx); ?>
    </div>
    <div class="btn_confirm">
        <button type="submit" class="ui-comment-submit ui-btn">입력</button>
    </div>
    </form>
</aside>
<?php } ?>

<!-- } 댓글 쓰기 끝 -->
