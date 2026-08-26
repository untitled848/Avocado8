<?php
if (!defined('_GNUBOARD_')) exit;

// 현재 모듈 URL (view 파일에서 이미지/JS/CSS 경로로 사용). 모든 bootstrap 공통 규약.
$module_url = G5_MDL_URL.'/bbs_action_combine';

/**
 * mmb 조합(H) 액션 모듈
 *
 * - write.skin.php : action select 에 <option value="H">조합</option> 추가
 *                    + 재료 선택 패널 (#action_H) 추가
 * - write_update.inc.php : action='H' 처리 (레시피 매칭 → 결과 아이템 인벤토리 추가)
 */

// 1) 폼 옵션
mdl_add_filter('bbs_write_form_action_options', function ($html, $ctx) {
    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    if (!mdl_board_enabled($bo_table, 'bbs_action_combine', 'write')) return $html;

    // 수정 모드에서 이미 wr_log 가 차 있으면 액션 옵션 숨김
    if (!empty($ctx['write']['wr_log'])) return $html;

    // 일일 횟수 초과 → disabled
    $character = isset($GLOBALS['character']) && is_array($GLOBALS['character']) ? $GLOBALS['character'] : array();
    $mode   = reset_counter_mode('bbs_action_combine', 'combine_mode', 'minus');
    $key    = isset($character['ch_id']) ? (string)$character['ch_id'] : '';
    $remain = reset_counter_remain('character_combine', $key, $mode, array('unlimited_when_no_max' => true));
    $max    = reset_counter_max('character_combine', 0);
    if ($remain <= 0) {
        return $html.'<option value="H" disabled>조합 ('.$remain.'/'.$max.')</option>';
    }

    return $html.'<option value="H">조합 ('.$remain.'/'.$max.')</option>';
}, 10);

/**
 * 재료 선택 패널 HTML 생성 (write/comment 공통).
 *
 * @param int|string $scope_id  패널/입력 ID 의 스코프 식별자.
 *                              - write 폼: '' (빈 문자열) → id="action_H", id="make_1"~"make_3"
 *                              - comment 폼: 부모/대상 wr_id → id="action_<wr_id>_H", id="make_<wr_id>_1"~"make_<wr_id>_3"
 *                              comment 의 경우 한 페이지에 여러 폼이 공존하므로 반드시 고유값.
 *                              POST name 은 항상 make_1/2/3 (백엔드가 그 키로 읽음).
 */
function bbs_action_combine_render_panel($scope_id) {
    global $g5;
    if (!isset($g5['inventory_table']) || !isset($g5['item_table'])) return '';

    $character = isset($GLOBALS['character']) && is_array($GLOBALS['character']) ? $GLOBALS['character'] : array();
    if (empty($character['ch_id'])) return '';

    $ch_id_safe = addslashes($character['ch_id']);
    $sql = " select inven.in_id, it.it_name
               from {$g5['inventory_table']} inven,
                    {$g5['item_table']} it
              where inven.ch_id = '{$ch_id_safe}'
                and it.it_use_recepi = 1
                and inven.it_id = it.it_id ";
    $rs = sql_query($sql);

    $options = '<option value="">재료 선택</option>';
    if ($rs) {
        while ($row = sql_fetch_array($rs)) {
            $options .= '<option value="'.(int)$row['in_id'].'">'.htmlspecialchars($row['it_name'], ENT_QUOTES).'</option>';
        }
    }

    $sid     = ($scope_id === '' || $scope_id === null) ? '' : '_'.preg_replace('/[^a-zA-Z0-9_]/', '', (string)$scope_id);
    $panel_id = 'action'.$sid.'_H';

    $out  = '<div class="comment-data" id="'.$panel_id.'">';
    for ($mi = 1; $mi <= 3; $mi++) {
        // POST name 은 고정 (make_1/2/3) — 백엔드 처리부와 매칭. ID 만 고유화.
        $iid = 'make'.$sid.'_'.$mi;
        $out .= '<dl><dt>ITEM '.$mi.'</dt><dd>';
        $out .= '<select name="make_'.$mi.'" id="'.$iid.'" class="make-imtem">'.$options.'</select>';
        $out .= '</dd></dl>';
    }
    $out .= '</div>';
    return $out;
}

