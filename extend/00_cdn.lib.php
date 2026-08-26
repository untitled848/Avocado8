<?php
if (!defined('_GNUBOARD_')) exit;

/*******************************************************************************
 * CDN 이미지 분리 — 코어 라이브러리 (extend/00_cdn.lib.php)
 *
 * 역할:
 *  - DB에 저장된 [DATA_URL] 토큰을 실제 URL로 치환 (출력 시점)
 *  - 업로드 후 미러링 액션 발사 (코어는 로컬 저장만, 모듈이 R2로 미러)
 *  - 모듈 cdn_cloudflare 가 비활성이어도 안전하게 동작 (토큰을 G5_DATA_URL로 변환)
 *
 * 토큰 형식: [DATA_URL]/character/123/foo.jpg
 *
 * 외부링크는 자동으로 치환에서 제외됨:
 *   ^https?://     (http:// 또는 https://)
 *   ^//            (프로토콜 상대 URL)
 *   ^data:         (data URI)
 *
 * 모듈 훅 (모듈 cdn_cloudflare 가 활용):
 *   filter cdn_base_url       — 토큰을 치환할 베이스 URL (기본 G5_DATA_URL)
 *   filter cdn_resolve_url    — URL 1건 변환 (모듈 후처리)
 *   filter cdn_resolve_content — 본문 일괄 변환 후처리
 *   action cdn_after_upload   — 업로드 후 R2 미러링 트리거
 *   action cdn_after_delete   — 삭제 후 R2 정리 트리거
 *   filter cdn_download_url   — 게시판 첨부 다운로드 URL 결정
 *******************************************************************************/

if (!defined('CDN_TOKEN')) define('CDN_TOKEN', '[DATA_URL]');

/**
 * URL 또는 토큰값을 실제 출력용 URL로 치환.
 *
 *  - 빈 값 / 비문자열은 그대로 반환
 *  - 외부링크(http://, https://, //, data:)는 그대로 반환
 *  - [DATA_URL] 토큰이 있으면 cdn_base_url 필터의 베이스로 치환
 *  - 레거시 절대경로(예: G5_URL/data/...)는 토큰화 후 치환 — 양방향 호환
 */
function cdn_resolve_url($value)
{
    if (!is_string($value) || $value === '') return $value;

    // 외부링크 보호
    if (preg_match('#^(?:https?:)?//#i', $value)) {
        // G5_URL 로 시작하는 절대경로(=내부 절대경로)는 토큰화 대상으로 본다.
        $self = rtrim(G5_URL, '/');
        if ($self !== '' && stripos($value, $self . '/' . G5_DATA_DIR . '/') === 0) {
            // 절대경로 → 토큰으로 정규화 후 다시 치환
            $value = CDN_TOKEN . substr($value, strlen($self . '/' . G5_DATA_DIR));
        } else {
            // 진짜 외부링크
            return $value;
        }
    } else if (strpos($value, 'data:') === 0) {
        return $value;
    }

    // 토큰 치환
    $base = function_exists('mdl_apply_filters')
        ? mdl_apply_filters('cdn_base_url', G5_DATA_URL)
        : G5_DATA_URL;
    $base = rtrim($base, '/');

    if (strpos($value, CDN_TOKEN) !== false) {
        $value = str_replace(CDN_TOKEN, $base, $value);
    }

    // 모듈 후처리 (예: 캐시 버스터 ?v=)
    if (function_exists('mdl_apply_filters')) {
        $value = mdl_apply_filters('cdn_resolve_url', $value);
    }

    return $value;
}

/**
 * HTML/본문 일괄 치환 (에디터 콘텐츠용).
 *
 *  - 본문 내 모든 [DATA_URL] 토큰을 베이스 URL로 일괄 변환
 *  - 외부 <img src="http://...">, base64 등은 건드리지 않음
 *  - 레거시 절대경로(G5_URL/data/...) 도 베이스에 맞춰 치환 (CDN 모드 한정)
 */
