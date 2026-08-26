<?php
@set_time_limit(0);

$gmnow = gmdate('D, d M Y H:i:s') . ' GMT';
header('Expires: 0');
header('Last-Modified: ' . $gmnow);
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: pre-check=0, post-check=0, max-age=0');
header('Pragma: no-cache');

// config.php 가 G5_PATH 를 $g5_path['path'] 로부터 정의하므로
// 모듈 시스템(_loader.php) 이 정상 동작하도록 미리 세팅한다.
// (이 값은 아래에서 g5_path_temp() 에 의해 url 포함 형태로 다시 채워짐)
$g5_path = array(
    'path' => str_replace('\\', '/', dirname(__DIR__)),
    'url'  => '',
);

include_once '../config.php';
include_once '../lib/common.lib.php';

$title = G5_VERSION . " 설치 완료 3/3";
include_once './install.inc.php';

// 필요시 디버그용
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

function post_or_empty($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : '';
}

// common.php 미포함 환경(인스톨러)에서 sql_escape_string 이 없을 경우 폴백 정의
if (!function_exists('sql_escape_string')) {
    function sql_escape_string($str) {
        return addslashes($str);
    }
}

$mysql_host         = post_or_empty('mysql_host');
$mysql_user         = post_or_empty('mysql_user');
$mysql_pass         = post_or_empty('mysql_pass');
$mysql_db           = post_or_empty('mysql_db');
$table_prefix       = post_or_empty('table_prefix');
$admin_id           = post_or_empty('admin_id');
$admin_pass         = post_or_empty('admin_pass');
$admin_name         = post_or_empty('admin_name');
$admin_email        = post_or_empty('admin_email');
$absolute_password  = post_or_empty('absolute_password');
$table_url          = post_or_empty('table_url');

// 필수값 검증
if ($mysql_host === '' || $mysql_user === '' || $mysql_db === '') {
    ?>
    <div class="ins_inner">
        <p>DB 접속 정보가 올바르게 전달되지 않았습니다. 이전 단계에서 다시 입력해 주십시오.</p>
        <div class="inner_btn"><a href="./install_config.php">뒤로가기</a></div>
    </div>
    <?php
    include_once './install.inc2.php';
    exit;
}

if ($table_prefix === '') {
    $table_prefix = 'avo_';
}

