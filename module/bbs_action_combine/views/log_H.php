<?php
/**
 * 조합 로그 렌더 뷰 (bbs_view_row_extra 필터에서 include)
 *
 * 진입 변수: $row, $ctx, $data_log = explode('||', $row['wr_log'])
 *   성공: [0]=H [1]=S [2]=it_id [3]=it_name [4]=in_id [5]=re_item_order
 *   실패: [0]=H [1]=F [2]=NON [3]=NON [4]=re_item_order
 */
if (!defined('_GNUBOARD_')) exit;

$result  = isset($data_log[1]) ? $data_log[1] : '';
$it_id   = isset($data_log[2]) ? $data_log[2] : '';
$it_name = isset($data_log[3]) ? $data_log[3] : '';

if ($result === 'S' && $it_id !== '' && $it_id !== 'NON') {
    ?>
    <div class="log-action-box data-combine<?php if($it_id){?> item-in<?php } ?>">
        <?php if($it_id){?>
        <em><img src="<?php echo function_exists('get_item_img') ? get_item_img($it_id) : ''; ?>" /></em>
        <?php }?>
        <p>
            <span>조합에 성공하여 <strong><?php echo htmlspecialchars($it_name, ENT_QUOTES); ?></strong><?php echo function_exists('j') ? j($it_name, '을') : '을(를)'; ?> 만들었습니다!</span>
        </p>
    </div>
    <?php
} else {
    ?>
    <div class="log-action-box data-combine">
        <em></em>
        <p><span>조합에 <strong>실패했습니다</strong>... 재료가 사라졌습니다.</span></p>
    </div>
    <?php
}
