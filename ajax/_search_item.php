<?php 
include_once("./_common.php");


if(!$is_member) { 
	echo "<ul><li class='no-data'>회원만 검색 가능합니다.</li></ul>";
} else {
	if($keyword == "") { 
		echo "<ul><li class='no-data'>키워드를 입력해 주시길 바랍니다.</li></ul>";
	} else {
		$keyword_safe = sql_real_escape_string($keyword);

		// fortune 옵션: 운명 토큰 아이템 테이블에서 검색
		if ($option === 'fortune') {
			$tbl = $g5['fortune_token_table'];
			echo "<ul>";
			$sql = " select it_img, it_name, it_id, it_content from {$tbl} where it_name like '%{$keyword_safe}%' order by it_name asc";
		} else {
			$tbl = $g5['item_table'];
			echo "<ul>";
			$sql = " select it_img, it_name, it_id, it_content from {$tbl} where it_name like '%{$keyword_safe}%'";
			if($option) {
				if($option == '뽑기') {
					$sql .= " and it_type = '{$option}'";
				} else if($option == '레시피') {
					$sql .= " and it_use_recepi = '1'";
				}
			}
			$sql .= " order by it_name asc";
		}
		$result = sql_query($sql);
		for($i=0; $row = sql_fetch_array($result); $i++) {
	?>
				<li>
					<a href="#" onclick="select_item('<?php echo $list_obj?>', '<?php echo $input_obj?>', '<?php echo $row['it_name']?>', '<?php echo $output_obj?>', '<?php echo $row['it_id']?>'); return false;">
						<div class="ui-thumb">
							<img src="<?php echo htmlspecialchars($row['it_img'], ENT_QUOTES, 'UTF-8')?>">
						</div>
						<div class="ui-info">
							<p class="point"><strong>아이템</strong>: <?php echo $row['it_name']?></p>
							<p><strong>설명</strong>: <?php echo $row['it_content']?></p>
						</div>
					</a>
				</li>
	<?php 
		}
		if($i==0) { 
			echo "<li class='no-data'>[ ".$keyword." ]에 대한 검색결과가 존재하지 않습니다.</li>";
		}
		echo "</ul>";
	}
}
?>