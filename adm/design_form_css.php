<?php  
@include_once('./_common.php');

/*
 * [LEGACY] data/css/_design.config.{N}.css 자동 생성기
 *
 * 2026-04-27 부로 디자인 시스템은
 *   /css/root.php?pd_id=N  (CSS 변수 emit)
 *   /css/_design.config.css (정적, var() 참조)
 * 으로 일원화되었다. 본 파일은 더 이상 런타임에 참조되지 않지만,
 *   - 기존 install/update 흐름의 호환
 *   - root.php 가 동작하지 않는 환경(예: php-cgi 비활성)으로의 폴백
 * 을 위해 유지된다. (theme_design_link_tag() 가 정적 파일 부재 시에만 폴백 사용)
 */

// 디자인 프리셋 (인자로 전달되거나 GET) — 미지정 시 1
$_pd_id = isset($GLOBALS['_design_pd_id']) ? (int)$GLOBALS['_design_pd_id'] : (isset($_GET['pd_id']) ? (int)$_GET['pd_id'] : 1);
if ($_pd_id <= 0) $_pd_id = 1;

// CSS 설정 가져오기
$css_sql = sql_query("select * from {$g5['css_table']} where pd_id = '{$_pd_id}'");
$css = array();
for($i=0; $cs = sql_fetch_array($css_sql); $i++) {
	$css[$cs['cs_name']][0] = $cs['cs_value'];
	$css[$cs['cs_name']][1] = $cs['cs_etc_1'];
	$css[$cs['cs_name']][2] = $cs['cs_etc_2'];
	$css[$cs['cs_name']][3] = $cs['cs_etc_3'];
	$css[$cs['cs_name']][4] = $cs['cs_etc_4'];
	$css[$cs['cs_name']][5] = $cs['cs_etc_5'];
	$css[$cs['cs_name']][6] = $cs['cs_etc_6'];
	$css[$cs['cs_name']][7] = $cs['cs_etc_7'];
	$css[$cs['cs_name']][8] = $cs['cs_etc_8'];
	$css[$cs['cs_name']][9] = $cs['cs_etc_9'];
	$css[$cs['cs_name']][10] = $cs['cs_etc_10'];
}

$tab_width = 1024;

?>
@charset "utf-8";
/***************************************************************
	Design Manager Setting Style: 자동생성 CSS
---------------------------------------------------------------
	- 최종 수정일 : <?php echo date('Y-m-d H:i:s')."\n"?>
***************************************************************/

<?php /**************************************************************
	폰트 기본 색상 지정
***************************************************************/?>

<?php  if($css['color_default'][0]) { ?>
body,
.txt-default	{ color: <?php echo $css['color_default'][0]?>; }
<?php  } ?>
<?php  if($css['color_point'][0]) { ?>
a,
.txt-point	{ color: <?php echo $css['color_point'][0]?>; }
<?php  } ?>


<?php /**************************************************************
	기본 사이트 배경 설정
	-
	모바일 기준 : width 1000px
***************************************************************/?>
@media all and (min-width: <?php echo ($tab_width + 1)?>px) { 
	html.single:before	{
	<?php  if($css['background'][0]) { ?>
	background-image: url('<?php echo $css['background'][0]?>');
	<?php  } if($css['background'][1]) { ?>
	background-color: <?php echo $css['background'][1]?>;
	<?php  } if($css['background'][2]) { ?>
	background-repeat: <?php echo $css['background'][2]?>;
	<?php  } if($css['background'][3]) { ?>
	background-position: <?php echo $css['background'][3]?>;
	<?php  } if($css['background'][4]) { ?>
	background-size: <?php echo $css['background'][4]?>;
	<?php  } if($css['background'][5]) { ?>
	background-attachment: <?php echo $css['background'][5]?>;
	<?php  } ?>
	}
}

@media all and (max-width: <?php echo $tab_width?>px) {
	html.single:before	{
	<?php  if($css['m_background'][0]) { ?>
	background-image: url('<?php echo $css['m_background'][0]?>');
	<?php  } else { ?>
	background-image: none;
	<?php  } if($css['m_background'][1]) { ?>
	background-color: <?php echo $css['m_background'][1]?>;
	<?php  } if($css['m_background'][2]) { ?>
	background-repeat: <?php echo $css['m_background'][2]?>;
	<?php  } if($css['m_background'][3]) { ?>
	background-position: <?php echo $css['m_background'][3]?>;
	<?php  } if($css['m_background'][4]) { ?>
	background-size: <?php echo $css['m_background'][4]?>;
	<?php  } if($css['m_background'][5]) { ?>
	background-attachment: <?php echo $css['m_background'][5]?>;
	<?php  } ?>
	}
}


