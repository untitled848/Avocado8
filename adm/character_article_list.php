<?php
$sub_menu = "400100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

// 자동 마이그레이션: ad_required_*, ad_*_length_name 컬럼
if (function_exists('alter_table_add_column')) {
	@alter_table_add_column($g5['article_default_table'], 'ad_required_thumb', "INT(11) NOT NULL DEFAULT '0'", 'ad_use_status');
	@alter_table_add_column($g5['article_default_table'], 'ad_required_head',  "INT(11) NOT NULL DEFAULT '0'", 'ad_required_thumb');
	@alter_table_add_column($g5['article_default_table'], 'ad_required_body',  "INT(11) NOT NULL DEFAULT '0'", 'ad_required_head');
	@alter_table_add_column($g5['article_default_table'], 'ad_required_name',  "INT(11) NOT NULL DEFAULT '1'", 'ad_required_body');
	@alter_table_add_column($g5['article_default_table'], 'ad_min_length_name', "INT(11) NOT NULL DEFAULT '0'", 'ad_required_name');
	@alter_table_add_column($g5['article_default_table'], 'ad_max_length_name', "INT(11) NOT NULL DEFAULT '0'", 'ad_min_length_name');
}

$token = get_token();

// 기본항목에 대한 설정값 가져오기
$profile = sql_fetch(" select * from {$g5['article_default_table']} ");
if(!$profile){
	sql_query("INSERT into {$g5['article_default_table']} set ad_id=1");
	$profile = sql_fetch(" select * from {$g5['article_default_table']} ");
}
$profile['ad_text_thumb'] = $profile['ad_text_thumb'] ? $profile['ad_text_thumb'] : "두상";
$profile['ad_text_head'] = $profile['ad_text_head'] ? $profile['ad_text_head'] : "흉상";
$profile['ad_text_body'] = $profile['ad_text_body'] ? $profile['ad_text_body'] : "전신";
$profile['ad_text_name'] = $profile['ad_text_name'] ? $profile['ad_text_name'] : "이름";


$pg_anchor = '<ul class="anchor">
	<li><a href="#anc_000">멤버 리스트 표시</a></li>
	<li><a href="#anc_001">기본 프로필 양식</a></li>
	<li><a href="#anc_002">추가 프로필 양식</a></li>
	<li><a href="#anc_003">프로필 항목 등록</a></li>
</ul>';

$frm_submit = '<div class="btn_confirm01 btn_confirm">
	<input type="submit" value="확인" class="btn_submit" accesskey="s">
</div>';

// 추가 항목에 대한 데이터들 가져오기
$sql = " select * from {$g5['article_table']} order by ar_order asc";
$result = sql_query($sql);

$g5['title'] = '프로필 양식 관리';
include_once ('./admin.head.php');
?>

<form name="farticle" method="post" action="./character_article_list_update.php" autocomplete="off">

