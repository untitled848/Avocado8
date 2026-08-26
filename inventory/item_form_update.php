<?php
include_once('./_common.php');

$ch_id = isset($_POST['ch_id']) ? (int)$_POST['ch_id'] : (isset($ch_id) ? (int)$ch_id : 0);
$in_id = isset($_POST['in_id']) ? (int)$_POST['in_id'] : (isset($in_id) ? (int)$in_id : 0);
$url = isset($_POST['url']) ? $_POST['url'] : (isset($url) ? $url : '');

$ch = get_character($ch_id);

if ($url) {
	$return_url = urldecode($url);
} else {
	$return_url = "./viewer.php?ch_id=".$ch_id;
}

if (!$ch['ch_id']) {
	alert('캐릭터 정보가 존재하지 않습니다.');
}

$in = sql_fetch("select * from {$g5['inventory_table']} inven, {$g5['item_table']} item where inven.in_id = '{$in_id}' and inven.it_id = item.it_id and inven.ch_id = '{$ch_id}'");

if (empty($in['in_id']) || $in['it_type'] != '아이템추가') {
	alert('올바른 아이템 정보가 아닙니다.');
}

$reuse_it_id = isset($_POST['reuse_it_id']) ? (int)$_POST['reuse_it_id'] : 0;
if ($reuse_it_id > 0) {
	if (!function_exists('item_grant_maker_reuse') || !item_grant_maker_reuse($ch_id, $reuse_it_id, $in_id, '일반', '개인')) {
		alert('선택한 아이템을 지급할 수 없습니다.');
	}
	goto_url($return_url);
}

$it_name = isset($_POST['it_name']) ? trim(stripslashes((string)$_POST['it_name'])) : '';
$it_content = isset($_POST['it_content']) ? trim(stripslashes((string)$_POST['it_content'])) : '';
if ($it_name === '' || $it_content === '') {
	alert('이름과 설명을 입력해 주세요.');
}

$item_data_path = G5_DATA_PATH."/item";
$item_data_url = G5_DATA_URL."/item";

@mkdir($item_data_path, G5_DIR_PERMISSION);
@chmod($item_data_path, G5_DIR_PERMISSION);

$tmp_row = sql_fetch(" select max(it_id) as max_it_id from {$g5['item_table']} ");
$it_id = (int)$tmp_row['max_it_id'] + 1;
$sql_img = "";

if (!empty($_FILES['it_img']['name']) && (int)$_FILES['it_img']['error'] === UPLOAD_ERR_OK) {
	$img = $_FILES['it_img']['name'];
	if (!preg_match("/\.(gif|jpg|png)$/i", $img)) {
		alert("아이템 이미지가 gif, jpg, png 파일이 아닙니다.");
	}
	$exp = explode(".", $_FILES['it_img']['name']);
	$exp = $exp[count($exp)-1];

	$image_name = "item_".$it_id."_img.".$exp;
	upload_file($_FILES['it_img']['tmp_name'], $image_name, $item_data_path);
	$it_img = $item_data_url."/".$image_name;
	$sql_img = " , it_img = '".sql_real_escape_string($it_img)."' ";
}

$it_name_sql = sql_real_escape_string($it_name);
$it_content_sql = sql_real_escape_string($it_content);
$ch_name_sql = sql_real_escape_string((string)$ch['ch_name']);
$maker_ch_id = (int)$ch_id;

$sql = " insert into {$g5['item_table']}
			set it_id = '{$it_id}',
				it_name = '{$it_name_sql}',
				it_category = '개인',
				it_type = '일반',
				it_content = '{$it_content_sql}',
				maker_ch_id = '{$maker_ch_id}'
				{$sql_img}";
sql_query($sql);

$sql = " insert into {$g5['inventory_table']}
			set ch_id = '{$ch_id}',
				it_id = '{$it_id}',
				it_name = '{$it_name_sql}',
				ch_name = '{$ch_name_sql}'";
sql_query($sql);

delete_inventory($in_id);

goto_url($return_url);
?>
