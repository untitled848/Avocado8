<?php
include_once('../../common.php');
if(!$is_member) { goto_url(G5_BBS_URL."/login.php"); }
perm_check_page('memo');
?>