<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$character = array();
if (!isset($config['cf_time'])) { $config['cf_time'] = 1; }
if (isset($member['ch_id']) && (int)$member['ch_id'] > 0) {
	$character = get_character($member['ch_id']);
}

if ($is_member && isset($member['mb_id']) && isset($character['mb_id']) && isset($member['ch_id']) && isset($character['ch_id'])
	&& ($member['mb_id'] != $character['mb_id'] || $member['ch_id'] == '' || !$character['ch_id'])) {
	$mb_id_esc = sql_escape_string($member['mb_id']);
	$character_sql = "select * from {$g5['character_table']} where mb_id = '{$mb_id_esc}' limit 0, 1";
	$character = sql_fetch($character_sql);
	if (!empty($character['ch_id'])) {
		sql_query("update {$g5['member_table']} set ch_id = '".(int)$character['ch_id']."' where mb_id = '{$mb_id_esc}'");
	}
	if (is_array($character)) {
		apply_character_time($character, get_current_time());
	}
}

if (isset($character['ch_id']) && $character['ch_id']) {
	// 탐색/일일 카운터 등은 avo_reset_manager 가 lazy reset 처리 (extend/00_reset.lib.php 참고)
	// 레거시 스킨 호환: $character['ch_search'] / ch_search_date 를 reset_manager 값으로 동기화
	if (function_exists('reset_value_get')) {
		$character['ch_search']      = (int)reset_value_get('character_search', $character['ch_id']);
		$character['ch_search_date'] = defined('G5_TIME_YMD') ? G5_TIME_YMD : date('Ymd');
	}
}


// 캐릭터 네임 가져오기 (시기 적용 — ctv_name 있으면 우선, 없으면 시기 1 이름 폴백)
function get_character_name($ch_id, $time = 0)
{
	global $g5;
	$ch_id = (int)$ch_id;
	if ($ch_id <= 0) return '';
	$character = sql_fetch("select ch_name from {$g5['character_table']} where ch_id = '{$ch_id}'");
	$name = (string)ses($character, 'ch_name', '');

	if (!$time) $time = get_current_time();
	$time = (int)$time;
	if ($time > 1) {
		$ctv = get_character_time_value($ch_id, $time);
		if (!empty($ctv) && (string)$ctv['ctv_name'] !== '') {
			$name = (string)$ctv['ctv_name'];
		}
	}
	if (function_exists('mdl_apply_filters')) {
		$name = mdl_apply_filters('character_name', $name, $ch_id, $time);
	}
	return $name;
}

// 캐릭터 네임 가져오기 (멤버 ID검색으로,대표 캐릭터 가져오기) — 시기 적용
function get_member_character_name($mb_id, $time = 0)
{
	global $g5;
	$mb_id_esc = sql_escape_string($mb_id);
	$character = sql_fetch("select ch.ch_id, ch.ch_name from {$g5['character_table']} ch, {$g5['member_table']} mb where mb.mb_id= '{$mb_id_esc}' and ch.mb_id = mb.mb_id");
	$name = (string)ses($character, 'ch_name', '');

	if (!$time) $time = get_current_time();
	$time = (int)$time;
	if ($time > 1 && !empty($character['ch_id'])) {
		$ctv = get_character_time_value((int)$character['ch_id'], $time);
		if (!empty($ctv) && (string)$ctv['ctv_name'] !== '') {
			$name = (string)$ctv['ctv_name'];
		}
	}
	return $name;
}

// 캐릭터 썸네일 이미지 (시기 적용)
function get_character_head($ch_id, $time = 0)
{
	global $g5;
	$ch_id = (int)$ch_id;
	if ($ch_id <= 0) return '';

	if (!$time) $time = get_current_time();
	$time = (int)$time;

	if ($time <= 1) {
		$ch = sql_fetch("select ch_thumb from {$g5['character_table']} where ch_id = '{$ch_id}'");
		$thumb = (string)ses($ch, 'ch_thumb', '');
		$thumb = function_exists('cdn_resolve_url') ? cdn_resolve_url($thumb) : $thumb;
		return $thumb;
	}

	// 시기 N: 사이드 테이블만 사용 (폴백 안 함)
	$ctv = get_character_time_value($ch_id, $time);
	$thumb = is_array($ctv) ? (string)ses($ctv, 'ctv_thumb', '') : '';
	$thumb = function_exists('cdn_resolve_url') ? cdn_resolve_url($thumb) : $thumb;
	return $thumb;
}


// 유저 네임 가져오기
function get_member_name($mb_id)
{
	global $g5;
	$mb_id_esc = sql_escape_string($mb_id);
	$member = sql_fetch("select mb_nick from {$g5['member_table']} where mb_id = '{$mb_id_esc}'");
	return ses($member, 'mb_nick', '');
}

