<?php
include_once('./_common.php');

$ch = get_character($ch_id);
if($ch['mb_id'] != $member['mb_id']) {
	alert("등록권한이 없습니다.");
}

$_extra_sql = '';
if (function_exists('mdl_apply_filters')) {
	$_extra_sql = (string)mdl_apply_filters('relation_save_columns', '', array(
		'mode' => ($w === 'u' ? 'edit' : 'add'),
		'rm_id'=> isset($_POST['rm_id']) ? (int)$_POST['rm_id'] : 0,
		'post' => $_POST,
	));
}

if($w == '') {
	// 신규 입력
	if(!$_POST['re_ch_id']) {
		$re_ch = sql_fetch("select * from {$g5['character_table']} where ch_name = '{$search_re_ch_name}'");
		$_POST['re_ch_id'] = $re_ch['ch_id'];
	}

	$sql = " insert into {$g5['relation_table']}
				set ch_id = '{$_POST['ch_id']}',
					re_ch_id = '{$_POST['re_ch_id']}',
					rm_like = '{$_POST['rm_like']}',
					rm_link = '{$_POST['rm_link']}',
					rm_order = '{$_POST['rm_order']}',
					rm_memo = '{$_POST['rm_memo']}'
					{$_extra_sql}";
	sql_query($sql);

	$_saved_rm_id = (int)sql_insert_id();

	if (function_exists('mdl_do_action')) {
		mdl_do_action('relation_save_after', $_saved_rm_id, 'add', $_POST);
	}

} else if($w == 'u') {
	// 수정사항
	$sql = " update {$g5['relation_table']}
				set rm_like = '{$_POST['rm_like']}',
					rm_link = '{$_POST['rm_link']}',
					rm_order = '{$_POST['rm_order']}',
					rm_memo = '{$_POST['rm_memo']}'
					{$_extra_sql}
				where rm_id = '{$_POST['rm_id']}'
				";
	sql_query($sql);

	if (function_exists('mdl_do_action')) {
		mdl_do_action('relation_save_after', (int)$_POST['rm_id'], 'edit', $_POST);
	}

}

// return_url 검증 (스킴/호스트 차단)
$_return = isset($_POST['return_url']) ? trim((string)$_POST['return_url']) : '';
if ($_return === '' || preg_match('#^(https?:)?//#i', $_return) || strpos($_return, "\n") !== false || strpos($_return, "\r") !== false) {
	$_return = './viewer.php?ch_id='.(int)$ch_id;
}
goto_url($_return);
?>
