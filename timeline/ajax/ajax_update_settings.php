<?php
/**
 * 타임라인 설정 저장 AJAX
 * timeline_user 프로필 필드 저장
 */
include_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');

// 로그인 체크
if (!$is_member) {
    echo json_encode(array('success' => false, 'message' => '로그인이 필요합니다.'));
    exit;
}

// 캐릭터 체크
$my_ch_id = ses($character, 'ch_id', 0, 'int');
if (!$my_ch_id) {
    echo json_encode(array('success' => false, 'message' => '캐릭터 정보가 없습니다.'));
    exit;
}

if (!$tl_table) {
    echo json_encode(array('success' => false, 'message' => '타임라인이 지정되지 않았습니다.'));
    exit;
}

$tl_safe = sql_escape_string($tl_table);
$mb_id = sql_escape_string(ses($member, 'mb_id', ''));

// POST 데이터
$tu_name = ses($_POST, 'tu_name', '', 'string');
$tu_men_id = ses($_POST, 'tu_men_id', '', 'string');
$tu_bio = ses($_POST, 'tu_bio', '', 'string');
$tu_profile = ses($_POST, 'tu_profile', '', 'string');
// 임시: 스레드 보기 비활성화 — 항상 normal
$tl_render_mode = 'normal';
$thumb_mode = ses($_POST, 'thumb_mode', 'url', 'raw');
$header_mode = ses($_POST, 'header_mode', 'url', 'raw');

// 기존 사용자 행
$tu_exists = sql_fetch("
    SELECT * FROM {$g5['timeline_user_table']}
    WHERE tl_table = '{$tl_safe}' AND ch_id = '{$my_ch_id}'
", false);
if (!is_array($tu_exists)) {
    $tu_exists = array();
}

// 썸네일 이미지 처리
$tu_thumb = '';
$uploaded_thumb_url = '';

if ($thumb_mode === 'file' && isset($_FILES['thumb_file']) && $_FILES['thumb_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['thumb_file'];

    $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        echo json_encode(array('success' => false, 'message' => '허용되지 않는 이미지 형식입니다. (JPG, PNG, GIF, WEBP만 가능)'));
        exit;
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(array('success' => false, 'message' => '파일 크기는 10MB 이하여야 합니다.'));
        exit;
    }

    $upload_dir = G5_DATA_PATH.'/timeline/thumbs';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0755, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) {
        $ext = 'jpg';
    }
    $new_filename = 'thumb_'.$my_ch_id.'_'.time().'.'.$ext;
    $upload_path = $upload_dir.'/'.$new_filename;

    // 기존 썸네일 파일은 유지 (과거 글 wr_thumb 참조용). 새 파일만 추가.
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        if (function_exists('timeline_resize_uploaded_image')) {
            timeline_resize_uploaded_image($upload_path, 800, 800, 85);
        }
        $tu_thumb = G5_DATA_URL.'/timeline/thumbs/'.$new_filename;
        $uploaded_thumb_url = $tu_thumb;
    } else {
        echo json_encode(array('success' => false, 'message' => '파일 업로드에 실패했습니다.'));
        exit;
    }
} else {
    $tu_thumb = ses($_POST, 'tu_thumb', '', 'string');
}

// 헤더 이미지 처리
$tu_header = '';
$uploaded_header_url = '';

if ($header_mode === 'file' && isset($_FILES['header_file']) && $_FILES['header_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['header_file'];

    $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        echo json_encode(array('success' => false, 'message' => '허용되지 않는 이미지 형식입니다. (JPG, PNG, GIF, WEBP만 가능)'));
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(array('success' => false, 'message' => '파일 크기는 5MB 이하여야 합니다.'));
        exit;
    }

    $upload_dir = G5_DATA_PATH.'/timeline/headers';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0755, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) {
        $ext = 'jpg';
    }
    $new_filename = 'header_'.$my_ch_id.'_'.time().'.'.$ext;
    $upload_path = $upload_dir.'/'.$new_filename;

    // 기존 헤더 이미지 삭제 (로컬 업로드분)
    $old_url = ses($tu_exists, 'tu_header', '', 'raw');
    if ($old_url && strpos($old_url, G5_DATA_URL.'/timeline/headers/') === 0) {
        $old_file = str_replace(G5_DATA_URL, G5_DATA_PATH, $old_url);
        if (file_exists($old_file)) {
            @unlink($old_file);
        }
    }

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        $tu_header = G5_DATA_URL.'/timeline/headers/'.$new_filename;
        $uploaded_header_url = $tu_header;
    } else {
        echo json_encode(array('success' => false, 'message' => '파일 업로드에 실패했습니다.'));
        exit;
    }
} else {
    $tu_header = ses($_POST, 'tu_header', '', 'string');
}

