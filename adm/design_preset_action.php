<?php
$sub_menu = "110200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');

if (!$is_admin)
    alert('관리자만 접근 가능합니다.');

$action = ses($_REQUEST, 'action', '', 'string');

include_once('./_design_json.inc.php');

if ($action === 'create') {
    $pd_name = ses($_REQUEST, 'pd_name', '', 'string');
    if ($pd_name === '') alert('이름을 입력하세요.');

    $max = sql_fetch("SELECT MAX(pd_order) AS m FROM `{$g5['design_preset_table']}`", false);
    $next_order = (int)$max['m'] + 1;
    sql_query("INSERT INTO `{$g5['design_preset_table']}` (pd_name, pd_order) VALUES ('{$pd_name}', '{$next_order}')", false);
    $new_pd = (int)sql_insert_id();

    // 신규 생성 시 현재 적용중인 디자인(=현재 테마 설정)의 값을 기본값으로 복사 (빈 시작 방지)
    $seed_pd = function_exists('get_active_design') ? (int)get_active_design('main') : 1;
    if ($seed_pd <= 0) $seed_pd = 1;
    if ($seed_pd !== $new_pd) {
        $r = sql_query("SELECT * FROM `{$g5['css_table']}` WHERE pd_id = '{$seed_pd}'", false);
        if ($r) {
            $cols = array('cs_name','cs_value','cs_descript','cs_etc_1','cs_etc_2','cs_etc_3','cs_etc_4','cs_etc_5','cs_etc_6','cs_etc_7','cs_etc_8','cs_etc_9','cs_etc_10');
            while ($row = sql_fetch_array($r)) {
                $sets = array("pd_id = '{$new_pd}'");
                foreach ($cols as $c) {
                    $v = sql_escape_string(isset($row[$c]) ? $row[$c] : '');
                    $sets[] = "`{$c}` = '{$v}'";
                }
                sql_query("INSERT INTO `{$g5['css_table']}` SET ".implode(',', $sets), false);
            }
        }
    }
    goto_url('./design_form.php?pd_id='.$new_pd);
}

if ($action === 'copy') {
    $src_pd = ses($_GET, 'pd_id', 0, 'int');
    $pd_name = ses($_GET, 'pd_name', '', 'string');
    if ($src_pd <= 0 || $pd_name === '') alert('파라미터 누락.');

    $exist = sql_fetch("SELECT pd_id FROM `{$g5['design_preset_table']}` WHERE pd_id = '{$src_pd}'", false);
    if (empty($exist['pd_id'])) alert('원본이 없습니다.');

    $max = sql_fetch("SELECT MAX(pd_order) AS m FROM `{$g5['design_preset_table']}`", false);
    $next_order = (int)$max['m'] + 1;
    sql_query("INSERT INTO `{$g5['design_preset_table']}` (pd_name, pd_order) VALUES ('{$pd_name}', '{$next_order}')", false);
    $new_pd = (int)sql_insert_id();

    // css_config 행 복제
    $r = sql_query("SELECT * FROM `{$g5['css_table']}` WHERE pd_id = '{$src_pd}'", false);
    if ($r) {
        while ($row = sql_fetch_array($r)) {
            $cols = array('cs_name','cs_value','cs_descript','cs_etc_1','cs_etc_2','cs_etc_3','cs_etc_4','cs_etc_5','cs_etc_6','cs_etc_7','cs_etc_8','cs_etc_9','cs_etc_10');
            $sets = array("pd_id = '{$new_pd}'");
            foreach ($cols as $c) {
                $v = sql_escape_string(isset($row[$c]) ? $row[$c] : '');
                $sets[] = "`{$c}` = '{$v}'";
            }
            sql_query("INSERT INTO `{$g5['css_table']}` SET ".implode(',', $sets), false);
        }
    }
    goto_url('./design_form.php?pd_id='.$new_pd);
}

