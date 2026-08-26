<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * pool 별 wr_item / wr_item_log 컬럼 이름 결정 (전역 헬퍼).
 *
 *   Wave 10+ : wr_item_log 단일 통합 — 모든 아이템 풀이 동일 컬럼 사용.
 *   pool 인자는 backwards-compat 위해 남겨두지만 항상 wr_item / wr_item_log 반환.
 */
if (!function_exists('item_use_columns_for_pool')) {
    function item_use_columns_for_pool($pool = 'item') {
        return array('wr_item', 'wr_item_log');
    }
}

/**
 * 아이템 사용 처리 (use_item) — 3단 파이프라인
 *
 *   1) item_use_pre  : 특수 아이템이 전체 가로채기 (handled=true → 코어 스킵)
 *   2) 코어 기본 처리 : delete_inventory + 뽑기/일반 로그 생성
 *   3) item_use_core : 결과(item_log/customer_sql) 가공
 *   * 이후 item_use_post 액션 발사
 *
 * @param int   $use_item   inventory.in_id
 * @param array $character  현재 캐릭터 (ch_id 필요)
 * @param array $ctx        호출자 컨텍스트 (bo_table, w, wr_id, write_table, member, post 등)
 * @return array {
 *   bool  handled        — 사용 처리됐는지 (false면 호출자가 customer_sql/item_log 추가 안 해야 함)
 *   string customer_sql  — append 할 SET 절 (예: " , wr_item='...', wr_item_log='...'")
 *   string item_log      — wr_item_log 문자열 (S||... / D||... / F||...)
 *   array  it            — 조회된 아이템 정보
 * }
 */
function item_use_process($use_item, $character, $ctx = array()) {
    global $g5;

    list($_col_it, $_col_log) = item_use_columns_for_pool(isset($ctx['pool']) ? $ctx['pool'] : 'item');

    $result = array(
        'handled'      => false,
        'customer_sql' => '',
        'item_log'     => '',
        'it'           => array(),
    );
    if (!$use_item) return $result;

    $use_item = (int)$use_item;
    $it = sql_fetch("select it.it_type, it.it_use_ever, it.it_name, it.it_id, it.it_value, it.it_content, it.it_content2 from {$g5['item_table']} it, {$g5['inventory_table']} inven where inven.in_id = '{$use_item}' and inven.it_id = it.it_id");
    if (!$it || !$it['it_id']) return $result;

    $result['it'] = $it;

    // 1) PRE — 전체 가로채기
    $pre_handled = false;
    $item_log    = '';
    $extra_sql   = '';
    if (function_exists('mdl_apply_filters')) {
        $_pre = mdl_apply_filters('item_use_pre', array(
            'handled'      => false,
            'customer_sql' => '',
            'item_log'     => '',
            'use_item'     => $use_item,
            'it'           => $it,
        ), $ctx);
        if (is_array($_pre) && !empty($_pre['handled'])) {
            $pre_handled = true;
            $extra_sql   = isset($_pre['customer_sql']) ? $_pre['customer_sql'] : '';
            $item_log    = isset($_pre['item_log'])     ? $_pre['item_log']     : '';
        }
    }

    // 2) 코어 기본 처리
    if (!$pre_handled) {
        if (!$it['it_use_ever']) {
            delete_inventory($use_item);
        }

        if ($it['it_type'] == '뽑기') {
            $seed = rand(1, 100);
            $item_result = sql_fetch("
                select re_it_id as it_id
                    from {$g5['explorer_table']}
                    where it_id = '".$it['it_id']."'
                      and (ie_per_s <= '{$seed}' and ie_per_e >= '{$seed}')
                    order by RAND()
                    limit 0, 1
            ");
            if ($item_result['it_id']) {
                $item_result['it_name'] = get_item_name($item_result['it_id']);
                insert_inventory($character['ch_id'], $item_result['it_id']);
                $item_log = "S||".$it['it_id']."||".$it['it_name']."||".$item_result['it_id']."||".$item_result['it_name'];
            } else {
                $item_log = "F||".$it['it_id']."||".$it['it_name'];
            }
        } else {
            $item_log = "D||".$it['it_id']."||".$it['it_name']."||".$it['it_type']."||".$it['it_value']."||".$it['it_content']."||".$it['it_content2'];
        }

        // 3) CORE — 결과 가공
        $core_extra_sql = '';
        if (function_exists('mdl_apply_filters')) {
            $_core = mdl_apply_filters('item_use_core', array(
                'it_type'      => $it['it_type'],
                'it'           => $it,
                'customer_sql' => '',
                'item_log'     => $item_log,
                'use_item'     => $use_item,
            ), $ctx);
            if (is_array($_core)) {
                $item_log       = isset($_core['item_log'])     ? $_core['item_log']     : $item_log;
                $core_extra_sql = isset($_core['customer_sql']) ? $_core['customer_sql'] : '';
            }
        }
        $extra_sql = " , {$_col_it} = '{$it['it_id']}', {$_col_log} = '{$item_log}'".$core_extra_sql;
    }

    // POST — 사후 알림
    if (function_exists('mdl_do_action')) {
        mdl_do_action('item_use_post', $ctx, $it, $item_log, $use_item);
    }

    $result['handled']      = true;
    $result['customer_sql'] = $extra_sql;
    $result['item_log']     = $item_log;
    return $result;
}

/**
 * 타임라인 보드인지 (mmb/basic 댓글과 구분).
 */
function item_use_is_timeline($bo_table)
{
    global $g5;
    static $cache = array();
    $bo = trim((string)$bo_table);
    if ($bo === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $bo)) {
        return false;
    }
    if (array_key_exists($bo, $cache)) {
        return $cache[$bo];
    }
    $tl_list = !empty($g5['timeline_table']) ? $g5['timeline_table'] : G5_TABLE_PREFIX.'timeline';
    $row = sql_fetch("SELECT tl_table FROM `{$tl_list}` WHERE tl_table = '".sql_escape_string($bo)."'", false);
    return $cache[$bo] = !empty($row['tl_table']);
}

