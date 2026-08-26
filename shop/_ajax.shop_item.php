<?php 
include_once("./_common.php");

$item = sql_fetch("select * from {$g5['shop_table']} shop, {$g5['item_table']} item where shop.it_id = item.it_id and shop.sh_id = '{$sh_id}'");

if($item['sh_id']) {

	$money = "";
	$add_str = "";

	if($item['sh_money'] && $item['sh_use_money']) { 
		$money .= $add_str.$item['sh_money'].' '.$config['cf_money_pice'];
		$add_str = ", ";
	}

	if($item['sh_exp'] && $item['sh_use_exp']) { 
		$money .= $add_str.$item['sh_exp'].' '.$config['cf_exp_pice'];
		$add_str = ", ";
	}

	if($item['sh_has_item'] && $item['sh_use_has_item']) { 
		$item['sh_has_item_count'] = ($item['sh_has_item_count'] == '' || $item['sh_has_item_count'] == '0') ? 1 : $item['sh_has_item_count'];
		$money .= $add_str.get_item_name($item['sh_has_item']).' '.$item['sh_has_item_count'].'개';
		$add_str = ", ";
	}

	if($item['it_content2']) { 
		$item['it_content'] .= "<br />(".$item['it_content2'].")";
	}
}

$_shop_skin_dir = G5_PATH.'/shop/skin';
if (function_exists('get_slot_theme') && function_exists('theme_resolve_base')) {
    $_th = get_slot_theme('shop');
    if ($_th !== '' && $_th !== 'default') {
        $_tb = theme_resolve_base($_th);
        if ($_tb['abs'] !== '' && is_file($_tb['abs'].'/shop/skin/shop.item.skin.php')) {
            $_shop_skin_dir = $_tb['abs'].'/shop/skin';
        }
    }
}
include($_shop_skin_dir."/shop.item.skin.php");

?>
