<?php
$sub_menu = "110100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$g5['title'] = '시기 관리';
include_once('./admin.head.php');

// 시기 목록
$list = array();
$result = sql_query("SELECT * FROM `{$g5['time_table']}` ORDER BY ti_order ASC");
while ($row = sql_fetch_array($result)) { $list[] = $row; }

$cf_time = isset($config['cf_time']) ? (int)$config['cf_time'] : 1;

// 프리셋 목록
$presets = array();
$res = sql_query("SELECT pr_id, pr_name FROM `{$g5['theme_preset_table']}` ORDER BY pr_order ASC, pr_id ASC", false);
if ($res) {
    while ($r = sql_fetch_array($res)) { $presets[] = $r; }
}
$cf_preset_id = isset($config['cf_preset_id']) ? (int)$config['cf_preset_id'] : 0;
?>
<div class="time-admin">

<div class="ta-help">
    <strong>시기 관리</strong> — 시기는 사이트의 컨텐츠/디자인을 시간 축으로 분기하는 단위입니다.
    각 시기에 <strong>타입</strong>(리뉴얼/AU/이벤트)과 <strong>테마 프리셋</strong>을 매핑할 수 있으며,
    시기 1은 항상 "리뉴얼"이며 삭제할 수 없습니다.<br>
    ※ 순서(ti_order)는 시기 변경 탭의 정렬 순서로, 자유롭게 변경 가능합니다.
</div>

<div class="ta-help" style="background:#f9f6ef;border-color:#e8dfc8;">
    <strong style="color:#7a5500;">시기 타입 안내</strong>
    <ul style="margin:6px 0 0 18px;padding:0;font-size:12px;line-height:1.8;color:#456;">
        <li><strong style="color:#06c;">리뉴얼</strong> — 본편 시기. 프로필/캐릭터 항목이 <em>새로 추가됨</em>. 멤버/마이페이지의 시기 변경 탭에 표시됨. 직전 리뉴얼 시기 프로필이 새 항목의 기본값이 됨.</li>
        <li><strong style="color:#a40">AU</strong> — Alternate Universe 시기. 프로필/캐릭터 항목이 추가됨. 멤버/마이페이지의 시기 변경 탭에 표시됨. AU 시기일 때만 AU 항목이 기본값.</li>
        <li><strong style="color:#888">이벤트</strong> — 단기 이벤트 시기. 프로필 항목 <em>추가되지 않음</em>. 시기 변경 탭에 <strong>표시되지 않음</strong>. 테마 프리셋만 변경되며, 프로필은 직전 리뉴얼 시기의 값을 사용.</li>
    </ul>
</div>

<form name="ftime" action="./config_time_update.php" method="post">
<input type="hidden" name="action" value="save">

<div class="ta-grid">
    <div class="ta-card">
        <h3>현재 공개 시기</h3>
        <p class="desc">외부 사용자가 <code>?time=</code> 없이 접근할 때 표시되는 기본 시기입니다.</p>
        <select name="cf_time" style="width:100%;">
        <?php foreach ($list as $r) { ?>
            <option value="<?php echo (int)$r['ti_order']; ?>" <?php echo ($cf_time === (int)$r['ti_order']) ? 'selected' : ''; ?>>
                #<?php echo (int)$r['ti_order']; ?> — <?php echo h($r['ti_name']); ?>
            </option>
        <?php } ?>
        </select>
    </div>
    <div class="ta-card">
        <h3>글로벌 기본 테마 프리셋</h3>
        <p class="desc">시기에 프리셋이 매핑되지 않은 경우 사용됩니다.</p>
        <select name="cf_preset_id" style="width:100%;">
            <option value="0" <?php echo ($cf_preset_id === 0) ? 'selected' : ''; ?>>(없음 — pr_id=1 default 사용)</option>
        <?php foreach ($presets as $p) { ?>
            <option value="<?php echo (int)$p['pr_id']; ?>" <?php echo ($cf_preset_id === (int)$p['pr_id']) ? 'selected' : ''; ?>>
                #<?php echo (int)$p['pr_id']; ?> — <?php echo h($p['pr_name']); ?>
            </option>
        <?php } ?>
        </select>
    </div>
</div>

