<?php
/**
 * 타임라인 글/댓글 수정용 데이터 조회
 */
include_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');

$my_ch_id = ses($character, 'ch_id', 0, 'int');
if (!$is_member || !$my_ch_id) {
    echo json_encode(array('success' => false, 'message' => '로그인이 필요합니다.'));
    exit;
}

$wr_id = ses($_GET, 'wr_id', 0, 'int');
if (!$wr_id) {
    echo json_encode(array('success' => false, 'message' => '잘못된 요청입니다.'));
    exit;
}

$post = sql_fetch("SELECT * FROM {$write_table} WHERE wr_id = '{$wr_id}' AND wr_deleted = 0");
if (!$post || empty($post['wr_id'])) {
    echo json_encode(array('success' => false, 'message' => '글을 찾을 수 없습니다.'));
    exit;
}

if (!can_edit_timeline_post($post, $my_ch_id, $is_admin)) {
    echo json_encode(array('success' => false, 'message' => '수정 권한이 없습니다.'));
    exit;
}

$images = array();
for ($i = 1; $i <= 4; $i++) {
    $fn = ses($post, 'wr_image'.$i, '', 'raw');
    if ($fn === '') continue;
    $path = G5_TIMELINE_PATH.'/'.$tl_table.'/'.$fn;
    if (!is_file($path)) continue;
    $images[] = array(
        'name' => $fn,
        'url'  => G5_TIMELINE_URL.'/'.$tl_table.'/'.$fn,
    );
}

$content_max = ses($timeline, 'tl_content_max', 500, 'int');
if (!$content_max) $content_max = 500;
$image_max = ses($timeline, 'tl_image_count', 4, 'int');
if ($image_max < 1) $image_max = 4;
if ($image_max > 4) $image_max = 4;

$wr_script = (function_exists('timeline_row_is_script') && timeline_row_is_script($post))
    ? 1
    : ((int)ses($post, 'wr_script', 0, 'int') ? 1 : 0);

echo json_encode(array(
    'success' => true,
    'wr_id' => (int)$post['wr_id'],
    'wr_content' => ses($post, 'wr_content', '', 'raw'),
    'wr_script' => $wr_script,
    'wr_img_hidden' => (int)ses($post, 'wr_img_hidden', 0, 'int') ? 1 : 0,
    'wr_text_hidden' => (int)ses($post, 'wr_text_hidden', 0, 'int') ? 1 : 0,
    'wr_waring_text' => ses($post, 'wr_waring_text', '', 'raw'),
    'images' => $images,
    'content_max' => $content_max,
    'image_max' => $image_max,
    'use_image' => (int)ses($timeline, 'tl_use_image', 0, 'int') ? 1 : 0,
    'is_comment' => (int)ses($post, 'wr_is_comment', 0, 'int') ? 1 : 0,
    'parent_id' => (int)ses($post, 'wr_parent', 0, 'int'),
));
