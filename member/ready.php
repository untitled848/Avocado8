<?php
include_once('./_common.php');
perm_check_page('ready');

$g5['title'] = "신청자 대기 목록";
include_once('./_head.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/style.member.css">', 0);

// 검색 및 필터 입력 기본값
$stx     = isset($stx) ? trim($stx) : '';
$sfl     = isset($sfl) ? trim($sfl) : '';
$s_class = isset($s_class) ? trim($s_class) : '';
$s_side  = isset($s_side) ? trim($s_side) : '';
$page    = isset($page) && (int)$page > 0 ? (int)$page : 1;

// 검색 where 절 공통
$sql_search    = '';
$search_arcode = false;

// 추가 항목 설정 데이터 (비공개 H 제외)
$ch_ar = array();
$ar_result = sql_query("SELECT * FROM {$g5['article_table']} WHERE ar_secret != 'H' ORDER BY ar_order ASC");
for ($i = 0; $row = sql_fetch_array($ar_result); $i++) {
    $ch_ar[$i] = $row;
}

// Wave10: 현재 시기 + 일반 회원 권한(cf_register_level) 기준으로 활성 side/class 만 노출.
//   - 신규 가입자가 선택 가능한 옵션을 그대로 보여주는 것이 의도이므로 가입 권한을 사용.
//   - 폴백: 권한 필터로 0개가 나오면 권한 무시하고 다시 조회 (사이드/종족 운영 누락 방지).
$_view_time = isset($_GET['time']) ? (int)$_GET['time'] : (function_exists('get_current_time') ? (int)get_current_time() : 1);
if ($_view_time < 1) $_view_time = 1;
$_view_level = isset($config['cf_register_level']) ? (int)$config['cf_register_level'] : 2;
if ($_view_level < 1) $_view_level = 1;
require_once G5_EXTEND_PATH.'/02_side_class_time.lib.php';

// 세력 정보
$ch_si = array();
if (!empty($config['cf_side_title'])) {
    $_sides = function_exists('get_sides_for_time') ? get_sides_for_time($_view_time, $_view_level) : null;
    // 폴백 1: 헬퍼가 NULL 반환(매핑 미설정) → 권한만 적용
    if (!is_array($_sides)) {
        $side_result = sql_query("SELECT si_id, si_name FROM {$g5['side_table']} WHERE si_auth <= '{$_view_level}' ORDER BY si_id ASC");
        $_sides = array();
        while ($row = sql_fetch_array($side_result)) $_sides[] = $row;
    }
    // 폴백 2: 권한 필터로 0건 → 권한 무시하고 활성 시기 매핑만 사용
    if (empty($_sides)) {
        $_sides = function_exists('get_sides_for_time') ? get_sides_for_time($_view_time, 0) : null;
        if (!is_array($_sides)) {
            $side_result = sql_query("SELECT si_id, si_name FROM {$g5['side_table']} ORDER BY si_id ASC");
            $_sides = array();
            while ($row = sql_fetch_array($side_result)) $_sides[] = $row;
        }
    }
    foreach ($_sides as $i => $row) {
        $ch_si[$i] = array(
            'id'   => $row['si_id'],
            'name' => $row['si_name'],
        );
    }
}

// 종족 정보
$ch_cl = array();
if (!empty($config['cf_class_title'])) {
    $_classes = function_exists('get_classes_for_time') ? get_classes_for_time($_view_time, $_view_level) : null;
    if (!is_array($_classes)) {
        $class_result = sql_query("SELECT cl_id, cl_name FROM {$g5['class_table']} WHERE cl_auth <= '{$_view_level}' ORDER BY cl_id ASC");
        $_classes = array();
        while ($row = sql_fetch_array($class_result)) $_classes[] = $row;
    }
    if (empty($_classes)) {
        $_classes = function_exists('get_classes_for_time') ? get_classes_for_time($_view_time, 0) : null;
        if (!is_array($_classes)) {
            $class_result = sql_query("SELECT cl_id, cl_name FROM {$g5['class_table']} ORDER BY cl_id ASC");
            $_classes = array();
            while ($row = sql_fetch_array($class_result)) $_classes[] = $row;
        }
    }
    foreach ($_classes as $i => $row) {
        $ch_cl[$i] = array(
            'id'   => $row['cl_id'],
            'name' => $row['cl_name'],
        );
    }
}

/**
 * 검색 조건 구성
 */
if ($stx !== '') {
    $check_sfl = explode('||', $sfl);

    if (isset($check_sfl[0]) && $check_sfl[0] === 'arcode' && !empty($check_sfl[1])) {
        // 추가 필드 검색
        $search_arcode = true;

        $sql_common = "
            FROM {$g5['character_table']} ch
            JOIN {$g5['value_table']} cv ON ch.ch_id = cv.ch_id
            WHERE (ch.ch_state = '대기' OR ch.ch_state = '수정중')
              AND cv.av_value LIKE '%{$stx}%'
              AND cv.ar_code = '{$check_sfl[1]}'
        ";

        if ($s_class !== '') {
            $sql_search .= " AND ch.ch_class = '{$s_class}'";
        }

        if ($s_side !== '') {
            $sql_search .= " AND ch.ch_side = '{$s_side}'";
        }

        $sql_order = " ORDER BY ch.ch_id DESC";
    }
}

