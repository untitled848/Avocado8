<?php
$sub_menu = "301100";
include_once('./_common.php');

// 관리자만 접근 가능
if (!$is_admin) {
    alert('관리자만 접근 가능합니다.');
}

include_once(__DIR__.'/timeline_require.inc.php');

$tl_table = ses($_REQUEST, 'tl_table', '', 'string');
if (!$tl_table) {
    alert('타임라인이 지정되지 않았습니다.', './timeline_list.php');
}

$timeline = get_timeline($tl_table);
if (!$timeline) {
    alert('존재하지 않는 타임라인입니다.', './timeline_list.php');
}

$g5['title'] = h($timeline['tl_subject']).' - 사용자 관리';
include_once('./admin.head.php');

// 검색 조건
$sfl = ses($_REQUEST, 'sfl', 'ch_name', 'string');
$stx = ses($_REQUEST, 'stx', '', 'string');
$page = ses($_REQUEST, 'page', 1, 'int');
$rows = 30;
$offset = ($page - 1) * $rows;

// 기존 등록된 사용자 목록
$timeline_users = get_timeline_users($tl_table);
$user_ch_ids = array();
foreach ($timeline_users as $u) {
    $user_ch_ids[] = (int)$u['ch_id'];
}

// 캐릭터 검색
$where = " WHERE 1 ";
if ($stx) {
    if ($sfl == 'ch_name') {
        $where .= " AND c.ch_name LIKE '%{$stx}%' ";
    } elseif ($sfl == 'mb_nick') {
        $where .= " AND m.mb_nick LIKE '%{$stx}%' ";
    } elseif ($sfl == 'ch_id') {
        $where .= " AND c.ch_id = '{$stx}' ";
    }
}

// 승인된 캐릭터만
$where .= " AND c.ch_state = '승인' ";

// 전체 캐릭터 수
$total_sql = "SELECT COUNT(*) AS cnt 
              FROM {$g5['character_table']} c 
              LEFT JOIN {$g5['member_table']} m ON m.mb_id = c.mb_id 
              {$where}";
$total_row = sql_fetch($total_sql);
$total_count = $total_row['cnt'];
$total_page = ceil($total_count / $rows);

// 캐릭터 목록
$sql = "SELECT c.*, m.mb_nick 
        FROM {$g5['character_table']} c 
        LEFT JOIN {$g5['member_table']} m ON m.mb_id = c.mb_id 
        {$where} 
        ORDER BY c.ch_name ASC 
        LIMIT {$offset}, {$rows}";
$result = sql_query($sql);

$qstr = '&amp;tl_table='.urlencode($tl_table).'&amp;sfl='.$sfl.'&amp;stx='.urlencode($stx);
$read_level = ses($timeline, 'tl_read_level', 1, 'int');
$write_level = ses($timeline, 'tl_write_level', 1, 'int');
?>