function cdn_resolve_content($content)
{
    if (!is_string($content) || $content === '') return $content;

    $base = function_exists('mdl_apply_filters')
        ? mdl_apply_filters('cdn_base_url', G5_DATA_URL)
        : G5_DATA_URL;
    $base = rtrim($base, '/');

    // 1) 토큰 치환
    if (strpos($content, CDN_TOKEN) !== false) {
        $content = str_replace(CDN_TOKEN, $base, $content);
    }

    // 2) 레거시 절대경로 호환 — 베이스가 G5_DATA_URL 과 다를 때만 (=CDN 활성)
    $local_data_url = rtrim(G5_DATA_URL, '/');
    if ($base !== $local_data_url && $local_data_url !== '') {
        // src="<G5_DATA_URL>/..." 또는 url(<G5_DATA_URL>/...) 모두 변환
        $content = str_replace($local_data_url . '/', $base . '/', $content);
    }

    if (function_exists('mdl_apply_filters')) {
        $content = mdl_apply_filters('cdn_resolve_content', $content);
    }

    return $content;
}

/**
 * 저장용 URL 토큰화.
 *
 * 입력:
 *   - G5_DATA_URL/character/.../foo.jpg  → [DATA_URL]/character/.../foo.jpg
 *   - data/character/.../foo.jpg         → [DATA_URL]/character/.../foo.jpg (상대 시도)
 *   - http(s) 외부링크                   → 그대로
 *   - 이미 토큰 포함                     → 그대로
 *
 * CDN 이 꺼져 있으면 토큰화하지 않고 실제 경로를 그대로 저장한다.
 *   → CDN 미사용 환경에서 [DATA_URL] 토큰이 DB 에 남아 생기는 문제 방지.
 *   → 마이그레이션(활성화 준비)에서는 $force=true 로 강제 토큰화.
 *
 * 일관 사용 시(CDN 활성) DB에는 항상 [DATA_URL]/... 형태가 남도록 해 둔다.
 */
function cdn_tokenize_url($url, $force = false)
{
    if (!is_string($url) || $url === '') return $url;
    if (strpos($url, CDN_TOKEN) !== false) return $url;

    // CDN 비활성 시 토큰화하지 않음 (실제 경로 그대로 저장). 마이그레이션은 $force 로 예외.
    if (!$force && !(function_exists('cdn_is_enabled') && cdn_is_enabled())) return $url;

    // 외부링크는 그대로
    if (preg_match('#^(?:https?:)?//#i', $url)) {
        $self = rtrim(G5_URL, '/');
        if ($self !== '' && stripos($url, $self . '/' . G5_DATA_DIR . '/') === 0) {
            return CDN_TOKEN . substr($url, strlen($self . '/' . G5_DATA_DIR));
        }
        // 진짜 외부 URL
        return $url;
    }

    // 절대경로(/data/...) 케이스
    if (strpos($url, '/' . G5_DATA_DIR . '/') === 0) {
        return CDN_TOKEN . substr($url, strlen('/' . G5_DATA_DIR));
    }
    if (strpos($url, G5_DATA_DIR . '/') === 0) {
        return CDN_TOKEN . '/' . substr($url, strlen(G5_DATA_DIR . '/'));
    }

    // 그 외(이미 어딘가의 절대 경로 등)는 변환 안함
    return $url;
}

/**
 * 본문(HTML) 저장 토큰화.
 *
 * 본문 안의 G5_DATA_URL/... 또는 //host/data/... 절대경로를 모두 [DATA_URL]/... 로 정규화.
 * (외부 URL, base64 등은 그대로)
 *
 * CDN 비활성 시에는 토큰화하지 않는다 (실제 경로 유지). 마이그레이션은 $force=true.
 */
function cdn_tokenize_content($content, $force = false)
{
    if (!is_string($content) || $content === '') return $content;

    // CDN 비활성 시 토큰화하지 않음. 마이그레이션은 $force 로 예외.
    if (!$force && !(function_exists('cdn_is_enabled') && cdn_is_enabled())) return $content;

    // 1) 풀 URL (G5_URL/data/...)
    $self = rtrim(G5_URL, '/');
    if ($self !== '') {
        $needle = $self . '/' . G5_DATA_DIR . '/';
        $content = str_ireplace($needle, CDN_TOKEN . '/', $content);
    }

    // 2) 호스트 없는 G5_DATA_URL (이미 같으면 1) 에 흡수됨)
    $local_data_url = rtrim(G5_DATA_URL, '/');
    if ($local_data_url !== '' && $local_data_url !== $self . '/' . G5_DATA_DIR) {
        $content = str_replace($local_data_url . '/', CDN_TOKEN . '/', $content);
    }

    return $content;
}

