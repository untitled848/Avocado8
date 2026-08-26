<?php
/**
 * 타임라인 새 글 체크 AJAX (폴링용)
 * 게시판 호환 구조
 */

require_once(__DIR__.'/../_common.php');

$my_ch_id = ses($character, 'ch_id', 0, 'int');

header('Content-Type: application/json; charset=utf-8');

if (!$is_member || !$my_ch_id) {
    echo json_encode(array('success' => false, 'new_count' => 0, 'mention_count' => 0));
    exit;
}

$last_check = ses($_GET, 'last_check', 0, 'int');
$tab = ses($_GET, 'tab', 'all', 'raw');

$result = check_timeline_new($tl_table, $my_ch_id, $last_check, $tab);

echo json_encode($result);