// DB 접속
try {
    $dblink = sql_connect($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
} catch (Exception $e) {
    $dblink = false;
}

if (!$dblink) {
    ?>
    <div class="ins_inner">
        <p>MySQL Host, User, Password 를 확인해 주십시오.</p>
        <div class="inner_btn"><a href="./install_config.php">뒤로가기</a></div>
    </div>
    <?php
    include_once './install.inc2.php';
    exit;
}

// DB 선택
try {
    $select_db = sql_select_db($mysql_db, $dblink);
} catch (Exception $e) {
    $select_db = false;
}

if (!$select_db) {
    ?>
    <div class="ins_inner">
        <p>MySQL DB 를 확인해 주십시오.</p>
        <div class="inner_btn"><a href="./install_config.php">뒤로가기</a></div>
    </div>
    <?php
    include_once './install.inc2.php';
    exit;
}

// sql_mode 정리
$mysql_set_mode = 'false';
sql_set_charset('utf8mb4', $dblink);
$result = sql_query("SELECT @@sql_mode AS mode", true, $dblink);
$row = sql_fetch_array($result);
if (!empty($row['mode'])) {
    sql_query("SET SESSION sql_mode = ''", true, $dblink);
    $mysql_set_mode = 'true';
}
unset($result, $row);
?>

<div class="ins_inner">
    <h2><?php echo G5_VERSION ?> 설치가 시작되었습니다.</h2>
    <ol>
<?php
// 테이블 생성
$schema_file = __DIR__ . '/gnuboard5.sql';
if (!is_file($schema_file) || !is_readable($schema_file)) {
    ?>
    <li>gnuboard5.sql 파일을 읽을 수 없습니다. 경로와 권한을 확인해 주십시오. (<?php echo htmlspecialchars($schema_file, ENT_QUOTES, 'UTF-8'); ?>)</li>
    <?php
    include_once './install.inc2.php';
    exit;
}

$schema = file_get_contents($schema_file);
if ($schema === false) {
    ?>
    <li>gnuboard5.sql 내용을 불러오는 데 실패했습니다.</li>
    <?php
    include_once './install.inc2.php';
    exit;
}

// 기존 형태 유지 (SQL 내 변수 치환용)
eval("\$schema = \"$schema\";");

// 주석 제거
$schema = preg_replace('/^--.*$/m', '', $schema);

// 테이블 프리픽스 치환
$schema = preg_replace('/`avo_([^`]+`)/', '`' . $table_prefix . '$1', $schema);

// 구문 단위로 분리 후 실행
$queries = explode(';', $schema);
foreach ($queries as $q) {
    $q = trim($q);
    if ($q === '') continue;
    sql_query($q, true, $dblink);
}
?>

        <li>전체 테이블 생성 완료</li>
<?php


// config 테이블 초기 행 — 변수/긴 텍스트만 명시 입력.
// 그 외 컬럼(cf_use_point, cf_page_rows, cf_money 등)은 gnuboard5.sql 의 DEFAULT 사용.
$admin_id_e    = sql_escape_string($admin_id);
$cf_title_e    = sql_escape_string(G5_VERSION);
$prohibit_id   = 'admin,administrator,관리자,운영자,어드민,주인장,webmaster,웹마스터,sysop,시삽,시샵,manager,매니저,메니저,root,루트,su,guest,방문객';
$stipulation   = '해당 커뮤니티에 맞는 커뮤니티 활동 규칙을 입력합니다. 회원 가입시 오너 동의 사항으로 출력됩니다.';
$privacy       = '해당 커뮤니티에 맞는 캐릭터 생성 유의사항을 입력합니다. 회원 가입시 오너 동의 사항으로 출력됩니다.';
$sql = "
INSERT INTO `{$table_prefix}config`
SET cf_title       = '{$cf_title_e}',
    cf_admin       = '{$admin_id_e}',
    cf_prohibit_id = '".sql_escape_string($prohibit_id)."',
    cf_stipulation = '".sql_escape_string($stipulation)."',
    cf_privacy     = '".sql_escape_string($privacy)."'
";
sql_query($sql, true, $dblink);


// 관리자 회원가입
$sql = "
INSERT INTO `{$table_prefix}member`
SET mb_id = '{$admin_id}',
    mb_password = '" . get_encrypt_string($admin_pass) . "',
    mb_name = '{$admin_name}',
    mb_nick = '{$admin_name}',
    mb_level = '10',
    mb_datetime = '" . G5_TIME_YMDHIS . "',
    mb_ip = '{$_SERVER['REMOTE_ADDR']}'
";
sql_query($sql, true, $dblink);

// 게시판 그룹 추가
$sql = "
INSERT INTO `{$table_prefix}group`
SET gr_id = 'home',
    gr_subject = 'HOME',
    gr_device = 'both'
";
sql_query($sql, true, $dblink);

// 디자인 설정용 경로 계산
function g5_path_temp()
{
    $result['path'] = str_replace('\\', '/', dirname(__FILE__));
    $tilde_remove = preg_replace('/^\/\~[^\/]+(.*)$/', '$1', $_SERVER['SCRIPT_NAME']);
    $document_root = str_replace($tilde_remove, '', $_SERVER['SCRIPT_FILENAME']);
    $root = str_replace($document_root, '', $result['path']);
    $port = $_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '';
    $http = 'http' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 's' : '') . '://';
    $user = str_replace(str_replace($document_root, '', $_SERVER['SCRIPT_FILENAME']), '', $_SERVER['SCRIPT_NAME']);
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
    if (isset($_SERVER['HTTP_HOST']) && preg_match('/:[0-9]+$/', $host)) {
        $host = preg_replace('/:[0-9]+$/', '', $host);
    }
    $host = preg_replace("/[\<\>\'\"\\\'\\\"\%\=\(\)\/\^\*]/", '', $host);
    $result['url'] = $http . $host . $port . $user . $root;
    $result['url'] = str_replace("/install", "", $result['url']);
    return $result;
}

