<?php
include_once('./_common.php');
include_once(G5_EDITOR_LIB);

// 기본 변수 방어 (PHP 8 대응)
if (!isset($board) || !is_array($board)) $board = array();
if (!isset($write) || !is_array($write)) $write = array();
if (!isset($member) || !is_array($member)) $member = array('mb_id'=>'','mb_level'=>0,'mb_point'=>0);
if (!isset($group) || !is_array($group)) $group = array();
if (!isset($g5) || !is_array($g5)) $g5 = array();

if (!isset($bo_table)) $bo_table = $_REQUEST['bo_table'] ?? '';
if (!isset($wr_id)) $wr_id = (int)($_REQUEST['wr_id'] ?? 0);
if (!isset($w)) $w = $_REQUEST['w'] ?? '';
if (!isset($qstr)) $qstr = '';
if (!isset($is_member)) $is_member = isset($member['mb_id']) && $member['mb_id'] ? true : false;
if (!isset($is_admin)) $is_admin = '';
if (!isset($is_guest)) $is_guest = !$is_member;
if (!isset($write_table) && isset($g5['write_prefix']) && $bo_table) $write_table = $g5['write_prefix'].$bo_table;
if (!isset($config) || !is_array($config)) $config = array();


if (!isset($config['cf_editor'])) $config['cf_editor'] = '';
if (!isset($board['bo_table'])) $board['bo_table'] = '';
if (!isset($board['bo_notice'])) $board['bo_notice'] = '';
if (!isset($board['bo_device'])) $board['bo_device'] = '';
if (!isset($board['bo_write_level'])) $board['bo_write_level'] = 1;
if (!isset($board['bo_reply_level'])) $board['bo_reply_level'] = 1;
if (!isset($board['bo_html_level'])) $board['bo_html_level'] = 1;
if (!isset($board['bo_link_level'])) $board['bo_link_level'] = 1;
if (!isset($board['bo_upload_level'])) $board['bo_upload_level'] = 1;
if (!isset($board['bo_use_dhtml_editor'])) $board['bo_use_dhtml_editor'] = 0;
if (!isset($board['bo_use_secret'])) $board['bo_use_secret'] = 0;
if (!isset($board['bo_use_email'])) $board['bo_use_email'] = 0;
if (!isset($board['bo_use_file_content'])) $board['bo_use_file_content'] = 0;
if (!isset($board['bo_upload_count'])) $board['bo_upload_count'] = 0;
if (!isset($board['bo_upload_size'])) $board['bo_upload_size'] = 0;
if (!isset($board['bo_write_min'])) $board['bo_write_min'] = 0;
if (!isset($board['bo_write_max'])) $board['bo_write_max'] = 0;
if (!isset($board['bo_table_width'])) $board['bo_table_width'] = 0;
if (!isset($board['bo_insert_content'])) $board['bo_insert_content'] = '';
if (!isset($board['bo_subject'])) $board['bo_subject'] = '';
if (!isset($board['bo_mobile_subject'])) $board['bo_mobile_subject'] = '';
if (!isset($board['bo_use_category'])) $board['bo_use_category'] = 0;
if (!isset($board['bo_category_list'])) $board['bo_category_list'] = '';
if (!isset($board['bo_use_rss_view'])) $board['bo_use_rss_view'] = 0;
if (!isset($board['bo_page_rows'])) $board['bo_page_rows'] = 15;
if (!isset($board['bo_mobile_skin'])) $board['bo_mobile_skin'] = '';
if (!isset($board['bo_skin'])) $board['bo_skin'] = '';

if (!isset($config['cf_mobile_pages'])) $config['cf_mobile_pages'] = 5;
if (!isset($config['cf_write_pages'])) $config['cf_write_pages'] = 10;

// 게시판 유효성
if (!$board['bo_table']) {
    alert('존재하지 않는 게시판입니다.', G5_URL);
}

if (!$bo_table) {
    alert("bo_table 값이 넘어오지 않았습니다.\\nwrite.php?bo_table=code 와 같은 방식으로 넘겨 주세요.", G5_URL);
}

