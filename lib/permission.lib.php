<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * permission.lib.php — 통합 권한 관리 라이브러리
 *
 * - perm_deny()                  : 권한 없음 처리 (fallback URL 또는 /warning.php)
 * - perm_check_page($key)        : 페이지별 사용여부 체크 (관리자 우회)
 * - perm_check_admin_only()      : cf_open_scope='admin' 이면 비관리자 차단
 * - perm_is_guest_allowed_page() : 비공개 사이트에서 비회원이 접근 가능한 인증/가입 페이지
 * - perm_is_fallback_request()   : 현재 요청이 cf_perm_fallback_url 과 같은지 (자기참조 루프 방지)
 * - perm_is_warning_request()    : 현재 요청이 /warning.php 인지
 *
 * DB 스키마 (cf_perm_*, cf_open_scope, cf_character_*) 는
 * install/gnuboard5.sql 에서 생성/추가.
 */

// 페이지 접근 레벨 상수 (3단계)
//   1  = 방문자(누구나)
//   2  = 일반회원(로그인 필요)
//   10 = 운영자(관리자만)
if (!defined('PERM_LV_GUEST'))  define('PERM_LV_GUEST',  1);
if (!defined('PERM_LV_MEMBER')) define('PERM_LV_MEMBER', 2);
if (!defined('PERM_LV_ADMIN'))  define('PERM_LV_ADMIN', 10);

// config 배열 갱신 (구 환경에서 컬럼 누락 시 안전 기본값 보완)
if (isset($config) && is_array($config)) {
    $config += [
        'cf_perm_fallback_url'         => '',
        'cf_open_scope'                => 'all',
        'cf_character_count'           => 0,
        'cf_character_edit_admin_only' => 0,
        'cf_perm_ready'                => PERM_LV_GUEST,
        'cf_perm_member'               => PERM_LV_GUEST,
        'cf_perm_memo'                 => PERM_LV_MEMBER,
        'cf_perm_shop'                 => PERM_LV_GUEST,
    ];
}

// ─── 함수 ─────────────────────────────────────────────────────────────────────

/**
 * 권한 없음 처리.
 * cf_perm_fallback_url 이 있고 지금이 그 주소가 아니면 그곳으로.
 * URL 이 비었거나 그 주소에서도 막히면 /warning.php (alert 없음).
 */
function perm_deny($msg = '접근 권한이 없습니다.') {
    global $config;
    if (perm_is_warning_request()) {
        return;
    }
    $url = trim((string)($config['cf_perm_fallback_url'] ?? ''));
    if ($url !== '' && !perm_is_fallback_request()) {
        goto_url($url);
    }
    if (function_exists('set_session')) {
        set_session('ss_perm_deny_msg', $msg);
    }
    goto_url(perm_warning_url());
}

/**
 * 현재 사용자의 권한 레벨 반환.
 *   비로그인 → PERM_LV_GUEST(1)
 *   로그인   → PERM_LV_MEMBER(2)
 *   관리자   → PERM_LV_ADMIN(10)
 */
function perm_current_level() {
    global $is_admin, $is_member;
    if (!empty($is_admin))  return PERM_LV_ADMIN;
    if (!empty($is_member)) return PERM_LV_MEMBER;
    return PERM_LV_GUEST;
}

/**
 * 페이지별 접근 레벨 체크.
 * cf_perm_{$key} 가 현재 사용자 레벨보다 높으면 거부.
 * 관리자는 항상 통과. cf_perm_X 미설정/0 은 PERM_LV_GUEST 폴백.
 *
 * @param string $key  'memo' | 'member' | 'ready' | 'shop' 등
 */
