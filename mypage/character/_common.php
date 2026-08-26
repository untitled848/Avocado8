<?php
include_once('../../common.php');
if(!$is_member) { goto_url(G5_BBS_URL."/login.php"); }
perm_check_page('member');
if (function_exists('mdl_do_action')) mdl_do_action('mypage_common');
?>