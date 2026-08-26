<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * mmb 글쓰기 폼 — 스킨 진입점 (얇은 wrapper)
 *
 * 기능(컨트롤러)은 bbs/mmb/ 에 있고, 스킨은 표현(템플릿)만 담당합니다.
 *   - 컨트롤러:    bbs/mmb/write.controller.php
 *   - 기본 템플릿: bbs/mmb/template/write.tpl.php
 *   - override:    skin/board/<스킨>/template/write.tpl.php (있으면 우선 사용)
 *
 * 다른 mmb 호환 스킨을 만들 때는 이 파일을 그대로 복사하고
 * `template/write.tpl.php` 만 새로 작성하면 됩니다.
 *
 */

require_once G5_BBS_PATH . '/mmb/_common.php';
require G5_BBS_PATH . '/mmb/write.controller.php';

if (!$is_error) {
    require bbs_template('write.tpl.php', $board_skin_path, 'mmb');
}
