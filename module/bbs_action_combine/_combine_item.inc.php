<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * "조합횟수" 아이템 통합.
 *
 * 모듈이 활성화되어 있으면 이 파일이 로드되어
 *   - character_combine reset_setting 자동 등록 (없을 때)
 *   - it_type='조합횟수' 아이템을 글/댓글/마이페이지 사용 시 처리
 *
 * apply / 필터 등록은 함수 존재 여부로 멱등 보장.
*/

// reset_setting 자동 등록
if (function_exists('reset_setting_register') && function_exists('reset_setting_get')) {
    if (!reset_setting_get('character_combine')) {
        reset_setting_register('character_combine', array(
            'label'   => '아이템 조합 횟수',
            'period'  => 'daily',
            'default' => 0,
            'plus'    => 0,
            'max'     => 5,
            'min'     => 0,
            'type'    => 'character',
        ));
    }
}

// 모드 helper — 범용 reset_counter_mode 사용
if (!function_exists('combine_item_mode')) {
    function combine_item_mode() {
        if (function_exists('reset_counter_mode')) return reset_counter_mode('bbs_action_combine', 'combine_mode', 'minus');
        return mdl_setting_get('bbs_action_combine', 'combine_mode', 'minus');
    }
}

// 조합 횟수 변경 — value 만큼 카운터 증감 (양수=회복, 음수=차감)
if (!function_exists('combine_item_apply')) {
    function combine_item_apply($ch_id, $it_value) {
        // 범용 reset_counter_apply 에 위임 (단일 진입점)
        if (function_exists('reset_counter_apply')) {
            return reset_counter_apply('character_combine', $ch_id, $it_value, combine_item_mode());
        }
        $ch_id = (int)$ch_id;
        $delta = (int)$it_value;
        if ($ch_id <= 0 || $delta === 0) return 0;
        if (!function_exists('reset_setting_get') || !function_exists('reset_value_get') || !function_exists('reset_value_set')) return 0;

        $set = reset_setting_get('character_combine');
        if (!$set) return 0;

        $max  = (int)$set['rs_max'];
        $mode = combine_item_mode();
        $cur  = (int)reset_value_get('character_combine', $ch_id);

        $remain     = ($mode === 'plus') ? $cur : ($max - $cur);
        $new_remain = max(0, min($max, $remain + $delta));
        $applied    = $new_remain - $remain;

        if ($applied === 0) return 0;
        if ($mode === 'plus') {
            reset_value_set('character_combine', $ch_id, $new_remain);
        } else {
            reset_value_set('character_combine', $ch_id, $max - $new_remain);
        }
        return $applied;
    }
}

// 필터 중복 등록 방지 — 한번만 부착
if (empty($GLOBALS['__combine_item_filters_loaded'])) {
    $GLOBALS['__combine_item_filters_loaded'] = true;

    // 1) 마이페이지 인벤 → 즉시 사용
    mdl_add_filter('inventory_use_dispatch', function ($result, $ctx) {
        if (!empty($result['handled'])) return $result;

        $in       = isset($ctx['in']) ? $ctx['in'] : array();
        $function = isset($ctx['inven_function']) ? $ctx['inven_function'] : '';
        if ($function !== '조합횟수') return $result;

        $in_id   = (int)($ctx['in_id'] ?? 0);
        $ch_id   = (int)($in['ch_id'] ?? 0);
        $value   = (int)($in['it_value'] ?? 0);
        $name    = (string)($in['it_name'] ?? '아이템');
        $ever    = !empty($in['it_use_ever']);
        $ret_url = (string)($ctx['return_url'] ?? '');

        $applied = combine_item_apply($ch_id, $value);

        if (function_exists('delete_inventory')) delete_inventory($in_id, $ever ? 1 : 0);
        if (function_exists('mdl_do_action')) mdl_do_action('inventory_use_after', $in_id, $ch_id, '조합횟수');

        $msg = ($applied > 0)
            ? "『{$name}』 사용 — 조합 횟수 +{$applied}"
            : (($applied < 0) ? "『{$name}』 사용 — 조합 횟수 {$applied}" : "『{$name}』 사용 — (변화 없음 · 이미 한도 도달)");

        $result['handled'] = true;
        $result['echo']    = "LOCATIONURL||||" . $ret_url . "&amp;tabs=i&amp;msg=" . urlencode($msg);
        return $result;
    }, 10);

    // 2) 글/댓글 작성 시 use_item / 운명토큰 (Wave 10+ : wr_item_log 단일 컬럼)
    mdl_add_filter('item_use_pre', function ($result, $ctx) {
        if (!empty($result['handled'])) return $result;
        $it = isset($result['it']) ? $result['it'] : array();
        if (!is_array($it) || ($it['it_type'] ?? '') !== '조합횟수') return $result;

        $character = isset($ctx['character']) ? $ctx['character'] : array();
        $ch_id     = (int)($character['ch_id'] ?? 0);
        $value     = (int)($it['it_value'] ?? 0);
        $name      = (string)($it['it_name'] ?? '아이템');
        $use_item  = (int)($result['use_item'] ?? 0);

        $applied = combine_item_apply($ch_id, $value);

        if (empty($it['it_use_ever']) && function_exists('delete_inventory') && $use_item > 0) {
            delete_inventory($use_item);
        }

        $sign     = ($applied >= 0) ? "+{$applied}" : (string)$applied;
        $item_log = "D||" . ($it['it_id'] ?? 0) . "||" . $name . "||조합횟수||" . $value . "||" . $sign . "||";

        // Wave 10+ : 단일 wr_item / wr_item_log 컬럼
        $_col_it  = 'wr_item';
        $_col_log = 'wr_item_log';

        $result['handled']      = true;
        $result['item_log']     = $item_log;
        $result['customer_sql'] = " , {$_col_it} = '" . sql_real_escape_string((string)($it['it_id'] ?? '')) . "', {$_col_log} = '" . sql_real_escape_string($item_log) . "' ";
        return $result;
    }, 10);

    // 3) it_type 동적 카테고리 — '조합횟수' 옵션 추가
    mdl_add_filter('item_type_options', function ($opts) {
        if (!in_array('조합횟수', $opts, true)) $opts[] = '조합횟수';
        return $opts;
    });
}
