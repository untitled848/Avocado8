<?php
$sub_menu = '110200';
include_once('./_common.php');
include_once('./_theme_upload.inc.php');

auth_check($auth[$sub_menu], 'r');

if (!$is_admin) {
    alert('관리자만 접근 가능합니다.');
}

$thm_token = get_admin_token();

$g5['title'] = '테마 프리셋 설정';
include_once('./admin.head.php');
$design_hub = 'preset';
$design_hub_tab = 'upload';
include_once('./_design_hub_tabs.inc.php');

$theme_root = theme_upload_root();
$themes = array();
if (is_dir($theme_root)) {
    foreach (scandir($theme_root) as $d) {
        if ($d === '' || $d[0] === '.') continue;
        $dir = $theme_root.'/'.$d;
        if (!is_dir($dir)) continue;
        if (theme_upload_is_protected($d)) continue;

        $slots = array();
        if (function_exists('theme_supported_slots')) {
            $slots = theme_supported_slots($d);
        }

        $themes[$d] = array(
            'has_markers' => theme_upload_has_markers($dir),
            'slots'       => $slots,
        );
    }
    ksort($themes, SORT_STRING);
}

$used = array();
if (!empty($g5['theme_preset_slot_table']) && !empty($g5['theme_preset_table'])) {
    $sql = "SELECT s.ps_theme, s.ps_slot, p.pr_id, p.pr_name
              FROM `{$g5['theme_preset_slot_table']}` s
              LEFT JOIN `{$g5['theme_preset_table']}` p ON p.pr_id = s.pr_id
             WHERE s.ps_theme <> '' AND s.ps_theme <> 'default'";
    $r = sql_query($sql, false);
    if ($r) {
        while ($row = sql_fetch_array($r)) {
            $tn = trim((string)$row['ps_theme']);
            if ($tn === '') continue;
            $used[$tn][] = $row;
        }
    }
}

$slot_labels = isset($GLOBALS['THEME_SLOT_MAP']) ? $GLOBALS['THEME_SLOT_MAP'] : array();
?>

<?php if (!empty($_GET['msg'])) { ?>
<div class="ml-msg">✓ <?php echo h($_GET['msg']); ?></div>
<?php } ?>

<div class="local_desc02 local_desc">
    <p>
        테마는 <code>theme/테마이름/</code> 에 설치됩니다.
        업로드한 테마는 <a href="./theme_preset_list.php">테마 프리셋</a>에서 슬롯별로 고를 수 있습니다.
        <code>default</code> 는 루트 파일을 쓰는 가상 테마이며 업로드/삭제 대상이 아닙니다.
    </p>
</div>

<?php if ($is_admin): ?>
<form method="post" enctype="multipart/form-data" action="./theme_upload.php"
      onsubmit="return confirm('tar.gz 파일을 업로드하여 새 테마를 설치합니다. 진행할까요?');"
      class="ml-upload">
    <input type="hidden" name="token" value="<?php echo $thm_token; ?>">
    <strong>새 테마 설치 (tar.gz 업로드)</strong>
    <input type="file" name="theme_pkg" accept=".gz,application/gzip,application/x-gzip" required>
    <button type="submit" class="btn_01" style="padding:6px 12px;font-size:12px;">설치</button>
    <span class="hint">
        tar.gz 안에 단일 폴더가 있어야 하고, 그 안에 <code>head.php</code> / <code>menu.php</code> / <code>css/</code> / <code>skin/</code> 같은 테마 파일이 있어야 합니다.
        예: <code>tar -czf dark.tar.gz dark</code>
    </span>
</form>
<?php endif; ?>

<div class="local_ov01 local_ov">
    설치된 테마 <strong><?php echo number_format(count($themes)); ?></strong>개
</div>

<div class="tbl_head01 tbl_wrap">
<table>
<caption>테마 목록</caption>
<colgroup>
    <col style="width:160px">
    <col>
    <col>
    <col style="width:120px">
</colgroup>
<thead>
<tr>
    <th scope="col">테마 이름</th>
    <th scope="col">지원 슬롯</th>
    <th scope="col">사용 중인 프리셋</th>
    <th scope="col">관리</th>
</tr>
</thead>
<tbody>
<?php if (empty($themes)): ?>
<tr><td colspan="4" class="empty_table">설치된 테마 폴더가 없습니다. 위에서 tar.gz 를 업로드하세요.</td></tr>
<?php else: foreach ($themes as $name => $info):
    $rows = isset($used[$name]) ? $used[$name] : array();
    $seen = array();
    $preset_links = array();
    foreach ($rows as $u) {
        $pid = (int)$u['pr_id'];
        if ($pid <= 0 || isset($seen[$pid])) continue;
        $seen[$pid] = true;
        $label = ($u['pr_name'] !== '' ? $u['pr_name'] : ('#'.$pid));
        $preset_links[] = '<a href="./theme_preset_form.php?pr_id='.$pid.'">'.h($label).'</a>';
    }

    $slot_show = array();
    foreach ($info['slots'] as $slot) {
        $lab = isset($slot_labels[$slot]['label']) ? $slot_labels[$slot]['label'] : $slot;
        $slot_show[] = h($lab);
    }
?>
<tr>
    <td class="td_left"><strong><?php echo h($name); ?></strong></td>
    <td class="td_left" style="font-size:12px;color:#555;">
        <?php
        if (!$info['has_markers']) {
            echo '<span style="color:#c33;">테마 파일 없음</span>';
        } elseif (empty($slot_show)) {
            echo '<span style="color:#888;">(슬롯 없음)</span>';
        } else {
            echo implode(', ', $slot_show);
        }
        ?>
    </td>
    <td class="td_left" style="font-size:12px;">
        <?php echo empty($preset_links) ? '<span style="color:#888;">—</span>' : implode(', ', $preset_links); ?>
    </td>
    <td class="td_center">
        <?php
            $warn = '테마 폴더 theme/'.$name.' 를 삭제합니다.\\n되돌릴 수 없습니다.';
            if (!empty($seen)) {
                $warn .= '\\n이 테마를 쓰는 프리셋이 있습니다. 삭제하면 해당 슬롯이 깨질 수 있습니다.';
            }
            $warn .= '\\n계속할까요?';
        ?>
        <a href="./theme_upload_delete.php?theme=<?php echo urlencode($name); ?>&amp;token=<?php echo urlencode($thm_token); ?>"
           class="btn_frmline"
           style="color:#c33;"
           onclick="return confirm('<?php echo $warn; ?>');">폴더 삭제</a>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>

<div class="local_desc02 local_desc">
    <p>
        <strong>패키징 예 (PowerShell / bash)</strong> —
        테마 폴더의 <em>부모</em>에서 압축합니다.
        <code>cd theme</code> 후 <code>tar -czf dark.tar.gz dark</code>
    </p>
</div>

<?php
include_once('./admin.tail.php');
