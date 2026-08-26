<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * bbs_item_use — 게시판 아이템 사용/표시 코어 (구 inventory/bbs)
 *
 * 진입점:
 *   - item_use_process()  : 글/코멘트 등록 시 use_item POST 처리 (use.php)
 *   - item_gift_process() : 글 등록 시 gift_item POST 처리 (use.php)
 *   - item_log_render()   : wr_item_log 문자열을 HTML로 렌더 (render.php)
 *   - item_form_select()  : 폼에 아이템 사용/선물 select 출력 (render.php). mmb 원글 Action형, mmb 댓글 드롭다운만, 타임라인 현행.
 */

require_once __DIR__ . '/use.php';
require_once __DIR__ . '/render.php';

if (!function_exists('item_template')) {
    /**
     * 템플릿 override-aware lookup.
     *   1) <스킨>/template/<name>  (있으면 우선)
     *   2) module/bbs_item_use/bbs/template/<name>
     */
    function item_template($name, $board_skin_path = '') {
        $name = ltrim($name, '/');
        if ($board_skin_path !== '') {
            $skin_tpl = $board_skin_path . '/template/' . $name;
            if (is_file($skin_tpl)) return $skin_tpl;
        }
        return __DIR__ . '/template/' . $name;
    }
}
