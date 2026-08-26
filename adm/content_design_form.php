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
                @mkdir(G5_DATA_PATH.'/content/design', G5_DIR_PERMISSION);
                @chmod(G5_DATA_PATH.'/content/design', G5_DIR_PERMISSION);
                $fname = 'c_' . preg_replace('/[^a-z0-9]/', '_', $cs_name) . '_' . $pd_id . '.' . $ext;
                $dest  = G5_DATA_PATH . '/content/design/' . $fname;
                if (@move_uploaded_file($_FILES['cs_value_file']['tmp_name'][$n], $dest)) {
                    @chmod($dest, G5_FILE_PERMISSION);
                    $cs_value = G5_DATA_URL . '/content/design/' . $fname;
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

        $exists = sql_fetch("SELECT cs_id FROM `{$g5['content_design_config_table']}` WHERE cs_name = '{$cs_name_e}' AND pd_id = '{$pd_id}'");
        if ($exists['cs_id']) {
            sql_query("UPDATE `{$g5['content_design_config_table']}` SET cs_value = '{$cs_value_e}' {$etc_sets} WHERE cs_id = '{$exists['cs_id']}'");
        } else {
            sql_query("INSERT INTO `{$g5['content_design_config_table']}` SET cs_name = '{$cs_name_e}', pd_id = '{$pd_id}', cs_value = '{$cs_value_e}' {$etc_sets}");
        }
    }

    goto_url("./content_design_form.php?pd_id={$pd_id}");
}

// ─── 프리셋 목록 + 현재 설정값 로드 ────────────────────────────────────
if (isset($_GET['pd_id'])) {
    $pd_id = (int)$_GET['pd_id'];
} else {
    $pd_id = function_exists('get_active_design') ? (int)get_active_design('content') : 0;
}
if ($pd_id <= 0) $pd_id = 1;

$pd_list = array();
$r = sql_query("SELECT * FROM `{$g5['content_preset_table']}` ORDER BY pd_order, pd_id");
while ($row = sql_fetch_array($r)) { $pd_list[$row['pd_id']] = $row; }
if (!isset($pd_list[$pd_id])) $pd_id = 1;

$de = array();
$r  = sql_query("SELECT * FROM `{$g5['content_design_config_table']}` WHERE pd_id = '{$pd_id}'");
while ($row = sql_fetch_array($r)) { $de[$row['cs_name']] = $row; }

$g5['title'] = '페이지 디자인 설정';
include_once(G5_ADMIN_PATH.'/admin.head.php');
$design_hub = 'page';
$design_hub_tab = 'content';
include_once(G5_ADMIN_PATH.'/_design_hub_tabs.inc.php');

$di = 0; // design field index
?>
<div class="cd-bar">
    <strong>편집:</strong> #<?php echo (int)$pd_id; ?> <?php echo htmlspecialchars2($pd_list[$pd_id]['pd_name'] ?? ''); ?>
    <span style="color:#888;">|</span>
    <select id="pd_switch">
        <?php foreach ($pd_list as $pid => $pr): ?>
        <option value="<?php echo (int)$pid; ?>"<?php echo ($pid == $pd_id) ? ' selected' : ''; ?>>#<?php echo (int)$pid; ?> <?php echo htmlspecialchars2($pr['pd_name']); ?></option>
        <?php endforeach; ?>
    </select>
    <button type="button" onclick="location.href='./content_design_form.php?pd_id='+document.getElementById('pd_switch').value;" class="btn_admin">전환</button>
    <button type="button" onclick="cdRename();" class="btn_admin">이름 변경</button>
    <a href="./content_preset_list.php" class="btn_admin">문서 프리셋 목록</a>
    <?php
    $cd_active_pd_id  = function_exists('get_active_design') ? (int)get_active_design('content') : 1;
    $cd_active_preset = function_exists('get_preset') ? get_preset() : array('pr_name' => '');
    if ((int)$pd_id === $cd_active_pd_id) {
        echo '<span style="color:#369;font-weight:bold;margin-left:8px;">★ 현재 사용중인 '.htmlspecialchars2($cd_active_preset['pr_name'] ?: 'default').' 의 문서 디자인입니다.</span>';
    }
    ?>
    <span class="spc" style="flex:1"></span>
    <button type="button" onclick="document.getElementById('fcontentdesign').submit();" class="btn_admin" style="background:#369;color:#fff;border:0;padding:6px 16px;font-weight:600;">💾 저장</button>
    <div class="cd-bar-json">
        <strong>JSON:</strong>
        <button type="button" onclick="cdExportDesign();" class="btn_admin">JSON 내보내기</button>
        <button type="button" onclick="cdImportDesign();" class="btn_admin">JSON 가져오기 (현재에 적용)</button>
        <button type="button" onclick="cdImportDesignNew();" class="btn_admin">JSON으로 새 디자인</button>
        <span class="linked">저장되지 않은 변경은 포함되지 않습니다. 배경 등 이미지는 경로만 저장되며, 다른 사이트에서 가져오면 파일을 다시 올려야 할 수 있습니다.</span>
    </div>
