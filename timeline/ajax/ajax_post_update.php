<?php
/**
 * 타임라인 글/댓글 수정 AJAX
 * 허용: wr_content, 이미지, 가림/경고문구
 * wr_thumb / wr_name / wr_log 등은 변경하지 않음
 */
include_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');

$my_ch_id = ses($character, 'ch_id', 0, 'int');
if (!$is_member || !$my_ch_id) {
    echo json_encode(array('success' => false, 'message' => '로그인이 필요합니다.'));
    exit;
}

$wr_id = ses($_POST, 'wr_id', 0, 'int');
$content = ses($_POST, 'content', '', 'raw');
$wr_img_hidden = ses($_POST, 'wr_img_hidden', 0, 'int') ? 1 : 0;
$wr_text_hidden = ses($_POST, 'wr_text_hidden', 0, 'int') ? 1 : 0;
$wr_waring_text = ses($_POST, 'wr_waring_text', '', 'string');

$keep_images = array();
if (isset($_POST['keep_images'])) {
    if (is_array($_POST['keep_images'])) {
        $keep_images = $_POST['keep_images'];
    } else {
        $keep_images = array($_POST['keep_images']);
    }
}

if (!$wr_id) {
    echo json_encode(array('success' => false, 'message' => '잘못된 요청입니다.'));
    exit;
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

$keep_count = 0;
foreach ($keep_images as $fn) {
    if (basename((string)$fn) !== '') $keep_count++;
}
$remain_slots = max(0, $max_images - $keep_count);

if (isset($_FILES['images']) && is_array($_FILES['images']['name']) && $remain_slots > 0) {
    $file_count = min(count($_FILES['images']['name']), $remain_slots);
    for ($i = 0; $i < $file_count; $i++) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext, true)) continue;
        $filename = $my_ch_id.'_'.time().'_'.mt_rand(1000, 9999).'_e'.$i.'.'.$ext;
        $filepath = $upload_dir.'/'.$filename;
        if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $filepath)) {
            $uploaded[] = $filename;
        }
    }
}

$result = update_timeline_post($tl_table, $wr_id, $my_ch_id, array(
    'content' => $content,
    'keep_images' => $keep_images,
    'new_images' => $uploaded,
    'wr_img_hidden' => $wr_img_hidden,
    'wr_text_hidden' => $wr_text_hidden,
    'wr_waring_text' => $wr_waring_text,
), $is_admin);

if (!$result['success']) {
    echo json_encode($result);
    exit;
}

// 목록 갱신용 HTML (원글/댓글 공통 item.skin)
$write = get_timeline_post($tl_table, $wr_id, $my_ch_id);
$html = '';
if ($write && empty($result['is_comment']) && file_exists($timeline_skin_path.'/item.skin.php')) {
    // get_timeline_post가 댓글이면 원글+스레드를 반환할 수 있음 → 단건 재조회
}
$row = sql_fetch("
    SELECT w.*, c.ch_name,
           (SELECT COUNT(*) FROM {$g5['timeline_good_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$my_ch_id}') AS my_like,
           (SELECT COUNT(*) FROM {$g5['timeline_bookmark_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$my_ch_id}') AS my_bookmark
    FROM {$write_table} w
    LEFT JOIN {$g5['character_table']} c ON c.ch_id = w.ch_id
    WHERE w.wr_id = '{$wr_id}'
");

if ($row && file_exists($timeline_skin_path.'/item.skin.php')) {
    ob_start();
    $wr_id = (int)$row['wr_id'];
    $ch_id = (int)$row['ch_id'];
    $ch_name = h(get_timeline_row_name($row));
    $ch_image = get_timeline_row_thumb($row);
    $content = render_timeline_content(ses($row, 'wr_content', '', 'raw'), $tl_table);
    $content_raw = ses($row, 'wr_content', '', 'raw');
    $time_ago = get_time_ago(ses($row, 'wr_datetime', '', 'raw'));
    $datetime = ses($row, 'wr_datetime', '', 'raw');
    $is_mine = ($ch_id == $my_ch_id);
    $is_reply = ((int)ses($row, 'wr_parent', 0, 'int') > 0);
    $my_like = !empty($row['my_like']);
    $my_bookmark = !empty($row['my_bookmark']);
    $like_count = (int)ses($row, 'wr_like_count', 0, 'int');
    $comment_count = (int)ses($row, 'wr_comment', 0, 'int');
    $images = array();
    for ($i = 1; $i <= 4; $i++) {
        $img = ses($row, 'wr_image'.$i, '', 'raw');
        if ($img && is_file(G5_TIMELINE_PATH.'/'.$tl_table.'/'.$img)) {
            $images[] = G5_TIMELINE_URL.'/'.$tl_table.'/'.$img;
        }
    }
    $profile_url = G5_URL.'/timeline/profile.php?tl_table='.$tl_table.'&ch_id='.$ch_id;
    $view_url = G5_URL.'/timeline/view.php?tl_table='.$tl_table.'&wr_id='.$wr_id;
    $open_post_id = $wr_id;
    $wr_log = ses($row, 'wr_log', '', 'raw');
    $wr_item_log = ses($row, 'wr_item_log', '', 'raw');
    $wr_dice = ses($row, 'wr_dice', '', 'raw');
    $wr_dice1 = ses($row, 'wr_dice1', 0, 'int');
    $wr_dice2 = ses($row, 'wr_dice2', 0, 'int');
    $wr_reply_to = ses($row, 'wr_reply_to', 0, 'int');
    $wr_parent = ses($row, 'wr_parent', 0, 'int');
    include($timeline_skin_path.'/item.skin.php');
    $html = ob_get_clean();
}

$result['html'] = $html;
echo json_encode($result);