// 2) 재료 선택 패널 — write 폼 (단일 폼이라 scope_id 기본 빈값, 답글은 ctx.scope_id)
mdl_add_filter('bbs_write_form_action_panels', function ($html, $ctx) {
    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    if (!mdl_board_enabled($bo_table, 'bbs_action_combine', 'write')) return $html;
    if (!empty($ctx['write']['wr_log'])) return $html;
    $scope_id = isset($ctx['scope_id']) ? $ctx['scope_id'] : '';
    return $html . bbs_action_combine_render_panel($scope_id);
}, 10);

// 2-b) 댓글 폼 옵션 — 기본 라디오, ctx.action_ui === 'select' 이면 <option>
mdl_add_filter('bbs_comment_form_action_options', function ($html, $ctx) {
    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    if (!mdl_board_enabled($bo_table, 'bbs_action_combine', 'comment')) return $html;

    $character = isset($GLOBALS['character']) && is_array($GLOBALS['character']) ? $GLOBALS['character'] : array();
    $mode   = reset_counter_mode('bbs_action_combine', 'combine_mode', 'minus');
    $key    = isset($character['ch_id']) ? (string)$character['ch_id'] : '';
    $remain = reset_counter_remain('character_combine', $key, $mode, array('unlimited_when_no_max' => true));
    $max    = reset_counter_max('character_combine', 0);

    if (!empty($ctx['action_ui']) && $ctx['action_ui'] === 'select') {
        if ($remain <= 0) {
            return $html.'<option value="H" disabled>조합 ('.$remain.'/'.$max.')</option>';
        }
        return $html.'<option value="H">조합 ('.$remain.'/'.$max.')</option>';
    }

    // 스코프 ID: mmb(인라인 폼) 는 list_item.wr_id, basic(단일 폼) 는 ctx.wr_id (부모 wr_id)
    $scope_id = 0;
    if (isset($ctx['list_item']['wr_id'])) $scope_id = (int)$ctx['list_item']['wr_id'];
    else if (isset($ctx['wr_id']))         $scope_id = (int)$ctx['wr_id'];

    $id = 'action_'.$scope_id.'_H';

    // 일일 횟수 초과 → disabled
    if ($remain <= 0) {
        return $html
            .'<input type="radio" name="action" id="'.$id.'" value="H" disabled />'
            .'<label for="'.$id.'" style="opacity:.5;" title="오늘 '.$max.'회 소진">조합&nbsp;&nbsp;&nbsp;&nbsp;</label>';
    }

    return $html
        .'<input type="radio" name="action" id="'.$id.'" value="H" />'
        .'<label for="'.$id.'">조합&nbsp;&nbsp;&nbsp;&nbsp;</label>';
}, 10);

// 2-c) 댓글 폼 패널 — </form> 직전에 출력 (bbs_comment_form_extra)
mdl_add_filter('bbs_comment_form_extra', function ($html, $ctx) {
    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    if (!mdl_board_enabled($bo_table, 'bbs_action_combine', 'comment')) return $html;

    $scope_id = 0;
    if (isset($ctx['list_item']['wr_id'])) $scope_id = (int)$ctx['list_item']['wr_id'];
    else if (isset($ctx['wr_id']))         $scope_id = (int)$ctx['wr_id'];

    return $html . bbs_action_combine_render_panel($scope_id);
}, 10);

// 2-d) 자산 푸시 — 토글/중복방지 JS. 게시판이 활성된 페이지에서만 로드
mdl_add_action('bbs_enqueue_assets', function ($bo_table) use ($module_url) {
    if (!mdl_board_enabled($bo_table, 'bbs_action_combine')) return;
    add_javascript('<script src="'.htmlspecialchars($module_url, ENT_QUOTES).'/js/combine.js"></script>', 100);
}, 10, 1);

