<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// PHP8 대응: 기본값 초기화
if (!isset($board) || !is_array($board)) $board = array();
if (!isset($config) || !is_array($config)) $config = array();
if (!isset($write_table)) $write_table = '';
if (!isset($bo_table)) $bo_table = '';
if (!isset($qstr)) $qstr = '';
if (!isset($page) || !$page) $page = 1;
if (!isset($sop)) $sop = '';
if (!isset($sca)) $sca = '';
if (!isset($sfl)) $sfl = '';
if (!isset($stx)) $stx = '';
if (!isset($hash)) $hash = '';
if (!isset($log)) $log = '';
if (!isset($single)) $single = '';
if (!isset($sst)) $sst = '';
if (!isset($sod)) $sod = '';
if (!isset($group) || !is_array($group)) $group = array();
if (!isset($member) || !is_array($member)) $member = array('mb_id'=>'','mb_level'=>0);
if (!isset($is_member)) $is_member = false;
if (!isset($is_admin)) $is_admin = '';
if (!isset($board_skin_path)) $board_skin_path = '';
if (!isset($board_skin_url)) $board_skin_url = '';

if (!isset($board['bo_use_category'])) $board['bo_use_category'] = 0;
if (!isset($board['bo_category_list'])) $board['bo_category_list'] = '';
if (!isset($board['bo_notice'])) $board['bo_notice'] = '';
if (!isset($board['bo_page_rows'])) $board['bo_page_rows'] = 15;
if (!isset($board['bo_mobile_page_rows'])) $board['bo_mobile_page_rows'] = $board['bo_page_rows'];
if (!isset($board['bo_mobile_subject_len'])) $board['bo_mobile_subject_len'] = 20;
if (!isset($board['bo_subject_len'])) $board['bo_subject_len'] = 60;
if (!isset($board['bo_gallery_cols'])) $board['bo_gallery_cols'] = 1;
if (!isset($board['bo_sort_field'])) $board['bo_sort_field'] = '';
if (!isset($board['bo_use_rss_view'])) $board['bo_use_rss_view'] = 0;
if (!isset($board['bo_write_level'])) $board['bo_write_level'] = 1;
if (!isset($board['bo_admin'])) $board['bo_admin'] = '';
if (!isset($config['cf_search_part'])) $config['cf_search_part'] = 10000;
if (!isset($config['cf_mobile_pages'])) $config['cf_mobile_pages'] = 5;
if (!isset($config['cf_write_pages'])) $config['cf_write_pages'] = 10;

// 카테고리 처리
$is_category = false;
$category_option = '';

if ($board['bo_use_category']) {
    $is_category = true;
    $category_href = G5_BBS_URL.'/board.php?bo_table='.$bo_table;

    $category_option .= '<li><a href="'.$category_href.'"';
    if ($sca === '')
        $category_option .= ' id="bo_cate_on"';
    $category_option .= '>전체</a></li>';

    $categories = explode('|', $board['bo_category_list']);
    for ($i=0; $i<count($categories); $i++) {
        $category = trim($categories[$i]);
        if ($category === '') continue;

        $category_option .= '<li><a href="'.$category_href.'&amp;sca='.urlencode($category).'"';
        if ($category === $sca)
            $category_option .= ' id="bo_cate_on"';
        $category_option .= '>'.$category.'</a></li>';
    }
}

// sop 처리
$sop = strtolower($sop);
if ($sop !== 'and' && $sop !== 'or')
    $sop = 'and';

// 검색어 정리
$stx = trim($stx);

// 검색 / 분류 / 해시 / 로그가 있는 경우
if ($sca !== '' || $stx !== '' || $hash !== '' || $log !== '') {

    if (!function_exists('get_sql_search_mmb')) {
        // 해당 함수 없으면 치명적이라 바로 종료
        alert('검색 처리 함수가 정의되어 있지 않습니다.');
    }

    $sql_search = get_sql_search_mmb($sca, $sfl, $stx, $hash, $sop, $log, $single);

    // 최소 wr_num
    $sql = " select MIN(wr_num) as min_wr_num from {$write_table} ";
    $row = sql_fetch($sql);
    $min_spt = isset($row['min_wr_num']) ? (int)$row['min_wr_num'] : 0;

    if (!isset($spt) || !$spt) $spt = $min_spt;

    $sql_search .= " and (wr_num between {$spt} and ({$spt} + {$config['cf_search_part']})) ";

    // 원글 기준 count
    $sql = " SELECT COUNT(DISTINCT wr_parent) AS cnt FROM {$write_table} WHERE {$sql_search} ";
    $row = sql_fetch($sql);
    $total_count = isset($row['cnt']) ? (int)$row['cnt'] : 0;

} else {
    $sql_search = "";
    $total_count = isset($board['bo_count_write']) ? (int)$board['bo_count_write'] : 0;
}

