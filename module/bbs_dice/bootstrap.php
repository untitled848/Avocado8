<?php
if (!defined('_GNUBOARD_')) exit;

require_once __DIR__ . '/lib.php';

// 현재 모듈 URL (view 파일에서 이미지/JS/CSS 경로로 사용)
$module_url = G5_MDL_URL.'/bbs_dice';

/**
 * 다이스 모듈 (v2)
 *
 * - 글/댓글 폼 옵션에 "주사위" 체크박스 주입
 * - bbs_custom_action 필터에서 wr_dice=1 일 때 어드민 설정대로 굴려 wr_dice 컬럼에 콤마 구분 저장
 * - 굴림 결과는 views/row.php 에서 이미지/숫자 출력
 */

// 1) 글 폼 옵션 (write scope)
mdl_add_filter('bbs_write_form_options', function ($html, $ctx) {
    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    if (!mdl_board_enabled($bo_table, 'bbs_dice', 'write')) return $html;

    if (!empty($ctx['write']['wr_dice'])) return $html; // 이미 굴려진 글 수정 시 노출 X

    $raw_pfx = isset($ctx['id_prefix']) ? preg_replace('/[^a-zA-Z0-9_]/', '', (string)$ctx['id_prefix']) : '';
    $id = $raw_pfx === '' ? 'wr_dice' : $raw_pfx.'_wr_dice';

    return $html.'<fieldset>
                    <input type="checkbox" id="'.htmlspecialchars($id, ENT_QUOTES).'" name="wr_dice" value="1">
                    <label for="'.htmlspecialchars($id, ENT_QUOTES).'">주사위</label>
                 </fieldset>';
}, 10);

// 2) 댓글 폼 옵션
mdl_add_filter('bbs_comment_form_options', function ($html, $ctx) {
    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    if (!mdl_board_enabled($bo_table, 'bbs_dice', 'comment')) return $html;

    $wr_id = isset($ctx['list_item']['wr_id']) ? (int)$ctx['list_item']['wr_id'] : 0;
    $id    = 'wr_dice_'.$wr_id;
    return $html.'<input type="checkbox" id="'.$id.'" name="wr_dice" value="1" />'
                .'<label for="'.$id.'">주사위</label>&nbsp;&nbsp;&nbsp;&nbsp;';
}, 10);

// 3) 액션 처리
mdl_add_filter('bbs_custom_action', function ($result, $ctx) {
    if (!empty($result['handled'])) return $result;

    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    $w        = isset($ctx['w']) ? $ctx['w'] : '';
    if (function_exists('mdl_board_enabled_for_w')) {
        if (!mdl_board_enabled_for_w($bo_table, 'bbs_dice', $w)) return $result;
    } else {
        $scope = ($w === 'c' || $w === 'cu') ? 'comment' : 'write';
        if (!mdl_board_enabled($bo_table, 'bbs_dice', $scope)) return $result;
    }

    $existing = isset($ctx['existing']) && is_array($ctx['existing']) ? $ctx['existing'] : array();
    if (!empty($existing['wr_dice'])) return $result; // 보존

    $post = isset($ctx['post']) ? $ctx['post'] : array();
    if (!isset($post['wr_dice']) || (string)$post['wr_dice'] !== '1') return $result;

    $rolls = bbs_dice_roll_all();
    $val   = sql_real_escape_string(implode(',', $rolls));

    $result['handled']      = true;
    $result['customer_sql'] = " , wr_dice = '{$val}' ";
    return $result;
}, 10);

// 4) row 렌더 — 모듈이 활성화되어 있는 한 (게시판별 OFF 와 무관하게) 항상 출력
//   사용자 요구: 작성/수정은 게이트하더라도 이미 남은 로그는 표시되어야 함
mdl_add_filter('bbs_view_row_extra', function ($html, $row, $ctx) use ($module_url) {
    $rolls = bbs_dice_parse(isset($row['wr_dice']) ? $row['wr_dice'] : '');
    // 레거시 호환: wr_dice1/2 만 있는 경우
    if (empty($rolls) && (!empty($row['wr_dice1']) || !empty($row['wr_dice2']))) {
        if (!empty($row['wr_dice1'])) $rolls[] = (int)$row['wr_dice1'];
        if (!empty($row['wr_dice2'])) $rolls[] = (int)$row['wr_dice2'];
    }
    if (empty($rolls)) return $html;

    $view = __DIR__.'/views/row.php';
    if (!is_file($view)) return $html;

    ob_start();
    include $view;
    return $html.ob_get_clean();
}, 10);
