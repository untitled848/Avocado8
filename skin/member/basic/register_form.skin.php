<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);

$member['mb_nick']=isset($member['mb_nick'])?$member['mb_nick']:'';
?>

<div id="member_page">

	<h1 class="member-title">
		<strong>계정 정보 <?php echo $w == '' ? "등록" : "수정"?></strong>
		<span>《 Community Register Form 》</span>
	</h1>

	<div class="member-contents register-pannel">
		<form id="fregisterform" name="fregisterform" action="<?php echo $register_action_url ?>" onsubmit="return fregisterform_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
			<input type="hidden" name="w" value="<?php echo $w ?>">
			<input type="hidden" name="url" value="<?php echo $urlencode ?>">
			<input type="hidden" name="agree" value="<?php echo $agree ?>">
			<input type="hidden" name="agree2" value="<?php echo $agree2 ?>">
			<input type="hidden" name="mb_nick_default" value="<?php echo get_text($member['mb_nick']) ?>">
			<input type="hidden" name="mb_nick" value="<?php echo get_text($member['mb_nick']) ?>">
	

			<table class="member-form theme-form">
				<colgroup>
					<col style="width: 110px;" />
					<col />
				</colgroup>
				<tbody>
					<tr>
						<th>아이디</th>
						<td>
							<input type="text" name="mb_id" value="<?php echo $member['mb_id'] ?>" id="reg_mb_id" <?php echo $required ?> <?php echo $readonly ?> class="frm_input <?php echo $required ?> <?php echo $readonly ?>" minlength="3" maxlength="20">
							<span id="msg_mb_id"></span>
							<?php
							if (function_exists('register_field_help')) {
								$h = register_field_help('mb_id');
								if ($h !== '') echo '<div class="reg-help" style="font-size:12px;color:#888;margin-top:3px;">'.htmlspecialchars($h, ENT_QUOTES).'</div>';
							}
							?>
						</td>
					</tr>
					<tr>
						<th scope="row">비밀번호</th>
						<td>
							<input type="password" name="mb_password" id="reg_mb_password" <?php echo $required ?> class="frm_input <?php echo $required ?>" minlength="3" maxlength="20">
							<?php
							if (function_exists('register_field_help')) {
								$h = register_field_help('mb_password');
								if ($h !== '') echo '<div class="reg-help" style="font-size:12px;color:#888;margin-top:3px;">'.htmlspecialchars($h, ENT_QUOTES).'</div>';
							}
							?>
						</td>
					</tr>
					<tr>
						<th scope="row">비밀번호 확인</th>
						<td><input type="password" name="mb_password_re" id="reg_mb_password_re" <?php echo $required ?> class="frm_input <?php echo $required ?>" minlength="3" maxlength="20"></td>
					</tr>
				</tbody>
			</table>

			<table class="member-form theme-form">
				<colgroup>
					<col style="width: 110px;" />
					<col />
				</colgroup>
				<tbody>
					<tr>
						<th scope="row">닉네임<?php echo function_exists('register_nick_min') && register_nick_min() > 0 ? ' <span style="color:#e74c3c;">*</span>' : ''; ?></th>
						<td>
							<?php
							$nick_min = function_exists('register_nick_min') ? register_nick_min() : 2;
							$nick_max = function_exists('register_nick_max') ? register_nick_max() : 20;
							$nick_filter = function_exists('register_nick_filter') ? register_nick_filter() : '';
							$nick_pattern = '';
							if ($nick_filter === 'ko')   $nick_pattern = '[\uAC00-\uD7A3\u3131-\u318E\\s]+';
							if ($nick_filter === 'koen') $nick_pattern = '[\uAC00-\uD7A3\u3131-\u318E A-Za-z\\s]+';
							?>
							<input type="text" name="mb_name" value="<?php echo isset($member['mb_name'])?get_text($member['mb_name']):''; ?>" required class="frm_input required" size="10"
								<?php if($nick_min>0) echo 'minlength="'.$nick_min.'"'; ?>
								<?php if($nick_max>0) echo 'maxlength="'.$nick_max.'"'; ?>
								<?php if($nick_pattern) echo 'pattern="'.$nick_pattern.'"'; ?>>
							<?php
							// 닉네임 제한사항 안내 (주의: register_field_help 보다 먼저)
							$_nick_msgs = array();
							if ($nick_min > 0 && $nick_max > 0) {
								$_nick_msgs[] = $nick_min.'~'.$nick_max.'자';
							} else if ($nick_max > 0) {
								$_nick_msgs[] = '최대 '.$nick_max.'자';
							} else if ($nick_min > 0) {
								$_nick_msgs[] = '최소 '.$nick_min.'자';
							}
							if ($nick_filter === 'ko')   $_nick_msgs[] = '한글만 사용';
							else if ($nick_filter === 'koen') $_nick_msgs[] = '한글·영문만 사용';
							if ($_nick_msgs) {
								echo '<div class="reg-nick-rule" style="font-size:12px;color:#888;margin-top:3px;">'.htmlspecialchars(implode(' / ', $_nick_msgs), ENT_QUOTES).'</div>';
							}
							if (function_exists('register_field_help')) {
								$h = register_field_help('mb_name');
								if ($h !== '') echo '<div class="reg-help" style="font-size:12px;color:#888;margin-top:3px;">'.htmlspecialchars($h, ENT_QUOTES).'</div>';
							}
							?>
						</td>
					</tr>
					<?php
					$birth_mode = function_exists('register_birth_mode') ? register_birth_mode() : 'year_only';
					if ($birth_mode === 'none') {
						if (function_exists('register_birth_input')) {
							register_birth_input($member['mb_birth'] ?? '', false);
						}
					} else {
					?>
					<tr>
						<th scope="row">생년</th>
						<td>
							<?php
							if (function_exists('register_birth_input')) {
								register_birth_input($member['mb_birth'] ?? '', true);
							} else {
							?>
							<input type="text" name="mb_birth" value="<?php echo isset($member['mb_birth'])?$member['mb_birth']:''; ?>" required class="frm_input required" size="5" maxlength="100">
							<?php } ?>
							<?php
							if (function_exists('register_field_help')) {
								$h = register_field_help('mb_birth');
								if ($h !== '') echo '<div class="reg-help" style="font-size:12px;color:#888;margin-top:3px;">'.htmlspecialchars($h, ENT_QUOTES).'</div>';
							}
							?>
						</td>
					</tr>
					<?php } ?>
				</tbody>
			</table>

			<?php
			// 추가 입력폼 (cf_register_extra_fields)
			if (function_exists('register_extra_render')) {
				register_extra_render($member);
			}
			?>
	
			<div class="btn_confirm txt-center">
				<button type="submit" id="btn_submit" class="ui-btn point" accesskey="s"><?php echo $w==''?'회원가입':'정보수정'; ?></button>
				<a href="<?php echo G5_URL ?>" class="ui-btn">취소</a>
			</div>
		</form>
	</div>
