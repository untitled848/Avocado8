<?php
$sub_menu = "520020";
include_once('./_common.php');

$cp_id = isset($_GET['cp_id']) ? (int)$_GET['cp_id'] : 0;
$is_new = ($cp_id <= 0);
$cp = array(
    'cp_id'          => 0,
    'cp_name'        => '',
    'cp_use'         => 1,
    'cp_use_request' => 0,
    'cp_payload'     => '',
    'cp_memo'        => '',
    'cp_order'       => 0,
);
$payload_initial = array();
if (!$is_new) {
    $row = sql_fetch("SELECT * FROM `{$g5['calc_preset_table']}` WHERE cp_id = '{$cp_id}'");
    if (empty($row['cp_id'])) alert('등록된 프리셋이 없습니다.', G5_ADMIN_URL.'/calc_preset_list.php');
    $cp = array_merge($cp, $row);
    $payload_initial = function_exists('payout_preset_decode') ? payout_preset_decode($row['cp_payload']) : array();
}

$g5['title'] = '정산 프리셋 ' . ($is_new ? '등록' : '수정');
include_once(G5_ADMIN_PATH.'/admin.head.php');
?>
<form name="fpreset" action="./calc_preset_form_update.php" method="post">
<?php echo check_admin_token(); ?>
<input type="hidden" name="cp_id" value="<?php echo (int)$cp['cp_id']; ?>">

<div class="tbl_frm01 tbl_wrap">
<table>
    <colgroup><col style="width:130px"><col></colgroup>
    <tbody>
        <tr>
            <th><label>이름</label></th>
            <td><input type="text" name="cp_name" value="<?php echo htmlspecialchars($cp['cp_name'], ENT_QUOTES, 'UTF-8'); ?>" required class="frm_input" style="width:60%"></td>
        </tr>
        <tr>
            <th><label>사용</label></th>
            <td>
                <label><input type="checkbox" name="cp_use" value="1" <?php echo !empty($cp['cp_use']) ? 'checked' : ''; ?>> 활성</label>
                <label style="margin-left:20px"><input type="checkbox" name="cp_use_request" value="1" <?php echo !empty($cp['cp_use_request']) ? 'checked' : ''; ?>> 정산 신청 (calculate 게시판 신청 폼에 노출)</label>
            </td>
        </tr>
        <tr>
            <th><label>기본 정산내용</label></th>
            <td><input type="text" name="cp_memo" value="<?php echo htmlspecialchars($cp['cp_memo'], ENT_QUOTES, 'UTF-8'); ?>" class="frm_input" style="width:60%" placeholder="(옵션) 정산 시 memo 미입력 시 사용"></td>
        </tr>
        <tr>
            <th><label>정렬</label></th>
            <td><input type="number" name="cp_order" value="<?php echo (int)$cp['cp_order']; ?>" class="frm_input" style="width:80px"></td>
        </tr>
        <tr>
            <th><label>지급 항목</label></th>
            <td><?php $payload_field='payload'; $payload_uid='preset'; include __DIR__.'/_partial/calc_payload_editor.inc.php'; ?></td>
        </tr>
    </tbody>
</table>
</div>

<div class="btn_confirm01 btn_confirm">
    <a href="./calc_preset_list.php" class="btn btn_02">목록</a>
    <button type="submit" class="btn_submit btn">저장</button>
</div>
</form>

<?php include_once(G5_ADMIN_PATH.'/admin.tail.php'); ?>
