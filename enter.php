<?php
	include_once('./_common.php');

	// 모바일 index.php 가 이 파일을 include 하면 SCRIPT_FILENAME 은 index.php 라
	// 디스패처가 enter 슬롯을 못 잡는다. 직접 호출과 동일하게 여기서 오버라이드.
	if (function_exists('theme_include') && ($_p = theme_include('enter', 'enter.php'))) {
		include $_p;
		return;
	}

	if ($is_member) {
		goto_url(G5_URL.'/main.php');
	}

	/*********** Logo Data ************/
	$logo   = function_exists('get_logo') ? get_logo('pc') : '';
	$m_logo = function_exists('get_logo') ? get_logo('mo') : '';
	$logo_data = '';
	if ($logo) {
		$logo_data .= '<img src="'.htmlspecialchars($logo, ENT_QUOTES, 'UTF-8').'" alt="" class="only-pc">';
	}
	if ($m_logo) {
		$logo_data .= '<img src="'.htmlspecialchars($m_logo, ENT_QUOTES, 'UTF-8').'" alt="" class="not-pc">';
	}
	/*********************************/
?>
<!doctype html>
<html lang="ko">
<head>
	<script>if (!window.parent || window.parent === window) { document.documentElement.classList.add('single'); }</script>
	<meta charset="utf-8">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta http-equiv="imagetoolbar" content="no">
	<meta http-equiv="X-UA-Compatible" content="IE=Edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<meta name="title" content="<?php echo $g5['title']=isset($g5['title'])?$g5['title']:'아보카도 8'?>">
	<meta name="keywords" content="<?php echo $config['cf_site_descript']?>">
	<meta name="description" content="<?php echo $config['cf_site_descript']?>">

	<meta property="og:title" content="<?php echo $g5['title']?>">
	<meta property="og:description" content="<?php echo $config['cf_site_descript']?>">
	<meta property="og:url" content="<?php echo G5_URL?>">

	<title><?php echo $g5['title']?></title>

	<link rel="shortcut icon" href="<?php echo $config['cf_favicon']?>">
	<link rel="icon" href="<?php echo $config['cf_favicon']?>">
	<?php
		if (function_exists('emit_enter_head_css')) {
			emit_enter_head_css();
		}
	?>

	<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
</head>
<body>


<div class="wrapper">
	<div class="inner">
		<div class="index-logo">
			<a href="./main.php">
				<?php echo $logo_data?>
				<p class="txt-default">본 홈페이지는 1920 * 1080 PC를 기준으로 제작되었으며, 크롬 브라우저 이용을 권장합니다.</p>
			</a>
		</div>
	</div>
</div>

<script>

window.onload=function() {
	$('html').addClass('on')
	setTimeout(function() { $('html').addClass('active') }, 800);
};
</script>

</body>
</html>
