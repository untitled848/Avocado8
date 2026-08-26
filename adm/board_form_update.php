<?php
$sub_menu = "300100";
include_once('./_common.php');

/* 기본 방어 */
if (!isset($auth) || !is_array($auth)) $auth = array();
if (!isset($g5) || !is_array($g5)) $g5 = array();
if (!isset($g5['board_table'])) $g5['board_table'] = G5_TABLE_PREFIX.'board';
if (!isset($g5['write_prefix'])) $g5['write_prefix'] = G5_TABLE_PREFIX.'write_';
if (!isset($w)) $w = $_POST['w'] ?? (isset($_GET['w']) ? $_GET['w'] : '');
if (!isset($qstr)) $qstr = "";

/* POST 값 기본 세팅 */
$bo_table = trim($_POST['bo_table'] ?? '');
$gr_id = $_POST['gr_id'] ?? '';

$bo_category_list = $_POST['bo_category_list'] ?? '';
$bo_use_category = $_POST['bo_use_category'] ?? '';

$bo_device = $_POST['bo_device'] ?? '';
$bo_admin = $_POST['bo_admin'] ?? '';
$bo_list_level = $_POST['bo_list_level'] ?? '';
$bo_read_level = $_POST['bo_read_level'] ?? '';
$bo_write_level = $_POST['bo_write_level'] ?? '';
$bo_reply_level = $_POST['bo_reply_level'] ?? '';
$bo_comment_level = $_POST['bo_comment_level'] ?? '';
$bo_html_level = $_POST['bo_html_level'] ?? '';
$bo_link_level = $_POST['bo_link_level'] ?? '';
$bo_count_modify = $_POST['bo_count_modify'] ?? 0;
$bo_count_delete = $_POST['bo_count_delete'] ?? 0;
$bo_upload_level = $_POST['bo_upload_level'] ?? '';
$bo_download_level = $_POST['bo_download_level'] ?? '';
$bo_read_point = $_POST['bo_read_point'] ?? 0;
$bo_write_point = $_POST['bo_write_point'] ?? 0;
$bo_comment_point = $_POST['bo_comment_point'] ?? 0;
$bo_download_point = $_POST['bo_download_point'] ?? 0;

$bo_use_sideview = $_POST['bo_use_sideview'] ?? '';
$bo_use_file_content = $_POST['bo_use_file_content'] ?? '';
$bo_use_secret = $_POST['bo_use_secret'] ?? 0;
$bo_use_emoticon = !empty($_POST['bo_use_emoticon']) ? 1 : 0;
$bo_use_dhtml_editor = $_POST['bo_use_dhtml_editor'] ?? '';
$bo_use_rss_view = $_POST['bo_use_rss_view'] ?? '';
$bo_use_good = $_POST['bo_use_good'] ?? '';
$bo_use_nogood = $_POST['bo_use_nogood'] ?? '';
$bo_use_name = $_POST['bo_use_name'] ?? '';
$bo_use_signature = $_POST['bo_use_signature'] ?? '';
$bo_use_ip_view = $_POST['bo_use_ip_view'] ?? '';
$bo_use_list_view = $_POST['bo_use_list_view'] ?? '';
$bo_use_list_file = $_POST['bo_use_list_file'] ?? '';
$bo_use_list_content = $_POST['bo_use_list_content'] ?? '';
$bo_use_email = $_POST['bo_use_email'] ?? '';
$bo_use_cert = $_POST['bo_use_cert'] ?? '';
$bo_use_sns = $_POST['bo_use_sns'] ?? '';
$bo_use_chick = $_POST['bo_use_chick'] ?? '';
$bo_use_noname = $_POST['bo_use_noname'] ?? '';

$bo_type = $_POST['bo_type'] ?? '';
$bo_mobile_subject = $_POST['bo_mobile_subject'] ?? '';
$bo_table_width = $_POST['bo_table_width'] ?? '';
$bo_subject_len = $_POST['bo_subject_len'] ?? 0;
$bo_mobile_subject_len = $_POST['bo_mobile_subject_len'] ?? 0;
$bo_page_rows = $_POST['bo_page_rows'] ?? 0;
$bo_mobile_page_rows = $_POST['bo_mobile_page_rows'] ?? 0;
$bo_new = $_POST['bo_new'] ?? 0;
$bo_hot = $_POST['bo_hot'] ?? 0;
$bo_image_width = $_POST['bo_image_width'] ?? 0;
$bo_skin = $_POST['bo_skin'] ?? '';
$bo_mobile_skin = $_POST['bo_mobile_skin'] ?? '';
$bo_content_head = $_POST['bo_content_head'] ?? '';
$bo_content_tail = $_POST['bo_content_tail'] ?? '';
$bo_mobile_content_head = $_POST['bo_mobile_content_head'] ?? '';
$bo_mobile_content_tail = $_POST['bo_mobile_content_tail'] ?? '';
$bo_insert_content = $_POST['bo_insert_content'] ?? '';
$bo_gallery_cols = $_POST['bo_gallery_cols'] ?? '';
$bo_gallery_width = $_POST['bo_gallery_width'] ?? '';
$bo_gallery_height = $_POST['bo_gallery_height'] ?? '';
$bo_mobile_gallery_width = $_POST['bo_mobile_gallery_width'] ?? '';
$bo_mobile_gallery_height = $_POST['bo_mobile_gallery_height'] ?? '';
$bo_upload_count = $_POST['bo_upload_count'] ?? 0;
$bo_upload_size = $_POST['bo_upload_size'] ?? 0;
$bo_reply_order = $_POST['bo_reply_order'] ?? 1;
$bo_use_search = $_POST['bo_use_search'] ?? '';
$bo_order = $_POST['bo_order'] ?? '';
$bo_sort_field = $_POST['bo_sort_field'] ?? '';

