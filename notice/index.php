<?php
include_once('../common.php');

@include_once(G5_LIB_PATH.'/notice.lib.php');
@include_once(G5_LIB_PATH.'/content.lib.php');

$no_id = (int)($_GET['no_id'] ?? 0);

if ($no_id > 0) {
    include_once(G5_PATH.'/notice/skin/content.skin.php');
} else {
    include_once(G5_PATH.'/notice/skin/list.skin.php');
}
?>
