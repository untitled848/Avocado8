<?php
$sub_menu = "110300";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');
if (!$is_admin) alert('관리자만 접근 가능합니다.');

// ─── POST 저장 처리 ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    auth_check($auth[$sub_menu], 'w');
    check_admin_token();

    $pd_id = (int)($_POST['pd_id'] ?? 1);
    if ($pd_id <= 0) $pd_id = 1;

    $allowed_img = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $cs_names    = is_array($_POST['cs_name'] ?? null)   ? $_POST['cs_name']   : array();
    $cs_values   = is_array($_POST['cs_value'] ?? null)  ? $_POST['cs_value']  : array();

    foreach ($cs_names as $n => $cs_name) {
        $cs_name = trim($cs_name);
        if (empty($cs_name)) continue;

        $cs_value = $cs_values[$n] ?? '';

        // 이미지 파일 업로드
        if (!empty($_FILES['cs_value_file']['name'][$n]) && !$_FILES['cs_value_file']['error'][$n]) {
            $ext = strtolower(pathinfo($_FILES['cs_value_file']['name'][$n], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_img)) {
                @mkdir(G5_DATA_PATH.'/world/design', G5_DIR_PERMISSION);
                @chmod(G5_DATA_PATH.'/world/design', G5_DIR_PERMISSION);
                $fname = 'w_' . preg_replace('/[^a-z0-9]/', '_', $cs_name) . '_' . $pd_id . '.' . $ext;
                $dest  = G5_DATA_PATH . '/world/design/' . $fname;
                if (@move_uploaded_file($_FILES['cs_value_file']['tmp_name'][$n], $dest)) {
                    @chmod($dest, G5_FILE_PERMISSION);
                    $cs_value = G5_DATA_URL . '/world/design/' . $fname;
                    if (function_exists('cdn_after_upload')) cdn_after_upload($dest);
                    if (function_exists('cdn_tokenize_url')) $cs_value = cdn_tokenize_url($cs_value);
                }
            }
        }

        $etc_sets = '';
        for ($e = 1; $e <= 10; $e++) {
            $v = sql_escape_string($_POST['cs_etc_'.$e][$n] ?? '');
            $etc_sets .= ", cs_etc_{$e} = '{$v}'";
        }

        $cs_name_e  = sql_escape_string($cs_name);
        $cs_value_e = sql_escape_string($cs_value);

        $exists = sql_fetch("SELECT cs_id FROM `{$g5['world_config_table']}` WHERE cs_name = '{$cs_name_e}' AND pd_id = '{$pd_id}'");
        if ($exists['cs_id']) {
            sql_query("UPDATE `{$g5['world_config_table']}` SET cs_value = '{$cs_value_e}' {$etc_sets} WHERE cs_id = '{$exists['cs_id']}'");
        } else {
            sql_query("INSERT INTO `{$g5['world_config_table']}` SET cs_name = '{$cs_name_e}', pd_id = '{$pd_id}', cs_value = '{$cs_value_e}' {$etc_sets}");
        }
    }

    goto_url("./world_design_form.php?pd_id={$pd_id}");
}

// ─── 프리셋 목록 + 현재 설정값 로드 ────────────────────────────────────
// GET 으로 ?pd_id 가 명시되면 그 값, 아니면 현재 적용 중인 세계관 디자인 프리셋을 기본값으로
if (isset($_GET['pd_id'])) {
    $pd_id = (int)$_GET['pd_id'];
} else {
    $pd_id = function_exists('get_active_design') ? (int)get_active_design('world') : 0;
}
if ($pd_id <= 0) $pd_id = 1;

$pd_list = array();
$r = sql_query("SELECT * FROM `{$g5['world_preset_table']}` ORDER BY pd_order, pd_id");
while ($row = sql_fetch_array($r)) { $pd_list[$row['pd_id']] = $row; }
if (!isset($pd_list[$pd_id])) $pd_id = 1;

$de = array();
$r  = sql_query("SELECT * FROM `{$g5['world_config_table']}` WHERE pd_id = '{$pd_id}'");
while ($row = sql_fetch_array($r)) { $de[$row['cs_name']] = $row; }

$g5['title'] = '페이지 디자인 설정';
include_once(G5_ADMIN_PATH.'/admin.head.php');
$design_hub = 'page';
$design_hub_tab = 'world';
include_once(G5_ADMIN_PATH.'/_design_hub_tabs.inc.php');

$di = 0; // design field index
?>
<div class="wd-bar">
    <strong>편집:</strong> #<?php echo (int)$pd_id; ?> <?php echo htmlspecialchars2($pd_list[$pd_id]['pd_name'] ?? ''); ?>
    <span style="color:#888;">|</span>
    <select id="pd_switch">
        <?php foreach ($pd_list as $pid => $pr): ?>
        <option value="<?php echo (int)$pid; ?>"<?php echo ($pid == $pd_id) ? ' selected' : ''; ?>>#<?php echo (int)$pid; ?> <?php echo htmlspecialchars2($pr['pd_name']); ?></option>
        <?php endforeach; ?>
    </select>
    <button type="button" onclick="location.href='./world_design_form.php?pd_id='+document.getElementById('pd_switch').value;" class="btn_admin">전환</button>
    <button type="button" onclick="wdRename();" class="btn_admin">이름 변경</button>
    <a href="./world_preset_list.php" class="btn_admin">세계관 프리셋 목록</a>
    <?php
    $wd_active_pd_id = function_exists('get_active_design') ? (int)get_active_design('world') : 1;
    $wd_active_preset = function_exists('get_preset') ? get_preset() : array('pr_name' => '');
    if ((int)$pd_id === $wd_active_pd_id) {
        echo '<span style="color:#369;font-weight:bold;margin-left:8px;">★ 현재 사용중인 '.htmlspecialchars2($wd_active_preset['pr_name'] ?: 'default').' 의 디자인입니다.</span>';
    }
    ?>
    <span class="spc" style="flex:1"></span>
    <button type="button" onclick="document.getElementById('fworlddesign').submit();" class="btn_admin" style="background:#369;color:#fff;border:0;padding:6px 16px;font-weight:600;">💾 저장</button>
    <div class="wd-bar-json">
        <strong>JSON:</strong>
        <button type="button" onclick="wdExportDesign();" class="btn_admin">JSON 내보내기</button>
        <button type="button" onclick="wdImportDesign();" class="btn_admin">JSON 가져오기 (현재에 적용)</button>
        <button type="button" onclick="wdImportDesignNew();" class="btn_admin">JSON으로 새 디자인</button>
        <span class="linked">저장되지 않은 변경은 포함되지 않습니다. 배경 등 이미지는 경로만 저장되며, 다른 사이트에서 가져오면 파일을 다시 올려야 할 수 있습니다.</span>
    </div>
</div>
<form id="wd_json_import_form" method="post" action="./world_preset_action.php" enctype="multipart/form-data" style="display:none">
    <input type="hidden" name="action" id="wd_json_action" value="import">
    <input type="hidden" name="pd_id" value="<?php echo (int)$pd_id; ?>">
    <input type="hidden" name="pd_name" id="wd_json_pd_name" value="">
    <input type="file" name="json_file" id="wd_json_file" accept="application/json,.json">
