<?php
/*
 * jQuery File Upload Plugin PHP Example 5.14
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */
include_once("./_common.php");
@include_once("./JSON.php");

if( !function_exists('json_encode') ) {
    function json_encode($data) {
        $json = new Services_JSON();
        return( $json->encode($data) );
    }
}

@ini_set('gd.jpeg_ignore_warning', 1);

$ym = date('ym', G5_SERVER_TIME);

$data_dir = G5_DATA_PATH.'/editor/'.$ym.'/';
$data_url = G5_DATA_URL.'/editor/'.$ym.'/';

// parent editor 디렉터리까지 재귀 생성
if (!is_dir(G5_DATA_PATH.'/editor')) {
    @mkdir(G5_DATA_PATH.'/editor', G5_DIR_PERMISSION, true);
}
@mkdir($data_dir, G5_DIR_PERMISSION, true);
@chmod(G5_DATA_PATH.'/editor', G5_DIR_PERMISSION);
@chmod($data_dir, G5_DIR_PERMISSION);

if(!function_exists('ft_nonce_is_valid')){
    include_once('../../../editor.lib.php');
}

$is_editor_upload = false;

// G5 common.php 가 $_GET 에 addslashes 를 적용하므로 검증 전 1회 제거
$editor_nonce = '';
if (isset($_GET['_nonce'])) {
    $editor_nonce = stripslashes((string)$_GET['_nonce']);
} elseif (isset($_POST['_nonce'])) {
    $editor_nonce = stripslashes((string)$_POST['_nonce']);
}

if ($editor_nonce !== '' && function_exists('ft_nonce_is_valid') && ft_nonce_is_valid($editor_nonce, 'smarteditor')) {
    $is_editor_upload = true;
}

// 팝업 중첩 등으로 논스가 깨져도, 로그인 회원이면 업로드 허용
if (!$is_editor_upload && !empty($is_member)) {
    $is_editor_upload = true;
}

// common/extend 출력이 JSON을 깨지 않게 버퍼 비우기
while (ob_get_level() > 0) {
    ob_end_clean();
}

if( $is_editor_upload ) {

    require('UploadHandler.php');
    $options = array(
        'upload_dir' => $data_dir,
        'upload_url' => $data_url,
        // This option will disable creating thumbnail images and will not create that extra folder.
        // However, due to this, the images preview will not be displayed after upload
        'image_versions' => array()
    );

    $upload_handler = new UploadHandler($options);

} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('files'=>array(array('error'=>'정상적인 업로드가 아닙니다. 로그인 후 다시 시도해 주세요.'))));
    exit;
}