// 캐릭터 기본 정보 가져오기 (시기 적용)
function get_character($ch_id, $time = 0)
{
	global $g5;
	$ch_id = (int)$ch_id;
	if ($ch_id <= 0) return array();

	if (!$time) $time = get_current_time();

	$character = sql_fetch("select * from {$g5['character_table']} where ch_id = '{$ch_id}'");
	if (!is_array($character)) return array();

	apply_character_time($character, $time);

	// CDN: 이미지/본문 컬럼 토큰 치환 (모듈 비활성이면 G5_DATA_URL 폴백, 안전)
	if (function_exists('cdn_resolve_url')) {
		if (isset($character['ch_thumb'])) $character['ch_thumb'] = cdn_resolve_url($character['ch_thumb']);
	}
	if (function_exists('cdn_resolve_content')) {
		if (isset($character['ch_head'])) $character['ch_head'] = cdn_resolve_content($character['ch_head']);
		if (isset($character['ch_body'])) $character['ch_body'] = cdn_resolve_content($character['ch_body']);
	}
	// 레거시 스킨 호환: ch_search / ch_search_date 컬럼이 제거된 후에도
	// reset_manager 의 character_search 값을 가상 컬럼으로 주입.
	if (function_exists('reset_value_get')) {
		$character['ch_search']      = (int)reset_value_get('character_search', $ch_id);
		$character['ch_search_date'] = defined('G5_TIME_YMD') ? G5_TIME_YMD : date('Ymd');
	}
	if (function_exists('mdl_apply_filters')) {
		$character = mdl_apply_filters('character_loaded', $character, $ch_id, $time);
	}
	return $character;
}

// 캐릭터 추가 정보 가져오기 (시기 적용 — 시기 N 빈값=빈값, 폴백 안 함)
function get_character_info($ch_id, $ar_code, $time = 0)
{
	global $g5;
	$ch_id = (int)$ch_id;
	if ($ch_id <= 0) return '';

	if (!$time) $time = get_current_time();
	$time = (int)$time;

	if ($time <= 1) {
		$ar_code_esc = sql_escape_string($ar_code);
		$ch = sql_fetch("select av_value from {$g5['value_table']} where ch_id = '{$ch_id}' and ar_code = '{$ar_code_esc}'");
		$val = (string)ses($ch, 'av_value', '');
		return function_exists('cdn_resolve_content') ? cdn_resolve_content($val) : $val;
	}

	return get_character_time_article($ch_id, $ar_code, $time);
}

function get_character_list($side = '', $class = '', $state = '승인', $time = 0, $opts = array()) {
	global $g5, $config;

	if (!$time) $time = get_current_time();
	$character = array();

	$state_esc = sql_escape_string($state);
	$sql_search = '';
	if ($side) {
		$side_esc = sql_escape_string($side);
		$sql_search .= " and ch_side = '{$side_esc}' ";
	}
	if ($class) {
		$class_esc = sql_escape_string($class);
		$sql_search .= " and ch_class = '{$class_esc}' ";
	}

	// NPC 표시 처리
	$only_npc   = !empty($opts['only_npc']);
	$include_npc = isset($opts['include_npc']) ? (bool)$opts['include_npc'] : null;
	if ($include_npc === null) {
		$npc_mode = isset($config['cf_member_npc_mode']) ? $config['cf_member_npc_mode'] : 'split';
		$include_npc = ($npc_mode === 'merge');
	}

	if ($only_npc) {
		$sql_search .= " and ch_type = 'npc' ";
	} elseif ($include_npc) {
		$sql_search .= " and ch_type IN ('main','npc') ";
	} else {
		$sql_search .= " and ch_type = 'main' ";
	}

	// Wave10 #7: NPC 시기별 활성 필터
	if (function_exists('get_active_npc_ids_for_time') && ($only_npc || $include_npc)) {
		$_npc_ids = get_active_npc_ids_for_time($time);
		if (is_array($_npc_ids)) {
			if (count($_npc_ids) === 0) {
				if ($only_npc) {
					$sql_search .= " and 0 ";
				} else {
					// main 만 노출 — npc 제외
					$sql_search = preg_replace("/and ch_type IN \\('main','npc'\\)/", "and ch_type = 'main' ", $sql_search);
				}
			} else {
				$_ids_str = implode(',', array_map('intval', $_npc_ids));
				if ($only_npc) {
					$sql_search .= " and ch_id IN ({$_ids_str}) ";
				} else {
					// main 전체 + 매핑된 npc
					$sql_search = preg_replace("/and ch_type IN \\('main','npc'\\)/", "and (ch_type = 'main' OR (ch_type = 'npc' AND ch_id IN ({$_ids_str}))) ", $sql_search);
				}
			}
		}
	}

	// ORDER BY 처리 — cf_member_order 파싱 또는 기본값
	$order_raw = isset($opts['order_by']) ? $opts['order_by']
	           : (isset($config['cf_member_order']) ? $config['cf_member_order'] : 'ch_id asc');
	$allowed_cols = array('ch_id', 'ch_name', 'ch_rank', 'ch_class', 'ch_side', 'ch_order', 'ch_type');
	$order_parts  = array();
	foreach (array_filter(array_map('trim', explode(',', $order_raw))) as $part) {
		$p   = preg_split('/\s+/', trim($part));
		$col = isset($p[0]) ? $p[0] : '';
		$dir = isset($p[1]) ? strtoupper($p[1]) : 'ASC';
		if (!in_array($col, $allowed_cols)) continue;
		if (!in_array($dir, array('ASC', 'DESC'))) $dir = 'ASC';
		$order_parts[] = $col . ' ' . $dir;
	}

	// NPC top/bottom 보조 정렬
	if ($include_npc && !$only_npc) {
		$npc_pos = isset($config['cf_member_npc_pos']) ? $config['cf_member_npc_pos'] : 'top';
		if ($npc_pos === 'top') {
			array_unshift($order_parts, "(ch_type='npc') DESC");
		} elseif ($npc_pos === 'bottom') {
			array_unshift($order_parts, "(ch_type='npc') ASC");
		}
	}

	if (empty($order_parts)) $order_parts[] = 'ch_id ASC';
	$sql_order = implode(', ', $order_parts);

	$sql_common = "select *
			from	{$g5['character_table']}
			where	ch_state = '{$state_esc}'
					{$sql_search}
			order by {$sql_order}";

	$result = sql_query($sql_common);

	for ($i = 0; $row = sql_fetch_array($result); $i++) {
		apply_character_time($row, $time);
		if (function_exists('cdn_resolve_url')) {
			if (isset($row['ch_thumb'])) $row['ch_thumb'] = cdn_resolve_url($row['ch_thumb']);
		}
		$character[] = $row;
	}

	if (function_exists('mdl_apply_filters')) {
		$character = mdl_apply_filters('character_list', $character, $side, $class, $state, $time);
	}

	return $character;
}

