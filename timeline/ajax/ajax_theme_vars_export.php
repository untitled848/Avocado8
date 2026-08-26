<?php
/**
 * 테마 CSS 변수 JSON 내보내기 (저장된 직접 설정)
 */
include_once(__DIR__.'/../_common.php');

if (!$is_admin) {
    alert('관리자만 접근할 수 있습니다.');
}

$lib = $timeline_skin_path.'/lib/theme_vars.lib.php';
if (!is_file($lib)) {
    alert('스킨 라이브러리가 없습니다.');
}
include_once $lib;

$vars = tl_theme_load_json($tl_table);
$payload = tl_theme_build_export($tl_table, $vars);
$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($json === false) {
    alert('JSON 생성에 실패했습니다.');
}

$safe_table = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tl_table);
if ($safe_table === '') $safe_table = 'timeline';
$filename = 'theme_'.$safe_table.'_'.date('Ymd_His').'.json';

while (ob_get_level()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
echo $json;
exit;
