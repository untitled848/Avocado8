<?php
if (!defined('_GNUBOARD_')) exit;

require_once __DIR__.'/lib.php';
if (!function_exists('mdl_db_column_exists') && defined('G5_PATH') && is_file(G5_PATH.'/module/_db.php')) {
    include_once G5_PATH.'/module/_db.php';
}

/**
 * bbs_action_map — 지역 이동 (AvocadoEdition.MapManager 모듈화)
 *
 *   action = MAP / MAP_MON
 *   wr_log = MAP||… / MAP_MON||…
 *   scopes = write 만. 원글 위치이동·댓글 몬스터 공략 모두 write 게이트.
 */

bbs_action_map_drop_comment_scope();

$module_url = G5_MDL_URL.'/bbs_action_map';

// 자산 — 로그 CSS는 스코프 OFF 여도 유지. 맵 JS 는 액션 UI 용.
mdl_add_action('bbs_enqueue_assets', function ($bo_table) use ($module_url) {
    add_stylesheet('<link rel="stylesheet" href="'.htmlspecialchars($module_url.'/css/map.css', ENT_QUOTES).'">', 100);
    if (bbs_action_map_board_enabled($bo_table) && is_file(G5_MDL_PATH.'/bbs_action_map/js/map.js')) {
        add_javascript('<script src="'.htmlspecialchars($module_url.'/js/map.js', ENT_QUOTES).'"></script>', 100);
    }
}, 10, 1);

// 목록 상단 전체 맵
mdl_add_filter('list_top', function ($html, $bo_table) {
    if (!bbs_action_map_board_enabled($bo_table)) return $html;

    $character = (isset($GLOBALS['character']) && is_array($GLOBALS['character'])) ? $GLOBALS['character'] : array();
    return $html.bbs_action_map_render_overview($character);
}, 10);

// 글쓰기 옵션
mdl_add_filter('bbs_write_form_action_options', function ($html, $ctx) {
    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    if (!bbs_action_map_board_enabled($bo_table)) return $html;
    if (!empty($ctx['write']['wr_log'])) return $html;

    global $character;
    $mode   = reset_counter_mode('bbs_action_map', 'explore_mode', 'minus');
    $key    = isset($character['ch_id']) ? (string)$character['ch_id'] : '';
    $remain = function_exists('reset_counter_remain') ? reset_counter_remain('character_map_explore', $key, $mode, array('unlimited_when_no_max' => true)) : 1;
    $max    = function_exists('reset_counter_max') ? reset_counter_max('character_map_explore', 0) : 0;
    $disabled = ($max > 0 && $remain <= 0) ? 'disabled' : '';
    $label = ($max > 0) ? '위치이동 ('.$remain.'/'.$max.')' : '위치이동';

    return $html.'<option value="MAP" '.$disabled.'>'.$label.'</option>';
}, 10);

// 글쓰기 패널
mdl_add_filter('bbs_write_form_action_panels', function ($html, $ctx) {
    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    if (!bbs_action_map_board_enabled($bo_table)) return $html;
    if (!empty($ctx['write']['wr_log'])) return $html;

    $character = array();
    if (isset($ctx['character']) && is_array($ctx['character'])) $character = $ctx['character'];
    elseif (isset($GLOBALS['character']) && is_array($GLOBALS['character'])) $character = $GLOBALS['character'];
    if (empty($character['ch_id'])) return $html;

    return $html.bbs_action_map_render_action_panel($character, $ctx);
}, 10);

// 댓글 — 몬스터 공략 (원글에 몬스터 진행 중일 때). 게이트는 write.
mdl_add_filter('bbs_comment_form_action_options', function ($html, $ctx) {
    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    if (!bbs_action_map_board_enabled($bo_table)) return $html;

    $parent = bbs_action_map_comment_parent($ctx);
    if (empty($parent['wr_mon_state']) || $parent['wr_mon_state'] !== 'S') return $html;

    $character = array();
    if (isset($ctx['character']) && is_array($ctx['character'])) $character = $ctx['character'];
    elseif (isset($GLOBALS['character']) && is_array($GLOBALS['character'])) $character = $GLOBALS['character'];

    $post_ma = isset($parent['ma_id']) ? (int)$parent['ma_id'] : 0;
    $my_ma = bbs_action_map_char_ma_id($character);
    if ($post_ma > 0 && $my_ma > 0 && $post_ma !== $my_ma) return $html;

    if (!empty($ctx['action_ui']) && $ctx['action_ui'] === 'select') {
        return $html.'<option value="MAP_MON">몬스터 공략</option>';
    }

    $wr_id = isset($parent['wr_id']) ? (int)$parent['wr_id'] : 0;
    $id = 'action_'.$wr_id.'_MAP_MON';
    return $html
        .'<input type="radio" name="action" id="'.$id.'" value="MAP_MON" />'
        .'<label for="'.$id.'">몬스터 공략&nbsp;&nbsp;&nbsp;&nbsp;</label>';
}, 10);

