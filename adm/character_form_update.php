<?php
$sub_menu = "400200";
include_once("./_common.php");
if ($w == 'u') check_demo();
auth_check($auth[$sub_menu], 'w');
check_token();

// Wave10 #6: 시기
$time = isset($_POST['time']) ? (int)$_POST['time'] : 1;
if ($time < 1) $time = 1;

$ch_id = trim($_POST['ch_id']);
$mb = get_member($mb_id);

if(!$mb['mb_id']) {
	alert('존재하지 않는 회원 정보 입니다.');
}

$character_image_path = G5_DATA_PATH."/character/".$mb_id;
$character_image_url = G5_DATA_URL."/character/".$mb_id;

@mkdir($character_image_path, G5_DIR_PERMISSION);
@chmod($character_image_path, G5_DIR_PERMISSION);


$sql_article = "";

// 기본 데이터 등록
$ad = sql_fetch("select * from {$g5['article_default_table']}");
$sql_article .= "
	ch_state		= '{$ch_state}',
	ch_type			= '{$ch_type}',
	mb_id			= '{$mb_id}',
	ch_side			= '{$ch_side}',
	ch_class		= '{$ch_class}',
	ch_name			= '{$ch_name}',
	ch_point		= '{$ch_point}'
";

// 이미지 등록 (각 이미지 변경 여부 추적)
$ch_thumb_changed = false;
$ch_head_changed  = false;
$ch_body_changed  = false;

// -- 두상
if ($ad['ad_use_thumb']) {
	if ($_FILES['ch_thumb_file']['name'] && empty($_FILES['ch_thumb_file']['error'])) {
		// 파일 업로드 모드 + 새 파일: 업로드 후 저장
		$exp = explode(".", $_FILES['ch_thumb_file']['name']);
		$exp = $exp[count($exp)-1];
		$image_name = "thumb_".time().".".$exp;
		upload_file($_FILES['ch_thumb_file']['tmp_name'], $image_name, $character_image_path);
		// cdn_after_upload 는 upload_file() 내부에서 이미 호출됨
		$ch_thumb = $character_image_url."/".$image_name;
		if (function_exists('cdn_tokenize_url')) $ch_thumb = cdn_tokenize_url($ch_thumb);
		$sql_article .= " , ch_thumb\t\t= '{$ch_thumb}'";
		$ch_thumb_changed = true;
	} elseif ($ad['ad_url_thumb']) {
		// URL 직접 입력 모드: 관리자가 텍스트로 입력한 값을 항상 저장
		if (function_exists('cdn_tokenize_url')) $ch_thumb = cdn_tokenize_url($ch_thumb);
		$sql_article .= " , ch_thumb\t\t= '{$ch_thumb}'";
		$ch_thumb_changed = true;
	}
	// 파일 업로드 모드 + 새 파일 없음: DB 기존값 유지 ($sql_article 에 포함하지 않음)
}
// -- 흉상
if ($ad['ad_use_head']) {
	if ($_FILES['ch_head_file']['name'] && empty($_FILES['ch_head_file']['error'])) {
		$exp = explode(".", $_FILES['ch_head_file']['name']);
		$exp = $exp[count($exp)-1];
		$image_name = "head_".time().".".$exp;
		upload_file($_FILES['ch_head_file']['tmp_name'], $image_name, $character_image_path);
		$ch_head = $character_image_url."/".$image_name;
		if (function_exists('cdn_tokenize_url'))     $ch_head = cdn_tokenize_url($ch_head);
		if (function_exists('cdn_tokenize_content')) $ch_head = cdn_tokenize_content($ch_head);
		$sql_article .= " , ch_head\t\t= '{$ch_head}'";
		$ch_head_changed = true;
	} elseif ($ad['ad_url_head']) {
		if (function_exists('cdn_tokenize_url'))     $ch_head = cdn_tokenize_url($ch_head);
		if (function_exists('cdn_tokenize_content')) $ch_head = cdn_tokenize_content($ch_head);
		$sql_article .= " , ch_head\t\t= '{$ch_head}'";
		$ch_head_changed = true;
	}
}
// -- 전신
if ($ad['ad_use_body']) {
	if ($_FILES['ch_body_file']['name'] && empty($_FILES['ch_body_file']['error'])) {
		$exp = explode(".", $_FILES['ch_body_file']['name']);
		$exp = $exp[count($exp)-1];
		$image_name = "body_".time().".".$exp;
		upload_file($_FILES['ch_body_file']['tmp_name'], $image_name, $character_image_path);
		$ch_body = $character_image_url."/".$image_name;
		if (function_exists('cdn_tokenize_url'))     $ch_body = cdn_tokenize_url($ch_body);
		if (function_exists('cdn_tokenize_content')) $ch_body = cdn_tokenize_content($ch_body);
		$sql_article .= " , ch_body\t\t= '{$ch_body}'";
		$ch_body_changed = true;
	} elseif ($ad['ad_url_body']) {
		if (function_exists('cdn_tokenize_url'))     $ch_body = cdn_tokenize_url($ch_body);
		if (function_exists('cdn_tokenize_content')) $ch_body = cdn_tokenize_content($ch_body);
		$sql_article .= " , ch_body\t\t= '{$ch_body}'";
		$ch_body_changed = true;
	}
}


