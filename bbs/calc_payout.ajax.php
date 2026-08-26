<?php
include_once('./_common.php');

header('Content-Type: application/json; charset=utf-8');

function _calc_pay_resp($ok, $msg = '', $extra = array()) {
    echo json_encode(array_merge(array('ok'=>$ok, 'message'=>$msg), $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$is_admin) _calc_pay_resp(false, '관리자만 사용 가능합니다.');
if (!function_exists('payout_apply')) _calc_pay_resp(false, '정산 라이브러리가 로드되지 않았습니다.');

$bo_table = isset($_POST['bo_table']) ? preg_replace('/[^a-z0-9_]/i', '', (string)$_POST['bo_table']) : '';
$wr_id    = isset($_POST['wr_id'])    ? (int)$_POST['wr_id']    : 0;
if ($bo_table === '' || $wr_id <= 0) _calc_pay_resp(false, '잘못된 요청.');

$board = sql_fetch("SELECT * FROM `{$g5['board_table']}` WHERE bo_table = '{$bo_table}'");
if (empty($board['bo_table'])) _calc_pay_resp(false, '게시판 없음.');

$write_table = $g5['write_prefix'] . $bo_table;
$write = sql_fetch("SELECT * FROM `{$write_table}` WHERE wr_id = '{$wr_id}'");
if (empty($write['wr_id'])) _calc_pay_resp(false, '글 없음.');

$ch_id = isset($write['ch_id']) ? (int)$write['ch_id'] : 0;
if ($ch_id <= 0) _calc_pay_resp(false, '글 작성자 캐릭터를 찾을 수 없습니다.');

// 이미 지급된 신청은 차단
$existing_req = array();
if (!empty($write['wr_link1'])) {
    $t = json_decode($write['wr_link1'], true);
    if (is_array($t) && !empty($t['_calc_req'])) $existing_req = $t;
}
if (!empty($existing_req['paid'])) _calc_pay_resp(false, '이미 지급된 신청입니다.');

$mode = isset($_POST['mode']) ? (string)$_POST['mode'] : 'merge';
$memo = isset($_POST['memo']) ? trim((string)$_POST['memo']) : '';

$payload_list = array();

// 1) 프리셋 (선택했으면 항상 포함)
$preset_id = isset($_POST['preset_id']) ? (int)$_POST['preset_id'] : 0;
if ($preset_id > 0) {
    $preset = payout_preset_get($preset_id);
    if ($preset && !empty($preset['payload'])) {
        $payload_list = array_merge($payload_list, $preset['payload']);
    }
}

// 2) 추가 화폐/경험치
$extra_money = isset($_POST['extra_money']) ? (int)$_POST['extra_money'] : 0;
$extra_exp   = isset($_POST['extra_exp'])   ? (int)$_POST['extra_exp']   : 0;
if ($extra_money > 0) $payload_list[] = array('type'=>'money', 'qty'=>$extra_money);
if ($extra_exp   > 0) $payload_list[] = array('type'=>'exp',   'qty'=>$extra_exp);


// 4) 추가 payload editor 항목 (item/title/fortune/...)
$pl    = isset($_POST['payload']) && is_array($_POST['payload']) ? $_POST['payload'] : array();
$types = isset($pl['type']) && is_array($pl['type']) ? $pl['type'] : array();
$ids   = isset($pl['id'])   && is_array($pl['id'])   ? $pl['id']   : array();
$qtys  = isset($pl['qty'])  && is_array($pl['qty'])  ? $pl['qty']  : array();
$n = count($types);
for ($i = 0; $i < $n; $i++) {
    $t = (string)$types[$i];
    if ($t === '') continue;
    $payload_list[] = array(
        'type' => $t,
        'id'   => isset($ids[$i])  ? (int)$ids[$i]  : 0,
        'qty'  => isset($qtys[$i]) ? (int)$qtys[$i] : 1,
    );
}

$extra_class = isset($_POST['extra_class']) ? trim((string)$_POST['extra_class']) : '';

if (empty($payload_list) && $extra_class === '') _calc_pay_resp(false, '지급 항목이 없습니다.');

$stats = array(
    'targets'  => 1,
    'payloads' => 1,
    'success'  => 1,
    'failed'   => 0,
    'logs'     => array(),
);
if(!empty($payload_list)) $stats = payout_apply(array($ch_id), $payload_list, $memo);

// 신청이 있었으면 paid=1 으로 표시
if (!empty($existing_req)) {
    
    // 승급 신청이 있으면 실제로 승급도 처리
    if ($extra_class !== '') {
        sql_query (" UPDATE {$g5['character_table']}  set ch_class='{$extra_class}' where ch_id = '{$ch_id}' ");
        $stats['logs'][]="승급 완료";
    }

    $existing_req['paid'] = 1;
    $existing_req['paid_at'] = G5_TIME_YMDHIS;
    $json = json_encode($existing_req, JSON_UNESCAPED_UNICODE);
    sql_query("UPDATE `{$write_table}` SET wr_link1 = '".addslashes($json)."' WHERE wr_id = '{$wr_id}'");
}

_calc_pay_resp(true, '지급 완료', $stats);
