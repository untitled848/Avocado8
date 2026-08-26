<?php
// mypage/_common.php 의 '../common.php' 는 CWD 기준 상대경로라 admin/ 하위에서 깨짐.
// dirname(__FILE__) 로 절대경로 지정.
include_once(dirname(__FILE__).'/../../common.php');
if (!$is_member) { goto_url(G5_BBS_URL.'/login.php'); }
if (function_exists('mdl_do_action')) mdl_do_action('mypage_common');

// 멤버 모드 작동 중에도 진짜 관리자($is_admin_real)는 접근 가능
$_real = isset($GLOBALS['is_admin_real']) ? $GLOBALS['is_admin_real'] : $is_admin;
if (empty($_real)) {
    alert('관리자만 접근 가능한 페이지입니다.');
}
