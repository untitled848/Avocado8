<?php
$sub_menu = "520020";
include_once('./_common.php');
check_admin_token();

$cp_id          = isset($_POST['cp_id']) ? (int)$_POST['cp_id'] : 0;
// G5_ESCAPE_FUNCTION 이 $_POST 전체에 addslashes 를 사전 적용하므로
// stripslashes 로 되돌린 뒤 sql_real_escape_string 을 한 번만 적용한다.
$cp_name        = isset($_POST['cp_name']) ? trim(stripslashes((string)$_POST['cp_name'])) : '';
$cp_use         = !empty($_POST['cp_use']) ? 1 : 0;
$cp_use_request = !empty($_POST['cp_use_request']) ? 1 : 0;
$cp_memo        = isset($_POST['cp_memo']) ? trim(stripslashes((string)$_POST['cp_memo'])) : '';
$cp_order       = isset($_POST['cp_order']) ? (int)$_POST['cp_order'] : 0;

if ($cp_name === '') alert('이름을 입력하세요.');

// payload 직렬화
$pl = isset($_POST['payload']) && is_array($_POST['payload']) ? $_POST['payload'] : array();
$types = isset($pl['type']) && is_array($pl['type']) ? $pl['type'] : array();
$ids   = isset($pl['id'])   && is_array($pl['id'])   ? $pl['id']   : array();
$qtys  = isset($pl['qty'])  && is_array($pl['qty'])  ? $pl['qty']  : array();
$payload = array();
$n = count($types);
for ($i = 0; $i < $n; $i++) {
    $t = (string)$types[$i];
    if ($t === '') continue;
    $payload[] = array(
        'type' => $t,
        'id'   => isset($ids[$i])  ? (int)$ids[$i]  : 0,
        'qty'  => isset($qtys[$i]) ? (int)$qtys[$i] : 1,
    );
}

if (function_exists('mdl_apply_filters')) {
    $payload = mdl_apply_filters('payout_preset_payload_save', $payload, $_POST);
    if (!is_array($payload)) $payload = array();
}

$json = json_encode($payload, JSON_UNESCAPED_UNICODE);
$json_sql = sql_real_escape_string($json);
$name_sql = sql_real_escape_string($cp_name);
$memo_sql = sql_real_escape_string($cp_memo);

if ($cp_id > 0) {
    sql_query("UPDATE `{$g5['calc_preset_table']}` SET
        cp_name='{$name_sql}', cp_use='{$cp_use}', cp_use_request='{$cp_use_request}',
        cp_memo='{$memo_sql}', cp_order='{$cp_order}', cp_payload='{$json_sql}'
        WHERE cp_id='{$cp_id}'");
} else {
    sql_query("INSERT INTO `{$g5['calc_preset_table']}` SET
        cp_name='{$name_sql}', cp_use='{$cp_use}', cp_use_request='{$cp_use_request}',
        cp_memo='{$memo_sql}', cp_order='{$cp_order}', cp_payload='{$json_sql}',
        cp_datetime='".G5_TIME_YMDHIS."'");
}

goto_url(G5_ADMIN_URL.'/calc_preset_list.php');