/**
 * 토큰 → 실제 로컬 경로로 되돌리기 (저장값 역토큰화).
 *   [DATA_URL]/character/.../foo.jpg → G5_DATA_URL/character/.../foo.jpg
 * CDN 제거/초기화 시 DB 에서 토큰을 걷어내는 용도.
 */
function cdn_detokenize($value)
{
    if (!is_string($value) || $value === '') return $value;
    if (strpos($value, CDN_TOKEN) === false) return $value;
    return str_replace(CDN_TOKEN, rtrim(G5_DATA_URL, '/'), $value);
}

/**
 * 마이그레이션 화이트리스트.
 *   [ table_key(g5[...]), pk(string|string[]), column, type('url'|'content') ]
 *
 * ※ 아이템(g5_item)은 CDN 대상에서 제외 — 서버 부담이 작고 토큰화하지 않음.
 */
function cdn_migration_targets()
{
    $t = array(
        array('character_table',    'ch_id',  'ch_thumb',          'url'),
        array('character_table',    'ch_id',  'ch_head',           'content'),
        array('character_table',    'ch_id',  'ch_body',           'content'),
        array('value_table',        'av_id',  'av_value',          'content'),
        array('content_table',      'co_id',  'co_content',        'content'),
        array('content_table',      'co_id',  'co_mobile_content', 'content'),
        array('world_table',        'wo_id',  'wo_content',        'content'),
        array('world_table',        'wo_id',  'wo_mobile_content', 'content'),
        array('notice_table',       'no_id',  'no_content',        'content'),
        array('time_value_table',   'ctv_id', 'ctv_thumb',         'url'),
        array('time_value_table',   'ctv_id', 'ctv_head',          'content'),
        array('time_value_table',   'ctv_id', 'ctv_body',          'content'),
        array('time_article_table', 'cta_id', 'cta_value',         'content'),
        // pers_blog (모듈 테이블 — g5 키는 cdn_run_migration 에서 보장)
        array('blog_table',         'bl_id',                'bl_thumb', 'url'),
        array('blog_file_table',    array('bl_id','bf_no'), 'bf_file',  'url'),
    );
    // 디자인 프리셋 (cs_value + cs_etc_1..10) — 3개 설정 테이블, URL 취급
    $css_tables = array('css_table', 'world_config_table', 'content_design_config_table');
    foreach ($css_tables as $ct) {
        $t[] = array($ct, 'cs_id', 'cs_value', 'url');
        for ($i = 1; $i <= 10; $i++) {
            $t[] = array($ct, 'cs_id', 'cs_etc_'.$i, 'url');
        }
    }
    return $t;
}

/**
 * 컬럼 1개 일괄 변환.
 *   $pk: 단일 PK 컬럼명 또는 복합 PK 배열 (예: array('bl_id','bf_no'))
 *   $mode: 'tokenize'  — 실제경로 → [DATA_URL] (활성화 준비, $force 토큰화)
 *          'detokenize'— [DATA_URL] → 실제경로  (CDN 제거/초기화)
 * 반환: array(total, changed, skipped, error)
 */
