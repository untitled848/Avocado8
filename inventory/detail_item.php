<?php
include_once('./_common.php');

$in = sql_fetch("select * from {$g5['inventory_table']} inven, {$g5['item_table']} item where inven.in_id = '{$in_id}' and inven.it_id = item.it_id");
$ch = get_character($in['ch_id']);

$is_mine = $ch['mb_id'] == $character['mb_id'] ? true : false;

if(!$in['in_id']) { 
	echo "<p>아이템 보유 정보를 확인할 수 없습니다.</p>";
} else {

	// 레거시 G5_THEME_PATH 오버라이드 제거 (테마가 다시 이 파일을 include 하면 무한루프 가능)
	include(function_exists('theme_dir_file') ? theme_dir_file('inventory/skin/item.skin.php') : (G5_PATH."/inventory/skin/item.skin.php"));

} ?>