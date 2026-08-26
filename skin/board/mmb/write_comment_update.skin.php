<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

$it = array();
$customer_sql = "";
$temp_wr_id = $comment_id;
$wr_num = $wr['wr_num'];
if(!$wr_num) $wr_num = $comment['wr_num'];

include_once($board_skin_path.'/write_update.inc.php');

// customer_sql 은 작성(c)/수정(cu) 모두 적용 (각 모듈이 자체 컬럼 가드).
if ($customer_sql !== '' && $comment_id) {
	if ($w !== 'cu') {
		$sql = " update {$write_table}
					set wr_subject = '{$wr_subject}'
					{$customer_sql}
				  where wr_id = '{$comment_id}' ";
	} else {
		$sql = " update {$write_table}
					set wr_id = '{$comment_id}'
					{$customer_sql}
				  where wr_id = '{$comment_id}' ";
	}
	sql_query($sql);
} else if ($w !== 'cu') {
	// customer_sql 가 비어있어도 작성 시에는 wr_subject 만은 슌워줘야 함.
	$sql = " update {$write_table}
				set wr_subject = '{$wr_subject}'
			  where wr_id = '{$comment_id}' ";
	sql_query($sql);
}

// memo_custom_sql 은 수정(cu) 의 호출 알람 추가 처리용 — 기존대로 cu 일 때만.
if ($w === 'cu' && !empty($memo_custom_sql)) {
	$sql = " update {$write_table}
				set wr_id = '{$comment_id}'
				{$memo_custom_sql}
			  where wr_id = '{$comment_id}' ";
	sql_query($sql);
}

$original_write =  sql_fetch("select mb_id, wr_subject from $write_table where wr_id = '$wr_id' ");

if($original_write['mb_id'] == $member['mb_id'] && ($original_write['wr_subject'] == '--|UPLOADING|--' || $original_write['wr_subject'] == '')) { 
	// 췩 상태가 해제가 안되었을 때
	$sql = " update {$write_table}
				set wr_subject = '{$character['ch_name']}', wr_ing = '0', wr_datetime = '".date('Y-m-d H:i:s')."'
			  where wr_id = '{$wr_id}' ";
	sql_query($sql);
}

if (function_exists('mdl_do_action')) {
	mdl_do_action('bbs_comment_after', $bo_table, $comment_id, $wr_id, $w);
	// 표준 comment_after 도 발사 — bbs_item_use 등 게시판 공유 모듈이 구독
	mdl_do_action('comment_after', $bo_table, $comment_id, $wr_id);
}

goto_url('./board.php?bo_table='.$bo_table.'&amp;'.$qstr.'&amp;#c_'.$comment_id);
?>