/**
 * write 테이블 경로 해석 (타임라인 우선)
 */
function item_use_resolve_write_table($bo_table) {
    global $g5;
    $bo_table = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$bo_table);
    if ($bo_table === '') return '';

    if (!empty($g5['timeline_write_prefix'])) {
        $tw = $g5['timeline_write_prefix'].$bo_table;
        $exists = sql_fetch("SHOW TABLES LIKE '".sql_escape_string($tw)."'");
        if ($exists) return $tw;
    }
    return $g5['write_prefix'].$bo_table;
}

/**
 * 아이템 선물 처리 (inventory_update 로직, 호출/알림 제외)
 * wr_item_log: P||it_id||it_name||re_ch_id||re_ch_name
 *
 * @param int   $in_id      inventory.in_id
 * @param int   $to_ch_id   받는 캐릭터 ID
 * @param array $character  보내는 캐릭터
 * @param array $ctx
 * @return array {handled, customer_sql, item_log, it}
 */
function item_gift_process($in_id, $to_ch_id, $character, $ctx = array()) {
    global $g5;

    list($_col_it, $_col_log) = item_use_columns_for_pool(isset($ctx['pool']) ? $ctx['pool'] : 'item');

    $result = array(
        'handled'      => false,
        'customer_sql' => '',
        'item_log'     => '',
        'it'           => array(),
    );

    $in_id = (int)$in_id;
    $to_ch_id = (int)$to_ch_id;
    $from_ch_id = (int)(isset($character['ch_id']) ? $character['ch_id'] : 0);
    if (!$in_id || !$to_ch_id || !$from_ch_id) return $result;
    if ($to_ch_id === $from_ch_id) return $result;

    $in = sql_fetch("
        select inven.*, it.it_id, it.it_name, it.it_has, it.it_type, it.it_value, it.it_content, it.it_content2
          from {$g5['inventory_table']} inven
          join {$g5['item_table']} it on it.it_id = inven.it_id
         where inven.in_id = '{$in_id}'
           and inven.ch_id = '{$from_ch_id}'
    ");
    if (!$in || empty($in['in_id'])) return $result;
    if (!empty($in['it_has']) && (string)$in['it_has'] !== '0') return $result;

    $re_ch = function_exists('get_character') ? get_character($to_ch_id) : sql_fetch("SELECT * FROM {$g5['character_table']} WHERE ch_id = '{$to_ch_id}'");
    if (!$re_ch || empty($re_ch['ch_id'])) return $result;

    $se_ch_name = isset($character['ch_name']) ? $character['ch_name'] : '';
    $re_ch_name = isset($re_ch['ch_name']) ? $re_ch['ch_name'] : '';

    $se_ch_name_esc = sql_escape_string($se_ch_name);
    $re_ch_name_esc = sql_escape_string($re_ch_name);

    sql_query("
        UPDATE {$g5['inventory_table']} SET
            ch_id = '{$to_ch_id}',
            ch_name = '{$re_ch_name_esc}',
            se_ch_id = '{$from_ch_id}',
            se_ch_name = '{$se_ch_name_esc}',
            re_ch_id = '{$to_ch_id}',
            re_ch_name = '{$re_ch_name_esc}'
        WHERE in_id = '{$in_id}'
    ", false);

    if (function_exists('mdl_do_action')) {
        mdl_do_action('inventory_send_after', $in_id, $from_ch_id, $to_ch_id);
    }

    $it_id = (int)$in['it_id'];
    $it_name = sql_escape_string($in['it_name']);
    $item_log = "P||{$it_id}||{$in['it_name']}||{$to_ch_id}||{$re_ch_name}";
    $item_log_esc = sql_escape_string($item_log);

    $result['handled'] = true;
    $result['it'] = $in;
    $result['item_log'] = $item_log;
    $result['customer_sql'] = " , {$_col_it} = '{$it_id}', {$_col_log} = '{$item_log_esc}'";
    return $result;
}
