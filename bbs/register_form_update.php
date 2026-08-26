<?php
include_once('./_common.php');
include_once(G5_LIB_PATH.'/register.lib.php');

// 리퍼러 체크
referer_check();


if(!$is_add_register) {
	// 회원가입 기간 경과
	alert('계정생성 기간이 아닙니다.');
}

if (!($w == '' || $w == 'u')) {
    alert('w 값이 제대로 넘어오지 않았습니다.');
}

if ($w == 'u' && $is_admin) {
    if (file_exists(G5_PATH.'/DEMO'))
        alert('데모 화면에서는 하실(보실) 수 없는 작업입니다.');
}

if($w == 'u')
    $mb_id = isset($_SESSION['ss_mb_id']) ? trim($_SESSION['ss_mb_id']) : '';
else if($w == '')
    $mb_id = trim($_POST['mb_id']);
else
    alert('잘못된 접근입니다', G5_URL);

if(!$mb_id)
    alert('회원아이디 값이 없습니다. 올바른 방법으로 이용해 주십시오.');

$mb_password    = trim($_POST['mb_password']);
$mb_password_re = trim($_POST['mb_password_re']);
$mb_name        = trim($_POST['mb_name']);
$mb_birth = trim($_POST['mb_birth'] ?? "");

// avo: 생년 모드에 따른 정규화
if (function_exists('register_birth_mode')) {
    $bm = register_birth_mode();
    if ($bm === 'adult_check') {
        // 체크 시 mb_birth='1945-08-15' (성인 표식), 미체크 시 빈 값
        $checked = !empty($_POST['mb_adult_check']);
        $mb_birth = $checked ? '1945-08-15' : '';
        if ($w == '' && !$checked) {
            alert('체크박스를 선택해 주십시오.', '', true, true);
        }
    } elseif ($bm === 'none') {
        // 입력받지 않음 — 신규 가입 시 빈 값, 수정 시 기존 값 유지
        if ($w == '') $mb_birth = '';
    }else{
        $mb_birth = preg_replace('/\D/', '', $mb_birth);
        if (strlen($mb_birth) >= 4) $mb_birth = substr($mb_birth, 0, 4);
    }
}

$mb_name        = clean_xss_tags($mb_name);
$mb_nick        = $mb_name;

// avo: 닉네임 길이/문자 제한 검증
if (function_exists('register_validate_nick')) {
    $nick_msg = register_validate_nick($mb_nick);
    if ($nick_msg !== '') alert($nick_msg, '', true, true);
}

if ($w == '' || $w == 'u') {

    if ($msg = empty_mb_id($mb_id))         alert($msg, "", true, true); 
    if ($msg = valid_mb_id($mb_id))         alert($msg, "", true, true);
    if ($msg = count_mb_id($mb_id))         alert($msg, "", true, true);

    // 이름, 닉네임에 utf-8 이외의 문자가 포함됐다면 오류
    // 서버환경에 따라 정상적으로 체크되지 않을 수 있음.
    $tmp_mb_nick = iconv('UTF-8', 'UTF-8//IGNORE', $mb_nick);
    if($tmp_mb_nick != $mb_nick) {
        alert('닉네임을 올바르게 입력해 주십시오.');
    }

    if ($w == '' && !$mb_password)
        alert('비밀번호가 넘어오지 않았습니다.');
    if($w == '' && $mb_password != $mb_password_re)
        alert('비밀번호가 일치하지 않습니다.');

    if ($msg = empty_mb_name($mb_name))       alert($msg, "", true, true);
    if ($msg = empty_mb_nick($mb_nick))     alert($msg, "", true, true);

    if ($w=='') {
        if ($msg = exist_mb_id($mb_id))     alert($msg);
        if ($msg = reserve_mb_id($mb_id))   alert($msg, "", true, true);
        if ($msg = reserve_mb_nick($mb_nick)) alert($msg, "", true, true);
    } else {
        $mb_nick = $member['mb_nick'];
    }

    if ($msg = exist_mb_nick($mb_nick, $mb_id))     alert($msg, "", true, true);
}

if (function_exists('register_extra_validate')) {
    register_extra_validate();
}

// 사용자 코드 실행
@include_once($member_skin_path.'/register_form_update.head.skin.php');

if ($w == '') {
    $sql = " insert into {$g5['member_table']}
                set mb_id = '{$mb_id}',
                     mb_password = '".get_encrypt_string($mb_password)."',
                     mb_name = '{$mb_name}',
                     mb_nick = '{$mb_nick}',
                     mb_birth = '{$mb_birth}',
                     mb_today_login = '".G5_TIME_YMDHIS."',
                     mb_datetime = '".G5_TIME_YMDHIS."',
                     mb_ip = '{$_SERVER['REMOTE_ADDR']}',
                     mb_level = '{$config['cf_register_level']}'";

    sql_query($sql);

    // avo: 추가 입력폼 저장 (cf_register_extra_fields)
    if (function_exists('register_extra_save')) {
        register_extra_save($mb_id);
    }

    // 회원가입 포인트 부여
    insert_point($mb_id, $config['cf_register_point'], '회원가입 축하', '@member', $mb_id, '회원가입');

    set_session('ss_mb_reg', $mb_id);

    if (function_exists('mdl_do_action')) mdl_do_action('member_register_after', $mb_id, $_POST);

} else if ($w == 'u') {
    if (!trim($_SESSION['ss_mb_id']))
        alert('로그인 되어 있지 않습니다.');

    if (trim($_POST['mb_id']) != $mb_id)
        alert("로그인된 정보와 수정하려는 정보가 틀리므로 수정할 수 없습니다.\\n만약 올바르지 않은 방법을 사용하신다면 바로 중지하여 주십시오.");

    $sql_password = "";
    if ($mb_password)
        $sql_password = " , mb_password = '".get_encrypt_string($mb_password)."' ";

    $sql = " update {$g5['member_table']}
                set mb_name = '{$mb_name}',
					mb_nick = '{$mb_nick}',
                    mb_birth = '{$mb_birth}'
                    {$sql_password}
              where mb_id = '$mb_id' ";
    sql_query($sql);

    // avo: 추가 입력폼 저장 (cf_register_extra_fields)
    if (function_exists('register_extra_save')) {
        register_extra_save($mb_id);
    }
}



$msg = "";

// 사용자 코드 실행
@include_once ($member_skin_path.'/register_form_update.tail.skin.php');

if ($msg)
    echo '<script>alert(\''.$msg.'\');</script>';

if ($w == '') {
    goto_url(G5_HTTP_BBS_URL.'/register_result.php');
} else if ($w == 'u') {
    $row  = sql_fetch(" select mb_password from {$g5['member_table']} where mb_id = '{$member['mb_id']}' ");
    $tmp_password = $row['mb_password'];

    echo '
    <!doctype html>
    <html lang="ko">
    <head>
    <meta charset="utf-8">
    <title>회원정보수정</title>
    <body>
    <form name="fregisterupdate" method="post" action="'.G5_HTTP_BBS_URL.'/register_form.php">
    <input type="hidden" name="w" value="u">
    <input type="hidden" name="mb_id" value="'.$mb_id.'">
    <input type="hidden" name="mb_password" value="'.$tmp_password.'">
    <input type="hidden" name="is_update" value="1">
    </form>
    <script>
    alert("회원 정보가 수정 되었습니다.");
    document.fregisterupdate.submit();
    </script>
    </body>
    </html>';
    
}
?>
