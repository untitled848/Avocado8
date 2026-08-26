<?php
if (!defined('_GNUBOARD_')) exit;

$sub_menu = function_exists('mdl_admin_menu_code') ? mdl_admin_menu_code('bbs_action_map', '201') : '003201';

$ma_id = isset($_GET['ma_id']) ? (int)$_GET['ma_id'] : (isset($ma_id) ? (int)$ma_id : 0);

// ma_id 없으면 첫 맵으로 안내 or 맵 선택 UI
$maps = array();
$mrs = sql_query("SELECT ma_id, ma_name FROM `{$g5['module_map_table']}` ORDER BY ma_id ASC");
if ($mrs) {
    while ($m = sql_fetch_array($mrs)) {
        $maps[] = array('id' => (int)$m['ma_id'], 'name' => (string)$m['ma_name']);
    }
}

if ($ma_id <= 0 && !empty($maps)) {
    $ma_id = (int)$maps[0]['id'];
}

$ma = $ma_id > 0 ? sql_fetch("SELECT * FROM `{$g5['module_map_table']}` WHERE ma_id = '{$ma_id}' LIMIT 1") : array();
if (empty($ma['ma_id'])) {
    echo '<div class="local_desc01 local_desc">먼저 <a href="?m=bbs_action_map&amp;p=map_list">맵</a>을 등록해 주세요.</div>';
    return;
}

$sql_common = " FROM `{$g5['module_map_event_table']}` WHERE ma_id = '{$ma_id}' ";
$row = sql_fetch("SELECT COUNT(*) AS cnt {$sql_common}");
$total_count = isset($row['cnt']) ? (int)$row['cnt'] : 0;
$result = sql_query("SELECT * {$sql_common} ORDER BY me_id ASC");

$g5['title'] = '['.$ma['ma_name'].'] 지역 이벤트 관리';
$colspan = 11;
?>
<div class="local_ov01 local_ov">
    전체 <?php echo number_format($total_count); ?> 건
</div>

<div class="btn_add01 btn_add">
    <a href="?m=bbs_action_map&amp;p=map_list">맵 관리</a>
    <a href="?m=bbs_action_map&amp;p=event_form&amp;ma_id=<?php echo (int)$ma_id; ?>">이벤트 추가</a>
</div>

<form method="get" class="local_sch01 local_sch" action="">
    <input type="hidden" name="m" value="bbs_action_map">
    <input type="hidden" name="p" value="event_list">
    <label for="ma_id">지역</label>
    <select name="ma_id" id="ma_id" onchange="this.form.submit()">
        <?php foreach ($maps as $m) { ?>
        <option value="<?php echo (int)$m['id']; ?>" <?php echo get_selected($m['id'], $ma_id); ?>><?php echo htmlspecialchars($m['name'], ENT_QUOTES); ?></option>
        <?php } ?>
    </select>
</form>

<form name="feventlist" id="feventlist" method="post" action="?m=bbs_action_map&amp;p=event_list_update" onsubmit="return feventlist_submit(this);">
<input type="hidden" name="ma_id" value="<?php echo (int)$ma_id; ?>">
<div class="tbl_head01 tbl_wrap">
<table>
<caption><?php echo htmlspecialchars($g5['title'], ENT_QUOTES); ?></caption>
<thead>
<tr>
    <th scope="col"><input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)"></th>
    <th scope="col">종류 / 이벤트명</th>
    <th scope="col">아이템</th>
    <th scope="col">화폐</th>
    <th scope="col">이동지역</th>
    <th scope="col">몬스터 HP</th>
    <th scope="col">몬스터 공격</th>
    <th scope="col">획득개수</th>
    <th scope="col">현재획득</th>
    <th scope="col">사용</th>
    <th scope="col">관리</th>