</form>
<script>
function wdRename(){
    var nm = prompt('새 이름?', '<?php echo htmlspecialchars2($pd_list[$pd_id]['pd_name'] ?? '', ENT_QUOTES); ?>');
    if (!nm) return;
    location.href = './world_preset_action.php?action=rename&pd_id=<?php echo (int)$pd_id; ?>&pd_name=' + encodeURIComponent(nm);
}
function wdExportDesign() {
    location.href = './world_preset_action.php?action=export&pd_id=<?php echo (int)$pd_id; ?>';
}
function wdImportDesign() {
    if (!confirm('현재 편집 중인 세계관 디자인 설정을 JSON 파일 내용으로 덮어씁니다.\n(이미지 파일 자체는 포함되지 않으며, 경로만 가져옵니다.)\n계속할까요?')) return;
    document.getElementById('wd_json_action').value = 'import';
    document.getElementById('wd_json_pd_name').value = '';
    document.getElementById('wd_json_file').value = '';
    document.getElementById('wd_json_file').click();
}
function wdImportDesignNew() {
    var nm = prompt('새 세계관 디자인 프리셋 이름을 입력하세요', '<?php echo htmlspecialchars2($pd_list[$pd_id]['pd_name'] ?? '', ENT_QUOTES); ?>_import');
    if (!nm) return;
    document.getElementById('wd_json_action').value = 'import_new';
    document.getElementById('wd_json_pd_name').value = nm;
    document.getElementById('wd_json_file').value = '';
    document.getElementById('wd_json_file').click();
}
(function(){
    var fi = document.getElementById('wd_json_file');
    if (!fi) return;
    fi.addEventListener('change', function(){
        if (this.files && this.files[0]) {
            document.getElementById('wd_json_import_form').submit();
        }
    });
})();
</script>

<div class="wd-split">
<main class="wd-form-col">

<form name="fworlddesign" id="fworlddesign" method="post" action="./world_design_form.php" onsubmit="return true;" enctype="multipart/form-data">
<input type="hidden" name="token" value="">
<input type="hidden" name="pd_id" value="<?php echo (int)$pd_id; ?>">

<?php /* ===================== 섹션 1: 메뉴 위치 ===================== */ ?>
<h2 class="h2_frm">메뉴 위치</h2>
<div class="tbl_frm01 tbl_wrap">
<table>
<colgroup><col style="width:160px"><col></colgroup>
<tbody>

<tr>
    <th scope="row">메뉴 상하/좌우
        <input type="hidden" name="cs_name[<?php echo $di; ?>]" value="menu_pos_v">
    </th>
    <td>
        <select name="cs_value[<?php echo $di; ?>]">
            <?php foreach (array('top'=>'상(위)', 'bottom'=>'하(아래)', 'left'=>'좌(왼쪽)', 'right'=>'우(오른쪽)') as $v => $l): ?>
            <option value="<?php echo $v; ?>"<?php echo ($de['menu_pos_v']['cs_value'] ?? '') === $v ? ' selected' : ''; ?>><?php echo $l; ?></option>
            <?php endforeach; ?>
        </select>
    </td>
</tr><?php $di++; ?>

<tr>
    <th scope="row">content 내부/외부
        <input type="hidden" name="cs_name[<?php echo $di; ?>]" value="menu_pos_in">
    </th>
    <td>
        <select name="cs_value[<?php echo $di; ?>]">
            <option value="outside"<?php echo ($de['menu_pos_in']['cs_value'] ?? 'outside') === 'outside' ? ' selected' : ''; ?>>content 영역 외부</option>
            <option value="inside"<?php echo ($de['menu_pos_in']['cs_value'] ?? '') === 'inside' ? ' selected' : ''; ?>>content 영역 내부</option>
        </select>
    </td>
</tr><?php $di++; ?>

<tr>
    <th scope="row">서브메뉴 표시 (좌/우 전용)
        <input type="hidden" name="cs_name[<?php echo $di; ?>]" value="menu_sub_expand">
    </th>
    <td>
        <select name="cs_value[<?php echo $di; ?>]">
            <option value="0"<?php echo ($de['menu_sub_expand']['cs_value'] ?? '0') === '0' ? ' selected' : ''; ?>>접힘 (현재 메인의 서브만 표시)</option>
            <option value="1"<?php echo ($de['menu_sub_expand']['cs_value'] ?? '') === '1' ? ' selected' : ''; ?>>펼침 (모든 서브 항상 표시)</option>
        </select>
    </td>
</tr><?php $di++; ?>

</tbody>
</table>
</div>

<?php /* ===================== 섹션 2: 메뉴 영역 ===================== */ ?>
<h2 class="h2_frm">메뉴 영역 스타일</h2>
<div class="tbl_frm01 tbl_wrap">
<table>
<colgroup><col style="width:160px"><col style="width:120px"><col></colgroup>
<tbody>

<tr>
    <th scope="row">메뉴 너비 (좌/우)
        <input type="hidden" name="cs_name[<?php echo $di; ?>]" value="menu_area_width">
    </th>
    <td colspan="2">
        <input type="text" name="cs_value[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($de['menu_area_width']['cs_value'] ?? '100%'); ?>" class="frm_input" size="10" placeholder="100%">
        &nbsp;높이: <input type="text" name="cs_etc_1[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($de['menu_area_width']['cs_etc_1'] ?? ''); ?>" class="frm_input" size="10" placeholder="auto">
        <?php echo help('기본값 100%. 상/하 위치에선 전체 너비, 좌/우에서는 사이드바 너비'); ?>
    </td>
</tr><?php $di++; ?>

<tr>
    <th rowspan="3" scope="row">메뉴 영역 배경
        <input type="hidden" name="cs_name[<?php echo $di; ?>]" value="menu_area_bg">
    </th>
    <td class="bo-right bo-left txt-center">
        <?php if (!empty($de['menu_area_bg']['cs_value'])): ?><img src="<?php echo $de['menu_area_bg']['cs_value']; ?>" class="prev_thumb"><?php else: ?>이미지 미등록<?php endif; ?>
    </td>
    <td>직접등록&nbsp; <input type="file" name="cs_value_file[<?php echo $di; ?>]" accept="image/*"></td>
</tr>
<tr>
    <td class="bo-right bo-left" colspan="2">외부경로&nbsp; <input type="text" name="cs_value[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($de['menu_area_bg']['cs_value'] ?? ''); ?>" size="50"></td>
</tr>
<tr>
    <td colspan="2">
        배경색&nbsp; <input type="text" name="cs_etc_1[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($de['menu_area_bg']['cs_etc_1'] ?? ''); ?>" size="10">
        <input type="color" value="<?php echo htmlspecialchars2($de['menu_area_bg']['cs_etc_1'] ?? '#ffffff'); ?>">
        &nbsp;반복&nbsp;
        <?php $v2 = $de['menu_area_bg']['cs_etc_2'] ?? ''; ?>
        <select name="cs_etc_2[<?php echo $di; ?>]">
            <option value="">반복</option>
            <option value="no-repeat"<?php echo $v2 === 'no-repeat' ? ' selected' : ''; ?>>반복없음</option>
            <option value="repeat-x"<?php echo $v2 === 'repeat-x' ? ' selected' : ''; ?>>가로반복</option>
            <option value="repeat-y"<?php echo $v2 === 'repeat-y' ? ' selected' : ''; ?>>세로반복</option>
        </select>
        &nbsp;크기&nbsp;
        <?php $v4 = $de['menu_area_bg']['cs_etc_3'] ?? ''; ?>
        <select name="cs_etc_3[<?php echo $di; ?>]">
            <option value="">원본</option>
            <option value="cover"<?php echo $v4 === 'cover' ? ' selected' : ''; ?>>꽉참</option>
            <option value="contain"<?php echo $v4 === 'contain' ? ' selected' : ''; ?>>맞춤</option>
        </select>
    </td>
