<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$yoil = array("일","월","화","수","목","금","토");


$is_add_register = $config['cf_1'] ? true : false;
$is_add_character = $config['cf_2'] ? true : false;
// 캐릭터 수정 허용: cf_character_edit_admin_only=1 이면 관리자만, 0 이면 회원도 가능
$is_mod_character = empty($config['cf_character_edit_admin_only']) ? true : (!empty($is_admin));
// true = 멤버가 캐릭터를 만들면 즉시 승인 + default 타임라인에 사용자 등록
// false = 기존처럼 수정중/대기 후 관리자 승인
$ch_member_auto_approve = false;
// 탐색 사용 가능 여부와 일일 횟수는 bbs_action_search 모듈에서 관리.


// MMB LIST 이미지 가져오기
function get_mmb_image($bo_table, $wr_id)
{
	global $g5, $config;
	$filename = $alt = "";

	$sql = " select bf_file, bf_width, bf_height from {$g5['board_file_table']}
				where bo_table = '$bo_table' and wr_id = '$wr_id' and bf_type between '1' and '3' order by bf_no limit 0, 1 ";
	$row = sql_fetch($sql);

	if($row['bf_file']) {
		$rel = 'file/'.$bo_table.'/'.$row['bf_file'];
		$src = function_exists('cdn_data_url') ? cdn_data_url($rel) : G5_DATA_URL.'/'.$rel;
		$w = $row['bf_width'];
		$h = $row['bf_height'];
	} else {
		return false;
	}

	$thumb = array("src"=>$src, "width"=>$w, "height"=>$h);
	return $thumb;
}

function del_html($str)
{
	$str = str_replace( ">", "&gt;",$str );
	$str = str_replace( "<", "&lt;",$str );
	$str = str_replace( "\"", "&quot;",$str );
	$str = str_replace( "&lt;br&gt;","<br>",$str); //br은되게함
	return $str;
}
// 입력 폼 안내문
function help($help="")
{
	global $g5;
	$str  = '<span class="frm_info">'.str_replace("\n", "<br>", $help).'</span>';
	return $str;
}


// 파일을 업로드 함
function upload_file($srcfile, $destfile, $dir)
{
    if ($destfile == "") return false;
    // 업로드 한후 , 퍼미션을 변경함
    @move_uploaded_file($srcfile, $dir.'/'.$destfile);
    @chmod($dir.'/'.$destfile, G5_FILE_PERMISSION);

    // CDN: 업로드 직후 미러링 액션 발사 (모듈 cdn_cloudflare 가 R2로 복사)
    //   - 모듈 비활성이면 아무 일도 일어나지 않음 (cdn_after_upload 가 자체 가드)
    //   - data 디렉토리 외부 경로면 자동으로 무시됨
    if (function_exists('cdn_after_upload')) {
        cdn_after_upload($dir.'/'.$destfile);
    }
    return true;
}



function check_site_auth(){
	global $g5, $config, $is_member;

	if ($is_member) {
		return;
	}

	$scope = (string)($config['cf_open_scope'] ?? 'all');
	if ($scope === 'all') {
		return;
	}

	// 회원 공개(member) + 계정생성 허용 시 회원가입 페이지는 통과
	if (function_exists('perm_is_guest_allowed_page') && perm_is_guest_allowed_page()) {
		return;
	}

	goto_url(G5_BBS_URL.'/login.php');
}

// 메타태그를 이용한 URL 이동
// header("location:URL") 을 대체
function goto_url_top($url)
{
    $url = str_replace("&amp;", "&", $url);

    // common.php 최상단 G5_FORCE_HTTP 스위치: https → http 강제 변환
    if (defined('G5_FORCE_HTTP') && G5_FORCE_HTTP) {
        $url = preg_replace('#^https://#i', 'http://', $url);
    }

    if (!headers_sent())
        header('Location: '.$url);
    else {
        echo '<script>';
        echo 'top.location.replace("'.$url.'");';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
        echo '</noscript>';
    }
    exit;
}

// 로고 정보 가져오기 — 활성 디자인 프리셋(pd_id) 기준
function get_logo($type) {
	global $g5;

	$pd_id = function_exists('get_active_design') ? (int)get_active_design('main') : 1;
	if ($pd_id <= 0) $pd_id = 1;

	$cs_name = ($type == 'pc') ? 'logo' : 'm_logo';
	$logo = sql_fetch("select cs_value from {$g5['css_table']} where cs_name = '{$cs_name}' and pd_id = '{$pd_id}'");
	// 프리셋별 로고 다운로드 레코드가 없으면 default(=1) 폴백
	if (empty($logo['cs_value']) && $pd_id !== 1) {
		$logo = sql_fetch("select cs_value from {$g5['css_table']} where cs_name = '{$cs_name}' and pd_id = '1'");
	}

	return $logo['cs_value'];
}

