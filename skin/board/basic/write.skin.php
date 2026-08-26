<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * basic 글쓰기 폼 — 스킨 진입점 (얇은 wrapper)
 *
 * 기능(컨트롤러)은 bbs/basic/ 에 있고, 스킨은 표현(템플릿)만 담당.
 *   - 컨트롤러:    bbs/basic/write.controller.php
 *   - 기본 템플릿: bbs/basic/template/write.tpl.php
 *   - override:    skin/board/<스킨>/template/write.tpl.php (있으면 우선 사용)
 */

require_once G5_BBS_PATH . '/basic/_common.php';
require G5_BBS_PATH . '/basic/write.controller.php';

if (!$is_error) {
    require bbs_template('write.tpl.php', $board_skin_path, 'basic');
}
