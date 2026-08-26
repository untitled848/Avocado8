<?php
$menu['menu410'] = array(
    array('410000', '랭킹/경험치/스탯',                                   G5_ADMIN_URL.'/level_list.php',  ''),
    array('410100', ($config['cf_rank_name'] ? $config['cf_rank_name'] : '랭킹').' 설정 관리', G5_ADMIN_URL.'/level_list.php',  ''),
    array('410110', ($config['cf_exp_name']  ? $config['cf_exp_name']  : '경험치').' 관리',     G5_ADMIN_URL.'/exp_list.php',    ''),
    array('410120', '스탯 관리',                                          G5_ADMIN_URL.'/status_list.php', ''),
);
?>
