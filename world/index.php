<?php
include_once('../common.php');
include_once(G5_LIB_PATH.'/world.lib.php');

// ─── wo_id 결정 ───────────────────────────────────────────────────────────
$wo_id = trim($_GET['wo_id'] ?? '');
if ($wo_id !== '' && !preg_match('/^[a-z0-9_]+$/i', $wo_id)) {
    $wo_id = '';
}

if ($wo_id === '') {
    $wo_id = world_get_first_wo_id();
    if ($wo_id === null) {
        // 등록된 페이지 없음
        $g5['title'] = '세계관';
        include_once('../head.php');
        echo '<p style="padding:40px;text-align:center;">등록된 세계관 페이지가 없습니다.</p>';
        include_once('../tail.php');
        exit;
    }
}

// ─── 페이지 로드 ──────────────────────────────────────────────────────────
$wo = sql_fetch("SELECT * FROM `{$g5['world_table']}` WHERE wo_id = '".sql_escape_string($wo_id)."'");
if (empty($wo['wo_id'])) {
    $wo_id = world_get_first_wo_id();
    if ($wo_id) {
        $wo = sql_fetch("SELECT * FROM `{$g5['world_table']}` WHERE wo_id = '".sql_escape_string($wo_id)."'");
    }
    if (empty($wo['wo_id'])) {
        alert('등록된 세계관 페이지가 없습니다.');
    }
}

// ─── 메뉴 트리 + 활성 위치 ────────────────────────────────────────────────
$world_menu_tree   = world_get_menu_tree();
$world_menu_active = world_get_active($world_menu_tree, $wo_id);

// ─── 디자인 프리셋 id ─────────────────────────────────────────────────────
$world_pd_id = function_exists('get_active_design') ? (int)get_active_design('world') : 1;
if ($world_pd_id <= 0) $world_pd_id = 1;
$world_cfg = world_get_config($world_pd_id);

$world_menu_position = $world_cfg['menu_pos_v'][0]   ?? 'top';
$world_menu_pos_in   = $world_cfg['menu_pos_in'][0]  ?? 'outside';
$world_menu_expand   = (int)($world_cfg['menu_sub_expand'][0] ?? 0);

// ─── 콘텐츠 HTML 생성 ────────────────────────────────────────────────────
$str = conv_content($wo['wo_content'], $wo['wo_html'], $wo['wo_tag_filter_use']);
if (G5_IS_MOBILE && !empty($wo['wo_mobile_content'])) {
    $str = conv_content($wo['wo_mobile_content'], $wo['wo_html'], $wo['wo_tag_filter_use']);
}

// ─── 타이틀 설정 ──────────────────────────────────────────────────────────
$g5['title'] = $wo['wo_subject'];

// ─── 스킨 확인 ────────────────────────────────────────────────────────────
// wo_skin 이 지정돼 있으면 그쪽 우선,
// 비어 있으면 글로벌 디자인(default 테마 프리셋의 pr_world_design)에서
//   - 'skin:DIRNAME' 이면 그 스킨 사용
//   - 프리셋이면 default 모드(스킨 없이 디자인 변수만 적용)
$world_skin      = $wo['wo_skin'] ?? '';
if (trim((string)$world_skin) === '' && function_exists('get_active_design_resolved')) {
    $resolved = get_active_design_resolved('world');
    if ($resolved['kind'] === 'skin' && $resolved['value'] !== '') {
        $world_skin = $resolved['value'];
    }
}
$use_default     = empty($world_skin) || $world_skin === 'default';
$world_skin_path = '';
$world_skin_url  = '';
$world_skin_file = '';

if (!$use_default) {
    $world_skin_path = get_skin_path('world', $world_skin);
    $world_skin_url  = str_replace(G5_PATH, G5_URL, $world_skin_path);
    $world_skin_file = $world_skin_path . '/world.skin.php';
    if (!is_file($world_skin_file)) {
        $use_default = true;
    }
}

// ─── 디자인 CSS 등록 (기본/스킨 모드 모두) ────────────────────────────────
// 공유 구조 프리셋 → 디자인 변수 → 동적 디자인 CSS 순서로 로드
add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/css/world.preset.css">', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/css/world_root.php?pd_id='.$world_pd_id.'">', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/css/world_design.php?pd_id='.$world_pd_id.'">', 0);

// ─── HEAD ─────────────────────────────────────────────────────────────────
if ($wo['wo_include_head'] && is_include_path_check($wo['wo_include_head'])) {
    @include_once($wo['wo_include_head']);
} else {
    include_once('../head.php');
}

// ─── 본문 출력 ────────────────────────────────────────────────────────────
if (!$use_default) {
    // 스킨에 변수 전달 후 include
    include($world_skin_file);
} else {
    // 기본 레이아웃 렌더링
    $menu_html   = world_render_menu($world_menu_tree, $world_menu_active, $world_menu_position, $world_menu_expand);
    $is_vertical = in_array($world_menu_position, array('left', 'right'));
    $wrap_class  = 'world-wrap world-wrap--' . $world_menu_position . ($world_menu_pos_in === 'inside' ? ' world-wrap--menu-inside' : '');
    ?>
    <div class="<?php echo $wrap_class; ?>">
        <?php if ($world_menu_pos_in !== 'inside' || in_array($world_menu_position, array('left', 'right'))): ?>
            <?php if (!in_array($world_menu_position, array('bottom'))): ?>
            <?php echo $menu_html; ?>
            <?php endif; ?>
        <?php endif; ?>

        <div class="world-content">
            <?php if ($world_menu_pos_in === 'inside'): ?>
            <?php echo $menu_html; ?>
            <?php endif; ?>
            <?php echo $str; ?>
        </div>

        <?php if ($world_menu_position === 'bottom' && $world_menu_pos_in !== 'inside'): ?>
        <?php echo $menu_html; ?>
        <?php endif; ?>
    </div>
    <?php
}

// ─── TAIL ─────────────────────────────────────────────────────────────────
if ($wo['wo_include_tail'] && is_include_path_check($wo['wo_include_tail'])) {
    @include_once($wo['wo_include_tail']);
} else {
    include_once('../tail.php');
}
?>
