<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * basic(일반 게시판) 글쓰기 폼 — 컨트롤러
 *
 * - 스킨 CSS 등록
 * - 옵션 영역 문자열 구성 ($option, $option_hidden)
 * - 모듈 옵션 prerender ($module_options) — 필터 결과를 미리 만들어 템플릿이 단순 echo
 * - bbs_write_form_before 액션 발사
 *
 * 출력은 하지 않는다. 템플릿 (template/write.tpl.php) 가 담당.
 */

add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);

$is_error = false;
$option = '';
$option_hidden = '';

if ($is_notice || $is_html || $is_secret || $is_mail) {
    if ($is_notice) {
        $option .= "\n".'<input type="checkbox" id="notice" name="notice" value="1" '.$notice_checked.'>'."\n".'<label for="notice">공지</label>';
    }
    if ($is_html) {
        if ($is_dhtml_editor) {
            $option_hidden .= '<input type="hidden" value="html1" name="html">';
        } else {
            $option .= "\n".'<input type="checkbox" id="html" name="html" onclick="html_auto_br(this);" value="'.$html_value.'" '.$html_checked.'>'."\n".'<label for="html">html</label>';
        }
    }
    if ($is_secret) {
        if ($is_admin || $is_secret == 1) {
            $option .= "\n".'<input type="checkbox" id="secret" name="secret" value="secret" '.$secret_checked.'>'."\n".'<label for="secret">비밀글</label>';
        } else {
            $option_hidden .= '<input type="hidden" name="secret" value="secret">';
        }
    }
    if ($is_mail) {
        $option .= "\n".'<input type="checkbox" id="mail" name="mail" value="mail" '.$recv_email_checked.'>'."\n".'<label for="mail">답변메일받기</label>';
    }
}

// 모듈 훅 컨텍스트
$basic_form_ctx = array(
    'bo_table' => $bo_table,
    'w'        => $w,
    'wr_id'    => isset($wr_id) ? $wr_id : 0,  // 새 글(w='') 일 때 미설정
    'write'    => isset($write) ? $write : array(), // 수정(w='u') 일 때만 채워짐
    'board'    => $board,
);

// 모듈 옵션 prerender
$module_options        = '';
$module_action_options = '';
$module_action_panels  = '';
if (function_exists('mdl_apply_filters')) {
    $module_options        = mdl_apply_filters('bbs_write_form_options',        '', $basic_form_ctx); // 체크박스류
    $module_action_options = mdl_apply_filters('bbs_write_form_action_options', '', $basic_form_ctx); // <select name="action"> 옵션
    $module_action_panels  = mdl_apply_filters('bbs_write_form_action_panels',  '', $basic_form_ctx); // 액션별 패널
}

if (function_exists('mdl_do_action')) {
    mdl_do_action('bbs_write_form_before', $basic_form_ctx);
}
