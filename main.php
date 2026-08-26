<?php
include_once('./_common.php');
define('_MAIN_', true);

include_once(G5_PATH.'/head.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/main.css">', 0);
include_once(G5_PATH."/intro.php");
?>

<div id="main_body">

<?php 
$main_content = get_site_content('site_main');
if($main_content) { 
	echo $main_content;
} else { 
?>
	<div id="no_design_main">
		<div id="main_visual_box">
			<?php  include(G5_PATH."/templete/txt.visual.php"); ?>
		</div>

		<div id="main_notice_box" class="theme-box">
			<?php echo function_exists('get_widget') ? get_widget('notice') : ''; ?>
		</div>

		<div id="main_side_box">
			<div id="main_login_box" class="theme-box">
				<?php  include(G5_PATH."/templete/txt.outlogin.php"); ?>
			</div>
			<div id="main_banner_box" class="theme-box">
				<img src="<?php echo G5_URL?>/adm/img/logo.png" alt="임시 메인 배너이미지" />
				<p>AVOCADO EDITION</p>
				<p><a href="https://avocado-edition-rout.postype.com/" target="_blank">https://avocado-edition-rout.postype.com/</a></p>
			</div>
		</div>

		<div id="main_copyright_box" class="theme-box txt-center">
			COPYRIGHT &copy; 2021 by Avocado
		</div>
	</div>
<?php } ?>
</div>

<script>
document.documentElement.classList.remove('js-loading');
</script>

<?php 
include_once(G5_PATH.'/tail.php');
?>