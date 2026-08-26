<?php
$sub_menu = "310100";
include_once('./_common.php');

if ($w == "u" || $w == "d")
	check_demo();

if ($w == 'd')
	auth_check($auth[$sub_menu], "d");
else
	auth_check($auth[$sub_menu], "w");

check_admin_token();

@mkdir(G5_DATA_PATH."/world", G5_DIR_PERMISSION);
@chmod(G5_DATA_PATH."/world", G5_DIR_PERMISSION);

if ($wo_himg_del)  { $_p = G5_DATA_PATH."/world/{$wo_id}_h"; if (function_exists('cdn_unlink')) { @cdn_unlink($_p); } else { @unlink($_p); } }
if ($wo_timg_del)  { $_p = G5_DATA_PATH."/world/{$wo_id}_t"; if (function_exists('cdn_unlink')) { @cdn_unlink($_p); } else { @unlink($_p); } }

// CDN: 본문 저장 시 토큰화
if (function_exists('cdn_tokenize_content') && isset($wo_content)) {
	$wo_content = cdn_tokenize_content($wo_content);
}

$error_msg = '';

if ($wo_include_head && !is_include_path_check($wo_include_head)) {
	$wo_include_head = '';
	$error_msg = '/data/file/ 또는 /data/editor/ 포함된 문자를 상단 파일 경로에 포함시킬수 없습니다.';
}

if ($wo_include_tail && !is_include_path_check($wo_include_tail)) {
	$wo_include_tail = '';
	$error_msg = '/data/file/ 또는 /data/editor/ 포함된 문자를 하단 파일 경로에 포함시킬수 없습니다.';
}

$sql_common = " wo_html             = '$wo_html',
                wo_subject          = '$wo_subject',
                wo_content          = '$wo_content',
                wo_mobile_content   = '$wo_mobile_content',
                wo_skin             = '$wo_skin',
                wo_mobile_skin      = '$wo_mobile_skin' ";

if ($w == "")
{
	if (preg_match("/[^a-z0-9_]/i", $wo_id)) alert("ID 는 영문자, 숫자, _ 만 가능합니다.");

	$sql = " select wo_id from {$g5['world_table']} where wo_id = '$wo_id' ";
	$row = sql_fetch($sql);
	if ($row['wo_id'])
		alert("이미 같은 ID로 등록된 페이지가 있습니다.");

	$sql = " insert {$g5['world_table']}
				set wo_id = '$wo_id',
					$sql_common ";
	sql_query($sql);
}
else if ($w == "u")
{
	$sql = " update {$g5['world_table']}
				set $sql_common
			  where wo_id = '$wo_id' ";
	sql_query($sql);
}
else if ($w == "d")
{
	$_p1 = G5_DATA_PATH."/world/{$wo_id}_h"; $_p2 = G5_DATA_PATH."/world/{$wo_id}_t";
	if (function_exists('cdn_unlink')) { @cdn_unlink($_p1); @cdn_unlink($_p2); } else { @unlink($_p1); @unlink($_p2); }

	$sql = " delete from {$g5['world_table']} where wo_id = '$wo_id' ";
	sql_query($sql);
}

if ($w == "" || $w == "u")
{
	if ($_FILES['wo_himg']['name'])
	{
		$dest_path = G5_DATA_PATH."/world/".$wo_id."_h";
		@move_uploaded_file($_FILES['wo_himg']['tmp_name'], $dest_path);
		@chmod($dest_path, G5_FILE_PERMISSION);
		if (function_exists('cdn_after_upload')) cdn_after_upload($dest_path);
	}
	if ($_FILES['wo_timg']['name'])
	{
		$dest_path = G5_DATA_PATH."/world/".$wo_id."_t";
		@move_uploaded_file($_FILES['wo_timg']['tmp_name'], $dest_path);
		@chmod($dest_path, G5_FILE_PERMISSION);
		if (function_exists('cdn_after_upload')) cdn_after_upload($dest_path);
	}

	if ($error_msg) {
		alert($error_msg, "./world_form.php?w=u&amp;wo_id=$wo_id");
	} else {
		goto_url("./world_form.php?w=u&amp;wo_id=$wo_id");
	}
}
else
{
	goto_url("./world_list.php");
}
?>
