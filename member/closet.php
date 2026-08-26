<?php
include_once('./_common.php');
perm_check_page('member');

$ch_id = isset($ch_id) ? (int)$ch_id : (isset($_GET['ch_id']) ? (int)$_GET['ch_id'] : 0);
if ($ch_id <= 0) alert("캐릭터 정보가 존재하지 않습니다.");

$ch = sql_fetch("select * from {$g5['character_table']} where ch_id='{$ch_id}'");
if($ch['ch_state']!=='승인'&&$ch['mb_id']!==($member['mb_id'] ?? '')){ 
    perm_check_page('ready');
}
if(!is_array($ch) || !$ch['ch_id']) {
	alert("캐릭터 정보가 존재하지 않습니다.");
}

// ---------------- 시기 결정 + 권한 체크 ----------------
//   - GET ti_time 우선, 없으면 현재 시기
//   - viewer.php 와 동일한 check_time_access() 게이트 적용
//   - viewer 진입 권한과 자동 연동됨 (동일한 시기별 권한 사용)
$mb_owner = isset($ch['mb_id']) ? get_member($ch['mb_id']) : array();
$is_owner = (!empty($member['mb_id']) && !empty($mb_owner['mb_id']) && $member['mb_id'] === $mb_owner['mb_id']);

$_time = isset($_GET['ti_time']) ? (int)$_GET['ti_time'] : (int)get_current_time();
if (function_exists('check_time_access') && !check_time_access($_time, (int)$config['cf_time'], (bool)$is_admin, $is_owner)) {
    if (function_exists('time_safe_redirect')) {
        time_safe_redirect(G5_URL.'/member/closet.php', array('ch_id' => $ch_id));
    } else {
        alert('해당 시기의 옷장에 접근할 권한이 없습니다.');
    }
}

if (function_exists('apply_character_time')) apply_character_time($ch, $_time);
$g5['title'] = $ch['ch_name']." 옷장";

include_once('./_head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/closet.css">', 0);

$cl = array();
$_time_int = (int)$_time;
$cl_result = sql_query("select * from {$g5['closthes_table']} where ch_id = '{$ch_id}' and cl_time = '{$_time_int}' order by cl_type desc, cl_id asc");
$str_array_data = "";
$array_data = "";
for($i=0; $row=sql_fetch_array($cl_result); $i++) { 
	$cl[$i] = $row;
	$array_data .= "{$str_array_data}'{$row['cl_subject']}'";
	$str_array_data = ",";
}

?>

<div id="closet_page" class="none-trans">
	<div class="closet-menu theme-box">
		<div class="pager"></div>
	</div>

	<div class="swiper-container">
		<ul class="swiper-wrapper">
	<?php  for($i=0; $i < count($cl); $i++) { ?>
			<li class="swiper-slide">
				<a href="<?php echo $cl[$i]['cl_path']?>" onclick="window.open(this.href, 'big_viewer', 'width=500 height=800 menubar=no status=no toolbar=no location=no scrollbars=yes resizable=yes'); return false;" style="background-image:url(<?php echo $cl[$i]['cl_path']?>);"></a>
			</li>
	<?php  } ?>
		</ul>
	</div>
</div>

<script src="<?php echo G5_JS_URL ?>/swiper.js"></script>
<script>
var cl_name = [<?php echo $array_data?>];
$(function() {
	var swiper = new Swiper("#closet_page .swiper-container", {
		loop:true,
		pagination: {
			el: "#closet_page .pager",
			clickable: true,
			renderBullet: function (index, className) {
				return '<span class="' + className + '">' + cl_name[index] + "</span>";
			}
		},
		autoplay: {
			delay: 4500,
			disableOnInteraction: false,
		},
	});
});
</script>

<?php
include_once('./_tail.sub.php');
?>
