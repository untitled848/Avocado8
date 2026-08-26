<?php
/**
 * 디자인/프리셋 허브 탭
 *
 * 사용:
 *   $design_hub = 'page';   // page | preset
 *   $design_hub_tab = 'site';
 *   include G5_ADMIN_PATH.'/_design_hub_tabs.inc.php';
 */
if (!defined('_GNUBOARD_')) exit;

if (empty($design_hub) || empty($design_hub_tab)) return;

$dhub_page_tabs = array(
    'site'    => array('label' => '사이트', 'url' => './design_form.php',         'hint' => '색·폰트·버튼'),
    'world'   => array('label' => '세계관', 'url' => './world_design_form.php',   'hint' => '메뉴·배경'),
    'content' => array('label' => '문서',   'url' => './content_design_form.php', 'hint' => '내용관리 페이지'),
);

$dhub_preset_tabs = array(
    'bundle'  => array('label' => '테마 프리셋',   'url' => './theme_preset_list.php',    'hint' => '시기에 연결하는 한 세트'),
    'site'    => array('label' => '사이트 프리셋', 'url' => './design_preset_list.php',   'hint' => '색 저장본 목록', 'sep' => true),
    'world'   => array('label' => '세계관 프리셋', 'url' => './world_preset_list.php',    'hint' => '세계관 저장본'),
    'content' => array('label' => '문서 프리셋',   'url' => './content_preset_list.php',  'hint' => '문서 저장본'),
    'upload'  => array('label' => '테마 업로드',   'url' => './theme_upload_list.php',    'hint' => '테마 폴더 설치', 'sep' => true),
);

$dhub_help_map = array(
    'page' => array(
        'site'    => '사이트 전역의 <strong>색·폰트·버튼·여백</strong>을 편집합니다. 여러 벌의 저장본은 아래 ‘현재 편집 중’에서 바꾸고, 시기에 연결하려면 <a href="./theme_preset_list.php">테마 프리셋 설정</a>에서 테마 프리셋에 넣습니다.',
        'world'   => '세계관 페이지의 <strong>메뉴·배경</strong> 등을 편집합니다. 저장본을 시기에 연결하려면 <a href="./theme_preset_list.php">테마 프리셋 설정</a>에서 테마 프리셋에 넣습니다.',
        'content' => '내용관리(문서) 페이지의 디자인을 편집합니다. 저장본을 시기에 연결하려면 <a href="./theme_preset_list.php">테마 프리셋 설정</a>에서 테마 프리셋에 넣습니다.',
    ),
    'preset' => array(
        'bundle'  => '<strong>테마 프리셋</strong>은 테마 파일 + 사이트/세계관/문서 디자인을 하나로 고른 세트입니다. <a href="./config_time.php">시기 관리</a>에서 이 세트를 연결하면 그 시기 동안 사이트가 이 모습으로 바뀝니다. 색·메뉴 내용은 <a href="./design_form.php">페이지 디자인 설정</a>에서 고칩니다.',
        'site'    => '사이트 색·폰트 설정의 <strong>저장본 목록</strong>입니다. 내용을 고치려면 <a href="./design_form.php">페이지 디자인 설정 → 사이트</a>로 가세요. 시기에 쓰려면 <a href="./theme_preset_list.php">테마 프리셋</a> 탭에서 고릅니다.',
        'world'   => '세계관 디자인의 <strong>저장본 목록</strong>입니다. 내용을 고치려면 <a href="./world_design_form.php">페이지 디자인 설정 → 세계관</a>으로 가세요. 시기에 쓰려면 <a href="./theme_preset_list.php">테마 프리셋</a>에서 고릅니다.',
        'content' => '문서 디자인의 <strong>저장본 목록</strong>입니다. 내용을 고치려면 <a href="./content_design_form.php">페이지 디자인 설정 → 문서</a>로 가세요. 시기에 쓰려면 <a href="./theme_preset_list.php">테마 프리셋</a>에서 고릅니다.',
        'upload'  => '테마 폴더를 tar.gz로 설치합니다. 설치한 테마는 <a href="./theme_preset_list.php">테마 프리셋</a>에서 영역(슬롯)별로 고를 수 있습니다. 색·메뉴 모양은 테마가 아니라 <a href="./design_form.php">페이지 디자인 설정</a>입니다.',
    ),
);

if ($design_hub === 'page') {
    $dhub_tabs = $dhub_page_tabs;
} else {
    $dhub_tabs = $dhub_preset_tabs;
}

$dhub_help = '';
if (isset($dhub_help_map[$design_hub][$design_hub_tab])) {
    $dhub_help = $dhub_help_map[$design_hub][$design_hub_tab];
}
?>
<div class="dhub">
    <?php if ($dhub_help !== ''): ?>
    <p class="dhub-help"><?php echo $dhub_help; ?></p>
    <?php endif; ?>
    <nav class="dhub-tabs" aria-label="디자인 설정 탭">
        <?php foreach ($dhub_tabs as $key => $t):
            $on  = ($key === $design_hub_tab) ? ' on' : '';
            $sep = !empty($t['sep']) ? ' sep' : '';
        ?>
        <a class="dhub-tab<?php echo $on.$sep; ?>" href="<?php echo $t['url']; ?>">
            <?php echo h($t['label']); ?>
            <?php if (!empty($t['hint'])): ?><span class="dhub-hint"><?php echo h($t['hint']); ?></span><?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>
</div>
<?php
unset($dhub_page_tabs, $dhub_preset_tabs, $dhub_tabs, $dhub_help, $dhub_help_map, $key, $t, $on, $sep);
