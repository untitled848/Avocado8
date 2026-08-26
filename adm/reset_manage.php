<?php
$sub_menu = "400800";
include_once('./_common.php');

$g5['title'] = '리셋 카운터 관리';

$st = $g5['reset_setting_table'];
$mn = $g5['reset_manager_table'];

/* ---------------------------- POST: 값 수정 --------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_admin_token();
    $action = $_POST['act'] ?? '';

    if ($action === 'save') {
        $rs_id = (int)$_POST['rs_id'];
        $vals  = isset($_POST['val'])    && is_array($_POST['val'])    ? $_POST['val']    : array();
        $dels  = isset($_POST['del'])    && is_array($_POST['del'])    ? $_POST['del']    : array();
        $set = sql_fetch("select * from `{$st}` where rs_id={$rs_id} limit 1", false);
        if (!empty($set)) {
            // 삭제 먼저
            foreach ($dels as $rm_id => $_v) {
                $rm_id = (int)$rm_id;
                if ($rm_id > 0) sql_query("delete from `{$mn}` where rm_id={$rm_id} and rs_id={$rs_id}");
            }
            $max = (int)$set['rs_max']; $min = (int)$set['rs_min'];
            foreach ($vals as $rm_id => $v) {
                $rm_id = (int)$rm_id;
                if (isset($dels[$rm_id])) continue; // 삭제 대상은 스킵
                $v = (int)$v;
                if ($v > $max) $v = $max;
                if ($v < $min) $v = $min;
                sql_query("update `{$mn}` set rm_value={$v} where rm_id={$rm_id} and rs_id={$rs_id}");
            }
            // 신규 추가 row 처리
            $new_subkey = trim($_POST['new_subkey'] ?? '');
            $new_value  = isset($_POST['new_value']) && $_POST['new_value'] !== '' ? (int)$_POST['new_value'] : null;
            if ($new_subkey !== '' && $new_value !== null) {
                if ($new_value > $max) $new_value = $max;
                if ($new_value < $min) $new_value = $min;
                $sub_e = sql_real_escape_string($new_subkey);
                sql_query("insert into `{$mn}` (rs_id, rm_subkey, rm_value)
                           values ({$rs_id}, '{$sub_e}', {$new_value})
                           on duplicate key update rm_value={$new_value}");
            }
        }
        goto_url('./reset_manage.php?rs_id='.$rs_id.'&saved=1');
    }
    if ($action === 'force_reset') {
        $rs_id = (int)$_POST['rs_id'];
        if (function_exists('reset_force')) reset_force($rs_id);
        goto_url('./reset_manage.php?rs_id='.$rs_id.'&forced=1');
    }
    if ($action === 'seed_existing') {
        $rs_id = (int)$_POST['rs_id'];
        $added = 0;
        if (function_exists('reset_seed_existing')) {
            $added = (int)reset_seed_existing($rs_id);
        }
        goto_url('./reset_manage.php?rs_id='.$rs_id.'&seeded='.$added);
    }
    if ($action === 'orphan_clean') {
        // 잌존 데이터 일괄 삭제:
        //  - rs_type=character: character_table 에 없거나 ch_state='삭제'(소프트 삭제) 인 경우
        //  - rs_type=member   : member_table 에 없거나 소프트 삭제 상태(member_delete 적용: mb_level<=1 AND mb_memo REGEXP '^[0-9]{8}.*삭제함')
        $deleted = 0;
        if (!empty($g5['character_table'])) {
            sql_query("delete mn from `{$mn}` mn
                        inner join `{$st}` st on st.rs_id = mn.rs_id
                        where st.rs_type='character'
                          and not exists (select 1 from `{$g5['character_table']}` c where c.ch_id = mn.rm_subkey and c.ch_state <> '삭제')", false);
            if (function_exists('sql_affected_rows')) $deleted += (int)sql_affected_rows();
        }
        if (!empty($g5['member_table'])) {
            sql_query("delete mn from `{$mn}` mn
                        inner join `{$st}` st on st.rs_id = mn.rs_id
                        where st.rs_type='member'
                          and not exists (select 1 from `{$g5['member_table']}` m
                                          where m.mb_id = mn.rm_subkey
                                            and not (m.mb_level <= 1 and m.mb_memo regexp '^[0-9]{8}.*삭제함'))", false);
            if (function_exists('sql_affected_rows')) $deleted += (int)sql_affected_rows();
        }
        goto_url('./reset_manage.php?rs_id='.(int)$_POST['rs_id'].'&orphan_cleaned='.$deleted);
    }
}

/* ---------------------------- GET: 표시 ------------------------------------- */
$rs_id_sel = isset($_GET['rs_id']) ? (int)$_GET['rs_id'] : 0;
$kw = trim($_GET['kw'] ?? '');
$kw_e      = sql_real_escape_string($kw);

