<?php
include_once('./_common.php');
define('_INDEX_', true);

$index_url = "./enter.php";

$_open_scope = $config['cf_open_scope'] ?? 'all';
$_site_open = ($_open_scope === 'all'); // 비회원 포함 접근 가능 여부

if(!$is_member && !$_site_open) { 
	// 멤버가 아니고, 사이트 오픈이 되어 있지 않은 경우 로그인 페이지로 점프 시키기
	goto_url(G5_BBS_URL.'/login.php');

} else { 
	if($_site_open) {
		// 사이트 오픈이 되어 있을 경우
		if($is_member) {
			// 사이트 오픈이 되어 있고, 로그인이 끝났을 경우
			if (G5_IS_MOBILE) {
				// 모바일은 iframe 없이 직접 include 하므로 theme_dispatch_override() 가
				// SCRIPT_FILENAME(index.php) 기준으로 'main' 슬롯을 잡지 못한다.
				// → 활성 테마의 main.php 를 직접 해석해서 포함한다.
				if (function_exists('theme_include') && ($_pm = theme_include('main', 'main.php'))) {
					include $_pm;
				} else {
					include_once(G5_PATH.'/main.php');
				}
				exit;
			}
			$index_url = "./main.php";
		} else {
			// 사이트 오픈이 되어 있고, 로그인이 안되어 있을 경우
			if (G5_IS_MOBILE) {
				// iframe 없이 직접 include. SCRIPT_FILENAME 은 index.php 이므로
				// 디스패처가 enter 슬롯을 못 잡는다 → enter.php 안의 theme_include 가 처리.
				include G5_PATH.'/enter.php';
				exit;
			}
			$index_url = "./enter.php";
		}
		
	}


	include_once(G5_PATH.'/head.sub.php');
	add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/index.css">', 0);
	add_stylesheet('<style>#admin-status-banner{display:none}</style>', 0);
?>

	<div id="site_bgm_box">
		<iframe src="./bgm.php?action=play" name="bgm_frame" id="bgm_frame" border="0" frameborder="0" marginheight="0" marginwidth="0" topmargin="0" scrolling="no" allowTransparency="true"></iframe>
	</div>
	<!-- 콘텐츠 시작 -->
	<div id="wrapper">
		<iframe src="<?php echo $index_url?>" name="frm_main" id="main" border="0" frameborder="0" marginheight="0" marginwidth="0" topmargin="0" scrolling="auto" allowTransparency="true"></iframe>
	</div>
	<script>
	$(document.body).on("keydown", this, function (event) {
		if (event.keyCode == 116) {
			document.getElementById('main').contentDocument.location.reload(true);
			return false;
		}
	});
	</script>

<?php
	include_once(G5_PATH.'/tail.sub.php');
}
?>