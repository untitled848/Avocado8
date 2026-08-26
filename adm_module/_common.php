<?php
define('G5_IS_ADMIN', true);
include_once ('../common.php');

if (!defined('G5_ACTIVE_ADMIN_URL'))  define('G5_ACTIVE_ADMIN_URL',  G5_MDL_ADMIN_URL);
if (!defined('G5_ACTIVE_ADMIN_PATH')) define('G5_ACTIVE_ADMIN_PATH', G5_MDL_ADMIN_PATH);

// canonical admin.lib.php (adm/) 사용 — G5_ACTIVE_ADMIN_* 기반으로 자동 분기
if(!defined("_LOGIN_PAGE_") || (defined("_LOGIN_PAGE_") && !_LOGIN_PAGE_)) {
	include_once(G5_ADMIN_PATH.'/admin.lib.php');
}
$_GET['sfl']=isset($_GET['sfl'])?$_GET['sfl']:'';
if (function_exists('mdl_do_action')) mdl_do_action('admin_common');
?>
