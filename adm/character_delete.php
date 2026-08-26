<?php 
$sub_menu = "400200";
include_once("./_common.php");

check_demo();

auth_check($auth[$sub_menu], "d");

$ch = sql_fetch("select * from {$g5['character_table']} where ch_id = '{$ch_id}'");

if (!$ch['ch_id']) {
	alert("{$ch['ch_id']} : 캐릭터 자료가 존재하지 않습니다.");
} else {
	
	$prev_file_path = str_replace(G5_URL, G5_PATH, $ch['ch_thumb']);
	if (function_exists('cdn_unlink')) { @cdn_unlink($prev_file_path); } else { @unlink($prev_file_path); }
	$prev_file_path = str_replace(G5_URL, G5_PATH, $ch['ch_head']);
	if (function_exists('cdn_unlink')) { @cdn_unlink($prev_file_path); } else { @unlink($prev_file_path); }
	$prev_file_path = str_replace(G5_URL, G5_PATH, $ch['ch_body']);
	if (function_exists('cdn_unlink')) { @cdn_unlink($prev_file_path); } else { @unlink($prev_file_path); }

	sql_query(" delete from {$g5['character_table']} where ch_id = '{$ch['ch_id']}' ");
	sql_query(" delete from {$g5['value_table']} where ch_id = '{$ch['ch_id']}' ");
	sql_query(" delete from {$g5['exp_table']} where ch_id = '{$ch['ch_id']}' ");
	sql_query(" delete from {$g5['title_has_table']} where ch_id = '{$ch['ch_id']}' ");
	sql_query(" delete from {$g5['closthes_table']} where ch_id = '{$ch['ch_id']}' ");
	sql_query(" delete from {$g5['inventory_table']} where ch_id = '{$ch['ch_id']}' ");

	// 캐릭터 제거 훅 — 모듈/코어 서브시스템 연동 (reset_manager 자동 정리 등)
	if (function_exists('mdl_do_action')) {
		mdl_do_action('character_delete_after', $ch['ch_id'], array('mb_id' => $ch['mb_id'], 'context' => 'admin'));
	}

	// 시기별 사이드 테이블 (00_time.lib.php)
	if (!empty($g5['time_value_table'])) {
		sql_query(" delete from {$g5['time_value_table']} where ch_id = '{$ch['ch_id']}' ", false);
	}
	if (!empty($g5['time_article_table'])) {
		sql_query(" delete from {$g5['time_article_table']} where ch_id = '{$ch['ch_id']}' ", false);
	}

	$sql = " update {$g5['member_table']}
				set ch_id = ''
				where mb_id = '{$ch['mb_id']}' and ch_id = '{$ch['ch_id']}' ";
	sql_query($sql);
}

goto_url("./character_list.php?$qstr");
?>