</tr><?php $di++; ?>

<tr>
    <th scope="row">메뉴 영역 테두리
        <input type="hidden" name="cs_name[<?php echo $di; ?>]" value="menu_area_border">
    </th>
    <td colspan="2">
        두께: <input type="text" name="cs_value[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($de['menu_area_border']['cs_value'] ?? ''); ?>" class="frm_input" size="6" placeholder="1px">
        &nbsp;스타일:
        <?php $bs = $de['menu_area_border']['cs_etc_1'] ?? 'solid'; ?>
        <select name="cs_etc_1[<?php echo $di; ?>]">
            <?php foreach (array('solid','dashed','dotted','none') as $bsv): ?><option value="<?php echo $bsv; ?>"<?php echo $bs === $bsv ? ' selected' : ''; ?>><?php echo $bsv; ?></option><?php endforeach; ?>
        </select>
        &nbsp;색상: <input type="text" name="cs_etc_2[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($de['menu_area_border']['cs_etc_2'] ?? ''); ?>" size="10">
        <input type="color" value="<?php echo htmlspecialchars2($de['menu_area_border']['cs_etc_2'] ?? '#cccccc'); ?>">
    </td>
</tr><?php $di++; ?>

<tr>
    <th scope="row">메뉴 영역 정렬/패딩/마진
        <input type="hidden" name="cs_name[<?php echo $di; ?>]" value="menu_area_layout">
    </th>
    <td colspan="2">
        <?php
        $mal = $de['menu_area_layout'] ?? array();
        $mal_align = $mal['cs_value'] ?? '';
        $mal_main_align = $mal['cs_etc_1'] ?? '';
        $mal_sub_align  = $mal['cs_etc_2'] ?? '';
        $mal_padding    = $mal['cs_etc_3'] ?? '';
        $mal_margin     = $mal['cs_etc_4'] ?? '';
        $mal_main_gap   = $mal['cs_etc_5'] ?? '';
        $mal_sub_gap    = $mal['cs_etc_6'] ?? '';
        ?>
        전체(.world-menu) 정렬:
        <select name="cs_value[<?php echo $di; ?>]">
            <option value=""<?php echo $mal_align === '' ? ' selected' : ''; ?>>기본</option>
            <option value="left"<?php echo $mal_align === 'left' ? ' selected' : ''; ?>>왼쪽</option>
            <option value="center"<?php echo $mal_align === 'center' ? ' selected' : ''; ?>>가운데</option>
            <option value="right"<?php echo $mal_align === 'right' ? ' selected' : ''; ?>>오른쪽</option>
        </select>
        &nbsp;메인행 정렬:
        <select name="cs_etc_1[<?php echo $di; ?>]">
            <option value=""<?php echo $mal_main_align === '' ? ' selected' : ''; ?>>기본</option>
            <option value="left"<?php echo $mal_main_align === 'left' ? ' selected' : ''; ?>>왼쪽</option>
            <option value="center"<?php echo $mal_main_align === 'center' ? ' selected' : ''; ?>>가운데</option>
            <option value="right"<?php echo $mal_main_align === 'right' ? ' selected' : ''; ?>>오른쪽</option>
        </select>
        &nbsp;서브행 정렬:
        <select name="cs_etc_2[<?php echo $di; ?>]">
            <option value=""<?php echo $mal_sub_align === '' ? ' selected' : ''; ?>>기본</option>
            <option value="left"<?php echo $mal_sub_align === 'left' ? ' selected' : ''; ?>>왼쪽</option>
            <option value="center"<?php echo $mal_sub_align === 'center' ? ' selected' : ''; ?>>가운데</option>
            <option value="right"<?php echo $mal_sub_align === 'right' ? ' selected' : ''; ?>>오른쪽</option>
        </select>
        <br>
        메뉴 영역 패딩: <input type="text" name="cs_etc_3[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($mal_padding); ?>" size="14" placeholder="6px 10px" class="frm_input">
        &nbsp;마진: <input type="text" name="cs_etc_4[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($mal_margin); ?>" size="14" placeholder="0" class="frm_input">
        <br>
        메인 메뉴 컨테이너 gap: <input type="text" name="cs_etc_5[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($mal_main_gap); ?>" size="8" placeholder="8px" class="frm_input">
        &nbsp;서브 메뉴 컨테이너 gap: <input type="text" name="cs_etc_6[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($mal_sub_gap); ?>" size="8" placeholder="4px" class="frm_input">
        <?php echo help('각 메뉴 행/그룹 안 항목 간 간격 (CSS gap). 비우면 0.'); ?>
    </td>
</tr><?php $di++; ?>

</tbody>
</table>
</div>

<?php /* ===================== 섹션 3: 메뉴 아이템 스타일 (메인/서브 × default/hover) ===================== */ ?>
<?php
// 단일 cs_name 에 etc 슬롯으로 저장: cs_value=이미지, etc_1=글씨색, etc_2=배경색, etc_3=글씨크기, etc_4=테두리두께, etc_5=테두리스타일, etc_6=테두리색
$menu_style_fields = array(
    array('menu_main_default', '메인 메뉴 기본'),
    array('menu_main_hover',   '메인 메뉴 Hover/Active'),
    array('menu_sub_default',  '서브 메뉴 기본'),
    array('menu_sub_hover',    '서브 메뉴 Hover/Active'),
);
foreach ($menu_style_fields as $msf):
    $mcs_name = $msf[0];
    $mlabel   = $msf[1];
    $d = $de[$mcs_name] ?? array();
?>
<h2 class="h2_frm"><?php echo $mlabel; ?></h2>
<div class="tbl_frm01 tbl_wrap">
<table>
<colgroup><col style="width:160px"><col style="width:120px"><col></colgroup>
<tbody>
<input type="hidden" name="cs_name[<?php echo $di; ?>]" value="<?php echo $mcs_name; ?>">
<tr>
    <th scope="row">배경이미지</th>
    <td colspan="2">
        <input type="text" name="cs_value[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($d['cs_value'] ?? ''); ?>" size="60" class="frm_input" placeholder="https://example.com/menu-bg.png">
        <p style="margin:4px 0 0;color:#888;font-size:12px;">메뉴 항목의 배경 이미지 URL. 메뉴 텍스트와 함께 출력됩니다(텍스트는 배경 위에 표시).</p>
    </td>
</tr>
<tr>
    <th scope="row">글씨 색상</th>
    <td colspan="2">
        <input type="text" name="cs_etc_1[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($d['cs_etc_1'] ?? ''); ?>" size="10" class="frm_input" placeholder="#333">
        <input type="color" value="<?php echo htmlspecialchars2($d['cs_etc_1'] ?? '#333333'); ?>">
    </td>
