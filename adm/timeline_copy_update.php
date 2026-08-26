<?php
$sub_menu = '301100';
include_once('./_common.php');

// 관리자만 접근 가능
if (!$is_admin) {
    alert('관리자만 접근 가능합니다.');
}

include_once(__DIR__.'/timeline_require.inc.php');

check_admin_token();

$tl_table = ses($_POST, 'tl_table', '', 'string');
$target_table = ses($_POST, 'target_table', '', 'string');
$target_subject = ses($_POST, 'target_subject', '', 'string');
$copy_case = ses($_POST, 'copy_case', 'schema_only', 'string');

if (!preg_match('/^[A-Za-z0-9_]{1,20}$/', $target_table)) {
    alert('타임라인 TABLE명은 공백없이 영문자, 숫자, _ 만 사용 가능합니다. (20자 이내)');
}

// 원본 타임라인 확인
$timeline = sql_fetch(" SELECT * FROM {$g5['timeline_table']} WHERE tl_table = '{$tl_table}' ");
if (!$timeline['tl_table']) {
    alert('원본 타임라인이 존재하지 않습니다.');
}

// 중복 체크
$row = sql_fetch(" SELECT COUNT(*) AS cnt FROM {$g5['timeline_table']} WHERE tl_table = '{$target_table}' ");
if ($row['cnt']) {
    alert($target_table.'은(는) 이미 존재하는 타임라인 테이블명입니다.\\n복사할 테이블명으로 사용할 수 없습니다.');
}

// 글 테이블 생성 (설치 스키마 기준, 모듈 컬럼 제외)
create_timeline_write_table($target_table);

$copy_file = 0;

// 구조만 복사시
if ($copy_case == 'schema_only') {
    $tl_count_write = 0;
    $tl_count_comment = 0;
} else {
    $tl_count_write = $timeline['tl_count_write'];
    $tl_count_comment = $timeline['tl_count_comment'];
}

// 타임라인 설정 INSERT
$sql = " INSERT INTO {$g5['timeline_table']}
         SET tl_table = '{$target_table}',
             gr_id = '{$timeline['gr_id']}',
             tl_subject = '{$target_subject}',
             tl_skin = '{$timeline['tl_skin']}',
             tl_read_level = '{$timeline['tl_read_level']}',
             tl_write_level = '{$timeline['tl_write_level']}',
             tl_use_image = '{$timeline['tl_use_image']}',
             tl_image_count = '{$timeline['tl_image_count']}',
             tl_image_width = '{$timeline['tl_image_width']}',
             tl_content_max = '{$timeline['tl_content_max']}',
             tl_use_dm = '".(int)ses($timeline, 'tl_use_dm', 1, 'int')."',
             tl_ti_use = '".(int)ses($timeline, 'tl_ti_use', 0, 'int')."',
             tl_poll_interval = '".(int)ses($timeline, 'tl_poll_interval', 10000, 'int')."',
             tl_dm_poll_interval = '".(int)ses($timeline, 'tl_dm_poll_interval', 3000, 'int')."',
             tl_write_point = '{$timeline['tl_write_point']}',
             tl_count_write = '{$tl_count_write}',
             tl_count_comment = '{$tl_count_comment}',
             tl_1_subj = '".addslashes($timeline['tl_1_subj'])."',
             tl_2_subj = '".addslashes($timeline['tl_2_subj'])."',
             tl_3_subj = '".addslashes($timeline['tl_3_subj'])."',
             tl_4_subj = '".addslashes($timeline['tl_4_subj'])."',
             tl_5_subj = '".addslashes($timeline['tl_5_subj'])."',
             tl_6_subj = '".addslashes($timeline['tl_6_subj'])."',
             tl_7_subj = '".addslashes($timeline['tl_7_subj'])."',
             tl_8_subj = '".addslashes($timeline['tl_8_subj'])."',
             tl_9_subj = '".addslashes($timeline['tl_9_subj'])."',
             tl_10_subj = '".addslashes($timeline['tl_10_subj'])."',
             tl_1 = '".addslashes($timeline['tl_1'])."',
             tl_2 = '".addslashes($timeline['tl_2'])."',
             tl_3 = '".addslashes($timeline['tl_3'])."',
             tl_4 = '".addslashes($timeline['tl_4'])."',
             tl_5 = '".addslashes($timeline['tl_5'])."',
             tl_6 = '".addslashes($timeline['tl_6'])."',
             tl_7 = '".addslashes($timeline['tl_7'])."',
             tl_8 = '".addslashes($timeline['tl_8'])."',
             tl_9 = '".addslashes($timeline['tl_9'])."',
             tl_10 = '".addslashes($timeline['tl_10'])."' ";
