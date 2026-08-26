<?php
$sub_menu = "110100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');

// ---------------- 기존 시기 수정 / 삭제 ----------------

$ti_name_arr = ses($_POST, 'ti_name', array(), 'array');
$ti_open_arr = ses($_POST, 'ti_open', array(), 'array');
$ti_type_arr = ses($_POST, 'ti_type', array(), 'array');
$ti_preset_arr = ses($_POST, 'ti_preset_id', array(), 'array');
$ti_order_arr = ses($_POST, 'ti_order', array(), 'array');
$ti_del_arr  = ses($_POST, 'ti_del', array(), 'array');

// ---------------- ti_order 재배치 (UNIQUE 충돌 회피) ----------------
// 1단계: 모든 row 일시 시프트 (+100000)
// 2단계: 새 값으로 UPDATE
if (!empty($ti_order_arr)) {
    sql_query("UPDATE `{$g5['time_table']}` SET ti_order = ti_order + 100000 WHERE ti_order < 100000", false);
    foreach ($ti_order_arr as $ti_id => $new_order) {
        $ti_id = (int)$ti_id;
        $new_order = (int)$new_order;
        if ($ti_id <= 0 || $new_order <= 0) continue;
        // 시기 1 은 항상 ti_order=1 으로 고정
        $info = get_time_by_id($ti_id);
        if (!empty($info['ti_id']) && (int)$info['ti_order'] === 100001) { // 기존 ti_order=1 이 시프트되어 100001
            $new_order = 1;
        }
        sql_query("UPDATE `{$g5['time_table']}` SET ti_order = '{$new_order}' WHERE ti_id = '{$ti_id}'", false);
    }
    // 처리 안된 row 가 남아있을 경우 최종 보정
    sql_query("UPDATE `{$g5['time_table']}` SET ti_order = ti_order - 100000 WHERE ti_order >= 100000", false);
}

$valid_types = array('renewal', 'au', 'event');

foreach ($ti_name_arr as $ti_id => $ti_name) {
    $ti_id = (int)$ti_id;
    if ($ti_id <= 0) continue;

    // 삭제 우선
    if (in_array((string)$ti_id, array_map('strval', $ti_del_arr), true)) {
        $info = get_time_by_id($ti_id);
        if (!empty($info['ti_id']) && (int)$info['ti_order'] === 1) {
            // 시기 1 삭제 차단
            continue;
        }
        sql_query("DELETE FROM `{$g5['time_table']}` WHERE ti_id = '{$ti_id}'", false);
        sql_query("DELETE FROM `{$g5['time_value_table']}` WHERE ti_id = '{$ti_id}'", false);
        sql_query("DELETE FROM `{$g5['time_article_table']}` WHERE ti_id = '{$ti_id}'", false);
        continue;
    }

    $name_esc = sql_escape_string(trim((string)$ti_name));
    $open = isset($ti_open_arr[$ti_id]) ? (int)$ti_open_arr[$ti_id] : 1;
    if (!in_array($open, array(0, 1, 2), true)) $open = 1;
    $pid = isset($ti_preset_arr[$ti_id]) ? (int)$ti_preset_arr[$ti_id] : 0;
    if ($pid < 0) $pid = 0;
    $type = isset($ti_type_arr[$ti_id]) ? (string)$ti_type_arr[$ti_id] : 'renewal';
    if (!in_array($type, $valid_types, true)) $type = 'renewal';
    // 시기 1 은 무조건 renewal + ti_open=1 고정
    $info_chk = get_time_by_id($ti_id);
    if (!empty($info_chk['ti_id']) && (int)$info_chk['ti_order'] === 1) { $type = 'renewal'; $open = 1; }
    $type_esc = sql_escape_string($type);
    sql_query("UPDATE `{$g5['time_table']}` SET ti_name = '{$name_esc}', ti_open = '{$open}', ti_preset_id = '{$pid}', ti_type = '{$type_esc}' WHERE ti_id = '{$ti_id}'", false);
}

// ---------------- 신규 시기 추가 ----------------

$new_order = ses($_POST, 'new_order', 0, 'int');
$new_name  = ses($_POST, 'new_name', '', 'string');
$new_open  = ses($_POST, 'new_open', 1, 'int');
if (!in_array($new_open, array(0, 1, 2), true)) $new_open = 1;
$new_type  = ses($_POST, 'new_type', 'renewal', 'string');
if (!in_array($new_type, $valid_types, true)) $new_type = 'renewal';
$new_type_esc = sql_escape_string($new_type);

if ($new_order > 0 && $new_name !== '') {
    $exists = sql_fetch("SELECT ti_id FROM `{$g5['time_table']}` WHERE ti_order = '{$new_order}'", false);
    if (empty($exists['ti_id'])) {
        sql_query("INSERT INTO `{$g5['time_table']}` (ti_name, ti_order, ti_open, ti_type) VALUES ('{$new_name}', '{$new_order}', '{$new_open}', '{$new_type_esc}')", false);
    }
    // 이미 같은 ti_order 가 있으면 무시 (UNIQUE 충돌 방어)
}

// ---------------- cf_time 업데이트 ----------------

$cf_time = ses($_POST, 'cf_time', 1, 'int');
if ($cf_time < 1) $cf_time = 1;

$col = sql_fetch("SHOW COLUMNS FROM `{$g5['config_table']}` LIKE 'cf_time'", false);
if (!empty($col['Field'])) {
    sql_query("UPDATE `{$g5['config_table']}` SET cf_time = '{$cf_time}'", false);
}

// ---------------- cf_preset_id 업데이트 ----------------

$cf_pid = ses($_POST, 'cf_preset_id', 0, 'int');
if ($cf_pid < 0) $cf_pid = 0;
$col = sql_fetch("SHOW COLUMNS FROM `{$g5['config_table']}` LIKE 'cf_preset_id'", false);
if (!empty($col['Field'])) {
    sql_query("UPDATE `{$g5['config_table']}` SET cf_preset_id = '{$cf_pid}'", false);
}

goto_url('./config_time.php');
