<?php
/**
 * bbs_action_map — 지역 이동/이벤트 헬퍼
 *
 * 원본: AvocadoEdition.MapManager
 * wr_log:
 *   MAP||{ma_id}||{type}||{me_id}||{msg}
 *     type: F(무이벤트) D(텍스트) I(아이템) G(화폐) W(강제이동) M(몬스터)
 *   MAP_MON||{state}||{mon_atk}||{user_atk}||{hp_info}||{d1}+{d2}+{item_atk}
 */
if (!defined('_GNUBOARD_')) exit;

global $g5;
if (!is_array($g5)) $g5 = array();
if (empty($g5['module_map_table'])) {
    $g5['module_map_table']           = G5_TABLE_PREFIX.'module_map';
    $g5['module_map_event_table']     = G5_TABLE_PREFIX.'module_map_event';
    $g5['module_map_character_table'] = G5_TABLE_PREFIX.'module_map_character';
}

if (!function_exists('bbs_action_map_board_enabled')) {
/** write 스코프만. 댓글 MAP_MON 도 이 게이트. */
function bbs_action_map_board_enabled($bo_table) {
    if ($bo_table === '' || !function_exists('mdl_board_enabled')) return false;
    return mdl_board_enabled($bo_table, 'bbs_action_map', 'write');
}
}

if (!function_exists('bbs_action_map_drop_comment_scope')) {
/** 구 comment 키 제거. comment ON · write OFF 보드는 write 로 승계. */
function bbs_action_map_drop_comment_scope() {
    static $done = false;
    if ($done || !function_exists('mdl_load_board_state') || !function_exists('mdl_save_board_state')) {
        return;
    }
    $done = true;
    $state = mdl_load_board_state();
    if (!is_array($state)) return;
    $key_c = 'bbs_action_map@comment';
    $key_w = 'bbs_action_map@write';
    $changed = false;
    foreach ($state as $bo => &$mods) {
        if (!is_array($mods) || !array_key_exists($key_c, $mods)) continue;
        if (!empty($mods[$key_c]) && empty($mods[$key_w])) {
            $mods[$key_w] = true;
        }
        unset($mods[$key_c]);
        $changed = true;
    }
    unset($mods);
    if ($changed) mdl_save_board_state($state);
}
}

if (!function_exists('bbs_action_map_esc')) {
function bbs_action_map_esc($v) {
    return sql_real_escape_string((string)$v);
}
}

if (!function_exists('bbs_action_map_post_unslash')) {
/** G5 POST 는 이미 addslashes. 저장 전 1회만 푼다. (HOOK_CHECKLIST §G-3) */
function bbs_action_map_post_unslash($v) {
    if (is_array($v)) {
        foreach ($v as $k => $x) {
            $v[$k] = bbs_action_map_post_unslash($x);
        }
        return $v;
    }
    return stripslashes((string)$v);
}
}

if (!function_exists('bbs_action_map_esc_post')) {
function bbs_action_map_esc_post($v) {
    return bbs_action_map_esc(bbs_action_map_post_unslash($v));
}
}

if (!function_exists('bbs_action_map_sanitize_id')) {
function bbs_action_map_sanitize_id($id) {
    return preg_replace('/[^a-zA-Z0-9_]/', '', (string)$id);
}
}

if (!function_exists('bbs_action_map_fail')) {
/**
 * 액션 실패 중단. 타임라인 AJAX 는 JSON, mmb 는 빈 글/댓글 롤백 후 alert.
 */
function bbs_action_map_fail($msg, $return_url = '') {
    $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string)$_SERVER['SCRIPT_NAME']) : '';
    if (function_exists('timeline_post_json') && preg_match('/ajax_post\\.php$/i', $script)) {
        timeline_post_json(array('success' => false, 'message' => $msg));
    }
    if (function_exists('timeline_comment_json') && preg_match('/ajax_comment\\.php$/i', $script)) {
        timeline_comment_json(array(
            'success' => false,
            'message' => $msg,
            'comment_id' => 0,
        ));
    }
    bbs_action_map_rollback_failed_board_write();
    bbs_action_map_rollback_failed_board_comment();
    if ($return_url !== '') {
        alert($msg, $return_url);
    }
    alert($msg);
}
}

