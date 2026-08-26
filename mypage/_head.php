<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
define('_MYPAGE_', true);
$g5['title'] = $member['mb_name']." 마이페이지";
include_once(G5_PATH.'/_head.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/mypage.css">', 0);

// 기본 항목 설정 데이터
$ad = $article;
?>

<div class="mypageWrap">
	<div class="mypageInside">
		<nav id="submenu" class="theme-box">
			<ul>
				<li><a href="<?php  echo G5_URL; ?>/mypage/"			><span>계정정보</span></a></li>
				<li><a href="<?php  echo G5_URL; ?>/mypage/character"	><span>캐릭터</span></a></li>
				<li><a href="<?php  echo G5_URL; ?>/mypage/log"			><span>로그내역</span></a></li>
			<?php  if($ad['ad_use_money']) { ?>
				<li><a href="<?php  echo G5_URL; ?>/mypage/money"		><span><?php echo $config['cf_money']?>관리</span></a></li>
			<?php  } ?>
			<?php
				// 우편함 메뉴: cf_perm_memo 가 관리자 전용(>=10) 이고 현재 사용자가 관리자가 아니면 숨김
				$_memo_lv = isset($config['cf_perm_memo']) ? (int)$config['cf_perm_memo'] : 2;
				$_show_memo = !($_memo_lv >= 10 && !$is_admin);
				if ($_show_memo) {
			?>
				<li><a href="<?php  echo G5_URL; ?>/mypage/memo"		><span>우편함</span></a></li>
			<?php  } ?>
			<?php
				if (function_exists('mdl_apply_filters')) {
					echo mdl_apply_filters('mypage_submenu', '', array(
						'member'        => isset($member) ? $member : array(),
						'character'     => isset($character) ? $character : array(),
						'is_admin_real' => isset($GLOBALS['is_admin_real']) ? $GLOBALS['is_admin_real'] : $is_admin,
					));
				}
				$_admin_real = isset($GLOBALS['is_admin_real']) ? $GLOBALS['is_admin_real'] : $is_admin;
				if ($_admin_real) {
			?>
				<li><a href="<?php echo G5_URL; ?>/mypage/admin/" style="color:#c0392b;"><span>관리자 메뉴</span></a></li>
			<?php } ?>
			</ul>
		</nav>
		<div id="subpage">