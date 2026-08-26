<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 조합(H) 결과 로그 스킨
 * 
 * $data_log 구조:
 * [0] = 'H' (액션 타입)
 * [1] = 'S' (성공) 또는 'F' (실패) 또는 'E' (권한 없음)
 * [2] = 결과 아이템 ID (성공 시) 또는 'NON'
 * [3] = 결과 아이템명 (성공 시) 또는 'NON'
 * [4] = 인벤토리 ID (성공 시) 또는 재료 조합
 * [5] = 재료 조합 (성공 시)
 */

$make_result = isset($data_log[1]) ? $data_log[1] : '';
$make_item_id = isset($data_log[2]) ? $data_log[2] : '';
$make_item_name = isset($data_log[3]) ? $data_log[3] : '';
$make_materials = isset($data_log[5]) ? $data_log[5] : (isset($data_log[4]) ? $data_log[4] : '');

if ($make_result == 'S') {
    // 조합 성공
    $item_img = ($make_item_id && $make_item_id != 'NON') ? get_item_img($make_item_id) : '';
    ?>
    <div class="tl-action-result success">
        <span>🔧 조합 성공!</span>
        <?php if ($item_img) { ?>
        <img src="<?php echo $item_img; ?>" alt="" class="item-icon">
        <?php } ?>
        <strong><?php echo h($make_item_name); ?></strong>
        <span>제작 완료</span>
    </div>
    <?php
} elseif ($make_result == 'F') {
    // 조합 실패
    ?>
    <div class="tl-action-result fail">
        <span>🔧 조합 실패</span>
        <span>해당 재료로는 아무것도 만들 수 없습니다.</span>
    </div>
    <?php
} elseif ($make_result == 'E') {
    // 권한 없음
    ?>
    <div class="tl-action-result fail">
        <span>🔧 조합 오류</span>
        <span>재료 아이템을 확인해주세요.</span>
    </div>
    <?php
}
?>
