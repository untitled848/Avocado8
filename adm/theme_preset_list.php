<?php
$sub_menu = "110200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

if (!$is_admin)
    alert('관리자만 접근 가능합니다.');

$g5['title'] = '테마 프리셋 설정';
include_once('./admin.head.php');
$design_hub = 'preset';
$design_hub_tab = 'bundle';
include_once('./_design_hub_tabs.inc.php');

// 프리셋 목록
$presets = array();
$res = sql_query("SELECT * FROM `{$g5['theme_preset_table']}` ORDER BY pr_order ASC, pr_id ASC", false);
if ($res) {
    while ($r = sql_fetch_array($res)) { $presets[] = $r; }
}

$cf_preset_id   = isset($config['cf_preset_id']) ? (int)$config['cf_preset_id'] : 0;
$active_pr_id   = function_exists('get_active_preset_id') ? (int)get_active_preset_id() : 1;
$themes         = function_exists('theme_list') ? theme_list() : array('default');

// 디자인 프리셋 이름 맵 (main, world, content)
$design_names  = array();   // pd_id => name
$world_names   = array();
$content_names = array();
$r = sql_query("SELECT pd_id, pd_name FROM `{$g5['design_preset_table']}` ORDER BY pd_id", false);
if ($r) { while ($x = sql_fetch_array($r)) { $design_names[(int)$x['pd_id']] = $x['pd_name']; } }
if (!empty($g5['world_preset_table'])) {
    $r = sql_query("SELECT pd_id, pd_name FROM `{$g5['world_preset_table']}` ORDER BY pd_id", false);
    if ($r) { while ($x = sql_fetch_array($r)) { $world_names[(int)$x['pd_id']] = $x['pd_name']; } }
}
if (!empty($g5['content_preset_table'])) {
    $r = sql_query("SELECT pd_id, pd_name FROM `{$g5['content_preset_table']}` ORDER BY pd_id", false);
    if ($r) { while ($x = sql_fetch_array($r)) { $content_names[(int)$x['pd_id']] = $x['pd_name']; } }
}

// 시기 매핑 (이 프리셋을 사용하는 시기들)
$time_links = array(); // pr_id => array of ti rows
if (!empty($g5['time_table'])) {
    $col = sql_fetch("SHOW COLUMNS FROM `{$g5['time_table']}` LIKE 'ti_preset_id'", false);
    if (!empty($col['Field'])) {
        $r = sql_query("SELECT ti_id, ti_order, ti_name, ti_preset_id FROM `{$g5['time_table']}` WHERE ti_preset_id > 0 ORDER BY ti_order", false);
        if ($r) { while ($x = sql_fetch_array($r)) { $time_links[(int)$x['ti_preset_id']][] = $x; } }
    }
}

// 슬롯별 오버라이드 테마 (default 이 아닌 것만)
$slot_overrides = array(); // pr_id => array(slot => theme)
$r = sql_query("SELECT pr_id, ps_slot, ps_theme FROM `{$g5['theme_preset_slot_table']}` WHERE ps_theme <> '' AND ps_theme <> 'default'", false);
if ($r) { while ($x = sql_fetch_array($r)) { $slot_overrides[(int)$x['pr_id']][$x['ps_slot']] = $x['ps_theme']; } }
?>
<div class="tpl-wrap">

<div class="tpl-help">
    여기서 만든 세트는 <a href="./config_time.php">시기 관리</a>에서 시기별로 매핑하거나, 아래에서 <em>글로벌 기본</em>으로 지정할 수 있습니다.
    <br>
    <span style="display:inline-flex;align-items:center;gap:6px;margin-top:6px;font-size:12px;">
        <span class="tpl-mark active">활성</span> = 현재 사이트에 적용 중
        <span class="tpl-mark global" style="margin-left:8px;">글로벌</span> = 시기 매핑이 없을 때 기본 사용
        <span class="tpl-mark def" style="margin-left:8px;">기본</span> = 시스템 기본 (pr_id=1)
    </span>
</div>

<div class="tpl-card">
    <h3>글로벌 기본 프리셋</h3>
    <p class="desc">시기에 프리셋이 매핑되지 않은 경우 여기서 선택한 프리셋이 사용됩니다.</p>
    <form name="fcfpreset" action="./theme_preset_list_update.php" method="post" class="row-flex">
        <input type="hidden" name="action" value="set_global">
        <select name="cf_preset_id">
            <option value="0" <?php echo ($cf_preset_id === 0) ? 'selected' : ''; ?>>(없음 — pr_id=1 default 사용)</option>
        <?php foreach ($presets as $p) { ?>
            <option value="<?php echo (int)$p['pr_id']; ?>" <?php echo ($cf_preset_id === (int)$p['pr_id']) ? 'selected' : ''; ?>>
                #<?php echo (int)$p['pr_id']; ?> — <?php echo h($p['pr_name']); ?>
            </option>
        <?php } ?>
        </select>
        <button type="submit" class="ui-btn admin">저장</button>
    </form>
