<?php
$sub_menu = "100400";
include_once('./_common.php');

auth_check($auth[$sub_menu], "r");

$g5['title'] = '인트로 관리';
include_once (G5_ADMIN_PATH.'/admin.head.php');

$sql_common = " from {$g5['intro_table']} ";

// 테이블의 전체 레코드수만 얻음
$sql = " select count(*) as cnt " . $sql_common;
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select * {$sql_common}
		order by bn_order, bn_id desc
		limit {$from_record}, {$rows}  ";
$result = sql_query($sql);

?>

<div class="local_ov01 local_ov">
	등록된 이미지 <?php echo $total_count; ?>개
</div>

<div class="btn_add01 btn_add">
	<a href="./intro_form.php">인트로 추가</a>
</div>

<div class="tbl_head01 tbl_wrap">
	<table>
		<caption><?php echo $g5['title']; ?> 목록</caption>
		<colgroup>
			<col style="width: 50px;" />
			<col style="width: 170px;" />
			<col />
			<col style="width: 130px;" />
			<col style="width: 130px;" />
			<col style="width: 130px;" />
			<col style="width: 80px;" />
			<col style="width: 80px;" />
		</colgroup>
		<thead>
			<tr>
				<th scope="col" class="bo-right">ID</th>
				<th scope="col">이미지</th>
				<th scope="col">대체텍스트</th>
				<th scope="col">시작일시</th>
				<th scope="col">종료일시</th>
				<th scope="col">출력시기</th>
				<th scope="col">출력순서</th>
				<th scope="col">관리</th>
			</tr>
		</thead>
		<tbody>
			<?php
			
			for ($i=0; $row=sql_fetch_array($result); $i++) {
				// 새창 띄우기인지
				$bn_new_win = ($row['bn_new_win']) ? 'target="_blank"' : '';

				$bn_img_obj = "";
				$link_tag = false;
				if($row['bn_img']) {
					if ($row['bn_url'] && $row['bn_url'] != "http://") { $link_tag = true; }

					if($link_tag) $bn_img_obj .= "<a hreef='".$row['bn_url']."' ".$bn_new_win.">";
					$bn_img_obj .= "<img src='".$row['bn_img']."' alt='".$row['bn_alt']."' class='banner-thumb'/>";
					if($link_tag) $bn_img_obj .= "</a>";
				}

				$bn_begin_time = substr($row['bn_begin_time'], 2, 14);
				$bn_end_time   = substr($row['bn_end_time'], 2, 14);

				// 출력 시기 (bn_time)
				$bn_time_disp = '<span style="color:#999;">전체</span>';
				if (function_exists('shop_time_from_db') && !empty($row['bn_time']) && strtolower($row['bn_time']) !== 'all') {
					$_bnt_arr = shop_time_from_db($row['bn_time']);
					if (!empty($_bnt_arr)) {
						static $_bnt_name_map = null;
						if ($_bnt_name_map === null) {
							$_bnt_name_map = array();
							if (!empty($g5['time_table'])) {
								$_r2 = sql_query("SELECT ti_order, ti_name FROM `{$g5['time_table']}` ORDER BY ti_order ASC", false);
								if ($_r2) { while ($_x2 = sql_fetch_array($_r2)) { $_bnt_name_map[(int)$_x2['ti_order']] = $_x2['ti_name']; } }
							}
						}
						$_lbl = array();
						foreach ($_bnt_arr as $_tio) { $_lbl[] = '#'.$_tio.' '.(isset($_bnt_name_map[$_tio]) ? $_bnt_name_map[$_tio] : ''); }
						$bn_time_disp = '<span style="color:#7a5500;">'.htmlspecialchars(implode(', ', $_lbl), ENT_QUOTES, 'UTF-8').'</span>';
					}
				}

				$bg = 'bg'.($i%2);
			?>

			<tr class="<?php echo $bg; ?>">
				<td><?php echo $row['bn_id']; ?></td>
				<td><?php echo $bn_img_obj; ?></td>
				<td><?php echo $row['bn_alt']; ?></td>
				<td><?php echo $bn_begin_time; ?></td>
				<td><?php echo $bn_end_time; ?></td>
				<td><?php echo $bn_time_disp; ?></td>
				<td><?php echo $row['bn_order']; ?></td>
				<td>
					<a href="./intro_form.php?w=u&amp;bn_id=<?php echo $row['bn_id']; ?>">수정</a></li>
					<a href="./intro_form_update.php?w=d&amp;bn_id=<?php echo $row['bn_id']; ?>" onclick="return delete_confirm();">삭제</a>
				</td>
			</tr>
			

			<?php
			}
			if ($i == 0) {
			echo '<tr><td colspan="8" class="empty_table">자료가 없습니다.</td></tr>';
			}
			?>
		</tbody>
	</table>

</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['PHP_SELF']}?$qstr&amp;page="); ?>

<script>
$(function() {
	$(".sbn_img_view").on("click", function() {
		$(this).closest(".td_img_view").find(".sbn_image").slideToggle();
	});
});
</script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>
