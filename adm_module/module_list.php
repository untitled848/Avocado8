<?php
/**
 * 모듈 목록 — 어드민
 *
 * 위치: adm_module/ (모듈 관리 전용 어드민 영역)
 *  → 모든 자기참조 URL 은 G5_MDL_ADMIN_URL 사용.
 *  → admin.head.php / admin.tail.php 는 G5_MDL_ADMIN_PATH 에서 include.
 */
$sub_menu = '001100';
include_once('./_common.php');

auth_check($auth[$sub_menu], "r");

if (!function_exists('mdl_scan')) {
    alert('모듈 시스템이 로드되지 않았습니다. module/_loader.php 가 존재하는지 확인하세요.');
}

$action = $_GET['action'] ?? '';
$name   = isset($_GET['m']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['m']) : '';

// 기본 제공 모듈 — 언인스톨 및 폴더 삭제 불가
define('MDL_CORE_MODULES', array('bbs_action_combine', 'bbs_action_search', 'bbs_dice', 'bbs_item_use'));

if ($action && $name) {
    if ($action === 'activate') {
        $r = mdl_activate($name);
        if ($r === true) {
            goto_url(G5_MDL_ADMIN_URL.'/module_list.php?msg='.urlencode($name.' 모듈을 켰습니다'));
        } else {
            alert($r ? $r : '활성화 실패', G5_MDL_ADMIN_URL.'/module_list.php');
        }
    } else if ($action === 'deactivate') {
        mdl_deactivate($name, false);
        goto_url(G5_MDL_ADMIN_URL.'/module_list.php?msg='.urlencode($name.' 모듈을 껐습니다'));
    } else if ($action === 'uninstall') {
        if (in_array($name, MDL_CORE_MODULES, true)) {
            alert('기본 제공 모듈은 데이터 제거를 할 수 없습니다.', G5_MDL_ADMIN_URL.'/module_list.php');
        }
        mdl_deactivate($name, true);
        goto_url(G5_MDL_ADMIN_URL.'/module_list.php?msg='.urlencode($name.' 모듈을 제거했습니다 (데이터 포함)'));
    } else if ($action === 'reinstall') {
        // 재설치: 활성 상태와 무관하게 install.php 를 강제로 다시 실행하고 버전도 최신으로 갱신.
        // - 설치 시 계정 권한이 부족해서 실패한 도관 모듈이나, install.php 를 고친 뒤 재적용해야 하는 경우 사용.
        // - DB 테이블/컬럼 생성은 IF NOT EXISTS / mdl_db_add_column 으로 멱등하므로 안전.
        $modules = mdl_scan();
        if (!isset($modules[$name])) {
            alert('존재하지 않는 모듈입니다.', G5_MDL_ADMIN_URL.'/module_list.php');
        }
        // bootstrap 을 부르지 않는다. 별칭은 install/lib.php. HOOK §I-3 · §I-8.
        $r = function_exists('mdl_run_install') ? mdl_run_install($name) : 'mdl_run_install 없음';
        if ($r !== true) {
            alert($r, G5_MDL_ADMIN_URL.'/module_list.php');
        }
        // 상태 버전 업데이트 (이미 활성이면 버전만 갱신, 아니면 활성 아닌 상태 유지)
        $state2 = mdl_load_state();
        $state2['versions'][$name] = $modules[$name]['version'];
        mdl_save_state($state2);
        goto_url(G5_MDL_ADMIN_URL.'/module_list.php?msg='.urlencode($name.' 모듈을 재설치했습니다 (install.php 재실행)'));
    }
}

$g5['title'] = '모듈 목록';
include_once(G5_MDL_ADMIN_PATH.'/admin.head.php');

$modules = mdl_scan();
$state   = mdl_load_state();
$active  = isset($state['active']) ? $state['active'] : array();

// 실제 폴더가 없는 고아(orphan) active 항목 탐지
$orphans = array();
foreach ($active as $n) {
    if (!isset($modules[$n])) $orphans[] = $n;
}

