<?php
/**
 * css/world_design.php — 세계관 디자인 CSS (배경/메뉴/컨텐츠 선택자) 공개 엔드포인트.
 *
 * adm/world_design_form_css.php 는 adm/_common.php → admin.lib.php 에서
 * 비로그인/비관리자를 adm/login.php 로 리다이렉트하므로, 방문자에게는 CSS 가
 * 내려가지 않아 세계관 배경/디자인이 반영되지 않는 버그가 있었다.
 * 이 파일은 공개 common.php 로 부팅하여 누구나 로드할 수 있게 한다.
 */
@include_once(__DIR__ . '/../common.php');

$_pd_id = isset($GLOBALS['_world_pd_id']) ? (int)$GLOBALS['_world_pd_id']
        : (isset($_GET['pd_id']) ? (int)$_GET['pd_id'] : 1);
if ($_pd_id <= 0) $_pd_id = 1;

$css_sql = sql_query("SELECT * FROM {$g5['world_config_table']} WHERE pd_id = '{$_pd_id}'");
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

if (!isset($GLOBALS['_world_css_embedded'])) {
    header('Content-Type: text/css; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
}
?>
@charset "utf-8";
/* =====================================================
   세계관 디자인 CSS  (pd_id: <?php echo (int)$_pd_id; ?>)
   자동 생성 — <?php echo date('Y-m-d H:i:s'); ?>
===================================================== */

/* :before 요소가 모든 html 에서 표시되도록 보장 (테마 css 의 .single 한정 규칙 보완) */
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
        <?php if ($css['page_bg_desktop'][0]): ?>background-image: url('<?php echo $css['page_bg_desktop'][0]; ?>') !important;<?php endif; ?>
        <?php if ($css['page_bg_desktop'][1]): ?>background-color: <?php echo $css['page_bg_desktop'][1]; ?> !important;<?php endif; ?>
        <?php if ($css['page_bg_desktop'][2]): ?>background-repeat: <?php echo $css['page_bg_desktop'][2]; ?> !important;<?php endif; ?>
        <?php if ($css['page_bg_desktop'][3]): ?>background-position: <?php echo $css['page_bg_desktop'][3]; ?> !important;<?php endif; ?>
        <?php if ($css['page_bg_desktop'][4]): ?>background-size: <?php echo $css['page_bg_desktop'][4]; ?> !important;<?php endif; ?>
        <?php if ($css['page_bg_desktop'][5]): ?>background-attachment: <?php echo $css['page_bg_desktop'][5]; ?> !important;<?php endif; ?>
    }
}

/* === 전체 페이지 배경 (모바일 1024px 이하) === */
@media all and (max-width: 1024px) {
    html.single:before, html:before {
        <?php if ($css['page_bg_mobile'][0]): ?>
        background-image: url('<?php echo $css['page_bg_mobile'][0]; ?>') !important;
        <?php else: ?>
        background-image: none !important;
        <?php endif; ?>
        <?php if ($css['page_bg_mobile'][1]): ?>background-color: <?php echo $css['page_bg_mobile'][1]; ?> !important;<?php endif; ?>
        <?php if ($css['page_bg_mobile'][2]): ?>background-repeat: <?php echo $css['page_bg_mobile'][2]; ?> !important;<?php endif; ?>
        <?php if ($css['page_bg_mobile'][3]): ?>background-position: <?php echo $css['page_bg_mobile'][3]; ?> !important;<?php endif; ?>
        <?php if ($css['page_bg_mobile'][4]): ?>background-size: <?php echo $css['page_bg_mobile'][4]; ?> !important;<?php endif; ?>
    }
}

/* === Wrapper === */
.world-wrap { display: flex; width: 100%; box-sizing: border-box; }
.world-wrap--top    { flex-direction: column; }
.world-wrap--bottom { flex-direction: column-reverse; }
.world-wrap--left   { flex-direction: row; }
.world-wrap--right  { flex-direction: row-reverse; }

/* === 메뉴 영역 === */
.world-menu {
    box-sizing: border-box;
    width: <?php echo !empty($css['menu_area_width'][0]) ? $css['menu_area_width'][0] : '100%'; ?>;
    <?php if (!empty($css['menu_area_width'][1])): ?>height: <?php echo $css['menu_area_width'][1]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_area_bg'][0])): ?>background-image: url('<?php echo $css['menu_area_bg'][0]; ?>');<?php endif; ?>
    <?php if (!empty($css['menu_area_bg'][1])): ?>background-color: <?php echo $css['menu_area_bg'][1]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_area_bg'][2])): ?>background-repeat: <?php echo $css['menu_area_bg'][2]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_area_bg'][3])): ?>background-size: <?php echo $css['menu_area_bg'][3]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_area_border'][0])): ?>border: <?php echo $css['menu_area_border'][0]; ?> <?php echo $css['menu_area_border'][1] ?: 'solid'; ?> <?php echo $css['menu_area_border'][2]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_area_layout'][0])): ?>text-align: <?php echo $css['menu_area_layout'][0]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_area_layout'][3])): ?>padding: <?php echo $css['menu_area_layout'][3]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_area_layout'][4])): ?>margin: <?php echo $css['menu_area_layout'][4]; ?>;<?php endif; ?>
}

