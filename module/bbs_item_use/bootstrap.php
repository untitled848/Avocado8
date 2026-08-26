<?php
if (!defined('_GNUBOARD_')) exit;

// 현재 모듈 URL (view 파일에서 이미지/JS/CSS 경로로 사용). 모든 bootstrap 공통 규약.
$module_url = G5_MDL_URL.'/bbs_item_use';

/**
 * inventory 모듈 — 게시판 아이템 사용 게이트
 *
 *   - 게이트: 핸들러 첫 줄에서 mdl_board_enabled() 체크 → 비활성 시 즉시 return
 *   - lazy require: 활성 게시판일 때만 module/bbs_item_use/bbs/_common.php 로드
 *   - item_log_render 필터는 보드/스킨 의존이 약하므로 항상 등록 (출력 단계라 안전)
 */

// ---------- 글쓰기/수정 시 use_item / gift_item 처리 — 표준 write_after 훅 사용 ----------
mdl_add_action('write_after', function ($bo_table, $wr_id, $w) {
    if (!mdl_board_enabled($bo_table, 'bbs_item_use', 'write')) return;

    $post = $_POST;
    $item_mode = isset($post['item_mode']) ? (string)$post['item_mode'] : 'use';
    $use_item = isset($post['use_item']) ? (int)$post['use_item'] : 0;
    $gift_item = isset($post['gift_item']) ? (int)$post['gift_item'] : 0;
    $gift_to = isset($post['gift_to_ch_id']) ? (int)$post['gift_to_ch_id'] : 0;

    if ($item_mode === 'gift') {
        if (!$gift_item || !$gift_to) return;
    } else {
        if (!$use_item) return;
    }

    require_once __DIR__ . '/bbs/_common.php';

    global $g5;
    $write_table = item_use_resolve_write_table($bo_table);
    if ($write_table === '') return;

    // 수정 모드(w='u') 이면서 기존 wr_item_log 가 있으면 덮어쓰지 않음
    if ($w === 'u') {
        $existing = sql_fetch("select wr_item_log from {$write_table} where wr_id = '".(int)$wr_id."'");
        if (!empty($existing['wr_item_log'])) return;
    }

    $character = isset($GLOBALS['character']) && is_array($GLOBALS['character']) ? $GLOBALS['character'] : array();

    $ctx = array(
        'bo_table' => $bo_table,
        'wr_id'    => $wr_id,
        'w'        => $w,
        'scope'    => 'write',
        'pool'     => 'item',
        'post'     => $post,
    );

    if ($item_mode === 'gift') {
        $r = item_gift_process($gift_item, $gift_to, $character, $ctx);
    } else {
        $r = item_use_process($use_item, $character, $ctx);
    }
    if (!$r['handled']) return;

    $set = ltrim($r['customer_sql'], " ,\t\r\n");
    if ($set === '') return;

    $sql = "update {$write_table} set {$set} where wr_id = '".(int)$wr_id."'";
    sql_query($sql, false);
}, 10);

