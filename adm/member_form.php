<?php
$sub_menu = "200100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');

if ($w == '')
{
	$required_mb_id = 'required';
	$required_mb_id_class = 'required alnum_';
	$required_mb_password = 'required';
	$sound_only = '<strong class="sound_only">필수</strong>';

	$mb['mb_level'] = $config['cf_register_level'];
	$html_title = '추가';
}
else if ($w == 'u')
{
	$mb = get_member($mb_id);
	if (!$mb['mb_id'])
		alert('존재하지 않는 회원자료입니다.');

	if (!$is_admin && $mb['mb_level'] >= $member['mb_level'])
		alert('자신보다 권한이 높거나 같은 회원은 수정할 수 없습니다.');

	$required_mb_id = 'readonly';
	$required_mb_password = '';
	$html_title = '수정';

	$mb['mb_name'] = get_text($mb['mb_name']);
	$mb['mb_nick'] = get_text($mb['mb_nick']);
	$mb['mb_1'] = get_text($mb['mb_1']);
	$mb['mb_2'] = get_text($mb['mb_2']);
	$mb['mb_3'] = get_text($mb['mb_3']);
	$mb['mb_4'] = get_text($mb['mb_4']);
	$mb['mb_5'] = get_text($mb['mb_5']);
	$mb['mb_6'] = get_text($mb['mb_6']);
	$mb['mb_7'] = get_text($mb['mb_7']);
	$mb['mb_8'] = get_text($mb['mb_8']);
	$mb['mb_9'] = get_text($mb['mb_9']);
	$mb['mb_10'] = get_text($mb['mb_10']);
}
else
	alert('제대로 된 값이 넘어오지 않았습니다.');


if ($mb['mb_intercept_date']) $g5['title'] = "차단된 ";
else $g5['title'] .= "";
$g5['title'] .= '회원 '.$html_title;
include_once('./admin.head.php');

?>

<form name="fmember" id="fmember" action="./member_form_update.php" onsubmit="return fmember_submit(this);" method="post" enctype="multipart/form-data">
<input type="hidden" name="w" value="<?php echo $w ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<input type="hidden" name="token" value="">

<div class="tbl_frm01 tbl_wrap">
	<table>
	<caption><?php echo $g5['title']; ?></caption>
	<colgroup>
		<col class="grid_4">
		<col>
		<col class="grid_4">
		<col>
	</colgroup>
	<tbody>
	<tr>
		<th scope="row"><label for="mb_id">아이디<?php echo $sound_only ?></label></th>
		<td>
			<input type="text" name="mb_id" value="<?php echo $mb['mb_id'] ?>" id="mb_id" <?php echo $required_mb_id ?> class="frm_input <?php echo $required_mb_id_class ?>" size="15" minlength="3" maxlength="20">
			<?php if ($w=='u'){ ?><a href="./boardgroupmember_form.php?mb_id=<?php echo $mb['mb_id'] ?>">접근가능그룹보기</a><?php } ?>
		</td>
		<th scope="row"><label for="mb_password">비밀번호<?php echo $sound_only ?></label></th>
		<td><input type="password" name="mb_password" id="mb_password" <?php echo $required_mb_password ?> class="frm_input <?php echo $required_mb_password ?>" size="15" maxlength="20"></td>
	</tr>
	<tr>
		<th scope="row"><label for="mb_name">닉네임<strong class="sound_only">필수</strong></label></th>
		<td>
			<?php
			$_nick_min = function_exists('register_nick_min') ? register_nick_min() : 2;
			$_nick_max = function_exists('register_nick_max') ? register_nick_max() : 20;
			$_nick_filter = function_exists('register_nick_filter') ? register_nick_filter() : '';
			$_nick_pattern = '';
			if ($_nick_filter === 'ko')   $_nick_pattern = '[\uAC00-\uD7A3\u3131-\u318E\\s]+';
			if ($_nick_filter === 'koen') $_nick_pattern = '[\uAC00-\uD7A3\u3131-\u318E A-Za-z\\s]+';
			?>
			<input type="text" name="mb_name" value="<?php echo $mb['mb_name'] ?>" id="mb_name" required class="required frm_input" size="15"
				<?php if ($_nick_min > 0) echo ' minlength="'.$_nick_min.'"'; ?>
				<?php if ($_nick_max > 0) echo ' maxlength="'.$_nick_max.'"'; ?>
				<?php if ($_nick_pattern) echo ' pattern="'.$_nick_pattern.'"'; ?>>
			<?php
			if (function_exists('register_field_help')) {
				$_h = register_field_help('mb_name');
				if ($_h !== '') echo '<div class="reg-help" style="font-size:12px;color:#888;margin-top:3px;">'.htmlspecialchars($_h, ENT_QUOTES).'</div>';
			}
			?>
		</td>
		<th scope="row"><label for="mb_level">회원 권한</label></th>
		<td><?php echo get_member_level_select('mb_level', 1, $member['mb_level'], $mb['mb_level']) ?></td>
	</tr>
	<tr>
		<th scope="row"><label for="mb_birth">생년</label></th>
		<td colspan="3">
			<input type="text" name="mb_birth" value="<?php echo isset($mb['mb_birth']) ? htmlspecialchars($mb['mb_birth'], ENT_QUOTES) : ''; ?>" id="mb_birth" maxlength="100" class="frm_input" size="20" placeholder="자유 입력 (예: 1990, 1990-05, 1990-05-12)">
			<div class="reg-help" style="font-size:12px;color:#888;margin-top:3px;">
				관리자 수정 폼은 형식 제한 없이 자유 입력입니다. 회원가입 폼 정책(연도만/안 받음 등) 과 무관하게 기존 값을 그대로 보존·수정할 수 있습니다.
			</div>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="mb_memo">메모</label></th>
		<td colspan="3"><textarea name="mb_memo" id="mb_memo"><?php echo $mb['mb_memo'] ?></textarea></td>
	</tr>

	<?php if ($w == 'u') { ?>
	<tr>
		<th scope="row">회원가입일</th>
		<td><?php echo $mb['mb_datetime'] ?></td>
		<th scope="row">최근접속일</th>
		<td><?php echo $mb['mb_today_login'] ?></td>
	</tr>
	<tr>
		<th scope="row">IP</th>
		<td colspan="3"><?php echo $mb['mb_ip'] ?></td>
	</tr>
	<?php } ?>

	<tr>
		<th scope="row"><label for="mb_leave_date">탈퇴일자</label></th>
		<td>
			<input type="text" name="mb_leave_date" value="<?php echo $mb['mb_leave_date'] ?>" id="mb_leave_date" class="frm_input" maxlength="8">
			<input type="checkbox" value="<?php echo date("Ymd"); ?>" id="mb_leave_date_set_today" onclick="if (this.form.mb_leave_date.value==this.form.mb_leave_date.defaultValue) {
