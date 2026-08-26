<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 테마 head.php 있으면 해당 파일에서 처리
if (function_exists('theme_include') && ($_p = theme_include('head', 'head.php'))) { include $_p; return; }

include_once(G5_PATH.'/head.sub.php');
include_once(G5_LIB_PATH.'/latest.lib.php');
include_once(G5_LIB_PATH.'/outlogin.lib.php');

/*********** Logo Data ************/
$logo   = function_exists('get_logo') ? get_logo('pc') : '';
$m_logo = function_exists('get_logo') ? get_logo('mo') : '';

$logo_data = '';
if ($logo) {
    $logo_data .= '<img src="'.htmlspecialchars($logo, ENT_QUOTES, 'UTF-8').'" class="only-pc">';
}
if ($m_logo) {
    $logo_data .= '<img src="'.htmlspecialchars($m_logo, ENT_QUOTES, 'UTF-8').'" class="not-pc">';
}
/*********************************/
?>
<!-- 헤더 영역 -->
   <?php include G5_PATH.'/menu.php'; ?>
<!-- // 헤더 영역 -->

<section id="body">
    <div class="fix-layout">