$settings = array();
$rs = sql_query("select * from `{$st}` order by rs_use desc, rs_name asc", false);
if ($rs) while ($r = sql_fetch_array($rs)) $settings[] = $r;

$cur = null;
foreach ($settings as $s) if ((int)$s['rs_id'] === $rs_id_sel) { $cur = $s; break; }

include_once('./admin.head.php');
$reset_hub_tab = 'values';
include_once('./_reset_hub_tabs.inc.php');

function _rm_resolve_owner_name($type, $subkey) {
    global $g5;
    $subkey = trim((string)$subkey);
    if ($subkey === '') return '(전역)';
    if ($type === 'character') {
        if (function_exists('get_character_name')) {
            $n = get_character_name($subkey);
            return ($n !== '') ? $n : '(없음)';
        }
        return $subkey;
    }
    if ($type === 'member') {
        $sub_e = sql_real_escape_string($subkey);
        $row = sql_fetch("select mb_nick from {$g5['member_table']} where mb_id='{$sub_e}' limit 1", false);
        return !empty($row) ? $row['mb_nick'] : '(없음)';
    }
    return '';
}
?>

<form method="get" action="" class="local_sch01 local_sch">
    <label for="rs_id" class="sound_only">설정 선택</label>
    <select name="rs_id" id="rs_id" onchange="this.form.submit()">
        <option value="0">— 설정을 선택하세요 —</option>
<?php foreach ($settings as $s): ?>
        <option value="<?php echo (int)$s['rs_id']; ?>" <?php echo ($s['rs_id']==$rs_id_sel?'selected':''); ?>>
            [<?php echo htmlspecialchars($s['rs_type'], ENT_QUOTES); ?>] <?php echo htmlspecialchars($s['rs_name'].' — '.$s['rs_label'], ENT_QUOTES); ?>
        </option>
<?php endforeach; ?>
    </select>
    <?php if ($cur): ?>
        <label for="kw" class="sound_only">검색</label>
        <input type="text" name="kw" id="kw" value="<?php echo htmlspecialchars($kw, ENT_QUOTES); ?>" placeholder="<?php
            echo $cur['rs_type']==='character' ? 'ch_id 또는 캐릭터명'
                : ($cur['rs_type']==='member' ? 'mb_id 또는 닉네임' : '서브키');
        ?>" />
        <button type="submit" class="btn_submit">검색</button>
    <?php endif; ?>
</form>

<?php if (!empty($_GET['saved'])): ?>
<p class="msg_sok">저장되었습니다.</p>
<?php endif; ?>
<?php if (!empty($_GET['forced'])): ?>
<p class="msg_sok">즉시 리셋이 실행되었습니다.</p>
<?php endif; ?>
<?php if (isset($_GET['orphan_cleaned'])): ?>
<p class="msg_sok">사용하지 않는 항목 <?php echo (int)$_GET['orphan_cleaned']; ?>건을 정리했습니다.</p>
<?php endif; ?>
<?php if (isset($_GET['seeded'])): ?>
<p class="msg_sok">기존 캐릭터/회원에 대해 <?php echo (int)$_GET['seeded']; ?>건의 reset_manager 행을 추가했습니다.</p>
<?php endif; ?>

<?php if ($cur): ?>
<div class="local_desc02 local_desc">
    <p>
        <strong>[<?php echo htmlspecialchars($cur['rs_name'], ENT_QUOTES); ?>]</strong>
        타입: <?php echo htmlspecialchars($cur['rs_type'], ENT_QUOTES); ?>
        — 기본값 <?php echo (int)$cur['rs_default']; ?> / 범위 [<?php echo (int)$cur['rs_min']; ?>, <?php echo (int)$cur['rs_max']; ?>]
        <form method="post" action="./reset_manage.php" style="display:inline; margin-left:10px;"
              onsubmit="return confirm('아직 reset_manager 행이 없는 모든 기존 캐릭터/회원에게 기본값으로 행을 추가합니다. 진행할까요?');">
            <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>" />
            <input type="hidden" name="act" value="seed_existing" />
            <input type="hidden" name="rs_id" value="<?php echo (int)$cur['rs_id']; ?>" />
            <button type="submit" class="btn btn_02">기존 주체에 일괄 행 추가</button>
        </form>
    </p>
</div>
<?php endif; ?>

