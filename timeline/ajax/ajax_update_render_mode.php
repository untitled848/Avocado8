<?php
/**
 * 타임라인 렌더링 모드 변경 AJAX
 *
 * timeline_user.tl_render_mode
 * - thread: 멘션 탭에서 원글+커넥터+답글 표시
 * - normal: 원글/커넥터 없이 답글만 표시
 */

require_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');

$my_ch_id = ses($character, 'ch_id', 0, 'int');
if (!$is_member || !$my_ch_id) {
    echo json_encode(array('success' => false, 'message' => '로그인이 필요합니다.'));
    exit;
}

if (!$tl_table) {
    echo json_encode(array('success' => false, 'message' => '타임라인이 지정되지 않았습니다.'));
    exit;
}

// 임시: 스레드 보기 비활성화 — 항상 normal
$tl_render_mode = 'normal';

$tl_safe = sql_escape_string($tl_table);
$mode_esc = sql_escape_string($tl_render_mode);

$exists = sql_fetch("
    SELECT ch_id FROM {$g5['timeline_user_table']}
    WHERE tl_table = '{$tl_safe}' AND ch_id = '{$my_ch_id}'
", false);

if (is_array($exists) && !empty($exists['ch_id'])) {
    sql_query("
        UPDATE {$g5['timeline_user_table']}
        SET tl_render_mode = '{$mode_esc}'
        WHERE tl_table = '{$tl_safe}' AND ch_id = '{$my_ch_id}'
    ");
} else {
    // 사용자 행이 없으면 기본 프로필로 생성 후 모드 저장
    add_timeline_user($tl_table, $my_ch_id);
    sql_query("
        UPDATE {$g5['timeline_user_table']}
        SET tl_render_mode = '{$mode_esc}'
        WHERE tl_table = '{$tl_safe}' AND ch_id = '{$my_ch_id}'
    ");
}

echo json_encode(array(
    'success' => true,
    'message' => '설정이 변경되었습니다.',
    'render_mode' => $tl_render_mode
));
