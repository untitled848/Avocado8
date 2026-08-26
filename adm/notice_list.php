<?php
$sub_menu = "310200";
include_once('./_common.php');

// ─── 공지 그리드 설정 저장 ──────────────────
if (isset($_POST['notice_grid_update']) && check_admin_token()) {
    $nm = in_array(($_POST['cf_notice_list_mode'] ?? ''), array('list','grid')) ? $_POST['cf_notice_list_mode'] : 'list';
    $gm = preg_replace('/[^a-zA-Z\-]/', '', (string)($_POST['cf_notice_grid_mode'] ?? 'auto-fill'));
    if (!in_array($gm, array('auto-fill','auto-fit','fixed'))) $gm = 'auto-fill';
    $gc = max(1, min(12, (int)($_POST['cf_notice_grid_count'] ?? 3)));
    $mw = preg_replace('/[^\w\.%\-]/', '', (string)($_POST['cf_notice_card_min_w'] ?? '240px'));
    if ($mw === '') $mw = '240px';
    $aw = max(1, min(100, (int)($_POST['cf_notice_ar_w'] ?? 16)));
    $ah = max(1, min(100, (int)($_POST['cf_notice_ar_h'] ?? 9)));
    sql_query("UPDATE `{$g5['config_table']}` SET
        cf_notice_list_mode  = '".sql_escape_string($nm)."',
        cf_notice_grid_mode  = '".sql_escape_string($gm)."',
        cf_notice_grid_count = '{$gc}',
        cf_notice_card_min_w = '".sql_escape_string($mw)."',
        cf_notice_ar_w       = '{$aw}',
        cf_notice_ar_h       = '{$ah}'
    ", false);
    goto_url('./notice_list.php?grid_saved=1');
}

// ─── 공지사항 카테고리 관리 ──────────────────
if (isset($_POST['notice_cat_update']) && check_admin_token()) {
    $parts = array();
    foreach (($_POST['cf_notice_category_spr'] ?? array()) as $v) {
        $v = trim($v);
        if ($v !== '') $parts[] = sql_escape_string($v);
    }
    $cf = implode('||', $parts);
    sql_query("UPDATE `{$g5['config_table']}` SET cf_notice_category = '{$cf}'");
    goto_url('./notice_list.php');
}

$g5['title'] = '공지사항 관리';
include_once(G5_ADMIN_PATH.'/admin.head.php');

// 그리드 설정 config 읽기 (컬럼 미존재 시 기본값 폴백)
$no_list_mode  = isset($config['cf_notice_list_mode'])  ? $config['cf_notice_list_mode']  : 'list';
$no_grid_mode  = isset($config['cf_notice_grid_mode'])  ? $config['cf_notice_grid_mode']  : 'auto-fill';
$no_grid_count = isset($config['cf_notice_grid_count']) ? max(1, (int)$config['cf_notice_grid_count']) : 3;
$no_card_min_w = (isset($config['cf_notice_card_min_w']) && $config['cf_notice_card_min_w'] !== '') ? $config['cf_notice_card_min_w'] : '240px';
$no_ar_w       = isset($config['cf_notice_ar_w'])       ? max(1, (int)$config['cf_notice_ar_w']) : 16;
$no_ar_h       = isset($config['cf_notice_ar_h'])       ? max(1, (int)$config['cf_notice_ar_h']) : 9;

// 시기 이름 맵
$ti_name_map = array();
if (!empty($g5['time_table'])) {
    $tr = sql_query("SELECT ti_order, ti_name FROM `{$g5['time_table']}` ORDER BY ti_order ASC", false);
    if ($tr) { while ($trw = sql_fetch_array($tr)) { $ti_name_map[(int)$trw['ti_order']] = $trw['ti_name']; } }
}

// 카테고리 필터
$f_cate = trim((string)($_GET['cate'] ?? ''));
$cate_where = '';
if ($f_cate !== '') {
    $cate_where = " WHERE nc_name = '".sql_escape_string($f_cate)."' ";
}

$rows  = isset($config['cf_page_rows']) ? (int)$config['cf_page_rows'] : 20;
if ($rows <= 0) $rows = 20;
$page  = max(1, (int)($_GET['page'] ?? 1));
$from  = ($page - 1) * $rows;

$row = sql_fetch("SELECT COUNT(*) AS c FROM `{$g5['notice_table']}` {$cate_where}", false);
$total_count = (int)($row['c'] ?? 0);
$total_page  = (int)ceil($total_count / $rows);

$result = sql_query("SELECT * FROM `{$g5['notice_table']}` {$cate_where} ORDER BY no_pinned DESC, no_order ASC, no_datetime DESC, no_id DESC LIMIT {$from}, {$rows}", false);
?>

<div class="local_ov01 local_ov">
    등록된 공지 <strong><?php echo number_format($total_count); ?></strong>개
    <?php if ($f_cate !== ''): ?>
        / 카테고리: <strong><?php echo htmlspecialchars($f_cate, ENT_QUOTES); ?></strong>
        <a href="./notice_list.php" class="btn btn_03" style="margin-left:6px;">전체</a>
    <?php endif; ?>
</div>

<details class="adm-panel cat-chips-panel" open>
<summary>공지사항 카테고리 관리</summary>
<div class="adm-panel-body">
<form method="post" action="">
<?php echo get_admin_token_field(); ?>
<input type="hidden" name="notice_cat_update" value="1">
<p class="adm-panel-help">공지사항 분류용 <strong>카테고리</strong>를 관리합니다. 칩을 드래그하여 순서 변경, ×로 삭제, 텍스트를 클릭해 수정할 수 있습니다.</p>
<?php $_nc_list = function_exists('notice_categories') ? notice_categories() : array(); ?>
<div class="cat-chips" data-name="cf_notice_category_spr[]">
<?php foreach ($_nc_list as $c): ?>
    <span class="cat-chip" draggable="true">
        <span class="cat-chip-handle" title="드래그">⋮⋮</span>
        <input type="text" class="cat-chip-edit" value="<?php echo htmlspecialchars($c, ENT_QUOTES); ?>">
        <button type="button" class="cat-chip-del" title="삭제">×</button>
        <input type="hidden" name="cf_notice_category_spr[]" value="<?php echo htmlspecialchars($c, ENT_QUOTES); ?>">
    </span>
<?php endforeach; ?>
</div>
<div class="cat-add-row">
    <input type="text" class="frm_input cat-add-input" placeholder="새 카테고리 이름 입력 후 Enter 또는 [추가]" maxlength="32">
    <button type="button" class="btn_03 cat-add-btn">추가</button>
    <span style="font-size:11px;color:#888;">변경 후 반드시 [카테고리 저장]을 눌러야 적용됩니다.</span>
</div>
<div class="adm-panel-actions">
    <button type="submit" class="btn_submit">카테고리 저장</button>
</div>
</form>
</div>
</details>

<details class="adm-panel notice-grid-panel" open>
<summary>공지 목록 그리드 설정</summary>
<div class="adm-panel-body">
<?php if (!empty($_GET['grid_saved'])): ?>
<div style="padding:8px 14px;background:#e8f5e9;border:1px solid #a5d6a7;border-radius:4px;color:#197d18;font-weight:600;margin-bottom:10px;">✓ 그리드 설정이 저장되었습니다.</div>
<?php endif; ?>
<p class="adm-panel-help">
    공지사항 <strong>목록 페이지</strong>의 표시 방식을 설정합니다.
</p>
<form method="post" action="">
<?php echo get_admin_token_field(); ?>
<input type="hidden" name="notice_grid_update" value="1">
<div class="tbl_frm01 tbl_wrap">
<table>
<colgroup><col style="width:170px"><col></colgroup>
<tbody>
<tr>
    <th scope="row">목록 표시 방식</th>
    <td>
        <label><input type="radio" name="cf_notice_list_mode" value="list" <?php echo $no_list_mode !== 'grid' ? 'checked' : ''; ?> onchange="noticeGridToggle()"> 일반 목록</label>
        <label style="margin-left:14px;"><input type="radio" name="cf_notice_list_mode" value="grid" <?php echo $no_list_mode === 'grid' ? 'checked' : ''; ?> onchange="noticeGridToggle()"> 그리드 카드</label>
    </td>
</tr>
<tbody id="notice-grid-opts" style="<?php echo $no_list_mode === 'grid' ? '' : 'display:none'; ?>">
<tr>
    <th scope="row">그리드 정렬 방식</th>
    <td>
        <?php
        $gm_opts = array(
            'auto-fill' => '자동 채움 (auto-fill)',
            'auto-fit'  => '자동 맞춤 (auto-fit)',
            'fixed'     => '컬럼 수 고정',
        );
        ?>
        <select name="cf_notice_grid_mode" id="notice_grid_mode" onchange="noticeGridToggle()">
            <?php foreach ($gm_opts as $gv => $gl): ?>
            <option value="<?php echo htmlspecialchars($gv, ENT_QUOTES); ?>" <?php echo $no_grid_mode === $gv ? 'selected' : ''; ?>><?php echo htmlspecialchars($gl, ENT_QUOTES); ?></option>
            <?php endforeach; ?>
        </select>
    </td>
</tr>
<tr id="notice-row-count" style="<?php echo $no_grid_mode === 'fixed' ? '' : 'display:none'; ?>">
    <th scope="row">고정 컬럼 수</th>
    <td>
        <input type="number" name="cf_notice_grid_count" value="<?php echo $no_grid_count; ?>" min="1" max="12" size="4" class="frm_input"> 열
    </td>
</tr>
<tr id="notice-row-minw" style="<?php echo $no_grid_mode !== 'fixed' ? '' : 'display:none'; ?>">
    <th scope="row">카드 최소 너비</th>
    <td>
        <input type="text" name="cf_notice_card_min_w" value="<?php echo htmlspecialchars($no_card_min_w, ENT_QUOTES); ?>" size="12" class="frm_input" placeholder="예: 240px, 30%, 18em">
        <span class="frm_info" style="display:inline;margin-left:8px;">px · % · em · rem 등 CSS 단위 모두 입력 가능</span>
    </td>
</tr>
<tr>
    <th scope="row">썸네일 비율</th>
    <td>
        가로 <input type="number" name="cf_notice_ar_w" value="<?php echo $no_ar_w; ?>" min="1" max="100" size="4" class="frm_input"> :
        세로 <input type="number" name="cf_notice_ar_h" value="<?php echo $no_ar_h; ?>" min="1" max="100" size="4" class="frm_input">
        <span class="frm_info" style="display:inline;margin-left:8px;">기본 16:9 / 1:1(정사각형) / 4:3 등</span>
    </td>
</tr>
</tbody>
</tbody>
</table>
</div>
<div class="adm-panel-actions">
    <button type="submit" class="btn_submit">저장</button>
</div>
</form>
</div>
</details>
<script>
function noticeGridToggle() {
    var mode = document.querySelector('[name=cf_notice_list_mode]:checked');
    if (!mode) return;
    var isGrid = mode.value === 'grid';
    document.getElementById('notice-grid-opts').style.display = isGrid ? '' : 'none';
    if (!isGrid) return;
    var gm = document.getElementById('notice_grid_mode').value;
    document.getElementById('notice-row-count').style.display = gm === 'fixed'  ? '' : 'none';
    document.getElementById('notice-row-minw').style.display  = gm !== 'fixed'  ? '' : 'none';
}
</script>

<form method="get" action="" style="margin:6px 0;">
    <label>카테고리 필터:
        <select name="cate" onchange="this.form.submit();">
            <option value="">전체</option>
            <?php foreach ($_nc_list as $c): ?>
            <option value="<?php echo htmlspecialchars($c, ENT_QUOTES); ?>" <?php echo ($f_cate === $c) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c, ENT_QUOTES); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
