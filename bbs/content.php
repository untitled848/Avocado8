<?php
include_once('./_common.php');

//dbconfig파일에 $g5['content_table'] 배열변수가 있는지 체크
if( !isset($g5['content_table']) ){
    die('<meta charset="utf-8">관리자 모드에서 게시판관리->내용 관리를 먼저 확인해 주세요.');
}

// 내용
$sql = " select * from {$g5['content_table']} where co_id = '$co_id' ";
$co = sql_fetch($sql);
if (!$co['co_id'])
    alert('등록된 내용이 없습니다.');

$g5['title'] = $co['co_subject'];


if (is_include_path_check($co['co_include_head']))
    @include_once($co['co_include_head']);
else
    include_once('./_head.php');

if (G5_IS_MOBILE && $co['co_mobile_content'] != "") {
	$str = conv_content($co['co_mobile_content'], $co['co_html'], $co['co_tag_filter_use']);
} else {
	$str = conv_content($co['co_content'], $co['co_html'], $co['co_tag_filter_use']);
}

// $src 를 $dst 로 변환
unset($src);
unset($dst);
$src[] = "/{{쇼핑몰명}}|{{홈페이지제목}}/";
$dst[] = $config['cf_title'];
$src[] = "/{{회사명}}|{{상호}}/";
$dst[] = $default['de_admin_company_name'];
$src[] = "/{{대표자명}}/";
$dst[] = $default['de_admin_company_owner'];
$src[] = "/{{사업자등록번호}}/";
$dst[] = $default['de_admin_company_saupja_no'];
$src[] = "/{{대표전화번호}}/";
$dst[] = $default['de_admin_company_tel'];
$src[] = "/{{팩스번호}}/";
$dst[] = $default['de_admin_company_fax'];
$src[] = "/{{통신판매업신고번호}}/";
$dst[] = $default['de_admin_company_tongsin_no'];
$src[] = "/{{사업장우편번호}}/";
$dst[] = $default['de_admin_company_zip'];
$src[] = "/{{사업장주소}}/";
$dst[] = $default['de_admin_company_addr'];
$src[] = "/{{운영자명}}|{{관리자명}}/";
$dst[] = $default['de_admin_name'];
$src[] = "/{{운영자e-mail}}|{{관리자e-mail}}/i";
$dst[] = $default['de_admin_email'];
$src[] = "/{{정보관리책임자명}}/";
$dst[] = $default['de_admin_info_name'];
$src[] = "/{{정보관리책임자e-mail}}|{{정보책임자e-mail}}/i";
$dst[] = $default['de_admin_info_email'];

$str = preg_replace($src, $dst, $str);

// 스킨경로
// 문서에 co_skin 이 지정돼 있으면 그쪽 우선,
// 없으면 글로벌 디자인(default 테마 프리셋의 pr_content_design) 적용
//   - 'skin:DIRNAME' → 해당 스킨 디렉토리 사용
//   - 프리셋(숫자)   → 별도 스킨 없이 디자인 프리셋 CSS 만 적용
//                      (스킨 미지정 안내 문구 출력)
$content_skin_dir = trim((string)$co['co_skin']);
$content_use_skin = true;   // 스킨 파일을 include 할지 여부
if ($content_skin_dir === '' && function_exists('get_active_design_resolved')) {
    $resolved = get_active_design_resolved('content');
    if ($resolved['kind'] === 'skin' && $resolved['value'] !== '') {
        $content_skin_dir = $resolved['value'];
    } else {
        $content_use_skin = false;   // 프리셋만 적용 — 스킨 include 생략
    }
}

if ($content_use_skin && $content_skin_dir !== '') {
    $content_skin_path = get_skin_path('content', $content_skin_dir);
    $content_skin_url  = get_skin_url('content', $content_skin_dir);
    $skin_file = $content_skin_path.'/content.skin.php';
} else {
    $content_skin_path = '';
    $content_skin_url  = '';
    $skin_file         = '';
}

// === 문서 디자인 프리셋 — CSS link + 꾸밈 이미지 ===========================
@include_once(G5_LIB_PATH.'/content.lib.php');
$content_pd_id = function_exists('content_active_pd_id') ? content_active_pd_id() : 1;
add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/css/content_design.php?pd_id='.(int)$content_pd_id.'">', 0);
$content_deco_top    = function_exists('content_render_decoration') ? content_render_decoration('top',    $content_pd_id) : '';
$content_deco_bottom = function_exists('content_render_decoration') ? content_render_decoration('bottom', $content_pd_id) : '';

if ($is_admin)
    echo '<div class="ctt_admin"><a href="'.G5_ADMIN_URL.'/contentform.php?w=u&amp;co_id='.$co_id.'" class="ui-btn admin">내용 수정</a></div>';
?>

<?php
if ($content_use_skin && $skin_file !== '' && is_file($skin_file)) {
    $himg = G5_DATA_PATH.'/content/'.$co_id.'_h';
    if (file_exists($himg)) // 상단 이미지
        echo '<div id="ctt_himg" class="ctt_img"><img src="'.G5_DATA_URL.'/content/'.$co_id.'_h" alt=""></div>';

    echo $content_deco_top;
    include($skin_file);
    echo $content_deco_bottom;

    $timg = G5_DATA_PATH.'/content/'.$co_id.'_t';
    if (file_exists($timg)) // 하단 이미지
        echo '<div id="ctt_timg" class="ctt_img"><img src="'.G5_DATA_URL.'/content/'.$co_id.'_t" alt=""></div>';
} else if (!$content_use_skin) {
    // 스킨 없이 프리셋(디자인 변수)만 적용 — 본문을 그대로 출력
    $himg = G5_DATA_PATH.'/content/'.$co_id.'_h';
    if (file_exists($himg))
        echo '<div id="ctt_himg" class="ctt_img"><img src="'.G5_DATA_URL.'/content/'.$co_id.'_h" alt=""></div>';
    echo $content_deco_top;
    echo '<div id="content_body" class="ctt_box">'.$str.'</div>';
    echo $content_deco_bottom;
    $timg = G5_DATA_PATH.'/content/'.$co_id.'_t';
    if (file_exists($timg))
        echo '<div id="ctt_timg" class="ctt_img"><img src="'.G5_DATA_URL.'/content/'.$co_id.'_t" alt=""></div>';
} else {
    echo '<p>'.str_replace(G5_PATH.'/', '', $skin_file).'이 존재하지 않습니다.</p>';
}

if (is_include_path_check($co['co_include_tail']))
    @include_once($co['co_include_tail']);
else
    include_once('./_tail.php');
?>
