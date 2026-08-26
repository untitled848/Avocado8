<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);

set_session('ss_bo_table', $_REQUEST['bo_table']);
if(isset($_REQUEST['wr_id'])) set_session('ss_wr_id', $_REQUEST['wr_id']);

// ── avo8.0 댓글 제한 상수 (게시판 설정 → list/comment skin 공통) ──────
$mmb_climit_count    = (int)($board['bo_comment_limit_count']    ?? 0);
$mmb_climit_time     = (int)($board['bo_comment_limit_time']     ?? 0); // 시간(h)
$mmb_climit_text     = (int)($board['bo_comment_limit_text']     ?? 0);
$mmb_climit_text_exc = trim((string)($board['bo_comment_limit_text_exc'] ?? ''));
$mmb_climit_excs     = $mmb_climit_text_exc !== '' ? array_values(array_filter(array_map('trim', explode(',', $mmb_climit_text_exc)), 'strlen')) : array();

// 게시판 댓글수/최초 댓글 시각 캐시 (per-wr_id) — list.log.skin.php 에서 차단 판정용
// mb_cnt 는 "원글 포함 해당 멤버가 쓴 글 수" (원글 자신이 본인이면 +1)
$mmb_cmt_meta = array();
if (($mmb_climit_count > 0 || $mmb_climit_time > 0) && !empty($list) && is_array($list)) {
    $_ids = array();
    foreach ($list as $_li) { if (!empty($_li['wr_id'])) $_ids[] = (int)$_li['wr_id']; }
    if (!empty($_ids)) {
        $_in = implode(',', $_ids);
        $_mb = ($is_member && !empty($member['mb_id'])) ? sql_escape_string($member['mb_id']) : '';
        $_mb_clause = $_mb !== '' ? " AND mb_id = '{$_mb}' " : '';
        $_r = sql_query("SELECT wr_parent, COUNT(*) AS cnt FROM {$write_table} WHERE wr_parent IN ({$_in}) AND wr_is_comment = 1 {$_mb_clause} GROUP BY wr_parent", false);
        if ($_r) while ($_row = sql_fetch_array($_r)) {
            $mmb_cmt_meta[(int)$_row['wr_parent']]['mb_cnt'] = (int)$_row['cnt'];
        }
        // 부모 글 작성시각 + 본인 원글 여부 (원글도 카운트)
        $_r2 = sql_query("SELECT wr_id, wr_datetime, mb_id FROM {$write_table} WHERE wr_id IN ({$_in})", false);
        if ($_r2) while ($_row = sql_fetch_array($_r2)) {
            $_pid = (int)$_row['wr_id'];
            $mmb_cmt_meta[$_pid]['parent_ts'] = $_row['wr_datetime'] ? strtotime($_row['wr_datetime']) : 0;
            if ($_mb !== '' && !empty($_row['mb_id']) && $_row['mb_id'] === $member['mb_id']) {
                $mmb_cmt_meta[$_pid]['mb_cnt'] = (int)($mmb_cmt_meta[$_pid]['mb_cnt'] ?? 0) + 1;
            }
        }
    }
}
?>
<script>
// avo8.0: 댓글/본문 글자수 라이브 카운터 (제외 문자 지원)
(function(){
    if (window.mmbBindCommentLimit) return;
    window.mmbBindCommentLimit = function(textareaEl, max, excList){
        if (!textareaEl || !max || max <= 0) return;
        var box = textareaEl.parentNode;
        if (!box) return;
        if (getComputedStyle(box).position === 'static') box.style.position = 'relative';
        var badge = document.createElement('div');
        badge.className = 'mmb-cl-badge';
        badge.style.cssText = '';
        box.appendChild(badge);
        function count(){
            var v = textareaEl.value || '';
            if (excList && excList.length) {
                for (var i=0;i<excList.length;i++){ v = v.split(excList[i]).join(''); }
            }
            var len = Array.from(v).length; // codepoint
            if (len > max) {
                // 초과분 잘라내기 (제외 문자는 유지)
                var raw = textareaEl.value, kept = '', n = 0;
                var arr = Array.from(raw);
                for (var j=0;j<arr.length;j++){
                    var c = arr[j];
                    var isExc = excList && excList.indexOf(c) !== -1;
                    if (isExc) { kept += c; continue; }
                    if (n >= max) {
                        // trailing 제외 문자만 따라붙이기
                        for (var k=j;k<arr.length;k++){ if (excList.indexOf(arr[k]) !== -1) kept += arr[k]; else break; }
                        break;
                    }
                    kept += c; n++;
                }
                textareaEl.value = kept;
                len = max;
            }
            badge.textContent = len + ' / ' + max + (excList && excList.length ? ' (제외:' + excList.join('') + ')' : '');
        }
        textareaEl.addEventListener('input', count);
        textareaEl.addEventListener('keyup', count);
        count();
    };
})();
</script>
<?php

