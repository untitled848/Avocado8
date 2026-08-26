<?php
/*******************************************************************************
** 공통 변수, 상수, 코드 (PHP 8 대응, ?? 미사용, isset 기반)
*******************************************************************************/
error_reporting(E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING);

// PHP 8.0+ : Notice/Warning 으로 승격된 일부 undefined 접근을 조용히 스킵
// (레거시 코드 호환용. 새 코드는 ses() / isset 사용 권장)
if (PHP_VERSION_ID >= 80000) {
    set_error_handler(function ($errno, $errstr) {
        if (stripos($errstr, 'Undefined array key') !== false
            || stripos($errstr, 'Undefined variable') !== false
            || stripos($errstr, 'Undefined index') !== false
            || stripos($errstr, 'Undefined offset') !== false
            || stripos($errstr, 'Trying to access array offset on') !== false
            || stripos($errstr, 'Attempt to read property') !== false) {
            return true;
        }
        return false;
    }, E_WARNING | E_NOTICE);
}

// 보안설정이나 프레임이 달라도 쿠키가 통하도록 설정
header('P3P: CP="ALL CURa ADMa DEVa TAIa OUR BUS IND PHY ONL UNI PUR FIN COM NAV INT DEM CNT STA POL HEA PRE LOC OTC"');

//==========================================================================================================================
// HTTPS 자동 강제 제어 스위치
//   - true  : 모든 내부 리다이렉트/URL 생성 시 https:// → http:// 로 강제 변환
//             (HSTS·upgrade-insecure-requests·DB에 저장된 https URL 등으로
//              http 사이트인데 https 로 자동 전환되어 화면이 안 뜨는 문제 방지)
//   - false : 원래 스킴 그대로 사용 (기본값)
//   영향 함수: lib/common.lib.php 의 goto_url(), https_url()
//==========================================================================================================================
//if (!defined('G5_FORCE_HTTP')) define('G5_FORCE_HTTP', true);

if (!defined('G5_SET_TIME_LIMIT')) define('G5_SET_TIME_LIMIT', 0);
@set_time_limit(G5_SET_TIME_LIMIT);

//==========================================================================================================================
// extract($_GET) 등으로 슈퍼글로벌 오염 방지
//==========================================================================================================================
$ext_arr = array(
    'PHP_SELF', '_ENV', '_GET', '_POST', '_FILES', '_SERVER', '_COOKIE', '_SESSION', '_REQUEST',
    'HTTP_ENV_VARS', 'HTTP_GET_VARS', 'HTTP_POST_VARS', 'HTTP_POST_FILES', 'HTTP_SERVER_VARS',
    'HTTP_COOKIE_VARS', 'HTTP_SESSION_VARS', 'GLOBALS'
);
$ext_cnt = count($ext_arr);
for ($i=0; $i<$ext_cnt; $i++) {
    if (isset($_GET[$ext_arr[$i]]))  unset($_GET[$ext_arr[$i]]);
    if (isset($_POST[$ext_arr[$i]])) unset($_POST[$ext_arr[$i]]);
}
//==========================================================================================================================

function g5_path()
{
    $result = array();

    $base_path = dirname(__FILE__);
    $result['path'] = str_replace('\\', '/', $base_path);

    $script_name     = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $script_filename = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';

    $tilde_remove  = preg_replace('/^\/\~[^\/]+(.*)$/', '$1', $script_name);
    $document_root = str_replace($tilde_remove, '', $script_filename);
    $root          = str_replace($document_root, '', $result['path']);

    $server_port = isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 80;
    $port = $server_port !== 80 ? ':'.$server_port : '';

    $https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    $http  = $https ? 'https://' : 'http://';

    $user = str_replace(str_replace($document_root, '', $script_filename), '', $script_name);

    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
    } else {
        $host = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
    }

    if (preg_match('/:[0-9]+$/', $host)) {
        $host = preg_replace('/:[0-9]+$/', '', $host);
    }

    $host = preg_replace("/[\<\>\'\"\\\'\\\"\%\=\(\)\/\^\*]/", '', $host);

    $result['url'] = $http.$host.$port.$user.$root;

    return $result;
}

$g5_path = g5_path();

include_once($g5_path['path'].'/config.php');

unset($g5_path);

// multi-dimensional array에 사용자지정 함수 적용
function array_map_deep($fn, $array)
{
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = array_map_deep($fn, $value);
            } else {
                $array[$key] = call_user_func($fn, $value);
            }
        }
    } else {
        $array = call_user_func($fn, $array);
    }

    return $array;
}

