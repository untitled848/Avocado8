<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

$it = array();
$customer_sql = "";
$temp_wr_id = $wr_id;
if(!$wr_num) $wr_num = isset($write['wr_num']) ? $write['wr_num'] : '';

/**
 * 정산 신청 (bo_1=1) — wr_link1 JSON
 *
 * 코어 write_update.php 는 POST 에 wr_link1 이 없으면 빈 문자열로 UPDATE 한다.
 * 목록 내용수정·write 폼 모두 이 스킨에서 복원/재저장해야 신청·지급 기록이 유지된다.
 */
if (isset($board['bo_1']) && $board['bo_1'] === '1') {
    // 코어 UPDATE 직전 스냅샷 ($wr / $write 메모리)
    $_prev_link1 = '';
    $_prev_wr10  = '';
    if ($w === 'u') {
        if (!empty($wr['wr_link1'])) {
            $_prev_link1 = $wr['wr_link1'];
        } else if (!empty($write['wr_link1'])) {
            $_prev_link1 = $write['wr_link1'];
        }
        if (isset($wr['wr_10']) && $wr['wr_10'] !== '') {
            $_prev_wr10 = $wr['wr_10'];
        } else if (isset($write['wr_10']) && $write['wr_10'] !== '') {
            $_prev_wr10 = $write['wr_10'];
        }
    }

    $_prev_req = array();
    if ($_prev_link1 !== '') {
        $_t = json_decode($_prev_link1, true);
        if (is_array($_t) && !empty($_t['_calc_req'])) {
            $_prev_req = $_t;
        }
    }

    $_has_req_fields = array_key_exists('calc_req_preset', $_POST)
        || array_key_exists('calc_req_money', $_POST)
        || array_key_exists('calc_req_exp', $_POST);

    if ($_has_req_fields) {
        $_pid   = isset($_POST['calc_req_preset']) ? (int)$_POST['calc_req_preset'] : 0;
        $_money = isset($_POST['calc_req_money'])  ? (int)$_POST['calc_req_money']  : 0;
        $_exp   = isset($_POST['calc_req_exp'])    ? (int)$_POST['calc_req_exp']    : 0;

        if ($_pid > 0 || $_money > 0 || $_exp > 0) {
            $_calc_req = array(
                '_calc_req' => 1,
                'preset_id' => $_pid,
                'money'     => $_money,
                'exp'       => $_exp,
                'paid'      => !empty($_prev_req['paid']) ? 1 : 0,
            );
            if (!empty($_prev_req['paid_at'])) {
                $_calc_req['paid_at'] = $_prev_req['paid_at'];
            }
            $_json = json_encode($_calc_req, JSON_UNESCAPED_UNICODE);
            $customer_sql .= " , wr_link1 = '".addslashes($_json)."' ";
        } else if ($w === 'u' && !empty($_prev_req['paid'])) {
            // 이미 지급된 신청은 비우지 않음
            $customer_sql .= " , wr_link1 = '".addslashes($_prev_link1)."' ";
        } else if ($w === 'u') {
            $customer_sql .= " , wr_link1 = '' ";
        }
    } else if ($w === 'u' && $_prev_link1 !== '') {
        // 목록 내용수정 등 — 신청 필드 없음 → 코어가 지운 wr_link1 복원
        $customer_sql .= " , wr_link1 = '".addslashes($_prev_link1)."' ";
    }

    // wr_10(정산완료/반려): write 폼 등에 필드가 없으면 코어가 비움 → 복원
    if ($w === 'u' && $_prev_wr10 !== '' && (!isset($_POST['wr_10']) || trim((string)$_POST['wr_10']) === '')) {
        $customer_sql .= " , wr_10 = '".addslashes($_prev_wr10)."' ";
    }
}

$sql = " update {$write_table}
			set wr_id = '{$wr_id}'
			{$customer_sql}
		  where wr_id = '{$wr_id}' ";
sql_query($sql);

goto_url(G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table.$qstr);
?>