<div class="ta-card" style="padding:0;overflow:hidden;">
<table class="ta-list">
    <thead>
        <tr>
            <th class="ta-col-order">순서</th>
            <th>이름</th>
            <th class="ta-col-open">공개</th>
            <th class="ta-col-type">타입</th>
            <th class="ta-col-preset">테마 프리셋</th>
            <th class="ta-col-mng">삭제</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($list as $r) {
        $is_base = ((int)$r['ti_order'] === 1);
        $cur_type = isset($r['ti_type']) ? (string)$r['ti_type'] : 'renewal';
        $cur_pid = isset($r['ti_preset_id']) ? (int)$r['ti_preset_id'] : 0;
    ?>
        <tr<?php echo $is_base ? ' class="row-base"' : ''; ?> data-tio="<?php echo (int)$r['ti_order']; ?>">
            <td class="ta-col-order">
                <?php if ($is_base) { ?>
                    <input type="hidden" name="ti_order[<?php echo (int)$r['ti_id']; ?>]" value="1">
                    <span class="ta-tag">1</span>
                <?php } else { ?>
                    <input type="number" name="ti_order[<?php echo (int)$r['ti_id']; ?>]" min="2" value="<?php echo (int)$r['ti_order']; ?>">
                <?php } ?>
            </td>
            <td>
                <input type="hidden" name="ti_id[]" value="<?php echo (int)$r['ti_id']; ?>">
                <input type="text" name="ti_name[<?php echo (int)$r['ti_id']; ?>]" value="<?php echo h($r['ti_name']); ?>">
            </td>
            <td>
                <?php if ($is_base) { ?>
                    <input type="hidden" name="ti_open[<?php echo (int)$r['ti_id']; ?>]" value="1">
                    <span class="ta-tag">공개</span>
                <?php } else { ?>
                <select name="ti_open[<?php echo (int)$r['ti_id']; ?>]">
                    <option value="1" <?php echo ((int)$r['ti_open'] === 1) ? 'selected' : ''; ?>>공개</option>
                    <option value="2" <?php echo ((int)$r['ti_open'] === 2) ? 'selected' : ''; ?>>비공개</option>
                    <option value="0" <?php echo ((int)$r['ti_open'] === 0) ? 'selected' : ''; ?>>미사용</option>
                </select>
                <?php } ?>
            </td>
            <td>
                <?php if ($is_base) { ?>
                    <input type="hidden" name="ti_type[<?php echo (int)$r['ti_id']; ?>]" value="renewal">
                    <span class="ta-tag">리뉴얼</span>
                <?php } else { ?>
                <select name="ti_type[<?php echo (int)$r['ti_id']; ?>]">
                    <option value="renewal" <?php echo ($cur_type === 'renewal') ? 'selected' : ''; ?>>리뉴얼</option>
                    <option value="au"      <?php echo ($cur_type === 'au')      ? 'selected' : ''; ?>>AU</option>
                    <option value="event"   <?php echo ($cur_type === 'event')   ? 'selected' : ''; ?>>이벤트</option>
                </select>
                <?php } ?>
            </td>
            <td>
                <select name="ti_preset_id[<?php echo (int)$r['ti_id']; ?>]">
                    <option value="0" <?php echo ($cur_pid === 0) ? 'selected' : ''; ?>>(글로벌)</option>
                    <?php foreach ($presets as $p) { ?>
                    <option value="<?php echo (int)$p['pr_id']; ?>" <?php echo ($cur_pid === (int)$p['pr_id']) ? 'selected' : ''; ?>>
                        #<?php echo (int)$p['pr_id']; ?> <?php echo h($p['pr_name']); ?>
                    </option>
                    <?php } ?>
                </select>
            </td>
            <td class="ta-col-mng">
                <?php if (!$is_base) { ?>
                <label title="저장 시 삭제됩니다"><input type="checkbox" name="ti_del[]" value="<?php echo (int)$r['ti_id']; ?>"></label>
                <?php } else { ?>
                <span class="ta-tag">기본</span>
                <?php } ?>
                <button type="button" class="ti-detail-btn" data-tio="<?php echo (int)$r['ti_order']; ?>" style="margin-left:6px;font-size:11px;padding:2px 8px;cursor:pointer;background:#f4f4f4;border:1px solid #ccc;border-radius:3px;">자세히</button>
            </td>
        </tr>
        <tr class="ti-detail-row" id="ti-detail-row-<?php echo (int)$r['ti_order']; ?>" style="display:none;">
            <td colspan="6" style="padding:10px 14px;background:#fafafa;border-top:1px dashed #ccc;">
                <div class="ti-detail-content" data-loaded="0">로딩 중...</div>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
</div>

<details class="ta-add">
    <summary>＋ 새 시기 추가</summary>
    <div class="ta-add-body">
        <div class="field">
            <label>순서 (ti_order)</label>
            <input type="number" name="new_order" min="2" value="" placeholder="예: 2">
        </div>
        <div class="field">
            <label>이름</label>
            <input type="text" name="new_name" value="" placeholder="예: 이벤트 1">
        </div>
        <div class="field">
            <label>공개</label>
            <select name="new_open">
                <option value="1">공개</option>
                <option value="2">비공개</option>
                <option value="0">미사용</option>
            </select>
        </div>
        <div class="field">
            <label>타입</label>
            <select name="new_type">
                <option value="renewal">리뉴얼</option>
                <option value="au">AU</option>
                <option value="event">이벤트</option>
            </select>
        </div>
    </div>
</details>

<div class="ta-save-bar">
    <button type="submit" class="ui-btn admin">전체 저장</button>
<script>
(function(){
    document.addEventListener('click', function(e){
        var btn = e.target.closest('.ti-detail-btn');
        if (!btn) return;
        var tio = btn.getAttribute('data-tio');
        var row = document.getElementById('ti-detail-row-' + tio);
        if (!row) return;
        var visible = row.style.display !== 'none';
        if (visible) { row.style.display = 'none'; return; }
        row.style.display = '';
        var box = row.querySelector('.ti-detail-content');
        if (box && box.getAttribute('data-loaded') !== '1') {
            box.innerHTML = '<span style="color:#999;">불러오는 중...</span>';
            fetch('./config_time_detail.ajax.php?ti_order=' + encodeURIComponent(tio), {credentials:'same-origin'})
                .then(function(r){ return r.text(); })
                .then(function(html){ box.innerHTML = html; box.setAttribute('data-loaded', '1'); })
                .catch(function(){ box.innerHTML = '<span style="color:#a00;">불러오기 실패</span>'; });
        }
    });
})();
</script>

</div>

</form>

</div>

<?php include_once('./admin.tail.php'); ?>
