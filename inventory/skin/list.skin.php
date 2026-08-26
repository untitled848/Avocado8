<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
?>


<ul class="inventory-list">
<?php  
for($i=0; $i < count($inven_list); $i++) { ?>
	<li class="item-box">
<?php  if($inven_list[$i]['in_id']){ ?>
		<a href="#<?php echo $inven_list[$i]['in_id']?>" class="inven-open-popup" data-idx="<?php echo $inven_list[$i]['in_id']?>" data-type="">
			<img src="<?php echo $inven_list[$i]['it_img']?>" />
		<?php  if($inven_list[$i]['cnt'] > 1) { ?>
			<i class="count"><?php echo $inven_list[$i]['cnt']?></i>
		<?php  } ?>
		<?php  if($inven_list[$i]['se_ch_id'] != '') { ?>
			<i class="present"></i>
		<?php  } ?>
		</a>
<?php  } ?>
	</li>
<?php  } 

if($i == 0) { 
?>
	<li class="no-data">
		보유중인 아이템이 없습니다.
	</li>
<?php  } ?>
</ul>
