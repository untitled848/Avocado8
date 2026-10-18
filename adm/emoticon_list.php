<?php
$sub_menu = "300700";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$token = get_token();

$sql_common = " from {$g5['emoticon_table']} ";

$sql_search = " where (1) ";

if (!$sst) {
	$sst  = "me_id";
	$sod = "desc";
}
$sql_order = " order by {$sst} {$sod} ";

$sql = " select count(*) as cnt
			{$sql_common}
			{$sql_search}
			{$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 18;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select *
			{$sql_common}
			{$sql_search}
			{$sql_order}
			limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

$g5['title'] = '이모티콘 관리';
include_once ('./admin.head.php');

$colspan = 9;


?>

<h2 class="h2_frm">이모티콘 목록</h2>

<?php
// 신청 모드 폼
$apply_mode = isset($config['cf_emoticon_apply_mode']) ? $config['cf_emoticon_apply_mode'] : 'disabled';
$mode_labels = array('disabled' => '신청불가', 'manual' => '관리자 승인', 'auto' => '자동승인');
?>
<form method="post" action="./emoticon_list_update.php" style="margin-bottom:12px;">
	<input type="hidden" name="token" value="<?php echo $token ?>">
	<input type="hidden" name="act" value="mode">
	<label><strong>유저 신청 정책:</strong>
		<select name="cf_emoticon_apply_mode">
			<?php foreach ($mode_labels as $v => $lab): ?>
			<option value="<?php echo $v; ?>" <?php if ($apply_mode === $v) echo 'selected'; ?>><?php echo $lab; ?></option>
			<?php endforeach; ?>
		</select>
	</label>
	<input type="submit" value="저장" class="btn_submit">
	<span class="frm_info"> · 신청불가: 유저 신청 차단 / 관리자 승인: 보류 후 승인 필요 / 자동승인: 즉시 사용 가능</span>
</form>

<?php
// 보류건 (em_use=0) 우선 표시
$pending = sql_query("SELECT * FROM {$g5['emoticon_table']} WHERE em_use = '0' ORDER BY em_datetime DESC, me_id DESC", false);
if ($pending && sql_num_rows($pending) > 0): ?>
<section style="margin-bottom:16px;">
	<h2 class="h2_frm">승인 대기 (<?php echo sql_num_rows($pending); ?>건)</h2>
	<form method="post" action="./emoticon_list_update.php">
		<input type="hidden" name="token" value="<?php echo $token ?>">
		<input type="hidden" name="act" value="approve">
		<div class="tbl_head01 tbl_wrap">
			<table>
				<thead><tr><th style="width:40px;"><input type="checkbox" onclick="var c=this.checked;document.querySelectorAll('input.pchk').forEach(function(e){e.checked=c;});"></th><th style="width:80px;">이미지</th><th>명령어</th><th style="width:140px;">신청자</th><th style="width:160px;">신청일시</th><th style="width:120px;">개별 처리</th></tr></thead>
				<tbody>
				<?php while ($p = sql_fetch_array($pending)): $pid = (int)$p['me_id']; ?>
				<tr>
					<td><input type="checkbox" class="pchk" name="pid[]" value="<?php echo $pid; ?>"></td>
					<td><img src="<?php echo G5_URL.$p['me_img']; ?>" alt="" style="max-height:32px;"></td>
					<td><?php echo htmlspecialchars($p['me_text'], ENT_QUOTES); ?></td>
					<td><?php echo htmlspecialchars($p['em_mb_name'], ENT_QUOTES); ?></td>
					<td><?php echo $p['em_datetime']; ?></td>
					<td>
						<a href="./emoticon_list_update.php?act=one_approve&me_id=<?php echo $pid; ?>&token=<?php echo $token; ?>" class="btn_frmline" onclick="return confirm('승인하시겠습니까?');">승인</a>
						<a href="./emoticon_list_update.php?act=one_reject&me_id=<?php echo $pid; ?>&token=<?php echo $token; ?>" class="btn_frmline" onclick="return confirm('거부(삭제)하시겠습니까?');">거부</a>
					</td>
				</tr>
				<?php endwhile; ?>
				</tbody>
			</table>
		</div>
		<div class="btn_list01 btn_list">
			<button type="submit" name="bulk" value="approve">선택 승인</button>
			<button type="submit" name="bulk" value="reject" onclick="return confirm('선택 항목을 모두 거부(삭제)하시겠습니까?');">선택 거부</button>
		</div>
	</form>
</section>
<?php endif; ?>

<div class="local_ov01 local_ov">
	<?php echo $listall ?>
	전체 <?php echo number_format($total_count) ?> 건
</div>

<form name="fpointlist" id="fpointlist" method="post" action="./emoticon_list_update.php" onsubmit="return fpointlist_submit(this);">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<input type="hidden" name="token" value="<?php echo $token ?>">

<div class="tbl_head01 tbl_wrap">
	<table>
	<caption><?php echo $g5['title']; ?> 목록</caption>
	<colgroup>
		<col style="width: 40px;" />
		<col style="width: 70px;"/>
		<col style="width: 90px;" />
		<col  />
		<col style="width: 110px;" />
		<col style="width: 110px;" />
	</colgroup>
	<thead>
	<tr>
		<th scope="col">
			<label for="chkall" class="sound_only">이모티콘 내역 전체</label>
			<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
		</th>
		<th scope="col">사용</th>
		<th scope="col">이미지</th>
		<th scope="col">명령어</th>
		<th scope="col">신청자</th>
		<th scope="col">이미지 교체</th>
	</tr>
	</thead>
	<tbody>
	<?php
	for ($i=0; $row=sql_fetch_array($result); $i++) {
		$bg = 'bg'.($i%2);
		$row_me_id = (int)$row['me_id'];
	?>
	<tr class="<?php echo $bg; ?>">
		<td style="text-align: center">
			<input type="hidden" name="me_id[<?php echo $i ?>]" value="<?php echo $row_me_id ?>" id="me_id_<?php echo $i ?>">
			<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
		</td>
		<td style="text-align:center;">
			<select name="em_use[<?php echo $i ?>]">
				<option value="1" <?php if ((int)$row['em_use'] === 1) echo 'selected'; ?>>사용</option>
				<option value="0" <?php if ((int)$row['em_use'] !== 1) echo 'selected'; ?>>보류</option>
			</select>
		</td>
		<td style="text-align: center"><img src="<?php echo G5_URL.$row['me_img']; ?>" alt="<?php echo htmlspecialchars($row['me_text'], ENT_QUOTES); ?>" style="max-height:32px;"></td>
		<td class="txt-left"><input type="text" name="me_text[<?php echo $i ?>]" value="<?php echo htmlspecialchars($row['me_text'], ENT_QUOTES); ?>" class="frm_input" style="width:90%;"></td>
		<td><?php echo $row['em_mb_name'] !== '' ? htmlspecialchars($row['em_mb_name'], ENT_QUOTES) : '<span style="color:#888;">관리자</span>'; ?></td>
		<td style="text-align:center;">
			<input type="file" accept="image/*" style="width:100px;" data-me-id="<?php echo $row_me_id ?>" onchange="emoticon_img_replace(this);">
		</td>
	</tr>
	<?php
	}

	if ($i == 0)
		echo '<tr><td colspan="6" class="empty_table">자료가 없습니다.</td></tr>';
	?>
	</tbody>
	</table>
</div>

<div class="btn_list01 btn_list">
	<input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value">
	<input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
</div>

</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['PHP_SELF']}?$qstr&amp;page="); ?>

