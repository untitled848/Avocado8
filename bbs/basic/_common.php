<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * basic 게시판 공통 헬퍼
 *
 * mmb 와 동일한 override-aware 템플릿 lookup 패턴을 사용한다.
 * 이미 mmb 측에 `bbs_template()` 가 정의되어 있으면 재사용.
 */
require_once G5_BBS_PATH . '/mmb/_common.php';
