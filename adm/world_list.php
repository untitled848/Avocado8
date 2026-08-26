<?php
$sub_menu = "310100";
include_once('./_common.php');

auth_check($auth[$sub_menu], "r");

$g5['title'] = '세계관 페이지 관리';
include_once (G5_ADMIN_PATH.'/admin.head.php');

$sql_common = " from {$g5['world_table']} where wo_subject != '' ";

$sql = " select count(*) as cnt " . $sql_common;
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);
if ($page < 1) { $page = 1; }
$from_record = ($page - 1) * $rows;

$sql = "select * $sql_common order by wo_id limit $from_record, {$config['cf_page_rows']} ";
$result = sql_query($sql);
?>

<div class="local_ov01 local_ov">
	<?php if ($page > 1) {?><a href="<?php echo $_SERVER['SCRIPT_NAME']; ?>">처음으로</a><?php } ?>
	<span>전체 페이지 <?php echo $total_count; ?>건</span>
</div>

<div class="btn_add01 btn_add">
	<a href="./world_form.php">페이지 추가</a>
	<a href="./world_design_form.php">세계관 디자인 설정</a>
</div>

<div class="tbl_head01 tbl_wrap">
	<table>
	<caption><?php echo $g5['title']; ?> 목록</caption>
	<thead>
	<tr>
		<th scope="col">ID</th>
		<th scope="col">제목</th>
		<th scope="col">관리</th>
	</tr>
	</thead>
	<tbody>
	<?php for ($i=0; $row=sql_fetch_array($result); $i++) {
		$bg = 'bg'.($i%2);
	?>
	<tr class="<?php echo $bg; ?>">
		<td class="td_id"><?php echo $row['wo_id']; ?></td>
		<td><?php echo htmlspecialchars2($row['wo_subject']); ?></td>
		<td class="td_mng">
			<a href="./world_form.php?w=u&amp;wo_id=<?php echo $row['wo_id']; ?>"><span class="sound_only"><?php echo htmlspecialchars2($row['wo_subject']); ?> </span>수정</a>
			<a href="<?php echo G5_WORLD_URL; ?>/?wo_id=<?php echo $row['wo_id']; ?>" target="_blank"><span class="sound_only"><?php echo htmlspecialchars2($row['wo_subject']); ?> </span>보기</a>
			<a href="./world_form_update.php?w=d&amp;wo_id=<?php echo $row['wo_id']; ?>" onclick="return delete_confirm(this);"><span class="sound_only"><?php echo htmlspecialchars2($row['wo_subject']); ?> </span>삭제</a>
		</td>
	</tr>
	<?php
	}
	if ($i == 0) {
		echo '<tr><td colspan="3" class="empty_table">자료가 한건도 없습니다.</td></tr>';
	}
	?>
	</tbody>
	</table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>
