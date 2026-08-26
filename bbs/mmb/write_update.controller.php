<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// mmb 전용 처리 (호출/멤버 알람) + 공용 모듈 훅(bbs/_module_write_update.inc.php)
// 모듈 훅 컨텍스트는 공용 inc 가 빌드. mmb 는 호출/메모 컬럼만 채움.

$memo_custom_sql = '';

// ******************** 호출 관련, 호출 시 알람테이블에 기재한다. 확인은 Y / N
// 멤버 닉네임 서칭
// -- 치환 문자로 사용될 문자가 본문에 사용 시 지워준다. (오류 방지)
$str = str_replace("||", "", $wr_content);
$str = str_replace("&&", "", $str);

// -- 괄호를 치환한다.
$str = str_replace("[[", "||&&", $str);
$str = str_replace("]]", "&&", $str);

// explode 로 해당 문자만 추출 할 수 있도록 작업한다.
$str = explode("||", $str);
// -- 추출한 배열을 토대로 정규식으로 닉네임을 추출한다.

$call_pattern = "/&&(.*)&&/";
$mb_nick_array = array();

for($i=0; $i < count($str); $i++) { 
	preg_match_all($call_pattern, $str[$i], $matches);
	if($matches[1]) {
		$mb_nick_array[] = $matches[1][0];
	}
}

// 배열 중복값 처리
$mb_nick_array = array_unique($mb_nick_array);


if(count($mb_nick_array) > 0) { 
	// -- 괄호를 치환한다.
	$memo = str_replace("[[", "", $wr_content);
	$memo = str_replace("]]", "", $memo);

	for($i=0; $i < count($mb_nick_array); $i++) { 
		// 회원 정보 있는지 여부 확인
		$memo_search = sql_fetch("select mb_id, mb_name from {$g5['member_table']} where mb_nick = '{$mb_nick_array[$i]}' or  mb_name = '{$mb_nick_array[$i]}'");
		if($memo_search['mb_id']) { 
			// 회원정보가 있을 시, 통합 호출(push_call) 로 알림 발송.
			// 동일 wr_id/bo_table/re_mb_id 중복은 기존 레코드 갱신, 신규는 push_call.
			$bc_link_val = G5_BBS_URL."/board.php?bo_table=".$bo_table."&log=".($wr_num * -1);

			$bc = sql_fetch(" select bc_id from {$g5['call_table']} where wr_id= '{$temp_wr_id}' and bo_table= '{$bo_table}' and re_mb_id = '{$memo_search['mb_id']}' and mb_id = '{$member['mb_id']}' ");

			if($bc['bc_id']) {
				// 기존 레코드 갱신 (memo/bc_link/check 리셋)
				sql_query(" UPDATE {$g5['call_table']} SET
						bc_type    = 'board',
						mb_name    = '".sql_escape_string($member['mb_nick'])."',
						re_mb_name = '".sql_escape_string($memo_search['mb_name'])."',
						ch_side    = '".sql_escape_string($character['ch_side'])."',
						wr_num     = '".(int)$wr_num."',
						memo       = '".sql_escape_string($memo)."',
						bc_link    = '".sql_escape_string($bc_link_val)."',
						bc_check   = 0,
						bc_datetime = '".G5_TIME_YMDHIS."'
					WHERE bc_id = '".(int)$bc['bc_id']."' ");
				sql_query(" UPDATE {$g5['member_table']} SET mb_memo_call = 1 WHERE mb_id = '".sql_escape_string($memo_search['mb_id'])."' ");
			} else if (function_exists('push_call')) {
				push_call(
					'board',
					['mb_id' => $member['mb_id'], 'mb_name' => $member['mb_nick']],
					[$memo_search['mb_id']],
					$memo,
					$bc_link_val,
					[],
					[
						'bo_table' => $bo_table,
						'wr_id'    => (int)$temp_wr_id,
						'wr_num'   => (int)$wr_num,
						'ch_side'  => $character['ch_side'],
					]
				);
			}
		} else { 
			// 회원정보가 없을 시, content 에 해당 닉네임을 블러 처리 하고
			// content 를 업데이트 한다.
			$wr_content = str_replace("[[".$mb_nick_array[$i]."]]", "[[???]]", $wr_content);
			$memo_custom_sql .= " , wr_content = '{$wr_content}' ";
		}
	}
}
// ******************** 호출 관련, 호출 시 해당 멤버에게 쪽지 보내기 기능 종료

if($w != 'cu') {
	$customer_sql = "
		{$memo_custom_sql}
	";
}

// 모듈 훅: wr_type(UPLOAD/URL 또는 모듈이 추가한 타입)에 따라 저장 SQL 을 달리한다.
//   기본 wr_type(UPLOAD/URL)은 코어 게시판 저장 로직이 처리하며, 모듈이 추가한
//   wr_type 은 이 훅에서 $customer_sql 에 컴럼 갱신을 덧붙일 수 있다.
if (function_exists('mdl_apply_filters')) {
	$mmb_wr_type = isset($_POST['wr_type']) ? trim($_POST['wr_type']) : (isset($write['wr_type']) ? $write['wr_type'] : '');
	$mmb_type_ctx = array(
		'bo_table'   => $bo_table,
		'w'          => $w,
		'wr_id'      => isset($wr_id) ? $wr_id : 0,
		'wr_num'     => isset($wr_num) ? $wr_num : 0,
		'temp_wr_id' => isset($temp_wr_id) ? $temp_wr_id : 0,
		'wr_type'    => $mmb_wr_type,
		'write'      => isset($write) && is_array($write) ? $write : array(),
		'character'  => isset($character) && is_array($character) ? $character : array(),
		'member'     => isset($member) && is_array($member) ? $member : array(),
		'post'       => $_POST,
	);
	$customer_sql = mdl_apply_filters('mmb_write_update_by_type', $customer_sql, $mmb_type_ctx);
}

// 모듈 훅 (액션 처리 + customer_sql/memo_custom_sql 변형 + before/after action)
//   $customer_sql 가 위에서 초기화되어 있어야 함. 아이템 사용은 inventory 모듈이 담당.
require G5_BBS_PATH . '/_module_write_update.inc.php';

?>