// action: 고아 항목 일괄 정리
if ($action === 'cleanup_orphans') {
    $cleaned = 0;
    foreach ($orphans as $n) {
        mdl_deactivate($n, false);
        $cleaned++;
    }
    goto_url(G5_MDL_ADMIN_URL.'/module_list.php?msg='.urlencode('상태 불일치 '.$cleaned.'개 항목을 정리했습니다.'));
}

// 카테고리 분류: 독립 모듈(standalone) > 게시판 모듈(board_scope) > 일반 모듈
$cat_board  = array();
$cat_normal = array();
$cat_alone  = array();
foreach ($modules as $mn => $meta) {
    if (!empty($meta['standalone'])) $cat_alone[$mn] = $meta;
    else if (!empty($meta['board_scope'])) $cat_board[$mn] = $meta;
    else $cat_normal[$mn] = $meta;
}

// 실제 활성 수 = active 중 폴더가 있는 것만
$active_real_cnt = count($active) - count($orphans);
$total_cnt  = count($modules);
?>
<?php if (isset($_GET['msg'])) { ?>
<div class="ml-msg">✓ <?php echo h($_GET['msg']); ?></div>
<?php } ?>

<div class="ml-toolbar">
    <div class="ml-stat">
        등록된 모듈 <strong id="ml-stat-total"><?php echo $total_cnt; ?></strong>개 ·
        켜진 모듈 <strong style="color:#0a7;"><?php echo $active_real_cnt; ?></strong>개
        <span id="ml-stat-shown" class="ml-stat-shown" style="display:none;"> · 표시 <strong></strong>개</span>
    </div>
    <div class="ml-spacer"></div>
    <input type="search" id="ml-search" class="ml-search" placeholder="이름 / 설명 / id 검색..." autocomplete="off">
    <div class="ml-filter" role="group" aria-label="상태 필터">
        <label><input type="radio" name="ml-state" value="all" checked> 전체</label>
        <label><input type="radio" name="ml-state" value="on"> 켜짐</label>
        <label><input type="radio" name="ml-state" value="off"> 꺼짐</label>
    </div>
    <a href="<?php echo G5_MDL_ADMIN_URL; ?>/module_verify.php" class="ml-btn-verify" title="활성 모듈이 사용한다고 선언한 훅이 실제로 존재하는지 검사합니다">
        모듈 점검
    </a>
</div>

<?php if (!empty($orphans)): ?>
<div style="background:#fff0f0;border:1px solid #f3b8b8;border-left:4px solid #c33;padding:12px 16px;border-radius:4px;margin:0 0 14px;font-size:12px;line-height:1.7;">
    <strong style="color:#c33;">상태 불일치 감지</strong> —
    켜진 것으로 기록되어 있지만 모듈 폴더가 없는 항목이 <?php echo count($orphans); ?>개 있습니다:<br>
    <span style="font-family:monospace;color:#666;"><?php echo h(implode(', ', $orphans)); ?></span><br>
    <a href="?action=cleanup_orphans" style="color:#c33;font-weight:bold;"
       onclick="return confirm('상태 불일치 항목 <?php echo count($orphans); ?>개를 활성 목록에서 제거합니다. 진행할까요?');">지금 정리하기</a>
</div>
<?php endif; ?>

<?php if ($is_admin): ?>
<form method="post" enctype="multipart/form-data" action="<?php echo G5_MDL_ADMIN_URL; ?>/module_upload.php"
      class="ml-upload">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <strong>새 모듈 설치 (tar.gz 업로드)</strong>
    <input type="file" name="mdl_pkg" accept=".gz,application/gzip,application/x-gzip" required>
    <button type="submit" class="btn_01" style="padding:6px 12px;font-size:12px;">설치</button>
    <span class="hint">tar.gz 안에 단일 폴더가 있어야 하고, 그 안에 <code>module.php</code> 가 있어야 합니다. 같은 이름이 이미 있으면 다음 화면에서 덮어쓰기 확인을 합니다. DB 데이터는 유지됩니다.</span>
</form>
<?php endif; ?>

<?php if (empty($modules)): ?>
<div class="ml-empty">아직 설치된 모듈이 없습니다. 위에서 zip 파일을 업로드해 새 모듈을 설치하세요.</div>
<?php else: ?>

