<?php
/**
 * extend/02_side_class_time.lib.php
 *
 * 시기별 side / class 활성 매핑 헬퍼 (Wave 10).
 *   - 매핑 테이블이 비었거나 해당 시기에 매핑 행이 0개면 "전체 활성" 으로 폴백
 *     (마이그레이션 직후 운영 중단을 막기 위함)
 *   - 매핑 행이 1개 이상 있으면 그 행들만 활성으로 간주
 *
 * 사용처:
 *   - adm/side_list.php / adm/class_list.php : 시기별 토글 UI
 *   - mypage/character/character_form.php / adm/character_form.php : 시기별 select
 *   - member/index.php : 시기에 표시할 side 그룹
 */
if (!defined('_GNUBOARD_')) exit;

/**
 * 시기 N에 활성화된 si_id 목록 (정수 배열).
 * 매핑이 비어있거나 해당 시기에 0개면 NULL 반환 (= "필터 안 걸림").
 *
 * @param int $ti_order
 * @return array|null
 */
function get_active_side_ids_for_time($ti_order)
{
    global $g5;
    $ti_order = (int)$ti_order;
    if ($ti_order <= 0) return null;

    // ti_order → ti_id 변환
    $ti = function_exists('get_time_by_order') ? get_time_by_order($ti_order) : array();
    $ti_id = isset($ti['ti_id']) ? (int)$ti['ti_id'] : 0;
    if ($ti_id <= 0) return null;

    // 매핑 테이블 자체가 비어있는지 확인 (전혀 사용 안 한 상태)
    $any = sql_fetch("SELECT 1 AS x FROM `{$g5['side_time_table']}` LIMIT 1", false);
    if (empty($any['x'])) return null; // 폴백: 전체 활성

    $rows = sql_query("SELECT si_id FROM `{$g5['side_time_table']}` WHERE ti_id = '{$ti_id}'", false);
    $out = array();
    while ($r = sql_fetch_array($rows)) $out[] = (int)$r['si_id'];

    // 이 시기에 매핑이 0개면 "이 시기엔 side 없음" 의미로 빈 배열 반환
    return $out;
}

/**
 * 시기 N에 활성화된 cl_id 목록.
 */
function get_active_class_ids_for_time($ti_order)
{
    global $g5;
    $ti_order = (int)$ti_order;
    if ($ti_order <= 0) return null;

    $ti = function_exists('get_time_by_order') ? get_time_by_order($ti_order) : array();
    $ti_id = isset($ti['ti_id']) ? (int)$ti['ti_id'] : 0;
    if ($ti_id <= 0) return null;

    $any = sql_fetch("SELECT 1 AS x FROM `{$g5['class_time_table']}` LIMIT 1", false);
    if (empty($any['x'])) return null;

    $rows = sql_query("SELECT cl_id FROM `{$g5['class_time_table']}` WHERE ti_id = '{$ti_id}'", false);
    $out = array();
    while ($r = sql_fetch_array($rows)) $out[] = (int)$r['cl_id'];
    return $out;
}

/**
 * 시기 N의 활성 side 행 목록 (권한 필터 옵션).
 * NULL 반환 시 호출측에서 "전체 활성"(폴백)으로 간주해야 함.
 *
 * @param int $ti_order
 * @param int $mb_level (0=권한 무시)
 * @return array|null  배열일 때 si_id 키 정렬된 행 리스트
 */
function get_sides_for_time($ti_order, $mb_level = 0)
{
    global $g5;
    $ids = get_active_side_ids_for_time($ti_order);
    $where = array();
    if ($mb_level > 0) $where[] = "si_auth <= '".(int)$mb_level."'";
    if (is_array($ids)) {
        if (count($ids) === 0) return array(); // 명시적으로 0개
        $where[] = 'si_id IN ('.implode(',', array_map('intval', $ids)).')';
    }
    $sql = "SELECT * FROM `{$g5['side_table']}`";
    if (!empty($where)) $sql .= ' WHERE '.implode(' AND ', $where);
    $sql .= ' ORDER BY si_id ASC';
    $rs = sql_query($sql, false);
    $out = array();
    while ($r = sql_fetch_array($rs)) $out[] = $r;
    if (!is_array($ids) && empty($out)) return null; // 폴백 의미 유지
    return $out;
}

/**
 * 시기 N의 활성 class 행 목록.
 */
function get_classes_for_time($ti_order, $mb_level = 0)
{
    global $g5;
    $ids = get_active_class_ids_for_time($ti_order);
    $where = array();
    if ($mb_level > 0) $where[] = "cl_auth <= '".(int)$mb_level."'";
    if (is_array($ids)) {
        if (count($ids) === 0) return array();
        $where[] = 'cl_id IN ('.implode(',', array_map('intval', $ids)).')';
    }
    $sql = "SELECT * FROM `{$g5['class_table']}`";
    if (!empty($where)) $sql .= ' WHERE '.implode(' AND ', $where);
    $sql .= ' ORDER BY cl_id ASC';
    $rs = sql_query($sql, false);
    $out = array();
    while ($r = sql_fetch_array($rs)) $out[] = $r;
    if (!is_array($ids) && empty($out)) return null;
    return $out;
}

/**
 * 어드민에서 시기-side 매핑을 일괄 갱신.
 *   $ti_order 시기에 대해 활성 si_id 배열을 받아 DELETE+INSERT.
 */
