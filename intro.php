<?php  
if (!defined('_GNUBOARD_')) exit;

// 테마 intro.php 있으면 해당 파일에서 처리
if (function_exists('theme_include') && ($_p = theme_include('intro', 'intro.php'))) { include $_p; return; }
if(!isset($_COOKIE['intro_close'])||!$_COOKIE['intro_close']) {
	$_intro_time_where = '';
	if (function_exists('shop_time_sql_where')) {
		$_cur_time = isset($config['cf_time']) ? (int)$config['cf_time'] : 0;
		$_intro_time_where = shop_time_sql_where('bn_time', $_cur_time);
	}
	$sql = " select * from {$g5['intro_table']} where '".G5_TIME_YMDHIS."' between bn_begin_time and bn_end_time {$_intro_time_where} order by bn_order, bn_id desc ";
	$result = sql_query($sql);
	$intro = array();
	for ($i=0; $row = sql_fetch_array($result); $i++) {
		$intro[$i] = $row;
	}

	if(count($intro) > 0) { 
		add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/style.intro.css">', 0);
	?>
		<div id="intro_wrap">
		<?php  for($i=0; $i < count($intro); $i++) { 
			
			
			$is_last = ($i == count($intro)-1);
			$link = '';

			if($is_last) { 
				// 마지막 링크일 경우
				$link = "$('#intro_wrap').fadeOut(500, function() { $(this).remove(); }); set_cookie('intro_close', 'Y', 24, g5_cookie_domain); $('html').addClass('close-intro'); return false;";
			}

			$bimg_pc = $intro[$i]['bn_img'];
			$bimg_m = $intro[$i]['bn_m_img'];

			$banner_image = "<img src='".$bimg_pc."' alt='".$intro[$i]['bn_alt']."' ";
			if($bimg_m) {
				$banner_image .= "class='only-pc' /><img src='".$bimg_m."' alt='".$intro[$i]['bn_alt']."' class='not-pc'";
			}
			$banner_image .= " />";
		?>
			<div class="intro-item" style="z-index: <?php echo (count($intro)-$i)?>; display: <?php echo $i === 0 ? 'block' : 'none'; ?>;">
				<a href="#" onclick="<?php echo $is_last ? $link : 'return intro_next(this);'; ?>">
					<?php echo $banner_image?>
				</a>
			</div>
		<?php  } ?>
		</div>
		<script>
		function intro_next(el) {
			var $current = $(el).closest('.intro-item');
			var $next = $current.next('.intro-item');

			if (!$next.length) {
				return true;
			}

			var $wrap = $('#intro_wrap');
			if ($wrap.length) {
				$wrap.stop().animate({ scrollTop: 0 }, 0);
			} else {
				$('html, body').stop().animate({ scrollTop: 0 }, 0);
			}

			$current.fadeOut(0, function() {
				$next.fadeIn();
			});

			return false;
		}
		</script>

	<?php  } 
}
?>