// 일반 검색 (추가 필드 검색이 아닌 경우)
if (!$search_arcode) {
    $sql_common = "
        FROM {$g5['character_table']}
        WHERE ch_state != '승인'
          AND ch_state != '삭제'
    ";

    if ($s_class !== '') {
        $sql_search .= " AND ch_class = '{$s_class}'";
    }

    if ($s_side !== '') {
        $sql_search .= " AND ch_side = '{$s_side}'";
    }

    if ($stx !== '') {
        if ($sfl === 'mb_name') {
            // 이름으로 회원 검색 후 mb_id 매핑
            $temp_search   = '';
            $connect_str   = '';
            $mb_result = sql_query("SELECT mb_id FROM {$g5['member_table']} WHERE mb_name LIKE '%{$stx}%'");
            for ($t = 0; $row = sql_fetch_array($mb_result); $t++) {
                $temp_search .= $connect_str."mb_id = '{$row['mb_id']}'";
                $connect_str = " OR ";
            }

            if ($temp_search !== '') {
                $sql_search .= " AND ({$temp_search})";
            } else {
                $sql_search .= " AND (mb_id = '')";
            }
        } else if ($sfl !== '') {
            $sql_search .= " AND {$sfl} LIKE '%{$stx}%'";
        }
    }

    $sql_order = " ORDER BY ch_id DESC";
}

/**
 * 전체 / 검색 건수
 */
$all_row = sql_fetch("SELECT COUNT(*) AS cnt {$sql_common}");
$all_count = isset($all_row['cnt']) ? (int)$all_row['cnt'] : 0;

$total_row = sql_fetch("SELECT COUNT(*) AS cnt {$sql_common} {$sql_search} {$sql_order}");
$total_count = isset($total_row['cnt']) ? (int)$total_row['cnt'] : 0;

// 페이징
$page_rows   = 10;
$total_page  = $total_count > 0 ? (int)ceil($total_count / $page_rows) : 1;
if ($page > $total_page) {
    $page = $total_page;
}
$from_record = ($page - 1) * $page_rows;

// 목록 조회
$sql = "SELECT * {$sql_common} {$sql_search} {$sql_order} LIMIT {$from_record}, {$page_rows}";
$rank_result = sql_query($sql);

// 페이징 링크
$write_pages = get_paging(
    G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'],
    $page,
    $total_page,
    './ready.php?s_class='.$s_class.'&s_side='.$s_side.'&sfl='.$sfl.'&stx='.$stx.'&page='
);

/**
 * 사이드 필터 링크 구성
 */
$side_link  = "<li class='menu-first'><a href='".G5_URL."/member/ready.php'><span>ALL({$all_count})</span></a></li>";

$character_list = array();
for ($i = 0; $row = sql_fetch_array($rank_result); $i++) {
    $character_list[] = $row;
}

// 세력별 / 종족별 카운트 및 링크
for ($i = 0; $i < count($ch_si); $i++) {

    $current_side_id   = $ch_si[$i]['id'];
    $current_side_name = $ch_si[$i]['name'];

    // 상위 세력 선택 표시
    $side_style = ($s_side === $current_side_id) ? " style='color: rgb(255, 213, 0);'" : '';

    // 세력별 카운트
    $count_row = sql_fetch("
        SELECT COUNT(*) AS cnt
        {$sql_common}
        AND ch_side = '{$current_side_id}'
    ");
    $side_cnt = isset($count_row['cnt']) ? (int)$count_row['cnt'] : 0;

    // 세력만 필터링하는 링크 (종족 없음)
    $side_link .= "
        <li>
            <a href='".G5_URL."/member/ready.php?s_class=&amp;s_side={$current_side_id}&amp;sfl={$sfl}&amp;stx={$stx}'{$side_style}>
                <span style='font-size:11px;letter-spacing:-1px;'>{$current_side_name}({$side_cnt})</span>
            </a>
    ";

    // 종족 목록이 있을 경우 하위 메뉴
    if (count($ch_cl) > 0) {
        $side_link .= "<ul>";

        for ($k = 0; $k < count($ch_cl); $k++) {

            $class_id   = $ch_cl[$k]['id'];
            $class_name = $ch_cl[$k]['name'];

            // 세력+종족 동시 선택 강조
            if ($s_class === $class_id && $s_side === $current_side_id) {
                $c_class = " style='color: rgb(255, 213, 0);'";
            } else {
                $c_class = "";
            }

            // 세력+종족 조합 카운트
            $c_count_row = sql_fetch("
                SELECT COUNT(*) AS cnt
                {$sql_common}
                AND ch_side = '{$current_side_id}'
                AND ch_class = '{$class_id}'
            ");
            $c_cnt = isset($c_count_row['cnt']) ? (int)$c_count_row['cnt'] : 0;

            $side_link .= "
                <li>
                    <a href='".G5_URL."/member/ready.php?s_class={$class_id}&amp;s_side={$current_side_id}&amp;sfl={$sfl}&amp;stx={$stx}'{$c_class}>
                        <span style='font-size:11px;letter-spacing:-1px;'>{$class_name}({$c_cnt})</span>
                    </a>
                </li>
            ";
        }

        $side_link .= "</ul>";
    }

    $side_link .= "</li>";
}

/**
 * 스킨 로드
 */
include function_exists('theme_dir_file') ? theme_dir_file('member/skin/ready_list.skin.php') : (G5_PATH."/member/skin/ready_list.skin.php");

unset($rank_result);
unset($character_list);

include_once('./_tail.php');
?>