<?php /**************************************************************
	HEADER / GNB STYLE
***************************************************************/?>
@media all and (min-width: <?php echo ($tab_width + 1)?>px) { 
	#header	{
	<?php  if($css['header_background'][0]) { ?>
	background-image: url('<?php echo $css['header_background'][0]?>');
	<?php  } if($css['header_background'][1]) { ?>
	background-color: <?php echo $css['header_background'][1]?>;
	<?php  } if($css['header_background'][2]) { ?>
	background-repeat: <?php echo $css['header_background'][2]?>;
	<?php  } if($css['header_background'][3]) { ?>
	background-position: <?php echo $css['header_background'][3]?>;
	<?php  } if($css['header_background'][4]) { ?>
	background-size: <?php echo $css['header_background'][4]?>;
	<?php  } if($css['header_background'][5]) { ?>
	background-attachment: <?php echo $css['header_background'][5]?>;
	<?php  } ?>
	}

	#gnb	{
	<?php  if($css['menu_background'][0]) { ?>
	background-image: url('<?php echo $css['menu_background'][0]?>');
	<?php  } if($css['menu_background'][1]) { ?>
	background-color: <?php echo $css['menu_background'][1]?>;
	<?php  } if($css['menu_background'][2]) { ?>
	background-repeat: <?php echo $css['menu_background'][2]?>;
	<?php  } if($css['menu_background'][3]) { ?>
	background-position: <?php echo $css['menu_background'][3]?>;
	<?php  } if($css['menu_background'][4]) { ?>
	background-size: <?php echo $css['menu_background'][4]?>;
	<?php  } if($css['menu_background'][5]) { ?>
	background-attachment: <?php echo $css['menu_background'][5]?>;
	<?php  } ?>
	}

	#gnb_control_box	{ display: none; }
}

@media all and (max-width: <?php echo $tab_width?>px) {

	#header	{
	<?php  if($css['m_header_background'][0]) { ?>
	background-image: url('<?php echo $css['m_header_background'][0]?>');
	<?php  } if($css['m_header_background'][1]) { ?>
	background-color: <?php echo $css['m_header_background'][1]?>;
	<?php  } if($css['m_header_background'][2]) { ?>
	background-repeat: <?php echo $css['m_header_background'][2]?>;
	<?php  } if($css['m_header_background'][3]) { ?>
	background-position: <?php echo $css['m_header_background'][3]?>;
	<?php  } if($css['m_header_background'][4]) { ?>
	background-size: <?php echo $css['m_header_background'][4]?>;
	<?php  } if($css['m_header_background'][5]) { ?>
	background-attachment: <?php echo $css['m_header_background'][5]?>;
	<?php  } ?>
	}

	#gnb	{
	<?php  if($css['m_menu_background'][0]) { ?>
	background-image: url('<?php echo $css['m_menu_background'][0]?>');
	<?php  } if($css['m_menu_background'][1]) { ?>
	background-color: <?php echo $css['m_menu_background'][1]?>;
	<?php  } if($css['m_menu_background'][2]) { ?>
	background-repeat: <?php echo $css['m_menu_background'][2]?>;
	<?php  } if($css['m_menu_background'][3]) { ?>
	background-position: <?php echo $css['m_menu_background'][3]?>;
	<?php  } if($css['m_menu_background'][4]) { ?>
	background-size: <?php echo $css['m_menu_background'][4]?>;
	<?php  } if($css['m_menu_background'][5]) { ?>
	background-attachment: <?php echo $css['m_menu_background'][5]?>;
	<?php  } ?>
	}

	#gnb_control_box	{
	display: block;
	}
}


<?php  if($css['use_header'][0] == 'N') { 
/****************************************************
	헤더 사용 여부 설정
*****************************************************/ ?>
#header,
#footer	{ display: none !important; }
#body	{ margin: 0 !important; }
<?php  } ?>

<?php  if($css['menu_pos'][0] == 'left') { 
/****************************************************
	메뉴 좌측 레이아웃 설정
*****************************************************/ ?>
#body	{ margin-left: <?php echo $css['menu_width'][0]?>px; }
#header	{
	position: fixed;
	top: 0;
	left: 0;
	bottom: 0;
	overflow-y: auto;
	width: <?php echo $css['menu_width'][0]?>px;
	
}
#logo	{
	padding:20px 0;
	text-align: center;
}


<?php  } else if($css['menu_pos'][0] == 'top') {
/****************************************************
	메뉴 상단 레이아웃 설정
*****************************************************/ ?>

#header	{
	position: relative;
	margin: 0;
	padding: 0;
	clear: both;
	height: <?php echo $css['menu_height'][0]?>px;
}
#header .fix-layout	{ height: 100%; }
#logo	{
	display: block;
	position: absolute;
	top: 50%;
	left: 0%;
	transform: translateY(-50%);
}
<?php  } ?>

/*#gnb	{ float: right; }*/
#gnb,
#gnb *	{
	color: <?php echo $css['menu_text'][0]?>;
	font-size: <?php echo $css['menu_text'][1]?>px;
}
#gnb a:hover {
	color: <?php echo $css['menu_text'][2]?>;
	font-size: <?php echo $css['menu_text'][3]?>px;
}



