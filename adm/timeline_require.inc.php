<?php
/**
 * 타임라인 관리자 페이지 공통
 * - 함수 라이브러리 로드
 * - 목록 테이블이 없으면 사이트 DB 점검/보정 페이지로 이동
 */
if (!defined('_GNUBOARD_')) exit;

require_once G5_PATH.'/timeline/_functions.php';

if (!function_exists('timeline_admin_require_installed')) {
    alert('타임라인 라이브러리를 불러올 수 없습니다.');
}

$__tl_req_json = !empty($GLOBALS['timeline_admin_require_json']);
timeline_admin_require_installed($__tl_req_json);
unset($__tl_req_json);