// 디바이스 체크
check_device($board['bo_device']);

// 공지 배열
$notice_array = array();
if (trim($board['bo_notice']) !== '') {
    $notice_array = explode(',', trim($board['bo_notice']));
}

// w 값 검증
if (!($w === '' || $w === 'u' || $w === 'r')) {
    alert('w 값이 제대로 넘어오지 않았습니다.');
}

// 수정/답변 시 원글 존재 확인
if ($w === 'u' || $w === 'r') {
    if (isset($write['wr_id']) && $write['wr_id']) {
        for ($i=1; $i<=10; $i++) {
            $key = 'wr_'.$i;
            $$key = isset($write[$key]) ? $write[$key] : '';
        }
    } else {
        alert("글이 존재하지 않습니다.\\n삭제되었거나 이동된 경우입니다.", G5_URL);
    }
}

// 글쓰기
if ($w === '') {

    if ($wr_id) {
        alert('글쓰기에는 $wr_id 값을 사용하지 않습니다.', G5_BBS_URL.'/board.php?bo_table='.$bo_table);
    }

    if ($member['mb_level'] < $board['bo_write_level']) {
        if ($member['mb_id']) {
            alert('글을 쓸 권한이 없습니다.');
        } else {
            alert("글을 쓸 권한이 없습니다.\\n회원이시라면 로그인 후 이용해 보십시오.", './login.php?'.$qstr.'&amp;url='.urlencode($_SERVER['SCRIPT_NAME'].'?bo_table='.$bo_table));
        }
    }

    if ($is_member) {
        $tmp_point = ($member['mb_point'] > 0) ? $member['mb_point'] : 0;
        if ($tmp_point + $board['bo_write_point'] < 0 && !$is_admin) {
            alert('보유하신 포인트('.number_format($member['mb_point']).')가 없거나 모자라서 글쓰기('.number_format($board['bo_write_point']).')가 불가합니다.\\n\\n포인트를 적립하신 후 다시 글쓰기 해 주십시오.');
        }
    }

    $title_msg = '글쓰기';

// 글수정
} else if ($w === 'u') {

    if ($member['mb_id'] && isset($write['mb_id']) && $write['mb_id'] == $member['mb_id']) {
        // 본인 수정 허용
    } else if ($member['mb_level'] < $board['bo_write_level']) {
        if ($member['mb_id']) {
            alert('글을 수정할 권한이 없습니다.');
        } else {
            alert('글을 수정할 권한이 없습니다.\\n\\n회원이시라면 로그인 후 이용해 보십시오.', './login.php?'.$qstr.'&amp;url='.urlencode($_SERVER['SCRIPT_NAME'].'?bo_table='.$bo_table));
        }
    }

    $len = isset($write['wr_reply']) ? strlen($write['wr_reply']) : 0;
    if ($len < 0) $len = 0;
    $reply = $len ? substr($write['wr_reply'], 0, $len) : '';

    $sql = " select count(*) as cnt
               from {$write_table}
              where wr_reply like '{$reply}%'
                and wr_id <> '{$write['wr_id']}'
                and wr_num = '{$write['wr_num']}'
                and wr_is_comment = 0 ";
    $row = sql_fetch($sql);
    if (isset($row['cnt']) && $row['cnt'] && !$is_admin) {
        alert('이 글과 관련된 답변글이 존재하므로 수정 할 수 없습니다.\\n\\n답변글이 있는 원글은 수정할 수 없습니다.');
    }

    $sql = " select count(*) as cnt
               from {$write_table}
              where wr_parent = '{$wr_id}'
                and mb_id <> '{$member['mb_id']}'
                and wr_is_comment = 1 ";
    $row = sql_fetch($sql);
    if ($board['bo_count_modify'] && isset($row['cnt']) && $row['cnt'] >= $board['bo_count_modify'] && !$is_admin) {
        alert('이 글과 관련된 댓글이 존재하므로 수정 할 수 없습니다.\\n\\n댓글이 '.$board['bo_count_modify'].'건 이상 달린 원글은 수정할 수 없습니다.');
    }

    $title_msg = '글수정';

// 답변
} else if ($w === 'r') {

    if ($member['mb_level'] < $board['bo_reply_level']) {
        if ($member['mb_id']) {
            alert('글을 답변할 권한이 없습니다.');
        } else {
            alert('답변글을 작성할 권한이 없습니다.\\n\\n회원이시라면 로그인 후 이용해 보십시오.', './login.php?'.$qstr.'&amp;url='.urlencode($_SERVER['SCRIPT_NAME'].'?bo_table='.$bo_table));
        }
    }

    $tmp_point = isset($member['mb_point']) ? $member['mb_point'] : 0;
    if ($tmp_point + $board['bo_write_point'] < 0 && !$is_admin) {
        alert('보유하신 포인트('.number_format($member['mb_point']).')가 없거나 모자라서 글답변('.number_format($board['bo_comment_point']).')가 불가합니다.\\n\\n포인트를 적립하신 후 다시 글답변 해 주십시오.');
    }

    if (in_array((int)$wr_id, array_map('intval', $notice_array))) {
        alert('공지에는 답변 할 수 없습니다.');
    }

    if (isset($write['wr_is_comment']) && $write['wr_is_comment']) {
        alert('정상적인 접근이 아닙니다.');
    }

    if (isset($write['wr_option']) && strstr($write['wr_option'], 'secret')) {
        if (isset($write['mb_id']) && $write['mb_id']) {
            if (!($write['mb_id'] == $member['mb_id'] || $is_admin)) {
                alert('비밀글에는 자신 또는 관리자만 답변이 가능합니다.');
            }
        } else {
            if (!$is_admin) {
                alert('비회원의 비밀글에는 답변이 불가합니다.');
            }
        }
    }

    $reply_array = $write;

    if (strlen($reply_array['wr_reply']) == 10)
        alert('더 이상 답변하실 수 없습니다.\\n\\n답변은 10단계 까지만 가능합니다.');

    $reply_len = strlen($reply_array['wr_reply']) + 1;

    if ($board['bo_reply_order']) {
        $begin_reply_char = 'A';
        $end_reply_char = 'Z';
        $reply_number = 1;
        $sql = " select MAX(SUBSTRING(wr_reply, {$reply_len}, 1)) as reply
                   from {$write_table}
                  where wr_num = '{$reply_array['wr_num']}'
                    and SUBSTRING(wr_reply, {$reply_len}, 1) <> '' ";
    } else {
        $begin_reply_char = 'Z';
        $end_reply_char = 'A';
        $reply_number = -1;
        $sql = " select MIN(SUBSTRING(wr_reply, {$reply_len}, 1)) as reply
                   from {$write_table}
                  where wr_num = '{$reply_array['wr_num']}'
                    and SUBSTRING(wr_reply, {$reply_len}, 1) <> '' ";
    }

    if ($reply_array['wr_reply']) {
        $sql .= " and wr_reply like '{$reply_array['wr_reply']}%' ";
    }

    $row = sql_fetch($sql);

    if (!isset($row['reply']) || !$row['reply']) {
        $reply_char = $begin_reply_char;
    } else if ($row['reply'] == $end_reply_char) {
        alert('더 이상 답변하실 수 없습니다.\\n\\n답변은 26개 까지만 가능합니다.');
    } else {
        $reply_char = chr(ord($row['reply']) + $reply_number);
    }

    $reply = $reply_array['wr_reply'] . $reply_char;

    $title_msg = '글답변';
    if (isset($write['wr_subject']))
        $write['wr_subject'] = 'Re: '.$write['wr_subject'];
}

