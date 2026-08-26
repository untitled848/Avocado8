<?php
include_once('../common.php');
perm_check_page('shop');
if (function_exists('mdl_do_action')) mdl_do_action('shop_common');
?>