<?php
function _ml_render_card($mn, $meta, $is_active, $is_admin_super) {
    $title = isset($meta['title']) ? $meta['title'] : $mn;
    $desc  = isset($meta['description']) ? $meta['description'] : '';
    $ver   = isset($meta['version']) ? $meta['version'] : '0.0.0';
    $auth  = isset($meta['author']) && $meta['author'] !== '' ? $meta['author'] : '';
    $deps  = (isset($meta['depends']) && is_array($meta['depends'])) ? $meta['depends'] : array();
    $scopes = (isset($meta['scopes']) && is_array($meta['scopes'])) ? $meta['scopes'] : array();
    $has_settings = !empty($meta['admin_menu']);
    $has_menus    = !empty($meta['admin_menus']);

    // 설정 버튼 URL 결정 — admin_menu(단일) 우선, 없으면 admin_menus(다중) 의 첫 번째 메뉴 URL
    $settings_url = '';
    if ($has_settings) {
        $settings_url = G5_MDL_ADMIN_URL.'/module.php?m='.urlencode($mn).'&p=setting';
    } else if ($has_menus) {
        foreach ($meta['admin_menus'] as $group) {
            if (is_array($group)) {
                foreach ($group as $entry) {
                    if (is_array($entry) && !empty($entry[2])) {
                        $settings_url = $entry[2];
                        break 2;
                    }
                }
            }
        }
    }
    $is_board     = !empty($meta['board_scope']);
    $mod_type     = isset($meta['module_type']) ? trim((string)$meta['module_type']) : '';
    $has_readme   = is_file(G5_PATH.'/module/'.$mn.'/README.md');
    $has_widget   = function_exists('mdl_module_has_widget') && mdl_module_has_widget($mn);
    $search_hay   = strtolower($mn.' '.$title.' '.$desc.' '.$auth.($has_widget ? ' 위젯 widget' : ''));
?>
<div class="ml-card <?php echo $is_active ? 'on' : 'off'; ?>"
     data-state="<?php echo $is_active ? 'on' : 'off'; ?>"
     data-search="<?php echo h($search_hay); ?>">
    <div class="ml-head">
        <div class="ml-title">
            <?php echo h($title); ?>
            <span class="ml-name">(<?php echo h($mn); ?>)</span>
        </div>
        <span class="ml-state <?php echo $is_active ? 'on' : 'off'; ?>">
            <?php echo $is_active ? '● 켜짐' : '○ 꺼짐'; ?>
        </span>
    </div>

    <?php if ($desc): ?>
    <div class="ml-desc"><?php echo h($desc); ?></div>
    <?php endif; ?>

    <div class="ml-meta">
        <span class="ver">v<?php echo h($ver); ?></span>
        <?php if ($auth): ?><span class="author">by <?php echo h($auth); ?></span><?php endif; ?>
    </div>

    <?php
    if (function_exists('mdl_print_version_banners')) {
        mdl_print_version_banners(array($mn => $meta));
    }
    ?>

    <?php if ($is_board || !empty($scopes) || !empty($deps) || $has_menus || $mod_type !== '' || $has_widget): ?>
    <div class="ml-tags">
        <?php if ($is_board): ?>
        <span class="ml-tag">📋 게시판별 켜고 끄기 가능</span>
        <?php endif; ?>
        <?php if ($mod_type !== ''): ?>
        <span class="ml-tag type" title="같은 그룹 안에서는 한 번에 하나만 켤 수 있습니다. 다른 모듈을 켜면 이 모듈은 자동으로 꺼집니다.">🔗 단독 그룹: <?php echo h($mod_type); ?></span>
        <?php endif; ?>
        <?php if ($has_widget): ?>
        <span class="ml-tag widget" title="테마·스킨의 원하는 위치에 get_widget() 한 줄을 붙여 넣으면 됩니다. 키와 역할은 도움말을 보세요.">위젯</span>
        <?php endif; ?>
        <?php if (!empty($scopes)): foreach ($scopes as $sc): ?>
        <span class="ml-tag scope"><?php echo h(mdl_scope_label($sc)); ?></span>
        <?php endforeach; endif; ?>
        <?php if (!empty($deps)): ?>
        <span class="ml-tag dep">↳ 필요: <?php echo h(implode(', ', $deps)); ?></span>
        <?php endif; ?>
        <?php if ($has_menus): ?>
        <span class="ml-tag menu">⚙ 어드민 메뉴 <?php echo count($meta['admin_menus']); ?>개</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="ml-actions">
        <?php if ($is_active): ?>
            <a href="?action=deactivate&amp;m=<?php echo urlencode($mn); ?>" class="act-off"
               onclick="return confirm('이 모듈을 끄시겠습니까?');">끄기</a>
            <?php if ($settings_url !== ''): ?>
            <a href="<?php echo $settings_url; ?>" class="act-set">설정</a>
            <?php endif; ?>
            <?php if (!empty($meta['view_url'])): ?>
            <a href="<?php echo h($meta['view_url']); ?>" class="act-view" target="_blank" rel="noopener"
               title="이 모듈이 제공하는 사용자 페이지를 새 탭에서 엽니다.">페이지 보기</a>
            <?php endif; ?>
            <a href="?action=reinstall&amp;m=<?php echo urlencode($mn); ?>" class="act-reinstall"
               title="install.php 를 다시 실행합니다 (디비 컬럼/설정값 보강). 기존 데이터는 보존됩니다."
               onclick="return confirm('install.php 를 다시 실행합니다.\n\n• DB 테이블/컬럼은 멱등적으로 재생성(존재하면 스킵)\n• 기본 설정값 보강\n• 기존 데이터는 유지\n\n진행할까요?');">재설치</a>
        <?php else: ?>
            <a href="?action=activate&amp;m=<?php echo urlencode($mn); ?>" class="act-on"
               <?php if ($mod_type !== ''): ?>onclick="return confirm('이 모듈은 \'<?php echo h($mod_type); ?>\' 단독 그룹입니다.\n같은 그룹에서 켜져있는 다른 모듈이 있으면 자동으로 꺼집니다. 진행할까요?');"<?php endif; ?>
            >켜기</a>
            <?php if (!in_array($mn, MDL_CORE_MODULES, true)): ?>
            <a href="?action=uninstall&amp;m=<?php echo urlencode($mn); ?>" class="act-rm"
               onclick="return confirm('데이터까지 모두 제거합니다 (uninstall.php 실행). 진행할까요?');">데이터 제거</a>
            <?php if ($is_admin_super): ?>
            <a href="<?php echo G5_MDL_ADMIN_URL; ?>/module_delete.php?m=<?php echo urlencode($mn); ?>&amp;token=<?php echo get_admin_token(); ?>"
               class="act-del"
               onclick="return confirm('디스크에서 모듈 폴더 자체를 삭제합니다.\nuninstall.php 가 자동 실행됩니다.\n되돌릴 수 없습니다. 정말 진행할까요?');">폴더 삭제</a>
            <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

        <a href="<?php echo G5_MDL_ADMIN_URL; ?>/module_verify.php?m=<?php echo urlencode($mn); ?>" class="act-verify"
           title="이 모듈이 사용하는 훅이 실제로 존재하는지 검사합니다">점검</a>

        <?php if ($has_readme): ?>
        <a href="<?php echo G5_MDL_ADMIN_URL; ?>/module_readme.php?m=<?php echo urlencode($mn); ?>" class="act-help"
           target="_blank" rel="noopener">도움말</a>
        <?php endif; ?>
    </div>
</div>
<?php
}
?>

