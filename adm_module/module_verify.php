<?php
/**
 * 모듈 점검 페이지
 *
 * 활성 모듈이 사용한다고 선언한 훅(연결 지점)이 현재 홈페이지에서
 * 실제로 존재하는지 확인합니다. 현재 적용된 테마/스킨이 모두 반영된
 * "현재 홈페이지 상태" 기준으로 검사합니다.
 *
 * GET module_verify.php           — 활성 모듈 전체 점검
 * GET module_verify.php?m=<name>  — 모듈 1개만 점검
 */
$sub_menu = '001100';
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

if (!function_exists('mdl_verify_module')) {
    alert('모듈 점검 시스템이 로드되지 않았습니다 (module/_registry.php 확인).');
}

$only = isset($_GET['m']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['m']) : '';

$g5['title'] = '모듈 점검';
include_once(G5_MDL_ADMIN_PATH.'/admin.head.php');

$state    = mdl_load_state();
$active   = isset($state['active']) ? $state['active'] : array();
$modules  = mdl_scan();

if ($only !== '') {
    $targets = isset($modules[$only]) ? array($only => $modules[$only]) : array();
} else {
    $targets = array();
    foreach ($active as $n) {
        if (!isset($modules[$n])) continue;
        // 독립 모듈(standalone): 다른 코드에 끼어들지 않는 자체 페이지/테이블만 가진 모듈은 점검 대상에서 제외
        if (!empty($modules[$n]['standalone'])) continue;
        $targets[$n] = $modules[$n];
    }
}

// 활성 프리셋 정보 (어떤 상태로 점검했는지 표기용)
$active_pr_id = function_exists('get_active_preset_id') ? (int)get_active_preset_id() : 1;
$active_pr    = function_exists('get_preset') ? get_preset($active_pr_id) : array();
$slot_diff    = array();
if (!empty($active_pr['slots'])) {
    foreach ($active_pr['slots'] as $sk => $tv) {
        if ($tv !== '' && $tv !== 'default') $slot_diff[$sk] = $tv;
    }
}
?>
<div class="mv-wrap">

<div class="mv-actions">
    <a href="<?php echo G5_MDL_ADMIN_URL; ?>/module_list.php">← 모듈 목록</a>
    <?php if ($only !== ''): ?>
    <a href="<?php echo G5_MDL_ADMIN_URL; ?>/module_verify.php">활성 모듈 전체 점검</a>
    <?php endif; ?>
</div>

<div class="mv-meta">
    <b>점검 기준</b>: 현재 활성 프리셋 <code>#<?php echo $active_pr_id; ?> <?php echo h(isset($active_pr['pr_name']) ? $active_pr['pr_name'] : ''); ?></code> 의 테마/스킨 설정이 모두 반영된 "현재 홈페이지 상태" 입니다.
    각 모듈이 사용한다고 선언한 <strong>연결 지점(훅)</strong> 이 실제로 코어/스킨/테마 어딘가에 존재하는지 확인합니다.
    <?php if (!empty($slot_diff)): ?>
    <br>
    <span style="color:#7a5500;">▶ 기본값 대신 다른 스킨/테마로 바꿔서 쓰는 슬롯:</span>
    <?php foreach ($slot_diff as $sk => $tv): ?>
    <code><?php echo h($sk); ?>→<?php echo h($tv); ?></code>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (empty($targets)): ?>
<div class="mv-empty">
    <?php if ($only !== ''): ?>
    모듈 <code><?php echo h($only); ?></code> 를 찾을 수 없습니다.
    <?php else: ?>
    켜진 모듈이 없습니다.
    <?php endif; ?>
</div>
<?php else: ?>
<?php
$total_ok = 0; $total_bad = 0;
$reports = array();
foreach ($targets as $mn => $meta) {
    $r = mdl_verify_module($mn);
    $reports[$mn] = $r;
    if ($r['ok']) $total_ok++; else $total_bad++;
}
?>

