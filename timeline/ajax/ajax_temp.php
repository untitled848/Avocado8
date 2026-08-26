<?php
/**
 * 타임라인 답글 임시저장 AJAX
 *
 * POST/GET: tl_table, parent_wr_id
 * act: get | save | delete
 * save 시 wr_content (원본 텍스트, 렌더링하지 않음)
 */
include_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');

function timeline_temp_json($payload) {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$my_ch_id = ses($character, 'ch_id', 0, 'int');
$empty = timeline_temp_row_payload(null);

if (!$is_member || !$my_ch_id) {
    timeline_temp_json(array(
        'success'  => false,
        'message'  => '로그인이 필요합니다.',
        'has_temp' => false,
    ) + $empty);
}

if (!function_exists('can_write_timeline') || !can_write_timeline($timeline, $my_ch_id, $is_admin)) {
    timeline_temp_json(array(
        'success'  => false,
        'message'  => '글쓰기 권한이 없습니다.',
        'has_temp' => false,
    ) + $empty);
}

$act = ses($_REQUEST, 'act', 'get', 'raw');
if ($act === '') {
    $act = ses($_REQUEST, 'action', 'get', 'raw');
}
$parent_wr_id = ses($_REQUEST, 'parent_wr_id', 0, 'int');
if (!$parent_wr_id) {
    $parent_wr_id = ses($_REQUEST, 'wr_id', 0, 'int');
}

$tu_id = get_timeline_temp_tu_id($my_ch_id);

if ($act === 'save') {
    $wr_content = ses($_POST, 'wr_content', '', 'raw');
    if ($wr_content === '') {
        $wr_content = ses($_POST, 'content', '', 'raw');
    }
    $saved = save_timeline_temp($tl_table, $parent_wr_id, $wr_content, $tu_id);
    $payload = timeline_temp_row_payload(isset($saved['row']) ? $saved['row'] : null);
    timeline_temp_json(array(
        'success'  => !empty($saved['success']),
        'message'  => isset($saved['message']) ? $saved['message'] : '',
        'has_temp' => !empty($saved['success']),
    ) + $payload);
}

if ($act === 'delete') {
    $ok = delete_timeline_temp($tl_table, $parent_wr_id, $tu_id);
    timeline_temp_json(array(
        'success'  => (bool)$ok,
        'message'  => $ok ? '임시저장을 삭제했습니다.' : '삭제할 내역이 없습니다.',
        'has_temp' => false,
    ) + timeline_temp_row_payload(null));
}

$row = get_timeline_temp($tl_table, $parent_wr_id, $tu_id);
$payload = timeline_temp_row_payload($row);
timeline_temp_json(array(
    'success'  => true,
    'message'  => '',
    'has_temp' => ($payload['wr_content'] !== ''),
) + $payload);