// 스타일 가져오기 — 활성 디자인 프리셋(pd_id) 기준
function get_style($code, $field = '*') {
	global $g5;

	$pd_id = function_exists('get_active_design') ? (int)get_active_design('main') : 1;
	if ($pd_id <= 0) $pd_id = 1;

	if(!$field) $field = " * ";
	$result = sql_fetch("select ".$field." from {$g5['css_table']} where cs_name = '".$code."' and pd_id = '{$pd_id}'");
	// 폴백
	if (empty($result) && $pd_id !== 1) {
		$result = sql_fetch("select ".$field." from {$g5['css_table']} where cs_name = '".$code."' and pd_id = '1'");
	}
	return $result;
}

// 사이트 화면 디자인 가져오기
function get_site_content($co_id) {
	global $g5, $config, $is_member;

	$result = "";
	$co = sql_fetch( " select * from {$g5['content_table']} where co_id = '{$co_id}' ");

	$pc_str = conv_content($co['co_content'], $co['co_html'], $co['co_tag_filter_use']);
	$mo_str = conv_content($co['co_mobile_content'], $co['co_html'], $co['co_tag_filter_use']);

	// 내용 변환 진행
	$no_mem = "";
	$use_mem = "";

	if($is_member) { $no_mem = " style='display: none;' "; }
	if(!$is_member) { $use_mem = " style='display: none;' "; }

	if(strstr($pc_str, "{{LOGIN}}") || strstr($mo_str, "{{LOGIN}}")) { 
		ob_start();
		include G5_PATH.'/templete/txt.login.php';
		$login_str = ob_get_contents();
		ob_end_clean();

		$pc_str = str_replace("{{LOGIN}}", $login_str, $pc_str);
		$mo_str = str_replace("{{LOGIN}}", $login_str, $mo_str);
	}

	if(strstr($pc_str, "{{LOGOUT}}") || strstr($mo_str, "{{LOGOUT}}")) { 
		ob_start();
		include G5_PATH.'/templete/txt.logout.php';
		$logout_str = ob_get_contents();
		ob_end_clean();

		$pc_str = str_replace("{{LOGOUT}}", $logout_str, $pc_str);
		$mo_str = str_replace("{{LOGOUT}}", $logout_str, $mo_str);
	}

	if(strstr($pc_str, "{{JOIN}}") || strstr($mo_str, "{{JOIN}}")) { 
		ob_start();
		include G5_PATH.'/templete/txt.join.php';
		$join_str = ob_get_contents();
		ob_end_clean();

		$pc_str = str_replace("{{JOIN}}", $join_str, $pc_str);
		$mo_str = str_replace("{{JOIN}}", $join_str, $mo_str);
	}

	if(strstr($pc_str, "{{MYPAGE}}") || strstr($mo_str, "{{MYPAGE}}")) { 
		ob_start();
		include G5_PATH.'/templete/txt.mypage.php';
		$mypage_str = ob_get_contents();
		ob_end_clean();

		$pc_str = str_replace("{{MYPAGE}}", $mypage_str, $pc_str);
		$mo_str = str_replace("{{MYPAGE}}", $mypage_str, $mo_str);
	}

	if(strstr($pc_str, "{{BGM}}") || strstr($mo_str, "{{BGM}}")) { 
		ob_start();
		include G5_PATH.'/templete/txt.bgm.php';
		$visual_str = ob_get_contents();
		ob_end_clean();

		$pc_str = str_replace("{{BGM}}", $visual_str, $pc_str);
		$mo_str = str_replace("{{BGM}}", $visual_str, $mo_str);
	}

	if(strstr($pc_str, "{{VISUAL_SLIDE}}") || strstr($mo_str, "{{VISUAL_SLIDE}}")) { 
		ob_start();
		include G5_PATH.'/templete/txt.visual.php';
		$visual_str = ob_get_contents();
		ob_end_clean();

		$pc_str = str_replace("{{VISUAL_SLIDE}}", $visual_str, $pc_str);
		$mo_str = str_replace("{{VISUAL_SLIDE}}", $visual_str, $mo_str);
	}

	if(strstr($pc_str, "{{OUTLOGIN}}") || strstr($mo_str, "{{OUTLOGIN}}")) { 
		ob_start();
		include G5_PATH.'/templete/txt.outlogin.php';
		$visual_str = ob_get_contents();
		ob_end_clean();

		$pc_str = str_replace("{{OUTLOGIN}}", $visual_str, $pc_str);
		$mo_str = str_replace("{{OUTLOGIN}}", $visual_str, $mo_str);
	}

	if(strstr($pc_str, "{{NOTICE}}") || strstr($mo_str, "{{NOTICE}}")) {
		$notice_str = '';
		if (function_exists('get_widget')) {
			$notice_str = get_widget('notice');
		} else if (function_exists('notice_widget_html')) {
			$notice_str = notice_widget_html();
		}
		$pc_str = str_replace("{{NOTICE}}", $notice_str, $pc_str);
		$mo_str = str_replace("{{NOTICE}}", $notice_str, $mo_str);
	}

	if($co['co_content']) {
		if($co['co_mobile_content']) {
			$result .= "<div class='only-pc'>".$pc_str."</div>";
		} else {
			$result .= $pc_str;
		}
	}
	if($co['co_mobile_content']) {
		$result .= "<div class='not-pc'>".$mo_str."</div>";
	}

	if (function_exists('mdl_apply_filters')) {
		$result = mdl_apply_filters('site_content', $result, $co_id);
	}

	return $result;
}


