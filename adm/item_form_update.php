<?php
$sub_menu = "500200";
include_once('./_common.php');

if ($w == 'u') check_demo();
auth_check($auth[$sub_menu], 'w');
check_token();

$item_data_path = G5_DATA_PATH . "/item";
$item_data_url  = G5_DATA_URL . "/item";

// 디렉토리 준비
if (!is_dir($item_data_path)) {
    if (!mkdir($item_data_path, G5_DIR_PERMISSION, true)) {
        alert('아이템 저장 폴더 생성 실패');
    }
    @chmod($item_data_path, G5_DIR_PERMISSION);
}

$sql_common = "";

if ($w == '') {
    $tmp_row = sql_fetch("SELECT MAX(it_id) AS max_it_id FROM {$g5['item_table']}");
    $it_id = (int)$tmp_row['max_it_id'] + 1;
} else {
    $it_id = trim($it_id);
    if ($it_id === '') alert('it_id 값이 없습니다.');
}

// 초기값
$it_img = isset($it_img) ? $it_img : '';
$it_1   = isset($it_1) ? $it_1 : '';

// 안전한 파일 업로드 처리 함수(간단검증)
function safe_upload($file_field, $prefix, $it_id, $dest_path, $dest_url) {
    if (empty($_FILES[$file_field]) || !isset($_FILES[$file_field]['name']) || !$_FILES[$file_field]['name']) return '';
    $file = $_FILES[$file_field];
    if ($file['error'] !== UPLOAD_ERR_OK) return '';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = array('jpg','jpeg','png','gif','webp');
    if (!in_array($ext, $allowed)) return '';
    $safe_name = sprintf('%s_%s.%s', $prefix, $it_id, $ext);
    // upload_file 함수 사용(기존 함수 유지). 반환값 검사
    if (upload_file($file['tmp_name'], $safe_name, $dest_path)) {
        // 아이템 자산은 로컬 전용 — cdn_after_upload 호출하지 않음
        return $dest_url . '/' . $safe_name;
    }
    return '';
}

// it_img 업로드
$uploaded_it_img = safe_upload('it_img_file', 'item_img', $it_id, $item_data_path, $item_data_url);
if ($uploaded_it_img) $it_img = $uploaded_it_img;

// it_1 업로드 (상세)
$uploaded_it_1 = safe_upload('it_1_file', 'item_detail', $it_id, $item_data_path, $item_data_url);
if ($uploaded_it_1) $it_1 = $uploaded_it_1;

// CDN 토큰화 안 함 — 아이템은 로컬 자산

// bool 값 정리 (체크박스는 폼에서 존재여부로 판정)
$it_use         = isset($it_use) ? 'Y' : 'N';
$it_use_sell    = isset($it_use_sell) ? '1' : '0';
$it_use_ever    = isset($it_use_ever) ? '1' : '0';
$it_has         = isset($it_has) ? '1' : '0';
$it_use_able    = isset($it_use_able) ? '1' : '0';
$it_use_mmb_able= isset($it_use_mmb_able) ? '1' : '0';
$it_use_recepi  = isset($it_use_recepi) ? '1' : '0';
$it_seeker      = isset($it_seeker) ? '1' : '0';

// G5_ESCAPE_FUNCTION 이 $_POST 전체를 사전 escape 하므로
// stripslashes 로 되돌린 뒤 addslashes 를 한 번만 적용한다.
function esc($v) { return addslashes(stripslashes((string)$v)); }

