<?php
// 이 파일은 새로운 파일 생성시 반드시 포함되어야 함
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

check_site_auth(isset($is_member) ? $is_member : 0);

// 테마 head.sub.php 파일
if (!defined('G5_IS_ADMIN') && function_exists('theme_include') && ($_p = theme_include('head_sub', 'head.sub.php'))) { include $_p; return; }

/* PHP 8 호환: 기본값 및 타이틀 처리 */
$g5_debug['php']['begin_time'] = $begin_time = get_microtime();

if (!isset($g5['title']) || $g5['title'] === '') {
    $g5['title'] = isset($config['cf_title']) ? $config['cf_title'] : '';
    $g5_head_title = $g5['title'];
} else {
    $g5_head_title = $g5['title'];
    if (isset($config['cf_title']) && $config['cf_title'] !== '') {
        $g5_head_title .= ' | '.$config['cf_title'];
    }
}

/* 현재 접속자 정보 */
$g5['lo_location'] = addslashes($g5['title']);
if (!$g5['lo_location']) {
    $req_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $g5['lo_location'] = addslashes(clean_xss_tags($req_uri));
}

$req_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$g5['lo_url'] = addslashes(clean_xss_tags($req_uri));

if ((isset($g5['lo_url']) && strstr($g5['lo_url'], '/'.G5_ADMIN_DIR.'/')) || !empty($is_admin)) {
    $g5['lo_url'] = '';
}

/* 로그인 페이지 여부 */
$is_page_login = false;
if ($req_uri && strstr($req_uri, 'login') !== false) {
    $is_page_login = true;
}

/* 인덱스 프레임 강제 단독 호출 */
if (defined('_INDEX_')) {
    echo "<script>if(parent && parent!==this) location.href='./main.php';</script>";
}
?>
<!doctype html>
<html lang="ko" class="<?php echo $is_page_login ? 'login' : ''; ?> <?php echo (isset($_COOKIE['header_close']) && $_COOKIE['header_close'] === 'close') ? 'close-header' : ''; ?>">
<head>
<meta charset="utf-8">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<?php
if (defined('G5_IS_MOBILE') && G5_IS_MOBILE) {
    echo '<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">'.PHP_EOL;
    echo '<meta name="HandheldFriendly" content="true">'.PHP_EOL;
    echo '<meta name="format-detection" content="telephone=no">'.PHP_EOL;
} else {
    echo '<meta http-equiv="imagetoolbar" content="no">'.PHP_EOL;
    echo '<meta http-equiv="X-UA-Compatible" content="IE=Edge">'.PHP_EOL;
}


?>

<?php if (!empty($config['cf_site_img'])) { ?>
<link rel="image_src" href="<?php echo $config['cf_site_img']; ?>">
<?php } ?>

<meta name="description" content="<?php echo isset($config['cf_site_descript']) ? $config['cf_site_descript'] : ''; ?>">
<meta name="twitter:card" content="summary">
<meta name="twitter:url" content="<?php echo G5_URL; ?>">
<meta name="twitter:title" content="<?php echo $g5_head_title; ?>">
<meta name="twitter:description" content="<?php echo isset($config['cf_site_descript']) ? $config['cf_site_descript'] : ''; ?>">
<?php if (!empty($config['cf_site_img'])) { ?>
<meta name="twitter:image" content="<?php echo $config['cf_site_img']; ?>">
<?php } ?>

<title><?php echo $g5_head_title; ?></title>
<?php
if (defined('G5_IS_ADMIN')) {
    echo '<link rel="stylesheet" href="'.cdn_css(G5_ADMIN_URL.'/css/admin.css').'" type="text/css">'.PHP_EOL;
    echo '<link rel="stylesheet" href="'.cdn_css(G5_ADMIN_URL.'/css/admin.layout.css').'" type="text/css">'.PHP_EOL;
    echo '<link rel="stylesheet" href="'.cdn_css(G5_ADMIN_URL.'/css/admin.theme.css').'" type="text/css">'.PHP_EOL;
} else {
    echo '<link rel="stylesheet" href="'.cdn_css(G5_CSS_URL.'/default.css').'" type="text/css">'.PHP_EOL;
    if (empty($config['cf_7'])) {
        // 디자인 변수 (CSS 커스텀 프로퍼티) — inline 출력로 FOUC 방지 + 여분 HTTP 요청 제거
        $_active_pd = function_exists('get_active_design') ? (int)get_active_design('main') : 1;
        echo '<style id="d-root" data-pd-id="'.(int)$_active_pd.'">'.PHP_EOL;
        emit_design_root_inline($_active_pd);
        echo PHP_EOL.'</style>'.PHP_EOL;
        if (function_exists('theme_design_link_tag')) {
            $_design_tag = theme_design_link_tag();
            if ($_design_tag !== '') echo $_design_tag.PHP_EOL;
            unset($_design_tag);
        }
    }
    echo '<link rel="stylesheet" href="'.cdn_css(G5_CSS_URL.'/preset.css').'" type="text/css">'.PHP_EOL;
    echo '<link rel="stylesheet" href="'.cdn_css(G5_CSS_URL.'/style.css').'" type="text/css">'.PHP_EOL;
    echo '<link href="https://fonts.googleapis.com/icon?family=Material+Icons"
      rel="stylesheet">'.PHP_EOL;
    }