<?php /**************************************************************
	스크롤 / 마우스 드래그 블록 색상 지정
***************************************************************/?>

<?php /*** Scroll Style ***************/?>
<?php  if($css['color_bak'][0]) { ?>
*::-webkit-scrollbar-track	{ background-color: <?php echo $css['color_bak'][0]?>; }
<?php  } if($css['color_point'][0]) { ?>
*::-webkit-scrollbar-thumb	{ background: <?php echo $css['color_point'][0]?>; }
<?php  } ?>

<?php /*** Block Style ***************/?>
<?php  if($css['color_point'][0]) { ?>
* { outline-color: <?php echo $css['color_point'][0]?>; }
::selection	{ background:<?php echo $css['color_point'][0]?>; }
::-moz-selection	{ background:<?php echo $css['color_point'][0]?>; }
::-webkit-selection	{ background:<?php echo $css['color_point'][0]?>; }
<?php  } ?>

<?php  if($css['color_bak'][0]) { ?>
::selection	{ color:<?php echo $css['color_bak'][0]?>; }
::-moz-selection	{ color:<?php echo $css['color_bak'][0]?>; }
::-webkit-selection	{ color:<?php echo $css['color_bak'][0]?>; }
<?php  } ?>



<?php /**************************************************************
	버튼 색상 지정
***************************************************************/?>

.ui-btn	{
	color:	<?php echo $css['btn_default'][0]?>;
	background:	<?php echo $css['btn_default'][1]?>;
	border-color:	<?php echo $css['btn_default'][2]?>;
}
.ui-btn:hover	{
	color:	<?php echo $css['btn_default'][3]?>;
	background:	<?php echo $css['btn_default'][4]?>;
	border-color:	<?php echo $css['btn_default'][5]?>;
}

.ui-btn.point	{
	color:	<?php echo $css['btn_point'][0]?>;
	background:	<?php echo $css['btn_point'][1]?>;
	border-color:	<?php echo $css['btn_point'][2]?>;
}
.ui-btn.point:hover	{
	color:	<?php echo $css['btn_point'][3]?>;
	background:	<?php echo $css['btn_point'][4]?>;
	border-color:	<?php echo $css['btn_point'][5]?>;
}

.ui-btn.etc	{
	color:	<?php echo $css['btn_etc'][0]?>;
	background:	<?php echo $css['btn_etc'][1]?>;
	border-color:	<?php echo $css['btn_etc'][2]?>;
}
.ui-btn.etc:hover	{
	color:	<?php echo $css['btn_etc'][3]?>;
	background:	<?php echo $css['btn_etc'][4]?>;
	border-color:	<?php echo $css['btn_etc'][5]?>;
}
.swiper-pagination-bullet-active {background:	<?php echo $css['btn_point'][4]?>;}

<?php /**************************************************************
	페이징 스타일 설정
***************************************************************/?>

.pg_wrap .pg_page	{
	color:	<?php echo $css['btn_default'][0]?>;
	background:	<?php echo $css['btn_default'][1]?>;
	border-color:	<?php echo $css['btn_default'][2]?>;
}
.pg_wrap .pg_page:hover	{
	color:	<?php echo $css['btn_default'][3]?>;
	background:	<?php echo $css['btn_default'][4]?>;
	border-color:	<?php echo $css['btn_default'][5]?>;
}

.pg_wrap .pg_current,
.pg_wrap .pg_current:hover	{
	color:	<?php echo $css['btn_point'][0]?>;
	background:	<?php echo $css['btn_point'][1]?>;
	border-color:	<?php echo $css['btn_point'][2]?>;
}




<?php /**************************************************************
	이퀄라이저 설정
***************************************************************/?>

.bar-equalizer i	{
<?php  if($css['equalizer'][0]) { ?>
	background: <?php echo $css['equalizer'][0]?>;
<?php  } if($css['equalizer'][1]) { ?>
	-webkit-box-shadow: 0px 0px 3px 0px <?php echo $css['equalizer'][1]?>;
	-moz-box-shadow: 0px 0px 3px 0px <?php echo $css['equalizer'][1]?>;
	box-shadow: 0px 0px 3px 0px <?php echo $css['equalizer'][1]?>;
<?php  } ?>
}


<?php /**************************************************************
	스테이터스바 설정
***************************************************************/?>

.status-bar dd p	{
	background: <?php echo $css['color_default'][0]?>;
}
.status-bar dd p span	{
	background: <?php echo $css['color_point'][0]?>;
	opacity: .5;
}
.status-bar dd p sup	{
	background: <?php echo $css['color_point'][0]?>;
}
.status-bar dd p i	{
	color: <?php echo $css['color_bak'][0]?>;
}


