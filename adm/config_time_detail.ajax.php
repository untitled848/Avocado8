<?php
$sub_menu = "110100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

if (!$is_admin) {
    echo '<div style="color:#a00;">권한 없음</div>';
    exit;
}

$ti_order = (int)($_GET['ti_order'] ?? 0);
if ($ti_order <= 0) {
    echo '<div style="color:#999;">잘못된 시기</div>';
    exit;
}

// 시기 정보
$ti = sql_fetch("SELECT * FROM `{$g5['time_table']}` WHERE ti_order = '{$ti_order}'", false);
if (empty($ti)) { echo '<div style="color:#999;">존재하지 않음</div>'; exit; }

$ti_preset_id = isset($ti['ti_preset_id']) ? (int)$ti['ti_preset_id'] : 0;
$cf_preset_id = isset($config['cf_preset_id']) ? (int)$config['cf_preset_id'] : 0;
$effective_pr = $ti_preset_id > 0 ? $ti_preset_id : ($cf_preset_id > 0 ? $cf_preset_id : 1);

// 프리셋 정보
$pr = sql_fetch("SELECT * FROM `{$g5['theme_preset_table']}` WHERE pr_id = '{$effective_pr}'", false);

// 상점 진열 아이템 (sh_time 매칭)
$shop_items = array();
if (!empty($g5['shop_table']) && !empty($g5['item_table'])) {
    $where = function_exists('shop_time_sql_where') ? shop_time_sql_where('sh_time', $ti_order) : '';
    $sql = "SELECT s.sh_id, s.it_id, i.it_name, s.sh_time FROM `{$g5['shop_table']}` s LEFT JOIN `{$g5['item_table']}` i ON s.it_id = i.it_id WHERE 1=1 {$where} ORDER BY s.sh_order LIMIT 50";
    $r = sql_query($sql, false);
    if ($r) { while ($x = sql_fetch_array($r)) { $shop_items[] = $x; } }
}

// 메인슬라이드 (bn_time 매칭)
$banners = array();
if (!empty($g5['banner_table'])) {
    $where = function_exists('shop_time_sql_where') ? shop_time_sql_where('bn_time', $ti_order) : '';
    $sql = "SELECT bn_id, bn_alt, bn_img, bn_time FROM `{$g5['banner_table']}` WHERE 1=1 {$where} ORDER BY bn_order, bn_id DESC LIMIT 30";
    $r = sql_query($sql, false);
    if ($r) { while ($x = sql_fetch_array($r)) { $banners[] = $x; } }
}

// 인트로 (bn_time 매칭) — 메인슬라이드와 동일 패턴
$intros = array();
if (!empty($g5['intro_table'])) {
    $where = function_exists('shop_time_sql_where') ? shop_time_sql_where('bn_time', $ti_order) : '';
    $sql = "SELECT bn_id, bn_alt, bn_img, bn_time FROM `{$g5['intro_table']}` WHERE 1=1 {$where} ORDER BY bn_order, bn_id DESC LIMIT 30";
    $r = sql_query($sql, false);
    if ($r) { while ($x = sql_fetch_array($r)) { $intros[] = $x; } }
}

// 공지사항 (no_time 매칭, 사용 중인 것만)
$notices = array();
if (!empty($g5['notice_table'])) {
    $where = function_exists('shop_time_sql_where') ? shop_time_sql_where('no_time', $ti_order) : '';
    $sql = "SELECT no_id, no_subject, no_time, no_pinned, no_use, no_datetime FROM `{$g5['notice_table']}` WHERE no_use = 1 {$where} ORDER BY no_pinned DESC, no_order ASC, no_datetime DESC, no_id DESC LIMIT 30";
    $r = sql_query($sql, false);
    if ($r) { while ($x = sql_fetch_array($r)) { $notices[] = $x; } }
}

