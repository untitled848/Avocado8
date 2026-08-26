<?php
include_once('./_common.php');
include_once('./_head.php');

// 전체 알림 보기 모드 — call.inc.php 가 cf_call_page_rows 사용
$call_view_mode = 'full';
include __DIR__ . '/call.inc.php';

include_once('./_tail.php');
