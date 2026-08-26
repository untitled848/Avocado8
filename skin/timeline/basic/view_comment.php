<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 타임라인 댓글 목록 출력 스킨
 * MMB의 view_comment.php와 유사한 구조
 * 
 * 변수:
 * - $list_item: 원글 정보 (replies 배열 포함)
 * - $write_table: 테이블명
 * - $character: 현재 캐릭터
 * - $is_admin: 관리자 여부
 * - $is_comment_write: 댓글 작성 권한
 * 
 * 스레드 뷰 (is_thread_view=true):
 * - list_item['replies']에 이미 조회된 댓글 사용
 * - focused_id로 클릭한 글 하이라이트
 * 
 * 일반 뷰 (is_thread_view=false):
 * - list_item['replies']에 이미 조회된 댓글 사용
 */

$comments = array();

// === 디버그 로그 ===
echo '<!-- view_comment.php DEBUG: is_thread_view='.($list_item['is_thread_view'] ? 'true' : 'false').', focused_id='.ses($list_item, 'focused_id', 0).', replies='.(isset($list_item['replies']) ? count($list_item['replies']) : 'NOT SET').' -->';
// === 디버그 끝 ===

// 댓글 작성 권한
$is_comment_write = ($member['mb_level'] >= $timeline['tl_write_level']);

// 스레드 뷰 관련 변수
$is_thread_view = !empty($list_item['is_thread_view']);
$focused_id = ses($list_item, 'focused_id', 0, 'int');
$has_more_before = !empty($list_item['has_more_before']);
$more_before_from = ses($list_item, 'more_before_from', 0, 'int');

// get_timeline_post()에서 이미 조회된 replies 사용
if (isset($list_item['replies']) && is_array($list_item['replies'])) {
    $c_i = 0;
    foreach ($list_item['replies'] as $c_row) {
        $comments[$c_i] = $c_row;
        
        // 이름 처리
        $comments[$c_i]['name'] = get_text(ses($c_row, 'wr_name', ''));
        
        // 본문은 원문 유지 — 스킨에서 render_timeline_content()로 일괄 처리
        // (여기서 conv_content/nl2br 하면 이후 htmlspecialchars에 의해 <br/> 가 그대로 보임)
        $comments[$c_i]['content'] = ses($c_row, 'wr_content', '');
        $comments[$c_i]['content1'] = ses($c_row, 'wr_content', ''); // 원본 (수정용)
        
        // 시간 처리
        $wr_datetime = ses($c_row, 'wr_datetime', '');
        $comments[$c_i]['datetime'] = $wr_datetime ? substr($wr_datetime, 2, 14) : '';
        $comments[$c_i]['time_ago'] = get_time_ago($wr_datetime);
        
        // IP 처리 (관리자가 아니면 마스킹)
        $comments[$c_i]['ip'] = ses($c_row, 'wr_ip', '');
        if (!$is_admin) {
            $comments[$c_i]['ip'] = preg_replace("/([0-9]+).([0-9]+).([0-9]+).([0-9]+)/", G5_IP_DISPLAY, $comments[$c_i]['ip']);
        }
        
        // 권한 설정
        $comments[$c_i]['is_reply'] = false;
        $comments[$c_i]['is_edit'] = false;
        $comments[$c_i]['is_del'] = false;
        
        if ($is_comment_write || $is_admin) {
            $token = '';
            
            // 회원인 경우
            if ($member['mb_id']) {
                $c_mb_id = ses($c_row, 'mb_id', '');
                if ($c_mb_id == $member['mb_id'] || $is_admin) {
                    set_session('ss_delete_tl_comment_'.$c_row['wr_id'].'_token', $token = uniqid(time()));
                    $comments[$c_i]['del_token'] = $token;
                    $comments[$c_i]['is_edit'] = true;
                    $comments[$c_i]['is_del'] = true;
                }
            }
            
            // 액션 로그가 있으면 관리자 외 삭제 불가
            if (ses($c_row, 'wr_log', '') && !$is_admin) {
                $comments[$c_i]['is_del'] = false;
            }
        }
        
        // 포커스 여부 (클릭한 글)
        $comments[$c_i]['is_focused'] = ($c_row['wr_id'] == $focused_id);
        
        $c_i++;
    }
}

// 댓글 스킨 include
include($timeline_skin_path.'/view_comment.skin.php');
