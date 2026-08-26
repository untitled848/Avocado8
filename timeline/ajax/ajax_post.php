<?php
/**
 * 타임라인 글 작성 AJAX
 * 게시판 호환 구조
 * MMB와 호환되는 $customer_sql 추가 UPDATE 지원
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

/**
 * JSON 응답 후 종료 (워닝/잡음이 본문을 깨지 않게 버퍼 정리)
 */
function timeline_post_json($payload) {
    $buf = ob_get_contents();
    if ($buf !== false) {
        ob_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    $flags = JSON_UNESCAPED_UNICODE;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
    }
    echo json_encode($payload, $flags);
    exit;
}

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    return true; // 워닝/노티스는 JSON을 깨지 않게 삼킴
});

register_shutdown_function(function () {
    $err = error_get_last();
    if (!$err) return;
    $fatal = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR);
    if (!in_array($err['type'], $fatal, true)) return;
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
    }
    echo json_encode(array(
        'success' => false,
        'message' => 'PHP Fatal: '.$err['message'].' ('.basename($err['file']).':'.$err['line'].')',
    ), JSON_UNESCAPED_UNICODE);
});

ob_start();

try {
    require_once(__DIR__.'/../_common.php');

    header('Content-Type: application/json; charset=utf-8');

    $my_ch_id = ses($character, 'ch_id', 0, 'int');
    if (!$is_member || !$my_ch_id) {
        timeline_post_json(array('success' => false, 'message' => '로그인이 필요합니다.'));
    }

    // 권한 체크 (사용자 목록 기반)
    if (!can_write_timeline($timeline, $my_ch_id, $is_admin)) {
        timeline_post_json(array('success' => false, 'message' => '글쓰기 권한이 없습니다.'));
    }

$content = ses($_POST, 'content', '', 'raw');
$parent_id = ses($_POST, 'parent_id', 0, 'int');

// 모듈 훅으로 액션 처리 (_module_write_update.inc.php, bbs와 동일 방식)
$w = '';
$wr_id = 0;
$wr_num = 0;
$temp_wr_id = 0;
$comment_id = 0;
$customer_sql = '';
$memo_custom_sql = '';
$log = '';

require G5_BBS_PATH . '/_module_write_update.inc.php';

// 옵션 배열 구성
$options = array(
    'parent_id'       => $parent_id,
    'log'             => $log,
    'customer_sql'    => $customer_sql,
    'wr_img_hidden'   => ses($_POST, 'wr_img_hidden', 0, 'int') ? 1 : 0,
    'wr_text_hidden'  => ses($_POST, 'wr_text_hidden', 0, 'int') ? 1 : 0,
    'wr_waring_text'  => ses($_POST, 'wr_waring_text', '', 'string'),
);

// 복수 이미지 업로드 (최대 tl_image_count개)
$max_images = ses($timeline, 'tl_image_count', 4, 'int');
if ($max_images < 1) $max_images = 4;
$allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
$uploaded_images = array();

// 이미지 저장 디렉토리 확인
$upload_dir = G5_TIMELINE_PATH.'/'.$tl_table;
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}

// 복수 파일 처리 (images[] 배열)
if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
    $file_count = min(count($_FILES['images']['name']), $max_images);
    
    for ($i = 0; $i < $file_count; $i++) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
        
        $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext)) continue;
        
        $filename = $my_ch_id.'_'.time().'_'.mt_rand(1000, 9999).'_'.$i.'.'.$ext;
        $filepath = $upload_dir.'/'.$filename;
        
        if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $filepath)) {
            $uploaded_images[] = $filename;
        }
    }
}

// 단일 파일 처리 (image 필드, 하위 호환)
if (empty($uploaded_images) && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed_ext)) {
        $filename = $my_ch_id.'_'.time().'_'.mt_rand(1000, 9999).'.'.$ext;
        $filepath = $upload_dir.'/'.$filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
            $uploaded_images[] = $filename;
        }
    }
}

