<?php 
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
?>

<div class="message-item <?php echo $class?>">
	<div class="thumb">
	<?php  if($ch['ch_thumb']) {?>
		<a href="<?php echo G5_URL?>/member/viewer.php?ch_id=<?php echo $ch['ch_id']?>">
			<img src="<?php echo $ch['ch_thumb']?>" />
		</a>
	<?php } ?>
	</div>
	<div class="detail theme-box">
		<div class="info">
			<p class="name"><?php  if($ch['ch_name']) { ?>[<?php echo $ch['ch_name']?>] <?php  } ?><?php echo $mb['mb_name']?></p>
			<p class="date"><?php echo date('m-d H:i', strtotime($me['me_send_datetime']))?></p>
			<p class="check">
			<?php  if($me['me_read_datetime'] == '0000-00-00 00:00:00' && $mb['mb_id'] == $member['mb_id']) { ?>
				***
			<?php  } else { ?>
				읽음
			<?php  } ?>

			<?php  if($del){ ?>
				<a href="<?php echo $del?>" onclick="return confirm('정말 삭제하시겠습니까? 상대방의 우편함의 내용도 함께 삭제 됩니다.');" class="ui-btn ico del">삭제</a>
			<?php  } ?>
			</p>
		</div>
		<div class="text">
			<?php
			$_memo_html = conv_content($me['me_memo'], 0);
			if (!empty($config['cf_use_memo_emoticon']) && function_exists('emote_ev')) $_memo_html = emote_ev($_memo_html);
			echo $_memo_html;
			?>
		</div>
	</div>
</div>