<?php
$sub_menu = "310200";
include_once('./_common.php');

check_admin_token();

$w = $_POST['w'] ?? '';
$no_id = (int)($_POST['no_id'] ?? 0);

// G5_ESCAPE_FUNCTION 이 $_POST 전체에 addslashes 를 사전 적용하므로,
// 문자열 필드는 stripslashes 로 되돌린 뒤 sql_escape_string 을 한 번만 적용한다.
$no_subject = trim(stripslashes((string)($_POST['no_subject'] ?? '')));
$no_subtext = trim(stripslashes((string)($_POST['no_subtext'] ?? '')));
$no_content = stripslashes((string)($_POST['no_content'] ?? ''));
$no_pinned  = !empty($_POST['no_pinned']) ? 1 : 0;
$no_widget  = !empty($_POST['no_widget']) ? 1 : 0;
$no_use     = isset($_POST['no_use']) && (int)$_POST['no_use'] === 1 ? 1 : 0;
$no_order   = (int)($_POST['no_order'] ?? 0);
$no_html    = !empty($_POST['no_html']) ? 1 : 0;
$no_skin    = trim(stripslashes((string)($_POST['no_skin'] ?? '')));
$nc_name    = trim(stripslashes((string)($_POST['nc_name'] ?? '')));

if ($no_subject === '') alert('제목을 입력하세요.');
if (trim(strip_tags($no_content)) === '') alert('내용을 입력하세요.');

// 시기 직렬화 (배너의 shop_time_to_db 재사용)
$nt_input = $_POST['no_time'] ?? array();
$no_time  = function_exists('shop_time_to_db') ? shop_time_to_db($nt_input) : '';

$mb_id_e      = sql_escape_string(isset($member['mb_id']) ? $member['mb_id'] : '');
$no_subj_e    = sql_escape_string($no_subject);
$no_subtext_e = sql_escape_string($no_subtext);
// CDN: 본문 토큰화
if (function_exists('cdn_tokenize_content')) $no_content = cdn_tokenize_content($no_content);
$no_content_e = sql_escape_string($no_content);
$no_time_e    = sql_escape_string($no_time);
$no_skin_e    = sql_escape_string($no_skin);
$nc_name_e    = sql_escape_string($nc_name);

// ── 썸네일 파일 업로드 ────────────────────────────────
$no_thumb_set = '';  // SET 절에 추가할 문자열. 변경 없으면 빈 채로 둠.

$notice_data_path = G5_DATA_PATH . '/notice';
if (!is_dir($notice_data_path)) @mkdir($notice_data_path, G5_DIR_PERMISSION, true);

// 기존 썸네일 (수정 모드)
$old_thumb = '';
if ($w === 'u' && $no_id > 0) {
    $old_row   = sql_fetch("SELECT no_thumb FROM `{$g5['notice_table']}` WHERE no_id = '{$no_id}'", false);
    $old_thumb = $old_row ? (string)($old_row['no_thumb'] ?? '') : '';
}

// 삭제 요청
if (!empty($_POST['no_thumb_delete'])) {
    if ($old_thumb) {
        $old_local = str_replace(
            defined('CDN_TOKEN') ? CDN_TOKEN : '[DATA_URL]',
            G5_DATA_PATH,
            $old_thumb
        );
        if ($old_local !== $old_thumb && is_file($old_local)) @unlink($old_local);
    }
    $no_thumb_set = ", no_thumb = ''";
}

// 새 파일 업로드
if (!empty($_FILES['no_thumb_file']['name']) && $_FILES['no_thumb_file']['error'] === UPLOAD_ERR_OK) {
    $ext_allowed = array('jpg','jpeg','png','gif','webp');
    $ext = strtolower(pathinfo($_FILES['no_thumb_file']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $ext_allowed)) {
        // 기존 파일 삭제
        if ($old_thumb) {
            $old_local = str_replace(
                defined('CDN_TOKEN') ? CDN_TOKEN : '[DATA_URL]',
                G5_DATA_PATH,
                $old_thumb
            );
            if ($old_local !== $old_thumb && is_file($old_local)) @unlink($old_local);
        }
        $img_name = 'notice_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
        if (upload_file($_FILES['no_thumb_file']['tmp_name'], $img_name, $notice_data_path)) {
            $img_url = G5_DATA_URL . '/notice/' . $img_name;
            if (function_exists('cdn_tokenize_url'))  $img_url = cdn_tokenize_url($img_url);
            if (function_exists('cdn_after_upload'))  cdn_after_upload($notice_data_path . '/' . $img_name);
            $no_thumb_set = ", no_thumb = '" . sql_escape_string($img_url) . "'";
        }
    }
}

if ($w === 'u' && $no_id > 0) {
    $exist = sql_fetch("SELECT no_id FROM `{$g5['notice_table']}` WHERE no_id = '{$no_id}'", false);
    if (!$exist) alert('등록된 공지가 없습니다.');

    sql_query("
        UPDATE `{$g5['notice_table']}` SET
            no_subject = '{$no_subj_e}',
            no_subtext = '{$no_subtext_e}',
            no_content = '{$no_content_e}',
            no_html    = '{$no_html}',
            no_time    = '{$no_time_e}',
            no_use     = '{$no_use}',
            no_pinned  = '{$no_pinned}',
            no_widget  = '{$no_widget}',
            no_order   = '{$no_order}',
            no_skin    = '{$no_skin_e}',
            nc_name    = '{$nc_name_e}'
            {$no_thumb_set}
        WHERE no_id = '{$no_id}'
    ", false);
} else {
    $now = G5_TIME_YMDHIS;
    // no_thumb_set 은 ", no_thumb='...'" 형태 — INSERT 에서는 앞 콤마를 제거
    $thumb_insert = $no_thumb_set ? ltrim($no_thumb_set, ', ') . ',' : '';
    sql_query("
        INSERT INTO `{$g5['notice_table']}` SET
            no_subject  = '{$no_subj_e}',
            no_subtext  = '{$no_subtext_e}',
            no_content  = '{$no_content_e}',
            no_html     = '{$no_html}',
            no_time     = '{$no_time_e}',
            mb_id       = '{$mb_id_e}',
            no_use      = '{$no_use}',
            no_pinned   = '{$no_pinned}',
            no_widget   = '{$no_widget}',
            no_order    = '{$no_order}',
            no_skin     = '{$no_skin_e}',
            nc_name     = '{$nc_name_e}',
            {$thumb_insert}
            no_datetime = '{$now}'
    ", false);
    $no_id = (int)sql_insert_id();
}

goto_url('./notice_form.php?w=u&no_id='.$no_id);
?>