// ---------- 코멘트 등록/수정 시 use_item / gift_item 처리 — 표준 comment_after 훅 사용 ----------
// mmb 댓글은 사용만. 선물(item_mode=gift)은 타임라인 댓글만.
mdl_add_action('comment_after', function ($bo_table, $comment_id, $wr_id) {
    if (function_exists('mdl_board_enabled_for_w')) {
        if (!mdl_board_enabled_for_w($bo_table, 'bbs_item_use', 'c')) return;
    } elseif (!mdl_board_enabled($bo_table, 'bbs_item_use', 'comment')) {
        return;
    }
    require_once __DIR__ . '/bbs/_common.php';

    $post = $_POST;
    $item_mode = isset($post['item_mode']) ? (string)$post['item_mode'] : 'use';
    $use_item = isset($post['use_item']) ? (int)$post['use_item'] : 0;
    $gift_item = isset($post['gift_item']) ? (int)$post['gift_item'] : 0;
    $gift_to = isset($post['gift_to_ch_id']) ? (int)$post['gift_to_ch_id'] : 0;

    if ($item_mode === 'gift') {
        if (!item_use_is_timeline($bo_table)) return;
        if (!$gift_item || !$gift_to) return;
    } else {
        if (!$use_item) return;
    }

    // 댓글 수정 모드(w='cu') 이면서 기존 wr_item_log 가 있으면 덮어쓰지 않음
    $w = isset($post['w']) ? $post['w'] : '';
    global $g5;
    $write_table = item_use_resolve_write_table($bo_table);
    if ($write_table === '') return;

    if ($w === 'cu') {
        $existing = sql_fetch("select wr_item_log from {$write_table} where wr_id = '".(int)$comment_id."'");
        if (!empty($existing['wr_item_log'])) return;
    }

    $character = isset($GLOBALS['character']) && is_array($GLOBALS['character']) ? $GLOBALS['character'] : array();

    $ctx = array(
        'bo_table'   => $bo_table,
        'wr_id'      => $wr_id,
        'comment_id' => $comment_id,
        'w'          => $w,
        'scope'      => 'comment',
        'pool'       => 'item',
        'post'       => $post,
    );

    if ($item_mode === 'gift') {
        $r = item_gift_process($gift_item, $gift_to, $character, $ctx);
    } else {
        $r = item_use_process($use_item, $character, $ctx);
    }
    if (!$r['handled']) return;

    $set = ltrim($r['customer_sql'], " ,\t\r\n");
    if ($set === '') return;

    $sql = "update {$write_table} set {$set} where wr_id = '".(int)$comment_id."'";
    sql_query($sql, false);
}, 10);

// ---------- 글쓰기 폼: 사용 가능 아이템 select 주입 ----------
mdl_add_filter('bbs_write_form_after_action', function ($html, $ctx) {
    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    if (!mdl_board_enabled($bo_table, 'bbs_item_use', 'write')) return $html;

    $character = array();
    if (isset($ctx['character']) && is_array($ctx['character'])) {
        $character = $ctx['character'];
    } elseif (isset($GLOBALS['character']) && is_array($GLOBALS['character'])) {
        $character = $GLOBALS['character'];
    }
    if (empty($character['ch_id']) || (isset($character['ch_state']) && $character['ch_state'] !== '승인')) {
        return $html;
    }
    // 이미 사용 로그가 있는 글 수정이면 select 안 띄움
    if (isset($ctx['write']['wr_item_log']) && $ctx['write']['wr_item_log'] !== '') {
        return $html;
    }

    require_once __DIR__ . '/bbs/_common.php';
    return $html . item_form_select($character, $ctx);
}, 10);

// ---------- 댓글 폼: mmb 는 사용 드롭다운만, 타임라인은 사용/선물 ----------
mdl_add_filter('bbs_comment_form_after_action', function ($html, $ctx) {
    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    if (!mdl_board_enabled($bo_table, 'bbs_item_use', 'comment')) return $html;

    $character = array();
    if (isset($ctx['character']) && is_array($ctx['character'])) {
        $character = $ctx['character'];
    } elseif (isset($GLOBALS['character']) && is_array($GLOBALS['character'])) {
        $character = $GLOBALS['character'];
    }
    if (empty($character['ch_id']) || (isset($character['ch_state']) && $character['ch_state'] !== '승인')) {
        return $html;
    }

    require_once __DIR__ . '/bbs/_common.php';
    return $html . item_form_select_comment($character, $ctx);
}, 10);

