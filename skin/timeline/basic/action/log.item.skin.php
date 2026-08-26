<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 아이템 사용 로그 스킨
 * 
 * $item_log 구조:
 * 
 * 뽑기 성공: S||사용아이템ID||사용아이템명||결과아이템ID||결과아이템명
 * 뽑기 실패: F||사용아이템ID||사용아이템명
 * 일반 사용: D||아이템ID||아이템명||아이템타입||아이템값||내용1||내용2
 */

$item_result = isset($item_log[0]) ? $item_log[0] : '';
$item_id = isset($item_log[1]) ? $item_log[1] : '';
$item_name = isset($item_log[2]) ? $item_log[2] : '';

if ($item_result == 'S') {
    // 뽑기 성공
    $result_item_id = isset($item_log[3]) ? $item_log[3] : '';
    $result_item_name = isset($item_log[4]) ? $item_log[4] : '';
    $item_img = $result_item_id ? get_item_img($result_item_id) : '';
    ?>
    <div class="tl-item-used">
        <span>📦 <?php echo h($item_name); ?> 사용</span>
        <span>→</span>
        <?php if ($item_img) { ?>
        <img src="<?php echo $item_img; ?>" alt="">
        <?php } ?>
        <strong><?php echo h($result_item_name); ?></strong>
        <span class="success-text">획득!</span>
    </div>
    <?php
} elseif ($item_result == 'F') {
    // 뽑기 실패
    ?>
    <div class="tl-item-used">
        <span>📦 <?php echo h($item_name); ?> 사용</span>
        <span>→</span>
        <span class="fail-text">꽝...</span>
    </div>
    <?php
} elseif ($item_result == 'D') {
    // 일반 아이템 사용
    $item_type = isset($item_log[3]) ? $item_log[3] : '';
    $item_value = isset($item_log[4]) ? $item_log[4] : '';
    $item_content = isset($item_log[5]) ? $item_log[5] : '';
    $item_content2 = isset($item_log[6]) ? $item_log[6] : '';
    $item_img = $item_id ? get_item_img($item_id) : '';
    ?>
    <div class="tl-item-used">
        <?php if ($item_img) { ?>
        <img src="<?php echo $item_img; ?>" alt="">
        <?php } ?>
        <span>📦 <?php echo h($item_name); ?> 사용</span>
        <?php if ($item_type) { ?>
        <span class="item-type">[<?php echo h($item_type); ?>]</span>
        <?php } ?>
        <?php if ($item_content) { ?>
        <span class="item-effect"><?php echo h($item_content); ?></span>
        <?php } ?>
    </div>
    <?php
}
?>