if($w == '') {

	if ($time > 1) alert('시기 N 에서는 캐릭터 신규 등록이 불가능합니다. 먼저 시기 1 에서 등록하세요.');

	$sql = " insert into {$g5['character_table']} set {$sql_article}";
	sql_query($sql);

	$ch_id = sql_insert_id();
	if($mb['ch_id'] == "") sql_query("update {$g5['member_table']} set ch_id = '{$ch_id}' where mb_id = '{$mb['mb_id']}'");

	if($ch_body) { 
		$sql = " insert into {$g5['closthes_table']}
					set ch_id			= '{$ch_id}',
						cl_subject		= '기본의상',
						cl_path			= '{$ch_body}',
						cl_use			= '1',
						cl_type			= 'default',
						cl_time			= '1'";
		sql_query($sql);
	}

	// 캐릭터 생성 훅 — 모듈(reset_manager 등) 연동
	if (function_exists('mdl_do_action')) {
		mdl_do_action('character_create_after', $ch_id, array('mb_id' => $mb['mb_id'], 'context' => 'admin'));
	}

} else { 

	// 기존 캐릭터 데이터 호출
	$ch = get_character($ch_id);

	if(!$ch['ch_id'])
		alert("캐릭터 정보가 존재하지 않습니다.");
	if ($time > 1) {
		// Wave10 #6: 시기 N — character_table 대신 character_time_value 에 저장

		// 이미지 미변경 시 DB 원시 토큰값 유지 (CDN-resolved URL 로 덮어쓰지 않도록)
		$ctv_raw = array();
		if (!$ch_thumb_changed || !$ch_head_changed || !$ch_body_changed) {
			$_ti_info = function_exists('get_time_by_order') ? get_time_by_order($time) : array();
			$_ti_id   = !empty($_ti_info['ti_id']) ? (int)$_ti_info['ti_id'] : 0;
			if ($_ti_id > 0) {
				$ctv_raw = sql_fetch("SELECT ctv_thumb, ctv_head, ctv_body FROM `{$g5['time_value_table']}` WHERE ch_id='{$ch_id}' AND ti_id='{$_ti_id}'", false);
				if (!is_array($ctv_raw)) $ctv_raw = array();
			}
		}
		$tv_thumb = $ch_thumb_changed ? $ch_thumb : (string)(isset($ctv_raw['ctv_thumb']) ? $ctv_raw['ctv_thumb'] : '');
		$tv_head  = $ch_head_changed  ? $ch_head  : (string)(isset($ctv_raw['ctv_head'])  ? $ctv_raw['ctv_head']  : '');
		$tv_body  = $ch_body_changed  ? $ch_body  : (string)(isset($ctv_raw['ctv_body'])  ? $ctv_raw['ctv_body']  : '');

		if (function_exists('set_character_time_value')) {
			set_character_time_value($ch_id, $time, array(
				'ctv_name'  => $ch_name,
				'ctv_thumb' => $tv_thumb,
				'ctv_head'  => $tv_head,
				'ctv_body'  => $tv_body,
				'ctv_side'  => $ch_side,
				'ctv_class' => $ch_class,
			));
		}
		if (function_exists('set_character_default_clothing')) {
			set_character_default_clothing($ch_id, $time, $tv_body);
		}
	} else {
	
	if($ad['ad_use_thumb'] && !$ad['ad_url_thumb'] && $ch['ch_thumb'] != $ch_thumb) { 
		// 해당 서버에 업로드 한 파일일 경우
		$prev_file_path = str_replace(G5_URL, G5_PATH, $ch['ch_thumb']);
		if (function_exists('cdn_unlink')) { @cdn_unlink($prev_file_path); } else { @unlink($prev_file_path); }
	}
	if($ad['ad_use_head'] && !$ad['ad_url_head'] && $ch['ch_head'] != $ch_head) { 
		// 해당 서버에 업로드 한 파일일 경우
		$prev_file_path = str_replace(G5_URL, G5_PATH, $ch['ch_head']);
		if (function_exists('cdn_unlink')) { @cdn_unlink($prev_file_path); } else { @unlink($prev_file_path); }
	}
	if($ad['ad_use_body'] && !$ad['ad_url_body'] && $ch['ch_body'] != $ch_body) { 
		// 해당 서버에 업로드 한 파일일 경우
		$prev_file_path = str_replace(G5_URL, G5_PATH, $ch['ch_body']);
		if (function_exists('cdn_unlink')) { @cdn_unlink($prev_file_path); } else { @unlink($prev_file_path); }
	}


	$sql = " update {$g5['character_table']}
				set {$sql_article}
				where ch_id = '{$ch_id}'";
	sql_query($sql);
	
	if($mb['ch_id'] == "") sql_query("update {$g5['member_table']} set ch_id = '{$ch_id}' where mb_id = '{$mb['mb_id']}'");

	if($ch_body) { 
		// 옷장정보 불러오기
		$cl_sql = "select cl_id from {$g5['closthes_table']} where ch_id = '{$ch_id}' and cl_type = 'default' and cl_time = '1'";
		$cl = sql_fetch($cl_sql);

		if($cl['cl_id']) { 
			// 정보 업데이트
			$sql = " update {$g5['closthes_table']}
						set cl_path = '{$ch_body}'
						where cl_id = '{$cl['cl_id']}'";
			sql_query($sql);
		} else { 
			// 신규등록
			$sql = " insert into {$g5['closthes_table']}
					set ch_id			= '{$ch_id}',
						cl_subject		= '기본의상',
						cl_path			= '{$ch_body}',
						cl_use			= '1',
						cl_type			= 'default',
						cl_time			= '1'";
			sql_query($sql);
		}
	}
	} // end of else ($time<=1)
}


