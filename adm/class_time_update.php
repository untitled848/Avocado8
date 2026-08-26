<?php
/**
 * adm/class_time_update.php
 *
 * 시기별 class 활성 매핑 일괄 갱신 (Wave 10).
 */
$sub_menu = '400700';
include_once('./_common.php');
check_admin_token();

global $g5;

$map = isset($_POST['map']) && is_array($_POST['map']) ? $_POST['map'] : array();

$ti_ids = array();
$rs = sql_query("SELECT ti_id, ti_type FROM `{$g5['time_table']}`", false);
while ($r = sql_fetch_array($rs)) {
    $t = (string)($r['ti_type'] ?? 'renewal');
    if ($t === 'renewal' || $t === 'au') $ti_ids[] = (int)$r['ti_id'];
}

$cl_ids = array();
$rs = sql_query("SELECT cl_id FROM `{$g5['class_table']}`", false);
while ($r = sql_fetch_array($rs)) $cl_ids[] = (int)$r['cl_id'];

if (!empty($ti_ids)) {
    $in = implode(',', $ti_ids);
    sql_query("DELETE FROM `{$g5['class_time_table']}` WHERE ti_id IN ({$in})");

    foreach ($cl_ids as $cl) {
        if (empty($map[$cl]) || !is_array($map[$cl])) continue;
        foreach ($ti_ids as $ti) {
            if (!empty($map[$cl][$ti])) {
                sql_query("INSERT IGNORE INTO `{$g5['class_time_table']}` SET cl_id = '{$cl}', ti_id = '{$ti}'");
            }
        }
    }
}

goto_url('./class_list.php#anc_003');
