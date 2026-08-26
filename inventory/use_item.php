<?php
include_once('./_common.php');

function location_url($url) {
	return "LOCATIONURL||||{$url}";
}

if($url) { 
	$return_url = urldecode($url);
} else {
	$return_url = "./viewer.php?ch_id=".$ch_id;
}

$in = sql_fetch("select * from {$g5['inventory_table']} inven, {$g5['item_table']} item where inven.in_id = '{$in_id}' and inven.it_id = item.it_id");

if($in['ch_id'] == $character['ch_id']) {
	$ch = $character;
} else {
	$ch = get_character($in['ch_id']);
}

if(!$in['in_id']) { 
	echo "<p>아이템 보유 정보를 확인할 수 없습니다.</p>";
} else {
	$inven_function = $in['it_type'];

	// avo8.0: 모듈이 커스텀 it_type 을 가로채서 처리할 수 있도록 dispatch 필터 발사
	$_disp_handled = false;
	if (function_exists('mdl_apply_filters')) {
		$_d = mdl_apply_filters('inventory_use_dispatch', array(
			'handled'    => false,
			'echo'       => '',
		), array(
			'in'             => $in,
			'in_id'          => $in_id,
			'character'      => $ch,
			'inven_function' => $inven_function,
			'return_url'     => $return_url,
			'url'            => $url,
		));
		if (is_array($_d) && !empty($_d['handled'])) {
			$_disp_handled = true;
			if (!empty($_d['echo'])) echo $_d['echo'];
		}
	}

	if (!$_disp_handled) {

	/*
	 * inven_function 분기 — 다른 모듈이 추가한 기능은 'inventory_use_dispatch' 필터로 위 가로채기.
	 * default 절은 inventory/extend/*.php 자동 로드 (레거시 확장 포인트).
	 */
	switch ($inven_function) {

	case "프로필수정":
		// avo8.0: 시기별 프로필 수정 지원 — 사용자가 사용 시기를 선택하도록 드롭다운 노출
		// (관리자: 모든 시기 / 회원: 본인 캐릭터 + ti_open>=1 인 시기만)
		$_is_owner = (!empty($member['mb_id']) && isset($ch['mb_id']) && $ch['mb_id'] === $member['mb_id']);
		$_t_list = function_exists('get_time_list') ? get_time_list(false, (bool)$is_admin, $_is_owner) : array();
		$_t_writable = array();
		foreach ($_t_list as $_tr) {
			$_to = (int)$_tr['ti_order'];
			if (function_exists('check_time_write_access') && check_time_write_access($_to, (bool)$is_admin, $_is_owner)) {
				$_t_writable[] = $_tr;
			}
		}

		$_target_base = G5_URL."/mypage/character/character_form.php?w=u&ch_id=".(int)$in['ch_id']."&in_id=".(int)$in['in_id']."&url=".urlencode($url);

		if (empty($_t_writable)) {
			echo '<div class="default-form" style="padding:20px;text-align:center;">수정 가능한 시기가 없습니다.<br>(접근 권한이 있는 시기가 없습니다)</div>';
		} else if (count($_t_writable) === 1) {
			$_only = (int)$_t_writable[0]['ti_order'];
			echo location_url($_target_base.'&time='.$_only);
		} else {
?>
<div class="default-form inven-profile-time" style="padding:20px;">
	<h3 style="margin:0 0 12px;">프로필 수정 시기 선택</h3>
	<p style="margin:0 0 12px;color:#666;font-size:13px;">아이템을 사용하여 수정할 <strong>시기</strong>를 선택하세요. 접근 권한이 있는 시기만 표시됩니다.</p>
	<select id="inven_profile_time_sel" class="frm_input" style="min-width:220px;">
<?php foreach ($_t_writable as $_tr): $_to = (int)$_tr['ti_order']; ?>
		<option value="<?php echo $_to; ?>">#<?php echo $_to; ?> <?php echo htmlspecialchars($_tr['ti_name'], ENT_QUOTES, 'UTF-8'); ?></option>
<?php endforeach; ?>
	</select>
	<button type="button" class="btn_submit" id="inven_profile_time_go" style="margin-left:8px;">선택 후 이동</button>
</div>
<script>
(function(){
	var $sel = $('#inven_profile_time_sel');
	$('#inven_profile_time_go').on('click', function(){
		var t = parseInt($sel.val(), 10) || 1;
		location.href = <?php echo json_encode($_target_base); ?> + '&time=' + t;
	});
})();
</script>
<?php
		}
		break;

	case "아이템추가":
		include('./inc/add_item_form.php');
		break;

	case "스탯회복":
		set_status($ch['ch_id'], $in['st_id'], ($in['it_value'] * -1));
		delete_inventory($in['in_id'], $in['it_use_ever']);
		echo location_url($return_url);
		break;

	case "뽑기":
		$result = get_item_explo($in['ch_id'], $in['it_id']);
		delete_inventory($in['in_id'], $in['it_use_ever']);
		if (function_exists('mdl_do_action')) mdl_do_action('inventory_use_after', $in['in_id'], $in['ch_id'], $inven_function);
		if($result['it_name']) {
			alert("『{$result['it_name']}』 획득 성공!");
		} else {
			alert("아무것도 획득하지 못했습니다.", $return_url."&amp;tabs=i");
		}
		break;

	default:
		// 레거시 확장 포인트 — inventory/extend/*.php 자동 로드.
		// 새 모듈은 'inventory_use_dispatch' 필터를 사용하는 것을 권장.
		$use_extend_file = array();
		$use_tmp = dir(G5_PATH."/inventory/extend");
		while ($entry = $use_tmp->read()) {
			if (preg_match("/(\.php)$/i", $entry))
				$use_extend_file[] = $entry;
		}
		if(!empty($use_extend_file) && is_array($use_extend_file)) {
			natsort($use_extend_file);
			foreach($use_extend_file as $file) {
				include_once(G5_PATH."/inventory/extend/".$file);
			}
		}
		unset($use_extend_file);
		break;
	}

	} // end if(!$_disp_handled)
}?>