</tr>
<tr>
    <th scope="row">배경색</th>
    <td colspan="2">
        <input type="text" name="cs_etc_2[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($d['cs_etc_2'] ?? ''); ?>" size="10" class="frm_input" placeholder="transparent">
        <input type="color" value="<?php echo htmlspecialchars2($d['cs_etc_2'] ?? '#ffffff'); ?>">
    </td>
</tr>
<tr>
    <th scope="row">글씨 크기</th>
    <td colspan="2">
        <input type="text" name="cs_etc_3[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($d['cs_etc_3'] ?? ''); ?>" size="8" class="frm_input" placeholder="14px">
    </td>
</tr>
<tr>
    <th scope="row">테두리</th>
    <td colspan="2">
        두께: <input type="text" name="cs_etc_4[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($d['cs_etc_4'] ?? ''); ?>" size="6" placeholder="1px">
        &nbsp;스타일:
        <?php $bs = $d['cs_etc_5'] ?? 'solid'; ?>
        <select name="cs_etc_5[<?php echo $di; ?>]">
            <?php foreach (array('solid','dashed','dotted','none') as $bsv): ?><option value="<?php echo $bsv; ?>"<?php echo $bs === $bsv ? ' selected' : ''; ?>><?php echo $bsv; ?></option><?php endforeach; ?>
        </select>
        &nbsp;색상: <input type="text" name="cs_etc_6[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($d['cs_etc_6'] ?? ''); ?>" size="10">
        <input type="color" value="<?php echo htmlspecialchars2($d['cs_etc_6'] ?? '#cccccc'); ?>">
    </td>
</tr>
<tr>
    <th scope="row">패딩 / 영역크기 / 정렬</th>
    <td colspan="2">
        패딩: <input type="text" name="cs_etc_7[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($d['cs_etc_7'] ?? ''); ?>" size="14" placeholder="8px 14px" class="frm_input">
        &nbsp;너비: <input type="text" name="cs_etc_8[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($d['cs_etc_8'] ?? ''); ?>" size="8" placeholder="auto" class="frm_input">
        &nbsp;정렬:
        <?php $ta = $d['cs_etc_9'] ?? ''; ?>
        <select name="cs_etc_9[<?php echo $di; ?>]">
            <option value=""<?php echo $ta === '' ? ' selected' : ''; ?>>기본</option>
            <option value="left"<?php echo $ta === 'left' ? ' selected' : ''; ?>>왼쪽</option>
            <option value="center"<?php echo $ta === 'center' ? ' selected' : ''; ?>>가운데</option>
            <option value="right"<?php echo $ta === 'right' ? ' selected' : ''; ?>>오른쪽</option>
        </select>
        <?php if ($mcs_name === 'menu_sub_default'): ?>
        &nbsp;<span style="color:#456;">서브 그룹 위 간격(px):</span>
        <input type="text" name="cs_etc_10[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($d['cs_etc_10'] ?? ''); ?>" size="5" placeholder="0" class="frm_input">
        <?php else: ?>
        <input type="hidden" name="cs_etc_10[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($d['cs_etc_10'] ?? ''); ?>">
        <?php endif; ?>
    </td>
</tr>
</tbody>
</table>
</div>
<?php $di++; endforeach; ?>

<?php /* ===================== 섹션 4: content 영역 ===================== */ ?>
<h2 class="h2_frm">문서 영역 스타일</h2>
<div class="tbl_frm01 tbl_wrap">
<table>
<colgroup><col style="width:160px"><col style="width:120px"><col></colgroup>
<tbody>
<tr>
    <th scope="row">배경색
        <input type="hidden" name="cs_name[<?php echo $di; ?>]" value="content_bg_color">
    </th>
    <td colspan="2">
        <input type="text" name="cs_value[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($de['content_bg_color']['cs_value'] ?? ''); ?>" size="10" class="frm_input">
        <input type="color" value="<?php echo htmlspecialchars2($de['content_bg_color']['cs_value'] ?? '#ffffff'); ?>">
    </td>
</tr><?php $di++; ?>
<tr>
    <th scope="row">기본 글씨색
        <input type="hidden" name="cs_name[<?php echo $di; ?>]" value="content_text_color">
    </th>
    <td colspan="2">
        <input type="text" name="cs_value[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($de['content_text_color']['cs_value'] ?? ''); ?>" size="10" class="frm_input">
        <input type="color" value="<?php echo htmlspecialchars2($de['content_text_color']['cs_value'] ?? '#222222'); ?>">
        <span style="color:#888;font-size:12px;margin-left:6px;">(본문 영역의 기본 글씨색)</span>
    </td>
</tr><?php $di++; ?>
<tr>
    <th scope="row">테두리
        <input type="hidden" name="cs_name[<?php echo $di; ?>]" value="content_border">
    </th>
    <td colspan="2">
        두께: <input type="text" name="cs_value[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($de['content_border']['cs_value'] ?? ''); ?>" size="5" placeholder="0">
        &nbsp;스타일: <?php $cbs = $de['content_border']['cs_etc_1'] ?? 'solid'; ?>
        <select name="cs_etc_1[<?php echo $di; ?>]">
            <?php foreach (array('solid','dashed','dotted','none') as $bsv): ?><option value="<?php echo $bsv; ?>"<?php echo $cbs === $bsv ? ' selected' : ''; ?>><?php echo $bsv; ?></option><?php endforeach; ?>
        </select>
        &nbsp;색상: <input type="text" name="cs_etc_2[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($de['content_border']['cs_etc_2'] ?? ''); ?>" size="10">
        <input type="color" value="<?php echo htmlspecialchars2($de['content_border']['cs_etc_2'] ?? '#cccccc'); ?>">
    </td>
</tr><?php $di++; ?>
<tr>
    <th scope="row">Padding
        <input type="hidden" name="cs_name[<?php echo $di; ?>]" value="content_padding">
    </th>
    <td colspan="2">
        <?php
        // cs_value 에 "top right bottom left" 형태로 저장됨. 입력은 4개로 분리해 표시.
        $cp_raw = trim((string)($de['content_padding']['cs_value'] ?? ''));
        $cp = preg_split('/\s+/', $cp_raw);
        // CSS 단축 규칙에 따라 추론 (1 개 → 모두, 2 개 → 세로/가로, 3 개 → 상/좌우/하, 4 개 → 상우하좌)
        if (count($cp) === 1 && $cp[0] === '') $cp = array('','','','');
        elseif (count($cp) === 1) $cp = array($cp[0],$cp[0],$cp[0],$cp[0]);
        elseif (count($cp) === 2) $cp = array($cp[0],$cp[1],$cp[0],$cp[1]);
        elseif (count($cp) === 3) $cp = array($cp[0],$cp[1],$cp[2],$cp[1]);
        elseif (count($cp) >= 4) $cp = array($cp[0],$cp[1],$cp[2],$cp[3]);
        ?>
        <input type="hidden" name="cs_value[<?php echo $di; ?>]" id="content_padding_combined" value="<?php echo htmlspecialchars2($cp_raw); ?>">
        <span class="padding-split" data-target="#content_padding_combined">
            상 <input type="text" data-pad="0" value="<?php echo htmlspecialchars2($cp[0]); ?>" size="6" placeholder="20px" class="frm_input">
            우 <input type="text" data-pad="1" value="<?php echo htmlspecialchars2($cp[1]); ?>" size="6" placeholder="24px" class="frm_input">
            하 <input type="text" data-pad="2" value="<?php echo htmlspecialchars2($cp[2]); ?>" size="6" placeholder="20px" class="frm_input">
            좌 <input type="text" data-pad="3" value="<?php echo htmlspecialchars2($cp[3]); ?>" size="6" placeholder="24px" class="frm_input">
        </span>
        <span style="color:#888;font-size:12px;margin-left:6px;">(비워두면 해당 방향 패딩 없음)</span>
    </td>