<?php if (!empty($cat_normal)): ?>
<div class="ml-cat-head">🧩 일반 모듈 <span class="cat-cnt">(<?php echo count($cat_normal); ?>개)</span></div>
<div class="ml-card-grid">
<?php foreach ($cat_normal as $mn => $meta) {
    _ml_render_card($mn, $meta, in_array($mn, $active, true), ($is_admin));
} ?>
</div>
<?php endif; ?>

<?php if (!empty($cat_board)): ?>
<div class="ml-cat-head">📋 게시판 모듈 <span class="cat-cnt">(<?php echo count($cat_board); ?>개) — 게시판별로 켜고 끌 수 있습니다</span></div>
<div class="ml-card-grid">
<?php foreach ($cat_board as $mn => $meta) {
    _ml_render_card($mn, $meta, in_array($mn, $active, true), ($is_admin));
} ?>
</div>
<?php endif; ?>

<?php if (!empty($cat_alone)): ?>
<div class="ml-cat-head">🧱 독립 모듈 <span class="cat-cnt">(<?php echo count($cat_alone); ?>개) — 다른 코드에 끼어들지 않고 자기 페이지/테이블만 가지는 모듈입니다 (훅 점검 대상 아님)</span></div>
<div class="ml-card-grid">
<?php foreach ($cat_alone as $mn => $meta) {
    _ml_render_card($mn, $meta, in_array($mn, $active, true), ($is_admin));
} ?>
</div>
<?php endif; ?>