// 유효성 검사
if ($tu_name === '') {
    echo json_encode(array('success' => false, 'message' => '닉네임을 입력해주세요.'));
    exit;
}

if (mb_strlen($tu_name) > 50) {
    echo json_encode(array('success' => false, 'message' => '닉네임은 50자 이내여야 합니다.'));
    exit;
}

if ($tu_men_id === '') {
    echo json_encode(array('success' => false, 'message' => '멘션용 아이디를 입력해주세요.'));
    exit;
}

if (preg_match('/\s/u', $tu_men_id)) {
    echo json_encode(array('success' => false, 'message' => '멘션용 아이디에는 띄어쓰기를 넣을 수 없습니다.'));
    exit;
}

if (mb_strlen($tu_men_id) > 6) {
    echo json_encode(array('success' => false, 'message' => '멘션용 아이디는 6자 이내여야 합니다.'));
    exit;
}

// 멘션용 아이디 중복 체크 (같은 타임라인 내)
$dup = sql_fetch("
    SELECT ch_id FROM {$g5['timeline_user_table']}
    WHERE tl_table = '{$tl_safe}'
      AND tu_men_id = '".sql_escape_string($tu_men_id)."'
      AND tu_men_id != ''
      AND ch_id != '{$my_ch_id}'
    LIMIT 1
", false);
if (is_array($dup) && !empty($dup['ch_id'])) {
    echo json_encode(array('success' => false, 'message' => '이미 사용 중인 멘션용 아이디입니다.'));
    exit;
}

if (strlen($tu_thumb) > 255) {
    echo json_encode(array('success' => false, 'message' => '썸네일 이미지 URL이 너무 깁니다.'));
    exit;
}

if (strlen($tu_header) > 255) {
    echo json_encode(array('success' => false, 'message' => '헤더 이미지 URL이 너무 깁니다.'));
    exit;
}

if (strlen($tu_bio) > 500) {
    echo json_encode(array('success' => false, 'message' => '프로필 소개는 500자 이내여야 합니다.'));
    exit;
}

if (strlen($tu_profile) > 255) {
    echo json_encode(array('success' => false, 'message' => '프로필 주소가 너무 깁니다.'));
    exit;
}

if (!in_array($tl_render_mode, array('thread', 'normal'))) {
    $tl_render_mode = 'normal';
}

$tu_name_esc = sql_escape_string($tu_name);
$tu_men_id_esc = sql_escape_string($tu_men_id);
$tu_thumb_esc = sql_escape_string($tu_thumb);
$tu_header_esc = sql_escape_string($tu_header);
$tu_bio_esc = sql_escape_string($tu_bio);
$tu_profile_esc = sql_escape_string($tu_profile);
$tl_render_mode_esc = sql_escape_string($tl_render_mode);

// timeline_user upsert
if (!empty($tu_exists['tl_user_id']) || !empty($tu_exists['ch_id'])) {
    $result = sql_query("
        UPDATE {$g5['timeline_user_table']} SET
            mb_id = '{$mb_id}',
            tu_name = '{$tu_name_esc}',
            tu_men_id = '{$tu_men_id_esc}',
            tu_thumb = '{$tu_thumb_esc}',
            tu_header = '{$tu_header_esc}',
            tu_bio = '{$tu_bio_esc}',
            tu_profile = '{$tu_profile_esc}',
            tl_render_mode = '{$tl_render_mode_esc}'
        WHERE tl_table = '{$tl_safe}' AND ch_id = '{$my_ch_id}'
    ");
} else {
    $result = sql_query("
        INSERT INTO {$g5['timeline_user_table']} SET
            tl_table = '{$tl_safe}',
            mb_id = '{$mb_id}',
            ch_id = '{$my_ch_id}',
            tu_name = '{$tu_name_esc}',
            tu_men_id = '{$tu_men_id_esc}',
            tu_thumb = '{$tu_thumb_esc}',
            tu_header = '{$tu_header_esc}',
            tu_bio = '{$tu_bio_esc}',
            tu_profile = '{$tu_profile_esc}',
            tl_render_mode = '{$tl_render_mode_esc}',
            tl_user_datetime = '".G5_TIME_YMDHIS."'
    ");
}

if ($result) {
    $response = array('success' => true, 'message' => '설정이 저장되었습니다.');
    if ($uploaded_thumb_url) {
        $response['thumb_url'] = $uploaded_thumb_url;
    }
    if ($uploaded_header_url) {
        $response['header_url'] = $uploaded_header_url;
    }
    echo json_encode($response);
} else {
    echo json_encode(array('success' => false, 'message' => '저장에 실패했습니다.'));
}
