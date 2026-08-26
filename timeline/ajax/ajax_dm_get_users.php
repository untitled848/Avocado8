<?php
/**
 * DM 사용자 목록 조회 API (timeline_user.tu_name / get_timeline_thumb)
 */
require_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');

$my_ch_id = timeline_dm_ajax_require();

$users = get_dm_candidate_users($tl_table, $my_ch_id);

echo json_encode(array(
    'success' => true,
    'users' => $users,
));
