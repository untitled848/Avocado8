<?php
if (!defined('_GNUBOARD_')) exit;

// 현재 모듈 URL (view 파일에서 이미지/JS/CSS 경로로 사용). 모든 bootstrap 공통 규약.
$module_url = G5_MDL_URL.'/bbs_action_search';

/**
 * mmb 탐색(S) 액션 모듈
 *
 *   설정 (avo_module_setting)
 *     bbs_action_search.search_mode : 'minus' (차감식, 기본) | 'plus' (누적식, 행동력)
 *
 *   카운터 (avo_reset_setting / avo_reset_manager)
 *     rs_name = 'character_search', rm_subkey = ch_id
 *     - minus: rm_value = "사용 횟수"   → 남은 = rs_max - rm_value, 탐색시 +1
 *     - plus : rm_value = "남은 행동력" → 남은 = rm_value,         탐색시 -1
 *
 *   횟수 로직은 범용 reset_counter_* (extend/00_reset.lib.php) 을 직접 호출한다.
 */

// 0) 캐릭터 신규 생성 시 — reset_manager 자동 등록
mdl_add_action('character_create_after', function ($ch_id /*, $ctx */) {
    if (!$ch_id) return;
    if (!function_exists('reset_value_set')) return;
    if (!function_exists('reset_setting_get') || !reset_setting_get('character_search')) return;
    $mode = reset_counter_mode('bbs_action_search', 'search_mode', 'minus');
    reset_value_set('character_search', $ch_id, reset_counter_initial_value('character_search', $mode));
}, 10);

// 1) 글 폼 옵션 (write scope)
mdl_add_filter('bbs_write_form_action_options', function ($html, $ctx) {
    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    if (!mdl_board_enabled($bo_table, 'bbs_action_search', 'write')) return $html;

    // 수정 모드에서 이미 wr_log 가 차 있으면 액션 옵션 숨김 (더이상 굴리지 않음)
    if (!empty($ctx['write']['wr_log'])) return $html;

    global $character;
    $mode   = reset_counter_mode('bbs_action_search', 'search_mode', 'minus');
    $key    = isset($character['ch_id']) ? (string)$character['ch_id'] : '';
    $remain = reset_counter_remain('character_search', $key, $mode);
    $max    = reset_counter_max('character_search', 5);
    $disabled = ($remain <= 0) ? 'disabled' : '';

    return $html.'<option value="S" '.$disabled.'>탐색 ('.$remain.'/'.$max.')</option>';
}, 10);

// 1-b) 댓글 폼 옵션 (comment scope)
// ctx.action_ui === 'select' 이면 타임라인 답글 등 select UI용 <option> 반환
mdl_add_filter('bbs_comment_form_action_options', function ($html, $ctx) {
    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    if (!mdl_board_enabled($bo_table, 'bbs_action_search', 'comment')) return $html;

    global $character;
    $mode   = reset_counter_mode('bbs_action_search', 'search_mode', 'minus');
    $key    = isset($character['ch_id']) ? (string)$character['ch_id'] : '';
    $remain = reset_counter_remain('character_search', $key, $mode);
    $max    = reset_counter_max('character_search', 5);

    if (!empty($ctx['action_ui']) && $ctx['action_ui'] === 'select') {
        $disabled = ($remain <= 0) ? 'disabled' : '';
        return $html.'<option value="S" '.$disabled.'>탐색 ('.$remain.'/'.$max.')</option>';
    }

    if ($remain <= 0) return $html;

    $wr_id = isset($ctx['list_item']['wr_id']) ? (int)$ctx['list_item']['wr_id'] : 0;
    $id = 'action_'.$wr_id.'_S';
    return $html
        .'<input type="radio" name="action" id="'.$id.'" value="S" />'
        .'<label for="'.$id.'">탐색 ('.$remain.'/'.$max.')&nbsp;&nbsp;&nbsp;&nbsp;</label>';
}, 10);

