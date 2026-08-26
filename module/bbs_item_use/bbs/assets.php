<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * inventory 활성 게시판에서만 푸시할 JS/CSS.
 *   bbs_enqueue_assets 액션 핸들러에서 require 됨.
 */

// 현재 따로 푸시할 자산 없음. 필요 시 아래처럼 추가:
//   add_javascript('<script src="'.G5_URL.'/inventory/js/picker.js"></script>', 100);
//   add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/inventory/css/picker.css">', 100);
