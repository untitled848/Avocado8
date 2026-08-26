<?php
/**
 * 테마 폴더 삭제
 *
 *  - super 관리자만
 *  - default(가상 테마) 보호
 */
$sub_menu = '110200';
include_once('./_common.php');
include_once('./_theme_upload.inc.php');

auth_check($auth[$sub_menu], 'd');

if (!$is_admin) {
    alert('관리자만 테마를 삭제할 수 있습니다.');
}

check_admin_token();

$name = isset($_REQUEST['theme']) ? trim((string)$_REQUEST['theme']) : '';
if (!theme_upload_valid_name($name)) {
    alert('잘못된 테마 이름입니다.');
}
if (theme_upload_is_protected($name)) {
    alert('기본 테마는 삭제할 수 없습니다.', './theme_upload_list.php');
}

$base = theme_upload_root().'/'.$name;
$real = realpath($base);
$root = realpath(theme_upload_root());
if ($real === false || $root === false || strpos($real, $root.DIRECTORY_SEPARATOR) !== 0) {
    alert('테마 경로가 유효하지 않습니다.');
}
if (!is_dir($real)) {
    alert('테마 폴더가 존재하지 않습니다.');
}

theme_upload_rm_rf(str_replace('\\', '/', $real));

if (is_dir($real)) {
    alert('일부 파일을 삭제하지 못했습니다. 권한을 확인하세요: '.h($real));
}

goto_url('./theme_upload_list.php?msg='.urlencode($name.' 테마 폴더 삭제 완료'));