<?php /**************************************************************
	구분선 설정
***************************************************************/?>

hr.line	{
	background: <?php echo $css['color_point'][0]?>;
}



<?php /**************************************************************
	인풋 타입
***************************************************************/?>

.form-input,
input[type="file"],
input[type="text"],
input[type="number"],
input[type="password"],
textarea,
select	{
	color:	<?php echo $css['input_bak'][1]?>;
	background:	<?php echo $css['input_bak'][0]?>;
	border-color:	<?php echo $css['input_bak'][2]?>;
	
	<?php  if($css['input_bak'][3]) { ?>border-top-left-radius:<?php echo $css['input_bak'][3]?>px;<?php  } ?>
	<?php  if($css['input_bak'][4]) { ?>border-top-right-radius:<?php echo $css['input_bak'][4]?>px;<?php  } ?>
	<?php  if($css['input_bak'][5]) { ?>border-bottom-right-radius:<?php echo $css['input_bak'][5]?>px;<?php  } ?>
	<?php  if($css['input_bak'][6]) { ?>border-bottom-left-radius:<?php echo $css['input_bak'][6]?>px;<?php  } ?>
}

::-webkit-input-placeholder {
	color: <?php echo $css['input_bak'][1]?>;
}
:-moz-placeholder { /* Mozilla Firefox 4 to 18 */
	color: <?php echo $css['input_bak'][1]?>;
	opacity:  1;
}
::-moz-placeholder { /* Mozilla Firefox 19+ */
	color: <?php echo $css['input_bak'][1]?>;
	opacity:  1;
}
:-ms-input-placeholder { /* Internet Explorer 10-11 */
	color: <?php echo $css['input_bak'][1]?>;
}


*::-webkit-input-placeholder	{ color:#000; } 
textarea:-moz-placeholder, input:-moz-placeholder	{ color:#000; }
input:-webkit-autofill,
textarea:-webkit-autofill,
select:-webkit-autofill {
  background-color: <?php echo $css['input_bak'][0]?> !important;
  color: <?php echo $css['input_bak'][1]?>;
}



<?php /**************************************************************
	비쥬얼 슬라이드 설정
***************************************************************/?>

.flex-control-paging a	{ background: <?php echo $css['color_default'][0]?>; }
.flex-control-paging a.flex-active	{ background: <?php echo $css['color_point'][0]?>; }



<?php /**************************************************************
	기본 박스 설정
***************************************************************/?>

.theme-box	{
<?php  if($css['box_style'][0]) { ?>
	background-color: <?php echo $css['box_style'][0]?>;
<?php  } if($css['box_style'][2]) { ?>
	border: 1px solid <?php echo $css['box_style'][2]?>;
<?php  } if($css['box_style'][1]) { ?>
	color: <?php echo $css['box_style'][1]?>;
<?php  } ?>
	<?php  if($css['box_style'][3]) { ?>border-top-left-radius:<?php echo $css['box_style'][3]?>px;<?php  } ?>
	<?php  if($css['box_style'][4]) { ?>border-top-right-radius:<?php echo $css['box_style'][4]?>px;<?php  } ?>
	<?php  if($css['box_style'][5]) { ?>border-bottom-right-radius:<?php echo $css['box_style'][5]?>px;<?php  } ?>
	<?php  if($css['box_style'][6]) { ?>border-bottom-left-radius:<?php echo $css['box_style'][6]?>px;<?php  } ?>
}
.theme-box.no-link a	{
<?php  if($css['box_style'][1]) { ?>
	color: <?php echo $css['box_style'][1]?>;
<?php  } ?>
}


<?php /**************************************************************
	인벤토리 팝업 설정 (기본 박스와 스타일을 함께 합니다)
***************************************************************/?>

.inven-popup-viewer {
<?php
	/* /css/preset.css 에서 제거된 관리자 기본을 fallback 으로 제공 (공백 방지) */
	$_box_bg     = !empty($css['box_style'][0]) ? $css['box_style'][0] : 'rgba(0,0,0,.5)';
	$_box_color  = !empty($css['box_style'][1]) ? $css['box_style'][1] : '#eeeeee';
	$_box_border = !empty($css['box_style'][2]) ? $css['box_style'][2] : '';
?>
	background-color: <?php echo $_box_bg?>;
	color: <?php echo $_box_color?>;
<?php  if($_box_border) { ?>
	border: 1px solid <?php echo $_box_border?>;
<?php  } ?>
}



<?php /**************************************************************
	서브메뉴 출력
***************************************************************/?>

#submenu	{
	background-color:	<?php echo $css['sub_menu'][0]?>;
	color:	<?php echo $css['sub_menu'][1]?>;
<?php  
	$css['sub_menu']['border'] = explode("||", $css['sub_menu'][5]);
	for($i=0; $i < count($css['sub_menu']['border']); $i++) {
	if($css['sub_menu']['border'][$i]) { ?>
	border-<?php echo $css['sub_menu']['border'][$i]?>-color:	<?php echo $css['sub_menu'][2]?>;
	border-<?php echo $css['sub_menu']['border'][$i]?>-style:	<?php echo $css['sub_menu'][3]?>;
	border-<?php echo $css['sub_menu']['border'][$i]?>-width: <?php echo $css['sub_menu'][4]?>px;
<?php  } } ?>
}
#submenu a	{
	color: <?php echo $css['sub_menu'][1]?>;
}
#submenu a:hover	{
	color: <?php echo $css['sub_menu'][6]?>;
}