// 페이지당 목록수
if (defined('G5_IS_MOBILE') && G5_IS_MOBILE) {
    $page_rows = (int)$board['bo_mobile_page_rows'];
    if ($page_rows <= 0) $page_rows = (int)$board['bo_page_rows'];
    if ($page_rows <= 0) $page_rows = 15;
} else {
    $page_rows = (int)$board['bo_page_rows'];
    if ($page_rows <= 0) $page_rows = 15;
}
$list_page_rows = $page_rows;

// 페이지 최소값
if ($page < 1) $page = 1;

// 날짜
$today2 = defined('G5_TIME_YMD') ? G5_TIME_YMD : date('Y-m-d');

// 리스트 배열
$list = array();
$i = 0;
$notice_count = 0;
$notice_array = array();

// 공지 처리 (검색/분류/해시/로그 없을 때만)
if ($sca === '' && $stx === '' && $hash === '' && $log === '') {

    $arr_notice = array();
    if (trim($board['bo_notice']) !== '')
        $arr_notice = explode(',', trim($board['bo_notice']));

    $from_notice_idx = ($page - 1) * $page_rows;
    if ($from_notice_idx < 0) $from_notice_idx = 0;

    $board_notice_count = count($arr_notice);

    for ($k=0; $k<$board_notice_count; $k++) {
        $wr_id = trim($arr_notice[$k]);
        if ($wr_id === '') continue;

        $row = sql_fetch(" select * from {$write_table} where wr_id = '{$wr_id}' ");
        if (!isset($row['wr_id']) || !$row['wr_id']) continue;

        $notice_array[] = $row['wr_id'];

        if ($k < $from_notice_idx) continue;

        $list[$i] = get_list(
            $row,
            $board,
            $board_skin_url,
            (defined('G5_IS_MOBILE') && G5_IS_MOBILE) ? $board['bo_mobile_subject_len'] : $board['bo_subject_len']
        );
        $list[$i]['is_notice'] = true;

        $i++;
        $notice_count++;

        if ($notice_count >= $list_page_rows)
            break;
    }
}

// 전체 페이지 및 시작 인덱스
$total_page  = $page_rows > 0 ? ceil($total_count / $page_rows) : 0;
$from_record = ($page - 1) * $page_rows;

// 공지 반영
if (!empty($notice_array)) {

    $from_record -= count($notice_array);
    if ($from_record < 0) $from_record = 0;

    if ($notice_count > 0)
        $page_rows -= $notice_count;

    if ($page_rows < 0)
        $page_rows = $list_page_rows;
}

// 관리자 체크박스 권한
$is_checkbox = false;
if ($is_member) {
    $gr_admin = isset($group['gr_admin']) ? $group['gr_admin'] : '';
    $bo_admin = isset($board['bo_admin']) ? $board['bo_admin'] : '';
    if ($is_admin || $gr_admin === $member['mb_id'] || $bo_admin === $member['mb_id']) {
        $is_checkbox = true;
    }
}

// 정렬용 qstr2
$qstr2 = 'bo_table='.$bo_table.'&amp;sop='.$sop;

// 갤러리 컬럼
$bo_gallery_cols = (isset($board['bo_gallery_cols']) && (int)$board['bo_gallery_cols'] > 0) ? (int)$board['bo_gallery_cols'] : 1;
$td_width = (int)(100 / $bo_gallery_cols);

// 정렬필드 결정
if ($sst === '') {
    if ($board['bo_sort_field']) {
        $sst = $board['bo_sort_field'];
    } else {
        $sst = "wr_num, wr_reply";
        $sod = "";
    }
} else {
    // 허용 필드만 사용
    if (!preg_match("/^(wr_datetime|wr_hit|wr_good|wr_nogood)$/i", $sst)) {
        $sst = "";
    }
    if ($sst === '') {
        $sst = "wr_num, wr_reply";
        $sod = "";
    }
}

$sql_order = '';
if ($sst !== '') {
    $sql_order = " order by {$sst} {$sod} ";
}

// 메인 쿼리
if ($sca !== '' || $stx !== '' || $hash !== '' || $log !== '') {
    $sql = " select distinct wr_parent from {$write_table} where {$sql_search} {$sql_order} limit {$from_record}, {$page_rows} ";
} else {
    $sql = " select * from {$write_table} where wr_is_comment = 0 ";
    if (!empty($notice_array)) {
        $sql .= " and wr_id not in (".implode(', ', array_map('intval', $notice_array)).") ";
    }
    $sql .= " {$sql_order} limit {$from_record}, {$page_rows} ";
}

