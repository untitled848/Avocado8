<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/member.css">', 0);
?>


<nav id="submenu" class="scroll-fix">
	<ul>
		<?php echo $side_link?>
	</ul>
</nav>

<div id="subpage">
	<div class="searc-sub-box" style="padding: 10px 5px;">
		<div class="ui-search-box">
			<form name='frmSearch' method='get'>
				<fieldset class="sch_category">
					<select name="sfl">
						<option value="mb_name" <?php echo $sfl == 'mb_name' ? "selected" : ""?>>오너명</option>
						<option value="ch_name" <?php echo $sfl == 'ch_name' ? "selected" : ""?>>캐릭명</option>
					<?php  if(count($ch_ar) > 0) { 
						for($i=0; $i < count($ch_ar); $i++) { 
					?>
						<option value="arcode||<?php echo $ch_ar[$i]['ar_code']?>" <?php echo $sfl == 'arcode||'.$ch_ar[$i]['ar_code'] ? "selected" : ""?>><?php echo $ch_ar[$i]['ar_name']?></option>
					<?php  } } ?>
					</select>
				</fieldset>
				<fieldset class="sch_text">
					<input type="text" name="stx" value="<?php echo $stx?>" />
				</fieldset>
				<fieldset class="sch_button">
					<input type="submit" value="Search" class="ui-btn"/>
				</fieldset>
			</form>
		</div>
	</div>

	<div class="ui-page txt-center">
		<?php echo $write_pages?>
	</div>

	<ul class="ready-member-list">
<?php 
	for($i=0; $i < count($character_list); $i++) {
		$ch = $character_list[$i];
?>
		<li>
			<div class="item theme-box">
				<div class="ui-thumb">
					<a href="./viewer.php?ch_id=<?php echo $ch['ch_id']?>">
						<?php  if($ch['ch_thumb']) { ?>
							<img src="<?php echo function_exists('cdn_resolve_url') ? htmlspecialchars(cdn_resolve_url($ch['ch_thumb']), ENT_QUOTES, 'UTF-8') : htmlspecialchars($ch['ch_thumb'], ENT_QUOTES, 'UTF-8')?>" />
						<?php  } ?>
					</a>
				</div>
				<div class="ui-profile">
					<p class="name">
						<a href="./viewer.php?ch_id=<?php echo $ch['ch_id']?>">
							<strong>[<?php echo $ch['ch_state']?>] <?php echo $ch['ch_name']?></strong>
						</a>
					</p>
					<span>
						<?php 
							if($config['cf_side_title']) {
								echo get_side_name($ch['ch_side']);
							}
							if($config['cf_class_title']) { 
								if($config['cf_side_title']) { echo " / "; }
								echo get_class_name($ch['ch_class']);
							}
						?>
					</span>
					<span class="owner">
						<?php echo get_member_name($ch['mb_id'])?>
					</span>
				</div>
			</div>
		</li>


<?php 
	}
	if($i == 0) { 
		echo "<li class='empty'>대기자가 없습니다.</li>";
	}
	
?>
	</ul>

	<div class="ui-page">
		<?php echo $write_pages?>
	</div>

</div>