<style>
.tl_user_info { margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px; }
.tl_user_info h3 { margin: 0 0 10px 0; font-size: 16px; }
.tl_user_info .info_row { margin: 5px 0; }
.tl_user_info .label { display: inline-block; width: 100px; font-weight: bold; }
.tl_user_stats { display: flex; gap: 20px; margin-top: 10px; }
.tl_user_stats .stat { padding: 10px 20px; background: #fff; border-radius: 5px; }
.tl_user_stats .stat strong { font-size: 20px; color: #3b82f6; }
.tl_user_list { margin-top: 15px; padding: 10px; background: #fff; border-radius: 5px; max-height: 200px; overflow-y: auto; }
.tl_user_list .user_item { display: inline-block; padding: 3px 8px; margin: 2px; background: #e5e7eb; border-radius: 3px; font-size: 12px; }
.ch_avatar { width: 30px; height: 30px; border-radius: 50%; vertical-align: middle; margin-right: 5px; }
.btn_check_all { margin: 10px 0; }
.registered { background: #dcfce7 !important; }
.level_select { padding: 5px 10px; font-size: 14px; }
</style>

<div class="tl_user_info">
    <h3><?php echo h($timeline['tl_subject']); ?> (<?php echo h($tl_table); ?>)</h3>
    <div class="info_row">
        <span class="label">등록 사용자:</span>
        <strong><?php echo count($timeline_users); ?></strong>명
        <?php if (count($timeline_users) == 0) { ?>
        <span style="color:#888;">(제한 없음 - 모든 회원 쓰기 가능)</span>
        <?php } ?>
    </div>
    <div class="info_row">
        <span class="label">열람 권한:</span>
        <form method="post" action="./timeline_user_update.php" style="display:inline;">
            <input type="hidden" name="tl_table" value="<?php echo h($tl_table); ?>">
            <input type="hidden" name="action" value="read_level">
            <input type="hidden" name="token" value="<?php echo $token; ?>">
            <select name="tl_read_level" class="level_select" onchange="this.form.submit()">
                <option value="1"<?php echo $read_level == 1 ? ' selected' : ''; ?>>전체</option>
                <option value="2"<?php echo $read_level == 2 ? ' selected' : ''; ?>>회원만</option>
                <option value="3"<?php echo $read_level == 3 ? ' selected' : ''; ?>>사용자만</option>
                <option value="10"<?php echo $read_level == 10 ? ' selected' : ''; ?>>운영자만</option>
            </select>
        </form>
    </div>
    <div class="info_row">
        <span class="label">쓰기 권한:</span>
        <form method="post" action="./timeline_user_update.php" style="display:inline;">
            <input type="hidden" name="tl_table" value="<?php echo h($tl_table); ?>">
            <input type="hidden" name="action" value="write_level">
            <input type="hidden" name="token" value="<?php echo $token; ?>">
            <select name="tl_write_level" class="level_select" onchange="this.form.submit()">
                <option value="1"<?php echo $write_level == 1 ? ' selected' : ''; ?>>모든 사용자</option>
                <option value="2"<?php echo $write_level == 2 ? ' selected' : ''; ?>>운영자만</option>
            </select>
        </form>
        <span style="color:#888; font-size:12px;">(사용자가 등록된 경우에만 적용)</span>
    </div>
    
    <?php if (count($timeline_users) > 0) { ?>
    <div class="tl_user_list">
        <strong>등록된 사용자:</strong>
        <?php foreach ($timeline_users as $u) { ?>
        <span class="user_item">
            <?php echo h(ses($u, 'ch_name', 'Unknown')); ?>
            <a href="./timeline_user_update.php?tl_table=<?php echo h($tl_table); ?>&amp;action=remove&amp;ch_id=<?php echo $u['ch_id']; ?>&amp;token=<?php echo $token; ?>" onclick="return confirm('이 사용자를 제거하시겠습니까?');" title="제거">✕</a>
        </span>
        <?php } ?>
    </div>
    <?php } ?>
</div>

<div class="local_ov01 local_ov">
    <span class="btn_ov01"><span class="ov_txt">전체</span> <?php echo number_format($total_count); ?></span>
</div>

<form name="fsearchform" method="get">
<input type="hidden" name="tl_table" value="<?php echo h($tl_table); ?>">
<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
    <option value="ch_name"<?php echo ($sfl == 'ch_name') ? ' selected' : ''; ?>>캐릭터명</option>
    <option value="mb_nick"<?php echo ($sfl == 'mb_nick') ? ' selected' : ''; ?>>회원닉네임</option>
    <option value="ch_id"<?php echo ($sfl == 'ch_id') ? ' selected' : ''; ?>>캐릭터ID</option>
</select>
<label for="stx" class="sound_only">검색어</label>
<input type="text" name="stx" value="<?php echo h($stx); ?>" id="stx" class="frm_input">
<input type="submit" value="검색" class="btn_submit">
</form>

<form name="fcharlist" id="fcharlist" action="./timeline_user_update.php" method="post" onsubmit="return fcharlist_submit(this);">
<input type="hidden" name="tl_table" value="<?php echo h($tl_table); ?>">
<input type="hidden" name="action" value="bulk">
<input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
<input type="hidden" name="stx" value="<?php echo h($stx); ?>">
<input type="hidden" name="page" value="<?php echo $page; ?>">
<input type="hidden" name="token" value="<?php echo $token; ?>">

<div class="btn_check_all">
    <button type="button" onclick="checkAll(true)" class="btn">전체선택</button>
    <button type="button" onclick="checkAll(false)" class="btn">전체해제</button>
    <button type="button" onclick="checkRegistered()" class="btn">등록된 사용자만</button>
    <button type="button" onclick="checkUnregistered()" class="btn">미등록자만</button>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption>캐릭터 목록</caption>
    <thead>
    <tr>
        <th scope="col">
            <label for="chkall" class="sound_only">전체선택</label>
            <input type="checkbox" name="chkall" id="chkall" onclick="check_all(this.form)">
        </th>
        <th scope="col">ID</th>
        <th scope="col">캐릭터</th>
        <th scope="col">진영/클래스</th>
        <th scope="col">회원</th>
        <th scope="col">등록여부</th>
    </tr>
    </thead>
    <tbody>
    <?php
    for ($i = 0; $row = sql_fetch_array($result); $i++) {
        $is_registered = in_array((int)$row['ch_id'], $user_ch_ids);
        $bg = $is_registered ? 'registered' : ('bg'.($i % 2));
        $ch_image = get_timeline_thumb($row['ch_id'], 1);
    ?>
    <tr class="<?php echo $bg; ?>">
        <td>
            <input type="checkbox" name="chk[]" value="<?php echo $row['ch_id']; ?>" id="chk_<?php echo $i; ?>" data-registered="<?php echo $is_registered ? '1' : '0'; ?>">
        </td>
        <td><?php echo $row['ch_id']; ?></td>
        <td>
            <img src="<?php echo $ch_image; ?>" alt="" class="ch_avatar">
            <?php echo h($row['ch_name']); ?>
        </td>
        <td>
            <?php echo get_side_name($row['ch_side']); ?> / <?php echo get_class_name($row['ch_class']); ?>
        </td>
        <td><?php echo h($row['mb_nick']); ?></td>
        <td>
            <?php if ($is_registered) { ?>
            <span style="color:green;">● 등록됨</span>
            <?php } else { ?>
            <span style="color:#999;">○ 미등록</span>
            <?php } ?>
        </td>
    </tr>
    <?php
    }
    if ($i == 0) {
        echo '<tr><td colspan="6" class="empty_table">캐릭터가 없습니다.</td></tr>';
    }
    ?>
    </tbody>
    </table>
</div>

<div class="btn_list01 btn_list">
    <input type="submit" name="act_button" value="선택 추가" onclick="document.pressed=this.value">
    <input type="submit" name="act_button" value="선택 제거" onclick="document.pressed=this.value">
</div>

</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $_SERVER['SCRIPT_NAME'].'?tl_table='.urlencode($tl_table).'&amp;sfl='.$sfl.'&amp;stx='.urlencode($stx).'&amp;page='); ?>

<div class="btn_confirm01 btn_confirm">
    <a href="./timeline_list.php">타임라인 목록</a>
    <a href="./timeline_form.php?w=u&amp;tl_table=<?php echo h($tl_table); ?>">타임라인 설정</a>
</div>

<script>
function fcharlist_submit(f) {
    if (!is_checked("chk[]")) {
        alert(document.pressed + " 하실 캐릭터를 선택하세요.");
        return false;
    }
    return true;
}

function checkAll(checked) {
    var chks = document.querySelectorAll('input[name="chk[]"]');
    for (var i = 0; i < chks.length; i++) {
        chks[i].checked = checked;
    }
}

function checkRegistered() {
    var chks = document.querySelectorAll('input[name="chk[]"]');
    for (var i = 0; i < chks.length; i++) {
        chks[i].checked = (chks[i].getAttribute('data-registered') === '1');
    }
}

function checkUnregistered() {
    var chks = document.querySelectorAll('input[name="chk[]"]');
    for (var i = 0; i < chks.length; i++) {
        chks[i].checked = (chks[i].getAttribute('data-registered') === '0');
    }
}
</script>

<?php
include_once('./admin.tail.php');
?>
