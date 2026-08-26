<?php 
include_once('./_common.php');

$g5['title'] = '이모티콘';

include_once('./_head.sub.php');

// 이모티콘 목록 불러오기 (사용중인 것만)
$sql = "select * from {$g5['emoticon_table']} where em_use = '1' order by me_id desc";
$result = sql_query($sql);

add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/emoticon.css">', 0);

// 신청 모드/권한
$apply_mode = isset($config['cf_emoticon_apply_mode']) ? $config['cf_emoticon_apply_mode'] : 'disabled';
$can_apply  = ($apply_mode !== 'disabled')
              && $is_member
              && !empty($character)
              && ($character['ch_state'] ?? '') === '승인';
?>

<div id="emoticon_page">
	<div id="emoticon_head"></div>
	<div id="page_title">
		이모티콘
		<i id="emoticon_line"></i>
	</div>

	<div id="emoticon_content">
		<ul>
		<?php for($i=0; $row = sql_fetch_array($result); $i++) { ?>
			<li>
				<em>
					<img src="<?php echo G5_URL?><?php echo $row['me_img']?>" alt="" />
				</em>
				<span><?php echo $row['me_text']?></span>
			</li>
		<?php }
			if($i == 0) { 
		?>
			<li class="no-data">
				등록된 이모티콘이 없습니다.
			</li>
		<?php 
			}
		?>
		</ul>
	</div>

	<?php if ($can_apply): ?>
	<details class="emo-apply-box theme-box" style="margin:20px auto;">
		<summary style="cursor:pointer; font-weight:bold; padding:6px 0;">+ 이모티콘 신청</summary>
		<form method="post" action="<?php echo G5_BBS_URL; ?>/emoticon_apply.php" enctype="multipart/form-data" style="margin-top:10px;">
			<input type="hidden" name="token" value="<?php echo get_token(); ?>">
			<p style="margin:6px 0;">
				<label>명령어: <input type="text" name="me_text" required maxlength="200" placeholder="/명령어" style="width:200px;"></label>
			</p>
			<p style="margin:6px 0;">
				<label>이미지: <input type="file" name="me_img" accept="image/*" required></label>
			</p>
			<p style="margin:6px 0; color:#666; font-size:12px;">
				<?php if ($apply_mode === 'manual'): ?>
				※ 관리자 승인 후 사용 가능합니다.
				<?php else: ?>
				※ 신청 즉시 사용됩니다.
				<?php endif; ?>
			</p>
			<p style="margin:8px 0;">
				<button type="submit" class="btn_submit ui-btn">신청</button>
			</p>
		</form>
	</details>
	<?php elseif ($apply_mode !== 'disabled' && $is_member): ?>
	<p style="text-align:center; color:#888; margin:20px 0; font-size:13px;">
		※ 이모티콘 신청은 승인된 캐릭터를 가진 회원만 가능합니다.
	</p>
	<?php endif; ?>

	<div id="emoticon_footer"></div>
</div>




<?php include_once('./_tail.sub.php'); ?>