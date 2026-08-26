<?php
$sub_menu = "400700";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$token = get_token();

$sql_common = " from {$g5['class_table']} ";

$sql_search = " where (1) ";

if (!$sst) {
	$sst  = "cl_id";
	$sod = "desc";
}
$sql_order = " order by {$sst} {$sod} ";

$sql = " select count(*) as cnt
			{$sql_common}
			{$sql_search}
			{$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
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

$g5['title'] = $config['cf_class_title'].' 관리';
include_once ('./admin.head.php');

$colspan = 7;



$class_label = $config['cf_class_title'] ? $config['cf_class_title'] : '직업';
$is_unused = !$config['cf_class_title'];

$pg_anchor = '<ul class="anchor">
	<li><a href="#anc_000">분류명 설정</a></li>
	<li><a href="#anc_001">'.$class_label.' 목록</a></li>
	<li><a href="#anc_002">'.$class_label.' 등록</a></li>
	<li><a href="#anc_003">시기별 활성 설정</a></li>
</ul>';

?>

<section id="anc_000">
	<h2 class="h2_frm">분류명 설정</h2>
	<?php echo $pg_anchor ?>
	<form method="post" action="./class_update.php" autocomplete="off">
		<input type="hidden" name="token" value="<?php echo $token ?>">
		<input type="hidden" name="only_title" value="1">
		<div class="tbl_frm01 tbl_wrap">
			<table>
			<colgroup><col style="width:150px;"><col></colgroup>
			<tbody>
			<tr>
				<th scope="row"><label for="cf_class_title">분류 이름</label></th>
				<td>
					<input type="text" name="cf_class_title" id="cf_class_title" value="<?php echo htmlspecialchars($config['cf_class_title']) ?>" placeholder="예: 직업, 클래스, 역할 (비워두면 미사용)" class="frm_input" style="width:240px;">
					<button type="submit" class="btn_03">이름 저장</button>
					<?php if($is_unused): ?><span style="color:#888;margin-left:8px;">현재 미사용 상태입니다. 분류 이름을 입력하면 사용이 시작됩니다.</span><?php endif; ?>
				</td>
			</tr>
			</tbody>
			</table>
		</div>
	</form>
</section>

<section id="anc_001">
	<h2 class="h2_frm"><?php echo $class_label?> 목록</h2>
	<?php echo $pg_anchor ?>

	<div class="local_ov01 local_ov">
		<?php echo $listall ?>
		전체 <?php echo number_format($total_count) ?> 건
	</div>

	<form name="fclasslist" id="fclasslist" method="post" action="./class_list_delete.php" onsubmit="return fclasslist_submit(this);"  enctype="multipart/form-data">
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
				<col style="width: 50px;" />
				<col style="width: 50px;" />
				<col style="width: 120px;"/>
				<col style="width: 120px;"/>
				<col  />
				<col style="width: 100px;" />
				<col style="width: 100px;" />
			</colgroup>
			<thead>
			<tr>
				<th scope="col">
					<label for="chkall" class="sound_only"><?php echo $config['cf_class_title']?> 내역 전체</label>
					<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
				</th>
				<th scope="col">IDX</th>
				<th scope="col" colspan="2">이미지</th>
				<th scope="col"><?php echo $config['cf_class_title']?>명</th>
				<th scope="col">선택권한</th>
				<th scope="col">보기</th>
			</tr>
			</thead>
			<tbody>
			<?php
			for ($i=0; $row=sql_fetch_array($result); $i++) {
				$bg = 'bg'.($i%2);
			?>

			<tr class="<?php echo $bg; ?>">
				<td style="text-align: center">
					<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
				</td>
				<td>
					<input type="text" name="cl_id[<?php echo $i ?>]" value="<?php echo $row['cl_id'] ?>" id="cl_id_<?php echo $i ?>" readonly style="width: 30px;">
				</td>
				<td style="text-align: center">
					<?php if($row['cl_img']) { ?>
						<img src='<?php echo $row['cl_img']?>' alt='<?php echo $row['cl_name']?>' style="max-width: 40px;">
						<input type="hidden" name="old_cl_img[<?php echo $i?>]" value="<?php echo $row['cl_img']?>" />
					<?php  } ?>
				</td>
				<td>
					<input type="file" name="cl_img[<?php echo $i?>]" id="cl_img<?php echo $i?>" />
				</td>
				<td>
					<input type="text" name="cl_name[<?php echo $i ?>]" value="<?php echo $row['cl_name'] ?>" class="frm_input" style="width: 98%;">
				</td>
				<td>
					<?php echo get_member_level_select("cl_auth[$i]", 2, $member['mb_level'], $row['cl_auth']) ?>
				</td>
				<td>
					<a href="<?php echo G5_URL?>/member/index.php?class=<?php echo $row['cl_id']?>" target="_blank">
						멤버목록보기
					</a>
				</td>
			</tr>

			<?php
			}

			if ($i == 0)
				echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
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
</section>

<section id="anc_002">
	<h2 class="h2_frm"><?php echo $config['cf_class_title']?>정보 등록</h2>
	<?php echo $pg_anchor ?>

	<form name="fclassform" method="post" id="fclassform" action="./class_update.php" autocomplete="off" enctype="multipart/form-data">
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
			<th scope="row"><label for="cl_name"><?php echo $config['cf_class_title']?>명<strong class="sound_only">필수</strong></label></th>
			<td><input type="text" name="cl_name" id="cl_name" class="required frm_input" required></td>
		</tr>
		<tr>
			<th scope="row"><label for="cl_img">이미지<strong class="sound_only">필수</strong></label></th>
			<td><input type="file" name="cl_img" id="cl_img"></td>
		</tr>
		<tr>
			<th scope="row"><label for="cl_auth">선택권한</label></th>
			<td><?php echo get_member_level_select('cl_auth', 2, 10) ?></td>
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
function fclasslist_submit(f)
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

	return true;
}
</script>

