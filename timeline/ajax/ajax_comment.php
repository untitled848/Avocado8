<?php
/**
 * 타임라인 댓글(답글) 작성 AJAX
 * MMB 호환 구조 (w=c 모드)
 *
 * POST: tl_table, wr_id, reply_to?, content, action?, use_item?, gift_*, images[]...
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

/**
 * JSON 응답 후 종료 (우리가 연 버퍼만 정리)
 */
function timeline_comment_json($payload) {
    // 이 요청에서 연 버퍼만 비움 — 상위(gzip 등) 버퍼는 건드리지 않음
    $buf = ob_get_contents();
    if ($buf !== false) {
        ob_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // 치명적이지 않은 알림은 JSON을 깨지 않게 삼킴 (로그용으로만)
    if (!(error_reporting() & $errno)) {
        return false;
    }
    // E_ERROR 계열은 핸들러로 안 옴 — 아래 Throwable 에서 처리
    return true; // 워닝/노티스 출력 억제
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
    // 이미 출력된 잡음이 있어도 클라이언트 파서가 {..} 복구 가능
    echo json_encode(array(
        'success' => false,
        'message' => 'PHP Fatal: '.$err['message'].' ('.basename($err['file']).':'.$err['line'].')',
        'comment_id' => 0,
    ), JSON_UNESCAPED_UNICODE);
});

ob_start();

try {
    include_once(__DIR__.'/../_common.php');

    $my_ch_id = ses($character, 'ch_id', 0, 'int');
    $result = array('success' => false, 'message' => '', 'comment_id' => 0);

    if (!$is_member || !$my_ch_id) {
        $result['message'] = '로그인이 필요합니다.';
        timeline_comment_json($result);
    }

    if (!function_exists('can_write_timeline') || !can_write_timeline($timeline, $my_ch_id, $is_admin)) {
        $result['message'] = '글쓰기 권한이 없습니다.';
        timeline_comment_json($result);
    }

    $wr_id = ses($_POST, 'wr_id', 0, 'int');
    $reply_to = ses($_POST, 'reply_to', 0, 'int');
    $content = ses($_POST, 'content', '', 'raw');

    if ($reply_to == 0 && $wr_id > 0) {
        $reply_to = $wr_id;
    }

    if (!$wr_id) {
        $result['message'] = '원글 정보가 없습니다.';
        timeline_comment_json($result);
    }

    $write = sql_fetch("SELECT * FROM {$write_table} WHERE wr_id = '{$wr_id}' AND wr_deleted = 0");
    if (!$write || empty($write['wr_id'])) {
        $result['message'] = '원글을 찾을 수 없습니다.';
        timeline_comment_json($result);
    }

    $w = 'c';
    $comment_id = 0;
    $wr_num = isset($write['wr_num']) ? $write['wr_num'] : 0;

    $temp_wr_id = 0;
    $customer_sql = '';
    $memo_custom_sql = '';
    $log = '';

    require G5_BBS_PATH . '/_module_write_update.inc.php';

    $uploaded_images = array();
    $max_images = ses($timeline, 'tl_image_count', 4, 'int');
    if ($max_images < 1) $max_images = 4;
    if ($max_images > 4) $max_images = 4;
    $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');

    $upload_dir = (defined('G5_TIMELINE_PATH') ? G5_TIMELINE_PATH : (G5_DATA_PATH.'/timeline')).'/'.$tl_table;
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0755, true);
    }

    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $file_count = min(count($_FILES['images']['name']), $max_images);
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext)) continue;
            $filename = $my_ch_id.'_'.time().'_'.mt_rand(1000, 9999).'_'.$i.'.'.$ext;
            $filepath = $upload_dir.'/'.$filename;
            if (@move_uploaded_file($_FILES['images']['tmp_name'][$i], $filepath)) {
                $uploaded_images[] = $filename;
            }
        }
    }

    $options = array(
        'parent_id'      => $wr_id,
        'reply_to'       => $reply_to,
        'log'            => $log,
        'customer_sql'   => $customer_sql,
        'wr_img_hidden'  => ses($_POST, 'wr_img_hidden', 0, 'int') ? 1 : 0,
        'wr_text_hidden' => ses($_POST, 'wr_text_hidden', 0, 'int') ? 1 : 0,
        'wr_waring_text' => ses($_POST, 'wr_waring_text', '', 'raw'),
    );

    for ($i = 0; $i < count($uploaded_images) && $i < 4; $i++) {
        $options['wr_image'.($i+1)] = $uploaded_images[$i];
    }
    $options['wr_file'] = count($uploaded_images);

    for ($i = 1; $i <= 10; $i++) {
        $options['wr_'.$i] = ses($_POST, 'wr_'.$i, '', 'raw');
    }

    $insert_result = insert_timeline_post($tl_table, $my_ch_id, $content, $options);

    if (!empty($insert_result['success'])) {
        $comment_id = (int)$insert_result['wr_id'];

        if (function_exists('delete_timeline_temp')) {
            $temp_parent_wr_id = $reply_to > 0 ? $reply_to : $wr_id;
            delete_timeline_temp($tl_table, $temp_parent_wr_id, $my_ch_id);
        }

        if (function_exists('mdl_do_action')) {
            mdl_do_action('comment_after', $bo_table, $comment_id, $wr_id);
        }

        $result['success'] = true;
        $result['message'] = '댓글이 등록되었습니다.';
        $result['comment_id'] = $comment_id;
        $result['parent_id'] = $wr_id;
        $result['mention_root'] = isset($insert_result['mention_root']) ? (int)$insert_result['mention_root'] : 0;
    } else {
        $result['message'] = isset($insert_result['message']) ? $insert_result['message'] : '등록에 실패했습니다.';
    }

    timeline_comment_json($result);

} catch (Throwable $e) {
    timeline_comment_json(array(
        'success' => false,
        'message' => '서버 오류: '.$e->getMessage().' ('.basename($e->getFile()).':'.$e->getLine().')',
        'comment_id' => 0,
    ));
}
