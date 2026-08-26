<?php
$sub_menu = "200010";
include_once('./_common.php');

if (!$is_admin) alert('관리자만 접근 가능합니다.');

$g5['title'] = '회원가입 설정';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$cf_birth_mode = $config['cf_register_birth_mode'] ?? 'year_only';
if (!in_array($cf_birth_mode, array('year_only','adult_check','none'), true)) $cf_birth_mode = 'year_only';
$cf_nick_filter = $config['cf_register_nick_filter'] ?? '';
?>

<div class="local_desc02 local_desc">
	<p>회원가입 시 적용되는 <strong>가입 권한 / 입력 항목 / 닉네임 정책 / 약관</strong> 등을 한곳에서 관리합니다.</p>
</div>

<form name="fmemreg" id="fmemreg" method="post" action="./member_register_form_update.php" onsubmit="return true;">
<?php echo get_admin_token_field(); ?>

<section>
	<h2 class="h2_frm">가입 기본</h2>
	<div class="tbl_frm01 tbl_wrap">
	<table>
	<colgroup><col style="width:160px;"><col><col style="width:160px;"><col></colgroup>
	<tbody>
	<tr>
		<th scope="row"><label for="cf_register_level">가입 시 권한</label></th>
		<td><?php echo get_member_level_select('cf_register_level', 1, 9, $config['cf_register_level']) ?></td>
		<th scope="row"><label for="cf_register_point">가입 시 지급 포인트</label></th>
		<td><input type="number" name="cf_register_point" id="cf_register_point" value="<?php echo (int)$config['cf_register_point'] ?>" class="frm_input" size="6"> 점</td>
	</tr>
	<tr>
		<th scope="row"><label for="cf_leave_day">탈퇴 후 자동 삭제일</label></th>
		<td colspan="3">
			<?php echo help('회원이 탈퇴 신청 후 N일이 지나면 데이터가 자동으로 영구 삭제됩니다. (0 = 즉시 삭제 안 함)') ?>
			<input type="number" name="cf_leave_day" id="cf_leave_day" value="<?php echo (int)$config['cf_leave_day'] ?>" class="frm_input" size="4" min="0"> 일 후 자동 삭제
		</td>
	</tr>
	</tbody>
	</table>
	</div>
</section>

<section>
	<h2 class="h2_frm">닉네임 정책</h2>
	<div class="tbl_frm01 tbl_wrap">
	<table>
	<colgroup><col style="width:160px;"><col></colgroup>
	<tbody>
	<tr>
		<th scope="row">글자수 / 문자 제한</th>
		<td>
			최소 <input type="number" name="cf_register_nick_min" value="<?php echo (int)($config['cf_register_nick_min'] ?? 2) ?>" class="frm_input" size="3" min="0">자
			&nbsp; 최대 <input type="number" name="cf_register_nick_max" value="<?php echo (int)($config['cf_register_nick_max'] ?? 20) ?>" class="frm_input" size="3" min="0">자
			&nbsp;&nbsp; 사용 가능 문자
			<select name="cf_register_nick_filter">
				<option value=""     <?php echo $cf_nick_filter===''?'selected':''; ?>>제한 없음 (한/영/숫자/특수)</option>
				<option value="ko"   <?php echo $cf_nick_filter==='ko'?'selected':''; ?>>한글만</option>
				<option value="koen" <?php echo $cf_nick_filter==='koen'?'selected':''; ?>>한글+영문만</option>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="cf_prohibit_id">아이디·닉네임 금지단어</label></th>
		<td>
			<?php echo help('회원아이디·닉네임으로 사용할 수 없는 단어. 쉼표(,)로 구분합니다.') ?>
			<textarea name="cf_prohibit_id" id="cf_prohibit_id" rows="4" style="width:100%;"><?php echo get_text($config['cf_prohibit_id']) ?></textarea>
		</td>
	</tr>
	</tbody>
	</table>
	</div>
