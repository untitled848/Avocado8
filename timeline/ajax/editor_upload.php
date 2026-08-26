<?php
/**
 * 타임라인 스크립트(스마트에디터) 이미지 업로드
 * - 회원 세션 기준으로 인증 (논스/중첩 팝업 이슈 회피)
 * - SmartEditor photo_uploader 가 기대하는 JSON 형식 반환
 */
include_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');

function tl_editor_upload_error($msg) {
    echo json_encode(array(
        'files' => array(
            array('error' => $msg)
        )
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$is_member) {
    tl_editor_upload_error('로그인이 필요합니다.');
}

$ym = date('ym', G5_SERVER_TIME);
$data_dir = G5_DATA_PATH.'/editor/'.$ym;
$data_url = G5_DATA_URL.'/editor/'.$ym;

if (!is_dir(G5_DATA_PATH.'/editor')) {
    @mkdir(G5_DATA_PATH.'/editor', G5_DIR_PERMISSION, true);
}
if (!is_dir($data_dir)) {
    @mkdir($data_dir, G5_DIR_PERMISSION, true);
}
if (!is_dir($data_dir) || !is_writable($data_dir)) {
    tl_editor_upload_error('업로드 폴더를 만들 수 없습니다. data/editor 권한을 확인해 주세요.');
}

$allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp');
$allowed_mime = array(
    'image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp',
    'image/x-ms-bmp', 'image/pjpeg',
);

// jquery.fileupload: files[]
$file = null;
if (isset($_FILES['files'])) {
    if (is_array($_FILES['files']['name'])) {
        $file = array(
            'name' => $_FILES['files']['name'][0],
            'type' => $_FILES['files']['type'][0],
            'tmp_name' => $_FILES['files']['tmp_name'][0],
            'error' => $_FILES['files']['error'][0],
            'size' => $_FILES['files']['size'][0],
        );
    } else {
        $file = $_FILES['files'];
    }
} elseif (isset($_FILES['file'])) {
    $file = $_FILES['file'];
}

if (!$file || empty($file['tmp_name'])) {
    tl_editor_upload_error('업로드된 파일이 없습니다.');
}

if (!empty($file['error']) && (int)$file['error'] !== UPLOAD_ERR_OK) {
    $map = array(
        UPLOAD_ERR_INI_SIZE => '파일이 서버 제한 용량을 초과했습니다.',
        UPLOAD_ERR_FORM_SIZE => '파일이 폼 제한 용량을 초과했습니다.',
        UPLOAD_ERR_PARTIAL => '파일이 일부만 업로드되었습니다.',
        UPLOAD_ERR_NO_FILE => '파일이 없습니다.',
        UPLOAD_ERR_NO_TMP_DIR => '임시 폴더가 없습니다.',
        UPLOAD_ERR_CANT_WRITE => '디스크 쓰기에 실패했습니다.',
        UPLOAD_ERR_EXTENSION => '확장에 의해 업로드가 중단되었습니다.',
    );
    $code = (int)$file['error'];
    tl_editor_upload_error(isset($map[$code]) ? $map[$code] : ('업로드 오류 코드: '.$code));
}

$orig_name = (string)$file['name'];
$ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
if (!in_array($ext, $allowed_ext, true)) {
    tl_editor_upload_error('이미지 파일만 업로드할 수 있습니다. (jpg, png, gif, webp, bmp)');
}

$tmp = $file['tmp_name'];
if (!is_uploaded_file($tmp)) {
    tl_editor_upload_error('잘못된 업로드 요청입니다.');
}

$img_info = @getimagesize($tmp);
if ($img_info === false) {
    tl_editor_upload_error('이미지 파일이 아닙니다.');
}

$mime = isset($img_info['mime']) ? $img_info['mime'] : (isset($file['type']) ? $file['type'] : '');
if ($mime && !in_array($mime, $allowed_mime, true)) {
    tl_editor_upload_error('허용되지 않는 이미지 형식입니다.');
}

$pass = md5(sha1(session_id().(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '')));
$save_name = $pass.'_'.str_replace('.', '_', (string)microtime(true)).'.'.$ext;
$save_path = $data_dir.'/'.$save_name;

if (!@move_uploaded_file($tmp, $save_path)) {
    tl_editor_upload_error('파일 저장에 실패했습니다.');
}

if (defined('G5_FILE_PERMISSION')) {
    @chmod($save_path, G5_FILE_PERMISSION);
}

if (function_exists('cdn_after_upload')) {
    @cdn_after_upload($save_path);
}

$url = $data_url.'/'.$save_name;
$width = isset($img_info[0]) ? (int)$img_info[0] : 0;
$height = isset($img_info[1]) ? (int)$img_info[1] : 0;

echo json_encode(array(
    'files' => array(
        array(
            'name' => $save_name,
            'oriname' => $orig_name,
            'size' => (int)@filesize($save_path),
            'type' => $mime ? $mime : ('image/'.$ext),
            'url' => $url,
            'thumbnailUrl' => $url,
            'deleteUrl' => '',
            'deleteType' => 'DELETE',
            'width' => $width,
            'height' => $height,
        )
    )
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
