<?php
$sub_menu = "301200";
include_once('./_common.php');

// 관리자만 접근 가능
if (!$is_admin) {
    alert('관리자만 접근 가능합니다.');
}

include_once(__DIR__.'/timeline_require.inc.php');

check_admin_token();

// POST 값 받기
$tl_table = ses($_POST, 'tl_table', '', 'string');
$tl_subject = ses($_POST, 'tl_subject', '', 'string');
$gr_id = ses($_POST, 'gr_id', '', 'string');
$tl_skin = ses($_POST, 'tl_skin', 'basic', 'string');
$tl_read_level = ses($_POST, 'tl_read_level', 1, 'int');
$tl_write_level = ses($_POST, 'tl_write_level', 1, 'int');
// tl_read_level: 1=전체, 2=회원, 3=사용자, 10=운영자
if (!in_array($tl_read_level, array(1, 2, 3, 10))) $tl_read_level = 1;
// tl_write_level: 1=모든 사용자, 2=운영자만
if (!in_array($tl_write_level, array(1, 2))) $tl_write_level = 1;
$tl_use_image = ses($_POST, 'tl_use_image', 1, 'int');
$tl_image_count = ses($_POST, 'tl_image_count', 4, 'int');
$tl_image_width = ses($_POST, 'tl_image_width', 600, 'int');
$tl_content_max = ses($_POST, 'tl_content_max', 500, 'int');
$tl_use_dm = ses($_POST, 'tl_use_dm', 1, 'int') ? 1 : 0;
$tl_ti_use = ses($_POST, 'tl_ti_use', 0, 'int') ? 1 : 0;
$tl_poll_interval = get_timeline_poll_interval_ms(array('tl_poll_interval' => ses($_POST, 'tl_poll_interval', 10000, 'int')), 'tl_poll_interval', 10000);
$tl_dm_poll_interval = get_timeline_poll_interval_ms(array('tl_dm_poll_interval' => ses($_POST, 'tl_dm_poll_interval', 3000, 'int')), 'tl_dm_poll_interval', 3000);
$tl_write_point = ses($_POST, 'tl_write_point', 0, 'int');
// 여분필드
for ($i = 1; $i <= 10; $i++) {
    ${'tl_'.$i.'_subj'} = ses($_POST, 'tl_'.$i.'_subj', '', 'string');
    ${'tl_'.$i} = ses($_POST, 'tl_'.$i, '', 'string');
}

// 검증
if ($tl_table === '') {
    alert('타임라인 TABLE명을 입력하세요.');
}

if (!preg_match('/^[A-Za-z0-9_]{1,20}$/', $tl_table)) {
    alert('타임라인 TABLE명은 공백없이 영문자, 숫자, _ 만 사용 가능합니다. (20자 이내)');
}

if ($tl_subject === '') {
    alert('타임라인 제목을 입력하세요.');
}

// 이미지 수 제한
$tl_image_count = min(max($tl_image_count, 0), 4);

// 공통 SQL
$sql_common = "
    gr_id = '{$gr_id}',
    tl_subject = '{$tl_subject}',
    tl_skin = '{$tl_skin}',
    tl_read_level = '{$tl_read_level}',
    tl_write_level = '{$tl_write_level}',
    tl_use_image = '{$tl_use_image}',
    tl_image_count = '{$tl_image_count}',
    tl_image_width = '{$tl_image_width}',
    tl_content_max = '{$tl_content_max}',
    tl_use_dm = '{$tl_use_dm}',
    tl_ti_use = '{$tl_ti_use}',
    tl_poll_interval = '{$tl_poll_interval}',
    tl_dm_poll_interval = '{$tl_dm_poll_interval}',
    tl_write_point = '{$tl_write_point}',
    tl_1_subj = '{$tl_1_subj}',
    tl_2_subj = '{$tl_2_subj}',
    tl_3_subj = '{$tl_3_subj}',
    tl_4_subj = '{$tl_4_subj}',
    tl_5_subj = '{$tl_5_subj}',
    tl_6_subj = '{$tl_6_subj}',
    tl_7_subj = '{$tl_7_subj}',
    tl_8_subj = '{$tl_8_subj}',
    tl_9_subj = '{$tl_9_subj}',
    tl_10_subj = '{$tl_10_subj}',
    tl_1 = '{$tl_1}',
    tl_2 = '{$tl_2}',
    tl_3 = '{$tl_3}',
    tl_4 = '{$tl_4}',
    tl_5 = '{$tl_5}',
    tl_6 = '{$tl_6}',
    tl_7 = '{$tl_7}',
    tl_8 = '{$tl_8}',
    tl_9 = '{$tl_9}',
    tl_10 = '{$tl_10}'
