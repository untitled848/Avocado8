<?php
/**
 * board_form.php 의 "게시판별 모듈 on/off" 섹션을 위한 라이브 재검증 ajax.
 *
 * GET ?bo_skin=<name>
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
 * board_form 의 select[name=bo_skin] change 이벤트에서 호출.
 */
$sub_menu = '001100';
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

header('Content-Type: application/json; charset=utf-8');

$bo_skin = isset($_GET['bo_skin']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['bo_skin']) : '';
if ($bo_skin === '') $bo_skin = 'basic';

$skin_default = G5_PATH.'/skin/board/'.$bo_skin;
$skin_path    = function_exists('get_skin_path') ? get_skin_path('board', $bo_skin) : $skin_default;

$overridden = false;
$skin_path_show = '';
if ($skin_path && @realpath($skin_path) !== @realpath($skin_default)) {
    $overridden = true;
    $skin_path_show = ltrim(str_replace(array('\\', G5_PATH), array('/', ''), $skin_path), '/');
}
if (!$overridden) {
    $skin_path_show = 'skin/board/'.$bo_skin;
}

$result = array(
    'skin'       => $bo_skin,
    'skin_path'  => $skin_path_show,
    'overridden' => $overridden,
    'exists'     => is_dir($skin_path),
    'modules'    => array(),
);

if (function_exists('mdl_board_scope_modules') && function_exists('mdl_module_skin_compat')) {
    $scope_mods = mdl_board_scope_modules();
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
        $compat = mdl_module_skin_compat($mmeta, $skin_path);
        $result['modules'][$mname] = array(
            'supported' => !empty($compat['supported']),
            'missing'   => isset($compat['missing']) ? array_values($compat['missing']) : array(),
            'requires'  => array_values($req),
        );
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
exit;