function cdn_migrate_column($table_key, $pk, $col, $type, $mode, $apply)
{
    global $g5;
    if (empty($g5[$table_key])) return array(0, 0, 0, 'table missing');
    $tbl = $g5[$table_key];
    $total = 0; $changed = 0; $skipped = 0;

    $pk_cols = is_array($pk) ? $pk : array($pk);
    $pk_select = '';
    foreach ($pk_cols as $c) {
        $pk_select .= '`'.$c.'`, ';
    }

    if ($mode === 'detokenize') {
        // MySQL LIKE 에서 '['·']' 는 일반 문자 (와일드카드는 % _ 뿐)
        $sql = "SELECT {$pk_select}`{$col}` FROM `{$tbl}`
                WHERE `{$col}` LIKE '%".CDN_TOKEN."%'";
    } else {
        $like_self = sql_real_escape_string(rtrim(G5_URL, '/'));
        $sql = "SELECT {$pk_select}`{$col}` FROM `{$tbl}`
                WHERE `{$col}` LIKE '%{$like_self}%/".G5_DATA_DIR."/%'
                  AND `{$col}` NOT LIKE '%".CDN_TOKEN."%'";
    }
    $res = sql_query($sql, false);
    if (!$res) return array(0, 0, 0, 'query failed');

    while ($row = sql_fetch_array($res)) {
        $total++;
        $val = (string)$row[$col];
        if ($mode === 'detokenize') {
            $new = cdn_detokenize($val);
        } else {
            $new = ($type === 'content')
                ? cdn_tokenize_content($val, true)
                : cdn_tokenize_url($val, true);
        }
        if ($new === $val) { $skipped++; continue; }
        $changed++;
        if ($apply) {
            $new_e = sql_real_escape_string($new);
            $where = array();
            foreach ($pk_cols as $c) {
                $where[] = '`'.$c."` = '".sql_real_escape_string((string)$row[$c])."'";
            }
            sql_query("UPDATE `{$tbl}` SET `{$col}` = '{$new_e}' WHERE ".implode(' AND ', $where));
        }
    }
    return array($total, $changed, $skipped, '');
}

/**
 * 화이트리스트 전체 일괄 변환.
 *   $mode: 'tokenize' | 'detokenize'
 * 반환: array( [table_name, column, array(total,changed,skipped,err)], ... )
 */
function cdn_run_migration($mode, $apply)
{
    global $g5;
    // pers_blog 테이블 키가 아직 없으면 보정 (모듈 비활성이어도 마이그레이션 가능)
    if (empty($g5['blog_table']))      $g5['blog_table']      = G5_TABLE_PREFIX.'blog';
    if (empty($g5['blog_file_table'])) $g5['blog_file_table'] = G5_TABLE_PREFIX.'blog_file';

    $report = array();
    foreach (cdn_migration_targets() as $tg) {
        list($tk, $pk, $col, $type) = $tg;
        $report[] = array(
            isset($g5[$tk]) ? $g5[$tk] : $tk,
            $col,
            cdn_migrate_column($tk, $pk, $col, $type, $mode, $apply),
        );
    }
    return $report;
}

/**
 * 절대 로컬 파일 경로 → R2 키 (data 디렉토리 기준 상대 경로).
 *
 * 예: G5_DATA_PATH/character/123/foo.jpg  →  character/123/foo.jpg
 *     실패(=data 디렉토리 외부) 시 null
 */
function cdn_local_path_to_key($local_path)
{
    if (!is_string($local_path) || $local_path === '') return null;

    $real = realpath($local_path);
    if (!$real) $real = $local_path;
    $real = str_replace('\\', '/', $real);

    // /data/ 하위
    $base = realpath(G5_DATA_PATH);
    if (!$base) $base = G5_DATA_PATH;
    $base = rtrim(str_replace('\\', '/', $base), '/') . '/';
    if (strpos($real, $base) === 0) return substr($real, strlen($base));

    // /img/ 하위 — R2 키는 '_img/<...>' 로 저장 (data/ 와 충돌 방지)
    if (defined('G5_IMG_PATH')) {
        $ibase = realpath(G5_IMG_PATH);
        if (!$ibase) $ibase = G5_IMG_PATH;
        $ibase = rtrim(str_replace('\\', '/', $ibase), '/') . '/';
        if (strpos($real, $ibase) === 0) return '_img/' . substr($real, strlen($ibase));
    }

    return null;
}

/**
 * 업로드 직후 호출하는 게이트.
 *
 * 코어는 항상 로컬 저장이 끝난 뒤 이 함수를 부른다.
 * 모듈 cdn_cloudflare 가 cdn_after_upload 액션을 받아 R2 업로드를 수행.
 * 모듈이 없으면 아무 일도 하지 않으므로 안전.
 *
 * 사용:
 *   cdn_after_upload(G5_DATA_PATH.'/character/'.$mb_id.'/'.$filename);
 */
