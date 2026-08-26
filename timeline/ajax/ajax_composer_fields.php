<?php
/**
 * 타임라인 글쓰기 컴포저 필드 HTML (AJAX)
 * 게시/아이템 사용·선물 후 드롭다운 등 동적 영역을 새로고침할 때 사용
 *
 * GET/POST: tl_table
 * GET: mode=write|reply (기본 write)
 * reply 시: reply_form_id, parent_id, reply_to_id(optional)
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

include_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function timeline_composer_json($payload) {
    if (ob_get_level() > 0) {
        ob_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
    $flags = JSON_UNESCAPED_UNICODE;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
    }
    $json = json_encode($payload, $flags);
    if ($json === false) {
        $json = json_encode(array(
            'success' => false,
            'html'    => '',
            'message' => '컴포저 필드를 인코딩하지 못했습니다.',
        ), JSON_UNESCAPED_UNICODE);
    }
    echo $json;
    exit;
}

$result = array('success' => false, 'html' => '', 'message' => '');

$my_ch_id = ses($character, 'ch_id', 0, 'int');
if (!$is_member || !$my_ch_id) {
    $result['message'] = '로그인이 필요합니다.';
    timeline_composer_json($result);
}

if (!can_write_timeline($timeline, $my_ch_id, $is_admin)) {
    $result['message'] = '글쓰기 권한이 없습니다.';
    timeline_composer_json($result);
}

$mode = ses($_REQUEST, 'mode', 'write', 'raw');
if ($mode !== 'reply') {
    $mode = 'write';
}

$content_max = ses($timeline, 'tl_content_max', 500, 'int');
if (!$content_max) $content_max = 500;
$image_max = ses($timeline, 'tl_image_count', 4, 'int');
if ($image_max < 1) $image_max = 4;
if ($image_max > 4) $image_max = 4;
$tl_use_image = ses($timeline, 'tl_use_image', 0, 'int');

$fields_path = $timeline_skin_path.'/composer_fields.skin.php';
if (!file_exists($fields_path)) {
    $result['message'] = '스킨 파일을 찾을 수 없습니다.';
    timeline_composer_json($result);
}

if ($mode === 'reply') {
    $reply_form_id = ses($_REQUEST, 'reply_form_id', 0, 'int');
    $parent_id = ses($_REQUEST, 'parent_id', 0, 'int');
    if (!$reply_form_id || !$parent_id) {
        $result['message'] = '답글 폼 정보가 없습니다.';
        timeline_composer_json($result);
    }

    $form_prefix = 'ir_'.$reply_form_id;
    // mmb 댓글과 동일 — bbs_comment_form_* 훅
    $_ir_ctx = array(
        'bo_table'  => $bo_table,
        'w'         => 'c',
        'wr_id'     => $parent_id,
        'write'     => array(),
        'character' => $character,
        'board'     => isset($board) ? $board : array(),
        'scope_id'  => $reply_form_id,
        'id_prefix' => $form_prefix,
        'list_item' => array('wr_id' => $reply_form_id),
        'action_ui' => 'select',
        'ui'        => 'composer',
    );
    $_ir_action_options = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_comment_form_action_options', '', $_ir_ctx) : '';
    $_ir_action_panels  = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_comment_form_extra', '', $_ir_ctx) : '';
    $_ir_form_options   = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_comment_form_options', '', $_ir_ctx) : '';
    $_ir_after_action   = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_comment_form_after_action', '', $_ir_ctx) : '';
    $_ir_composer_icons = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_comment_form_composer_icons', '', $_ir_ctx) : '';
    $_ir_composer_panels = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_comment_form_composer_panels', '', $_ir_ctx) : '';

    $tl_cf = array(
        'prefix'              => $form_prefix,
        'content_max'         => $content_max,
        'image_max'           => $image_max,
        'use_image'           => $tl_use_image,
        'is_approved'         => $is_approved,
        'placeholder'         => '답글을 입력하세요...',
        'mention_placeholder' => '사용자 검색 (최대 6자)',
        'textarea_class'      => 'tl-comment-textarea',
        'textarea_attrs'      => 'oninput="irUpdateCharCount(\''.h($form_prefix).'\', '.$content_max.')"',
        'form_options'        => $_ir_form_options,
        'action_options'      => $_ir_action_options,
        'action_panels'       => $_ir_action_panels,
        'after_action'        => $_ir_after_action,
        'composer_icons'      => $_ir_composer_icons,
        'composer_panels'     => $_ir_composer_panels,
        'submit_label'        => '답글',
        'submit_disabled'     => false,
        'submit_class'        => 'tl-btn tl-btn-primary',
        'image_onchange'      => 'irPreviewImages(\''.h($form_prefix).'\', this, '.$image_max.')',
        'toggle_action_js'    => 'irToggleAction(\''.h($form_prefix).'\')',
        'toggle_item_js'      => 'irToggleItem(\''.h($form_prefix).'\')',
    );
} else {
    $tl_form_ctx = array(
        'bo_table'  => $bo_table,
        'w'         => '',
        'wr_id'     => 0,
        'write'     => array(),
        'character' => $character,
        'id_prefix' => '',
        'ui'        => 'composer',
    );
    $_mdl_action_options = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_write_form_action_options', '', $tl_form_ctx) : '';
    $_mdl_action_panels  = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_write_form_action_panels', '', $tl_form_ctx) : '';
    $_mdl_after_action   = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_write_form_after_action', '', $tl_form_ctx) : '';
    $_mdl_form_options   = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_write_form_options', '', $tl_form_ctx) : '';
    $_mdl_composer_icons = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_write_form_composer_icons', '', $tl_form_ctx) : '';
    $_mdl_composer_panels = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_write_form_composer_panels', '', $tl_form_ctx) : '';

    $tl_cf = array(
        'prefix'              => '',
        'content_max'         => $content_max,
        'image_max'           => $image_max,
        'use_image'           => $tl_use_image,
        'is_approved'         => $is_approved,
        'placeholder'         => '*굵게*|**기울기**|***굵고 기울기***|[선택1/선택2/선택3]|[NdM]',
        'mention_placeholder' => '캐릭터 이름 검색 (최대 6자)',
        'form_options'        => $_mdl_form_options,
        'action_options'      => $_mdl_action_options,
        'action_panels'       => $_mdl_action_panels,
        'after_action'        => $_mdl_after_action,
        'composer_icons'      => $_mdl_composer_icons,
        'composer_panels'     => $_mdl_composer_panels,
        'submit_label'        => '게시',
        'submit_disabled'     => true,
        'submit_class'        => 'tl-submit-btn',
        'image_onchange'      => 'previewImages(this, '.$image_max.')',
        'toggle_action_js'    => 'toggleActionArea()',
        'toggle_item_js'      => 'toggleItemArea()',
    );
}

$action_options = ($mode === 'reply') ? $_ir_action_options : $_mdl_action_options;
$action_panels  = ($mode === 'reply') ? $_ir_action_panels  : $_mdl_action_panels;
$after_action   = ($mode === 'reply') ? $_ir_after_action   : $_mdl_after_action;
$composer_icons = ($mode === 'reply') ? $_ir_composer_icons : $_mdl_composer_icons;
$composer_panels = ($mode === 'reply') ? $_ir_composer_panels : $_mdl_composer_panels;

$fragment = ses($_REQUEST, 'fragment', '', 'raw');
if ($fragment === 'inventory') {
    timeline_composer_json(array(
        'success'          => true,
        'fragment'         => 'inventory',
        'mode'             => $mode,
        'item_html'        => (string)$after_action,
        'action_options'   => (string)$action_options,
        'action_panels'    => (string)$action_panels,
        'composer_icons'   => (string)$composer_icons,
        'composer_panels'  => (string)$composer_panels,
    ));
}

ob_start();
include $fields_path;
$html = ob_get_clean();

$result['success'] = true;
$result['html'] = $html;
$result['mode'] = $mode;
$result['item_html'] = (string)$after_action;
$result['action_options'] = (string)$action_options;
$result['action_panels'] = (string)$action_panels;
$result['composer_icons'] = (string)$composer_icons;
$result['composer_panels'] = (string)$composer_panels;
timeline_composer_json($result);