</tr>
</thead>
<tbody>
<?php
$i = 0;
while ($me = sql_fetch_array($result)) {
    $bg = 'bg'.($i % 2);
?>
<tr class="<?php echo $bg; ?>">
    <td class="td_chk">
        <input type="hidden" name="me_id[<?php echo $i; ?>]" value="<?php echo (int)$me['me_id']; ?>">
        <input type="checkbox" name="chk[]" value="<?php echo $i; ?>" id="chk_<?php echo $i; ?>">
    </td>
    <td>
        <select name="me_type[<?php echo $i; ?>]" class="frm_input">
            <option value="" <?php echo $me['me_type'] === '' ? 'selected' : ''; ?>>일반</option>
            <option value="아이템" <?php echo $me['me_type'] === '아이템' ? 'selected' : ''; ?>>아이템획득</option>
            <option value="화폐" <?php echo $me['me_type'] === '화폐' ? 'selected' : ''; ?>>화폐변동</option>
            <option value="이동" <?php echo $me['me_type'] === '이동' ? 'selected' : ''; ?>>지역이동</option>
            <option value="몬스터" <?php echo $me['me_type'] === '몬스터' ? 'selected' : ''; ?>>몬스터공격</option>
        </select>
        <input type="text" name="me_title[<?php echo $i; ?>]" value="<?php echo get_text($me['me_title']); ?>" class="frm_input" style="width:100%;margin-top:4px;">
    </td>
    <td style="text-align:center;">
        <?php if (!empty($me['me_get_item']) && function_exists('get_item_img')) { ?>
            <img src="<?php echo get_item_img($me['me_get_item']); ?>" style="max-width:50px;" alt="">
        <?php } else { echo '-'; } ?>
    </td>
    <td><input type="text" name="me_get_money[<?php echo $i; ?>]" value="<?php echo get_text($me['me_get_money']); ?>" class="frm_input" style="width:70px;"></td>
    <td>
        <select name="me_move_map[<?php echo $i; ?>]" class="frm_input">
            <option value="0">-</option>
            <?php foreach ($maps as $m) { ?>
            <option value="<?php echo (int)$m['id']; ?>" <?php echo get_selected($m['id'], $me['me_move_map']); ?>><?php echo htmlspecialchars($m['name'], ENT_QUOTES); ?></option>
            <?php } ?>
        </select>
    </td>
    <td><input type="text" name="me_mon_hp[<?php echo $i; ?>]" value="<?php echo get_text($me['me_mon_hp']); ?>" class="frm_input" style="width:70px;"></td>
    <td><input type="text" name="me_mon_attack[<?php echo $i; ?>]" value="<?php echo get_text($me['me_mon_attack']); ?>" class="frm_input" style="width:70px;"></td>
    <td><input type="text" name="me_replay_cnt[<?php echo $i; ?>]" value="<?php echo get_text($me['me_replay_cnt']); ?>" class="frm_input" style="width:70px;"></td>
    <td><input type="text" name="me_now_cnt[<?php echo $i; ?>]" value="<?php echo get_text($me['me_now_cnt']); ?>" class="frm_input" style="width:70px;"></td>
    <td><input type="checkbox" name="me_use[<?php echo $i; ?>]" value="1" <?php echo ((int)$me['me_use'] === 1) ? 'checked' : ''; ?>></td>
    <td><a href="?m=bbs_action_map&amp;p=event_form&amp;w=u&amp;me_id=<?php echo (int)$me['me_id']; ?>">수정</a></td>
</tr>
<?php
    $i++;
}
if ($i === 0) {
    echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
}
?>
</tbody>
</table>
</div>

<div class="btn_list01 btn_list">
    <input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value">
    <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
</div>
</form>

<script>
function feventlist_submit(f) {
    if (!is_checked("chk[]")) {
        alert(document.pressed + " 하실 항목을 하나 이상 선택하세요.");
        return false;
    }
    if (document.pressed === "선택삭제") {
        if (!confirm("선택한 자료를 정말 삭제하시겠습니까?")) return false;
    }
    return true;
}
</script>