$g5_path = g5_path_temp();

// CSS 기본 설정 insert (원본 유지)
$sql = "INSERT INTO `{$table_prefix}css_config` (`cs_id`, `cs_name`, `cs_value`, `cs_descript`, `cs_etc_1`, `cs_etc_2`, `cs_etc_3`, `cs_etc_4`, `cs_etc_5`, `cs_etc_6`, `cs_etc_7`, `cs_etc_8`, `cs_etc_9`, `cs_etc_10`) VALUES
(1, 'logo', '".$g5_path['url']."/adm/img/logo.png', '', '', '', '', '', '', '', '', '', '', ''),
(2, 'm_logo', '".$g5_path['url']."/adm/img/logo.png', '', '', '', '', '', '', '', '', '', '', ''),
(3, 'background', '".$g5_path['url']."/skin/member/basic/img/bak_admin_login_top_pattern.png', '', '', '', '', '', '', '', '', '', '', ''),
(4, 'm_background', '".$g5_path['url']."/skin/member/basic/img/bak_admin_login_top_pattern.png', '', '', '', '', '', '', '', '', '', '', ''),
(5, 'menu_pos', 'left', '', '', '', '', '', '', '', '', '', '', ''),
(6, 'menu_width', '200', '', '', '', '', '', '', '', '', '', '', ''),
(7, 'menu_height', '50', '', '', '', '', '', '', '', '', '', '', ''),
(8, 'menu_background', '', '', '', '', '', '', '', '', '', '', '', ''),
(9, 'm_menu_background', '', 'rgba(0, 0, 0, .5)', '', '', '', '', '', '', '', '', '', ''),
(10, 'btn_default', '#ffffff', '', '#000000', '#000000', '#ffffff', '#222222', '#000000', '', '', '', '', ''),
(11, 'btn_point', '#ffffff', '', '#29c7c9', '#29c7c9', '#ffffff', '#29c7c9', '#29c7c9', '', '', '', '', ''),
(12, 'btn_etc', '#ffffff', '', '#555555', '#333333', '#ffffff', '#555555', '#333333', '', '', '', '', ''),
(13, 'mmb_contain_bak', '', '', '', '', '', '', '', '', '', '', '', ''),
(14, 'mmb_notice', 'rgba(0, 0, 0, .5)', '', '#ffffff', '', '', '', '', '', '', '', '', ''),
(15, 'mmb_list', '', '', '', '', '', '', '', '', '', '', '', ''),
(16, 'mmb_list_item', '', '', '', '', '', '', '', '', '', '', '', ''),
(17, 'mmb_log', '', '', '', '', '', '', '', '', '', '', '', ''),
(18, 'mmb_reply', '', '', '', '', '', '', '', '', '', '', '', ''),
(19, 'mmb_reply_item', 'rgba(0, 0, 0, .5)', '', '#eaeaea', '', '', '||top||right||bottom||left||', '', '10', '', '', '', ''),
(20, 'mmb_name', '#eeeeee', '', '12', '', '', '', '', '', '', '', '', ''),
(21, 'mmb_owner_name', '#29c7c9', '', '12', '▶', '◀', '', '', '', '', '', '', ''),
(22, 'mmb_datetime', '#bbbbbb', '', '11', '', '', '', '', '', '', '', '', ''),
(23, 'mmb_link', '#29c7c9', '', '', '', '', '', '', '', '', '', '', ''),
(24, 'mmb_call', '#29c7c9', '', '', '', '', '', '', '', '', '', '', ''),
(25, 'mmb_log_ank', '#29c7c9', '', '', '', '', '', '', '', '', '', '', ''),
(26, 'mmb_hash', '#29c7c9', '', '', '', '', '', '', '', '', '', '', ''),
(27, 'color_default', '#ffffff', '', '', '', '', '', '', '', '', '', '', ''),
(28, 'color_bak', '#333333', '', '', '', '', '', '', '', '', '', '', ''),
(29, 'color_point', '#29c7c9', '', '', '', '', '', '', '', '', '', '', ''),
(30, 'input_bak', '#000000', '', '#eeeeee', '#222222', '', '', '', '', '', '', '', ''),
(31, 'box_style', 'rgba(0, 0, 0, .5)', '', '#eeeeee', '', '', '', '', '', '', '', '', ''),
(32, 'menu_text', '#ffffff', '', '14', '#29c7c9', '14', '', '', '', '', '', '', ''),
(33, 'use_header', '', '', '', '', '', '', '', '', '', '', '', ''),
(34, 'm_header_background', '', '', 'rgba(0, 0, 0, .5)', '', '', '', '', '', '', '', '', ''),
(35, 'header_background', '', '', 'rgba(0, 0, 0, .5)', '', '', '', '', '', '', '', '', ''),
(36, 'equalizer', '#29c7c9', '', '#000000', '', '', '', '', '', '', '', '', ''),
(37, 'board_table', '', '', '', '', '', '', '', '', '', '', '', ''),
(38, 'list_header', '#000000', '', '#ffffff', '#333333', 'double', '1', '||top||bottom||', '', '', '', '', ''),
(39, 'list_body', '', '', '#ffffff', '', '', '', '', '', '', '', '', ''),
(40, 'form_header', '#000000', '', '#ffffff', '', '', '', '', '', '', '', '', ''),
(41, 'form_body', 'rgba(255, 255, 255, .1)', '', '#ffffff', '#333333', 'solid', '1', '||top||bottom||', '', '', '', '', ''),
(42, 'sub_menu', 'rgba(0, 0, 0, .5)', '', '#eeeeee', '#555555', 'dashed', '1', '||top||bottom||', '#29c7c9', '', '', '', ''),
(43, 'mmb_counter', '', '', '', '', '', '', '', '', '', '', '', ''),
(44, 'log_action_box', '', '', '', '', '1px solid #ccc', '10px 14px', '14px', '', '', '', '', ''),
(45, 'inventory_item_box', '', '', '#333333', '', '50px', '2px', '1px solid #ccc', '', '', '10px', '', ''),
(46, 'inventory_item_popup', '', '', '', '', '14px', '20px', '1px solid #ccc', '', '', '', '', '')
";
sql_query($sql, true, $dblink);