function perm_check_page($key) {
    global $config;
    $cur = perm_current_level();
    if ($cur >= PERM_LV_ADMIN) return true;

    $cfg_key = 'cf_perm_' . $key;
    $need = isset($config[$cfg_key]) ? (int)$config[$cfg_key] : PERM_LV_GUEST;
    if ($need <= 0) $need = PERM_LV_GUEST;

    if ($cur < $need) {
        if ($need >= PERM_LV_ADMIN) {
            perm_deny('관리자만 접근 가능합니다.');
        } elseif ($need >= PERM_LV_MEMBER && $cur < PERM_LV_MEMBER) {
            // 비로그인 → 접근 거부 처리 (fallback URL 또는 alert 후 뒤로)
            perm_deny('회원만 접근 가능합니다.');
        } else {
            perm_deny();
        }
    }
    return true;
}

/**
 * 현재 요청의 PHP 스크립트 파일명 (소문자 basename).
 */
function perm_current_script_name() {
    $candidates = array(
        $_SERVER['SCRIPT_NAME'] ?? '',
        $_SERVER['PHP_SELF'] ?? '',
        (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH),
    );
    foreach ($candidates as $p) {
        $base = strtolower(basename(str_replace('\\', '/', (string)$p)));
        if ($base !== '' && substr($base, -4) === '.php') {
            return $base;
        }
    }
    return '';
}

/**
 * 사이트 공개 범위가 회원/관리자일 때, 비회원이 접근해도 되는 페이지인지 여부.
 *
 * - 로그인/로그아웃/안내(alert) 는 항상 허용
 * - 회원가입 흐름은 cf_open_scope='member' 이고 계정생성(cf_1)이 허용된 경우에만 허용
 * - 관리자 잠금(admin) 에서는 회원가입을 열지 않음
 */
function perm_is_guest_allowed_page() {
    global $config;

    $script = perm_current_script_name();
    if ($script === '') {
        return false;
    }

    $always = array(
        'login.php',
        'login_check.php',
        'logout.php',
        'alert.php',
        'alert_close.php',
        'warning.php',
    );
    if (in_array($script, $always, true)) {
        return true;
    }

    $scope = (string)($config['cf_open_scope'] ?? 'all');
    if ($scope !== 'member') {
        return false;
    }
    if (intval($config['cf_1'] ?? 0) < 1) {
        return false;
    }

    $register = array(
        'register.php',
        'register_form.php',
        'register_form_update.php',
        'register_result.php',
        'ajax.mb_id.php',
        'ajax.filter.php',
    );
    return in_array($script, $register, true);
}

/**
 * 내부 URL을 path + query 로 정규화.
 * 상대 경로는 G5_URL 기준. 다른 호스트면 external.
 * 요청당·URL 문자열당 static 캐시.
 *
 * @return array{external:bool,path:string,query:array}|null
 */
function perm_normalize_request($url) {
    static $cache = array();
    $url = trim(str_replace('&amp;', '&', (string)$url));
    if ($url === '') {
        return null;
    }
    if (isset($cache[$url])) {
        return $cache[$url];
    }

    $site = parse_url((string)G5_URL);
    $site_host = strtolower((string)($site['host'] ?? ''));
    $site_port = (string)($site['port'] ?? '');
    $scheme    = (string)($site['scheme'] ?? 'http');
    if ($scheme === '') {
        $scheme = 'http';
    }

    $full = $url;
    if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
        if (isset($url[0]) && $url[0] === '/') {
            $hostport = (string)($site['host'] ?? 'localhost');
            if ($site_port !== '') {
                $hostport .= ':'.$site_port;
            }
            $full = $scheme.'://'.$hostport.$url;
        } else {
            $rel = $url;
            if (strpos($rel, './') === 0) {
                $rel = substr($rel, 2);
            }
            $full = rtrim((string)G5_URL, '/').'/'.ltrim($rel, '/');
        }
    }

    $p = parse_url($full);
    if ($p === false) {
        $cache[$url] = null;
        return null;
    }

    $host = strtolower((string)($p['host'] ?? ''));
    $port = (string)($p['port'] ?? '');
    if ($host !== '' && $site_host !== '' && ($host !== $site_host || $port !== $site_port)) {
        $cache[$url] = array('external' => true, 'path' => '', 'query' => array());
        return $cache[$url];
    }

    $path = (string)($p['path'] ?? '/');
    if ($path === '') {
        $path = '/';
    }
    $path = rawurldecode($path);
    if (preg_match('#/index\.php$#i', $path)) {
        $path = substr($path, 0, -10);
        if ($path === '' || $path === false) {
            $path = '/';
        }
    }
    if ($path !== '/' && substr($path, -1) === '/') {
        $path = rtrim($path, '/');
    }
    $path = strtolower($path);

    $query = array();
    if (!empty($p['query'])) {
        parse_str($p['query'], $query);
    }

    $cache[$url] = array('external' => false, 'path' => $path, 'query' => $query);
    return $cache[$url];
}

