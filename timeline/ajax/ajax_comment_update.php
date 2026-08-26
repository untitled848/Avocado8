<?php
/**
 * 타임라인 댓글(답글) 수정 AJAX (레거시)
 * 실제 처리는 update_timeline_post()로 통일 — wr_thumb/wr_name/로그 미변경
 *
 * POST: comment_id, content, (optional) wr_img_hidden, wr_text_hidden, wr_waring_text, keep_images[], images[]
 */
include_once(__DIR__.'/../_common.php');

$my_ch_id = ses($character, 'ch_id', 0, 'int');

header('Content-Type: application/json');

$result = array('success' => false, 'message' => '');

if (!$is_member || !$my_ch_id) {
    $result['message'] = '로그인이 필요합니다.';
    echo json_encode($result);
    exit;
}

$comment_id = ses($_POST, 'comment_id', 0, 'int');
if (!$comment_id) {
    $comment_id = ses($_POST, 'wr_id', 0, 'int');
}
$content = ses($_POST, 'content', '', 'raw');

if (!$comment_id) {
    $result['message'] = '댓글 정보가 없습니다.';
    echo json_encode($result);
    exit;
}

$comment = sql_fetch("SELECT * FROM {$write_table} WHERE wr_id = '{$comment_id}' AND wr_deleted = 0");
if (!$comment) {
    $result['message'] = '댓글을 찾을 수 없습니다.';
    echo json_encode($result);
    exit;
}

$keep_images = array();
if (isset($_POST['keep_images'])) {
    $keep_images = is_array($_POST['keep_images']) ? $_POST['keep_images'] : array($_POST['keep_images']);
} else {
    // keep 미전달 시 기존 이미지 유지
    for ($i = 1; $i <= 4; $i++) {
        $fn = ses($comment, 'wr_image'.$i, '', 'raw');
        if ($fn !== '') $keep_images[] = $fn;
    }
}

$max_images = ses($timeline, 'tl_image_count', 4, 'int');
if ($max_images < 1) $max_images = 4;
if ($max_images > 4) $max_images = 4;
$allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
$uploaded = array();
$upload_dir = G5_TIMELINE_PATH.'/'.$tl_table;
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}

$remain = max(0, $max_images - count($keep_images));
if (isset($_FILES['images']) && is_array($_FILES['images']['name']) && $remain > 0) {
    $file_count = min(count($_FILES['images']['name']), $remain);
    for ($i = 0; $i < $file_count; $i++) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext, true)) continue;
        $filename = $my_ch_id.'_'.time().'_'.mt_rand(1000, 9999).'_ce'.$i.'.'.$ext;
        if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $upload_dir.'/'.$filename)) {
            $uploaded[] = $filename;
        }
    }
}

$result = update_timeline_post($tl_table, $comment_id, $my_ch_id, array(
    'content' => $content,
    'keep_images' => $keep_images,
    'new_images' => $uploaded,
    'wr_img_hidden' => ses($_POST, 'wr_img_hidden', (int)ses($comment, 'wr_img_hidden', 0, 'int'), 'int'),
    'wr_text_hidden' => ses($_POST, 'wr_text_hidden', (int)ses($comment, 'wr_text_hidden', 0, 'int'), 'int'),
    'wr_waring_text' => ses($_POST, 'wr_waring_text', ses($comment, 'wr_waring_text', '', 'raw'), 'string'),
), $is_admin);

echo json_encode($result);