// 세계관 디자인 기본 설정 insert
// ⓘ adm/world_design_form.php 의 placeholder/select 기본값 기반 (확인 필요)
$sql = "INSERT INTO `{$table_prefix}world_config` (`cs_id`, `cs_name`, `cs_value`, `cs_descript`, `cs_etc_1`, `cs_etc_2`, `cs_etc_3`, `cs_etc_4`, `cs_etc_5`, `cs_etc_6`, `cs_etc_7`, `cs_etc_8`, `cs_etc_9`, `cs_etc_10`) VALUES
(1, 'menu_pos_v', 'top', '', '', '', '', '', '', '', '', '', '', ''),
(2, 'menu_pos_in', 'outside', '', '', '', '', '', '', '', '', '', '', ''),
(3, 'menu_sub_expand', '0', '', '', '', '', '', '', '', '', '', '', ''),
(4, 'menu_area_width', '100%', '', 'auto', '', '', '', '', '', '', '', '', ''),
(5, 'menu_area_bg', '', '', '', '', '', '', '', '', '', '', '', ''),
(6, 'menu_area_border', '1px', '', 'solid', '#cccccc', '', '', '', '', '', '', '', ''),
(7, 'menu_area_layout', '', '', '', '', '6px 10px', '0', '8px', '4px', '', '', '', ''),
(8, 'menu_main_default', '', '', '#333333', '', '14px', '1px', 'solid', '#cccccc', '8px 14px', 'auto', '', ''),
(9, 'menu_main_hover',   '', '', '#333333', '', '14px', '1px', 'solid', '#cccccc', '8px 14px', 'auto', '', ''),
(10, 'menu_sub_default', '', '', '#333333', '', '14px', '1px', 'solid', '#cccccc', '8px 14px', 'auto', '', '0'),
(11, 'menu_sub_hover',   '', '', '#333333', '', '14px', '1px', 'solid', '#cccccc', '8px 14px', 'auto', '', ''),
(12, 'content_bg_color', '', '', '', '', '', '', '', '', '', '', '', ''),
(13, 'content_border', '0', '', 'solid', '#cccccc', '', '', '', '', '', '', '', ''),
(14, 'content_padding', '', '', '', '', '', '', '', '', '', '', '', ''),
(15, 'content_bg_img', '', '', 'no-repeat', '', '', '', '', '', '', '', '', ''),
(16, 'page_bg_desktop', '', '', '', 'no-repeat', 'center center', 'cover', 'fixed', '', '', '', '', ''),
(17, 'page_bg_mobile',  '', '', '', 'no-repeat', 'center center', 'cover', '', '', '', '', '', '')
";
sql_query($sql, true, $dblink);

