<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * basic 댓글 뷰 — 스킨 진입점 (얇은 wrapper)
 *
 *   - 컨트롤러:    bbs/basic/view_comment.controller.php
 *   - 기본 템플릿: bbs/basic/template/view_comment.tpl.php
 *   - override:    skin/board/<스킨>/template/view_comment.tpl.php (있으면 우선)
 */

require_once G5_BBS_PATH . '/basic/_common.php';
require G5_BBS_PATH . '/basic/view_comment.controller.php';
require bbs_template('view_comment.tpl.php', $board_skin_path, 'basic');
