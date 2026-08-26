<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
include_once(G5_PATH.'/_head.php');
if (function_exists('mdl_do_action')) mdl_do_action('bbs_enqueue_assets', isset($bo_table) ? $bo_table : '');
?>