/* 여분필드 */
for ($i=1;$i<=10;$i++) {
    if (!isset($_POST['bo_'.$i.'_subj'])) $_POST['bo_'.$i.'_subj'] = '';
    if (!isset($_POST['bo_'.$i])) $_POST['bo_'.$i] = '';
}

/* 데모체크 */
if ($w === 'u') {
    if (function_exists('check_demo')) {
        check_demo();
    }
}

/* 권한 체크 */
$auth_val = isset($auth[$sub_menu]) ? $auth[$sub_menu] : '';
auth_check($auth_val, 'w');

check_admin_token();

/* 필수값 검사 */
if ($gr_id === '') alert('그룹 ID는 반드시 선택하세요.');
if ($bo_table === '') alert('게시판 TABLE명은 반드시 입력하세요.');
if (!preg_match("/^([A-Za-z0-9_]{1,20})$/", $bo_table)) alert('게시판 TABLE명은 공백없이 영문자, 숫자, _ 만 사용 가능합니다. (20자 이내)');
if (!isset($_POST['bo_subject']) || $_POST['bo_subject'] === '') alert('게시판 제목을 입력하세요.');

/* include head/tail 정리 */
$_POST['bo_include_head'] = isset($_POST['bo_include_head']) ? preg_replace("#[\\\]+$#", "", substr($_POST['bo_include_head'], 0, 255)) : '';
$_POST['bo_include_tail'] = isset($_POST['bo_include_tail']) ? preg_replace("#[\\\]+$#", "", substr($_POST['bo_include_tail'], 0, 255)) : '';

if ($_POST['bo_include_head'] !== '') {
    $purl = @parse_url($_POST['bo_include_head']);
    $file = isset($purl['path']) ? $purl['path'] : '';
    if ($file && !preg_match("/\.(php|html?)$/i", $file)) alert('상단 파일 경로가 php, html 파일이 아닙니다.');
    $_POST['bo_include_head'] = $file;
}

if ($_POST['bo_include_tail'] !== '') {
    $purl = @parse_url($_POST['bo_include_tail']);
    $file = isset($purl['path']) ? $purl['path'] : '';
    if ($file && !preg_match("/\.(php|html?)$/i", $file)) alert('하단 파일 경로가 php, html 파일이 아닙니다.');
    $_POST['bo_include_tail'] = $file;
}

if (function_exists('is_include_path_check')) {
    if (!is_include_path_check($_POST['bo_include_head'])) {
        alert('/data/file/ 또는 /data/editor/ 포함된 문자를 상단 파일 경로에 포함시킬수 없습니다.');
    }
    if (!is_include_path_check($_POST['bo_include_tail'])) {
        alert('/data/file/ 또는 /data/editor/ 포함된 문자를 하단 파일 경로에 포함시킬수 없습니다.');
    }
}

/* 게시판 디렉토리 생성 */
$board_path = G5_DATA_PATH.'/file/'.$bo_table;
@mkdir($board_path, G5_DIR_PERMISSION);
@chmod($board_path, G5_DIR_PERMISSION);

$file = $board_path.'/index.php';
$f = @fopen($file, 'w');
if ($f) {
    @fwrite($f, '');
    @fclose($f);
    @chmod($file, G5_FILE_PERMISSION);
}

/* 카테고리 특수문자 치환 */
$src_char = array('&', '=');
$dst_char = array('＆', '〓');
$bo_category_list = str_replace($src_char, $dst_char, $bo_category_list);

