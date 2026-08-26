<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * mmb 댓글 쓰기 폼 — 스킨 진입점 (얇은 wrapper)
 *
 * 기능(컨트롤러)은 bbs/mmb/ 에 있고, 스킨은 표현(템플릿)만 담당합니다.
 *   - 컨트롤러:    bbs/mmb/write_comment.controller.php
 *   - 기본 템플릿: bbs/mmb/template/write_comment.tpl.php
 *   - override:    skin/board/<스킨>/template/write_comment.tpl.php (있으면 우선)
 *
 * 호출측 가드($is_comment_write)는 entry 에서 처리한다 — 컨트롤러는 무조건 돌지 않게.
 */

if (!isset($is_comment_write) || !$is_comment_write) return;

require_once G5_BBS_PATH . '/mmb/_common.php';
require G5_BBS_PATH . '/mmb/write_comment.controller.php';
require bbs_template('write_comment.tpl.php', $board_skin_path, 'mmb');