</tr><?php $di++; ?>
<tr>
    <th rowspan="2" scope="row">배경 이미지
        <input type="hidden" name="cs_name[<?php echo $di; ?>]" value="content_bg_img">
    </th>
    <td class="bo-right bo-left txt-center">
        <?php if (!empty($de['content_bg_img']['cs_value'])): ?><img src="<?php echo $de['content_bg_img']['cs_value']; ?>" class="prev_thumb"><?php else: ?>미등록<?php endif; ?>
    </td>
    <td>직접등록&nbsp; <input type="file" name="cs_value_file[<?php echo $di; ?>]" accept="image/*"></td>
</tr>
<tr>
    <td class="bo-right bo-left" colspan="2">
        외부경로&nbsp; <input type="text" name="cs_value[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($de['content_bg_img']['cs_value'] ?? ''); ?>" size="50">
        &nbsp;반복&nbsp;
        <?php $cir = $de['content_bg_img']['cs_etc_1'] ?? 'no-repeat'; ?>
        <select name="cs_etc_1[<?php echo $di; ?>]">
            <option value="no-repeat"<?php echo $cir === 'no-repeat' ? ' selected' : ''; ?>>반복없음</option>
            <option value="repeat"<?php echo $cir === 'repeat' ? ' selected' : ''; ?>>반복</option>
            <option value="repeat-x"<?php echo $cir === 'repeat-x' ? ' selected' : ''; ?>>가로반복</option>
            <option value="repeat-y"<?php echo $cir === 'repeat-y' ? ' selected' : ''; ?>>세로반복</option>
        </select>
        &nbsp;크기&nbsp;
        <?php $cis = $de['content_bg_img']['cs_etc_2'] ?? ''; ?>
        <select name="cs_etc_2[<?php echo $di; ?>]">
            <option value="">원본</option>
            <option value="cover"<?php echo $cis === 'cover' ? ' selected' : ''; ?>>꽉참</option>
            <option value="contain"<?php echo $cis === 'contain' ? ' selected' : ''; ?>>맞춤</option>
        </select>
    </td>
</tr><?php $di++; ?>
</tbody>
</table>
</div>

<?php /* ===================== 섹션 5: 전체 페이지 배경 ===================== */ ?>
<?php
$bg_fields = array(
    array('page_bg_desktop', '전체 페이지 배경 (데스크톱 1025px+)'),
    array('page_bg_mobile',  '전체 페이지 배경 (모바일 1024px 이하)'),
);
foreach ($bg_fields as $bgf):
    $bcs_name = $bgf[0];
    $blabel   = $bgf[1];
    $d = $de[$bcs_name] ?? array();
?>
<h2 class="h2_frm"><?php echo $blabel; ?></h2>
<div class="tbl_frm01 tbl_wrap">
<table>
<colgroup><col style="width:160px"><col style="width:120px"><col></colgroup>
<tbody>
<tr>
    <th rowspan="3" scope="row">배경 이미지
        <input type="hidden" name="cs_name[<?php echo $di; ?>]" value="<?php echo $bcs_name; ?>">
    </th>
    <td class="bo-right bo-left txt-center">
        <?php if (!empty($d['cs_value'])): ?><img src="<?php echo $d['cs_value']; ?>" class="prev_thumb"><?php else: ?>미등록<?php endif; ?>
    </td>
    <td>직접등록&nbsp; <input type="file" name="cs_value_file[<?php echo $di; ?>]" accept="image/*"></td>
</tr>
<tr>
    <td colspan="2">외부경로&nbsp; <input type="text" name="cs_value[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($d['cs_value'] ?? ''); ?>" size="50"></td>
</tr>
<tr>
    <td colspan="2">
        배경색&nbsp; <input type="text" name="cs_etc_1[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($d['cs_etc_1'] ?? ''); ?>" size="10">
        <input type="color" value="<?php echo htmlspecialchars2($d['cs_etc_1'] ?? '#ffffff'); ?>">
        &nbsp;반복&nbsp;
        <?php $br = $d['cs_etc_2'] ?? 'no-repeat'; ?>
        <select name="cs_etc_2[<?php echo $di; ?>]">
            <option value="no-repeat"<?php echo $br === 'no-repeat' ? ' selected' : ''; ?>>반복없음</option>
            <option value="repeat"<?php echo $br === 'repeat' ? ' selected' : ''; ?>>반복</option>
            <option value="repeat-x"<?php echo $br === 'repeat-x' ? ' selected' : ''; ?>>가로반복</option>
            <option value="repeat-y"<?php echo $br === 'repeat-y' ? ' selected' : ''; ?>>세로반복</option>
        </select>
        &nbsp;위치&nbsp;
        <?php $bp = $d['cs_etc_3'] ?? 'center center'; ?>
        <select name="cs_etc_3[<?php echo $di; ?>]">
            <?php foreach (array('center center'=>'중앙','left top'=>'왼쪽 위','right top'=>'오른쪽 위','left bottom'=>'왼쪽 아래','right bottom'=>'오른쪽 아래') as $bpv => $bpl): ?>
            <option value="<?php echo $bpv; ?>"<?php echo $bp === $bpv ? ' selected' : ''; ?>><?php echo $bpl; ?></option>
            <?php endforeach; ?>
        </select>
        &nbsp;크기&nbsp;
        <?php $bsz = $d['cs_etc_4'] ?? 'cover'; ?>
        <select name="cs_etc_4[<?php echo $di; ?>]">
            <option value=""<?php echo $bsz === '' ? ' selected' : ''; ?>>원본</option>
            <option value="cover"<?php echo $bsz === 'cover' ? ' selected' : ''; ?>>꽉참</option>
            <option value="contain"<?php echo $bsz === 'contain' ? ' selected' : ''; ?>>맞춤</option>
            <option value="100% 100%"<?php echo $bsz === '100% 100%' ? ' selected' : ''; ?>>늘이기</option>
        </select>
        <?php if ($bcs_name === 'page_bg_desktop'): ?>
        &nbsp;스크롤&nbsp;
        <?php $bat = $d['cs_etc_5'] ?? 'fixed'; ?>
        <select name="cs_etc_5[<?php echo $di; ?>]">
            <option value="scroll"<?php echo $bat === 'scroll' ? ' selected' : ''; ?>>같이 스크롤</option>
            <option value="fixed"<?php echo $bat === 'fixed' ? ' selected' : ''; ?>>고정</option>
        </select>
        <?php endif; ?>
    </td>
