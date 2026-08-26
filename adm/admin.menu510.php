<?php
$menu['menu510'] = array(
    array('510000', '상점/화폐',         G5_ADMIN_URL.'/point_list.php',  ''),
    array('510010', ($config['cf_money'] ? $config['cf_money'] : '재화').' 관리', G5_ADMIN_URL.'/point_list.php', ''),
    array('510100', '상점 관리',         G5_ADMIN_URL.'/shop_list.php',   ''),
    array('510400', '아이템 구매기록',   G5_ADMIN_URL.'/order_list.php',  ''),
);
?>
