<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 기본값 정리
$sca   = isset($sca)   ? $sca   : '';
$stx   = isset($stx)   ? $stx   : '';
$sfl   = isset($sfl)   ? $sfl   : '';
$page  = isset($page)  ? (int)$page : 1;
$qstr  = isset($qstr)  ? $qstr  : '';
$wr_id = isset($wr_id) ? (int)$wr_id : 0;

// sop 정규화
$sop = isset($sop) ? strtolower($sop) : 'and';
if ($sop !== 'and' && $sop !== 'or') {
    $sop = 'and';
}

// head 스킨
if (isset($board_skin_path)) {
    @include_once($board_skin_path.'/view.head.skin.php');
}

$sql_search = "";

// 검색 여부
if ($sca !== '' || $stx !== '') {
    $sql_search = get_sql_search($sca, $sfl, $stx, $sop);
    $search_href = './board.php?bo_table='.$bo_table.'&amp;page='.$page.$qstr;
    $list_href   = './board.php?bo_table='.$bo_table;
} else {
    $search_href = '';
    $list_href   = './board.php?bo_table='.$bo_table.'&amp;page='.$page;
}

$prev = array();
$next = array();

if (empty($board['bo_use_list_view'])) {

    if ($sql_search !== '') {
        $sql_search = ' and '.$sql_search;
    }

    // 윗글
    $sql = " select wr_id, wr_subject
               from {$write_table}
              where wr_is_comment = 0
                and wr_num = '{$write['wr_num']}'
                and wr_reply < '{$write['wr_reply']}'
                {$sql_search}
           order by wr_num desc, wr_reply desc
              limit 1 ";
    $prev = sql_fetch($sql);

    if (!isset($prev['wr_id']) || !$prev['wr_id']) {
        $sql = " select wr_id, wr_subject
                   from {$write_table}
                  where wr_is_comment = 0
                    and wr_num < '{$write['wr_num']}'
                    {$sql_search}
               order by wr_num desc, wr_reply desc
                  limit 1 ";
        $prev = sql_fetch($sql);
    }

    // 아래글
    $sql = " select wr_id, wr_subject
               from {$write_table}
              where wr_is_comment = 0
                and wr_num = '{$write['wr_num']}'
                and wr_reply > '{$write['wr_reply']}'
                {$sql_search}
           order by wr_num, wr_reply
              limit 1 ";
    $next = sql_fetch($sql);

    if (!isset($next['wr_id']) || !$next['wr_id']) {
        $sql = " select wr_id, wr_subject
                   from {$write_table}
                  where wr_is_comment = 0
                    and wr_num > '{$write['wr_num']}'
                    {$sql_search}
               order by wr_num, wr_reply
                  limit 1 ";
        $next = sql_fetch($sql);
    }
}

// 이전글 링크
$prev_href = '';
if (isset($prev['wr_id']) && $prev['wr_id']) {
    $prev_wr_subject = get_text(cut_str($prev['wr_subject'], 255));
    $prev_href = './board.php?bo_table='.$bo_table.'&amp;wr_id='.$prev['wr_id'].$qstr;
}

// 다음글 링크
$next_href = '';
if (isset($next['wr_id']) && $next['wr_id']) {
    $next_wr_subject = get_text(cut_str($next['wr_subject'], 255));
    $next_href = './board.php?bo_table='.$bo_table.'&amp;wr_id='.$next['wr_id'].$qstr;
}

// 쓰기 링크
$write_href = '';
if (isset($member['mb_level'], $board['bo_write_level']) && $member['mb_level'] >= $board['bo_write_level']) {
    $write_href = './write.php?bo_table='.$bo_table;
}

// 답변 링크
$reply_href = '';
if (isset($member['mb_level'], $board['bo_reply_level']) && $member['mb_level'] >= $board['bo_reply_level']) {
    $reply_href = './write.php?w=r&amp;bo_table='.$bo_table.'&amp;wr_id='.$wr_id.$qstr;
}

// 수정/삭제 링크
$update_href = '';
$delete_href = '';

