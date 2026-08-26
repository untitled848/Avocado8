<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if ($is_comment_write) {
	if($w == '') $w = 'c';
?>
<!-- 댓글 쓰기 시작 { -->
<aside class="bo_vc_w" id="bo_vc_w_<?php echo $data['wr_id']?>">
	<form name="fviewcomment" action="./write_comment_update.php" onsubmit="return fviewcomment_submit(this);" method="post" autocomplete="off">
	<input type="hidden" name="w" value="<?php echo $w ?>" id="w">
	<input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
	<input type="hidden" name="wr_id" value="<?php echo $data['wr_id'] ?>">
	<input type="hidden" name="sca" value="<?php echo $sca ?>">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="spt" value="<?php echo $spt ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">

	<input type="hidden" name="ch_id" value="<?php echo $character['ch_id']?>" />
	<input type="hidden" name="wr_subject" value="<?php echo $character['ch_name']?>" />

	<input type="hidden" name="comment_mb_id" value="<?php echo $data['mb_id']?>" />
	<input type="hidden" name="comment_ch_id" value="<?php echo $data['ch_id']?>" />

<?php  if($is_admin) { ?>
	<div class="admin-comment">
		<p>
			<input type="radio" name="state" id="state_<?php echo $data['wr_id']?>_1" value="정산완료" checked/>
			<label for="state_<?php echo $data['wr_id']?>_1">정산완료</label>

			<input type="radio" name="state" id="state_<?php echo $data['wr_id']?>_2" value="반려" />
			<label for="state_<?php echo $data['wr_id']?>_2">반려</label>
		</p>
	</div>
<?php  } ?>

	<div class="input-comment">
		<textarea name="wr_content" required class="required" title="내용"></textarea>
	</div>
	<div class="btn_confirm">
		<button type="submit" class="ui-comment-submit ui-btn">입력</button>
	</div>

	</form>
</aside>
<?php 
}
?>

