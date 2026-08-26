<?php 
include_once("./_common.php");


if(!$is_member) { 
	echo "<ul><li class='no-data'>회원만 검색 가능합니다.</li></ul>";
} else {
	if($keyword == "") { 
		echo "<ul><li class='no-data'>키워드를 입력해 주시길 바랍니다.</li></ul>";
	} else {
		echo "<ul>";
		
		if($option == 'user') {
			$sql = " select ch_thumb, ch_name, ch_id, mb_id from {$g5['character_table']} where ch_name like '%{$keyword}%' and ch_state = '승인' and mb_id != '{$config['cf_admin']}' and ch_type = 'main' order by ch_name asc";
		} else {
			$sql = " select ch_thumb, ch_name, ch_id, mb_id from {$g5['character_table']} where ch_name like '%{$keyword}%' and ch_state = '승인' order by ch_name asc";
		}

		$result = sql_query($sql);
		$_search_time = function_exists('get_current_time') ? get_current_time() : 1;
		for($i=0; $row = sql_fetch_array($result); $i++) {
			if (function_exists('apply_character_time')) apply_character_time($row, $_search_time);
	?>
				<li>
					<a href="#" onclick="select_item('<?php echo $list_obj?>', '<?php echo $input_obj?>', '<?php echo $row['ch_name']?>', '<?php echo $output_obj?>', '<?php echo $row['ch_id']?>'); return false;">
						<strong class="point">[<?php echo $row['ch_name']?>]</strong> <?php echo get_member_name($row['mb_id'])?>
					</a>
				</li>
	<?php 
		}
		if($i==0) { 
			echo "<li class='no-data' style='line-height:1.2; padding:5px 5px 15px; font-size:11px; word-break:keep-all;'>[ ".$keyword." ]에 대한 검색결과가 존재하지 않습니다.</li>";
		}
		echo "</ul>";
	}
}
?>