// 모듈 확장: 저장 직전 값 가공 (예: it_type 별 자동 분류/플래그 강제)
//   $data 는 컬럼명 → 값 배열. 모듈은 일부만 덮어써서 반환.
if (function_exists('mdl_apply_filters')) {
    $_data = array(
        'it_use'         => $it_use,        'it_category'   => isset($it_category) ? $it_category : '',
        'it_type'        => isset($it_type) ? $it_type : '',
        'it_value'       => isset($it_value) ? $it_value : '',
        'it_name'        => isset($it_name) ? $it_name : '',
        'it_img'         => isset($it_img) ? $it_img : '',
        'it_content'     => isset($it_content) ? $it_content : '',
        'it_content2'    => isset($it_content2) ? $it_content2 : '',
        'it_use_sell'    => $it_use_sell,   'it_sell'         => isset($it_sell) ? $it_sell : '',
        'it_use_ever'    => $it_use_ever,   'it_has'          => $it_has,
        'it_use_able'    => $it_use_able,   'it_use_mmb_able' => $it_use_mmb_able,
        'it_use_recepi'  => $it_use_recepi,
        'it_seeker'      => $it_seeker,     'it_seeker_per_s' => isset($it_seeker_per_s) ? $it_seeker_per_s : '',
        'it_seeker_per_e'=> isset($it_seeker_per_e) ? $it_seeker_per_e : '',
        'st_id'          => isset($st_id) ? $st_id : '',
        'it_1'           => isset($it_1) ? $it_1 : '', 'it_2' => isset($it_2) ? $it_2 : '',
        'it_3'           => isset($it_3) ? $it_3 : '', 'it_4' => isset($it_4) ? $it_4 : '',
        'it_5'           => isset($it_5) ? $it_5 : '',
    );
    $_data = mdl_apply_filters('admin_item_form_save_data', $_data, $w);
    foreach ($_data as $_k => $_v) { $$_k = $_v; }
}

$sql_common = "
    it_use             = '".esc($it_use)."',
    it_category        = '".esc($it_category)."',
    it_type            = '".esc($it_type)."',
    it_value           = '".esc($it_value)."',
    it_name            = '".esc($it_name)."',
    it_img             = '".esc($it_img)."',
    it_content         = '".esc($it_content)."',
    it_content2        = '".esc($it_content2)."',
    it_use_sell        = '".esc($it_use_sell)."',
    it_sell            = '".esc($it_sell)."',
    it_use_ever        = '".esc($it_use_ever)."',
    it_has             = '".esc($it_has)."',
    it_use_able        = '".esc($it_use_able)."',
    it_use_mmb_able    = '".esc($it_use_mmb_able)."',
    it_use_recepi      = '".esc($it_use_recepi)."',
    it_seeker          = '".esc($it_seeker)."',
    it_seeker_per_s    = '".esc($it_seeker_per_s)."',
    it_seeker_per_e    = '".esc($it_seeker_per_e)."',
    st_id              = '".esc($st_id)."',
    it_1               = '".esc($it_1)."',
    it_2               = '".esc($it_2)."',
    it_3               = '".esc($it_3)."',
    it_4               = '".esc($it_4)."',
    it_5               = '".esc($it_5)."'";

if ($w == '') {
    $sql = "INSERT INTO {$g5['item_table']} SET it_id = '".(int)$it_id."', {$sql_common}";
    sql_query($sql);
} else {
    $it = sql_fetch("SELECT it_id, it_img, it_1 FROM {$g5['item_table']} WHERE it_id = '".addslashes($it_id)."'");
    if (!$it['it_id']) alert("아이템 정보가 존재하지 않습니다.");

    $sql = "UPDATE {$g5['item_table']} SET {$sql_common} WHERE it_id = '".addslashes($it['it_id'])."'";
    sql_query($sql);

    // 이전 파일 삭제 (서버 업로드 된 경우에만)
    if ($it['it_img'] && $it['it_img'] != $it_img && strpos($it['it_img'], G5_URL) === 0) {
        $prev_file_path = str_replace(G5_URL, G5_PATH, $it['it_img']);
        if (is_file($prev_file_path)) { @unlink($prev_file_path); }
    }
    if ($it['it_1'] && $it['it_1'] != $it_1 && strpos($it['it_1'], G5_URL) === 0) {
        $prev_file_path = str_replace(G5_URL, G5_PATH, $it['it_1']);
        if (is_file($prev_file_path)) { @unlink($prev_file_path); }
    }
}

goto_url('./item_list.php?'.$qstr, false);
?>
