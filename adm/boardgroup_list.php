<?php
$sub_menu = "300010";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

// 게시판 기본 / 기본 스킨 설정 저장 (config_form 에서 이전)
if (isset($_POST['board_default_update']) && check_admin_token()) {
	if (!$is_admin) alert('관리자만 접근 가능합니다.');
	$cf_page_rows       = (int)($_POST['cf_page_rows'] ?? 15);
	$cf_write_pages     = (int)($_POST['cf_write_pages'] ?? 10);
	$cf_delay_sec       = (int)($_POST['cf_delay_sec'] ?? 0);
	$cf_search_part     = (int)($_POST['cf_search_part'] ?? 0);
	$cf_link_target     = preg_replace('/[^a-zA-Z_]/', '', (string)($_POST['cf_link_target'] ?? '_blank'));
	$cf_image_extension = sql_escape_string(trim($_POST['cf_image_extension'] ?? ''));
	$cf_movie_extension = sql_escape_string(trim($_POST['cf_movie_extension'] ?? ''));
	$cf_filter          = sql_escape_string($_POST['cf_filter'] ?? '');
	sql_query("UPDATE `{$g5['config_table']}`
		SET cf_page_rows='{$cf_page_rows}',
		    cf_write_pages='{$cf_write_pages}',
		    cf_delay_sec='{$cf_delay_sec}',
		    cf_search_part='{$cf_search_part}',
		    cf_link_target='{$cf_link_target}',
		    cf_image_extension='{$cf_image_extension}',
		    cf_movie_extension='{$cf_movie_extension}',
		    cf_filter='{$cf_filter}'");
	goto_url('./boardgroup_list.php');
}


$sql_common = " from {$g5['group_table']} ";

$sql_search = " where (1) ";
if (!$is_admin)
    $sql_search .= " and (gr_admin = '{$member['mb_id']}') ";

if ($stx) {
    $sql_search .= " and ( ";
    switch ($sfl) {
        case "gr_id" :
        case "gr_admin" :
            $sql_search .= " ({$sfl} = '{$stx}') ";
            break;
        default :
            $sql_search .= " ({$sfl} like '%{$stx}%') ";
            break;
    }
    $sql_search .= " ) ";
}

if ($sst)
    $sql_order = " order by {$sst} {$sod} ";
else
    $sql_order = " order by gr_id asc ";

$sql = " select count(*) as cnt {$sql_common} {$sql_search} {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select * {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$listall = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="ov_listall">처음</a>';

$g5['title'] = '게시판그룹설정';
include_once('./admin.head.php');

$colspan = 10;
?>

<details class="adm-panel"<?php echo ($is_admin) ? ' open' : ' style="display:none;"'; ?>>
<summary>게시판 기본 설정 / 기본 스킨 설정</summary>
<div class="adm-panel-body">
<p class="adm-panel-help">모든 게시판에 공통으로 적용되는 <strong>기본값</strong>입니다. 각 게시판에서 개별 설정 시 이 값을 덮어씁니다.</p>
<form method="post" action="">
<?php echo get_admin_token_field(); ?>
<input type="hidden" name="board_default_update" value="1">
<div class="tbl_frm01 tbl_wrap">
<table>
<colgroup><col style="width:160px;"><col><col style="width:160px;"><col></colgroup>
<tbody>
<tr>
	<th scope="row"><label for="cf_page_rows">한 페이지당 줄 수</label></th>
	<td><input type="number" name="cf_page_rows" id="cf_page_rows" value="<?php echo (int)$config['cf_page_rows'] ?>" class="frm_input" size="5" min="1"></td>
	<th scope="row"><label for="cf_write_pages">페이지 표시 수</label></th>
	<td><input type="number" name="cf_write_pages" id="cf_write_pages" value="<?php echo (int)$config['cf_write_pages'] ?>" class="frm_input" size="5" min="1" required></td>
</tr>
<tr>
	<th scope="row"><label for="cf_delay_sec">글쓰기 간격</label></th>
	<td><input type="number" name="cf_delay_sec" id="cf_delay_sec" value="<?php echo (int)$config['cf_delay_sec'] ?>" class="frm_input" size="5" min="0"> 초 후 가능</td>
	<th scope="row"><label for="cf_search_part">검색 단위</label></th>
	<td><input type="number" name="cf_search_part" id="cf_search_part" value="<?php echo (int)$config['cf_search_part'] ?>" class="frm_input" size="5" min="0"> 건 단위</td>
</tr>
<tr>
	<th scope="row"><label for="cf_link_target">새창 링크</label></th>
	<td colspan="3">
		<?php echo help('글내용 중 자동 링크되는 타겟을 지정합니다.') ?>
		<select name="cf_link_target" id="cf_link_target">
			<?php foreach (['_blank','_self','_top','_new'] as $tg): ?>
			<option value="<?php echo $tg ?>"<?php echo get_selected($config['cf_link_target'], $tg) ?>><?php echo $tg ?></option>
			<?php endforeach; ?>
		</select>
	</td>
</tr>
<tr>
	<th scope="row"><label for="cf_image_extension">이미지 확장자</label></th>
	<td><input type="text" name="cf_image_extension" id="cf_image_extension" value="<?php echo $config['cf_image_extension'] ?>" class="frm_input" size="40" placeholder="jpg|jpeg|png|gif"></td>
	<th scope="row"><label for="cf_movie_extension">동영상 확장자</label></th>
	<td><input type="text" name="cf_movie_extension" id="cf_movie_extension" value="<?php echo $config['cf_movie_extension'] ?>" class="frm_input" size="40" placeholder="mp4|webm"></td>
</tr>
<tr>
	<th scope="row"><label for="cf_filter">단어 필터링</label></th>
	<td colspan="3">
		<?php echo help('입력된 단어가 포함된 내용은 게시할 수 없습니다. 단어와 단어 사이는 ,로 구분합니다.') ?>
		<textarea name="cf_filter" id="cf_filter" rows="4" style="width:100%;"><?php echo $config['cf_filter'] ?></textarea>
	</td>
</tr>
</tbody>
</table>
</div>
<div class="adm-panel-actions">
	<button type="submit" class="btn_submit">기본 설정 저장</button>
</div>
</form>
</div>
</details>

<div class="local_ov01 local_ov">
    <?php echo $listall ?>
    전체그룹 <?php echo number_format($total_count) ?>개
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
    <option value="gr_subject"<?php echo get_selected($_GET['sfl'], "gr_subject"); ?>>제목</option>
    <option value="gr_id"<?php echo get_selected($_GET['sfl'], "gr_id"); ?>>ID</option>
    <option value="gr_admin"<?php echo get_selected($_GET['sfl'], "gr_admin"); ?>>그룹관리자</option>
</select>
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" required class="required frm_input">
<input type="submit" value="검색" class="btn_submit">
</fieldset>
</form>

<?php if ($is_admin) { ?>
<div class="btn_add01 btn_add sort_with">
    <a href="./boardgroup_form.php" id="bo_gr_add">게시판그룹 추가</a>
</div>
<?php } ?>

<form name="fboardgrouplist" id="fboardgrouplist" action="./boardgroup_list_update.php" onsubmit="return fboardgrouplist_submit(this);" method="post">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<input type="hidden" name="token" value="">

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?> 목록</caption>
	<colgroup>
		<col style="width:45px;" />
		<col style="width:120px;" />
		<col />
		<col style="width:120px;" />
		<col style="width:120px;" />
		<col style="width:80px;" />
		<col style="width:80px;" />
		<col style="width:80px;" />
	</colgroup>
    <thead>
    <tr>
        <th scope="col">
            <label for="chkall" class="sound_only">그룹 전체</label>
            <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
        </th>
        <th scope="col"><?php echo subject_sort_link('gr_id') ?>그룹아이디</a></th>
        <th scope="col"><?php echo subject_sort_link('gr_subject') ?>제목</a></th>
        <th scope="col"><?php echo subject_sort_link('gr_admin') ?>그룹관리자</a></th>
        <th scope="col">게시판</th>
        <th scope="col"><?php echo subject_sort_link('gr_order') ?>출력 순서</a></th>
        <th scope="col">접속기기</th>
        <th scope="col">관리</th>
    </tr>
    </thead>
    <tbody>
    <?php
    for ($i=0; $row=sql_fetch_array($result); $i++)
    {
        // 접근회원수
        $sql1 = " select count(*) as cnt from {$g5['group_member_table']} where gr_id = '{$row['gr_id']}' ";
        $row1 = sql_fetch($sql1);

        // 게시판수
        $sql2 = " select count(*) as cnt from {$g5['board_table']} where gr_id = '{$row['gr_id']}' ";
        $row2 = sql_fetch($sql2);

        $s_upd = '<a href="./boardgroup_form.php?'.$qstr.'&amp;w=u&amp;gr_id='.$row['gr_id'].'">수정</a>';

        $bg = 'bg'.($i%2);
    ?>

    <tr class="<?php echo $bg; ?>">
        <td class="td_chk">
            <input type="hidden" name="group_id[<?php echo $i ?>]" value="<?php echo $row['gr_id'] ?>">
            <input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
        </td>
        <td class="td_grid"><?php echo $row['gr_id'] ?></td>
        <td class="td_input">
            <input type="text" name="gr_subject[<?php echo $i ?>]" value="<?php echo get_text($row['gr_subject']) ?>" id="gr_subject_<?php echo $i ?>" class="frm_input full">
        </td>
        <td class="td_mng td_input">
        <?php if ($is_admin){ ?>
            <input type="text" name="gr_admin[<?php echo $i ?>]" value="<?php echo $row['gr_admin'] ?>" id="gr_admin_<?php echo $i ?>" class="frm_input full" size="10" maxlength="20">
        <?php }else{ ?>
            <input type="hidden" name="gr_admin[<?php echo $i ?>]" value="<?php echo $row['gr_admin'] ?>"><?php echo $row['gr_admin'] ?>
        <?php } ?>
        </td>
        <td class="td_numsmall"><a href="./board_list.php?sfl=a.gr_id&amp;stx=<?php echo $row['gr_id'] ?>"><?php echo $row2['cnt'] ?></a></td>
        <td class="td_chk">
            <input type="text" name="gr_order[<?php echo $i ?>]" value="<?php echo $row['gr_order'] ?>" id="gr_order_<?php echo $i ?>" class="frm_input full" size="2">
        </td>
        <td class="td_mng">
            <select name="gr_device[<?php echo $i ?>]" id="gr_device_<?php echo $i ?>" class="full">
                <option value="both"<?php echo get_selected($row['gr_device'], 'both'); ?>>모두</option>
                <option value="pc"<?php echo get_selected($row['gr_device'], 'pc'); ?>>PC</option>
                <option value="mobile"<?php echo get_selected($row['gr_device'], 'mobile'); ?>>모바일</option>
            </select>
        </td>
        <td class="td_mngsmall"><?php echo $s_upd ?></td>
    </tr>

    <?php
        }
    if ($i == 0)
        echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
    ?>
    </table>
</div>

<div class="btn_list01 btn_list">
    <input type="submit" name="act_button" onclick="document.pressed=this.value" value="선택수정">
    <input type="submit" name="act_button" onclick="document.pressed=this.value" value="선택삭제">
    <a href="./boardgroup_form.php">게시판그룹 추가</a>
</div>
</form>

<div class="local_desc01 local_desc">
    <p>
        접근사용 옵션을 설정하시면 관리자가 지정한 회원만 해당 그룹에 접근할 수 있습니다.<br>
        접근사용 옵션은 해당 그룹에 속한 모든 게시판에 적용됩니다.
    </p>
</div>

<?php
$pagelist = get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $_SERVER['SCRIPT_NAME'].'?'.$qstr.'&amp;page=');
echo $pagelist;
?>

<script>
function fboardgrouplist_submit(f)
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
include_once('./admin.tail.php');
?>
