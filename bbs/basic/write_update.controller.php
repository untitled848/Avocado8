<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * basic(일반 게시판) write_update — 컨트롤러
 *
 * gnuboard 코어가 이미 wr_ row 를 INSERT/UPDATE 한 뒤 호출됨.
 * 모듈 훅(_module_write_update.inc.php) 을 통해 customer_sql 을 누적,
 * 결과를 wr_ 행에 추가 UPDATE 한다.
 */

$customer_sql    = '';
$memo_custom_sql = '';
$temp_wr_id      = $wr_id;
if (empty($wr_num) && isset($write['wr_num'])) {
    $wr_num = $write['wr_num'];
}

require G5_BBS_PATH . '/_module_write_update.inc.php';

if ($customer_sql !== '' && $wr_id) {
    $sql = " update {$write_table}
                set wr_id = '{$wr_id}'
                {$customer_sql}
              where wr_id = '{$wr_id}' ";
    sql_query($sql);
}

if (function_exists('mdl_do_action')) {
    mdl_do_action('bbs_post_after', $bo_table, $wr_id, $w);
}

if (!empty($file_upload_msg)) {
    alert($file_upload_msg, G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table.'&amp;wr_id='.$wr_id.$qstr);
} else {
    goto_url(G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table.'&amp;wr_id='.$wr_id.$qstr);
}