// SQL Injection 대응 문자열 필터링
function sql_escape_string($str)
{
    if (defined('G5_ESCAPE_PATTERN') && defined('G5_ESCAPE_REPLACE')) {
        $pattern = G5_ESCAPE_PATTERN;
        $replace = G5_ESCAPE_REPLACE;

        if ($pattern) {
            $str = preg_replace($pattern, $replace, $str);
        }
    }

    $str = addslashes($str);

    return $str;
}

// 슈퍼글로벌 필터 적용
if (defined('G5_ESCAPE_FUNCTION')) {
    $_POST    = array_map_deep(G5_ESCAPE_FUNCTION, $_POST);
    $_GET     = array_map_deep(G5_ESCAPE_FUNCTION, $_GET);
    $_COOKIE  = array_map_deep(G5_ESCAPE_FUNCTION, $_COOKIE);
    $_REQUEST = array_map_deep(G5_ESCAPE_FUNCTION, $_REQUEST);
}

// PHP 4 스타일 호환 (기존 코드 유지 목적) - 순서 주의
@extract($_GET);
@extract($_POST);
@extract($_SERVER);

// 오염 방지용 초기화
$config  = array();
$member  = array();
$board   = array();
$group   = array();
$article = array();
$g5      = isset($g5) && is_array($g5) ? $g5 : array();

//==============================================================================
// DB 설정 및 공통 라이브러리
//------------------------------------------------------------------------------
$dbconfig_file = G5_DATA_PATH.'/'.G5_DBCONFIG_FILE;

if (file_exists($dbconfig_file)) {

    include_once($dbconfig_file);
    include_once(G5_LIB_PATH.'/common.lib.php');

    $connect_db = sql_connect(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD);
    $select_db  = sql_select_db(G5_MYSQL_DB, $connect_db);

    $g5['connect_db'] = $connect_db;

    sql_set_charset('utf8mb4', $connect_db);

    if (defined('G5_MYSQL_SET_MODE') && G5_MYSQL_SET_MODE) {
        sql_query("SET SESSION sql_mode = ''");
    }

    if (defined('G5_TIMEZONE')) {
        sql_query("SET time_zone = '".G5_TIMEZONE."'");
    }

} else {

    ?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>오류! <?php echo G5_VERSION; ?> 설치하기</title>
<link rel="stylesheet" href="install/install.css">
</head>
<body>
<div id="ins_bar">
    <span id="bar_img">AVOCADO EDITION</span>
    <span id="bar_txt">Message</span>
</div>
<h1>아보카도 에디션을 먼저 설치해주십시오.</h1>
<div class="ins_inner">
    <p>다음 파일을 찾을 수 없습니다.</p>
    <ul>
        <li><strong><?php echo G5_DATA_DIR.'/'.G5_DBCONFIG_FILE; ?></strong></li>
    </ul>
    <p>아보카도 에디션 설치 후 다시 실행하시기 바랍니다.</p>
    <div class="inner_btn">
        <a href="<?php echo G5_URL; ?>/install/"><?php echo G5_VERSION; ?> 설치하기</a>
    </div>
</div>
<div id="ins_ft">
    <strong>AVOCADO EDITION</strong>
    <p>GPL! OPEN SOURCE GNUBOARD</p>
</div>
</body>
</html>
<?php
    exit;
}

//==============================================================================
// 디자인 미설치 체크
//------------------------------------------------------------------------------
if (
    !defined('G5_IS_ADMIN')
    && !strstr($_SERVER['REQUEST_URI'], 'login')
    && !strstr($_SERVER['REQUEST_URI'], 'logout')
) {
    $check_design_setting = sql_fetch("select count(*) as cnt from {$g5['css_table']}");
    if (empty($check_design_setting['cnt'])) {
        ?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>오류! <?php echo G5_VERSION; ?> 설정하기</title>
<link rel="stylesheet" href="<?php echo G5_URL; ?>/install/install.css">
</head>
<body>
<div id="ins_bar">
    <span id="bar_img">AVOCADO EDITION</span>
    <span id="bar_txt">Message</span>
</div>
<h1>아보카도 에디션 설정을 완료해주십시오.</h1>
<br>
<div class="ins_inner">
    <p>아보카도 에디션 설치가 완료되었습니다.</p>
    <p>하지만, 디자인 설정 및 캐릭터 프로필 설정이 완료되지 않았습니다.</p>
    <p>관리자 화면에서 관련 설정을 최소 1회 이상 저장해야 합니다.</p>
    <div class="inner_btn">
        <a href="<?php echo G5_URL; ?>/adm/"><?php echo G5_VERSION; ?> 관리자 바로가기</a>
    </div>
</div>
<div id="ins_ft">
    <strong>AVOCADO EDITION</strong>
    <p>GPL! OPEN SOURCE GNUBOARD</p>
</div>
</body>
</html>
<?php
        exit;
    }
}

