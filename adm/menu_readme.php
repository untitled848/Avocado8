<?php
$sub_menu = '';
include_once('./_common.php');
include_once(G5_ADMIN_PATH.'/_menu_readme.inc.php');

$m = isset($_GET['m']) ? preg_replace('/[^0-9]/', '', $_GET['m']) : '';
if ($m === '0') {
    $m = '000';
}
if (strlen($m) > 0 && strlen($m) < 3) {
    $m = str_pad($m, 3, '0', STR_PAD_LEFT);
}

$info = ($m !== '') ? adm_menu_readme_info($m) : null;
if (!$info) {
    alert('해당 메뉴 설명서가 없습니다.');
}

$menu_name = '';
$menu_key = 'menu'.$info['key'];
if (isset($menu[$menu_key][0][1])) {
    $menu_name = $menu[$menu_key][0][1];
}

$g5['title'] = ($menu_name !== '' ? $menu_name.' 설명서' : '관리자 메뉴 설명서');
include_once('./admin.head.php');
?>

<div class="local_desc01 local_desc">
    <p>
        <strong><?php echo h($menu_name !== '' ? $menu_name : $info['key']); ?></strong>
        메뉴 설명서입니다.
        <small style="color:#888;">(docs/adm/<?php echo h($info['file']); ?>)</small>
    </p>
    <p>
        <a href="<?php echo G5_ADMIN_URL; ?>/" class="btn_02">← 관리자 메인</a>
        <?php if (!empty($menu[$menu_key][0][2])): ?>
        <a href="<?php echo $menu[$menu_key][0][2]; ?>" class="btn_02">이 메뉴 열기</a>
        <?php endif; ?>
    </p>
</div>

<div class="adm-md">
<?php echo $info['full_html']; ?>
</div>

<?php
include_once('./admin.tail.php');
