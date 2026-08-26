<?php
$sub_menu = "110200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

if (!$is_admin)
    alert('관리자만 접근 가능합니다.');

$pr_id = ses($_GET, 'pr_id', 0, 'int');
if ($pr_id <= 0) goto_url('./theme_preset_list.php');

$row = sql_fetch("SELECT * FROM `{$g5['theme_preset_table']}` WHERE pr_id = '{$pr_id}'", false);
if (empty($row['pr_id'])) alert('존재하지 않는 프리셋입니다.', './theme_preset_list.php');

// 슬롯 매핑 로드
$slot_map = array();
$res = sql_query("SELECT ps_slot, ps_theme FROM `{$g5['theme_preset_slot_table']}` WHERE pr_id = '{$pr_id}'", false);
if ($res) {
    while ($r = sql_fetch_array($res)) { $slot_map[$r['ps_slot']] = $r['ps_theme']; }
}

$themes = function_exists('theme_list') ? theme_list() : array('default');
$module_themes = function_exists('theme_list_modules') ? theme_list_modules() : array();
$slot_def = isset($GLOBALS['THEME_SLOT_MAP']) ? $GLOBALS['THEME_SLOT_MAP'] : array();
$slot_groups = isset($GLOBALS['THEME_SLOT_GROUPS']) ? $GLOBALS['THEME_SLOT_GROUPS'] : array();

// "한 번에 모두 적용" 드롭다운용: 코어/모듈 분리, 지원 슬롯이 0개인 테마는 제외
$bulk_themes_core = array();
$bulk_themes_mod  = array();
if (function_exists('theme_supported_slots')) {
    foreach ($themes as $t) {
        if ($t === 'default' || count(theme_supported_slots($t)) > 0) $bulk_themes_core[] = $t;
    }
    foreach ($module_themes as $t) {
        if (count(theme_supported_slots($t)) > 0) $bulk_themes_mod[] = $t;
    }
} else {
    $bulk_themes_core = $themes;
    $bulk_themes_mod  = $module_themes;
}

// 디자인 프리셋 레지스트리 (main, world, ...)
$design_types = isset($GLOBALS['DESIGN_PRESET_TYPES']) ? $GLOBALS['DESIGN_PRESET_TYPES'] : array();

// 각 디자인 타입별 통합 옵션 목록 (프리셋 + 스킨)
$design_choices = array();  // type => array of {value,label,kind,group}
foreach ($design_types as $dt_key => $dt_def) {
    $design_choices[$dt_key] = function_exists('get_design_choice_options')
        ? get_design_choice_options($dt_key)
        : array();
}

// 슬롯을 group / 단일 으로 분류
$grouped = array();   // group_key => array(slot, slot, ...)
$singles = array();   // slot list (그룹 없음)
foreach ($slot_def as $slot => $def) {
    $g = isset($def['group']) ? $def['group'] : '';
    if ($g !== '' && isset($slot_groups[$g])) {
        if (!isset($grouped[$g])) $grouped[$g] = array();
        $grouped[$g][] = $slot;
    } else {
        $singles[] = $slot;
    }
}

// 그룹별 현재 값(첫 슬롯 기준)
$group_value = array();
foreach ($grouped as $g => $slots) {
    $first = $slots[0];
    $group_value[$g] = isset($slot_map[$first]) ? $slot_map[$first] : 'default';
}

$g5['title'] = '테마 프리셋 설정';
include_once('./admin.head.php');
$design_hub = 'preset';
$design_hub_tab = 'bundle';
include_once('./_design_hub_tabs.inc.php');
?>
<div class="preset-edit">

<form action="./theme_preset_form_update.php" method="post">
<input type="hidden" name="pr_id" value="<?php echo (int)$row['pr_id']; ?>">

<div class="help">
    <strong>편집 중:</strong> #<?php echo (int)$row['pr_id']; ?> <?php echo h($row['pr_name']); ?>
    — 테마 파일과 사이트/세계관/문서 디자인을 이 세트에 묶습니다.
    저장 후 <a href="./config_time.php">시기 관리</a>에서 연결하거나, <a href="./theme_preset_list.php">테마 프리셋</a> 목록의 글로벌 기본으로 지정합니다.
</div>