// 2) 액션 처리
mdl_add_filter('bbs_custom_action', function ($result, $ctx) {
    if (!empty($result['handled'])) return $result;
    if (!isset($result['action']) || $result['action'] !== 'S') return $result;

    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    $w        = isset($ctx['w']) ? $ctx['w'] : '';
    if (function_exists('mdl_board_enabled_for_w')) {
        if (!mdl_board_enabled_for_w($bo_table, 'bbs_action_search', $w)) return $result;
    } else {
        $scope = ($w === 'c' || $w === 'cu') ? 'comment' : 'write';
        if (!mdl_board_enabled($bo_table, 'bbs_action_search', $scope)) return $result;
    }

    // 수정 모드에서 기존 wr_log 가 있으면 덮어쓰지 않음 (이중 보호 — 글루에서도 가드)
    $existing = isset($ctx['existing']) && is_array($ctx['existing']) ? $ctx['existing'] : array();
    if (!empty($existing['wr_log'])) return $result;

    global $g5;
    $character = isset($ctx['character']) ? $ctx['character'] : array();
    if (empty($character['ch_id'])) {
        $result['handled'] = true;
        return $result;
    }

    $mode   = reset_counter_mode('bbs_action_search', 'search_mode', 'minus');
    $key    = isset($character['ch_id']) ? (string)$character['ch_id'] : '';
    $remain = reset_counter_remain('character_search', $key, $mode);
    if ($remain <= 0) {
        $result['handled'] = true;
        return $result;
    }

    $log = '';
    $seed = rand(0, 100);

    $item_result = sql_fetch("
        select it_id, it_name
            from {$g5['item_table']}
            where it_use = 'Y'
              and it_seeker = '1'
              and (it_seeker_per_s <= '{$seed}' and it_seeker_per_e >= '{$seed}')
            order by RAND()
            limit 0, 1
    ");

    if (!empty($item_result['it_id']) && function_exists('insert_inventory')) {
        insert_inventory($character['ch_id'], $item_result['it_id']);
        $log = 'S||S||'.$item_result['it_id'].'||'.$item_result['it_name'].'||';
    } else {
        $log = 'S||F';
    }

    // 카운터 갱신 (mode 에 따라 +1 또는 -1)
    reset_counter_consume('character_search', $character['ch_id'], $mode);

    $result['handled']      = true;
    $result['log']          = $log;
    $result['customer_sql'] = " , wr_log = '".addslashes($log)."' ";
    return $result;
}, 10);

// 3) 로그 렌더 (S = 탐색) — 통합 row 훅 사용
//    $row['wr_log'] = "S||S|F||it_id||it_name||in_id"
//    보드 write/comment 스코프와 무관. 모듈이 전역 ON 이면 과거 로그를 항상 출력.
mdl_add_filter('bbs_view_row_extra', function ($html, $row, $ctx) {
    if (empty($row['wr_log'])) return $html;
    $data_log = explode('||', $row['wr_log']);
    if (!isset($data_log[0]) || $data_log[0] !== 'S') return $html;

    $view = __DIR__.'/views/log_S.php';
    if (!is_file($view)) return $html;

    ob_start();
    include $view;          // 뷰는 $data_log 사용
    return $html.ob_get_clean();
}, 10);

// ---------------------------------------------------------------
// 4) "탐색횟수" 아이템 통합 (bbs_item_search 기능 병합)
//    탐색 카운터를 it_value 만큼 변경 (양수=회복, 음수=차감)
//    → 범용 reset_counter_apply('character_search', ...) 직접 호출
// ---------------------------------------------------------------

// 4-a) 인벤사용 (마이페이지 인벤 → 즉시 사용)
mdl_add_filter('inventory_use_dispatch', function ($result, $ctx) {
    if (!empty($result['handled'])) return $result;

    $in       = isset($ctx['in']) ? $ctx['in'] : array();
    $function = isset($ctx['inven_function']) ? $ctx['inven_function'] : '';
    if ($function !== '탐색횟수') return $result;

    $in_id   = (int)($ctx['in_id'] ?? 0);
    $ch_id   = (int)($in['ch_id'] ?? 0);
    $value   = (int)($in['it_value'] ?? 0);
    $name    = (string)($in['it_name'] ?? '아이템');
    $ever    = !empty($in['it_use_ever']);
    $ret_url = (string)($ctx['return_url'] ?? '');

    $applied = reset_counter_apply('character_search', $ch_id, $value, reset_counter_mode('bbs_action_search', 'search_mode', 'minus'));

    if (function_exists('delete_inventory')) delete_inventory($in_id, $ever ? 1 : 0);
    if (function_exists('mdl_do_action')) mdl_do_action('inventory_use_after', $in_id, $ch_id, '탐색횟수');

    $msg = ($applied > 0)
        ? "『{$name}』 사용 — 탐색 횟수 +{$applied}"
        : (($applied < 0) ? "『{$name}』 사용 — 탐색 횟수 {$applied}" : "『{$name}』 사용 — (변화 없음 · 이미 한도 도달)");

    $result['handled'] = true;
    $result['echo']    = "LOCATIONURL||||" . $ret_url . "&amp;tabs=i&amp;msg=" . urlencode($msg);
    return $result;
}, 10);

// 4-b) 자비사용 (글/댓글 작성 시 use_item)
mdl_add_filter('item_use_pre', function ($result, $ctx) {
    if (!empty($result['handled'])) return $result;
    $it = isset($result['it']) ? $result['it'] : array();
    if (!is_array($it) || ($it['it_type'] ?? '') !== '탐색횟수') return $result;

    $character = isset($ctx['character']) ? $ctx['character'] : array();
    $ch_id     = (int)($character['ch_id'] ?? 0);
    $value     = (int)($it['it_value'] ?? 0);
    $name      = (string)($it['it_name'] ?? '아이템');
    $use_item  = (int)($result['use_item'] ?? 0);

    $applied = reset_counter_apply('character_search', $ch_id, $value, reset_counter_mode('bbs_action_search', 'search_mode', 'minus'));

    if (empty($it['it_use_ever']) && function_exists('delete_inventory') && $use_item > 0) {
        delete_inventory($use_item);
    }

    $sign     = ($applied >= 0) ? "+{$applied}" : (string)$applied;
    $item_log = "D||" . ($it['it_id'] ?? 0) . "||" . $name . "||탐색횟수||" . $value . "||" . $sign . "||";

    // Wave 10+ 단일 컬럼 정책
    $result['handled']      = true;
    $result['item_log']     = $item_log;
    $result['customer_sql'] = " , wr_item = '" . sql_real_escape_string((string)($it['it_id'] ?? '')) . "', wr_item_log = '" . sql_real_escape_string($item_log) . "' ";
    return $result;
}, 10);

// 5) it_type 동적 카테고리 — 탐색횟수 옵션 추가
mdl_add_filter('item_type_options', function ($opts) {
    $opts[] = '탐색횟수';
    return $opts;
});