if ($action === 'rename') {
    $pd_id   = ses($_REQUEST, 'pd_id', 0, 'int');
    $pd_name = trim((string)ses($_REQUEST, 'pd_name', '', 'string'));
    if ($pd_id <= 0 || $pd_name === '') alert('파라미터 누락.');
    $name_esc = sql_escape_string($pd_name);
    sql_query("UPDATE `{$g5['design_preset_table']}` SET pd_name = '{$name_esc}' WHERE pd_id = '{$pd_id}'", false);
    goto_url('./design_preset_list.php');
}

if ($action === 'delete') {
    $pd_id = ses($_GET, 'pd_id', 0, 'int');
    if ($pd_id <= 1) alert('기본 디자인은 삭제할 수 없습니다.', './design_form.php');

    sql_query("DELETE FROM `{$g5['css_table']}` WHERE pd_id = '{$pd_id}'", false);
    sql_query("DELETE FROM `{$g5['design_preset_table']}` WHERE pd_id = '{$pd_id}'", false);

    // 이 디자인을 참조하던 테마 프리셋은 1(기본)로 회귀
    sql_query("UPDATE `{$g5['theme_preset_table']}` SET pr_design = '1' WHERE pr_design = '{$pd_id}'", false);

    // 생성된 CSS 파일 제거
    $css_data_path = G5_DATA_PATH.'/css';
    @unlink($css_data_path.'/_design.config.'.$pd_id.'.css');

    goto_url('./design_form.php?pd_id=1');
}

if ($action === 'export') {
    $pd_id = ses($_GET, 'pd_id', 0, 'int');
    if ($pd_id <= 0) alert('파라미터 누락.');

    $preset = sql_fetch("SELECT pd_id, pd_name FROM `{$g5['design_preset_table']}` WHERE pd_id = '{$pd_id}'", false);
    if (empty($preset['pd_id'])) alert('디자인 프리셋이 없습니다.');

    $payload = array(
        'format'      => 'timeline-design',
        'version'     => 1,
        'exported_at' => date('Y-m-d H:i:s'),
        'preset'      => array(
            'pd_name' => isset($preset['pd_name']) ? $preset['pd_name'] : '',
        ),
        'settings'    => design_json_collect($g5['css_table'], $pd_id),
    );

    design_json_download($payload, 'design_'.$pd_id.'_'.date('Ymd_His').'.json');
}

if ($action === 'import') {
    $pd_id = ses($_POST, 'pd_id', 0, 'int');
    if ($pd_id <= 0) alert('파라미터 누락.');

    $exist = sql_fetch("SELECT pd_id FROM `{$g5['design_preset_table']}` WHERE pd_id = '{$pd_id}'", false);
    if (empty($exist['pd_id'])) alert('디자인 프리셋이 없습니다.');

    list($data, $settings) = design_json_parse_upload('timeline-design');
    design_json_apply($g5['css_table'], $pd_id, $settings);
    goto_url('./design_form.php?pd_id='.$pd_id);
}

if ($action === 'import_new') {
    list($data, $settings) = design_json_parse_upload('timeline-design');

    $pd_name = trim((string)ses($_POST, 'pd_name', '', 'string'));
    if ($pd_name === '' && isset($data['preset']['pd_name'])) {
        $pd_name = trim((string)$data['preset']['pd_name']);
    }
    if ($pd_name === '') $pd_name = 'imported';

    $max = sql_fetch("SELECT MAX(pd_order) AS m FROM `{$g5['design_preset_table']}`", false);
    $next_order = (int)$max['m'] + 1;
    $name_esc = sql_escape_string($pd_name);
    sql_query("INSERT INTO `{$g5['design_preset_table']}` (pd_name, pd_order) VALUES ('{$name_esc}', '{$next_order}')", false);
    $new_pd = (int)sql_insert_id();
    if ($new_pd <= 0) alert('새 디자인 생성에 실패했습니다.');

    design_json_apply($g5['css_table'], $new_pd, $settings);
    goto_url('./design_form.php?pd_id='.$new_pd);
}

alert('알 수 없는 작업입니다.', './design_form.php');