</tr>
</tbody>
</table>
</div>
<?php $di++; endforeach; ?>

<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="저장" class="btn_submit" accesskey="s">
</div>
</form>

</main>

<aside class="wd-preview">
    <div class="wd-preview-bar" id="wd-preview-bar">
        <button type="button" class="wd-preview-toggle" id="wd-preview-toggle" title="접기/펼치기">▶</button>
        <span class="wd-preview-bar-main" style="display:flex;align-items:center;gap:8px;flex:1">
            <span class="live-dot" title="실시간 반영"></span>
            <strong>실시간 미리보기</strong>
            <span class="spc" style="flex:1"></span>
            <a href="<?php echo G5_URL; ?>/world/?_pd_id=<?php echo (int)$pd_id; ?>" target="_blank" style="font-size:11px;color:#369;text-decoration:none;">⤤ 실제 페이지 새창</a>
        </span>
    </div>
    <div class="wd-sample-host" id="wd-sample-host">
        <div class="world-wrap world-wrap--top" id="wd-sample-wrap">
            <!-- 상/하 위치용: 메인 행 + 서브 행 분리 (JS로 모드별 표시 제어) -->
            <nav class="world-menu world-menu--horizontal" id="wd-menu-horizontal">
                <div class="world-menu-main-row">
                    <div class="world-menu-main-item active" data-mid="1">
                        <a class="world-menu-main active" href="#" onclick="wdSetActive(1);return false;">메인 메뉴 1</a>
                    </div>
                    <div class="world-menu-main-item" data-mid="2">
                        <a class="world-menu-main" href="#" onclick="wdSetActive(2);return false;">메인 메뉴 2</a>
                    </div>
                    <div class="world-menu-main-item" data-mid="3">
                        <a class="world-menu-main" href="#" onclick="wdSetActive(3);return false;">메인 메뉴 3</a>
                    </div>
                </div>
                <div class="world-menu-sub-row" id="wd-sub-row">
                    <div class="world-menu-sub-wrap" data-for-mid="1">
                        <a class="world-menu-sub active" href="#" onclick="return false">서브 1-1</a>
                        <a class="world-menu-sub" href="#" onclick="return false">서브 1-2</a>
                    </div>
                    <div class="world-menu-sub-wrap" data-for-mid="2" style="display:none">
                        <a class="world-menu-sub" href="#" onclick="return false">서브 2-1</a>
                        <a class="world-menu-sub" href="#" onclick="return false">서브 2-2</a>
                    </div>
                    <div class="world-menu-sub-wrap" data-for-mid="3" style="display:none"></div>
                </div>
            </nav>
            <!-- 좌/우 위치용: 메인 아래 자체 서브 -->
            <nav class="world-menu world-menu--vertical" id="wd-menu-vertical" style="display:none">
                <div class="world-menu-main-item active" data-mid="1">
                    <a class="world-menu-main active" href="#" onclick="wdSetActive(1);return false;">메인 메뉴 1</a>
                    <div class="world-menu-sub-wrap">
                        <a class="world-menu-sub active" href="#" onclick="return false">서브 1-1</a>
                        <a class="world-menu-sub" href="#" onclick="return false">서브 1-2</a>
                    </div>
                </div>
                <div class="world-menu-main-item" data-mid="2">
                    <a class="world-menu-main" href="#" onclick="wdSetActive(2);return false;">메인 메뉴 2</a>
                    <div class="world-menu-sub-wrap">
                        <a class="world-menu-sub" href="#" onclick="return false">서브 2-1</a>
                        <a class="world-menu-sub" href="#" onclick="return false">서브 2-2</a>
                    </div>
                </div>
                <div class="world-menu-main-item" data-mid="3">
                    <a class="world-menu-main" href="#" onclick="wdSetActive(3);return false;">메인 메뉴 3</a>
                    <div class="world-menu-sub-wrap"></div>
                </div>
            </nav>
            <main class="world-content">
                <h3>샘플 콘텐츠</h3>
                <p>이 영역은 세계관 페이지의 본문 영역입니다. 콘텐츠 배경·테두리·패딩 설정이 여기에 적용됩니다.</p>
                <p>위/아래 메뉴 위치에서는 메인 메뉴 행과 서브 메뉴 행이 분리되어 표시됩니다.<br>좌/우 메뉴 위치에서는 펼침/접힘 설정에 따라 다르게 노출됩니다.</p>
                <p style="color:#888;font-size:13px;">메인 메뉴 항목을 클릭하면 활성 항목이 바뀝니다.</p>
            </main>
        </div>
    </div>
</aside>

</div>

<script>
// 폼 → CSS 문자열 (world_design_form_css.php 와 동일 규칙)
function collectDesign() {
    var form = document.getElementById('fworlddesign');
    var map = {};
    form.querySelectorAll('input[name^="cs_name["]').forEach(function(nameInput) {
        var m = nameInput.name.match(/^cs_name\[(\d+)\]$/);
        if (!m) return;
        var idx = m[1];
        var key = nameInput.value;
        if (!key) return;
        var entry = { v:'' , e:[null, '', '', '', '', '', '', '', '', '', ''] };
        var vinp = form.querySelector('[name="cs_value['+idx+']"]');
        if (vinp) {
            // 파일 업로드 미리보기(data URL)가 있으면 우선 사용
            var pv = vinp.getAttribute('data-preview-file-url');
            entry.v = pv || vinp.value;
        }
        for (var i = 1; i <= 10; i++) {
            var einp = form.querySelector('[name="cs_etc_'+i+'['+idx+']"]');
            if (einp) entry.e[i] = einp.value;
        }
        map[key] = entry;
    });
    return map;
}

// 파일 input → 동일 idx의 cs_value 텍스트 input에 data URL을 임시 저장해 미리보기 반영
function wdBindFilePreview() {
    var form = document.getElementById('fworlddesign');
    form.querySelectorAll('input[type="file"][name^="cs_value_file["]').forEach(function(fi) {
        var m = fi.name.match(/^cs_value_file\[(\d+)\]$/);
        if (!m) return;
        var idx = m[1];
        fi.addEventListener('change', function() {
            var f = fi.files && fi.files[0];
            if (!f) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                var t = form.querySelector('[name="cs_value['+idx+']"]');
                if (t) {
                    t.setAttribute('data-preview-file-url', e.target.result);
                    t.dispatchEvent(new Event('input', { bubbles:true }));
                }
            };
            reader.readAsDataURL(f);
        });
    });
}