<section id="anc_000">
	<h2 class="h2_frm">멤버 캐릭터 리스트 표시 설정</h2>
	<?php echo $pg_anchor ?>
	<div class="tbl_frm01 tbl_wrap">
		<table>
		<caption>멤버 리스트 표시</caption>
		<colgroup>
			<col style="width:20%"><col style="width:10%"><col>
		</colgroup>
		<tbody>
		<tr>
			<th scope="row">캐릭터 두상 크기</th>
			<td>px</td>
			<td>
				가로 <input type="number" name="cf_member_thumb_w" value="<?php echo (int)($config['cf_member_thumb_w'] ?? 120) ?>" id="cf_member_thumb_w" size="5" min="40" max="800">
				px &nbsp;&nbsp;
				세로 <input type="number" name="cf_member_thumb_h" value="<?php echo (int)($config['cf_member_thumb_h'] ?? 160) ?>" id="cf_member_thumb_h" size="5" min="40" max="800">
				px
			</td>
		</tr>
		<tr>
			<th scope="row">그리드 정렬 방식</th>
			<td></td>
			<td>
				<?php
				$grid_mode_val = isset($config['cf_member_grid_mode']) ? $config['cf_member_grid_mode'] : 'auto-fill';
				if (!in_array($grid_mode_val, array('auto-fill','auto-fit','fixed'), true)) $grid_mode_val = 'auto-fill';
				$grid_modes = ['auto-fill' => '자동 채움 (auto-fill)', 'auto-fit' => '자동 맞춤 (auto-fit)', 'fixed' => '갯수 고정 (직접 지정)'];
				?>
				<select name="cf_member_grid_mode" id="cf_member_grid_mode" onchange="toggleGridCount(this.value)">
					<?php foreach ($grid_modes as $gv => $gl): ?>
					<option value="<?php echo htmlspecialchars($gv, ENT_QUOTES); ?>" <?php echo $grid_mode_val===$gv?'selected':''; ?>><?php echo htmlspecialchars($gl, ENT_QUOTES); ?></option>
					<?php endforeach; ?>
				</select>
				<span id="ml-grid-count" style="margin-left:10px;<?php echo $grid_mode_val === 'fixed' ? '' : 'display:none;'; ?>">
					한 줄 최대
					<input type="number" name="cf_member_grid_count" value="<?php echo (int)($config['cf_member_grid_count'] ?? 4) ?>" id="cf_member_grid_count" size="3" min="1" max="20">
					개
				</span>
			</td>
		</tr>
		<tr>
			<th scope="row">사이드 통합 표시</th>
			<td></td>
			<td>
				<?php $side_merge = !empty($config['cf_member_side_merge']); ?>
				<label><input type="checkbox" name="cf_member_side_merge" value="1"<?php echo $side_merge ? ' checked' : ''; ?>> 사이드 구분 없이 통합해서 보여주기</label>
				<?php if (!empty($config['cf_side_title'])) echo help('체크 시 \''.get_text($config['cf_side_title']).'\' 구분이 사라지고 한 목록으로 합쳐집니다.'); ?>
			</td>
		</tr>
		<tr>
			<th scope="row">목록 정렬 순서</th>
			<td></td>
			<td>
				<?php
				$order_val  = isset($config['cf_member_order']) ? $config['cf_member_order'] : 'ch_id asc';
				$order_cols = ['ch_id' => '등록순(ch_id)', 'ch_name' => '이름순'];
				if (!empty($config['cf_rank_name'])) $order_cols['ch_rank'] = get_text($config['cf_rank_name']).'순';
				if (!empty($config['cf_class_title'])) $order_cols['ch_class'] = get_text($config['cf_class_title']).'순';
				if (!empty($config['cf_side_title'])) $order_cols['ch_side'] = get_text($config['cf_side_title']).'순';
				$order_parts = array_filter(array_map('trim', explode(',', $order_val)));
				?>
				<div id="ml-order-rows">
				<?php foreach ($order_parts as $part) {
					$p = preg_split('/\s+/', trim($part));
					$col = isset($p[0]) ? $p[0] : 'ch_id';
					$dir = isset($p[1]) ? strtolower($p[1]) : 'asc';
					if (!isset($order_cols[$col])) $order_cols[$col] = $col;
					echo '<div class="ml-order-row" style="margin-bottom:4px;">';
					echo '<select class="ml-order-col">';
					foreach ($order_cols as $cv => $cl) echo '<option value="'.htmlspecialchars($cv, ENT_QUOTES).'"'.($cv===$col?' selected':'').'>'.htmlspecialchars($cl, ENT_QUOTES).'</option>';
					echo '</select> ';
					echo '<select class="ml-order-dir"><option value="asc"'.($dir==='asc'?' selected':'').'>오름차순</option><option value="desc"'.($dir==='desc'?' selected':'').'>내림차순</option></select> ';
					echo '<button type="button" onclick="this.parentNode.remove();mlSerialize()">✕</button>';
					echo '</div>';
				} ?>
				</div>
				<button type="button" onclick="mlAddRow()">＋ 정렬 조건 추가</button>
				<input type="hidden" name="cf_member_order" id="ml-order-hidden" value="<?php echo htmlspecialchars($order_val, ENT_QUOTES); ?>">
				<script>
				var ML_ORDER_COLS = <?php echo json_encode($order_cols, JSON_UNESCAPED_UNICODE); ?>;
				function mlSerialize() {
					var rows = document.querySelectorAll('#ml-order-rows .ml-order-row');
					var parts = [];
					rows.forEach(function(r) {
						var col = r.querySelector('.ml-order-col').value;
						var dir = r.querySelector('.ml-order-dir').value;
						parts.push(col + ' ' + dir);
					});
					document.getElementById('ml-order-hidden').value = parts.join(', ');
				}
				function mlAddRow() {
					var div = document.createElement('div');
					div.className = 'ml-order-row';
					div.style.marginBottom = '4px';
					var sel1 = '<select class="ml-order-col">';
					for (var cv in ML_ORDER_COLS) sel1 += '<option value="'+cv+'">'+ML_ORDER_COLS[cv]+'</option>';
					sel1 += '</select>';
					var sel2 = '<select class="ml-order-dir"><option value="asc">오름차순</option><option value="desc">내림차순</option></select>';
					div.innerHTML = sel1 + ' ' + sel2 + ' <button type="button" onclick="this.parentNode.remove();mlSerialize()">✕</button>';
					document.getElementById('ml-order-rows').appendChild(div);
				}
				document.querySelectorAll('#ml-order-rows').forEach(function(wrap) {
					wrap.addEventListener('change', mlSerialize);
				});
				function toggleGridCount(v) {
					document.getElementById('ml-grid-count').style.display = v === 'fixed' ? '' : 'none';
				}
				</script>
			</td>
		</tr>
		<tr>
			<th scope="row">NPC 표시</th>
			<td></td>
			<td>
				<?php
				$npc_mode = isset($config['cf_member_npc_mode']) ? $config['cf_member_npc_mode'] : 'split';
				$npc_pos  = isset($config['cf_member_npc_pos'])  ? $config['cf_member_npc_pos']  : 'top';
				?>
				<strong>표시 방식</strong><br>
				<label><input type="radio" name="cf_member_npc_mode" value="split"<?php echo $npc_mode==='split' ? ' checked' : ''; ?>> 멤버 캐릭터와 분리</label>
				<label style="margin-left:12px;"><input type="radio" name="cf_member_npc_mode" value="merge"<?php echo $npc_mode==='merge' ? ' checked' : ''; ?>> 멤버 캐릭터와 통합</label>
				<div id="ml-npc-pos" style="margin-top:6px;">
					<strong>NPC 위치</strong>
					<span style="font-size:11px; color:#888;">— 분리 모드에서는 멤버 캐릭터 리스트의 상/하단에 구분 표시, 통합 모드에서는 난입 위치</span>
					<br>
					<?php
					$npc_pos_opts = ['top'=>'목록 위쪽', 'bottom'=>'목록 아래쪽', 'inline'=>'정렬 순서에 따름 (통합 모드 전용)'];
					foreach ($npc_pos_opts as $pv => $pl) {
						$chk = ($npc_pos === $pv) ? ' checked' : '';
						echo ' <label style="margin-right:10px;"><input type="radio" name="cf_member_npc_pos" value="'.htmlspecialchars($pv, ENT_QUOTES).'"'.$chk.'> '.$pl.'</label>';
					}
					?>
				</div>
			</td>
		</tr>
		</tbody>
		</table>
	</div>