</div>

<h3 style="margin:24px 0 12px;font-size:16px;">프리셋 목록 (<?php echo count($presets); ?>개)</h3>

<div class="tpl-list">
<?php foreach ($presets as $p):
    $pid          = (int)$p['pr_id'];
    $is_active    = ($pid === $active_pr_id);
    $is_global    = ($pid === $cf_preset_id || ($cf_preset_id === 0 && $pid === 1));
    $is_default   = ($pid === 1);

    $pr_design    = (int)$p['pr_design'];
    $design_name  = isset($design_names[$pr_design]) ? $design_names[$pr_design] : ('#'.$pr_design);

    // world / content: 프리셋ID 또는 'skin:DIRNAME'
    $world_raw    = isset($p['pr_world_design'])   ? (string)$p['pr_world_design']   : '';
    $content_raw  = isset($p['pr_content_design']) ? (string)$p['pr_content_design'] : '';
    $world_pp     = function_exists('parse_design_value') ? parse_design_value($world_raw)   : array('kind'=>'preset','value'=>1);
    $content_pp   = function_exists('parse_design_value') ? parse_design_value($content_raw) : array('kind'=>'preset','value'=>1);

    $bgm_id       = isset($p['pr_bgm_id'])     ? trim((string)$p['pr_bgm_id'])   : '';
    $bgm_type     = isset($p['pr_bgm_type'])   ? (string)$p['pr_bgm_type']       : 'playlist';
    $bgm_vol      = isset($p['pr_bgm_volume']) ? (int)$p['pr_bgm_volume']        : 50;

    $linked_times = isset($time_links[$pid]) ? $time_links[$pid] : array();

    $cls = 'tpl-item';
    if ($is_active) $cls .= ' is-active';
    if ($is_global && !$is_active) $cls .= ' is-global';
