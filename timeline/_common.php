<?php
/**
 * 타임라인 공통 설정 파일
 * 게시판(bbs)과 호환되는 변수 구조 사용
 * 
 * 주요 변수:
 * - $tl_table: 타임라인 테이블명 (bo_table 대응)
 * - $timeline: 타임라인 설정 ($board 대응)
 * - $write_table: 글 테이블명 (avo_timeline_{tl_table})
 * - $write: 현재 글 정보
 * - $wr_id: 현재 글 ID
 */

require_once(__DIR__.'/../common.php');
require_once(__DIR__.'/_functions.php');

// bo_table 변수 초기화 (common.php에서 사용될 수 있음)
$bo_table = '';

// =====================================================
// 타임라인 테이블명 가져오기
// =====================================================
$tl_table = ses($_REQUEST, 'tl_table', '', 'raw');
if (!$tl_table) {
    $tl_table = ses($_REQUEST, 'bo_table', '', 'raw'); // bo_table 호환
}
if (!$tl_table) {
    $tl_table = 'default'; // 기본 타임라인
}
$tl_table = preg_replace('/[^a-z0-9_]/i', '', $tl_table);

// =====================================================
// 타임라인 설정 로드 (게시판 $board 대응)
// =====================================================
$timeline = sql_fetch("SELECT * FROM {$g5['timeline_table']} WHERE tl_table = '{$tl_table}'");

// sql_fetch가 null/false를 반환할 수 있음
if (!is_array($timeline)) {
    $timeline = array();
}

if (!isset($timeline['tl_table']) || !$timeline['tl_table']) {
    // 타임라인이 없으면 default 시도
    if ($tl_table !== 'default') {
        $tl_table = 'default';
        $timeline = sql_fetch("SELECT * FROM {$g5['timeline_table']} WHERE tl_table = 'default'");
        if (!is_array($timeline)) {
            $timeline = array();
        }
    }
    
    if (!isset($timeline['tl_table']) || !$timeline['tl_table']) {
        // default도 없으면 에러
        if (!defined('_TIMELINE_ALLOW_NO_TABLE_')) {
            alert('타임라인이 존재하지 않습니다.', G5_URL);
        }
    }
}

// =====================================================
// 게시판 호환용 변수 설정
// =====================================================

// $board 변수로도 접근 가능하게 (MMB 스킨 호환)
$board = array();
if (isset($timeline['tl_table']) && $timeline['tl_table']) {
    $board['bo_table'] = $timeline['tl_table'];
    $board['bo_subject'] = $timeline['tl_subject'];
    $board['bo_skin'] = $timeline['tl_skin'];

    $board['bo_read_level'] = $timeline['tl_read_level'];
    $board['bo_write_level'] = $timeline['tl_write_level'];
    $board['bo_write_point'] = $timeline['tl_write_point'];
    $board['bo_count_write'] = $timeline['tl_count_write'];
    $board['bo_count_comment'] = $timeline['tl_count_comment'];
    $board['bo_gallery_cols'] = 1;
    $board['bo_gallery_width'] = $timeline['tl_image_width'];
    $board['bo_gallery_height'] = 0;
    $board['bo_image_width'] = $timeline['tl_image_width'];
    $board['bo_1_subj'] = $timeline['tl_1_subj'];
    $board['bo_2_subj'] = $timeline['tl_2_subj'];
    $board['bo_3_subj'] = $timeline['tl_3_subj'];
    $board['bo_4_subj'] = $timeline['tl_4_subj'];
    $board['bo_5_subj'] = $timeline['tl_5_subj'];
    $board['bo_6_subj'] = $timeline['tl_6_subj'];
    $board['bo_7_subj'] = $timeline['tl_7_subj'];
    $board['bo_8_subj'] = $timeline['tl_8_subj'];
    $board['bo_9_subj'] = $timeline['tl_9_subj'];
    $board['bo_10_subj'] = $timeline['tl_10_subj'];
    $board['bo_1'] = $timeline['tl_1'];
    $board['bo_2'] = $timeline['tl_2'];
    $board['bo_3'] = $timeline['tl_3'];
    $board['bo_4'] = $timeline['tl_4'];
    $board['bo_5'] = $timeline['tl_5'];
    $board['bo_6'] = $timeline['tl_6'];
    $board['bo_7'] = $timeline['tl_7'];
    $board['bo_8'] = $timeline['tl_8'];
    $board['bo_9'] = $timeline['tl_9'];
    $board['bo_10'] = $timeline['tl_10'];
    
    // 타임라인 전용 설정 (액션/주사위/아이템은 모듈 on/off 로 관리)
    $board['tl_use_image'] = $timeline['tl_use_image'];
    $board['tl_image_count'] = $timeline['tl_image_count'] ? $timeline['tl_image_count'] : 4;
    $board['tl_content_max'] = $timeline['tl_content_max'];
    $board['tl_use_dm'] = ses($timeline, 'tl_use_dm', 1, 'int');
    $board['tl_ti_use'] = ses($timeline, 'tl_ti_use', 0, 'int');
    $board['tl_poll_interval'] = ses($timeline, 'tl_poll_interval', 10000, 'int');
    $board['tl_dm_poll_interval'] = ses($timeline, 'tl_dm_poll_interval', 3000, 'int');
}

// bo_table 변수도 설정 (호환용)
$bo_table = $tl_table;

// =====================================================
// 글 테이블 설정 (게시판 $write_table 대응)
// =====================================================
// 기존 $g5['write_prefix'] 대신 타임라인 전용 접두사 사용
$g5['board_table'] = $g5['timeline_table'];
$write_prefix = $g5['timeline_write_prefix'];
$write_table = $write_prefix.$tl_table;