//==============================================================================
// SESSION 설정
//------------------------------------------------------------------------------
@ini_set('session.use_trans_sid', 0);
@ini_set('url_rewriter.tags', '');

if (defined('G5_SESSION_PATH')) {
    session_save_path(G5_SESSION_PATH);
}

if (isset($SESSION_CACHE_LIMITER) && $SESSION_CACHE_LIMITER) {
    @session_cache_limiter($SESSION_CACHE_LIMITER);
} else {
    @session_cache_limiter('no-cache, must-revalidate');
}

ini_set('session.cache_expire', 180);
ini_set('session.gc_maxlifetime', 10800);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

session_set_cookie_params(0, '/');

if (defined('G5_COOKIE_DOMAIN')) {
    ini_set('session.cookie_domain', G5_COOKIE_DOMAIN);
}

@session_start();

//==============================================================================
// 공용 변수 로드
//------------------------------------------------------------------------------
$config  = sql_fetch(" select * from {$g5['config_table']} ");
$article = sql_fetch(" select * from {$g5['article_default_table']} ");

define('G5_HTTP_BBS_URL',  https_url(G5_BBS_DIR, false));
define('G5_HTTPS_BBS_URL', https_url(G5_BBS_DIR, true));

if (!empty($config['cf_editor'])) {
    define('G5_EDITOR_LIB', G5_EDITOR_PATH.'/'.$config['cf_editor'].'/editor.lib.php');
} else {
    define('G5_EDITOR_LIB', G5_LIB_PATH.'/editor.lib.php');
}

// PHPSESSID 위조 방지
if (isset($_REQUEST['PHPSESSID']) && $_REQUEST['PHPSESSID'] !== session_id()) {
    goto_url(G5_BBS_URL.'/logout.php');
}

// QUERY STRING 빌드
$qstr = '';

// sca
if (isset($_REQUEST['sca'])) {
    $sca = clean_xss_tags(trim($_REQUEST['sca']));
    $sca = preg_replace("/[\<\>\'\"\\\'\\\"\%\=\(\)\/\^\*]/", "", $sca);
    if ($sca !== '') {
        $qstr .= '&amp;sca='.urlencode($sca);
    }
} else {
    $sca = '';
}

// sfl
if (isset($_REQUEST['sfl'])) {
    $sfl = trim($_REQUEST['sfl']);
    $sfl = preg_replace("/[\<\>\'\"\\\'\\\"\%\=\(\)\/\^\*\s]/", "", $sfl);
    if ($sfl !== '') {
        $qstr .= '&amp;sfl='.urlencode($sfl);
    }
} else {
    $sfl = '';
}

// stx
if (isset($_REQUEST['stx'])) {
    $stx = get_search_string(trim($_REQUEST['stx']));
    if ($stx !== '') {
        $qstr .= '&amp;stx='.urlencode(cut_str($stx, 20, ''));
    }
} else {
    $stx = '';
}

// sst
if (isset($_REQUEST['sst'])) {
    $sst = trim($_REQUEST['sst']);
    $sst = preg_replace("/[\<\>\'\"\\\'\\\"\%\=\(\)\/\^\*\s]/", "", $sst);
    if ($sst !== '') {
        $qstr .= '&amp;sst='.urlencode($sst);
    }
} else {
    $sst = '';
}

// sod
if (isset($_REQUEST['sod'])) {
    $req_sod = trim($_REQUEST['sod']);
    if (preg_match("/^(asc|desc)$/i", $req_sod)) {
        $sod = $req_sod;
        $qstr .= '&amp;sod='.urlencode($sod);
    } else {
        $sod = '';
    }
} else {
    $sod = '';
}

// sop
if (isset($_REQUEST['sop'])) {
    $req_sop = trim($_REQUEST['sop']);
    if (preg_match("/^(or|and)$/i", $req_sop)) {
        $sop = $req_sop;
        $qstr .= '&amp;sop='.urlencode($sop);
    } else {
        $sop = '';
    }
} else {
    $sop = '';
}

