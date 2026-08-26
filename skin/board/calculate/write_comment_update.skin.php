<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

$it = array();
$customer_sql = "";
$temp_wr_id = $comment_id;
$wr_num = isset($wr['wr_num']) ? $wr['wr_num'] : '';
if(!$wr_num && isset($comment['wr_num'])) $wr_num = $comment['wr_num'];

/**
 * 원글 wr_10 = 정산 상태(정산완료/반려).
 * 신규 댓글 작성 폼의 state 라디오가 있을 때만 갱신한다.
 * 댓글 수정(cu) 폼에는 state 가 없어, 무조건 UPDATE 하면 상태가 빈 값으로 지워진다.
 */
$state = isset($_POST['state']) ? trim((string)$_POST['state']) : '';
if ($state !== '') {
	$sql = " update {$write_table}
			set wr_10 = '".addslashes($state)."'
		  where wr_id = '{$wr_id}' ";
	sql_query($sql);
}

if($w != 'cu') {
	// ----- 경험치 변동 (레거시 — 현재 댓글 폼에 필드 없음)
	if(isset($ex_value) && $ex_value && $ex_value > 0) {
		insert_exp($comment_ch_id, $ex_value, $ex_content);
	}
	// ----- 금액 변동 (레거시)
	if(isset($mo_value) && $mo_value && $mo_value > 0) {
		insert_point($comment_mb_id, $mo_value, $mo_content, '@passive', $comment_mb_id, 'admin-'.uniqid(''), 0);
	}
}

goto_url('./board.php?bo_table='.$bo_table.'&amp;'.$qstr.'&amp;#c_'.$comment_id);
?>