<?php if (!empty($css['menu_area_layout'][1])):
    $jc_main = ($css['menu_area_layout'][1]==='center'?'center':($css['menu_area_layout'][1]==='right'?'flex-end':'flex-start'));
?>
.world-menu-main-row { justify-content: <?php echo $jc_main; ?>; text-align: <?php echo $css['menu_area_layout'][1]; ?>; }
<?php endif; ?>
<?php if (!empty($css['menu_area_layout'][2])):
    $jc_sub = ($css['menu_area_layout'][2]==='center'?'center':($css['menu_area_layout'][2]==='right'?'flex-end':'flex-start'));
?>
.world-menu-sub-row, .world-menu-sub-wrap { justify-content: <?php echo $jc_sub; ?>; text-align: <?php echo $css['menu_area_layout'][2]; ?>; }
<?php endif; ?>

<?php /* === 메인/서브 컨테이너 gap (Task 2-2) === */ ?>
<?php if (!empty($css['menu_area_layout'][5])): ?>
.world-menu-main-row { gap: <?php echo $css['menu_area_layout'][5]; ?>; }
.world-wrap--left .world-menu, .world-wrap--right .world-menu { gap: <?php echo $css['menu_area_layout'][5]; ?>; display: flex; flex-direction: column; }
<?php endif; ?>
<?php if (!empty($css['menu_area_layout'][6])): ?>
.world-menu-sub-wrap { gap: <?php echo $css['menu_area_layout'][6]; ?>; }
<?php endif; ?>

/* sticky — top/bottom 위치 */
.world-wrap--top .world-menu,
.world-wrap--bottom .world-menu { position: sticky; top: 0; z-index: 100; width: 100%; }
.world-wrap--bottom .world-menu { top: auto; bottom: 0; }

/* sticky — left/right 위치 */
.world-wrap--left .world-menu,
.world-wrap--right .world-menu {
    position: sticky;
    top: 0;
    align-self: flex-start;
    max-height: 100vh;
    overflow-y: auto;
}

/* 상/하 메뉴 가로 배열 — 메인 행 + 서브 행 분리 */
.world-wrap--top .world-menu-main-row,
.world-wrap--bottom .world-menu-main-row { display: flex; flex-wrap: wrap; width: 100%; }
.world-wrap--top .world-menu-sub-row,
.world-wrap--bottom .world-menu-sub-row { display: block; width: 100%; }
.world-wrap--top .world-menu-main-item,
.world-wrap--bottom .world-menu-main-item { display: inline-block; }
.world-wrap--top .world-menu-sub-wrap,
.world-wrap--bottom .world-menu-sub-wrap { display: flex; flex-wrap: wrap; }

/* 좌/우 메뉴 세로 배열 */
.world-wrap--left .world-menu-main-item,
.world-wrap--right .world-menu-main-item { display: block; }
.world-wrap--left .world-menu-sub-wrap,
.world-wrap--right .world-menu-sub-wrap { display: block; }

<?php
// 서브 그룹 위 간격 (margin-top px) — menu_sub_default[10]
$sub_gap = isset($css['menu_sub_default'][10]) ? trim((string)$css['menu_sub_default'][10]) : '';
if ($sub_gap !== '' && is_numeric($sub_gap)):
?>
/* 서브메뉴 그룹 위 간격 */
.world-wrap--top .world-menu-sub-row,
.world-wrap--bottom .world-menu-sub-row { margin-top: <?php echo (int)$sub_gap; ?>px; }
.world-wrap--left .world-menu-sub-wrap,
.world-wrap--right .world-menu-sub-wrap { margin-top: <?php echo (int)$sub_gap; ?>px; }
<?php endif; ?>

/* css 배열 규칙: [0]=이미지, [1]=글씨색, [2]=배경색, [3]=글씨크기, [4]=테두리두께, [5]=테두리스타일, [6]=테두리색 */

/* === 메인 메뉴 === */
.world-menu-main {
    display: inline-block;
    text-decoration: none;
    padding: 8px 14px;
    box-sizing: border-box;
    <?php if (!empty($css['menu_main_default'][0])): ?>background-image: url('<?php echo $css['menu_main_default'][0]; ?>');<?php endif; ?>
    <?php if (!empty($css['menu_main_default'][1])): ?>color: <?php echo $css['menu_main_default'][1]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_main_default'][2])): ?>background-color: <?php echo $css['menu_main_default'][2]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_main_default'][3])): ?>font-size: <?php echo $css['menu_main_default'][3]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_main_default'][4])): ?>border: <?php echo $css['menu_main_default'][4]; ?> <?php echo $css['menu_main_default'][5] ?: 'solid'; ?> <?php echo $css['menu_main_default'][6]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_main_default'][7])): ?>padding: <?php echo $css['menu_main_default'][7]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_main_default'][8])): ?>width: <?php echo $css['menu_main_default'][8]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_main_default'][9])): ?>text-align: <?php echo $css['menu_main_default'][9]; ?>;<?php endif; ?>
}
.world-menu-main:hover,
.world-menu-main.active {
    <?php if (!empty($css['menu_main_hover'][0])): ?>background-image: url('<?php echo $css['menu_main_hover'][0]; ?>');<?php endif; ?>
    <?php if (!empty($css['menu_main_hover'][1])): ?>color: <?php echo $css['menu_main_hover'][1]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_main_hover'][2])): ?>background-color: <?php echo $css['menu_main_hover'][2]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_main_hover'][3])): ?>font-size: <?php echo $css['menu_main_hover'][3]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_main_hover'][4])): ?>border: <?php echo $css['menu_main_hover'][4]; ?> <?php echo $css['menu_main_hover'][5] ?: 'solid'; ?> <?php echo $css['menu_main_hover'][6]; ?>;<?php endif; ?>
}

