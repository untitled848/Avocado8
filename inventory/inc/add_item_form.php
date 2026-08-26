<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$maker_ch_id = isset($ch['ch_id']) ? (int)$ch['ch_id'] : 0;
$maker_items = function_exists('get_maker_item_list')
	? get_maker_item_list($maker_ch_id, '일반', '개인')
	: array();
$reuse_sel_id = 'add_item_reuse_'.(isset($in['in_id']) ? (int)$in['in_id'] : 0);
$maker_reuse_only = false;
?>

<div class="info">
	<div>
		<img src="<?php echo $in['it_img']?>" />
	</div>
</div>
<div class="text">
	<p class="title">
		<?php echo $in['it_name']?>
		<span><?php echo number_format($in['it_sell'])?><?php echo $config['cf_money_pice']?></span>
	</p>
	<div>
		<p><?php echo $in['it_content']?></p>
	</div>
</div>
<form action="<?php echo G5_URL?>/inventory/item_form_update.php" method="post" name="frmItemAdd" enctype="multipart/form-data">
	<input type="hidden" name="type" value="add" />
	<input type="hidden" name="in_id" id="a_item_add_in_id" value="<?php echo $in['in_id']?>" />
	<input type="hidden" name="ch_id" id="a_item_add_ch_id" value="<?php echo $ch['ch_id']?>" />
	<input type="hidden" name="url" value="<?php echo $url?>" />
	<?php include G5_PATH.'/inventory/inc/maker_item_select.inc.php'; ?>
	<div class="add-item-form">
		<div class="item-info">
			<input type="file" name="it_img" id="inven_it_img" class="required" required/>
		</div>
		<div class="item-input">
			<input type="text" name="it_name" class="frm_input required" placeholder="아이템 이름" required/>
		</div>
		<div class="item-input">
			<input type="text" name="it_content" class="frm_input required" placeholder="아이템 설명" required/>
		</div>
	</div>
	<div class="control-box">
		<button type="submit" class="ui-btn simple js-add-item-submit">등록하기</button>
	</div>
</form>