// 문서 디자인 기본 설정 insert
// ⓘ adm/content_design_form.php 의 placeholder/select 기본값 기반 (확인 필요)
$sql = "INSERT INTO `{$table_prefix}content_design_config` (`cs_id`, `cs_name`, `cs_value`, `cs_descript`, `cs_etc_1`, `cs_etc_2`, `cs_etc_3`, `cs_etc_4`, `cs_etc_5`, `cs_etc_6`, `cs_etc_7`, `cs_etc_8`, `cs_etc_9`, `cs_etc_10`) VALUES
(1, 'content_bg_color', '', '', '', '', '', '', '', '', '', '', '', ''),
(2, 'content_border', '0', '', 'solid', '#cccccc', '', '', '', '', '', '', '', ''),
(3, 'content_padding', '', '', '', '', '', '', '', '', '', '', '', ''),
(4, 'content_bg_img', '', '', 'no-repeat', '', '', '', '', '', '', '', '', ''),
(5, 'decoration_top',    '', '', 'center', '', '', '', '', '', '', '', '', ''),
(6, 'decoration_bottom', '', '', 'center', '', '', '', '', '', '', '', '', ''),
(7, 'page_bg_desktop', '', '', '', 'no-repeat', 'center center', 'cover', 'fixed', '', '', '', '', ''),
(8, 'page_bg_mobile',  '', '', '', 'no-repeat', 'center center', 'cover', '', '', '', '', '', '')
";
sql_query($sql, true, $dblink);
?>

        <li>DB설정 완료</li>
<?php
// 디렉토리 생성
$dir_arr = array(
    $data_path . '/cache',
    $data_path . '/editor',
    $data_path . '/file',
    $data_path . '/log',
    $data_path . '/member',
    $data_path . '/session',
    $data_path . '/content',
    $data_path . '/faq',
    $data_path . '/tmp',
    $data_path . '/banner',
    $data_path . '/intro',
    $data_path . '/character',
    $data_path . '/item',
    $data_path . '/emoticon',
    $data_path . '/side',
    $data_path . '/site',
    $data_path . '/title',
    $data_path . '/class',
    $data_path . '/timeline',
    $data_path . '/timeline/default'
);

foreach ($dir_arr as $dir) {
    if (!is_dir($dir) && !@mkdir($dir, G5_DIR_PERMISSION, true)) {
        echo '<li>디렉토리 생성 실패: ' . htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') . '</li>';
        include_once './install.inc2.php';
        exit;
    }
    @chmod($dir, G5_DIR_PERMISSION);
}
?>

        <li>데이터 디렉토리 생성 완료</li>
<?php
// DB 설정 파일 생성
$dbconfig_file = '../' . G5_DATA_DIR . '/' . G5_DBCONFIG_FILE;
$f = @fopen($dbconfig_file, 'w');
if ($f === false) {
    echo '<li>DB 설정 파일 생성 실패: ' . htmlspecialchars($dbconfig_file, ENT_QUOTES, 'UTF-8') . '</li>';
    include_once './install.inc2.php';
    exit;
}

