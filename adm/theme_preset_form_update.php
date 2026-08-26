<?php
$sub_menu = "110200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');

if (!$is_admin)
    alert('관리자만 접근 가능합니다.');

$pr_id = ses($_POST, 'pr_id', 0, 'int');
if ($pr_id <= 0) goto_url('./theme_preset_list.php');

$row = sql_fetch("SELECT pr_id FROM `{$g5['theme_preset_table']}` WHERE pr_id = '{$pr_id}'", false);
if (empty($row['pr_id'])) alert('존재하지 않는 프리셋입니다.', './theme_preset_list.php');

// 기본 정보 업데이트
$pr_name  = ses($_POST, 'pr_name', '', 'string');
$pr_order = ses($_POST, 'pr_order', 0, 'int');
if ($pr_name === '') alert('이름을 입력하세요.');

// DESIGN_PRESET_TYPES 레지스트리 기반: 각 디자인 타입의 컬럼/값 정규화
// 값 포맷: '1','2',...(=프리셋 ID) | 'skin:DIRNAME'(=스킨) | 'none'(=사용 안함)
$design_in    = ses($_POST, 'design', array(), 'array');
$design_types = isset($GLOBALS['DESIGN_PRESET_TYPES']) ? $GLOBALS['DESIGN_PRESET_TYPES'] : array();

$design_sets = array();  // 컬럼명 => 값
foreach ($design_types as $dt_key => $dt_def) {
    $col        = $dt_def['column'];
    $allow_none = !empty($dt_def['allow_none']);
    $val        = isset($design_in[$dt_key]) ? trim((string)$design_in[$dt_key]) : '1';

    // 1) none
    if ($allow_none && $val === 'none') {
        $design_sets[$col] = 'none';
        continue;
    }
    // 2) skin:DIRNAME — 스킨 디렉토리명 화이트리스트 검증
    if (strpos($val, 'skin:') === 0 && !empty($dt_def['skin_gubun']) && function_exists('get_skin_dir')) {
        $skin_dir = substr($val, 5);
        // 디렉토리 형식 안전 검증
        if ($skin_dir !== '' && preg_match('#^(theme/)?[A-Za-z0-9_./-]{1,100}$#', $skin_dir) && strpos($skin_dir, '..') === false) {
            // 실제 존재하는 스킨인지 확인
            $skin_gubun = $dt_def['skin_gubun'];
            $allowed = get_skin_dir($skin_gubun);
            $allowed_theme = array();
            if ((is_array($allowed) && in_array($skin_dir, $allowed, true))
                || in_array($skin_dir, $allowed_theme, true)) {
                $design_sets[$col] = 'skin:'.$skin_dir;
                continue;
            }
        }
        // 검증 실패 → 기본값
        $design_sets[$col] = '1';
        continue;
    }
    // 3) 프리셋 ID
    $pd_check = (int)$val;
    if ($pd_check <= 0) $pd_check = 1;

    if (!empty($dt_def['table_key']) && !empty($g5[$dt_def['table_key']])) {
        $tbl   = $g5[$dt_def['table_key']];
        $exist = sql_fetch("SELECT pd_id FROM `{$tbl}` WHERE pd_id = '{$pd_check}'", false);
        $pd_check = !empty($exist['pd_id']) ? (int)$exist['pd_id'] : 1;
    }
    $design_sets[$col] = (string)$pd_check;
}

// UPDATE 절 구성
$set_sql = "pr_name = '{$pr_name}', pr_order = '{$pr_order}'";
foreach ($design_sets as $col => $val) {
    $col_e = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
    $val_e = sql_escape_string($val);
    $set_sql .= ", `{$col_e}` = '{$val_e}'";
}

// BGM
$pr_bgm_type   = ses($_POST, 'pr_bgm_type', 'playlist', 'string');
if (!in_array($pr_bgm_type, array('playlist','video'), true)) $pr_bgm_type = 'playlist';
$pr_bgm_id     = trim((string)ses($_POST, 'pr_bgm_id', '', 'string'));
$pr_bgm_id     = preg_replace('/[^A-Za-z0-9_-]/', '', $pr_bgm_id); // YouTube ID 안전 문자
$pr_bgm_volume = (int)ses($_POST, 'pr_bgm_volume', 50, 'int');
if ($pr_bgm_volume < 0)   $pr_bgm_volume = 0;
if ($pr_bgm_volume > 100) $pr_bgm_volume = 100;
$set_sql .= ", `pr_bgm_type` = '".sql_escape_string($pr_bgm_type)."'";
$set_sql .= ", `pr_bgm_id` = '".sql_escape_string($pr_bgm_id)."'";
$set_sql .= ", `pr_bgm_volume` = '{$pr_bgm_volume}'";
$pr_font_family = trim((string)ses($_POST, 'pr_font_family', '', 'string'));
$set_sql .= ", `pr_font_family` = '".sql_escape_string($pr_font_family)."'";

sql_query("UPDATE `{$g5['theme_preset_table']}` SET {$set_sql} WHERE pr_id = '{$pr_id}'", false);

// 슬롯 매핑 갱신
$slot_in   = ses($_POST, 'slot', array(), 'array');
$group_in  = ses($_POST, 'group', array(), 'array');
$slot_def  = isset($GLOBALS['THEME_SLOT_MAP']) ? $GLOBALS['THEME_SLOT_MAP'] : array();
$slot_groups = isset($GLOBALS['THEME_SLOT_GROUPS']) ? $GLOBALS['THEME_SLOT_GROUPS'] : array();

// 그룹 입력값을 해당 그룹의 모든 슬롯에 풀어서 적용 (slot_in 보다 우선)
foreach ($slot_def as $slot => $def) {
    $g = isset($def['group']) ? $def['group'] : '';
    if ($g !== '' && isset($slot_groups[$g]) && isset($group_in[$g])) {
        $slot_in[$slot] = $group_in[$g];
    }
}

foreach ($slot_def as $slot => $def) {
    $val = isset($slot_in[$slot]) ? trim((string)$slot_in[$slot]) : 'default';
    if ($val === '') $val = 'default';
    if (!is_valid_theme($val)) $val = 'default';

    // 매칭되지 않는 테마(파일/폴더 없음) → default 강제
    if ($val !== 'default') {
        $type = isset($def['type']) ? $def['type'] : '';
        $path = isset($def['path']) ? $def['path'] : '';
        $abs  = G5_PATH.'/'.G5_THEME_DIR.'/'.$val.'/'.$path;
        if ($type === 'file' && !is_file($abs)) $val = 'default';
        else if ($type === 'dir' && !is_dir($abs)) $val = 'default';
        else if ($type === 'skin' && !is_dir($abs)) $val = 'default';
    }

    $slot_esc = sql_escape_string($slot);
    $val_esc  = sql_escape_string($val);

    $exists = sql_fetch("SELECT ps_id FROM `{$g5['theme_preset_slot_table']}` WHERE pr_id = '{$pr_id}' AND ps_slot = '{$slot_esc}'", false);
    if (!empty($exists['ps_id'])) {
        sql_query("UPDATE `{$g5['theme_preset_slot_table']}` SET ps_theme = '{$val_esc}' WHERE ps_id = '{$exists['ps_id']}'", false);
    } else {
        sql_query("INSERT INTO `{$g5['theme_preset_slot_table']}` (pr_id, ps_slot, ps_theme) VALUES ('{$pr_id}', '{$slot_esc}', '{$val_esc}')", false);
    }
}

goto_url('./theme_preset_form.php?pr_id='.$pr_id);
