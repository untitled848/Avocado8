<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 통합 정산 라이브러리 (Phase 2.A)
 *
 * 정산 = "복수 캐릭터 × 복수 항목(아이템/타이틀/화폐/경험치 + 모듈 확장 타입)" 일괄 지급.
 * 모듈은 'payout_item_types' 필터로 신규 타입을 등록하고
 * 'payout_resolve_<type>' 필터로 실제 지급 로직을 콜백할 수 있다.
 *
 * payload 항목 형식: ['type'=>'item|title|money|exp|<module>', 'id'=>int, 'qty'=>int, 'extra'=>...]
 * targets 형식: int[] (ch_id 배열)
 */

if (!function_exists('payout_item_types_default')) {
    function payout_item_types_default()
    {
        global $config;
        $money_label = (!empty($config['cf_money'])) ? $config['cf_money'] : '재화';
        $exp_label = (!empty($config['cf_exp_name'])) ? $config['cf_exp_name'] : '경험치';
        return array(
            'item'  => array('label'=>'아이템',   'search_url'=>G5_URL.'/ajax/_search_item.php',  'qty'=>true,  'id'=>true),
            'title' => array('label'=>'타이틀',   'search_url'=>G5_URL.'/ajax/_search_title.php', 'qty'=>false, 'id'=>true),
            'money' => array('label'=>$money_label,'search_url'=>'',                              'qty'=>true,  'id'=>false),
            'exp'   => array('label'=>$exp_label,   'search_url'=>'',                              'qty'=>true,  'id'=>false),
        );
    }
}

if (!function_exists('payout_item_types')) {
    function payout_item_types()
    {
        $types = payout_item_types_default();
        if (function_exists('mdl_apply_filters')) {
            $types = mdl_apply_filters('payout_item_types', $types);
            if (!is_array($types)) $types = payout_item_types_default();
        }
        return $types;
    }
}

if (!function_exists('payout_resolve_targets')) {
    /**
     * 대상 캐릭터 ID 해석.
     *  $spec = [
     *    'mode'        => 'all'|'side'|'class'|'direct',
     *    'side_ids'    => int[],
     *    'class_ids'   => int[],
     *    'ch_ids'      => int[],
     *    'include_npc' => 0|1,  // all/side/class 모드에서만 의미
     *  ]
     */
    function payout_resolve_targets($spec)
    {
        global $g5;
        $mode = isset($spec['mode']) ? (string)$spec['mode'] : '';
        $include_npc = !empty($spec['include_npc']) ? 1 : 0;

        if ($mode === 'direct') {
            $ids = isset($spec['ch_ids']) && is_array($spec['ch_ids']) ? $spec['ch_ids'] : array();
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
            return $ids;
        }

        $where = array("ch_state = '승인'");
        if (!$include_npc) $where[] = "ch_type <> 'npc'";

        if ($mode === 'side') {
            $sides = isset($spec['side_ids']) && is_array($spec['side_ids']) ? array_filter(array_map('intval', $spec['side_ids'])) : array();
            if (count($sides) === 0) return array();
            $where[] = 'ch_side IN ('.implode(',', $sides).')';
        } else if ($mode === 'class') {
            $classes = isset($spec['class_ids']) && is_array($spec['class_ids']) ? array_filter(array_map('intval', $spec['class_ids'])) : array();
            if (count($classes) === 0) return array();
            $where[] = 'ch_class IN ('.implode(',', $classes).')';
        } else if ($mode !== 'all') {
            return array();
        }

        $sql = "SELECT ch_id FROM `{$g5['character_table']}` WHERE ".implode(' AND ', $where);
        $rs = sql_query($sql, false);
        $out = array();
        while ($r = sql_fetch_array($rs)) $out[] = (int)$r['ch_id'];
        return $out;
    }
}

if (!function_exists('payout_apply_one')) {
    /**
     * 단일 캐릭터에게 단일 항목을 지급.
     * @return array ['ok'=>bool, 'message'=>string]
     */
    function payout_apply_one($ch_id, $payload, $memo = '')
    {
        $ch_id   = (int)$ch_id;
        $type    = isset($payload['type']) ? (string)$payload['type'] : '';
        if ($ch_id <= 0 || $type === '') return array('ok'=>false, 'message'=>'invalid');

        // 모듈 우선 처리
        if (function_exists('mdl_apply_filters')) {
            $hook = 'payout_resolve_'.$type;
            $ret = mdl_apply_filters($hook, null, $ch_id, $payload, $memo);
            if (is_array($ret) && isset($ret['ok'])) return $ret;
        }

        // 코어 처리
        $id  = isset($payload['id'])  ? (int)$payload['id']  : 0;
        $qty = isset($payload['qty']) ? (int)$payload['qty'] : 1;
        if ($qty < 1) $qty = 1;

        if ($type === 'item') {
            if ($id <= 0) return array('ok'=>false, 'message'=>'item id 누락');
            if (!function_exists('insert_inventory')) return array('ok'=>false, 'message'=>'insert_inventory 미정의');
            $n = insert_inventory($ch_id, $id, null, $qty);
            return array('ok'=>$n > 0, 'message'=>($n>0 ? "아이템 {$id} × {$n}" : '지급 실패'));
        }
        if ($type === 'title') {
            return payout_grant_title($ch_id, $id);
        }
        if ($type === 'money') {
            return payout_grant_money($ch_id, $qty, $memo);
        }
        if ($type === 'exp') {
            if (!function_exists('insert_exp')) return array('ok'=>false, 'message'=>'insert_exp 미정의');
            insert_exp($ch_id, $qty, $memo, '정산');
            return array('ok'=>true, 'message'=>"경험치 +{$qty}");
        }
        return array('ok'=>false, 'message'=>"알 수 없는 타입: {$type}");
    }
}