// 목록 생성 (페이지당 목록 수가 0 초과일 때만)
if ($page_rows > 0) {
    $result = sql_query($sql);
    $k = 0;

    while ($row = sql_fetch_array($result)) {

        // 검색 모드일 때는 wr_parent 기준으로 다시 로드
        if ($sca !== '' || $stx !== '' || $hash !== '' || $log !== '') {
            if (!isset($row['wr_parent']) || !$row['wr_parent']) continue;
            $row = sql_fetch(" select * from {$write_table} where wr_id = '{$row['wr_parent']}' ");
            if (!isset($row['wr_id']) || !$row['wr_id']) continue;
        }

        $list[$i] = get_list(
            $row,
            $board,
            $board_skin_url,
            (defined('G5_IS_MOBILE') && G5_IS_MOBILE) ? $board['bo_mobile_subject_len'] : $board['bo_subject_len']
        );

        if (strstr($sfl, 'subject')) {
            $list[$i]['subject'] = search_font($stx, $list[$i]['subject']);
        }
        

        $list[$i]['is_notice'] = false;

        $list_num = $total_count - ($page - 1) * $list_page_rows - $notice_count;
        $list[$i]['num'] = $list_num - $k;

        $i++;
        $k++;
    }
}

// 해시, 로그 qstr 구성
$hash_val = '';
if (isset($_REQUEST['hash'])) {
    $hash_val = $_REQUEST['hash'];
}
$log_val = '';
if (isset($_REQUEST['log'])) {
    $log_val = $_REQUEST['log'];
}

$hash_pattern = "/\\#([0-9a-zA-Z가-힣_]*)/";
$real_str = preg_replace($hash_pattern, '%23$1', $hash_val);

$qstr .= "&amp;hash=".$real_str."&amp;log=".$log_val."&amp;single=".$single;

// 페이징
$write_pages = get_paging(
    (defined('G5_IS_MOBILE') && G5_IS_MOBILE) ? $config['cf_mobile_pages'] : $config['cf_write_pages'],
    $page,
    $total_page,
    './board.php?bo_table='.$bo_table.$qstr.'&amp;page='
);

// 검색/분류 모드일 때 이전/다음 검색 구간 링크
$list_href = '';
$prev_part_href = '';
$next_part_href = '';

if ($sca !== '' || $stx !== '' || $hash !== '' || $log !== '') {
    $list_href = './board.php?bo_table='.$bo_table;

    $patterns = array('#&amp;page=[0-9]*#', '#&amp;spt=[0-9\-]*#');

    // 이전 검색
    if (isset($spt) && isset($min_spt)) {
        $prev_spt = $spt - $config['cf_search_part'];
        if ($prev_spt >= $min_spt) {
            $qstr1 = preg_replace($patterns, '', $qstr);
            $prev_part_href = './board.php?bo_table='.$bo_table.$qstr1.'&amp;spt='.$prev_spt.'&amp;page=1';
            $write_pages = page_insertbefore($write_pages, '<a href="'.$prev_part_href.'" class="pg_page pg_prev">이전검색</a>');
        }
    }

    // 다음 검색 (원본 로직 유지)
    if (isset($spt)) {
        $next_spt = $spt + $config['cf_search_part'];
        if ($next_spt < 0) {
            $qstr1 = preg_replace($patterns, '', $qstr);
            $next_part_href = './board.php?bo_table='.$bo_table.$qstr1.'&amp;spt='.$next_spt.'&amp;page=1';
            $write_pages = page_insertafter($write_pages, '<a href="'.$next_part_href.'" class="pg_page pg_end">다음검색</a>');
        }
    }
}

// 글쓰기 링크
$write_href = '';
if ($member['mb_level'] >= $board['bo_write_level']) {
    $write_href = './write.php?bo_table='.$bo_table;
}

// nobr 처리
$nobr_begin = '';
$nobr_end = '';
if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match("/gecko|firefox/i", $_SERVER['HTTP_USER_AGENT'])) {
    $nobr_begin = '<nobr>';
    $nobr_end   = '</nobr>';
}

// RSS
$rss_href = '';
if ($board['bo_use_rss_view']) {
    $rss_href = './rss.php?bo_table='.$bo_table;
}

// 검색어 출력용 필터
$stx = get_text(stripslashes($stx));

if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('list_top', '', $bo_table);
include_once($board_skin_path.'/list.skin.php');
if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('list_bottom', '', $bo_table);
?>
