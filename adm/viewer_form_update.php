<?php
$sub_menu = "100200";
include_once('./_common.php');
/*
check_demo();
auth_check($auth[$sub_menu], 'w');

if (!$is_admin)
	alert('관리자만 접근 가능합니다.');

check_admin_token();
*/


$sql_common = " co_html             = '1',
                co_tag_filter_use   = '0' ";

// CDN: 본문 토큰화 (모듈 비활성이어도 안전)
if (function_exists('cdn_tokenize_content')) {
	if (isset($menu_content))    $menu_content    = cdn_tokenize_content($menu_content);
	if (isset($m_menu_content))  $m_menu_content  = cdn_tokenize_content($m_menu_content);
	if (isset($main_content))    $main_content    = cdn_tokenize_content($main_content);
	if (isset($m_main_content))  $m_main_content  = cdn_tokenize_content($m_main_content);
}

// 메뉴 정보 등록
$sql = " select co_id from {$g5['content_table']} where co_id = 'site_menu' ";
$menu_co = sql_fetch($sql);
if(!$menu_co['co_id']) {
	// Insert
	$sql = " insert {$g5['content_table']}
				set co_id = 'site_menu',
					co_content          = '{$menu_content}',
					co_mobile_content   = '{$m_menu_content}',
					{$sql_common} ";
	sql_query($sql);
} else {
	// Update
	$sql = " update {$g5['content_table']}
				set co_content          = '{$menu_content}',
					co_mobile_content   = '{$m_menu_content}',
					{$sql_common}
			  where co_id = 'site_menu' ";
	sql_query($sql);
}

// -- 메인 정보 가져오기
$sql = " select co_id from {$g5['content_table']} where co_id = 'site_main' ";
$main_co = sql_fetch($sql);
if(!$main_co['co_id']) {
	// Insert
	$sql = " insert {$g5['content_table']}
				set co_id = 'site_main',
					co_content          = '{$main_content}',
					co_mobile_content   = '{$m_main_content}',
					{$sql_common} ";
	sql_query($sql);
} else {
	// Update
	$sql = " update {$g5['content_table']}
				set co_content          = '{$main_content}',
					co_mobile_content   = '{$m_main_content}',
					{$sql_common}
			  where co_id = 'site_main' ";
	sql_query($sql);
}


goto_url('./viewer_form.php', false);
?>