<?php endif; ?>

<div style="margin-top:24px; padding:10px 14px; background:#fafafa; border:1px dashed #ddd; border-radius:4px; font-size:11px; color:#888; line-height:1.7;">
    <strong>도움말</strong> —
    <strong>켜기/끄기</strong>는 모듈을 활성/비활성만 바꿉니다 (데이터 유지).
    <strong>재설치</strong>는 install.php 를 다시 실행해 DB 컬럼/기본 설정값을 보강합니다 (디비/설치 실패 복구용, 데이터 유지).
    <strong>데이터 제거</strong>는 모듈의 <code>uninstall.php</code> 를 실행해 DB 컬럼·파일 등을 정리합니다.
    <strong>폴더 삭제</strong>는 디스크의 <code>module/&lt;이름&gt;/</code> 폴더를 통째로 지웁니다 (super 관리자 전용).
    <strong>위젯</strong> 태그가 있는 모듈은 테마·스킨의 원하는 위치에 <code>&lt;?php echo get_widget('모듈명'); ?&gt;</code> 한 줄을 붙여 넣으면 됩니다. 키와 역할은 해당 모듈 도움말을 보세요.
</div>

<div id="ml-noresult" class="ml-noresult" style="display:none;">검색 결과가 없습니다.</div>

<script>
(function(){
    var $search = document.getElementById('ml-search');
    var $filters = document.querySelectorAll('input[name="ml-state"]');
    var $cards = document.querySelectorAll('.ml-card');
    var $cats  = document.querySelectorAll('.ml-cat-head');
    var $grids = document.querySelectorAll('.ml-card-grid');
    var $shown = document.getElementById('ml-stat-shown');
    var $shownNum = $shown ? $shown.querySelector('strong') : null;
    var $noresult = document.getElementById('ml-noresult');

    function getState(){
        for (var i=0; i<$filters.length; i++) if ($filters[i].checked) return $filters[i].value;
        return 'all';
    }

    function apply(){
        var q = ($search.value || '').trim().toLowerCase();
        var st = getState();
        var shown = 0;
        for (var i=0; i<$cards.length; i++){
            var c = $cards[i];
            var match = true;
            if (st !== 'all' && c.getAttribute('data-state') !== st) match = false;
            if (match && q){
                var hay = c.getAttribute('data-search') || '';
                if (hay.indexOf(q) === -1) match = false;
            }
            c.style.display = match ? '' : 'none';
            if (match) shown++;
        }
        // 빈 그리드/카테고리 헤더 숨김
        for (var j=0; j<$grids.length; j++){
            var g = $grids[j];
            var visible = g.querySelectorAll('.ml-card:not([style*="display: none"])').length;
            g.style.display = visible ? '' : 'none';
            // 직전 형제(카테고리 헤더)도 같이 숨김
            var prev = g.previousElementSibling;
            if (prev && prev.classList.contains('ml-cat-head')) prev.style.display = visible ? '' : 'none';
        }
        if ($shown && $shownNum){
            if (q || st !== 'all'){
                $shown.style.display = '';
                $shownNum.textContent = shown;
            } else {
                $shown.style.display = 'none';
            }
        }
        if ($noresult) $noresult.style.display = (shown === 0) ? '' : 'none';
    }

    if ($search) $search.addEventListener('input', apply);
    for (var k=0; k<$filters.length; k++) $filters[k].addEventListener('change', apply);
})();
</script>

<?php
include_once(G5_MDL_ADMIN_PATH.'/admin.tail.php');