// 그룹 접근 제한
if (!empty($group['gr_use_access'])) {
    if ($is_guest) {
        alert("접근 권한이 없습니다.\\n\\n회원이시라면 로그인 후 이용해 보십시오.", 'login.php?'.$qstr.'&amp;url='.urlencode($_SERVER['SCRIPT_NAME'].'?bo_table='.$bo_table));
    }

    $gr_admin = isset($group['gr_admin']) ? $group['gr_admin'] : '';
    $bo_admin = isset($board['bo_admin']) ? $board['bo_admin'] : '';

    if (!($is_admin || $gr_admin == $member['mb_id'] || $bo_admin == $member['mb_id'])) {
        $sql = " select gr_id
                   from {$g5['group_member_table']}
                  where gr_id = '{$board['gr_id']}'
                    and mb_id = '{$member['mb_id']}' ";
        $row = sql_fetch($sql);
        if (!isset($row['gr_id']) || !$row['gr_id']) {
            alert('접근 권한이 없으므로 글쓰기가 불가합니다.\\n\\n궁금하신 사항은 관리자에게 문의 바랍니다.');
        }
    }
}

// 글자수 제한 (avo8.0: bo_write_limit_text 만 사용 — max 만 의미 있음)
if ($is_admin || $board['bo_use_dhtml_editor']) {
    $write_min = 0;
    $write_max = 0;
} else {
    $write_min = 0;
    $write_max = (int)($board['bo_write_limit_text'] ?? 0);
}