// =====================================================
// 현재 글 정보 로드
// =====================================================
$wr_id = ses($_REQUEST, 'wr_id', 0, 'int');
$write = array();

if ($wr_id && isset($timeline['tl_table']) && $timeline['tl_table']) {
    // 테이블 존재 확인
    $table_exists = sql_fetch("SHOW TABLES LIKE '{$write_table}'");
    if ($table_exists) {
        $write = sql_fetch("SELECT * FROM {$write_table} WHERE wr_id = '{$wr_id}'");
    }
}

// =====================================================
// 스킨 경로 설정
// =====================================================
$timeline_skin = (isset($timeline['tl_skin']) && $timeline['tl_skin']) ? $timeline['tl_skin'] : 'basic';

// get_skin_path: 코어 경로 + 활성 프리셋의 skin_timeline_{스킨} 슬롯 오버라이드
$timeline_skin_path = get_skin_path('timeline', $timeline_skin);
$timeline_skin_url  = get_skin_url('timeline', $timeline_skin);

// 스킨이 없으면 basic 사용 (basic 도 테마 슬롯 오버라이드 적용)
if (!is_dir($timeline_skin_path)) {
    $timeline_skin = 'basic';
    $timeline_skin_path = get_skin_path('timeline', 'basic');
    $timeline_skin_url  = get_skin_url('timeline', 'basic');
}

// 게시판 스킨 변수 호환
$board_skin_path = $timeline_skin_path;
$board_skin_url = $timeline_skin_url;

/**
 * 타임라인 CSS 출력 (스킨 css_head.inc.php 우선, 없으면 기본 링크)
 * 적용 우선순위: JSON > skin/css > timeline/css
 */
if (!function_exists('timeline_print_css_head')) {
    function timeline_print_css_head() {
        global $timeline_skin_path, $timeline_skin_url, $bo_table;
        if (!empty($timeline_skin_path) && is_file($timeline_skin_path.'/css_head.inc.php')) {
            include $timeline_skin_path.'/css_head.inc.php';
        } else {
            echo '<link rel="stylesheet" href="'.G5_URL.'/timeline/timeline.css">'."\n";
            if (!empty($timeline_skin_path) && is_file($timeline_skin_path.'/css/style.css')) {
                echo '<link rel="stylesheet" href="'.$timeline_skin_url.'/css/style.css">'."\n";
            }
        }
        if (function_exists('mdl_do_action')) {
            mdl_do_action('bbs_enqueue_assets', $bo_table);
        }
    }
}

// =====================================================
// 이미지 경로 설정
// =====================================================
if (!defined('G5_TIMELINE_PATH')) {
    define('G5_TIMELINE_PATH', G5_DATA_PATH.'/timeline');
}
if (!defined('G5_TIMELINE_URL')) {
    define('G5_TIMELINE_URL', G5_DATA_URL.'/timeline');
}

// 타임라인별 이미지 경로
$timeline_file_path = G5_TIMELINE_PATH.'/'.$tl_table;
$timeline_file_url = G5_TIMELINE_URL.'/'.$tl_table;

define('_TIMELINE_ALLOW_NO_CHARACTER_', true);

// =====================================================
// 로그인/캐릭터 체크
// =====================================================
if (!$is_member) {
    if (!defined('_TIMELINE_ALLOW_GUEST_')) {
        // 열람 권한이 '전체'가 아니면 로그인 필요
        $tl_read_level = ses($timeline, 'tl_read_level', 1, 'int');
        if ($tl_read_level > 1) {
            alert('로그인이 필요합니다.', G5_BBS_URL.'/login.php?url='.urlencode($_SERVER['REQUEST_URI']));
        }
    }
}

if ($is_member && !ses($character, 'ch_id', 0, 'int')) {
    if (!defined('_TIMELINE_ALLOW_NO_CHARACTER_')) {
        alert('캐릭터 정보가 필요합니다.', G5_URL);
    }
}

// =====================================================
// 타임라인 활성 계정 (세션, 대표 캐릭터 member.ch_id는 유지)
// =====================================================
$my_timeline_users = array();
if ($is_member && $tl_table && function_exists('apply_timeline_active_character')) {
    $my_timeline_users = apply_timeline_active_character($tl_table);
}

// =====================================================
// 열람 권한 체크
// =====================================================
if (!defined('_TIMELINE_SKIP_VIEW_CHECK_')) {
    $my_ch_id_for_check = ses($character, 'ch_id', 0, 'int');
    if (!can_view_timeline($timeline, $my_ch_id_for_check, $is_admin, $is_member)) {
        alert('이 타임라인을 열람할 권한이 없습니다.', G5_URL);
    }
}

// =====================================================
// 쓰기 권한 체크 변수
// =====================================================
$can_write = can_write_timeline($timeline, ses($character, 'ch_id', 0, 'int'), $is_admin);

// =====================================================
// 프로필 이미지 설정
// =====================================================
$my_profile = '';
$my_ch_id = ses($character, 'ch_id', 0, 'int');
if ($my_ch_id) {
    $my_profile = get_timeline_thumb($my_ch_id, 1);
}

// =====================================================
// 캐릭터 승인 상태
// =====================================================
$ch_state = ses($character, 'ch_state', '', 'raw');
$is_approved = ($ch_state == '승인');

// 타임라인 전용 쿼리스트링
if (!isset($qstr)) {
    $qstr = '';
}
$qstr .= '&amp;tl_table='.$tl_table;
$tl_qstr = 'tl_table='.$tl_table;
