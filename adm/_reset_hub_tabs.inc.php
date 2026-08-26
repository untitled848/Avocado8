<?php
/**
 * 리셋 카운터 허브 탭
 *
 * 사용:
 *   $reset_hub_tab = 'list';  // list | values
 *   include G5_ADMIN_PATH.'/_reset_hub_tabs.inc.php';
 */
if (!defined('_GNUBOARD_')) exit;

if (empty($reset_hub_tab)) return;

$rhub_tabs = array(
    'list'   => array('label' => '카운터 일람', 'url' => './reset_list.php',   'hint' => '정의·주기'),
    'values' => array('label' => '값 관리',     'url' => './reset_manage.php', 'hint' => 'rm_value 수정'),
);

$rhub_help_map = array(
    'list'   => '주기적으로 자동 리셋되는 카운터 <strong>정의</strong>를 조회합니다. 모듈이 등록한 항목은 자동 생성·갱신되며 여기서는 읽기 전용입니다. 실제 값(rm_value)은 <a href="./reset_manage.php">값 관리</a> 탭에서 수정합니다.',
    'values' => 'rs_id 별로 reset_manager 행의 <strong>rm_value</strong>만 수정합니다. 카운터 정의·주기는 <a href="./reset_list.php">카운터 일람</a> 탭에서 확인하세요.',
);

$rhub_help = isset($rhub_help_map[$reset_hub_tab]) ? $rhub_help_map[$reset_hub_tab] : '';
?>
<div class="dhub">
    <?php if ($rhub_help !== ''): ?>
    <p class="dhub-help"><?php echo $rhub_help; ?></p>
    <?php endif; ?>
    <nav class="dhub-tabs" aria-label="리셋 카운터 탭">
        <?php foreach ($rhub_tabs as $key => $t):
            $on = ($key === $reset_hub_tab) ? ' on' : '';
        ?>
        <a class="dhub-tab<?php echo $on; ?>" href="<?php echo $t['url']; ?>">
            <?php echo h($t['label']); ?>
            <?php if (!empty($t['hint'])): ?><span class="dhub-hint"><?php echo h($t['hint']); ?></span><?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>
</div>
<?php
unset($rhub_tabs, $rhub_help, $rhub_help_map, $key, $t, $on);
