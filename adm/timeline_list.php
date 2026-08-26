<?php
$sub_menu = "301100";
include_once('./_common.php');

// 관리자만 접근 가능
if (!$is_admin) {
    alert('관리자만 접근 가능합니다.');
}

include_once(__DIR__.'/timeline_require.inc.php');

$sql_common = " FROM {$g5['timeline_table']} ";
$sql_search = " WHERE (1) ";

if ($stx) {
    $sql_search .= " AND ( ";
    switch ($sfl) {
        case "tl_table":
            $sql_search .= " ({$sfl} LIKE '{$stx}%') ";
            break;
        default:
            $sql_search .= " ({$sfl} LIKE '%{$stx}%') ";
            break;
    }
    $sql_search .= " ) ";
}

if (!$sst) {
    $sst = "tl_table";
    $sod = "asc";
}
$sql_order = " ORDER BY {$sst} {$sod} ";

$sql = " SELECT COUNT(*) AS cnt {$sql_common} {$sql_search} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = ses($config, 'cf_page_rows', 15, 'int');
if ($rows < 1) $rows = 15;
$total_page = ceil($total_count / $rows);
if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지
$from_record = ($page - 1) * $rows;

$sql = " SELECT * {$sql_common} {$sql_search} {$sql_order} LIMIT {$from_record}, {$rows} ";
$result = sql_query($sql);

$listall = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="ov_listall">전체목록</a>';

$g5['title'] = '타임라인 관리';
include_once('./admin.head.php');
$mgmt_hub = 'timeline';
$mgmt_hub_tab = 'list';
include_once('./_mgmt_hub_tabs.inc.php');

$colspan = 13;
?>

<div class="local_ov01 local_ov">
    <?php echo $listall; ?>
    생성된 타임라인 수 <?php echo number_format($total_count); ?>개
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
    <option value="tl_table"<?php echo get_selected($sfl, "tl_table", true); ?>>TABLE</option>
    <option value="tl_subject"<?php echo get_selected($sfl, "tl_subject"); ?>>제목</option>
</select>
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx; ?>" id="stx" required class="required frm_input">
<input type="submit" value="검색" class="btn_submit">
</form>

<div class="btn_add01 btn_add">
    <a href="./timeline_form.php" id="tl_add">타임라인 추가</a>
    <a href="./db_check.php">사이트 DB 점검/보정</a>
</div>