/**
 * 캐릭터 뷰어 「옷장」 옆 도구 메뉴.
 * 훅 `member_viewer_tools` / `mypage_character_viewer_tools` 의 `{label, href}` 항목.
 * popup 기본 ON — 옷장과 같이 window.open.
 *
 * @param mixed $tools
 * @return array<int,array{label:string,href:string,popup:bool,target:string,features:string}>
 */
function character_viewer_normalize_tools($tools)
{
	if (!is_array($tools)) {
		return array();
	}
	$out = array();
	$default_feat = 'width=800,height=800,menubar=no,status=no,toolbar=no,location=no,scrollbars=yes,resizable=yes';
	foreach ($tools as $item) {
		if (!is_array($item)) {
			continue;
		}
		$label = isset($item['label']) ? trim((string)$item['label']) : '';
		$href = isset($item['href']) ? trim((string)$item['href']) : '';
		if ($label === '' || $href === '') {
			continue;
		}
		$popup = true;
		if (array_key_exists('popup', $item) && ($item['popup'] === false || $item['popup'] === 0 || $item['popup'] === '0')) {
			$popup = false;
		}
		$target = isset($item['target']) ? preg_replace('/[^a-zA-Z0-9_]/', '', (string)$item['target']) : '';
		if ($target === '') {
			$target = 'viewer_tool';
		}
		$features = isset($item['features']) ? trim((string)$item['features']) : '';
		$features = str_replace(array("'", '"', "\r", "\n", '\\'), '', $features);
		if ($features === '') {
			$features = $default_feat;
		}
		$out[] = array(
			'label'    => $label,
			'href'     => $href,
			'popup'    => $popup,
			'target'   => $target,
			'features' => $features,
		);
	}
	return $out;
}

/**
 * 도구 메뉴 한 칸 HTML. `$class` 는 a 태그에 붙는다.
 *
 * @param array $item
 * @param string $class
 * @return string
 */
function character_viewer_tool_html($item, $class = '')
{
	$norm = character_viewer_normalize_tools(array($item));
	if (!$norm) {
		return '';
	}
	$item = $norm[0];
	$class = trim((string)$class);
	$cls = $class !== '' ? ' class="'.htmlspecialchars($class, ENT_QUOTES, 'UTF-8').'"' : '';
	$href = htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8');
	$label = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
	if (empty($item['popup'])) {
		return '<a'.$cls.' href="'.$href.'">'.$label.'</a>';
	}
	// 옷장과 같이 onclick="..." 안에는 작은따옴표 JS 문자열.
	// json_encode 큰따옴표는 속성 경계를 끊어 window.open 이 무력화된다.
	$target = htmlspecialchars($item['target'], ENT_QUOTES, 'UTF-8');
	$features = htmlspecialchars($item['features'], ENT_QUOTES, 'UTF-8');
	return '<a'.$cls.' href="'.$href.'" onclick="window.open(this.href, \''.$target.'\', \''.$features.'\'); return false;">'.$label.'</a>';
}

/**
 * 도구 메뉴 전체를 이어 붙인 HTML.
 *
 * @param mixed $tools
 * @param string $class
 * @return string
 */
function character_viewer_tools_html($tools, $class = '')
{
	$html = '';
	foreach (character_viewer_normalize_tools($tools) as $item) {
		$html .= character_viewer_tool_html($item, $class);
	}
	return $html;
}
