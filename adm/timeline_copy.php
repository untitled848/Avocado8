<?php
$sub_menu = "301100";
include_once("./_common.php");

// 관리자만 접근 가능
if (!$is_admin) {
    alert('관리자만 접근 가능합니다.');
}

include_once(__DIR__.'/timeline_require.inc.php');

$tl_table = ses($_REQUEST, 'tl_table', '', 'string');

if (!$tl_table) {
    alert('타임라인 테이블명이 없습니다.');
}

// 원본 타임라인 정보
$timeline = sql_fetch(" SELECT * FROM {$g5['timeline_table']} WHERE tl_table = '{$tl_table}' ");
if (!$timeline['tl_table']) {
    alert('존재하지 않는 타임라인입니다.');
}

$g5['title'] = '타임라인 복사';
include_once(G5_PATH.'/head.sub.php');
?>

<script src="<?php echo G5_ADMIN_URL; ?>/admin.js?ver=<?php echo G5_JS_VER; ?>"></script>

<div class="new_win">
    <h1><?php echo $g5['title']; ?></h1>

    <form name="ftimelinecopy" id="ftimelinecopy" action="./timeline_copy_update.php" onsubmit="return ftimelinecopy_check(this);" method="post">
    <input type="hidden" name="tl_table" value="<?php echo $tl_table; ?>" id="tl_table">
    <input type="hidden" name="token" value="">

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption><?php echo $g5['title']; ?></caption>
        <tbody>
        <tr>
            <th scope="col">원본 테이블명</th>
            <td><?php echo $tl_table; ?></td>
        </tr>
        <tr>
            <th scope="col"><label for="target_table">복사 테이블명<strong class="sound_only">필수</strong></label></th>
            <td><input type="text" name="target_table" id="target_table" required class="required alnum_ frm_input" maxlength="20">영문자, 숫자, _ 만 가능 (공백없이)</td>
        </tr>
        <tr>
            <th scope="col"><label for="target_subject">타임라인 제목<strong class="sound_only">필수</strong></label></th>
            <td><input type="text" name="target_subject" value="[복사본] <?php echo h($timeline['tl_subject']); ?>" id="target_subject" required class="required frm_input" maxlength="120"></td>
        </tr>
        <tr>
            <th scope="col">복사 유형</th>
            <td>
                <input type="radio" name="copy_case" value="schema_only" id="copy_case" checked>
                <label for="copy_case">구조만</label>
                <input type="radio" name="copy_case" value="schema_data_both" id="copy_case2">
                <label for="copy_case2">구조와 데이터</label>
            </td>
        </tr>
        </tbody>
        </table>
    </div>

    <div class="btn_confirm01 btn_confirm">
        <input type="submit" class="btn_submit" value="복사">
        <input type="button" class="btn_cancel" value="창닫기" onclick="window.close();">
    </div>

    </form>

</div>

<script>
function ftimelinecopy_check(f)
{
    if (f.tl_table.value == f.target_table.value) {
        alert("원본 테이블명과 복사할 테이블명이 달라야 합니다.");
        return false;
    }
    
    if (!/^[A-Za-z0-9_]{1,20}$/.test(f.target_table.value)) {
        alert("타임라인 TABLE명은 공백없이 영문자, 숫자, _ 만 사용 가능합니다. (20자 이내)");
        return false;
    }

    return true;
}
</script>

<?php
include_once(G5_PATH.'/tail.sub.php');
?>