<?php /**************************************************************
	테이블 설정
***************************************************************/?>

.theme-list,
.theme-form	{
	background-color:	<?php echo $css['board_table'][0]?>;
	color:	<?php echo $css['board_table'][1]?>;
<?php  
	$css['board_table']['border'] = explode("||", $css['board_table'][5]);
	for($i=0; $i < count($css['board_table']['border']); $i++) {
	if($css['board_table']['border'][$i]) { ?>
	border-<?php echo $css['board_table']['border'][$i]?>-color:	<?php echo $css['board_table'][2]?>;
	border-<?php echo $css['board_table']['border'][$i]?>-style:	<?php echo $css['board_table'][3]?>;
	border-<?php echo $css['board_table']['border'][$i]?>-width: <?php echo $css['board_table'][4]?>px;
<?php  } } ?>
}

/*** Form Area ***/
.theme-form th	{
	background-color:	<?php echo $css['form_header'][0]?>;
	color:	<?php echo $css['form_header'][1]?>;
<?php  
	$css['form_header']['border'] = explode("||", $css['form_header'][5]);
	for($i=0; $i < count($css['form_header']['border']); $i++) {
	if($css['form_header']['border'][$i]) { ?>
	border-<?php echo $css['form_header']['border'][$i]?>-color:	<?php echo $css['form_header'][2]?>;
	border-<?php echo $css['form_header']['border'][$i]?>-style:	<?php echo $css['form_header'][3]?>;
	border-<?php echo $css['form_header']['border'][$i]?>-width:	<?php echo $css['form_header'][4]?>px;
<?php  } } ?>
}
.theme-form td	{
	background-color:	<?php echo $css['form_body'][0]?>;
	color:	<?php echo $css['form_body'][1]?>;
<?php  
	$css['form_body']['border'] = explode("||", $css['form_body'][5]);
	for($i=0; $i < count($css['form_body']['border']); $i++) {
	if($css['form_body']['border'][$i]) { ?>
	border-<?php echo $css['form_body']['border'][$i]?>-color:	<?php echo $css['form_body'][2]?>;
	border-<?php echo $css['form_body']['border'][$i]?>-style:	<?php echo $css['form_body'][3]?>;
	border-<?php echo $css['form_body']['border'][$i]?>-width:	<?php echo $css['form_body'][4]?>px;
<?php  } } ?>
}

/*** List Area ***/
.theme-list th	{
	background-color:	<?php echo $css['list_header'][0]?>;
	color:	<?php echo $css['list_header'][1]?>;
<?php  
	$css['list_header']['border'] = explode("||", $css['list_header'][5]);
	for($i=0; $i < count($css['list_header']['border']); $i++) {
	if($css['list_header']['border'][$i]) { ?>
	border-<?php echo $css['list_header']['border'][$i]?>-color:	<?php echo $css['list_header'][2]?>;
	border-<?php echo $css['list_header']['border'][$i]?>-style:	<?php echo $css['list_header'][3]?>;
	border-<?php echo $css['list_header']['border'][$i]?>-width:	<?php echo $css['list_header'][4]?>px;
<?php  } } ?>
}
.theme-list td	{
	background-color:	<?php echo $css['list_body'][0]?>;
	color:	<?php echo $css['list_body'][1]?>;
<?php  
	$css['list_body']['border'] = explode("||", $css['list_body'][5]);
	for($i=0; $i < count($css['list_body']['border']); $i++) {
	if($css['list_body']['border'][$i]) { ?>
	border-<?php echo $css['list_body']['border'][$i]?>-color:	<?php echo $css['list_body'][2]?>;
	border-<?php echo $css['list_body']['border'][$i]?>-style:	<?php echo $css['list_body'][3]?>;
	border-<?php echo $css['list_body']['border'][$i]?>-width:	<?php echo $css['list_body'][4]?>px;
<?php  } } ?>
}



<?php /**************************************************************
	탭 설정
***************************************************************/?>

#tab_list { border-color: <?php echo $css['btn_point'][2]?>; }



<?php /**************************************************************
	인벤토리 설정
***************************************************************/?>

.inventory-list a i	{
	/*
	background: <?php echo $css['color_point'][0]?>;
	color: <?php echo $css['color_bak'][0]?>;
	*/

	border: 1px solid <?php echo $css['color_point'][0]?>;
	background: rgba(0, 0, 0, .7);
	color: #fff;
}