</section>

<section>
	<h2 class="h2_frm">생년 입력 방식</h2>
	<div class="tbl_frm01 tbl_wrap">
	<table>
	<colgroup><col style="width:160px;"><col></colgroup>
	<tbody>
	<tr>
		<th scope="row"><label for="cf_register_birth_mode">생년 표시</label></th>
		<td>
			<?php echo help('회원가입 폼에 표시되는 [생년] 항목의 입력 방식을 선택합니다.') ?>
			<select name="cf_register_birth_mode" id="cf_register_birth_mode">
				<option value="year_only"   <?php echo $cf_birth_mode==='year_only'?'selected':''; ?>>직접입력 (예: 00)</option>
				<option value="adult_check" <?php echo $cf_birth_mode==='adult_check'?'selected':''; ?>>체크박스 (커뮤니티 연령제한에 저촉되지 않습니다.)</option>
				<option value="none"        <?php echo $cf_birth_mode==='none'?'selected':''; ?>>입력받지 않음</option>
			</select>
		</td>
	</tr>
	</tbody>
	</table>
	</div>
</section>

<section>
	<h2 class="h2_frm">추가 입력 항목 / 도움말</h2>
	<div class="local_desc02 local_desc">
		<p><strong>추가 입력 항목</strong>은 가입 폼에 노출되는 커스텀 입력 칸입니다. <strong>항목별 도움말</strong>은 각 입력 칸 아래에 표시되는 안내 문구입니다.</p>
	</div>
	<div class="tbl_frm01 tbl_wrap">
	<table>
	<colgroup><col style="width:160px;"><col></colgroup>
	<tbody>
	<tr>
		<th scope="row"><label for="cf_register_extra_fields">추가 입력폼</label></th>
		<td>
			<?php
			// 기존 저장값 파싱 (register_extra_fields() 와 동일 형식)
			$_xrows = array();
			$_xfraw = trim((string)($config['cf_register_extra_fields'] ?? ''));
			foreach (preg_split('/\r\n|\r|\n/', $_xfraw) as $ln) {
				$parsed = function_exists('register_extra_parse_line') ? register_extra_parse_line($ln) : null;
				if (!$parsed) continue;
				$code = $parsed['code'];
				$label = $parsed['label'];
				$tr = $parsed['type_raw'];
				$req = $parsed['required'];
				$type = 'text'; $opts = '';
				if (preg_match('/^select\s*\((.*)\)$/i', $tr, $m)) {
					$type = 'select';
					$opts = $m[1];
				} elseif (in_array($tr, array('text','textarea','select'), true)) {
					$type = $tr;
				}
				$_xrows[] = array('code'=>$code,'label'=>$label,'type'=>$type,'options'=>$opts,'required'=>$req);
			}
			?>
			<div class="local_desc02 local_desc" style="margin-bottom:8px;">
				<p>가입 폼에 노출되는 커스텀 입력 칸을 정의합니다. <strong>+ 항목 추가</strong> 로 새 칸을 추가하고, 코드(영문/숫자/_)와 라벨(표시 이름)을 입력하세요.<br>
				<strong>유형</strong>: 한 줄 입력(<code>text</code>) / 여러 줄 입력(<code>textarea</code>) / 선택형(<code>select</code>) — 선택형은 옵션을 <code>||</code> 로 구분.</p>
			</div>
			<table class="reg-xfield-tbl" id="reg-xfield-tbl" style="width:100%;border-collapse:collapse;">
				<thead>
					<tr style="background:#eef;">
						<th style="text-align:left;padding:6px 8px;width:140px;">코드</th>
						<th style="text-align:left;padding:6px 8px;width:140px;">라벨</th>
						<th style="text-align:left;padding:6px 8px;width:120px;">유형</th>
						<th style="text-align:left;padding:6px 8px;">옵션 (select 일 때)</th>
						<th style="text-align:center;padding:6px 8px;width:60px;">필수</th>
						<th style="padding:6px 8px;width:50px;"></th>
					</tr>
				</thead>
				<tbody id="reg-xfield-body">
					<?php foreach ($_xrows as $i => $r): ?>
					<tr class="reg-xfield-row" data-xfield-id="xf-<?php echo (int)$i; ?>">
						<td style="padding:4px 8px;border-top:1px solid #eee;"><input type="text" class="frm_input rxf-code" value="<?php echo htmlspecialchars($r['code'], ENT_QUOTES); ?>" style="width:100%;" placeholder="mb_xxx"></td>
						<td style="padding:4px 8px;border-top:1px solid #eee;"><input type="text" class="frm_input rxf-label" value="<?php echo htmlspecialchars($r['label'], ENT_QUOTES); ?>" style="width:100%;" placeholder="라벨"></td>
						<td style="padding:4px 8px;border-top:1px solid #eee;">
							<select class="rxf-type" style="width:100%;">
								<option value="text"     <?php echo $r['type']==='text'?'selected':''; ?>>한 줄 입력 (text)</option>
								<option value="textarea" <?php echo $r['type']==='textarea'?'selected':''; ?>>여러 줄 입력 (textarea)</option>
								<option value="select"   <?php echo $r['type']==='select'?'selected':''; ?>>선택 (select)</option>
							</select>
						</td>
						<td style="padding:4px 8px;border-top:1px solid #eee;">
							<input type="text" class="frm_input rxf-opts" value="<?php echo htmlspecialchars($r['options'], ENT_QUOTES); ?>" style="width:100%;<?php echo $r['type']==='select'?'':'display:none;'; ?>" placeholder="옵션1||옵션2||옵션3">
						</td>
						<td style="padding:4px 8px;border-top:1px solid #eee;text-align:center;"><input type="checkbox" class="rxf-req" <?php echo $r['required']?'checked':''; ?>></td>
						<td style="padding:4px 8px;border-top:1px solid #eee;text-align:center;"><button type="button" class="btn btn_03 rxf-del" style="padding:4px 8px;">삭제</button></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<div style="margin-top:8px;">
				<button type="button" id="rxf-add" class="btn btn_02">＋ 항목 추가</button>
			</div>
			<textarea name="cf_register_extra_fields" id="cf_register_extra_fields" style="display:none;"><?php echo htmlspecialchars($config['cf_register_extra_fields'] ?? '', ENT_QUOTES); ?></textarea>
		</td>
	</tr>
	<tr>
		<th scope="row">항목별 도움말</th>
		<td>
			<?php
			// 도움말 맵 파싱
			$help_map = array();
			$_help_raw = trim((string)($config['cf_register_help'] ?? ''));
			if ($_help_raw !== '') {
				foreach (preg_split('/\r\n|\r|\n/', $_help_raw) as $ln) {
					$ln = trim($ln);
					if ($ln === '' || $ln[0] === '#') continue;
					$pos = strpos($ln, '|');
					if ($pos === false) continue;
					$code = trim(substr($ln, 0, $pos));
					$txt  = trim(substr($ln, $pos + 1));
					if ($code !== '') $help_map[$code] = $txt;
				}
			}
			// 기본 슬롯
			$base_slots = array(
				'mb_id'       => array('아이디', '예: 4~20자 영문/숫자'),
				'mb_password' => array('비밀번호', '예: 8자 이상, 영문/숫자/특수문자 조합'),
				'mb_name'     => array('닉네임', '닉네임 작성 안내'),
				'mb_birth'    => array('생년', '예: 이 커뮤니티의 연령제한은 00년생 이상입니다.'),
			);
			// 추가 입력항목 코드 추출
			$extra_codes = array();
			foreach ($_xrows as $_xr) {
				$extra_codes[$_xr['code']] = $_xr['label'];
			}
			?>
			<div class="reg-help-editor" style="border:1px solid #ddd;border-radius:4px;padding:8px;background:#fafafa;">
				<p style="margin:0 0 8px;color:#555;font-size:12px;">각 입력 항목 아래에 표시되는 안내 문구입니다. 비워두면 도움말이 표시되지 않습니다.</p>
				<table style="width:100%;border-collapse:collapse;">
					<thead>
						<tr style="background:#eef;">
							<th style="text-align:left;padding:6px 8px;width:160px;">필드</th>
							<th style="text-align:left;padding:6px 8px;">도움말</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ($base_slots as $code => $info): $val = $help_map[$code] ?? ''; ?>
						<tr>
							<td style="padding:6px 8px;border-top:1px solid #eee;">
								<strong><?php echo htmlspecialchars($info[0]); ?></strong><br>
								<small style="color:#888;font-family:monospace;"><?php echo htmlspecialchars($code); ?></small>
							</td>
							<td style="padding:6px 8px;border-top:1px solid #eee;">
								<input type="text" class="frm_input" data-help-code="<?php echo htmlspecialchars($code, ENT_QUOTES); ?>" value="<?php echo htmlspecialchars($val, ENT_QUOTES); ?>" placeholder="<?php echo htmlspecialchars($info[1], ENT_QUOTES); ?>" style="width:100%;">
							</td>
						</tr>
					<?php endforeach; ?>
					<?php foreach ($_xrows as $i => $r): if (isset($base_slots[$r['code']])) continue; $val = $help_map[$r['code']] ?? ''; ?>
						<tr class="reg-help-extra-row" data-xfield-id="xf-<?php echo (int)$i; ?>">
							<td style="padding:6px 8px;border-top:1px solid #eee;">
								<strong><?php echo htmlspecialchars($r['label']); ?></strong> <small style="color:#369;">(추가)</small><br>
								<small class="help-code" style="color:#888;font-family:monospace;"><?php echo htmlspecialchars($r['code']); ?></small>
							</td>
							<td style="padding:6px 8px;border-top:1px solid #eee;">
								<input type="text" class="frm_input" data-help-code="<?php echo htmlspecialchars($r['code'], ENT_QUOTES); ?>" value="<?php echo htmlspecialchars($val, ENT_QUOTES); ?>" style="width:100%;">
							</td>
						</tr>
					<?php endforeach; ?>
					<?php
					// 추가/기본 외 (직접 입력된) 코드 — 보존
					foreach ($help_map as $code => $val) {
						if (isset($base_slots[$code]) || isset($extra_codes[$code])) continue;
					?>
						<tr>
							<td style="padding:6px 8px;border-top:1px solid #eee;">
								<strong style="color:#888;">(미정의)</strong><br>
								<small style="color:#888;font-family:monospace;"><?php echo htmlspecialchars($code); ?></small>
							</td>
							<td style="padding:6px 8px;border-top:1px solid #eee;">
								<input type="text" class="frm_input" data-help-code="<?php echo htmlspecialchars($code, ENT_QUOTES); ?>" value="<?php echo htmlspecialchars($val, ENT_QUOTES); ?>" style="width:100%;">
							</td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
			</div>
			<textarea name="cf_register_help" id="cf_register_help" style="display:none;"><?php echo htmlspecialchars($config['cf_register_help'] ?? '', ENT_QUOTES); ?></textarea>
		</td>
	</tr>
	</tbody>
	</table>
	</div>
</section>

<script>
(function(){
	// === 추가 입력폼 (구조화 편집) ===
	var tbody = document.getElementById('reg-xfield-body');
	var btnAdd = document.getElementById('rxf-add');
	var helpBody = document.querySelector('.reg-help-editor tbody');
	function normalizeCode(code) {
		code = (code || '').replace(/[^A-Za-z0-9_]/g, '').trim();
		if (code && !/^mb_/i.test(code)) code = 'mb_' + code;
		return code.toLowerCase();
	}
	function newXfieldId() {
		return 'xf-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8);
	}
	function helpRowFor(tr) {
		if (!helpBody || !tr) return null;
		var id = tr.getAttribute('data-xfield-id');
		if (!id) return null;
		return helpBody.querySelector('tr[data-xfield-id="' + id + '"]');
	}
	function syncHelpRow(tr) {
		if (!helpBody || !tr) return;
		var id = tr.getAttribute('data-xfield-id');
		if (!id) {
			id = newXfieldId();
			tr.setAttribute('data-xfield-id', id);
		}
		var code = normalizeCode((tr.querySelector('.rxf-code') || {}).value);
		var label = (((tr.querySelector('.rxf-label') || {}).value) || '').replace(/[\r\n|]+/g, ' ').trim() || code || '새 항목';
		var helpTr = helpRowFor(tr);
		if (!helpTr) {
			helpTr = document.createElement('tr');
			helpTr.className = 'reg-help-extra-row';
			helpTr.setAttribute('data-xfield-id', id);
			helpTr.innerHTML =
				'<td style="padding:6px 8px;border-top:1px solid #eee;">' +
					'<strong></strong> <small style="color:#369;">(추가)</small><br>' +
					'<small class="help-code" style="color:#888;font-family:monospace;"></small>' +
				'</td>' +
				'<td style="padding:6px 8px;border-top:1px solid #eee;">' +
					'<input type="text" class="frm_input" data-help-code="" value="" style="width:100%;">' +
				'</td>';
			helpBody.appendChild(helpTr);
		}
		var strong = helpTr.querySelector('strong');
		var small = helpTr.querySelector('small.help-code');
		var inp = helpTr.querySelector('input[data-help-code], input.frm_input');
		if (strong) strong.textContent = label;
		if (small) small.textContent = code;
		if (inp) inp.setAttribute('data-help-code', code);
	}
	function removeHelpRow(tr) {
		var helpTr = helpRowFor(tr);
		if (helpTr && helpTr.parentNode) helpTr.parentNode.removeChild(helpTr);
	}
	function rowTemplate() {
		var tr = document.createElement('tr');
		tr.className = 'reg-xfield-row';
		tr.setAttribute('data-xfield-id', newXfieldId());
		tr.innerHTML =
			'<td style="padding:4px 8px;border-top:1px solid #eee;"><input type="text" class="frm_input rxf-code" style="width:100%;" placeholder="mb_xxx"></td>' +
			'<td style="padding:4px 8px;border-top:1px solid #eee;"><input type="text" class="frm_input rxf-label" style="width:100%;" placeholder="라벨"></td>' +
			'<td style="padding:4px 8px;border-top:1px solid #eee;">' +
				'<select class="rxf-type" style="width:100%;">' +
					'<option value="text">한 줄 입력 (text)</option>' +
					'<option value="textarea">여러 줄 입력 (textarea)</option>' +
					'<option value="select">선택 (select)</option>' +
				'</select>' +
			'</td>' +
			'<td style="padding:4px 8px;border-top:1px solid #eee;"><input type="text" class="frm_input rxf-opts" style="width:100%;display:none;" placeholder="옵션1||옵션2||옵션3"></td>' +
			'<td style="padding:4px 8px;border-top:1px solid #eee;text-align:center;"><input type="checkbox" class="rxf-req show"></td>' +
			'<td style="padding:4px 8px;border-top:1px solid #eee;text-align:center;"><button type="button" class="btn btn_03 rxf-del" style="padding:5px">X</button></td>';
		return tr;
	}
	if (btnAdd && tbody) btnAdd.addEventListener('click', function(){
		var tr = rowTemplate();
		tbody.appendChild(tr);
		syncHelpRow(tr);
	});
	if (tbody) tbody.addEventListener('click', function(e){
		if (e.target.classList.contains('rxf-del')) {
			var tr = e.target.closest('tr.reg-xfield-row');
			if (tr) {
				removeHelpRow(tr);
				tr.parentNode.removeChild(tr);
			}
		}
	});
	if (tbody) tbody.addEventListener('change', function(e){
		if (e.target.classList.contains('rxf-type')) {
			var tr = e.target.closest('tr.reg-xfield-row'); if (!tr) return;
			var opts = tr.querySelector('.rxf-opts');
			if (opts) opts.style.display = (e.target.value === 'select') ? '' : 'none';
		}
	});
	if (tbody) tbody.addEventListener('input', function(e){
		if (e.target.classList.contains('rxf-code') || e.target.classList.contains('rxf-label')) {
			var tr = e.target.closest('tr.reg-xfield-row');
			if (tr) syncHelpRow(tr);
		}
	});
	function serializeXFields() {
		if (!tbody) return '';
		var lines = [];
		tbody.querySelectorAll('tr.reg-xfield-row').forEach(function(tr){
			var code  = (tr.querySelector('.rxf-code')||{}).value || '';
			var label = (tr.querySelector('.rxf-label')||{}).value || '';
			var type  = (tr.querySelector('.rxf-type')||{}).value || 'text';
			var opts  = (tr.querySelector('.rxf-opts')||{}).value || '';
			var req   = (tr.querySelector('.rxf-req')||{}).checked ? 1 : 0;
			code  = code.replace(/[^A-Za-z0-9_]/g, '').trim();
			label = label.replace(/[\r\n|]+/g, ' ').trim();
			if (!code || !label) return; // 불완전 행 제거
			var tpart = type;
			if (type === 'select') {
				var os = opts.split('||').map(function(s){ return s.replace(/[\r\n|]+/g,' ').trim(); }).filter(Boolean);
				if (!os.length) return; // select 인데 옵션 없으면 제거
				tpart = 'select(' + os.join('||') + ')';
			} else if (type !== 'text' && type !== 'textarea') {
				tpart = 'text';
			}
			lines.push(code + '|' + label + '|' + tpart + '|' + req);
		});
		return lines.join('\n');
	}

	// === 도움말 직렬화 (기존) ===
	var form = document.getElementById('fmemreg');
	if (!form) return;
	form.addEventListener('submit', function(){
		// 추가 입력폼 직렬화
		var xta = document.getElementById('cf_register_extra_fields');
		if (xta) xta.value = serializeXFields();
		// 도움말 직렬화
		var ta = document.getElementById('cf_register_help');
		if (!ta) return;
		var lines = [];
		document.querySelectorAll('.reg-help-editor input[data-help-code]').forEach(function(inp){
			var code = inp.getAttribute('data-help-code');
			var val  = (inp.value || '').replace(/[\r\n]+/g,' ').trim();
			if (code) lines.push(code + '|' + val);
		});
		ta.value = lines.join('\n');
	});
})();
</script>

<section>
	<h2 class="h2_frm">약관 / 안내문</h2>
	<div class="tbl_frm01 tbl_wrap">
	<table>
	<colgroup><col style="width:160px;"><col></colgroup>
	<tbody>
	<tr>
		<th scope="row"><label for="cf_stipulation">커뮤니티 활동 규칙</label></th>
		<td><textarea name="cf_stipulation" id="cf_stipulation" rows="8" style="width:100%;"><?php echo get_text($config['cf_stipulation']) ?></textarea></td>
	</tr>
	<tr>
		<th scope="row"><label for="cf_privacy">캐릭터 생성 유의사항</label></th>
		<td><textarea name="cf_privacy" id="cf_privacy" rows="8" style="width:100%;"><?php echo get_text($config['cf_privacy']) ?></textarea></td>
	</tr>
	</tbody>
	</table>
	</div>
</section>

<div class="btn_confirm01 btn_confirm">
	<input type="submit" value="저장" class="btn_submit" accesskey="s">
</div>

</form>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
