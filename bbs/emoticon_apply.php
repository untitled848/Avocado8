<?php
include_once('./_common.php');

if (!$is_member) alert('로그인 후 이용해주세요.', G5_BBS_URL.'/login.php');

check_token();

$apply_mode = isset($config['cf_emoticon_apply_mode']) ? $config['cf_emoticon_apply_mode'] : 'disabled';
if ($apply_mode === 'disabled') alert('이모티콘 신청이 허용되지 않습니다.');

if (empty($character) || ($character['ch_state'] ?? '') !== '승인') {
    alert('승인된 캐릭터를 가진 회원만 신청할 수 있습니다.');
}

$me_text = trim($_POST['me_text'] ?? '');
if ($me_text === '') alert('명령어를 입력하세요.');
if (mb_strlen($me_text) > 200) alert('명령어가 너무 깁니다.');

// 중복 명령어 체크
$me_text_esc = sql_escape_string($me_text);
$dup = sql_fetch("SELECT me_id FROM {$g5['emoticon_table']} WHERE me_text = '{$me_text_esc}'");
if ($dup) alert('이미 등록된 명령어입니다.');

// 이미지 업로드
if (empty($_FILES['me_img']['name'])) alert('이미지를 첨부하세요.');
$img = $_FILES['me_img']['name'];
if (!preg_match("/\.(gif|jpg|jpeg|png|webp)$/i", $img)) alert('gif, jpg, png, webp 파일만 업로드 가능합니다.');
if ($_FILES['me_img']['size'] > 1024 * 1024) alert('이미지 크기는 1MB 이하여야 합니다.');

@mkdir(G5_DATA_PATH.'/emoticon', G5_DIR_PERMISSION);
@chmod(G5_DATA_PATH.'/emoticon', G5_DIR_PERMISSION);

$ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
$emoticon_image_code = time().'_'.mt_rand(100, 999).'.'.$ext;
$emoticon_image_path = G5_DATA_PATH.'/emoticon/'.$emoticon_image_code;
$emoticon_image_url  = '/data/emoticon/'.$emoticon_image_code;

if (!move_uploaded_file($_FILES['me_img']['tmp_name'], $emoticon_image_path)) {
    alert('이미지 업로드 실패.');
}
@chmod($emoticon_image_path, 0606);
if (function_exists('cdn_after_upload')) cdn_after_upload($emoticon_image_path);

$em_use     = ($apply_mode === 'auto') ? 1 : 0;
$mb_name    = sql_escape_string($member['mb_name'] ?: $member['mb_id']);
$me_img_esc = sql_escape_string($emoticon_image_url);

sql_query("INSERT INTO {$g5['emoticon_table']}
    SET me_text     = '{$me_text_esc}',
        me_img      = '{$me_img_esc}',
        em_use      = '{$em_use}',
        em_mb_name  = '{$mb_name}',
        em_datetime = '".G5_TIME_YMDHIS."'");

if ($apply_mode === 'auto') {
    alert('이모티콘이 등록되었습니다. 즉시 사용 가능합니다.', G5_BBS_URL.'/emoticon_list.php');
} else {
    alert('이모티콘이 신청되었습니다. 관리자 승인 후 사용 가능합니다.', G5_BBS_URL.'/emoticon_list.php');
}
