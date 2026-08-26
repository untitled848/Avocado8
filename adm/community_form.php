<?php
$sub_menu = "100100";
include_once('./_common.php');
include_once(G5_ADMIN_PATH."/config_form_prev.php");
include_once(G5_EDITOR_LIB);

if (!$is_admin)
	alert('관리자만 접근 가능합니다.');

$g5['title'] = '커뮤니티 설정';
include_once ('./admin.head.php');

$pg_anchor = '<ul class="anchor">
	<li><a href="#anc_001">기본환경</a></li>
	<li><a href="#anc_003">기타항목 명칭</a></li>
	<li><a href="#anc_007">여분 필드 (cf_1~cf_10)</a></li>
	<li><a href="#anc_008">접속 제한</a></li>
	<li><a href="#anc_010">레이아웃 추가설정</a></li>
</ul>';

$frm_submit = '<div class="btn_confirm01 btn_confirm">
	<input type="submit" value="확인" class="btn_submit" accesskey="s">
</div>';
?>

<div class="local_desc02 local_desc" style="background:#f7f7f7; padding:8px 12px; border-left:3px solid #888; margin-bottom:10px;">
	<p>이 페이지는 <strong>커뮤니티 기본환경 / 기타 항목 명칭</strong> 만 관리합니다.</p>
	<ul style="margin:6px 0 0 18px; font-size:12px; color:#555;">
		<li>사이트 공개 / 계정·캐릭터 가능 여부 → <a href="./permission_form.php">권한 설정</a></li>
		<li>화폐 명칭/단위 → <a href="./point_list.php">화폐 관리</a></li>
		<li>알림(쪽지) 보관 기간 → <a href="./config_call_form.php">알림 설정</a></li>
		<li>최초 스탯 포인트 → <a href="./status_list.php">스탯 설정 관리</a></li>
		<li>캐릭터 최대 생성 갯수 → <a href="./permission_form.php">권한 설정</a></li>
		<li>상점 카테고리 → <a href="./shop_list.php">상점 관리</a></li>
		<li>아이템 기능 카테고리 → <a href="./item_list.php">아이템 관리</a></li>
		<li>멤버 리스트 표시 → <a href="./character_article_list.php#anc_000">프로필 양식 관리</a></li>
	</ul>
</div>

<form name="fconfigform" id="fconfigform" method="post" onsubmit="return fconfigform_submit(this);" enctype="multipart/form-data">
<?php echo get_admin_token_field(); ?>