?>
<div class="<?php echo $cls; ?>">
    <div class="tpl-item-head">
        <div>
            <div class="tpl-id">#<?php echo $pid; ?></div>
            <div class="tpl-name"><?php echo h($p['pr_name']); ?></div>
        </div>
        <div class="tpl-marks">
            <?php if ($is_active):  ?><span class="tpl-mark active" title="현재 사이트에 적용 중">활성</span><?php endif; ?>
            <?php if ($is_global):  ?><span class="tpl-mark global" title="글로벌 기본 프리셋">글로벌</span><?php endif; ?>
            <?php if ($is_default): ?><span class="tpl-mark def" title="시스템 기본 (pr_id=1)">기본</span><?php endif; ?>
        </div>
    </div>

    <dl class="tpl-fields">
        <dt>디자인</dt>
        <dd><a class="pd-link" href="./design_form.php?pd_id=<?php echo $pr_design; ?>">#<?php echo $pr_design; ?> <?php echo h($design_name); ?></a></dd>

        <dt>세계관</dt>
        <dd>
            <?php if ($world_pp['kind'] === 'skin'): ?>
                <span class="pd-link" style="background:#e8f0e8;color:#264;">[스킨] <code><?php echo h($world_pp['value']); ?></code></span>
            <?php else: $_pid = (int)$world_pp['value']; $_nm = isset($world_names[$_pid]) ? $world_names[$_pid] : ('#'.$_pid); ?>
                <a class="pd-link" href="./world_design_form.php?pd_id=<?php echo $_pid; ?>">[프리셋] #<?php echo $_pid; ?> <?php echo h($_nm); ?></a>
            <?php endif; ?>
        </dd>

        <dt>문서</dt>
        <dd>
            <?php if ($content_pp['kind'] === 'skin'): ?>
                <span class="pd-link" style="background:#e8f0e8;color:#264;">[스킨] <code><?php echo h($content_pp['value']); ?></code></span>
            <?php else: $_pid = (int)$content_pp['value']; $_nm = isset($content_names[$_pid]) ? $content_names[$_pid] : ('#'.$_pid); ?>
                <a class="pd-link" href="./content_design_form.php?pd_id=<?php echo $_pid; ?>">[프리셋] #<?php echo $_pid; ?> <?php echo h($_nm); ?></a>
            <?php endif; ?>
        </dd>

        <dt>BGM</dt>
        <dd>
            <?php if ($bgm_id !== ''): ?>
                <span class="bgm-on">●</span>
                <code style="font-size:11px;background:#f4f4f4;padding:2px 6px;border-radius:3px;"><?php echo h($bgm_type); ?>:<?php echo h(mb_substr($bgm_id, 0, 16)); ?><?php echo (mb_strlen($bgm_id) > 16) ? '…' : ''; ?></code>
                <span style="color:#999;font-size:11px;">vol <?php echo $bgm_vol; ?>%</span>
            <?php else: ?>
                <span class="bgm-off">○ 없음 (cf_bgm 폴백)</span>
            <?php endif; ?>
        </dd>

        <dt>사용 시기</dt>
        <dd>
            <?php if (!empty($linked_times)): ?>
                <?php foreach ($linked_times as $ti): ?>
                <a class="ti-link" href="./config_time.php" title="시기 관리로 이동">#<?php echo (int)$ti['ti_order']; ?> <?php echo h($ti['ti_name']); ?></a>
                <?php endforeach; ?>
            <?php elseif ($is_global): ?>
                <span class="none">— 시기 매핑 없음 (글로벌 기본으로 사용)</span>
            <?php else: ?>
                <span class="none">— 사용처 없음</span>
            <?php endif; ?>
        </dd>

        <dt>정렬</dt>
        <dd><span style="color:#999;font-size:11px;">order: <?php echo (int)$p['pr_order']; ?></span></dd>
    </dl>

    <?php
    $p_slot_overrides = isset($slot_overrides[$pid]) ? $slot_overrides[$pid] : array();
    if (!empty($p_slot_overrides)):
        // 슬롯 레이블 매핑
        $slot_label_map = array();
        if (!empty($GLOBALS['THEME_SLOT_MAP'])) {
            foreach ($GLOBALS['THEME_SLOT_MAP'] as $sk => $sd) {
                $slot_label_map[$sk] = isset($sd['label']) ? $sd['label'] : $sk;
            }
        }
        // 테마별로 그룹화
        $by_theme = array();
        foreach ($p_slot_overrides as $sk => $tv) {
            $by_theme[$tv][] = $sk;
        }
    ?>
    <div class="tpl-slot-overrides">
        <span class="tslot-title">오버라이드 중인 슬롯 (default 제외)</span>
        <?php foreach ($by_theme as $tname => $sk_list): ?>
            <span class="tslot-row">
                <span class="tslot-val"><?php echo h($tname); ?></span>
                <span class="tslot-key">← <?php
                    $labels = array();
                    foreach ($sk_list as $sk) { $labels[] = isset($slot_label_map[$sk]) ? $slot_label_map[$sk] : $sk; }
                    echo h(implode(', ', $labels));
                ?></span>
            </span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="tpl-actions">
        <a class="tpl-edit" href="./theme_preset_form.php?pr_id=<?php echo $pid; ?>">편집</a>
        <a class="tpl-preview" href="<?php echo G5_URL; ?>/?preset=<?php echo $pid; ?>" target="_blank" title="이 프리셋으로 사이트 미리보기">미리보기</a>
        <?php if (!$is_default): ?>
        <a class="tpl-del" href="./theme_preset_list_update.php?action=delete&amp;pr_id=<?php echo $pid; ?>" onclick="return confirm('이 프리셋을 정말 삭제하시겠습니까? 이 프리셋을 사용 중인 시기는 글로벌 기본으로 회귀합니다.');">삭제</a>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<div class="tpl-add">
    <form name="fnewpreset" action="./theme_preset_list_update.php" method="post">
        <input type="hidden" name="action" value="create">
        <input type="text" name="pr_name" placeholder="새 프리셋 이름 (예: 할로윈, 겨울이벤트)" required>
        <button type="submit" class="ui-btn admin">＋ 새 프리셋 추가</button>
    </form>
</div>

<div class="tpl-card" style="margin-top:24px;">
    <h3>설치된 테마 폴더 (<?php echo count($themes); ?>개)</h3>
    <p class="desc">각 프리셋의 슬롯 매핑에서 선택할 수 있는 테마 폴더입니다.</p>
    <div class="tpl-themes">
    <?php foreach ($themes as $t) { ?>
        <code><?php echo h($t); ?></code><?php echo ($t === 'default') ? ' <span style="color:#999;font-size:11px;">(가상 — 오버라이드 안 함)</span>' : ''; ?> &nbsp;
    <?php } ?>
    </div>
</div>

</div>

<?php include_once('./admin.tail.php'); ?>
