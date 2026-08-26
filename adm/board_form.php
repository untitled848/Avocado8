<?php
$sub_menu = "300100";
include_once './_common.php';
include_once G5_EDITOR_LIB;

if (!isset($auth) || !is_array($auth)) $auth = array();
$auth_val = isset($auth[$sub_menu]) ? $auth[$sub_menu] : '';
auth_check($auth_val, 'w');

if (!isset($config) || !is_array($config)) $config = array();
if (!isset($g5) || !is_array($g5)) $g5 = array();
if (!isset($g5['group_table'])) $g5['group_table'] = G5_TABLE_PREFIX.'group';
if (!isset($member) || !is_array($member)) $member = array();
if (!isset($is_admin)) $is_admin = '';
if (!isset($w)) $w = $_REQUEST['w'] ?? '';
if (!isset($board) || !is_array($board)) $board = array();
if (!isset($qstr)) $qstr = '';

$sql = " SELECT COUNT(*) AS cnt FROM {$g5['group_table']} ";
$row = sql_fetch($sql);
if (empty($row['cnt'])) {
    alert('게시판그룹이 한개 이상 생성되어야 합니다.', './boardgroup_form.php');
}

// 자동 마이그레이션: bo_write_limit_text 컬럼
if (function_exists('alter_table_add_column')) {
    @alter_table_add_column($g5['board_table'], 'bo_write_limit_text', "INT(11) NOT NULL DEFAULT '0'", 'bo_comment_limit_text');
}

$html_title = '게시판';
$required = '';
$readonly = '';
$sound_only = '';
$required_valid = '';
$gr_id = isset($gr_id) ? $gr_id : '';

if ($w === '') {
    $html_title .= ' 생성';

    $required = 'required';
    $required_valid = 'alnum_';
    $sound_only = '<strong class="sound_only">필수</strong>';

    if (!isset($board['bo_count_delete'])) $board['bo_count_delete'] = 1;
    if (!isset($board['bo_count_modify'])) $board['bo_count_modify'] = 1;
    $board['bo_read_point']         = 0;
    $board['bo_write_point']        = 0;
    $board['bo_comment_point']      = 0;
    $board['bo_download_point']     = 0;
    $board['bo_gallery_height']     = 600;
    $board['bo_page_rows']          = isset($config['cf_page_rows']) ? (int)$config['cf_page_rows'] : 15;
    $board['bo_mobile_page_rows']   = $board['bo_page_rows'];
    $board['bo_subject_len']        = 60;
    $board['bo_mobile_subject_len'] = 30;
    $board['bo_new']                = 24;
    $board['bo_hot']                = 100;
    $board['bo_image_width']        = 600;
    $board['bo_upload_count']       = 1;
    $board['bo_upload_size']        = (int)ini_get("upload_max_filesize") * 1048576;
    $board['bo_reply_order']        = 1;
    $board['bo_use_search']         = 1;
    $board['bo_skin']               = 'basic';
    $board['bo_mobile_skin']        = 'basic';
    $board['gr_id']                 = $gr_id;
    $board['bo_use_secret']         = 0;
    $board['bo_include_head']       = '_head.php';
    $board['bo_include_tail']       = '_tail.php';

} elseif ($w === 'u') {
    $html_title .= ' 수정';

    if (!isset($board['bo_table']) || !$board['bo_table']) {
        alert('존재하지 않은 게시판 입니다.');
    }

    if ($is_admin === 'group') {
        if (!isset($group) || !is_array($group)) {
            $group = get_group(isset($board['gr_id']) ? $board['gr_id'] : '');
        }
        if (!isset($member['mb_id']) || $member['mb_id'] != $group['gr_admin']) {
            alert('그룹이 틀립니다.');
        }
    }

    $readonly = 'readonly';
}

if ($is_admin !== 'super') {
    if (!isset($group) || !is_array($group)) {
        $group = get_group(isset($board['gr_id']) ? $board['gr_id'] : '');
    }
    $is_admin = is_admin(isset($member['mb_id']) ? $member['mb_id'] : '');
}

$g5['title'] = $html_title;
include_once './admin.head.php';
$mgmt_hub = 'board';
$mgmt_hub_tab = 'list';
include_once './_mgmt_hub_tabs.inc.php';

$pg_anchor = '<ul class="anchor">
    <li><a href="#anc_001">기본 설정</a></li>
    <li><a href="#anc_002">권한 설정</a></li>
    <li><a href="#anc_003">기능 설정</a></li>
    <li><a href="#anc_004">디자인/양식</a></li>
    <li><a href="#anc_005">포인트 설정</a></li>
    <li><a href="#anc_006">여분필드</a></li>
    <li><a href="#anc_007">모듈</a></li>
</ul>';

$frm_submit = '<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="확인" class="btn_submit" accesskey="s">
    <a href="./board_list.php?'.$qstr.'">목록</a>'.PHP_EOL;

if ($w === 'u') {
    $bo_table_safe = htmlspecialchars($board['bo_table'], ENT_QUOTES, 'UTF-8');
    $frm_submit .= '<a href="./board_copy.php?bo_table='.$bo_table_safe.'" id="board_copy" target="win_board_copy">게시판복사</a>
    <a href="'.G5_BBS_URL.'/board.php?bo_table='.$bo_table_safe.'" class="btn_frmline">게시판 바로가기</a>
    <a href="./board_thumbnail_delete.php?bo_table='.$bo_table_safe.'&amp;'.$qstr.'" onclick="return delete_confirm2(\'게시판 썸네일 파일을 삭제하시겠습니까?\');">게시판 썸네일 삭제</a>'.PHP_EOL;
}
$frm_submit .= '</div>';
?>

<form name="fboardform" id="fboardform" action="./board_form_update.php" onsubmit="return fboardform_submit(this)" method="post" enctype="multipart/form-data">
<input type="hidden" name="w" value="<?php echo htmlspecialchars($w, ENT_QUOTES); ?>">
<input type="hidden" name="sfl" value="<?php echo isset($sfl) ? htmlspecialchars($sfl, ENT_QUOTES) : ''; ?>">
<input type="hidden" name="stx" value="<?php echo isset($stx) ? htmlspecialchars($stx, ENT_QUOTES) : ''; ?>">
<input type="hidden" name="sst" value="<?php echo isset($sst) ? htmlspecialchars($sst, ENT_QUOTES) : ''; ?>">
<input type="hidden" name="sod" value="<?php echo isset($sod) ? htmlspecialchars($sod, ENT_QUOTES) : ''; ?>">
<input type="hidden" name="page" value="<?php echo isset($page) ? (int)$page : 1; ?>">
<input type="hidden" name="token" value="">