<?php /**************************************************************
	로드비 게시판 설정
***************************************************************/?>

#load_log_board	{
	<?php  if($css['mmb_contain_bak'][0]) { ?>
	background-image: url('<?php echo $css['mmb_contain_bak'][0]?>');
	<?php  } if($css['mmb_contain_bak'][1]) { ?>
	background-color: <?php echo $css['mmb_contain_bak'][1]?>;
	<?php  } if($css['mmb_contain_bak'][2]) { ?>
	background-repeat: <?php echo $css['mmb_contain_bak'][2]?>;
	<?php  } if($css['mmb_contain_bak'][3]) { ?>
	background-position: <?php echo $css['mmb_contain_bak'][3]?>;
	<?php  } if($css['mmb_contain_bak'][4]) { ?>
	background-size: <?php echo $css['mmb_contain_bak'][4]?>;
	<?php  } if($css['mmb_contain_bak'][5]) { ?>
	background-attachment: <?php echo $css['mmb_contain_bak'][5]?>;
	<?php  } ?>
}

.board-notice	{
	<?php  if($css['mmb_notice'][0]) { ?>
	background-color: <?php echo $css['mmb_notice'][0]?>;
	<?php  } if($css['mmb_notice'][1]) { ?>
	color: <?php echo $css['mmb_notice'][1]?>;
	<?php  } 
	$css['mmb_notice']['border'] = explode("||", $css['mmb_notice'][5]);
	for($i=0; $i < count($css['mmb_notice']['border']); $i++) {
	if($css['mmb_notice']['border'][$i]) { ?>
	border-<?php echo $css['mmb_notice']['border'][$i]?>-color:	<?php echo $css['mmb_notice'][2]?>;
	border-<?php echo $css['mmb_notice']['border'][$i]?>-style:	<?php echo $css['mmb_notice'][3]?>;
	border-<?php echo $css['mmb_notice']['border'][$i]?>-width: <?php echo $css['mmb_notice'][4]?>px;
	<?php  } } ?>
}

#log_list	{
	<?php  if($css['mmb_list'][0]) { ?>
	background-color: <?php echo $css['mmb_list'][0]?>;
	<?php  } if($css['mmb_list'][1]) { ?>
	color: <?php echo $css['mmb_list'][1]?>;
	<?php  } 
	$css['mmb_list']['border'] = explode("||", $css['mmb_list'][5]);
	for($i=0; $i < count($css['mmb_list']['border']); $i++) {
	if($css['mmb_list']['border'][$i]) { ?>
	border-<?php echo $css['mmb_list']['border'][$i]?>-color:	<?php echo $css['mmb_list'][2]?>;
	border-<?php echo $css['mmb_list']['border'][$i]?>-style:	<?php echo $css['mmb_list'][3]?>;
	border-<?php echo $css['mmb_list']['border'][$i]?>-width: <?php echo $css['mmb_list'][4]?>px;
	<?php  } } ?>
}
<?php 
$is_item_area = false;
?>
#log_list .item	{
	<?php  if($css['mmb_list_item'][0]) { $is_item_area = true; ?>
	background-color: <?php echo $css['mmb_list_item'][0]?>;
	<?php  } if($css['mmb_list_item'][1]) { ?>
	color: <?php echo $css['mmb_list_item'][1]?>;
	<?php  } 
	$css['mmb_list_item']['border'] = explode("||", $css['mmb_list_item'][5]);
	for($i=0; $i < count($css['mmb_list_item']['border']); $i++) {
	if($css['mmb_list_item']['border'][$i]) { $is_item_area = true; ?>
	border-<?php echo $css['mmb_list_item']['border'][$i]?>-color:	<?php echo $css['mmb_list_item'][2]?>;
	border-<?php echo $css['mmb_list_item']['border'][$i]?>-style:	<?php echo $css['mmb_list_item'][3]?>;
	border-<?php echo $css['mmb_list_item']['border'][$i]?>-width: <?php echo $css['mmb_list_item'][4]?>px;
	<?php  } } ?>

	<?php  if($css['mmb_list_item'][6]) { ?>
	margin-bottom: <?php echo $css['mmb_list_item'][6]?>px !important;
	<?php  } ?>
}

#log_list .item .item-inner .ui-pic	{
	<?php  if($css['mmb_log'][0]) { ?>
	background-color: <?php echo $css['mmb_log'][0]?>;
	<?php  } if($css['mmb_log'][1]) { ?>
	color: <?php echo $css['mmb_log'][1]?>;
	<?php  } 
	$css['mmb_log']['border'] = explode("||", $css['mmb_log'][5]);
	for($i=0; $i < count($css['mmb_log']['border']); $i++) {
	if($css['mmb_log']['border'][$i]) { ?>
	border-<?php echo $css['mmb_log']['border'][$i]?>-color:	<?php echo $css['mmb_log'][2]?>;
	border-<?php echo $css['mmb_log']['border'][$i]?>-style:	<?php echo $css['mmb_log'][3]?>;
	border-<?php echo $css['mmb_log']['border'][$i]?>-width: <?php echo $css['mmb_log'][4]?>px;
	<?php  } } ?>
}