if (!function_exists('payout_grant_title')) {
    function payout_grant_title($ch_id, $ti_id)
    {
        global $g5;
        $ch_id = (int)$ch_id; $ti_id = (int)$ti_id;
        if ($ch_id <= 0 || $ti_id <= 0) return array('ok'=>false, 'message'=>'invalid');
        $ch = sql_fetch("SELECT ch_id, ch_name FROM `{$g5['character_table']}` WHERE ch_id = '{$ch_id}'");
        if (empty($ch['ch_id'])) return array('ok'=>false, 'message'=>'캐릭터 없음');
        $ti = sql_fetch("SELECT ti_id, ti_title FROM `{$g5['title_table']}` WHERE ti_id = '{$ti_id}'");
        if (empty($ti['ti_id'])) return array('ok'=>false, 'message'=>'타이틀 없음');
        $dup = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$g5['title_has_table']}` WHERE ti_id='{$ti_id}' AND ch_id='{$ch_id}'");
        if (!empty($dup['cnt'])) return array('ok'=>true, 'message'=>'이미 보유');
        $ch_name_sql = sql_real_escape_string($ch['ch_name']);
        sql_query("INSERT INTO `{$g5['title_has_table']}` SET ch_id='{$ch_id}', ch_name='{$ch_name_sql}', ti_id='{$ti_id}', hi_use='1'");
        return array('ok'=>true, 'message'=>"타이틀 {$ti['ti_title']} 지급");
    }
}

if (!function_exists('payout_grant_money')) {
    /**
     * 캐릭터 소유자(mb_id)에게 화폐(point) 지급.
     */
    function payout_grant_money($ch_id, $amount, $memo = '')
    {
        global $g5, $config;
        $ch_id = (int)$ch_id; $amount = (int)$amount;
        if ($ch_id <= 0 || $amount === 0) return array('ok'=>false, 'message'=>'invalid amount');
        $ch = sql_fetch("SELECT ch_id, ch_name, mb_id FROM `{$g5['character_table']}` WHERE ch_id = '{$ch_id}'");
        if (empty($ch['mb_id'])) return array('ok'=>false, 'message'=>'캐릭터 소유자 없음');
        if (!function_exists('insert_point')) return array('ok'=>false, 'message'=>'insert_point 미정의');
        $unit = isset($config['cf_money_pice']) ? $config['cf_money_pice'] : '';
        $content = '[정산] ['.$ch['ch_name'].'] '.$amount.$unit;
        if ($memo !== '') $content .= ' — '.$memo;
        insert_point($ch['mb_id'], $amount, $content);
        return array('ok'=>true, 'message'=>"화폐 {$amount}{$unit}");
    }
}

if (!function_exists('payout_apply')) {
    /**
     * targets × payload_list 일괄 적용.
     * @return array [
     *   'targets'   => int N,
     *   'payloads'  => int N,
     *   'success'   => int,
     *   'failed'    => int,
     *   'logs'      => array<string>,
     * ]
     */
    function payout_apply($targets, $payload_list, $memo = '')
    {
        if (!is_array($targets)) $targets = array();
        if (!is_array($payload_list)) $payload_list = array();
        $targets = array_values(array_unique(array_filter(array_map('intval', $targets))));
        $stats = array(
            'targets'  => count($targets),
            'payloads' => count($payload_list),
            'success'  => 0,
            'failed'   => 0,
            'logs'     => array(),
        );
        if (function_exists('mdl_do_action')) {
            mdl_do_action('payout_begin', $targets, $payload_list, $memo);
        }
        foreach ($targets as $ch_id) {
            foreach ($payload_list as $payload) {
                if (!is_array($payload)) continue;
                $r = payout_apply_one($ch_id, $payload, $memo);
                if (!empty($r['ok'])) $stats['success']++; else $stats['failed']++;
                $stats['logs'][] = '['.$ch_id.'] '.(isset($payload['type'])?$payload['type']:'?').' → '.(isset($r['message'])?$r['message']:'');
            }
        }
        if (function_exists('mdl_do_action')) {
            mdl_do_action('payout_end', $targets, $payload_list, $memo, $stats);
        }
        return $stats;
    }
}

if (!function_exists('payout_preset_get')) {
    function payout_preset_get($cp_id)
    {
        global $g5;
        $cp_id = (int)$cp_id;
        if ($cp_id <= 0 || empty($g5['calc_preset_table'])) return null;
        $row = sql_fetch("SELECT * FROM `{$g5['calc_preset_table']}` WHERE cp_id = '{$cp_id}'");
        if (empty($row['cp_id'])) return null;
        $row['payload'] = payout_preset_decode($row['cp_payload']);
        return $row;
    }
}

if (!function_exists('payout_preset_decode')) {
    function payout_preset_decode($json)
    {
        if (!is_string($json) || $json === '') return array();
        $arr = json_decode($json, true);
        if (!is_array($arr)) return array();
        return $arr;
    }
}

if (!function_exists('payout_preset_list')) {
    function payout_preset_list($only_request = false)
    {
        global $g5;
        if (empty($g5['calc_preset_table'])) return array();
        $where = "WHERE cp_use = '1'";
        if ($only_request) $where .= " AND cp_use_request = '1'";
        $sql = "SELECT cp_id, cp_name, cp_use_request, cp_memo FROM `{$g5['calc_preset_table']}` {$where} ORDER BY cp_order ASC, cp_id ASC";
        $rs = sql_query($sql, false);
        $out = array();
        while ($r = sql_fetch_array($rs)) $out[] = $r;
        return $out;
    }
}
