<?php
// 이 파일은 adm_module/ 로 이전되었습니다.
include_once('./_common.php');
goto_url(G5_MDL_ADMIN_URL.'/module_list.php' . ($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : ''));