// 사용 가능 아이템 목록 ($mmb_item) 은 inventory 모듈이 bbs_enqueue_assets 단계에서 채워준다.
//   - 이 게시판에서 inventory 모듈이 비활성이거나 캐릭터가 없으면 빈 배열로 떨어짐
//   - 동일 쿼리 중복 실행을 막기 위해 여기선 fallback 만 둔다.
if (!isset($mmb_item) || !is_array($mmb_item)) {
    if (!empty($character['ch_id']) && function_exists('inventory_get_usable_items')) {
        $mmb_item = inventory_get_usable_items($character);
    } else {
        $mmb_item = array();
    }
}


$owner_front = get_style('mmb_owner_name', 'cs_etc_2');		// 자기 로그 접두문자
$owner_front = $owner_front['cs_etc_2'];
$owner_behind = get_style('mmb_owner_name', 'cs_etc_3');		// 자기 로그 접미문자
$owner_behind = $owner_behind['cs_etc_3'];

?>

<div id="load_log_board">


<!-- 자비란 상단 공지 부분 -->
<?php  if($board['bo_content_head']) { ?>
	<div class="board-notice">
		<?php echo stripslashes($board['bo_content_head']);?>
	</div>
<?php  } ?>

<?php 
	/*-------------------------------------------
		동접자 카운터 설정
	---------------------------------------------*/
	$wiget = get_style('mmb_counter');
	if($wiget['cs_value']) { echo '<div class="connect-wiget">'.$wiget['cs_value'].'</div>'; }
?>

	<!-- 공지사항 한줄 롤링 -->
	<div class="marquee mmb-notice">
		<span><i><?php echo $config['cf_10']?></i></span>
	</div>
	<!-- // 공지사항 한줄 롤링 -->

