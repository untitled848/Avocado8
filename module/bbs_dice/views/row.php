<?php
/**
 * 다이스 row 렌더 뷰 (bbs_view_row_extra 필터에서 include)
 *
 * 진입 시 사용 가능한 변수:
 *   $row         : 글/댓글 row
 *   $module_url  : G5_MDL_URL.'/bbs_dice'
 *   $rolls       : 굴림 결과 배열 (예: [3,5,2])
 */
if (!defined('_GNUBOARD_')) exit;

if (empty($rolls)) return;
$base        = htmlspecialchars($module_url, ENT_QUOTES);
$render_mode = function_exists('bbs_dice_render_mode') ? bbs_dice_render_mode() : 'image';

// 페이지당 1회만 .dice-num 스타일 출력
static $dice_css_emitted = false;
if (!$dice_css_emitted) {
    $dice_css_emitted = true;
    if (function_exists('bbs_dice_num_css')) {
        echo '<style>'.bbs_dice_num_css().'</style>';
    }
}
?>
<div class="log-action-box">
<span class="dice">
<?php foreach ($rolls as $v) {
    $v = (int)$v;
    $url = ($render_mode === 'image') ? bbs_dice_image_url_for($v, $base) : '';
    if ($url !== '') { ?>
    <img src="<?php echo $url; ?>" alt="<?php echo $v; ?>" />
<?php } else { ?>
    <span class="dice-num"><?php echo $v; ?></span>
<?php }
} ?>
</span>
</div>
