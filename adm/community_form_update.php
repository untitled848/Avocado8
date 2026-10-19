<?php
$sub_menu = "100100";
include_once('./_common.php');

check_demo();
auth_check($auth[$sub_menu], 'w');

if (!$is_admin)
	alert('관리자만 접근 가능합니다.');

check_admin_token();


$site_style_path = G5_DATA_PATH."/site";
$site_style_url = G5_DATA_URL."/site";

@mkdir($site_style_path, G5_DIR_PERMISSION);
@chmod($site_style_path, G5_DIR_PERMISSION);

// G5_ESCAPE_FUNCTION 이 $_POST 전체에 addslashes 를 사전 적용하므로
// stripslashes 로 되돌린 뒤 sql_escape_string 을 한 번만 적용한다.
// 최고관리자 (config_form 에서 이전)
$cf_admin = trim(stripslashes((string)($_POST['cf_admin'] ?? '')));
if ($cf_admin !== '') {
	$mb_chk = get_member($cf_admin);
	if (!$mb_chk['mb_id']) alert('최고관리자 회원아이디가 존재하지 않습니다.');
}
$cf_admin_e = sql_escape_string($cf_admin);

// 관리자 아이콘 삭제
if (!empty($_POST['admin_icon_delete']) && empty($_FILES['admin_icon_file']['name'])) {
	$icon_path = G5_DATA_PATH."/site/ico_admin";
	if (is_file($icon_path)) {
		@unlink($icon_path);
		if (function_exists('cdn_after_delete')) cdn_after_delete($icon_path);
	}
}
// 관리자 아이콘 업로드 (config_form 에서 이전)
if (!empty($_FILES['admin_icon_file']['name'])) {
	$image_name = "ico_admin";
	upload_file($_FILES['admin_icon_file']['tmp_name'], $image_name, $site_style_path);
	if (function_exists('cdn_after_upload')) cdn_after_upload($site_style_path.'/'.$image_name);
}

// 에디터 / 접속제한 / 추가 script (config_form 에서 이전)
$cf_editor       = sql_escape_string(trim(stripslashes((string)($_POST['cf_editor'] ?? ''))));
$cf_possible_ip  = sql_escape_string(trim(stripslashes((string)($_POST['cf_possible_ip'] ?? ''))));
$cf_intercept_ip = sql_escape_string(trim(stripslashes((string)($_POST['cf_intercept_ip'] ?? ''))));
$cf_add_script   = sql_escape_string(stripslashes((string)($_POST['cf_add_script'] ?? '')));
$cf_add_fonts    = sql_escape_string(stripslashes((string)($_POST['cf_add_fonts'] ?? '')));

$cf_site_img = $_POST['cf_site_img'];

// 이미지 등록 시, 이미지를 업로드한 뒤 - 해당 이미지 경로를 삽입
if ($_FILES['cf_site_img_file']['name']) {
	// 확장자 따기
	$exp = explode(".", $_FILES['cf_site_img_file']['name']);
	$exp = $exp[count($exp)-1];

	$image_name = "site_prevew_image.".$exp;
	upload_file($_FILES['cf_site_img_file']['tmp_name'], $image_name, $site_style_path);
	if (function_exists('cdn_after_upload')) cdn_after_upload($site_style_path.'/'.$image_name);
	$cf_site_img = $site_style_url."/".$image_name;
}
if ($_FILES['cf_favicon_file']['name']) {
	// 확장자 따기
	$exp = explode(".", $_FILES['cf_favicon_file']['name']);
	$exp = $exp[count($exp)-1];

	$image_name = "site_favicon.".$exp;
	upload_file($_FILES['cf_favicon_file']['tmp_name'], $image_name, $site_style_path);
	if (function_exists('cdn_after_upload')) cdn_after_upload($site_style_path.'/'.$image_name);
	$cf_favicon = $site_style_url."/".$image_name;
}

$cf_profile_group = "";
$add_str = "";
$_pgs = isset($_POST['cf_profile_group_spr']) && is_array($_POST['cf_profile_group_spr']) ? $_POST['cf_profile_group_spr'] : array();
for($i=0; $i < count($_pgs); $i++) {
	if($_pgs[$i]) {
		$cf_profile_group .= $add_str.$_pgs[$i];
		$add_str = "||";
	}
}

// 상점/아이템 카테고리 처리는 adm/shop_list.php / adm/item_list.php 로 이동됨

$sql = " update {$g5['config_table']}
			set cf_title			= '{$_POST['cf_title']}',
				cf_bgm				= '{$_POST['cf_bgm']}',
				cf_side_title		= '{$_POST['cf_side_title']}',
				cf_class_title		= '{$_POST['cf_class_title']}',
				cf_profile_group	= '{$cf_profile_group}',
				cf_site_descript	= '{$_POST['cf_site_descript']}',
				cf_site_img			= '{$cf_site_img}',
				cf_favicon			= '{$cf_favicon}',
				cf_exp_name			= '{$_POST['cf_exp_name']}',
				cf_exp_pice			= '{$_POST['cf_exp_pice']}',
				cf_rank_name		= '{$_POST['cf_rank_name']}',
				cf_3				= '".addslashes(stripslashes((string)($_POST['cf_3'] ?? '')))."',
				cf_4				= '".addslashes(stripslashes((string)($_POST['cf_4'] ?? '')))."',
				cf_5				= '".addslashes(stripslashes((string)($_POST['cf_5'] ?? '')))."',
				cf_6				= '".addslashes(stripslashes((string)($_POST['cf_6'] ?? '')))."',
				cf_7				= '{$_POST['cf_7']}',
				cf_8				= '{$_POST['cf_8']}',
				cf_9				= '{$_POST['cf_9']}',
				cf_10				= '{$_POST['cf_10']}',
				cf_admin             = '{$cf_admin_e}',
				cf_editor            = '{$cf_editor}',
				cf_possible_ip       = '{$cf_possible_ip}',
				cf_intercept_ip      = '{$cf_intercept_ip}',
				cf_add_script        = '{$cf_add_script}',
				cf_add_fonts         = '{$cf_add_fonts}' ";
sql_query($sql);


//sql_query(" OPTIMIZE TABLE `$g5['config_table']` ");

goto_url('./community_form.php', false);
?>