sql_query($sql, false);

// 이미지 폴더 생성
if (defined('G5_TIMELINE_PATH')) {
    $source_path = G5_TIMELINE_PATH.'/'.$tl_table;
    $target_path = G5_TIMELINE_PATH.'/'.$target_table;
    
    @mkdir($target_path, G5_DIR_PERMISSION);
    @chmod($target_path, G5_DIR_PERMISSION);
    
    // index.php 생성
    $file = $target_path.'/index.php';
    $f = @fopen($file, 'w');
    if ($f) {
        @fwrite($f, '<?php');
        @fclose($f);
        @chmod($file, G5_FILE_PERMISSION);
    }
    
    // 구조와 데이터 복사시 이미지도 복사
    if ($copy_case == 'schema_data_both' && is_dir($source_path)) {
        $d = dir($source_path);
        while ($entry = $d->read()) {
            if ($entry == '.' || $entry == '..' || $entry == 'index.php') continue;
            
            if (is_file($source_path.'/'.$entry)) {
                @copy($source_path.'/'.$entry, $target_path.'/'.$entry);
                @chmod($target_path.'/'.$entry, G5_FILE_PERMISSION);
                $copy_file++;
            }
        }
        $d->close();
    }
}

// 구조와 데이터 복사
if ($copy_case == 'schema_data_both') {
    $source_write_table = get_timeline_write_table($tl_table);
    $target_write_table = get_timeline_write_table($target_table);
    
    // 글 복사 (모듈 컬럼 wr_event_log, wr_mon_*, ma_id 는 제외)
    $copy_cols = array_keys(get_timeline_write_schema());
    $col_list = '`'.implode('`,`', $copy_cols).'`';
    $sql = " INSERT INTO {$target_write_table} ({$col_list}) SELECT {$col_list} FROM {$source_write_table} ";
    sql_query($sql, false);
    
    // 좋아요 복사
    $sql = " INSERT INTO {$g5['timeline_good_table']} (tl_table, wr_id, mb_id, ch_id, tl_good_datetime)
             SELECT '{$target_table}', wr_id, mb_id, ch_id, tl_good_datetime 
             FROM {$g5['timeline_good_table']} WHERE tl_table = '{$tl_table}' ";
    sql_query($sql, false);
    
    // 북마크 복사
    $sql = " INSERT INTO {$g5['timeline_bookmark_table']} (tl_table, wr_id, mb_id, ch_id, tl_bm_datetime)
             SELECT '{$target_table}', wr_id, mb_id, ch_id, tl_bm_datetime 
             FROM {$g5['timeline_bookmark_table']} WHERE tl_table = '{$tl_table}' ";
    sql_query($sql, false);
    
    // 멘션 복사
    $sql = " INSERT INTO {$g5['timeline_mention_table']} (tl_table, wr_id, mb_id, ch_id, tl_mt_read, tl_mt_datetime)
             SELECT '{$target_table}', wr_id, mb_id, ch_id, tl_mt_read, tl_mt_datetime 
             FROM {$g5['timeline_mention_table']} WHERE tl_table = '{$tl_table}' ";
    sql_query($sql, false);
}

echo "<script>opener.document.location.reload();</script>";

alert("복사에 성공했습니다.", './timeline_copy.php?tl_table='.$tl_table.'&amp;'.$qstr);
?>