// 이미지 경로를 options에 저장 (wr_image1 ~ wr_image4)
for ($i = 0; $i < count($uploaded_images) && $i < 4; $i++) {
    $options['wr_image'.($i+1)] = $uploaded_images[$i];
}
$options['wr_file'] = count($uploaded_images);

// 글 작성
$result = insert_timeline_post($tl_table, $my_ch_id, $content, $options);

if ($result['success']) {
    $wr_id = $result['wr_id'];
    
    // write_after 훅 발사 — bbs_item_use 모듈이 use_item POST를 처리
    if (function_exists('mdl_do_action')) {
        mdl_do_action('write_after', $bo_table, $wr_id, $w);
    }
    
    // 포인트 지급
    $tl_write_point = ses($timeline, 'tl_write_point', 0, 'int');
    if ($tl_write_point) {
        $tl_subject = ses($timeline, 'tl_subject', '타임라인', 'raw');
        insert_point($member['mb_id'], $tl_write_point, $tl_subject.' 글작성', 'timeline', $tl_table, $wr_id);
    }
    
    // HTML 렌더링 (목록에 바로 추가용)
    $write = get_timeline_post($tl_table, $wr_id, $my_ch_id);
    if ($write && file_exists($timeline_skin_path.'/item.skin.php')) {
        ob_start();
        
        // item.skin.php에 필요한 변수 설정
        $row = $write;
        $wr_id = ses($write, 'wr_id', 0, 'int');
        $ch_id = ses($write, 'ch_id', 0, 'int');
        $ch_name = h(get_timeline_row_name($write));
        $ch_image = get_timeline_row_thumb($write);
        $content = render_timeline_content(ses($write, 'wr_content', '', 'raw'), $tl_table);
        $content_raw = ses($write, 'wr_content', '', 'raw');
        $time_ago = get_time_ago(ses($write, 'wr_datetime', '', 'raw'));
        $datetime = ses($write, 'wr_datetime', '', 'raw');
        $is_mine = ($ch_id == $my_ch_id);
        $is_reply = (ses($write, 'wr_reply', '', 'raw') || ses($write, 'wr_parent', 0, 'int'));
        $my_like = !empty(ses($write, 'my_like', 0, 'int'));
        $my_bookmark = !empty(ses($write, 'my_bookmark', 0, 'int'));
        $like_count = ses($write, 'wr_like_count', 0, 'int');
        $comment_count = ses($write, 'wr_comment', 0, 'int');
        $images = array();
        for ($i = 1; $i <= 4; $i++) {
            $img = ses($write, 'wr_image'.$i, '', 'raw');
            if ($img && file_exists(G5_TIMELINE_PATH.'/'.$tl_table.'/'.$img)) {
                $images[] = G5_TIMELINE_URL.'/'.$tl_table.'/'.$img;
            }
        }
        $profile_url = G5_URL.'/timeline/profile.php?tl_table='.$tl_table.'&ch_id='.$ch_id;
        $view_url = G5_URL.'/timeline/view.php?tl_table='.$tl_table.'&wr_id='.$wr_id;
        $open_post_id = $wr_id;
        $wr_log = ses($write, 'wr_log', '', 'raw');
        $wr_item_log = ses($write, 'wr_item_log', '', 'raw');
        $wr_dice = ses($write, 'wr_dice', '', 'raw');
        $wr_dice1 = ses($write, 'wr_dice1', 0, 'int');
        $wr_dice2 = ses($write, 'wr_dice2', 0, 'int');
        $wr_reply_to = ses($write, 'wr_reply_to', 0, 'int');
        $wr_parent = ses($write, 'wr_parent', 0, 'int');
        
        include($timeline_skin_path.'/item.skin.php');
        $result['html'] = ob_get_clean();
    }
    $result['server_time'] = (int)G5_SERVER_TIME;
}

    timeline_post_json($result);

} catch (Throwable $e) {
    timeline_post_json(array(
        'success' => false,
        'message' => '서버 오류: '.$e->getMessage().' ('.basename($e->getFile()).':'.$e->getLine().')',
    ));
}