?>

<?php if (!empty($config['cf_favicon'])) { ?>
<link rel="shortcut icon" href="<?php echo $config['cf_favicon']; ?>" type="image/x-icon">
<link rel="icon" href="<?php echo $config['cf_favicon']; ?>" type="image/x-icon">
<?php } ?>

<script>
var g5_url          = "<?php echo addslashes(G5_URL); ?>";
var g5_bbs_url      = "<?php echo addslashes(G5_BBS_URL); ?>";
var g5_is_member    = "<?php echo isset($is_member) ? (int)$is_member : 0; ?>";
var g5_is_admin     = "<?php echo isset($is_admin) ? addslashes($is_admin) : ''; ?>";
var g5_is_mobile    = "<?php echo (defined('G5_IS_MOBILE') && G5_IS_MOBILE) ? '1' : '0'; ?>";
var g5_bo_table     = "<?php echo isset($bo_table) ? addslashes($bo_table) : ''; ?>";
var g5_sca          = "<?php echo isset($sca) ? addslashes($sca) : ''; ?>";
var g5_editor       = "<?php
    $use_dhtml = (isset($board['bo_use_dhtml_editor']) && $board['bo_use_dhtml_editor']);
    echo (!empty($config['cf_editor']) && $use_dhtml) ? addslashes($config['cf_editor']) : '';
?>";
var g5_cookie_domain = "<?php echo addslashes(G5_COOKIE_DOMAIN); ?>";
<?php if (defined('G5_IS_ADMIN')) { ?>
var g5_admin_url    = "<?php echo addslashes(G5_ADMIN_URL); ?>";
<?php } ?>
</script>

<?php if (defined('G5_IS_ADMIN')) { ?>
<script src="<?php echo G5_JS_URL; ?>/jquery-1.8.3.min.js"></script>
<?php } else { ?>
<script src="<?php echo G5_JS_URL; ?>/jquery-1.12.3.min.js"></script>
<?php } ?>
<script src="<?php echo G5_JS_URL; ?>/jquery.cookie.js"></script>
<script src="<?php echo G5_JS_URL; ?>/jquery.rwdImageMaps.js"></script>
<script src="<?php echo G5_JS_URL; ?>/common.js"></script>
<script src="<?php echo G5_JS_URL; ?>/wrest.js?ver=<?php echo G5_JS_VER; ?>"></script>
<?php if (!defined('G5_IS_ADMIN')) { ?>
<script src="<?php echo G5_JS_URL; ?>/action_panel.js?ver=<?php echo G5_JS_VER; ?>"></script>
<?php } ?>

<?php
if (defined('G5_IS_MOBILE') && G5_IS_MOBILE) {
    echo '<script src="'.G5_JS_URL.'/modernizr.custom.70111.js"></script>'.PHP_EOL;
}
if (!defined('G5_IS_ADMIN') && !empty($config['cf_add_fonts'])) {
    echo $config['cf_add_fonts'].PHP_EOL;
}
if (!defined('G5_IS_ADMIN') && function_exists('get_active_preset_id')) {
    $_pr_id = (int)get_active_preset_id();
    if ($_pr_id > 0) {
        $_pr_row = sql_fetch("SELECT pr_font_family FROM `{$g5['theme_preset_table']}` WHERE pr_id = '{$_pr_id}'", false);
        if (!empty($_pr_row['pr_font_family'])) {
            echo '<style id="d-font">body, html { font-family: '.htmlspecialchars($_pr_row['pr_font_family'], ENT_QUOTES).' !important; }</style>'.PHP_EOL;
        }
    }
}
if (!defined('G5_IS_ADMIN') && !empty($config['cf_add_script'])) {
    echo $config['cf_add_script'];
}
?>

<script>
if (!parent || parent === this) {
    document.documentElement.classList.add('single');
}
</script>
<?php if (function_exists('theme_emit_parent_design_sync')) theme_emit_parent_design_sync(); ?>
<?php if (function_exists('mdl_do_action')) mdl_do_action('head'); ?>
</head>
<body>
<?php if (!defined('G5_IS_ADMIN')) { ?>
<?php if (function_exists('print_admin_status_banner')) print_admin_status_banner(); ?>
<?php } ?>