function buildCss(d, scope) {
    var sp = scope ? (scope + ' ') : '';
    function g(k, idx) { return (d[k] && d[k].e[idx]) ? d[k].e[idx] : ''; }
    function gv(k)     { return (d[k] && d[k].v) ? d[k].v : ''; }
    function imgRule(k, idx) { var u = (idx === 0 ? gv(k) : g(k, idx)); return u ? "background-image:url('"+u+"');" : ""; }
    function colorRule(prop, k, idx) { var c = (idx === 0 ? gv(k) : g(k, idx)); return c ? prop+":"+c+";" : ""; }
    function valRule(prop, k, idx) { var v = (idx === 0 ? gv(k) : g(k, idx)); return v ? prop+":"+v+";" : ""; }
    function menuItemRule(k) {
        // 배경이미지(cs_value)는 메뉴 텍스트와 함께 표시되도록 background-image 로 적용 (텍스트는 위에 오버레이).
        return imgRule(k, 0) +
               colorRule('color', k, 1) +
               colorRule('background-color', k, 2) +
               valRule('font-size', k, 3) +
               (g(k,4) ? "border:"+g(k,4)+" "+(g(k,5)||'solid')+" "+g(k,6)+";" : "") +
               (g(k,7) ? "padding:"+g(k,7)+";" : "") +
               (g(k,8) ? "width:"+g(k,8)+";" : "") +
               (g(k,9) ? "text-align:"+g(k,9)+";" : "");
    }

    var css = '';

    // 페이지 배경 — 미리보기에선 .wd-sample-host 자체에 적용 (데스크톱 규칙만)
    if (scope) {
        css += scope+"{";
        var pbd_img = gv('page_bg_desktop');
        if (pbd_img) css += "background-image:url('"+pbd_img+"');";
        if (g('page_bg_desktop',1)) css += "background-color:"+g('page_bg_desktop',1)+";";
        if (g('page_bg_desktop',2)) css += "background-repeat:"+g('page_bg_desktop',2)+";";
        if (g('page_bg_desktop',3)) css += "background-position:"+g('page_bg_desktop',3)+";";
        if (g('page_bg_desktop',4)) css += "background-size:"+g('page_bg_desktop',4)+";";
        css += "}";
    } else {
        css += "@media (min-width:1025px){html.single:before,html:before{";
        css += imgRule('page_bg_desktop', 0).replace(';', ' !important;');
        css += g('page_bg_desktop',1) ? "background-color:"+g('page_bg_desktop',1)+" !important;" : "";
        css += g('page_bg_desktop',2) ? "background-repeat:"+g('page_bg_desktop',2)+" !important;" : "";
        css += g('page_bg_desktop',3) ? "background-position:"+g('page_bg_desktop',3)+" !important;" : "";
        css += g('page_bg_desktop',4) ? "background-size:"+g('page_bg_desktop',4)+" !important;" : "";
        css += g('page_bg_desktop',5) ? "background-attachment:"+g('page_bg_desktop',5)+" !important;" : "";
        css += "}}";
        css += "@media (max-width:1024px){html.single:before,html:before{";
        var pbm = gv('page_bg_mobile');
        css += pbm ? "background-image:url('"+pbm+"') !important;" : "background-image:none !important;";
        css += g('page_bg_mobile',1) ? "background-color:"+g('page_bg_mobile',1)+" !important;" : "";
        css += g('page_bg_mobile',2) ? "background-repeat:"+g('page_bg_mobile',2)+" !important;" : "";
        css += g('page_bg_mobile',3) ? "background-position:"+g('page_bg_mobile',3)+" !important;" : "";
        css += g('page_bg_mobile',4) ? "background-size:"+g('page_bg_mobile',4)+" !important;" : "";
        css += "}}";
    }

    // 메뉴 영역
    css += sp+".world-menu{box-sizing:border-box;";
    if (gv('menu_area_width')) {
        css += "width:"+gv('menu_area_width')+";";
        if (g('menu_area_width',1)) css += "height:"+g('menu_area_width',1)+";";
    } else {
        css += "width:100%;";
    }
    css += imgRule('menu_area_bg', 0);
    css += colorRule('background-color','menu_area_bg',1);
    css += g('menu_area_bg',2) ? "background-repeat:"+g('menu_area_bg',2)+";" : "";
    css += g('menu_area_bg',3) ? "background-size:"+g('menu_area_bg',3)+";" : "";
    if (gv('menu_area_border')) {
        css += "border:"+gv('menu_area_border')+" "+(g('menu_area_border',1)||'solid')+" "+g('menu_area_border',2)+";";
    }
    // menu_area_layout: cs_value=전체 정렬, etc_1=메인행 정렬, etc_2=서브행 정렬, etc_3=패딩, etc_4=마진
    if (gv('menu_area_layout')) css += "text-align:"+gv('menu_area_layout')+";";
    if (g('menu_area_layout',3)) css += "padding:"+g('menu_area_layout',3)+";";
    if (g('menu_area_layout',4)) css += "margin:"+g('menu_area_layout',4)+";";
    css += "}";
    // 메인/서브행 정렬
    if (g('menu_area_layout',1)) {
        css += sp+" .world-menu-main-row{justify-content:"+(g('menu_area_layout',1)==='center'?'center':(g('menu_area_layout',1)==='right'?'flex-end':'flex-start'))+";text-align:"+g('menu_area_layout',1)+";}";
    }
    if (g('menu_area_layout',2)) {
        css += sp+" .world-menu-sub-row,"+sp+" .world-menu-sub-wrap{justify-content:"+(g('menu_area_layout',2)==='center'?'center':(g('menu_area_layout',2)==='right'?'flex-end':'flex-start'))+";text-align:"+g('menu_area_layout',2)+";}";
    }
    // 메인/서브 컨테이너 gap (Task 2-2)
    if (g('menu_area_layout',5)) {
        css += sp+".world-menu-main-row{gap:"+g('menu_area_layout',5)+";}";
        css += sp+".world-menu--vertical{gap:"+g('menu_area_layout',5)+";display:flex;flex-direction:column;}";
    }
    if (g('menu_area_layout',6)) {
        css += sp+".world-menu-sub-wrap{gap:"+g('menu_area_layout',6)+";}";
    }

    // 메뉴 아이템
    css += sp+".world-menu-main{"+menuItemRule('menu_main_default')+"}";
    css += sp+".world-menu-main:hover,"+sp+".world-menu-main.active{"+menuItemRule('menu_main_hover')+"}";
    css += sp+".world-menu-sub{"+menuItemRule('menu_sub_default')+"}";
    css += sp+".world-menu-sub:hover,"+sp+".world-menu-sub.active{"+menuItemRule('menu_sub_hover')+"}";

    // 서브메뉴 그룹 위 간격 (menu_sub_default[10])
    var subGap = g('menu_sub_default', 10);
    if (subGap && /^\d+$/.test(subGap)) {
        css += sp+".world-menu--horizontal .world-menu-sub-row,"+sp+".world-menu--vertical .world-menu-sub-wrap{margin-top:"+subGap+"px;}";
    }

    // content
    css += sp+".world-content{flex:1;min-width:0;box-sizing:border-box;";
    css += colorRule('background-color','content_bg_color',0);
    css += colorRule('color','content_text_color',0);
    if (gv('content_border')) css += "border:"+gv('content_border')+" "+(g('content_border',1)||'solid')+" "+(g('content_border',2)||'transparent')+";";
    if (gv('content_padding')) css += "padding:"+gv('content_padding')+";";
    css += imgRule('content_bg_img', 0);
    css += g('content_bg_img',1) ? "background-repeat:"+g('content_bg_img',1)+";" : "";
    css += g('content_bg_img',2) ? "background-size:"+g('content_bg_img',2)+";" : "";
    css += "}";

    return css;
}

