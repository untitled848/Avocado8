<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
$inven_list = array();
$p_count = 0;

// 개인 아이템 - 선물
$pe_inven_sql = "select * from {$g5['inventory_table']} inven, {$g5['item_table']} item where inven.ch_id = '$ch_id' and item.it_id = inven.it_id and inven.se_ch_id != '' ".((function_exists('mdl_apply_filters') ? mdl_apply_filters('inventory_list_where', '') : ''))." order by inven.it_id asc";
$pe_inven_result = sql_query($pe_inven_sql);
for($i=0; $row=sql_fetch_array($pe_inven_result); $i++) {
	$inven_list[$p_count] = $row;
	$p_count++;
}

// 개인 아이템 - 비선물
$pe_inven_sql = "select *, count(*) as cnt from {$g5['inventory_table']} inven, {$g5['item_table']} item where inven.ch_id = '$ch_id' and item.it_id = inven.it_id and inven.se_ch_id = '' ".((function_exists('mdl_apply_filters') ? mdl_apply_filters('inventory_list_where', '') : ''))." group by inven.it_id order by inven.it_id asc";
$pe_inven_result = sql_query($pe_inven_sql);
for($i; $row=sql_fetch_array($pe_inven_result); $i++) {
	$inven_list[$p_count] = $row;
	$p_count++;
}
$i = 0;



// 레거시 G5_THEME_PATH 오버라이드 제거 (테마가 다시 이 파일을 include 하면 무한루프 가능)
include(function_exists('theme_dir_file') ? theme_dir_file('inventory/skin/list.skin.php') : (G5_PATH."/inventory/skin/list.skin.php"));

?>
