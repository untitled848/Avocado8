<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 

$_cl_time = isset($time) ? (int)$time : get_current_time();
if ($_cl_time < 1) $_cl_time = 1;

// 시기 선택 (의상은 시기별로 따로 관리)
$_cl_is_admin = !empty($GLOBALS['is_admin']);
print_time_tabs($_cl_time, (bool)$_cl_is_admin, true);

$cl_count = sql_fetch("select count(*) as cnt from {$g5['closthes_table']} where ch_id = '{$ch_id}' and cl_time = '{$_cl_time}'");
$cl_count = $cl_count['cnt'];
$cl_result = sql_query("select * from {$g5['closthes_table']} where ch_id = '{$ch_id}' and cl_time = '{$_cl_time}' order by cl_id asc");
?>

<div class="closet-list">
	<form action="<?php echo G5_URL?>/mypage/character/closet_update.php" method="post" name="frm_closet" id="frm_closet" enctype="multipart/form-data">
		<input type="hidden" name="ch_id" value="<?php echo $ch_id?>" />
		<input type="hidden" name="cl_time" value="<?php echo (int)$_cl_time?>" />
		<fieldset>
			<input type="text" name="cl_subject" id="cl_sibject" value="" class="full" placeholder="의상 이름" />
			<?php  if($article['ad_url_body']) { ?>
			<input type="text" name="cl_path" id="cl_path" value="" class="full" placeholder="의상경로" />
			<?php  } else { ?>
			<input type="file" name="cl_path_file" id="cl_path_file" value="" class="full" />
			<?php  } ?>
			<input type="submit" value="추가" class="ui-btn point"/>
		</fieldset>
	</form>
	<form name="fcloset" action="./cloest_path_update.php" method="post"  onsubmit="return confirm('의상정보를 수정하시겠습니까?');" autocomplete="off">
		<input type="hidden" name="ch_id" value="<?php echo $ch_id?>" />
		<input type="hidden" name="cl_time" value="<?php echo (int)$_cl_time?>" />
	<?php  if($cl_count > 0) { ?>
		<hr class="line" />
		<ul class="theme-box">
	<?php 
	$modify_index = 0;
	for($i=0; $row=sql_fetch_array($cl_result); $i++) { 
		$class = "";

		$class .= $row['cl_type'];
		if($row['cl_use'] == '1') { 
			$class .=' selected ';
		}
	?>
			<li class="<?php echo $class?>" style="padding-right:110px;">
				<a href="<?php echo $row['cl_path']?>" style="display:block; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"  onclick="window.open(this.href, 'big_viewer', 'width=500 height=800 menubar=no status=no toolbar=no location=no scrollbars=yes resizable=yes'); return false;">
					<?php echo $row['cl_subject']?>
				</a>
				<?php  if($row['cl_type'] !='default') { ?>
				<div class="closet-modify" id="modify_closest_<?php echo $i?>">
					<input type="hidden" name="cl_id[<?php echo $modify_index?>]" value="<?php echo $row['cl_id']?>" />
					<input type="text" name="cl_path[<?php echo $modify_index?>]" value="<?php echo $row['cl_path']?>" />
					<button type="submit" class="ui-btn point" style="height:30px; padding:0;">수정</button>
				</div>
				<?php  $modify_index++; } ?>
				<div class="ui-btn-box" style="width:110px;">
					<a href="./closet_update.php?w=u&amp;cl_id=<?php echo $row['cl_id']?>&amp;ch_id=<?php echo $ch_id?>" class="ui-btm btn-use">사용</a>
				<?php  if($row['cl_type'] !='default'){ ?>
					<a href="javascript:;" class="ui-btm btn-modify" title="수정하기">수정</a>
					<a href="./closet_update.php?w=d&amp;cl_id=<?php echo $row['cl_id']?>&amp;ch_id=<?php echo $ch_id?>" onclick="return confirm('삭제한 데이터는 복구할 수 없습니다. 정말 삭제하시겠습니까?');" class="ui-btm btn-del">삭제</a>
				<?php  } ?>
				</div>
			</li>
	<?php  } ?>
		</ul>
	<?php  } ?>

	</form>

</div>

<style>

.closet-list ul .ui-btn-box a.btn-modify:before {content:"\e905";}
.closet-modify {display:none; position:relative; padding-right:50px;}
.state-modify .closet-modify {display:block;}
.closet-modify input {width:100%;}
.closet-modify button {display:block; position:absolute; top:0; right:0; bottom:0; width:50px; text-align:center; background:#dcd394; color:#000;}
</style>