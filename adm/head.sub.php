<?php
// 이 파일은 새로운 파일 생성시 반드시 포함되어야 함
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
check_site_auth($is_member);
$begin_time = get_microtime();

if (!isset($g5['title'])) {
    $g5['title'] = $config['cf_title'];
    $g5_head_title = $g5['title'];
}
else {
    $g5_head_title = $g5['title']; // 상태바에 표시될 제목
    $g5_head_title .= " | ".$config['cf_title'];
}
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex, nofollow">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta http-equiv="imagetoolbar" content="no">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">

<?php

?>

<title><?php echo $g5_head_title; ?></title>
<link rel="stylesheet" href="'.G5_ADMIN_URL.'/css/admin.css" type="text/css">
<link rel="stylesheet" href="'.G5_ADMIN_URL.'/css/admin.layout.css" type="text/css">

<script>
var g5_url       = "<?php echo addslashes(G5_URL); ?>";
var g5_bbs_url   = "<?php echo addslashes(G5_BBS_URL); ?>";
var g5_is_member = "<?php echo isset($is_member) ? (int)$is_member : 0; ?>";
var g5_is_admin  = "<?php echo isset($is_admin) ? $is_admin : ''; ?>";
var g5_is_mobile = "<?php echo G5_IS_MOBILE ? '1' : '0'; ?>";
var g5_bo_table  = "<?php echo isset($bo_table) ? addslashes($bo_table) : ''; ?>";
var g5_sca       = "<?php echo isset($sca) ? addslashes($sca) : ''; ?>";
var g5_editor    = "<?php echo (!empty($config['cf_editor']) && !empty($board['bo_use_dhtml_editor'])) ? addslashes($config['cf_editor']) : ''; ?>";
var g5_cookie_domain = "<?php echo addslashes(G5_COOKIE_DOMAIN); ?>";
<?php if (defined('G5_IS_ADMIN')) { ?>
var g5_admin_url = "<?php echo addslashes(G5_ADMIN_URL); ?>";
<?php } ?>
</script>
<script src="<?php echo G5_JS_URL ?>/jquery-1.8.3.min.js"></script>
<script src="<?php echo G5_JS_URL ?>/jquery.cookie.js"></script>
<script src="<?php echo G5_JS_URL ?>/common.js"></script>
<script src="<?php echo G5_JS_URL ?>/wrest.js?ver=<?php echo G5_JS_VER; ?>"></script>

<?php
if(!defined('G5_IS_ADMIN'))
    echo $config['cf_add_script'];
?>
</head>
<body>