fwrite($f, "<?php\n");
fwrite($f, "if (!defined('_GNUBOARD_')) exit;\n");
fwrite($f, "define('G5_MYSQL_HOST', '" . addslashes($mysql_host) . "');\n");
fwrite($f, "define('G5_MYSQL_USER', '" . addslashes($mysql_user) . "');\n");
fwrite($f, "define('G5_MYSQL_PASSWORD', '" . addslashes($mysql_pass) . "');\n");
fwrite($f, "define('G5_MYSQL_DB', '" . addslashes($mysql_db) . "');\n");
fwrite($f, "define('G5_MASTER_PW', '" . addslashes($absolute_password) . "');\n");
fwrite($f, "define('G5_DB_URL', '" . addslashes($table_url) . "');\n");
fwrite($f, "define('G5_MYSQL_SET_MODE', {$mysql_set_mode});\n\n");
fwrite($f, "define('G5_TABLE_PREFIX', '" . addslashes($table_prefix) . "');\n\n");
fwrite($f, "\$g5['write_prefix'] = G5_TABLE_PREFIX.'write_';\n");
fwrite($f, "\$g5['config_table'] = G5_TABLE_PREFIX.'config';\n");
fwrite($f, "\$g5['group_table'] = G5_TABLE_PREFIX.'group';\n");
fwrite($f, "\$g5['group_member_table'] = G5_TABLE_PREFIX.'group_member';\n");
fwrite($f, "\$g5['board_table'] = G5_TABLE_PREFIX.'board';\n");
fwrite($f, "\$g5['board_file_table'] = G5_TABLE_PREFIX.'board_file';\n");
fwrite($f, "\$g5['board_good_table'] = G5_TABLE_PREFIX.'board_good';\n");
fwrite($f, "\$g5['board_new_table'] = G5_TABLE_PREFIX.'board_new';\n");
fwrite($f, "\$g5['login_table'] = G5_TABLE_PREFIX.'login';\n");
fwrite($f, "\$g5['member_table'] = G5_TABLE_PREFIX.'member';\n");
fwrite($f, "\$g5['memo_table'] = G5_TABLE_PREFIX.'memo';\n");
fwrite($f, "\$g5['point_table'] = G5_TABLE_PREFIX.'point';\n");
fwrite($f, "\$g5['scrap_table'] = G5_TABLE_PREFIX.'scrap';\n");
fwrite($f, "\$g5['uniqid_table'] = G5_TABLE_PREFIX.'uniqid';\n");
fwrite($f, "\$g5['autosave_table'] = G5_TABLE_PREFIX.'autosave';\n");
fwrite($f, "\$g5['content_table'] = G5_TABLE_PREFIX.'content';\n");
fwrite($f, "\$g5['menu_table'] = G5_TABLE_PREFIX.'menu';\n");
fwrite($f, "\$g5['banner_table'] = G5_TABLE_PREFIX.'banner';\n");
fwrite($f, "\$g5['intro_table'] = G5_TABLE_PREFIX.'intro';\n");
fwrite($f, "\$g5['character_table'] = G5_TABLE_PREFIX.'character';\n");
fwrite($f, "\$g5['class_table'] = G5_TABLE_PREFIX.'character_class';\n");
fwrite($f, "\$g5['side_table'] = G5_TABLE_PREFIX.'character_side';\n");
fwrite($f, "\$g5['title_table'] = G5_TABLE_PREFIX.'character_title';\n");
fwrite($f, "\$g5['title_has_table'] = G5_TABLE_PREFIX.'has_title';\n");
fwrite($f, "\$g5['couple_table'] = G5_TABLE_PREFIX.'couple';\n");
fwrite($f, "\$g5['emoticon_table'] = G5_TABLE_PREFIX.'emoticon';\n");
fwrite($f, "\$g5['exp_table'] = G5_TABLE_PREFIX.'exp';\n");
fwrite($f, "\$g5['inventory_table'] = G5_TABLE_PREFIX.'inventory';\n");
fwrite($f, "\$g5['item_table'] = G5_TABLE_PREFIX.'item';\n");
fwrite($f, "\$g5['recepi_table'] = G5_TABLE_PREFIX.'item_recepi';\n");
fwrite($f, "\$g5['explorer_table'] = G5_TABLE_PREFIX.'item_explorer';\n");
fwrite($f, "\$g5['relation_table'] = G5_TABLE_PREFIX.'relation_character';\n");
fwrite($f, "\$g5['order_table'] = G5_TABLE_PREFIX.'order';\n");
fwrite($f, "\$g5['closthes_table'] = G5_TABLE_PREFIX.'character_closthes';\n");
fwrite($f, "\$g5['call_table'] = G5_TABLE_PREFIX.'call_board';\n");
fwrite($f, "\$g5['call_board_table'] = G5_TABLE_PREFIX.'call_board';\n");
fwrite($f, "\$g5['call_type_map_table'] = G5_TABLE_PREFIX.'call_type_map';\n");
fwrite($f, "\$g5['hook_registry_table'] = G5_TABLE_PREFIX.'hook_registry';\n");
fwrite($f, "\$g5['css_table'] = G5_TABLE_PREFIX.'css_config';\n");
fwrite($f, "\$g5['article_table'] = G5_TABLE_PREFIX.'article';\n");
fwrite($f, "\$g5['article_default_table'] = G5_TABLE_PREFIX.'article_default';\n");
fwrite($f, "\$g5['value_table'] = G5_TABLE_PREFIX.'article_value';\n");
fwrite($f, "\$g5['level_table'] = G5_TABLE_PREFIX.'level_setting';\n");
fwrite($f, "\$g5['shop_table'] = G5_TABLE_PREFIX.'shop';\n");
fwrite($f, "\$g5['status_config_table'] = G5_TABLE_PREFIX.'status';\n");
fwrite($f, "\$g5['status_table'] = G5_TABLE_PREFIX.'status_character';\n");
fwrite($f, "\$g5['module_setting_table'] = G5_TABLE_PREFIX.'module_setting';\n");
fwrite($f, "\$g5['calc_preset_table'] = G5_TABLE_PREFIX.'calc_preset';\n");
fwrite($f, "\$g5['reset_setting_table'] = G5_TABLE_PREFIX.'reset_setting';\n");
fwrite($f, "\$g5['reset_manager_table'] = G5_TABLE_PREFIX.'reset_manager';\n");
fwrite($f, "\$g5['notice_table'] = G5_TABLE_PREFIX.'notice';\n");
fwrite($f, "\$g5['time_table'] = G5_TABLE_PREFIX.'character_time';\n");
fwrite($f, "\$g5['time_value_table'] = G5_TABLE_PREFIX.'character_time_value';\n");
fwrite($f, "\$g5['time_article_table'] = G5_TABLE_PREFIX.'character_time_article';\n");
fwrite($f, "\$g5['side_time_table'] = G5_TABLE_PREFIX.'character_side_time';\n");
fwrite($f, "\$g5['class_time_table'] = G5_TABLE_PREFIX.'character_class_time';\n");
fwrite($f, "\$g5['npc_time_table'] = G5_TABLE_PREFIX.'character_npc_time';\n");
fwrite($f, "\$g5['theme_preset_table'] = G5_TABLE_PREFIX.'theme_preset';\n");
fwrite($f, "\$g5['theme_preset_slot_table'] = G5_TABLE_PREFIX.'theme_preset_slot';\n");
fwrite($f, "\$g5['design_preset_table'] = G5_TABLE_PREFIX.'design_preset';\n");
fwrite($f, "\$g5['content_preset_table'] = G5_TABLE_PREFIX.'content_preset';\n");
fwrite($f, "\$g5['content_design_config_table'] = G5_TABLE_PREFIX.'content_design_config';\n");
fwrite($f, "\$g5['world_table'] = G5_TABLE_PREFIX.'world';\n");
fwrite($f, "\$g5['world_menu_table'] = G5_TABLE_PREFIX.'world_menu';\n");
fwrite($f, "\$g5['world_preset_table'] = G5_TABLE_PREFIX.'world_preset';\n");
fwrite($f, "\$g5['world_config_table'] = G5_TABLE_PREFIX.'world_config';\n");
fwrite($f, "?>");
fclose($f);
@chmod($dbconfig_file, G5_FILE_PERMISSION);
?>

        <li>DB설정 파일 생성 완료 (<?php echo htmlspecialchars($dbconfig_file, ENT_QUOTES, 'UTF-8'); ?>)</li>
