<?php
define('G5_IS_ADMIN', true);
include_once ('../common.php');

if (!defined('G5_ACTIVE_ADMIN_URL'))  define('G5_ACTIVE_ADMIN_URL',  G5_ADMIN_URL);
if (!defined('G5_ACTIVE_ADMIN_PATH')) define('G5_ACTIVE_ADMIN_PATH', G5_ADMIN_PATH);

if(!defined("_LOGIN_PAGE_") || (defined("_LOGIN_PAGE_") && !_LOGIN_PAGE_)) {
	include_once(G5_ADMIN_PATH.'/admin.lib.php');
}
$_GET['sfl']=isset($_GET['sfl'])?$_GET['sfl']:'';
if (function_exists('mdl_do_action')) mdl_do_action('admin_common');
?>