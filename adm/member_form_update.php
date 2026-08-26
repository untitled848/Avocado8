<?php
$sub_menu = "200100";
include_once("./_common.php");
include_once(G5_LIB_PATH."/register.lib.php");

if ($w == 'u')
    check_demo();

auth_check($auth[$sub_menu], 'w');

check_admin_token();

$mb_id = trim($_POST['mb_id']);


$_POST['mb_nick'] = $_POST['mb_name'];
$_post_mb_birth = isset($_POST['mb_birth']) ? trim((string)$_POST['mb_birth']) : '';

// member_form.php 에서 전송하는 항목만 포함
$sql_common = "  mb_name = '{$_POST['mb_name']}',
                 mb_nick = '{$_POST['mb_nick']}',
                 mb_birth = '{$_post_mb_birth}',
                 mb_leave_date = '{$_POST['mb_leave_date']}',
                 mb_intercept_date = '{$_POST['mb_intercept_date']}',
                 mb_memo = '{$_POST['mb_memo']}',
                 mb_level = '{$_POST['mb_level']}',
                 mb_1 = '{$_POST['mb_1']}',
                 mb_2 = '{$_POST['mb_2']}',
                 mb_3 = '{$_POST['mb_3']}',
                 mb_4 = '{$_POST['mb_4']}',
                 mb_5 = '{$_POST['mb_5']}',
                 mb_6 = '{$_POST['mb_6']}',
                 mb_7 = '{$_POST['mb_7']}',
                 mb_8 = '{$_POST['mb_8']}',
                 mb_9 = '{$_POST['mb_9']}',
                 mb_10 = '{$_POST['mb_10']}' ";

if ($w == '')
{
    $mb = get_member($mb_id);
    if ($mb['mb_id'])
        alert('이미 존재하는 회원아이디입니다.\\nＩＤ : '.$mb['mb_id'].'\\n이름 : '.$mb['mb_name'].'\\n닉네임 : '.$mb['mb_nick']);

    // 닉네임중복체크
    $sql = " select mb_id, mb_name, mb_nick from {$g5['member_table']} where mb_nick = '{$_POST['mb_nick']}' ";
    $row = sql_fetch($sql);
    if ($row['mb_id'])
        alert('이미 존재하는 닉네임입니다.\\nＩＤ : '.$row['mb_id'].'\\n이름 : '.$row['mb_name'].'\\n닉네임 : '.$row['mb_nick']);

    sql_query(" insert into {$g5['member_table']} set mb_id = '{$mb_id}', mb_password = '".get_encrypt_string($mb_password)."', mb_datetime = '".G5_TIME_YMDHIS."', mb_ip = '{$_SERVER['REMOTE_ADDR']}', {$sql_common} ");
}
else if ($w == 'u')
{
    $mb = get_member($mb_id);
    if (!$mb['mb_id'])
        alert('존재하지 않는 회원자료입니다.');

    if (!$is_admin && $mb['mb_level'] >= $member['mb_level'])
        alert('자신보다 권한이 높거나 같은 회원은 수정할 수 없습니다.');

    if ($_POST['mb_id'] == $member['mb_id'] && $_POST['mb_level'] != $mb['mb_level'])
        alert($mb['mb_id'].' : 로그인 중인 관리자 레벨은 수정 할 수 없습니다.');

    // 닉네임중복체크
    $sql = " select mb_id, mb_name, mb_nick from {$g5['member_table']} where mb_nick = '{$_POST['mb_nick']}' and mb_id <> '{$mb_id}' ";
    $row = sql_fetch($sql);
    if ($row['mb_id'])
        alert('이미 존재하는 닉네임입니다.\\nＩＤ : '.$row['mb_id'].'\\n이름 : '.$row['mb_name'].'\\n닉네임 : '.$row['mb_nick']);


    if ($mb_password)
        $sql_password = " , mb_password = '".get_encrypt_string($mb_password)."' ";
    else
        $sql_password = "";

    $sql = " update {$g5['member_table']}
                set {$sql_common}
                     {$sql_password}
                where mb_id = '{$mb_id}' ";
    sql_query($sql);
}
else
    alert('제대로 된 값이 넘어오지 않았습니다.');

// avo: 닉네임 검증 (등록/수정 공통)
if (function_exists('register_validate_nick')) {
    $_nick_msg = register_validate_nick($_POST['mb_nick'] ?? '');
    if ($_nick_msg !== '') alert($_nick_msg);
}

// avo: 추가 입력폼 저장 (cf_register_extra_fields) — 등록/수정 공통
if (function_exists('register_extra_save')) {
    register_extra_save($mb_id);
}

goto_url('./member_form.php?'.$qstr.'&amp;w=u&amp;mb_id='.$mb_id, false);
?>