// Wave10: 시기별 활성 side / class / NPC
require_once G5_EXTEND_PATH.'/02_side_class_time.lib.php';
$active_sides   = function_exists('get_sides_for_time')   ? get_sides_for_time($ti_order, 0)   : null;
$active_classes = function_exists('get_classes_for_time') ? get_classes_for_time($ti_order, 0) : null;
$active_npcs    = array();
if (!empty($g5['npc_time_table']) && !empty($g5['character_table'])) {
    $_ti_id = (int)$ti['ti_id'];
    $_any   = sql_fetch("SELECT 1 AS x FROM `{$g5['npc_time_table']}` LIMIT 1", false);
    if (!empty($_any['x'])) {
        $_r = sql_query("SELECT c.ch_id, c.ch_name FROM `{$g5['npc_time_table']}` n
                         JOIN `{$g5['character_table']}` c ON c.ch_id = n.ch_id
                         WHERE n.ti_id = '{$_ti_id}' AND c.ch_type='npc' AND c.ch_state='승인'
                         ORDER BY c.ch_id ASC", false);
        if ($_r) while ($_x = sql_fetch_array($_r)) $active_npcs[] = $_x;
    } else {
        // 매핑 미사용 → 모든 승인 NPC 노출 (lib.php 와 동일 fallback)
        $_r = sql_query("SELECT ch_id, ch_name FROM `{$g5['character_table']}` WHERE ch_type='npc' AND ch_state='승인' ORDER BY ch_id ASC", false);
        if ($_r) while ($_x = sql_fetch_array($_r)) $active_npcs[] = $_x;
    }
}

// 디자인/세계관/문서 이름
$design_name = $world_name = $content_name = '';
$world_pp = $content_pp = array('kind'=>'preset','value'=>1);
if (!empty($pr)) {
    $pd_id = (int)$pr['pr_design'];
    $d = sql_fetch("SELECT pd_name FROM `{$g5['design_preset_table']}` WHERE pd_id='{$pd_id}'", false);
    $design_name = !empty($d['pd_name']) ? $d['pd_name'] : ('#'.$pd_id);

    if (function_exists('parse_design_value')) {
        $world_pp   = parse_design_value(isset($pr['pr_world_design'])   ? $pr['pr_world_design']   : '');
        $content_pp = parse_design_value(isset($pr['pr_content_design']) ? $pr['pr_content_design'] : '');
    }
    if ($world_pp['kind'] === 'preset' && !empty($g5['world_preset_table'])) {
        $_wpid = (int)$world_pp['value'];
        $w = sql_fetch("SELECT pd_name FROM `{$g5['world_preset_table']}` WHERE pd_id='{$_wpid}'", false);
        $world_name = !empty($w['pd_name']) ? $w['pd_name'] : ('#'.$_wpid);
    }
    if ($content_pp['kind'] === 'preset' && !empty($g5['content_preset_table'])) {
        $_cpid = (int)$content_pp['value'];
        $c = sql_fetch("SELECT pd_name FROM `{$g5['content_preset_table']}` WHERE pd_id='{$_cpid}'", false);
        $content_name = !empty($c['pd_name']) ? $c['pd_name'] : ('#'.$_cpid);
    }
}

// 슬롯 오버라이드
$slot_overrides = array();
if (!empty($g5['theme_preset_slot_table']) && !empty($pr)) {
    $r = sql_query("SELECT ps_slot, ps_theme FROM `{$g5['theme_preset_slot_table']}` WHERE pr_id='{$effective_pr}' AND ps_theme<>'' AND ps_theme<>'default'", false);
    if ($r) { while ($x = sql_fetch_array($r)) { $slot_overrides[$x['ps_slot']] = $x['ps_theme']; } }
}
?>
<?php
// 프리셋 BGM 정보
$bgm_id   = isset($pr['pr_bgm_id'])     ? trim((string)$pr['pr_bgm_id'])   : '';
$bgm_type = isset($pr['pr_bgm_type'])   ? (string)$pr['pr_bgm_type']       : 'playlist';
$bgm_vol  = isset($pr['pr_bgm_volume']) ? (int)$pr['pr_bgm_volume']        : 50;

// 활성 프리셋 여부
$is_active_pr = function_exists('get_active_preset_id') ? ((int)get_active_preset_id() === (int)$effective_pr) : false;
$is_global    = (!$ti_preset_id || $ti_preset_id === 0) && ($cf_preset_id > 0 ? ($effective_pr === $cf_preset_id) : ($effective_pr === 1));
$is_default   = ((int)$effective_pr === 1);

// 슬롯 레이블 매핑
$slot_label_map = array();
if (!empty($GLOBALS['THEME_SLOT_MAP'])) {
    foreach ($GLOBALS['THEME_SLOT_MAP'] as $sk => $sd) {
        $slot_label_map[$sk] = isset($sd['label']) ? $sd['label'] : $sk;
    }
}
// 슬롯 오버라이드를 테마별로 그룹화
$by_theme = array();
foreach ($slot_overrides as $sk => $tv) {
    $by_theme[$tv][] = $sk;
}
?>

<div class="tdetail">

<div class="sect">
    <h4>적용 테마 프리셋</h4>
    <?php if (!empty($pr)): ?>
    <div class="preset-card">
        <div>
            <span class="pid">#<?php echo (int)$pr['pr_id']; ?></span>
            <span class="pname"><?php echo h($pr['pr_name']); ?></span>
            <?php if ($is_active_pr): ?><span class="pmark">활성</span><?php endif; ?>
            <?php if ($ti_preset_id === 0): ?><span class="pmark global">글로벌 폴백</span><?php endif; ?>
            <?php if ($is_default): ?><span class="pmark def">기본</span><?php endif; ?>
        </div>

        <dl>
            <dt>디자인</dt>
            <dd><a class="pd-link" href="./design_form.php?pd_id=<?php echo (int)$pr['pr_design']; ?>">#<?php echo (int)$pr['pr_design']; ?> <?php echo h($design_name); ?></a></dd>

            <?php if (isset($pr['pr_world_design'])): ?>
            <dt>세계관</dt>
            <dd>
                <?php if ($world_pp['kind'] === 'skin'): ?>
                    <span class="pd-link" style="background:#e8f0e8;color:#264;">[스킨] <code><?php echo h($world_pp['value']); ?></code></span>
                <?php else: $_p = (int)$world_pp['value']; ?>
                    <a class="pd-link" href="./world_design_form.php?pd_id=<?php echo $_p; ?>">[프리셋] #<?php echo $_p; ?> <?php echo h($world_name); ?></a>
                <?php endif; ?>
            </dd>
            <?php endif; ?>

            <?php if (isset($pr['pr_content_design'])): ?>
            <dt>문서</dt>
            <dd>
                <?php if ($content_pp['kind'] === 'skin'): ?>
                    <span class="pd-link" style="background:#e8f0e8;color:#264;">[스킨] <code><?php echo h($content_pp['value']); ?></code></span>
                <?php else: $_p = (int)$content_pp['value']; ?>
                    <a class="pd-link" href="./content_design_form.php?pd_id=<?php echo $_p; ?>">[프리셋] #<?php echo $_p; ?> <?php echo h($content_name); ?></a>
                <?php endif; ?>
            </dd>
            <?php endif; ?>

            <dt>BGM</dt>
            <dd>
                <?php if ($bgm_id !== ''): ?>
                    <span class="bgm-on">●</span>
                    <span class="bgm-code"><?php echo h($bgm_type); ?>:<?php echo h(mb_substr($bgm_id, 0, 24)); ?><?php echo (mb_strlen($bgm_id) > 24) ? '…' : ''; ?></span>
                    <span style="color:#999;font-size:11px;">vol <?php echo $bgm_vol; ?>%</span>
                <?php else: ?>
                    <span class="bgm-off">○ 없음</span>
                    <span style="color:#999;font-size:11px;">— cf_bgm 폴백</span>
                <?php endif; ?>
            </dd>

            <dt>매핑 출처</dt>
            <dd>
                <?php if ($ti_preset_id > 0): ?>
                    <span style="color:#7a5500;">시기 #<?php echo (int)$ti_order; ?> 의 <code>ti_preset_id</code> = <?php echo (int)$ti_preset_id; ?></span>
                <?php elseif ($cf_preset_id > 0): ?>
                    <span style="color:#06c;">글로벌 <code>cf_preset_id</code> = <?php echo (int)$cf_preset_id; ?></span>
                <?php else: ?>
                    <span style="color:#999;">시스템 기본 (pr_id=1)</span>
                <?php endif; ?>
            </dd>
        </dl>

        <?php if (!empty($by_theme)): ?>
        <div class="slot-overrides">
            <span class="stitle">오버라이드 중인 슬롯 (default 제외)</span>
            <?php foreach ($by_theme as $tname => $sk_list): ?>
                <span class="srow">
                    <span class="sval"><?php echo h($tname); ?></span>
                    <span>← <?php
                        $labels = array();
                        foreach ($sk_list as $sk) { $labels[] = isset($slot_label_map[$sk]) ? $slot_label_map[$sk] : $sk; }
                        echo h(implode(', ', $labels));
                    ?></span>
                </span>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="slot-overrides" style="color:#999;">
            <span class="stitle">슬롯 오버라이드</span>
            모든 슬롯이 default — 루트 파일을 그대로 사용합니다.
        </div>
        <?php endif; ?>

        <div class="preset-actions">
            <a class="edit" href="./theme_preset_form.php?pr_id=<?php echo (int)$pr['pr_id']; ?>">프리셋 편집</a>
            <a class="preview" href="<?php echo G5_URL; ?>/?preset=<?php echo (int)$pr['pr_id']; ?>" target="_blank" rel="noopener">미리보기</a>
        </div>
    </div>
    <?php else: ?>
    <span class="empty">프리셋 정보를 찾을 수 없음</span>
    <?php endif; ?>
</div>

<div class="sect">
    <h4>이 시기의 상점 진열 아이템 (<?php echo count($shop_items); ?>건<?php if (count($shop_items)===50) echo ' / 최대 50'; ?>)</h4>
    <?php if (!empty($shop_items)): ?>
    <div class="item-list">
        <?php foreach ($shop_items as $si):
            $is_all = ($si['sh_time'] === '' || strtolower($si['sh_time']) === 'all');
        ?>
        <a href="./shop_form.php?w=u&amp;sh_id=<?php echo (int)$si['sh_id']; ?>" title="<?php echo h($si['it_id']); ?>">
            <?php echo h($si['it_name'] ?: $si['it_id']); ?>
            <?php if ($is_all): ?><span style="color:#999;">(전체)</span><?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <span class="empty">없음</span>
    <?php endif; ?>
</div>

<div class="sect">
    <h4>이 시기의 메인슬라이드 (<?php echo count($banners); ?>건)</h4>
    <?php if (!empty($banners)): ?>
    <div class="banner-list">
        <?php foreach ($banners as $bn): ?>
        <a href="./banner_form.php?w=u&amp;bn_id=<?php echo (int)$bn['bn_id']; ?>" title="<?php echo h($bn['bn_alt']); ?>">
            <?php if (!empty($bn['bn_img'])): ?><img src="<?php echo h($bn['bn_img']); ?>" alt=""><?php else: ?><span style="font-size:11px;padding:6px;display:inline-block;"><?php echo h($bn['bn_alt'] ?: '#'.(int)$bn['bn_id']); ?></span><?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <span class="empty">없음</span>
    <?php endif; ?>
</div>

<div class="sect">
    <h4>이 시기의 인트로 (<?php echo count($intros); ?>건)</h4>
    <?php if (!empty($intros)): ?>
    <div class="banner-list">
        <?php foreach ($intros as $bn): ?>
        <a href="./intro_form.php?w=u&amp;bn_id=<?php echo (int)$bn['bn_id']; ?>" title="<?php echo h($bn['bn_alt']); ?>">
            <?php if (!empty($bn['bn_img'])): ?><img src="<?php echo h($bn['bn_img']); ?>" alt=""><?php else: ?><span style="font-size:11px;padding:6px;display:inline-block;"><?php echo h($bn['bn_alt'] ?: '#'.(int)$bn['bn_id']); ?></span><?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <span class="empty">없음</span>
    <?php endif; ?>
</div>

<div class="sect">
    <h4>이 시기의 공지사항 (<?php echo count($notices); ?>건<?php if (count($notices)===30) echo ' / 최대 30'; ?>)</h4>
    <?php if (!empty($notices)): ?>
    <div class="item-list">
        <?php foreach ($notices as $no):
            $is_all = ($no['no_time'] === '' || strtolower($no['no_time']) === 'all');
        ?>
        <a href="./notice_form.php?w=u&amp;no_id=<?php echo (int)$no['no_id']; ?>" title="<?php echo h($no['no_datetime']); ?>">
            <?php if (!empty($no['no_pinned'])): ?><span style="color:#c33;font-weight:bold;">★</span> <?php endif; ?>
            <?php echo h($no['no_subject'] ?: '#'.(int)$no['no_id']); ?>
            <?php if ($is_all): ?><span style="color:#999;">(전체)</span><?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <span class="empty">없음</span>
    <?php endif; ?>
</div>

<div class="sect">
    <h4>이 시기의 활성 <?php echo h($config['cf_side_title'] ?: '세력'); ?> (<?php echo is_array($active_sides) ? count($active_sides) : '폴백'; ?>)</h4>
    <?php if (is_array($active_sides) && count($active_sides) > 0): ?>
    <div class="item-list">
        <?php foreach ($active_sides as $_si): ?>
        <a href="./side_form.php?w=u&amp;si_id=<?php echo (int)$_si['si_id']; ?>">
            #<?php echo (int)$_si['si_id']; ?> <?php echo h($_si['si_name']); ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php elseif (is_array($active_sides)): ?>
    <span class="empty">매핑 0개 — 이 시기에는 어떤 세력도 노출되지 않습니다.</span>
    <?php else: ?>
    <span class="empty">매핑 미사용 — 모든 세력이 노출됩니다.</span>
    <?php endif; ?>
</div>

<div class="sect">
    <h4>이 시기의 활성 <?php echo h($config['cf_class_title'] ?: '종족'); ?> (<?php echo is_array($active_classes) ? count($active_classes) : '폴백'; ?>)</h4>
    <?php if (is_array($active_classes) && count($active_classes) > 0): ?>
    <div class="item-list">
        <?php foreach ($active_classes as $_cl): ?>
        <a href="./class_form.php?w=u&amp;cl_id=<?php echo (int)$_cl['cl_id']; ?>">
            #<?php echo (int)$_cl['cl_id']; ?> <?php echo h($_cl['cl_name']); ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php elseif (is_array($active_classes)): ?>
    <span class="empty">매핑 0개 — 이 시기에는 어떤 종족도 노출되지 않습니다.</span>
    <?php else: ?>
    <span class="empty">매핑 미사용 — 모든 종족이 노출됩니다.</span>
    <?php endif; ?>
</div>

<div class="sect">
    <h4>이 시기의 활성 NPC (<?php echo count($active_npcs); ?>건)</h4>
    <?php if (!empty($active_npcs)): ?>
    <div class="item-list">
        <?php foreach ($active_npcs as $_nc): ?>
        <a href="./character_form.php?w=u&amp;ch_id=<?php echo (int)$_nc['ch_id']; ?>">
            #<?php echo (int)$_nc['ch_id']; ?> <?php echo h($_nc['ch_name']); ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <span class="empty">없음</span>
    <?php endif; ?>
</div>

</div>
