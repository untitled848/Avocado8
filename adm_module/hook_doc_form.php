<?php
/**
 * 훅 설명 편집
 */
$sub_menu = '001200';
include_once('./_common.php');

$hr_id = (int)($_REQUEST['hr_id'] ?? 0);
if (!$hr_id) alert('잘못된 접근', G5_MDL_ADMIN_URL.'/hook_registry.php');

global $g5;
$tbl = $g5['hook_registry_table'];
$row = sql_fetch("SELECT * FROM `{$tbl}` WHERE hr_id = {$hr_id}");
if (!$row) alert('해당 훅이 없습니다.', G5_MDL_ADMIN_URL.'/hook_registry.php');

// 저장
if (isset($_POST['act_button'])) {
    check_admin_token();
    $doc = isset($_POST['hr_doc']) ? trim($_POST['hr_doc']) : '';
    mdl_hook_registry_doc($hr_id, $doc);
    goto_url(G5_MDL_ADMIN_URL.'/hook_registry.php');
}

$g5['title'] = '훅 설명 편집 — '.$row['hr_name'];
include_once(G5_MDL_ADMIN_PATH.'/admin.head.php');
?>
<div id="frm_wrap">
<form name="fhookdoc" id="fhookdoc" method="post" action="<?php echo G5_MDL_ADMIN_URL; ?>/hook_doc_form.php">
<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
<input type="hidden" name="hr_id" value="<?php echo $hr_id; ?>">

<table class="tbl_write02 tbl_wrap">
<caption><?php echo $g5['title']; ?></caption>
<colgroup>
    <col style="width:20%"><col>
</colgroup>
<tbody>
<tr>
    <th>훅 이름</th>
    <td><?php echo htmlspecialchars($row['hr_name'], ENT_QUOTES); ?></td>
</tr>
<tr>
    <th>타입 / 스코프</th>
    <td><?php echo htmlspecialchars($row['hr_type']); ?> / <?php echo htmlspecialchars($row['hr_scope']); ?></td>
</tr>
<tr>
    <th>위치</th>
    <td><?php echo htmlspecialchars($row['hr_file']); ?>:<?php echo (int)$row['hr_line']; ?></td>
</tr>
<tr>
    <th>파라미터</th>
    <td><?php echo htmlspecialchars($row['hr_param_signature']); ?></td>
</tr>
<tr>
    <th>설명 (hr_doc)</th>
    <td>
        <textarea name="hr_doc" rows="8" style="width:98%"><?php echo htmlspecialchars((string)$row['hr_doc'], ENT_QUOTES); ?></textarea>
        <small class="frm_info">마크다운 사용 가능. 코드 스캔 시에는 덮어쓰지 않습니다.</small>
    </td>
</tr>
</tbody>
</table>

<div class="btn_confirm">
    <input type="submit" name="act_button" value="저장" class="btn_submit btn">
    <a href="<?php echo G5_MDL_ADMIN_URL; ?>/hook_registry.php" class="btn btn_02">취소</a>
</div>
</form>
</div>

<?php include_once(G5_MDL_ADMIN_PATH.'/admin.tail.php'); ?>