// 제목
$g5['title'] = ((defined('G5_IS_MOBILE') && G5_IS_MOBILE && isset($board['bo_mobile_subject']) && $board['bo_mobile_subject']) ? $board['bo_mobile_subject'] : $board['bo_subject']).' '.$title_msg;

// 공지 체크
$is_notice = false;
$notice_checked = '';
if ($is_admin && $w != 'r') {
    $is_notice = true;
    if ($w === 'u' && empty($write['wr_reply'])) {
        if (in_array((int)$wr_id, array_map('intval', $notice_array))) {
            $notice_checked = 'checked';
        }
    }
}

// HTML 권한
$is_html = ($member['mb_level'] >= $board['bo_html_level']) ? true : false;

// 비밀글
$is_secret = $board['bo_use_secret'];

// 메일
$is_mail = false;
if (false && $board['bo_use_email'])
    $is_mail = true;

$recv_email_checked = '';
if ($w === '' || (isset($write['wr_option']) && strstr($write['wr_option'], 'mail')))
    $recv_email_checked = 'checked';

// 비회원 입력항목
$is_name = false;
$is_password = false;
$is_email = false;
$is_homepage = false;
if ($is_guest || ($is_admin && $w === 'u' && isset($write['mb_id']) && $member['mb_id'] != $write['mb_id'])) {
    $is_name = $is_password = $is_email = $is_homepage = true;
}

// 카테고리
$is_category = false;
$category_option = '';
if ($board['bo_use_category']) {
    $ca_name = isset($write['ca_name']) ? $write['ca_name'] : '';
    $category_option = get_category_option($bo_table, $ca_name);
    $is_category = true;
}

// 링크/파일 권한
$is_link = ($member['mb_level'] >= $board['bo_link_level']) ? true : false;
$is_file = ($member['mb_level'] >= $board['bo_upload_level']) ? true : false;
$is_file_content = $board['bo_use_file_content'] ? true : false;

$file_count = (int)$board['bo_upload_count'];

// 작성자 기본값
$name = '';
$email = '';
$homepage = '';

if ($w === '' || $w === 'r') {
    if ($is_member && isset($write['wr_name'])) {
        $name = get_text(cut_str(stripslashes($write['wr_name']), 20));
    }
}

// 옵션 체크
$html_checked = '';
$html_value = '';
$secret_checked = '';

