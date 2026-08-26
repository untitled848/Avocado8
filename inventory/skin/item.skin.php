<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
?>


<div class="info">
	<div class="item-box">
		<img src="<?php echo $in['it_img']?>" />
	</div>
</div>
<div class="text">
	<p class="title">
		<?php echo $in['it_name']?>
		<span><?php echo number_format($in['it_sell'])?><?php echo $config['cf_money_pice']?></span>
	</p>
	<div class="item-content-box">
		<div class="default">
			<?php echo $in['it_content']?>
		</div>
	<?php  if($in['it_content2']) { ?>
		<div class="effect">
			<?php echo $in['it_content2']?>
		</div>
	<?php  }?>
	<?php  if($in['se_ch_name']) { ?>
		<div class="memo">
			<?php  if($in['in_memo']) { ?><p><?php echo $in['in_memo']?></p><?php  } ?>
			<p style="text-align: right;">By. <?php echo $in['se_ch_name']?></p>
		</div>
	<?php  }?>
	</div>
</div>
<div class="control-box">
<?php  if($is_mine) { ?>
	<ul>
		<?php  if($in['it_use_sell']) { ?>
			<li><a href="javascript:fn_inven_link_event('<?php echo $in['in_id']?>', 'sell');" data-idx="<?php echo $in['in_id']?>" data-type="sell" class="ui-style-btn">판매하기</a></li>
		<?php  }?>
		<?php  if($in['it_use_able']) { ?>
			<li><a href="javascript:fn_inven_link_event('<?php echo $in['in_id']?>', 'use');" data-idx="<?php echo $in['in_id']?>" data-type="use" class="ui-style-btn">사용하기</a></li>
		<?php  } ?>
		<?php  if(!$in['it_has']) { ?>
			<li><a href="javascript:fn_inven_link_event('<?php echo $in['in_id']?>', 'take');" data-idx="<?php echo $in['in_id']?>" data-type="take" class="ui-style-btn">선물하기</a></li>
		<?php  } ?>
	</ul>
<?php  } ?>
</div>