<input type="hidden" name="bo_device" value="both">
<input type="hidden" name="bo_link_level" value="1">
<input type="hidden" name="bo_upload_level" value="1">
<input type="hidden" name="bo_download_level" value="1">
<input type="hidden" name="bo_html_level" value="1">
<input type="hidden" name="bo_include_head" value="_head.php">
<input type="hidden" name="bo_include_tail" value="_tail.php">
<input type="hidden" name="bo_subject_len" value="120">
<input type="hidden" name="bo_mobile_subject_len" value="120">
<input type="hidden" name="bo_hot" value="0">

<!-- 섹션 1: 기본 설정 -->
<section id="anc_001">
    <h2 class="h2_frm">게시판 기본 설정</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>게시판 기본 설정</caption>
        <colgroup>
            <col style="width:150px">
            <col>
            <col style="width:180px">
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="bo_table">TABLE<?php echo $sound_only; ?></label></th>
            <td colspan="2">
                <input type="text" name="bo_table"
                       value="<?php echo isset($board['bo_table']) ? htmlspecialchars($board['bo_table'], ENT_QUOTES) : ''; ?>"
                       id="bo_table"
                       <?php echo $required; ?> <?php echo $readonly; ?>
                       class="frm_input <?php echo $readonly ? 'readonly' : ''; ?> <?php echo $required; ?> <?php echo $required_valid; ?>"
                       maxlength="20">
                <?php if ($w === '') { ?>
                    영문자, 숫자, _ 만 가능 (공백없이 20자 이내)
                <?php } else { ?>
                    <a href="<?php echo G5_BBS_URL; ?>/board.php?bo_table=<?php echo htmlspecialchars($board['bo_table'], ENT_QUOTES); ?>" class="btn_frmline">게시판 바로가기</a>
                    <a href="./board_list.php" class="btn_frmline">목록으로</a>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="gr_id">그룹<strong class="sound_only">필수</strong></label></th>
            <td colspan="2">
                <?php echo get_group_select('gr_id', isset($board['gr_id']) ? $board['gr_id'] : '', 'required'); ?>
                <?php if ($w === 'u') { ?>
                    <a href="javascript:document.location.href='./board_list.php?sfl=a.gr_id&stx='+document.fboardform.gr_id.value;" class="btn_frmline">동일그룹 게시판목록</a>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_type">게시판 타입<strong class="sound_only">필수</strong></label></th>
            <td colspan="2">
                <?php
                $bo_type = 'board';
                if (isset($board['bo_type']) && $board['bo_type'] !== '') $bo_type = $board['bo_type'];
                ?>
                <select id="bo_type" name="bo_type">
                    <option value="board" <?php echo ($bo_type === 'board') ? 'selected' : ''; ?>>일반게시판</option>
                    <option value="mmb"   <?php echo ($bo_type === 'mmb')   ? 'selected' : ''; ?>>로드비 게시판</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_subject">게시판 제목<strong class="sound_only">필수</strong></label></th>
            <td colspan="2">
                <input type="text" name="bo_subject"
                       value="<?php echo isset($board['bo_subject']) ? get_text($board['bo_subject']) : ''; ?>"
                       id="bo_subject" required class="required frm_input" size="80" maxlength="120">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_category_list">분류</label></th>
            <td>
                <?php echo help('분류와 분류 사이는 | 로 구분하세요. (예: 질문|답변) 첫자로 #은 입력하지 마세요. (예: #질문|#답변 [X])'); ?>
                <input type="text" name="bo_category_list"
                       value="<?php echo isset($board['bo_category_list']) ? get_text($board['bo_category_list']) : ''; ?>"
                       id="bo_category_list" class="frm_input" size="70">
                <input type="checkbox" name="bo_use_category" value="1" id="bo_use_category" <?php echo !empty($board['bo_use_category']) ? 'checked' : ''; ?>>
                <label for="bo_use_category">사용</label>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_category_list" value="1" id="chk_grp_category_list">
                <label for="chk_grp_category_list">그룹적용</label>
                <input type="checkbox" name="chk_all_category_list" value="1" id="chk_all_category_list">
                <label for="chk_all_category_list">전체적용</label>
            </td>
        </tr>
        <?php if ($w === 'u') { ?>
        <tr>
            <th scope="row"><label for="proc_count">카운트 조정</label></th>
            <td colspan="2">
                <?php
                $cnt_w = isset($board['bo_count_write']) ? (int)$board['bo_count_write'] : 0;
                $cnt_c = isset($board['bo_count_comment']) ? (int)$board['bo_count_comment'] : 0;
                echo help(
                    '현재 원글수 : '.number_format($cnt_w).
                    ', 현재 댓글수 : '.number_format($cnt_c)."\n".
                    '게시판 목록에서 글의 번호가 맞지 않을 경우에 체크하십시오.'
                );
                ?>
                <input type="checkbox" name="proc_count" value="1" id="proc_count">
            </td>
        </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<!-- 섹션 2: 권한 설정 -->
<section id="anc_002">
    <h2 class="h2_frm">게시판 권한 설정</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>게시판 권한 설정</caption>
        <colgroup>
            <col style="width:150px">
            <col>
            <col style="width:180px">
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="bo_list_level">목록보기 권한</label></th>
            <td><?php echo get_member_level_select('bo_list_level', 1, 10, isset($board['bo_list_level']) ? $board['bo_list_level'] : ''); ?></td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_list_level" value="1" id="chk_grp_list_level"><label for="chk_grp_list_level">그룹적용</label>
                <input type="checkbox" name="chk_all_list_level" value="1" id="chk_all_list_level"><label for="chk_all_list_level">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_read_level">글읽기 권한</label></th>
            <td><?php echo get_member_level_select('bo_read_level', 1, 10, isset($board['bo_read_level']) ? $board['bo_read_level'] : ''); ?></td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_read_level" value="1" id="chk_grp_read_level"><label for="chk_grp_read_level">그룹적용</label>
                <input type="checkbox" name="chk_all_read_level" value="1" id="chk_all_read_level"><label for="chk_all_read_level">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_write_level">글쓰기 권한</label></th>
            <td><?php echo get_member_level_select('bo_write_level', 1, 10, isset($board['bo_write_level']) ? $board['bo_write_level'] : ''); ?></td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_write_level" value="1" id="chk_grp_write_level"><label for="chk_grp_write_level">그룹적용</label>
                <input type="checkbox" name="chk_all_write_level" value="1" id="chk_all_write_level"><label for="chk_all_write_level">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_reply_level">글답변 권한</label></th>
            <td><?php echo get_member_level_select('bo_reply_level', 1, 10, isset($board['bo_reply_level']) ? $board['bo_reply_level'] : ''); ?></td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_reply_level" value="1" id="chk_grp_reply_level"><label for="chk_grp_reply_level">그룹적용</label>
                <input type="checkbox" name="chk_all_reply_level" value="1" id="chk_all_reply_level"><label for="chk_all_reply_level">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_comment_level">댓글쓰기 권한</label></th>
            <td><?php echo get_member_level_select('bo_comment_level', 1, 10, isset($board['bo_comment_level']) ? $board['bo_comment_level'] : ''); ?></td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_comment_level" value="1" id="chk_grp_comment_level"><label for="chk_grp_comment_level">그룹적용</label>
                <input type="checkbox" name="chk_all_comment_level" value="1" id="chk_all_comment_level"><label for="chk_all_comment_level">전체적용</label>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<!-- 섹션 3: 기능 설정 -->
<section id="anc_003">
    <h2 class="h2_frm">게시판 기능 설정</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>게시판 기능 설정</caption>
        <colgroup>
            <col style="width:150px">
            <col>
            <col style="width:180px">
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="bo_count_modify">원글 수정 불가</label></th>
            <td>
                <?php echo help('댓글의 수가 설정 수 이상이면 원글을 수정할 수 없습니다. 0이면 댓글 수와 관계없이 수정 가능.'); ?>
                댓글 <input type="text" name="bo_count_modify" value="<?php echo isset($board['bo_count_modify']) ? $board['bo_count_modify'] : 1; ?>" id="bo_count_modify" required class="required numeric frm_input" size="3">개 이상 달리면 수정불가
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_count_modify" value="1" id="chk_grp_count_modify"><label for="chk_grp_count_modify">그룹적용</label>
                <input type="checkbox" name="chk_all_count_modify" value="1" id="chk_all_count_modify"><label for="chk_all_count_modify">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_count_delete">원글 삭제 불가</label></th>
            <td>
                댓글 <input type="text" name="bo_count_delete" value="<?php echo isset($board['bo_count_delete']) ? $board['bo_count_delete'] : 1; ?>" id="bo_count_delete" required class="required numeric frm_input" size="3">개 이상 달리면 삭제불가
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_count_delete" value="1" id="chk_grp_count_delete"><label for="chk_grp_count_delete">그룹적용</label>
                <input type="checkbox" name="chk_all_count_delete" value="1" id="chk_all_count_delete"><label for="chk_all_count_delete">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_secret">비밀글 사용</label></th>
            <td>
                <?php echo help('"체크박스"는 선택시 비밀글. "무조건"은 모든글 비밀글. 스킨에 따라 미적용 가능.'); ?>
                <select id="bo_use_secret" name="bo_use_secret">
                    <?php
                    $bo_use_secret = isset($board['bo_use_secret']) ? (int)$board['bo_use_secret'] : 0;
                    echo option_selected(0, $bo_use_secret, "사용하지 않음");
                    echo option_selected(1, $bo_use_secret, "체크박스");
                    echo option_selected(2, $bo_use_secret, "무조건");
                    ?>
                </select>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_use_secret" value="1" id="chk_grp_use_secret"><label for="chk_grp_use_secret">그룹적용</label>
                <input type="checkbox" name="chk_all_use_secret" value="1" id="chk_all_use_secret"><label for="chk_all_use_secret">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_emoticon">이모티콘 사용</label></th>
            <td>
                <?php echo help('본문/댓글에서 이모티콘 명령어(예: :smile:)를 등록된 이미지로 자동 변환합니다. 이모티콘 목록은 [환경설정 > 이모티콘 관리]에서 관리합니다.'); ?>
                <input type="checkbox" name="bo_use_emoticon" value="1" id="bo_use_emoticon" <?php echo !empty($board['bo_use_emoticon']) ? 'checked' : ''; ?>> 사용
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_use_emoticon" value="1" id="chk_grp_use_emoticon"><label for="chk_grp_use_emoticon">그룹적용</label>
                <input type="checkbox" name="chk_all_use_emoticon" value="1" id="chk_all_use_emoticon"><label for="chk_all_use_emoticon">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_chick">췩 사용</label></th>
            <td>
                <input type="checkbox" name="bo_use_chick" value="1" id="bo_use_chick" <?php echo !empty($board['bo_use_chick']) ? 'checked' : ''; ?>> 사용
            </td>
            <td class="td_grpset"></td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_noname">익명 사용</label></th>
            <td>
                <input type="checkbox" name="bo_use_noname" value="1" id="bo_use_noname" <?php echo !empty($board['bo_use_noname']) ? 'checked' : ''; ?>> 사용
            </td>
            <td class="td_grpset"></td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_dhtml_editor">DHTML 에디터 사용</label></th>
            <td>
                <?php echo help('글작성시 DHTML 에디터 사용 여부. 스킨에 따라 미적용 가능.'); ?>
                <input type="checkbox" name="bo_use_dhtml_editor" value="1" id="bo_use_dhtml_editor" <?php echo !empty($board['bo_use_dhtml_editor']) ? 'checked' : ''; ?>> 사용
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_use_dhtml_editor" value="1" id="chk_grp_use_dhtml_editor"><label for="chk_grp_use_dhtml_editor">그룹적용</label>
                <input type="checkbox" name="chk_all_use_dhtml_editor" value="1" id="chk_all_use_dhtml_editor"><label for="chk_all_use_dhtml_editor">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_list_content">목록에서 내용 사용</label></th>
            <td>
                <?php echo help('목록에서 내용까지 읽어올 경우. 성능 저하 가능.'); ?>
                <input type="checkbox" name="bo_use_list_content" value="1" id="bo_use_list_content" <?php echo !empty($board['bo_use_list_content']) ? 'checked' : ''; ?>> 사용
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_use_list_content" value="1" id="chk_grp_use_list_content"><label for="chk_grp_use_list_content">그룹적용</label>
                <input type="checkbox" name="chk_all_use_list_content" value="1" id="chk_all_use_list_content"><label for="chk_all_use_list_content">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_list_file">목록에서 파일 사용</label></th>
            <td>
                <?php echo help('목록에서 첨부파일 정보를 사용. 성능 저하 가능.'); ?>
                <input type="checkbox" name="bo_use_list_file" value="1" id="bo_use_list_file" <?php echo !empty($board['bo_use_list_file']) ? 'checked' : ''; ?>> 사용
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_use_list_file" value="1" id="chk_grp_use_list_file"><label for="chk_grp_use_list_file">그룹적용</label>
                <input type="checkbox" name="chk_all_use_list_file" value="1" id="chk_all_use_list_file"><label for="chk_all_use_list_file">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_list_view">전체목록보이기 사용</label></th>
            <td>
                <input type="checkbox" name="bo_use_list_view" value="1" id="bo_use_list_view" <?php echo !empty($board['bo_use_list_view']) ? 'checked' : ''; ?>> 사용
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_use_list_view" value="1" id="chk_grp_use_list_view"><label for="chk_grp_use_list_view">그룹적용</label>
                <input type="checkbox" name="chk_all_use_list_view" value="1" id="chk_all_use_list_view"><label for="chk_all_use_list_view">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_upload_count">파일 업로드 개수</label></th>
            <td>
                <?php echo help('0이면 파일첨부 사용 안함.'); ?>
                <input type="text" name="bo_upload_count" value="<?php echo isset($board['bo_upload_count']) ? $board['bo_upload_count'] : 1; ?>" id="bo_upload_count" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_upload_count" value="1" id="chk_grp_upload_count"><label for="chk_grp_upload_count">그룹적용</label>
                <input type="checkbox" name="chk_all_upload_count" value="1" id="chk_all_upload_count"><label for="chk_all_upload_count">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_upload_size">파일 업로드 용량</label></th>
            <td>
                <?php echo help('최대 '.ini_get("upload_max_filesize").' 이하. 1MB = 1,048,576 bytes'); ?>
                업로드 파일 한개당 <input type="text" name="bo_upload_size" value="<?php echo isset($board['bo_upload_size']) ? $board['bo_upload_size'] : ((int)ini_get("upload_max_filesize") * 1048576); ?>" id="bo_upload_size" required class="required numeric frm_input" size="10"> bytes 이하
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_upload_size" value="1" id="chk_grp_upload_size"><label for="chk_grp_upload_size">그룹적용</label>
                <input type="checkbox" name="chk_all_upload_size" value="1" id="chk_all_upload_size"><label for="chk_all_upload_size">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_comment_limit_count">멤버당 댓글 수 제한</label></th>
            <td>
                <?php echo help('한 멤버가 하나의 글스레드(원글+댓글)에 쓸 수 있는 최대 개수. 0이면 제한 없음. 원글도 1개로 카운트됨 (본인 원글 + N-1개 댓글). 관리자는 항상 제외.'); ?>
                <input type="number" name="bo_comment_limit_count" value="<?php echo isset($board['bo_comment_limit_count']) ? (int)$board['bo_comment_limit_count'] : 0; ?>" id="bo_comment_limit_count" class="frm_input" min="0" size="4"> 개
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_comment_limit_count" value="1" id="chk_grp_comment_limit_count"><label for="chk_grp_comment_limit_count">그룹적용</label>
                <input type="checkbox" name="chk_all_comment_limit_count" value="1" id="chk_all_comment_limit_count"><label for="chk_all_comment_limit_count">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_comment_limit_time">댓글 작성 가능 시간(시간)</label></th>
            <td>
                <?php echo help('원글 작성 후 N시간 이내에만 댓글 가능. 0이면 무제한. 24시간 이상 가능. 관리자는 항상 제외.'); ?>
                <input type="number" name="bo_comment_limit_time" value="<?php echo isset($board['bo_comment_limit_time']) ? (int)$board['bo_comment_limit_time'] : 0; ?>" id="bo_comment_limit_time" class="frm_input" min="0" size="4"> 시간
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_comment_limit_time" value="1" id="chk_grp_comment_limit_time"><label for="chk_grp_comment_limit_time">그룹적용</label>
                <input type="checkbox" name="chk_all_comment_limit_time" value="1" id="chk_all_comment_limit_time"><label for="chk_all_comment_limit_time">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_write_limit_text">본문 글자 수 제한</label></th>
            <td>
                <?php echo help('본문(글)의 최대 글자 수. 0이면 제한 없음. 관리자는 항상 제외. 아래 「글자 수 제외 문자」 가 함께 적용됩니다.'); ?>
                <input type="number" name="bo_write_limit_text" value="<?php echo isset($board['bo_write_limit_text']) ? (int)$board['bo_write_limit_text'] : 0; ?>" id="bo_write_limit_text" class="frm_input" min="0" size="6"> 자
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_write_limit_text" value="1" id="chk_grp_write_limit_text"><label for="chk_grp_write_limit_text">그룹적용</label>
                <input type="checkbox" name="chk_all_write_limit_text" value="1" id="chk_all_write_limit_text"><label for="chk_all_write_limit_text">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_comment_limit_text">댓글 글자 수 제한</label></th>
            <td>
                <?php echo help('댓글의 최대 글자 수. 0이면 제한 없음. 관리자는 항상 제외. 아래 「글자 수 제외 문자」 가 함께 적용됩니다.'); ?>
                <input type="number" name="bo_comment_limit_text" value="<?php echo isset($board['bo_comment_limit_text']) ? (int)$board['bo_comment_limit_text'] : 0; ?>" id="bo_comment_limit_text" class="frm_input" min="0" size="6"> 자
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_comment_limit_text" value="1" id="chk_grp_comment_limit_text"><label for="chk_grp_comment_limit_text">그룹적용</label>
                <input type="checkbox" name="chk_all_comment_limit_text" value="1" id="chk_all_comment_limit_text"><label for="chk_all_comment_limit_text">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_comment_limit_text_exc">글자 수 제외 문자</label></th>
            <td>
                <?php echo help('글자 수 카운트에서 제외할 문자들. 쉼표(,)로 구분. 본문/댓글 양쪽에 적용. 예: ㅋ,ㅠ,ㅎ,ㅜ,.,!,?,~'); ?>
                <input type="text" name="bo_comment_limit_text_exc" value="<?php echo isset($board['bo_comment_limit_text_exc']) ? get_text($board['bo_comment_limit_text_exc']) : 'ㅋ,ㅠ,ㅎ,ㅜ,.,!,?,~'; ?>" id="bo_comment_limit_text_exc" class="frm_input" size="50">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_comment_limit_text_exc" value="1" id="chk_grp_comment_limit_text_exc"><label for="chk_grp_comment_limit_text_exc">그룹적용</label>
                <input type="checkbox" name="chk_all_comment_limit_text_exc" value="1" id="chk_all_comment_limit_text_exc"><label for="chk_all_comment_limit_text_exc">전체적용</label>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<!-- 섹션 4: 디자인/양식 -->
<section id="anc_004">
    <h2 class="h2_frm">게시판 디자인/양식</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>게시판 디자인/양식</caption>
        <colgroup>
            <col style="width:150px">
            <col>
            <col style="width:180px">
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="bo_skin">스킨 디렉토리</label></th>
            <td><?php echo get_skin_select('board', 'bo_skin', 'bo_skin', isset($board['bo_skin']) ? $board['bo_skin'] : 'basic', 'required'); ?></td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_skin" value="1" id="chk_grp_skin"><label for="chk_grp_skin">그룹적용</label>
                <input type="checkbox" name="chk_all_skin" value="1" id="chk_all_skin"><label for="chk_all_skin">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_include_head">상단 파일 경로</label></th>
            <td><input type="text" name="bo_include_head" value="<?php echo isset($board['bo_include_head']) ? $board['bo_include_head'] : ''; ?>" id="bo_include_head" class="frm_input" size="50"></td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_include_head" value="1" id="chk_grp_include_head"><label for="chk_grp_include_head">그룹적용</label>
                <input type="checkbox" name="chk_all_include_head" value="1" id="chk_all_include_head"><label for="chk_all_include_head">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_include_tail">하단 파일 경로</label></th>
            <td><input type="text" name="bo_include_tail" value="<?php echo isset($board['bo_include_tail']) ? $board['bo_include_tail'] : ''; ?>" id="bo_include_tail" class="frm_input" size="50"></td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_include_tail" value="1" id="chk_grp_include_tail"><label for="chk_grp_include_tail">그룹적용</label>
                <input type="checkbox" name="chk_all_include_tail" value="1" id="chk_all_include_tail"><label for="chk_all_include_tail">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_content_head">공지사항</label></th>
            <td>
                <?php echo editor_html("bo_content_head", isset($board['bo_content_head']) ? get_text($board['bo_content_head'], 0) : ''); ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_content_head" value="1" id="chk_grp_content_head"><label for="chk_grp_content_head">그룹적용</label>
                <input type="checkbox" name="chk_all_content_head" value="1" id="chk_all_content_head"><label for="chk_all_content_head">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_insert_content">글쓰기 기본 내용</label></th>
            <td>
                <textarea id="bo_insert_content" name="bo_insert_content" rows="5"><?php echo isset($board['bo_insert_content']) ? $board['bo_insert_content'] : ''; ?></textarea>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_insert_content" value="1" id="chk_grp_insert_content"><label for="chk_grp_insert_content">그룹적용</label>
                <input type="checkbox" name="chk_all_insert_content" value="1" id="chk_all_insert_content"><label for="chk_all_insert_content">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_page_rows">페이지당 목록 수</label></th>
            <td><input type="text" name="bo_page_rows" value="<?php echo isset($board['bo_page_rows']) ? $board['bo_page_rows'] : 15; ?>" id="bo_page_rows" required class="required numeric frm_input" size="4"></td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_page_rows" value="1" id="chk_grp_page_rows"><label for="chk_grp_page_rows">그룹적용</label>
                <input type="checkbox" name="chk_all_page_rows" value="1" id="chk_all_page_rows"><label for="chk_all_page_rows">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_mobile_page_rows">모바일 페이지당 목록 수</label></th>
            <td><input type="text" name="bo_mobile_page_rows" value="<?php echo isset($board['bo_mobile_page_rows']) ? $board['bo_mobile_page_rows'] : (isset($board['bo_page_rows']) ? $board['bo_page_rows'] : 15); ?>" id="bo_mobile_page_rows" required class="required numeric frm_input" size="4"></td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_mobile_page_rows" value="1" id="chk_grp_mobile_page_rows"><label for="chk_grp_mobile_page_rows">그룹적용</label>
                <input type="checkbox" name="chk_all_mobile_page_rows" value="1" id="chk_all_mobile_page_rows"><label for="chk_all_mobile_page_rows">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_gallery_height">로그접기 세로기준</label></th>
            <td>
                <?php echo help('자비란 형태 게시판에서 적용.'); ?>
                <input type="text" name="bo_gallery_height" value="<?php echo isset($board['bo_gallery_height']) ? $board['bo_gallery_height'] : 600; ?>" id="bo_gallery_height" required class="required numeric frm_input" size="4"> 픽셀
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_gallery_height" value="1" id="chk_grp_gallery_height"><label for="chk_grp_gallery_height">그룹적용</label>
                <input type="checkbox" name="chk_all_gallery_height" value="1" id="chk_all_gallery_height"><label for="chk_all_gallery_height">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_image_width">이미지 폭 크기</label></th>
            <td>
                <?php echo help('일반 게시판 이미지 폭.'); ?>
                <input type="text" name="bo_image_width" value="<?php echo isset($board['bo_image_width']) ? $board['bo_image_width'] : 600; ?>" id="bo_image_width" required class="required numeric frm_input" size="4"> 픽셀
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_image_width" value="1" id="chk_grp_image_width"><label for="chk_grp_image_width">그룹적용</label>
                <input type="checkbox" name="chk_all_image_width" value="1" id="chk_all_image_width"><label for="chk_all_image_width">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_new">새글 아이콘</label></th>
            <td>
                <?php echo help('글 입력 후 new 아이콘 유지 시간(시). 0이면 미사용.'); ?>
                <input type="text" name="bo_new" value="<?php echo isset($board['bo_new']) ? $board['bo_new'] : 24; ?>" id="bo_new" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_new" value="1" id="chk_grp_new"><label for="chk_grp_new">그룹적용</label>
                <input type="checkbox" name="chk_all_new" value="1" id="chk_all_new"><label for="chk_all_new">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_reply_order">답변 달기</label></th>
            <td>
                <?php $bo_reply_order = isset($board['bo_reply_order']) ? (int)$board['bo_reply_order'] : 1; ?>
                <select id="bo_reply_order" name="bo_reply_order">
                    <option value="1" <?php echo ($bo_reply_order === 1) ? 'selected' : ''; ?>>나중에 쓴 답변 아래로 달기 (기본)</option>
                    <option value="0" <?php echo ($bo_reply_order === 0) ? 'selected' : ''; ?>>나중에 쓴 답변 위로 달기</option>
                </select>
            </td>
            <td class="td_grpset">
                <input type="checkbox" id="chk_grp_reply_order" name="chk_grp_reply_order" value="1"><label for="chk_grp_reply_order">그룹적용</label>
                <input type="checkbox" id="chk_all_reply_order" name="chk_all_reply_order" value="1"><label for="chk_all_reply_order">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_sort_field">리스트 정렬 필드</label></th>
            <td>
                <?php echo help('기본 정렬 필드. 잘못 설정 시 속도 저하 가능.'); ?>
                <?php $bo_sort_field = isset($board['bo_sort_field']) ? $board['bo_sort_field'] : ''; ?>
                <select id="bo_sort_field" name="bo_sort_field">
                    <option value="" <?php echo $bo_sort_field == "" ? 'selected' : ''; ?>>wr_num, wr_reply : 기본</option>
                    <option value="wr_datetime asc" <?php echo $bo_sort_field == "wr_datetime asc" ? 'selected' : ''; ?>>wr_datetime asc</option>
                    <option value="wr_datetime desc" <?php echo $bo_sort_field == "wr_datetime desc" ? 'selected' : ''; ?>>wr_datetime desc</option>
                    <option value="wr_hit asc, wr_num, wr_reply" <?php echo $bo_sort_field == "wr_hit asc, wr_num, wr_reply" ? 'selected' : ''; ?>>wr_hit asc</option>
                    <option value="wr_hit desc, wr_num, wr_reply" <?php echo $bo_sort_field == "wr_hit desc, wr_num, wr_reply" ? 'selected' : ''; ?>>wr_hit desc</option>
                    <option value="wr_last asc" <?php echo $bo_sort_field == "wr_last asc" ? 'selected' : ''; ?>>wr_last asc</option>
                    <option value="wr_last desc" <?php echo $bo_sort_field == "wr_last desc" ? 'selected' : ''; ?>>wr_last desc</option>
                    <option value="wr_comment asc, wr_num, wr_reply" <?php echo $bo_sort_field == "wr_comment asc, wr_num, wr_reply" ? 'selected' : ''; ?>>wr_comment asc</option>
                    <option value="wr_comment desc, wr_num, wr_reply" <?php echo $bo_sort_field == "wr_comment desc, wr_num, wr_reply" ? 'selected' : ''; ?>>wr_comment desc</option>
                    <option value="wr_good asc, wr_num, wr_reply" <?php echo $bo_sort_field == "wr_good asc, wr_num, wr_reply" ? 'selected' : ''; ?>>wr_good asc</option>
                    <option value="wr_good desc, wr_num, wr_reply" <?php echo $bo_sort_field == "wr_good desc, wr_num, wr_reply" ? 'selected' : ''; ?>>wr_good desc</option>
                    <option value="wr_nogood asc, wr_num, wr_reply" <?php echo $bo_sort_field == "wr_nogood asc, wr_num, wr_reply" ? 'selected' : ''; ?>>wr_nogood asc</option>
                    <option value="wr_nogood desc, wr_num, wr_reply" <?php echo $bo_sort_field == "wr_nogood desc, wr_num, wr_reply" ? 'selected' : ''; ?>>wr_nogood desc</option>
                    <option value="wr_subject asc, wr_num, wr_reply" <?php echo $bo_sort_field == "wr_subject asc, wr_num, wr_reply" ? 'selected' : ''; ?>>wr_subject asc</option>
                    <option value="wr_subject desc, wr_num, wr_reply" <?php echo $bo_sort_field == "wr_subject desc, wr_num, wr_reply" ? 'selected' : ''; ?>>wr_subject desc</option>
                    <option value="wr_name asc, wr_num, wr_reply" <?php echo $bo_sort_field == "wr_name asc, wr_num, wr_reply" ? 'selected' : ''; ?>>wr_name asc</option>
                    <option value="wr_name desc, wr_num, wr_reply" <?php echo $bo_sort_field == "wr_name desc, wr_num, wr_reply" ? 'selected' : ''; ?>>wr_name desc</option>
                    <option value="ca_name asc, wr_num, wr_reply" <?php echo $bo_sort_field == "ca_name asc, wr_num, wr_reply" ? 'selected' : ''; ?>>ca_name asc</option>
                    <option value="ca_name desc, wr_num, wr_reply" <?php echo $bo_sort_field == "ca_name desc, wr_num, wr_reply" ? 'selected' : ''; ?>>ca_name desc</option>
                </select>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_sort_field" value="1" id="chk_grp_sort_field"><label for="chk_grp_sort_field">그룹적용</label>
                <input type="checkbox" name="chk_all_sort_field" value="1" id="chk_all_sort_field"><label for="chk_all_sort_field">전체적용</label>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<!-- 섹션 5: 포인트 설정 -->
<section id="anc_005">
    <h2 class="h2_frm">게시판 포인트 설정</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>게시판 포인트 설정</caption>
        <colgroup>
            <col style="width:150px">
            <col>
            <col style="width:180px">
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="chk_grp_point">기본값으로 설정</label></th>
            <td colspan="2">
                <?php echo help('환경설정에 입력된 포인트로 설정'); ?>
                <input type="checkbox" name="chk_grp_point" id="chk_grp_point" onclick="set_point(this.form)">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_read_point">글읽기 포인트</label></th>
            <td><input type="text" name="bo_read_point" value="<?php echo isset($board['bo_read_point']) ? $board['bo_read_point'] : 0; ?>" id="bo_read_point" required class="required frm_input" size="5"></td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_read_point" value="1" id="chk_grp_read_point"><label for="chk_grp_read_point">그룹적용</label>
                <input type="checkbox" name="chk_all_read_point" value="1" id="chk_all_read_point"><label for="chk_all_read_point">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_write_point">글쓰기 포인트</label></th>
            <td><input type="text" name="bo_write_point" value="<?php echo isset($board['bo_write_point']) ? $board['bo_write_point'] : 0; ?>" id="bo_write_point" required class="required frm_input" size="5"></td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_write_point" value="1" id="chk_grp_write_point"><label for="chk_grp_write_point">그룹적용</label>
                <input type="checkbox" name="chk_all_write_point" value="1" id="chk_all_write_point"><label for="chk_all_write_point">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_comment_point">댓글쓰기 포인트</label></th>
            <td><input type="text" name="bo_comment_point" value="<?php echo isset($board['bo_comment_point']) ? $board['bo_comment_point'] : 0; ?>" id="bo_comment_point" required class="required frm_input" size="5"></td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_comment_point" value="1" id="chk_grp_comment_point"><label for="chk_grp_comment_point">그룹적용</label>
                <input type="checkbox" name="chk_all_comment_point" value="1" id="chk_all_comment_point"><label for="chk_all_comment_point">전체적용</label>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<!-- 섹션 6: 여분필드 설정 -->
<section id="anc_006">
    <h2 class="h2_frm">게시판 여분필드 설정</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>게시판 여분필드 설정</caption>
        <colgroup>
            <col style="width:150px">
            <col>
            <col style="width:180px">
        </colgroup>
        <tbody>
        <?php for ($i = 1; $i <= 10; $i++) { ?>
        <tr>
            <th scope="row">여분필드<?php echo $i; ?></th>
            <td class="td_extra">
                <label for="bo_<?php echo $i; ?>_subj">여분필드 <?php echo $i; ?> 제목</label>
                <input type="text" name="bo_<?php echo $i; ?>_subj" id="bo_<?php echo $i; ?>_subj" value="<?php echo isset($board['bo_'.$i.'_subj']) ? get_text($board['bo_'.$i.'_subj']) : ''; ?>" class="frm_input">
                <label for="bo_<?php echo $i; ?>">여분필드 <?php echo $i; ?> 값</label>
                <input type="text" name="bo_<?php echo $i; ?>" id="bo_<?php echo $i; ?>" value="<?php echo isset($board['bo_'.$i]) ? get_text($board['bo_'.$i]) : ''; ?>" class="frm_input">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_grp_<?php echo $i; ?>" value="1" id="chk_grp_<?php echo $i; ?>"><label for="chk_grp_<?php echo $i; ?>">그룹적용</label>
                <input type="checkbox" name="chk_all_<?php echo $i; ?>" value="1" id="chk_all_<?php echo $i; ?>"><label for="chk_all_<?php echo $i; ?>">전체적용</label>
            </td>
        </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>
</section>

<?php
// ─── 스킨 설정 훅 (모듈 설정 섹션 위) ───────────────────────────────────────
// 현재 스킨 폴더에 setting.hook.php 가 있으면 include (스킨별 고유 설정 추가 가능)
$_h_bo_skin  = (isset($board['bo_skin']) && $board['bo_skin'] !== '') ? $board['bo_skin'] : 'basic';
$_h_skin_path = function_exists('get_skin_path') ? get_skin_path('board', $_h_bo_skin) : G5_SKIN_PATH.'/board/'.$_h_bo_skin;
$_h_hook_file = $_h_skin_path . '/setting.hook.php';
if (file_exists($_h_hook_file)) {
?>
<section id="anc_skinhook">
    <h2 class="h2_frm">스킨 설정 &mdash; <?php echo htmlspecialchars($_h_bo_skin, ENT_QUOTES); ?></h2>
    <?php include $_h_hook_file; // $board, $w 변수 사용 가능 ?>
</section>
<?php
}
unset($_h_bo_skin, $_h_skin_path, $_h_hook_file);
?>

<?php
/* 섹션 7: 게시판별 모듈 on/off (board_scope=true 모듈이 있을 때만 노출) */
$mdl_scope = (function_exists('mdl_board_scope_modules') ? mdl_board_scope_modules() : array());
if (!empty($mdl_scope)) {
    $cur_bo_table = isset($board['bo_table']) ? $board['bo_table'] : '';
?>
<section id="anc_007">
    <h2 class="h2_frm">게시판별 모듈 on/off</h2>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>게시판별 모듈 on/off</caption>
        <colgroup>
            <col style="width:200px">
            <col>
        </colgroup>
        <tbody>
        <?php foreach ($mdl_scope as $_mname => $_mmeta) {
            $_scopes = (isset($_mmeta['scopes']) && is_array($_mmeta['scopes'])) ? $_mmeta['scopes'] : array();
            $_default_on = (!isset($_mmeta['board_default']) || $_mmeta['board_default'] !== 'off');

            // 스킨 훅 지원 검사 (모듈 단위)
            $_compat = array('supported' => true, 'missing' => array());
            $_skin_overridden = false;
            $_skin_path_show  = '';
            if (function_exists('mdl_module_skin_compat') && !empty($_mmeta['requires_hooks'])) {
                $_bo_skin = (isset($board['bo_skin']) && $board['bo_skin'] !== '') ? $board['bo_skin'] : 'basic';
                $_skin_default = G5_PATH.'/skin/board/'.$_bo_skin;
                $_skin_path = function_exists('get_skin_path') ? get_skin_path('board', $_bo_skin) : $_skin_default;
                $_compat = mdl_module_skin_compat($_mmeta, $_skin_path);
                if ($_skin_path && @realpath($_skin_path) !== @realpath($_skin_default)) {
                    $_skin_overridden = true;
                    $_skin_path_show  = ltrim(str_replace(array('\\', G5_PATH), array('/', ''), $_skin_path), '/');
                }
            }
            $_disabled = !$_compat['supported'];
            $_id_base = 'mdl_'.preg_replace('/[^a-zA-Z0-9_]/', '_', $_mname);
        ?>
        <tr class="mdl-row"
            data-mod="<?php echo htmlspecialchars($_mname, ENT_QUOTES); ?>"
            data-requires="<?php echo htmlspecialchars(implode(',', isset($_mmeta['requires_hooks']) ? $_mmeta['requires_hooks'] : array()), ENT_QUOTES); ?>"
            <?php echo $_disabled ? 'style="opacity:0.55;"' : ''; ?>>
            <th scope="row"><?php echo htmlspecialchars($_mmeta['title'], ENT_QUOTES); ?></th>
            <td>
            <?php if (empty($_scopes)) {
                // 모듈 단위 단일 토글
                if ($w === '') {
                    $_enabled = $_default_on;
                } else {
                    $_enabled = ($cur_bo_table !== '' && function_exists('mdl_board_enabled'))
                              ? mdl_board_enabled($cur_bo_table, $_mname)
                              : $_default_on;
                }
                if ($_disabled) $_enabled = false;
                $_id = $_id_base;
            ?>
                <input type="hidden" name="mdl_modules_present[]" value="<?php echo htmlspecialchars($_mname, ENT_QUOTES); ?>">
                <input type="checkbox" name="mdl_modules[<?php echo htmlspecialchars($_mname, ENT_QUOTES); ?>]" id="<?php echo $_id; ?>" value="1" <?php echo $_enabled ? 'checked' : ''; ?> <?php echo $_disabled ? 'disabled' : ''; ?>>
                <label for="<?php echo $_id; ?>">사용</label>
            <?php } else {
                // scope 분리 토글 (각 scope 별 체크박스)
                foreach ($_scopes as $_sc) {
                    if ($w === '') {
                        $_en = $_default_on;
                    } else {
                        $_en = ($cur_bo_table !== '' && function_exists('mdl_board_enabled'))
                             ? mdl_board_enabled($cur_bo_table, $_mname, $_sc)
                             : $_default_on;
                    }
                    if ($_disabled) $_en = false;
                    $_key = $_mname.'@'.$_sc;
                    $_id  = $_id_base.'_'.preg_replace('/[^a-zA-Z0-9_]/', '_', $_sc);
            ?>
                <input type="hidden" name="mdl_modules_present[]" value="<?php echo htmlspecialchars($_key, ENT_QUOTES); ?>">
                <input type="checkbox" name="mdl_modules[<?php echo htmlspecialchars($_key, ENT_QUOTES); ?>]" id="<?php echo $_id; ?>" value="1" <?php echo $_en ? 'checked' : ''; ?> <?php echo $_disabled ? 'disabled' : ''; ?>>
                <label for="<?php echo $_id; ?>"><?php echo htmlspecialchars($_sc, ENT_QUOTES); ?></label>
                &nbsp;&nbsp;
            <?php } } ?>
                <span style="color:#888;font-size:12px;margin-left:10px;">
                    <?php echo htmlspecialchars($_mname, ENT_QUOTES); ?>
                    <?php if (!empty($_mmeta['description'])) { ?> &mdash; <?php echo htmlspecialchars($_mmeta['description'], ENT_QUOTES); ?><?php } ?>
                </span>
                <div class="mdl-status">
                <?php if ($_disabled) { ?>
                <div style="color:#c00;font-size:12px;margin-top:4px;">
                    ⚠ 현재 스킨(<strong class="cur-skin"><?php echo htmlspecialchars(isset($board['bo_skin']) ? $board['bo_skin'] : '', ENT_QUOTES); ?></strong>)이 필요한 훅을 제공하지 않아 사용할 수 없습니다.
                    누락 훅: <span class="missing-hooks"><?php echo htmlspecialchars(implode(', ', $_compat['missing']), ENT_QUOTES); ?></span>
                </div>
                <?php } else if (!empty($_mmeta['requires_hooks'])) { ?>
                <div style="color:#888;font-size:11px;margin-top:4px;">
                    필요 훅: <?php echo htmlspecialchars(implode(', ', $_mmeta['requires_hooks']), ENT_QUOTES); ?>
                    <?php if ($_skin_overridden) { ?>
                    <br><span style="color:#06a;">▶ 테마 오버라이드 적용된 스킨 경로 검사됨: <code class="skin-path-show"><?php echo htmlspecialchars($_skin_path_show, ENT_QUOTES); ?></code></span>
                    <?php } ?>
                </div>
                <?php } ?>
                </div>
            </td>
        </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>
<?php } /* end mdl_scope */ ?>

<?php if (empty($mdl_scope)) { /* 모듈 섹션이 없을 때도 제출 버튼은 1회 출력 */ ?>
<?php echo $frm_submit; ?>
<?php } ?>

</form>

<script>
// 게시판 스킨 변경 시 모듈 호환성 라이브 재검증
(function(){
    var $skin = $('select[name="bo_skin"]');
    var $rows = $('tr.mdl-row');
    if (!$skin.length || !$rows.length) return;

    function esc(s){ return $('<i>').text(s == null ? '' : String(s)).html(); }

    function recheck() {
        var skin = $skin.val() || 'basic';
        $.getJSON('./board_form_module_check.ajax.php', { bo_skin: skin })
            .done(function(res){
                if (!res || !res.modules) return;
                $rows.each(function(){
                    var $tr = $(this);
                    var name = $tr.data('mod');
                    var info = res.modules[name];
                    var $status = $tr.find('.mdl-status');
                    var $cbs = $tr.find('input[type="checkbox"][name^="mdl_modules["]');
                    var requires = ($tr.attr('data-requires') || '').split(',').filter(Boolean);
                    var html = '';
                    if (!info) {
                        $tr.css('opacity', 1);
                        $cbs.prop('disabled', false);
                    } else if (!info.supported) {
                        $tr.css('opacity', 0.55);
                        $cbs.prop('disabled', true).prop('checked', false);
                        html = '<div style="color:#c00;font-size:12px;margin-top:4px;">'
                             + '⚠ 스킨(<strong>'+ esc(skin) +'</strong>)이 필요한 훅을 제공하지 않아 사용할 수 없습니다.<br>'
                             + '누락 훅: '+ esc((info.missing||[]).join(', '))
                             + '</div>';
                    } else {
                        $tr.css('opacity', 1);
                        $cbs.prop('disabled', false);
                        if (requires.length) {
                            html = '<div style="color:#888;font-size:11px;margin-top:4px;">'
                                 + '필요 훅: '+ esc(requires.join(', '));
                            if (res.overridden) {
                                html += '<br><span style="color:#06a;">▶ 테마 오버라이드 적용된 스킨 경로 검사됨: <code>'
                                      + esc(res.skin_path) +'</code></span>';
                            }
                            html += '</div>';
                        }
                    }
                    $status.html(html);
                });
            })
            .fail(function(){ /* 무시 */ });
    }

    var lastSkin = $skin.val();
    $skin.on('change', function(){
        if ($(this).val() === lastSkin) return;
        lastSkin = $(this).val();
        recheck();
    });
})();
</script>

<script>
$(function(){
    $("#board_copy").click(function(){
        window.open(this.href, "win_board_copy", "left=10,top=10,width=500,height=400");
        return false;
    });

    $(".get_theme_galc").on("click", function() {
        if(!confirm("현재 테마의 게시판 이미지 설정을 적용하시겠습니까?"))
            return false;

        $.ajax({
            type: "POST",
            url: "./theme_config_load.php",
            cache: false,
            async: false,
            data: { type: "board" },
            dataType: "json",
            success: function(data) {
                if(data.error) {
                    alert(data.error);
                    return false;
                }

                var field = ['bo_gallery_cols','bo_gallery_width','bo_gallery_height','bo_mobile_gallery_width','bo_mobile_gallery_height','bo_image_width'];
                for (var i = 0; i < field.length; i++) {
                    var key = field[i];
                    if (typeof data[key] != "undefined" && data[key] !== "") {
                        $("input[name="+key+"]").val(data[key]);
                    }
                }
            }
        });
    });
});

function board_copy(bo_table) {
    window.open("./board_copy.php?bo_table="+bo_table, "BoardCopy", "left=10,top=10,width=500,height=200");
}

function set_point(f) {
    if (f.chk_grp_point.checked) {
        f.bo_read_point.value = 0;
        f.bo_write_point.value = 0;
        f.bo_comment_point.value = 0;
        f.bo_download_point.value = 0;
    } else {
        f.bo_read_point.value     = f.bo_read_point.defaultValue;
        f.bo_write_point.value    = f.bo_write_point.defaultValue;
        f.bo_comment_point.value  = f.bo_comment_point.defaultValue;
        f.bo_download_point.value = f.bo_download_point.defaultValue;
    }
}

function fboardform_submit(f)
{
    <?php echo get_editor_js("bo_content_head"); ?>
    <?php echo get_editor_js("bo_content_tail"); ?>
    <?php echo get_editor_js("bo_mobile_content_head"); ?>
    <?php echo get_editor_js("bo_mobile_content_tail"); ?>

    if (parseInt(f.bo_count_modify.value, 10) < 0) {
        alert("원글 수정 불가 댓글수는 0 이상 입력하셔야 합니다.");
        f.bo_count_modify.focus();
        return false;
    }

    if (parseInt(f.bo_count_delete.value, 10) < 1) {
        alert("원글 삭제 불가 댓글수는 1 이상 입력하셔야 합니다.");
        f.bo_count_delete.focus();
        return false;
    }

    return true;
}
</script>

<?php
include_once './admin.tail.php';
?>
