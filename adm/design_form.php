<?php
$sub_menu = "110300";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

if (!$is_admin)
	alert('관리자만 접근 가능합니다.');

// =========================================================
// 디자인 프리셋 선택
// =========================================================
// GET 으로 ?pd_id 가 명시되면 그 값, 아니면 현재 적용 중인 디자인 프리셋을 기본값으로
$pd_list = function_exists('design_preset_list') ? design_preset_list() : array(1 => 'default');
if (isset($_GET['pd_id'])) {
    $pd_id = (int)$_GET['pd_id'];
} else {
    $pd_id = function_exists('get_active_design') ? (int)get_active_design('main') : 0;
}
if ($pd_id <= 0 || !isset($pd_list[$pd_id])) $pd_id = 1;
$pd_cur  = function_exists('get_design_preset') ? get_design_preset($pd_id) : array('pd_id' => 1, 'pd_name' => 'default');

// 이 디자인 프리셋을 참조하고 있는 테마 프리셋 목록
$linked_themes = array();
if (!empty($g5['theme_preset_table'])) {
    $sql = "SELECT pr_id, pr_name FROM `{$g5['theme_preset_table']}` WHERE pr_design = '".(int)$pd_id."'";
    if ($pd_id == 1) $sql .= " OR pr_design = 'default' OR pr_design = ''";
    $sql .= " ORDER BY pr_order ASC, pr_id ASC";
    $r = sql_query($sql, false);
    if ($r) { while ($x = sql_fetch_array($r)) $linked_themes[] = $x; }
}

// 현재 사이트에서 실제 적용 중인 디자인 프리셋
$active_pd_id = function_exists('get_active_design') ? (int)get_active_design('main') : 1;
$active_preset = function_exists('get_preset') ? get_preset() : array('pr_name' => '');
$active_preset_name = !empty($active_preset['pr_name']) ? $active_preset['pr_name'] : '';

$g5['title'] = '페이지 디자인 설정';
include_once ('./admin.head.php');
$design_hub = 'page';
$design_hub_tab = 'site';
include_once('./_design_hub_tabs.inc.php');

$design_result = sql_query("select * from {$g5['css_table']} where pd_id = '{$pd_id}'");
$de = array();
for($i=0; $row = sql_fetch_array($design_result); $i++) {
	$de[$row['cs_name']] = $row;
}

$pg_anchor = '<ul class="anchor">
	<li><a href="#anc_001">화면</a></li>
	<li><a href="#anc_002">메뉴</a></li>
	<li><a href="#anc_003">버튼</a></li>
	<li><a href="#anc_006">테이블(게시판)</a></li>
	<li><a href="#anc_004">로드비 디자인</a></li>
	<li><a href="#anc_005">기타</a></li>
</ul>';

$frm_submit = '<div class="btn_confirm01 btn_confirm">
	<input type="submit" value="확인" class="btn_submit" accesskey="s">
</div>';

if($config['cf_add_fonts']) {
	$config['cf_add_fonts'] = get_text($config['cf_add_fonts'], 0);
}

$css_index = 0;
$editor_list = array();
?>
<div class="design-preset-bar">
    <div class="help">
        <strong>이 탭에서:</strong> 사이트 전역의 색상·폰트·버튼 모양을 편집합니다.
        여러 저장본은 아래에서 전환하고, 시기에 쓰려면 <a href="./theme_preset_list.php">테마 프리셋</a>에 넣습니다.
    </div>
    <div class="row">
        <label>현재 편집 중:</label>
        <strong>#<?php echo (int)$pd_id; ?> <?php echo h($pd_cur['pd_name']); ?></strong>
        <?php if ($pd_id == 1) echo ' <span class="linked">— 기본 디자인 (출력: _design.config.css)</span>'; ?>
    </div>
    <div class="row">
        <label>다른 디자인 편집:</label>
        <select id="pd_switch">
            <?php foreach ($pd_list as $pid => $pname) { ?>
            <option value="<?php echo (int)$pid; ?>" <?php echo ($pid == $pd_id) ? 'selected' : ''; ?>>#<?php echo (int)$pid; ?> <?php echo h($pname); ?></option>
            <?php } ?>
        </select>
        <button type="button" onclick="location.href='./design_form.php?pd_id='+document.getElementById('pd_switch').value;" class="btn_admin">전환</button>
        <button type="button" onclick="addNewDesign();" class="btn_admin">+ 새 디자인 만들기</button>
        <button type="button" onclick="copyDesign();" class="btn_admin">현재 디자인 복제</button>
        <button type="button" onclick="renameDesign();" class="btn_admin">이름 변경</button>
        <?php if ($pd_id != 1) { ?>
        <button type="button" onclick="if(confirm('정말 삭제하시겠습니까?\n이 디자인 프리셋과 관련 css_config 행이 모두 삭제됩니다.')) location.href='./design_preset_action.php?action=delete&pd_id=<?php echo (int)$pd_id; ?>';" class="btn_admin danger">삭제</button>
        <?php } ?>
    </div>
    <div class="row">
        <label>JSON:</label>
        <button type="button" onclick="exportDesign();" class="btn_admin">JSON 내보내기</button>
        <button type="button" onclick="importDesign();" class="btn_admin">JSON 가져오기 (현재에 적용)</button>
        <button type="button" onclick="importDesignNew();" class="btn_admin">JSON으로 새 디자인</button>
        <span class="linked">저장되지 않은 변경은 포함되지 않습니다. 로고 등 이미지는 경로만 저장되며, 다른 사이트에서 가져오면 파일을 다시 올려야 할 수 있습니다.</span>
    </div>
    <form id="design_json_import_form" method="post" action="./design_preset_action.php" enctype="multipart/form-data" style="display:none">
        <input type="hidden" name="action" id="design_json_action" value="import">
        <input type="hidden" name="pd_id" value="<?php echo (int)$pd_id; ?>">
        <input type="hidden" name="pd_name" id="design_json_pd_name" value="">
        <input type="file" name="json_file" id="design_json_file" accept="application/json,.json">
    </form>
    <?php if (!empty($linked_themes)) { ?>
    <div class="row linked">
        <label>이 디자인을 사용 중인 테마 프리셋:</label>
        <?php
        $links = array();
        foreach ($linked_themes as $t) {
            $links[] = '<a href="./theme_preset_form.php?pr_id='.(int)$t['pr_id'].'">#'.(int)$t['pr_id'].' '.h($t['pr_name']).'</a>';
        }
        echo implode(', ', $links);
        ?>
    </div>
    <?php } else if ((int)$pd_id === (int)$active_pd_id) { ?>
    <div class="row linked">
        <strong style="color:#369;">★ 현재 사용중인 <?php echo h($active_preset_name ?: 'default'); ?> 의 디자인입니다.</strong>
    </div>
    <?php } else { ?>
    <div class="row linked">아직 이 디자인을 사용하는 테마 프리셋이 없습니다.
        <a href="./theme_preset_list.php">[테마 프리셋으로 이동]</a>
    </div>
    <?php } ?>
</div>

<script>
function addNewDesign() {
    var nm = prompt('새 디자인 프리셋 이름을 입력하세요\n(예: "할로윈", "겨울 시즌")');
    if (!nm) return;
    location.href = './design_preset_action.php?action=create&pd_name=' + encodeURIComponent(nm);
}
function copyDesign() {
    var nm = prompt('복제본 이름?', '<?php echo h($pd_cur['pd_name']); ?>_copy');
    if (!nm) return;
    location.href = './design_preset_action.php?action=copy&pd_id=<?php echo (int)$pd_id; ?>&pd_name=' + encodeURIComponent(nm);
}
function renameDesign() {
    var nm = prompt('새 이름?', '<?php echo h($pd_cur['pd_name']); ?>');
    if (!nm) return;
    location.href = './design_preset_action.php?action=rename&pd_id=<?php echo (int)$pd_id; ?>&pd_name=' + encodeURIComponent(nm);
}
function exportDesign() {
    location.href = './design_preset_action.php?action=export&pd_id=<?php echo (int)$pd_id; ?>';
}
function importDesign() {
    if (!confirm('현재 편집 중인 디자인 설정을 JSON 파일 내용으로 덮어씁니다.\n(이미지 파일 자체는 포함되지 않으며, 경로만 가져옵니다.)\n계속할까요?')) return;
    document.getElementById('design_json_action').value = 'import';
    document.getElementById('design_json_pd_name').value = '';
    document.getElementById('design_json_file').value = '';
    document.getElementById('design_json_file').click();
}
function importDesignNew() {
    var nm = prompt('새 디자인 프리셋 이름을 입력하세요', '<?php echo h($pd_cur['pd_name']); ?>_import');
    if (!nm) return;
    document.getElementById('design_json_action').value = 'import_new';
    document.getElementById('design_json_pd_name').value = nm;
    document.getElementById('design_json_file').value = '';
    document.getElementById('design_json_file').click();
}
(function(){
    var fi = document.getElementById('design_json_file');
    if (!fi) return;
    fi.addEventListener('change', function(){
        if (this.files && this.files[0]) {
            document.getElementById('design_json_import_form').submit();
        }
    });
})();
</script>

<div class="df-split">
<main class="df-form-col">

<form name="fconfigform" id="fconfigform" method="post" onsubmit="return fconfigform_submit(this);" enctype="multipart/form-data" target="_self">
<input type="hidden" name="token" value="" id="token">
<input type="hidden" name="pd_id" value="<?php echo (int)$pd_id; ?>">