<h3>기본 정보</h3>
<table>
    <tr>
        <th>프리셋 이름</th>
        <td><input type="text" name="pr_name" value="<?php echo h($row['pr_name']); ?>" style="width:300px;" required>
            <span style="color:#888;font-size:12px;">관리자 식별용 (예: "기본", "할로윈", "겨울이벤트")</span>
        </td>
    </tr>
    <?php foreach ($design_types as $dt_key => $dt_def):
        $col        = $dt_def['column'];
        $allow_none = !empty($dt_def['allow_none']);
        $cur_raw    = isset($row[$col]) ? (string)$row[$col] : '';
        if ($cur_raw === '' || $cur_raw === 'default') $cur_raw = '1';
        if (!$allow_none && $cur_raw === 'none') $cur_raw = '1';

        $choices    = isset($design_choices[$dt_key]) ? $design_choices[$dt_key] : array();
        $cur_parsed = function_exists('parse_design_value') ? parse_design_value($cur_raw) : array('kind'=>'preset','value'=>(int)$cur_raw);

        // optgroup 묶기
        $by_group = array('preset' => array(), 'skin_core' => array(), 'skin_theme' => array(), 'skin_mtheme' => array(), 'skin' => array(), 'none' => array());
        foreach ($choices as $opt) {
            $gk = $opt['group'];
            if (!isset($by_group[$gk])) $by_group[$gk] = array();
            $by_group[$gk][] = $opt;
        }
        $group_labels = array(
            'preset'      => '디자인 프리셋',
            'skin_core'   => '코어 스킨',
            'skin_theme'  => '테마 스킨',
            'skin_mtheme' => '모듈-테마 스킨',
            'skin'        => '스킨',
            'none'        => '',
        );

        // 편집 링크 — 프리셋이면 해당 프리셋 편집 폼으로
        $edit_pd_id = ($cur_parsed['kind'] === 'preset') ? (int)$cur_parsed['value'] : 1;
    ?>
    <tr>
        <th><?php echo h($dt_def['label']); ?></th>
        <td>
            <select name="design[<?php echo h($dt_key); ?>]">
                <?php foreach (array('none','preset','skin_core','skin_theme','skin_mtheme','skin') as $gk):
                    if (empty($by_group[$gk])) continue;
                    $glabel = $group_labels[$gk];
                ?>
                <?php if ($gk === 'none'): ?>
                    <?php foreach ($by_group[$gk] as $opt): ?>
                    <option value="<?php echo h($opt['value']); ?>"<?php echo ($cur_raw === $opt['value']) ? ' selected' : ''; ?>><?php echo h($opt['label']); ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                <optgroup label="<?php echo h($glabel); ?>">
                    <?php foreach ($by_group[$gk] as $opt): ?>
                    <option value="<?php echo h($opt['value']); ?>"<?php echo ($cur_raw === $opt['value']) ? ' selected' : ''; ?>><?php echo h($opt['label']); ?></option>
                    <?php endforeach; ?>
                </optgroup>
                <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($dt_def['edit_url']) && $cur_parsed['kind'] === 'preset'): ?>
            <a href="<?php echo h($dt_def['edit_url']); ?>?pd_id=<?php echo $edit_pd_id; ?>" class="btn_admin">프리셋 편집</a>
            <?php endif; ?>
            <?php if ($cur_parsed['kind'] === 'skin'): ?>
            <span style="color:#369;font-size:12px;margin-left:6px;">선택된 스킨: <code><?php echo h($cur_parsed['value']); ?></code></span>
            <?php endif; ?>
            <?php if (!empty($dt_def['desc'])): ?>
            <div style="color:#888;font-size:12px;margin-top:4px;"><?php echo h($dt_def['desc']); ?></div>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <tr>
        <th>정렬 순서</th>
        <td><input type="number" name="pr_order" value="<?php echo (int)$row['pr_order']; ?>" min="0" style="width:80px;">
            <span style="color:#888;font-size:12px;">목록 정렬용 (작을수록 위)</span>
        </td>
    </tr>
</table>

<h3>배경 음악 (BGM)</h3>
<div class="help">
    이 프리셋이 활성일 때 사이트에 흐를 YouTube BGM 을 지정합니다.
    <strong>플레이리스트</strong>는 자동 순환 재생, <strong>단일 영상</strong>은 한 곡 반복입니다.
    값이 비어 있으면 <a href="./config_form.php">기본 환경설정</a>의 <code>cf_bgm</code> 을 사용합니다.
