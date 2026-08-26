<?php
// timeline_list_update.php 에서 include 하는 파일
// $tmp_tl_table 에 타임라인 테이블명을 넘겨주어야 함

if (!defined('_GNUBOARD_')) exit;
if (!$tmp_tl_table) return;

// 타임라인 설정 삭제
sql_query(" DELETE FROM {$g5['timeline_table']} WHERE tl_table = '{$tmp_tl_table}' ");

// 좋아요 삭제
sql_query(" DELETE FROM {$g5['timeline_good_table']} WHERE tl_table = '{$tmp_tl_table}' ");

// 북마크 삭제
sql_query(" DELETE FROM {$g5['timeline_bookmark_table']} WHERE tl_table = '{$tmp_tl_table}' ");

// 멘션 삭제
sql_query(" DELETE FROM {$g5['timeline_mention_table']} WHERE tl_table = '{$tmp_tl_table}' ");

// 사용자 목록 삭제
sql_query(" DELETE FROM {$g5['timeline_user_table']} WHERE tl_table = '{$tmp_tl_table}' ");

// 답글 임시저장 삭제
if (!empty($g5['timeline_temp_table'])) {
    sql_query(" DELETE FROM {$g5['timeline_temp_table']} WHERE tl_table = '{$tmp_tl_table}' ", false);
}

// 글 테이블 DROP
$write_table = get_timeline_write_table($tmp_tl_table);
sql_query(" DROP TABLE IF EXISTS {$write_table} ", false);

// 이미지 폴더 삭제
if (defined('G5_TIMELINE_PATH')) {
    $timeline_path = G5_TIMELINE_PATH.'/'.$tmp_tl_table;
    if (is_dir($timeline_path)) {
        rm_rf($timeline_path);
    }
}

// 모듈 on/off 상태 삭제 (board_state.json — tl_table 키)
if (function_exists('mdl_delete_board_state')) {
    mdl_delete_board_state($tmp_tl_table);
}
?>
