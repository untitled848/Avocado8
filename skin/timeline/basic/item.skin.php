<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 타임라인 아이템 스킨
 *
 * $wr_script = 1 이면 썸네일 없이 본문 전체 영역 사용
 */

$item_class = 'tl-item';
$is_script = !empty($wr_script);
if ($is_script) {
    $item_class .= ' tl-item-script';
}
$_item_row = isset($row) && is_array($row) ? $row : array();
if (function_exists('timeline_item_html_class')) {
    $item_class = timeline_item_html_class($item_class, $_item_row);
}
if (!isset($images)) $images = array();
$image_count = count($images);
?>
<div class="<?php echo $item_class; ?>" data-id="<?php echo $wr_id; ?>" data-my-reply="<?php echo !empty($my_reply) ? '1' : '0'; ?>"<?php echo $is_script ? ' data-script="1"' : ''; ?>>
    <?php if (!$is_script) { ?>
    <a href="<?php echo $profile_url; ?>" class="tl-profile">
        <img src="<?php echo $ch_image; ?>" alt="<?php echo $ch_name; ?>">
    </a>
    <?php } ?>
    
    <div class="tl-body">
        <?php
        $content_type = 'post';
        $item_id = $wr_id;
        include($timeline_skin_path.'/item.content.skin.php');
        ?>
    </div>
</div>