/* 공통 SQL */
// 댓글 제한 추가 컬럼 sanitize
$bo_comment_limit_count    = (int)($_POST['bo_comment_limit_count']    ?? 0);
$bo_comment_limit_time     = (int)($_POST['bo_comment_limit_time']     ?? 0);
$bo_comment_limit_text     = (int)($_POST['bo_comment_limit_text']     ?? 0);
$bo_comment_limit_text_exc = sql_escape_string(trim($_POST['bo_comment_limit_text_exc'] ?? 'ㅋ,ㅠ,ㅎ,ㅜ,.,!,?,~'));
$bo_write_limit_text       = (int)($_POST['bo_write_limit_text']       ?? 0);
// avo8.0: bo_write_limit_text 자동 마이그레이션 (board_form.php 미경유 직접 저장 대비)
if (function_exists('alter_table_add_column')) {
    @alter_table_add_column($g5['board_table'], 'bo_write_limit_text', "INT(11) NOT NULL DEFAULT '0'", 'bo_comment_limit_text');
}
$sql_common = "
    gr_id               = '{$gr_id}',
    bo_subject          = '{$_POST['bo_subject']}',
    bo_type             = '{$bo_type}',
    bo_mobile_subject   = '{$bo_mobile_subject}',
    bo_device           = '{$bo_device}',
    bo_admin            = '{$bo_admin}',
    bo_list_level       = '{$bo_list_level}',
    bo_read_level       = '{$bo_read_level}',
    bo_write_level      = '{$bo_write_level}',
    bo_reply_level      = '{$bo_reply_level}',
    bo_comment_level    = '{$bo_comment_level}',
    bo_html_level       = '{$bo_html_level}',
    bo_link_level       = '{$bo_link_level}',
    bo_count_modify     = '{$bo_count_modify}',
    bo_count_delete     = '{$bo_count_delete}',
    bo_upload_level     = '{$bo_upload_level}',
    bo_download_level   = '{$bo_download_level}',
    bo_read_point       = '{$bo_read_point}',
    bo_write_point      = '{$bo_write_point}',
    bo_comment_point    = '{$bo_comment_point}',
    bo_download_point   = '{$bo_download_point}',
    bo_use_category     = '{$bo_use_category}',
    bo_category_list    = '{$bo_category_list}',
    bo_use_sideview     = '{$bo_use_sideview}',
    bo_use_file_content = '{$bo_use_file_content}',
    bo_use_secret       = '{$bo_use_secret}',
    bo_use_emoticon     = '{$bo_use_emoticon}',
    bo_use_dhtml_editor = '{$bo_use_dhtml_editor}',
    bo_use_rss_view     = '{$bo_use_rss_view}',
    bo_use_good         = '{$bo_use_good}',
    bo_use_nogood       = '{$bo_use_nogood}',
    bo_use_name         = '{$bo_use_name}',
    bo_use_signature    = '{$bo_use_signature}',
    bo_use_ip_view      = '{$bo_use_ip_view}',
    bo_use_list_view    = '{$bo_use_list_view}',
    bo_use_list_file    = '{$bo_use_list_file}',
    bo_use_list_content = '{$bo_use_list_content}',
    bo_use_email        = '{$bo_use_email}',
    bo_use_cert         = '{$bo_use_cert}',
    bo_use_sns          = '{$bo_use_sns}',
    bo_table_width      = '{$bo_table_width}',
    bo_subject_len      = '{$bo_subject_len}',
    bo_mobile_subject_len = '{$bo_mobile_subject_len}',
    bo_page_rows        = '{$bo_page_rows}',
    bo_mobile_page_rows = '{$bo_mobile_page_rows}',
    bo_new              = '{$bo_new}',
    bo_hot              = '{$bo_hot}',
    bo_image_width      = '{$bo_image_width}',
    bo_skin             = '{$bo_skin}',
    bo_mobile_skin      = '{$bo_mobile_skin}',
    bo_include_head     = '{$_POST['bo_include_head']}',
    bo_include_tail     = '{$_POST['bo_include_tail']}',
    bo_content_head     = '{$bo_content_head}',
    bo_content_tail     = '{$bo_content_tail}',
    bo_mobile_content_head = '{$bo_mobile_content_head}',
    bo_mobile_content_tail = '{$bo_mobile_content_tail}',
    bo_insert_content   = '{$bo_insert_content}',
    bo_gallery_cols     = '{$bo_gallery_cols}',
    bo_gallery_width    = '{$bo_gallery_width}',
    bo_gallery_height   = '{$bo_gallery_height}',
    bo_mobile_gallery_width = '{$bo_mobile_gallery_width}',
    bo_mobile_gallery_height= '{$bo_mobile_gallery_height}',
    bo_upload_count     = '{$bo_upload_count}',
    bo_upload_size      = '{$bo_upload_size}',
    bo_reply_order      = '{$bo_reply_order}',
    bo_use_search       = '{$bo_use_search}',
    bo_order            = '{$bo_order}',
    bo_comment_limit_count    = '{$bo_comment_limit_count}',
    bo_comment_limit_time     = '{$bo_comment_limit_time}',
    bo_comment_limit_text     = '{$bo_comment_limit_text}',
    bo_write_limit_text       = '{$bo_write_limit_text}',
    bo_comment_limit_text_exc = '{$bo_comment_limit_text_exc}',
    bo_use_chick        = '{$bo_use_chick}',
    bo_use_noname       = '{$bo_use_noname}',
    bo_sort_field       = '{$bo_sort_field}',
    bo_1_subj           = '{$_POST['bo_1_subj']}',
    bo_2_subj           = '{$_POST['bo_2_subj']}',
    bo_3_subj           = '{$_POST['bo_3_subj']}',
    bo_4_subj           = '{$_POST['bo_4_subj']}',
    bo_5_subj           = '{$_POST['bo_5_subj']}',
    bo_6_subj           = '{$_POST['bo_6_subj']}',
    bo_7_subj           = '{$_POST['bo_7_subj']}',
    bo_8_subj           = '{$_POST['bo_8_subj']}',
    bo_9_subj           = '{$_POST['bo_9_subj']}',
    bo_10_subj          = '{$_POST['bo_10_subj']}',
    bo_1                = '{$_POST['bo_1']}',
    bo_2                = '{$_POST['bo_2']}',
    bo_3                = '{$_POST['bo_3']}',
    bo_4                = '{$_POST['bo_4']}',
    bo_5                = '{$_POST['bo_5']}',
    bo_6                = '{$_POST['bo_6']}',
    bo_7                = '{$_POST['bo_7']}',
    bo_8                = '{$_POST['bo_8']}',
    bo_9                = '{$_POST['bo_9']}',
    bo_10               = '{$_POST['bo_10']}'
