<?php
$sub_menu = "310101";
include_once('./_common.php');

auth_check($auth[$sub_menu], "r");

$g5['title'] = '세계관 메뉴 구성';
include_once(G5_ADMIN_PATH.'/admin.head.php');

// 페이지 목록
$pages_result = sql_query("SELECT wo_id, wo_subject FROM {$g5['world_table']} WHERE wo_subject != '' ORDER BY wo_id");
$pages = array();
while ($p = sql_fetch_array($pages_result)) { $pages[] = $p; }

// 메뉴 트리
$main_items = array();
$sub_items  = array();
$r = sql_query("SELECT * FROM {$g5['world_menu_table']} WHERE wm_depth = 0 ORDER BY wm_order, wm_id");
while ($row = sql_fetch_array($r)) { $main_items[] = $row; }
foreach ($main_items as $m) {
    $mid = (int)$m['wm_id'];
    $sub_items[$mid] = array();
    $rs = sql_query("SELECT * FROM {$g5['world_menu_table']} WHERE wm_parent = '{$mid}' AND wm_depth = 1 ORDER BY wm_order, wm_id");
    while ($row = sql_fetch_array($rs)) { $sub_items[$mid][] = $row; }
}

$page_json = array();
foreach ($pages as $pg) $page_json[] = array('id' => $pg['wo_id'], 'label' => $pg['wo_subject']);
?>