if ($w === '') {

    $password_required = 'required';

} else if ($w === 'u') {

    $password_required = '';

    if (!$is_admin) {
        if (!($is_member && isset($write['mb_id']) && $member['mb_id'] == $write['mb_id'])) {
            if (!check_password($wr_password, $write['wr_password'])) {
                alert('비밀번호가 틀립니다.');
            }
        }
    }

    $name = get_text(cut_str(stripslashes($write['wr_name']),20));

    for ($i=1; $i<=G5_LINK_COUNT; $i++) {
        $key = 'wr_link'.$i;
        if (isset($write[$key])) {
            $write[$key] = get_text($write[$key]);
            $link[$i] = $write[$key];
        } else {
            $link[$i] = '';
        }
    }

    if (isset($write['wr_option']) && strstr($write['wr_option'], 'html1')) {
        $html_checked = 'checked';
        $html_value = 'html1';
    } else if (isset($write['wr_option']) && strstr($write['wr_option'], 'html2')) {
        $html_checked = 'checked';
        $html_value = 'html2';
    }

    if (isset($write['wr_option']) && strstr($write['wr_option'], 'secret')) {
        $secret_checked = 'checked';
    }

    $file = get_file($bo_table, $wr_id);
    if (isset($file['count']) && $file_count < $file['count'])
        $file_count = $file['count'];

} else if ($w === 'r') {

    if (isset($write['wr_option']) && strstr($write['wr_option'], 'secret')) {
        $is_secret = true;
        $secret_checked = 'checked';
    }

    $password_required = 'required';

    for ($i=1; $i<=G5_LINK_COUNT; $i++) {
        $key = 'wr_link'.$i;
        if (isset($write[$key]))
            $write[$key] = get_text($write[$key]);
    }
}

// 세션 저장
set_session('ss_bo_table', $bo_table);
set_session('ss_wr_id', $wr_id);

// 제목, 내용
$subject = '';
if (isset($write['wr_subject'])) {
    $subject = str_replace('"', "&#034;", get_text(cut_str($write['wr_subject'], 255), 0));
}

$content = '';
if ($w === '') {
    $content = $board['bo_insert_content'];
} else if ($w === 'r') {
    if (!isset($write['wr_option']) || !strstr($write['wr_option'], 'html')) {
        $content = "\n\n\n &gt; "
                 ."\n &gt; "
                 ."\n &gt; ".str_replace("\n", "\n> ", get_text($write['wr_content'], 0))
                 ."\n &gt; "
                 ."\n &gt; ";
    }
} else {
    $content = get_text($write['wr_content'], 0);
}

$upload_max_filesize = number_format($board['bo_upload_size']).' 바이트';

// 폭
$width = (int)$board['bo_table_width'];
if ($width <= 100 && $width > 0)
    $width .= '%';
else if ($width > 0)
    $width .= 'px';
else
    $width = '100%';

// 에디터 사용 여부
$is_dhtml_editor = false;
$is_dhtml_editor_use = false;
$editor_content_js = '';

if (!function_exists('is_mobile')) {
    function is_mobile() { return false; }
}

if (!is_mobile() || (defined('G5_IS_MOBILE_DHTML_USE') && G5_IS_MOBILE_DHTML_USE)) {
    $is_dhtml_editor_use = true;
}

if ($config['cf_editor'] && $is_dhtml_editor_use && $board['bo_use_dhtml_editor'] && $member['mb_level'] >= $board['bo_html_level']) {
    $is_dhtml_editor = true;
    if (defined('G5_EDITOR_PATH') && defined('G5_EDITOR_URL')) {
        if (is_file(G5_EDITOR_PATH.'/'.$config['cf_editor'].'/autosave.editor.js')) {
            $editor_content_js = '<script src="'.G5_EDITOR_URL.'/'.$config['cf_editor'].'/autosave.editor.js"></script>'.PHP_EOL;
        }
    }
}

$editor_html = editor_html('wr_content', $content, $is_dhtml_editor);
$editor_js  = get_editor_js('wr_content', $is_dhtml_editor);
$editor_js .= chk_editor_js('wr_content', $is_dhtml_editor);

// 임시저장 개수
$autosave_count = autosave_count($member['mb_id']);

include_once(G5_PATH.'/head.sub.php');
@include_once($board_skin_path.'/write.head.skin.php');
include_once('./board_head.php');

$action_url = https_url(G5_BBS_DIR).'/write_update.php';

echo '<!-- skin : '.(defined('G5_IS_MOBILE') && G5_IS_MOBILE ? $board['bo_mobile_skin'] : $board['bo_skin']).' -->';

include_once($board_skin_path.'/write.skin.php');

include_once('./board_tail.php');
@include_once($board_skin_path.'/write.tail.skin.php');
include_once(G5_PATH.'/tail.sub.php');
?>