// spt
if (isset($_REQUEST['spt'])) {
    $req_spt = (int)$_REQUEST['spt'];
    if ($req_spt > 0) {
        $spt = $req_spt;
        $qstr .= '&amp;spt='.urlencode($spt);
    } else {
        $spt = '';
    }
} else {
    $spt = '';
}

// page
if (isset($_REQUEST['page'])) {
    $req_page = (int)$_REQUEST['page'];
    if ($req_page > 0) {
        $page = $req_page;
        $qstr .= '&amp;page='.urlencode($page);
    } else {
        $page = '';
    }
} else {
    $page = '';
}

// w
if (isset($_REQUEST['w'])) {
    $w = substr($_REQUEST['w'], 0, 2);
} else {
    $w = '';
}

// wr_id
if (isset($_REQUEST['wr_id'])) {
    $wr_id = (int)$_REQUEST['wr_id'];
} else {
    $wr_id = 0;
}

// bo_table
if (isset($_REQUEST['bo_table'])) {
    $bo_table = preg_replace('/[^a-z0-9_]/i', '', trim($_REQUEST['bo_table']));
    $bo_table = substr($bo_table, 0, 20);
} else {
    $bo_table = '';
}

// URL / urlencode
if (isset($_REQUEST['url'])) {
    $url = strip_tags(trim($_REQUEST['url']));
    $urlencode = urlencode($url);
} else {
    $url = '';
    $req_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $urlencode = urlencode($req_uri);

    if (defined('G5_DOMAIN') && G5_DOMAIN) {
        $p = @parse_url(G5_DOMAIN);
        if (isset($p['path'])) {
            $urlencode = G5_DOMAIN.urldecode(preg_replace('/^'.urlencode($p['path']).'/', '', $urlencode));
        } else {
            $urlencode = G5_DOMAIN.urldecode($urlencode);
        }
    }
}

// gr_id
if (isset($_REQUEST['gr_id']) && !is_array($_REQUEST['gr_id'])) {
    $gr_id = preg_replace('/[^a-z0-9_]/i', '', trim($_REQUEST['gr_id']));
} else {
    $gr_id = '';
}

//==============================================================================
// 자동 로그인 / 회원 정보
//------------------------------------------------------------------------------
if (isset($_SESSION['ss_mb_id']) && $_SESSION['ss_mb_id']) {

    $member = get_member($_SESSION['ss_mb_id']);

    if (!empty($member['mb_intercept_date']) && $member['mb_intercept_date'] <= date('Ymd', G5_SERVER_TIME)) {

        set_session('ss_mb_id', '');
        $member = array();

    } else {

        if (substr($member['mb_today_login'], 0, 10) !== G5_TIME_YMD) {

            insert_point($member['mb_id'], $config['cf_login_point'], G5_TIME_YMD.' 첫로그인', '@login', $member['mb_id'], G5_TIME_YMD);

            $sql = " update {$g5['member_table']}
                        set mb_today_login = '".G5_TIME_YMDHIS."',
                            mb_login_ip = '".$_SERVER['REMOTE_ADDR']."'
                      where mb_id = '{$member['mb_id']}' ";
            sql_query($sql);
        }
    }

} else {

    // 자동로그인
    $tmp_mb_id = get_cookie('ck_mb_id');

    if ($tmp_mb_id !== '') {
        $tmp_mb_id = substr(preg_replace('/[^a-zA-Z0-9_]*/', '', $tmp_mb_id), 0, 20);

        if (strtolower($tmp_mb_id) !== strtolower($config['cf_admin'])) {

            $sql = " select mb_password, mb_intercept_date, mb_leave_date
                       from {$g5['member_table']}
                      where mb_id = '{$tmp_mb_id}' ";
            $row = sql_fetch($sql);

            if (!empty($row['mb_password'])) {
                $key = md5(
                    (isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '').
                    (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '').
                    (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '').
                    $row['mb_password']
                );
                $tmp_key = get_cookie('ck_auto');

                if ($tmp_key !== '' && $tmp_key === $key) {
                    if ($row['mb_intercept_date'] === '' && $row['mb_leave_date'] === '') {

                        set_session('ss_mb_id', $tmp_mb_id);

                        echo "<script>window.location.reload();</script>";
                        exit;
                    }
                }
            }
            unset($row);
        }
    }
}