</form>

<div class="btn_add01 btn_add">
    <a href="./notice_form.php">공지 추가</a>
    <a href="./content_design_form.php" title="공지는 문서와 같은 디자인을 씁니다.">문서 디자인 설정</a>
</div>

<form method="post" action="./notice_action.php" onsubmit="return notice_list_check(this);">
<input type="hidden" name="token" value="">
<input type="hidden" name="act" value="">

<div class="tbl_head01 tbl_wrap">
<table>
<caption><?php echo $g5['title']; ?> 목록</caption>
<colgroup>
    <col style="width:40px">
    <col style="width:60px">
    <col style="width:56px">
    <col>
    <col style="width:160px">
    <col style="width:100px">
    <col style="width:120px">
    <col style="width:170px">
    <col style="width:60px">
    <col style="width:60px">
    <col style="width:60px">
    <col style="width:130px">
    <col style="width:110px">
</colgroup>
<thead>
<tr>
    <th scope="col"><input type="checkbox" id="chkall" onclick="var c=document.querySelectorAll('input[name=\'chk[]\']');for(var i=0;i<c.length;i++)c[i].checked=this.checked;"></th>
    <th scope="col">ID</th>
    <th scope="col">썸네일</th>
    <th scope="col">제목 / 부제</th>
    <th scope="col">카테고리</th>
    <th scope="col">작성자</th>
    <th scope="col">출력 시기</th>
    <th scope="col">위젯</th>
    <th scope="col">고정</th>
    <th scope="col">사용</th>
    <th scope="col">등록일시</th>
    <th scope="col">관리</th>