<?php 
$is_comment_area = false;
?>

#log_list .item .item-inner .item-comment	{
	<?php  if($css['mmb_reply_item'][0]) { $is_comment_area = true; ?>
	background-color: <?php echo $css['mmb_reply_item'][0]?>;
	<?php  } if($css['mmb_reply_item'][1]) { ?>
	color: <?php echo $css['mmb_reply_item'][1]?>;
	<?php  } 
	$css['mmb_reply_item']['border'] = explode("||", $css['mmb_reply_item'][5]);
	for($i=0; $i < count($css['mmb_reply_item']['border']); $i++) {
	if($css['mmb_reply_item']['border'][$i]) { $is_comment_area = true; ?>
	border-<?php echo $css['mmb_reply_item']['border'][$i]?>-color:	<?php echo $css['mmb_reply_item'][2]?>;
	border-<?php echo $css['mmb_reply_item']['border'][$i]?>-style:	<?php echo $css['mmb_reply_item'][3]?>;
	border-<?php echo $css['mmb_reply_item']['border'][$i]?>-width: <?php echo $css['mmb_reply_item'][4]?>px;
	<?php  } } ?>
	<?php  if($css['mmb_reply_item'][6]) { ?>
	margin-bottom: <?php echo $css['mmb_reply_item'][6]?>px !important;
	<?php  } ?>
}


#log_list .item .item-inner	.ui-comment	{
	<?php  if($css['mmb_reply'][0]) { $is_comment_area = true; ?>
	background-color: <?php echo $css['mmb_reply'][0]?>;
	<?php  } if($css['mmb_reply'][1]) { ?>
	color: <?php echo $css['mmb_reply'][1]?>;
	<?php  } 
	$css['mmb_reply']['border'] = explode("||", $css['mmb_reply'][5]);
	for($i=0; $i < count($css['mmb_reply']['border']); $i++) {
	if($css['mmb_reply']['border'][$i]) { $is_comment_area = true; ?>
	border-<?php echo $css['mmb_reply']['border'][$i]?>-color:	<?php echo $css['mmb_reply'][2]?>;
	border-<?php echo $css['mmb_reply']['border'][$i]?>-style:	<?php echo $css['mmb_reply'][3]?>;
	border-<?php echo $css['mmb_reply']['border'][$i]?>-width: <?php echo $css['mmb_reply'][4]?>px;
	<?php  } } ?>

	<?php  if($is_item_area && $is_comment_area) { ?>
	padding-left: 15px;
	padding-right: 15px;
	<?php  } ?>
}


#log_list .item .item-inner .co-header p,
#log_list .item .item-inner .co-header p a	{
	<?php  if($css['mmb_name'][0]) { ?>
	color: <?php echo $css['mmb_name'][0]?>;
	<?php  } ?>
	<?php  if($css['mmb_name'][1]) { ?>
	font-size: <?php echo $css['mmb_name'][1]?>px;
	<?php  } ?>
}

#log_list .item .item-inner .co-header p.owner,
#log_list .item .item-inner .co-header p.owner a	{
	<?php  if($css['mmb_owner_name'][0]) { ?>
	color: <?php echo $css['mmb_owner_name'][0]?>;
	<?php  } ?>
	<?php  if($css['mmb_owner_name'][1]) { ?>
	font-size: <?php echo $css['mmb_owner_name'][1]?>px;
	<?php  } ?>
}

#log_list .item .item-inner .co-footer .date	{
	<?php  if($css['mmb_datetime'][0]) { ?>
	color: <?php echo $css['mmb_datetime'][0]?>;
	<?php  } ?>
	<?php  if($css['mmb_datetime'][1]) { ?>
	font-size: <?php echo $css['mmb_datetime'][1]?>px;
	<?php  } ?>
}
#log_list .item .item-inner .co-content .other-site-link	{
	<?php  if($css['mmb_link'][0]) { ?>
	color: <?php echo $css['mmb_link'][0]?>;
	<?php  } ?>
}
#log_list .item .item-inner .co-content .link_hash_tag	{
	<?php  if($css['mmb_hash'][0]) { ?>
	color: <?php echo $css['mmb_hash'][0]?>;
	<?php  } ?>
}
#log_list .item .item-inner .co-content .log_link_tag	{
	<?php  if($css['mmb_log_ank'][0]) { ?>
	color: <?php echo $css['mmb_log_ank'][0]?>;
	<?php  } ?>
}
#log_list .item .item-inner .co-content .member_call	{
	<?php  if($css['mmb_call'][0]) { ?>
	color: <?php echo $css['mmb_call'][0]?>;
	<?php  } ?>
}

