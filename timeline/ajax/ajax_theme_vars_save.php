<?php
/**
 * 테마 CSS 변수 JSON 저장
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

$raw = isset($_POST['vars']) ? (string)$_POST['vars'] : '';
$stripped = stripslashes($raw);
if (json_decode($stripped, true) !== null || trim($stripped) === '{}' || trim($stripped) === '') {
    $raw = $stripped;
}

$save = tl_theme_save_json($tl_table, $raw);
if (!$save['ok']) {
    $result['message'] = $save['message'];
    echo json_encode($result);
    exit;
}

$layers = tl_theme_layers($tl_table);
$result['success'] = true;
$result['message'] = '저장되었습니다. ('.$save['count'].'개 변수)';
$result['count'] = $save['count'];
$result['json'] = $layers['json'];
$result['effective'] = $layers['effective'];
$result['source'] = $layers['source'];
echo json_encode($result);