";

/* 신규 생성 */
if ($w === '') {

    $row = sql_fetch(" SELECT COUNT(*) AS cnt FROM {$g5['board_table']} WHERE bo_table = '{$bo_table}' ");
    if (!empty($row['cnt'])) alert($bo_table.' 은(는) 이미 존재하는 TABLE 입니다.');

    $sql = " INSERT INTO {$g5['board_table']}
             SET bo_table = '{$bo_table}',
                 bo_count_write = '0',
                 bo_count_comment = '0',
                 {$sql_common} ";
    sql_query($sql);

    // 게시판 테이블 생성
    $file = @file('./sql_write.sql');
    if ($file !== false) {
        $sql = implode("\n", $file);
        $create_table = $g5['write_prefix'].$bo_table;
        $sql = preg_replace('/__TABLE_NAME__/', $create_table, $sql);
        $sql = preg_replace('/;[\r\n]+/', '', $sql);
        sql_query($sql, false);
    }

}
/* 수정 */
elseif ($w === 'u') {

    $row = sql_fetch(" SELECT COUNT(*) AS cnt FROM {$g5['write_prefix']}{$bo_table} WHERE wr_is_comment = 0 ");
    $bo_count_write = isset($row['cnt']) ? $row['cnt'] : 0;

    $row = sql_fetch(" SELECT COUNT(*) AS cnt FROM {$g5['write_prefix']}{$bo_table} WHERE wr_is_comment = 1 ");
    $bo_count_comment = isset($row['cnt']) ? $row['cnt'] : 0;

    if (isset($_POST['proc_count'])) {
        $sql = " SELECT a.wr_id, (COUNT(b.wr_parent) - 1) AS cnt
                 FROM {$g5['write_prefix']}{$bo_table} a,
                      {$g5['write_prefix']}{$bo_table} b
                 WHERE a.wr_id = b.wr_parent
                   AND a.wr_is_comment = 0
                 GROUP BY a.wr_id ";
        $result = sql_query($sql);
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            sql_query(" UPDATE {$g5['write_prefix']}{$bo_table}
                        SET wr_comment = '{$row['cnt']}'
                        WHERE wr_id = '{$row['wr_id']}' ");
        }
    }

    $bo_notice = '';
    $lf = '';
    if (isset($board['bo_notice']) && $board['bo_notice'] !== '') {
        $tmp_array = explode(",", $board['bo_notice']);
        for ($i=0; $i<count($tmp_array); $i++) {
            $tmp_wr_id = trim($tmp_array[$i]);
            if ($tmp_wr_id === '') continue;
            $row = sql_fetch(" SELECT COUNT(*) AS cnt FROM {$g5['write_prefix']}{$bo_table} WHERE wr_id = '{$tmp_wr_id}' ");
            if (!empty($row['cnt'])) {
                $bo_notice .= $lf.$tmp_wr_id;
                $lf = ",";
            }
        }
    }

    $sql = " UPDATE {$g5['board_table']}
             SET bo_notice = '{$bo_notice}',
                 bo_count_write = '{$bo_count_write}',
                 bo_count_comment = '{$bo_count_comment}',
                 {$sql_common}
             WHERE bo_table = '{$bo_table}' ";
    sql_query($sql);
}