function cdn_after_upload($local_path)
{
    if (!function_exists('mdl_do_action')) return;
    if (!$local_path || !is_file($local_path)) return;
    $key = cdn_local_path_to_key($local_path);
    if ($key === null) return;
    mdl_do_action('cdn_after_upload', $local_path, $key);
}

/**
 * 파일 삭제 직후 호출하는 게이트.
 */
function cdn_after_delete($local_path_or_key)
{
    if (!function_exists('mdl_do_action')) return;
    if (!is_string($local_path_or_key) || $local_path_or_key === '') return;
    $key = $local_path_or_key;
    if (strpos($key, '/') !== false || strpos($key, '\\') !== false) {
        // 경로로 보이면 키로 변환
        $maybe = cdn_local_path_to_key($local_path_or_key);
        if ($maybe !== null) $key = $maybe;
    }
    mdl_do_action('cdn_after_delete', $key);
}

/**
 * CDN 모드 활성 여부.
 *   1) module_type=cdn 어댑터가 활성
 *   2) cdn_base_url 필터가 G5_DATA_URL 이 아닌 베이스를 반환 (use_cdn=1 + cdn_url)
 * 모듈이 꺼져 있거나 설정만 끄면 false → 저장 시 토큰화하지 않음.
 */
function cdn_is_enabled()
{
    if (!function_exists('mdl_apply_filters')) return false;

    $adapter_on = false;
    if (!empty($GLOBALS['mdl_active']) && is_array($GLOBALS['mdl_active'])) {
        foreach ($GLOBALS['mdl_active'] as $meta) {
            if (!empty($meta['module_type']) && $meta['module_type'] === 'cdn') {
                $adapter_on = true;
                break;
            }
        }
    }
    if (!$adapter_on) return false;

    $base = mdl_apply_filters('cdn_base_url', G5_DATA_URL);
    return rtrim((string)$base, '/') !== rtrim((string)G5_DATA_URL, '/');
}

/**
 * 로컬 파일 삭제 + CDN 정리(원격 R2/CDN의 같은 키 삭제).
 *
 * 어드민 업로드 폼/리스트의 unlink 호출을 이 함수로 교체하면
 * 모듈 cdn_cloudflare 가 활성화된 경우 R2의 동일 키도 같이 삭제된다.
 * 모듈 비활성/CDN 미사용 환경에서는 일반 unlink와 동일 동작.
 *
 * @param string $local_path 절대 경로(보통 G5_DATA_PATH 하위)
 * @return bool 로컬 파일 삭제 성공 여부
 */
function cdn_unlink($local_path)
{
    if (!is_string($local_path) || $local_path === '') return false;
    // CDN 정리는 unlink 전에 키 추출이 가능해야 하므로 먼저 경로->키 변환
    $key = function_exists('cdn_local_path_to_key') ? cdn_local_path_to_key($local_path) : null;
    $ok = false;
    if (file_exists($local_path)) $ok = @unlink($local_path);
    if ($key !== null && function_exists('cdn_after_delete')) {
        cdn_after_delete($key);
    }
    return $ok;
}

/**
 * /img/ 경로의 이미지 URL 해결자.
 *
 * CDN 활성 시에는 https://r2.example.com/_img/<file>으로, 아니면 G5_IMG_URL/<file>로 반환.
 * 테플릿에서 자주 쓰는 이미지는 G5_IMG_URL 대신 cdn_img() 로 교체 시 CDN 서빙 가능.
 *
 * @param string $file '/btn_top.png' 또는 'btn_top.png'
 * @return string
 */
if (!function_exists('cdn_img')) {
function cdn_img($file = '')
{
    $file = ltrim((string)$file, '/');
    if (function_exists('cdn_is_enabled') && cdn_is_enabled()) {
        $base = function_exists('mdl_apply_filters') ? mdl_apply_filters('cdn_base_url', G5_DATA_URL) : G5_DATA_URL;
        $base = rtrim($base, '/');
        $url  = $base . '/_img' . ($file !== '' ? '/' . $file : '');
        if (function_exists('mdl_apply_filters')) $url = mdl_apply_filters('cdn_resolve_url', $url);
        return $url;
    }
    return rtrim(G5_IMG_URL, '/') . ($file !== '' ? '/' . $file : '');
}
}

