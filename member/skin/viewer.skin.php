<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/member.css">', 0);

$viewer_info = isset($viewer_info) && is_string($viewer_info) ? $viewer_info : '';
$viewer_bag = isset($viewer_bag) && is_string($viewer_bag) ? $viewer_bag : '';
$viewer_relation = isset($viewer_relation) && is_string($viewer_relation) ? $viewer_relation : '';
if (!isset($viewer_extra_tabs) || !is_array($viewer_extra_tabs)) {
    $viewer_extra_tabs = array();
}
if (!isset($viewer_tools) || !is_array($viewer_tools)) {
    $viewer_tools = array();
}
$has_bag = !empty($article['ad_use_title']) || !empty($article['ad_use_inven']) || (trim($viewer_bag) !== '');
$has_visual = (!empty($article['ad_use_body']) && !empty($ch['ch_body'])) || (!empty($article['ad_use_head']) && !empty($ch['ch_head']));
$mv_allowed = array('info', 'relation');
if ($has_bag) {
    $mv_allowed[] = 'bag';
}
foreach ($viewer_extra_tabs as $_xtab) {
    if (!empty($_xtab['id'])) {
        $mv_allowed[] = $_xtab['id'];
    }
}
$mv_tab = isset($_GET['tabs']) ? preg_replace('/[^a-zA-Z0-9_]/', '', (string)$_GET['tabs']) : 'info';
if (!in_array($mv_tab, $mv_allowed, true)) {
    $mv_tab = 'info';
}
unset($_xtab, $mv_allowed);

$mv_profile_codes = array('voice', 'age', 'height', 'gender');
$mv_profile = array();
$mv_articles = array();
if (!empty($ch_ar) && is_array($ch_ar)) {
	foreach ($ch_ar as $_ar) {
		$_code = isset($_ar['ar_code']) ? (string)$_ar['ar_code'] : '';
		if (in_array($_code, $mv_profile_codes, true)) {
			$mv_profile[$_code] = $_ar;
		} else {
			$mv_articles[] = $_ar;
		}
	}
}
unset($_ar, $_code);

$mv_article_html = function ($ar, $ch) {
	$key = isset($ar['ar_code']) ? $ar['ar_code'] : '';
	$val = ($key !== '' && isset($ch[$key])) ? $ch[$key] : '';
	$type = isset($ar['ar_type']) ? $ar['ar_type'] : '';
	if ($type === 'file' || $type === 'url') {
		return ($val !== '' && $val !== null) ? '<img src="'.$val.'" />' : '';
	}
	if ($type === 'textarea') {
		return nl2br($val);
	}
	$html = $val;
	if ($type !== 'select' && !empty($ar['ar_text'])) {
		$html .= $ar['ar_text'];
	}
	return $html;
};