<style>
.wm-wrap { max-width:1100px; }
.wm-help { background:#f0f7ff;border:1px solid #b3d4ff;padding:10px 14px;border-radius:4px;margin:8px 0 14px;color:#234;line-height:1.6; }
.wm-help code { background:#fff;padding:1px 4px;border:1px solid #ccd;border-radius:2px;font-size:12px; }
.wm-toolbar { display:flex;gap:8px;align-items:center;margin:10px 0; }
.wm-toolbar .spacer { flex:1; }
.wm-toolbar .btn-add { background:#2b7;border:none;color:#fff;padding:8px 14px;border-radius:4px;cursor:pointer;font-weight:600; }
.wm-toolbar .btn-add:hover { background:#196; }
.wm-toolbar .pages-link { color:#456;font-size:12px; }
.wm-list { list-style:none;padding:0;margin:0; }
.wm-card { background:#fff;border:1px solid #cfd8e3;border-radius:6px;margin:10px 0;box-shadow:0 1px 2px rgba(0,0,0,.04); }
.wm-card.deleted { opacity:.35;background:#fee; }
.wm-card .main-row { display:flex;align-items:center;gap:10px;padding:12px 14px;background:#f7faff;border-bottom:1px solid #e1e8f0;border-radius:6px 6px 0 0;flex-wrap:wrap; }
.wm-card .main-row .handle { cursor:grab;color:#aab;font-size:18px;padding:0 4px;user-select:none; }
.wm-card .main-row .handle:active { cursor:grabbing; }
.wm-card .main-row .badge-main { background:#3a6;color:#fff;font-size:11px;padding:2px 8px;border-radius:3px;font-weight:600; }
.wm-card .main-row input[type=text] { font-size:14px;font-weight:600;flex:0 1 220px; }
.wm-card .main-row select { flex:0 1 220px; }
.wm-card .main-row .first-toggle { display:flex;align-items:center;gap:4px;font-size:12px;color:#a70; }
.wm-card .main-row .actions { margin-left:auto;display:flex;gap:6px; }
.wm-card .main-row .btn-mini { font-size:12px;padding:4px 8px;border:1px solid #bcd;background:#fff;border-radius:3px;cursor:pointer; }
.wm-card .main-row .btn-mini:hover { background:#eef; }
.wm-card .main-row .btn-del { color:#c33;border-color:#fbb; }
.wm-card .imgs { display:flex;gap:14px;padding:8px 14px 0;font-size:11px;color:#678;flex-wrap:wrap; }
.wm-card .imgs .col { display:flex;align-items:center;gap:6px; }
.wm-card .imgs .thumb { width:36px;height:24px;border:1px solid #ddd;background:#f0f0f0 center/contain no-repeat;border-radius:2px; }
.wm-card .imgs label { display:inline-flex;align-items:center;gap:3px; }
.wm-subs { list-style:none;margin:0;padding:8px 14px 12px 32px; }
.wm-subs li { display:flex;align-items:center;gap:8px;padding:6px 8px;background:#fafbfc;border:1px solid #eef;border-radius:4px;margin:4px 0;flex-wrap:wrap; }
.wm-subs li.deleted { opacity:.35;background:#fee; }
.wm-subs .handle { cursor:grab;color:#bcd;font-size:14px;user-select:none; }
.wm-subs .badge-sub { color:#789;font-size:11px;padding:1px 6px;border:1px solid #cde;border-radius:3px;background:#fff; }
.wm-subs input[type=text] { flex:0 1 180px;font-size:13px; }
.wm-subs select { flex:0 1 200px; }
.wm-subs .imgs-inline { display:flex;gap:6px;align-items:center;font-size:11px;color:#678; }
.wm-subs .imgs-inline .thumb { width:28px;height:20px;border:1px solid #ddd;background:#f0f0f0 center/contain no-repeat;border-radius:2px; }
.wm-subs .actions { margin-left:auto;display:flex;gap:4px; }
.wm-subs .btn-mini { font-size:12px;padding:3px 6px;border:1px solid #bcd;background:#fff;border-radius:3px;cursor:pointer; }
.wm-subs .btn-del { color:#c33;border-color:#fbb; }
.wm-card .add-sub { padding:6px 14px 12px 32px; }
.wm-card .add-sub a { font-size:12px;color:#369;text-decoration:none;border:1px dashed #abc;padding:4px 10px;border-radius:3px; }
.wm-card .add-sub a:hover { background:#f0f7ff; }
.dnd-over { outline:2px dashed #69b;outline-offset:-2px; }
.wm-save { display:flex;justify-content:flex-end;margin:18px 0; }
.wm-save .btn-save { background:#369;color:#fff;padding:10px 24px;border:none;border-radius:4px;font-size:14px;font-weight:600;cursor:pointer; }
.wm-empty { padding:30px;text-align:center;color:#999;border:2px dashed #cde;border-radius:6px;margin:10px 0; }
</style>

<div class="wm-wrap">

<div class="wm-help">
    <strong>세계관 메뉴 구성</strong> — 메인 카드를 위/아래로 드래그하면 순서가 바뀝니다. 서브 메뉴도 드래그해서 다른 메인 아래로 옮길 수 있습니다.
    <br>각 메뉴에 <code>연결 페이지</code>를 골라야 클릭 시 해당 세계관 페이지로 이동합니다. <code>첫 페이지</code>로 지정한 메뉴가 <code>/world/</code> 진입 시 기본 노출됩니다.
    <br>관리할 페이지가 부족하다면 <a href="./world_list.php">[페이지 목록]</a>에서 먼저 만들어 주세요.
</div>

<form name="frmworldmenu" id="frmworldmenu" method="post" action="./world_menu_update.php" enctype="multipart/form-data" onsubmit="return wmBeforeSubmit();">
<input type="hidden" name="token" value="">

<div class="wm-toolbar">
    <button type="button" class="btn-add" onclick="addMainCard()">+ 메인 메뉴 추가</button>
    <span class="pages-link">등록된 페이지 <?php echo count($pages); ?>개</span>
    <span class="spacer"></span>
    <a href="./world_list.php" class="pages-link">[페이지 목록 관리]</a>
    <a href="./world_design_form.php" class="pages-link">[세계관 디자인 설정]</a>
</div>

<ul class="wm-list" id="wm-list">
<?php
$main_idx = 0;
$sub_idx_global = 0;
foreach ($main_items as $main):
    $mid = (int)$main['wm_id'];
    $dimg_name = $main['wm_img'] ?? '';
    $himg_name = $main['wm_img_hover'] ?? '';
    $dimg_url = ($dimg_name && file_exists(G5_DATA_PATH.'/world/menu/'.$dimg_name)) ? (G5_DATA_URL.'/world/menu/'.$dimg_name.'?t='.time()) : '';
    $himg_url = ($himg_name && file_exists(G5_DATA_PATH.'/world/menu/'.$himg_name)) ? (G5_DATA_URL.'/world/menu/'.$himg_name.'?t='.time()) : '';
?>
<li class="wm-card" draggable="true" data-card="main" data-main-idx="<?php echo $main_idx; ?>">
    <div class="main-row">
        <span class="handle" title="드래그로 순서 변경">⋮⋮</span>
        <span class="badge-main">메인</span>
        <input type="hidden" name="main_<?php echo $main_idx; ?>_id" value="<?php echo $mid; ?>">
        <input type="hidden" name="main_<?php echo $main_idx; ?>_del" class="del-flag" value="0">
        <input type="hidden" name="main_<?php echo $main_idx; ?>_order" class="order-input" value="<?php echo (int)$main['wm_order']; ?>">
        <input type="text" name="main_<?php echo $main_idx; ?>_title" value="<?php echo htmlspecialchars2($main['wm_title']); ?>" placeholder="메뉴명">
        <select name="main_<?php echo $main_idx; ?>_link_wo_id">
            <option value="">— 페이지 선택 —</option>
            <?php foreach ($pages as $pg): ?>
            <option value="<?php echo $pg['wo_id']; ?>"<?php echo ($main['wm_link_wo_id'] == $pg['wo_id']) ? ' selected' : ''; ?>><?php echo htmlspecialchars2($pg['wo_subject']); ?></option>
            <?php endforeach; ?>
        </select>
        <label class="first-toggle"><input type="radio" name="first_page" value="m_<?php echo $main_idx; ?>"<?php echo $main['wm_is_first'] ? ' checked' : ''; ?>> 첫 페이지</label>
        <span class="actions">
            <button type="button" class="btn-mini btn-del" onclick="delCard(this)">삭제</button>
        </span>
    </div>

    <div class="imgs">
        <div class="col">
            <span class="thumb" style="<?php echo $dimg_url ? 'background-image:url('.$dimg_url.')' : ''; ?>"></span>
            기본:<input type="file" name="main_img_<?php echo $main_idx; ?>_d" accept="image/*">
            <?php if ($dimg_url): ?><label><input type="checkbox" name="main_img_del_<?php echo $main_idx; ?>_d" value="1"> 삭제</label><?php endif; ?>
        </div>
        <div class="col">
            <span class="thumb" style="<?php echo $himg_url ? 'background-image:url('.$himg_url.')' : ''; ?>"></span>
            Hover:<input type="file" name="main_img_<?php echo $main_idx; ?>_h" accept="image/*">
            <?php if ($himg_url): ?><label><input type="checkbox" name="main_img_del_<?php echo $main_idx; ?>_h" value="1"> 삭제</label><?php endif; ?>
        </div>
    </div>

    <ul class="wm-subs" data-subs-of="<?php echo $main_idx; ?>">
<?php
foreach ($sub_items[$mid] as $sub):
    $sid = (int)$sub['wm_id'];
    $sd_name = $sub['wm_img'] ?? '';
    $sh_name = $sub['wm_img_hover'] ?? '';
    $sd_url = ($sd_name && file_exists(G5_DATA_PATH.'/world/menu/'.$sd_name)) ? (G5_DATA_URL.'/world/menu/'.$sd_name.'?t='.time()) : '';
    $sh_url = ($sh_name && file_exists(G5_DATA_PATH.'/world/menu/'.$sh_name)) ? (G5_DATA_URL.'/world/menu/'.$sh_name.'?t='.time()) : '';
?>
        <li draggable="true" data-card="sub" data-sub-idx="<?php echo $sub_idx_global; ?>">
            <span class="handle">⋮⋮</span>
            <span class="badge-sub">서브</span>
            <input type="hidden" name="sub_<?php echo $sub_idx_global; ?>_id" value="<?php echo $sid; ?>">
            <input type="hidden" name="sub_<?php echo $sub_idx_global; ?>_main_idx" class="parent-input" value="<?php echo $main_idx; ?>">
            <input type="hidden" name="sub_<?php echo $sub_idx_global; ?>_del" class="del-flag" value="0">
            <input type="hidden" name="sub_<?php echo $sub_idx_global; ?>_order" class="order-input" value="<?php echo (int)$sub['wm_order']; ?>">
            <input type="text" name="sub_<?php echo $sub_idx_global; ?>_title" value="<?php echo htmlspecialchars2($sub['wm_title']); ?>" placeholder="서브메뉴명">
            <select name="sub_<?php echo $sub_idx_global; ?>_link_wo_id">
                <option value="">— 페이지 선택 —</option>
                <?php foreach ($pages as $pg): ?>
                <option value="<?php echo $pg['wo_id']; ?>"<?php echo ($sub['wm_link_wo_id'] == $pg['wo_id']) ? ' selected' : ''; ?>><?php echo htmlspecialchars2($pg['wo_subject']); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="first-toggle"><input type="radio" name="first_page" value="s_<?php echo $sub_idx_global; ?>"<?php echo $sub['wm_is_first'] ? ' checked' : ''; ?>> 첫</label>
            <span class="imgs-inline">
                <span class="thumb" style="<?php echo $sd_url ? 'background-image:url('.$sd_url.')' : ''; ?>" title="기본"></span>
                <input type="file" name="sub_img_<?php echo $sub_idx_global; ?>_d" accept="image/*" title="기본 이미지">
                <span class="thumb" style="<?php echo $sh_url ? 'background-image:url('.$sh_url.')' : ''; ?>" title="hover"></span>
                <input type="file" name="sub_img_<?php echo $sub_idx_global; ?>_h" accept="image/*" title="hover 이미지">
            </span>
            <span class="actions">
                <button type="button" class="btn-mini btn-del" onclick="delCard(this)">삭제</button>
            </span>
        </li>
<?php $sub_idx_global++; endforeach; ?>
    </ul>

    <div class="add-sub">
        <a href="#" onclick="addSubCard(this); return false;">+ 서브 추가</a>
    </div>
</li>
<?php $main_idx++; endforeach; ?>
</ul>

<?php if (empty($main_items)): ?>
<div class="wm-empty" id="wm-empty">메뉴가 없습니다. 위 [+ 메인 메뉴 추가] 버튼을 눌러 시작하세요.</div>
<?php endif; ?>

<div class="wm-save">
    <button type="submit" class="btn-save">저장</button>
</div>
</form>

</div>

<script>
var WM_MAIN_COUNT = <?php echo $main_idx; ?>;
var WM_SUB_COUNT  = <?php echo $sub_idx_global; ?>;
var WM_PAGES = <?php echo json_encode($page_json, JSON_UNESCAPED_UNICODE); ?>;

function escAttr(s) { return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }

function pageOpts(sel) {
    var s = '<option value="">— 페이지 선택 —</option>';
    WM_PAGES.forEach(function(p) {
        s += '<option value="' + escAttr(p.id) + '"' + (p.id == sel ? ' selected' : '') + '>' + escAttr(p.label) + '</option>';
    });
    return s;
}

function addMainCard() {
    var emp = document.getElementById('wm-empty'); if (emp) emp.remove();
    var idx = WM_MAIN_COUNT++;
    var html =
      '<li class="wm-card" draggable="true" data-card="main" data-main-idx="'+idx+'">' +
        '<div class="main-row">' +
          '<span class="handle">⋮⋮</span><span class="badge-main">메인</span>' +
          '<input type="hidden" name="main_'+idx+'_id" value="0">' +
          '<input type="hidden" name="main_'+idx+'_del" class="del-flag" value="0">' +
          '<input type="hidden" name="main_'+idx+'_order" class="order-input" value="0">' +
          '<input type="text" name="main_'+idx+'_title" placeholder="메뉴명">' +
          '<select name="main_'+idx+'_link_wo_id">'+pageOpts('')+'</select>' +
          '<label class="first-toggle"><input type="radio" name="first_page" value="m_'+idx+'"> 첫 페이지</label>' +
          '<span class="actions"><button type="button" class="btn-mini btn-del" onclick="delCard(this)">삭제</button></span>' +
        '</div>' +
        '<div class="imgs">' +
          '<div class="col"><span class="thumb"></span>기본:<input type="file" name="main_img_'+idx+'_d" accept="image/*"></div>' +
          '<div class="col"><span class="thumb"></span>Hover:<input type="file" name="main_img_'+idx+'_h" accept="image/*"></div>' +
        '</div>' +
        '<ul class="wm-subs" data-subs-of="'+idx+'"></ul>' +
        '<div class="add-sub"><a href="#" onclick="addSubCard(this); return false;">+ 서브 추가</a></div>' +
      '</li>';
    document.getElementById('wm-list').insertAdjacentHTML('beforeend', html);
    bindDnd();
}

function addSubCard(triggerEl) {
    var card = triggerEl.closest('.wm-card');
    var ul = card.querySelector('.wm-subs');
    var mainIdx = card.getAttribute('data-main-idx');
    var idx = WM_SUB_COUNT++;
    var html =
      '<li draggable="true" data-card="sub" data-sub-idx="'+idx+'">' +
        '<span class="handle">⋮⋮</span><span class="badge-sub">서브</span>' +
        '<input type="hidden" name="sub_'+idx+'_id" value="0">' +
        '<input type="hidden" name="sub_'+idx+'_main_idx" class="parent-input" value="'+mainIdx+'">' +
        '<input type="hidden" name="sub_'+idx+'_del" class="del-flag" value="0">' +
        '<input type="hidden" name="sub_'+idx+'_order" class="order-input" value="0">' +
        '<input type="text" name="sub_'+idx+'_title" placeholder="서브메뉴명">' +
        '<select name="sub_'+idx+'_link_wo_id">'+pageOpts('')+'</select>' +
        '<label class="first-toggle"><input type="radio" name="first_page" value="s_'+idx+'"> 첫</label>' +
        '<span class="imgs-inline">' +
          '<span class="thumb" title="기본"></span><input type="file" name="sub_img_'+idx+'_d" accept="image/*">' +
          '<span class="thumb" title="hover"></span><input type="file" name="sub_img_'+idx+'_h" accept="image/*">' +
        '</span>' +
        '<span class="actions"><button type="button" class="btn-mini btn-del" onclick="delCard(this)">삭제</button></span>' +
      '</li>';
    ul.insertAdjacentHTML('beforeend', html);
    bindDnd();
}

function delCard(el) {
    var li = el.closest('li');
    var isMainCard = li.classList.contains('wm-card');
    var subCount = isMainCard ? li.querySelectorAll('.wm-subs > li').length : 0;
    var msg = isMainCard && subCount > 0 ? '메인 메뉴와 서브 '+subCount+'개를 함께 삭제합니다. 계속하시겠습니까?' : '삭제하시겠습니까?';
    if (!confirm(msg)) return;
    var topDel = isMainCard ? li.querySelector(':scope > .main-row > .del-flag') : li.querySelector(':scope > .del-flag');
    if (topDel) topDel.value = '1';
    li.classList.add('deleted');
    if (isMainCard) {
        li.querySelectorAll('.wm-subs .del-flag').forEach(function(f){ f.value='1'; });
        li.querySelectorAll('.wm-subs > li').forEach(function(s){ s.classList.add('deleted'); });
    }
}

// ─── Drag & Drop ───────────────────────────────────────────────
var dragEl = null;

function bindDnd() {
    document.querySelectorAll('#wm-list > li.wm-card, #wm-list .wm-subs > li').forEach(function(el) {
        if (el._dnd) return;
        el._dnd = true;
        el.addEventListener('dragstart', function(e) {
            // 입력 요소에서 드래그 시작 방지
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'BUTTON') {
                e.preventDefault(); return;
            }
            dragEl = el;
            el.style.opacity = '0.4';
            e.dataTransfer.effectAllowed = 'move';
            e.stopPropagation();
        });
        el.addEventListener('dragend', function() {
            if (dragEl) dragEl.style.opacity = '';
            dragEl = null;
            document.querySelectorAll('.dnd-over').forEach(function(x){ x.classList.remove('dnd-over'); });
        });
        el.addEventListener('dragover', function(e) {
            if (!dragEl || dragEl === el) return;
            if (dragEl.dataset.card !== el.dataset.card) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            el.classList.add('dnd-over');
        });
        el.addEventListener('dragleave', function() { el.classList.remove('dnd-over'); });
        el.addEventListener('drop', function(e) {
            if (!dragEl || dragEl === el) return;
            if (dragEl.dataset.card !== el.dataset.card) return;
            e.preventDefault();
            e.stopPropagation();
            el.classList.remove('dnd-over');
            var rect = el.getBoundingClientRect();
            var after = (e.clientY - rect.top) > rect.height / 2;
            el.parentNode.insertBefore(dragEl, after ? el.nextSibling : el);
            // sub 옮긴 경우 parent main_idx 갱신
            if (dragEl.dataset.card === 'sub') {
                var newCard = dragEl.closest('.wm-card');
                if (newCard) {
                    var newMainIdx = newCard.getAttribute('data-main-idx');
                    var pi = dragEl.querySelector('.parent-input');
                    if (pi) pi.value = newMainIdx;
                }
            }
        });
    });
}
bindDnd();

function wmBeforeSubmit() {
    var mIdx = 0;
    document.querySelectorAll('#wm-list > li.wm-card').forEach(function(card) {
        var oi = card.querySelector(':scope > .main-row > .order-input');
        if (oi) oi.value = (mIdx++);
        var sIdx = 0;
        card.querySelectorAll(':scope > .wm-subs > li').forEach(function(sub) {
            var so = sub.querySelector(':scope > .order-input');
            if (so) so.value = (sIdx++);
        });
    });
    return true;
}
</script>

<?php include_once(G5_ADMIN_PATH.'/admin.tail.php'); ?>
