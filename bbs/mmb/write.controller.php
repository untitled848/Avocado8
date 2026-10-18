<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * mmb 글쓰기 폼 — 컨트롤러 (PHP 전용)
 *
 * - 스킨 CSS 등록
 * - 옵션 영역 문자열 구성 ($option, $option_hidden)
 * - 카테고리 / 이미지 URL 계산
 * - 모듈 훅 컨텍스트 ($mmb_form_ctx) 구성 + bbs_write_form_before 액션 발사
 *
 * 출력은 하지 않는다. 템플릿(template/write.tpl.php)에서 사용한다.
 *
 * 변수 관련:
 *   PHP 8 에서 미정의 변수/어레이 키 접근은 Warning 레벨.
 *   error_reporting 로 제어 가능하므로 광범위 isset 가드는 두지 않는다.
 *   (특정 어레이 메서드의 아그먼트 타입이 엄격한 곳만 아래에서 명시적 메움)
 */

add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);

// bo_use_chick 사용시 새글은 바로 목록 이동
if (!empty($board['bo_use_chick']) && $w == '') {
    goto_url(G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table.$qstr);
}

$is_error = false;
$option = '';
$option_hidden = '';

// 옵션 영역
if ($is_notice || $is_html || $is_secret || $is_mail) {

    if ($is_notice) {
        $option .= "\n".'<input type="checkbox" id="notice" name="notice" value="1" '.$notice_checked.'>'."\n".'<label for="notice">공지</label>';
    }

    if ($is_html) {
        if ($is_dhtml_editor) {
            $option_hidden .= '<input type="hidden" value="html1" name="html">';
        } else {
            $option .= "\n".'<input type="checkbox" id="html" name="html" onclick="html_auto_br(this);" value="'.$html_value.'" '.$html_checked.'>'."\n".'<label for="html">html</label>';
        }
    }

    if ($is_secret) {
        if ($is_admin || $is_secret == 1) {
            $option .= "\n".'<input type="checkbox" id="secret" name="secret" value="secret" '.$secret_checked.'>'."\n".'<label for="secret">비밀글</label>';
        } else {
            $option_hidden .= '<input type="hidden" name="secret" value="secret">';
        }
    }

    if ($is_mail) {
        $option .= "\n".'<input type="checkbox" id="mail" name="mail" value="mail" '.$recv_email_checked.'>'."\n".'<label for="mail">답변메일받기</label>';
    }
}

/*
 * 대표캐릭터 미설정 등 추가 검증이 필요하면 여기서 $is_error = true 설정
 */

// 에러 시 템플릿 출력 스킵
if ($is_error) {
    return;
}

// 카테고리 재정의
$is_category = false;
$category_option = '';
if (!empty($board['bo_use_category']) && !empty($board['bo_category_list'])) {

    $ca_name = '';
    if (isset($write['ca_name']))
        $ca_name = $write['ca_name'];

    $categories = explode('|', $board['bo_category_list']);
    for ($i=0; $i<count($categories); $i++) {
        $category = trim($categories[$i]);
        if ($category === '') continue;

        if ($i == 0 && $ca_name === '') {
            $ca_name = $category;
        }

        $checked = '';
        $class = '';
        if ($category === $ca_name) {
            $class = ' class="on"';
            $checked = 'checked';
        }

        $category_option .= "<li{$class}>"
                          . "<input type='radio' name='ca_name' value='{$category}' id='ca_name_{$i}' {$checked} />"
                          . "<label for='ca_name_{$i}' data-index='view_{$i}'>".$category."</label>"
                          . "</li>\n";
    }

    $is_category = ($category_option !== '');
}

// 기본 이미지 URL
$image_url = $board_skin_url.'/img/no_image.png';
$img_data = '';

if ($w == 'u') {

    if (isset($write['wr_type']) && $write['wr_type'] === 'URL' && $write['wr_url'] !== '') {
        $image_url = $write['wr_url'];
        if ($write['wr_width'] && $write['wr_height']) {
            $img_data = 'width : '.$write['wr_width'].'px / height : '.$write['wr_height'].'px';
        }
    } else if (isset($file[0]) && isset($file[0]['file']) && $file[0]['file'] && isset($file[0]['path'])) {
        $image_url = $file[0]['path'].'/'.$file[0]['file'];
        if (isset($file[0]['wr_width'], $file[0]['wr_height'])) {
            $img_data = 'width : '.$file[0]['wr_width'].'px / height : '.$file[0]['wr_height'].'px';
        }
    }

    if (isset($write['wr_subject']) && $write['wr_subject'] === '--|UPLOADING|--') {
        $write['wr_subject'] = (isset($character['ch_name']) && $character['ch_name'] !== '') ? $character['ch_name'] : 'GUEST';
    }

} else {
    // 새 글
    $write['wr_subject'] = (isset($character['ch_name']) && $character['ch_name'] !== '') ? $character['ch_name'] : 'GUEST';
}

// 모듈 훅 컨텍스트
$mmb_form_ctx = array(
    'bo_table'  => $bo_table,
    'w'         => $w,
    'wr_id'     => $wr_id,
    'write'     => $write,
    'character' => $character,
    'board'     => $board,
);
if (function_exists('mdl_do_action')) mdl_do_action('bbs_write_form_before', $mmb_form_ctx);
