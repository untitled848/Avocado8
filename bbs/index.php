<?php
include_once('./_common.php');

// 진영별로 출력하기
$list = array();
$side = array();
$ch_list = array();

if($config['cf_side_title']) {
	$side_result = sql_query("select * from {$g5['side_table']}"); 
	for($i=0; $si = sql_fetch_array($side_result); $i++) { 
		$list[] = get_character_list($si['si_id']);
		$side[] = $si;
	}
} 

if(!$config['cf_side_title'] || count($side) < 2) {
	$list = array();
	$side = array();

	$list[] = get_character_list();
	$side[] = '';
}


$g5['title'] = "멤버란";
include_once(G5_PATH.'/head.php');

include(function_exists('theme_dir_file') ? theme_dir_file('member/skin/list.skin.php') : (G5_PATH."/member/skin/list.skin.php"));

unset($ch);
unset($list);

include_once(G5_PATH.'/tail.php');
?>
