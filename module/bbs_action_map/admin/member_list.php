<?php
if (!defined('_GNUBOARD_')) exit;

$sub_menu = function_exists('mdl_admin_menu_code') ? mdl_admin_menu_code('bbs_action_map', '202') : '003202';

$sfl = isset($_GET['sfl']) ? trim($_GET['sfl']) : 'ch_name';
$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if (!in_array($sfl, array('ch_name', 'mb_id'), true)) $sfl = 'ch_name';
if ($page < 1) $page = 1;

// ma_id 컬럼 보장
if (!empty($g5['character_table']) && function_exists('mdl_db_add_column')) {
    mdl_db_add_column($g5['character_table'], 'ma_id', "int(11) NOT NULL DEFAULT '0'");
}

$sql_common = " FROM `{$g5['character_table']}` ";
$sql_search = " WHERE 1 ";
if ($stx !== '') {
    $like = sql_real_escape_string($stx).'%';
    $sql_search .= " AND `{$sfl}` LIKE '{$like}' ";
}
$sql_order = " ORDER BY ch_id ASC ";

$row = sql_fetch("SELECT COUNT(*) AS cnt {$sql_common} {$sql_search}");
$total_count = isset($row['cnt']) ? (int)$row['cnt'] : 0;
$rows = 50;
$total_page = (int)ceil($total_count / $rows);
if ($total_page > 0 && $page > $total_page) $page = $total_page;
$from = ($page - 1) * $rows;

$result = sql_query("SELECT * {$sql_common} {$sql_search} {$sql_order} LIMIT {$from}, {$rows}");

$maps = array();
$mrs = sql_query("SELECT ma_id, ma_name FROM `{$g5['module_map_table']}` ORDER BY ma_id ASC");
if ($mrs) {
    while ($m = sql_fetch_array($mrs)) {
        $maps[] = array('id' => (int)$m['ma_id'], 'name' => (string)$m['ma_name']);
    }
}

$qstr = http_build_query(array('m' => 'bbs_action_map', 'p' => 'member_list', 'sfl' => $sfl, 'stx' => $stx));
$g5['title'] = '캐릭터별 위치 관리';
?>
<div class="local_ov01 local_ov">
    총캐릭터수 <?php echo number_format($total_count); ?>명
</div>

<form id="fsearch" class="local_sch01 local_sch" method="get">
    <input type="hidden" name="m" value="bbs_action_map">
    <input type="hidden" name="p" value="member_list">
    <select name="sfl" id="sfl">
        <option value="ch_name" <?php echo get_selected($sfl, 'ch_name'); ?>>캐릭터 이름</option>
        <option value="mb_id" <?php echo get_selected($sfl, 'mb_id'); ?>>오너 아이디</option>
    </select>
    <input type="text" name="stx" value="<?php echo get_text($stx); ?>" class="frm_input">
    <input type="submit" class="btn_submit" value="검색">
</form>

<div class="btn_add01 btn_add">
    <a href="?m=bbs_action_map&amp;p=map_list">맵 관리</a>
</div>

<form name="fmemberlist" method="post" action="?m=bbs_action_map&amp;p=member_list_update" onsubmit="return fmemberlist_submit(this);">
<input type="hidden" name="sfl" value="<?php echo htmlspecialchars($sfl, ENT_QUOTES); ?>">
<input type="hidden" name="stx" value="<?php echo htmlspecialchars($stx, ENT_QUOTES); ?>">
<input type="hidden" name="page" value="<?php echo (int)$page; ?>">

<div class="tbl_head01 tbl_wrap">
<table>
<thead>
<tr>
    <th><input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)"></th>
    <th>유형</th>
    <th>이름</th>
    <th>위치</th>
</tr>
</thead>
<tbody>
<?php
$i = 0;
while ($row = sql_fetch_array($result)) {
    $bg = 'bg'.($i % 2);
?>
<tr class="<?php echo $bg; ?>">
    <td>
        <input type="hidden" name="ch_id[<?php echo $i; ?>]" value="<?php echo (int)$row['ch_id']; ?>">
        <input type="checkbox" name="chk[]" value="<?php echo $i; ?>" id="chk_<?php echo $i; ?>">
    </td>
    <td><?php echo htmlspecialchars(isset($row['ch_type']) ? $row['ch_type'] : '', ENT_QUOTES); ?></td>
    <td class="txt-left"><?php echo get_text($row['ch_name']); ?></td>
    <td>
        <select name="ma_id[<?php echo $i; ?>]" style="width:100%;">
            <option value="0">위치 미지정</option>
            <?php foreach ($maps as $m) { ?>
            <option value="<?php echo (int)$m['id']; ?>" <?php echo get_selected($m['id'], isset($row['ma_id']) ? $row['ma_id'] : 0); ?>><?php echo htmlspecialchars($m['name'], ENT_QUOTES); ?></option>
            <?php } ?>
        </select>
    </td>
</tr>
<?php
    $i++;
}
if ($i === 0) {
    echo '<tr><td colspan="4" class="empty_table">자료가 없습니다.</td></tr>';
}
?>
</tbody>
</table>
</div>

<div class="btn_list01 btn_list">
    <input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value">
</div>
</form>

<?php
echo get_paging(
    G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'],
    $page,
    $total_page,
    G5_MDL_ADMIN_URL.'/module.php?'.$qstr.'&amp;page='
);
?>

<script>
function fmemberlist_submit(f) {
    if (!is_checked("chk[]")) {
        alert(document.pressed + " 하실 항목을 하나 이상 선택하세요.");
        return false;
    }
    return true;
}
</script>
