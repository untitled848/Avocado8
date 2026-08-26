<?php
/**
 * cdn_css.php — CSS 내부 url() 를 CDN 절대경로로 재작성하여 송출하는 프록시.
 *
 * CSS 파일은 정적이라 PHP 가 손대지 못하고, CDN 활성 시 url('../img/x.png') 같은
 * 상대경로는 R2 가 아닌 원본 서버에서 받아옴. 이 스크립트가 CSS 본문을 읽어
 *   - 모든 url(...) 의 상대/절대 경로를 절대 경로로 정규화
 *   - /img/ 경로는 cdn_img() 로 치환 (CDN 활성 시 R2 URL)
 *   - 그 외 G5 영역 경로(/css/, /skin/, /theme/, /data/) 는 G5_URL prefix
 *   - 외부 URL(http(s):, //, data:) 은 손대지 않음
 * 후 text/css 로 응답. ETag + 장시간 Cache-Control 동봉.
 *
 * 사용:
 *   <link rel="stylesheet" href="<?php echo cdn_css(G5_CSS_URL.'/style.css'); ?>">
 *
 * cdn_css() 헬퍼 (extend/00_cdn.lib.php) 가 CDN 비활성 시에는 원본 URL 그대로 반환.
 */

include_once(__DIR__.'/common.php');

// ── 입력 ─────────────────────────────────────────
$f = isset($_GET['f']) ? (string)$_GET['f'] : '';
if ($f === '') { http_response_code(400); exit('missing f'); }

// 보안: 워크스페이스(G5_PATH) 하위만 허용. ../ 차단.
$f = ltrim($f, '/');
if (strpos($f, '..') !== false) { http_response_code(400); exit('bad path'); }

$abs = realpath(G5_PATH . '/' . $f);
if (!$abs || strpos($abs, realpath(G5_PATH)) !== 0) { http_response_code(404); exit('not found'); }
if (substr($abs, -4) !== '.css') { http_response_code(415); exit('not css'); }
if (!is_file($abs)) { http_response_code(404); exit('not file'); }

// CSS 가 위치한 디렉터리의 웹 URL (상대경로 기준점)
$css_dir_abs = dirname($abs);
$css_dir_rel = substr($css_dir_abs, strlen(realpath(G5_PATH)));
$css_dir_rel = str_replace('\\', '/', $css_dir_rel);
$css_dir_url = G5_URL . $css_dir_rel; // ex) https://site/css

$mtime = filemtime($abs);

// CDN 토글/CDN URL 도 ETag 에 포함 → 토글 변경 시 캐시 무효화
$cdn_state = (function_exists('cdn_is_enabled') && cdn_is_enabled()) ? '1' : '0';
$cdn_base  = function_exists('mdl_apply_filters') ? (string)mdl_apply_filters('cdn_base_url', '') : '';
$etag = '"' . md5($f . '|' . $mtime . '|' . $cdn_state . '|' . $cdn_base) . '"';

// 304 처리
$inm = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
if ($inm === $etag) { http_response_code(304); exit; }

// ── 디스크 캐시 (재작성 결과) ──────────────────────
//   동일 키(파일+mtime+CDN상태+CDN base) 면 정규식 스킵.
$cache_key = trim($etag, '"');
$cache_dir = G5_DATA_PATH . '/cache/cdn_css';
$cache_file = $cache_dir . '/' . $cache_key . '.css';
if (is_file($cache_file)) {
    header('Content-Type: text/css; charset=utf-8');
    header('ETag: ' . $etag);
    header('Cache-Control: public, max-age=86400');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    readfile($cache_file);
    exit;
}

$body = file_get_contents($abs);
if ($body === false) { http_response_code(500); exit('read fail'); }

if (!function_exists('cdn_css_normalize_url')) {
    function cdn_css_normalize_url($url) {
        // 스킴+호스트 보존, 경로만 정리
        if (!preg_match('#^([a-z]+:)?//([^/]+)(/.*)$#i', $url, $m)) return $url;
        [$all, $scheme, $host, $path] = $m;
        $parts = [];
        foreach (explode('/', $path) as $seg) {
            if ($seg === '' || $seg === '.') continue;
            if ($seg === '..') { array_pop($parts); continue; }
            $parts[] = $seg;
        }
        return $scheme . '//' . $host . '/' . implode('/', $parts);
    }
}

// ── url(...) 재작성 ─────────────────────────────────
//   url(  ' "  PATH  ' "  )  — 따옴표 유무 모두 처리
$body = preg_replace_callback(
    '/url\(\s*([\'"]?)([^\'")]+)\1\s*\)/i',
    function ($m) use ($css_dir_url) {
        $orig = trim($m[2]);
        if ($orig === '') return $m[0];
        // 외부/특수 스킴은 그대로
        if (preg_match('#^(?:https?:|//|data:|about:|blob:)#i', $orig)) return $m[0];
        // 프래그먼트 분리 (SVG #symbol 등)
        $hash = '';
        if (($p = strpos($orig, '#')) !== false) { $hash = substr($orig, $p); $orig = substr($orig, 0, $p); }
        // 절대 경로
        if ($orig !== '' && $orig[0] === '/') {
            $abs_url = G5_URL . $orig;
        } else {
            // 상대 경로: CSS 디렉터리 기준 결합
            $abs_url = rtrim($css_dir_url, '/') . '/' . $orig;
        }
        // /./ /../ 정리
        $abs_url = cdn_css_normalize_url($abs_url);

        // /img/ 경로면 cdn_img() 로 (CDN 활성 시 R2)
        $g5_url   = rtrim(G5_URL, '/');
        $img_pref = $g5_url . '/' . trim(G5_IMG_DIR, '/') . '/';
        if (function_exists('cdn_img') && strpos($abs_url, $img_pref) === 0) {
            $rel = substr($abs_url, strlen($img_pref));
            $abs_url = cdn_img($rel);
        }
        return 'url("' . $abs_url . $hash . '")';
    },
    $body
);

// ── 응답 ─────────────────────────────────────────
// 캐시 저장 (실패해도 본문은 송출)
if (!is_dir($cache_dir)) @mkdir($cache_dir, 0755, true);
@file_put_contents($cache_file, $body, LOCK_EX);

header('Content-Type: text/css; charset=utf-8');
header('ETag: ' . $etag);
header('Cache-Control: public, max-age=86400');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
echo $body;
