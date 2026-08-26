<?php
/**
 * MAP_MON 로그 렌더
 * wr_log = MAP_MON||state||mon_atk||user_atk||hp_info||d1+d2+item_atk
 */
if (!defined('_GNUBOARD_')) exit;

$state    = isset($data_log[1]) ? $data_log[1] : '';
$mon_atk  = isset($data_log[2]) ? (int)$data_log[2] : 0;
$user_atk = isset($data_log[3]) ? (int)$data_log[3] : 0;
$dice_raw = isset($data_log[5]) ? $data_log[5] : '';
$dice     = $dice_raw !== '' ? explode('+', $dice_raw) : array();

if ($state !== 'E') {
    $monster_comment = '대상을 공격합니다!';
    if ($mon_atk < $user_atk) {
        $monster_comment_detail = '공격 성공! '.($user_atk - $mon_atk).'의 피해를 입혔습니다.';
    } else {
        $monster_comment_detail = '공격 실패!';
    }
} else {
    $monster_comment = '대상을 처치하는데 성공하였습니다!';
    $monster_comment_detail = '공격 성공! '.max(0, $user_atk - $mon_atk).'의 피해를 입혔습니다.';
}
?>
<div class="log-action-box log-map-mon">
    <p><?php echo htmlspecialchars($monster_comment, ENT_QUOTES); ?></p>
    <?php if ($dice_raw !== '') { ?>
    <p class="dice-line">
        <span class="dice">D<?php echo (int)(isset($dice[0]) ? $dice[0] : 0); ?></span>
        +
        <span class="dice">D<?php echo (int)(isset($dice[1]) ? $dice[1] : 0); ?></span>
        <?php if (!empty($dice[2])) { ?>
        + <?php echo (int)$dice[2]; ?>
        <?php } ?>
        = <?php echo (int)$user_atk; ?>
    </p>
    <?php } ?>
    <p><?php echo htmlspecialchars($monster_comment_detail, ENT_QUOTES); ?></p>
</div>