<form name="ftimelinelist" id="ftimelinelist" action="./timeline_list_update.php" onsubmit="return ftimelinelist_submit(this);" method="post">
<input type="hidden" name="sst" value="<?php echo $sst; ?>">
<input type="hidden" name="sod" value="<?php echo $sod; ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
<input type="hidden" name="stx" value="<?php echo $stx; ?>">
<input type="hidden" name="page" value="<?php echo $page; ?>">
<input type="hidden" name="token" value="<?php echo $token; ?>">

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?> 목록</caption>
    <thead>
    <tr>
        <th scope="col">
            <label for="chkall" class="sound_only">타임라인 전체</label>
            <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
        </th>
        <th scope="col"><?php echo subject_sort_link('tl_table'); ?>TABLE</a></th>
        <th scope="col"><?php echo subject_sort_link('tl_subject'); ?>제목</a></th>
        <th scope="col"><?php echo subject_sort_link('tl_skin'); ?>스킨</a></th>
        <th scope="col">열람</th>
        <th scope="col">쓰기</th>
        <th scope="col">사용자</th>
        <th scope="col">이미지</th>
        <th scope="col">글자수</th>
        <th scope="col">글P</th>
        <th scope="col"><?php echo subject_sort_link('tl_count_write'); ?>글 수</a></th>
        <th scope="col"><?php echo subject_sort_link('tl_count_comment'); ?>댓글 수</a></th>
        <th scope="col">관리</th>
    </tr>
    </thead>
    <tbody>
    <?php
    for ($i = 0; $row = sql_fetch_array($result); $i++) {
        $one_update = '<a href="./timeline_form.php?w=u&amp;tl_table='.$row['tl_table'].'&amp;'.$qstr.'">수정</a>';
        $one_user = '<a href="./timeline_user.php?tl_table='.$row['tl_table'].'" class="btn_admin_small">관리</a>';
        $one_copy = '<a href="./timeline_copy.php?tl_table='.$row['tl_table'].'" class="timeline_copy" target="win_timeline_copy">복사</a>';
        $one_view = '<a href="'.G5_URL.'/timeline/?tl_table='.$row['tl_table'].'" target="_blank">바로가기</a>';

        $bg = 'bg'.($i % 2);
    ?>
    <tr class="<?php echo $bg; ?>">
        <td>
            <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo get_text($row['tl_subject']); ?></label>
            <input type="checkbox" name="chk[]" value="<?php echo $i; ?>" id="chk_<?php echo $i; ?>">
        </td>
        <td>
            <input type="hidden" name="tl_table[<?php echo $i; ?>]" value="<?php echo $row['tl_table']; ?>">
            <a href="<?php echo G5_URL; ?>/timeline/?tl_table=<?php echo $row['tl_table']; ?>" target="_blank"><?php echo $row['tl_table']; ?></a>
        </td>
        <td>
            <label for="tl_subject_<?php echo $i; ?>" class="sound_only">타임라인 제목<strong class="sound_only"> 필수</strong></label>
            <input type="text" name="tl_subject[<?php echo $i; ?>]" value="<?php echo get_text($row['tl_subject']); ?>" id="tl_subject_<?php echo $i; ?>" required class="required frm_input" size="20">
        </td>
        <td>
            <label for="tl_skin_<?php echo $i; ?>" class="sound_only">스킨</label>
            <?php echo get_skin_select('timeline', 'tl_skin_'.$i, "tl_skin[$i]", $row['tl_skin']); ?>
        </td>
        <td>
            <label for="tl_read_level_<?php echo $i; ?>" class="sound_only">열람 권한</label>
            <select name="tl_read_level[<?php echo $i; ?>]" id="tl_read_level_<?php echo $i; ?>" class="frm_input">
                <option value="1"<?php echo get_selected($row['tl_read_level'], 1); ?>>전체</option>
                <option value="2"<?php echo get_selected($row['tl_read_level'], 2); ?>>회원</option>
                <option value="3"<?php echo get_selected($row['tl_read_level'], 3); ?>>사용자</option>
                <option value="10"<?php echo get_selected($row['tl_read_level'], 10); ?>>운영자</option>
            </select>
        </td>
        <td>
            <label for="tl_write_level_<?php echo $i; ?>" class="sound_only">쓰기 권한</label>
            <select name="tl_write_level[<?php echo $i; ?>]" id="tl_write_level_<?php echo $i; ?>" class="frm_input">
                <option value="1"<?php echo get_selected($row['tl_write_level'], 1); ?>>모든 사용자</option>
                <option value="2"<?php echo get_selected($row['tl_write_level'], 2); ?>>운영자만</option>
            </select>
        </td>
        <td>
            <?php echo $one_user; ?>
            <br>
            <?php
                $user_cnt = count_timeline_users($row['tl_table']);
                if ($user_cnt > 0) {
                    echo '<a href="./timeline_user.php?tl_table='.$row['tl_table'].'">'.$user_cnt.'명</a>';
                } else {
                    echo '<span style="color:#999;">제한없음</span>';
                }
            ?>
        </td>
        <td>
            <label for="tl_image_count_<?php echo $i; ?>" class="sound_only">이미지 수</label>
            <input type="text" name="tl_image_count[<?php echo $i; ?>]" value="<?php echo $row['tl_image_count']; ?>" id="tl_image_count_<?php echo $i; ?>" class="frm_input" size="2">
        </td>
        <td>
            <label for="tl_content_max_<?php echo $i; ?>" class="sound_only">최대 글자수</label>
            <input type="text" name="tl_content_max[<?php echo $i; ?>]" value="<?php echo $row['tl_content_max']; ?>" id="tl_content_max_<?php echo $i; ?>" class="frm_input" size="4">
        </td>
        <td>
            <label for="tl_write_point_<?php echo $i; ?>" class="sound_only">글쓰기 포인트</label>
            <input type="text" name="tl_write_point[<?php echo $i; ?>]" value="<?php echo $row['tl_write_point']; ?>" id="tl_write_point_<?php echo $i; ?>" class="frm_input" size="3">
        </td>
        <td><?php echo number_format($row['tl_count_write']); ?></td>
        <td><?php echo number_format($row['tl_count_comment']); ?></td>
        <td>
            <?php echo $one_update; ?>
            <?php echo $one_copy; ?>
            <?php echo $one_view; ?>
        </td>
    </tr>
    <?php
    }
    if ($i == 0) {
        echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
    }
    ?>
    </tbody>
    </table>
</div>

<div class="btn_list01 btn_list">
    <input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value">
    <input type="submit" name="act_button" value="선택초기화" onclick="document.pressed=this.value">
    <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
</div>

</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $_SERVER['SCRIPT_NAME'].'?'.$qstr.'&amp;page='); ?>

<script>
function ftimelinelist_submit(f)
{
    if (!is_checked("chk[]")) {
        alert(document.pressed + " 하실 항목을 하나 이상 선택하세요.");
        return false;
    }

    if (document.pressed == "선택초기화") {
        if (!confirm("선택한 타임라인의 모든 글을 삭제하시겠습니까?\n\n※ 타임라인 설정은 유지되고, 글/좋아요/북마크/멘션 데이터만 삭제됩니다.")) {
            return false;
        }
    }

    if (document.pressed == "선택삭제") {
        if (!confirm("선택한 타임라인을 정말 삭제하시겠습니까?\n\n※ 타임라인의 모든 글과 데이터가 삭제됩니다.")) {
            return false;
        }
    }

    return true;
}

$(function(){
    $(".timeline_copy").click(function(){
        window.open(this.href, "win_timeline_copy", "left=100,top=100,width=550,height=450");
        return false;
    });
});
</script>

<?php
include_once('./admin.tail.php');
?>
