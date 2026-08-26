<?php
/**
 * 공통 허브 탭 마크업 (.dhub)
 *
 * 사용:
 *   $hub_tabs = array('key' => array('label'=>'', 'url'=>'', 'hint'=>'', 'sep'=>false), ...);
 *   $hub_tab  = 'key';
 *   $hub_help = 'html';   // optional
 *   $hub_aria = '탭';
 *   include G5_ADMIN_PATH.'/_admin_hub_tabs.inc.php';
 */
if (!defined('_GNUBOARD_')) exit;

if (empty($hub_tabs) || !is_array($hub_tabs) || empty($hub_tab)) return;
if (!isset($hub_aria) || $hub_aria === '') $hub_aria = '하위 메뉴';
?>
<div class="dhub">
    <?php if (!empty($hub_help)): ?>
    <p class="dhub-help"><?php echo $hub_help; ?></p>
    <?php endif; ?>
    <nav class="dhub-tabs" aria-label="<?php echo h($hub_aria); ?>">
        <?php foreach ($hub_tabs as $key => $t):
            $on  = ($key === $hub_tab) ? ' on' : '';
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
unset($key, $t, $on, $sep);