this.form.mb_leave_date.value=this.value; } else { this.form.mb_leave_date.value=this.form.mb_leave_date.defaultValue; }">
			<label for="mb_leave_date_set_today">탈퇴일을 오늘로 지정</label>
		</td>
		<th scope="row">접근차단일자</th>
		<td>
			<input type="text" name="mb_intercept_date" value="<?php echo $mb['mb_intercept_date'] ?>" id="mb_intercept_date" class="frm_input" maxlength="8">
			<input type="checkbox" value="<?php echo date("Ymd"); ?>" id="mb_intercept_date_set_today" onclick="if
(this.form.mb_intercept_date.value==this.form.mb_intercept_date.defaultValue) { this.form.mb_intercept_date.value=this.value; } else {
this.form.mb_intercept_date.value=this.form.mb_intercept_date.defaultValue; }">
			<label for="mb_intercept_date_set_today">접근차단일을 오늘로 지정</label>
		</td>
	</tr>

	<?php for ($i=1; $i<=10; $i++) { ?>
	<tr style="display: none;">
		<th scope="row"><label for="mb_<?php echo $i ?>">여분 필드 <?php echo $i ?></label></th>
		<td colspan="3"><input type="text" name="mb_<?php echo $i ?>" value="<?php echo $mb['mb_'.$i] ?>" id="mb_<?php echo $i ?>" class="frm_input" size="30" maxlength="255"></td>
	</tr>
	<?php } ?>

	</tbody>
	</table>
</div>

<?php
// 회원가입 추가 입력폼 (cf_register_extra_fields) — 어드민에서도 동일 노출
if (function_exists('register_extra_render')) {
	echo '<div class="tbl_frm01 tbl_wrap" style="margin-top:10px;"><h3 style="padding:8px 12px;background:#f5f5f5;margin:0;">추가 입력 항목</h3>';
	register_extra_render($mb);
	echo '</div>';
}
?>

<div class="btn_confirm01 btn_confirm">
	<input type="submit" value="확인" class="btn_submit" accesskey='s'>
	<a href="./member_list.php?<?php echo $qstr ?>">목록</a>
</div>
</form>

<script>
function fmember_submit(f)
{
	return true;
}
</script>

<?php
include_once('./admin.tail.php');
?>
