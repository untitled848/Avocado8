<?php
$sub_menu = "200010";
include_once('./_common.php');

if (!$is_admin) alert('관리자만 접근 가능합니다.');
check_admin_token();

$cf_register_level        = (int)($_POST['cf_register_level'] ?? 2);
$cf_register_point        = (int)($_POST['cf_register_point'] ?? 0);
$cf_leave_day             = (int)($_POST['cf_leave_day'] ?? 0);
$cf_register_nick_min     = (int)($_POST['cf_register_nick_min'] ?? 2);
$cf_register_nick_max     = (int)($_POST['cf_register_nick_max'] ?? 20);
$cf_register_nick_filter  = trim($_POST['cf_register_nick_filter'] ?? '');
if (!in_array($cf_register_nick_filter, array('', 'ko', 'koen'), true)) $cf_register_nick_filter = '';
$cf_register_birth_mode   = trim($_POST['cf_register_birth_mode'] ?? 'year_only');
if (!in_array($cf_register_birth_mode, array('year_only', 'adult_check', 'none'), true)) $cf_register_birth_mode = 'year_only';

// G5_ESCAPE_FUNCTION 이 $_POST 전체에 addslashes 를 사전 적용하므로
// stripslashes 로 되돌린 뒤 sql_escape_string 을 한 번만 적용한다.
$cf_prohibit_id           = sql_escape_string(stripslashes((string)($_POST['cf_prohibit_id'] ?? '')));
$cf_stipulation           = sql_escape_string(stripslashes((string)($_POST['cf_stipulation'] ?? '')));
$cf_privacy               = sql_escape_string(stripslashes((string)($_POST['cf_privacy'] ?? '')));

// avo: 추가 입력폼 (cf_register_extra_fields) 정리
//   - 한 줄: code|label|type|required
//   - type 은 text / textarea / select(opt1||opt2||...)
//   - 코드는 [A-Za-z0-9_] 만, 라벨은 비어있으면 제외, select 인데 옵션 없으면 제외
$_xf_in    = stripslashes((string)($_POST['cf_register_extra_fields'] ?? ''));
$_xf_lines = preg_split('/\r\n|\r|\n/', $_xf_in);
$_xf_clean = array();
$_xf_seen  = array();
foreach ($_xf_lines as $_ln) {
	$_parsed = function_exists('register_extra_parse_line') ? register_extra_parse_line($_ln) : null;
	if (!$_parsed) continue;
	$_code  = preg_replace('/[^A-Za-z0-9_]/', '', $_parsed['code']);
	$_label = $_parsed['label'];
	$_tr    = $_parsed['type_raw'];
	$_req   = $_parsed['required'];
	if ($_code === '' || $_label === '') continue;
	// avo: 코드가 mb_ 로 시작하지 않으면 자동 prefix (member_table 컬럼명 규칙).
	if (!preg_match('/^mb_/i', $_code)) $_code = 'mb_'.$_code;
	$_code = strtolower($_code);
	if (function_exists('register_extra_reserved_codes') && in_array($_code, register_extra_reserved_codes(), true)) {
		alert('코드 '.$_code.' 는 회원 테이블 예약 컬럼이라 추가 입력 항목으로 사용할 수 없습니다.');
	}
	if (isset($_xf_seen[$_code])) continue; // 중복 코드 무시
	if (preg_match('/^select\s*\((.*)\)$/i', $_tr, $_m)) {
		$_opts = array_values(array_filter(array_map('trim', explode('||', $_m[1])), 'strlen'));
		if (!$_opts) continue;
		$_tpart = 'select('.implode('||', $_opts).')';
	} elseif ($_tr === 'textarea') {
		$_tpart = 'textarea';
	} else {
		$_tpart = 'text';
	}
	$_xf_clean[]      = $_code.'|'.$_label.'|'.$_tpart.'|'.$_req;
	$_xf_seen[$_code] = true;
}
$cf_register_extra_fields = sql_escape_string(implode("\n", $_xf_clean));
$cf_register_help         = sql_escape_string(stripslashes((string)($_POST['cf_register_help'] ?? '')));
$cf_register_nick_filter_e = sql_escape_string($cf_register_nick_filter);
$cf_register_birth_mode_e  = sql_escape_string($cf_register_birth_mode);

sql_query("UPDATE `{$g5['config_table']}` SET
	cf_register_level        = '{$cf_register_level}',
	cf_register_point        = '{$cf_register_point}',
	cf_leave_day             = '{$cf_leave_day}',
	cf_register_nick_min     = '{$cf_register_nick_min}',
	cf_register_nick_max     = '{$cf_register_nick_max}',
	cf_register_nick_filter  = '{$cf_register_nick_filter_e}',
	cf_register_birth_mode   = '{$cf_register_birth_mode_e}',
	cf_prohibit_id           = '{$cf_prohibit_id}',
	cf_stipulation           = '{$cf_stipulation}',
	cf_privacy               = '{$cf_privacy}',
	cf_register_extra_fields = '{$cf_register_extra_fields}',
	cf_register_help         = '{$cf_register_help}'");

// avo: 추가 입력폼 정의가 바뀔 때마다 member_table 에 누락된 컬럼을 자동 생성.
//   - 컬럼은 textarea→TEXT, 그 외→VARCHAR(255) 기본값 ''.
//   - textarea 인데 varchar 이면 TEXT 로 승격. 축소는 하지 않음.
//   - ALTER 실패는 모아서 관리자에게 알린다.
$_alter_fail = array();
foreach ($_xf_clean as $_def) {
	$_p = explode('|', $_def);
	$_c = $_p[0] ?? '';
	$_t = $_p[2] ?? 'text';
	if ($_c === '' || !preg_match('/^mb_[a-z0-9_]+$/i', $_c)) continue;
	$_chk = sql_fetch("SHOW COLUMNS FROM `{$g5['member_table']}` WHERE Field = '{$_c}'", false);
	if ($_chk) {
		$_type = strtolower((string)($_chk['Type'] ?? ''));
		if ($_t === 'textarea' && strpos($_type, 'varchar') !== false) {
			$_ok = sql_query("ALTER TABLE `{$g5['member_table']}` MODIFY COLUMN `{$_c}` TEXT NULL", false);
			if ($_ok === false) $_alter_fail[] = $_c.' (TEXT 승격 실패)';
		}
		continue;
	}
	if ($_t === 'textarea') {
		$_ddl = "ALTER TABLE `{$g5['member_table']}` ADD COLUMN `{$_c}` TEXT NULL";
	} else {
		$_ddl = "ALTER TABLE `{$g5['member_table']}` ADD COLUMN `{$_c}` VARCHAR(255) NOT NULL DEFAULT ''";
	}
	$_ok = sql_query($_ddl, false);
	if ($_ok === false) $_alter_fail[] = $_c;
}

if (!empty($_alter_fail)) {
	alert('회원가입 설정은 저장됐으나 회원 테이블 컬럼 생성/변경에 실패한 항목이 있습니다: '.implode(', ', $_alter_fail), './member_register_form.php');
}

goto_url('./member_register_form.php');
