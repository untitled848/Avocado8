<?php
/**
 * 타임라인 스크립트 등록/수정 처리
 */
include_once('./_common.php');

$my_ch_id = ses($character, 'ch_id', 0, 'int');
if (!$is_member || !$my_ch_id) {
    alert('로그인이 필요합니다.');
}
if (!can_write_timeline($timeline, $my_ch_id, $is_admin)) {
    alert('글쓰기 권한이 없습니다.');
}

$wr_id = ses($_POST, 'wr_id', 0, 'int');
$is_edit = ($wr_id > 0);

$content = isset($_POST['wr_content']) ? substr(trim($_POST['wr_content']), 0, 65536) : '';
$content = preg_replace("#[\\\]+$#", "", $content);
// G5 common.php 가 $_POST 에 addslashes 함 → insert/update 쪽에서 stripslashes 후 재이스케이프
$content_plain = stripslashes($content);
$content_check = strtolower(preg_replace('/\s+/', '', $content_plain));
$empty_vals = array('', '&nbsp;', '<p>&nbsp;</p>', '<p><br></p>', '<div><br></div>', '<p></p>', '<br>');
if (in_array($content_check, $empty_vals, true) || trim(strip_tags($content_plain)) === '') {
    // 이미지만 있는 경우는 허용
    if (!preg_match('/<img\b/i', $content_plain)) {
        alert('내용을 입력해 주세요.');
    }
}

if ($is_edit) {
    $post = sql_fetch("SELECT * FROM {$write_table} WHERE wr_id = '{$wr_id}' AND wr_deleted = 0");
    if (!$post || empty($post['wr_id'])) {
        alert('글을 찾을 수 없습니다.');
    }
    if (!can_edit_timeline_post($post, $my_ch_id, $is_admin)) {
        alert('수정 권한이 없습니다.');
    }
    if (!function_exists('timeline_row_is_script') || !timeline_row_is_script($post)) {
        alert('스크립트 글만 이 화면에서 수정할 수 있습니다.');
    }

    $keep_images = array();
    for ($i = 1; $i <= 4; $i++) {
        $fn = ses($post, 'wr_image'.$i, '', 'raw');
        if ($fn !== '') $keep_images[] = $fn;
    }

    $result = update_timeline_post($tl_table, $wr_id, $my_ch_id, array(
        'content' => $content_plain,
        'keep_images' => $keep_images,
        'new_images' => array(),
        'is_script' => 1,
        'wr_script' => 1,
        'wr_img_hidden' => (int)ses($post, 'wr_img_hidden', 0, 'int'),
        'wr_text_hidden' => (int)ses($post, 'wr_text_hidden', 0, 'int'),
        'wr_waring_text' => ses($post, 'wr_waring_text', '', 'raw'),
    ), $is_admin);

    if (empty($result['success'])) {
        alert(ses($result, 'message', '수정에 실패했습니다.', 'raw'));
    }

    // 스크립트 플래그 유지
    if (function_exists('table_has_column') && table_has_column($write_table, 'wr_script')) {
        sql_query("UPDATE {$write_table} SET wr_script = 1 WHERE wr_id = '{$wr_id}'");
    }
} else {
    $result = insert_timeline_post($tl_table, $my_ch_id, $content, array(
        'is_script' => 1,
        'wr_script' => 1,
    ));

    if (empty($result['success'])) {
        alert(ses($result, 'message', '등록에 실패했습니다.', 'raw'));
    }
}

$goto = './index.php?tl_table='.urlencode($tl_table);
echo '<script>
if (window.opener && !window.opener.closed) {
    try {
        if (typeof window.opener.loadTimeline === "function") {
            window.opener.loadTimeline(true);
        } else {
            window.opener.location.href = '.json_encode($goto).';
        }
    } catch (e) {
        window.opener.location.reload();
    }
    window.close();
} else {
    location.href = '.json_encode($goto).';
}
</script>';
exit;