// 쪽지 보내기
function send_memo($se_mb_id, $re_mb_id, $memo_content) {
	global $g5, $config;
	
	// 쪽지 INSERT
	$tmp_row = sql_fetch(" select max(me_id) as max_me_id from {$g5['memo_table']} ");
	$me_id = $tmp_row['max_me_id'] + 1;

	$sql = " insert into {$g5['memo_table']} 
			set	me_id = '{$me_id}',
				me_recv_mb_id = '{$re_mb_id}',
				me_send_mb_id = '{$se_mb_id}',
				me_send_datetime = '".G5_TIME_YMDHIS."',
				me_memo = '{$memo_content}'";
	sql_query($sql);

	$se_mb_name = get_member_name($se_mb_id);

	// 실시간 알림 (통합 push_call 이용)
	push_call('memo',
		['mb_id' => $se_mb_id, 'mb_name' => $se_mb_name],
		[$re_mb_id],
		cut_str(strip_tags($memo_content), 50));

	if (function_exists('mdl_do_action')) {
		mdl_do_action('memo_send_after', $se_mb_id, $re_mb_id, $memo_content);
	}

	return true;
}


function emote_ev($comment) {
	global $g5;
	static $cache = null;
	if ($cache === null) {
		$cache = array();
		// emoticon 테이블 컬럼 호환: em_use 컬럼이 있으면 use=1 만 적용 (5번 신청기능 대응)
		$has_use = sql_fetch("SHOW COLUMNS FROM `{$g5['emoticon_table']}` LIKE 'em_use'", false);
		$where   = $has_use ? " WHERE em_use = '1' " : '';
		$emo_result = sql_query("select me_text, me_img from {$g5['emoticon_table']} {$where}", false);
		if ($emo_result) {
			while ($row = sql_fetch_array($emo_result)) {
				$cache[$row['me_text']] = $row['me_img'];
			}
		}
	}
	if (empty($cache)) return $comment;
	$str = $comment;
	foreach ($cache as $text => $img) {
		$str = str_replace($text, "<img src=\"".G5_URL.$img."\">", $str);
	}
	return $str;
}

function have_jongsung ($chr) {
    static $no_jongsung = "가갸거겨고교구규그기개걔게계과괘궈궤괴귀긔까꺄꺼껴꼬꾜꾸뀨끄끼깨꺠께꼐꽈꽤꿔꿰꾀뀌끠나냐너녀노뇨누뉴느니내냬네녜놔놰눠눼뇌뉘늬다댜더뎌도됴두듀드디대댸데뎨돠돼둬뒈되뒤듸따땨떠뗘또뚀뚜뜌뜨띠때떄떼뗴똬뙈뚸뛔뙤뛰띄라랴러려로료루류르리래럐레례롸뢔뤄뤠뢰뤼릐마먀머며모묘무뮤므미매먜메몌뫄뫠뭐뭬뫼뮈믜바뱌버벼보뵤부뷰브비배뱨베볘봐봬붜붸뵈뷔븨빠뺘뻐뼈뽀뾰뿌쀼쁘삐빼뺴뻬뼤뽜뽸뿨쀄뾔쀠쁴사샤서셔소쇼수슈스시새섀세셰솨쇄숴쉐쇠쉬싀싸쌰써쎠쏘쑈쑤쓔쓰씨쌔썌쎄쎼쏴쐐쒀쒜쐬쒸씌아야어여오요우유으이애얘에예와왜워웨외위의자쟈저져조죠주쥬즈지재쟤제졔좌좨줘줴죄쥐즤짜쨔쩌쪄쪼쬬쭈쮸쯔찌째쨰쩨쪠쫘쫴쭤쮀쬐쮜쯰차챠처쳐초쵸추츄츠치채챼체쳬촤쵀춰췌최취츼카캬커켜코쿄쿠큐크키캐컈케켸콰쾌쿼퀘쾨퀴킈타탸터텨토툐투튜트티태턔테톄톼퇘퉈퉤퇴튀틔파퍄퍼펴포표푸퓨프피패퍠페폐퐈퐤풔풰푀퓌픠하햐허혀호효후휴흐히해햬헤혜화홰훠훼회휘희2459";
    if ($chr === '' || $chr === null || $chr === false) return false;
    return strpos($no_jongsung, (string)$chr) === false ? true : false;
}

function j ($s, $have_jongsung) {
	switch($have_jongsung) { 
		case "은" : 
			$no_jongsung = "는";
		break;
		case "이" : 
			$no_jongsung = "가";
		break;
		case "을" : 
			$no_jongsung = "를";
		break;
	}

	
    $last_chr = mb_substr($s, -1, 1, 'UTF-8');
    return have_jongsung($last_chr) ?
        $have_jongsung :
        $no_jongsung;
}
?>