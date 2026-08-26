<?php
$sub_menu = "400100";
include_once('./_common.php');

check_token();

$ar_code   = trim($_POST['ar_code']   ?? '');
// 공식 고유코드 규칙: 영문 소문자 / 숫자 / 언더스코어(_) 만
$ar_code   = preg_replace('/[^A-Za-z_]/', '', $ar_code);
$ar_name   = trim($_POST['ar_name']   ?? '');
$ar_type   = trim($_POST['ar_type']   ?? '');
$ar_size   = trim($_POST['ar_size']   ?? '');
$ar_text   = $_POST['ar_text']        ?? '';
$ar_help   = htmlspecialchars($_POST['ar_help'] ?? '', ENT_QUOTES);
$ar_order  = trim($_POST['ar_order']  ?? '');
$ar_secret = trim($_POST['ar_secret'] ?? '');
$ar_time_db    = function_exists('shop_time_to_db') ? shop_time_to_db($_POST['ar_time'] ?? '') : '';
$ar_min_length = (int)($_POST['ar_min_length'] ?? 0);
$ar_max_length = (int)($_POST['ar_max_length'] ?? 0);
$ar_required   = !empty($_POST['ar_required']) ? 1 : 0;

if ($ar_code === '') {
    alert('고유코드는 필수입니다.', './character_article_list.php');
}

$sql = " insert into {$g5['article_table']}
            set ar_code = '$ar_code',
                ar_name = '$ar_name',
                ar_type = '$ar_type',
                ar_size = '$ar_size',
                ar_text = '$ar_text',
                ar_help = '$ar_help',
                ar_order = '$ar_order',
                ar_secret = '$ar_secret',
                ar_time = '$ar_time_db',
                ar_min_length = '$ar_min_length',
                ar_max_length = '$ar_max_length',
                ar_required = '$ar_required'
";
sql_query($sql);


goto_url('./character_article_list.php?'.$qstr);
?>
