<?php
$sub_menu = "100500";
include_once('./_common.php');

$g5['title'] = '알림 설정';
include_once('./admin.head.php');

$interval = intval($config['cf_call_interval'] ?? 30);
$idx_rows = max(1, intval($config['cf_call_index_rows'] ?? 5));
$pg_rows  = max(1, intval($config['cf_call_page_rows']  ?? 20));
$memo_del = intval($config['cf_memo_del'] ?? 0);

$rows = sql_query("SELECT * FROM `{$g5['call_type_map_table']}` ORDER BY ct_system DESC, ct_id ASC");

if (isset($_GET['saved'])) echo '<div style="background:#e8ffe8;border:1px solid #cde6cd;padding:10px;margin-bottom:10px;">설정이 저장되었습니다.</div>';
?>

<form name="fform" id="fform" method="post" action="./config_call_form_update.php" autocomplete="off">
<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">

<section>
<h2 class="h2_frm">알림 종류</h2>
<p class="frm_info" style="margin:5px 0 10px;text-align:left;">각 알림 종류의 켜짐/꺼짐, 이름, 기본 메모, 기본 링크를 한 줄에서 편집합니다. 새로운 알림 종류는 모듈에서 등록됩니다.</p>

<div class="tbl_head01 tbl_wrap">
<table>
    <colgroup>
        <col>
        <col>
        <col>
        <col style="width:60px">
        <col style="width:60px">
    </colgroup>
    <thead>
        <tr>
            <th scope="col">이름</th>
            <th scope="col">기본 메모</th>
            <th scope="col">기본 링크</th>
            <th scope="col">사용</th>
            <th scope="col">삭제</th>
        </tr>
    </thead>
    <tbody>
<?php
$idx = 0;
while ($r = sql_fetch_array($rows)):
    $ct_id = intval($r['ct_id']);
    $is_sys = !empty($r['ct_system']);
?>
        <tr>
            <td style="text-align:left;">
                <input type="hidden" name="ct_ids[]" value="<?php echo $ct_id; ?>">
                <input type="hidden" name="ct_code_<?php echo $ct_id; ?>" value="<?php echo htmlspecialchars($r['ct_code']); ?>">
                <input type="text" name="ct_name_<?php echo $ct_id; ?>" value="<?php echo htmlspecialchars($r['ct_name']); ?>" class="frm_input" style="width:100%;" maxlength="50">
            </td>
            <td style="text-align:left;"><input type="text" name="ct_memo_<?php echo $ct_id; ?>" value="<?php echo htmlspecialchars($r['ct_memo']); ?>" class="frm_input" style="width:100%;" maxlength="255"></td>
            <td style="text-align:left;"><input type="text" name="ct_link_<?php echo $ct_id; ?>" value="<?php echo htmlspecialchars($r['ct_link']); ?>" class="frm_input" style="width:100%;" maxlength="255"></td>
            <td style="text-align:center;"><label><input type="checkbox" name="ct_use_<?php echo $ct_id; ?>" value="1" <?php echo $r['ct_use'] ? 'checked' : ''; ?>></label></td>
            <td style="text-align:center;">
<?php if (!$is_sys): ?>
                <label><input type="checkbox" name="ct_del_<?php echo $ct_id; ?>" value="1"></label>
<?php else: ?>
                <span style="color:#bbb;" title="시스템 종류는 삭제할 수 없습니다">-</span>
<?php endif; ?>
            </td>
        </tr>
<?php
    $idx++;
endwhile;
if ($idx === 0): ?>
        <tr><td colspan="5" class="empty_table">등록된 알림 종류가 없습니다.</td></tr>
<?php endif; ?>
    </tbody>
</table>
</div>
</section>

<section style="margin-top:20px;">
<h2 class="h2_frm">알림 기본 설정</h2>
<div class="tbl_frm01 tbl_wrap">
<table>
    <colgroup><col class="grid_4"><col></colgroup>
    <tbody>
        <tr>
            <th scope="row"><label for="cf_call_interval">확인 간격(초)</label></th>
            <td>
                <input type="number" name="cf_call_interval" id="cf_call_interval" value="<?php echo $interval; ?>" min="5" max="600" class="frm_input"> 초 (최소 5초)
                <p class="frm_info">새 알림을 자동 확인하는 주기. 짧을수록 서버 부하가 증가합니다.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_call_index_rows">마이페이지 요약 갯수</label></th>
            <td>
                <input type="number" name="cf_call_index_rows" id="cf_call_index_rows" value="<?php echo $idx_rows; ?>" min="1" max="100" class="frm_input"> 건
                <p class="frm_info">마이페이지 메인의 호출내역 박스에 보여줄 최신 알림 갯수입니다.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_call_page_rows">전체보기 페이지당 갯수</label></th>
            <td>
                <input type="number" name="cf_call_page_rows" id="cf_call_page_rows" value="<?php echo $pg_rows; ?>" min="1" max="200" class="frm_input"> 건
                <p class="frm_info">/mypage/call.php 전체 알림 페이지의 한 페이지당 표시 갯수입니다.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_memo_del">알림/쪽지 보관 기간</label></th>
            <td>
                <input type="number" name="cf_memo_del" id="cf_memo_del" value="<?php echo $memo_del; ?>" min="0" max="3650" class="frm_input"> 일 (0 = 영구 보관)
                <p class="frm_info">읽은 알림/쪽지를 자동으로 정리할 기간입니다.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_use_memo_emoticon">쪽지 이모티콘 사용</label></th>
            <td>
                <?php $memo_emo = !empty($config['cf_use_memo_emoticon']) ? 1 : 0; ?>
                <input type="checkbox" name="cf_use_memo_emoticon" id="cf_use_memo_emoticon" value="1" <?php echo $memo_emo ? 'checked' : ''; ?>> 사용
                <p class="frm_info">쪽지 본문에서 이모티콘 명령어를 등록된 이미지로 자동 변환합니다.</p>
            </td>
        </tr>
    </tbody>
</table>
</div>
</section>

<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="설정 저장" class="btn_submit">
</div>
</form>

<?php include_once('./admin.tail.php'); ?>
