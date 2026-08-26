<?php
$sub_menu = "100400";
include_once('./_common.php');

check_demo();

if ($w == 'd')
	auth_check($auth[$sub_menu], "d");
else
	auth_check($auth[$sub_menu], "w");


$intro_path = G5_DATA_PATH."/banner";
$intro_url = G5_DATA_URL."/banner";

@mkdir($intro_path, G5_DIR_PERMISSION);
@chmod($intro_path, G5_DIR_PERMISSION);



if ($w=="" || $w=="u") {
	// bn_time 출력 시기 (배열 → ||1||2|| 또는 '')
	$bn_time_in = isset($_POST['bn_time']) ? $_POST['bn_time'] : array();
	$bn_time = function_exists('shop_time_to_db') ? shop_time_to_db($bn_time_in) : '';
	$bn_time = sql_escape_string($bn_time);

	if ($w=="") { 
		$sql = " insert into {$g5['intro_table']}
					set bn_img        = '{$bn_img}',
						bn_m_img      = '{$bn_m_img}',
						bn_alt        = '{$bn_alt}',
						bn_url        = '{$bn_url}',
						bn_new_win    = '{$bn_new_win}',
						bn_begin_time = '{$bn_begin_time}',
						bn_end_time   = '{$bn_end_time}',
						bn_order      = '{$bn_order}',
						bn_time       = '{$bn_time}'";
		sql_query($sql);
		$bn_id = sql_insert_id();

	} else if ($w=="u") {

		$sql = " update {$g5['intro_table']}
					set bn_img        = '{$bn_img}',
						bn_m_img      = '{$bn_m_img}',
						bn_alt        = '{$bn_alt}',
						bn_url        = '{$bn_url}',
						bn_new_win    = '{$bn_new_win}',
						bn_begin_time = '{$bn_begin_time}',
						bn_end_time   = '{$bn_end_time}',
						bn_order      = '{$bn_order}',
						bn_time       = '{$bn_time}'
				  where bn_id = '{$bn_id}' ";
		sql_query($sql);

	}

	// 이미지 등록 시, 이미지를 업로드한 뒤 - 해당 이미지 경로를 삽입
	if ($_FILES['bn_img_file']['name']) {
		// 확장자 따기
		$intro_name = "intro_".$bn_id;
		upload_file($_FILES['bn_img_file']['tmp_name'], $intro_name, $intro_path);
		if (function_exists('cdn_after_upload')) cdn_after_upload($intro_path.'/'.$intro_name);
		$bn_img = $intro_url."/".$intro_name;

		$sql = " update {$g5['intro_table']}
				set bn_img	= '{$bn_img}'
			  where bn_id = '{$bn_id}' ";
		sql_query($sql);
	}

	// 이미지 등록 시, 이미지를 업로드한 뒤 - 해당 이미지 경로를 삽입
	if ($_FILES['bn_m_img_file']['name']) {
		// 확장자 따기
		$intro_name = "intro_".$bn_id."_m";
		upload_file($_FILES['bn_m_img_file']['tmp_name'], $intro_name, $intro_path);
		if (function_exists('cdn_after_upload')) cdn_after_upload($intro_path.'/'.$intro_name);
		$bn_img = $intro_url."/".$intro_name;

		$sql = " update {$g5['intro_table']}
				set bn_m_img	= '{$bn_img}'
			  where bn_id = '{$bn_id}' ";
		sql_query($sql);
	}

} else if ($w=="d") {

	$_p1 = $intro_path."/intro_".$bn_id;
	$_p2 = $intro_path."/intro_".$bn_id."_m";
	if (function_exists('cdn_unlink')) { @cdn_unlink($_p1); @cdn_unlink($_p2); } else { @unlink($_p1); @unlink($_p2); }

	$sql = " delete from {$g5['intro_table']} where bn_id = '{$bn_id}' ";
	$result = sql_query($sql);
}

goto_url("./intro_list.php");

?>