/* === 서브 메뉴 === */
.world-menu-sub {
    display: block;
    text-decoration: none;
    padding: 6px 14px 6px 22px;
    box-sizing: border-box;
    <?php if (!empty($css['menu_sub_default'][0])): ?>background-image: url('<?php echo $css['menu_sub_default'][0]; ?>');<?php endif; ?>
    <?php if (!empty($css['menu_sub_default'][1])): ?>color: <?php echo $css['menu_sub_default'][1]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_sub_default'][2])): ?>background-color: <?php echo $css['menu_sub_default'][2]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_sub_default'][3])): ?>font-size: <?php echo $css['menu_sub_default'][3]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_sub_default'][4])): ?>border: <?php echo $css['menu_sub_default'][4]; ?> <?php echo $css['menu_sub_default'][5] ?: 'solid'; ?> <?php echo $css['menu_sub_default'][6]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_sub_default'][7])): ?>padding: <?php echo $css['menu_sub_default'][7]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_sub_default'][8])): ?>width: <?php echo $css['menu_sub_default'][8]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_sub_default'][9])): ?>text-align: <?php echo $css['menu_sub_default'][9]; ?>;<?php endif; ?>
}
.world-menu-sub:hover,
.world-menu-sub.active {
    <?php if (!empty($css['menu_sub_hover'][0])): ?>background-image: url('<?php echo $css['menu_sub_hover'][0]; ?>');<?php endif; ?>
    <?php if (!empty($css['menu_sub_hover'][1])): ?>color: <?php echo $css['menu_sub_hover'][1]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_sub_hover'][2])): ?>background-color: <?php echo $css['menu_sub_hover'][2]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_sub_hover'][3])): ?>font-size: <?php echo $css['menu_sub_hover'][3]; ?>;<?php endif; ?>
    <?php if (!empty($css['menu_sub_hover'][4])): ?>border: <?php echo $css['menu_sub_hover'][4]; ?> <?php echo $css['menu_sub_hover'][5] ?: 'solid'; ?> <?php echo $css['menu_sub_hover'][6]; ?>;<?php endif; ?>
}

/* === Content 영역 === */
.world-content {
    flex: 1;
    min-width: 0;
    box-sizing: border-box;
    <?php if (!empty($css['content_bg_color'][0])): ?>background-color: <?php echo $css['content_bg_color'][0]; ?>;<?php endif; ?>
    <?php if (!empty($css['content_text_color'][0])): ?>color: <?php echo $css['content_text_color'][0]; ?>;<?php endif; ?>
    <?php if (!empty($css['content_border'][0])): ?>border: <?php echo $css['content_border'][0]; ?> <?php echo $css['content_border'][1] ?: 'solid'; ?> <?php echo $css['content_border'][2] ?: 'transparent'; ?>;<?php endif; ?>
    <?php if (!empty($css['content_padding'][0])): ?>padding: <?php echo $css['content_padding'][0]; ?>;<?php endif; ?>
    <?php if (!empty($css['content_bg_img'][0])): ?>background-image: url('<?php echo $css['content_bg_img'][0]; ?>') !important;<?php endif; ?>
    <?php if (!empty($css['content_bg_img'][1])): ?>background-repeat: <?php echo $css['content_bg_img'][1]; ?> !important;<?php endif; ?>
    <?php if (!empty($css['content_bg_img'][2])): ?>background-size: <?php echo $css['content_bg_img'][2]; ?> !important;<?php endif; ?>
}

/* === 이미지 메뉴 Hover 전환 === */
.world-menu-main .wm-img-h,
.world-menu-sub  .wm-img-h { display: none; }
.world-menu-main:not(.wm-no-hover):hover .wm-img-d,
.world-menu-main.active:not(.wm-no-hover) .wm-img-d { display: none; }
.world-menu-main:not(.wm-no-hover):hover .wm-img-h,
.world-menu-main.active:not(.wm-no-hover) .wm-img-h { display: inline-block; }
.world-menu-sub:not(.wm-no-hover):hover .wm-img-d,
.world-menu-sub.active:not(.wm-no-hover) .wm-img-d  { display: none; }
.world-menu-sub:not(.wm-no-hover):hover .wm-img-h,
.world-menu-sub.active:not(.wm-no-hover) .wm-img-h  { display: inline-block; }