/* 그룹 단위 적용 */
$grp_fields = '';
if (is_checked('chk_grp_device'))               $grp_fields .= " , bo_device = '{$bo_device}' ";
if (is_checked('chk_grp_admin'))                $grp_fields .= " , bo_admin = '{$bo_admin}' ";
if (is_checked('chk_grp_list_level'))           $grp_fields .= " , bo_list_level = '{$bo_list_level}' ";
if (is_checked('chk_grp_read_level'))           $grp_fields .= " , bo_read_level = '{$bo_read_level}' ";
if (is_checked('chk_grp_write_level'))          $grp_fields .= " , bo_write_level = '{$bo_write_level}' ";
if (is_checked('chk_grp_reply_level'))          $grp_fields .= " , bo_reply_level = '{$bo_reply_level}' ";
if (is_checked('chk_grp_comment_level'))        $grp_fields .= " , bo_comment_level = '{$bo_comment_level}' ";
if (is_checked('chk_grp_link_level'))           $grp_fields .= " , bo_link_level = '{$bo_link_level}' ";
if (is_checked('chk_grp_upload_level'))         $grp_fields .= " , bo_upload_level = '{$bo_upload_level}' ";
if (is_checked('chk_grp_download_level'))       $grp_fields .= " , bo_download_level = '{$bo_download_level}' ";
if (is_checked('chk_grp_html_level'))           $grp_fields .= " , bo_html_level = '{$bo_html_level}' ";
if (is_checked('chk_grp_count_modify'))         $grp_fields .= " , bo_count_modify = '{$bo_count_modify}' ";
if (is_checked('chk_grp_count_delete'))         $grp_fields .= " , bo_count_delete = '{$bo_count_delete}' ";
if (is_checked('chk_grp_read_point'))           $grp_fields .= " , bo_read_point = '{$bo_read_point}' ";
if (is_checked('chk_grp_write_point'))          $grp_fields .= " , bo_write_point = '{$bo_write_point}' ";
if (is_checked('chk_grp_comment_point'))        $grp_fields .= " , bo_comment_point = '{$bo_comment_point}' ";
if (is_checked('chk_grp_download_point'))       $grp_fields .= " , bo_download_point = '{$bo_download_point}' ";
if (is_checked('chk_grp_category_list')) {
    $grp_fields .= " , bo_category_list = '{$bo_category_list}' ";
    $grp_fields .= " , bo_use_category = '{$bo_use_category}' ";
}
if (is_checked('chk_grp_use_sideview'))         $grp_fields .= " , bo_use_sideview = '{$bo_use_sideview}' ";
if (is_checked('chk_grp_use_file_content'))     $grp_fields .= " , bo_use_file_content = '{$bo_use_file_content}' ";
if (is_checked('chk_grp_use_secret'))           $grp_fields .= " , bo_use_secret = '{$bo_use_secret}' ";
if (is_checked('chk_grp_use_emoticon'))         $grp_fields .= " , bo_use_emoticon = '{$bo_use_emoticon}' ";
if (is_checked('chk_grp_use_dhtml_editor'))     $grp_fields .= " , bo_use_dhtml_editor = '{$bo_use_dhtml_editor}' ";
if (is_checked('chk_grp_use_rss_view'))         $grp_fields .= " , bo_use_rss_view = '{$bo_use_rss_view}' ";
if (is_checked('chk_grp_use_good'))             $grp_fields .= " , bo_use_good = '{$bo_use_good}' ";
if (is_checked('chk_grp_use_nogood'))           $grp_fields .= " , bo_use_nogood = '{$bo_use_nogood}' ";
if (is_checked('chk_grp_use_name'))             $grp_fields .= " , bo_use_name = '{$bo_use_name}' ";
if (is_checked('chk_grp_use_signature'))        $grp_fields .= " , bo_use_signature = '{$bo_use_signature}' ";
if (is_checked('chk_grp_use_ip_view'))          $grp_fields .= " , bo_use_ip_view = '{$bo_use_ip_view}' ";
if (is_checked('chk_grp_use_list_view'))        $grp_fields .= " , bo_use_list_view = '{$bo_use_list_view}' ";
if (is_checked('chk_grp_use_list_file'))        $grp_fields .= " , bo_use_list_file = '{$bo_use_list_file}' ";
if (is_checked('chk_grp_use_list_content'))     $grp_fields .= " , bo_use_list_content = '{$bo_use_list_content}' ";
if (is_checked('chk_grp_use_email'))            $grp_fields .= " , bo_use_email = '{$bo_use_email}' ";
if (is_checked('chk_grp_use_cert'))             $grp_fields .= " , bo_use_cert = '{$bo_use_cert}' ";
if (is_checked('chk_grp_use_sns'))              $grp_fields .= " , bo_use_sns = '{$bo_use_sns}' ";
if (is_checked('chk_grp_skin'))                 $grp_fields .= " , bo_skin = '{$bo_skin}' ";
if (is_checked('chk_grp_mobile_skin'))          $grp_fields .= " , bo_mobile_skin = '{$bo_mobile_skin}' ";
if (is_checked('chk_grp_gallery_cols'))         $grp_fields .= " , bo_gallery_cols = '{$bo_gallery_cols}' ";
if (is_checked('chk_grp_gallery_width'))        $grp_fields .= " , bo_gallery_width = '{$bo_gallery_width}' ";
if (is_checked('chk_grp_gallery_height'))       $grp_fields .= " , bo_gallery_height = '{$bo_gallery_height}' ";
if (is_checked('chk_grp_mobile_gallery_width')) $grp_fields .= " , bo_mobile_gallery_width = '{$bo_mobile_gallery_width}' ";
if (is_checked('chk_grp_mobile_gallery_height'))$grp_fields .= " , bo_mobile_gallery_height = '{$bo_mobile_gallery_height}' ";
if (is_checked('chk_grp_table_width'))          $grp_fields .= " , bo_table_width = '{$bo_table_width}' ";
if (is_checked('chk_grp_page_rows'))            $grp_fields .= " , bo_page_rows = '{$bo_page_rows}' ";
if (is_checked('chk_grp_mobile_page_rows'))     $grp_fields .= " , bo_mobile_page_rows = '{$bo_mobile_page_rows}' ";
if (is_checked('chk_grp_subject_len'))          $grp_fields .= " , bo_subject_len = '{$bo_subject_len}' ";
if (is_checked('chk_grp_mobile_subject_len'))   $grp_fields .= " , bo_mobile_subject_len = '{$bo_mobile_subject_len}' ";
if (is_checked('chk_grp_new'))                  $grp_fields .= " , bo_new = '{$bo_new}' ";
if (is_checked('chk_grp_hot'))                  $grp_fields .= " , bo_hot = '{$bo_hot}' ";
if (is_checked('chk_grp_image_width'))          $grp_fields .= " , bo_image_width = '{$bo_image_width}' ";
if (is_checked('chk_grp_reply_order'))          $grp_fields .= " , bo_reply_order = '{$bo_reply_order}' ";
if (is_checked('chk_grp_sort_field'))           $grp_fields .= " , bo_sort_field = '{$bo_sort_field}' ";
if (is_checked('chk_grp_write_limit_text'))     $grp_fields .= " , bo_write_limit_text = '{$bo_write_limit_text}' ";
if (is_checked('chk_grp_comment_limit_count'))    $grp_fields .= " , bo_comment_limit_count    = '{$bo_comment_limit_count}' ";
if (is_checked('chk_grp_comment_limit_time'))     $grp_fields .= " , bo_comment_limit_time     = '{$bo_comment_limit_time}' ";
if (is_checked('chk_grp_comment_limit_text'))     $grp_fields .= " , bo_comment_limit_text     = '{$bo_comment_limit_text}' ";
if (is_checked('chk_grp_comment_limit_text_exc')) $grp_fields .= " , bo_comment_limit_text_exc = '{$bo_comment_limit_text_exc}' ";
if (is_checked('chk_grp_upload_count'))         $grp_fields .= " , bo_upload_count = '{$bo_upload_count}' ";
if (is_checked('chk_grp_upload_size'))          $grp_fields .= " , bo_upload_size = '{$bo_upload_size}' ";
if (is_checked('chk_grp_include_head'))         $grp_fields .= " , bo_include_head = '{$_POST['bo_include_head']}' ";
if (is_checked('chk_grp_include_tail'))         $grp_fields .= " , bo_include_tail = '{$_POST['bo_include_tail']}' ";
if (is_checked('chk_grp_content_head'))         $grp_fields .= " , bo_content_head = '{$bo_content_head}' ";
if (is_checked('chk_grp_content_tail'))         $grp_fields .= " , bo_content_tail = '{$bo_content_tail}' ";
if (is_checked('chk_grp_mobile_content_head'))  $grp_fields .= " , bo_mobile_content_head = '{$bo_mobile_content_head}' ";
if (is_checked('chk_grp_mobile_content_tail'))  $grp_fields .= " , bo_mobile_content_tail = '{$bo_mobile_content_tail}' ";
if (is_checked('chk_grp_insert_content'))       $grp_fields .= " , bo_insert_content = '{$bo_insert_content}' ";
if (is_checked('chk_grp_use_search'))           $grp_fields .= " , bo_use_search = '{$bo_use_search}' ";
if (is_checked('chk_grp_order'))                $grp_fields .= " , bo_order = '{$bo_order}' ";
for ($i=1; $i<=10; $i++) {
    if (is_checked('chk_grp_'.$i)) {
        $grp_fields .= " , bo_{$i}_subj = '".$_POST['bo_'.$i.'_subj']."' ";
        $grp_fields .= " , bo_{$i} = '".$_POST['bo_'.$i]."' ";
    }
}

