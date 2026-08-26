<?php
$sub_menu = "310200";
include_once('./_common.php');
include_once(G5_EDITOR_LIB);

$w = $_GET['w'] ?? '';
$no_id = (int)($_GET['no_id'] ?? 0);

$html_title = '공지사항';
if ($w === 'u') {
    $html_title .= ' 수정';
    $no = sql_fetch("SELECT * FROM `{$g5['notice_table']}` WHERE no_id = '{$no_id}'", false);
    if (!$no) alert('등록된 공지가 없습니다.');
} else {
    $html_title .= ' 등록';
    $no = array(
        'no_id'       => 0,
        'no_subject'  => '',
        'no_subtext'  => '',
        'no_thumb'    => '',
        'no_content'  => '',
        'no_html'     => 1,
        'no_time'     => '',
        'mb_id'       => isset($member['mb_id']) ? $member['mb_id'] : '',
        'no_writer'   => '',
        'no_use'      => 1,
        'no_pinned'   => 0,
        'no_widget'   => 0,
        'no_order'    => 0,
        'no_skin'     => '',
        'nc_name'     => '',
        'no_datetime' => G5_TIME_YMDHIS,
    );
}

$g5['title'] = $html_title;
include_once(G5_ADMIN_PATH.'/admin.head.php');
?>

<div class="btn_add01 btn_add">
    <a href="./content_design_form.php" title="공지는 문서와 같은 디자인을 씁니다.">문서 디자인 설정</a>
</div>

<form name="fnoticeform" id="fnoticeform" method="post" action="./notice_form_update.php" onsubmit="return notice_form_check(this);" enctype="multipart/form-data">
<input type="hidden" name="w" value="<?php echo htmlspecialchars($w, ENT_QUOTES, 'UTF-8'); ?>">
<input type="hidden" name="no_id" value="<?php echo (int)$no['no_id']; ?>">
<input type="hidden" name="no_html" value="1">
<input type="hidden" name="token" value="">

<div class="tbl_frm01 tbl_wrap">
<table>
<colgroup><col class="grid_4"><col></colgroup>
<tbody>
<tr>
    <th scope="row"><label for="no_subject">제목</label></th>
    <td><input type="text" name="no_subject" id="no_subject" value="<?php echo htmlspecialchars($no['no_subject'], ENT_QUOTES, 'UTF-8'); ?>" required class="frm_input required" size="80" maxlength="255"></td>
</tr>
<tr>
    <th scope="row"><label for="no_subtext">부제</label></th>
    <td><input type="text" name="no_subtext" id="no_subtext" value="<?php echo htmlspecialchars($no['no_subtext'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="frm_input" size="80" maxlength="255" placeholder="목록/카드에 표시할 짧은 설명 (선택)"></td>
</tr>
<tr>
    <th scope="row">썸네일 이미지</th>
    <td>
        <?php
        $thumb_disp = $no['no_thumb'] ?? '';
        if ($thumb_disp) {
            $thumb_disp = str_replace(defined('CDN_TOKEN') ? CDN_TOKEN : '[DATA_URL]', G5_DATA_URL, $thumb_disp);
            if (function_exists('cdn_resolve_url')) $thumb_disp = cdn_resolve_url($thumb_disp);
        }
        ?>
        <?php if ($thumb_disp): ?>
        <div style="margin-bottom:6px;">
            <img src="<?php echo htmlspecialchars($thumb_disp, ENT_QUOTES, 'UTF-8'); ?>" alt="" style="max-height:80px; border:1px solid #ddd; border-radius:4px;">
        </div>
        <?php endif; ?>
        <input type="file" name="no_thumb_file" id="no_thumb_file" accept="image/*">
        <?php if ($thumb_disp): ?>
        <label style="margin-left:8px;"><input type="checkbox" name="no_thumb_delete" value="1"> 현재 이미지 삭제</label>
        <?php endif; ?>
        <p class="frm_info">jpg, jpeg, png, gif, webp 허용. 새 파일 업로드 시 기존 파일이 교체됩니다.</p>
    </td>
