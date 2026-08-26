<?php
include_once('./_common.php');
perm_check_page('member');

if (function_exists('mdl_do_action')) mdl_do_action('member_profile_view', isset($member['mb_id']) ? $member['mb_id'] : '');

// Wave10: 시기별 side 분리
//   - GET ?time= (또는 current_time) 기준
//   - 활성 side 0 → 한 덩어리로 (cf_side_title 미사용처럼)
//   - 활성 side ≥1 → cf_member_side_merge=true 면 활성 side 의 캐릭터만 한 덩어리, false 면 side 별 분리
$_view_time = isset($_GET['time']) ? (int)$_GET['time'] : (int)get_current_time();
if ($_view_time < 1) $_view_time = 1;

if (!check_time_access($_view_time, (int)$config['cf_time'], (bool)$is_admin, false)) {
    time_safe_redirect(G5_URL.'/member/index.php');
}

$list = array();
$side = array();
$ch_list = array();

$side_merge = !empty($config['cf_member_side_merge']);
$npc_mode   = isset($config['cf_member_npc_mode']) ? $config['cf_member_npc_mode'] : 'split';
$npc_pos    = isset($config['cf_member_npc_pos'])  ? $config['cf_member_npc_pos']  : 'top';
$npc_list   = array();

// 시기 활성 side 목록 조회
$_active_sides = function_exists('get_sides_for_time') ? get_sides_for_time($_view_time, 0) : null;

// $_active_sides === null 이면 매핑 미사용 → 기존 side 전체를 사용
if ($_active_sides === null && !empty($config['cf_side_title']) && !empty($g5['side_table'])) {
	$_active_sides = array();
	$_rs = sql_query("SELECT * FROM `{$g5['side_table']}` ORDER BY si_id ASC", false);
	while ($_r = sql_fetch_array($_rs)) $_active_sides[] = $_r;
}

$_has_active_side = is_array($_active_sides) && count($_active_sides) > 0;

if ($_has_active_side && !empty($config['cf_side_title'])) {
	// 활성 side 목록을 기준으로 분리/필터링
	//   - get_character_list 가 apply_character_time 후 ch_side override 적용함.
	//   - SQL 필터는 시기 1 기준이므로 PHP 단계에서 active si_id 로 다시 묶음.
	$_all_main = get_character_list('', '', '승인', $_view_time, array('include_npc' => ($npc_mode === 'merge')));

	$_active_ids = array();
	foreach ($_active_sides as $_si) $_active_ids[(int)$_si['si_id']] = true;

	// 활성 side 별 그룹화 (활성에 속하지 않으면 제외)
	$_grouped = array();
	foreach ($_active_sides as $_si) $_grouped[(int)$_si['si_id']] = array();
	foreach ($_all_main as $_ch) {
		$_csid = (int)($_ch['ch_side'] ?? 0);
		if (!isset($_active_ids[$_csid])) continue; // 비활성 side 의 캐릭터는 미출력
		$_grouped[$_csid][] = $_ch;
	}

	if ($side_merge) {
		// 통합: 활성 side 의 캐릭터를 한 덩어리로
		$_merged = array();
		foreach ($_grouped as $_arr) foreach ($_arr as $_c) $_merged[] = $_c;
		$list[] = $_merged;
		$side[] = '';
	} else {
		// 분리: side 별 (단, 분리 모드인데 활성 side 1개면 한 덩어리)
		if (count($_active_sides) < 2) {
			$_merged = array();
			foreach ($_grouped as $_arr) foreach ($_arr as $_c) $_merged[] = $_c;
			$list[] = $_merged;
			$side[] = '';
		} else {
			foreach ($_active_sides as $_si) {
				$list[] = $_grouped[(int)$_si['si_id']];
				$side[] = $_si;
			}
		}
	}
} else {
	// 활성 side 0개 또는 cf_side_title 미사용 → 한 덩어리
	$list[] = get_character_list('', '', '승인', $_view_time, array('include_npc' => ($npc_mode === 'merge')));
	$side[] = '';
}

// NPC 별도 목록 — 다음 두 경우에 채움:
//   - split 모드 (기존): NPC 를 메인 list 에서 제외 + 별도 섹션 출력
//   - merge 모드 + npc_pos !== 'inline' : 통합으로 가져왔지만 위치가 명시되어 있으므로 별도 분리
$_npc_separate = ($npc_mode === 'split') || ($npc_mode === 'merge' && $npc_pos !== 'inline');
if ($_npc_separate) {
	$npc_list = get_character_list('', '', '승인', $_view_time, array('only_npc' => true));
	if ($npc_mode === 'merge') {
		// merge 결과에서 NPC 만 빼서 별도 섹션으로 — 메인 list 는 NPC 제외 후 재구성
		foreach ($list as $_li => $_arr) {
			$list[$_li] = array_values(array_filter($_arr, function($_c){
				return ($_c['ch_type'] ?? '') !== 'npc';
			}));
		}
	}
}

// 그리드 설정 변수 (스킨에서 사용)
$member_thumb_w    = (int)($config['cf_member_thumb_w'] ?? 120);
$member_thumb_h    = (int)($config['cf_member_thumb_h'] ?? 160);
$member_grid_mode  = isset($config['cf_member_grid_mode']) ? $config['cf_member_grid_mode'] : 'auto-fill';
$member_grid_count = (int)($config['cf_member_grid_count'] ?? 4);



$g5['title'] = "멤버란";
include_once(G5_PATH.'/head.php');

include(function_exists('theme_dir_file') ? theme_dir_file('member/skin/list.skin.php') : (G5_PATH."/member/skin/list.skin.php"));

unset($ch);
unset($list);

include_once(G5_PATH.'/tail.php');
?>
