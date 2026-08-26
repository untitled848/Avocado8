<?php
/**
 * 모듈 폴더 삭제
 *
 * 안전:
 *  - super 관리자만
 *  - 이미 비활성 상태여야 함 (활성이면 거부)
 *  - confirm 토큰 필수
 *  - uninstall.php 자동 실행 (state.versions 에서 제거)
 *  - module/<name>/ 폴더 재귀 삭제 (밖으로 나가는 링크 차단)
 *  - data/ 안의 모듈 데이터는 건드리지 않음 (uninstall.php 책임)
 */
$sub_menu = '001100';
include_once('./_common.php');

auth_check($auth[$sub_menu], 'd');

if (!$is_admin) {
    alert('관리자만 모듈을 삭제할 수 있습니다.');
}
if (!function_exists('mdl_scan')) {
    alert('모듈 시스템이 로드되지 않았습니다.');
}

check_admin_token();

$name = isset($_REQUEST['m']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_REQUEST['m']) : '';
if ($name === '' || $name[0] === '_') {
    alert('잘못된 모듈 이름입니다.');
}

// 기본 제공 모듈 보호
$core_modules = array('bbs_action_combine', 'bbs_action_search', 'bbs_dice', 'bbs_item_use');
if (in_array($name, $core_modules, true)) {
    alert('기본 제공 모듈은 삭제할 수 없습니다.', G5_MDL_ADMIN_URL.'/module_list.php');
}

$base = G5_PATH.'/module/'.$name;
$real = realpath($base);
$root = realpath(G5_PATH.'/module');
if ($real === false || $root === false || strpos($real, $root.DIRECTORY_SEPARATOR) !== 0) {
    alert('모듈 경로가 유효하지 않습니다.');
}
if (!is_dir($real)) {
    alert('모듈 폴더가 존재하지 않습니다.');
}

// 활성 상태면 거부
$state  = mdl_load_state();
$active = isset($state['active']) ? $state['active'] : array();
if (in_array($name, $active, true)) {
    alert('활성화된 모듈은 삭제할 수 없습니다. 먼저 [비활성화] 후 다시 시도하세요.');
}

// uninstall.php 실행 (있으면)
$un = $real.'/uninstall.php';
if (is_file($un)) {
    global $g5, $config;
    include $un;
}
// state 에서 versions 정리
if (isset($state['versions'][$name])) {
    unset($state['versions'][$name]);
    mdl_save_state($state);
}

// 폴더 재귀 삭제 (symlink 따라가지 않음)
function _mdl_rm_rf($path) {
    if (is_link($path)) {
        @unlink($path);
        return;
    }
    if (is_file($path)) {
        @unlink($path);
        return;
    }
    if (!is_dir($path)) return;
    $items = @scandir($path);
    if ($items === false) return;
    foreach ($items as $it) {
        if ($it === '.' || $it === '..') continue;
        _mdl_rm_rf($path.'/'.$it);
    }
    @rmdir($path);
}
_mdl_rm_rf($real);

if (is_dir($real)) {
    alert('일부 파일을 삭제하지 못했습니다. 권한을 확인하세요: '.h($real));
}

goto_url(G5_MDL_ADMIN_URL.'/module_list.php?msg='.urlencode($name.' 폴더 삭제 완료'));
