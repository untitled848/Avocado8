<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * basic(일반 게시판) write_comment_update — 컨트롤러
 *
 * gnuboard 코어가 댓글 행을 INSERT/UPDATE 한 뒤 호출됨.
 * 모듈 훅을 통해 customer_sql / memo_custom_sql 을 누적, 댓글 행에 추가 UPDATE.
 */

$customer_sql    = '';
$memo_custom_sql = '';
$temp_wr_id      = $comment_id;
if (empty($wr_num)) {
    if (isset($wr['wr_num']))           $wr_num = $wr['wr_num'];
    else if (isset($comment['wr_num'])) $wr_num = $comment['wr_num'];
}

require G5_BBS_PATH . '/_module_write_update.inc.php';

// customer_sql (액션/모듈 결과) 는 c · cu 모두에서 적용. 각 모듈이 $existing 을 보고 더할지 결정함.
if ($customer_sql !== '' && $comment_id) {
    $sql = " update {$write_table}
                set wr_id = '{$comment_id}'
                {$customer_sql}
              where wr_id = '{$comment_id}' ";
    sql_query($sql);
}
// memo_custom_sql (mmb 호출 알람 등) 은 수정(cu) 일 때만 적용.
if ($w === 'cu' && $memo_custom_sql !== '' && $comment_id) {
    $sql = " update {$write_table}
                set wr_id = '{$comment_id}'
                {$memo_custom_sql}
              where wr_id = '{$comment_id}' ";
    sql_query($sql);
}

if (function_exists('mdl_do_action')) {
    mdl_do_action('bbs_comment_after', $bo_table, $comment_id, $wr_id, $w);
}

// 일반 게시판: 댓글 작성 후 부모 글 상세 페이지의 해당 댓글 위치로 이동
//   (mmb 같은 log 기반 게시판은 #log_<wr_id> 로 이동하지만 basic 은 wr_id 기반)
goto_url('./board.php?bo_table='.$bo_table.'&amp;wr_id='.$wr_id.'&amp;'.$qstr.'#c_'.$comment_id);