if (!function_exists('bbs_action_map_rollback_failed_board_write')) {
/** mmb write_update 는 INSERT 뒤 훅. wr_log 없는 신규 원글만 되돌린다. §F-8 */
function bbs_action_map_rollback_failed_board_write() {
    global $write_table, $wr_id, $w, $bo_table, $g5, $member;

    $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string)$_SERVER['SCRIPT_NAME']) : '';
    if (!preg_match('/write_update\\.php$/i', $script)) {
        return;
    }
    if ($w !== '' && $w !== 'r') {
        return;
    }
    $wr_id = (int)$wr_id;
    $write_table = bbs_action_map_sanitize_id($write_table);
    $bo = bbs_action_map_sanitize_id($bo_table);
    if ($wr_id < 1 || $write_table === '' || $bo === '') {
        return;
    }
    $row = sql_fetch("SELECT wr_id, wr_log, wr_is_comment FROM `{$write_table}` WHERE wr_id='{$wr_id}' LIMIT 1", false);
    if (!is_array($row) || (int)$row['wr_id'] < 1) {
        return;
    }
    if (!empty($row['wr_is_comment']) || (isset($row['wr_log']) && trim((string)$row['wr_log']) !== '')) {
        return;
    }
    sql_query("DELETE FROM `{$write_table}` WHERE wr_id='{$wr_id}' LIMIT 1", false);
    if (!empty($g5['board_new_table'])) {
        sql_query("DELETE FROM {$g5['board_new_table']} WHERE bo_table='".bbs_action_map_esc($bo)."' AND wr_id='{$wr_id}'", false);
    }
    if (!empty($g5['board_table'])) {
        sql_query("UPDATE {$g5['board_table']} SET bo_count_write = IF(bo_count_write>0, bo_count_write-1, 0) WHERE bo_table='".bbs_action_map_esc($bo)."'", false);
    }
    if (function_exists('delete_point') && !empty($member['mb_id'])) {
        delete_point($member['mb_id'], $bo, $wr_id, '쓰기');
    }
}
}

if (!function_exists('bbs_action_map_rollback_failed_board_comment')) {
/** write_comment_update 는 INSERT 뒤 훅. wr_log 없는 신규 댓글만 되돌린다. */
function bbs_action_map_rollback_failed_board_comment() {
    global $write_table, $comment_id, $w, $bo_table, $g5, $member, $wr_id;

    $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string)$_SERVER['SCRIPT_NAME']) : '';
    if (!preg_match('/write_comment_update\\.php$/i', $script)) {
        return;
    }
    if ($w !== 'c') {
        return;
    }
    $comment_id = (int)$comment_id;
    $write_table = bbs_action_map_sanitize_id($write_table);
    $bo = bbs_action_map_sanitize_id($bo_table);
    if ($comment_id < 1 || $write_table === '' || $bo === '') {
        return;
    }
    $row = sql_fetch("SELECT wr_id, wr_log, wr_is_comment FROM `{$write_table}` WHERE wr_id='{$comment_id}' LIMIT 1", false);
    if (!is_array($row) || (int)$row['wr_id'] < 1) {
        return;
    }
    if (empty($row['wr_is_comment']) || (isset($row['wr_log']) && trim((string)$row['wr_log']) !== '')) {
        return;
    }
    sql_query("DELETE FROM `{$write_table}` WHERE wr_id='{$comment_id}' LIMIT 1", false);
    if (!empty($g5['board_new_table'])) {
        sql_query("DELETE FROM {$g5['board_new_table']} WHERE bo_table='".bbs_action_map_esc($bo)."' AND wr_id='{$comment_id}'", false);
    }
    if (!empty($g5['board_table'])) {
        sql_query("UPDATE {$g5['board_table']} SET bo_count_comment = IF(bo_count_comment>0, bo_count_comment-1, 0) WHERE bo_table='".bbs_action_map_esc($bo)."'", false);
    }
    $parent = (int)$wr_id;
    if ($parent > 0) {
        sql_query("UPDATE `{$write_table}` SET wr_comment = IF(wr_comment>0, wr_comment-1, 0) WHERE wr_id='{$parent}'", false);
    }
    if (function_exists('delete_point') && !empty($member['mb_id'])) {
        delete_point($member['mb_id'], $bo, $comment_id, '댓글');
    }
}
}

