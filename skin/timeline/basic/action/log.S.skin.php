<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 탐색(S) 결과 로그 스킨
 * 
 * $data_log 구조:
 * [0] = 'S' (액션 타입)
 * [1] = 'S' (성공) 또는 'F' (실패) 또는 'X' (횟수 초과)
 * [2] = 획득 아이템 ID (성공 시)
 * [3] = 획득 아이템명 (성공 시)
 * [4] = 인벤토리 ID (성공 시)
 */

$search_result = isset($data_log[1]) ? $data_log[1] : '';
$search_item_id = isset($data_log[2]) ? $data_log[2] : '';
$search_item_name = isset($data_log[3]) ? $data_log[3] : '';

if ($search_result == 'S') {
    // 탐색 성공
    $item_img = $search_item_id ? get_item_img($search_item_id) : '';
    ?>
    <div class="tl-action-result success">
        <span>🔍 탐색 성공!</span>
        <?php if ($item_img) { ?>
        <img src="<?php echo $item_img; ?>" alt="" class="item-icon">
        <?php } ?>
        <strong><?php echo h($search_item_name); ?></strong>
        <span>획득</span>
    </div>
    <?php
} elseif ($search_result == 'F') {
    // 탐색 실패
    ?>
    <div class="tl-action-result fail">
        <span>🔍 탐색 실패...</span>
        <span>아무것도 찾지 못했습니다.</span>
    </div>
    <?php
} elseif ($search_result == 'X') {
    // 횟수 초과
    ?>
    <div class="tl-action-result fail">
        <span>🔍 탐색 불가</span>
        <span>오늘 탐색 횟수를 모두 사용했습니다.</span>
    </div>
    <?php
}
?>