<section id="point_mng">
	<h2 class="h2_frm">이모티콘 등록</h2>

	<form name="fpointlist2" method="post" id="fpointlist2" action="./emoticon_form_update.php" autocomplete="off" enctype="multipart/form-data">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="sst" value="<?php echo $sst ?>">
	<input type="hidden" name="sod" value="<?php echo $sod ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">
	<input type="hidden" name="token" value="<?php echo $token ?>">

	<div class="tbl_frm01 tbl_wrap">
		<table>
		<colgroup>
			<col style="width: 120px;">
			<col>
		</colgroup>
		<tbody>
		<tr>
			<th scope="row"><label for="me_text">이모티콘 명령어<strong class="sound_only">필수</strong></label></th>
			<td><input type="text" name="me_text" id="me_text" class="required frm_input" required></td>
		</tr>
		<tr>
			<th scope="row"><label for="me_img">이미지<strong class="sound_only">필수</strong></label></th>
			<td><input type="file" name="me_img" id="me_img"></td>
		</tr>
		</tbody>
		</table>
	</div>

	<div class="btn_confirm01 btn_confirm">
		<input type="submit" value="확인" class="btn_submit">
	</div>

	</form>

</section>

<script>
function emoticon_img_replace(input)
{
	if (!input.files || !input.files.length) {
		return;
	}

	if (!confirm('이미지를 교체하시겠습니까?')) {
		input.value = '';
		return;
	}

	var fd = new FormData();
	fd.append('token', '<?php echo $token ?>');
	fd.append('act', 'img_replace');
	fd.append('me_id', input.getAttribute('data-me-id'));
	fd.append('me_img', input.files[0]);

	fetch('./emoticon_list_update.php', {
		method: 'POST',
		body: fd
	}).then(function() {
		location.reload();
	}).catch(function() {
		alert('이미지 교체 중 오류가 발생했습니다.');
		location.reload();
	});
}

function fpointlist_submit(f)
{
	if (!is_checked("chk[]")) {
		alert(document.pressed+" 하실 항목을 하나 이상 선택하세요.");
		return false;
	}

	if(document.pressed == "선택삭제") {
		if(!confirm("선택한 자료를 정말 삭제하시겠습니까?")) {
			return false;
		}
	}

	// 버튼 값에 따라 act 자동 설정
	var act = (document.pressed == "선택수정") ? "edit_bulk" : "";
	var h = f.elements['act'];
	if (!h) { h = document.createElement('input'); h.type='hidden'; h.name='act'; f.appendChild(h); }
	h.value = act;

	return true;
}
</script>

<?php
include_once ('./admin.tail.php');
?>