<div class="mv-meta" style="background:#fafafa;border-color:#ddd;color:#666;">
    점검 완료 — 정상 <strong style="color:#0a7;"><?php echo $total_ok; ?></strong>개,
    문제 있음 <strong style="color:#c33;"><?php echo $total_bad; ?></strong>개.
</div>

<?php
$mv_collisions = function_exists('mdl_admin_menu_collect_code_collisions')
    ? mdl_admin_menu_collect_code_collisions()
    : array();
if (!empty($mv_collisions)):
?>
<div class="mv-meta" style="background:#fff5f5;border-color:#e8a0a0;color:#842;">
    <strong>어드민 메뉴 코드 충돌</strong> (동일 6자리가 여러 모듈에 있음 — 나중에 등록된 항목은 사이드바에서 스킵됨):
    <ul style="margin:6px 0 0 1.2em;">
    <?php foreach ($mv_collisions as $c): ?>
        <li><code><?php echo h($c['code']); ?></code>
            — <?php echo h($c['module']); ?> «<?php echo h($c['label']); ?>»
            (먼저: <?php echo h($c['first']); ?>)</li>
    <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php foreach ($reports as $mn => $r): $meta = $targets[$mn]; ?>
<div class="mv-card <?php echo $r['ok'] ? 'ok' : 'bad'; ?>">
    <h3>
        <?php echo h(isset($meta['title']) ? $meta['title'] : $mn); ?>
        <span class="mv-mod-id">(<?php echo h($mn); ?>)</span>
        <?php
        $mv_menu_group = '';
        if (function_exists('mdl_admin_menus_has_auto') && mdl_admin_menus_has_auto(isset($meta['admin_menus']) ? $meta['admin_menus'] : null)) {
            $mv_menu_group = function_exists('mdl_admin_menu_get_group') ? mdl_admin_menu_get_group($mn) : '';
            if ($mv_menu_group === null) {
                $mv_menu_group = '';
            }
        } elseif (!empty($meta['admin_menus']) && is_array($meta['admin_menus'])) {
            foreach (array_keys($meta['admin_menus']) as $_gk) {
                if (preg_match('/^[0-9]{3}$/', (string)$_gk)) {
                    $mv_menu_group = (string)$_gk;
                    break;
                }
            }
        }
        if ($mv_menu_group !== ''): ?>
        <span class="mv-status" style="background:#eef5ff;color:#245;border:1px solid #bcd;">메뉴 그룹 <?php echo h($mv_menu_group); ?><?php echo (isset($meta['admin_menus']['auto'])) ? ' (auto)' : ''; ?></span>
        <?php endif; ?>
        <?php if ($r['ok']): ?>
        <span class="mv-status ok">✓ 정상 (일반 훅 <?php echo (int)$r['summary']['total']; ?>개)</span>
        <?php else: ?>
        <span class="mv-status bad">⚠ 누락 <?php echo (int)$r['summary']['missing']; ?> / <?php echo (int)$r['summary']['total']; ?>개</span>
        <?php endif; ?>
        <?php if (!empty($r['summary']['bbs_total'])): ?>
        <span class="mv-status" style="background:#fff5e0;color:#7a5500;border:1px solid #f0d090;">📋 게시판 훅 <?php echo (int)$r['summary']['bbs_total']; ?>개 (게시판별 확인)</span>
        <?php endif; ?>
    </h3>

    <?php if (empty($r['uses']) && empty($r['uses_bbs'])): ?>
    <div style="color:#888;font-size:12px;">선언된 연결 지점이 없습니다 (<code>_hook_list.php</code> 또는 <code>requires_hooks</code> 미정의).</div>
    <?php else: ?>

    <?php if (!empty($r['uses'])): ?>
    <table class="mv-table">
        <thead>
            <tr>
                <th class="mark">상태</th>
                <th>일반 훅 (이 모듈이 연결되는 지점)</th>
                <th class="cnt">존재하는 위치 수</th>
                <th>샘플 위치 (파일:줄)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($r['uses'] as $hk => $info): ?>
            <tr>
                <td class="mark"><?php echo $info['found'] ? '<span class="ok">✓</span>' : '<span class="bad">✗</span>'; ?></td>
                <td class="hk"><?php echo h($hk); ?></td>
                <td class="cnt"><?php echo (int)$info['count']; ?></td>
                <td class="smp">
                    <?php if (!empty($info['sample'])): ?>
                        <?php foreach ($info['sample'] as $_si => $_sv): ?><?php if ($_si > 0) echo '<br>'; ?><?php echo h($_sv); ?><?php endforeach; ?>
                    <?php else: ?>
                        <span style="color:#c33;">존재하지 않음</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if (!empty($r['uses_bbs'])): ?>
    <div class="mv-bbs-box">
        <div class="mv-bbs-head">
            ⓘ 아래는 <strong>게시판 스킨에서 사용되는 훅</strong> 입니다. 게시판마다 사용하는 스킨이 다르므로 모듈 점검 페이지에서 일괄 판정하지 않고,
            <strong>각 게시판의 [수정] 페이지 → "게시판별 모듈 on/off" 섹션</strong> 에서 스킨에 맞춰 자동 확인됩니다.
            아래 "사용중인 게시판" 목록을 클릭해 게시판 설정 페이지로 바로 이동할 수 있습니다.
        </div>
        <table class="mv-table">
            <thead>
                <tr>
                    <th>게시판 훅</th>
                    <th class="cnt">기본 스킨에서<br>존재 횟수</th>
                    <th>샘플 위치 — 참고용</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($r['uses_bbs'] as $hk => $info): ?>
                <tr>
                    <td class="hk"><?php echo h($hk); ?></td>
                    <td class="cnt"><?php echo (int)$info['count']; ?></td>
                    <td class="smp">
                        <?php if (!empty($info['sample'])): ?>
                            <?php foreach ($info['sample'] as $_si => $_sv): ?><?php if ($_si > 0) echo '<br>'; ?><?php echo h($_sv); ?><?php endforeach; ?>
                        <?php else: ?>
                            <span style="color:#888;">기본 스킨에는 없음 — 다른 스킨에서 제공할 수 있음</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php
            $boards_used = function_exists('mdl_module_used_in_boards') ? mdl_module_used_in_boards($mn) : array();
        ?>
        <div class="mv-boards">
            <strong>📋 이 모듈을 사용중인 게시판 (<?php echo count($boards_used); ?>개)</strong>
            <?php if (empty($boards_used)): ?>
            <div class="mv-boards-empty">사용중인 게시판이 없습니다. 게시판 [수정] 페이지에서 켜야 합니다.</div>
            <?php else: ?>
            <ul class="mv-boards-list">
                <?php foreach ($boards_used as $b): ?>
                <li>
                    <a href="<?php echo G5_ADMIN_URL; ?>/board_form.php?w=u&amp;bo_table=<?php echo urlencode($b['bo_table']); ?>" title="이 게시판의 설정 페이지로 이동">
                        <strong><?php echo h($b['bo_subject']); ?></strong>
                        <span class="bo-table">[<?php echo h($b['bo_table']); ?>]</span>
                    </a>
                    <span class="bo-skin">스킨: <code><?php echo h($b['bo_skin'] !== '' ? $b['bo_skin'] : 'basic'); ?></code></span>
                    <?php if (!empty($b['scopes']) && $b['scopes'] !== array('')):
                        $_lbls = array();
                        foreach ($b['scopes'] as $_sc) { if ($_sc !== '') $_lbls[] = mdl_scope_label($_sc); }
                    ?>
                    <span class="bo-scopes">사용 위치: <?php echo h(implode(' · ', $_lbls)); ?></span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

    <?php if (!empty($r['fires'])): ?>
    <div class="mv-fires">
        ▶ 이 모듈이 새로 만드는 연결 지점 (다른 모듈이 사용 가능): <?php foreach ($r['fires'] as $f): ?><code style="background:#f4f4f4;padding:1px 6px;border-radius:3px;"><?php echo h($f); ?></code> <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

</div>

<?php include_once(G5_MDL_ADMIN_PATH.'/admin.tail.php'); ?>
