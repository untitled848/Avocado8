<?php
$sub_menu = "100300";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');

if (!$is_admin) alert('관리자만 접근 가능합니다.');

$time_rows = array();
if (!empty($g5['time_table'])) {
    $r = sql_query("SELECT ti_order, ti_name FROM `{$g5['time_table']}` ORDER BY ti_order ASC", false);
    if ($r) { while ($x = sql_fetch_array($r)) { $time_rows[] = $x; } }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk') {
    check_admin_token();
    $ids   = isset($_POST['bn_id']) && is_array($_POST['bn_id']) ? $_POST['bn_id'] : array();
    $tarr  = isset($_POST['bn_time']) ? $_POST['bn_time'] : array();
    $value = function_exists('shop_time_to_db') ? shop_time_to_db($tarr) : '';
    $value = sql_escape_string($value);

    $cnt = 0;
    foreach ($ids as $id) {
        $id = (int)$id;
        if ($id <= 0) continue;
        sql_query("UPDATE `{$g5['banner_table']}` SET bn_time = '{$value}' WHERE bn_id = '{$id}'", false);
        $cnt++;
    }
    // banner cache 무효화
    @array_map('unlink', glob(G5_DATA_PATH.'/cache/banner-*.php') ?: array());
    alert($cnt.'개 슬라이드의 출력 시기를 일괄 변경했습니다.', './banner_time_bulk.php');
}

$g5['title'] = '출력 시기 일괄 설정 (메인슬라이드)';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$rows = array();
$r = sql_query("SELECT bn_id, bn_alt, bn_img, bn_time, bn_order FROM `{$g5['banner_table']}` ORDER BY bn_order, bn_id DESC", false);
if ($r) { while ($x = sql_fetch_array($r)) { $rows[] = $x; } }
?>
<div class="stb-wrap">
<div class="stb-help">
    체크한 메인슬라이드들의 <b>출력 시기</b>를 일괄 변경합니다.<br>
    "전체 시기(all)" 체크 또는 모두 미체크 시, 모든 시기에 출력됩니다.
</div>

<form method="post" onsubmit="return confirm('선택한 슬라이드들의 출력 시기를 변경합니다. 진행하시겠습니까?');">
<input type="hidden" name="action" value="bulk">
<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">

<div class="stb-time-box">
    <h3>적용할 시기 선택</h3>
    <label class="all">
        <input type="checkbox" name="bn_time[]" id="bulk_all" value="all"
            onclick="(function(c){var cs=document.querySelectorAll('input[name=\'bn_time[]\']:not(#bulk_all)');for(var i=0;i<cs.length;i++){cs[i].checked=false;cs[i].disabled=c.checked;}})(this);">
        <strong>전체 시기 (all)</strong>
    </label>
    <?php foreach ($time_rows as $tr):
        $tio = (int)$tr['ti_order']; ?>
    <label>
        <input type="checkbox" name="bn_time[]" value="<?php echo $tio; ?>"
            onclick="if(this.checked){document.getElementById('bulk_all').checked=false;}">
        #<?php echo $tio; ?> <?php echo htmlspecialchars($tr['ti_name'], ENT_QUOTES, 'UTF-8'); ?>
    </label>
    <?php endforeach; ?>
</div>

<div class="stb-list">
<table>
<thead>
<tr>
    <th style="width:30px;"><input type="checkbox" onclick="var cs=document.querySelectorAll('input[name=\'bn_id[]\']');for(var i=0;i<cs.length;i++)cs[i].checked=this.checked;"></th>
    <th style="width:60px;">ID</th>
    <th style="width:140px;">이미지</th>
    <th>대체텍스트</th>
    <th style="width:80px;">순서</th>
    <th style="width:140px;">현재 시기</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $row):
    $cur_time_arr = function_exists('shop_time_from_db') ? shop_time_from_db($row['bn_time']) : array();
    $cur_label = empty($cur_time_arr) ? '<span style="color:#999;">전체</span>' : implode(', ', array_map(function($v){return '#'.$v;}, $cur_time_arr));
?>
<tr>
    <td><input type="checkbox" name="bn_id[]" value="<?php echo (int)$row['bn_id']; ?>"></td>
    <td><?php echo (int)$row['bn_id']; ?></td>
    <td><?php if (!empty($row['bn_img'])): ?><img src="<?php echo htmlspecialchars($row['bn_img'], ENT_QUOTES, 'UTF-8'); ?>" alt=""><?php endif; ?></td>
    <td><?php echo htmlspecialchars($row['bn_alt'], ENT_QUOTES, 'UTF-8'); ?></td>
    <td><?php echo (int)$row['bn_order']; ?></td>
    <td class="cur-time"><?php echo $cur_label; ?></td>
</tr>
<?php endforeach; ?>
<?php if (empty($rows)): ?>
<tr><td colspan="6" style="text-align:center;color:#999;padding:20px;">메인슬라이드가 없습니다.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<div style="margin-top:18px;text-align:center;">
    <button type="submit" class="ui-btn admin">선택 항목 일괄 변경</button>
    <a href="./banner_list.php" class="ui-btn">목록으로</a>
</div>
</form>

</div>

<?php include_once(G5_ADMIN_PATH.'/admin.tail.php'); ?>
