<?php
/**
 * timeline_form.php 의 "타임라인별 모듈 on/off" 섹션을 위한 라이브 재검증 ajax.
 *
 * GET ?tl_skin=<name>
 *  → JSON: {
 *       skin: "<name>",
 *       skin_path: "<상대경로>",
 *       overridden: bool,
 *       modules: {
 *          <module_name>: { supported: bool, missing: [hook,...], requires: [hook,...] },
 *          ...
 *       }
 *    }
 *
 * timeline 폼의 select[name=tl_skin] change 이벤트에서 호출.
 * 검사 경로: skin/timeline/<skin> + timeline/ 코어 (게시판 bbs/ 는 포함하지 않음)
 */
$sub_menu = '301200';
include_once('./_common.php');

if (!$is_admin) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('error' => '권한이 없습니다.'), JSON_UNESCAPED_UNICODE);
    exit;
}

$GLOBALS['timeline_admin_require_json'] = true;
include_once(__DIR__.'/timeline_require.inc.php');

header('Content-Type: application/json; charset=utf-8');

$tl_skin = isset($_GET['tl_skin']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['tl_skin']) : '';
if ($tl_skin === '') $tl_skin = 'basic';

$skin_default = G5_PATH.'/skin/timeline/'.$tl_skin;
$skin_path    = function_exists('get_skin_path') ? get_skin_path('timeline', $tl_skin) : $skin_default;

$overridden = false;
$skin_path_show = '';
if ($skin_path && @realpath($skin_path) !== @realpath($skin_default)) {
    $overridden = true;
    $skin_path_show = ltrim(str_replace(array('\\', G5_PATH), array('/', ''), $skin_path), '/');
}
if (!$overridden) {
    $skin_path_show = 'skin/timeline/'.$tl_skin;
}

$result = array(
    'skin'       => $tl_skin,
    'skin_path'  => $skin_path_show,
    'overridden' => $overridden,
    'exists'     => is_dir($skin_path),
    'modules'    => array(),
);

if (function_exists('mdl_board_scope_modules') && function_exists('mdl_skin_supports_hooks')) {
    $scope_mods = mdl_board_scope_modules();
    $paths = array($skin_path);
    if (is_dir(G5_PATH.'/timeline')) {
        $paths[] = G5_PATH.'/timeline';
    }
    foreach ($scope_mods as $mname => $mmeta) {
        $req = isset($mmeta['requires_hooks']) ? $mmeta['requires_hooks'] : array();
        if (empty($req)) {
            $result['modules'][$mname] = array(
                'supported' => true,
                'missing'   => array(),
                'requires'  => array(),
            );
            continue;
        }
        $sup = mdl_skin_supports_hooks($paths, $req);
        $missing = array();
        foreach ($req as $h) {
            if (empty($sup[$h])) $missing[] = $h;
        }
        $result['modules'][$mname] = array(
            'supported' => empty($missing),
            'missing'   => array_values($missing),
            'requires'  => array_values($req),
        );
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
exit;