<?php

if (is_file($dbconfig_file)) {
    include_once $dbconfig_file;
}

// sql_query() 등에서 사용할 기본 커넥션 지정
// (그누보드 common.lib.php 구현에 맞춰 둘 다 세팅)
$g5['connect_db'] = $dblink;
$connect_db = $dblink;

// data 디렉토리 .htaccess 생성
$ht_path = $data_path . '/.htaccess';
$f = @fopen($ht_path, 'w');
if ($f === false) {
    echo '<li>.htaccess 생성 실패: ' . htmlspecialchars($ht_path, ENT_QUOTES, 'UTF-8') . '</li>';
    include_once './install.inc2.php';
    exit;
}
$ht_str = "<FilesMatch \"\.(htaccess|htpasswd|[Pp][Hh][Pp]|[Pp]?[Hh][Tt][Mm][Ll]?|[Ii][Nn][Cc]|[Cc][Gg][Ii]|[Pp][Ll])\">\n"
        . "Order allow,deny\n"
        . "Deny from all\n"
        . "</FilesMatch>\n";
fwrite($f, $ht_str);
fclose($f);
?>

        <li>data 디렉토리 접근제한 설정 완료</li>
<?php
/* ---------------------------------------------------------------------------
 *  기본 제공 모듈(bbs_*) 자동 활성화
 *  - 모듈 시스템(_loader.php) + reset_setting 헬퍼 로드 후 mdl_activate() 호출
 *  - 각 모듈의 install.php 가 실행되어 wr_* 컬럼/리셋 카운터 등이 자동 등록됨
 * ------------------------------------------------------------------------- */
