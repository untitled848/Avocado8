<?php
$sub_menu = "310100";
include_once('./_common.php');
include_once(G5_EDITOR_LIB);

auth_check($auth[$sub_menu], "w");

$html_title = "세계관 페이지";
$g5['title'] = $html_title.' 관리';

if ($w == "u")
{
	$html_title .= " 수정";
	$readonly = " readonly";

	$sql = " select * from {$g5['world_table']} where wo_id = '$wo_id' ";
	$wo = sql_fetch($sql);
	if (!$wo['wo_id'])
		alert('등록된 자료가 없습니다.');
}
else
{
	$html_title .= ' 입력';
	$wo['wo_html'] = 2;
	$wo['wo_skin'] = '';
	$wo['wo_mobile_skin'] = '';
}

include_once (G5_ADMIN_PATH.'/admin.head.php');
?>

<div class="btn_add01 btn_add">
	<a href="./world_design_form.php">세계관 디자인 설정</a>
</div>

<form name="frmworldform" action="./world_form_update.php" onsubmit="return frmworldform_check(this);" method="post" enctype="MULTIPART/FORM-DATA" >
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="wo_html" value="1">
<input type="hidden" name="token" value="">

<div class="tbl_frm01 tbl_wrap">
	<table>
	<caption><?php echo $g5['title']; ?></caption>
	<colgroup>
		<col class="grid_4">
		<col>
	</colgroup>
	<tbody>
	<tr>
		<th scope="row"><label for="wo_id">ID</label></th>
		<td>
			<?php echo help('20자 이내의 영문자, 숫자, _ 만 가능합니다.'); ?>
			<input type="text" value="<?php echo $wo['wo_id']; ?>" name="wo_id" id="wo_id" required <?php echo $readonly; ?> class="required frm_input" size="20" maxlength="20">
			<?php if ($w == 'u') { ?><a href="<?php echo G5_WORLD_URL; ?>/?wo_id=<?php echo $wo_id; ?>" target="_blank" class="btn_frmline">페이지 확인</a><?php } ?>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="wo_subject">제목</label></th>
		<td><input type="text" name="wo_subject" value="<?php echo htmlspecialchars2($wo['wo_subject']); ?>" id="wo_subject" required class="frm_input required" size="90"></td>
	</tr>
	<tr>
		<th scope="row">내용</th>
		<td><?php echo editor_html('wo_content', get_text($wo['wo_content'], 0)); ?></td>
	</tr>
	<tr>
		<th scope="row">모바일 내용</th>
		<td><?php echo editor_html('wo_mobile_content', get_text($wo['wo_mobile_content'], 0)); ?></td>
	</tr>
	<tr>
		<th scope="row"><label for="wo_skin">스킨 디렉토리</label></th>
		<td>
			<?php echo get_skin_select('world', 'wo_skin', 'wo_skin', $wo['wo_skin'], '', array('empty_label'=>'테마 프리셋에 따름','preset_type'=>'world')); ?>
			<?php echo help('비워두면 테마 프리셋의 「세계관 디자인」 또는 글로벌 설정이 적용됩니다. 「디자인 프리셋」 그룹은 코드를 따로 두지 않고 색상·여백 등 프리셋 설정만 적용하는 옵션이며, 「코어/테마」 그룹은 해당 폴더의 스킨 코드를 사용합니다.'); ?>
		</td>
	</tr>
	</tbody>
	</table>
</div>

<div class="btn_confirm01 btn_confirm">
	<input type="submit" value="확인" class="btn_submit" accesskey="s">
	<a href="./world_list.php">목록</a>
</div>

</form>

<script>
function frmworldform_check(f)
{
	errmsg = "";
	errfld = "";

	<?php echo get_editor_js('wo_content'); ?>
	<?php echo chk_editor_js('wo_content'); ?>
	<?php echo get_editor_js('wo_mobile_content'); ?>

	check_field(f.wo_id, "ID를 입력하세요.");
	check_field(f.wo_subject, "제목을 입력하세요.");
	check_field(f.wo_content, "내용을 입력하세요.");

	if (errmsg != "") {
		alert(errmsg);
		errfld.focus();
		return false;
	}
	return true;
}
</script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>
