<?php
if (!defined('_GNUBOARD_')) exit;
// skin/world/basic/world.skin.php
// 변수: $str, $wo, $world_menu_tree, $world_menu_active, $world_menu_position,
//        $world_menu_pos_in, $world_menu_expand, $world_pd_id, $world_skin_url

add_stylesheet('<link rel="stylesheet" href="'.$world_skin_url.'/style.css">', 0);
?>
<div class="world-wrap world-wrap--<?php echo htmlspecialchars($world_menu_position); ?>">
    <?php if ($world_menu_position !== 'bottom' && $world_menu_pos_in !== 'inside'): ?>
    <?php echo world_render_menu($world_menu_tree, $world_menu_active, $world_menu_position, $world_menu_expand); ?>
    <?php endif; ?>

    <div class="world-content">
        <?php if ($world_menu_pos_in === 'inside'): ?>
        <?php echo world_render_menu($world_menu_tree, $world_menu_active, $world_menu_position, $world_menu_expand); ?>
        <?php endif; ?>
        <?php echo $str; ?>
    </div>

    <?php if ($world_menu_position === 'bottom' && $world_menu_pos_in !== 'inside'): ?>
    <?php echo world_render_menu($world_menu_tree, $world_menu_active, $world_menu_position, $world_menu_expand); ?>
    <?php endif; ?>
</div>