if (
    isset($member['mb_id'], $write['mb_id']) &&
    $member['mb_id'] &&
    $member['mb_id'] === $write['mb_id']
) {
    // 본인 글
    $update_href = './write.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$wr_id.'&amp;page='.$page.$qstr;
    set_session('ss_delete_token', $token = uniqid((string)time(), true));
    $delete_href = './delete.php?bo_table='.$bo_table.'&amp;wr_id='.$wr_id.'&amp;token='.$token.'&amp;page='.$page.urldecode($qstr);
} else if (!empty($is_admin)) {
    // 관리자
    $update_href = './write.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$wr_id.'&amp;page='.$page.$qstr;
    set_session('ss_delete_token', $token = uniqid((string)time(), true));
    $delete_href = './delete.php?bo_table='.$bo_table.'&amp;wr_id='.$wr_id.'&amp;token='.$token.'&amp;page='.$page.urldecode($qstr);
} else if (empty($write['mb_id'])) {
    // 비회원 글
    $update_href = './password.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$wr_id.'&amp;page='.$page.$qstr;
    $delete_href = './password.php?w=d&amp;bo_table='.$bo_table.'&amp;wr_id='.$wr_id.'&amp;page='.$page.$qstr;
}

// 복사/이동 링크 (관리자, 답글 없음)
$copy_href = '';
$move_href = '';
if (
    isset($write['wr_reply']) && $write['wr_reply'] === '' &&
    !empty($is_admin)
) {
    $copy_href = './move.php?sw=copy&amp;bo_table='.$bo_table.'&amp;wr_id='.$wr_id.'&amp;page='.$page.$qstr;
    $move_href = './move.php?sw=move&amp;bo_table='.$bo_table.'&amp;wr_id='.$wr_id.'&amp;page='.$page.$qstr;
}

// 스크랩/추천/비추천
$scrap_href  = '';
$good_href   = '';
$nogood_href = '';

if (!empty($is_member)) {
    $scrap_href = './scrap_popin.php?bo_table='.$bo_table.'&amp;wr_id='.$wr_id;

    if (!empty($board['bo_use_good'])) {
        $good_href = './good.php?bo_table='.$bo_table.'&amp;wr_id='.$wr_id.'&amp;good=good';
    }

    if (!empty($board['bo_use_nogood'])) {
        $nogood_href = './good.php?bo_table='.$bo_table.'&amp;wr_id='.$wr_id.'&amp;good=nogood';
    }
}

// view 데이터 생성
$view = get_view($write, $board, $board_skin_path);

if (isset($sfl) && strstr($sfl, 'subject')) {
    $view['subject'] = search_font($stx, $view['subject']);
}

// HTML 옵션
$html = 0;
if (!empty($view['wr_option'])) {
    if (strstr($view['wr_option'], 'html1')) {
        $html = 1;
    } else if (strstr($view['wr_option'], 'html2')) {
        $html = 2;
    }
}

// 내용 변환
$view['content'] = conv_content($view['wr_content'], $html);

if (isset($sfl) && strstr($sfl, 'content')) {
    $view['content'] = search_font($stx, $view['content']);
}

// {이미지:n:옵션} 처리
function conv_rich_content($matches)
{
    global $view;
    $idx = isset($matches[1]) ? $matches[1] : '';
    $opt = isset($matches[2]) ? $matches[2] : '';
    return view_image($view, $idx, $opt);
}

$view['rich_content'] = preg_replace_callback(
    "/{이미지\:([0-9]+)[:]?([^}]*)}/i",
    'conv_rich_content',
    $view['content']
);

// 서명
$is_signature = false;
$signature    = '';

if (!empty($board['bo_use_signature']) && !empty($view['mb_id'])) {
    $mb = get_member($view['mb_id']);
    if (!empty($mb['mb_signature'])) {
        $is_signature = true;
        $signature = conv_content($mb['mb_signature'], 1);
    }
}

// view 스킨
if (isset($board_skin_path)) {
    if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('view_top', '', $bo_table, $wr_id);
    include_once($board_skin_path.'/view.skin.php');
    @include_once($board_skin_path.'/view.tail.skin.php');
    if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('view_bottom', '', $bo_table, $wr_id);
}
?>