/* ===== 로드비 액션박스 (.log-action-box) ===== */
.log-action-box {
	<?php if(!empty($css['log_action_box'][0])) { ?>background-image: url('<?php echo $css['log_action_box'][0]?>');background-repeat:no-repeat;background-size:cover;<?php } ?>
	<?php if(!empty($css['log_action_box'][1])) { ?>background-color: <?php echo $css['log_action_box'][1]?>;<?php } ?>
	<?php if(!empty($css['log_action_box'][2])) { ?>color: <?php echo $css['log_action_box'][2]?>;<?php } ?>
	<?php if(!empty($css['log_action_box'][3])) { ?>border: <?php echo $css['log_action_box'][3]?>;<?php } ?>
	<?php if(!empty($css['log_action_box'][4])) { ?>padding: <?php echo $css['log_action_box'][4]?>;<?php } ?>
	<?php if(!empty($css['log_action_box'][5])) { ?>font-size: <?php echo $css['log_action_box'][5]?>;<?php } ?>
}
<?php if(!empty($css['log_action_box'][6])) { ?>
.log-action-box:before { content:""; display:block; background-image:url('<?php echo $css['log_action_box'][6]?>'); background-repeat:no-repeat; background-size:contain; height:14px; margin-bottom:4px; }
<?php } ?>
<?php if(!empty($css['log_action_box'][7])) { ?>
.log-action-box:after { content:""; display:block; background-image:url('<?php echo $css['log_action_box'][7]?>'); background-repeat:no-repeat; background-size:contain; height:14px; margin-top:4px; }
<?php } ?>

/* ===== 인벤토리 아이템 박스 (.item-box) ===== */
.item-box {
	<?php if(!empty($css['inventory_item_box'][0])) { ?>background-image: url('<?php echo $css['inventory_item_box'][0]?>');background-repeat:no-repeat;background-size:cover;<?php } ?>
	<?php if(!empty($css['inventory_item_box'][1])) { ?>background-color: <?php echo $css['inventory_item_box'][1]?>;<?php } ?>
	<?php if(!empty($css['inventory_item_box'][2])) { ?>color: <?php echo $css['inventory_item_box'][2]?>;<?php } ?>
	<?php if(!empty($css['inventory_item_box'][3])) { ?>width: <?php echo $css['inventory_item_box'][3]?>;height: <?php echo $css['inventory_item_box'][3]?>;<?php } ?>
	<?php if(!empty($css['inventory_item_box'][4])) { ?>padding: <?php echo $css['inventory_item_box'][4]?>;<?php } ?>
	border: <?php echo !empty($css['inventory_item_box'][5]) ? $css['inventory_item_box'][5] : '1px solid #ccc'; ?>;
}
/* a.inven-open-popup 은 항상 100%/100% flex center (설정 무관) */
.item-box a.inven-open-popup { width:100%; height:100%; display:flex; justify-content:center; align-items:center; position:relative; }
<?php if(!empty($css['inventory_item_box'][6]) || !empty($css['inventory_item_box'][7]) || !empty($css['inventory_item_box'][8])) { ?>
.item-box i {
	<?php if(!empty($css['inventory_item_box'][6])) { ?>background: <?php echo $css['inventory_item_box'][6]?>;<?php } ?>
	<?php if(!empty($css['inventory_item_box'][7])) { ?>color: <?php echo $css['inventory_item_box'][7]?>;<?php } ?>
	<?php if(!empty($css['inventory_item_box'][8])) { ?>font-size: <?php echo $css['inventory_item_box'][8]?>;<?php } ?>
}
<?php } ?>

/* ===== 아이템 팝업 (#inven_popup_viewer) ===== */
#inven_popup_viewer {
	<?php if(!empty($css['inventory_item_popup'][0])) { ?>background-image: url('<?php echo $css['inventory_item_popup'][0]?>');background-repeat:no-repeat;background-size:cover;<?php } ?>
	<?php if(!empty($css['inventory_item_popup'][1])) { ?>background-color: <?php echo $css['inventory_item_popup'][1]?>;<?php } ?>
	<?php if(!empty($css['inventory_item_popup'][2])) { ?>color: <?php echo $css['inventory_item_popup'][2]?>;<?php } ?>
	<?php if(!empty($css['inventory_item_popup'][3])) { ?>font-size: <?php echo $css['inventory_item_popup'][3]?>;<?php } ?>
	<?php if(!empty($css['inventory_item_popup'][4])) { ?>padding: <?php echo $css['inventory_item_popup'][4]?>;<?php } ?>
	<?php if(!empty($css['inventory_item_popup'][5])) { ?>border: <?php echo $css['inventory_item_popup'][5]?>;<?php } ?>
}