<!-- 게시판 카테고리 시작 { -->
	<?php if ($is_category) { ?>
	<nav id="navi_category">
		<ul>
			<?php echo $category_option ?>
		</ul>
	</nav>
	<?php } ?>
<!-- } 게시판 카테고리 끝 -->

	<div class="ui-mmb-button">
	<?php if ($write_href) { 
		// 췩 사용 여부를 체크 한다.
		if($board['bo_use_chick']) { // 췩 사용 가능할 경우, 파일 업로드 폼을 생성한다.
			$write['wr_subject'] = '--|UPLOADING|--';

			$upload_action_url = G5_BBS_URL."/write_update.php";
	?>
		<div class="ui-mmb-list-write">
			<form name="fwrite" id="fwrite" action="<?php echo $upload_action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
				<input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
				<input type="hidden" name="w" value="<?php echo $w ?>">
				<input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
				<input type="hidden" name="wr_id" value="<?php echo $wr_id ?>">
				<input type="hidden" name="redirect" value="1">
				<input type="hidden" name="wr_subject" value="<?php echo $write['wr_subject']?>" />
				<input type="hidden" name="wr_content" value="" />
				<input type="hidden" name="wr_width" id="wr_width" value="<?php echo $write['wr_width']; ?>">
				<input type="hidden" name="wr_height" id="wr_height" value="<?php echo $write['wr_height']; ?>">

				<?php  if(!$is_member) { ?>
				<fieldset class="guest-box">
					<div class="name">
						<label>이름</label>
						<input type="text" name="wr_name" value="방문자" />
					</div>
					<div class="pw">
						<label>비밀번호</label>
						<input type="password" name="wr_password" value="" />
					</div>
				</fieldset>
				<?php  } ?>

				<div class="upload-box">
					<select name="wr_type" onchange="fn_log_type(this.value);">
						<option value="UPLOAD" <?php echo $write['wr_type'] == "UPLOAD" ? "selected" : ""?>>UPLOAD</option>
						<option value="URL" <?php echo $write['wr_type'] == "URL" ? "selected" : ""?>>URL</option>
					</select>
					
					<fieldset>
						<div id="add_UPLOAD" <?php echo $write['wr_type'] == "URL" ? "style='display: none;'" : ""?>>
							<input type="file" id="wr_file" name="bf_file[]" title="로그등록 :  용량 <?php echo $upload_max_filesize ?> 이하만 업로드 가능" class="frm_file frm_input view_image_area" />
						</div>
						<div id="add_URL" <?php echo $write['wr_type'] != "URL" ? "style='display: none;'" : ""?>>
							<input type="text" name="wr_url" value="<?php echo $write['wr_url']?>" title="이미지 링크를 가져와 주시길 바랍니다." id="wr_url" class="frm_input view_image_area" placeholder="이미지 링크 입력"/>
						</div>
					</fieldset>
					<button type="submit" id="btn_submit" accesskey="s" class="ui-btn">업로드</button>
				</div>
				<fieldset>
				<?php  if($is_member) { ?>
					<span>
						<input type="checkbox" id="wr_secret" name="wr_secret" value="1" <?php echo $write['wr_secret'] ? "checked" : ""?>/>
						<label for="wr_secret">멤버공개</label>
					</span>
					<span>
						<input type="checkbox" id="wr_adult" name="wr_adult" value="1" <?php echo $write['wr_adult'] ? "checked" : ""?>/>
						<label for="wr_adult">19금</label>
					</span>
				<?php  } ?>
					<span>
						<input type="checkbox" id="wr_wide" name="wr_wide" value="1" <?php echo $write['wr_wide'] ? "checked" : ""?>/>
						<label for="wr_wide">리플창 아래로</label>
					</span>
					<?php  if($board['bo_use_noname'] && $is_member) { ?>
					<span>
						<input type="checkbox" id="wr_noname" name="wr_noname" value="1" <?php echo $write['wr_noname'] ? "checked" : ""?>/>
						<label for="wr_noname">익명</label>
					</span>
					<?php  } ?>
					<span>
						<input type="checkbox" id="wr_plip" name="wr_plip" value="1" <?php echo $write['wr_plip'] ? "checked" : ""?>/>
						<label for="wr_plip">로그접기</label>
					</span>
				</fieldset>
			</form>
			<script>
				function fn_log_type(type) { 
					$('#add_'+type).siblings().hide();
					$('#add_'+type).show();

					$('#wr_url').val('');
					$('#wr_file').replaceWith( $('#wr_file').clone(true) );
				}
				function fwrite_submit(f) {
					if(f.wr_type.value == 'UPLOAD') {
						if(document.getElementById('wr_file').value == '') { 
							alert("업로드할 로그를 등록해 주시길 바랍니다.");
							return false;
						}
					} else if(f.wr_type.value == 'URL') { 
						if(document.getElementById('wr_url').value == '') { 
							alert("등록할 로그 URL을 입력해 주시길 바랍니다.");
							return false;
						}
					}
					document.getElementById("btn_submit").disabled = "disabled";
					return true;
				}
			</script>
		</div>
	<?php  } else { ?>
		<a href="<?php echo $write_href ?>" class="ui-btn point small">등록하기</a>
	<?php  } } ?>
		<a href="<?php echo $list_href ?>" class="ui-btn small">새로고침</a>
		<a href="<?php echo G5_BBS_URL ?>/emoticon_list.php" class="ui-btn small new_win">이모티콘</a>
	</div>


	<?php  if($write_pages) { ?><div class="ui-paging"><?php echo $write_pages;  ?></div><?php  } ?>


	<!-- 리스트 시작 -->
	<div id="log_list" class="none-trans">
	<?php 
		for ($i=0; $i<count($list); $i++) {
			$list_item = $list[$i];
			include($board_skin_path."/list.log.skin.php");
		}
		if (count($list) == 0) { echo "<div class=\"empty_list\">등록된 로그가 없습니다.</div>"; } 
	?>
	</div>

	<?php  if($write_pages) { ?>
	<div class="ui-paging">
		<?php echo $write_pages;  ?>
	</div>
<?php  } ?>

	<div class="searc-sub-box">

		<form name="fsearch" method="get">
			<input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
			<input type="hidden" name="sca" value="<?php echo $sca ?>">
			<input type="hidden" name="sop" value="and">
			<input type="hidden" name="hash" value="<?php echo $hash?>">

			<div class="ui-search-box">
				<fieldset class="sch_category select-box">
					<select name="sfl" id="sfl">
						<option value="wr_subject,1"<?php echo get_selected($sfl, 'wr_subject,1', true); ?>>캐릭터</option>
						<option value="wr_content"<?php echo get_selected($sfl, 'wr_content'); ?>>코멘트</option>
						<option value="wr_name,1"<?php echo get_selected($sfl, 'wr_name,1'); ?>>오너</option>
						<option value="wr_name"<?php echo get_selected($sfl, 'wr_name'); ?>>오너(코)</option>
					</select>
				</fieldset>
				<fieldset class="sch_text">
					<input type="text" name="stx" value="<?php echo stripslashes($stx) ?>" id="stx" class="frm_input" maxlength="20">
				</fieldset>
				<fieldset class="sch_button">
					<button type="submit" class="ui-btn point">검색</button>
				</fieldset>
			</div>
			<div class="ui-search-box">
				<fieldset class="sch_category">
					<span>해시태그</span>
				</fieldset>
				<fieldset class="sch_text">
					<input type="text" name="hash" value="<?php echo $hash?>" class="frm_input" maxlength="20">
				</fieldset>
				<fieldset class="sch_button">
					<button type="submit" class="ui-btn point">검색</button>
				</fieldset>
			</div>

			<div class="ui-search-box last">
				<fieldset class="sch_category">
					<span>로그번호</span>
				</fieldset>
				<fieldset class="sch_text">
					<input type="text" name="log" value="<?php echo $log?>" class="frm_input" maxlength="20">
				</fieldset>
				<fieldset class="sch_button">
					<button type="submit" class="ui-btn point">검색</button>
				</fieldset>
			</div>

		</form>
	</div>

