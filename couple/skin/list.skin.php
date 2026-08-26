<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/style.couple.css">', 0);
?>

<div id="couple_page">

	<div id="couple_list">
		<ul>
			<?php  for($i=0; $i < count($list); $i++) { ?>
				<li>
					<div class="visual">
						<a href="<?php echo G5_URL?>/member/viewer.php?ch_id=<?php echo $list[$i]['left']['idx']?>" class="left" target="_blank">
							<img src="<?php echo $list[$i]['left']['thumb']?>" />
						</a>

						<a href="<?php echo G5_URL?>/member/viewer.php?ch_id=<?php echo $list[$i]['right']['idx']?>" class="right" target="_blank">
							<img src="<?php echo $list[$i]['right']['thumb']?>" />
						</a>
					</div>

					<p>
						<?php echo $list[$i]['left']['name']?> ♥ <?php echo $list[$i]['right']['name']?> 커플<br />
						<?php echo $list[$i]['dday']?>일 째입니다.
					</p>
				</li>
			<?php  } ?>
		</ul>

	</div>
</div>