</section>
<?php echo $frm_submit; ?>

<section id="anc_001">
	<h2 class="h2_frm">기본 프로필 양식</h2>
	<?php echo $pg_anchor ?>

	<div class="tbl_frm01 tbl_wrap">
		<table>
		<colgroup>
			<col style="width: 120px;">
			<col style="width: 80px;">
			<col />
		</colgroup>
		<tbody>
		<tr>
			<th>기능사용설정</th>
			<td colspan="2">
				<input type="checkbox" name="ad_use_title" value="1" id="ad_use_title" <?php echo $profile['ad_use_title'] ? "checked" : ""?>/>
				<label for="ad_use_title">타이틀 사용</label>
				&nbsp;&nbsp;
				<input type="checkbox" name="ad_use_closet" value="1" id="ad_use_closet" <?php echo $profile['ad_use_closet'] ? "checked" : ""?>/>
				<label for="ad_use_closet">옷장 사용</label>
				&nbsp;&nbsp;
				<input type="checkbox" name="ad_use_inven" value="1" id="ad_use_inven" <?php echo $profile['ad_use_inven'] ? "checked" : ""?>/>
				<label for="ad_use_inven">인벤토리 사용</label>
				&nbsp;&nbsp;
				<input type="checkbox" name="ad_use_money" value="1" id="ad_use_money" <?php echo $profile['ad_use_money'] ? "checked" : ""?>/>
				<label for="ad_use_money">금액 사용</label>
				&nbsp;&nbsp;
				<input type="checkbox" name="ad_use_exp" value="1" id="ad_use_exp" <?php echo $profile['ad_use_exp'] ? "checked" : ""?>/>
				<label for="ad_use_exp">경험치 사용</label>
				&nbsp;&nbsp;
				<input type="checkbox" name="ad_use_rank" value="1" id="ad_use_rank" <?php echo $profile['ad_use_rank'] ? "checked" : ""?>/>
				<label for="ad_use_rank">랭킹 사용</label>
				&nbsp;&nbsp;
				<input type="checkbox" name="ad_use_status" value="1" id="ad_use_status" <?php echo $profile['ad_use_status'] ? "checked" : ""?>/>
				<label for="ad_use_status">스탯 사용</label>
			</td>
		</tr>

		<tr>
			<th rowspan="2"><input type="text" name="ad_text_thumb" value="<?php echo $profile['ad_text_thumb']?>" size="13"/></th>
			<td class="bo-right bo-left">사용여부</td>
			<td>
				<input type="checkbox" name="ad_use_thumb" value="1" id="ad_use_thumb" <?php echo $profile['ad_use_thumb'] ? "checked" : ""?>/>
				<label for="ad_use_thumb">사용</label>
				&nbsp;&nbsp;
				<input type="checkbox" name="ad_url_thumb" value="1" id="ad_url_thumb" <?php echo $profile['ad_url_thumb'] ? "checked" : ""?> />
				<label for="ad_url_thumb">외부 이미지 경로로 등록</label>
				&nbsp;&nbsp;
				<input type="checkbox" name="ad_required_thumb" value="1" id="ad_required_thumb" <?php echo !empty($profile['ad_required_thumb']) ? "checked" : ""?> />
				<label for="ad_required_thumb">필수 입력</label>
			</td></tr><tr>
			<td class="bo-right bo-left">도움말</td>
			<td>
				<input type="text" name="ad_help_thumb" value="<?php echo $profile['ad_help_thumb']?>" size="80" />
			</td>
		</tr>
		<tr>
			<th rowspan="2"><input type="text" name="ad_text_head" value="<?php echo $profile['ad_text_head']?>" size="13"/></th>
			<td class="bo-right bo-left">사용여부</td>
			<td>
				<input type="checkbox" name="ad_use_head" value="1" id="ad_use_head" <?php echo $profile['ad_use_head'] ? "checked" : ""?> />
				<label for="ad_use_head">사용</label>
				&nbsp;&nbsp;
				<input type="checkbox" name="ad_url_head" value="1" id="ad_url_head" <?php echo $profile['ad_url_head'] ? "checked" : ""?> />
				<label for="ad_url_head">외부 이미지 경로로 등록</label>
				&nbsp;&nbsp;
				<input type="checkbox" name="ad_required_head" value="1" id="ad_required_head" <?php echo !empty($profile['ad_required_head']) ? "checked" : ""?> />
				<label for="ad_required_head">필수 입력</label>
			</td></tr><tr>
			<td class="bo-right bo-left">도움말</td>
			<td>
				<input type="text" name="ad_help_head" value="<?php echo $profile['ad_help_head']?>" size="80" />
			</td>
		</tr>
		<tr>
			<th rowspan="2"><input type="text" name="ad_text_body" value="<?php echo $profile['ad_text_body']?>" size="13"/></th>
			<td class="bo-right bo-left">사용여부</td>
			<td>
				<input type="checkbox" name="ad_use_body" value="1" id="ad_use_body" <?php echo $profile['ad_use_body'] ? "checked" : ""?> />
				<label for="ad_use_body">사용</label>
				&nbsp;&nbsp;
				<input type="checkbox" name="ad_url_body" value="1" id="ad_url_body" <?php echo $profile['ad_url_body'] ? "checked" : ""?> />
				<label for="ad_url_body">외부 이미지 경로로 등록</label>
				&nbsp;&nbsp;
				<input type="checkbox" name="ad_required_body" value="1" id="ad_required_body" <?php echo !empty($profile['ad_required_body']) ? "checked" : ""?> />
				<label for="ad_required_body">필수 입력</label>
			</td></tr><tr>
			<td class="bo-right bo-left">도움말</td>
			<td>
				<input type="text" name="ad_help_body" value="<?php echo $profile['ad_help_body']?>" size="80" />
			</td>
		</tr>
		<tr>
			<th><input type="text" name="ad_text_name" value="<?php echo $profile['ad_text_name']?>" size="13"/></th>
			<td class="bo-right bo-left">도움말</td>
			<td>
				<input type="hidden" name="ad_use_name" value="1" />
				<input type="text" name="ad_help_name" value="<?php echo $profile['ad_help_name']?>" size="60" />
				&nbsp;&nbsp;
				<input type="checkbox" name="ad_required_name" value="1" id="ad_required_name" <?php echo !empty($profile['ad_required_name']) ? "checked" : ""?> />
				<label for="ad_required_name">필수 입력</label>
				&nbsp;&nbsp;
				<label>글자수 제한 (최소) <input type="number" name="ad_min_length_name" value="<?php echo (int)($profile['ad_min_length_name'] ?? 0); ?>" min="0" size="4" style="width:60px" /></label>
				&nbsp;
				<label>(최대) <input type="number" name="ad_max_length_name" value="<?php echo (int)($profile['ad_max_length_name'] ?? 0); ?>" min="0" size="4" style="width:60px" /></label>
				<small style="color:#888;">0 = 제한 없음</small>
			</td>
		</tr>
		
		</tbody>
		</table>
	</div>

