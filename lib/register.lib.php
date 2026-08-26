<?php
if (!defined('_GNUBOARD_')) exit;

function empty_mb_id($reg_mb_id)
{
    if (trim($reg_mb_id)=='')
        return "회원아이디를 입력해 주십시오.";
    else
        return "";
}

function valid_mb_id($reg_mb_id)
{
    if (preg_match("/[^0-9a-z_]+/i", $reg_mb_id))
        return "회원아이디는 영문자, 숫자, _ 만 입력하세요.";
    else
        return "";
}

function count_mb_id($reg_mb_id)
{
    if (strlen($reg_mb_id) < 3)
        return "회원아이디는 최소 3글자 이상 입력하세요.";
    else
        return "";
}

function exist_mb_id($reg_mb_id)
{
    global $g5;

    $reg_mb_id = trim($reg_mb_id);
    if ($reg_mb_id == "") return "";

    $sql = " select count(*) as cnt from `{$g5['member_table']}` where mb_id = '$reg_mb_id' ";
    $row = sql_fetch($sql);
    if ($row['cnt'])
        return "이미 사용중인 회원아이디 입니다.";
    else
        return "";
}

function reserve_mb_id($reg_mb_id)
{
    global $config;
    if (preg_match("/[\,]?{$reg_mb_id}/i", $config['cf_prohibit_id']))
        return "이미 예약된 단어로 사용할 수 없는 회원아이디 입니다.";
    else
        return "";
}

function reserve_mb_nick($reg_mb_nick)
{
    global $config;
    $reg_mb_nick = trim((string)$reg_mb_nick);
    if ($reg_mb_nick === '') return '';
    $list = (string)($config['cf_prohibit_id'] ?? '');
    if ($list === '') return '';
    if (preg_match("/[\,]?".preg_quote($reg_mb_nick, '/')."/i", $list))
        return "이미 예약된 단어로 사용할 수 없는 닉네임 입니다.";
    else
        return "";
}

function empty_mb_nick($reg_mb_nick)
{
    if (!trim($reg_mb_nick))
        return "닉네임을 입력해 주십시오.";
    else
        return "";
}

function valid_mb_nick($reg_mb_nick)
{
    if (!check_string($reg_mb_nick, G5_HANGUL + G5_ALPHABETIC + G5_NUMERIC))
        return "닉네임은 공백없이 한글, 영문, 숫자만 입력 가능합니다.";
    else
        return "";
}

function count_mb_nick($reg_mb_nick)
{
    if (strlen($reg_mb_nick) < 4)
        return "닉네임은 한글 2글자, 영문 4글자 이상 입력 가능합니다.";
    else
        return "";
}

function exist_mb_nick($reg_mb_nick, $reg_mb_id)
{
    global $g5;
    $row = sql_fetch(" select count(*) as cnt from {$g5['member_table']} where mb_nick = '$reg_mb_nick' and mb_id <> '$reg_mb_id' ");
    if ($row['cnt'])
        return "이미 존재하는 닉네임입니다.";
    else
        return "";
}


function empty_mb_name($reg_mb_name)
{
    if (!trim($reg_mb_name))
        return "이름을 입력해 주십시오.";
    else
        return "";
}


return;
?>