// 게시판/그룹 로드
$write       = array();
$write_table = '';

if ($bo_table !== '') {
    $board = sql_fetch(" select * from {$g5['board_table']} where bo_table = '{$bo_table}' ");
    if (!empty($board['bo_table'])) {
        set_cookie('ck_bo_table', $board['bo_table'], 86400);
        $gr_id       = $board['gr_id'];
        $write_table = $g5['write_prefix'].$bo_table;

        if ($wr_id) {
            $write = sql_fetch(" select * from {$write_table} where wr_id = '{$wr_id}' ");
        }
    }
}

if ($gr_id !== '') {
    $group = sql_fetch(" select * from {$g5['group_table']} where gr_id = '{$gr_id}' ");
}

// 회원 / 비회원
$is_member = false;
$is_guest  = false;
$is_admin  = '';

if (isset($member['mb_id']) && $member['mb_id'] !== '') {
    $is_member        = true;
    $is_admin         = is_admin($member['mb_id']);
    $member['mb_dir'] = substr($member['mb_id'], 0, 2);
} else {
    $is_guest          = true;
    $member['mb_id']   = '';
    $member['mb_level'] = 1;
}

// IP 접근 제한 (관리자 제외)
if (!$is_admin) {

    // 접근가능 IP
    $cf_possible_ip = trim(isset($config['cf_possible_ip']) ? $config['cf_possible_ip'] : '');
    if ($cf_possible_ip !== '') {
        $is_possible_ip = false;
        $pattern = explode("\n", $cf_possible_ip);

        for ($i=0; $i<count($pattern); $i++) {
            $line = trim($pattern[$i]);
            if ($line === '') continue;

            $line = str_replace('.', '\.', $line);
            $line = str_replace('+', '[0-9\.]+', $line);
            $pat  = '/^'.$line.'$/';

            if (preg_match($pat, $_SERVER['REMOTE_ADDR'])) {
                $is_possible_ip = true;
                break;
            }
        }

        if (!$is_possible_ip) {
            die('접근이 가능하지 않습니다.');
        }
    }

    // 접근차단 IP
    $cf_intercept_ip = trim(isset($config['cf_intercept_ip']) ? $config['cf_intercept_ip'] : '');
    if ($cf_intercept_ip !== '') {
        $pattern = explode("\n", $cf_intercept_ip);
        for ($i=0; $i<count($pattern); $i++) {
            $line = trim($pattern[$i]);
            if ($line === '') continue;

            $line = str_replace('.', '\.', $line);
            $line = str_replace('+', '[0-9\.]+', $line);
            $pat  = '/^'.$line.'$/';

            if (preg_match($pat, $_SERVER['REMOTE_ADDR'])) {
                die('접근 불가합니다.');
            }
        }
    }
}

//==============================================================================
// 테마 경로 설정 (레거시 G5_THEME_* 상수 호환용 — 신규 시스템은 extend/01_theme.lib.php의 슬롯/프리셋 사용)
//------------------------------------------------------------------------------
if (isset($config['cf_theme']) && trim($config['cf_theme']) !== '') {
    $theme_path = G5_PATH.'/'.G5_THEME_DIR.'/'.$config['cf_theme'];
    if (is_dir($theme_path)) {
        define('G5_THEME_PATH',        $theme_path);
        define('G5_THEME_URL',         G5_URL.'/'.G5_THEME_DIR.'/'.$config['cf_theme']);
        define('G5_THEME_MOBILE_PATH', $theme_path.'/'.G5_MOBILE_DIR);
        define('G5_THEME_LIB_PATH',    $theme_path.'/'.G5_LIB_DIR);
        define('G5_THEME_CSS_URL',     G5_THEME_URL.'/'.G5_CSS_DIR);
        define('G5_THEME_IMG_URL',     G5_THEME_URL.'/'.G5_IMG_DIR);
        define('G5_THEME_JS_URL',      G5_THEME_URL.'/'.G5_JS_DIR);
    }
    unset($theme_path);
}


//==============================================================================
// 사용기기 설정
//------------------------------------------------------------------------------
$is_mobile  = false;
$set_device = true;

if (defined('G5_THEME_DEVICE') && G5_THEME_DEVICE !== '') {
    if (G5_THEME_DEVICE === 'pc') {
        $is_mobile  = false;
        $set_device = false;
    } else if (G5_THEME_DEVICE === 'mobile') {
        $is_mobile  = true;
        $set_device = false;
    }
}