</section>
<?php  echo $frm_submit; ?>


<section id="anc_002">
	<h2 class="h2_frm">추가 프로필 양식</h2>
	<?php echo $pg_anchor ?>
	<div class="local_desc02 local_desc">
		<p>각 항목은 카드 한 장으로 표시됩니다. <strong>좌측 ⋮⋮ 핸들을 잡고 위/아래로 끌어</strong> 순서를 바꿀 수 있습니다.</p>
		<p>항목을 제거하려면 카드 우측 상단의 <strong>[삭제]</strong> 버튼을 누른 뒤 하단의 "확인"을 눌러 주세요.</p>
		<p>고유코드는 <strong>영문 대/소문자 / 언더스코어(_)</strong>만 사용 가능합니다.</p>
	</div>

	<div class="local_desc02 local_desc" style="margin-top:6px;">
		<p>카드 머리(헤드)를 클릭하면 본문이 <strong>접히고/펼쳐집니다</strong>. <strong>사용 시기</strong> 체크박스는 헤드 안에 있어 항상 바로 조작할 수 있습니다.</p>
	</div>
	<ul class="ar-cards" id="ar_cards">
	<?php  for($i = 0; $ar = sql_fetch_array($result); $i++) { ?>
		<li class="ar-card ar-collapsed" data-idx="<?php echo $i?>">
			<input type="hidden" name="ar_id[<?php echo $i?>]" value="<?php echo $ar['ar_id']?>" />
			<input type="hidden" name="ar_delete[<?php echo $i?>]" value="" class="ar-del-flag" />
			<div class="ar-card-head">
				<span class="ar-handle" title="드래그하여 순서 변경">⋮⋮</span>
				<span class="ar-toggle-arrow" aria-hidden="true">▶</span>
				<span class="ar-name-preview"><?php echo htmlspecialchars($ar['ar_name'] ?: $ar['ar_code'], ENT_QUOTES) ?></span>
				<div class="ar-time-inline" data-no-toggle="1">
					<span class="ar-time-inline-label">사용 시기</span>
					<?php av_time_checkbox_group("ar_time[$i]", $ar['ar_time'], array('id_prefix'=>"ar_time_$i", 'ti_type'=>array('au','renewal'), 'help'=>'')); ?>
				</div>
				<button type="button" class="btn_03 ar-delete-btn" data-no-toggle="1">삭제</button>
			</div>
			<div class="ar-card-body">
				<div class="ar-grid">
					<div class="ar-field">
						<label>고유코드</label>
						<input type="text" name="ar_code[<?php echo $i?>]" value="<?php echo $ar['ar_code']?>" pattern="[A-Za-z_]+" title="영문 대/소문자 / _ 만" class="frm_input full" />
					</div>
					<div class="ar-field">
						<label>항목명</label>
						<input type="text" name="ar_name[<?php echo $i?>]" value="<?php echo $ar['ar_name']?>" class="frm_input full" />
					</div>
					<div class="ar-field">
						<label>항목타입</label>
						<select name="ar_type[<?php echo $i?>]" class="full">
							<option value="text"		<?php echo $ar['ar_type']=="text" ? "selected" : ""?>>한줄 텍스트</option>
							<option value="textarea"	<?php echo $ar['ar_type']=="textarea" ? "selected" : ""?>>텍스트</option>
							<option value="select"		<?php echo $ar['ar_type']=="select" ? "selected" : ""?>>단일선택</option>
							<option value="file"		<?php echo $ar['ar_type']=="file" ? "selected" : ""?>>이미지 업로드</option>
							<option value="url"			<?php echo $ar['ar_type']=="url" ? "selected" : ""?>>외부이미지 링크</option>
						</select>
					</div>
					<div class="ar-field">
						<label>폼크기 (px)</label>
						<input type="text" name="ar_size[<?php echo $i?>]" value="<?php echo $ar['ar_size']?>" class="frm_input full"/>
					</div>
					<div class="ar-field ar-field-wide">
						<label>단위 / 선택항목 (||로 구분)</label>
						<input type="text" name="ar_text[<?php echo $i?>]" value="<?php echo $ar['ar_text']?>" class="frm_input full" placeholder="예: 남성||여성"/>
					</div>
					<div class="ar-field ar-field-wide">
						<label>도움말</label>
						<input type="text" name="ar_help[<?php echo $i?>]" value="<?php echo $ar['ar_help']?>" class="frm_input full"/>
					</div>
					<div class="ar-field">
						<label>글자수 제한 (최소 / 최대)</label>
						<div class="ar-inline-pair">
							<input type="number" name="ar_min_length[<?php echo $i?>]" value="<?php echo (int)$ar['ar_min_length']?>" min="0" class="frm_input" placeholder="최소" />
							<input type="number" name="ar_max_length[<?php echo $i?>]" value="<?php echo (int)$ar['ar_max_length']?>" min="0" class="frm_input" placeholder="최대"/>
						</div>
					</div>
					<div class="ar-field">
						<label>순서</label>
						<input type="number" name="ar_order[<?php echo $i?>]" value="<?php echo $ar['ar_order']?>" class="frm_input full ar-order-input"/>
					</div>
					<div class="ar-field">
						<label>공개영역</label>
						<select name="ar_secret[<?php echo $i?>]" class="full">
							<option value="">전체공개</option>
							<option value="S" <?php echo $ar['ar_secret']=="S" ? "selected" : ""?>>오너+관리자 공개</option>
							<option value="H" <?php echo $ar['ar_secret']=="H" ? "selected" : ""?>>관리자 공개</option>
						</select>
					</div>
					<div class="ar-field">
						<label>필수 입력</label>
						<label class="ar-switch"><input type="checkbox" name="ar_required[<?php echo $i?>]" value="1" <?php echo !empty($ar['ar_required']) ? 'checked' : ''; ?> /> 필수 입력으로 설정</label>
					</div>
				</div>
			</div>
		</li>
	<?php  }
	if($i==0){ ?>
		<li class="ar-card-empty">등록된 항목이 없습니다.</li>
	<?php  } ?>
	</ul>
