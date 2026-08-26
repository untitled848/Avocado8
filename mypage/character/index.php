<?php
include_once('./_common.php');
include_once('./_head.php');

$ch_array = array();
$character_result = sql_query("select * from {$g5['character_table']} where mb_id = '{$member['mb_id']}' and ch_state != '삭제'");
$_idx_time = function_exists('get_current_time') ? get_current_time() : 1;
for($i=0; $row = sql_fetch_array($character_result); $i++) { 
	if (function_exists('apply_character_time')) apply_character_time($row, $_idx_time);
	$ch_array[$i] = $row;
}

if($i==1) { 
	//goto_url('./viewer.php?ch_id='.$ch_array[0]['ch_id']);
}
?>

<section>
	<div>
		<table class="theme-form">
			<colgroup>
				<col style="width: 110px;" />
			</colgroup>
			<tbody>
				<tr>
					<th>대표캐릭터</th>
					<td>
						<form name="frm_main_character" action="./maincharacter_update.php" method="post">
							<input type="hidden" name="mb_id" value="<?php echo $member['mb_id']?>" />
							<input type="hidden" name="return_url" value="character" />
							<select name="ch_id" id="ch_id">
								<option value="">대표캐릭터 선택</option>
						<?php 	for($i = 0; $i < count($ch_array); $i++) { $ch = $ch_array[$i]; ?>
								<option value="<?php echo $ch['ch_id']?>" <?php echo $member['ch_id'] == $ch['ch_id'] ? "selected" : ""?>>
									<?php echo $ch['ch_name']?>
								</option>
						<?php  } ?>
							<select>
							<input type="submit" value="변경" class="ui-btn"/>
						</form>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</section>

<section>
	<div class="list-character-box">
<?php 	for($i = 0; $i < count($ch_array); $i++) { $ch = $ch_array[$i]; ?>
		<div class="item theme-box">
			<a href="./viewer.php?ch_id=<?php echo $ch['ch_id']?>">
				<?php  if($ch['ch_thumb']) { ?>
					<em style="background-image:url('<?php echo $ch['ch_thumb']?>');"></em>
				<?php  } else { ?>
					<em></em>
				<?php  } ?>
				<strong><?php echo $ch['ch_name'] ?></strong>
				<?php  if($config['cf_side_title']) { ?>
					<span><?php echo get_side_name($ch['ch_side'])?></span>
				<?php  } ?>
				<?php  if($config['cf_class_title']) { ?>
					<span><?php echo get_class_name($ch['ch_class'])?></span>
				<?php  } ?>
				<?php  if($article['ad_use_rank']) { ?>
					<span>
						<?php echo get_rank_name($ch['ch_rank']) ?> 
					</span>
				<?php  } ?>
				<?php  if($article['ad_use_exp']) { ?>
					<span>
						<?php echo $ch['ch_exp'].$config['cf_exp_pice'] ?> 
					</span>
				<?php  } ?>
			</a>
		</div>
<?php  } ?>
	</div>

<?php
	$_max_ch = (int)($config['cf_character_count'] ?? 0);
	$_can_create = $is_admin || ($is_add_character && ($_max_ch === 0 || $i < $_max_ch));
	if ($_can_create) { ?>
	<div class="txt-center">
		<a href="./character_form.php" class="ui-btn point">신규 캐릭터 등록</a>
	</div>
<?php  } ?>
</section>


<?php
include_once('./_tail.php');
?>
