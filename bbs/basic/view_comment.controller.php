<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * basic 댓글 뷰 — 컨트롤러
 *
 * 출력은 하지 않음. 템플릿(template/view_comment.tpl.php) 가 담당.
 *   - 액션 URL 계산
 *   - 모듈 옵션 prerender ($module_comment_options)
 */

$comment_action_url = https_url(G5_BBS_DIR).'/write_comment_update.php';

// 댓글 입력값 (수정 모드 w='cu' 가 아니면 비어있음)
$c_wr_content = isset($c_wr_content) ? $c_wr_content : '';

// 모듈 댓글 폼 옵션 prerender
$module_comment_ctx = array(
    'bo_table' => $bo_table,
    'wr_id'    => $wr_id,
);
$module_comment_options        = '';
$module_comment_action_options = '';
$module_comment_extra          = '';
$module_comment_after_action   = '';
if (function_exists('mdl_apply_filters')) {
    $module_comment_options        = mdl_apply_filters('bbs_comment_form_options',        '', $module_comment_ctx);
    $module_comment_action_options = mdl_apply_filters('bbs_comment_form_action_options', '', $module_comment_ctx);
    $module_comment_extra          = mdl_apply_filters('bbs_comment_form_extra',          '', $module_comment_ctx);
    $module_comment_after_action   = mdl_apply_filters('bbs_comment_form_after_action',   '', $module_comment_ctx);
}
