<?php
$menu['menu400'] = array(
    array('400000', '캐릭터/프로필',         G5_ADMIN_URL.'/character_list.php',         ''),
    array('400100', '프로필 양식 관리',      G5_ADMIN_URL.'/character_article_list.php', ''),
    array('400200', '캐릭터 관리',           G5_ADMIN_URL.'/character_list.php',         ''),
);
$menu['menu400'][] = array('400600', ($config['cf_side_title']  ? $config['cf_side_title']  : '진영').' 관리', G5_ADMIN_URL.'/side_list.php',  '');
$menu['menu400'][] = array('400700', ($config['cf_class_title'] ? $config['cf_class_title'] : '직업').' 관리', G5_ADMIN_URL.'/class_list.php', '');
$menu['menu400'][] = array('400800', '리셋 카운터 관리', G5_ADMIN_URL.'/reset_list.php', 'cf_reset');
?>
