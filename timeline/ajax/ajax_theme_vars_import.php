<?php
/**
 * 테마 CSS 변수 JSON 가져오기 (현재 타임라인에 저장)
 */
include_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');

$result = array('success' => false, 'message' => '');

if (!$is_admin) {
    $result['message'] = '권한이 없습니다.';
    echo json_encode($result);
    exit;
}

$lib = $timeline_skin_path.'/lib/theme_vars.lib.php';
if (!is_file($lib)) {
    $result['message'] = '스킨 라이브러리가 없습니다.';
    echo json_encode($result);
    exit;
}
include_once $lib;

if (empty($_FILES['json_file']['tmp_name']) || !is_uploaded_file($_FILES['json_file']['tmp_name'])) {
    $result['message'] = 'JSON 파일을 선택하세요.';
    echo json_encode($result);
    exit;
}
if (!empty($_FILES['json_file']['error']) && (int)$_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
    $result['message'] = '파일 업로드에 실패했습니다.';
    echo json_encode($result);
    exit;
}
if ((int)$_FILES['json_file']['size'] > 2 * 1024 * 1024) {
    $result['message'] = '파일이 너무 큽니다. (최대 2MB)';
    echo json_encode($result);
    exit;
}

$raw = file_get_contents($_FILES['json_file']['tmp_name']);
if ($raw === false || $raw === '') {
    $result['message'] = '파일을 읽을 수 없습니다.';
    echo json_encode($result);
    exit;
}
if (substr($raw, 0, 3) === "\xEF\xBB\xBF") $raw = substr($raw, 3);

$data = json_decode($raw, true);
if (!is_array($data)) {
    $result['message'] = '올바른 JSON 파일이 아닙니다.';
    echo json_encode($result);
    exit;
}

$extracted = tl_theme_extract_vars($data);
if (!$extracted['ok']) {
    $result['message'] = $extracted['message'];
    echo json_encode($result);
    exit;
}

$save = tl_theme_save_json($tl_table, $extracted['vars']);
if (!$save['ok']) {
    $result['message'] = $save['message'];
    echo json_encode($result);
    exit;
}

$layers = tl_theme_layers($tl_table);
$result['success'] = true;
$result['message'] = '가져왔습니다. ('.$save['count'].'개 변수)';
$result['count'] = $save['count'];
$result['json'] = $layers['json'];
$result['effective'] = $layers['effective'];
$result['source'] = $layers['source'];
echo json_encode($result);