// 액션 처리
mdl_add_filter('bbs_custom_action', function ($result, $ctx) {
    if (!empty($result['handled'])) return $result;
    $action = isset($result['action']) ? $result['action'] : '';
    if ($action !== 'MAP' && $action !== 'MAP_MON') return $result;

    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    $w = isset($ctx['w']) ? $ctx['w'] : '';
    if (!bbs_action_map_board_enabled($bo_table)) return $result;

    $existing = isset($ctx['existing']) && is_array($ctx['existing']) ? $ctx['existing'] : array();
    if (!empty($existing['wr_log'])) return $result;

    $character = isset($ctx['character']) && is_array($ctx['character']) ? $ctx['character'] : array();
    $member    = isset($ctx['member']) && is_array($ctx['member']) ? $ctx['member'] : array();
    $post      = isset($ctx['post']) && is_array($ctx['post']) ? $ctx['post'] : array();
    $is_comment = ($w === 'c' || $w === 'cu');

    if ($action === 'MAP') {
        if ($is_comment) return $result;
        if (empty($character['ch_id'])) {
            bbs_action_map_fail('캐릭터 정보가 없습니다.');
        }
        if (function_exists('reset_counter_remain')) {
            $mode = reset_counter_mode('bbs_action_map', 'explore_mode', 'minus');
            $key  = (string)$character['ch_id'];
            $max  = reset_counter_max('character_map_explore', 0);
            $remain = reset_counter_remain('character_map_explore', $key, $mode, array('unlimited_when_no_max' => true));
            if ($max > 0 && $remain <= 0) {
                bbs_action_map_fail('탐험 횟수가 없습니다.');
            }
        }

        $re_ma_id = isset($post['re_ma_id']) ? (int)$post['re_ma_id'] : 0;
        if ($re_ma_id <= 0) {
            bbs_action_map_fail('이동할 지역을 선택하세요.');
        }

        $write_table = bbs_action_map_ctx_write_table($ctx);
        if ($write_table) bbs_action_map_ensure_write_columns($write_table);

        $r = bbs_action_map_process_move($re_ma_id, $character, $member);
        if (!$r['ok']) {
            bbs_action_map_fail($r['message'] !== '' ? $r['message'] : '지역 이동에 실패했습니다.');
        }

        $result['handled'] = true;
        $result['log'] = $r['log'];
        $result['customer_sql'] = $r['customer_sql'];
        return $result;
    }

    // MAP_MON — 댓글에서 원글 몬스터 공격
    if (!$is_comment) return $result;
    $write_table = bbs_action_map_ctx_write_table($ctx);
    $parent_id = isset($ctx['wr_id']) ? (int)$ctx['wr_id'] : 0;
    $r = bbs_action_map_process_mon($write_table, $parent_id, $post, $character);
    if (!$r['ok']) {
        bbs_action_map_fail($r['message'] !== '' ? $r['message'] : '몬스터 공략에 실패했습니다.');
    }
    $result['handled'] = true;
    $result['log'] = $r['log'];
    $result['customer_sql'] = $r['customer_sql'];
    return $result;
}, 10);

mdl_add_action('write_after', function ($bo_table, $wr_id, $w) {
    if ($w === 'c' || $w === 'cu') return;
    bbs_action_map_flush_pending_move();
}, 10);

mdl_add_action('comment_after', function ($bo_table, $comment_id, $wr_id) {
    bbs_action_map_flush_pending_mon();
}, 10);

// 로그 렌더
mdl_add_filter('bbs_view_row_extra', function ($html, $row, $ctx) {
    if (empty($row['wr_log'])) return $html;
    $data_log = explode('||', $row['wr_log']);
    $type = isset($data_log[0]) ? $data_log[0] : '';
    if ($type === 'MAP') {
        $view = __DIR__.'/views/log_MAP.php';
        if (!is_file($view)) return $html;
        ob_start();
        include $view;
        return $html.ob_get_clean();
    }
    if ($type === 'MAP_MON') {
        $view = __DIR__.'/views/log_MAP_MON.php';
        if (!is_file($view)) return $html;
        ob_start();
        include $view;
        return $html.ob_get_clean();
    }
    return $html;
}, 10);

// 캐릭터 생성 시 카운터 등록
mdl_add_action('character_create_after', function ($ch_id) {
    if (!$ch_id) return;
    if (!function_exists('reset_value_set')) return;
    if (!function_exists('reset_setting_get') || !reset_setting_get('character_map_explore')) return;
    $mode = reset_counter_mode('bbs_action_map', 'explore_mode', 'minus');
    reset_value_set('character_map_explore', $ch_id, reset_counter_initial_value('character_map_explore', $mode));
}, 10);