</div>
<table>
    <tr>
        <th>형식</th>
        <td>
            <?php $_bgm_type = isset($row['pr_bgm_type']) ? (string)$row['pr_bgm_type'] : 'playlist'; ?>
            <select name="pr_bgm_type">
                <option value="playlist" <?php echo ($_bgm_type === 'playlist') ? 'selected' : ''; ?>>플레이리스트 (PlayList ID)</option>
                <option value="video"    <?php echo ($_bgm_type === 'video')    ? 'selected' : ''; ?>>단일 영상 (Video ID)</option>
            </select>
        </td>
    </tr>
    <tr>
        <th>YouTube ID</th>
        <td>
            <input type="text" name="pr_bgm_id" value="<?php echo h(isset($row['pr_bgm_id']) ? $row['pr_bgm_id'] : ''); ?>" style="width:360px;" placeholder="예: PLxxxxxxxx 또는 dQw4w9WgXcQ">
            <div style="color:#888;font-size:12px;margin-top:4px;">
                플레이리스트 URL <code>?list=PL...</code> 의 <code>PL...</code>, 또는 영상 URL <code>?v=...</code> 의 <code>v</code> 값을 입력하세요.
            </div>
        </td>
    </tr>
    <tr>
        <th>기본 볼륨</th>
        <td>
            <?php $_bgm_vol = isset($row['pr_bgm_volume']) ? (int)$row['pr_bgm_volume'] : 50; if ($_bgm_vol < 0 || $_bgm_vol > 100) $_bgm_vol = 50; ?>
            <input type="range" min="0" max="100" step="1" name="pr_bgm_volume" value="<?php echo $_bgm_vol; ?>" oninput="document.getElementById('pr_bgm_vol_disp').textContent=this.value+'%';" style="vertical-align:middle;width:200px;">
            <span id="pr_bgm_vol_disp"><?php echo $_bgm_vol; ?>%</span>
        </td>
    </tr>
    <tr>
        <th>기본 폰트 (font-family)</th>
        <td>
            <input type="text" name="pr_font_family" value="<?php echo h($row['pr_font_family'] ?? ''); ?>" style="width:360px" placeholder='예: "Noto Sans KR", sans-serif'>
            <span style="color:#888;font-size:12px;">비워두면 테마 기본 폰트를 사용. <a href="./community_form.php">기본환경 → 추가 폰트</a>에서 link 태그를 먼저 등록하세요.</span>
        </td>
    </tr>
</table>

<h3>슬롯별 테마 매핑</h3>
<div class="help">
    각 영역마다 어떤 테마 폴더를 사용할지 선택합니다.
    <code>default</code>는 사이트 루트의 기본 파일을 사용한다는 뜻입니다.
    선택한 테마에 해당 파일이 없으면 자동으로 <code>default</code>로 폴백됩니다.
</div>

<p>
    <label><strong>한 번에 모두 적용:</strong>
        <select id="bulk-theme">
            <optgroup label="코어 테마">
            <?php foreach ($bulk_themes_core as $t) { ?>
            <option value="<?php echo h($t); ?>"><?php echo h(function_exists('theme_label') ? theme_label($t) : $t); ?></option>
            <?php } ?>
            </optgroup>
            <?php if (!empty($bulk_themes_mod)) { ?>
            <optgroup label="모듈 테마">
            <?php foreach ($bulk_themes_mod as $t) { ?>
            <option value="<?php echo h($t); ?>"><?php echo h(function_exists('theme_label') ? theme_label($t) : $t); ?></option>
            <?php } ?>
            </optgroup>
            <?php } ?>
        </select>
    </label>
    <button type="button" onclick="bulkApply();" class="btn_admin">전체 슬롯에 적용</button>
    <span style="color:#888;font-size:12px;margin-left:10px;">※ 각 드롭다운에는 해당 슬롯을 지원하는 테마만 표시됩니다 (theme/&lt;name&gt;/override_slots.php 또는 폴더 자동 감지).</span>
</p>