</tr>
<tr>
    <th scope="row"><label for="nc_name">카테고리</label></th>
    <td>
        <?php $_nc_list = function_exists('notice_categories') ? notice_categories() : array(); ?>
        <select name="nc_name" id="nc_name" class="frm_input">
            <option value="">(분류 없음)</option>
            <?php foreach ($_nc_list as $_nc): ?>
            <option value="<?php echo htmlspecialchars($_nc, ENT_QUOTES); ?>" <?php echo ((string)$no['nc_name'] === (string)$_nc) ? 'selected' : ''; ?>><?php echo htmlspecialchars($_nc, ENT_QUOTES); ?></option>
            <?php endforeach; ?>
        </select>
        <?php echo help('카테고리는 「공지사항 관리」 상단에서 추가/삭제할 수 있습니다.'); ?>
    </td>
</tr>
<tr>
    <th scope="row">내용</th>
    <td><?php echo editor_html('no_content', get_text($no['no_content'], 0)); ?></td>
</tr>
<tr>
    <th scope="row"><label for="no_skin">스킨 디렉토리</label></th>
    <td>
        <?php echo get_skin_select('content', 'no_skin', 'no_skin', isset($no['no_skin']) ? $no['no_skin'] : '', '', array('empty_label'=>'테마 프리셋에 따름','preset_type'=>'content')); ?>
        <?php echo help('공지사항은 「내용관리(문서)」와 스킨을 공유합니다. 비워두면 테마 프리셋의 「문서 디자인」 또는 글로벌 설정이 적용됩니다. 「디자인 프리셋」 그룹은 코드를 따로 두지 않고 색상·여백 등 프리셋 설정만 적용하는 옵션이며, 「코어/테마」 그룹은 해당 폴더의 스킨 코드를 사용합니다.'); ?>
    </td>
</tr>
<tr>
    <th scope="row">출력 시기</th>
    <td>
        <?php av_time_checkbox_group('no_time', $no['no_time']); ?>
    </td>
</tr>
<tr>
    <th scope="row">위젯 사용</th>
    <td>
        <label><input type="checkbox" name="no_widget" value="1" <?php echo !empty($no['no_widget']) ? 'checked' : ''; ?>> 메인 공지 위젯에 제목·본문 표시</label>
        <?php echo help('사용 중이고 현재 시기에 맞는 글만 나갑니다. 테마는 get_widget(\'notice\'), 메인 HTML은 {{NOTICE}}. 공지 문서 디자인은 적용되지 않습니다.'); ?>
    </td>
</tr>
<tr>
    <th scope="row">상단 고정</th>
    <td>
        <label><input type="checkbox" name="no_pinned" value="1" <?php echo !empty($no['no_pinned']) ? 'checked' : ''; ?>> 목록/메인박스에서 위쪽에 고정</label>
    </td>
</tr>
<tr>
    <th scope="row"><label for="no_order">출력 순서</label></th>
    <td>
        <input type="number" name="no_order" id="no_order" value="<?php echo (int)$no['no_order']; ?>" class="frm_input" size="6" step="1">
        <?php echo help('숫자가 작을수록 위에 옵니다. 같은 값이면 등록일시 역순.'); ?>
    </td>
</tr>
<tr>
    <th scope="row">사용</th>
    <td>
        <label><input type="radio" name="no_use" value="1" <?php echo $no['no_use'] ? 'checked' : ''; ?>> 사용</label>
        <label style="margin-left:10px;"><input type="radio" name="no_use" value="0" <?php echo !$no['no_use'] ? 'checked' : ''; ?>> 사용 안 함</label>
    </td>
</tr>
<?php if ($w === 'u'): ?>
<tr>
    <th scope="row">등록일시</th>
    <td><span style="color:#888;"><?php echo $no['no_datetime']; ?></span></td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>

<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="확인" class="btn_submit" accesskey="s">
    <a href="./notice_list.php">목록</a>
</div>

</form>

<script>
function notice_form_check(f) {
    var errmsg = '', errfld = null;
    <?php echo get_editor_js('no_content'); ?>
    <?php echo chk_editor_js('no_content'); ?>
    if (!f.no_subject.value) { alert('제목을 입력하세요.'); f.no_subject.focus(); return false; }
    if (!f.no_content.value) { alert('내용을 입력하세요.'); return false; }
    if (errmsg) { alert(errmsg); if (errfld) errfld.focus(); return false; }
    return true;
}
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
?>