<section id="anc_001">
	<h2 class="h2_frm">커뮤니티 기본환경 설정</h2>
	<?php echo $pg_anchor ?>

	<div class="tbl_frm01 tbl_wrap">
		<table>
		<caption>홈페이지 기본환경 설정</caption>
		<colgroup>
			<col style="width: 150px;">
			<col>
		</colgroup>
		<tbody>
		<tr>
			<th scope="row"><label for="cf_admin">최고관리자<strong class="sound_only">필수</strong></label></th>
			<td><?php echo get_member_id_select('cf_admin', 10, $config['cf_admin'], 'required') ?></td>
		</tr>
		<tr>
			<th scope="row">관리자 아이콘</th>
			<td>
				<i class="admin-icon-box">
				<?php if (is_file(G5_DATA_PATH."/site/ico_admin")) { ?>
					<img src="<?php echo G5_DATA_URL ?>/site/ico_admin" alt="관리자 아이콘" />
				<?php } ?>
				</i>
				<input type="file" name="admin_icon_file" value="" size="50">
				<?php if (is_file(G5_DATA_PATH."/site/ico_admin")) { ?>
				<label style="margin-left:8px;"><input type="checkbox" name="admin_icon_delete" value="1"> 현재 아이콘 삭제</label>
				<?php } ?>
			</td>
		</tr>
		<tr>
			<th scope="row">홈페이지 제목</th>
			<td><input type="text" name="cf_title" value="<?php echo $config['cf_title'] ?>" id="cf_title" required class="required" size="40"></td>
		</tr>
		<tr>
			<th>사이트설명</th>
			<td>
				<input type="text" name="cf_site_descript" value="<?php echo $config['cf_site_descript'] ?>" size="50" />
			</td>
		</tr>
		<tr>
			<th rowspan="2">파비콘</th>
			<td>
				<?php echo help('파비콘 확장자는 ico 로 등록해 주셔야 적용됩니다.') ?>
				직접등록&nbsp;&nbsp; <input type="file" name="cf_favicon_file" value="" size="50">
			</td></tr><tr>
			<td>
				외부경로&nbsp;&nbsp; <input type="text" name="cf_favicon" value="<?php echo $config['cf_favicon']?>" size="50"/>
			</td>
		</tr>
		<tr>
			<th rowspan="2">사이트이미지</th>
			<td>
				<?php echo help('사이트 링크 추가시, SNS에서 미리보기로 뜨는 썸네일 이미지를 등록합니다. 290px * 160px 파일로 업로드해 주시길 바랍니다.') ?>
				직접등록&nbsp;&nbsp; <input type="file" name="cf_site_img_file" value="" size="50">
			</td></tr><tr>
			<td>
				외부경로&nbsp;&nbsp; <input type="text" name="cf_site_img" value="<?php echo $config['cf_site_img']?>" size="50"/>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="site_back">배경음악</label></th>
			<td>
				<?php echo help('유튜브 재생목록 아이디 (https://www.youtube.com/watch?list=재생목록고유아이디) 를 입력해 주세요.') ?>
				<input type="text" name="cf_bgm" value="<?php echo $config['cf_bgm'] ?>" id="cf_bgm" size="50">
			</td>
		</tr>
		<tr>
			<th scope="row">기타설정</th>
			<td>
				<?php echo help('디자인 관리 사용을 하시면, 기본 디자인 + 관리자 디자인 설정을 사용하실 수 있습니다. <br> 직접 디자인 수정을 원하신다면, 디자인 관리 사용하지 않음에 체크 하세요.') ?>
				<input type="checkbox" name="cf_7" value="1" id="cf_7" <?php echo $config['cf_7']?'checked':''; ?>>
				<label for="cf_7">디자인 관리 사용하지 않음</label>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="cf_editor">에디터 선택</label></th>
			<td>
				<?php echo help('게시판/회원가입 등에서 사용할 기본 에디터를 선택합니다.') ?>
				<select name="cf_editor" id="cf_editor">
				<?php
				$arr = get_skin_dir('', G5_EDITOR_PATH);
				for ($i=0; $i<count($arr); $i++) {
					if ($i == 0) echo "<option value=\"\">사용안함</option>";
					echo "<option value=\"".$arr[$i]."\"".get_selected($config['cf_editor'], $arr[$i]).">".$arr[$i]."</option>\n";
				}
				?>
				</select>
			</td>
		</tr>
		</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_003">
	<h2 class="h2_frm">기타 항목명 설정</h2>
	<?php echo $pg_anchor ?>

	<div class="tbl_frm01 tbl_wrap">
		<table>
		<colgroup>
			<col style="width: 150px;">
			<col style="width: 100px;">
			<col>
		</colgroup>
		<tbody>
		<tr>
			<th scope="row"><?php echo $config['cf_exp_name']?></th>
			<td>명칭/단위</td>
			<td>		
				<input type="text" name="cf_exp_name" value="<?php echo get_text($config['cf_exp_name']) ?>" id="cf_exp_name" size="5" placeholder="명칭">
				<input type="text" name="cf_exp_pice" value="<?php echo get_text($config['cf_exp_pice']) ?>" id="cf_exp_pice" size="5" placeholder="단위">
			</td>
		</tr>

		<tr>
			<th scope="row"><?php echo $config['cf_rank_name']?></th>
			<td>명칭</td>
			<td>		
				<input type="text" name="cf_rank_name" value="<?php echo get_text($config['cf_rank_name']) ?>" id="cf_rank_name" size="30">
			</td>
		</tr>

		<tr>
			<th scope="row"><?php echo $config['cf_side_title']?> (선택A)</th>
			<td>명칭</td>
			<td>
				<?php echo help('명칭이 입력되지 않을 시, 프로필에 출력되지 않습니다.') ?>
				<input type="text" name="cf_side_title" value="<?php echo get_text($config['cf_side_title']) ?>" id="cf_side_title" size="30">
			</td>
		</tr>
		<tr>
			<th scope="row"><?php echo $config['cf_class_title']?> (선택B)</th>
			<td>명칭</td>
			<td>
				<?php echo help('명칭이 입력되지 않을 시, 프로필에 출력되지 않습니다.') ?>
				<input type="text" name="cf_class_title" value="<?php echo get_text($config['cf_class_title']) ?>" id="cf_class_title" size="30">
			</td>
		</tr>
		</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_007">
	<h2 class="h2_frm">여분 필드 (cf_1 ~ cf_10)</h2>
	<?php echo $pg_anchor ?>
	<div class="local_desc02 local_desc">
		<p>config 테이블의 <strong>cf_1 ~ cf_10</strong> 컬럼은 일부가 코어 기능에 의해 사용되고, 나머지는 자유 사용 여분 필드입니다.<br>
		아래에서 모두 직접 수정 가능합니다. (<em>이미 사용중인 항목은 라벨에 용도 표시</em>)</p>
	</div>
	<div class="tbl_frm01 tbl_wrap">
		<table>
		<colgroup>
			<col style="width: 200px;">
			<col>
		</colgroup>
		<tbody>
		<?php
		// cf_N 별 라벨 + 안내 — 코어 검색 결과 기준
		$cf_extra_meta = array(
			3  => array('label' => 'cf_3 — 여분 필드', 'help' => '현재 코어에서 사용되는 곳이 없습니다. 자유롭게 사용 가능.', 'type' => 'text'),
			5  => array('label' => 'cf_5 — 여분 필드', 'help' => '현재 코어에서 사용되는 곳이 없습니다. 자유롭게 사용 가능.', 'type' => 'text'),
			6  => array('label' => 'cf_6 — 여분 필드', 'help' => '현재 코어에서 사용되는 곳이 없습니다. 자유롭게 사용 가능.', 'type' => 'text'),
			8  => array('label' => 'cf_8 — 여분 필드', 'help' => '현재 코어에서 사용되는 곳이 없습니다. 자유롭게 사용 가능.', 'type' => 'text'),
			9  => array('label' => 'cf_9 — 여분 필드', 'help' => '현재 코어에서 사용되는 곳이 없습니다. 자유롭게 사용 가능.', 'type' => 'text'),
			10 => array('label' => 'cf_10 — MMB 메인 상단 공지(한줄 롤링)', 'help' => 'skin/board/mmb/list.skin.php 의 marquee 영역에 출력됩니다. (HTML 일부 가능)', 'type' => 'textarea'),
		);
		foreach ($cf_extra_meta as $n => $meta) {
			$key = 'cf_'.$n;
			$val = isset($config[$key]) ? $config[$key] : '';
			echo '<tr>';
			echo '<th scope="row"><label for="'.$key.'">'.htmlspecialchars($meta['label'], ENT_QUOTES).'</label></th>';
			echo '<td>';
			if ($meta['help']) echo '<p style="margin:0 0 4px 0; font-size:12px; color:#666;">'.htmlspecialchars($meta['help'], ENT_QUOTES).'</p>';
			if ($meta['type'] === 'check') {
				$chk = !empty($val) ? ' checked' : '';
				echo '<input type="checkbox" name="'.$key.'" id="'.$key.'" value="1"'.$chk.'> <label for="'.$key.'">사용</label>';
			} elseif ($meta['type'] === 'textarea') {
				echo '<textarea name="'.$key.'" id="'.$key.'" rows="3" style="width:100%;">'.get_text((string)$val).'</textarea>';
			} else {
				echo '<input type="text" name="'.$key.'" id="'.$key.'" value="'.htmlspecialchars((string)$val, ENT_QUOTES).'" size="60">';
			}
			echo '</td></tr>';
		}
		?>
		</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_008">
	<h2 class="h2_frm">접속 제한 설정</h2>
	<?php echo $pg_anchor ?>
	<div class="local_desc02 local_desc">
		<p>특정 IP 대역의 접속을 허용하거나 차단하는 설정입니다.</p>
	</div>
	<div class="tbl_frm01 tbl_wrap">
		<table>
		<colgroup>
			<col style="width: 150px;">
			<col>
		</colgroup>
		<tbody>
		<tr>
			<th scope="row"><label for="cf_possible_ip">접근가능 IP</label></th>
			<td>
				<?php echo help('입력된 IP의 컴퓨터만 접근할 수 있습니다. 123.123.+ 도 입력 가능. (엔터로 구분)') ?>
				<textarea name="cf_possible_ip" id="cf_possible_ip" rows="4" style="width:100%;"><?php echo $config['cf_possible_ip'] ?></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="cf_intercept_ip">접근차단 IP</label></th>
			<td>
				<?php echo help('입력된 IP의 컴퓨터는 접근할 수 없음. 123.123.+ 도 입력 가능. (엔터로 구분)') ?>
				<textarea name="cf_intercept_ip" id="cf_intercept_ip" rows="4" style="width:100%;"><?php echo $config['cf_intercept_ip'] ?></textarea>
			</td>
		</tr>
		</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_010">
	<h2 class="h2_frm">레이아웃 추가설정</h2>
	<?php echo $pg_anchor ?>
	<div class="local_desc02 local_desc">
		<p>기본 설정된 파일 경로 및 script, css 를 추가하거나 변경할 수 있습니다.</p>
	</div>
	<div class="tbl_frm01 tbl_wrap">
		<table>
		<colgroup>
			<col style="width: 150px;">
			<col>
		</colgroup>
		<tbody>
		<tr>
			<th scope="row"><label for="cf_add_script">추가 script, css</label></th>
			<td>
				<?php echo help('HTML의 &lt;/HEAD&gt; 태그위로 추가될 JavaScript와 css 코드를 설정합니다.<br>관리자 페이지에서는 이 코드를 사용하지 않습니다.') ?>
				<textarea name="cf_add_script" id="cf_add_script" style="width:100%; height:300px;"><?php echo get_text($config['cf_add_script']); ?></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="cf_add_fonts">추가 폰트 (head)</label></th>
			<td>
				<?php echo help('Google Fonts 등 외부 폰트 link/style 태그를 HTML &lt;HEAD&gt; 내부에 추가합니다.') ?>
				<textarea name="cf_add_fonts" id="cf_add_fonts" style="width:100%; height:140px;"><?php echo get_text($config['cf_add_fonts'] ?? ''); ?></textarea>
			</td>
		</tr>
		</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>

</form>

<script>
function fconfigform_submit(f)
{
	f.action = "./community_form_update.php";
	return true;
}
</script>

<?php
include_once ('./admin.tail.php');
?>
