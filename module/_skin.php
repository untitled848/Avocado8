<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 모듈 — 게시판 스킨 공용 헬퍼 (전역 함수)
 *
 * 어떤 게시판 스킨에서든 사용 가능. _loader.php 가 로드.
 */

if (!function_exists('bbs_render_row_extra')) {
    /**
     * 글/댓글 row 1개 분량의 모듈 부가 렌더 진입점.
     *
     * @param array $row  글/댓글 row (write_table 한 행 — wr_log/wr_dice1/wr_item_log 등 포함)
     * @param array $ctx  bo_table, board_skin_url, board_skin_path, scope('post'|'comment')
     *
     * 1) bbs_view_row_extra 필터 호출 → 누적된 HTML echo
     * 2) wr_item_log 가 있으면 inventory 코어의 item_log_render 위임
     */
    function bbs_render_row_extra($row, $ctx) {
        if (!is_array($row)) $row = array();
        if (!is_array($ctx)) $ctx = array();

        // 1) 모듈 통합 훅
        if (function_exists('mdl_apply_filters')) {
            echo mdl_apply_filters('bbs_view_row_extra', '', $row, $ctx);
        }

        // 2) 아이템 사용 로그 (bbs_item_use / bbs_item_use_helas 모듈 코어)
        //    Wave 10+ : 일반 아이템과 운명토큰 모두 wr_item_log 단일 컬럼 사용.
        $item_log_str = isset($row['wr_item_log']) ? $row['wr_item_log'] : '';
        if ($item_log_str !== '') {
            $item_log = explode('||', $item_log_str);
            if (!empty($item_log[0])) {
                $core = G5_PATH.'/module/bbs_item_use/bbs/_common.php';
                if (is_file($core)) {
                    require_once $core;
                    $skin_path = isset($ctx['board_skin_path']) ? $ctx['board_skin_path'] : '';
                    if (function_exists('item_log_render')) {
                        item_log_render($item_log, $skin_path);
                    }
                }
            }
        }
    }
}