function set_side_time_mapping($ti_order, $si_ids)
{
    global $g5;
    $ti = get_time_by_order((int)$ti_order);
    $ti_id = isset($ti['ti_id']) ? (int)$ti['ti_id'] : 0;
    if ($ti_id <= 0) return false;

    sql_query("DELETE FROM `{$g5['side_time_table']}` WHERE ti_id = '{$ti_id}'");
    foreach ((array)$si_ids as $si) {
        $si = (int)$si;
        if ($si <= 0) continue;
        sql_query("INSERT IGNORE INTO `{$g5['side_time_table']}` SET si_id = '{$si}', ti_id = '{$ti_id}'");
    }
    return true;
}

function set_class_time_mapping($ti_order, $cl_ids)
{
    global $g5;
    $ti = get_time_by_order((int)$ti_order);
    $ti_id = isset($ti['ti_id']) ? (int)$ti['ti_id'] : 0;
    if ($ti_id <= 0) return false;

    sql_query("DELETE FROM `{$g5['class_time_table']}` WHERE ti_id = '{$ti_id}'");
    foreach ((array)$cl_ids as $cl) {
        $cl = (int)$cl;
        if ($cl <= 0) continue;
        sql_query("INSERT IGNORE INTO `{$g5['class_time_table']}` SET cl_id = '{$cl}', ti_id = '{$ti_id}'");
    }
    return true;
}

/**
 * 캐릭터의 시기 N 기준 ch_side / ch_class 값을 결정.
 *   1) ctv 테이블에 ctv_side/ctv_class 가 채워져 있으면 그 값
 *   2) 비어있고 시기 타입이 'au' 면 빈 값
 *   3) 비어있고 'renewal' 이면 직전 renewal 시기의 값을 폴백 조회 (재귀)
 *   4) 시기 1 또는 폴백 끝까지 가면 character_table 의 원본 ch_side/ch_class
 *
 * @param array  $ch    character row (ch_id 필요, 없으면 SQL 조회)
 * @param int    $time  ti_order
 * @param string $field 'side' | 'class'
 * @return string
 */
function resolve_character_side_class($ch, $time, $field = 'side')
{
    global $g5;
    $time = (int)$time;
    $field = ($field === 'class') ? 'class' : 'side';
    $ctv_col = 'ctv_'.$field;
    $ch_col  = 'ch_'.$field;

    if (empty($ch['ch_id'])) return '';
    $ch_id = (int)$ch['ch_id'];

    // 시기 1 = 원본 그대로
    if ($time <= 1) {
        return isset($ch[$ch_col]) ? (string)$ch[$ch_col] : '';
    }

    $ti = get_time_by_order($time);
    $ti_id   = isset($ti['ti_id'])   ? (int)$ti['ti_id']   : 0;
    $ti_type = isset($ti['ti_type']) ? (string)$ti['ti_type'] : 'renewal';

    if ($ti_id > 0) {
        $row = sql_fetch("SELECT `{$ctv_col}` AS v FROM `{$g5['time_value_table']}`
                          WHERE ch_id = '{$ch_id}' AND ti_id = '{$ti_id}'", false);
        if (!empty($row['v'])) return (string)$row['v'];
    }

    // 폴백
    if ($ti_type === 'au') return ''; // au 폴백 없음 (사용자 정책)

    // renewal: 직전 renewal 시기에서 다시 조회
    $prev = find_prev_time_of_type($time, 'renewal');
    if (!empty($prev['ti_order'])) {
        return resolve_character_side_class($ch, (int)$prev['ti_order'], $field);
    }

    // 최종 폴백: 시기 1 (= 원본)
    return isset($ch[$ch_col]) ? (string)$ch[$ch_col] : '';
}

/* ============================================================
 * Wave10 #7: NPC 시기별 활성 매핑
 *   - npc_time_table: (ch_id, ti_id) — ch_id 는 ch_type='npc' 캐릭터
 *   - 매핑 테이블 자체가 비어 있으면 NULL (전체 NPC 표시)
 *   - 시기 N 에 매핑 0개면 빈 배열 (해당 시기 NPC 없음)
 * ============================================================ */

function get_active_npc_ids_for_time($ti_order)
{
    global $g5;
    $ti_order = (int)$ti_order;
    if ($ti_order <= 0) return null;

    $ti = function_exists('get_time_by_order') ? get_time_by_order($ti_order) : array();
    $ti_id = isset($ti['ti_id']) ? (int)$ti['ti_id'] : 0;
    if ($ti_id <= 0) return null;

    if (empty($g5['npc_time_table'])) return null;

    $any = sql_fetch("SELECT 1 AS x FROM `{$g5['npc_time_table']}` LIMIT 1", false);
    if (empty($any['x'])) return null;

    $rows = sql_query("SELECT ch_id FROM `{$g5['npc_time_table']}` WHERE ti_id = '{$ti_id}'", false);
    $out = array();
    while ($r = sql_fetch_array($rows)) $out[] = (int)$r['ch_id'];
    return $out;
}

function set_npc_time_mapping($ti_order, $ch_ids)
{
    global $g5;
    if (empty($g5['npc_time_table'])) return false;
    $ti = get_time_by_order((int)$ti_order);
    $ti_id = isset($ti['ti_id']) ? (int)$ti['ti_id'] : 0;
    if ($ti_id <= 0) return false;

    sql_query("DELETE FROM `{$g5['npc_time_table']}` WHERE ti_id = '{$ti_id}'");
    foreach ((array)$ch_ids as $cid) {
        $cid = (int)$cid;
        if ($cid <= 0) continue;
        sql_query("INSERT IGNORE INTO `{$g5['npc_time_table']}` SET ch_id = '{$cid}', ti_id = '{$ti_id}'");
    }
    return true;
}