/**
 * 현재 요청이 cf_perm_fallback_url 과 같은 내부 주소인지.
 * path 가 같고, 폴백에 있는 쿼리 키가 현재 요청에도 같은 값이면 true.
 */
function perm_is_fallback_request() {
    global $config;
    static $result = null;
    if ($result !== null) {
        return $result;
    }

    $fb = trim((string)($config['cf_perm_fallback_url'] ?? ''));
    if ($fb === '') {
        $result = false;
        return $result;
    }

    $fb_p = perm_normalize_request($fb);
    if (!$fb_p || !empty($fb_p['external'])) {
        $result = false;
        return $result;
    }

    $cur_uri = (string)($_SERVER['REQUEST_URI'] ?? '');
    if ($cur_uri === '') {
        $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        $qs     = (string)($_SERVER['QUERY_STRING'] ?? '');
        $cur_uri = $script.($qs !== '' ? '?'.$qs : '');
    }
    $cur = perm_normalize_request($cur_uri);
    if (!$cur || !empty($cur['external'])) {
        $result = false;
        return $result;
    }

    if ($fb_p['path'] !== $cur['path']) {
        $result = false;
        return $result;
    }

    foreach ($fb_p['query'] as $k => $v) {
        if (!array_key_exists($k, $cur['query'])) {
            $result = false;
            return $result;
        }
        $cv = $cur['query'][$k];
        if (is_array($v) || is_array($cv)) {
            if (serialize($v) !== serialize($cv)) {
                $result = false;
                return $result;
            }
        } elseif ((string)$cv !== (string)$v) {
            $result = false;
            return $result;
        }
    }

    $result = true;
    return $result;
}

function perm_warning_url() {
    return rtrim((string)G5_URL, '/').'/warning.php';
}

function perm_is_warning_request() {
    return perm_current_script_name() === 'warning.php';
}

/**
 * 사이트 전체 잠금 체크 (cf_open_scope='admin' 이면 비관리자/비로그인 차단).
 * - 비로그인이면 로그인 페이지로 이동
 * - 로그인했으나 비관리자면 perm_deny() (fallback URL 또는 /warning.php)
 * - 폴백 URL 이 내부 주소이면 그 페이지만 로그인한 비관리자에게 허용 (루프 방지)
 * - warning.php 는 인증 페이지와 같이 잠금 예외
 * common.php 끝에서 호출. 로그인/로그아웃 페이지는 자동 예외 처리.
 *
 * 폴백 URL 비교는 잠금 + 로그인 비관리자 + 폴백 있음 일 때만 수행.
 */
function perm_check_admin_only() {
    global $config, $is_admin, $is_member;
    $scope = (string)($config['cf_open_scope'] ?? 'all');
    if ($scope !== 'admin' || $is_admin) return;

    if (perm_is_guest_allowed_page()) return;

    if (!$is_member) {
        // 비로그인 → 로그인 페이지로 이동 (현재 URL 보존)
        $req = $_SERVER['REQUEST_URI'] ?? '';
        $back = G5_URL . $req;
        goto_url(G5_BBS_URL . '/login.php?url=' . urlencode($back));
    }

    $fb = trim((string)($config['cf_perm_fallback_url'] ?? ''));
    if ($fb !== '' && perm_is_fallback_request()) {
        return;
    }

    perm_deny('관리자만 접근 가능합니다.');
}
