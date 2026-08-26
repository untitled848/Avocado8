<?php
include_once('./_common.php');

if (!function_exists('mdl_scan')) {
    alert('모듈 시스템이 로드되지 않았습니다.');
}

$name = isset($_GET['m']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['m']) : '';
$page = isset($_GET['p']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['p']) : 'setting';

if (!$name) {
    alert('모듈을 선택하세요.', G5_MDL_ADMIN_URL.'/module_list.php');
}

// sub_menu: 활성 모듈 admin_menus 를 쿼리(unit_type/category/raid_type 등)까지 비교해 고른다
$modules  = mdl_scan();
$sub_menu = function_exists('mdl_resolve_admin_sub_menu')
    ? mdl_resolve_admin_sub_menu($name, $page)
    : '001100';

if (isset($auth[$sub_menu])) {
    auth_check($auth[$sub_menu], "r");
}

if (!isset($modules[$name])) {
    alert('존재하지 않는 모듈입니다.', G5_MDL_ADMIN_URL.'/module_list.php');
}

$state  = mdl_load_state();
$active = isset($state['active']) ? $state['active'] : array();
if (!in_array($name, $active, true)) {
    alert('비활성 모듈입니다. 먼저 활성화하세요.', G5_MDL_ADMIN_URL.'/module_list.php');
}

$admin_file = G5_PATH.'/module/'.$name.'/admin/'.$page.'.php';
if (!is_file($admin_file)) {
    alert('관리 페이지가 없습니다: '.$page, G5_MDL_ADMIN_URL.'/module_list.php');
}

$g5['title'] = $modules[$name]['title'].' - '.$page;
include_once(G5_MDL_ADMIN_PATH.'/admin.head.php');

$mdl_meta = $modules[$name];
$mdl_name = $name;
include $admin_file;

include_once (G5_MDL_ADMIN_PATH.'/admin.tail.php');