</div>
<form id="cd_json_import_form" method="post" action="./content_preset_action.php" enctype="multipart/form-data" style="display:none">
    <input type="hidden" name="action" id="cd_json_action" value="import">
    <input type="hidden" name="pd_id" value="<?php echo (int)$pd_id; ?>">
    <input type="hidden" name="pd_name" id="cd_json_pd_name" value="">
    <input type="file" name="json_file" id="cd_json_file" accept="application/json,.json">
</form>
<script>
function cdRename(){
    var nm = prompt('새 이름?', '<?php echo htmlspecialchars2($pd_list[$pd_id]['pd_name'] ?? '', ENT_QUOTES); ?>');
    if (!nm) return;
    location.href = './content_preset_action.php?action=rename&pd_id=<?php echo (int)$pd_id; ?>&pd_name=' + encodeURIComponent(nm);
}
function cdExportDesign() {
    location.href = './content_preset_action.php?action=export&pd_id=<?php echo (int)$pd_id; ?>';
}
function cdImportDesign() {
    if (!confirm('현재 편집 중인 문서 디자인 설정을 JSON 파일 내용으로 덮어씁니다.\n(이미지 파일 자체는 포함되지 않으며, 경로만 가져옵니다.)\n계속할까요?')) return;
    document.getElementById('cd_json_action').value = 'import';
    document.getElementById('cd_json_pd_name').value = '';
    document.getElementById('cd_json_file').value = '';
    document.getElementById('cd_json_file').click();
}
function cdImportDesignNew() {
    var nm = prompt('새 문서 디자인 프리셋 이름을 입력하세요', '<?php echo htmlspecialchars2($pd_list[$pd_id]['pd_name'] ?? '', ENT_QUOTES); ?>_import');
    if (!nm) return;
    document.getElementById('cd_json_action').value = 'import_new';
    document.getElementById('cd_json_pd_name').value = nm;
    document.getElementById('cd_json_file').value = '';
    document.getElementById('cd_json_file').click();
}
(function(){
    var fi = document.getElementById('cd_json_file');
    if (!fi) return;
    fi.addEventListener('change', function(){
        if (this.files && this.files[0]) {
            document.getElementById('cd_json_import_form').submit();
        }
    });
})();
</script>

<div class="cd-split">
<main class="cd-form-col">

<form name="fcontentdesign" id="fcontentdesign" method="post" action="./content_design_form.php" onsubmit="return true;" enctype="multipart/form-data">
<input type="hidden" name="token" value="">
<input type="hidden" name="pd_id" value="<?php echo (int)$pd_id; ?>">

<?php /* ===================== 섹션 1: 문서 영역 스타일 ===================== */ ?>
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
        $cp_raw = trim((string)($de['content_padding']['cs_value'] ?? ''));
        $cp = preg_split('/\s+/', $cp_raw);
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

<?php /* ===================== 섹션 2: 꾸밈 이미지 (상/하) ===================== */ ?>
<?php
$deco_fields = array(
    array('decoration_top',    '상단 꾸밈 이미지', '문서 영역 위쪽에 표시됩니다.'),
    array('decoration_bottom', '하단 꾸밈 이미지', '문서 영역 아래쪽에 표시됩니다.'),
);
foreach ($deco_fields as $df):
    $dcs_name = $df[0];
    $dlabel   = $df[1];
    $dhelp    = $df[2];
    $d        = $de[$dcs_name] ?? array();