</section>
<?php  echo $frm_submit; ?>
</form>

<script>
(function(){
	var list = document.getElementById('ar_cards');
	if (!list) return;
	// 삭제 버튼: hidden ar_delete[] 플래그 토글 + 시각 표시
	list.addEventListener('click', function(e){
		var btn = e.target.closest('.ar-delete-btn');
		if (!btn) return;
		var card = btn.closest('.ar-card');
		if (!card) return;
		var flag = card.querySelector('.ar-del-flag');
		if (card.classList.toggle('ar-card-deleted')) {
			flag.value = '1';
			btn.textContent = '삭제 취소';
		} else {
			flag.value = '';
			btn.textContent = '삭제';
		}
	});
	// 카드 이름 프리뷰 동기화
	list.addEventListener('input', function(e){
		if (!e.target.matches('input[name^="ar_name["]')) return;
		var card = e.target.closest('.ar-card');
		var prev = card.querySelector('.ar-name-preview');
		if (prev) prev.textContent = e.target.value || card.querySelector('input[name^="ar_code["]').value || '';
	});
	// HTML5 native drag & drop
	var dragSrc = null;
	var dragArmed = false;   // 핸들에서 mousedown 한 직후만 true
	function onDragStart(e){
		// 핸들에서 시작한 것이 아니면 (인풋 텍스트 드래그 등) 거부
		if (!dragArmed) { e.preventDefault(); return false; }
		dragSrc = this;
		this.classList.add('ar-dragging');
		e.dataTransfer.effectAllowed = 'move';
		try { e.dataTransfer.setData('text/plain', this.dataset.idx); } catch(_){}
	}
	function onDragOver(e){ e.preventDefault(); e.dataTransfer.dropEffect = 'move'; this.classList.add('ar-drop-over'); return false; }
	function onDragLeave(){ this.classList.remove('ar-drop-over'); }
	function onDrop(e){
		e.stopPropagation(); e.preventDefault();
		this.classList.remove('ar-drop-over');
		if (!dragSrc || dragSrc === this) return false;
		// 위치 비교: 이 카드가 dragSrc보다 뒤면 after, 아니면 before
		var rect = this.getBoundingClientRect();
		var after = (e.clientY - rect.top) > rect.height/2;
		this.parentNode.insertBefore(dragSrc, after ? this.nextSibling : this);
		renumberOrder();
		return false;
	}
	function onDragEnd(){
		this.classList.remove('ar-dragging');
		Array.prototype.forEach.call(list.querySelectorAll('.ar-drop-over'), function(el){ el.classList.remove('ar-drop-over'); });
	}
	function renumberOrder(){
		var cards = list.querySelectorAll('.ar-card');
		Array.prototype.forEach.call(cards, function(card, i){
			var inp = card.querySelector('.ar-order-input');
			if (inp) inp.value = (i+1)*10;
		});
	}
	function bindCard(card){
		// 핸들에서만 드래그 시작 — card 전체에 attr 두면 입력 클릭이 방해됨
		var handle = card.querySelector('.ar-handle');
		if (!handle) return;
		card.setAttribute('draggable', 'false');
		handle.addEventListener('mousedown', function(){
			card.setAttribute('draggable', 'true');
			dragArmed = true;
		});
		card.addEventListener('dragstart', onDragStart);
		card.addEventListener('dragover',  onDragOver);
		card.addEventListener('dragleave', onDragLeave);
		card.addEventListener('drop',      onDrop);
		card.addEventListener('dragend',   onDragEnd);
	}
	// 마우스 떼면 즉시 무장 해제 + 모든 카드 draggable 해제
	document.addEventListener('mouseup', function(){
		dragArmed = false;
		Array.prototype.forEach.call(list.querySelectorAll('.ar-card'), function(c){
			c.setAttribute('draggable', 'false');
		});
	}, true);
	Array.prototype.forEach.call(list.querySelectorAll('.ar-card'), bindCard);

	// 헤드 클릭 → 본문 접기/펼치기. 단, 입력/버튼/시기 영역 클릭은 토글하지 않음.
	list.addEventListener('click', function(e){
		if (e.target.closest('[data-no-toggle], input, button, label, select, textarea, .ar-handle, .ar-delete-btn')) return;
		var head = e.target.closest('.ar-card-head');
		if (!head) return;
		var card = head.closest('.ar-card');
		if (!card) return;
		card.classList.toggle('ar-collapsed');
	});

	// 시기 체크박스 클릭 시 페이지 스크롤이 튀는 현상 방지
	//   - <label for=checkbox> → 브라우저가 체크박스로 포커스를 옮기며
	//     scrollIntoView를 호출, 카드가 길면 페이지가 꼭대기로 끌려감.
	//   - 클릭 직전 스크롤 위치를 캡처 → 다음 프레임에서 비정상 점프면 복원.
	list.addEventListener('mousedown', function(e){
		if (!e.target.closest('.ar-time-inline')) return;
		var y = window.pageYOffset || document.documentElement.scrollTop;
		requestAnimationFrame(function(){
			var ny = window.pageYOffset || document.documentElement.scrollTop;
			if (Math.abs(ny - y) > 8) window.scrollTo(window.pageXOffset, y);
		});
	}, true);
})();
</script>



