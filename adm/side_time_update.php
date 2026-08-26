<?php
/**
 * adm/side_time_update.php
 *
 * 시기별 side 활성 매핑 일괄 갱신 (Wave 10).
 *   POST: map[si_id][ti_id] = 1   (체크된 것만 옴)
 *   처리: time_table 의 모든 ti_id 에 대해 DELETE 후 체크된 si_id INSERT
 */
$sub_menu = '400600';
include_once('./_common.php');
check_admin_token();

global $g5;

$map = isset($_POST['map']) && is_array($_POST['map']) ? $_POST['map'] : array();

// 모든 ti_id 수집 (renewal/au 만)
$ti_ids = array();
$rs = sql_query("SELECT ti_id, ti_type FROM `{$g5['time_table']}`", false);
while ($r = sql_fetch_array($rs)) {
    $t = (string)($r['ti_type'] ?? 'renewal');
    if ($t === 'renewal' || $t === 'au') $ti_ids[] = (int)$r['ti_id'];
}

// 모든 si_id 수집
$si_ids = array();
$rs = sql_query("SELECT si_id FROM `{$g5['side_table']}`", false);
while ($r = sql_fetch_array($rs)) $si_ids[] = (int)$r['si_id'];

if (!empty($ti_ids)) {
    // 1) 대상 시기들의 매핑 모두 삭제
    $in = implode(',', $ti_ids);
    sql_query("DELETE FROM `{$g5['side_time_table']}` WHERE ti_id IN ({$in})");

    // 2) POST 에서 체크된 (si_id, ti_id) 만 INSERT
    foreach ($si_ids as $si) {
        if (empty($map[$si]) || !is_array($map[$si])) continue;
        foreach ($ti_ids as $ti) {
            if (!empty($map[$si][$ti])) {
                sql_query("INSERT IGNORE INTO `{$g5['side_time_table']}` SET si_id = '{$si}', ti_id = '{$ti}'");
            }
        }
    }
}

goto_url('./side_list.php#anc_003');
