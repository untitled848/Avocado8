<?php
/**
 * 타임라인 스킨 폴더 삭제
 *
 *  - super 관리자만
 *  - 기본 제공 스킨(basic) 보호
 */
$sub_menu = '301100';
include_once('./_common.php');
include_once('./_timeline_skin.inc.php');

auth_check($auth[$sub_menu], 'd');

if (!$is_admin) {
    alert('관리자만 타임라인 스킨을 삭제할 수 있습니다.');
}

check_admin_token();

$name = isset($_REQUEST['skin']) ? trim((string)$_REQUEST['skin']) : '';
if (!timeline_skin_valid_name($name)) {
    alert('잘못된 스킨 이름입니다.');
}
if (timeline_skin_is_protected($name)) {
    alert('기본 제공 스킨은 삭제할 수 없습니다.', './timeline_skin_list.php');
}

$base = timeline_skin_root().'/'.$name;
$real = realpath($base);
$root = realpath(timeline_skin_root());
if ($real === false || $root === false || strpos($real, $root.DIRECTORY_SEPARATOR) !== 0) {
    alert('스킨 경로가 유효하지 않습니다.');
}
if (!is_dir($real)) {
    alert('스킨 폴더가 존재하지 않습니다.');
}

timeline_skin_rm_rf(str_replace('\\', '/', $real));

if (is_dir($real)) {
    alert('일부 파일을 삭제하지 못했습니다. 권한을 확인하세요: '.h($real));
}

goto_url('./timeline_skin_list.php?msg='.urlencode($name.' 스킨 폴더 삭제 완료'));