?>
<h2 class="h2_frm"><?php echo $dlabel; ?> <span style="font-size:12px;color:#888;font-weight:normal;">— <?php echo $dhelp; ?></span></h2>
<div class="tbl_frm01 tbl_wrap">
<table>
<colgroup><col style="width:160px"><col style="width:120px"><col></colgroup>
<tbody>
<tr>
    <th rowspan="2" scope="row">이미지
        <input type="hidden" name="cs_name[<?php echo $di; ?>]" value="<?php echo $dcs_name; ?>">
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
    <th scope="row">정렬</th>
    <td colspan="2">
        <?php $align = $d['cs_etc_1'] ?? 'center'; ?>
        <select name="cs_etc_1[<?php echo $di; ?>]">
            <option value="left"<?php   echo $align === 'left'   ? ' selected' : ''; ?>>왼쪽</option>
            <option value="center"<?php echo $align === 'center' ? ' selected' : ''; ?>>중앙</option>
            <option value="right"<?php  echo $align === 'right'  ? ' selected' : ''; ?>>오른쪽</option>
        </select>
        &nbsp;최대 너비&nbsp; <input type="text" name="cs_etc_2[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($d['cs_etc_2'] ?? ''); ?>" size="8" placeholder="100% 또는 600px">
        &nbsp;여백&nbsp; <input type="text" name="cs_etc_3[<?php echo $di; ?>]" value="<?php echo htmlspecialchars2($d['cs_etc_3'] ?? ''); ?>" size="10" placeholder="0 0 16px 0">
        <span style="color:#888;font-size:12px;margin-left:6px;">(비워두면 기본값 사용)</span>
    </td>
</tr>
</tbody>
</table>
</div>
<?php $di++; endforeach; ?>

<?php /* ===================== 섹션 3: 전체 페이지 배경 ===================== */ ?>
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

<aside class="cd-preview">
    <div class="cd-preview-bar" id="cd-preview-bar">
        <button type="button" class="cd-preview-toggle" id="cd-preview-toggle" title="접기/펼치기">▶</button>
        <span class="cd-preview-bar-main" style="display:flex;align-items:center;gap:8px;flex:1">
            <span class="live-dot" title="실시간 반영"></span>
            <strong>실시간 미리보기</strong>
            <span class="spc" style="flex:1"></span>
        </span>
    </div>
    <div class="cd-sample-host" id="cd-sample-host">
        <div class="content-wrap" id="cd-sample-wrap">
            <div class="content-deco content-deco--top" id="cd-deco-top" style="display:none">
                <img id="cd-deco-top-img" src="" alt="상단 꾸밈">
            </div>
            <div class="ctt_box" id="cd-sample-content">
                <h3>샘플 문서</h3>
                <p>이 영역은 문서(content) 본문 영역입니다. 배경/테두리/패딩/배경이미지 설정이 여기에 적용됩니다.</p>
                <p>위/아래 꾸밈 이미지는 문서 영역 <strong>바깥쪽</strong>에 배치되어 장식 효과를 줍니다.</p>
                <p style="color:#888;font-size:13px;">실제 페이지에서 본문 텍스트는 작성된 문서 내용으로 대체됩니다.</p>
            </div>
            <div class="content-deco content-deco--bottom" id="cd-deco-bottom" style="display:none">
                <img id="cd-deco-bottom-img" src="" alt="하단 꾸밈">
            </div>
        </div>
    </div>
</aside>

</div>

