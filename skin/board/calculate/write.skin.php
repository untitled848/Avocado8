<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);

if($w == '') $write['wr_subject'] = $character['ch_name'];

// 정산 신청 기능 (bo_1 = 1)
$_calc_req_enabled = (isset($board['bo_1']) && $board['bo_1'] === '1');
$_calc_req_presets = array();
$_calc_req_existing = array(); // 수정 시 기존 신청 내용 복원
if ($_calc_req_enabled) {
    if (function_exists('payout_preset_list')) $_calc_req_presets = payout_preset_list(true);
    if ($w === 'u' && !empty($write['wr_link1'])) {
        $tmp = json_decode($write['wr_link1'], true);
        if (is_array($tmp) && isset($tmp['_calc_req'])) $_calc_req_existing = $tmp;
    }
}
?>

<?php  if($board['bo_content_head']) { ?>
	<div class="board-notice">
		<?php echo stripslashes($board['bo_content_head']);?>
	</div><hr class="padding" />
<?php  } ?>

<section id="bo_w">

	<!-- 게시물 작성/수정 시작 { -->
	<form name="fwrite" id="fwrite" action="<?php echo $action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
	<input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
	<input type="hidden" name="w" value="<?php echo $w ?>">
	<input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
	<input type="hidden" name="wr_id" value="<?php echo $wr_id ?>">
	<input type="hidden" name="sca" value="<?php echo $sca ?>">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="spt" value="<?php echo $spt ?>">
	<input type="hidden" name="sst" value="<?php echo $sst ?>">
	<input type="hidden" name="sod" value="<?php echo $sod ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">
	<input type="hidden" name="wr_subject" value="<?php echo $write['wr_subject'] ?>">
	<?php
	$option = '';
	$option_hidden = '';
	if ($is_notice || $is_html || $is_secret || $is_mail) {
		$option = '';
		if ($is_notice) {
			$option .= "\n".'<input type="checkbox" id="notice" name="notice" value="1" '.$notice_checked.'>'."\n".'<label for="notice">공지</label>';
		}

		if ($is_html) {
			if ($is_dhtml_editor) {
				$option_hidden .= '<input type="hidden" value="html1" name="html">';
			} else {
				$option .= "\n".'<input type="checkbox" id="html" name="html" onclick="html_auto_br(this);" value="'.$html_value.'" '.$html_checked.'>'."\n".'<label for="html">html</label>';
			}
		}

		if ($is_secret) {
			if ($is_admin || $is_secret==1) {
				$option .= "\n".'<input type="checkbox" id="secret" name="secret" value="secret" '.$secret_checked.'>'."\n".'<label for="secret">비밀글</label>';
			} else {
				$option_hidden .= '<input type="hidden" name="secret" value="secret">';
			}
		}

		if ($is_mail) {
			$option .= "\n".'<input type="checkbox" id="mail" name="mail" value="mail" '.$recv_email_checked.'>'."\n".'<label for="mail">답변메일받기</label>';
		}
	}
	echo $option_hidden;
	?>

	<div class="board-write">
		<?php if ($is_category) { ?>
		<nav id="write_category">
			<select name="ca_name" id="ca_name" required class="required" >
				<option value="">선택하세요</option>
				<?php echo $category_option ?>
			</select>
			&nbsp;&nbsp;
			<?php echo $option ?>
		</nav>
		<?php } ?>

		<div class="wr_content" style="margin-top: 10px;">
			<?php if($write_min || $write_max) { ?>
			<!-- 최소/최대 글자 수 사용 시 -->
			<p id="char_count_desc">이 게시판은 최소 <strong><?php echo $write_min; ?></strong>글자 이상, 최대 <strong><?php echo $write_max; ?></strong>글자 이하까지 글을 쓰실 수 있습니다.</p>
			<?php } ?>
			<?php echo $editor_html; // 에디터 사용시는 에디터로, 아니면 textarea 로 노출 ?>
			<?php if($write_min || $write_max) { ?>
			<!-- 최소/최대 글자 수 사용 시 -->
			<div id="char_count_wrap"><span id="char_count"></span>글자</div>
			<?php } ?>
		</div>

		<?php if ($_calc_req_enabled) { ?>
		<div class="wr_calc_request" style="margin-top:14px; padding:10px; background:#f8f8ff; border:1px solid #ccd; border-radius:4px;">
			<h4 style="margin:0 0 8px;">정산 신청</h4>
			<div style="margin:4px 0;">
				<label>프리셋: </label>
				<select name="calc_req_preset" id="calc_req_preset">
					<option value="0">- 사용 안함 -</option>
					<?php foreach ($_calc_req_presets as $p) {
						$sel = (!empty($_calc_req_existing['preset_id']) && (int)$_calc_req_existing['preset_id'] === (int)$p['cp_id']) ? 'selected' : '';
					?>
						<option value="<?php echo (int)$p['cp_id']; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($p['cp_name'], ENT_QUOTES, 'UTF-8'); ?></option>
					<?php } ?>
				</select>
			</div>
			<div style="margin:4px 0;">
				<label>화폐: </label>
				<input type="number" name="calc_req_money" value="<?php echo isset($_calc_req_existing['money']) ? (int)$_calc_req_existing['money'] : 0; ?>" min="0" style="width:120px;">
				<label style="margin-left:14px;">경험치: </label>
				<input type="number" name="calc_req_exp" value="<?php echo isset($_calc_req_existing['exp']) ? (int)$_calc_req_existing['exp'] : 0; ?>" min="0" style="width:120px;">
			</div>
			<p class="frm_info" style="margin:4px 0 0; color:#666; font-size:12px;">관리자가 글 보기에서 [확인] 을 누르면 위 내용이 작성자 캐릭터에게 지급됩니다.</p>
		</div>
		<?php } ?>

	</div>

	<hr class="padding" />
	<div class="btn_confirm txt-center">
		<input type="submit" value="작성완료" id="btn_submit" accesskey="s" class="btn_submit ui-btn point">
		<a href="./board.php?bo_table=<?php echo $bo_table ?>" class="btn_cancel ui-btn">취소</a>
	</div>
	</form>

	<script>
	<?php if($write_min || $write_max) { ?>
	// 글자수 제한
	var char_min = parseInt(<?php echo $write_min; ?>); // 최소
	var char_max = parseInt(<?php echo $write_max; ?>); // 최대
	check_byte("wr_content", "char_count");

	$(function() {
		$("#wr_content").on("keyup", function() {
			check_byte("wr_content", "char_count");
		});
	});

	<?php } ?>
	function html_auto_br(obj)
	{
		if (obj.checked) {
			result = confirm("자동 줄바꿈을 하시겠습니까?\n\n자동 줄바꿈은 게시물 내용중 줄바뀐 곳을<br>태그로 변환하는 기능입니다.");
			if (result)
				obj.value = "html2";
			else
				obj.value = "html1";
		}
		else
			obj.value = "";
	}

	function fwrite_submit(f)
	{
		<?php echo $editor_js; // 에디터 사용시 자바스크립트에서 내용을 폼필드로 넣어주며 내용이 입력되었는지 검사함   ?>

		var subject = "";
		var content = "";
		$.ajax({
			url: g5_bbs_url+"/ajax.filter.php",
			type: "POST",
			data: {
				"subject": f.wr_subject.value,
				"content": f.wr_content.value
			},
			dataType: "json",
			async: false,
			cache: false,
			success: function(data, textStatus) {
				subject = data.subject;
				content = data.content;
			}
		});

		if (subject) {
			alert("제목에 금지단어('"+subject+"')가 포함되어있습니다");
			f.wr_subject.focus();
			return false;
		}

		if (content) {
			alert("내용에 금지단어('"+content+"')가 포함되어있습니다");
			if (typeof(ed_wr_content) != "undefined")
				ed_wr_content.returnFalse();
			else
				f.wr_content.focus();
			return false;
		}

		if (document.getElementById("char_count")) {
			if (char_min > 0 || char_max > 0) {
				var cnt = parseInt(check_byte("wr_content", "char_count"));
				if (char_min > 0 && char_min > cnt) {
					alert("내용은 "+char_min+"글자 이상 쓰셔야 합니다.");
					return false;
				}
				else if (char_max > 0 && char_max < cnt) {
					alert("내용은 "+char_max+"글자 이하로 쓰셔야 합니다.");
					return false;
				}
			}
		}


		document.getElementById("btn_submit").disabled = "disabled";

		return true;
	}
	</script>
</section>
<!-- } 게시물 작성/수정 끝 -->
