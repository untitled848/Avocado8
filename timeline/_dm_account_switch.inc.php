<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * DM 헤더용 계정 전환 (write.skin.php 와 동일 세션 정책)
 * 필요: $my_ch_id, $character, $my_timeline_users, $tl_table, $my_profile
 */
if (!isset($my_timeline_users) || !is_array($my_timeline_users)) {
    $my_timeline_users = array();
}
$can_switch_account = count($my_timeline_users) > 1;
$switch_name = '';
foreach ($my_timeline_users as $_tu) {
    if ((int)ses($_tu, 'ch_id', 0, 'int') === (int)$my_ch_id) {
        $switch_name = ses($_tu, 'tu_name', '', 'raw');
        break;
    }
}
if ($switch_name === '') {
    $switch_name = ses($character, 'ch_name', '', 'raw');
}
$switch_img = !empty($my_profile) ? $my_profile : get_timeline_thumb((int)$my_ch_id, 1);
$switch_profile_url = G5_URL.'/timeline/profile.php?tl_table='.urlencode($tl_table).'&ch_id='.(int)$my_ch_id;
?>
<div class="tl-composer-avatar<?php echo $can_switch_account ? ' tl-composer-avatar-switch' : ''; ?> dm-account-switch">
    <?php if ($can_switch_account) { ?>
    <button type="button" class="tl-account-switch-btn" onclick="toggleMenu(this)" title="계정 전환" aria-haspopup="true">
        <img src="<?php echo $switch_img; ?>" alt="<?php echo h($switch_name); ?>">
    </button>
    <div class="tl-dropdown tl-account-dropdown">
        <?php foreach ($my_timeline_users as $acc) {
            $acc_ch_id = (int)ses($acc, 'ch_id', 0, 'int');
            if (!$acc_ch_id) continue;
            $acc_name = ses($acc, 'tu_name', '', 'raw');
            if ($acc_name === '') {
                $acc_name = ses($acc, 'ch_name', '', 'raw');
            }
            $acc_img = get_timeline_thumb($acc_ch_id, 1);
            $acc_current = ($acc_ch_id === (int)$my_ch_id);
        ?>
        <button type="button" class="tl-account-item<?php echo $acc_current ? ' is-current' : ''; ?>"<?php echo $acc_current ? '' : ' onclick="switchTimelineAccount('.$acc_ch_id.')"'; ?>>
            <img src="<?php echo $acc_img; ?>" alt="">
            <span class="tl-account-name"><?php echo h($acc_name); ?></span>
            <?php if ($acc_current) { ?><span class="tl-account-current">사용 중</span><?php } ?>
        </button>
        <?php } ?>
        <a href="<?php echo $switch_profile_url; ?>" class="tl-account-profile">프로필</a>
    </div>
    <?php } else { ?>
    <a href="<?php echo $switch_profile_url; ?>">
        <img src="<?php echo $switch_img; ?>" alt="<?php echo h($switch_name); ?>">
    </a>
    <?php } ?>
</div>
