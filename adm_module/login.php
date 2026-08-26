<?php
// adm_module/login.php — 로그인은 adm/ 으로 통합됨. redirect.
include_once('../common.php');
$dest = G5_ADMIN_URL.'/login.php';
goto_url($dest);
