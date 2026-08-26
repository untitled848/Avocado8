<?php
$sub_menu = "100300";
include_once('./_common.php');

check_demo();

if ($w == 'd')
	auth_check($auth[$sub_menu], "d");
else
	auth_check($auth[$sub_menu], "w");


$banner_path = G5_DATA_PATH."/banner";
$banner_url = G5_DATA_URL."/banner";

@mkdir($banner_path, G5_DIR_PERMISSION);
@chmod($banner_path, G5_DIR_PERMISSION);

$add_banner = "";

// 이미지 등록 시, 이미지를 업로드한 뒤 - 해당 이미지 경로를 삽입
if ($_FILES['bn_img_file']['name']) {
	// 확장자 따기
	// 확장자 따기
	$exp = explode(".", $_FILES['bn_img_file']['name']);
	$exp = $exp[count($exp)-1];
	$banner_name = "visual_".time().".".$exp;
	upload_file($_FILES['bn_img_file']['tmp_name'], $banner_name, $banner_path);
	if (function_exists('cdn_after_upload')) cdn_after_upload($banner_path.'/'.$banner_name);
	$bn_img = $banner_url."/".$banner_name;
}

// 이미지 등록 시, 이미지를 업로드한 뒤 - 해당 이미지 경로를 삽입
if ($_FILES['bn_m_img_file']['name']) {
	// 확장자 따기
	$exp = explode(".", $_FILES['bn_m_img_file']['name']);
	$exp = $exp[count($exp)-1];
	$banner_name = "visual_".time()."_m.".$exp;
	upload_file($_FILES['bn_m_img_file']['tmp_name'], $banner_name, $banner_path);
	if (function_exists('cdn_after_upload')) cdn_after_upload($banner_path.'/'.$banner_name);
	$bn_m_img = $banner_url."/".$banner_name;
}


if ($w=="" || $w=="u") {
	// bn_time 출력 시기 (배열 → ||1||2|| 또는 '')
	$bn_time_in = isset($_POST['bn_time']) ? $_POST['bn_time'] : array();
	$bn_time = function_exists('shop_time_to_db') ? shop_time_to_db($bn_time_in) : '';
	$bn_time = sql_escape_string($bn_time);

	if ($w=="") { 
		$sql = " insert into {$g5['banner_table']}
					set bn_img        = '$bn_img',
						bn_m_img      = '$bn_m_img',
						bn_alt        = '$bn_alt',
						bn_url        = '$bn_url',
						bn_new_win    = '$bn_new_win',
						bn_begin_time = '$bn_begin_time',
						bn_end_time   = '$bn_end_time',
						bn_order      = '$bn_order',
						bn_time       = '$bn_time'";
		sql_query($sql);

	} else if ($w=="u") {

		$sql = " update {$g5['banner_table']}
					set bn_img        = '$bn_img',
						bn_m_img      = '$bn_m_img',
						bn_alt        = '$bn_alt',
						bn_url        = '$bn_url',
						bn_new_win    = '$bn_new_win',
						bn_begin_time = '$bn_begin_time',
						bn_end_time   = '$bn_end_time',
						bn_order      = '$bn_order',
						bn_time       = '$bn_time'
				  where bn_id = '$bn_id' ";
		sql_query($sql);

	}
} else if ($w=="d") {

	$sql = " select * from {$g5['banner_table']} where bn_id = '$bn_id' ";
	$bn = sql_fetch($sql);

	$_bn_img_path   = $bn['bn_img']   ? str_replace(G5_URL, G5_PATH, $bn['bn_img'])   : '';
	$_bn_m_img_path = $bn['bn_m_img'] ? str_replace(G5_URL, G5_PATH, $bn['bn_m_img']) : '';
	if ($_bn_img_path)   { if (function_exists('cdn_unlink')) { @cdn_unlink($_bn_img_path); }   else { @unlink($_bn_img_path); } }
	if ($_bn_m_img_path) { if (function_exists('cdn_unlink')) { @cdn_unlink($_bn_m_img_path); } else { @unlink($_bn_m_img_path); } }

	$sql = " delete from {$g5['banner_table']} where bn_id = $bn_id ";
	$result = sql_query($sql);
}


goto_url("./banner_list.php");

?>
