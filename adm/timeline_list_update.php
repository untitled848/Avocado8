<?php
$sub_menu = "301100";
include_once('./_common.php');

// 관리자만 접근 가능
if (!$is_admin) {
    alert('관리자만 접근 가능합니다.');
}

include_once(__DIR__.'/timeline_require.inc.php');

$chk = ses($_POST, 'chk', array(), 'array');

if (!count($chk)) {
    alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

if ($_POST['act_button'] == "선택수정") {

    for ($i = 0; $i < count($chk); $i++) {
        $k = ses($chk, $i, 0, 'int');
        
        $tl_table_k = ses($_POST['tl_table'], $k, '', 'string');
        $tl_subject_k = ses($_POST['tl_subject'], $k, '', 'string');
        $tl_skin_k = ses($_POST['tl_skin'], $k, '', 'string');
        $tl_read_level_k = ses($_POST['tl_read_level'], $k, 1, 'int');
        $tl_write_level_k = ses($_POST['tl_write_level'], $k, 1, 'int');
        $tl_image_count_k = ses($_POST['tl_image_count'], $k, 4, 'int');
        $tl_content_max_k = ses($_POST['tl_content_max'], $k, 500, 'int');
        $tl_write_point_k = ses($_POST['tl_write_point'], $k, 0, 'int');

        $sql = " UPDATE {$g5['timeline_table']}
                    SET tl_subject = '{$tl_subject_k}',
                        tl_skin = '{$tl_skin_k}',
                        tl_read_level = '{$tl_read_level_k}',
                        tl_write_level = '{$tl_write_level_k}',
                        tl_image_count = '{$tl_image_count_k}',
                        tl_content_max = '{$tl_content_max_k}',
                        tl_write_point = '{$tl_write_point_k}'
                  WHERE tl_table = '{$tl_table_k}' ";
        sql_query($sql);
    }

} else if ($_POST['act_button'] == "선택초기화") {

    check_admin_token();

    for ($i = 0; $i < count($chk); $i++) {
        $k = ses($chk, $i, 0, 'int');
        $tmp_tl_table = ses($_POST['tl_table'], $k, '', 'string');
        
        if ($tmp_tl_table) {
            $write_table = get_timeline_write_table($tmp_tl_table);
            
            // 글 테이블 초기화 (TRUNCATE로 빠르게 삭제)
            sql_query("TRUNCATE TABLE {$write_table}");
            
            // 좋아요/북마크/멘션 데이터 삭제
            sql_query("DELETE FROM {$g5['timeline_good_table']} WHERE tl_table = '{$tmp_tl_table}'");
            sql_query("DELETE FROM {$g5['timeline_bookmark_table']} WHERE tl_table = '{$tmp_tl_table}'");
            sql_query("DELETE FROM {$g5['timeline_mention_table']} WHERE tl_table = '{$tmp_tl_table}'");
            
            // 타임라인 글/댓글 수 초기화
            sql_query("UPDATE {$g5['timeline_table']} SET tl_count_write = 0, tl_count_comment = 0 WHERE tl_table = '{$tmp_tl_table}'");
            
            // 첨부 이미지 폴더 비우기 (폴더는 유지)
            if (defined('G5_TIMELINE_PATH')) {
                $img_path = G5_TIMELINE_PATH.'/'.$tmp_tl_table;
                if (is_dir($img_path)) {
                    $files = glob($img_path.'/*');
                    foreach ($files as $file) {
                        if (is_file($file) && basename($file) != 'index.php') {
                            @unlink($file);
                        }
                    }
                }
            }
        }
    }

} else if ($_POST['act_button'] == "선택삭제") {

    check_admin_token();

    for ($i = 0; $i < count($chk); $i++) {
        $k = ses($chk, $i, 0, 'int');
        $tmp_tl_table = ses($_POST['tl_table'], $k, '', 'string');
        
        if ($tmp_tl_table) {
            include('./timeline_delete.inc.php');
        }
    }
}

goto_url('./timeline_list.php?'.$qstr);
?>