<script>
// 폼 → CSS (content_design_form_css.php 와 동일 규칙)
function collectDesign() {
    var form = document.getElementById('fcontentdesign');
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

function cdBindFilePreview() {
    var form = document.getElementById('fcontentdesign');
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

    var css = '';

    // 페이지 배경 — 미리보기에선 .cd-sample-host 자체에 적용 (데스크톱 규칙만)
    if (scope) {
        css += scope+"{";
        var pbd_img = gv('page_bg_desktop');
        if (pbd_img) css += "background-image:url('"+pbd_img+"');";
        if (g('page_bg_desktop',1)) css += "background-color:"+g('page_bg_desktop',1)+";";
        if (g('page_bg_desktop',2)) css += "background-repeat:"+g('page_bg_desktop',2)+";";
        if (g('page_bg_desktop',3)) css += "background-position:"+g('page_bg_desktop',3)+";";
        if (g('page_bg_desktop',4)) css += "background-size:"+g('page_bg_desktop',4)+";";
        css += "}";
    }

    // content (.ctt_box)
    css += sp+".ctt_box{box-sizing:border-box;";
    css += colorRule('background-color','content_bg_color',0);
    css += colorRule('color','content_text_color',0);
    if (gv('content_border')) css += "border:"+gv('content_border')+" "+(g('content_border',1)||'solid')+" "+(g('content_border',2)||'transparent')+";";
    if (gv('content_padding')) css += "padding:"+gv('content_padding')+";";
    css += imgRule('content_bg_img', 0);
    css += g('content_bg_img',1) ? "background-repeat:"+g('content_bg_img',1)+";" : "";
    css += g('content_bg_img',2) ? "background-size:"+g('content_bg_img',2)+";" : "";
    css += "}";

    // 꾸밈 이미지 정렬/마진 (이미지 자체는 <img>로 출력하므로 컨테이너만 제어)
    ['decoration_top','decoration_bottom'].forEach(function(k){
        var cls = (k === 'decoration_top') ? '.content-deco--top' : '.content-deco--bottom';
        var rules = '';
        if (g(k,1)) rules += "text-align:"+g(k,1)+";";
        if (g(k,3)) rules += "margin:"+g(k,3)+";";
        if (rules) css += sp+cls+"{"+rules+"}";
        if (g(k,2)) css += sp+cls+" img{max-width:"+g(k,2)+";}";
    });

    return css;
}

function applyLivePreview() {
    var host = document.getElementById('cd-sample-host');
    if (!host) return;
    var st = document.getElementById('cd-live-style');
    if (!st) {
        st = document.createElement('style');
        st.id = 'cd-live-style';
        document.head.appendChild(st);
    }
    var d = collectDesign();
    st.textContent = buildCss(d, '.cd-sample-host');

    // 꾸밈 이미지 src 갱신
    var topUrl    = (d['decoration_top']    && d['decoration_top'].v)    ? d['decoration_top'].v    : '';
    var bottomUrl = (d['decoration_bottom'] && d['decoration_bottom'].v) ? d['decoration_bottom'].v : '';
    var topBox    = document.getElementById('cd-deco-top');
    var bottomBox = document.getElementById('cd-deco-bottom');
    var topImg    = document.getElementById('cd-deco-top-img');
    var bottomImg = document.getElementById('cd-deco-bottom-img');
    if (topImg)    topImg.src = topUrl;
    if (bottomImg) bottomImg.src = bottomUrl;
    if (topBox)    topBox.style.display    = topUrl    ? '' : 'none';
    if (bottomBox) bottomBox.style.display = bottomUrl ? '' : 'none';
}

var _liveTimer = null;
function scheduleLive() {
    clearTimeout(_liveTimer);
    _liveTimer = setTimeout(applyLivePreview, 100);
}

// === <input type="color"> ↔ 인접 텍스트 input 동기화 ===
function cdBindColorPickers() {
    var form = document.getElementById('fcontentdesign');
    form.querySelectorAll('input[type="color"]').forEach(function(picker) {
        var sib = picker.previousElementSibling;
        while (sib && !(sib.tagName === 'INPUT' && (sib.type === 'text' || !sib.type))) {
            sib = sib.previousElementSibling;
        }
        if (!sib) return;
        var textInp = sib;
        function syncTextToPicker() {
            var v = (textInp.value || '').trim();
            if (/^#[0-9a-fA-F]{6}$/.test(v)) picker.value = v;
        }
        syncTextToPicker();
        picker.addEventListener('input', function() {
            textInp.value = picker.value;
            textInp.dispatchEvent(new Event('input', { bubbles: true }));
            textInp.dispatchEvent(new Event('change', { bubbles: true }));
        });
        textInp.addEventListener('input', syncTextToPicker);
    });
}

// === 패딩 split (4 입력 → cs_value 결합) ===
function cdBindPaddingSplit() {
    var form = document.getElementById('fcontentdesign');
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
    var form = document.getElementById('fcontentdesign');
    cdBindColorPickers();
    cdBindPaddingSplit();
    cdBindFilePreview();
    form.addEventListener('input', scheduleLive);
    form.addEventListener('change', scheduleLive);
    applyLivePreview();

    var split = document.querySelector('.cd-split');
    var btn = document.getElementById('cd-preview-toggle');
    var bar = document.getElementById('cd-preview-bar');
    function setCollapsed(c) {
        split.classList.toggle('preview-collapsed', c);
        btn.textContent = c ? '◀' : '▶';
        try { sessionStorage.setItem('cd_preview_collapsed', c ? '1' : '0'); } catch(e){}
    }
    if (btn && split) {
        var initC = false;
        try { initC = sessionStorage.getItem('cd_preview_collapsed') === '1'; } catch(e){}
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
