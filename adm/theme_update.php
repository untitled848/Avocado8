<?php
$sub_menu = "110200";
include_once('./_common.php');

if (!$is_admin)
	die('관리자만 접근 가능합니다.');

admin_referer_check();

$theme = trim($_POST['theme'] ?? '');
$post_type = isset($_POST['type']) ? clean_xss_tags($_POST['type'], 1, 1) : '';
$post_set_default_skin = isset($_POST['set_default_skin']) ? clean_xss_tags($_POST['set_default_skin'], 1, 1) : '';

$theme_dir = get_theme_dir();

if($post_type == 'reset') {
	$sql = " update {$g5['config_table']} set cf_theme = '' ";
	sql_query($sql);
	die('');
}

if(!in_array($theme, $theme_dir))
	die('선택하신 테마가 설치되어 있지 않습니다.');

// 테마적용
$sql = " update {$g5['config_table']} set cf_theme = '$theme' ";
sql_query($sql);

// 테마 설정 스킨 적용
if($post_set_default_skin == 1) {
	// avo8.0: cf_*_skin 컬럼들은 제거되었음 — 슬롯 매핑은 avo_theme_preset_slot 테이블에서 관리.
	// qa_skin / de_shop_skin 은 각각 avo_qa_config / avo_default 테이블 컬럼으로 유지.
	$keys = 'set_default_skin, qa_skin, qa_mobile_skin, de_shop_skin, de_shop_mobile_skin';

	$tconfig = get_theme_config_value($theme, $keys);

	if($tconfig['set_default_skin']) {
		$sql_common = array();
		$qa_sql_common = array();
		$de_sql_common = array();

		foreach($tconfig as $key => $val) {
			if(preg_match('#^qa_.+$#', $key)) {
				if($val) {
					if(!preg_match('#^theme/.+$#', $val))
						$val = 'theme/'.$val;

					$qa_sql_common[] = " $key = '$val' ";
				}

				continue;
			}

			if(preg_match('#^de_.+$#', $key)) {
				if(!isset($default[$key]))
					continue;

				if($val) {
					if(!preg_match('#^theme/.+$#', $val))
						$val = 'theme/'.$val;

					$de_sql_common[] = " $key = '$val' ";
				}

				continue;
			}

			if(!isset($config[$key]))
				continue;

			if($val) {
				if(!preg_match('#^theme/.+$#', $val))
					$val = 'theme/'.$val;

				$sql_common[] = " $key = '$val' ";
			}
		}

		if(!empty($sql_common)) {
			$sql = " update {$g5['config_table']} set " . implode(', ', $sql_common);
			sql_query($sql);
		}

	}
}

die('');