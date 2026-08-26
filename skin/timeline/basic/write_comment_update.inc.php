<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 타임라인 댓글 작성/수정 처리 스킨 (include용)
 * MMB의 write_comment_update.skin.php와 유사한 구조
 * 
 * ajax_comment.php, ajax_comment_update.php에서 include됨
 * 
 * 변수:
 * - $w: 모드 (c: 새 댓글, cu: 수정)
 * - $comment_id: 댓글 ID (수정 시)
 * - $wr_id: 원글 ID
 * - $wr_num: 원글 번호
 * - $write_table: 테이블명
 * - $character: 현재 캐릭터
 * - $customer_sql: 커스텀 SQL (이 파일에서 설정)
 * 
 * 설정 가능:
 * - $customer_sql: INSERT/UPDATE 후 추가 실행할 SQL 조각
 */

// 기본값
$it = array();
$customer_sql = "";

// 스킨에서 include할 공통 처리 파일
if (file_exists($timeline_skin_path.'/write_update.inc.php')) {
    include_once($timeline_skin_path.'/write_update.inc.php');
}

// ============================================
// 여기에 커스텀 로직 추가
// ============================================

// 예시: 특정 wr_1 필드 업데이트
// $customer_sql .= ", wr_1 = 'custom_value'";

// 예시: 아이템 사용 처리
// $use_item = ses($_POST, 'use_item', 0, 'int');
// if ($use_item) {
//     // 아이템 처리 로직
//     $customer_sql .= ", wr_item = '{$use_item}'";
// }
