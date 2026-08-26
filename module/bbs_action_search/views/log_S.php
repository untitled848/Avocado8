<?php
/**
 * 탐색 로그 렌더 뷰 (bbs_view_row_extra 필터에서 include)
 *
 * 진입 시 사용 가능한 변수:
 *   $row       : 글/댓글 row
 *   $ctx       : bo_table, board_skin_url, board_skin_path, scope
 *   $data_log  : explode('||', $row['wr_log'])
 *                [0]=S [1]=S(성공)|F(실패) [2]=it_id [3]=it_name [4]=in_id
 */
if (!defined('_GNUBOARD_')) exit;

$result  = isset($data_log[1]) ? $data_log[1] : '';
$it_id   = isset($data_log[2]) ? $data_log[2] : '';
$it_name = isset($data_log[3]) ? $data_log[3] : '';

if ($result === 'S' && $it_id !== '') {
    ?>
    <div class="log-action-box data-search <?php if($it_id){?>item-in<?php } ?>">
        <?php if($it_id){?>
            <em><img src="<?php echo function_exists('get_item_img') ? get_item_img($it_id) : ''; ?>" /></em>
        <?php }?>
        <p>
            <span>탐색에 성공해서 <strong><?php echo htmlspecialchars($it_name, ENT_QUOTES); ?></strong><?php echo function_exists('j') ? j($it_name, '을') : '을(를)'; ?> 획득하였습니다!</span>
        </p>
    </div>
    <?php
} else {
    ?>
    <div class="log-action-box data-search">
        <p><span>탐색했지만 <strong>아무것도 발견하지 못했습니다</strong>...</span></p>
    </div>
    <?php
}