// 3) 액션 처리
mdl_add_filter('bbs_custom_action', function ($result, $ctx) {
    if (!empty($result['handled'])) return $result;
    if (!isset($result['action']) || $result['action'] !== 'H') return $result;

    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    if (!mdl_board_enabled($bo_table, 'bbs_action_combine')) return $result;

    // 수정 모드에서 기존 wr_log 가 있으면 덮어쓰지 않음 (이중 보호 — 글루에서도 가드)
    $existing = isset($ctx['existing']) && is_array($ctx['existing']) ? $ctx['existing'] : array();
    if (!empty($existing['wr_log'])) return $result;

    global $g5;
    $character = isset($ctx['character']) ? $ctx['character'] : array();
    if (empty($character['ch_id'])) {
        $result['handled'] = true;
        return $result;
    }
    if (!function_exists('get_inventory_item') || !function_exists('insert_inventory') || !function_exists('delete_inventory') || !function_exists('get_item')) {
        $result['handled'] = true;
        return $result;
    }

    // 일일 횟수 가드
    $mode   = reset_counter_mode('bbs_action_combine', 'combine_mode', 'minus');
    $key    = isset($character['ch_id']) ? (string)$character['ch_id'] : '';
    $remain = reset_counter_remain('character_combine', $key, $mode, array('unlimited_when_no_max' => true));
    if ($remain <= 0) {
        $result['handled']  = true;
        $result['error']    = '오늘의 조합 횟수를 모두 사용했습니다.';
        return $result;
    }

    $post = isset($ctx['post']) ? $ctx['post'] : array();
    $make_1 = get_inventory_item(isset($post['make_1']) ? $post['make_1'] : 0);
    $make_2 = get_inventory_item(isset($post['make_2']) ? $post['make_2'] : 0);
    $make_3 = get_inventory_item(isset($post['make_3']) ? $post['make_3'] : 0);

    $re_item = array(
        isset($make_1['it_id']) ? $make_1['it_id'] : '',
        isset($make_2['it_id']) ? $make_2['it_id'] : '',
        isset($make_3['it_id']) ? $make_3['it_id'] : '',
    );
    sort($re_item);
    $re_item_order = implode('||', $re_item);

    $action = 'H';
    $log = '';

    $re_item_order_safe = addslashes($re_item_order);
    $re = sql_fetch("select it_id from {$g5['item_table']}_recepi where re_item_order = '{$re_item_order_safe}' and re_use = '1'");

    if (empty($re['it_id'])) {
        $log = $action.'||F||NON||NON||'.$re_item_order;
    } else {
        $item = get_item($re['it_id']);
        insert_inventory($character['ch_id'], $item['it_id'], $item);
        $log = $action.'||S||'.$re['it_id'].'||'.$item['it_name'].'||||'.$re_item_order;
    }

    if (!empty($make_1['in_id']) && empty($make_1['it_use_ever'])) delete_inventory($make_1['in_id']);
    if (!empty($make_2['in_id']) && empty($make_2['it_use_ever'])) delete_inventory($make_2['in_id']);
    if (!empty($make_3['in_id']) && empty($make_3['it_use_ever'])) delete_inventory($make_3['in_id']);

    // 일일 조합 횟수 소비
    reset_counter_consume('character_combine', $character['ch_id'], $mode);

    $result['handled']      = true;
    $result['log']          = $log;
    $result['customer_sql'] = " , wr_log = '".addslashes($log)."' ";
    return $result;
}, 10);

// 4) 로그 렌더 (H = 조합) — 통합 row 훅. 보드 스코프와 무관 (전역 ON 이면 항상).
mdl_add_filter('bbs_view_row_extra', function ($html, $row, $ctx) {
    if (empty($row['wr_log'])) return $html;
    $data_log = explode('||', $row['wr_log']);
    if (!isset($data_log[0]) || $data_log[0] !== 'H') return $html;

    $view = __DIR__.'/views/log_H.php';
    if (!is_file($view)) return $html;

    ob_start();
    include $view;          // 뷰는 $data_log 사용
    return $html.ob_get_clean();
}, 10);

// ---------------------------------------------------------------
// 캐릭터 신규 생성 시 — reset_manager 자동 등록
//   횟수 로직은 범용 reset_counter_* (extend/00_reset.lib.php) 사용
// ---------------------------------------------------------------
mdl_add_action('character_create_after', function ($ch_id) {
    if (!$ch_id) return;
    if (!function_exists('reset_value_set') || !function_exists('reset_setting_get')) return;
    if (!reset_setting_get('character_combine')) return;
    $mode = reset_counter_mode('bbs_action_combine', 'combine_mode', 'minus');
    reset_value_set('character_combine', $ch_id, reset_counter_initial_value('character_combine', $mode));
}, 10);

// "조합횟수" 아이템 통합
require_once __DIR__ . '/_combine_item.inc.php';