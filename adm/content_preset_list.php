<?php
$sub_menu = "110200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');
if (!$is_admin) alert('관리자만 접근 가능합니다.');

$g5['title'] = '테마 프리셋 설정';
include_once(G5_ADMIN_PATH.'/admin.head.php');
$design_hub = 'preset';
$design_hub_tab = 'content';
include_once(G5_ADMIN_PATH.'/_design_hub_tabs.inc.php');

$result = sql_query("SELECT * FROM `{$g5['content_preset_table']}` ORDER BY pd_order, pd_id");
$presets = array();
while ($row = sql_fetch_array($result)) { $presets[] = $row; }

// 문서 디자인 → 사용 중인 테마 프리셋 맵 (pr_content_design 컬럼이 있을 때만)
$linked = array();
$col = sql_fetch("SHOW COLUMNS FROM `{$g5['theme_preset_table']}` LIKE 'pr_content_design'", false);
if (!empty($col['Field'])) {
    $r = sql_query("SELECT pr_id, pr_name, pr_content_design FROM `{$g5['theme_preset_table']}` ORDER BY pr_order, pr_id", false);
    if ($r) {
        while ($t = sql_fetch_array($r)) {
            if (strpos((string)$t['pr_content_design'], 'skin:') === 0) continue;
            $key = ($t['pr_content_design'] === '' || $t['pr_content_design'] === 'default' || (int)$t['pr_content_design'] === 0) ? '1' : (string)(int)$t['pr_content_design'];
            $linked[$key][] = $t;
        }
    }
}

$active_pd_id = function_exists('get_active_design') ? (int)get_active_design('content') : 1;

include_once(G5_ADMIN_PATH.'/_preset_list_style.php');
?>

<div class="pl-wrap">

<?php
$design_type = 'content';
include G5_ADMIN_PATH.'/_design_global_box.inc.php';
?>

<div class="pl-help">
    <strong>문서 디자인 프리셋이란?</strong> 내용관리(문서) 페이지의 배경·꾸밈 이미지·콘텐츠 영역 스타일을 묶어 관리하는 단위입니다.
    여러 묶음을 만들어 두고 <a href="./theme_preset_list.php">테마 프리셋</a>에서 골라 쓰면 시기·이벤트별로 분위기를 손쉽게 바꿀 수 있습니다.
    행의 <span class="pl-active-mark">★</span> 표시는 현재 사이트에 적용 중인 프리셋입니다.
</div>

<form method="get" action="./content_preset_action.php" class="pl-add-form" onsubmit="return plAddCheck(this);">
    <input type="hidden" name="action" value="create">
    <input type="text" name="pd_name" placeholder="새 문서 디자인 프리셋 이름" required>
    <button type="submit" class="ui-btn admin">+ 새 프리셋</button>
</form>

<table class="pl-table">
<thead>
<tr>
    <th class="pl-col-id">ID</th>
    <th>프리셋 이름 (편집 후 저장)</th>
    <th class="pl-col-linked">사용 중인 테마 프리셋</th>
    <th class="pl-col-actions">관리</th>
</tr>
</thead>
<tbody>
<?php foreach ($presets as $pr):
    $pid = (int)$pr['pd_id'];
    $is_active = ($pid === $active_pd_id);
    $themes = isset($linked[(string)$pid]) ? $linked[(string)$pid] : array();
?>
<tr<?php echo $is_active ? ' class="pl-active-row"' : ''; ?>>
    <td class="pl-id"><?php echo $pid; ?><?php if ($is_active): ?><span class="pl-active-mark" title="현재 사이트에 적용 중">★</span><?php endif; ?></td>
    <td>
        <form method="post" action="./content_preset_action.php" class="pl-rename-form">
            <input type="hidden" name="action" value="rename">
            <input type="hidden" name="pd_id" value="<?php echo $pid; ?>">
            <input type="text" name="pd_name" value="<?php echo h($pr['pd_name']); ?>" class="pl-name-input" maxlength="100">
            <button type="submit" class="ui-btn small">저장</button>
            <?php if ($pid == 1): ?> <span class="pl-tag">기본</span><?php endif; ?>
        </form>
    </td>
    <td class="pl-linked">
        <?php if (empty($themes)): ?>
            <span class="pl-muted">— 사용 중인 테마 없음 —</span>
        <?php else: foreach ($themes as $t): ?>
            <a class="pl-badge" href="./theme_preset_form.php?pr_id=<?php echo (int)$t['pr_id']; ?>">#<?php echo (int)$t['pr_id']; ?> <?php echo h($t['pr_name']); ?></a>
        <?php endforeach; endif; ?>
    </td>
    <td class="pl-actions">
        <a href="./content_design_form.php?pd_id=<?php echo $pid; ?>" class="ui-btn small">디자인 편집</a>
        <a href="#" onclick="plCopy(<?php echo $pid; ?>, '<?php echo addslashes($pr['pd_name']); ?>'); return false;" class="ui-btn small">복제</a>
        <?php if ($pid != 1): ?>
        <a href="./content_preset_action.php?action=delete&amp;pd_id=<?php echo $pid; ?>" class="ui-btn small pl-danger" onclick="return confirm('이 프리셋과 관련 설정이 모두 삭제됩니다. 계속하시겠습니까?');">삭제</a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (empty($presets)): ?>
<tr><td colspan="4" class="empty_table">프리셋이 없습니다.</td></tr>
<?php endif; ?>
</tbody>
</table>

</div>

<script>
function plAddCheck(f){ if(!f.pd_name.value.trim()){ alert('이름을 입력하세요.'); return false; } return true; }
function plCopy(pd_id, orig_name){
    var nm = prompt('복제본 이름?', orig_name + '_copy');
    if (!nm) return;
    location.href = './content_preset_action.php?action=copy&pd_id=' + pd_id + '&pd_name=' + encodeURIComponent(nm);
}
</script>

<?php include_once(G5_ADMIN_PATH.'/admin.tail.php'); ?>
