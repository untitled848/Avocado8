<?php 
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
$colspan = 5;
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);

if($is_member) { 
	set_session('ss_bo_table', $_REQUEST['bo_table']);
}

// 기본 설정 불러오기
$commu_conf = sql_fetch(" select * from {$g5['article_default_table']} ");

$write_action_url = G5_BBS_URL."/write_update.php";

// 정산 신청/지급 UI 활성 여부 (게시판 setting.hook.php 의 bo_1)
$_calc_req_enabled = (isset($board['bo_1']) && $board['bo_1'] === '1');
$_calc_req_presets = ($_calc_req_enabled && function_exists('payout_preset_list')) ? payout_preset_list(true) : array();
$_calc_all_presets = ($is_admin && function_exists('payout_preset_list')) ? payout_preset_list(false) : array();
?>

<?php  if($board['bo_content_head']) { ?>
	<div class="board-notice">
		<?php echo stripslashes($board['bo_content_head']);?>
	</div><hr class="padding" />
<?php  } ?>

<?php  if ($write_href) { 
	$upload_action_url = G5_BBS_URL."/write_update.php";
	$category_option = get_category_option($bo_table, $sca);
?>
<div class="list-write-area">
	<a href="#" class="ui-btn" style="margin: 0 auto; display: flex; width:150px;" onclick="$('#write_box').slideToggle(); return false;">정산글 등록하기</a>

	<div id="write_box" class="none-trans theme-box" style="display: none;">
		<form name="fwrite" id="fwrite" action="<?php echo $upload_action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
			<input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
			<input type="hidden" name="w" value="<?php echo $w ?>">
			<input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
			<input type="hidden" name="wr_id" value="<?php echo $wr_id ?>">
			<input type="hidden" name="redirect" value="1">
			<input type="hidden" name="wr_subject" value="<?php echo $character['ch_name'] ? $character['ch_name'] : "GUEST"?>" />

			<div class="list-write-box">
				<select name="ca_name" id="ca_name" required class="required" >
					<option value="">정산 분류 선택</option>
					<?php echo $category_option ?>
				</select>
				
				<fieldset>
					
					<?php if ($_calc_req_enabled) { ?>
					<div class="calc-req-box">
						<strong>정산 신청</strong>
						<div style="margin:4px 0;">
							<label>프리셋:
								<select name="calc_req_preset">
									<option value="0">- 사용 안함 -</option>
									<?php foreach ($_calc_req_presets as $p) { ?>
										<option value="<?php echo (int)$p['cp_id']; ?>"><?php echo htmlspecialchars($p['cp_name'], ENT_QUOTES, 'UTF-8'); ?></option>
									<?php } ?>
								</select>
							</label>
						</div>
						<div style="margin:4px 0;">
							<label><?php echo $config['cf_money']; ?>: <input type="number" name="calc_req_money" value="0" min="0" style="width:100px;"></label>
							<label style="margin-left:10px;"><?php echo $config['cf_exp_name']; ?>: <input type="number" name="calc_req_exp" value="0" min="0" style="width:100px;"></label>
						</div>
						<p class="frm_info" style="margin:4px 0 0; color:#666; font-size:12px;">운영자가 승인하면 정산됩니다.</p>
					</div>
					<?php } ?>
					<textarea name="wr_content"></textarea>

				</fieldset>
				<button type="submit" id="btn_submit" accesskey="s" class="ui-btn">등록하기</button>
			</div>
		</form>
		<script>
			function fwrite_submit(f) {
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
			
	</div>
</div>
<?php  } ?>


<div class="board-skin-caculate">

	<!-- 게시판 카테고리 시작 { -->
	<?php  if ($is_category) { ?>
	<nav  class="board-category">
		<select name="sca" id="sca" onchange="location.href='?bo_table=<?php echo $bo_table?>&sca=' + this.value;">
			<option value="">전체</option>
			<?php  echo $category_option ?>
		</select>
	</nav>
	<?php  } ?>
	<!-- } 게시판 카테고리 끝 -->

	<div class="board-list">
<?php 
	for ($i=0; $i<count($list); $i++) {
		$data = $list[$i];
		$ch = get_character($data['ch_id']);
		$content = conv_content($data['wr_content'], 0, 'wr_content');
?>
		<div class="calc-item">
			<div class="thumb">
				<img src="<?php echo $ch['ch_thumb']?>" />
			</div>
			<div class="con-box theme-box">
				<div class="inner">
					<p class="name">
						<?php  if($data['wr_10']) { ?><i class="state" data-item = "<?php echo $data['wr_10']?>"><?php echo $data['wr_10']?></i><?php  } ?>
						<span>
							<a href="<?php echo G5_URL?>/member/viewer.php?ch_id=<?php echo $ch['ch_id']?>"><?php echo $ch['ch_name']?></a>
							[ <?php echo $data['wr_name']?> ]

							<?php 
								if(($data['mb_id'] == $member['mb_id'] && $data['wr_10'] == '') || $is_admin) { 
									$delete_href ='./delete.php?bo_table='.$bo_table.'&amp;wr_id='.$data['wr_id'];
							?>
						</span>
						<sup class="calc-btn-box">
							<a href="#" onclick="$(this).closest('.con-box').find('.modify-con').toggle(); return false;" class="btn-mod">수정</a>
							<a href="<?php echo $delete_href?>"  class="btn-del">삭제</a>
						</sup>
						<?php  } ?>
					</p>
					<div class="con">
						<?php echo $content?>
					</div>

					<?php  if(($data['mb_id'] == $member['mb_id'] && $data['wr_10'] == '') || $is_admin) { ?>
					<div class="modify-con">
						<form name="fwrite_<?php echo $data['wr_id']?>" action="<?php echo $write_action_url ?>" method="post" onsubmit="return fwrite_submit(this);" autocomplete="off">
							<input type="hidden" name="w" value="u">
							<input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
							<input type="hidden" name="wr_10" value="<?php echo $data['wr_10'] ?>">
							<input type="hidden" name="wr_id" value="<?php echo $data['wr_id'] ?>">
							<input type="hidden" name="wr_subject" value="<?php echo $data['wr_subject'] ?>">
							<select name="ca_name">
								<option value="">정산 분류 선택</option>
								<?php echo get_category_option($bo_table, $data['ca_name']); ?>
							</select>
							<textarea name="wr_content"><?php echo $data['wr_content']?></textarea>
							<button type="submit" id="btn_submit" accesskey="s" class="btn_submit ui-btn point">내용수정완료</button>
						</form>
					</div>
					<?php  } ?>

					<?php  include($board_skin_path."/view_comment.php");?>
					<?php  if($is_admin) { ?>
					<?php
						// 작성자 캐릭터에게 정산 지급 — 관리자 전용
						$_pay_ch_id = (int)$data['ch_id'];
						$_pay_req   = array();
						if (!empty($data['wr_link1'])) {
							$_t = json_decode($data['wr_link1'], true);
							if (is_array($_t) && !empty($_t['_calc_req'])) $_pay_req = $_t;
						}
						$_pay_paid = !empty($_pay_req['paid']);
						if ($_pay_ch_id > 0&&!$data['wr_10']) {
					?>
					<div class="calc-admin-payout" data-wr-id="<?php echo (int)$data['wr_id']; ?>" style="margin:8px 0; padding:8px; background:#ffffff50; border:1px solid grey; ">
						<strong>지급 실행 후 댓글을 달아 주세요.</strong>
						<?php if (!empty($_pay_req)) { ?>
							<span style="font-size:12px; margin-left:8px; color:<?php echo $_pay_paid?'#080':'#a40'; ?>;">
								<?php echo $_pay_paid ? '(이미 지급됨)' : '(작성자 신청 있음)'; ?>
							</span>
						<?php } ?>
						<form class="calc-pay-form" onsubmit="return calc_payout_submit(this);" style="margin-top:6px;">
							<input type="hidden" name="bo_table" value="<?php echo htmlspecialchars($bo_table, ENT_QUOTES, 'UTF-8'); ?>">
							<input type="hidden" name="wr_id"    value="<?php echo (int)$data['wr_id']; ?>">
							<div style="margin:4px 0;">
								<label>프리셋:
									<select name="preset_id">
										<option value="0">- 사용 안함 -</option>
										<?php foreach ($_calc_all_presets as $p) {
											$sel = (!empty($_pay_req['preset_id']) && (int)$_pay_req['preset_id'] === (int)$p['cp_id']) ? 'selected' : '';
										?>
											<option value="<?php echo (int)$p['cp_id']; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($p['cp_name'], ENT_QUOTES, 'UTF-8'); ?></option>
										<?php } ?>
									</select>
								</label>
								<span style="margin-left:10px; color:#666; font-size:12px;">신청자 화폐/경험치/프리셋이 자동 반영됨. 추가 항목은 아래에서.</span>
							</div>
							<div style="margin:4px 0;">
								<label>신청 <?php echo $config['cf_money']?>: <input type="number" name="extra_money" value="<?php echo isset($_pay_req['money'])?(int)$_pay_req['money']:0; ?>" style="width:100px;"></label>
								<label style="margin-left:10px;">신청  <?php echo $config['cf_exp_name']?>: <input type="number" name="extra_exp" value="<?php echo isset($_pay_req['exp'])?(int)$_pay_req['exp']:0; ?>" style="width:100px;"></label>
							</div>
							<div style="margin:4px 0;">
								<strong style="display:block; margin-bottom:4px;">추가 지급 항목</strong>
								<?php
									$payload_field   = 'payload';
									$payload_uid     = 'cp'.(int)$data['wr_id'];
									$payload_initial = array();
									include G5_ADMIN_PATH.'/_partial/calc_payload_editor.inc.php';
								?>
							</div>
							<div style="margin:4px 0;">
								<label>정산내용: <input type="text" name="memo" value="<?php echo htmlspecialchars($data['wr_subject'], ENT_QUOTES, 'UTF-8'); ?>" style="width:60%;"></label>
							</div>
							<div style="margin:6px 0 0;">
								<button type="submit" class="ui-btn point" <?php echo $_pay_paid ? 'disabled' : ''; ?>>확인 (지급 실행)</button>
								<span class="calc-pay-result" style="margin-left:8px;"></span>
							</div>
						</form>
					</div>
					<?php } ?>
					<div class="comment-form-box">
						<?php  include($board_skin_path."/write_comment.php");?>
					</div>
					<?php  } ?>
				</div>
			</div>
		</div>


<?php  } ?>
	</div>

	<!-- 페이지 -->
	<?php  echo $write_pages;  ?>

	<!-- 게시판 검색 시작 { -->
	<fieldset id="bo_sch" class="txt-center">
		<legend>게시물 검색</legend>

		<form name="fsearch" method="get">
		<input type="hidden" name="bo_table" value="<?php  echo $bo_table ?>">
		<input type="hidden" name="sca" value="<?php  echo $sca ?>">
		<input type="hidden" name="sop" value="and">
		<select name="sfl" id="sfl">
			<option value="wr_subject"<?php  echo get_selected($sfl, 'wr_subject', true); ?>>캐릭터명</option>
			<option value="wr_content"<?php  echo get_selected($sfl, 'wr_content'); ?>>내용</option>
			<option value="mb_id,1"<?php  echo get_selected($sfl, 'mb_id,1'); ?>>회원아이디</option>
			<option value="mb_id,0"<?php  echo get_selected($sfl, 'mb_id,0'); ?>>회원아이디(코)</option>
			<option value="wr_name,1"<?php  echo get_selected($sfl, 'wr_name,1'); ?>>글쓴이</option>
			<option value="wr_name,0"<?php  echo get_selected($sfl, 'wr_name,0'); ?>>글쓴이(코)</option>
		</select>
		<input type="text" name="stx" value="<?php  echo stripslashes($stx) ?>" id="stx" class="frm_input" size="15" maxlength="20">
		<button type="submit" class="ui-btn point ico search default">검색</button>
		</form>
	</fieldset>
	<!-- } 게시판 검색 끝 -->
</div>

<script>
var avo_mb_id = "<?php echo $member['mb_id']?>";
var avo_board_skin_path = "<?php echo $board_skin_path?>";
var avo_board_skin_url = "<?php echo $board_skin_url?>";

function calc_payout_submit(f){
	if (!confirm('지급을 실행합니다. 계속하시겠습니까?')) return false;
	var fd = new FormData(f);
	var msg = f.querySelector('.calc-pay-result');
	msg.style.color = '#666'; msg.textContent = '처리 중...';
	fetch('<?php echo G5_HTTP_BBS_URL; ?>/calc_payout.ajax.php', {
		method:'POST', body: fd, credentials:'same-origin'
	}).then(function(r){ return r.json(); })
	  .then(function(d){
		if (d && d.ok) {
			msg.style.color = '#080';
			msg.textContent = '지급 완료 (성공 ' + (d.success||0) + ' / 실패 ' + (d.failed||0) + ')';
			f.querySelector('button[type=submit]').disabled = true;
		} else {
			msg.style.color = '#a00';
			msg.textContent = (d && d.message) ? d.message : '지급 실패';
		}
	}).catch(function(e){
		msg.style.color = '#a00'; msg.textContent = '오류: ' + e;
	});
	return false;
}

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
	$('.modify_area').hide();
	$('.original_comment_area').show();

	$('#c_'+co_id).find('.modify_area').show();
	$('#c_'+co_id).find('.original_comment_area').hide();

	$('#save_co_comment_'+co_id).focus();

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
