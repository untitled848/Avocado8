<?php
/**
 * 타임라인 테마 색/변수 설정 페이지
 * URL: timeline/theme_vars.php?tl_table=xxx
 */
include_once('./_common.php');

if (!$is_admin) {
    alert('관리자만 접근할 수 있습니다.', './index.php?tl_table='.$tl_table);
}

$skin_lib = $timeline_skin_path.'/lib/theme_vars.lib.php';
$skin_ui  = $timeline_skin_path.'/theme_vars.skin.php';
if (!is_file($skin_lib) || !is_file($skin_ui)) {
    alert('현재 스킨에서 테마 설정을 지원하지 않습니다.', './index.php?tl_table='.$tl_table);
}

include_once $skin_lib;
$layers = tl_theme_layers($tl_table);
$catalog = tl_theme_var_catalog();

$g5['title'] = '테마 색 설정 - '.$timeline['tl_subject'];
include_once(G5_PATH.'/_head.php');
timeline_print_css_head();

if (is_file($timeline_skin_path.'/css/theme_vars.css')) {
    echo '<link rel="stylesheet" href="'.$timeline_skin_url.'/css/theme_vars.css">'."\n";
}

include $skin_ui;

include_once(G5_PATH.'/_tail.php');