</tr>
</thead>
<tbody>
<?php if ($result && sql_num_rows($result) > 0): ?>
<?php for ($i = 0; $row = sql_fetch_array($result); $i++):
    $nid       = (int)$row['no_id'];
    $nt        = trim((string)$row['no_time']);
    // 정책: '' 또는 'all' = 전체 시기 (상점/메인슬라이드와 통일)
    if ($nt === '' || strtolower($nt) === 'all') {
        $time_disp = '<span style="color:#369;">전체 시기</span>';
    } else if (function_exists('shop_time_from_db')) {
        $arr = shop_time_from_db($nt);
        if (!empty($arr)) {
            $labels = array();
            foreach ($arr as $tio) {
                $nm = isset($ti_name_map[$tio]) ? $ti_name_map[$tio] : '';
                $labels[] = $tio . ($nm !== '' ? '. '.htmlspecialchars($nm, ENT_QUOTES, 'UTF-8') : '');
            }
            $time_disp = implode(', ', $labels);
        } else {
            $time_disp = '<span style="color:#369;">전체 시기</span>';
        }
    } else {
        $time_disp = '<span style="color:#369;">전체 시기</span>';
    }
?>
<tr>
    <td class="td_chk"><input type="checkbox" name="chk[]" value="<?php echo $nid; ?>"></td>
    <td><?php echo $nid; ?></td>
    <td class="td_thumb" style="text-align:center;">
        <?php
        $turl = !empty($row['no_thumb']) ? $row['no_thumb'] : '';
        if ($turl) {
            $turl = str_replace(defined('CDN_TOKEN') ? CDN_TOKEN : '[DATA_URL]', G5_DATA_URL, $turl);
            if (function_exists('cdn_resolve_url')) $turl = cdn_resolve_url($turl);
        }
        ?>
        <?php if ($turl): ?>
        <img src="<?php echo htmlspecialchars($turl, ENT_QUOTES, 'UTF-8'); ?>" alt="" style="max-width:50px; max-height:36px; border-radius:3px;">
        <?php else: ?>-<?php endif; ?>
    </td>
    <td class="td_subject">
        <a href="./notice_form.php?w=u&amp;no_id=<?php echo $nid; ?>"><?php echo htmlspecialchars($row['no_subject'], ENT_QUOTES, 'UTF-8'); ?></a>
        <?php if (!empty($row['no_subtext'])): ?>
        <br><span style="color:#888; font-size:11px;"><?php echo htmlspecialchars($row['no_subtext'], ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
    </td>
    <td><?php echo htmlspecialchars((string)($row['nc_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
    <td><?php echo htmlspecialchars($row['no_writer'] ?: $row['mb_id'], ENT_QUOTES, 'UTF-8'); ?></td>
    <td><?php echo $time_disp; ?></td>
    <td class="td_chk"><?php echo !empty($row['no_widget']) ? 'O' : '-'; ?></td>
    <td class="td_chk"><?php echo $row['no_pinned'] ? '★' : '-'; ?></td>
    <td class="td_chk"><?php echo $row['no_use'] ? 'O' : '<span style="color:#a00;">X</span>'; ?></td>
    <td class="td_datetime"><?php echo substr($row['no_datetime'], 2, 14); ?></td>
    <td class="td_mng">
        <a href="<?php echo G5_URL; ?>/notice/?no_id=<?php echo $nid; ?>" target="_blank" class="btn btn_03">보기</a>
        <a href="./notice_form.php?w=u&amp;no_id=<?php echo $nid; ?>" class="btn btn_02">수정</a>
    </td>
</tr>
<?php endfor; ?>
<?php else: ?>
<tr><td colspan="13" class="empty_table">등록된 공지가 없습니다.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<?php if ($total_count > 0): ?>
<div class="btn_list01 btn_list">
    <button type="button" class="btn_03 btn" onclick="document.querySelector('[name=act]').value='use_on';this.form.submit();">선택 사용</button>
    <button type="button" class="btn_03 btn" onclick="document.querySelector('[name=act]').value='use_off';this.form.submit();">선택 사용해제</button>
    <button type="button" class="btn_03 btn" onclick="document.querySelector('[name=act]').value='pin_on';this.form.submit();">선택 고정</button>
    <button type="button" class="btn_03 btn" onclick="document.querySelector('[name=act]').value='pin_off';this.form.submit();">선택 고정해제</button>
    <button type="submit" class="btn_02 btn" onclick="document.querySelector('[name=act]').value='delete';return confirm('선택한 공지를 삭제하시겠습니까?');">선택 삭제</button>
</div>
<?php endif; ?>

</form>

<?php
echo get_paging($config['cf_write_pages'] ?? 10, $page, $total_page, './notice_list.php?'.($f_cate !== '' ? 'cate='.urlencode($f_cate).'&' : ''));
?>

<script>
function notice_list_check(f) {
    var ck = document.querySelectorAll('input[name="chk[]"]:checked');
    if (!ck.length) { alert('항목을 선택하세요.'); return false; }
    return true;
}
</script>
<script src="<?php echo G5_ADMIN_URL; ?>/js/category-chips.js"></script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
?>