// --------------------- 추가 프로필 데이터
// 추가 항목 값 가져오기
for($i=0; $i < count($ar_code); $i++) { 
	$key = 'av_'.$ar_code[$i];

	// 파일 등록일 경우, 이미지 업로드 처리
	if ($_FILES['av_value_file']['name'][$i]) {
		// 확장자 따기
		$exp = explode(".", $_FILES['av_value_file']['name'][$i]);
		$exp = $exp[count($exp)-1];

		$image_name = "img_".$ar_code[$i].($time>1?"_t".$time:"")."_".time().".".$exp;
		upload_file($_FILES['av_value_file']['tmp_name'][$i], $image_name, $character_image_path);
		// cdn_after_upload 는 upload_file() 내부에서 이미 호출됨
		$av_value[$i] = $character_image_url."/".$image_name;
	}

	// CDN: 저장 시 토큰화
	if (function_exists('cdn_tokenize_url'))     $av_value[$i] = cdn_tokenize_url($av_value[$i]);
	if (function_exists('cdn_tokenize_content')) $av_value[$i] = cdn_tokenize_content($av_value[$i]);

	if ($time > 1) {
		// 시기 N: character_time_article 에 저장
		if (function_exists('set_character_time_article')) {
			set_character_time_article($ch_id, $ar_code[$i], $time, isset($av_value[$i]) ? $av_value[$i] : '');
		}
		continue;
	}

	$prev_value = sql_fetch("select av_value from {$g5['value_table']} where ch_id = '{$ch_id}' and ar_code = '{$ar_code[$i]}'");
	$prev_value = $prev_value['av_value'];

	if(isset($prev_value) && $prev_value != $av_value[$i] && strstr(G5_URL, $prev_value)) { 
		// 해당 서버에 업로드 한 파일일 경우
		$prev_file_path = str_replace(G5_URL, G5_PATH, $prev_value);
		if (function_exists('cdn_unlink')) { @cdn_unlink($prev_file_path); } else { @unlink($prev_file_path); }
	}

	$sql_article = "
		ch_id		= '{$ch_id}',
		ar_code		= '{$ar_code[$i]}',
		av_value	= '{$av_value[$i]}'
	";
	
	if(isset($prev_value)) {
		// 업데이트
		$sql = " update {$g5['value_table']}
					set {$sql_article}
					where ar_code = '{$ar_code[$i]}' and ch_id = '{$ch_id}'
		";
		sql_query($sql);
	} else {
		// 추가
		$sql = " insert into {$g5['value_table']}
					set {$sql_article}
		";
		sql_query($sql);
	}
}



// --------------------- 캐릭터 스탯 등록
if($time <= 1 && count($st_id) > 0) {
	// 저장되는 스탯 정보가 존재할 시
	for($i=0; $i < count($st_id); $i++) {
		$temp_st_id = $st_id[$i];
		$old_sc = sql_fetch("select * from {$g5['status_table']} where ch_id = '{$ch_id}' and st_id = '{$temp_st_id}'");

		if($old_sc['sc_id']) { 
			// 업데이트
			$sql = " update {$g5['status_table']}
						set sc_max = '{$sc_max[$i]}',
							sc_value = '{$sc_value[$i]}'
						where sc_id = '{$old_sc['sc_id']}'
			";
			sql_query($sql);

		} else {
			// 등록
			$sql = " insert into {$g5['status_table']}
						set		st_id		= '{$st_id[$i]}',
								ch_id		= '{$ch_id}',
								sc_max		= '{$sc_max[$i]}',
								sc_value	= '{$sc_value[$i]}'
			";
			sql_query($sql);
		}
	}
}


goto_url('./character_form.php?'.$qstr.'&amp;w=u&amp;ch_id='.$ch_id, false);
?>
