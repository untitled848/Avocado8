<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * mmb 댓글 쓰기 폼 — 컨트롤러 (PHP 전용)
 *
 * - 댓글 폼 컨텍스트 ($mmb_comment_form_ctx) 구성
 * - bbs_comment_form_before 액션 발사
 *
 * use_item select 등 게시판 공유 기능은 inventory 모듈이 bbs_comment_form_after_action
 * 필터에 주입한다 (모듈 비활성 시 자동 미노출).
 *
 * 호출 환경: list.log.skin.php / view 등에서 list 루프 안에 include 됨.
 *   $list_item 은 호출측에서 set 되어 있어야 함 (per-row).
 *
 * PHP 8: 미정의 변수/어레이 키 접근은 Warning. error_reporting 으로 제어.
 */

if (!isset($w) || $w === '') $w = 'c';

// ── avo8.0: 댓글 차단 판정 (count/time) ─────────────────────────────────
$mmb_cmt_block_msg = '';
if (empty($is_admin) && $w === 'c') {
    $_pid       = (int)($list_item['wr_id'] ?? 0);
    $_meta      = $GLOBALS['mmb_cmt_meta'][$_pid] ?? array();
    $_lim_cnt   = (int)($GLOBALS['mmb_climit_count'] ?? 0);
    $_lim_time  = (int)($GLOBALS['mmb_climit_time']  ?? 0);
    if ($_lim_time > 0 && !empty($_meta['parent_ts'])) {
        if ((G5_SERVER_TIME - (int)$_meta['parent_ts']) > ($_lim_time * 3600)) {
            $mmb_cmt_block_msg = $_lim_time . '시간이 지나 더 이상 댓글을 달 수 없습니다.';
        }
    }
    if ($mmb_cmt_block_msg === '' && $_lim_cnt > 0 && (int)($_meta['mb_cnt'] ?? 0) >= $_lim_cnt) {
        $mmb_cmt_block_msg = '이 글에 더 이상 댓글을 달 수 없습니다.';
    }
}

$mmb_comment_form_ctx = array(
    'bo_table'  => $bo_table,
    'w'         => $w,
    'list_item' => $list_item,
    'character' => $character,
    'board'     => $board,
    'cmt_block' => $mmb_cmt_block_msg,
    'action_ui' => 'select',
);
if (function_exists('mdl_do_action')) mdl_do_action('bbs_comment_form_before', $mmb_comment_form_ctx);
