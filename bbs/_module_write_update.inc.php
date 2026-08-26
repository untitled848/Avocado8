<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 모듈 훅 — 글쓰기/댓글 update 시 공용 진입점
 *
 * 호출측 사전 조건 (스코프 변수):
 *   $bo_table, $w, $write_table, $member, $character (있으면)
 *   $wr_id, $wr_num, $temp_wr_id (글:wr_id, 댓글:comment_id), $comment_id (댓글일 때)
 *   $customer_sql, $memo_custom_sql (모두 string, 호출측이 미리 초기화)
 *
 * 호출 후 변수:
 *   $customer_sql, $memo_custom_sql, $log 갱신 (모듈이 처리한 경우)
 *
 * 내부에서 호출되는 훅:
 *   - bbs_write_update_before  (action)
 *   - bbs_custom_action        (filter, $w!='cu' 일 때만)
 *   - bbs_customer_sql         (filter)
 *   - bbs_memo_custom_sql      (filter)
 *   - bbs_write_update_after   (action)
 */

if (!isset($customer_sql))    $customer_sql    = '';
if (!isset($memo_custom_sql)) $memo_custom_sql = '';
if (!isset($log))             $log             = '';

// 수정 모드(w='u'/'cu') 일 때 기존 행을 미리 조회 — 모듈이 wr_log/wr_dice 등을
//   덮어쓰지 않도록 보호. 신규(w='') 또는 c 일 때는 기존 행이 없으므로 skip.
$bbs_existing_row = array();
if (isset($w) && ($w === 'u' || $w === 'cu')
    && isset($write_table) && $write_table !== ''
    && isset($temp_wr_id)  && $temp_wr_id) {
    $_eid = (int)$temp_wr_id;
    $bbs_existing_row = sql_fetch("select * from {$write_table} where wr_id = '{$_eid}'");
    if (!is_array($bbs_existing_row)) $bbs_existing_row = array();
}

$bbs_upd_ctx = array(
    'bo_table'    => isset($bo_table)    ? $bo_table    : '',
    'w'           => isset($w)           ? $w           : '',
    'wr_id'       => isset($wr_id)       ? $wr_id       : 0,
    'wr_num'      => isset($wr_num)      ? $wr_num      : 0,
    'temp_wr_id'  => isset($temp_wr_id)  ? $temp_wr_id  : 0,
    'comment_id'  => isset($comment_id)  ? $comment_id  : 0,
    'write_table' => isset($write_table) ? $write_table : '',
    'character'   => isset($character) && is_array($character) ? $character : array(),
    'member'      => isset($member)    && is_array($member)    ? $member    : array(),
    'post'        => isset($_POST) ? $_POST : array(),
    'existing'    => $bbs_existing_row,   // 모듈은 이걸 보고 덮어쓰기 여부 결정
);

if (function_exists('mdl_do_action')) {
    mdl_do_action('bbs_write_update_before', $bbs_upd_ctx);
}

// 액션 처리 — 글 작성/수정 및 댓글 작성/수정 모두에서 동작
// 각 모듈은 $ctx['existing'] 에서 자기 컬럼 값을 검사해 덮어쓰기 방지.
// 중앙 가드: 수정 모드에 "action POST 변수" 가 있는데 이미 wr_log 가
//   차 있으면 action 자체를 비움 (동일 wr_log 경쟁 재실행 차단).
//   → wr_dice 같이 다른 POST 변수로 트리거되는 모듈은 그대로 통과.
$_post_action = isset($_POST['action']) ? trim($_POST['action']) : '';
if ($_post_action !== ''
    && isset($w) && ($w === 'u' || $w === 'cu')
    && !empty($bbs_existing_row['wr_log'])) {
    $_post_action = '';
}
if (function_exists('mdl_apply_filters')) {
    $_r = mdl_apply_filters('bbs_custom_action', array(
        'handled'      => false,
        'customer_sql' => '',
        'log'          => '',
        'action'       => $_post_action,
    ), $bbs_upd_ctx);
    if (is_array($_r) && !empty($_r['handled'])) {
        if (!empty($_r['customer_sql'])) $customer_sql .= $_r['customer_sql'];
        if (!empty($_r['log']))          $log = $_r['log'];
    }
}

// 최종 SQL 변형 필터
if (function_exists('mdl_apply_filters')) {
    $customer_sql    = mdl_apply_filters('bbs_customer_sql',    $customer_sql,    $bbs_upd_ctx);
    $memo_custom_sql = mdl_apply_filters('bbs_memo_custom_sql', $memo_custom_sql, $bbs_upd_ctx);
}

if (function_exists('mdl_do_action')) {
    mdl_do_action('bbs_write_update_after', $bbs_upd_ctx);
}