// ---------- 자산 푸시 + 사용 가능 아이템 목록 글로벌 노출 ----------
//   write/comment 둘 중 하나라도 켜져 있으면 자산 로드 (어느 페이지에서 쓸지 모름)
//   bbs_enqueue_assets 는 bbs/_head.php → board_head.php 경유로 write.php / view.php 등 모든
//   게시판 페이지에서 호출되므로, $GLOBALS['mmb_item'] 을 여기서 채워두면 어느 스킨이든
//   동일한 데이터를 참조할 수 있다 (mmb 스킨은 더 이상 자체 쿼리를 돌릴 필요 없음).
mdl_add_action('bbs_enqueue_assets', function ($bo_table) {
    if (!mdl_board_enabled($bo_table, 'bbs_item_use', 'write')
     && !mdl_board_enabled($bo_table, 'bbs_item_use', 'comment')) return;
    require_once __DIR__ . '/bbs/assets.php';

    // 사용 가능 아이템 글로벌 노출 (스킨 호환용)
    require_once __DIR__ . '/bbs/_common.php';
    $character = isset($GLOBALS['character']) && is_array($GLOBALS['character']) ? $GLOBALS['character'] : array();
    $GLOBALS['mmb_item'] = function_exists('inventory_get_usable_items')
        ? inventory_get_usable_items($character)
        : array();
}, 10, 1);

// ---------- 아이템 로그 렌더 ----------
//   필터는 게시판 종류와 무관하게 호출되며, 게이트는 호출자(스킨)가 책임진다.
//   여기서는 '기본 템플릿이 있는데도 호출자가 모를 때' 안전한 fallback을 제공하지 않고,
//   호출자가 명시적으로 item_log_render() 함수를 호출하도록 한다.
//   (즉, 이 모듈은 별도 필터 등록을 하지 않음 — 호출자가 함수 직접 호출)

/* ============================================================================
 * [모듈로 아이템 추가 시 사용 가능한 hook 지점 — 가이드]
 *
 *   1) item_use_pre  (filter, $payload, $ctx)
 *      - 코어 처리(인벤토리 차감/뽑기/D|S|F 로그 생성) 전에 전체 가로채기.
 *      - $payload : ['handled'=>bool, 'customer_sql'=>string, 'item_log'=>string,
 *                    'use_item'=>int, 'it'=>row]
 *      - return $payload (handled=true 로 바꾸면 코어 스킵, item_log/customer_sql 사용)
 *      - 사용 예: 특수 아이템 (예: 특정 it_id 만 별도 로직 — 부활/이벤트 등)
 *
 *   2) item_use_core (filter, $payload, $ctx)
 *      - 코어가 만든 item_log/customer_sql 을 사후 가공.
 *      - $payload : ['it_type','it','customer_sql','item_log','use_item']
 *      - return $payload (item_log 또는 customer_sql 만 수정)
 *      - 사용 예: 일반 아이템 사용 결과에 wr_extra='...' 같은 SQL set 추가
 *
 *   3) item_use_post (action, $ctx, $it, $item_log, $use_item)
 *      - 사용 처리가 모두 끝난 뒤 사후 알림 (DB 적재 등 외부 효과)
 *      - 사용 예: 통계 카운트, 알림 큐 적재
 *
 *   4) item_log_render (filter, $html, $item_log_arr)
 *      - wr_item_log 가 화면에 출력되는 시점에 가로채기.
 *      - 비어있지 않은 문자열 반환 시 기본 템플릿(template/log.item.tpl.php) 대체.
 *      - 사용 예: 새 타입('B' 등) 추가 시 자체 HTML 출력
 *
 * 등록 예 (다른 모듈 bootstrap.php 에서):
 *   mdl_add_filter('item_use_pre', function($p, $ctx){
 *       if (!empty($p['it']) && $p['it']['it_id']==='magic_revive') {
 *           $p['handled']=true; $p['item_log']="B||{$p['it']['it_id']}||부활";
 *           $p['customer_sql']=" , wr_item='{$p['it']['it_id']}', wr_item_log='{$p['item_log']}'";
 *       }
 *       return $p;
 *   }, 10);
 *
 *   mdl_add_filter('item_log_render', function($html, $log){
 *       if ($log[0]==='B') return '<div class="log-action-box revive">'.$log[2].'</div>';
 *       return $html;
 *   }, 10);
 * ============================================================================ */
