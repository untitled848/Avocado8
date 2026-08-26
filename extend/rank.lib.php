<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 랭크 ID → 랭크명
function get_rank_name($rank)
{
    global $g5;

    $rank = (int)$rank;
    if (empty($g5['level_table']) || $rank <= 0) {
        return '-';
    }

    $row = sql_fetch("SELECT lv_name FROM {$g5['level_table']} WHERE lv_id = '{$rank}'");
    if (!is_array($row) || $row['lv_name'] === '' || $row['lv_name'] === null) {
        return '-';
    }

    return $row['lv_name'];
}

// 랭크명 → 랭크 ID
function get_rank_id($rank_name)
{
    global $g5;

    if (empty($g5['level_table']) || $rank_name === '') {
        return 0;
    }

    $rank_name = sql_real_escape_string($rank_name); // 환경에 따라 sql_escape 함수 사용
    $row = sql_fetch("SELECT lv_id FROM {$g5['level_table']} WHERE lv_name = '{$rank_name}'");
    if (!is_array($row) || empty($row['lv_id'])) {
        return 0;
    }

    return (int)$row['lv_id'];
}

// 경험치 기준 랭크/다음 랭크/추가 포인트 계산
function get_rank_exp($exp, $ch_id)
{
    global $g5;

    $result = array(
        'rank'      => 0,
        'next_rank' => 0,
        'add_point' => 0,
        'rest_exp'  => 0,
    );

    if (empty($g5['level_table']) || empty($g5['character_table'])) {
        return $result;
    }

    $exp   = (int)$exp;
    $ch_id = (int)$ch_id;
    if ($ch_id <= 0) {
        return $result;
    }

    // 캐릭터의 현재 랭크 정보
    $ch = sql_fetch("SELECT ch_rank FROM {$g5['character_table']} WHERE ch_id = '{$ch_id}'");
    $ch_rank_id = isset($ch['ch_rank']) ? (int)$ch['ch_rank'] : 0;

    $ch_rank = array('lv_exp' => 0);
    if ($ch_rank_id > 0) {
        $ch_rank = sql_fetch("SELECT lv_exp FROM {$g5['level_table']} WHERE lv_id = '{$ch_rank_id}'");
        if (!is_array($ch_rank) || $ch_rank['lv_exp'] === null) {
            $ch_rank = array('lv_exp' => 0);
        }
    }

    // 현재 경험치에 해당하는 랭크
    $level = sql_fetch("SELECT * FROM {$g5['level_table']} WHERE lv_exp <= {$exp} ORDER BY lv_exp DESC LIMIT 1");
    if (!is_array($level) || empty($level['lv_id'])) {
        // 조건 만족하는 랭크가 없으면 최소 랭크 사용
        $level = sql_fetch("SELECT * FROM {$g5['level_table']} ORDER BY lv_exp ASC LIMIT 1");
        if (!is_array($level) || empty($level['lv_id'])) {
            // 레벨 정의 자체가 없으면 그대로 리턴
            return $result;
        }
    }

    // 다음 랭크
    $n_level = sql_fetch("SELECT * FROM {$g5['level_table']} WHERE lv_exp > {$level['lv_exp']} ORDER BY lv_exp ASC LIMIT 1");
    if (!is_array($n_level) || empty($n_level['lv_id'])) {
        $n_level = null; // 최상위 랭크
    }

    $add_status = 0;
    $ch_lv_exp  = (int)$ch_rank['lv_exp'];
    $lv_exp     = (int)$level['lv_exp'];

    if ($ch_lv_exp < $lv_exp) {
        // 랭크 상승: 사이 구간 lv_add_state 합산
        $row = sql_fetch("
            SELECT SUM(lv_add_state) AS status
              FROM {$g5['level_table']}
             WHERE lv_exp > '{$ch_lv_exp}'
               AND lv_exp <= '{$lv_exp}'
        ");
        if (!empty($row['status'])) {
            $add_status = (int)$row['status'];
        }
    } elseif ($ch_lv_exp > $lv_exp) {
        // 랭크 하락: 회수할 포인트
        $row = sql_fetch("
            SELECT SUM(lv_add_state) AS status
              FROM {$g5['level_table']}
             WHERE lv_exp <= '{$ch_lv_exp}'
               AND lv_exp > '{$lv_exp}'
        ");
        if (!empty($row['status'])) {
            $add_status = -1 * (int)$row['status'];
        }
    }

    $result['rank']      = (int)$level['lv_id'];
    $result['next_rank'] = $n_level ? (int)$n_level['lv_id'] : 0;
    $result['add_point'] = $add_status;
    $result['rest_exp']  = $n_level
        ? max(0, (int)$n_level['lv_exp'] - $lv_exp)
        : 0;

    return $result;
}