<?php
// =====================================================================
// 시기별 활성 매트릭스 (Wave 10)
// =====================================================================
$_all_classes = sql_query("SELECT cl_id, cl_name FROM `{$g5['class_table']}` ORDER BY cl_id ASC", false);
$_classes_arr = array();
while ($_r = sql_fetch_array($_all_classes)) $_classes_arr[] = $_r;

$_time_rows = function_exists('get_time_list') ? get_time_list(false, true, true) : array();
$_time_rows = array_values(array_filter($_time_rows, function($r){
	$t = isset($r['ti_type']) ? (string)$r['ti_type'] : 'renewal';
	return $t === 'renewal' || $t === 'au';
}));

$_mapping = array();
$_rs = sql_query("SELECT cl_id, ti_id FROM `{$g5['class_time_table']}`", false);
while ($_r = sql_fetch_array($_rs)) $_mapping[(int)$_r['cl_id']][(int)$_r['ti_id']] = 1;
?>

<section id="anc_003">
	<h2 class="h2_frm">시기별 활성 설정</h2>
	<?php echo $pg_anchor ?>

	<p style="color:#666;font-size:13px;margin:0 0 8px;">
		ⓘ 시기마다 어떤 <?php echo h($class_label); ?> 을 활성화할지 체크합니다. 리뉴얼/AU 시기만 표시.
		<br>특정 시기의 체크가 <strong>0개</strong> 이면 폴백 정책에 따라 처리됩니다 (renewal: 직전 renewal 폴백 / au: 빈 값).
		<br>매핑 테이블 자체가 완전히 비어있으면 "전체 활성" 으로 간주됩니다.
	</p>

<?php if (empty($_time_rows) || empty($_classes_arr)): ?>
	<p style="color:#888;">표시할 시기 또는 <?php echo h($class_label); ?> 이 없습니다.</p>
<?php else: ?>
	<form method="post" action="./class_time_update.php" autocomplete="off">
		<input type="hidden" name="token" value="<?php echo $token ?>">
		<div class="tbl_head01 tbl_wrap">
			<table>
				<thead>
				<tr>
					<th scope="col" style="width:80px;">IDX</th>
					<th scope="col"><?php echo h($class_label); ?>명</th>
<?php foreach ($_time_rows as $_t): ?>
					<th scope="col" style="text-align:center;">
						<?php echo h($_t['ti_name']); ?>
						<br><small style="color:#888;font-weight:normal;">[<?php echo h(time_type_label($_t['ti_type'])); ?>]</small>
					</th>
<?php endforeach; ?>
				</tr>
				</thead>
				<tbody>
<?php foreach ($_classes_arr as $_c):
	$_cl_id = (int)$_c['cl_id']; ?>
				<tr>
					<td><?php echo $_cl_id; ?></td>
					<td><?php echo h($_c['cl_name']); ?></td>
<?php foreach ($_time_rows as $_t):
		$_ti_id = (int)$_t['ti_id'];
		$_chk = !empty($_mapping[$_cl_id][$_ti_id]); ?>
					<td style="text-align:center;">
						<input type="checkbox" name="map[<?php echo $_cl_id; ?>][<?php echo $_ti_id; ?>]" value="1" <?php echo $_chk ? 'checked' : ''; ?>>
					</td>
<?php endforeach; ?>
				</tr>
<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="btn_confirm01 btn_confirm">
			<input type="submit" value="시기별 활성 저장" class="btn_submit">
		</div>
	</form>
<?php endif; ?>
</section>

<?php
include_once ('./admin.tail.php');
?>
