<?php
$sub_menu = '301100';
include_once('./_common.php');
include_once('./_timeline_skin.inc.php');

auth_check($auth[$sub_menu], 'r');

if (!$is_admin) {
    alert('관리자만 접근 가능합니다.');
}

include_once(__DIR__.'/timeline_require.inc.php');

$tlsk_token = get_admin_token();

$g5['title'] = '타임라인 관리';
include_once('./admin.head.php');
$mgmt_hub = 'timeline';
$mgmt_hub_tab = 'skin';
include_once('./_mgmt_hub_tabs.inc.php');

$skin_root = timeline_skin_root();
$skins = array();
if (is_dir($skin_root)) {
    foreach (scandir($skin_root) as $d) {
        if ($d === '' || $d[0] === '.') continue;
        $dir = $skin_root.'/'.$d;
        if (!is_dir($dir)) continue;

        $files = array();
        foreach (timeline_skin_required_files() as $f) {
            if (is_file($dir.'/'.$f)) $files[] = $f;
        }
        $php_count = 0;
        $items = @scandir($dir);
        if ($items) {
            foreach ($items as $it) {
                if ($it === '.' || $it === '..') continue;
                if (is_file($dir.'/'.$it) && preg_match('/\.php$/i', $it)) $php_count++;
            }
        }
        $skins[$d] = array(
            'files'     => $files,
            'php_count' => $php_count,
            'core'      => timeline_skin_is_protected($d),
        );
    }
    ksort($skins, SORT_STRING);
}

$used = array();
if (!empty($g5['timeline_table'])) {
    $r = sql_query("SELECT tl_table, tl_subject, tl_skin FROM `{$g5['timeline_table']}`", false);
    if ($r) {
        while ($row = sql_fetch_array($r)) {
            $sk = isset($row['tl_skin']) ? trim((string)$row['tl_skin']) : '';
            if ($sk === '') $sk = 'basic';
            $used[$sk][] = array(
                'tl_table'   => $row['tl_table'],
                'tl_subject' => $row['tl_subject'],
            );
        }
    }
}
?>

<?php if (!empty($_GET['msg'])) { ?>
<div class="ml-msg">✓ <?php echo h($_GET['msg']); ?></div>
<?php } ?>

<div class="local_desc02 local_desc">
    <p>
        타임라인 스킨은 <code>skin/timeline/스킨이름/</code> 에 설치됩니다.
        업로드한 스킨은 타임라인 설정의 <strong>스킨</strong>에서 고를 수 있습니다.
        기본 제공 스킨(<?php echo h(implode(', ', timeline_skin_protected_names())); ?>)은 덮어쓰거나 삭제할 수 없습니다.
    </p>
</div>

<?php if ($is_admin): ?>
<form method="post" enctype="multipart/form-data" action="./timeline_skin_upload.php"
      onsubmit="return confirm('tar.gz 파일을 업로드하여 새 타임라인 스킨을 설치합니다. 진행할까요?');"
      class="ml-upload">
    <input type="hidden" name="token" value="<?php echo $tlsk_token; ?>">
    <strong>새 타임라인 스킨 설치 (tar.gz 업로드)</strong>
    <input type="file" name="skin_pkg" accept=".gz,application/gzip,application/x-gzip" required>
    <button type="submit" class="btn_01" style="padding:6px 12px;font-size:12px;">설치</button>
    <span class="hint">
        tar.gz 안에 단일 폴더가 있어야 하고, 그 안에 <code>write.skin.php</code> / <code>item.skin.php</code> / <code>view_detail.skin.php</code> 중 하나 이상이 있어야 합니다.
        예: <code>tar -czf myskin.tar.gz myskin</code>
    </span>
</form>
<?php endif; ?>

<div class="local_ov01 local_ov">
    설치된 타임라인 스킨 <strong><?php echo number_format(count($skins)); ?></strong>개
</div>

<div class="tbl_head01 tbl_wrap">
<table>
<caption>타임라인 스킨 목록</caption>
<colgroup>
    <col style="width:180px">
    <col style="width:110px">
    <col>
    <col>
    <col style="width:120px">
</colgroup>
<thead>
<tr>
    <th scope="col">스킨 이름</th>
    <th scope="col">종류</th>
    <th scope="col">스킨 파일</th>
    <th scope="col">사용 중인 타임라인</th>
    <th scope="col">관리</th>
</tr>
</thead>
<tbody>
<?php if (empty($skins)): ?>
<tr><td colspan="5" class="empty_table">설치된 타임라인 스킨이 없습니다.</td></tr>
<?php else: foreach ($skins as $name => $info):
    $tls = isset($used[$name]) ? $used[$name] : array();
    $seen = array();
    $tl_links = array();
    foreach ($tls as $t) {
        if (isset($seen[$t['tl_table']])) continue;
        $seen[$t['tl_table']] = true;
        $label = ($t['tl_subject'] !== '' ? $t['tl_subject'] : $t['tl_table']);
        $tl_links[] = '<a href="./timeline_form.php?w=u&amp;tl_table='.urlencode($t['tl_table']).'">'.h($label).'</a>';
    }
?>
<tr>
    <td class="td_left"><strong><?php echo h($name); ?></strong></td>
    <td class="td_center">
        <?php if ($info['core']): ?>
        <span style="display:inline-block;font-size:11px;padding:2px 6px;border-radius:3px;background:#eee;color:#666;">기본 제공</span>
        <?php else: ?>
        업로드
        <?php endif; ?>
    </td>
    <td class="td_left" style="font-size:12px;color:#555;">
        <?php
        if (!empty($info['files'])) echo h(implode(', ', $info['files']));
        else echo '<span style="color:#c33;">스킨 PHP 없음</span>';
        echo ' <span style="color:#888;">(php '. (int)$info['php_count'].')</span>';
        ?>
    </td>
    <td class="td_left" style="font-size:12px;">
        <?php echo empty($tl_links) ? '<span style="color:#888;">—</span>' : implode(', ', $tl_links); ?>
    </td>
    <td class="td_center">
        <?php if ($info['core']): ?>
        <span style="color:#888;">—</span>
        <?php else:
            $warn = '스킨 폴더 skin/timeline/'.$name.' 를 삭제합니다.\\n되돌릴 수 없습니다.';
            if (!empty($seen)) {
                $warn .= '\\n이 스킨을 쓰는 타임라인이 있습니다. 삭제하면 해당 화면이 깨질 수 있습니다.';
            }
            $warn .= '\\n계속할까요?';
        ?>
        <a href="./timeline_skin_delete.php?skin=<?php echo urlencode($name); ?>&amp;token=<?php echo urlencode($tlsk_token); ?>"
           class="btn_frmline"
           style="color:#c33;"
           onclick="return confirm('<?php echo $warn; ?>');">폴더 삭제</a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>

<div class="local_desc02 local_desc">
    <p>
        <strong>패키징 예 (PowerShell / bash)</strong> —
        스킨 폴더의 <em>부모</em>에서 압축합니다.
        <code>cd skin/timeline</code> 후 <code>tar -czf myskin.tar.gz myskin</code>
    </p>
</div>

<?php
include_once('./admin.tail.php');
