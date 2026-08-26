<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 

/* 보유중인 타이틀 정보 */
$ti_result = sql_query("select t.ti_id, t.ti_title from {$g5['title_has_table']} th, {$g5['title_table']} t where th.ti_id = t.ti_id and th.ch_id = '{$ch['ch_id']}'");

$ti = array();

for($i=0; $ti_row = sql_fetch_array($ti_result); $i++) {
	$ti_row['value'] = get_title_value($ti_row['ti_id']);
	$ti[] = $ti_row;
}

if(count($ti) > 0) { ?>

<div class="title-list">
	<?php  for($i=0; $i < count($ti); $i++) {  echo $ti[$i]['value']; } ?>
</div>

<?php  if($ch['mb_id'] == $member['mb_id']) { 
	// 캐릭터 소유주와 접속자의 정보가 동일 할 때
	// 타이틀 변경
?>

<hr class="line" />
타이틀 변경 : 
<select onchange="fn_save_title(this.value, '<?php echo $ch['ch_id']?>');">
	<option value="">타이틀 없음</option>
<?php  for($i=0; $i < count($ti); $i++) { ?>
	<option value="<?php echo $ti[$i]['ti_id']?>" <?php echo $ti[$i]['ti_id'] == $ch['ch_title'] ? "selected" : ""?>><?php echo $ti[$i]['ti_title']?></option>
<?php  } ?>
</select>

<?php  }

} else { 
		echo "<div class='no-data'>보유중인 타이틀이 없습니다.</div>";
}
?>