$mv_voice = (!empty($mv_profile['voice'])) ? trim((string)$mv_article_html($mv_profile['voice'], $ch)) : '';
$mv_name = (!empty($article['ad_use_name'])) ? (string)($ch['ch_name'] ?? '') : '';
$mv_meta_parts = array();
$mv_meta_suffix = array('age' => '세', 'height' => 'cm');
foreach (array('age', 'height', 'gender') as $_code) {
	if (empty($mv_profile[$_code])) {
		continue;
	}
	$_txt = trim((string)$mv_article_html($mv_profile[$_code], $ch));
	if ($_txt !== '') {
		if (isset($mv_meta_suffix[$_code]) && empty($mv_profile[$_code]['ar_text'])) {
			$_suf = $mv_meta_suffix[$_code];
			if (substr($_txt, -strlen($_suf)) !== $_suf) {
				$_txt .= $_suf;
			}
		}
		$mv_meta_parts[] = $_txt;
	}
}
$_side = function_exists('get_side_name') ? get_side_name($ch['ch_side'] ?? '') : '';
$_class = function_exists('get_class_name') ? get_class_name($ch['ch_class'] ?? '') : '';
$mv_group_parts = array();
if ($_side !== '' && $_side !== '-') {
	$mv_group_parts[] = $_side;
}
if ($_class !== '' && $_class !== '-') {
	$mv_group_parts[] = $_class;
}
unset($_code, $_txt, $_suf, $_side, $_class);
$mv_has_profile = ($mv_voice !== '' || $mv_name !== '' || !empty($mv_meta_parts) || !empty($mv_group_parts));
?>
<style>.time-tabs:not(.time-tabs:last-of-type){display:none;}</style>
<div id="character_profile" class="<?php echo $has_visual ? 'mv-has-visual' : 'mv-no-visual'; ?>">

	<?php if ($has_visual) { ?>
	<div class="mv-visual">
		<div class="visual-area">
			<?php  if($article['ad_use_body'] && $ch['ch_body']) { ?>
				<div id="character_body">
					<img src="<?php echo $ch['ch_body']?>" alt="캐릭터 전신" />
				</div>
			<?php  } ?>
			<?php  if($article['ad_use_head'] && $ch['ch_head']) { ?>
				<div id="character_head">
					<img src="<?php echo $ch['ch_head']?>" alt="캐릭터 흉상" />
				</div>
			<?php  } ?>
		</div>
	</div>
	<?php } ?>

	<div class="mv-main">
	<div class="mv-head">
		<?php if (function_exists('print_time_tabs')) { print_time_tabs($time, (bool)$is_admin, !empty($is_owner)); } ?>
		<div class="tab-nav">
			<button type="button" class="mv-tab<?php echo $mv_tab === 'info' ? ' on' : ''; ?>" data-mv-tab="info">기본정보</button>
			<?php if ($has_bag) { ?>
			<button type="button" class="mv-tab<?php echo $mv_tab === 'bag' ? ' on' : ''; ?>" data-mv-tab="bag">가방</button>
			<?php } ?>
			<button type="button" class="mv-tab<?php echo $mv_tab === 'relation' ? ' on' : ''; ?>" data-mv-tab="relation">관계</button>
			<?php
			foreach ($viewer_extra_tabs as $_xtab) {
				if (!empty($_xtab['href'])) {
					echo '<a class="mv-tab" href="'.htmlspecialchars($_xtab['href'], ENT_QUOTES).'">'.htmlspecialchars($_xtab['label'], ENT_QUOTES).'</a>';
					continue;
				}
				if (empty($_xtab['id'])) {
					continue;
				}
				$_xid = $_xtab['id'];
				echo '<button type="button" class="mv-tab'.($mv_tab === $_xid ? ' on' : '').'" data-mv-tab="'.htmlspecialchars($_xid, ENT_QUOTES).'">'.htmlspecialchars($_xtab['label'], ENT_QUOTES).'</button>';
			}
			unset($_xtab, $_xid);
			?>
		</div>
	</div>

	<div class="tab-box-group">
	<div class="tab-box" id="mv_info_area"<?php echo $mv_tab === 'info' ? '' : ' style="display:none;"'; ?>>

	<?php if ($mv_has_profile) { ?>
	<div class="mv-profile-head">
		<?php if ($mv_voice !== '') { ?>
		<p class="mv-profile-voice">"<?php echo $mv_voice; ?>"</p>
		<?php } ?>
		<?php if ($mv_name !== '') { ?>
		<p class="mv-profile-name"><?php echo $mv_name; ?></p>
		<?php } ?>
		<?php if (!empty($mv_meta_parts)) { ?>
		<p class="mv-profile-meta"><?php echo implode(' / ', $mv_meta_parts); ?></p>
		<?php } ?>
		<?php if (!empty($mv_group_parts)) { ?>
		<p class="mv-profile-group"><?php echo implode(' / ', $mv_group_parts); ?></p>
		<?php } ?>
	</div>
	<?php } ?>

	<?php  if($article['ad_use_status']) { // 스탯 설정 ?>
		<h3>
			STATUS
			<span class="mv-sec-meta">
				<em class="txt-point" data-type="point_space"><?php echo get_space_status($ch['ch_id'])?></em> / <?php echo $ch['ch_point']?>
			</span>
		</h3>
		<div class="theme-box mv-status-box">
			<div class="status-bar">
				<?php
					$resent_use_point = 0;
					for($i = 0; $i < count($status); $i++) {

					$status[$i]['has']	= $status[$i]['has'] ? $status[$i]['has'] : $status[$i]['min'];

					$status_percent = $status[$i]['max'] ? $status[$i]['has'] / $status[$i]['max'] * 100 : 0;
					$mine_percent = $status[$i]['max'] ? $status[$i]['now'] / $status[$i]['max'] * 100 : 0;

					$resent_use_point += $status[$i]['has'];

					$sub_text = "";
					if($status[$i]['drop']) $sub_text = "(".$status[$i]['now'].")";
				?>
					<dl>
						<dt><?php echo $status[$i]['name']?></dt>
						<dd>
							<p>
								<i><?php echo $status[$i]['has']?><?php echo $sub_text?></i>
								<span style="width: <?php echo $status_percent?>%;"></span>
								<sup style="width: <?php echo $mine_percent?>%;"></sup>
							</p>
						</dd>
					</dl>
				<?php  } ?>
			</div>
		</div>
	<?php  } ?>

	<?php foreach ($mv_articles as $ar) { ?>
	<h3><?php echo $ar['ar_name']?></h3>
	<div class="mv-article-body">
		<?php echo $mv_article_html($ar, $ch); ?>
	</div>
	<?php } ?>

		<?php echo $viewer_info; // member_viewer_info ?>

	</div><!-- // mv_info_area -->

	<?php if ($has_bag) { ?>
	<div class="tab-box" id="mv_bag_area"<?php echo $mv_tab === 'bag' ? '' : ' style="display:none;"'; ?>>

<?php  if($article['ad_use_title']) { // 타이틀 설정 ?>
	<h3>
		TITLE
	</h3>
	<div class="theme-box">
		<div class="title-list">
			<?php
				for($i=0; $i < count($title); $i++) {  echo get_title_value($title[$i]['ti_id']);  }
				if($i == 0) { echo "<div class='no-data'>보유중인 타이틀이 없습니다.</div>"; }
			?>
		</div>
	</div>
<?php  } ?>

	<?php  if($article['ad_use_inven']) { // 인벤토리 출력 ?>
		<h3>
			INVENTORY
		<?php  if($article['ad_use_money']) { // 소지금 사용시 현재 보유 중인 소지금 출력 ?>
			<span class="mv-sec-meta">
				<em class="txt-point"><?php echo $mb['mb_point']?></em><?php echo $config['cf_money_pice']?>
			</span>
		<?php  } ?>
		</h3>
		<div class="theme-box">
			<?php  include(function_exists('theme_dir_file') ? theme_dir_file('inventory/list.inc.php') : (G5_PATH."/inventory/list.inc.php")); ?>
		</div>
	<?php  } ?>

		<?php echo $viewer_bag; // member_viewer_bag ?>

	</div><!-- // mv_bag_area -->
	<?php } ?>

	<div class="tab-box" id="mv_relation_area"<?php echo $mv_tab === 'relation' ? '' : ' style="display:none;"'; ?>>
		<?php include function_exists('theme_dir_file') ? theme_dir_file('member/skin/relation.skin.php') : (G5_PATH.'/member/skin/relation.skin.php'); ?>
		<?php echo $viewer_relation; // member_viewer_relation ?>
	</div>

	<?php
	foreach ($viewer_extra_tabs as $_xtab) {
		if (empty($_xtab['id']) || !empty($_xtab['href'])) {
			continue;
		}
		$_xid = $_xtab['id'];
		echo '<div class="tab-box" id="mv_'.htmlspecialchars($_xid, ENT_QUOTES).'_area"'.($mv_tab === $_xid ? '' : ' style="display:none;"').'>';
		echo $_xtab['html'];
		echo '</div>';
	}
	unset($_xtab, $_xid);
	?>
	</div><!-- // tab-box-group -->
	<div class="mv-foot">
		<?php
		$has_closet = !empty($article['ad_use_closet']) && !empty($article['ad_use_body']);
		$has_exp = !empty($article['ad_use_exp']);
		$has_tools = !empty($viewer_tools);
		if ($has_closet || $has_exp || $has_tools) { ?>
		<nav class="mv-tools">
		<?php  if($has_closet) { ?>
			<a href="<?php echo G5_URL?>/member/closet.php?ch_id=<?php echo $ch['ch_id']?>&amp;ti_time=<?php echo (int)($time ?? 0); ?>" onclick="window.open(this.href, 'big_viewer', 'width=800 height=800 menubar=no status=no toolbar=no location=no scrollbars=yes resizable=yes'); return false;">옷장</a>
		<?php  } ?>
		<?php
		if ($has_tools && function_exists('character_viewer_tools_html')) {
			echo character_viewer_tools_html($viewer_tools);
		}
		?>
		<?php  if($has_exp) { ?>
			<a href="<?php echo G5_URL?>/member/exp.php?ch_id=<?php echo $ch['ch_id']?>" onclick="popup_window(this.href, 'exp', 'width=400, height=500'); return false;">경험치</a>
		<?php  } ?>
		</nav>
		<?php } ?>
		<p class="mv-owner">오너 : <?php echo htmlspecialchars($mb['mb_name'] ?? '', ENT_QUOTES); ?></p>
	</div>
	</div><!-- // mv-main -->

</div>

<script>
(function($){
	$('#character_profile .tab-nav [data-mv-tab]').on('click', function(){
		var id = $(this).data('mv-tab');
		$(this).addClass('on').siblings('[data-mv-tab]').removeClass('on');
		$('#character_profile .tab-box').hide();
		$('#mv_'+id+'_area').show();
		$('#character_profile .tab-box-group').scrollTop(0);
	});
})(jQuery);
</script>