<?php
// ---- 그룹 카드 (기본 세팅 등) ----
foreach ($grouped as $gkey => $slot_list) {
    $ginfo = $slot_groups[$gkey];
    $cur = $group_value[$gkey];
    $g_themes = function_exists('theme_list_for_group') ? theme_list_for_group($slot_list) : $themes;
    // 현재 선택값이 지원 목록에 없으면 추가(데이터 보존)
    if ($cur !== '' && !in_array($cur, $g_themes, true)) $g_themes[] = $cur;
?>
<div class="group-card">
    <h4><?php echo h($ginfo['label']); ?></h4>
    <div class="desc"><?php echo h($ginfo['desc']); ?></div>
    <label><strong>테마:</strong>
        <select name="group[<?php echo h($gkey); ?>]" class="group-theme">
            <optgroup label="코어">
            <?php foreach ($g_themes as $t) if (strncmp($t,'mtheme:',7)!==0) { ?>
            <option value="<?php echo h($t); ?>" <?php echo ($cur === $t) ? 'selected' : ''; ?>><?php echo h(function_exists('theme_label') ? theme_label($t) : $t); ?></option>
            <?php } ?>
            </optgroup>
            <?php $_has_mod = false; foreach ($g_themes as $t) if (strncmp($t,'mtheme:',7)===0) { $_has_mod = true; break; } ?>
            <?php if ($_has_mod) { ?>
            <optgroup label="모듈">
            <?php foreach ($g_themes as $t) if (strncmp($t,'mtheme:',7)===0) { ?>
            <option value="<?php echo h($t); ?>" <?php echo ($cur === $t) ? 'selected' : ''; ?>><?php echo h(function_exists('theme_label') ? theme_label($t) : $t); ?></option>
            <?php } ?>
            </optgroup>
            <?php } ?>
        </select>
    </label>
    <div class="slot-list">
        포함 슬롯:
        <?php
        $names = array();
        foreach ($slot_list as $s) {
            $lbl = isset($slot_def[$s]['label']) ? $slot_def[$s]['label'] : $s;
            $names[] = h($lbl).' <code>('.h($slot_def[$s]['path']).')</code>';
        }
        echo implode(' · ', $names);
        ?>
    </div>
</div>
<?php } ?>

<table class="slot-table">
    <thead>
        <tr>
            <th style="width:240px;">슬롯</th>
            <th style="width:80px;">종류</th>
            <th>경로</th>
            <th style="width:280px;">테마</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($singles as $slot) {
        $def = $slot_def[$slot];
        $cur = isset($slot_map[$slot]) ? $slot_map[$slot] : 'default';
        $type_label = array('file'=>'파일','dir'=>'폴더','skin'=>'스킨');
        $tlabel = isset($type_label[$def['type']]) ? $type_label[$def['type']] : $def['type'];
        $s_themes = function_exists('theme_list_for_slot') ? theme_list_for_slot($slot) : $themes;
        if ($cur !== '' && !in_array($cur, $s_themes, true)) $s_themes[] = $cur;
    ?>
        <tr>
            <td class="label-cell">
                <strong><?php echo h(isset($def['label']) ? $def['label'] : $slot); ?></strong>
                <div class="slot-type"><?php echo h($slot); ?></div>
            </td>
            <td style="text-align:center;"><?php echo h($tlabel); ?></td>
            <td><code><?php echo h($def['path']); ?></code></td>
            <td>
                <select name="slot[<?php echo h($slot); ?>]" class="slot-theme" data-slot="<?php echo h($slot); ?>" data-type="<?php echo h($def['type']); ?>" data-path="<?php echo h($def['path']); ?>">
                    <optgroup label="코어">
                    <?php foreach ($s_themes as $t) if (strncmp($t,'mtheme:',7)!==0) { ?>
                    <option value="<?php echo h($t); ?>" <?php echo ($cur === $t) ? 'selected' : ''; ?>><?php echo h(function_exists('theme_label') ? theme_label($t) : $t); ?></option>
                    <?php } ?>
                    </optgroup>
                    <?php $_has_mod = false; foreach ($s_themes as $t) if (strncmp($t,'mtheme:',7)===0) { $_has_mod = true; break; } ?>
                    <?php if ($_has_mod) { ?>
                    <optgroup label="모듈">
                    <?php foreach ($s_themes as $t) if (strncmp($t,'mtheme:',7)===0) { ?>
                    <option value="<?php echo h($t); ?>" <?php echo ($cur === $t) ? 'selected' : ''; ?>><?php echo h(function_exists('theme_label') ? theme_label($t) : $t); ?></option>
                    <?php } ?>
                    </optgroup>
                    <?php } ?>
                </select>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>

<p style="margin-top:20px;">
    <button type="submit" class="btn_submit" style="padding:8px 20px;">저장</button>
    <a href="./theme_preset_list.php" class="btn_admin">목록으로</a>
    <a href="<?php echo G5_URL; ?>/?preset=<?php echo (int)$row['pr_id']; ?>" target="_blank" class="btn_admin">미리보기 (새창)</a>
</p>

</form>

</div>

<script>
function bulkApply() {
    var t = document.getElementById('bulk-theme').value;
    var sels = document.querySelectorAll('select.slot-theme, select.group-theme');
    for (var i = 0; i < sels.length; i++) {
        for (var j = 0; j < sels[i].options.length; j++) {
            if (sels[i].options[j].value === t) {
                sels[i].selectedIndex = j;
                break;
            }
        }
    }
}
</script>

<?php include_once('./admin.tail.php'); ?>
