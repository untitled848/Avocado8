<?php
$sub_menu = "110200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');
if (!$is_admin) alert('관리자만 접근 가능합니다.');

$action = $_REQUEST['action'] ?? '';

if ($action === 'create') {
    $pd_name = trim($_REQUEST['pd_name'] ?? '');
    if ($pd_name === '') alert('이름을 입력하세요.');
    $max = sql_fetch("SELECT MAX(pd_order) AS m FROM `{$g5['world_preset_table']}`", false);
    $next_order = (int)$max['m'] + 1;
    sql_query("INSERT INTO `{$g5['world_preset_table']}` (pd_name, pd_order) VALUES ('".sql_escape_string($pd_name)."', '{$next_order}')", false);
    $new_pd = (int)sql_insert_id();

    // 신규 생성 시 현재 적용중인 세계관 디자인의 값을 기본값으로 복사
    $seed_pd = function_exists('get_active_design') ? (int)get_active_design('world') : 1;
    if ($seed_pd <= 0) $seed_pd = 1;
    if ($seed_pd !== $new_pd) {
        $r = sql_query("SELECT * FROM `{$g5['world_config_table']}` WHERE pd_id = '{$seed_pd}'", false);
        if ($r) {
            $cols = array('cs_name','cs_value','cs_descript','cs_etc_1','cs_etc_2','cs_etc_3','cs_etc_4','cs_etc_5','cs_etc_6','cs_etc_7','cs_etc_8','cs_etc_9','cs_etc_10');
            while ($row = sql_fetch_array($r)) {
                $sets = array("pd_id = '{$new_pd}'");
                foreach ($cols as $c) {
                    $v = sql_escape_string($row[$c] ?? '');
                    $sets[] = "`{$c}` = '{$v}'";
                }
                sql_query("INSERT INTO `{$g5['world_config_table']}` SET ".implode(',', $sets), false);
            }
        }
    }
    goto_url('./world_design_form.php?pd_id='.$new_pd);
}

if ($action === 'copy') {
    $src_pd  = (int)($_GET['pd_id'] ?? 0);
    $pd_name = trim($_GET['pd_name'] ?? '');
    if ($src_pd <= 0 || $pd_name === '') alert('파라미터 누락.');
    $exist = sql_fetch("SELECT pd_id FROM `{$g5['world_preset_table']}` WHERE pd_id = '{$src_pd}'", false);
    if (empty($exist['pd_id'])) alert('원본이 없습니다.');
    $max = sql_fetch("SELECT MAX(pd_order) AS m FROM `{$g5['world_preset_table']}`", false);
    $next_order = (int)$max['m'] + 1;
    sql_query("INSERT INTO `{$g5['world_preset_table']}` (pd_name, pd_order) VALUES ('".sql_escape_string($pd_name)."', '{$next_order}')", false);
    $new_pd = (int)sql_insert_id();
    $r = sql_query("SELECT * FROM `{$g5['world_config_table']}` WHERE pd_id = '{$src_pd}'", false);
    if ($r) {
        $cols = array('cs_name','cs_value','cs_descript','cs_etc_1','cs_etc_2','cs_etc_3','cs_etc_4','cs_etc_5','cs_etc_6','cs_etc_7','cs_etc_8','cs_etc_9','cs_etc_10');
        while ($row = sql_fetch_array($r)) {
            $sets = array("pd_id = '{$new_pd}'");
            foreach ($cols as $c) {
                $v = sql_escape_string($row[$c] ?? '');
                $sets[] = "`{$c}` = '{$v}'";
            }
            sql_query("INSERT INTO `{$g5['world_config_table']}` SET ".implode(',', $sets), false);
        }
    }
    goto_url('./world_design_form.php?pd_id='.$new_pd);
}

if ($action === 'rename') {
    $pd_id   = (int)($_REQUEST['pd_id'] ?? 0);
    $pd_name = trim($_REQUEST['pd_name'] ?? '');
    if ($pd_id <= 0 || $pd_name === '') alert('파라미터 누락.');
    sql_query("UPDATE `{$g5['world_preset_table']}` SET pd_name = '".sql_escape_string($pd_name)."' WHERE pd_id = '{$pd_id}'", false);
    goto_url('./world_preset_list.php');
}

if ($action === 'delete') {
    $pd_id = (int)($_GET['pd_id'] ?? 0);
    if ($pd_id <= 1) alert('기본 프리셋은 삭제할 수 없습니다.', './world_design_form.php');
    sql_query("DELETE FROM `{$g5['world_config_table']}` WHERE pd_id = '{$pd_id}'", false);
    sql_query("DELETE FROM `{$g5['world_preset_table']}` WHERE pd_id = '{$pd_id}'", false);
    goto_url('./world_design_form.php?pd_id=1');
}

include_once('./_design_json.inc.php');

if ($action === 'export') {
    $pd_id = (int)($_GET['pd_id'] ?? 0);
    if ($pd_id <= 0) alert('파라미터 누락.');

    $preset = sql_fetch("SELECT pd_id, pd_name FROM `{$g5['world_preset_table']}` WHERE pd_id = '{$pd_id}'", false);
    if (empty($preset['pd_id'])) alert('세계관 디자인 프리셋이 없습니다.');

    $payload = array(
        'format'      => 'timeline-world-design',
        'version'     => 1,
        'exported_at' => date('Y-m-d H:i:s'),
        'preset'      => array(
            'pd_name' => isset($preset['pd_name']) ? $preset['pd_name'] : '',
        ),
        'settings'    => design_json_collect($g5['world_config_table'], $pd_id),
    );

    design_json_download($payload, 'world_design_'.$pd_id.'_'.date('Ymd_His').'.json');
}

if ($action === 'import') {
    $pd_id = (int)($_POST['pd_id'] ?? 0);
    if ($pd_id <= 0) alert('파라미터 누락.');

    $exist = sql_fetch("SELECT pd_id FROM `{$g5['world_preset_table']}` WHERE pd_id = '{$pd_id}'", false);
    if (empty($exist['pd_id'])) alert('세계관 디자인 프리셋이 없습니다.');

    list($data, $settings) = design_json_parse_upload('timeline-world-design');
    design_json_apply($g5['world_config_table'], $pd_id, $settings);
    goto_url('./world_design_form.php?pd_id='.$pd_id);
}

if ($action === 'import_new') {
    list($data, $settings) = design_json_parse_upload('timeline-world-design');

    $pd_name = trim((string)($_POST['pd_name'] ?? ''));
    if ($pd_name === '' && isset($data['preset']['pd_name'])) {
        $pd_name = trim((string)$data['preset']['pd_name']);
    }
    if ($pd_name === '') $pd_name = 'imported';

    $max = sql_fetch("SELECT MAX(pd_order) AS m FROM `{$g5['world_preset_table']}`", false);
    $next_order = (int)$max['m'] + 1;
    sql_query("INSERT INTO `{$g5['world_preset_table']}` (pd_name, pd_order) VALUES ('".sql_escape_string($pd_name)."', '{$next_order}')", false);
    $new_pd = (int)sql_insert_id();
    if ($new_pd <= 0) alert('새 세계관 디자인 생성에 실패했습니다.');

    design_json_apply($g5['world_config_table'], $new_pd, $settings);
    goto_url('./world_design_form.php?pd_id='.$new_pd);
}

alert('알 수 없는 작업입니다.', './world_preset_list.php');
?>