if ($grp_fields && $gr_id !== '') {
    sql_query(" UPDATE {$g5['board_table']} SET bo_table = bo_table {$grp_fields} WHERE gr_id = '{$gr_id}' ");
}

/* 전체 게시판 적용 */
$all_fields = '';
if (is_checked('chk_all_device'))               $all_fields .= " , bo_device = '{$bo_device}' ";
if (is_checked('chk_all_admin'))                $all_fields .= " , bo_admin = '{$bo_admin}' ";
if (is_checked('chk_all_list_level'))           $all_fields .= " , bo_list_level = '{$bo_list_level}' ";
if (is_checked('chk_all_read_level'))           $all_fields .= " , bo_read_level = '{$bo_read_level}' ";
if (is_checked('chk_all_write_level'))          $all_fields .= " , bo_write_level = '{$bo_write_level}' ";
if (is_checked('chk_all_reply_level'))          $all_fields .= " , bo_reply_level = '{$bo_reply_level}' ";
if (is_checked('chk_all_comment_level'))        $all_fields .= " , bo_comment_level = '{$bo_comment_level}' ";
if (is_checked('chk_all_link_level'))           $all_fields .= " , bo_link_level = '{$bo_link_level}' ";
if (is_checked('chk_all_upload_level'))         $all_fields .= " , bo_upload_level = '{$bo_upload_level}' ";
if (is_checked('chk_all_download_level'))       $all_fields .= " , bo_download_level = '{$bo_download_level}' ";
if (is_checked('chk_all_html_level'))           $all_fields .= " , bo_html_level = '{$bo_html_level}' ";
if (is_checked('chk_all_count_modify'))         $all_fields .= " , bo_count_modify = '{$bo_count_modify}' ";
if (is_checked('chk_all_count_delete'))         $all_fields .= " , bo_count_delete = '{$bo_count_delete}' ";
if (is_checked('chk_all_read_point'))           $all_fields .= " , bo_read_point = '{$bo_read_point}' ";
if (is_checked('chk_all_write_point'))          $all_fields .= " , bo_write_point = '{$bo_write_point}' ";
if (is_checked('chk_all_comment_point'))        $all_fields .= " , bo_comment_point = '{$bo_comment_point}' ";
if (is_checked('chk_all_download_point'))       $all_fields .= " , bo_download_point = '{$bo_download_point}' ";
if (is_checked('chk_all_category_list')) {
    $all_fields .= " , bo_category_list = '{$bo_category_list}' ";
    $all_fields .= " , bo_use_category = '{$bo_use_category}' ";
}
if (is_checked('chk_all_use_sideview'))         $all_fields .= " , bo_use_sideview = '{$bo_use_sideview}' ";
if (is_checked('chk_all_use_file_content'))     $all_fields .= " , bo_use_file_content = '{$bo_use_file_content}' ";
if (is_checked('chk_all_use_secret'))           $all_fields .= " , bo_use_secret = '{$bo_use_secret}' ";
if (is_checked('chk_all_use_emoticon'))         $all_fields .= " , bo_use_emoticon = '{$bo_use_emoticon}' ";
if (is_checked('chk_all_use_dhtml_editor'))     $all_fields .= " , bo_use_dhtml_editor = '{$bo_use_dhtml_editor}' ";
if (is_checked('chk_all_use_rss_view'))         $all_fields .= " , bo_use_rss_view = '{$bo_use_rss_view}' ";
if (is_checked('chk_all_use_good'))             $all_fields .= " , bo_use_good = '{$bo_use_good}' ";
if (is_checked('chk_all_use_nogood'))           $all_fields .= " , bo_use_nogood = '{$bo_use_nogood}' ";
if (is_checked('chk_all_use_name'))             $all_fields .= " , bo_use_name = '{$bo_use_name}' ";
if (is_checked('chk_all_use_signature'))        $all_fields .= " , bo_use_signature = '{$bo_use_signature}' ";
if (is_checked('chk_all_use_ip_view'))          $all_fields .= " , bo_use_ip_view = '{$bo_use_ip_view}' ";
if (is_checked('chk_all_use_list_view'))        $all_fields .= " , bo_use_list_view = '{$bo_use_list_view}' ";
if (is_checked('chk_all_use_list_file'))        $all_fields .= " , bo_use_list_file = '{$bo_use_list_file}' ";
if (is_checked('chk_all_use_list_content'))     $all_fields .= " , bo_use_list_content = '{$bo_use_list_content}' ";
if (is_checked('chk_all_use_email'))            $all_fields .= " , bo_use_email = '{$bo_use_email}' ";
if (is_checked('chk_all_use_cert'))             $all_fields .= " , bo_use_cert = '{$bo_use_cert}' ";
if (is_checked('chk_all_use_sns'))              $all_fields .= " , bo_use_sns = '{$bo_use_sns}' ";
if (is_checked('chk_all_skin'))                 $all_fields .= " , bo_skin = '{$bo_skin}' ";
if (is_checked('chk_all_mobile_skin'))          $all_fields .= " , bo_mobile_skin = '{$bo_mobile_skin}' ";
if (is_checked('chk_all_gallery_cols'))         $all_fields .= " , bo_gallery_cols = '{$bo_gallery_cols}' ";
if (is_checked('chk_all_gallery_width'))        $all_fields .= " , bo_gallery_width = '{$bo_gallery_width}' ";
if (is_checked('chk_all_gallery_height'))       $all_fields .= " , bo_gallery_height = '{$bo_gallery_height}' ";
if (is_checked('chk_all_mobile_gallery_width')) $all_fields .= " , bo_mobile_gallery_width = '{$bo_mobile_gallery_width}' ";
if (is_checked('chk_all_mobile_gallery_height'))$all_fields .= " , bo_mobile_gallery_height = '{$bo_mobile_gallery_height}' ";
if (is_checked('chk_all_table_width'))          $all_fields .= " , bo_table_width = '{$bo_table_width}' ";
if (is_checked('chk_all_page_rows'))            $all_fields .= " , bo_page_rows = '{$bo_page_rows}' ";
if (is_checked('chk_all_mobile_page_rows'))     $all_fields .= " , bo_mobile_page_rows = '{$bo_mobile_page_rows}' ";
if (is_checked('chk_all_subject_len'))          $all_fields .= " , bo_subject_len = '{$bo_subject_len}' ";
if (is_checked('chk_all_mobile_subject_len'))   $all_fields .= " , bo_mobile_subject_len = '{$bo_mobile_subject_len}' ";
if (is_checked('chk_all_new'))                  $all_fields .= " , bo_new = '{$bo_new}' ";
if (is_checked('chk_all_hot'))                  $all_fields .= " , bo_hot = '{$bo_hot}' ";
if (is_checked('chk_all_image_width'))          $all_fields .= " , bo_image_width = '{$bo_image_width}' ";
if (is_checked('chk_all_reply_order'))          $all_fields .= " , bo_reply_order = '{$bo_reply_order}' ";
if (is_checked('chk_all_sort_field'))           $all_fields .= " , bo_sort_field = '{$bo_sort_field}' ";
if (is_checked('chk_all_write_limit_text'))     $all_fields .= " , bo_write_limit_text = '{$bo_write_limit_text}' ";
if (is_checked('chk_all_comment_limit_count'))    $all_fields .= " , bo_comment_limit_count    = '{$bo_comment_limit_count}' ";
if (is_checked('chk_all_comment_limit_time'))     $all_fields .= " , bo_comment_limit_time     = '{$bo_comment_limit_time}' ";
if (is_checked('chk_all_comment_limit_text'))     $all_fields .= " , bo_comment_limit_text     = '{$bo_comment_limit_text}' ";
if (is_checked('chk_all_comment_limit_text_exc')) $all_fields .= " , bo_comment_limit_text_exc = '{$bo_comment_limit_text_exc}' ";
if (is_checked('chk_all_upload_count'))         $all_fields .= " , bo_upload_count = '{$bo_upload_count}' ";
if (is_checked('chk_all_upload_size'))          $all_fields .= " , bo_upload_size = '{$bo_upload_size}' ";
if (is_checked('chk_all_include_head'))         $all_fields .= " , bo_include_head = '{$_POST['bo_include_head']}' ";
if (is_checked('chk_all_include_tail'))         $all_fields .= " , bo_include_tail = '{$_POST['bo_include_tail']}' ";
if (is_checked('chk_all_content_head'))         $all_fields .= " , bo_content_head = '{$bo_content_head}' ";
if (is_checked('chk_all_content_tail'))         $all_fields .= " , bo_content_tail = '{$bo_content_tail}' ";
if (is_checked('chk_all_mobile_content_head'))  $all_fields .= " , bo_mobile_content_head = '{$bo_mobile_content_head}' ";
if (is_checked('chk_all_mobile_content_tail'))  $all_fields .= " , bo_mobile_content_tail = '{$bo_mobile_content_tail}' ";
if (is_checked('chk_all_insert_content'))       $all_fields .= " , bo_insert_content = '{$bo_insert_content}' ";
if (is_checked('chk_all_use_search'))           $all_fields .= " , bo_use_search = '{$bo_use_search}' ";
if (is_checked('chk_all_order'))                $all_fields .= " , bo_order = '{$bo_order}' ";
for ($i=1; $i<=10; $i++) {
    if (is_checked('chk_all_'.$i)) {
        $all_fields .= " , bo_{$i}_subj = '".$_POST['bo_'.$i.'_subj']."' ";
        $all_fields .= " , bo_{$i} = '".$_POST['bo_'.$i]."' ";
    }
}

if ($all_fields !== '') {
    sql_query(" UPDATE {$g5['board_table']} SET bo_table = bo_table {$all_fields} ");
}

/* 캐시 삭제 및 리다이렉트 */
if (function_exists('delete_cache_latest')) {
    delete_cache_latest($bo_table);
}

/* 게시판별 모듈 on/off 저장 */
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
        mdl_set_board_modules($bo_table, $_map);
    }
}

// ─── 스킨 설정 훅 update ────────────────────────────────────────────────────
// 현재 스킨 폴더에 setting_update.hook.php 가 있으면 include ($bo_table, $_POST 사용 가능)
$_hu_skin_path = function_exists('get_skin_path') ? get_skin_path('board', $bo_skin ?: 'basic') : G5_SKIN_PATH.'/board/'.($bo_skin ?: 'basic');
$_hu_hook_file = $_hu_skin_path . '/setting_update.hook.php';
if (file_exists($_hu_hook_file)) {
    include $_hu_hook_file;
}
unset($_hu_skin_path, $_hu_hook_file);

goto_url("./board_form.php?w=u&bo_table={$bo_table}&".$qstr);
?>