</div>
	

<script>
	// submit 최종 폼체크
	var reg_mb_id_check = function() {
		var result = "";
		$.ajax({
			type: "POST",
			url: g5_bbs_url+"/ajax.mb_id.php",
			data: {
				"reg_mb_id": encodeURIComponent($("#reg_mb_id").val())
			},
			cache: false,
			async: false,
			success: function(data) {
				result = data;
			}
		});
		return result;
	}

	function fregisterform_submit(f)
	{
		// 회원아이디 검사
		if (f.w.value == "") {
			var msg = reg_mb_id_check();
			if (msg) {
				alert(msg);
				f.mb_id.select();
				return false;
			}
		}

		if (f.w.value == "") {
			if (f.mb_password.value.length < 3) {
				alert("비밀번호를 3글자 이상 입력하십시오.");
				f.mb_password.focus();
				return false;
			}
		}

		if (f.mb_password.value != f.mb_password_re.value) {
			alert("비밀번호가 같지 않습니다.");
			f.mb_password_re.focus();
			return false;
		}

		if (f.mb_password.value.length > 0) {
			if (f.mb_password_re.value.length < 3) {
				alert("비밀번호를 3글자 이상 입력하십시오.");
				f.mb_password_re.focus();
				return false;
			}
		}

		document.getElementById("btn_submit").disabled = "disabled";

		return true;
	}
</script>

</div>
<!-- } 회원정보 입력/수정 끝 -->