";

// 신규 생성
if ($w === '') {
    // 중복 체크
    $row = sql_fetch(" SELECT COUNT(*) AS cnt FROM {$g5['timeline_table']} WHERE tl_table = '{$tl_table}' ");
    if ($row['cnt']) {
        alert($tl_table.'은(는) 이미 존재하는 타임라인 테이블명입니다.');
    }
    
    // 타임라인 설정 INSERT
    $sql = " INSERT INTO {$g5['timeline_table']}
             SET tl_table = '{$tl_table}',
                 {$sql_common},
                 tl_count_write = 0,
                 tl_count_comment = 0 ";
    sql_query($sql);
    
    // 글 테이블 생성
    create_timeline_write_table($tl_table);
    
    // 이미지 폴더 생성
    if (defined('G5_TIMELINE_PATH')) {
        $timeline_path = G5_TIMELINE_PATH.'/'.$tl_table;
        @mkdir($timeline_path, G5_DIR_PERMISSION);
        @chmod($timeline_path, G5_DIR_PERMISSION);
        
        // index.php 생성 (디렉토리 리스팅 방지)
        $file = $timeline_path.'/index.php';
        $f = @fopen($file, 'w');
        if ($f) {
            @fwrite($f, '<?php');
            @fclose($f);
            @chmod($file, G5_FILE_PERMISSION);
        }
    }
    
    $msg = '타임라인이 생성되었습니다.';
}
// 수정
elseif ($w === 'u') {
    // 존재 여부 체크
    $row = sql_fetch(" SELECT COUNT(*) AS cnt FROM {$g5['timeline_table']} WHERE tl_table = '{$tl_table}' ");
    if (!$row['cnt']) {
        alert('존재하지 않는 타임라인입니다.');
    }
    
    $sql = " UPDATE {$g5['timeline_table']}
             SET {$sql_common}
             WHERE tl_table = '{$tl_table}' ";
    sql_query($sql);
    
    $msg = '타임라인이 수정되었습니다.';
}

/* 타임라인별 모듈 on/off 저장 (tl_table = board_state 키, board_scope 모듈과 공유) */
if (function_exists('mdl_set_board_modules') && function_exists('mdl_board_scope_modules')) {
    $_present = isset($_POST['mdl_modules_present']) && is_array($_POST['mdl_modules_present'])
              ? $_POST['mdl_modules_present'] : array();
    $_checked = isset($_POST['mdl_modules']) && is_array($_POST['mdl_modules'])
              ? $_POST['mdl_modules'] : array();
    if (!empty($_present)) {
        $_map = array();
        foreach ($_present as $_name) {
            $_map[$_name] = !empty($_checked[$_name]);
        }
        mdl_set_board_modules($tl_table, $_map);
    }
}

// ─── 스킨 설정 훅 update ────────────────────────────────────────────────────
$_hu_skin_path = function_exists('get_skin_path') ? get_skin_path('timeline', $tl_skin ?: 'basic') : G5_SKIN_PATH.'/timeline/'.($tl_skin ?: 'basic');
$_hu_hook_file = $_hu_skin_path . '/setting_update.hook.php';
if (file_exists($_hu_hook_file)) {
    include $_hu_hook_file;
}
unset($_hu_skin_path, $_hu_hook_file);

goto_url('./timeline_form.php?w=u&tl_table='.$tl_table.'&'.$qstr, $msg);
?>
