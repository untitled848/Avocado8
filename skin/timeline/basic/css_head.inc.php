<?php
/**
 * 타임라인 CSS 로드 (우선순위: JSON > skin > timeline)
 * 각 페이지에서 link 태그 대신 include
 */
if (!defined('_GNUBOARD_')) exit;

if (!function_exists('tl_theme_print_stylesheets')) {
    include_once __DIR__.'/lib/theme_vars.lib.php';
}

$__tl_table_css = '';
if (isset($tl_table) && $tl_table) {
    $__tl_table_css = $tl_table;
} elseif (isset($timeline['tl_table']) && $timeline['tl_table']) {
    $__tl_table_css = $timeline['tl_table'];
}

tl_theme_print_stylesheets($__tl_table_css);
unset($__tl_table_css);