if (defined('G5_SET_DEVICE') && $set_device) {
    if (G5_SET_DEVICE === 'pc') {
        $is_mobile  = false;
        $set_device = false;
    } else if (G5_SET_DEVICE === 'mobile') {
        $is_mobile  = true;
        $set_device = false;
    }
}

// 모바일 환경 결정
if (G5_USE_MOBILE && $set_device) {
    if (isset($_REQUEST['device']) && $_REQUEST['device'] === 'pc') {
        $is_mobile = false;
    } else if (isset($_REQUEST['device']) && $_REQUEST['device'] === 'mobile') {
        $is_mobile = true;
    } else if (isset($_SESSION['ss_is_mobile'])) {
        $is_mobile = $_SESSION['ss_is_mobile'] ? true : false;
    } else if (is_mobile()) {
        $is_mobile = true;
    }
} else if (!$set_device) {
    // nothing
}

$_SESSION['ss_is_mobile'] = $is_mobile;

define('G5_IS_MOBILE', $is_mobile);
define('G5_DEVICE_BUTTON_DISPLAY', $set_device ? true : false);

if (G5_IS_MOBILE) {
    $g5['mobile_path'] = G5_PATH.'/'.$g5['mobile_dir'];
}

//==============================================================================
// 스킨경로 기본값 (extend 로드 이전 — 임시/기본값)
//------------------------------------------------------------------------------
// 주의: 이 시점에는 theme_skin_dir() 등 extend 함수가 아직 로드되지 않아
// override 해석이 불가능하므로, 최종 값은 extend 로드 뒤 재설정한다.
if (isset($board['bo_skin'])) {
    $board_skin_path = get_skin_path('board', $board['bo_skin']);
    $board_skin_url  = get_skin_url('board', $board['bo_skin']);
}

$member_skin_path = get_skin_path('member', 'basic');
$member_skin_url  = get_skin_url('member', 'basic');

//==============================================================================
// DB 정리 및 확장 로드
//------------------------------------------------------------------------------
include_once(G5_BBS_PATH.'/db_table.optimize.php');

// 모듈 시스템 로더 (설치되어 있을 때만)
if (is_file(G5_PATH.'/module/_loader.php')) {
    include_once(G5_PATH.'/module/_loader.php');
    $module_path = G5_PATH.'/module';
    $module_url = G5_URL.'/module';
}

// 확장 기능
if (defined('G5_EXTEND_PATH') && is_dir(G5_EXTEND_PATH)) {
    $extend_file = array();
    $tmp = dir(G5_EXTEND_PATH);
    while (false !== ($entry = $tmp->read())) {
        if (preg_match('/\.php$/i', $entry)) {
            $extend_file[] = $entry;
        }
    }
    $tmp->close();

    if (!empty($extend_file)) {
        natsort($extend_file);
        foreach ($extend_file as $file) {
            include_once(G5_EXTEND_PATH.'/'.$file);
        }
    }
    unset($extend_file);
}


// 권한 관리 라이브러리
include_once(G5_LIB_PATH.'/permission.lib.php');
perm_check_admin_only();

ob_start();

// 캐시/헤더 설정
header('Content-Type: text/html; charset=utf-8');
$gmnow = gmdate('D, d M Y H:i:s').' GMT';
header('Expires: 0');
header('Last-Modified: '.$gmnow);
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: pre-check=0, post-check=0, max-age=0');
header('Pragma: no-cache');

$html_process = new html_process();

//==============================================================================
// 스킨경로 재설정 — extend(테마 라이브러리 포함) 로드 완료 후 override 반영
//------------------------------------------------------------------------------
if (isset($board['bo_skin'])) {
    $board_skin_path = get_skin_path('board', $board['bo_skin']);
    $board_skin_url  = get_skin_url('board', $board['bo_skin']);
}
$member_skin_path = get_skin_path('member', 'basic');
$member_skin_url  = get_skin_url('member', 'basic');

//==============================================================================
// 테마 디스패처 — 모든 setup(html_process / skin path / extend) 완료 후 호출
// 이전에는 extend/zz_theme_dispatch.php 에서 호출했으나 그 시점에는
// $html_process 가 아직 설정되지 않아 add_stylesheet 가 무효화되는 버그 있음.
//------------------------------------------------------------------------------
if (function_exists('theme_dispatch_override')) {
    theme_dispatch_override();
}