</div>

<script>
var avo_mb_id = "<?php echo $member['mb_id']?>";
var avo_board_skin_path = "<?php echo $board_skin_path?>";
var avo_board_skin_url = "<?php echo $board_skin_url?>";

var save_before = '';
var save_html = '';

function fviewcomment_submit(f)
{
	set_comment_token(f);
	var pattern = /(^\s*)|(\s*$)/g; // \s 공백 문자

	var content = "";
	$.ajax({
		url: g5_bbs_url+"/ajax.filter.php",
		type: "POST",
		data: {
			"content": f.wr_content.value
		},
		dataType: "json",
		async: false,
		cache: false,
		success: function(data, textStatus) {
			content = data.content;
		}
	});

	if (content) {
		alert("내용에 금지단어('"+content+"')가 포함되어있습니다");
		f.wr_content.focus();
		return false;
	}
	
	if (!f.wr_content.value) {
		alert("댓글을 입력하여 주십시오.");
		return false;
	}

	if (typeof(f.wr_name) != 'undefined')
	{
		f.wr_name.value = f.wr_name.value.replace(pattern, "");
		if (f.wr_name.value == '')
		{
			alert('이름이 입력되지 않았습니다.');
			f.wr_name.focus();
			return false;
		}
	}

	if (typeof(f.wr_password) != 'undefined')
	{
		f.wr_password.value = f.wr_password.value.replace(pattern, "");
		if (f.wr_password.value == '')
		{
			alert('비밀번호가 입력되지 않았습니다.');
			f.wr_password.focus();
			return false;
		}
	}

	return true;
}

function comment_delete()
{
	return confirm("이 댓글을 삭제하시겠습니까?");
}

function comment_box(co_id, wr_id) { 

	if($('#c_'+co_id).find('.modify_area').is(':visible')) {
		$('.modify_area').hide();
		$('.original_comment_area').show();
		co_id = '';
		wr_id = '';
	} else {
		$('.modify_area').hide();
		$('.original_comment_area').show();

		$('#c_'+co_id).find('.modify_area').show();
		$('#c_'+co_id).find('.original_comment_area').hide();
		$('#save_co_comment_'+co_id).focus();
	}

	var modify_form = document.getElementById('frm_modify_comment');
	modify_form.wr_id.value = wr_id;
	modify_form.comment_id.value = co_id;
}

function modify_commnet(co_id) { 
	var modify_form = document.getElementById('frm_modify_comment');
	var wr_content = $('#save_co_comment_'+co_id).val();

	modify_form.wr_content.value = wr_content;
	$('#frm_modify_comment').submit();
}

</script>

<form name="modify_comment" id="frm_modify_comment"  action="./write_comment_update.php" onsubmit="return fviewcomment_submit(this);" method="post" autocomplete="off">
	<input type="hidden" name="w" value="cu">
	<input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
	<input type="hidden" name="sca" value="<?php echo $sca ?>">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="spt" value="<?php echo $spt ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">

	<input type="hidden" name="comment_id" value="">
	<input type="hidden" name="wr_id" value="">
	<textarea name="wr_content" style="display: none;"></textarea>
	<button type="submit" style="display: none;"></button>
</form>

<script src="<?php echo $board_skin_url ?>/js/load.board.js"></script>
