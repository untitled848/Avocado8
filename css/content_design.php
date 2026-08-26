<?php
/**
 * css/content_design.php — 문서(내용관리) 디자인 CSS 공개 엔드포인트.
 *
 * adm/content_design_form_css.php 는 adm 부팅에서 비로그인/비관리자를
 * adm/login.php 로 리다이렉트하므로 방문자에게 CSS 가 내려가지 않았다.
 * 이 파일은 공개 common.php 로 부팅하여 누구나 로드할 수 있게 한다.
 */
@include_once(__DIR__ . '/../common.php');

$_pd_id = isset($GLOBALS['_content_pd_id']) ? (int)$GLOBALS['_content_pd_id']
        : (isset($_GET['pd_id']) ? (int)$_GET['pd_id'] : 1);
if ($_pd_id <= 0) $_pd_id = 1;

$css_sql = sql_query("SELECT * FROM {$g5['content_design_config_table']} WHERE pd_id = '{$_pd_id}'");
$css = array();
while ($cs = sql_fetch_array($css_sql)) {
    $cs_v = (string)$cs['cs_value'];
    if (function_exists('cdn_resolve_url')) $cs_v = cdn_resolve_url($cs_v);
    $css[$cs['cs_name']][0] = $cs_v;
    for ($i = 1; $i <= 10; $i++) {
        $v = (string)$cs['cs_etc_'.$i];
        if (function_exists('cdn_resolve_url')) $v = cdn_resolve_url($v);
        $css[$cs['cs_name']][$i] = $v;
    }
}

if (!isset($GLOBALS['_content_css_embedded'])) {
    header('Content-Type: text/css; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
}
?>
@charset "utf-8";
/* =====================================================
   문서 디자인 CSS  (pd_id: <?php echo (int)$_pd_id; ?>)
   자동 생성 — <?php echo date('Y-m-d H:i:s'); ?>
===================================================== */

/* :before 요소가 모든 html 에서 표시되도록 보장 */
html:before {
    content: "";
    display: block;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    z-index: -1;
    pointer-events: none;
}

/* === 전체 페이지 배경 (데스크톱 1025px+) === */
@media all and (min-width: 1025px) {
    html.single:before, html:before {
        <?php if (!empty($css['page_bg_desktop'][0])): ?>background-image: url('<?php echo $css['page_bg_desktop'][0]; ?>') !important;<?php endif; ?>
        <?php if (!empty($css['page_bg_desktop'][1])): ?>background-color: <?php echo $css['page_bg_desktop'][1]; ?> !important;<?php endif; ?>
        <?php if (!empty($css['page_bg_desktop'][2])): ?>background-repeat: <?php echo $css['page_bg_desktop'][2]; ?> !important;<?php endif; ?>
        <?php if (!empty($css['page_bg_desktop'][3])): ?>background-position: <?php echo $css['page_bg_desktop'][3]; ?> !important;<?php endif; ?>
        <?php if (!empty($css['page_bg_desktop'][4])): ?>background-size: <?php echo $css['page_bg_desktop'][4]; ?> !important;<?php endif; ?>
        <?php if (!empty($css['page_bg_desktop'][5])): ?>background-attachment: <?php echo $css['page_bg_desktop'][5]; ?> !important;<?php endif; ?>
    }
}

/* === 전체 페이지 배경 (모바일 1024px 이하) === */
@media all and (max-width: 1024px) {
    html.single:before, html:before {
        <?php if (!empty($css['page_bg_mobile'][0])): ?>
        background-image: url('<?php echo $css['page_bg_mobile'][0]; ?>') !important;
        <?php else: ?>
        background-image: none !important;
        <?php endif; ?>
        <?php if (!empty($css['page_bg_mobile'][1])): ?>background-color: <?php echo $css['page_bg_mobile'][1]; ?> !important;<?php endif; ?>
        <?php if (!empty($css['page_bg_mobile'][2])): ?>background-repeat: <?php echo $css['page_bg_mobile'][2]; ?> !important;<?php endif; ?>
        <?php if (!empty($css['page_bg_mobile'][3])): ?>background-position: <?php echo $css['page_bg_mobile'][3]; ?> !important;<?php endif; ?>
        <?php if (!empty($css['page_bg_mobile'][4])): ?>background-size: <?php echo $css['page_bg_mobile'][4]; ?> !important;<?php endif; ?>
    }
}

/* === 문서 영역 (.ctt_box) === */
.ctt_box {
    box-sizing: border-box;
    <?php if (!empty($css['content_bg_color'][0])): ?>background-color: <?php echo $css['content_bg_color'][0]; ?>;<?php endif; ?>
    <?php if (!empty($css['content_text_color'][0])): ?>color: <?php echo $css['content_text_color'][0]; ?>;<?php endif; ?>
    <?php if (!empty($css['content_border'][0])): ?>border: <?php echo $css['content_border'][0]; ?> <?php echo $css['content_border'][1] ?: 'solid'; ?> <?php echo $css['content_border'][2] ?: 'transparent'; ?>;<?php endif; ?>
    <?php if (!empty($css['content_padding'][0])): ?>padding: <?php echo $css['content_padding'][0]; ?>;<?php endif; ?>
    <?php if (!empty($css['content_bg_img'][0])): ?>background-image: url('<?php echo $css['content_bg_img'][0]; ?>') !important;<?php endif; ?>
    <?php if (!empty($css['content_bg_img'][1])): ?>background-repeat: <?php echo $css['content_bg_img'][1]; ?> !important;<?php endif; ?>
    <?php if (!empty($css['content_bg_img'][2])): ?>background-size: <?php echo $css['content_bg_img'][2]; ?> !important;<?php endif; ?>
}

/* === 꾸밈 이미지 (상/하) === */
/* cs_value=URL, cs_etc_1=정렬, cs_etc_2=최대너비, cs_etc_3=여백 */
.content-deco { line-height: 0; }
.content-deco img { max-width: 100%; height: auto; display: inline-block; }

<?php if (!empty($css['decoration_top'][0])): ?>
.content-deco--top {
    <?php if (!empty($css['decoration_top'][1])): ?>text-align: <?php echo $css['decoration_top'][1]; ?>;<?php else: ?>text-align: center;<?php endif; ?>
    <?php if (!empty($css['decoration_top'][3])): ?>margin: <?php echo $css['decoration_top'][3]; ?>;<?php else: ?>margin: 0 0 16px 0;<?php endif; ?>
}
<?php if (!empty($css['decoration_top'][2])): ?>
.content-deco--top img { max-width: <?php echo $css['decoration_top'][2]; ?>; }
<?php endif; ?>
<?php endif; ?>

<?php if (!empty($css['decoration_bottom'][0])): ?>
.content-deco--bottom {
    <?php if (!empty($css['decoration_bottom'][1])): ?>text-align: <?php echo $css['decoration_bottom'][1]; ?>;<?php else: ?>text-align: center;<?php endif; ?>
    <?php if (!empty($css['decoration_bottom'][3])): ?>margin: <?php echo $css['decoration_bottom'][3]; ?>;<?php else: ?>margin: 16px 0 0 0;<?php endif; ?>
}
<?php if (!empty($css['decoration_bottom'][2])): ?>
.content-deco--bottom img { max-width: <?php echo $css['decoration_bottom'][2]; ?>; }
<?php endif; ?>
<?php endif; ?>
