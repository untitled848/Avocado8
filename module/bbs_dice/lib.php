<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * bbs_dice — 설정/유틸 라이브러리
 *
 * 설정 키 (mdl_setting_*):
 *   - count       : 굴릴 주사위 개수 (1~10)            기본 2
 *   - sides       : 면 수 (2~100)                      기본 6
 *   - images      : '|' 구분 이미지 파일명 (./img/ 기준) 기본 'd1.png|d2.png|...|d6.png'
 *
 * 저장 형식 (write 테이블 wr_dice 컬럼, varchar(255)):
 *   - 굴림값 콤마 구분 문자열 (예: "3,5,2")
 *   - 빈 문자열이면 굴리지 않음
 */

function bbs_dice_count()  { return max(1, min(10, (int)mdl_setting_get('bbs_dice', 'count', 2))); }
function bbs_dice_sides()  { return max(2, min(100, (int)mdl_setting_get('bbs_dice', 'sides', 6))); }
function bbs_dice_render_mode() { return mdl_setting_get('bbs_dice', 'render_mode', 'image') === 'number' ? 'number' : 'image'; }

/**
 * .dice-num 박스 스타일 (border, background, color, padding)
 *   - 관리자 페이지에서 설정. 빈 값이면 기본값 사용.
 *   - bbs_dice_num_css() 가 하나의 <style> 블록을 만들어 페이지에 1회 출력.
 */
function bbs_dice_num_style() {
    return array(
        'border'     => (string)mdl_setting_get('bbs_dice', 'num_border',     '1px solid #999'),
        'background' => (string)mdl_setting_get('bbs_dice', 'num_background', '#ffffff'),
        'color'      => (string)mdl_setting_get('bbs_dice', 'num_color',      '#222222'),
        'padding'    => (string)mdl_setting_get('bbs_dice', 'num_padding',    '2px 6px'),
    );
}

function bbs_dice_num_css() {
    $s = bbs_dice_num_style();
    // CSS 인젝션 차단: 중괄호/세미콜론/주석 차단
    foreach ($s as $k => $v) {
        if (preg_match('/[{};]|\*\//', $v)) $s[$k] = '';
    }
    $border = $s['border']     !== '' ? "border:{$s['border']};"        : '';
    $bg     = $s['background'] !== '' ? "background:{$s['background']};" : '';
    $color  = $s['color']      !== '' ? "color:{$s['color']};"           : '';
    $pad    = $s['padding']    !== '' ? "padding:{$s['padding']};"       : '';
    return ".dice-num{display:inline-block;min-width:24px;margin:0 2px;border-radius:4px;text-align:center;font-weight:600;{$border}{$bg}{$color}{$pad}}";
}

function bbs_dice_images() {
    $raw = (string)mdl_setting_get('bbs_dice', 'images', 'd1.png|d2.png|d3.png|d4.png|d5.png|d6.png');
    $arr = array_values(array_filter(array_map('trim', explode('|', $raw)), 'strlen'));
    return $arr;
}

function bbs_dice_roll_all() {
    $n = bbs_dice_count();
    $s = bbs_dice_sides();
    $out = array();
    for ($i = 0; $i < $n; $i++) $out[] = mt_rand(1, $s);
    return $out;
}

function bbs_dice_parse($wr_dice) {
    if (!is_string($wr_dice) || $wr_dice === '') return array();
    $arr = array_filter(array_map('intval', explode(',', $wr_dice)));
    return array_values($arr);
}

function bbs_dice_image_url_for($value, $module_url) {
    $imgs = bbs_dice_images();
    $val = (int)$value;
    // value 가 면수 범위를 벗어나거나 이미지가 부족하면 fallback
    if ($val >= 1 && $val <= count($imgs)) {
        return $module_url . '/img/' . htmlspecialchars($imgs[$val - 1], ENT_QUOTES);
    }
    return ''; // 이미지 없음 — 호출측에서 숫자로 대체
}

/**
 * 주사위 이미지 업로드: $_FILES['dice_img'] → module/bbs_dice/img/ 저장
 * 반환: array('ok'=>bool, 'filename'=>..., 'msg'=>...)
 */
function bbs_dice_image_upload($file_arr) {
    $allowed_exts = array('png', 'jpg', 'jpeg', 'gif', 'webp');
    $max_size     = 512 * 1024; // 512KB

    $name = isset($file_arr['name']) ? basename($file_arr['name']) : '';
    $tmp  = isset($file_arr['tmp_name']) ? $file_arr['tmp_name'] : '';
    $size = isset($file_arr['size']) ? (int)$file_arr['size'] : 0;
    $err  = isset($file_arr['error']) ? (int)$file_arr['error'] : UPLOAD_ERR_NO_FILE;

    if ($err !== UPLOAD_ERR_OK || !$tmp) return array('ok' => false, 'msg' => '업로드 오류 코드 '.$err);
    if ($size > $max_size) return array('ok' => false, 'msg' => '파일이 너무 큽니다 (최대 512KB)');

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_exts, true)) return array('ok' => false, 'msg' => '허용된 확장자: '.implode('/', $allowed_exts));

    // MIME 검증 (php < 8.1 호환)
    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
    if ($finfo) {
        $mime = finfo_file($finfo, $tmp);
        finfo_close($finfo);
        $allowed_mimes = array('image/png','image/jpeg','image/gif','image/webp');
        if (!in_array($mime, $allowed_mimes, true)) return array('ok' => false, 'msg' => 'MIME 타입 불일치: '.$mime);
    }

    $safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
    $img_dir   = G5_PATH . '/module/bbs_dice/img';
    if (!is_dir($img_dir)) mkdir($img_dir, 0755, true);

    // 충돌 방지
    $dest = $img_dir . '/' . $safe_name;
    if (is_file($dest)) {
        $base  = pathinfo($safe_name, PATHINFO_FILENAME);
        $ext2  = pathinfo($safe_name, PATHINFO_EXTENSION);
        $i = 2;
        while (is_file($img_dir.'/'.$base.'_'.$i.'.'.$ext2)) $i++;
        $safe_name = $base.'_'.$i.'.'.$ext2;
        $dest = $img_dir . '/' . $safe_name;
    }

    if (!move_uploaded_file($tmp, $dest)) return array('ok' => false, 'msg' => '파일 저장 실패');
    return array('ok' => true, 'filename' => $safe_name, 'msg' => '업로드 성공');
}