$core_modules = array('bbs_action_combine', 'bbs_action_search', 'bbs_dice', 'bbs_item_use');

if (defined('G5_PATH') && G5_PATH !== '' && is_file(G5_PATH.'/module/_loader.php')) {
    // dbconfig.php 가 이미 include 되어 $g5 / $connect_db 가 세팅된 상태
    if (is_file(G5_PATH.'/extend/00_reset.lib.php')) {
        include_once G5_PATH.'/extend/00_reset.lib.php';
    }
    include_once G5_PATH.'/module/_loader.php';

    echo '<li>기본 제공 모듈 활성화 시작</li>';
    foreach ($core_modules as $mn) {
        if (!is_dir(G5_PATH.'/module/'.$mn)) continue;
        $r = function_exists('mdl_activate') ? mdl_activate($mn) : '모듈 시스템 미로드';
        if ($r === true) {
            echo '<li>&nbsp;&nbsp;✓ '.htmlspecialchars($mn, ENT_QUOTES).' 활성화 완료</li>';
        } else {
            echo '<li>&nbsp;&nbsp;⚠ '.htmlspecialchars($mn, ENT_QUOTES).' 활성화 실패: '.htmlspecialchars((string)$r, ENT_QUOTES).'</li>';
        }
    }
    echo '<li>기본 제공 모듈 활성화 완료</li>';
}
?>
    </ol>

    <p>축하합니다. <?php echo G5_VERSION ?> 설치가 완료되었습니다.</p>
</div>

<div class="ins_inner">
    <h2>환경설정 변경은 다음의 과정을 따르십시오.</h2>
    <ol>
        <li>메인화면으로 이동</li>
        <li>관리자 로그인</li>
        <li>관리자 모드 접속</li>
        <li>환경설정 메뉴의 기본환경설정 페이지로 이동</li>
    </ol>
    <div class="inner_btn">
        <a href="../adm/">아보카도 8 관리자 페이지로 이동</a>
    </div>
</div>

<?php
include_once './install.inc2.php';