if (!function_exists('bbs_action_map_ctx_write_table')) {
function bbs_action_map_ctx_write_table($ctx) {
    global $g5, $write_table;
    if (!is_array($ctx)) {
        $ctx = array();
    }
    if (!empty($ctx['write_table'])) {
        return bbs_action_map_sanitize_id($ctx['write_table']);
    }
    if (!empty($write_table)) {
        return bbs_action_map_sanitize_id($write_table);
    }
    $bo = isset($ctx['bo_table']) ? bbs_action_map_sanitize_id($ctx['bo_table']) : '';
    if ($bo === '') {
        return '';
    }
    if (!empty($GLOBALS['tl_table']) && !empty($g5['timeline_write_prefix'])) {
        return bbs_action_map_sanitize_id($g5['timeline_write_prefix'].$bo);
    }
    $prefix = !empty($g5['write_prefix']) ? $g5['write_prefix'] : G5_TABLE_PREFIX.'write_';
    return bbs_action_map_sanitize_id($prefix.$bo);
}
}

if (!function_exists('bbs_action_map_comment_parent')) {
/**
 * 타임라인 인라인 답글 list_item 은 wr_id 만 있다. 원글 wr_mon_* / ma_id 를 다시 읽는다.
 */
function bbs_action_map_comment_parent($ctx) {
    if (!is_array($ctx)) {
        $ctx = array();
    }
    $list_item = (isset($ctx['list_item']) && is_array($ctx['list_item'])) ? $ctx['list_item'] : array();
    $write = (isset($ctx['write']) && is_array($ctx['write'])) ? $ctx['write'] : array();
    $parent_id = 0;
    if (!empty($ctx['wr_id'])) {
        $parent_id = (int)$ctx['wr_id'];
    } elseif (!empty($list_item['wr_id']) && empty($list_item['wr_is_comment'])) {
        $parent_id = (int)$list_item['wr_id'];
    }

    foreach (array($write, $list_item) as $row) {
        if (!is_array($row) || empty($row['wr_id'])) {
            continue;
        }
        if (!empty($row['wr_is_comment'])) {
            continue;
        }
        if ($parent_id > 0 && (int)$row['wr_id'] !== $parent_id) {
            continue;
        }
        if (array_key_exists('wr_mon_state', $row)) {
            return $row;
        }
    }

    $table = bbs_action_map_ctx_write_table($ctx);
    if ($parent_id < 1 || $table === '') {
        return $list_item;
    }
    $row = sql_fetch("SELECT wr_id, ma_id, wr_mon_state, wr_mon_hp, wr_mon_now_hp, wr_mon_attack
        FROM `{$table}` WHERE wr_id='{$parent_id}' LIMIT 1", false);
    return (is_array($row) && !empty($row['wr_id'])) ? $row : $list_item;
}
}

if (!function_exists('bbs_action_map_pending_set')) {
function bbs_action_map_pending_set($kind, $data) {
    if (!isset($GLOBALS['_bbs_action_map_pending']) || !is_array($GLOBALS['_bbs_action_map_pending'])) {
        $GLOBALS['_bbs_action_map_pending'] = array();
    }
    $GLOBALS['_bbs_action_map_pending'][$kind] = $data;
}
}

if (!function_exists('bbs_action_map_pending_take')) {
function bbs_action_map_pending_take($kind) {
    if (empty($GLOBALS['_bbs_action_map_pending'][$kind]) || !is_array($GLOBALS['_bbs_action_map_pending'][$kind])) {
        return array();
    }
    $data = $GLOBALS['_bbs_action_map_pending'][$kind];
    unset($GLOBALS['_bbs_action_map_pending'][$kind]);
    return $data;
}
}

if (!function_exists('bbs_action_map_flush_pending_move')) {
/** write_after — 글 INSERT 성공 뒤에 위치·보상·횟수 반영 */
function bbs_action_map_flush_pending_move() {
    global $g5;

    $p = bbs_action_map_pending_take('move');
    if (empty($p['ch_id'])) {
        return;
    }
    $ch_id = (int)$p['ch_id'];
    $ma_id = isset($p['ma_id']) ? (int)$p['ma_id'] : 0;
    bbs_action_map_set_char_ma_id($ch_id, $ma_id);

    $me_id = isset($p['me_id']) ? (int)$p['me_id'] : 0;
    if ($me_id > 0 && !empty($g5['module_map_event_table'])) {
        sql_query("UPDATE `{$g5['module_map_event_table']}` SET me_now_cnt = me_now_cnt + 1 WHERE me_id = '{$me_id}'", false);
    }

    $it_id = isset($p['item_it_id']) ? (int)$p['item_it_id'] : 0;
    if ($it_id > 0) {
        if (function_exists('insert_inventory')) {
            insert_inventory($ch_id, $it_id);
        } elseif (!empty($g5['inventory_table'])) {
            $it_name = bbs_action_map_esc(isset($p['item_it_name']) ? $p['item_it_name'] : '');
            $ch_name = bbs_action_map_esc(isset($p['ch_name']) ? $p['ch_name'] : '');
            sql_query("INSERT INTO `{$g5['inventory_table']}`
                SET ch_id = '{$ch_id}',
                    it_id = '{$it_id}',
                    it_name = '{$it_name}',
                    ch_name = '{$ch_name}'", false);
        }
    }

    if (array_key_exists('money', $p) && $p['money'] !== null) {
        $mb_id = isset($p['mb_id']) ? (string)$p['mb_id'] : '';
        if ($mb_id !== '' && function_exists('insert_point')) {
            insert_point($mb_id, (int)$p['money'], '지역 이벤트', 'money', time(), 'map_event');
        }
    }

    if (!empty($p['consume_counter']) && function_exists('reset_counter_consume')) {
        $mode = function_exists('reset_counter_mode')
            ? reset_counter_mode('bbs_action_map', 'explore_mode', 'minus')
            : 'minus';
        reset_counter_consume('character_map_explore', (string)$ch_id, $mode);
    }
}
}

if (!function_exists('bbs_action_map_flush_pending_mon')) {
/** comment_after — 댓글 INSERT 성공 뒤에 원글 HP·아이템 소모 */
function bbs_action_map_flush_pending_mon() {
    $p = bbs_action_map_pending_take('mon');
    if (empty($p)) {
        return;
    }
    $table = isset($p['write_table']) ? bbs_action_map_sanitize_id($p['write_table']) : '';
    $wr_id = isset($p['wr_id']) ? (int)$p['wr_id'] : 0;
    if (!empty($p['apply_hp']) && $table !== '' && $wr_id > 0) {
        $hp = (int)$p['mon_hp'];
        $state = bbs_action_map_esc(isset($p['mon_state']) ? $p['mon_state'] : '');
        sql_query("UPDATE `{$table}`
            SET wr_mon_now_hp = '{$hp}', wr_mon_state = '{$state}'
            WHERE wr_id = '{$wr_id}'", false);
    }
    $in_id = isset($p['use_in_id']) ? (int)$p['use_in_id'] : 0;
    if ($in_id > 0 && function_exists('delete_inventory')) {
        delete_inventory($in_id, 0);
    }
}
}

if (!function_exists('bbs_action_map_j')) {
/** 한글 조사 — 코어 j() 우선 */
function bbs_action_map_j($word, $josa) {
    if (function_exists('j')) return j($word, $josa);
    return $josa;
}
}

if (!function_exists('bbs_action_map_get')) {
function bbs_action_map_get($ma_id) {
    global $g5;
    $ma_id = (int)$ma_id;
    if ($ma_id <= 0) return array();
    $row = sql_fetch("SELECT * FROM `{$g5['module_map_table']}` WHERE ma_id = '{$ma_id}' LIMIT 1");
    return is_array($row) ? $row : array();
}
}

if (!function_exists('bbs_action_map_char_ma_id')) {
/** 캐릭터 현재 위치 ma_id */
function bbs_action_map_char_ma_id($character) {
    if (!is_array($character) || empty($character['ch_id'])) return 0;
    if (isset($character['ma_id'])) return (int)$character['ma_id'];
    global $g5;
    $row = sql_fetch("SELECT ma_id FROM `{$g5['character_table']}` WHERE ch_id = '".(int)$character['ch_id']."' LIMIT 1");
    return isset($row['ma_id']) ? (int)$row['ma_id'] : 0;
}
}

if (!function_exists('bbs_action_map_set_char_ma_id')) {
function bbs_action_map_set_char_ma_id($ch_id, $ma_id) {
    global $g5;
    $ch_id = (int)$ch_id;
    $ma_id = (int)$ma_id;
    if ($ch_id <= 0) return false;
    if (!mdl_db_column_exists($g5['character_table'], 'ma_id')) {
        mdl_db_add_column($g5['character_table'], 'ma_id', "int(11) NOT NULL DEFAULT '0'");
    }
    sql_query("UPDATE `{$g5['character_table']}` SET ma_id = '{$ma_id}' WHERE ch_id = '{$ch_id}'", false);
    // 보조 테이블 동기화(있으면)
    if (!empty($g5['module_map_character_table'])) {
        $ch = sql_fetch("SELECT ch_name FROM `{$g5['character_table']}` WHERE ch_id = '{$ch_id}' LIMIT 1");
        $name = isset($ch['ch_name']) ? sql_escape_string($ch['ch_name']) : '';
        $ex = sql_fetch("SELECT mc_id FROM `{$g5['module_map_character_table']}` WHERE ch_id = '{$ch_id}' LIMIT 1");
        if (!empty($ex['mc_id'])) {
            sql_query("UPDATE `{$g5['module_map_character_table']}` SET ma_id = '{$ma_id}', ch_name = '{$name}' WHERE ch_id = '{$ch_id}'", false);
        } else {
            sql_query("INSERT INTO `{$g5['module_map_character_table']}` SET ch_id = '{$ch_id}', ma_id = '{$ma_id}', ch_name = '{$name}'", false);
        }
    }
    return true;
}
}

if (!function_exists('bbs_action_map_parse_dpt_ids')) {
/** dpt_ma_ids(콤마구분) → int[] */
function bbs_action_map_parse_dpt_ids($raw) {
    $out = array();
    foreach (explode(',', (string)$raw) as $p) {
        $id = (int)trim($p);
        if ($id > 0) $out[] = $id;
    }
    return array_values(array_unique($out));
}
}

if (!function_exists('bbs_action_map_able_destinations')) {
/**
 * 현재 위치에서 이동 가능한 맵 목록
 * @return array<int,array{ma_id:int,ma_name:string}>
 */
function bbs_action_map_able_destinations($from_ma_id) {
    global $g5;
    $from = bbs_action_map_get($from_ma_id);
    $ids = bbs_action_map_parse_dpt_ids(isset($from['dpt_ma_ids']) ? $from['dpt_ma_ids'] : '');
    $out = array();
    foreach ($ids as $id) {
        $m = bbs_action_map_get($id);
        if (empty($m['ma_id']) || (isset($m['ma_use']) && !(int)$m['ma_use'])) continue;
        $out[] = array('ma_id' => (int)$m['ma_id'], 'ma_name' => (string)$m['ma_name']);
    }
    return $out;
}
}

if (!function_exists('bbs_action_map_ensure_write_columns')) {
function bbs_action_map_ensure_write_columns($write_table) {
    if ($write_table === '') return;
    mdl_db_add_column($write_table, 'ma_id', "int(11) NOT NULL DEFAULT '0'");
    mdl_db_add_column($write_table, 'wr_mon_state', "varchar(255) NOT NULL DEFAULT ''");
    mdl_db_add_column($write_table, 'wr_mon_hp', "int(11) NOT NULL DEFAULT '0'");
    mdl_db_add_column($write_table, 'wr_mon_now_hp', "int(11) NOT NULL DEFAULT '0'");
    mdl_db_add_column($write_table, 'wr_mon_attack', "int(11) NOT NULL DEFAULT '0'");
}
}

if (!function_exists('bbs_action_map_pick_event')) {
/** 확률 구간 이벤트 1건 */
function bbs_action_map_pick_event($ma_id) {
    global $g5;
    $ma_id = (int)$ma_id;
    $seed = mt_rand(0, 100);
    $row = sql_fetch("
        SELECT * FROM `{$g5['module_map_event_table']}`
         WHERE ma_id = '{$ma_id}'
           AND me_use = '1'
           AND me_per_s <= '{$seed}' AND me_per_e >= '{$seed}'
           AND (me_replay_cnt = 0 OR me_replay_cnt > me_now_cnt)
         ORDER BY RAND()
         LIMIT 1
    ");
    return is_array($row) && !empty($row['me_id']) ? $row : array();
}
}

if (!function_exists('bbs_action_map_process_move')) {
/**
 * 위치 이동 + 이벤트 처리
 * @return array{
 *   ok:bool, customer_sql:string, log:string, ma_id:int, message:string
 * }
 */
function bbs_action_map_process_move($re_ma_id, $character, $member = array()) {
    global $config;

    $result = array(
        'ok' => false,
        'customer_sql' => '',
        'log' => '',
        'ma_id' => 0,
        'message' => '',
    );

    if (empty($character['ch_id'])) {
        $result['message'] = '캐릭터 정보가 없습니다.';
        return $result;
    }

    $re_ma_id = (int)$re_ma_id;
    $ma = bbs_action_map_get($re_ma_id);
    if (empty($ma['ma_id']) || (isset($ma['ma_use']) && !(int)$ma['ma_use'])) {
        $result['message'] = '이동할 지역 정보가 올바르지 않습니다.';
        return $result;
    }

    $cur_id = bbs_action_map_char_ma_id($character);
    if ($cur_id > 0 && $cur_id !== $re_ma_id) {
        $able = bbs_action_map_able_destinations($cur_id);
        $ok = false;
        foreach ($able as $a) {
            if ((int)$a['ma_id'] === $re_ma_id) { $ok = true; break; }
        }
        // 현재 위치 유지가 아닌데 통행 목록에 없으면 거부
        // (단, 위치가 0이면 최초 배치로 허용)
        if (!$ok) {
            $result['message'] = '통행할 수 없는 지역입니다.';
            return $result;
        }
    }

    $pending = array(
        'ch_id' => (int)$character['ch_id'],
        'ch_name' => isset($character['ch_name']) ? $character['ch_name'] : '',
        'ma_id' => (int)$ma['ma_id'],
        'me_id' => 0,
        'item_it_id' => 0,
        'item_it_name' => '',
        'money' => null,
        'mb_id' => isset($member['mb_id']) ? (string)$member['mb_id'] : '',
        'consume_counter' => true,
    );

    $m_re_type = 'F';
    $event_msg = '';
    $me_id = 0;
    $extra_sql = '';

    $me = bbs_action_map_pick_event((int)$ma['ma_id']);
    if (!empty($me['me_id'])) {
        $me_id = (int)$me['me_id'];
        $pending['me_id'] = $me_id;

        $type = isset($me['me_type']) ? (string)$me['me_type'] : '';
        if ($type === '') {
            $m_re_type = 'D';
        } elseif ($type === '아이템') {
            $m_re_type = 'I';
            $it_id = (int)$me['me_get_item'];
            if ($it_id > 0 && function_exists('get_item')) {
                $item = get_item($it_id);
                if (!empty($item['it_id'])) {
                    $pending['item_it_id'] = (int)$item['it_id'];
                    $pending['item_it_name'] = isset($item['it_name']) ? $item['it_name'] : '';
                    $event_msg = $item['it_name'].bbs_action_map_j($item['it_name'], '을').' 획득했다!';
                }
            }
        } elseif ($type === '화폐') {
            $m_re_type = 'G';
            $money = (int)$me['me_get_money'];
            $pending['money'] = $money;
            $unit = !empty($config['cf_money']) ? $config['cf_money'] : '화폐';
            if ($money < 0) {
                $event_msg = ((-1) * $money).$unit.bbs_action_map_j($unit, '을').' 잃었다.';
            } else {
                $event_msg = $money.$unit.bbs_action_map_j($unit, '을').' 획득했다!';
            }
        } elseif ($type === '이동') {
            $m_re_type = 'W';
            $to = (int)$me['me_move_map'];
            if ($to > 0) {
                $m_map = bbs_action_map_get($to);
                $pending['ma_id'] = $to;
                $ma = $m_map ?: $ma;
                $event_msg = '['.(isset($m_map['ma_name']) ? $m_map['ma_name'] : $to).'] 구역으로 이동되었다!';
            }
        } elseif ($type === '몬스터') {
            $m_re_type = 'M';
            $hp = (int)$me['me_mon_hp'];
            $atk = (int)$me['me_mon_attack'];
            $extra_sql .= " , wr_mon_state = 'S', wr_mon_hp = '{$hp}', wr_mon_now_hp = '{$hp}', wr_mon_attack = '{$atk}' ";
        }
    }

    $final_ma = (int)$pending['ma_id'];
    $log = 'MAP||'.$final_ma.'||'.$m_re_type.'||'.$me_id.'||'.str_replace(array('|', "\r", "\n"), array('/', '', ' '), $event_msg);
    bbs_action_map_pending_set('move', $pending);
    $result['ok'] = true;
    $result['ma_id'] = $final_ma;
    $result['log'] = $log;
    $result['customer_sql'] = " , wr_log = '".bbs_action_map_esc($log)."', ma_id = '{$final_ma}' ".$extra_sql;
    return $result;
}
}

if (!function_exists('bbs_action_map_process_mon')) {
/**
 * 몬스터 공략 (원글 wr_mon_* 갱신)
 */
function bbs_action_map_process_mon($write_table, $wr_id, $post = array(), $character = array()) {
    $result = array('ok' => false, 'customer_sql' => '', 'log' => '', 'message' => '');
    $wr_id = (int)$wr_id;
    $write_table = bbs_action_map_sanitize_id($write_table);
    if ($write_table === '' || $wr_id <= 0) {
        $result['message'] = '대상 글이 없습니다.';
        return $result;
    }
    bbs_action_map_ensure_write_columns($write_table);
    $origin = sql_fetch("SELECT * FROM `{$write_table}` WHERE wr_id = '{$wr_id}' LIMIT 1");
    if (empty($origin['wr_id'])) {
        $result['message'] = '대상 글을 찾을 수 없습니다.';
        return $result;
    }

    $post_ma = isset($origin['ma_id']) ? (int)$origin['ma_id'] : 0;
    $my_ma = bbs_action_map_char_ma_id($character);
    if ($post_ma > 0 && $my_ma > 0 && $post_ma !== $my_ma) {
        $result['message'] = '해당 지역에 있어야 공략할 수 있습니다.';
        return $result;
    }

    $mon_hp = (int)$origin['wr_mon_now_hp'];
    $mon_attack = (int)$origin['wr_mon_attack'];
    $mon_state = (string)$origin['wr_mon_state'];
    if ($mon_state !== 'S' || $mon_hp < 1) {
        $result['message'] = '공략할 몬스터가 없습니다.';
        return $result;
    }

    $d1 = mt_rand(1, 6);
    $d2 = mt_rand(1, 6);
    $attack = $d1 + $d2;
    $item_attack = 0;
    $use_in_id = 0;

    $use_item = isset($post['use_item']) ? (int)$post['use_item'] : 0;
    if ($use_item > 0) {
        $ch_id = isset($character['ch_id']) ? (int)$character['ch_id'] : 0;
        $inv = function_exists('get_inventory_item') ? get_inventory_item($use_item) : array();
        if (!is_array($inv) || empty($inv['in_id'])) {
            $result['message'] = '사용할 아이템을 찾을 수 없습니다.';
            return $result;
        }
        if ($ch_id < 1 || (int)$inv['ch_id'] !== $ch_id) {
            $result['message'] = '다른 캐릭터의 아이템은 사용할 수 없습니다.';
            return $result;
        }
        if (empty($inv['it_type']) || $inv['it_type'] !== '공격력추가') {
            $result['message'] = '공격력추가 아이템만 사용할 수 있습니다.';
            return $result;
        }
        $item_attack = (int)$inv['it_value'];
        $attack += $item_attack;
        $use_in_id = (int)$inv['in_id'];
    }

    $result_attack = $mon_attack - $attack; // >0 유저 피격, <0 몬스터 피격
    $apply_hp = false;
    if ($result_attack < 0) {
        $mon_hp = $mon_hp + $result_attack;
        if ($mon_hp <= 0) {
            $mon_hp = 0;
            $mon_state = 'E';
        }
        $apply_hp = true;
    }

    $log = 'MAP_MON||'.$mon_state.'||'.$mon_attack.'||'.$attack.'||유저의HP정보||'.$d1.'+'.$d2.'+'.$item_attack;
    bbs_action_map_pending_set('mon', array(
        'write_table' => $write_table,
        'wr_id' => $wr_id,
        'mon_hp' => $mon_hp,
        'mon_state' => $mon_state,
        'apply_hp' => $apply_hp,
        'use_in_id' => $use_in_id,
    ));
    $result['ok'] = true;
    $result['log'] = $log;
    $result['customer_sql'] = " , wr_log = '".bbs_action_map_esc($log)."' ";
    return $result;
}
}

if (!function_exists('bbs_action_map_render_action_panel')) {
/** 글쓰기/답글 위치이동 패널 HTML */
function bbs_action_map_render_action_panel($character, $ctx = array()) {
    $info_panel = mdl_setting_get('bbs_action_map', 'info_panel', '1');
    $cur_id = bbs_action_map_char_ma_id($character);
    $cur = $cur_id ? bbs_action_map_get($cur_id) : array();
    $ables = $cur_id ? bbs_action_map_able_destinations($cur_id) : array();

    // 위치 0이면 사용 중인 전체 맵을 목적지로
    if ($cur_id <= 0) {
        global $g5;
        $rs = sql_query("SELECT ma_id, ma_name FROM `{$g5['module_map_table']}` WHERE ma_use = '1' ORDER BY ma_id ASC");
        $ables = array();
        if ($rs) {
            while ($r = sql_fetch_array($rs)) {
                $ables[] = array('ma_id' => (int)$r['ma_id'], 'ma_name' => (string)$r['ma_name']);
            }
        }
    }

    $scope = isset($ctx['scope_id']) ? (int)$ctx['scope_id'] : 0;
    $sel_id = $scope > 0 ? 're_ma_id_'.$scope : 're_ma_id';

    ob_start();
    ?>
    <div class="comment-data map-panel" id="action_MAP">
        <dl class="map-row">
            <dt>위치이동</dt>
            <dd>
                <select name="re_ma_id" id="<?php echo htmlspecialchars($sel_id, ENT_QUOTES); ?>">
                    <?php if ($cur_id > 0) { ?>
                    <option value="<?php echo (int)$cur_id; ?>">[현재위치 유지] <?php echo htmlspecialchars(isset($cur['ma_name']) ? $cur['ma_name'] : '', ENT_QUOTES); ?></option>
                    <?php } else { ?>
                    <option value="0">지역 선택</option>
                    <?php } ?>
                    <?php foreach ($ables as $a) {
                        if ($cur_id > 0 && (int)$a['ma_id'] === $cur_id) continue;
                        $label = ($cur_id > 0)
                            ? '[위치이동] '.(isset($cur['ma_name']) ? $cur['ma_name'] : '').' ▶ '.$a['ma_name']
                            : '[이동] '.$a['ma_name'];
                    ?>
                    <option value="<?php echo (int)$a['ma_id']; ?>"><?php echo htmlspecialchars($label, ENT_QUOTES); ?></option>
                    <?php } ?>
                </select>
            </dd>
        </dl>
        <?php if ($info_panel !== '0' && !empty($cur['ma_id'])) { ?>
        <div class="map-info">
            <?php if (!empty($cur['ma_thumb'])) { ?>
            <div class="map-info-thumb"><img src="<?php echo htmlspecialchars($cur['ma_thumb'], ENT_QUOTES); ?>" alt=""></div>
            <?php } ?>
            <div class="map-info-text">
                <strong><?php echo htmlspecialchars($cur['ma_name'], ENT_QUOTES); ?></strong>
                <?php if (!empty($cur['ma_text'])) { ?>
                <p><?php echo nl2br(htmlspecialchars($cur['ma_text'], ENT_QUOTES)); ?></p>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
    </div>
    <?php
    return ob_get_clean();
}
}

if (!function_exists('bbs_action_map_render_overview')) {
/** 전체 맵 이미지 + 핀 (설정에 이미지가 있을 때) */
function bbs_action_map_render_overview($character = array()) {
    $img = mdl_setting_get('bbs_action_map', 'map_image', '');
    if ($img === '') return '';
    $w = (int)mdl_setting_get('bbs_action_map', 'map_image_width', 0);
    $h = (int)mdl_setting_get('bbs_action_map', 'map_image_height', 0);
    if ($w < 1) $w = 200;
    if ($h < 1) $h = 200;

    global $g5;
    $cur_id = bbs_action_map_char_ma_id($character);
    $rs = sql_query("SELECT * FROM `{$g5['module_map_table']}` WHERE ma_use = '1' ORDER BY ma_id ASC");
    ob_start();
    ?>
    <div id="mmb_map_pannel" class="bbs-action-map-overview">
        <div class="map-inner" style="max-width:<?php echo (int)$w; ?>px;">
            <div class="pad">
            <?php
            if ($rs) {
                while ($map = sql_fetch_array($rs)) {
                    $pos_w = ((int)$map['ma_width'] / $w) * 100;
                    $pos_h = ((int)$map['ma_height'] / $h) * 100;
                    $pos_t = ((int)$map['ma_y'] / $h) * 100; // ma_y ≈ top
                    $pos_l = ((int)$map['ma_x'] / $w) * 100; // ma_x ≈ left
                    $cls = '';
                    if ((int)$map['ma_id'] === $cur_id) $cls .= ' my';
                    $cnt = 0;
                    if (!empty($g5['character_table']) && mdl_db_column_exists($g5['character_table'], 'ma_id')) {
                        $c = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$g5['character_table']}` WHERE ma_id = '".(int)$map['ma_id']."'");
                        $cnt = isset($c['cnt']) ? (int)$c['cnt'] : 0;
                    }
                    if ($cnt > 0) $cls .= ' other';
                    ?>
                <div class="pin<?php echo $cls; ?>" style="top:<?php echo $pos_t; ?>%;left:<?php echo $pos_l; ?>%;width:<?php echo $pos_w; ?>%;height:<?php echo $pos_h; ?>%;"><span><?php echo $cnt ? $cnt : ''; ?></span></div>
                    <?php
                }
            }
            ?>
            </div>
            <img src="<?php echo htmlspecialchars($img, ENT_QUOTES); ?>" alt="지도">
        </div>
    </div>
    <?php
    return ob_get_clean();
}
}
