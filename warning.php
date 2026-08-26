<?php
	include_once('./_common.php');

	// enter.php 와 같이 head.sub 를 타지 않는 독립 페이지.
	// 테마에 warning.php 가 있으면 그쪽을 쓴다.
	if (function_exists('theme_include') && ($_p = theme_include('warning', 'warning.php'))) {
		include $_p;
		return;
	}

	$g5['title'] = '접근 안내';
	$warn_msg = '';
	if (function_exists('get_session')) {
		$warn_msg = trim((string)get_session('ss_perm_deny_msg'));
		if (function_exists('set_session')) {
			set_session('ss_perm_deny_msg', '');
		}
	}
	if ($warn_msg === '') {
		$warn_msg = '접근 권한이 없습니다.';
	}

	$logo   = function_exists('get_logo') ? get_logo('pc') : '';
	$m_logo = function_exists('get_logo') ? get_logo('mo') : '';
	$logo_data = '';
	if ($logo) {
		$logo_data .= '<img src="'.htmlspecialchars($logo, ENT_QUOTES, 'UTF-8').'" alt="" class="only-pc">';
	}
	if ($m_logo) {
		$logo_data .= '<img src="'.htmlspecialchars($m_logo, ENT_QUOTES, 'UTF-8').'" alt="" class="not-pc">';
	}
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

	<meta name="title" content="<?php echo htmlspecialchars($g5['title'], ENT_QUOTES, 'UTF-8'); ?>">
	<title><?php echo htmlspecialchars($g5['title'], ENT_QUOTES, 'UTF-8'); ?><?php echo !empty($config['cf_title']) ? ' | '.htmlspecialchars($config['cf_title'], ENT_QUOTES, 'UTF-8') : ''; ?></title>

	<?php if (!empty($config['cf_favicon'])) { ?>
	<link rel="shortcut icon" href="<?php echo $config['cf_favicon']?>">
	<link rel="icon" href="<?php echo $config['cf_favicon']?>">
	<?php } ?>
	<?php
		if (function_exists('emit_enter_head_css')) {
			emit_enter_head_css();
		}
	?>
</head>
<body>

<div class="wrapper">
	<div class="inner">
		<div class="index-logo">
			<?php echo $logo_data; ?>
			<p class="txt-default"><?php echo htmlspecialchars($warn_msg, ENT_QUOTES, 'UTF-8'); ?></p>
			<?php if (empty($is_member)) { ?>
			<p><a href="<?php echo G5_BBS_URL; ?>/login.php">로그인</a></p>
			<?php } ?>
		</div>
	</div>
</div>

</body>
</html>
