<?php
$sub_menu = "510010";
include_once('./_common.php');

// 화폐 명칭/단위 저장 (community_form 에서 이전)
if (isset($_POST['money_cfg_update']) && check_admin_token()) {
    $cf_money       = sql_escape_string(trim($_POST['cf_money'] ?? ''));
    $cf_money_pice  = sql_escape_string(trim($_POST['cf_money_pice'] ?? ''));
    $cf_use_point   = !empty($_POST['cf_use_point']) ? '1' : '0';
    $cf_login_point = (int)($_POST['cf_login_point'] ?? 0);
    sql_query("UPDATE `{$g5['config_table']}` SET cf_money = '{$cf_money}', cf_money_pice = '{$cf_money_pice}', cf_use_point = '{$cf_use_point}', cf_login_point = '{$cf_login_point}'");
    goto_url('./point_list.php');
}

$sql_common = " from {$g5['point_table']} ";

$sql_search = " where (1) ";

if ($stx) {
	$sql_search .= " and ( ";
	switch ($sfl) {
		case 'mb_id' :
			$sql_search .= " ({$sfl} = '{$stx}') ";
			break;
		default :
			$sql_search .= " ({$sfl} like '%{$stx}%') ";
			break;
	}
	$sql_search .= " ) ";
}

if (!$sst) {
	$sst  = "po_id";
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

$listall = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="ov_listall">전체목록</a>';

$mb = array();
if ($sfl == 'mb_id' && $stx)
	$mb = get_member($stx);

$g5['title'] = $config['cf_money'].' 관리';
include_once ('./admin.head.php');

$colspan = 8;

?>

<details class="adm-panel" open>
<summary><?php echo get_text($config['cf_money']); ?> 명칭 / 단위 설정</summary>
<div class="adm-panel-body">
<form method="post" action="">
<?php echo get_admin_token_field(); ?>
<input type="hidden" name="money_cfg_update" value="1">
<p class="adm-panel-help">사이트에서 사용할 <strong>화폐의 이름과 단위</strong>를 지정합니다. (예: 포인트 / 점)</p>
<div class="adm-opt-grid">
	<label class="adm-opt-label" for="cf_money">화폐 명칭</label>
	<div>
		<input type="text" name="cf_money" id="cf_money" value="<?php echo get_text($config['cf_money']); ?>" size="12" class="frm_input" placeholder="예: 포인트">
	</div>
	<label class="adm-opt-label" for="cf_money_pice">단위</label>
	<div>
		<input type="text" name="cf_money_pice" id="cf_money_pice" value="<?php echo get_text($config['cf_money_pice']); ?>" size="8" class="frm_input" placeholder="예: 점">
	</div>
	<label class="adm-opt-label" for="cf_use_point">화폐 기능</label>
	<div>
		<label><input type="checkbox" name="cf_use_point" id="cf_use_point" value="1"<?php echo !empty($config['cf_use_point'])?' checked':''; ?>> 화폐(포인트) 기능 사용</label>
	</div>
	<label class="adm-opt-label" for="cf_login_point">로그인 시 획득</label>
	<div>
		<input type="number" name="cf_login_point" id="cf_login_point" value="<?php echo (int)($config['cf_login_point'] ?? 0); ?>" size="6" class="frm_input"> 점 <span class="red" style="font-size:11px;">(1일 1회)</span>
	</div>
</div>
<div class="adm-panel-actions">
	<button type="submit" class="btn_submit">화폐 설정 저장</button>
</div>
</form>
</div>
</details>

<div class="local_ov01 local_ov">
	<?php echo $listall ?>
	전체 <?php echo number_format($total_count) ?> 건
	<?php
	if (isset($mb['mb_id']) && $mb['mb_id']) {
		echo '&nbsp;(' . $mb['mb_id'] .' 님 '.$config['cf_money'].' 합계 : ' . number_format($mb['mb_point']) . '점)';
	} else {
		$row2 = sql_fetch(" select sum(po_point) as sum_point from {$g5['point_table']} ");
		echo '&nbsp;(전체 합계 '.number_format($row2['sum_point']).'점)';
	}
	?>
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
	<option value="mb_id"<?php echo get_selected($_GET['sfl'], "mb_id"); ?>>회원아이디</option>
	<option value="po_content"<?php echo get_selected($_GET['sfl'], "po_content"); ?>>내용</option>
</select>
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" id="stx" required class="required frm_input">
<input type="submit" class="btn_submit" value="검색">
</form>
<br />

<form name="fpointlist" id="fpointlist" method="post" action="./point_list_delete.php" onsubmit="return fpointlist_submit(this);">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<input type="hidden" name="token" value="">

<div class="tbl_head01 tbl_wrap">
	<table>
	<caption><?php echo $g5['title']; ?> 목록</caption>
	<thead>
	<tr>
		<th scope="col">
			<label for="chkall" class="sound_only"><?php echo $config['cf_money']?> 내역 전체</label>
			<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
		</th>
		<th scope="col"><?php echo subject_sort_link('mb_id') ?>회원아이디</a></th>
		<th scope="col">이름</th>
		<th scope="col"><?php echo subject_sort_link('po_content') ?>내용</a></th>
		<th scope="col"><?php echo subject_sort_link('po_datetime') ?>일시</a></th>
		<th scope="col"><?php echo subject_sort_link('po_point') ?><?php echo $config['cf_money']?></a></th>
		<th scope="col"><?php echo $config['cf_money']?>합</th>
	</tr>
	</thead>
	<tbody>
	<?php
	for ($i=0; $row=sql_fetch_array($result); $i++) {
		if ($i==0 || ($row2['mb_id'] != $row['mb_id'])) {
			$sql2 = " select mb_id, mb_name, mb_nick, mb_point from {$g5['member_table']} where mb_id = '{$row['mb_id']}' ";
			$row2 = sql_fetch($sql2);
		}

		$mb_nick = get_sideview($row['mb_id'], $row2['mb_nick']);

		$link1 = $link2 = '';
		if (!preg_match("/^\@/", $row['po_rel_table']) && $row['po_rel_table']) {
			$link1 = '<a href="'.G5_BBS_URL.'/board.php?bo_table='.$row['po_rel_table'].'&amp;wr_id='.$row['po_rel_id'].'" target="_blank">';
			$link2 = '</a>';
		}

		$expr = '';

		$bg = 'bg'.($i%2);
	?>

	<tr class="<?php echo $bg; ?>">
		<td>
			<input type="hidden" name="mb_id[<?php echo $i ?>]" value="<?php echo $row['mb_id'] ?>" id="mb_id_<?php echo $i ?>">
			<input type="hidden" name="po_id[<?php echo $i ?>]" value="<?php echo $row['po_id'] ?>" id="po_id_<?php echo $i ?>">
			<label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo $row['po_content'] ?> 내역</label>
			<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
		</td>
		<td><a href="?sfl=mb_id&amp;stx=<?php echo $row['mb_id'] ?>"><?php echo $row['mb_id'] ?></a></td>
		<td><?php echo get_text($row2['mb_name']); ?></td>
		<td class="txt-left"><?php echo $link1 ?><?php echo $row['po_content'] ?><?php echo $link2 ?></td>
		<td><?php echo $row['po_datetime'] ?></td>
		<td><?php echo number_format($row['po_point']) ?></td>
		<td><?php echo number_format($row['po_mb_point']) ?></td>
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
	<input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
</div>

</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>

<section id="point_mng">
	<h2 class="h2_frm">개별회원 <?php echo $config['cf_money']?> 증감 설정</h2>

	<form name="fpointlist2" method="post" id="fpointlist2" action="./point_update.php" autocomplete="off">
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
					<th scope="row">지급유형</th>
					<td>
						<input type="radio" id="take_type_01" name="take_type" value="P" checked onclick="if(document.getElementById('take_type_01').checked) $('#take_member_name').show();"/>
						<label for="take_type_01">개별지급</label>
						&nbsp;&nbsp;
						<input type="radio" id="take_type_02" name="take_type" value="A" onclick="if(document.getElementById('take_type_02').checked) $('#take_member_name').hide();"/>
						<label for="take_type_02">전체지급</label>
					</td>
				</tr>
				<tr id="take_member_name">
					<th scope="row">멤버 닉네임</th>
					<td>
						<?php echo help('개별지급 시 입력') ?>
						<input type="hidden" name="mb_id" id="mb_id" value="" />
						<input type="text" name="mb_name" value="" id="mb_name" onkeyup="get_ajax_member(this, 'member_list', 'mb_id');" />
						<div id="member_list" class="ajax-list-box"><div class="list"></div></div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="po_content"><?php echo $config['cf_money']?> 내용<strong class="sound_only">필수</strong></label></th>
					<td><input type="text" name="po_content" id="po_content" required class="required frm_input" size="80"></td>
				</tr>
				<tr>
					<th scope="row"><label for="po_point"><?php echo $config['cf_money']?><strong class="sound_only">필수</strong></label></th>
					<td><input type="text" name="po_point" id="po_point" required class="required frm_input"></td>
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

	return true;
}
</script>

<?php
include_once ('./admin.tail.php');
?>