/**
 * /data/ 하위 경로의 URL 해결자 (게시판 첨부, 썸네일, 캐릭터 이미지 등).
 *
 *   $rel = 'file/free/abc.png'  (G5_DATA_DIR 기준 상대 경로, 선두 슬래시 무관)
 *
 * CDN 활성 시 cdn_base/<rel> 을 cdn_resolve_url 로 한 번 더 거쳐 반환
 *   (인덱스에 없으면 자동 로컬 fallback).
 * CDN 비활성 시 G5_DATA_URL/<rel> 그대로.
 */
if (!function_exists('cdn_data_url')) {
function cdn_data_url($rel)
{
    $rel = ltrim((string)$rel, '/');
    if ($rel === '') return rtrim(G5_DATA_URL, '/');
    if (function_exists('cdn_is_enabled') && cdn_is_enabled()) {
        $base = function_exists('mdl_apply_filters') ? mdl_apply_filters('cdn_base_url', G5_DATA_URL) : G5_DATA_URL;
        $base = rtrim($base, '/');
        $url  = $base . '/' . $rel;
        if (function_exists('mdl_apply_filters')) $url = mdl_apply_filters('cdn_resolve_url', $url);
        return $url;
    }
    return rtrim(G5_DATA_URL, '/') . '/' . $rel;
}
}

/**
 * CSS URL 해결자.
 *
 * CSS 내부의 url('../img/x.png') 같은 상대경로는 PHP 가 손대지 못하므로
 * CDN 활성 시에는 cdn_css.php 프록시를 통해 url() 를 절대/CDN 경로로 재작성한 결과를 송출.
 * CDN 비활성 시에는 원본 URL 을 그대로 반환.
 *
 * 사용:
 *   <link rel="stylesheet" href="<?php echo cdn_css(G5_CSS_URL.'/style.css'); ?>">
 *   <link rel="stylesheet" href="<?php echo cdn_css('/css/style.css'); ?>">  // 절대 경로도 OK
 *
 * @param string $css_url G5_URL 기준 CSS URL 또는 '/'로 시작하는 사이트 절대 경로.
 * @return string 그대로 또는 cdn_css.php?f=...&v=mtime URL.
 */
if (!function_exists('cdn_css')) {
function cdn_css($css_url)
{
    $css_url = (string)$css_url;
    if ($css_url === '') return '';

    // CDN 비활성이면 원본 그대로
    if (!function_exists('cdn_is_enabled') || !cdn_is_enabled()) return $css_url;

    // CSS 재작성 별도 비활성화 시 원본 그대로
    if (function_exists('mdl_apply_filters')) {
        if (!mdl_apply_filters('cdn_css_rewrite_enabled', true)) return $css_url;
    }

    // G5_URL prefix 제거 → 사이트 절대 경로
    $g5 = rtrim(G5_URL, '/');
    if (strpos($css_url, $g5) === 0) {
        $rel = substr($css_url, strlen($g5));
    } elseif ($css_url[0] === '/') {
        $rel = $css_url;
    } else {
        // 외부 또는 상대 — 그대로
        return $css_url;
    }
    $rel = ltrim($rel, '/');

    // 로컬 파일 mtime → 캐시 버스터
    $abs = G5_PATH . '/' . $rel;
    $v   = is_file($abs) ? filemtime($abs) : 0;

    // CDN 활성 시: cdn_url 해시를 v 에 합침 → 토글/URL 변경 시 자동 재다운로드
    if (function_exists('mdl_apply_filters')) {
        $base = (string)mdl_apply_filters('cdn_base_url', '');
        if ($base !== '') $v .= '_' . substr(md5($base), 0, 6);
    }

    return $g5 . '/cdn_css.php?f=' . rawurlencode($rel) . ($v ? '&v=' . $v : '');
}
}