<section id="anc_001">
	<h2 class="h2_frm">화면 디자인 설정</h2>
	<?php echo $pg_anchor ?>

	<div class="local_desc02 local_desc">
		<p>다양한 디자인을 적용하기 위해선 직접 HTML / CSS 파일을 수정해 주시길 바랍니다.</p>
	</div>

	<div class="tbl_frm01 tbl_wrap">
		<table>
			<caption>홈페이지 기본환경 설정</caption>
			<colgroup>
				<col style="width: 140px;">
				<col style="width: 130px;">
				<col>
			</colgroup>
			<tbody>
				<tr>
					<th scope="row" class="design-th">
						<em><?php echo $css_index?></em>헤더사용
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="use_header" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						-
					</td>
					<td>
						<?php echo help('로고 및 메뉴 영역 사용에 대한 선택입니다. 내용만 출력하실 경우 사용하지 않음을 선택해 주세요.') ?>
						<select name="cs_value[<?php echo $css_index?>]">
							<option value="">사용</option>
							<option value="N" <?php echo $de['use_header']['cs_value'] == 'N' ? "selected" : ""?>>사용하지 않음</option>
						</select>
					</td>
				</tr <?php  $css_index++; ?>>
			</tbody>
		</table>
		<br />
		<table>
			<caption>홈페이지 기본환경 설정</caption>
			<colgroup>
				<col style="width: 140px;">
				<col style="width: 130px;">
				<col>
			</colgroup>
			<tbody>
				<tr>
					<th rowspan="2" class="design-th">
						<em><?php echo $css_index?></em>로고
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="logo" readonly class="full" />
					</th>
					<td rowspan="2" class="bo-right bo-left txt-center">
						<?php  if($de['logo']['cs_value']) { ?>
							<img src="<?php echo $de['logo']['cs_value']?>" class="prev_thumb"/>
						<?php  } else { ?>
						이미지 미등록
						<?php  } ?>
					</td>
					<td>
						직접등록&nbsp;&nbsp; <input type="file" name="cs_value_file[<?php echo $css_index?>]" value="" size="50">
					</td></tr><tr>
					<td>
						외부경로&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['logo']['cs_value']?>" size="50"/>
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th rowspan="2" class="design-th">
						<em><?php echo $css_index?></em>모바일로고
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="m_logo" readonly class="full" />
					</th>
					<td rowspan="2" class="bo-right bo-left txt-center">
						<?php  if($de['m_logo']['cs_value']) { ?>
							<img src="<?php echo $de['m_logo']['cs_value']?>" class="prev_thumb"/>
						<?php  } else { ?>
						이미지 미등록
						<?php  } ?>
					</td>
					<td>
						직접등록&nbsp;&nbsp; <input type="file" name="cs_value_file[<?php echo $css_index?>]" value="" size="50">
					</td></tr><tr>
					<td>
						외부경로&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['m_logo']['cs_value']?>" size="50"/>
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th rowspan="3" class="design-th">
						<em><?php echo $css_index?></em>배경
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="background" readonly class="full" />
					</th>
					<td rowspan="3" class="bo-right bo-left txt-center">
						<?php  if($de['background']['cs_value']) { ?>
							<img src="<?php echo $de['background']['cs_value']?>" class="prev_thumb"/>
						<?php  } else { ?>
						이미지 미등록
						<?php  } ?>
					</td>
					<td>
						직접등록&nbsp;&nbsp; <input type="file" name="cs_value_file[<?php echo $css_index?>]" value="" size="50">
					</td></tr><tr>
					<td>
						외부경로&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['background']['cs_value']?>" size="50"/>				
					</td></tr><tr>
					<td>
						색상코드&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['background']['cs_etc_1']?>" size="8"/>
						<input type="color" class="color-preview" value="<?php echo $de['background']['cs_etc_1']?>" />
						&nbsp;&nbsp;

						배경반복&nbsp;&nbsp;
						<select name="cs_etc_2[<?php echo $css_index?>]">
							<option value="">반복</option>
							<option value="no-repeat" <?php echo $de['background']['cs_etc_2'] == 'no-repeat' ? "selected" : ""?>>반복없음</option>
							<option value="repeat-x"  <?php echo $de['background']['cs_etc_2'] == 'repeat-x' ? "selected" : ""?>>가로반복</option>
							<option value="repeat-y"  <?php echo $de['background']['cs_etc_2'] == 'repeat-y' ? "selected" : ""?>>세로반복</option>
						</select>
						&nbsp;&nbsp;

						배경위치&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]">
							<option value="">왼쪽 상단</option>
							<option value="left middle"		<?php echo $de['background']['cs_etc_3'] == 'left middle' ? "selected" : ""?>>왼쪽 중단</option>
							<option value="left bottom"		<?php echo $de['background']['cs_etc_3'] == 'left bottom' ? "selected" : ""?>>왼쪽 하단</option>

							<option value="center top"		<?php echo $de['background']['cs_etc_3'] == 'center top' ? "selected" : ""?>>중간 상단</option>
							<option value="center center"	<?php echo $de['background']['cs_etc_3'] == 'center center' ? "selected" : ""?>>중간 중단</option>
							<option value="center bottom"	<?php echo $de['background']['cs_etc_3'] == 'center bottom' ? "selected" : ""?>>중간 하단</option>

							<option value="right top"		<?php echo $de['background']['cs_etc_3'] == 'right top' ? "selected" : ""?>>오른쪽 상단</option>
							<option value="right middle"	<?php echo $de['background']['cs_etc_3'] == 'right middle' ? "selected" : ""?>>오른쪽 중단</option>
							<option value="right bottom"	<?php echo $de['background']['cs_etc_3'] == 'right bottom' ? "selected" : ""?>>오른쪽 하단</option>
						</select>
						&nbsp;&nbsp;

						배경크기&nbsp;&nbsp;
						<select name="cs_etc_4[<?php echo $css_index?>]">
							<option value="">원본크기</option>
							<option value="contain"		<?php echo $de['background']['cs_etc_4'] == 'contain' ? "selected" : ""?>>맞춤</option>
							<option value="cover"		<?php echo $de['background']['cs_etc_4'] == 'cover' ? "selected" : ""?>>꽉참</option>
							<option value="100% 100%"	<?php echo $de['background']['cs_etc_4'] == '100% 100%' ? "selected" : ""?>>늘이기</option>
						</select>
						&nbsp;&nbsp;

						고정여부&nbsp;&nbsp;
						<select name="cs_etc_5[<?php echo $css_index?>]">
							<option value="">스크롤(기본)</option>
							<option value="scroll" <?php echo $de['background']['cs_etc_5'] == 'scroll' ? "selected" : ""?>>스크롤</option>
							<option value="fixed"  <?php echo $de['background']['cs_etc_5'] == 'fixed' ? "selected" : ""?>>고정</option>
							<option value="local"  <?php echo $de['background']['cs_etc_5'] == 'local' ? "selected" : ""?>>요소내</option>
						</select>
						&nbsp;&nbsp;
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th rowspan="3" class="design-th">
						<em><?php echo $css_index?></em>헤더배경
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="header_background" readonly class="full" />
					</th>
					<td rowspan="3" class="bo-right bo-left txt-center">
						<?php  if($de['header_background']['cs_value']) { ?>
							<img src="<?php echo $de['header_background']['cs_value']?>" class="prev_thumb"/>
						<?php  } else { ?>
						이미지 미등록
						<?php  } ?>
					</td>
					<td>
						직접등록&nbsp;&nbsp; <input type="file" name="cs_value_file[<?php echo $css_index?>]" value="" size="50">
					</td></tr><tr>
					<td>
						외부경로&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['header_background']['cs_value']?>" size="50"/>				
					</td></tr><tr>
					<td>
						색상코드&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['header_background']['cs_etc_1']?>" size="8"/>
						<input type="color" class="color-preview" value="<?php echo $de['header_background']['cs_etc_1']?>" />
						&nbsp;&nbsp;

						배경반복&nbsp;&nbsp;
						<select name="cs_etc_2[<?php echo $css_index?>]">
							<option value="">반복</option>
							<option value="no-repeat" <?php echo $de['header_background']['cs_etc_2'] == 'no-repeat' ? "selected" : ""?>>반복없음</option>
							<option value="repeat-x"  <?php echo $de['header_background']['cs_etc_2'] == 'repeat-x' ? "selected" : ""?>>가로반복</option>
							<option value="repeat-y"  <?php echo $de['header_background']['cs_etc_2'] == 'repeat-y' ? "selected" : ""?>>세로반복</option>
						</select>
						&nbsp;&nbsp;

						배경위치&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]">
							<option value="">왼쪽 상단</option>
							<option value="left middle"		<?php echo $de['header_background']['cs_etc_3'] == 'left middle' ? "selected" : ""?>>왼쪽 중단</option>
							<option value="left bottom"		<?php echo $de['header_background']['cs_etc_3'] == 'left bottom' ? "selected" : ""?>>왼쪽 하단</option>

							<option value="center top"		<?php echo $de['header_background']['cs_etc_3'] == 'center top' ? "selected" : ""?>>중간 상단</option>
							<option value="center center"	<?php echo $de['header_background']['cs_etc_3'] == 'center center' ? "selected" : ""?>>중간 중단</option>
							<option value="center bottom"	<?php echo $de['header_background']['cs_etc_3'] == 'center bottom' ? "selected" : ""?>>중간 하단</option>

							<option value="right top"		<?php echo $de['header_background']['cs_etc_3'] == 'right top' ? "selected" : ""?>>오른쪽 상단</option>
							<option value="right middle"	<?php echo $de['header_background']['cs_etc_3'] == 'right middle' ? "selected" : ""?>>오른쪽 중단</option>
							<option value="right bottom"	<?php echo $de['header_background']['cs_etc_3'] == 'right bottom' ? "selected" : ""?>>오른쪽 하단</option>
						</select>
						&nbsp;&nbsp;

						배경크기&nbsp;&nbsp;
						<select name="cs_etc_4[<?php echo $css_index?>]">
							<option value="">원본크기</option>
							<option value="contain"		<?php echo $de['header_background']['cs_etc_4'] == 'contain' ? "selected" : ""?>>맞춤</option>
							<option value="cover"		<?php echo $de['header_background']['cs_etc_4'] == 'cover' ? "selected" : ""?>>꽉참</option>
							<option value="100% 100%"	<?php echo $de['header_background']['cs_etc_4'] == '100% 100%' ? "selected" : ""?>>늘이기</option>
						</select>
						&nbsp;&nbsp;

						고정&nbsp;&nbsp;
						<select name="cs_etc_5[<?php echo $css_index?>]">
							<option value="">스크롤</option>
							<option value="fixed"		<?php echo $de['header_background']['cs_etc_5'] == 'fixed' ? "selected" : ""?>>고정</option>
						</select>
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th rowspan="3" class="design-th">
						<em><?php echo $css_index?></em>모바일 배경
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="m_background" readonly class="full" />
					</th>
					<td rowspan="3" class="bo-right bo-left txt-center">
						<?php  if($de['m_background']['cs_value']) { ?>
							<img src="<?php echo $de['m_background']['cs_value']?>" class="prev_thumb"/>
						<?php  } else { ?>
						이미지 미등록
						<?php  } ?>
					</td>
					<td>
						직접등록&nbsp;&nbsp; <input type="file" name="cs_value_file[<?php echo $css_index?>]" value="" size="50">
					</td></tr><tr>
					<td>
						외부경로&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['m_background']['cs_value']?>" size="50"/>				
					</td></tr><tr>
					<td>
						색상코드&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['m_background']['cs_etc_1']?>" size="8"/>
						<input type="color" class="color-preview" value="<?php echo $de['m_background']['cs_etc_1']?>" />
						&nbsp;&nbsp;

						배경반복&nbsp;&nbsp;
						<select name="cs_etc_2[<?php echo $css_index?>]">
							<option value="">반복</option>
							<option value="no-repeat" <?php echo $de['m_background']['cs_etc_2'] == 'no-repeat' ? "selected" : ""?>>반복없음</option>
							<option value="repeat-x"  <?php echo $de['m_background']['cs_etc_2'] == 'repeat-x' ? "selected" : ""?>>가로반복</option>
							<option value="repeat-y"  <?php echo $de['m_background']['cs_etc_2'] == 'repeat-y' ? "selected" : ""?>>세로반복</option>
						</select>
						&nbsp;&nbsp;

						배경위치&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]">
							<option value="">왼쪽 상단</option>
							<option value="left middle"		<?php echo $de['m_background']['cs_etc_3'] == 'left middle' ? "selected" : ""?>>왼쪽 중단</option>
							<option value="left bottom"		<?php echo $de['m_background']['cs_etc_3'] == 'left bottom' ? "selected" : ""?>>왼쪽 하단</option>

							<option value="center top"		<?php echo $de['m_background']['cs_etc_3'] == 'center top' ? "selected" : ""?>>중간 상단</option>
							<option value="center center"	<?php echo $de['m_background']['cs_etc_3'] == 'center center' ? "selected" : ""?>>중간 중단</option>
							<option value="center bottom"	<?php echo $de['m_background']['cs_etc_3'] == 'center bottom' ? "selected" : ""?>>중간 하단</option>

							<option value="right top"		<?php echo $de['m_background']['cs_etc_3'] == 'right top' ? "selected" : ""?>>오른쪽 상단</option>
							<option value="right middle"	<?php echo $de['m_background']['cs_etc_3'] == 'right middle' ? "selected" : ""?>>오른쪽 중단</option>
							<option value="right bottom"	<?php echo $de['m_background']['cs_etc_3'] == 'right bottom' ? "selected" : ""?>>오른쪽 하단</option>
						</select>
						&nbsp;&nbsp;

						배경크기&nbsp;&nbsp;
						<select name="cs_etc_4[<?php echo $css_index?>]">
							<option value="">원본크기</option>
							<option value="contain"		<?php echo $de['m_background']['cs_etc_4'] == 'contain' ? "selected" : ""?>>맞춤</option>
							<option value="cover"		<?php echo $de['m_background']['cs_etc_4'] == 'cover' ? "selected" : ""?>>꽉참</option>
							<option value="100% 100%"	<?php echo $de['m_background']['cs_etc_4'] == '100% 100%' ? "selected" : ""?>>늘이기</option>
						</select>
						&nbsp;&nbsp;

						고정여부&nbsp;&nbsp;
						<select name="cs_etc_5[<?php echo $css_index?>]">
							<option value="">스크롤(기본)</option>
							<option value="scroll" <?php echo $de['m_background']['cs_etc_5'] == 'scroll' ? "selected" : ""?>>스크롤</option>
							<option value="fixed"  <?php echo $de['m_background']['cs_etc_5'] == 'fixed' ? "selected" : ""?>>고정</option>
							<option value="local"  <?php echo $de['m_background']['cs_etc_5'] == 'local' ? "selected" : ""?>>요소내</option>
						</select>
						&nbsp;&nbsp;
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th rowspan="3" class="design-th">
						<em><?php echo $css_index?></em>모바일 헤더배경
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="m_header_background" readonly class="full" />
					</th>
					<td rowspan="3" class="bo-right bo-left txt-center">
						<?php  if($de['m_header_background']['cs_value']) { ?>
							<img src="<?php echo $de['m_header_background']['cs_value']?>" class="prev_thumb"/>
						<?php  } else { ?>
						이미지 미등록
						<?php  } ?>
					</td>
					<td>
						직접등록&nbsp;&nbsp; <input type="file" name="cs_value_file[<?php echo $css_index?>]" value="" size="50">
					</td></tr><tr>
					<td>
						외부경로&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['m_header_background']['cs_value']?>" size="50"/>				
					</td></tr><tr>
					<td>
						색상코드&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['m_header_background']['cs_etc_1']?>" size="8"/>
						<input type="color" class="color-preview" value="<?php echo $de['m_header_background']['cs_etc_1']?>" />
						&nbsp;&nbsp;

						배경반복&nbsp;&nbsp;
						<select name="cs_etc_2[<?php echo $css_index?>]">
							<option value="">반복</option>
							<option value="no-repeat" <?php echo $de['m_header_background']['cs_etc_2'] == 'no-repeat' ? "selected" : ""?>>반복없음</option>
							<option value="repeat-x"  <?php echo $de['m_header_background']['cs_etc_2'] == 'repeat-x' ? "selected" : ""?>>가로반복</option>
							<option value="repeat-y"  <?php echo $de['m_header_background']['cs_etc_2'] == 'repeat-y' ? "selected" : ""?>>세로반복</option>
						</select>
						&nbsp;&nbsp;

						배경위치&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]">
							<option value="">왼쪽 상단</option>
							<option value="left middle"		<?php echo $de['m_header_background']['cs_etc_3'] == 'left middle' ? "selected" : ""?>>왼쪽 중단</option>
							<option value="left bottom"		<?php echo $de['m_header_background']['cs_etc_3'] == 'left bottom' ? "selected" : ""?>>왼쪽 하단</option>

							<option value="center top"		<?php echo $de['m_header_background']['cs_etc_3'] == 'center top' ? "selected" : ""?>>중간 상단</option>
							<option value="center center"	<?php echo $de['m_header_background']['cs_etc_3'] == 'center center' ? "selected" : ""?>>중간 중단</option>
							<option value="center bottom"	<?php echo $de['m_header_background']['cs_etc_3'] == 'center bottom' ? "selected" : ""?>>중간 하단</option>

							<option value="right top"		<?php echo $de['m_header_background']['cs_etc_3'] == 'right top' ? "selected" : ""?>>오른쪽 상단</option>
							<option value="right middle"	<?php echo $de['m_header_background']['cs_etc_3'] == 'right middle' ? "selected" : ""?>>오른쪽 중단</option>
							<option value="right bottom"	<?php echo $de['m_header_background']['cs_etc_3'] == 'right bottom' ? "selected" : ""?>>오른쪽 하단</option>
						</select>
						&nbsp;&nbsp;

						배경크기&nbsp;&nbsp;
						<select name="cs_etc_4[<?php echo $css_index?>]">
							<option value="">원본크기</option>
							<option value="contain"		<?php echo $de['m_header_background']['cs_etc_4'] == 'contain' ? "selected" : ""?>>맞춤</option>
							<option value="cover"		<?php echo $de['m_header_background']['cs_etc_4'] == 'cover' ? "selected" : ""?>>꽉참</option>
							<option value="100% 100%"	<?php echo $de['m_header_background']['cs_etc_4'] == '100% 100%' ? "selected" : ""?>>늘이기</option>
						</select>
						&nbsp;&nbsp;

						고정&nbsp;&nbsp;
						<select name="cs_etc_5[<?php echo $css_index?>]">
							<option value="">스크롤</option>
							<option value="fixed"		<?php echo $de['m_header_background']['cs_etc_5'] == 'fixed' ? "selected" : ""?>>고정</option>
						</select>
					</td>
				</tr <?php  $css_index++; ?>>

			</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_002">
	<h2 class="h2_frm">메뉴 디자인 설정</h2>
	<?php echo $pg_anchor ?>

	<div class="local_desc02 local_desc">
		<p>다양한 디자인을 적용하기 위해선 직접 HTML / CSS 파일을 수정해 주시길 바랍니다.</p>
	</div>

	<div class="tbl_frm01 tbl_wrap">
		<table>
			<caption>홈페이지 기본환경 설정</caption>
			<colgroup>
				<col style="width: 140px;">
				<col style="width: 130px;">
				<col>
			</colgroup>
			<tbody>
				<tr>
					<th scope="row" class="design-th">
						<em><?php echo $css_index?></em>메뉴위치
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="menu_pos" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						-
					</td>
					<td>
						<select name="cs_value[<?php echo $css_index?>]">
							<option value="left">좌측</option>
							<option value="top" <?php echo $de['menu_pos']['cs_value'] == 'top' ? "selected" : ""?>>상단</option>
						</select>
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th scope="row" class="design-th">
						<em><?php echo $css_index?></em>가로사이즈
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="menu_width" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						-
					</td>
					<td>
						<input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['menu_width']['cs_value']?>" size="10"/> px
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th scope="row" class="design-th">
						<em><?php echo $css_index?></em>세로사이즈
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="menu_height" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						-
					</td>
					<td>
						<input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['menu_height']['cs_value']?>" size="10"/> px
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th rowspan="3" scope="row" class="design-th">
						<em><?php echo $css_index?></em>메뉴배경
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="menu_background" readonly class="full" />
					</th>
					<td rowspan="3" class="bo-right bo-left txt-center">
						<?php  if($de['menu_background']['cs_value']) { ?>
							<img src="<?php echo $de['menu_background']['cs_value']?>" class="prev_thumb"/>
						<?php  } else { ?>
						이미지 미등록
						<?php  } ?>
					</td>
					<td>
						직접등록&nbsp;&nbsp; <input type="file" name="cs_value_file[<?php echo $css_index?>]" value="" size="50">
					</td></tr><tr>
					<td>
						외부경로&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['menu_background']['cs_value']?>" size="50"/>				
					</td></tr><tr>
					<td>
						색상코드&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['menu_background']['cs_etc_1']?>" size="8"/>
						<input type="color" class="color-preview" value="<?php echo $de['menu_background']['cs_etc_1']?>" />
						&nbsp;&nbsp;

						배경반복&nbsp;&nbsp;
						<select name="cs_etc_2[<?php echo $css_index?>]">
							<option value="">반복</option>
							<option value="no-repeat" <?php echo $de['menu_background']['cs_etc_2'] == 'no-repeat' ? "selected" : ""?>>반복없음</option>
							<option value="repeat-x"  <?php echo $de['menu_background']['cs_etc_2'] == 'repeat-x' ? "selected" : ""?>>가로반복</option>
							<option value="repeat-y"  <?php echo $de['menu_background']['cs_etc_2'] == 'repeat-y' ? "selected" : ""?>>세로반복</option>
						</select>
						&nbsp;&nbsp;

						배경위치&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]">
							<option value="">왼쪽 상단</option>
							<option value="left middle"		<?php echo $de['menu_background']['cs_etc_3'] == 'left middle' ? "selected" : ""?>>왼쪽 중단</option>
							<option value="left bottom"		<?php echo $de['menu_background']['cs_etc_3'] == 'left bottom' ? "selected" : ""?>>왼쪽 하단</option>

							<option value="center top"		<?php echo $de['menu_background']['cs_etc_3'] == 'center top' ? "selected" : ""?>>중간 상단</option>
							<option value="center center"	<?php echo $de['menu_background']['cs_etc_3'] == 'center center' ? "selected" : ""?>>중간 중단</option>
							<option value="center bottom"	<?php echo $de['menu_background']['cs_etc_3'] == 'center bottom' ? "selected" : ""?>>중간 하단</option>

							<option value="right top"		<?php echo $de['menu_background']['cs_etc_3'] == 'right top' ? "selected" : ""?>>오른쪽 상단</option>
							<option value="right middle"	<?php echo $de['menu_background']['cs_etc_3'] == 'right middle' ? "selected" : ""?>>오른쪽 중단</option>
							<option value="right bottom"	<?php echo $de['menu_background']['cs_etc_3'] == 'right bottom' ? "selected" : ""?>>오른쪽 하단</option>
						</select>
						&nbsp;&nbsp;

						배경크기&nbsp;&nbsp;
						<select name="cs_etc_4[<?php echo $css_index?>]">
							<option value="">원본크기</option>
							<option value="contain"		<?php echo $de['menu_background']['cs_etc_4'] == 'contain' ? "selected" : ""?>>맞춤</option>
							<option value="cover"		<?php echo $de['menu_background']['cs_etc_4'] == 'cover' ? "selected" : ""?>>꽉참</option>
							<option value="100% 100%"	<?php echo $de['menu_background']['cs_etc_4'] == '100% 100%' ? "selected" : ""?>>늘이기</option>
						</select>
						&nbsp;&nbsp;

						고정&nbsp;&nbsp;
						<select name="cs_etc_5[<?php echo $css_index?>]">
							<option value="">스크롤</option>
							<option value="fixed"		<?php echo $de['menu_background']['cs_etc_5'] == 'fixed' ? "selected" : ""?>>고정</option>
						</select>
					</td>
				</tr <?php  $css_index++; ?>>

				

				<tr>
					<th rowspan="3" scope="row" class="design-th">
						<em><?php echo $css_index?></em>모바일 메뉴배경
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="m_menu_background" readonly class="full" />
					</th>
					<td rowspan="3" class="bo-right bo-left txt-center">
						<?php  if($de['m_menu_background']['cs_value']) { ?>
							<img src="<?php echo $de['m_menu_background']['cs_value']?>" class="prev_thumb"/>
						<?php  } else { ?>
						이미지 미등록
						<?php  } ?>
					</td>
					<td>
						직접등록&nbsp;&nbsp; <input type="file" name="cs_value_file[<?php echo $css_index?>]" value="" size="50">
					</td></tr><tr>
					<td>
						외부경로&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['m_menu_background']['cs_value']?>" size="50"/>				
					</td></tr><tr>
					<td>
						색상코드&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['m_menu_background']['cs_etc_1']?>" size="8"/>
						<input type="color" class="color-preview" value="<?php echo $de['m_menu_background']['cs_etc_1']?>" />
						&nbsp;&nbsp;

						배경반복&nbsp;&nbsp;
						<select name="cs_etc_2[<?php echo $css_index?>]">
							<option value="">반복</option>
							<option value="no-repeat" <?php echo $de['m_menu_background']['cs_etc_2'] == 'no-repeat' ? "selected" : ""?>>반복없음</option>
							<option value="repeat-x"  <?php echo $de['m_menu_background']['cs_etc_2'] == 'repeat-x' ? "selected" : ""?>>가로반복</option>
							<option value="repeat-y"  <?php echo $de['m_menu_background']['cs_etc_2'] == 'repeat-y' ? "selected" : ""?>>세로반복</option>
						</select>
						&nbsp;&nbsp;

						배경위치&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]">
							<option value="">왼쪽 상단</option>
							<option value="left middle"		<?php echo $de['m_menu_background']['cs_etc_3'] == 'left middle' ? "selected" : ""?>>왼쪽 중단</option>
							<option value="left bottom"		<?php echo $de['m_menu_background']['cs_etc_3'] == 'left bottom' ? "selected" : ""?>>왼쪽 하단</option>

							<option value="center top"		<?php echo $de['m_menu_background']['cs_etc_3'] == 'center top' ? "selected" : ""?>>중간 상단</option>
							<option value="center center"	<?php echo $de['m_menu_background']['cs_etc_3'] == 'center center' ? "selected" : ""?>>중간 중단</option>
							<option value="center bottom"	<?php echo $de['m_menu_background']['cs_etc_3'] == 'center bottom' ? "selected" : ""?>>중간 하단</option>

							<option value="right top"		<?php echo $de['m_menu_background']['cs_etc_3'] == 'right top' ? "selected" : ""?>>오른쪽 상단</option>
							<option value="right middle"	<?php echo $de['m_menu_background']['cs_etc_3'] == 'right middle' ? "selected" : ""?>>오른쪽 중단</option>
							<option value="right bottom"	<?php echo $de['m_menu_background']['cs_etc_3'] == 'right bottom' ? "selected" : ""?>>오른쪽 하단</option>
						</select>
						&nbsp;&nbsp;

						배경크기&nbsp;&nbsp;
						<select name="cs_etc_4[<?php echo $css_index?>]">
							<option value="">원본크기</option>
							<option value="contain"		<?php echo $de['m_menu_background']['cs_etc_4'] == 'contain' ? "selected" : ""?>>맞춤</option>
							<option value="cover"		<?php echo $de['m_menu_background']['cs_etc_4'] == 'cover' ? "selected" : ""?>>꽉참</option>
							<option value="100% 100%"	<?php echo $de['m_menu_background']['cs_etc_4'] == '100% 100%' ? "selected" : ""?>>늘이기</option>
						</select>
						&nbsp;&nbsp;

						고정&nbsp;&nbsp;
						<select name="cs_etc_5[<?php echo $css_index?>]">
							<option value="">스크롤</option>
							<option value="fixed"		<?php echo $de['m_menu_background']['cs_etc_5'] == 'fixed' ? "selected" : ""?>>고정</option>
						</select>
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th rowspan="2" scope="row" class="design-th">
						<em><?php echo $css_index?></em>폰트색상
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="menu_text" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						일반상태
					</td>
					<td>
						폰트색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['menu_text']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['menu_text']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트크기&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['menu_text']['cs_etc_1']?>" size="5"/> px

					</td></tr><tr>
					<td class="bo-right bo-left txt-center">
						마우스 오버
					</td>
					<td>
						폰트색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['menu_text']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['menu_text']['cs_etc_2']?>" />
						&nbsp;&nbsp;
						폰트크기&nbsp;&nbsp; <input type="text" name="cs_etc_3[<?php echo $css_index?>]" value="<?php echo $de['menu_text']['cs_etc_3']?>" size="5"/> px
					</td>
				</tr <?php  $css_index++; ?>>

			</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_006">
	<h2 class="h2_frm">테이블(게시판)디자인</h2>
	<?php echo $pg_anchor ?>

	<div class="local_desc02 local_desc">
		<p>다양한 디자인을 적용하기 위해선 직접 HTML / CSS 파일을 수정해 주시길 바랍니다.</p>
	</div>

	<div class="tbl_frm01 tbl_wrap">
		<table>
			<caption>홈페이지 기본환경 설정</caption>
			<colgroup>
				<col style="width: 140px;">
				<col style="width: 130px;">
				<col>
			</colgroup>
			<tbody>
				<tr>
					<th rowspan="2" scope="row" class="design-th">
						<em><?php echo $css_index?></em>전체테이블
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="board_table" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						배경/폰트
					</td>
					<td>
						배경색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['board_table']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['board_table']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트색상&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['board_table']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['board_table']['cs_etc_1']?>" />
					</td></tr><tr>
					<td class="bo-right bo-left txt-center">
						라인
					</td>
					<td>
						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['board_table']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['board_table']['cs_etc_2']?>" />
						&nbsp;&nbsp;

						라인타입&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]" style="width: 84px;">
							<option value="">라인없음</option>
							<option value="solid" <?php echo $de['board_table']['cs_etc_3'] == 'solid' ? "selected" : ""?>>실선</option>
							<option value="dotted" <?php echo $de['board_table']['cs_etc_3'] == 'dotted' ? "selected" : ""?>>점선</option>
							<option value="dashed" <?php echo $de['board_table']['cs_etc_3'] == 'dashed' ? "selected" : ""?>>대쉬선</option>
							<option value="double" <?php echo $de['board_table']['cs_etc_3'] == 'double' ? "selected" : ""?>>이중선</option>
						</select>
						&nbsp;&nbsp;

						라인굵기&nbsp;&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['board_table']['cs_etc_4']?>" size="5"/> px
						&nbsp;&nbsp;&nbsp;&nbsp;

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_1_board_table" value="top" <?php echo strstr($de['board_table']['cs_etc_5'], 'top') ? "checked" : ""?> />
						<label for="cs_etc_5_1_board_table">상&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_2_board_table" value="bottom" <?php echo strstr($de['board_table']['cs_etc_5'], 'bottom') ? "checked" : ""?> />
						<label for="cs_etc_5_2_board_table">하&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_3_board_table" value="left" <?php echo strstr($de['board_table']['cs_etc_5'], 'left') ? "checked" : ""?> />
						<label for="cs_etc_5_3_board_table">좌&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_4_board_table" value="right" <?php echo strstr($de['board_table']['cs_etc_5'], 'right') ? "checked" : ""?> />
						<label for="cs_etc_5_4_board_table">우</label>
					</td>
				</tr <?php  $css_index++; ?>>
				<tr>
					<th rowspan="2" scope="row" class="design-th">
						<em><?php echo $css_index?></em>목록 : 제목
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="list_header" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						배경/폰트
					</td>
					<td>
						배경색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['list_header']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['list_header']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트색상&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['list_header']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['list_header']['cs_etc_1']?>" />
					</td></tr><tr>
					<td class="bo-right bo-left txt-center">
						라인
					</td>
					<td>
						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['list_header']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['list_header']['cs_etc_2']?>" />
						&nbsp;&nbsp;

						라인타입&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]" style="width: 84px;">
							<option value="">라인없음</option>
							<option value="solid" <?php echo $de['list_header']['cs_etc_3'] == 'solid' ? "selected" : ""?>>실선</option>
							<option value="dotted" <?php echo $de['list_header']['cs_etc_3'] == 'dotted' ? "selected" : ""?>>점선</option>
							<option value="dashed" <?php echo $de['list_header']['cs_etc_3'] == 'dashed' ? "selected" : ""?>>대쉬선</option>
							<option value="double" <?php echo $de['list_header']['cs_etc_3'] == 'double' ? "selected" : ""?>>이중선</option>
						</select>
						&nbsp;&nbsp;

						라인굵기&nbsp;&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['list_header']['cs_etc_4']?>" size="5"/> px
						&nbsp;&nbsp;&nbsp;&nbsp;

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_1_list_header" value="top" <?php echo strstr($de['list_header']['cs_etc_5'], 'top') ? "checked" : ""?> />
						<label for="cs_etc_5_1_list_header">상&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_2_list_header" value="bottom" <?php echo strstr($de['list_header']['cs_etc_5'], 'bottom') ? "checked" : ""?> />
						<label for="cs_etc_5_2_list_header">하&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_3_list_header" value="left" <?php echo strstr($de['list_header']['cs_etc_5'], 'left') ? "checked" : ""?> />
						<label for="cs_etc_5_3_list_header">좌&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_4_list_header" value="right" <?php echo strstr($de['list_header']['cs_etc_5'], 'right') ? "checked" : ""?> />
						<label for="cs_etc_5_4_list_header">우</label>
					</td>
				</tr <?php  $css_index++; ?>>
				<tr>
					<th rowspan="2" scope="row" class="design-th">
						<em><?php echo $css_index?></em>목록 : 내용
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="list_body" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						배경/폰트
					</td>
					<td>
						배경색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['list_body']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['list_body']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트색상&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['list_body']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['list_body']['cs_etc_1']?>" />
					</td></tr><tr>
					<td class="bo-right bo-left txt-center">
						라인
					</td>
					<td>
						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['list_body']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['list_body']['cs_etc_2']?>" />
						&nbsp;&nbsp;

						라인타입&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]" style="width: 84px;">
							<option value="">라인없음</option>
							<option value="solid" <?php echo $de['list_body']['cs_etc_3'] == 'solid' ? "selected" : ""?>>실선</option>
							<option value="dotted" <?php echo $de['list_body']['cs_etc_3'] == 'dotted' ? "selected" : ""?>>점선</option>
							<option value="dashed" <?php echo $de['list_body']['cs_etc_3'] == 'dashed' ? "selected" : ""?>>대쉬선</option>
							<option value="double" <?php echo $de['list_body']['cs_etc_3'] == 'double' ? "selected" : ""?>>이중선</option>
						</select>
						&nbsp;&nbsp;

						라인굵기&nbsp;&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['list_body']['cs_etc_4']?>" size="5"/> px
						&nbsp;&nbsp;&nbsp;&nbsp;

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_1_list_body" value="top" <?php echo strstr($de['list_body']['cs_etc_5'], 'top') ? "checked" : ""?> />
						<label for="cs_etc_5_1_list_body">상&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_2_list_body" value="bottom" <?php echo strstr($de['list_body']['cs_etc_5'], 'bottom') ? "checked" : ""?> />
						<label for="cs_etc_5_2_list_body">하&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_3_list_body" value="left" <?php echo strstr($de['list_body']['cs_etc_5'], 'left') ? "checked" : ""?> />
						<label for="cs_etc_5_3_list_body">좌&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_4_list_body" value="right" <?php echo strstr($de['list_body']['cs_etc_5'], 'right') ? "checked" : ""?> />
						<label for="cs_etc_5_4_list_body">우</label>
					</td>
				</tr <?php  $css_index++; ?>>
				<tr>
					<th rowspan="2" scope="row" class="design-th">
						<em><?php echo $css_index?></em>양식 : 제목
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="form_header" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						배경/폰트
					</td>
					<td>
						배경색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['form_header']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['form_header']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트색상&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['form_header']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['form_header']['cs_etc_1']?>" />
					</td></tr><tr>
					<td class="bo-right bo-left txt-center">
						라인
					</td>
					<td>
						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['form_header']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['form_header']['cs_etc_2']?>" />
						&nbsp;&nbsp;

						라인타입&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]" style="width: 84px;">
							<option value="">라인없음</option>
							<option value="solid" <?php echo $de['form_header']['cs_etc_3'] == 'solid' ? "selected" : ""?>>실선</option>
							<option value="dotted" <?php echo $de['form_header']['cs_etc_3'] == 'dotted' ? "selected" : ""?>>점선</option>
							<option value="dashed" <?php echo $de['form_header']['cs_etc_3'] == 'dashed' ? "selected" : ""?>>대쉬선</option>
							<option value="double" <?php echo $de['form_header']['cs_etc_3'] == 'double' ? "selected" : ""?>>이중선</option>
						</select>
						&nbsp;&nbsp;

						라인굵기&nbsp;&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['form_header']['cs_etc_4']?>" size="5"/> px
						&nbsp;&nbsp;&nbsp;&nbsp;

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_1_form_header" value="top" <?php echo strstr($de['form_header']['cs_etc_5'], 'top') ? "checked" : ""?> />
						<label for="cs_etc_5_1_form_header">상&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_2_form_header" value="bottom" <?php echo strstr($de['form_header']['cs_etc_5'], 'bottom') ? "checked" : ""?> />
						<label for="cs_etc_5_2_form_header">하&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_3_form_header" value="left" <?php echo strstr($de['form_header']['cs_etc_5'], 'left') ? "checked" : ""?> />
						<label for="cs_etc_5_3_form_header">좌&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_4_form_header" value="right" <?php echo strstr($de['form_header']['cs_etc_5'], 'right') ? "checked" : ""?> />
						<label for="cs_etc_5_4_form_header">우</label>
					</td>
				</tr <?php  $css_index++; ?>>
				<tr>
					<th rowspan="2" scope="row" class="design-th">
						<em><?php echo $css_index?></em>양식 : 내용
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="form_body" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						배경/폰트
					</td>
					<td>
						배경색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['form_body']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['form_body']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트색상&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['form_body']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['form_body']['cs_etc_1']?>" />
					</td></tr><tr>
					<td class="bo-right bo-left txt-center">
						라인
					</td>
					<td>
						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['form_body']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['form_body']['cs_etc_2']?>" />
						&nbsp;&nbsp;

						라인타입&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]" style="width: 84px;">
							<option value="">라인없음</option>
							<option value="solid" <?php echo $de['form_body']['cs_etc_3'] == 'solid' ? "selected" : ""?>>실선</option>
							<option value="dotted" <?php echo $de['form_body']['cs_etc_3'] == 'dotted' ? "selected" : ""?>>점선</option>
							<option value="dashed" <?php echo $de['form_body']['cs_etc_3'] == 'dashed' ? "selected" : ""?>>대쉬선</option>
							<option value="double" <?php echo $de['form_body']['cs_etc_3'] == 'double' ? "selected" : ""?>>이중선</option>
						</select>
						&nbsp;&nbsp;

						라인굵기&nbsp;&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['form_body']['cs_etc_4']?>" size="5"/> px
						&nbsp;&nbsp;&nbsp;&nbsp;

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_1_form_body" value="top" <?php echo strstr($de['form_body']['cs_etc_5'], 'top') ? "checked" : ""?> />
						<label for="cs_etc_5_1_form_body">상&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_2_form_body" value="bottom" <?php echo strstr($de['form_body']['cs_etc_5'], 'bottom') ? "checked" : ""?> />
						<label for="cs_etc_5_2_form_body">하&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_3_form_body" value="left" <?php echo strstr($de['form_body']['cs_etc_5'], 'left') ? "checked" : ""?> />
						<label for="cs_etc_5_3_form_body">좌&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_4_form_body" value="right" <?php echo strstr($de['form_body']['cs_etc_5'], 'right') ? "checked" : ""?> />
						<label for="cs_etc_5_4_form_body">우</label>
					</td>
				</tr <?php  $css_index++; ?>>
			</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_003">
	<h2 class="h2_frm">버튼 디자인 설정</h2>
	<?php echo $pg_anchor ?>

	<div class="local_desc02 local_desc">
		<p>다양한 디자인을 적용하기 위해선 직접 HTML / CSS 파일을 수정해 주시길 바랍니다.</p>
	</div>

	<div class="tbl_frm01 tbl_wrap">
		<table>
			<caption>홈페이지 기본환경 설정</caption>
			<colgroup>
				<col style="width: 140px;">
				<col style="width: 130px;">
				<col>
			</colgroup>
			<tbody>
				<tr>
					<th rowspan="3" scope="row" class="design-th">
						<em><?php echo $css_index?></em>기본버튼
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="btn_default" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						일반상태
					</td>
					<td>
						텍스트색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['btn_default']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_default']['cs_value']?>" />
						&nbsp;&nbsp;

						배경색상&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['btn_default']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_default']['cs_etc_1']?>" />
						&nbsp;&nbsp;

						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['btn_default']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_default']['cs_etc_2']?>" />
					</td></tr><tr>
					<td class="bo-right bo-left txt-center">
						마우스 오버
					</td>
					<td>
						텍스트색상&nbsp;&nbsp; <input type="text" name="cs_etc_3[<?php echo $css_index?>]" value="<?php echo $de['btn_default']['cs_etc_3']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_default']['cs_etc_3']?>" />
						&nbsp;&nbsp;

						배경색상&nbsp;&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['btn_default']['cs_etc_4']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_default']['cs_etc_4']?>" />
						&nbsp;&nbsp;

						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_5[<?php echo $css_index?>]" value="<?php echo $de['btn_default']['cs_etc_5']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_default']['cs_etc_5']?>" />
					</td></tr><tr>
					<td class="bo-right txt-center">
						모서리 라운드
					</td>
					<td>
						좌측상단 <input type="text" name="cs_etc_12[<?php echo $css_index?>]" value="<?php echo $de['btn_default']['cs_etc_12']=isset($de['btn_default']['cs_etc_12'])?$de['btn_default']['cs_etc_12']:0?>" size="3"/> px
						&nbsp;&nbsp;
						우측상단 <input type="text" name="cs_etc_13[<?php echo $css_index?>]" value="<?php echo $de['btn_default']['cs_etc_13']=isset($de['btn_default']['cs_etc_13'])?$de['btn_default']['cs_etc_13']:0?>" size="3"/> px
						&nbsp;&nbsp;
						우측하단 <input type="text" name="cs_etc_14[<?php echo $css_index?>]" value="<?php echo $de['btn_default']['cs_etc_14']=isset($de['btn_default']['cs_etc_14'])?$de['btn_default']['cs_etc_14']:0?>" size="3"/> px
						&nbsp;&nbsp;
						좌측하단 <input type="text" name="cs_etc_15[<?php echo $css_index?>]" value="<?php echo $de['btn_default']['cs_etc_15']=isset($de['btn_default']['cs_etc_15'])?$de['btn_default']['cs_etc_15']:0?>" size="3"/> px
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th rowspan="3" scope="row" class="design-th">
						<em><?php echo $css_index?></em>포인트버튼
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="btn_point" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						일반상태
					</td>
					<td>
						텍스트색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['btn_point']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_point']['cs_value']?>" />
						&nbsp;&nbsp;

						배경색상&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['btn_point']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_point']['cs_etc_1']?>" />
						&nbsp;&nbsp;

						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['btn_point']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_point']['cs_etc_2']?>" />
					</td></tr><tr>
					<td class="bo-right bo-left txt-center">
						마우스 오버
					</td>
					<td>
						텍스트색상&nbsp;&nbsp; <input type="text" name="cs_etc_3[<?php echo $css_index?>]" value="<?php echo $de['btn_point']['cs_etc_3']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_point']['cs_etc_3']?>" />
						&nbsp;&nbsp;

						배경색상&nbsp;&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['btn_point']['cs_etc_4']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_point']['cs_etc_4']?>" />
						&nbsp;&nbsp;

						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_5[<?php echo $css_index?>]" value="<?php echo $de['btn_point']['cs_etc_5']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_point']['cs_etc_5']?>" />
					</td></tr><tr>
					<td class="bo-right txt-center">
						모서리 라운드
					</td>
					<td>
						좌측상단 <input type="text" name="cs_etc_12[<?php echo $css_index?>]" value="<?php echo $de['btn_point']['cs_etc_12']=isset($de['btn_point']['cs_etc_12'])?$de['btn_point']['cs_etc_12']:0?>" size="3"/> px
						&nbsp;&nbsp;
						우측상단 <input type="text" name="cs_etc_13[<?php echo $css_index?>]" value="<?php echo $de['btn_point']['cs_etc_13']=isset($de['btn_point']['cs_etc_13'])?$de['btn_point']['cs_etc_13']:0?>" size="3"/> px
						&nbsp;&nbsp;
						우측하단 <input type="text" name="cs_etc_14[<?php echo $css_index?>]" value="<?php echo $de['btn_point']['cs_etc_14']=isset($de['btn_point']['cs_etc_14'])?$de['btn_point']['cs_etc_14']:0?>" size="3"/> px
						&nbsp;&nbsp;
						좌측하단 <input type="text" name="cs_etc_15[<?php echo $css_index?>]" value="<?php echo $de['btn_point']['cs_etc_15']=isset($de['btn_point']['cs_etc_15'])?$de['btn_point']['cs_etc_15']:0?>" size="3"/> px
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th rowspan="3" scope="row" class="design-th">
						<em><?php echo $css_index?></em>기타버튼
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="btn_etc" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						일반상태
					</td>
					<td>
						텍스트색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['btn_etc']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_etc']['cs_value']?>" />
						&nbsp;&nbsp;

						배경색상&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['btn_etc']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_etc']['cs_etc_1']?>" />
						&nbsp;&nbsp;

						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['btn_etc']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_etc']['cs_etc_2']?>" />
					</td></tr><tr>
					<td class="bo-right bo-left txt-center">
						마우스 오버
					</td>
					<td>
						텍스트색상&nbsp;&nbsp; <input type="text" name="cs_etc_3[<?php echo $css_index?>]" value="<?php echo $de['btn_etc']['cs_etc_3']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_etc']['cs_etc_3']?>" />
						&nbsp;&nbsp;

						배경색상&nbsp;&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['btn_etc']['cs_etc_4']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_etc']['cs_etc_4']?>" />
						&nbsp;&nbsp;

						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_5[<?php echo $css_index?>]" value="<?php echo $de['btn_etc']['cs_etc_5']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['btn_etc']['cs_etc_5']?>" />
					</td></tr><tr>
					<td class="bo-right txt-center">
						모서리 라운드
					</td>
					<td>
						좌측상단 <input type="text" name="cs_etc_12[<?php echo $css_index?>]" value="<?php echo $de['btn_etc']['cs_etc_12']=isset($de['btn_etc']['cs_etc_12'])?$de['btn_etc']['cs_etc_12']:0?>" size="3"/> px
						&nbsp;&nbsp;
						우측상단 <input type="text" name="cs_etc_13[<?php echo $css_index?>]" value="<?php echo $de['btn_etc']['cs_etc_13']=isset($de['btn_etc']['cs_etc_13'])?$de['btn_etc']['cs_etc_13']:0?>" size="3"/> px
						&nbsp;&nbsp;
						우측하단 <input type="text" name="cs_etc_14[<?php echo $css_index?>]" value="<?php echo $de['btn_etc']['cs_etc_14']=isset($de['btn_etc']['cs_etc_14'])?$de['btn_etc']['cs_etc_14']:0?>" size="3"/> px
						&nbsp;&nbsp;
						좌측하단 <input type="text" name="cs_etc_15[<?php echo $css_index?>]" value="<?php echo $de['btn_etc']['cs_etc_15']=isset($de['btn_etc']['cs_etc_15'])?$de['btn_etc']['cs_etc_15']:0?>" size="3"/> px
					</td>
				</tr <?php  $css_index++; ?>>

			</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_004">
	<h2 class="h2_frm">로드 게시판 디자인 설정</h2>
	<?php echo $pg_anchor ?>

	<div class="local_desc02 local_desc">
		<p>다양한 디자인을 적용하기 위해선 직접 HTML / CSS 파일을 수정해 주시길 바랍니다.</p>
	</div>

	<div class="tbl_frm01 tbl_wrap">
		<table>
			<caption>홈페이지 기본환경 설정</caption>
			<colgroup>
				<col style="width: 140px;">
				<col style="width: 130px;">
				<col>
			</colgroup>
			<tbody>
				<tr>
					<th rowspan="3" scope="row" class="design-th">
						<em><?php echo $css_index?></em>전체 배경
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="mmb_contain_bak" readonly class="full" />
					</th>
					<td rowspan="3" class="bo-right bo-left txt-center">
						<?php  if($de['mmb_contain_bak']['cs_value']) { ?>
							<img src="<?php echo $de['mmb_contain_bak']['cs_value']?>" class="prev_thumb"/>
						<?php  } else { ?>
						이미지 미등록
						<?php  } ?>
					</td>
					<td>
						직접등록&nbsp;&nbsp; <input type="file" name="cs_value_file[<?php echo $css_index?>]" value="" size="50">
					</td></tr><tr>
					<td>
						외부경로&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['mmb_contain_bak']['cs_value']?>" size="50"/>				
					</td></tr><tr>
					<td>
						색상코드&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['mmb_contain_bak']['cs_etc_1']?>" size="8"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_contain_bak']['cs_etc_1']?>" />
						&nbsp;&nbsp;

						배경반복&nbsp;&nbsp;
						<select name="cs_etc_2[<?php echo $css_index?>]">
							<option value="">반복</option>
							<option value="no-repeat" <?php echo $de['mmb_contain_bak']['cs_etc_2'] == 'no-repeat' ? "selected" : ""?>>반복없음</option>
							<option value="repeat-x"  <?php echo $de['mmb_contain_bak']['cs_etc_2'] == 'repeat-x' ? "selected" : ""?>>가로반복</option>
							<option value="repeat-y"  <?php echo $de['mmb_contain_bak']['cs_etc_2'] == 'repeat-y' ? "selected" : ""?>>세로반복</option>
						</select>
						&nbsp;&nbsp;

						배경위치&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]">
							<option value="">왼쪽 상단</option>
							<option value="left middle"		<?php echo $de['mmb_contain_bak']['cs_etc_3'] == 'left middle' ? "selected" : ""?>>왼쪽 중단</option>
							<option value="left bottom"		<?php echo $de['mmb_contain_bak']['cs_etc_3'] == 'left bottom' ? "selected" : ""?>>왼쪽 하단</option>

							<option value="center top"		<?php echo $de['mmb_contain_bak']['cs_etc_3'] == 'center top' ? "selected" : ""?>>중간 상단</option>
							<option value="center center"	<?php echo $de['mmb_contain_bak']['cs_etc_3'] == 'center center' ? "selected" : ""?>>중간 중단</option>
							<option value="center bottom"	<?php echo $de['mmb_contain_bak']['cs_etc_3'] == 'center bottom' ? "selected" : ""?>>중간 하단</option>

							<option value="right top"		<?php echo $de['mmb_contain_bak']['cs_etc_3'] == 'right top' ? "selected" : ""?>>오른쪽 상단</option>
							<option value="right middle"	<?php echo $de['mmb_contain_bak']['cs_etc_3'] == 'right middle' ? "selected" : ""?>>오른쪽 중단</option>
							<option value="right bottom"	<?php echo $de['mmb_contain_bak']['cs_etc_3'] == 'right bottom' ? "selected" : ""?>>오른쪽 하단</option>
						</select>
						&nbsp;&nbsp;

						배경크기&nbsp;&nbsp;
						<select name="cs_etc_4[<?php echo $css_index?>]">
							<option value="">원본크기</option>
							<option value="contain"		<?php echo $de['mmb_contain_bak']['cs_etc_4'] == 'contain' ? "selected" : ""?>>맞춤</option>
							<option value="cover"		<?php echo $de['mmb_contain_bak']['cs_etc_4'] == 'cover' ? "selected" : ""?>>꽉참</option>
							<option value="100% 100%"	<?php echo $de['mmb_contain_bak']['cs_etc_4'] == '100% 100%' ? "selected" : ""?>>늘이기</option>
						</select>
						&nbsp;&nbsp;

						고정&nbsp;&nbsp;
						<select name="cs_etc_5[<?php echo $css_index?>]">
							<option value="">스크롤</option>
							<option value="fixed"		<?php echo $de['mmb_contain_bak']['cs_etc_5'] == 'fixed' ? "selected" : ""?>>고정</option>
						</select>
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th rowspan="2" scope="row" class="design-th">
						<em><?php echo $css_index?></em>공지사항
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="mmb_notice" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						배경/폰트
					</td>
					<td>
						배경색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['mmb_notice']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_notice']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트색상&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['mmb_notice']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_notice']['cs_etc_1']?>" />
					</td></tr><tr>
					<td class="bo-right bo-left txt-center">
						라인
					</td>
					<td>
						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['mmb_notice']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_notice']['cs_etc_2']?>" />
						&nbsp;&nbsp;

						라인타입&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]" style="width: 84px;">
							<option value="">라인없음</option>
							<option value="solid" <?php echo $de['mmb_notice']['cs_etc_3'] == 'solid' ? "selected" : ""?>>실선</option>
							<option value="dotted" <?php echo $de['mmb_notice']['cs_etc_3'] == 'dotted' ? "selected" : ""?>>점선</option>
							<option value="dashed" <?php echo $de['mmb_notice']['cs_etc_3'] == 'dashed' ? "selected" : ""?>>대쉬선</option>
							<option value="double" <?php echo $de['mmb_notice']['cs_etc_3'] == 'double' ? "selected" : ""?>>이중선</option>
						</select>
						&nbsp;&nbsp;

						라인굵기&nbsp;&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['mmb_notice']['cs_etc_4']?>" size="5"/> px
						&nbsp;&nbsp;&nbsp;&nbsp;

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_1_mmb_notice" value="top" <?php echo strstr($de['mmb_notice']['cs_etc_5'], 'top') ? "checked" : ""?> />
						<label for="cs_etc_5_1_mmb_notice">상&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_2_mmb_notice" value="bottom" <?php echo strstr($de['mmb_notice']['cs_etc_5'], 'bottom') ? "checked" : ""?> />
						<label for="cs_etc_5_2_mmb_notice">하&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_3_mmb_notice" value="left" <?php echo strstr($de['mmb_notice']['cs_etc_5'], 'left') ? "checked" : ""?> />
						<label for="cs_etc_5_3_mmb_notice">좌&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_4_mmb_notice" value="right" <?php echo strstr($de['mmb_notice']['cs_etc_5'], 'right') ? "checked" : ""?> />
						<label for="cs_etc_5_4_mmb_notice">우</label>
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th scope="row" class="design-th">
						<em><?php echo $css_index?></em>접속자 카운터
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="mmb_counter" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						<a href="https://whos.amung.us/getstarted/" target="_blank" class="btn_">Whos Link</a>
					</td>
					<td>
						<textarea name="cs_value[<?php echo $css_index?>]" style="height:90px;"><?php echo $de['mmb_counter']['cs_value']?></textarea>
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th rowspan="2" scope="row" class="design-th">
						<em><?php echo $css_index?></em>리스트영역
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="mmb_list" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						배경/폰트
					</td>
					<td>
						배경색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['mmb_list']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_list']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트색상&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['mmb_list']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_list']['cs_etc_1']?>" />
					</td></tr><tr>
					<td class="bo-right bo-left txt-center">
						라인
					</td>
					<td>
						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['mmb_list']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_list']['cs_etc_2']?>" />
						&nbsp;&nbsp;

						라인타입&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]" style="width: 84px;">
							<option value="">라인없음</option>
							<option value="solid" <?php echo $de['mmb_list']['cs_etc_3'] == 'solid' ? "selected" : ""?>>실선</option>
							<option value="dotted" <?php echo $de['mmb_list']['cs_etc_3'] == 'dotted' ? "selected" : ""?>>점선</option>
							<option value="dashed" <?php echo $de['mmb_list']['cs_etc_3'] == 'dashed' ? "selected" : ""?>>대쉬선</option>
							<option value="double" <?php echo $de['mmb_list']['cs_etc_3'] == 'double' ? "selected" : ""?>>이중선</option>
						</select>
						&nbsp;&nbsp;

						라인굵기&nbsp;&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['mmb_list']['cs_etc_4']?>" size="5"/> px
						&nbsp;&nbsp;&nbsp;&nbsp;

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_1_mmb_list" value="top" <?php echo strstr($de['mmb_list']['cs_etc_5'], 'top') ? "checked" : ""?> />
						<label for="cs_etc_5_1_mmb_list">상&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_2_mmb_list" value="bottom" <?php echo strstr($de['mmb_list']['cs_etc_5'], 'bottom') ? "checked" : ""?> />
						<label for="cs_etc_5_2_mmb_list">하&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_3_mmb_list" value="left" <?php echo strstr($de['mmb_list']['cs_etc_5'], 'left') ? "checked" : ""?> />
						<label for="cs_etc_5_3_mmb_list">좌&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_4_mmb_list" value="right" <?php echo strstr($de['mmb_list']['cs_etc_5'], 'right') ? "checked" : ""?> />
						<label for="cs_etc_5_4_mmb_list">우</label>
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th rowspan="2" scope="row" class="design-th">
						<em><?php echo $css_index?></em>로그 <br /><span class="red">이미지 + 코멘트</span>
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="mmb_list_item" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						배경/폰트
					</td>
					<td>
						배경색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['mmb_list_item']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_list_item']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트색상&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['mmb_list_item']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_list_item']['cs_etc_1']?>" />
						&nbsp;&nbsp;
						하단여백&nbsp;&nbsp; <input type="text" name="cs_etc_6[<?php echo $css_index?>]" value="<?php echo $de['mmb_list_item']['cs_etc_6']?>" size="10"/> px
					</td></tr><tr>
					<td class="bo-right bo-left txt-center">
						라인
					</td>
					<td>
						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['mmb_list_item']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_list_item']['cs_etc_2']?>" />
						&nbsp;&nbsp;

						라인타입&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]" style="width: 84px;">
							<option value="">라인없음</option>
							<option value="solid" <?php echo $de['mmb_list_item']['cs_etc_3'] == 'solid' ? "selected" : ""?>>실선</option>
							<option value="dotted" <?php echo $de['mmb_list_item']['cs_etc_3'] == 'dotted' ? "selected" : ""?>>점선</option>
							<option value="dashed" <?php echo $de['mmb_list_item']['cs_etc_3'] == 'dashed' ? "selected" : ""?>>대쉬선</option>
							<option value="double" <?php echo $de['mmb_list_item']['cs_etc_3'] == 'double' ? "selected" : ""?>>이중선</option>
						</select>
						&nbsp;&nbsp;

						라인굵기&nbsp;&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['mmb_list_item']['cs_etc_4']?>" size="5"/> px
						&nbsp;&nbsp;&nbsp;&nbsp;

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_1_mmb_list_item" value="top" <?php echo strstr($de['mmb_list_item']['cs_etc_5'], 'top') ? "checked" : ""?> />
						<label for="cs_etc_5_1_mmb_list_item">상&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_2_mmb_list_item" value="bottom" <?php echo strstr($de['mmb_list_item']['cs_etc_5'], 'bottom') ? "checked" : ""?> />
						<label for="cs_etc_5_2_mmb_list_item">하&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_3_mmb_list_item" value="left" <?php echo strstr($de['mmb_list_item']['cs_etc_5'], 'left') ? "checked" : ""?> />
						<label for="cs_etc_5_3_mmb_list_item">좌&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_4_mmb_list_item" value="right" <?php echo strstr($de['mmb_list_item']['cs_etc_5'], 'right') ? "checked" : ""?> />
						<label for="cs_etc_5_4_mmb_list_item">우</label>
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th rowspan="2" scope="row" class="design-th">
						<em><?php echo $css_index?></em>이미지
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="mmb_log" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						배경/폰트
					</td>
					<td>
						배경색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['mmb_log']['cs_value']?>" size="10"/>	
						<input type="color" class="color-preview" value="<?php echo $de['mmb_log']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트색상&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['mmb_log']['cs_etc_1']?>" size="10"/>	
						<input type="color" class="color-preview" value="<?php echo $de['mmb_log']['cs_etc_1']?>" />
					</td></tr><tr>
					<td class="bo-right bo-left txt-center">
						라인
					</td>
					<td>
						
						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['mmb_log']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_log']['cs_etc_2']?>" />
						&nbsp;&nbsp;

						라인타입&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]" style="width: 84px;">
							<option value="">라인없음</option>
							<option value="solid" <?php echo $de['mmb_log']['cs_etc_3'] == 'solid' ? "selected" : ""?>>실선</option>
							<option value="dotted" <?php echo $de['mmb_log']['cs_etc_3'] == 'dotted' ? "selected" : ""?>>점선</option>
							<option value="dashed" <?php echo $de['mmb_log']['cs_etc_3'] == 'dashed' ? "selected" : ""?>>대쉬선</option>
							<option value="double" <?php echo $de['mmb_log']['cs_etc_3'] == 'double' ? "selected" : ""?>>이중선</option>
						</select>
						&nbsp;&nbsp;

						라인굵기&nbsp;&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['mmb_log']['cs_etc_4']?>" size="5"/> px
						&nbsp;&nbsp;&nbsp;&nbsp;

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_1_mmb_log" value="top" <?php echo strstr($de['mmb_log']['cs_etc_5'], 'top') ? "checked" : ""?> />
						<label for="cs_etc_5_1_mmb_log">상&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_2_mmb_log" value="bottom" <?php echo strstr($de['mmb_log']['cs_etc_5'], 'bottom') ? "checked" : ""?> />
						<label for="cs_etc_5_2_mmb_log">하&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_3_mmb_log" value="left" <?php echo strstr($de['mmb_log']['cs_etc_5'], 'left') ? "checked" : ""?> />
						<label for="cs_etc_5_3_mmb_log">좌&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_4_mmb_log" value="right" <?php echo strstr($de['mmb_log']['cs_etc_5'], 'right') ? "checked" : ""?> />
						<label for="cs_etc_5_4_mmb_log">우</label>
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th rowspan="2" scope="row" class="design-th">
						<em><?php echo $css_index?></em>코멘트
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="mmb_reply" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						배경/폰트
					</td>
					<td>
						배경색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['mmb_reply']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_reply']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트색상&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['mmb_reply']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_reply']['cs_etc_1']?>" />
					</td></tr><tr>
					<td class="bo-right bo-left txt-center">
						라인
					</td>
					<td>
						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['mmb_reply']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_reply']['cs_etc_2']?>" />
						&nbsp;&nbsp;

						라인타입&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]" style="width: 84px;">
							<option value="">라인없음</option>
							<option value="solid" <?php echo $de['mmb_reply']['cs_etc_3'] == 'solid' ? "selected" : ""?>>실선</option>
							<option value="dotted" <?php echo $de['mmb_reply']['cs_etc_3'] == 'dotted' ? "selected" : ""?>>점선</option>
							<option value="dashed" <?php echo $de['mmb_reply']['cs_etc_3'] == 'dashed' ? "selected" : ""?>>대쉬선</option>
							<option value="double" <?php echo $de['mmb_reply']['cs_etc_3'] == 'double' ? "selected" : ""?>>이중선</option>
						</select>
						&nbsp;&nbsp;

						라인굵기&nbsp;&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['mmb_reply']['cs_etc_4']?>" size="5"/> px
						&nbsp;&nbsp;&nbsp;&nbsp;

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_1_mmb_reply" value="top" <?php echo strstr($de['mmb_reply']['cs_etc_5'], 'top') ? "checked" : ""?> />
						<label for="cs_etc_5_1_mmb_reply">상&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_2_mmb_reply" value="bottom" <?php echo strstr($de['mmb_reply']['cs_etc_5'], 'bottom') ? "checked" : ""?> />
						<label for="cs_etc_5_2_mmb_reply">하&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_3_mmb_reply" value="left" <?php echo strstr($de['mmb_reply']['cs_etc_5'], 'left') ? "checked" : ""?> />
						<label for="cs_etc_5_3_mmb_reply">좌&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_4_mmb_reply" value="right" <?php echo strstr($de['mmb_reply']['cs_etc_5'], 'right') ? "checked" : ""?> />
						<label for="cs_etc_5_4_mmb_reply">우</label>
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th rowspan="2" scope="row" class="design-th">
						<em><?php echo $css_index?></em>개별코멘트
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="mmb_reply_item" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						배경/폰트/여백
					</td>
					<td>
						배경색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['mmb_reply_item']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_reply_item']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트색상&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['mmb_reply_item']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_reply_item']['cs_etc_1']?>" />
						&nbsp;&nbsp;
						하단여백&nbsp;&nbsp; <input type="text" name="cs_etc_6[<?php echo $css_index?>]" value="<?php echo $de['mmb_reply_item']['cs_etc_6']?>" size="10"/> px
					</td></tr><tr>
					<td class="bo-right bo-left txt-center">
						라인
					</td>
					<td>
						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['mmb_reply_item']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_reply_item']['cs_etc_2']?>" />
						&nbsp;&nbsp;

						라인타입&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]" style="width: 84px;">
							<option value="">라인없음</option>
							<option value="solid" <?php echo $de['mmb_reply_item']['cs_etc_3'] == 'solid' ? "selected" : ""?>>실선</option>
							<option value="dotted" <?php echo $de['mmb_reply_item']['cs_etc_3'] == 'dotted' ? "selected" : ""?>>점선</option>
							<option value="dashed" <?php echo $de['mmb_reply_item']['cs_etc_3'] == 'dashed' ? "selected" : ""?>>대쉬선</option>
							<option value="double" <?php echo $de['mmb_reply_item']['cs_etc_3'] == 'double' ? "selected" : ""?>>이중선</option>
						</select>
						&nbsp;&nbsp;

						라인굵기&nbsp;&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['mmb_reply_item']['cs_etc_4']?>" size="5"/> px
						&nbsp;&nbsp;&nbsp;&nbsp;

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_1_mmb_reply_item" value="top" <?php echo strstr($de['mmb_reply_item']['cs_etc_5'], 'top') ? "checked" : ""?> />
						<label for="cs_etc_5_1_mmb_reply_item">상&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_2_mmb_reply_item" value="bottom" <?php echo strstr($de['mmb_reply_item']['cs_etc_5'], 'bottom') ? "checked" : ""?> />
						<label for="cs_etc_5_2_mmb_reply_item">하&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_3_mmb_reply_item" value="left" <?php echo strstr($de['mmb_reply_item']['cs_etc_5'], 'left') ? "checked" : ""?> />
						<label for="cs_etc_5_3_mmb_reply_item">좌&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_4_mmb_reply_item" value="right" <?php echo strstr($de['mmb_reply_item']['cs_etc_5'], 'right') ? "checked" : ""?> />
						<label for="cs_etc_5_4_mmb_reply_item">우</label>
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th scope="row" class="design-th">
						<em><?php echo $css_index?></em>「작성자」
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="mmb_name" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						-
					</td>
					<td>
						색상코드&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['mmb_name']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_name']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트크기&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['mmb_name']['cs_etc_1']?>" size="5"/> px
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th scope="row" class="design-th">
						<em><?php echo $css_index?></em>「작성자」<br /><span class="red">원글 작성자</span>
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="mmb_owner_name" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						-
					</td>
					<td>
						색상코드&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['mmb_owner_name']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_owner_name']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트크기&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['mmb_owner_name']['cs_etc_1']?>" size="5"/> px
						&nbsp;&nbsp;
						접두문자&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['mmb_owner_name']['cs_etc_2']?>" size="5"/>
						&nbsp;&nbsp;
						접미문자&nbsp;&nbsp; <input type="text" name="cs_etc_3[<?php echo $css_index?>]" value="<?php echo $de['mmb_owner_name']['cs_etc_3']?>" size="5"/>
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th scope="row" class="design-th">
						<em><?php echo $css_index?></em>「날짜」
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="mmb_datetime" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						-
					</td>
					<td>
						색상코드&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['mmb_datetime']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_datetime']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트크기&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['mmb_datetime']['cs_etc_1']?>" size="5"/> px
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th scope="row" class="design-th">
						<em><?php echo $css_index?></em>「외부링크」
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="mmb_link" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						-
					</td>
					<td>
						색상코드&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['mmb_link']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_link']['cs_value']?>" />
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th scope="row" class="design-th">
						<em><?php echo $css_index?></em>「멤버호출」
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="mmb_call" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						-
					</td>
					<td>
						색상코드&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['mmb_call']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_call']['cs_value']?>" />
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th scope="row" class="design-th">
						<em><?php echo $css_index?></em>「로그링크」
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="mmb_log_ank" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						-
					</td>
					<td>
						색상코드&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['mmb_log_ank']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_log_ank']['cs_value']?>" />
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th scope="row" class="design-th">
						<em><?php echo $css_index?></em>「해시태그」
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="mmb_hash" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						-
					</td>
					<td>
						색상코드&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['mmb_hash']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['mmb_hash']['cs_value']?>" />
					</td>
				</tr <?php  $css_index++; ?>>
			</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_004b">
	<h2 class="h2_frm">로드비/인벤토리 — 액션·아이템 박스</h2>

	<div class="local_desc02 local_desc">
		<p>로드비 액션 박스 (.log-action-box), 인벤토리 아이템 박스 (.item-box), 아이템 팝업 (#inven_popup_viewer) 의 디자인을 설정합니다.</p>
	</div>

	<div class="tbl_frm01 tbl_wrap">
		<table>
			<caption>로드비/인벤토리 박스 디자인</caption>
			<colgroup>
				<col style="width: 140px;">
				<col style="width: 130px;">
				<col>
			</colgroup>
			<tbody>

				<?php /* === log-action-box === */ ?>
				<tr>
					<th rowspan="3" scope="row" class="design-th">
						<em><?php echo $css_index?></em>로드비 액션박스 (.log-action-box)
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="log_action_box" readonly class="full" />
					</th>
					<td rowspan="3" class="bo-right bo-left txt-center">
						<?php if($de['log_action_box']['cs_value']) { ?><img src="<?php echo $de['log_action_box']['cs_value']?>" class="prev_thumb"/><?php } else { ?>이미지 미등록<?php } ?>
					</td>
					<td>
						배경이미지(직접등록)&nbsp; <input type="file" name="cs_value_file[<?php echo $css_index?>]" />
					</td></tr><tr>
					<td>
						배경이미지(외부경로)&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['log_action_box']['cs_value']?>" size="50"/>
					</td></tr><tr>
					<td>
						배경색&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['log_action_box']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['log_action_box']['cs_etc_1']?>" />
						&nbsp;글씨색&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['log_action_box']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['log_action_box']['cs_etc_2']?>" />
						&nbsp;폰트크기&nbsp; <input type="text" name="cs_etc_5[<?php echo $css_index?>]" value="<?php echo $de['log_action_box']['cs_etc_5']?>" size="6" placeholder="14px"/>
						<br>
						테두리(축약)&nbsp; <input type="text" name="cs_etc_3[<?php echo $css_index?>]" value="<?php echo $de['log_action_box']['cs_etc_3']?>" size="20" placeholder="1px solid #ccc"/>
						&nbsp;패딩&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['log_action_box']['cs_etc_4']?>" size="14" placeholder="10px 14px"/>
						<br>
						상단 이미지(URL)&nbsp; <input type="text" name="cs_etc_6[<?php echo $css_index?>]" value="<?php echo $de['log_action_box']['cs_etc_6']?>" size="40" placeholder="https://..."/>
						<br>
						하단 이미지(URL)&nbsp; <input type="text" name="cs_etc_7[<?php echo $css_index?>]" value="<?php echo $de['log_action_box']['cs_etc_7']?>" size="40" placeholder="https://..."/>
					</td>
				</tr <?php  $css_index++; ?>>

				<?php /* === inventory_item_box === */ ?>
				<tr>
					<th rowspan="3" scope="row" class="design-th">
						<em><?php echo $css_index?></em>아이템 리스트 박스 (.item-box)
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="inventory_item_box" readonly class="full" />
					</th>
					<td rowspan="3" class="bo-right bo-left txt-center">
						<?php if($de['inventory_item_box']['cs_value']) { ?><img src="<?php echo $de['inventory_item_box']['cs_value']?>" class="prev_thumb"/><?php } else { ?>이미지 미등록<?php } ?>
					</td>
					<td>
						배경이미지(직접등록)&nbsp; <input type="file" name="cs_value_file[<?php echo $css_index?>]" />
					</td></tr><tr>
					<td>
						배경이미지(외부경로)&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['inventory_item_box']['cs_value']?>" size="50"/>
					</td></tr><tr>
					<td>
						박스 배경색&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['inventory_item_box']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['inventory_item_box']['cs_etc_1']?>" />
						&nbsp;글씨색&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['inventory_item_box']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['inventory_item_box']['cs_etc_2']?>" />
						&nbsp;크기&nbsp; <input type="text" name="cs_etc_3[<?php echo $css_index?>]" value="<?php echo $de['inventory_item_box']['cs_etc_3']?>" size="6" placeholder="50px"/>
						<br>
						패딩&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['inventory_item_box']['cs_etc_4']?>" size="14" placeholder="2px"/>
						&nbsp;테두리&nbsp; <input type="text" name="cs_etc_5[<?php echo $css_index?>]" value="<?php echo $de['inventory_item_box']['cs_etc_5']?>" size="20" placeholder="1px solid #ccc"/>
						<br>
						<small style="color:#666;">개수 표시(i) → </small>
						배경&nbsp; <input type="text" name="cs_etc_6[<?php echo $css_index?>]" value="<?php echo $de['inventory_item_box']['cs_etc_6']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['inventory_item_box']['cs_etc_6']?>" />
						&nbsp;글씨색&nbsp; <input type="text" name="cs_etc_7[<?php echo $css_index?>]" value="<?php echo $de['inventory_item_box']['cs_etc_7']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['inventory_item_box']['cs_etc_7']?>" />
						&nbsp;크기&nbsp; <input type="text" name="cs_etc_8[<?php echo $css_index?>]" value="<?php echo $de['inventory_item_box']['cs_etc_8']?>" size="6" placeholder="10px"/>
					</td>
				</tr <?php  $css_index++; ?>>

				<?php /* === inventory_item_popup === */ ?>
				<tr>
					<th rowspan="3" scope="row" class="design-th">
						<em><?php echo $css_index?></em>아이템 팝업 (#inven_popup_viewer)
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="inventory_item_popup" readonly class="full" />
					</th>
					<td rowspan="3" class="bo-right bo-left txt-center">
						<?php if($de['inventory_item_popup']['cs_value']) { ?><img src="<?php echo $de['inventory_item_popup']['cs_value']?>" class="prev_thumb"/><?php } else { ?>이미지 미등록<?php } ?>
					</td>
					<td>
						배경이미지(직접등록)&nbsp; <input type="file" name="cs_value_file[<?php echo $css_index?>]" />
					</td></tr><tr>
					<td>
						배경이미지(외부경로)&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['inventory_item_popup']['cs_value']?>" size="50"/>
					</td></tr><tr>
					<td>
						배경색&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['inventory_item_popup']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['inventory_item_popup']['cs_etc_1']?>" />
						&nbsp;글씨색&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['inventory_item_popup']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['inventory_item_popup']['cs_etc_2']?>" />
						&nbsp;폰트크기&nbsp; <input type="text" name="cs_etc_3[<?php echo $css_index?>]" value="<?php echo $de['inventory_item_popup']['cs_etc_3']?>" size="6" placeholder="14px"/>
						<br>
						테두리&nbsp; <input type="text" name="cs_etc_5[<?php echo $css_index?>]" value="<?php echo $de['inventory_item_popup']['cs_etc_5']?>" size="20" placeholder="1px solid #ccc"/>
						&nbsp;패딩&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['inventory_item_popup']['cs_etc_4']?>" size="14" placeholder="20px"/>
						<br>
						<small style="color:#666;">※ 내부 .item-box 는 위의 '아이템 리스트 박스' 설정을 공유합니다.</small>
					</td>
				</tr <?php  $css_index++; ?>>

			</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_005">
	<h2 class="h2_frm">기타 디자인 설정</h2>
	<?php echo $pg_anchor ?>

	<div class="local_desc02 local_desc">
		<p>다양한 디자인을 적용하기 위해선 직접 HTML / CSS 파일을 수정해 주시길 바랍니다.</p>
	</div>

	<div class="tbl_frm01 tbl_wrap">
		<table>
			<caption>홈페이지 기본환경 설정</caption>
			<colgroup>
				<col style="width: 140px;">
				<col style="width: 130px;">
				<col>
			</colgroup>
			<tbody>
				<tr>
					<th scope="row" class="design-th">
						<em><?php echo $css_index?></em>기본색
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="color_default" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						-
					</td>
					<td>
						색상코드&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['color_default']['cs_value']?>" size="8"/>
						<input type="color" class="color-preview" value="<?php echo $de['color_default']['cs_value']?>" />
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th scope="row" class="design-th">
						<em><?php echo $css_index?></em>전경색
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="color_bak" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						-
					</td>
					<td>
						색상코드&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['color_bak']['cs_value']?>" size="8"/>
						<input type="color" class="color-preview" value="<?php echo $de['color_bak']['cs_value']?>" />
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th scope="row" class="design-th">
						<em><?php echo $css_index?></em>강조색
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="color_point" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						-
					</td>
					<td>
						색상코드&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['color_point']['cs_value']?>" size="8"/>
						<input type="color" class="color-preview" value="<?php echo $de['color_point']['cs_value']?>" />
					</td>
				</tr <?php  $css_index++; ?>>
				<tr>
					<th scope="row" rowspan="2" class="design-th">
						<em><?php echo $css_index?></em>입력폼
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="input_bak" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						배경/폰트
					</td>
					<td>
						배경색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['input_bak']['cs_value']?>" size="8"/>
						<input type="color" class="color-preview" value="<?php echo $de['input_bak']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트색상&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['input_bak']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['input_bak']['cs_etc_1']?>" />
						&nbsp;&nbsp;
						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['input_bak']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['input_bak']['cs_etc_2']?>" />
					</td></tr><tr>
					<td class="bo-right txt-center">
						모서리 라운드
					</td>
					<td>
						좌측상단 <input type="text" name="cs_etc_3[<?php echo $css_index?>]" value="<?php echo $de['input_bak']['cs_etc_3']?>" size="3"/> px
						&nbsp;&nbsp;
						우측상단 <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['input_bak']['cs_etc_4']?>" size="3"/> px
						&nbsp;&nbsp;
						우측하단 <input type="text" name="cs_etc_5[<?php echo $css_index?>]" value="<?php echo $de['input_bak']['cs_etc_5']?>" size="3"/> px
						&nbsp;&nbsp;
						좌측하단 <input type="text" name="cs_etc_6[<?php echo $css_index?>]" value="<?php echo $de['input_bak']['cs_etc_6']?>" size="3"/> px
					</td>
				</tr <?php  $css_index++; ?>>

				
				<tr>
					<th scope="row" rowspan="2" class="design-th">
						<em><?php echo $css_index?></em>기본박스
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="box_style" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						배경/폰트
					</td>
					<td>
						배경색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['box_style']['cs_value']?>" size="8"/>
						<input type="color" class="color-preview" value="<?php echo $de['box_style']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트색상&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['box_style']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['box_style']['cs_etc_1']?>" />
						&nbsp;&nbsp;
						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['box_style']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['box_style']['cs_etc_2']?>" />
					</td></tr><tr>
					<td class="bo-right txt-center">
						모서리 라운드
					</td>
					<td>
						좌측상단 <input type="text" name="cs_etc_3[<?php echo $css_index?>]" value="<?php echo $de['box_style']['cs_etc_3']?>" size="3"/> px
						&nbsp;&nbsp;
						우측상단 <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['box_style']['cs_etc_4']?>" size="3"/> px
						&nbsp;&nbsp;
						우측하단 <input type="text" name="cs_etc_5[<?php echo $css_index?>]" value="<?php echo $de['box_style']['cs_etc_5']?>" size="3"/> px
						&nbsp;&nbsp;
						좌측하단 <input type="text" name="cs_etc_6[<?php echo $css_index?>]" value="<?php echo $de['box_style']['cs_etc_6']?>" size="3"/> px
					</td>
				</tr <?php  $css_index++; ?>>

				<tr>
					<th scope="row" class="design-th">
						<em><?php echo $css_index?></em>이퀄라이저
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="equalizer" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						-
					</td>
					<td>
						막대색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['equalizer']['cs_value']?>" size="8"/>
						<input type="color" class="color-preview" value="<?php echo $de['equalizer']['cs_value']?>" />
						&nbsp;&nbsp;
						외부광선&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['equalizer']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['equalizer']['cs_etc_1']?>" />
					</td>
				</tr <?php  $css_index++; ?>>
				<tr>
					<th rowspan="2" scope="row" class="design-th">
						<em><?php echo $css_index?></em>서브메뉴
						<input type="text" name="cs_name[<?php echo $css_index?>]" value="sub_menu" readonly class="full" />
					</th>
					<td class="bo-right bo-left txt-center">
						배경/폰트
					</td>
					<td>
						배경색상&nbsp;&nbsp; <input type="text" name="cs_value[<?php echo $css_index?>]" value="<?php echo $de['sub_menu']['cs_value']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['sub_menu']['cs_value']?>" />
						&nbsp;&nbsp;
						폰트색상&nbsp;&nbsp; <input type="text" name="cs_etc_1[<?php echo $css_index?>]" value="<?php echo $de['sub_menu']['cs_etc_1']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['sub_menu']['cs_etc_1']?>" />
						&nbsp;&nbsp;
						오버색상&nbsp;&nbsp; <input type="text" name="cs_etc_6[<?php echo $css_index?>]" value="<?php echo $de['sub_menu']['cs_etc_6']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['sub_menu']['cs_etc_6']?>" />
					</td></tr><tr>
					<td class="bo-right txt-center">
						라인
					</td>
					<td>
						라인색상&nbsp;&nbsp; <input type="text" name="cs_etc_2[<?php echo $css_index?>]" value="<?php echo $de['sub_menu']['cs_etc_2']?>" size="10"/>
						<input type="color" class="color-preview" value="<?php echo $de['sub_menu']['cs_etc_2']?>" />
						&nbsp;&nbsp;

						라인타입&nbsp;&nbsp;
						<select name="cs_etc_3[<?php echo $css_index?>]" style="width: 84px;">
							<option value="">라인없음</option>
							<option value="solid" <?php echo $de['sub_menu']['cs_etc_3'] == 'solid' ? "selected" : ""?>>실선</option>
							<option value="dotted" <?php echo $de['sub_menu']['cs_etc_3'] == 'dotted' ? "selected" : ""?>>점선</option>
							<option value="dashed" <?php echo $de['sub_menu']['cs_etc_3'] == 'dashed' ? "selected" : ""?>>대쉬선</option>
							<option value="double" <?php echo $de['sub_menu']['cs_etc_3'] == 'double' ? "selected" : ""?>>이중선</option>
						</select>
						&nbsp;&nbsp;

						라인굵기&nbsp;&nbsp; <input type="text" name="cs_etc_4[<?php echo $css_index?>]" value="<?php echo $de['sub_menu']['cs_etc_4']?>" size="5"/> px
						&nbsp;&nbsp;&nbsp;&nbsp;

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_1_sub_menu" value="top" <?php echo strstr($de['sub_menu']['cs_etc_5'], 'top') ? "checked" : ""?> />
						<label for="cs_etc_5_1_sub_menu">상&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_2_sub_menu" value="bottom" <?php echo strstr($de['sub_menu']['cs_etc_5'], 'bottom') ? "checked" : ""?> />
						<label for="cs_etc_5_2_sub_menu">하&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_3_sub_menu" value="left" <?php echo strstr($de['sub_menu']['cs_etc_5'], 'left') ? "checked" : ""?> />
						<label for="cs_etc_5_3_sub_menu">좌&nbsp;</label>

						<input type="checkbox" name="cs_etc_5[<?php echo $css_index?>][]" id="cs_etc_5_4_sub_menu" value="right" <?php echo strstr($de['sub_menu']['cs_etc_5'], 'right') ? "checked" : ""?> />
						<label for="cs_etc_5_4_sub_menu">우</label>
					</td>
				</tr <?php  $css_index++; ?>>
			</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>


</form>

</main>

<aside class="df-preview">
    <div class="df-preview-bar" id="df-preview-bar">
        <button type="button" class="df-preview-toggle" id="df-preview-toggle" title="접기/펼치기">▶</button>
        <span class="df-preview-bar-main" style="display:flex;align-items:center;gap:8px;flex:1">
            <span class="live-dot" id="df-livedot" title="실시간 반영"></span>
            <strong>실시간 미리보기</strong>
            <span class="spc" style="flex:1"></span>
            <a href="<?php echo G5_URL; ?>/" target="_blank" style="font-size:11px;color:#369;text-decoration:none;">⤤ 실제 페이지</a>
        </span>
    </div>
    <div class="df-preview-body">
        <div class="df-sample-host" id="df-sample-host">

            <div class="df-sample" data-target="board_table">
                <div class="df-sample-title">■ 게시판 리스트 테이블 (.theme-list)</div>
                <table class="theme-list">
                    <thead>
                        <tr>
                            <th style="width:50px">번호</th>
                            <th>제목</th>
                            <th style="width:80px">글쓴이</th>
                            <th style="width:70px">날짜</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>3</td><td><a href="#" onclick="return false">세 번째 샘플 글</a></td><td>홍길동</td><td>10-15</td></tr>
                        <tr><td>2</td><td><a href="#" onclick="return false">두 번째 샘플 글</a></td><td>이순신</td><td>10-14</td></tr>
                        <tr><td>1</td><td><a href="#" onclick="return false">첫 번째 샘플 글</a></td><td>세종</td><td>10-13</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="df-sample" data-target="btn_default">
                <div class="df-sample-title">■ 버튼 (.ui-btn / .ui-btn.point / .ui-btn.etc)</div>
                <a href="#" class="ui-btn" onclick="return false">기본 버튼</a>
                <a href="#" class="ui-btn point" onclick="return false">강조 버튼</a>
                <a href="#" class="ui-btn etc" onclick="return false">기타 버튼</a>
            </div>

            <div class="df-sample" data-target="box_style">
                <div class="df-sample-title">■ 기본 박스 (.theme-box)</div>
                <div class="theme-box">
                    기본 박스(.theme-box) 샘플입니다. 배경·글자색·테두리·둥글기 설정이 적용됩니다.
                    <div style="margin-top:6px;"><a href="#" onclick="return false">링크 샘플</a></div>
                </div>
            </div>

            <div class="df-sample" data-target="form_header">
                <div class="df-sample-title">■ 입력폼 / 양식 테이블 (.theme-form / form_header[16] / form_body[17] / input_bak)</div>
                <table class="theme-form">
                    <tbody>
                        <tr>
                            <th style="width:90px;">닉네임</th>
                            <td><input type="text" class="form-input" placeholder="닉네임을 입력하세요" style="width:200px;"></td>
                        </tr>
                        <tr>
                            <th>분류</th>
                            <td>
                                <select><option>자유</option><option>일상</option><option>공지</option></select>
                            </td>
                        </tr>
                        <tr>
                            <th>내용</th>
                            <td><textarea rows="3" style="width:90%;" placeholder="샘플 텍스트…"></textarea></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="df-sample" data-target="mmb_contain_bak">
                <div class="df-sample-title">■ 로드 게시판 글 (#load_log_board / mmb)</div>
                <div id="load_log_board">
                    <div class="board-notice">■ 공지사항 샘플 (.board-notice) — 테두리/배경색/폰트색 설정이 적용됩니다.</div>
                    <div id="log_list" class="none-trans">
                        <div class="item">
                            <div class="item-inner">
                                <div class="ui-pic">
                                    <div class="pic-header">
                                        <p class="no">No. 1 <span class="ico-category">자유</span></p>
                                        <span><a href="#" onclick="return false" class="new">로그링크</a><a href="#" onclick="return false" class="fav">관심</a><a href="#" onclick="return false" class="mod">수정</a></span>
                                    </div>
                                    <div class="pic-data">[로그 영역]</div>
                                </div>
                                <div class="ui-comment">
                                    <div class="item-comment-box">
                                        <div class="item-comment">
                                            <div class="co-header">
                                                <p class="owner"><span class="owner-front"></span><strong><a href="#" onclick="return false">캐릭터</a></strong> <span>[<a href="#" onclick="return false">오너</a>]</span><span class="owner-behind"></span></p>
                                            </div>
                                            <div class="co-content">코멘트 샘플. <span class="link_hash_tag">#해시태그</span> <a href="#" onclick="return false" class="other-site-link">외부링크</a> <a href="#" onclick="return false" class="log_link_tag">@로그링크</a></div>
                                            <div class="co-footer"><span class="date">10-15 13:24</span></div>
                                        </div>
                                        <div class="item-comment">
                                            <div class="co-header">
                                                <p><strong><a href="#" onclick="return false">캐릭터</a></strong> <span>[<a href="#" onclick="return false">오너</a>]</span></p>
                                            </div>
                                            <div class="co-content">답변 샘플 <span class="member_call">[[호출]]</span></div>
                                            <div class="co-footer"><span class="date">10-15 13:30</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- 코멘트만 있는 로드비 샘플 (log-action-box 미리보기용 — 그림 없음) -->
                        <div class="item">
                            <div class="item-inner">
                                <div class="ui-comment" style="width:100%;">
                                    <div class="item-comment-box">
                                        <div class="item-comment">
                                            <div class="co-content">
                                                <div class="log-action-box data-item">
                                                    <em>📦</em>
                                                    <p><span><strong>샘플 아이템</strong>을 사용했습니다! (.log-action-box)</span></p>
                                                </div>
                                            </div>
                                            <div class="co-footer"><span class="date">10-15 13:35</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="df-sample" data-target="inventory_item_box">
                <div class="df-sample-title">■ 인벤토리 리스트 (.inventory-list / .item-box)</div>
                <ul class="inventory-list" style="list-style:none;padding:0;margin:0;display:flex;flex-wrap:wrap;gap:6px;">
                    <li class="item-box">
                        <a class="inven-open-popup" href="#" onclick="return false" data-idx="1" data-type="own"><img src="<?php echo G5_ADMIN_URL ?>/../img/no_img.png" onerror="this.style.display='none'"><i class="count">3</i></a>
                    </li>
                    <li class="item-box" >
                        <a class="inven-open-popup" href="#" onclick="return false" data-idx="2" data-type="own"><img src="<?php echo G5_ADMIN_URL ?>/../img/no_img.png" onerror="this.style.display='none'"><i class="count" >1</i></a>
                    </li>
                    <li class="item-box">
                        <a class="inven-open-popup" href="#" onclick="return false" data-idx="3" data-type="own"><img src="<?php echo G5_ADMIN_URL ?>/../img/no_img.png" onerror="this.style.display='none'"></a>
                    </li>
                </ul>
            </div>

            <div class="df-sample" data-target="inventory_item_popup">
                <div class="df-sample-title">■ 아이템 팝업 (#inven_popup_viewer)</div>
                <div id="inven_popup_viewer" class="inven-popup-viewer default-form">
                    <div class="inner-content">
                        <div class="info">
                            <div class="item-box">
                                <img src="" onerror="this.style.display='none'">
                            </div>
                        </div>
                        <div class="text">
                            <p class="title"><strong>샘플 아이템</strong></p>
                            <div class="item-content-box">
                                <div class="default">설명 텍스트</div>
                                <div class="effect">효과 텍스트</div>
                                <div class="memo">메모란</div>
                            </div>
                        </div>
                        <div class="control-box" style="margin-top:8px;">
                            <ul>
                                <li><a href="#" onclick="return false" class="ui-style-btn">사용하기</a></li>
                                <li><a href="#" onclick="return false" class="ui-style-btn">선물하기</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</aside>

</div>

<script>
// === 메인 디자인 폼 → 미리보기 샘플 실시간 반영 ===
// 구조: form의 cs_name[i] / cs_value[i] / cs_etc_1..5[i] 입력을 수집
//        → 주요 cs_name만 명시적 규칙으로 CSS 생성 (#df-sample-host 내부로 스코프)
function dfCollectDesign() {
    var form = document.getElementById('fconfigform');
    if (!form) return {};
    var map = {};
    var nameInputs = form.querySelectorAll('input[name^="cs_name["]');
    nameInputs.forEach(function(inp) {
        var key = inp.value;
        if (!key) return;
        var m = inp.name.match(/\[(\d+)\]/);
        if (!m) return;
        var idx = m[1];
        function getVal(prefix) {
            var el = form.querySelector('[name="' + prefix + '[' + idx + ']"]');
            if (!el) return '';
            if (el.type === 'checkbox') return el.checked ? el.value : '';
            // 파일 업로드 미리보기(data URL)가 있으면 우선 사용
            if (prefix === 'cs_value') {
                var pv = el.getAttribute('data-preview-file-url');
                if (pv) return pv;
            }
            return el.value || '';
        }
        var entry = { v: getVal('cs_value'), e: [] };
        for (var i = 1; i <= 10; i++) {
            var v = getVal('cs_etc_' + i);
            // checkbox 그룹(예: cs_etc_5[i][])
            if (!v) {
                var checks = form.querySelectorAll('[name="cs_etc_' + i + '[' + idx + '][]"]:checked');
                if (checks && checks.length) {
                    var arr = [];
                    checks.forEach(function(c){ arr.push(c.value); });
                    v = arr.join('||');
                }
            }
            entry.e[i] = v;
        }
        map[key] = entry;
    });
    return map;
}

function dfBuildCss(d) {
    var sp = '#df-sample-host';
    function gv(k) { return (d[k] && d[k].v) ? d[k].v : ''; }
    function ge(k, i) { return (d[k] && d[k].e && d[k].e[i]) ? d[k].e[i] : ''; }
    function borderRules(selector, k) {
        // [2]=color, [3]=style, [4]=width(px), [5]=direction list ("top||right||bottom||left")
        var color = ge(k,2), style = ge(k,3) || 'solid', width = ge(k,4), dirs = ge(k,5);
        if (!width || !dirs) return '';
        var arr = String(dirs).split('||').filter(Boolean);
        if (!arr.length) return '';
        var css = selector + '{';
        arr.forEach(function(dir){
            css += 'border-' + dir + '-color:' + color + ';';
            css += 'border-' + dir + '-style:' + style + ';';
            css += 'border-' + dir + '-width:' + width + 'px;';
        });
        css += '}';
        return css;
    }
    function tableRule(selector, k) {
        var bg = gv(k) || ge(k,0), color = ge(k,1);
        var c = '';
        if (bg || color) {
            c += selector + '{';
            if (bg) c += 'background-color:' + bg + ';';
            if (color) c += 'color:' + color + ';';
            c += '}';
        }
        c += borderRules(selector, k);
        return c;
    }
    function btnRule(baseSel, k) {
        // [0]=color, [1]=bg, [2]=border, [3]=hcolor, [4]=hbg, [5]=hborder
        var v0 = gv(k) || ge(k,0);
        var c = baseSel + '{';
        if (v0) c += 'color:' + v0 + ';';
        if (ge(k,1)) c += 'background:' + ge(k,1) + ';';
        if (ge(k,2)) c += 'border-color:' + ge(k,2) + ';';
        c += '}';
        c += baseSel + ':hover{';
        if (ge(k,3)) c += 'color:' + ge(k,3) + ';';
        if (ge(k,4)) c += 'background:' + ge(k,4) + ';';
        if (ge(k,5)) c += 'border-color:' + ge(k,5) + ';';
        c += '}';
        return c;
    }
    function bgImageRule(selector, k) {
        // [0]=image-url, [1]=bg-color, [2]=repeat, [3]=position, [4]=size, [5]=attachment
        var img = gv(k) || ge(k,0);
        var bgc = ge(k,1), rep = ge(k,2), pos = ge(k,3), siz = ge(k,4), att = ge(k,5);
        if (!img && !bgc && !rep && !pos && !siz && !att) return '';
        var c = selector + '{';
        if (img) c += "background-image:url('" + img + "');";
        if (bgc) c += 'background-color:' + bgc + ';';
        if (rep) c += 'background-repeat:' + rep + ';';
        if (pos) c += 'background-position:' + pos + ';';
        if (siz) c += 'background-size:' + siz + ';';
        if (att) c += 'background-attachment:' + att + ';';
        c += '}';
        return c;
    }

    var css = '';

    // 기본 색상 (타이틀/레이블 등이 상속하도록 * 적용)
    if (gv('color_default')) {
        css += sp + ',' + sp + ' .df-sample-title{color:' + gv('color_default') + ';}';
    }
    if (gv('color_point')) {
        css += sp + ' a, ' + sp + ' .txt-point{color:' + gv('color_point') + ';}';
    }

    // 샘플 영역 자체에 홈페이지 전체 배경 적용 (cs_name='background')
    css += bgImageRule(sp, 'background');

    // 입력폼 (input_bak): [0]=bg, [1]=color, [2]=border, [3..6]=radius(px) tl/tr/br/bl
    (function() {
        var bg = gv('input_bak') || ge('input_bak',0);
        var fg = ge('input_bak',1);
        var bd = ge('input_bak',2);
        var r1 = ge('input_bak',3), r2 = ge('input_bak',4), r3 = ge('input_bak',5), r4 = ge('input_bak',6);
        if (!(bg||fg||bd||r1||r2||r3||r4)) return;
        var s = sp + ' .form-input,' + sp + ' input[type="text"],' + sp + ' input[type="password"],' + sp + ' input[type="email"],' + sp + ' input[type="number"],' + sp + ' textarea,' + sp + ' select';
        css += s + '{';
        if (bg) css += 'background-color:' + bg + ';';
        if (fg) css += 'color:' + fg + ';';
        if (bd) css += 'border:1px solid ' + bd + ';';
        if (r1) css += 'border-top-left-radius:' + r1 + 'px;';
        if (r2) css += 'border-top-right-radius:' + r2 + 'px;';
        if (r3) css += 'border-bottom-right-radius:' + r3 + 'px;';
        if (r4) css += 'border-bottom-left-radius:' + r4 + 'px;';
        css += '}';
    })();

    // 테이블 (.theme-list)
    css += tableRule(sp + ' .theme-list', 'board_table');
    css += tableRule(sp + ' .theme-list th', 'list_header');
    css += tableRule(sp + ' .theme-list td', 'list_body');

    // 양식 테이블 (.theme-form)  — form_header[16] = th, form_body[17] = td
    css += tableRule(sp + ' .theme-form', 'board_table');
    css += tableRule(sp + ' .theme-form th', 'form_header');
    css += tableRule(sp + ' .theme-form td', 'form_body');

    // 버튼
    css += btnRule(sp + ' .ui-btn', 'btn_default');
    css += btnRule(sp + ' .ui-btn.point', 'btn_point');
    css += btnRule(sp + ' .ui-btn.etc',   'btn_etc');

    // 기본 박스 (.theme-box)
    // box_style: [0]=bg, [1]=color, [2]=border-color, [3..6]=radius(px) tl/tr/br/bl
    (function() {
        var bg = gv('box_style') || ge('box_style',0);
        var fg = ge('box_style',1);
        var bd = ge('box_style',2);
        var r1 = ge('box_style',3), r2 = ge('box_style',4), r3 = ge('box_style',5), r4 = ge('box_style',6);
        if (!(bg||fg||bd||r1||r2||r3||r4)) return;
        css += sp + ' .theme-box{';
        if (bg) css += 'background-color:' + bg + ';';
        if (fg) css += 'color:' + fg + ';';
        if (bd) css += 'border:1px solid ' + bd + ';';
        if (r1) css += 'border-top-left-radius:' + r1 + 'px;';
        if (r2) css += 'border-top-right-radius:' + r2 + 'px;';
        if (r3) css += 'border-bottom-right-radius:' + r3 + 'px;';
        if (r4) css += 'border-bottom-left-radius:' + r4 + 'px;';
        css += '}';
        if (fg) css += sp + ' .theme-box.no-link a{color:' + fg + ';}';
    })();

    // 로드 게시판 (mmb skin) - 실제 CSS 셀렉터와 일치
    // mmb_*_name / mmb_datetime / mmb_link/hash/call/log_ank 는 cs_value(.v) 에 색상을 저장하므로 gv() 우선
    css += bgImageRule(sp + ' #load_log_board', 'mmb_contain_bak');

    // 공지사항 (.board-notice)  — [22] mmb_notice: cs_value=bg, etc_1=color, borderRules(etc_2..5)
    (function() {
        var bg = gv('mmb_notice'), fg = ge('mmb_notice',1);
        if (bg || fg) {
            css += sp + ' .board-notice{';
            if (bg) css += 'background-color:' + bg + ';';
            if (fg) css += 'color:' + fg + ';';
            css += '}';
        }
        css += borderRules(sp + ' .board-notice', 'mmb_notice');
    })();

    // 리스트영역 (#log_list) — [24] mmb_list
    (function() {
        var bg = gv('mmb_list'), fg = ge('mmb_list',1);
        if (bg || fg) {
            css += sp + ' #log_list{';
            if (bg) css += 'background-color:' + bg + ';';
            if (fg) css += 'color:' + fg + ';';
            css += '}';
        }
        css += borderRules(sp + ' #log_list', 'mmb_list');
    })();

    css += tableRule(sp + ' #log_list .item', 'mmb_list_item');
    css += tableRule(sp + ' #log_list .item .item-inner .ui-pic', 'mmb_log');
    css += tableRule(sp + ' #log_list .item .item-inner .item-comment', 'mmb_reply_item');
    css += tableRule(sp + ' #log_list .item .item-inner .ui-comment', 'mmb_reply');
    var c;
    if ((c = gv('mmb_name') || ge('mmb_name',0))) {
        css += sp + ' #log_list .item .item-inner .co-header p,' + sp + ' #log_list .item .item-inner .co-header p a{color:' + c + ';}';
    }
    if ((c = gv('mmb_owner_name') || ge('mmb_owner_name',0))) {
        css += sp + ' #log_list .item .item-inner .co-header p.owner,' + sp + ' #log_list .item .item-inner .co-header p.owner a{color:' + c + ';}';
    }
    if ((c = gv('mmb_datetime') || ge('mmb_datetime',0))) {
        css += sp + ' #log_list .item .item-inner .co-footer .date{color:' + c + ';}';
    }
    if ((c = gv('mmb_link') || ge('mmb_link',0))) {
        css += sp + ' #log_list .item .item-inner .co-content .other-site-link{color:' + c + ';}';
    }
    if ((c = gv('mmb_hash') || ge('mmb_hash',0))) {
        css += sp + ' #log_list .item .item-inner .co-content .link_hash_tag{color:' + c + ';}';
    }
    if ((c = gv('mmb_call') || ge('mmb_call',0))) {
        css += sp + ' #log_list .item .item-inner .co-content .member_call{color:' + c + ';}';
    }
    if ((c = gv('mmb_log_ank') || ge('mmb_log_ank',0))) {
        css += sp + ' #log_list .item .item-inner .co-content .log_link_tag{color:' + c + ';}';
    }

    // 원글 작성자 접두/접미 문자 (mmb_owner_name etc_2 / etc_3) - 샘플의 .owner-front/.owner-behind 에 content 주입
    var of = ge('mmb_owner_name', 2);
    var ob = ge('mmb_owner_name', 3);
    if (of) css += sp + ' #log_list .co-header p.owner .owner-front:before{content:' + JSON.stringify(of) + ';margin-right:2px;}';
    if (ob) css += sp + ' #log_list .co-header p.owner .owner-behind:after{content:' + JSON.stringify(ob) + ';margin-left:2px;}';

    // === log-action-box (로드비 액션박스) ===
    // cs_value=bg-image, etc_1=bg-color, etc_2=color, etc_3=border shorthand,
    // etc_4=padding, etc_5=font-size, etc_6=top deco img, etc_7=bottom deco img
    (function() {
        var img = gv('log_action_box');
        var bg  = ge('log_action_box',1), fg  = ge('log_action_box',2);
        var bd  = ge('log_action_box',3), pad = ge('log_action_box',4);
        var fs  = ge('log_action_box',5);
        var topImg = ge('log_action_box',6), botImg = ge('log_action_box',7);
        if (img||bg||fg||bd||pad||fs) {
            css += sp + ' .log-action-box{';
            if (img) css += "background-image:url('"+img+"');background-repeat:no-repeat;background-size:cover;";
            if (bg)  css += 'background-color:'+bg+';';
            if (fg)  css += 'color:'+fg+';';
            if (bd)  css += 'border:'+bd+';';
            if (pad) css += 'padding:'+pad+';';
            if (fs)  css += 'font-size:'+fs+';';
            css += '}';
        }
        if (topImg) css += sp + ' .log-action-box:before{content:"";display:block;background-image:url('+JSON.stringify(topImg)+');background-repeat:no-repeat;background-size:contain;height:14px;margin-bottom:4px;}';
        if (botImg) css += sp + ' .log-action-box:after{content:"";display:block;background-image:url('+JSON.stringify(botImg)+');background-repeat:no-repeat;background-size:contain;height:14px;margin-top:4px;}';
    })();

    // === inventory_item_box (.item-box) ===
    // cs_value=bg-image, etc_1=bg-color, etc_2=color, etc_3=size(width/height),
    // etc_4=padding, etc_5=border, etc_6=i.bg, etc_7=i.color, etc_8=i.font-size
    (function() {
        var img = gv('inventory_item_box');
        var bg  = ge('inventory_item_box',1), fg  = ge('inventory_item_box',2);
        var sz  = ge('inventory_item_box',3), pad = ge('inventory_item_box',4);
        var bd  = ge('inventory_item_box',5);
        var ibg = ge('inventory_item_box',6), ifg = ge('inventory_item_box',7), ifs = ge('inventory_item_box',8);
        if (img||bg||fg||sz||pad||bd) {
            css += sp + ' .item-box{';
            if (img) css += "background-image:url('"+img+"');background-repeat:no-repeat;background-size:cover;";
            if (bg)  css += 'background-color:'+bg+';';
            if (fg)  css += 'color:'+fg+';';
            if (sz)  css += 'width:'+sz+';height:'+sz+';';
            if (pad) css += 'padding:'+pad+';';
            if (bd)  css += 'border:'+bd+';';
            css += '}';
        }
        // a.inven-open-popup 은 항상 100%/100% flex center
        css += sp + ' .item-box a.inven-open-popup{width:100%;height:100%;display:flex;justify-content:center;align-items:center;position:relative;}';
        if (ibg||ifg||ifs) {
            css += sp + ' .item-box i{';
            if (ibg) css += 'background:'+ibg+';';
            if (ifg) css += 'color:'+ifg+';';
            if (ifs) css += 'font-size:'+ifs+';';
            css += '}';
        }
    })();

    // === inventory_item_popup (#inven_popup_viewer) ===
    (function() {
        var img = gv('inventory_item_popup');
        var bg  = ge('inventory_item_popup',1), fg  = ge('inventory_item_popup',2);
        var fs  = ge('inventory_item_popup',3), pad = ge('inventory_item_popup',4);
        var bd  = ge('inventory_item_popup',5);
        if (img||bg||fg||fs||pad||bd) {
            css += sp + ' #inven_popup_viewer{';
            if (img) css += "background-image:url('"+img+"');background-repeat:no-repeat;background-size:cover;";
            if (bg)  css += 'background-color:'+bg+';';
            if (fg)  css += 'color:'+fg+';';
            if (fs)  css += 'font-size:'+fs+';';
            if (pad) css += 'padding:'+pad+';';
            if (bd)  css += 'border:'+bd+';';
            css += '}';
        }
    })();

    return css;
}

function dfApplyLivePreview() {
    var st = document.getElementById('df-live-style');
    if (!st) {
        st = document.createElement('style');
        st.id = 'df-live-style';
        document.head.appendChild(st);
    }
    st.textContent = dfBuildCss(dfCollectDesign());
}

var _dfLiveTimer = null;
function dfScheduleLive() {
    clearTimeout(_dfLiveTimer);
    _dfLiveTimer = setTimeout(dfApplyLivePreview, 100);
}

document.addEventListener('DOMContentLoaded', function(){
    var form = document.getElementById('fconfigform');
    if (form) {
        form.addEventListener('input', dfScheduleLive);
        form.addEventListener('change', dfScheduleLive);

        // 파일 업로드 직접등록 → data URL로 미리보기 반영
        form.querySelectorAll('input[type="file"][name^="cs_value_file["]').forEach(function(fi){
            var m = fi.name.match(/^cs_value_file\[(\d+)\]$/);
            if (!m) return;
            var idx = m[1];
            fi.addEventListener('change', function(){
                var f = fi.files && fi.files[0];
                if (!f) return;
                var r = new FileReader();
                r.onload = function(e){
                    var t = form.querySelector('[name="cs_value['+idx+']"]');
                    if (t) {
                        t.setAttribute('data-preview-file-url', e.target.result);
                        dfScheduleLive();
                    }
                };
                r.readAsDataURL(f);
            });
        });
    }
    dfApplyLivePreview();

    // 미리보기 항목 클릭 → 해당 설정 섹션으로 스크롤
    (function(){
        var formEl = document.getElementById('fconfigform');
        if (!formEl) return;
        // cs_name -> 섹션 컨테이너(보통 <table>) 매핑 구축
        var anchors = {};
        formEl.querySelectorAll('input[name^="cs_name["]').forEach(function(inp){
            var key = inp.value;
            if (!key || anchors[key]) return;
            // 가장 가까운 table을 섹션 단위로 사용
            var tbl = inp.closest('table');
            anchors[key] = tbl || inp.closest('th') || inp.parentElement;
        });
        document.querySelectorAll('.df-sample[data-target]').forEach(function(s){
            var t = s.getAttribute('data-target');
            s.style.cursor = 'pointer';
            s.title = '클릭: [' + t + '] 설정으로 이동';
            s.addEventListener('click', function(ev){
                // 폼 컨트롤 클릭은 제외
                var tag = (ev.target.tagName || '').toLowerCase();
                if (tag === 'input' || tag === 'select' || tag === 'textarea' || tag === 'option' || tag === 'a') return;
                var el = anchors[t];
                if (!el) return;
                el.scrollIntoView({behavior:'smooth', block:'center'});
                // 일시 강조
                el.style.transition = 'box-shadow 0.3s';
                el.style.boxShadow = '0 0 0 3px #ffd84d';
                setTimeout(function(){ el.style.boxShadow = ''; }, 1400);
            });
        });
    })();

    // 미리보기 접기/펼치기
    var split = document.querySelector('.df-split');
    var btn = document.getElementById('df-preview-toggle');
    var bar = document.getElementById('df-preview-bar');
    function setCollapsed(c) {
        split.classList.toggle('preview-collapsed', c);
        btn.textContent = c ? '◀' : '▶';
        try { sessionStorage.setItem('df_preview_collapsed', c ? '1' : '0'); } catch(e){}
    }
    if (btn && split) {
        var initC = false;
        try { initC = sessionStorage.getItem('df_preview_collapsed') === '1'; } catch(e){}
        setCollapsed(initC);
        btn.addEventListener('click', function(e){ e.stopPropagation(); setCollapsed(!split.classList.contains('preview-collapsed')); });
        bar.addEventListener('click', function(e){
            if (split.classList.contains('preview-collapsed')) { setCollapsed(false); }
        });
    }
});
</script>

<script>
function fconfigform_submit(f)
{
	f.action = "./design_form_update.php";
	return true;
}
</script>

<?php
include_once ('./admin.tail.php');
?>
