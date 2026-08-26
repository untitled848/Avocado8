<?php
include_once('./_common.php');
include_once('./_head.php');
?>


<h2 class="page-title">
	<strong>계정정보</strong>
	<span>My Information</span>
</h2>

<section>
	<table class="theme-form">
		<colgroup>
			<col style="width: 110px;" />
			<col />
		</colgroup>
		<tbody>
			<tr>
				<th>오너</th>
				<td>
					<?php echo $member['mb_nick']?> <?php  if($member['mb_birth']) { ?>( <?php echo @number_format($member['mb_birth'])?> 년생 )<?php  } ?>
				</td>
			</tr>
			<tr>
				<th>가입일</th>
				<td>
					<?php echo $member['mb_open_date']?>
				</td>
			</tr>
<?php  if($member['mb_error_cnt'] > 0) { ?>
			<tr>
				<th>경고내역</th>
				<td>
					<p>
						[ <span style='color: red; font-weight: bold;'><?php echo number_format($member['mb_error_cnt'])?></span> ] 회
					</p>
					<?php echo $member['mb_error_content']?>
				</td>
			</tr>
<?php  } ?>
		</tbody>
	</table>
	<br />
	<div class="txt-center">
		<a href="<?php echo G5_BBS_URL?>/member_confirm.php?url=register_form.php" class="ui-btn">정보수정</a>
	</div>
</section>

<?php
// 호출내역 — call.inc.php 로 통합 (board 타입은 wr_content 100자로 치환)
include __DIR__ . '/call.inc.php';
?>


<div class="ui-form-layout">

</div>
<?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('mypage_index_widgets', ''); ?>
<hr class="padding" />
<div class="txt-center">
	<a href="<?php echo G5_BBS_URL; ?>/member_confirm.php?url=member_leave.php" class="ui-btn etc">탈퇴</a>
</div>

<?php
include_once('./_tail.php');
?>