<form name="farticle_add" method="post" action="./character_article_update.php" autocomplete="off">

<section id="anc_003">
	<h2 class="h2_frm">프로필 항목 등록</h2>
	<?php echo $pg_anchor ?>
	<div class="local_desc02 local_desc">
		<p>프로필 항목 등록은 하단의 확인을 눌러야만 등록이 됩니다. (기본 프로필 양식 및 추가 프로필 양식의 확인버튼을 클릭 시 내용이 날아갑니다.) </p>
	</div>

	<div class="tbl_frm01 tbl_wrap">
		<table>
			<colgroup>
				<col style="width: 120px;">
				<col />
			</colgroup>
			<tbody>
				<tr>
					<th>고유코드</th>
					<td>
						<?php echo help("영문 대/소문자 / 언더스코어(_) 만 사용 가능") ?>
						<input type="text" name="ar_code" value="" size="30" pattern="[A-Za-z_]+" title="영문 대/소문자 / _ 만" required />
					</td>
				</tr>
				<tr>
					<th>항목명</th>
					<td>
						<input type="text" name="ar_name" value="" size="30" />
					</td>
				</tr>
				<tr>
					<th>항목타입</th>
					<td>
						<select name="ar_type">
							<option value="text">한줄 텍스트</option>
							<option value="textarea">텍스트</option>
							<option value="select">단일선택</option>
							<option value="file">이미지 업로드</option>
							<option value="url">외부이미지 링크</option>
						</select>
					</td>
				</tr>
				<tr>
					<th>폼크기</th>
					<td>
						<?php echo help("입력폼의 가로 사이즈를 지정합니다. (텍스트 타입의 경우, 세로 사이즈)") ?>
						<input type="text" name="ar_size" value="" size="20" />px
					</td>
				</tr>
				<tr>
					<th>단위/항목</th>
					<td>
						<?php echo help("입력폼의 옆에 출력될 단위, 혹은 단일 선택 항목의 경우 선택할 항목을 입력해 주세요.") ?>
						<?php echo help("항목 구분은 '||' 로 해주세요. (ex : 남성||여성)") ?>
						<input type="text" name="ar_text" value="" size="30" />
					</td>
				</tr>
				<tr>
					<th>도움말</th>
					<td>
						<?php echo help("해당 항목에 대한 추가 설명문을 입력해 주세요.") ?>
						<input type="text" name="ar_help" value="" size="80" />
					</td>
				</tr>
				<tr>
					<th>순서</th>
					<td>
						<?php echo help("항목 출력 순서를 작성해주세요. 숫자가 작을수록 먼저 출력됩니다.") ?>
						<input type="text" name="ar_order" value="" size="10" />
					</td>
				</tr>
				<tr>
					<th>공개범위</th>
					<td>
						<select name="ar_secret">
							<option value="">전체공개</option>
							<option value="S">오너+관리자 공개</option>
							<option value="H">관리자 공개</option>
						</select>
					</td>
				</tr>
				<tr>
					<th>글자수 제한</th>
					<td>
						<?php echo help("text/textarea 타입에만 적용됩니다. 0 이면 제한 없음.") ?>
						최소 <input type="number" name="ar_min_length" value="0" min="0" size="6" />
						&nbsp;최대 <input type="number" name="ar_max_length" value="0" min="0" size="6" />
						&nbsp;&nbsp;
						<label><input type="checkbox" name="ar_required" value="1" /> 필수 입력</label>
					</td>
				</tr>
				<tr>
					<th>사용 시기</th>
					<td>
						<?php av_time_checkbox_group('ar_time', '', array('ti_type'=>array('au','renewal'))); ?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</section>
<?php  echo $frm_submit; ?>

</form>


<?php
include_once ('./admin.tail.php');
?>
