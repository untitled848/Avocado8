<?php
define('G5_IS_ADMIN', true);
include_once ('../../common.php');
if (!defined('G5_ACTIVE_ADMIN_URL'))  define('G5_ACTIVE_ADMIN_URL',  G5_MDL_ADMIN_URL);
if (!defined('G5_ACTIVE_ADMIN_PATH')) define('G5_ACTIVE_ADMIN_PATH', G5_MDL_ADMIN_PATH);
include_once(G5_ADMIN_PATH.'/admin.lib.php');
?>
