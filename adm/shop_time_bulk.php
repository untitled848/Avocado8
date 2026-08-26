<?php
$sub_menu = "510100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');

if (!$is_admin) alert('관리자만 접근 가능합니다.');

// 시기 목록
$time_rows = array();
if (!empty($g5['time_table'])) {
    $r = sql_query("SELECT ti_order, ti_name FROM `{$g5['time_table']}` ORDER BY ti_order ASC", false);
    if ($r) { while ($x = sql_fetch_array($r)) { $time_rows[] = $x; } }
}

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk') {
    check_admin_token();
    $ids   = isset($_POST['sh_id']) && is_array($_POST['sh_id']) ? $_POST['sh_id'] : array();
    $tarr  = isset($_POST['sh_time']) ? $_POST['sh_time'] : array();
    $value = function_exists('shop_time_to_db') ? shop_time_to_db($tarr) : '';
    $value = sql_escape_string($value);

    $cnt = 0;
    foreach ($ids as $id) {
        $id = (int)$id;
        if ($id <= 0) continue;
        sql_query("UPDATE `{$g5['shop_table']}` SET sh_time = '{$value}' WHERE sh_id = '{$id}'", false);
        $cnt++;
    }
    alert($cnt.'개 항목의 진열 시기를 일괄 변경했습니다.', './shop_time_bulk.php');
}

$g5['title'] = '진열 시기 일괄 설정 (상점)';
include_once('./admin.head.php');

// 모든 상점 진열 항목 조회
$rows = array();
$r = sql_query("SELECT s.sh_id, s.it_id, s.sh_time, s.sh_order, i.it_name, i.it_2 FROM `{$g5['shop_table']}` s LEFT JOIN `{$g5['item_table']}` i ON s.it_id = i.it_id ORDER BY i.it_2, s.sh_order", false);
if ($r) { while ($x = sql_fetch_array($r)) { $rows[] = $x; } }
?>
<div class="stb-wrap">
<div class="stb-help">
    체크한 항목들의 <b>진열 시기</b>를 일괄 변경합니다.<br>
    "전체 시기(all)" 체크 또는 모두 미체크 시, 모든 시기에 출력됩니다.
</div>

<form method="post" name="fbulk" onsubmit="return confirm('선택한 항목들의 진열 시기를 변경합니다. 진행하시겠습니까?');">
<input type="hidden" name="action" value="bulk">
<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">

<div class="stb-time-box">
    <h3>적용할 시기 선택</h3>
    <label class="all">
        <input type="checkbox" name="sh_time[]" id="bulk_all" value="all"
            onclick="(function(c){var cs=document.querySelectorAll('input[name=\'sh_time[]\']:not(#bulk_all)');for(var i=0;i<cs.length;i++){cs[i].checked=false;cs[i].disabled=c.checked;}})(this);">
        <strong>전체 시기 (all)</strong>
    </label>
    <?php foreach ($time_rows as $tr):
        $tio = (int)$tr['ti_order']; ?>
    <label>
        <input type="checkbox" name="sh_time[]" value="<?php echo $tio; ?>"
            onclick="if(this.checked){document.getElementById('bulk_all').checked=false;}">
        #<?php echo $tio; ?> <?php echo htmlspecialchars($tr['ti_name'], ENT_QUOTES, 'UTF-8'); ?>
    </label>
    <?php endforeach; ?>
</div>

<div class="stb-list">
<table>
<thead>
<tr>
    <th style="width:30px;"><input type="checkbox" onclick="var cs=document.querySelectorAll('input[name=\'sh_id[]\']');for(var i=0;i<cs.length;i++)cs[i].checked=this.checked;"></th>
    <th style="width:120px;">분류</th>
    <th style="width:120px;">it_id</th>
    <th>아이템명</th>
    <th>현재 시기</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $row):
    $cur_time_arr = function_exists('shop_time_from_db') ? shop_time_from_db($row['sh_time']) : array();
    $cur_label = empty($cur_time_arr) ? '<span style="color:#999;">전체</span>' : implode(', ', array_map(function($v){return '#'.$v;}, $cur_time_arr));
?>
<tr>
    <td><input type="checkbox" name="sh_id[]" value="<?php echo (int)$row['sh_id']; ?>"></td>
    <td><?php echo htmlspecialchars($row['it_2'], ENT_QUOTES, 'UTF-8'); ?></td>
    <td><?php echo htmlspecialchars($row['it_id'], ENT_QUOTES, 'UTF-8'); ?></td>
    <td><?php echo htmlspecialchars($row['it_name'], ENT_QUOTES, 'UTF-8'); ?></td>
    <td class="cur-time"><?php echo $cur_label; ?></td>
</tr>
<?php endforeach; ?>
<?php if (empty($rows)): ?>
<tr><td colspan="5" style="text-align:center;color:#999;padding:20px;">진열 항목이 없습니다.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<div style="margin-top:18px;text-align:center;">
    <button type="submit" class="ui-btn admin">선택 항목 일괄 변경</button>
    <a href="./shop_list.php" class="ui-btn">목록으로</a>
</div>
</form>

</div>

<?php include_once('./admin.tail.php'); ?>
