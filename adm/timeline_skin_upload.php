<?php
/**
 * 타임라인 스킨 tar.gz 업로드 처리
 *
 * 게시판 스킨 업로드와 같은 방식:
 *  - super 관리자만
 *  - .tar.gz 만 허용 (PharData)
 *  - 엔트리 path traversal 차단
 *  - 최상위 디렉토리 1개, 그 안에 write/item/view_detail.skin.php 중 하나
 *  - 동일 이름 폴더가 이미 있으면 거부
 */
$sub_menu = '301100';
include_once('./_common.php');
include_once('./_timeline_skin.inc.php');

auth_check($auth[$sub_menu], 'w');

if (!$is_admin) {
    alert('관리자만 타임라인 스킨을 업로드할 수 있습니다.');
}
if (!class_exists('PharData')) {
    alert('서버에 Phar 확장이 없어 tar.gz 를 처리할 수 없습니다.');
}
if (empty($_FILES['skin_pkg']['name'])) {
    alert('업로드할 파일이 없습니다.');
}

check_admin_token();

$f = $_FILES['skin_pkg'];
if (!empty($f['error']) || !is_uploaded_file($f['tmp_name'])) {
    alert('파일 업로드에 실패했습니다. (error='.(int)$f['error'].')');
}
if (!preg_match('/\.tar\.gz$/i', $f['name'])) {
    alert('.tar.gz 파일만 업로드 가능합니다.');
}
if ((int)$f['size'] > TIMELINE_SKIN_MAX_BYTES) {
    alert('파일이 너무 큽니다. (최대 20MB)');
}

$tmp_path = sys_get_temp_dir().DIRECTORY_SEPARATOR
          . 'tlsk_'.bin2hex(random_bytes(8)).'.tar.gz';

if (!move_uploaded_file($f['tmp_name'], $tmp_path)) {
    alert('임시 파일 이동 실패. 서버 권한을 확인하세요.');
}

try {
    $phar = new PharData($tmp_path);
} catch (Exception $e) {
    @unlink($tmp_path);
    alert('tar.gz 파일을 열 수 없습니다: '.h($e->getMessage()));
}

$skin_name = '';
$has_entry = false;
$file_count = 0;
$phar_base = 'phar://'.str_replace('\\', '/', $tmp_path).'/';
$reserved_top = array('skin', 'board', 'mobile', 'theme', 'module', 'adm', 'timeline');

try {
    $rit = new RecursiveIteratorIterator(
        $phar,
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($rit as $entry_info) {
        if (method_exists($entry_info, 'isLink') && $entry_info->isLink()) {
            @unlink($tmp_path);
            alert('심볼릭 링크가 포함된 패키지는 업로드할 수 없습니다.');
        }

        $entry_path = str_replace('\\', '/', (string)$entry_info);
        $rel = ltrim(substr($entry_path, strlen($phar_base)), '/');
        if ($rel === '' || $rel === '.' || $rel === '..') continue;

        if (strpos($rel, '..') !== false
            || $rel[0] === '/'
            || preg_match('/^[a-zA-Z]:/', $rel)) {
            @unlink($tmp_path);
            alert('안전하지 않은 경로가 포함되어 있습니다: '.h($rel));
        }

        $parts = explode('/', rtrim($rel, '/'));
        $top   = $parts[0];

        if ($skin_name === '') {
            $skin_name = $top;
        } elseif ($top !== $skin_name) {
            @unlink($tmp_path);
            alert('tar.gz 안에 여러 개의 최상위 폴더가 있습니다. 스킨 패키지는 하나의 최상위 폴더만 가져야 합니다.');
        }

        $file_count++;
        if ($file_count > 2000) {
            @unlink($tmp_path);
            alert('패키지 안의 파일이 너무 많습니다. (최대 2000개)');
        }

        if (count($parts) === 2 && in_array($parts[1], timeline_skin_required_files(), true)) {
            $has_entry = true;
        }
    }
} catch (Exception $e) {
    @unlink($tmp_path);
    alert('tar.gz 파일 파싱 오류: '.h($e->getMessage()));
}

if ($skin_name === '') {
    @unlink($tmp_path);
    alert('tar.gz 안에 파일이 없거나 구조를 파악할 수 없습니다.');
}
if (strpos($skin_name, '.') !== false) {
    @unlink($tmp_path);
    alert('최상위가 폴더가 아니라 파일입니다. 스킨 폴더(예: <code>myskin/</code>)를 통째로 압축하세요.');
}
if (in_array(strtolower($skin_name), $reserved_top, true)) {
    @unlink($tmp_path);
    alert('최상위 폴더 이름이 <strong>'.h($skin_name).'</strong> 입니다. 스킨 폴더 자체(예: <code>myskin/</code>)를 압축하세요.');
}
if (!timeline_skin_valid_name($skin_name)) {
    @unlink($tmp_path);
    alert('스킨 폴더 이름이 유효하지 않습니다: '.h($skin_name).' (영숫자로 시작, 영숫자·_·- 만 허용)');
}
if (!$has_entry) {
    @unlink($tmp_path);
    alert('최상위 폴더 (<strong>'.h($skin_name).'</strong>) 에 <code>write.skin.php</code> / <code>item.skin.php</code> / <code>view_detail.skin.php</code> 중 하나가 없습니다.');
}

$dest_root = timeline_skin_root();
if (!is_dir($dest_root)) {
    @mkdir($dest_root, G5_DIR_PERMISSION, true);
}

$dest = $dest_root.'/'.$skin_name;
if (is_dir($dest)) {
    @unlink($tmp_path);
    if (timeline_skin_is_protected($skin_name)) {
        alert('기본 제공 스킨 <strong>'.h($skin_name).'</strong> 은 덮어쓸 수 없습니다.');
    }
    alert('스킨 <strong>'.h($skin_name).'</strong> 이(가) 이미 있습니다. 먼저 삭제한 뒤 다시 업로드하세요.');
}

try {
    $phar->extractTo($dest_root);
} catch (Exception $e) {
    @unlink($tmp_path);
    alert('압축 해제 실패: '.h($e->getMessage()).'. skin/timeline/ 쓰기 권한을 확인하세요.');
}

@unlink($tmp_path);

$real_dest = realpath($dest);
$real_root = realpath($dest_root);
if ($real_dest === false || $real_root === false
    || strpos($real_dest, $real_root.DIRECTORY_SEPARATOR) !== 0
    || !timeline_skin_has_entry_files($real_dest)) {
    if ($real_dest) timeline_skin_rm_rf(str_replace('\\', '/', $real_dest));
    alert('압축은 풀렸지만 스킨 파일을 확인할 수 없습니다. 패키지 구조를 확인하세요.');
}

goto_url('./timeline_skin_list.php?msg='.urlencode($skin_name.' 스킨 업로드 완료. 타임라인 설정의 스킨에서 선택할 수 있습니다.'));
