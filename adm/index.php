<?php
include_once('./_common.php');
include_once(G5_ADMIN_PATH.'/_menu_readme.inc.php');

$g5['title'] = '관리자메인';
include_once ('./admin.head.php');
?>

<section>
	<h2>시스템 페이지</h2>
	<div class="tbl_frm01 tbl_wrap">
		<table>
		<tbody>
			<tr>
				<th style="width:120px;">멤버목록</th>
				<td><a href="<?php echo G5_URL?>/member" target="_blank"><?php echo G5_URL?>/member</a></td>
				<th style="width:120px;">신청자목록</th>
				<td><a href="<?php echo G5_URL?>/member/ready.php" target="_blank"><?php echo G5_URL?>/member/ready.php</a></td>
				<th>마이페이지</th>
				<td><a href="<?php echo G5_URL?>/mypage" target="_blank"><?php echo G5_URL?>/mypage</a></td>
			</tr>
			<tr>
				<th>상점</th>
				<td><a href="<?php echo G5_URL?>/shop" target="_blank"><?php echo G5_URL?>/shop</a></td>
				<th>세계관</th>
				<td><a href="<?php echo G5_URL?>/world" target="_blank"><?php echo G5_URL?>/world</a></td>
				<th>공지</th>
				<td><a href="<?php echo G5_URL?>/notice" target="_blank"><?php echo G5_URL?>/notice</a></td>
			</tr>
		</tbody>
		</table>
	</div>
</section>
<section>
	<h2>관리자 메뉴</h2>
	<nav class="index-gnb">
		<?php
		$gnb_str = "<ul>";
		foreach($amenu as $key=>$value) {
			$href1 = $href2 = '';
			if ($menu['menu'.$key][0][2]) {
				$menu_label = $menu['menu'.$key][0][1];
				$href1 = '<a href="'.$menu['menu'.$key][0][2].'" class="gnb_1da no-submenu" data-text="'.htmlspecialchars($menu_label, ENT_QUOTES, 'UTF-8').'">';
				$href2 = '</a>';
			} else {
				continue;
			}
			$current_class = "";
			if (isset($sub_menu) && (substr($sub_menu, 0, 3) == substr($menu['menu'.$key][0][0], 0, 3)))
				$current_class = " gnb_1dli_air";
			$gnb_str .= '<li class="gnb_1dli'.$current_class.'">'.PHP_EOL;
			$gnb_str .= '<div class="gnb_row">'.PHP_EOL;
			$gnb_str .=  $href1 . $menu['menu'.$key][0][1] . $href2;
			$gnb_str .=  print_menu1('menu'.$key, 1);
			$readme_url = adm_menu_readme_url($key);
			if ($readme_url !== '') {
				$gnb_str .= '<a href="'.htmlspecialchars($readme_url, ENT_QUOTES, 'UTF-8').'" class="gnb_readme_open">설명서</a>';
			}
			$gnb_str .=  "</div></li>";
		}
		$gnb_str .= "</ul>";
		echo $gnb_str;
		?>
	</nav>
</section>

<?php
include_once ('./admin.tail.php');
?>
