<?php
include_once('./_common.php');
if (function_exists('perm_check_page')) perm_check_page('memo');

if ($is_guest)
    alert('회원만 이용하실 수 있습니다.');


$recv_list = explode(',', trim($_POST['me_recv_mb_id']));
$str_nick_list = '';
$msg = '';
$error_list  = array();
$member_list = array();
for ($i=0; $i<count($recv_list); $i++) {
    $row = sql_fetch("select mb_id, mb_nick, mb_leave_date, mb_intercept_date from {$g5['member_table']} where mb_id = '{$recv_list[$i]}' ");
    if ($row) {
        if (!$row['mb_leave_date'] && !$row['mb_intercept_date']) {
            $member_list['id'][]   = $row['mb_id'];
            $member_list['nick'][] = $row['mb_nick'];
        } else {
            $error_list[]   = $recv_list[$i];
        }
    }

}

$error_msg = implode(",", $error_list);

if ($error_msg && !$is_admin)
    alert("회원아이디 '{$error_msg}' 은(는) 존재하지 않는 회원이거나 탈퇴, 접근차단된 회원아이디 입니다.\\n쪽지를 발송하지 않았습니다.");

/*if (!$is_admin) {
    if (count($member_list['id'])) {
        $point = 0;
        if ($point) {
            if ($member['mb_point'] - $point < 0) {
                alert('보유하신 포인트('.number_format($member['mb_point']).'점)가 모자라서 쪽지를 보낼 수 없습니다.');
            }
        }
    }
}*/

for ($i=0; $i<count($member_list['id']); $i++) {
    $recv_mb_id = $member_list['id'][$i];
    send_memo($member['mb_id'], $recv_mb_id, $_POST['me_memo']);
}

if ($member_list) {
    $str_nick_list = implode(',', $member_list['nick']);
    alert($str_nick_list." 님께 쪽지를 전달하였습니다.", G5_HTTP_BBS_URL."/memo.php?kind=send", false);
} else {
    alert("회원아이디 오류 같습니다.", G5_HTTP_BBS_URL."/memo_form.php", false);
}
?>