<?php
/* ---------------------- 고아 행 요약 (전체 설정 대상) ----------------------- */
$orphan_total = 0;
$orphan_per_set = array(); // rs_id => count
if (!empty($g5['character_table'])) {
    $rs_o = sql_query("select rm.rs_id, count(*) c from `{$mn}` rm
                        inner join `{$st}` rs on rs.rs_id = rm.rs_id
                        where rs.rs_type='character'
                          and not exists (select 1 from `{$g5['character_table']}` c where c.ch_id = rm.rm_subkey and c.ch_state <> '삭제')
                        group by rm.rs_id", false);
    if ($rs_o) while ($r = sql_fetch_array($rs_o)) {
        $orphan_per_set[(int)$r['rs_id']] = (isset($orphan_per_set[(int)$r['rs_id']])?$orphan_per_set[(int)$r['rs_id']]:0) + (int)$r['c'];
        $orphan_total += (int)$r['c'];
    }
}
if (!empty($g5['member_table'])) {
    $rs_o = sql_query("select rm.rs_id, count(*) c from `{$mn}` rm
                        inner join `{$st}` rs on rs.rs_id = rm.rs_id
                        where rs.rs_type='member'
                          and not exists (select 1 from `{$g5['member_table']}` m
                                          where m.mb_id = rm.rm_subkey
                                            and not (m.mb_level <= 1 and m.mb_memo regexp '^[0-9]{8}.*삭제함'))
                        group by rm.rs_id", false);
    if ($rs_o) while ($r = sql_fetch_array($rs_o)) {
        $orphan_per_set[(int)$r['rs_id']] = (isset($orphan_per_set[(int)$r['rs_id']])?$orphan_per_set[(int)$r['rs_id']]:0) + (int)$r['c'];
        $orphan_total += (int)$r['c'];
    }
}
?>
<?php if ($orphan_total > 0): ?>
<div class="local_desc02 local_desc" style="border-color:#c33;">
    <p style="color:#c33;">
        <strong>※ 이미 삭제된 캐릭터/회원의 잔존 데이터가 <?php echo (int)$orphan_total; ?>건 남아 있습니다.</strong>
        <form method="post" action="./reset_manage.php" style="display:inline; margin-left:10px;"
              onsubmit="return confirm('삭제된 캐릭터/회원의 reset_manager 행 <?php echo (int)$orphan_total; ?>건을 모두 정리합니다. 진행할까요?');">
            <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>" />
            <input type="hidden" name="act" value="orphan_clean" />
            <input type="hidden" name="rs_id" value="<?php echo (int)$rs_id_sel; ?>" />
            <button type="submit" class="btn btn_02">잔존 데이터 일괄 정리</button>
        </form>
    </p>
</div>
<?php endif; ?>

<?php if ($cur): ?>

<?php
// 검색 조건에 맞는 rm 행 조회.
// character/member 검색은 이름까지 매칭하기 위해 PHP 단에서 필터.
$rows = array();
$rs2 = sql_query("select * from `{$mn}` where rs_id=".(int)$cur['rs_id']." order by rm_id asc", false);
if ($rs2) while ($r = sql_fetch_array($rs2)) $rows[] = $r;

$filtered = array();
foreach ($rows as $r) {
    $name = _rm_resolve_owner_name($cur['rs_type'], $r['rm_subkey']);
    if ($kw !== '') {
        $hay = $r['rm_subkey'].' '.$name;
        if (mb_stripos($hay, $kw) === false) continue;
    }
    $r['_owner_name'] = $name;
    // 고아 여부 (character/member 만 의미 있음)
    $r['_orphan'] = false;
    if ($cur['rs_type'] === 'character' && !empty($g5['character_table']) && $r['rm_subkey'] !== '') {
        $sub_e = sql_real_escape_string($r['rm_subkey']);
        // 삭제 상태(ch_state='삭제')도 잌존 데이터로 취급
        $chk = sql_fetch("select 1 as e from `{$g5['character_table']}` where ch_id = '{$sub_e}' and ch_state <> '삭제' limit 1", false);
        if (empty($chk)) $r['_orphan'] = true;
    } else if ($cur['rs_type'] === 'member' && !empty($g5['member_table']) && $r['rm_subkey'] !== '') {
        $sub_e = sql_real_escape_string($r['rm_subkey']);
        // 소프트 삭제된 회원(member_delete: mb_level<=1, mb_memo 접두 'YYYYMMDD 삭제함')도 잌존 데이터로 취급
        $chk = sql_fetch("select 1 as e from `{$g5['member_table']}` where mb_id = '{$sub_e}' and not (mb_level <= 1 and mb_memo regexp '^[0-9]{8}.*삭제함') limit 1", false);
        if (empty($chk)) $r['_orphan'] = true;
    }
    $filtered[] = $r;
}
$cur_orphan_cnt = isset($orphan_per_set[(int)$cur['rs_id']]) ? (int)$orphan_per_set[(int)$cur['rs_id']] : 0;
?>

<form method="post" action="./reset_manage.php" onsubmit="return confirm('변경된 값과 삭제 표시를 적용합니다. 진행할까요?');" autocomplete="off">
<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>" />
<input type="hidden" name="act"  value="save" />
<input type="hidden" name="rs_id" value="<?php echo (int)$cur['rs_id']; ?>" />

<div class="tbl_head01 tbl_wrap">
<table>
    <caption><?php echo htmlspecialchars($cur['rs_name'], ENT_QUOTES); ?> — 값 관리 (<?php echo count($filtered); ?>건)</caption>
    <thead>
    <tr>
        <th scope="col">rm_id</th>
        <th scope="col"><?php
            echo $cur['rs_type']==='character' ? 'ch_id'
                : ($cur['rs_type']==='member' ? 'mb_id' : 'subkey');
        ?></th>
        <th scope="col">표시명</th>
        <th scope="col">현재 값 (rm_value)</th>
        <th scope="col">관리</th>
    </tr>
    </thead>
    <tbody>
<?php if (!$filtered): ?>
    <tr><td colspan="5" class="empty_table">표시할 데이터가 없습니다.</td></tr>
<?php else: foreach ($filtered as $r): ?>
    <tr<?php echo !empty($r['_orphan']) ? ' style="background:#fff4f4;"' : ''; ?>>
        <td><?php echo (int)$r['rm_id']; ?></td>
        <td><?php echo htmlspecialchars($r['rm_subkey'], ENT_QUOTES); ?></td>
        <td>
            <?php echo htmlspecialchars($r['_owner_name'], ENT_QUOTES); ?>
            <?php if (!empty($r['_orphan'])): ?>
                <span style="display:inline-block; margin-left:6px; padding:1px 6px; background:#c33; color:#fff; border-radius:3px; font-size:11px;">삭제된 대상</span>
            <?php endif; ?>
        </td>
        <td>
            <input type="number" name="val[<?php echo (int)$r['rm_id']; ?>]"
                   value="<?php echo (int)$r['rm_value']; ?>"
                   min="<?php echo (int)$cur['rs_min']; ?>" max="<?php echo (int)$cur['rs_max']; ?>"
                   class="frm_input" style="width:100px;" />
            <small style="color:#888;">(<?php echo (int)$cur['rs_min']; ?> ~ <?php echo (int)$cur['rs_max']; ?>)</small>
        </td>
        <td>
            <label><input type="checkbox" name="del[<?php echo (int)$r['rm_id']; ?>]" value="1" <?php echo !empty($r['_orphan'])?'checked':''; ?>/> 삭제</label>
        </td>
    </tr>
<?php endforeach; endif; ?>
    </tbody>
    <tfoot>
    <tr>
        <td>신규</td>
        <td><input type="text" name="new_subkey" class="frm_input" placeholder="<?php
            echo $cur['rs_type']==='character' ? 'ch_id'
                : ($cur['rs_type']==='member' ? 'mb_id' : 'subkey');
        ?>" /></td>
        <td>—</td>
        <td><input type="number" name="new_value" class="frm_input" style="width:100px;" placeholder="<?php echo (int)$cur['rs_default']; ?>" /></td>
        <td>저장 시 함께 추가</td>
    </tr>
    </tfoot>
</table>
</div>

<div class="btn_fixed_top">
    <button type="submit" class="btn_submit">변경 저장</button>
</div>
</form>

<form method="post" action="./reset_manage.php" style="margin-top:14px;"
      onsubmit="return confirm('지금 즉시 이 항목의 모든 값을 리셋하시겠습니까?\n(주기와 무관하게 강제 실행)');">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>" />
    <input type="hidden" name="act"  value="force_reset" />
    <input type="hidden" name="rs_id" value="<?php echo (int)$cur['rs_id']; ?>" />
    <button type="submit" class="btn btn_02">⚡ 즉시 리셋 실행</button>
    <small style="color:#888; margin-left:8px;">
        rs_plus=<?php echo (int)$cur['rs_plus']; ?>
        / rs_default=<?php echo (int)$cur['rs_default']; ?>
        / 다음 자동 리셋: <?php echo $cur['rs_next'] ? date('Y-m-d H:i', (int)$cur['rs_next']) : '-'; ?>
    </small>
</form>

<?php endif; ?>

<?php
include_once('./admin.tail.php');
?>
