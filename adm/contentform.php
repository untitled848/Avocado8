<?php
$sub_menu = "310201";
include_once('./_common.php');
include_once(G5_EDITOR_LIB);

auth_check($auth[$sub_menu], "w");

$html_title = "내용";
$g5['title'] = $html_title.' 관리';

if ($w == "u")
{
	$html_title .= " 수정";
	$readonly = " readonly";

	$sql = " select * from {$g5['content_table']} where co_id = '$co_id' ";
	$co = sql_fetch($sql);
	if (!$co['co_id'])
		alert('등록된 자료가 없습니다.');
}
else
{
	$html_title .= ' 입력';
	$co['co_html'] = 2;
	$co['co_skin'] = '';
	$co['co_mobile_skin'] = '';
}

$g5['title'] = $html_title;
include_once(G5_ADMIN_PATH.'/admin.head.php');
?>

<div class="btn_add01 btn_add">
    <a href="./content_design_form.php">문서 디자인 설정</a>
</div>

<form name="frmcontentform" action="./contentformupdate.php" onsubmit="return frmcontentform_check(this);" method="post" enctype="MULTIPART/FORM-DATA" >
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="co_html" value="1">
<input type="hidden" name="token" value="">

<div class="tbl_frm01 tbl_wrap">
	<table>
	<caption><?php echo $g5['title']; ?> 목록</caption>
	<colgroup>
		<col class="grid_4">
		<col>
	</colgroup>
	<tbody>
	<tr>
		<th scope="row"><label for="co_id">ID</label></th>
		<td>
			<?php echo help('20자 이내의 영문자, 숫자, _ 만 가능합니다.'); ?>
			<input type="text" value="<?php echo $co['co_id']; ?>" name="co_id" id ="co_id" required <?php echo $readonly; ?> class="required <?php echo $readonly; ?> frm_input" size="20" maxlength="20">
			<?php if ($w == 'u') { ?><a href="<?php echo G5_BBS_URL; ?>/content.php?co_id=<?php echo $co_id; ?>" class="btn_frmline">내용확인</a><?php } ?>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="co_subject">제목</label></th>
		<td><input type="text" name="co_subject" value="<?php echo htmlspecialchars2($co['co_subject']); ?>" id="co_subject" required class="frm_input required" size="90"></td>
	</tr>
	<tr>
		<th scope="row">내용</th>
		<td><?php echo editor_html('co_content', get_text($co['co_content'], 0)); ?></td>
	</tr>
	<tr>
		<th scope="row">모바일 내용</th>
		<td><?php echo editor_html('co_mobile_content', get_text($co['co_mobile_content'], 0)); ?></td>
	</tr>
	<tr>
		<th scope="row"><label for="co_skin">스킨 디렉토리</label></th>
		<td>
			<?php echo get_skin_select('content', 'co_skin', 'co_skin', $co['co_skin'], '', array('empty_label'=>'테마 프리셋에 따름','preset_type'=>'content')); ?>
			<?php echo help('비워두면 테마 프리셋의 「문서 디자인」 또는 글로벌 설정이 적용됩니다. 「디자인 프리셋」 그룹은 코드를 따로 두지 않고 색상·여백 등 프리셋 설정만 적용하는 옵션이며, 「코어/테마」 그룹은 해당 폴더의 스킨 코드를 사용합니다.'); ?>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="co_tag_filter_use">태그 필터링 사용</label></th>
		<td>
			<?php echo help("내용에서 iframe 등의 태그를 사용하려면 사용안함으로 선택해 주십시오."); ?>
			<select name="co_tag_filter_use" id="co_tag_filter_use">
				<option value="1"<?php echo get_selected(1, $co['co_tag_filter_use']); ?>>사용함</option>
				<option value="0"<?php echo get_selected(0, $co['co_tag_filter_use']); ?>>사용안함</option>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="co_include_head">상단 파일 경로</label></th>
		<td>
			<?php echo help("설정값이 없으면 기본 상단 파일을 사용합니다."); ?>
			<input type="text" name="co_include_head" value="<?php echo $co['co_include_head']; ?>" id="co_include_head" class="frm_input" size="60">
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="co_include_tail">하단 파일 경로</label></th>
		<td>
			<?php echo help("설정값이 없으면 기본 하단 파일을 사용합니다."); ?>
			<input type="text" name="co_include_tail" value="<?php echo $co['co_include_tail']; ?>" id="co_include_tail" class="frm_input" size="60">
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="co_himg">상단이미지</label></th>
		<td>
			<input type="file" name="co_himg" id="co_himg">
			<?php
			$himg = G5_DATA_PATH.'/content/'.$co['co_id'].'_h';
			if (file_exists($himg)) {
				$size = @getimagesize($himg);
				if($size[0] && $size[0] > 750)
					$width = 750;
				else
					$width = $size[0];

				echo '<input type="checkbox" name="co_himg_del" value="1" id="co_himg_del"> <label for="co_himg_del">삭제</label>';
				$himg_str = '<img src="'.G5_DATA_URL.'/content/'.$co['co_id'].'_h" width="'.$width.'" alt="">';
			}
			if ($himg_str) {
				echo '<div class="banner_or_img">';
				echo $himg_str;
				echo '</div>';
			}
			?>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="co_timg">하단이미지</label></th>
		<td>
			<input type="file" name="co_timg" id="co_timg">
			<?php
			$timg = G5_DATA_PATH.'/content/'.$co['co_id'].'_t';
			if (file_exists($timg)) {
				$size = @getimagesize($timg);
				if($size[0] && $size[0] > 750)
					$width = 750;
				else
					$width = $size[0];

				echo '<input type="checkbox" name="co_timg_del" value="1" id="co_timg_del"> <label for="co_timg_del">삭제</label>';
				$timg_str = '<img src="'.G5_DATA_URL.'/content/'.$co['co_id'].'_t" width="'.$width.'" alt="">';
			}
			if ($timg_str) {
				echo '<div class="banner_or_img">';
				echo $timg_str;
				echo '</div>';
			}
			?>
		</td>
	</tr>
	</tbody>
	</table>
</div>

<div class="btn_confirm01 btn_confirm">
	<input type="submit" value="확인" class="btn_submit" accesskey="s">
	<a href="./contentlist.php">목록</a>
</div>

</form>

<script>
function frmcontentform_check(f)
{
	errmsg = "";
	errfld = "";

	<?php echo get_editor_js('co_content'); ?>
	<?php echo chk_editor_js('co_content'); ?>
	<?php echo get_editor_js('co_mobile_content'); ?>

	check_field(f.co_id, "ID를 입력하세요.");
	check_field(f.co_subject, "제목을 입력하세요.");
	check_field(f.co_content, "내용을 입력하세요.");

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
