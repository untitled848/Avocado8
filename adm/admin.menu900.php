<?php
$menu['menu900'] = array(
    array('900000', '데이터/기타 관리',          G5_ADMIN_URL.'/session_file_delete.php', ''),
    array('900100', '세션파일 일괄삭제',         G5_ADMIN_URL.'/session_file_delete.php', 'cf_session', 1),
    array('900200', '캐시파일 일괄삭제',         G5_ADMIN_URL.'/cache_file_delete.php',   'cf_cache',   1),
    array('900400', '사이트 DB 점검/보정',       G5_ADMIN_URL.'/db_check.php',        ''),
    array('900990', '웹 DB 접속',                G5_DB_URL,                                ''),
);
?>
