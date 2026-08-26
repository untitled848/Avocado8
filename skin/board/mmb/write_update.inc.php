<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * mmb 글쓰기 처리 — 스킨 진입점 (얇은 wrapper)
 *
 * 기능(컨트롤러)은 bbs/mmb/ 에 있습니다.
 *   - 컨트롤러: bbs/mmb/write_update.controller.php
 *
 * 글쓰기 후처리(서버측 로직)는 표현 분기점이 없으므로 템플릿이 없습니다.
 * 다른 mmb 호환 스킨도 이 파일을 그대로 복사해 사용합니다.
 *
 * v1.0 — 기존 호환성 유지 안 함. (module/README.md §0 호환성 정책 참조)
 */

require G5_BBS_PATH . '/mmb/write_update.controller.php';
