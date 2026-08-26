<?php
/**
 * MAP 로그 렌더
 * wr_log = MAP||ma_id||type||me_id||msg
 * type: F D I G W M
 */
if (!defined('_GNUBOARD_')) exit;

global $g5;

$ma_id = isset($data_log[1]) ? (int)$data_log[1] : 0;
$etype = isset($data_log[2]) ? $data_log[2] : 'F';
$me_id = isset($data_log[3]) ? (int)$data_log[3] : 0;
$emsg  = isset($data_log[4]) ? $data_log[4] : '';

$me_data = array();
$ma_data = array();
if ($me_id > 0) {
    $me_data = sql_fetch("SELECT * FROM `{$g5['module_map_event_table']}` WHERE me_id = '{$me_id}' LIMIT 1");
    if (!is_array($me_data)) $me_data = array();
}
if ($ma_id > 0) {
    $ma_data = sql_fetch("SELECT * FROM `{$g5['module_map_table']}` WHERE ma_id = '{$ma_id}' LIMIT 1");
    if (!is_array($ma_data)) $ma_data = array();
}

$ma_name = isset($ma_data['ma_name']) ? $ma_data['ma_name'] : '';
?>
<div class="log-action-box log-map">
<?php if ($etype === 'F') { ?>
    <p><span><?php echo htmlspecialchars($ma_name !== '' ? $ma_name : '지역', ENT_QUOTES); ?> 구역으로 이동했다.</span></p>
<?php } else { ?>
    <?php if (!empty($me_data['me_img'])) { ?>
    <div class="log-map-img"><img src="<?php echo htmlspecialchars($me_data['me_img'], ENT_QUOTES); ?>" alt=""></div>
    <?php } ?>
    <?php if (!empty($me_data['me_title']) || !empty($me_data['me_content'])) { ?>
    <p>
        <?php if (!empty($me_data['me_title'])) { ?>
        <em class="title"><?php echo htmlspecialchars($me_data['me_title'], ENT_QUOTES); ?></em><br>
        <?php } ?>
        <?php echo nl2br(htmlspecialchars(isset($me_data['me_content']) ? $me_data['me_content'] : '', ENT_QUOTES)); ?>
    </p>
    <?php } ?>

    <?php if ($etype === 'I' && !empty($me_data['me_get_item']) && function_exists('get_item_img')) { ?>
    <div class="thumb"><img src="<?php echo get_item_img($me_data['me_get_item']); ?>" alt=""></div>
    <?php } ?>

    <?php if ($etype === 'M') {
        $now = isset($row['wr_mon_now_hp']) ? (int)$row['wr_mon_now_hp'] : 0;
        $max = isset($row['wr_mon_hp']) ? (int)$row['wr_mon_hp'] : 0;
        $pct = ($max > 0) ? max(0, min(100, ($now / $max) * 100)) : 0;
    ?>
    <div class="hp-bar">
        <em><i><?php echo $now; ?>/<?php echo $max; ?></i>
        <span style="width:<?php echo $pct; ?>%;"></span></em>
    </div>
    <?php } ?>

    <?php if ($emsg !== '') { ?>
    <p><?php echo htmlspecialchars($emsg, ENT_QUOTES); ?></p>
    <?php } ?>
<?php } ?>
</div>