function applyLivePreview() {
    var host = document.getElementById('wd-sample-host');
    if (!host) return;
    var st = document.getElementById('wd-live-style');
    if (!st) {
        st = document.createElement('style');
        st.id = 'wd-live-style';
        document.head.appendChild(st);
    }
    var d = collectDesign();
    st.textContent = buildCss(d, '.wd-sample-host');

    // 메뉴 위치 클래스 토글
    var pos = (d['menu_pos_v'] && d['menu_pos_v'].v) ? d['menu_pos_v'].v : 'top';
    var posIn = (d['menu_pos_in'] && d['menu_pos_in'].v) ? d['menu_pos_in'].v : 'outside';
    var expand = (d['menu_sub_expand'] && d['menu_sub_expand'].v === '1');
    var wrap = document.getElementById('wd-sample-wrap');
    if (!wrap) return;
    ['top','bottom','left','right'].forEach(function(p){ wrap.classList.remove('world-wrap--'+p); });
    wrap.classList.add('world-wrap--'+pos);
    wrap.classList.toggle('world-wrap--menu-inside', posIn === 'inside');

    var isVertical = (pos === 'left' || pos === 'right');
    var menuH = document.getElementById('wd-menu-horizontal');
    var menuV = document.getElementById('wd-menu-vertical');
    if (menuH) menuH.style.display = isVertical ? 'none' : '';
    if (menuV) menuV.style.display = isVertical ? '' : 'none';

    // 메뉴 inside/outside 위치 — content 안 또는 밖으로 실제 DOM 이동
    //  - inside  : .world-content 의 첫 자식으로 이동 (좌/우 위치는 항상 outside 처럼 동작)
    //  - outside : .world-wrap 직속 자식으로 복원
    var content = wrap.querySelector('.world-content');
    var activeNav = isVertical ? menuV : menuH;
    if (content && activeNav) {
        var shouldBeInside = (posIn === 'inside' && !isVertical);
        if (shouldBeInside) {
            if (activeNav.parentNode !== content) content.insertBefore(activeNav, content.firstChild);
        } else {
            if (activeNav.parentNode !== wrap) {
                // 위치(top/bottom)에 따라 content 앞/뒤로 배치
                if (pos === 'bottom') wrap.appendChild(activeNav);
                else wrap.insertBefore(activeNav, content);
            }
        }
    }

    if (!isVertical) {
        // 상/하: 서브-행에서 활성 main 의 sub-wrap 만 노출
        var activeMid = '1';
        if (menuH) {
            var act = menuH.querySelector('.world-menu-main-item.active');
            if (act) activeMid = act.getAttribute('data-mid');
            menuH.querySelectorAll('.world-menu-sub-wrap').forEach(function(w){
                w.style.display = (w.getAttribute('data-for-mid') === activeMid) ? '' : 'none';
            });
        }
    } else {
        // 좌/우: 펼침이면 모두 표시, 아니면 활성만
        if (menuV) {
            menuV.querySelectorAll('.world-menu-main-item').forEach(function(it) {
                var isActive = it.classList.contains('active');
                var sub = it.querySelector('.world-menu-sub-wrap');
                if (!sub) return;
                sub.style.display = (expand || isActive) ? '' : 'none';
            });
        }
    }
}

// 메인 메뉴 클릭 시 active 토글 (미리보기 데모용)
window.wdSetActive = function(mid) {
    var midStr = String(mid);
    ['wd-menu-horizontal', 'wd-menu-vertical'].forEach(function(id) {
        var nav = document.getElementById(id);
        if (!nav) return;
        nav.querySelectorAll('.world-menu-main-item').forEach(function(it) {
            var on = (it.getAttribute('data-mid') === midStr);
            it.classList.toggle('active', on);
            var a = it.querySelector('.world-menu-main');
            if (a) a.classList.toggle('active', on);
        });
    });
    applyLivePreview();
};

var _liveTimer = null;
function scheduleLive() {
    clearTimeout(_liveTimer);
    _liveTimer = setTimeout(applyLivePreview, 100);
}

// === <input type="color"> ↔ 인접 텍스트 input 동기화 ===
function wdBindColorPickers() {
    var form = document.getElementById('fworlddesign');
    form.querySelectorAll('input[type="color"]').forEach(function(picker) {
        // picker 직전의 형제 중 가장 가까운 텍스트 input 찾기
        var sib = picker.previousElementSibling;
        while (sib && !(sib.tagName === 'INPUT' && (sib.type === 'text' || !sib.type))) {
            sib = sib.previousElementSibling;
        }
        if (!sib) return;
        var textInp = sib;
        // 초기값: 텍스트가 비어 있으면 비워둠. 색상 picker 는 텍스트 값으로 동기화
        function syncTextToPicker() {
            var v = (textInp.value || '').trim();
            if (/^#[0-9a-fA-F]{6}$/.test(v)) picker.value = v;
        }
        syncTextToPicker();
        picker.addEventListener('input', function() {
            textInp.value = picker.value;
            // 디자인 폼 변경 이벤트 트리거
            textInp.dispatchEvent(new Event('input', { bubbles: true }));
            textInp.dispatchEvent(new Event('change', { bubbles: true }));
        });
        textInp.addEventListener('input', syncTextToPicker);
    });
}

// === 패딩 split (4 입력 → cs_value 결합) ===
function wdBindPaddingSplit() {
    var form = document.getElementById('fworlddesign');
    form.querySelectorAll('.padding-split').forEach(function(grp) {
        var target = document.querySelector(grp.getAttribute('data-target'));
        if (!target) return;
        var inputs = grp.querySelectorAll('input[data-pad]');
        function combine() {
            var t = (inputs[0].value||'').trim();
            var r = (inputs[1].value||'').trim();
            var b = (inputs[2].value||'').trim();
            var l = (inputs[3].value||'').trim();
            if (!t && !r && !b && !l) { target.value=''; }
            else if (t === r && r === b && b === l) { target.value = t; }
            else if (t === b && r === l) { target.value = t + ' ' + r; }
            else { target.value = t + ' ' + r + ' ' + b + ' ' + l; }
            target.dispatchEvent(new Event('input', { bubbles: true }));
        }
        inputs.forEach(function(i){ i.addEventListener('input', combine); });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('fworlddesign');
    wdBindColorPickers();
    wdBindPaddingSplit();
    wdBindFilePreview();
    form.addEventListener('input', scheduleLive);
    form.addEventListener('change', scheduleLive);
    applyLivePreview();

    // 미리보기 접기/펼치기
    var split = document.querySelector('.wd-split');
    var btn = document.getElementById('wd-preview-toggle');
    var bar = document.getElementById('wd-preview-bar');
    function setCollapsed(c) {
        split.classList.toggle('preview-collapsed', c);
        btn.textContent = c ? '◀' : '▶';
        try { sessionStorage.setItem('wd_preview_collapsed', c ? '1' : '0'); } catch(e){}
    }
    if (btn && split) {
        var initC = false;
        try { initC = sessionStorage.getItem('wd_preview_collapsed') === '1'; } catch(e){}
        setCollapsed(initC);
        btn.addEventListener('click', function(e){ e.stopPropagation(); setCollapsed(!split.classList.contains('preview-collapsed')); });
        bar.addEventListener('click', function(){
            if (split.classList.contains('preview-collapsed')) { setCollapsed(false); }
        });
    }
});
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
?>
