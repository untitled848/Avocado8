<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * Theme 시스템 v2 — 프리셋(preset) + 슬롯(slot) 기반 선택적 오버라이드
 *
 * 데이터 모델:
 *   {prefix}theme_preset       (pr_id, pr_name, pr_design, pr_order)
 *   {prefix}theme_preset_slot  (ps_id, pr_id, ps_slot, ps_theme)
 *   {prefix}config.cf_preset_id              — 글로벌 기본 프리셋
 *   {prefix}character_time.ti_preset_id      — 시기별 프리셋 매핑
 *
 * 우선순위:
 *   현재 시기 ti_preset_id > config.cf_preset_id > pr_id=1 (default)
 *   관리자 한정 GET ?preset=N 미리보기 가능
 *
 * 슬롯 화이트리스트 ($GLOBALS['THEME_SLOT_MAP']):
 *   각 항목 ['type' => 'file'|'dir'|'skin', 'path' => '<상대경로>', 'label' => '<관리자 표시명>']
 *   file: 디스패처가 SCRIPT_FILENAME과 정확 일치 시 매칭
 *   dir : SCRIPT_FILENAME이 path/ 로 시작하면 매칭 (디렉토리 하위 전체)
 *   skin: 디스패처는 무시. theme_resolve_board_skin()/theme_skin_dir()로 런타임 해석
 *
 * 신규 슬롯 추가 = $GLOBALS['THEME_SLOT_MAP']에 항목 추가만 하면 됨
 *   (관리자 프리셋 폼은 이 배열을 자동 순회하여 슬롯 행을 노출)
 */

// =============================================================================
// 테이블명은 install/install_db.php 에서 data/dbconfig.php 에 일괄 등록:
//   $g5['theme_preset_table'], $g5['theme_preset_slot_table'], $g5['design_preset_table']
// =============================================================================

// =============================================================================
// 슬롯 화이트리스트
// =============================================================================
$GLOBALS['THEME_SLOT_MAP'] = array(
    // 코어 페이지 — 사이트 골격(<head>/푸터). 4개는 항상 같은 테마로 묶임.
    'head'     => array('type' => 'file', 'path' => 'head.php',     'label' => '헤더',     'group' => 'frame'),
    'tail'     => array('type' => 'file', 'path' => 'tail.php',     'label' => '테일',     'group' => 'frame'),
    'head_sub' => array('type' => 'file', 'path' => 'head.sub.php', 'label' => '헤더 sub', 'group' => 'frame'),
    'tail_sub' => array('type' => 'file', 'path' => 'tail.sub.php', 'label' => '테일 sub', 'group' => 'frame'),
    'menu'     => array('type' => 'file', 'path' => 'menu.php',     'label' => '메뉴'),
    'index'    => array('type' => 'file', 'path' => 'index.php',    'label' => '인덱스'),
    'intro'    => array('type' => 'file', 'path' => 'intro.php',    'label' => '인트로'),
    'main'     => array('type' => 'file', 'path' => 'main.php',     'label' => '메인'),
    'enter'    => array('type' => 'file', 'path' => 'enter.php',    'label' => '엔터 페이지'),
    'warning'  => array('type' => 'file', 'path' => 'warning.php',  'label' => '접근 거부 안내'),
    'bbs_login'=> array('type' => 'file', 'path' => 'bbs/login.php','label' => '로그인'),

    // 영역 디렉토리
    'mypage'   => array('type' => 'dir',  'path' => 'mypage',    'label' => '마이페이지'),
    'member'   => array('type' => 'dir',  'path' => 'member',    'label' => '멤버란'),
    'shop'     => array('type' => 'dir',  'path' => 'shop',      'label' => '상점'),
    'inventory'=> array('type' => 'dir',  'path' => 'inventory', 'label' => '인벤토리'),
    'world'    => array('type' => 'dir',  'path' => 'world',     'label' => '세계관'),

    // 스킨
    'skin_board_mmb'       => array('type' => 'skin', 'path' => 'skin/board/mmb',       'label' => 'MMB 게시판 스킨'),
    'skin_board_basic'     => array('type' => 'skin', 'path' => 'skin/board/basic',     'label' => '기본 게시판 스킨'),
    'skin_board_qna'       => array('type' => 'skin', 'path' => 'skin/board/qna',       'label' => 'QnA 게시판 스킨'),
    'skin_board_calculate' => array('type' => 'skin', 'path' => 'skin/board/calculate', 'label' => '정산 게시판 스킨'),
    'skin_outlogin_basic'  => array('type' => 'skin', 'path' => 'skin/outlogin/basic',  'label' => 'outlogin 기본 스킨'),
    'skin_world_basic'     => array('type' => 'skin', 'path' => 'skin/world/basic',     'label' => '세계관 기본 스킨'),
    'skin_content_basic'   => array('type' => 'skin', 'path' => 'skin/content/basic',   'label' => '문서(내용관리) 기본 스킨'),
    'skin_timeline_basic'  => array('type' => 'skin', 'path' => 'skin/timeline/basic',  'label' => '타임라인 기본 스킨'),
);

// 슬롯 그룹 정의 — 폼에서 묶어서 노출
$GLOBALS['THEME_SLOT_GROUPS'] = array(
    'frame' => array(
        'label' => '기본 세팅 (헤더·푸터 묶음)',
        'desc'  => '사이트 모든 페이지의 골격을 담당합니다. 헤더·헤더 sub·테일·테일 sub 4개는 항상 같은 테마를 사용해야 깨지지 않습니다.',
    ),
);

// =============================================================================
// 디자인 프리셋 타입 레지스트리
// theme_preset 테이블의 pr_{type}_design 컬럼 ↔ 디자인 프리셋 테이블 매핑
// =============================================================================
$GLOBALS['DESIGN_PRESET_TYPES'] = array(
    'main' => array(
        'column'     => 'pr_design',
        'table_key'  => 'design_preset_table',
        'label'      => '색상·디자인 (일반)',
        'edit_url'   => './design_form.php',
        'list_func'  => 'design_preset_list',
        'allow_none' => true,
        'desc'       => '"페이지 디자인 설정 → 사이트"에서 만든 색상/폰트/버튼 묶음 중 하나를 고릅니다.',
    ),
    'world' => array(
        'column'     => 'pr_world_design',
        'table_key'  => 'world_preset_table',
        'label'      => '세계관 디자인',
        'edit_url'   => './world_design_form.php',
        'list_func'  => '',
        'allow_none' => false,
        'skin_gubun' => 'world',
        'desc'       => '"페이지 디자인 설정 → 세계관"에서 만든 메뉴/배경 묶음, 또는 세계관 스킨을 고릅니다.',
    ),
    'content' => array(
        'column'     => 'pr_content_design',
        'table_key'  => 'content_preset_table',
        'label'      => '문서 디자인',
        'edit_url'   => './content_design_form.php',
        'list_func'  => '',
        'allow_none' => false,
        'skin_gubun' => 'content',
        'desc'       => '"페이지 디자인 설정 → 문서"에서 만든 묶음, 또는 문서 스킨을 고릅니다.',
    ),
);

// 스키마(theme_preset / theme_preset_slot / design_preset / 관련 컬럼)는
// install/gnuboard5.sql 에서 일괄 생성됩니다 (런타임 마이그레이션 없음).

// =============================================================================
// 헬퍼 함수
// =============================================================================

/**
 * 활성 프리셋 ID
 * 우선순위: 관리자 ?preset= → 시기 ti_preset_id → cf_preset_id → 1
 */
function get_active_preset_id()
{
    global $config, $g5, $is_admin;
    static $cache = null;
    if ($cache !== null) return $cache;

    // 관리자 미리보기
    if (!empty($is_admin)) {
        $req = ses($_GET, 'preset', 0, 'int');
        if ($req > 0) {
            $row = sql_fetch("SELECT pr_id FROM `{$g5['theme_preset_table']}` WHERE pr_id = '{$req}'", false);
            if (!empty($row['pr_id'])) {
                $cache = (int)$row['pr_id'];
                return $cache;
            }
        }
    }

    // 시기 ti_preset_id
    if (function_exists('get_current_time') && function_exists('get_time_by_order')) {
        $time = (int)get_current_time();
        if ($time > 0) {
            $info = get_time_by_order($time);
            if (!empty($info['ti_preset_id'])) {
                $pr_id = (int)$info['ti_preset_id'];
                // 존재 검증
                $row = sql_fetch("SELECT pr_id FROM `{$g5['theme_preset_table']}` WHERE pr_id = '{$pr_id}'", false);
                if (!empty($row['pr_id'])) {
                    $cache = $pr_id;
                    return $cache;
                }
            }
        }
    }

    // 글로벌 cf_preset_id
    $cf_pid = isset($config['cf_preset_id']) ? (int)$config['cf_preset_id'] : 0;
    if ($cf_pid > 0) {
        $row = sql_fetch("SELECT pr_id FROM `{$g5['theme_preset_table']}` WHERE pr_id = '{$cf_pid}'", false);
        if (!empty($row['pr_id'])) {
            $cache = $cf_pid;
            return $cache;
        }
    }

    // 폴백: pr_id=1
    $cache = 1;
    return $cache;
}

/**
 * 프리셋 정보 + 슬롯 맵 캐시 반환
 * @return array ['pr_id'=>..,'pr_name'=>..,'pr_design'=>..,'slots'=>['head'=>'basic',...]]
 */
function get_preset($pr_id = 0)
{
    global $g5;
    static $cache = array();
    $pr_id = (int)$pr_id;
    if ($pr_id <= 0) $pr_id = get_active_preset_id();
    if (isset($cache[$pr_id])) return $cache[$pr_id];

    $row = sql_fetch("SELECT * FROM `{$g5['theme_preset_table']}` WHERE pr_id = '{$pr_id}'", false);
    if (empty($row['pr_id'])) {
        $fallback = array('pr_id' => 0, 'pr_name' => 'default', 'slots' => array());
        if (!empty($GLOBALS['DESIGN_PRESET_TYPES'])) {
            foreach ($GLOBALS['DESIGN_PRESET_TYPES'] as $_dp_def) {
                $fallback[$_dp_def['column']] = '1';
            }
        } else {
            $fallback['pr_design'] = '1';
        }
        $cache[$pr_id] = $fallback;
        return $cache[$pr_id];
    }

    $slots = array();
    $res = sql_query("SELECT ps_slot, ps_theme FROM `{$g5['theme_preset_slot_table']}` WHERE pr_id = '{$pr_id}'", false);
    if ($res) {
        while ($r = sql_fetch_array($res)) {
            $slots[$r['ps_slot']] = (string)$r['ps_theme'];
        }
    }
    $row['slots'] = $slots;
    $cache[$pr_id] = $row;
    return $cache[$pr_id];
}

/**
 * 슬롯의 활성 테마명 ('default' 또는 테마명)
 */
function get_slot_theme($slot, $pr_id = 0)
{
    $preset = get_preset($pr_id);
    if (isset($preset['slots'][$slot])) {
        $t = (string)$preset['slots'][$slot];
        if ($t !== '' && is_valid_theme($t)) return $t;
    }
    return 'default';
}

/**
 * 활성 디자인 프리셋 ID (타입별)
 * @param string $type DESIGN_PRESET_TYPES 키 ('main', 'world', ...)
 * @return mixed 'none' (디자인 미사용, allow_none에서만) 또는 INT pd_id
 *
 * 우선순위: 시기 테마 > 기본 테마 > 1
 *
 * 주의: 값이 'skin:DIRNAME' 형식이면 (= 디자인 프리셋 대신 스킨 사용)
 *      여기서는 1 (= 스킨 사용 시 CSS 변수는 default 프리셋 값으로) 을 반환합니다.
 *      스킨/프리셋 구분이 필요한 경우 get_active_design_resolved($type) 를 사용하세요.
 */
function get_active_design($type = 'main')
{
    $reg = isset($GLOBALS['DESIGN_PRESET_TYPES']) ? $GLOBALS['DESIGN_PRESET_TYPES'] : array();
    if (!isset($reg[$type])) {
        // 하위 호환·폴백
        if ($type !== 'main' && isset($reg['main'])) $type = 'main';
        else return 1;
    }
    $col        = $reg[$type]['column'];
    $allow_none = !empty($reg[$type]['allow_none']);

    $preset = get_preset();
    $d = isset($preset[$col]) ? trim((string)$preset[$col]) : '';
    if ($allow_none && $d === 'none') return 'none';
    if ($d === '' || $d === 'default' || $d === 'none') return 1;
    // 'skin:...' 형식: 디자인 변수는 기본(=1) 사용
    if (strpos($d, 'skin:') === 0) return 1;
    $pd = (int)$d;
    if ($pd <= 0) return 1;
    return $pd;
}

/**
 * 테마 CSS link 태그 (자동 폴백)
 *
 * 우선순위:
 *   1) 지정 슬롯의 활성 테마 디렉터리(theme/{slot_theme}/css/{name})
 *   2) cf_theme 디렉터리(G5_THEME_PATH/css/{name})
 *   3) 글로벌 /css/{name}
 *
 * @param string $name CSS 파일명 (예: 'style.css', 'default.css')
 * @param string $slot 기준 슬롯 (기본 'head_sub' — head.sub.php에서 호출 시 자기 자신 기준)
 * @return string <link> 태그
 */
function theme_css_link_tag($name, $slot = 'head_sub')
{
    $name = ltrim((string)$name, '/');
    if ($name === '') return '';

    $url = '';

    // 1) 슬롯 테마 디렉터리 확인
    if (function_exists('get_slot_theme')) {
        $theme = get_slot_theme($slot);
        if ($theme !== '' && $theme !== 'default') {
            $tb = theme_resolve_base($theme);
            if ($tb['abs'] !== '') {
                $abs = $tb['abs'].'/'.G5_CSS_DIR.'/'.$name;
                if (is_file($abs)) $url = $tb['url'].'/'.G5_CSS_DIR.'/'.$name;
            }
        }
    }

    // 2) 글로벌 폴백
    if ($url === '') {
        $url = G5_URL.'/'.G5_CSS_DIR.'/'.$name;
    }

    if (function_exists('cdn_css')) $url = cdn_css($url);
    return '<link rel="stylesheet" href="'.$url.'" type="text/css">';
}

/**
 * 테마 자산(임의 경로) link/URL 헬퍼.
 * 테마 디렉터리 루트 기준 상대경로를 받아, 활성 슬롯 테마에 파일이 있으면 해당 URL,
 * 없으면 G5_URL 기준 같은 경로(글로벌)로 폴백.
 *
 * @param string $rel  테마 루트 기준 상대경로 (예: 'css/preset.css', 'style.css', 'img/logo.png')
 * @param string $slot 기준 슬롯 (기본 'head_sub')
 * @return string URL (cdn_css 적용)
 */
function theme_asset_url($rel, $slot = 'head_sub')
{
    $rel = ltrim((string)$rel, '/');
    if ($rel === '') return '';
    $url = '';
    if (function_exists('get_slot_theme')) {
        $theme = get_slot_theme($slot);
        if ($theme !== '' && $theme !== 'default') {
            $tb = theme_resolve_base($theme);
            if ($tb['abs'] !== '') {
                $abs = $tb['abs'].'/'.$rel;
                if (is_file($abs)) $url = $tb['url'].'/'.$rel;
            }
        }
    }
    if ($url === '') $url = G5_URL.'/'.$rel;
    if (function_exists('cdn_css')) $url = cdn_css($url);
    return $url;
}

function theme_asset_link_tag($rel, $slot = 'head_sub')
{
    $url = theme_asset_url($rel, $slot);
    if ($url === '') return '';
    return '<link rel="stylesheet" href="'.$url.'" type="text/css">';
}
 /*
 * 2026-04-27부터 모든 디자인 규칙은 정적 /css/_design.config.css 가
 *   /css/root.php?pd_id=N 의 :root 변수를 var() 로 참조하는 방식으로 일원화.
 *   data/css/_design.config*.css 레거시 파일은 더 이상 생성도/참조도 되지 않음.
 */
function theme_design_link_tag()
{
    $d = get_active_design();
    if ($d === 'none') return '';
    $url = G5_URL.'/'.G5_CSS_DIR.'/_design.config.css';
    if (function_exists('cdn_css')) $url = cdn_css($url);
    return '<link rel="stylesheet" href="'.$url.'" type="text/css">';
}

/**
 * css/root.php 인라인 출력 (FOUC 방지). 함수 스코프로 격리하여
 * root.php 내부 로컬변수($side, $name 등)가 글로벌로 새지 않도록 한다.
 */
function emit_design_root_inline($pd_id)
{
    $_GET['pd_id']  = (int)$pd_id;
    $_INLINE_ROOT   = true;
    include G5_PATH.'/css/root.php';
}

/**
 * enter.php standalone head CSS.
 * iframe 없이 출력되는 엔터 페이지는 head.sub.php 를 타지 않으므로
 * 전체 디자인 변수·_design.config.css·배경 박스(style.css)를 여기서 올린다.
 */
function emit_enter_head_css()
{
    global $config, $g5;

    $href = function ($url) {
        return function_exists('cdn_css') ? cdn_css($url) : $url;
    };

    echo '<link rel="stylesheet" href="'.$href(G5_CSS_URL.'/default.css').'" type="text/css">'."\n";
    if (empty($config['cf_7'])) {
        $pd = function_exists('get_active_design') ? (int)get_active_design('main') : 1;
        if ($pd <= 0) $pd = 1;
        echo '<style id="d-root" data-pd-id="'.(int)$pd.'">'."\n";
        emit_design_root_inline($pd);
        echo "\n</style>\n";
        if (function_exists('theme_design_link_tag')) {
            $tag = theme_design_link_tag();
            if ($tag !== '') echo $tag."\n";
        }
    }
    echo '<link rel="stylesheet" href="'.$href(G5_CSS_URL.'/style.css').'" type="text/css">'."\n";
    if (function_exists('theme_css_link_tag')) {
        echo theme_css_link_tag('enter.css', 'enter')."\n";
    } else {
        echo '<link rel="stylesheet" href="'.$href(G5_CSS_URL.'/enter.css').'" type="text/css">'."\n";
    }

    if (function_exists('get_active_preset_id') && !empty($g5['theme_preset_table'])) {
        $pr_id = (int)get_active_preset_id();
        if ($pr_id > 0) {
            $row = sql_fetch("SELECT pr_font_family FROM `{$g5['theme_preset_table']}` WHERE pr_id = '{$pr_id}'", false);
            if (!empty($row['pr_font_family'])) {
                echo '<style id="d-font">body, html { font-family: '.htmlspecialchars($row['pr_font_family'], ENT_QUOTES, 'UTF-8').' !important; }</style>'."\n";
            }
        }
    }

    theme_emit_parent_design_sync();
}

/**
 * index.php iframe(#main) 의 시기·프리셋 CSS 변수(#d-root)를 부모 html.single 에 복사한다.
 *
 * 사이트 배경은 css/_design.config.css 가 부모의 html.single:before 에만 입힌다.
 * iframe 안 페이지는 .single 이 없어 링크색 등만 바뀌고, 바깥 배경은 index.php 로드 시점
 * 변수에 고정된다. 멤버란 ?time= / 마이페이지 테스트 시기처럼 테마가 시기를 따르면
 * iframe 의 #d-root 를 부모로 옮겨 배경색·이미지도 같이 바꾼다.
 *
 * 최상위 창·#main 이 아닌 iframe·팝업에서는 no-op.
 */
function theme_emit_parent_design_sync()
{
    static $done = false;
    if ($done) return;
    $done = true;
    echo <<<'HTML'
<script>
(function () {
    if (!parent || parent === window) return;
    try {
        var frame = window.frameElement;
        if (!frame || frame.id !== 'main') return;
        var copyStyle = function (id, removeIfMissing) {
            var src = document.getElementById(id);
            var dst = parent.document.getElementById(id);
            if (!src) {
                if (removeIfMissing && dst && dst.parentNode) dst.parentNode.removeChild(dst);
                return;
            }
            if (!dst) {
                dst = parent.document.createElement('style');
                dst.id = id;
                parent.document.head.appendChild(dst);
            }
            if (dst.textContent !== src.textContent) dst.textContent = src.textContent;
        };
        copyStyle('d-root', false);
        copyStyle('d-font', true);
    } catch (e) {}
})();
</script>
HTML;
}

/**
 * 디자인 프리셋 목록
 */
function design_preset_list()
{
    global $g5;
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = array();
    $r = sql_query("SELECT pd_id, pd_name FROM `{$g5['design_preset_table']}` ORDER BY pd_order ASC, pd_id ASC", false);
    if ($r) {
        while ($row = sql_fetch_array($r)) {
            $cache[(int)$row['pd_id']] = $row['pd_name'];
        }
    }
    if (empty($cache)) $cache[1] = 'default';
    return $cache;
}

/**
 * 디자인 프리셋 단건 조회
 */
function get_design_preset($pd_id)
{
    global $g5;
    $pd_id = (int)$pd_id;
    if ($pd_id <= 0) $pd_id = 1;
    return sql_fetch("SELECT * FROM `{$g5['design_preset_table']}` WHERE pd_id = '{$pd_id}'", false);
}

/**
 * theme/ 디렉토리 스캔 (가상 'default' 항상 포함)
 */
function theme_list()
{
    static $cache = null;
    if ($cache !== null) return $cache;
    $others = array();
    $base = G5_PATH.'/'.G5_THEME_DIR;
    if (is_dir($base)) {
        $dh = @opendir($base);
        if ($dh) {
            while (($f = readdir($dh)) !== false) {
                if ($f === '.' || $f === '..' || $f === 'default') continue;
                if (is_dir($base.'/'.$f)) $others[] = $f;
            }
            closedir($dh);
        }
    }
    sort($others);
    // 'default' 는 항상 처음에 위치 (드롭다운에서 가장 먼저 보이도록)
    $cache = array_merge(array('default'), $others);
    return $cache;
}

/**
 * 테마 이름 유효성
 *
 * 허용 형식:
 *  - 'default'
 *  - 코어 테마: 'basic' (theme/<name>)
 *
 * (2026-05-07~) 모듈 테마 'mtheme:<mod>/<theme>' 는 폐지 — 항상 false.
 */
function is_valid_theme($name)
{
    if ($name === 'default') return true;
    if ($name === '') return false;
    if (strncmp($name, 'mtheme:', 7) === 0) return false; // 폐지
    if (strpos($name, '/') !== false || strpos($name, '\\') !== false || strpos($name, '..') !== false) return false;
    return is_dir(G5_PATH.'/'.G5_THEME_DIR.'/'.$name);
}

/**
 * 테마 식별자 → 절대경로/URL 베이스로 해석.
 * 반환: array('abs'=>..., 'url'=>..., 'kind'=>'core'|'default'|'invalid', 'theme'=>..., 'mod'=>'')
 * default 또는 invalid 면 abs/url 은 ''.
 *
 * (2026-05-07~) 'mtheme:<mod>/<theme>' 은 폐지 — invalid 반환 (자동으로 default 폴백).
 */
function theme_resolve_base($theme)
{
    if ($theme === 'default' || $theme === '') {
        return array('abs'=>'', 'url'=>'', 'kind'=>'default', 'theme'=>'default', 'mod'=>'');
    }
    if (strncmp($theme, 'mtheme:', 7) === 0) {
        return array('abs'=>'', 'url'=>'', 'kind'=>'invalid', 'theme'=>$theme, 'mod'=>'');
    }
    if (!is_valid_theme($theme)) {
        return array('abs'=>'', 'url'=>'', 'kind'=>'invalid', 'theme'=>$theme, 'mod'=>'');
    }
    return array(
        'abs'   => G5_PATH.'/'.G5_THEME_DIR.'/'.$theme,
        'url'   => G5_URL .'/'.G5_THEME_DIR.'/'.$theme,
        'kind'  => 'core',
        'theme' => $theme,
        'mod'   => '',
    );
}

/**
 * 테마 라벨 (드롭다운 표시용)
 */
function theme_label($theme)
{
    if ($theme === 'default' || $theme === '') return 'default';
    if (strncmp($theme, 'mtheme:', 7) === 0) return $theme.' (구 모듈테마 — 사용 안 됨)';
    return $theme;
}

/**
 * 활성 모듈이 제공하는 테마 목록 — 폐지(빈 배열).
 * (2026-05-07~) 모듈은 테마/스킨 추가 책임 없음. 기존 호출처 호환용 stub.
 */
function theme_list_modules()
{
    return array();
}

/**
 * 테마가 지원하는 슬롯 키 목록 반환.
 *
 * 우선순위:
 *  1) theme/{name}/override_slots.php 가 있으면 해당 파일이 return 한 array 사용 (명시 선언).
 *     - 형식: <?php return array('head','tail','menu', ...);
 *     - 'all' 또는 ['*'] 단일 항목이면 모든 슬롯 지원으로 간주.
 *  2) 없으면 슬롯별로 파일(file) / 디렉터리(dir·skin) 존재 여부를 스캔.
 *     (전체 슬롯 자동 허용하지 않음 — 빈 매핑으로 빈 페이지가 나오던 문제 방지)
 *
 * 'default' 는 항상 모든 슬롯을 지원한다.
 */
function theme_supported_slots($theme)
{
    static $cache = array();
    if (isset($cache[$theme])) return $cache[$theme];

    $all_slots = array_keys(isset($GLOBALS['THEME_SLOT_MAP']) ? $GLOBALS['THEME_SLOT_MAP'] : array());

    if ($theme === 'default') {
        return $cache[$theme] = $all_slots;
    }
    $info = theme_resolve_base($theme);
    if ($info['kind'] === 'invalid' || $info['abs'] === '') {
        return $cache[$theme] = array();
    }
    $base = $info['abs'];

    // (1) 명시 선언 파일
    $decl = $base.'/override_slots.php';
    if (is_file($decl)) {
        $declared = @include $decl;
        if (is_array($declared)) {
            // ['*'] / ['all'] → 전체 허용
            $low = array_map('strtolower', $declared);
            if (in_array('*', $low, true) || in_array('all', $low, true)) {
                return $cache[$theme] = $all_slots;
            }
            // 화이트리스트와 교집합 (오타/제거된 슬롯 방어)
            return $cache[$theme] = array_values(array_intersect($all_slots, $declared));
        }
    }

    // (2) 선언 파일 없음 → 슬롯별로 실제 파일/디렉토리 존재 여부 스캔
    //     (이전: 무조건 전체 허용 — theme/ 하위에 없는 슬롯도 매핑되어 빈 페이지가 나오는 문제)
    $supported = array();
    $slot_map  = isset($GLOBALS['THEME_SLOT_MAP']) ? $GLOBALS['THEME_SLOT_MAP'] : array();
    foreach ($all_slots as $slot) {
        $def  = isset($slot_map[$slot]) ? $slot_map[$slot] : null;
        if (!$def || empty($def['path'])) continue;
        $path = $base.'/'.ltrim($def['path'], '/');
        $type = isset($def['type']) ? $def['type'] : 'file';
        if ($type === 'file') {
            if (is_file($path)) $supported[] = $slot;
        } else {
            // dir / skin
            if (is_dir($path)) $supported[] = $slot;
        }
    }
    return $cache[$theme] = $supported;
}

/**
 * 특정 슬롯을 지원하는 테마 목록만 반환.
 * theme_preset_form 의 슬롯별 드롭다운 필터링용.
 */
function theme_list_for_slot($slot)
{
    $out = array();
    foreach (theme_list() as $t) {
        if (in_array($slot, theme_supported_slots($t), true)) $out[] = $t;
    }
    foreach (theme_list_modules() as $t) {
        if (in_array($slot, theme_supported_slots($t), true)) $out[] = $t;
    }
    if (!in_array('default', $out, true)) array_unshift($out, 'default');
    return $out;
}

/**
 * 그룹(여러 슬롯 묶음)을 모두 지원하는 테마만 반환.
 */
function theme_list_for_group($slot_list)
{
    $out = array();
    $check = function($t) use ($slot_list) {
        $sup = theme_supported_slots($t);
        foreach ($slot_list as $s) if (!in_array($s, $sup, true)) return false;
        return true;
    };
    foreach (theme_list() as $t) if ($check($t)) $out[] = $t;
    foreach (theme_list_modules() as $t) if ($check($t)) $out[] = $t;
    if (!in_array('default', $out, true)) array_unshift($out, 'default');
    return $out;
}

/**
 * 슬롯의 활성 테마에 파일이 존재하면 절대경로 반환, 없으면 '' 반환.
 * 호출자에서 `if ($_p = theme_include(...)) { include $_p; return; }` 패턴으로 사용.
 * (호출자 스코프에서 직접 include 하므로 글로벌 변수 주입 불필요 — 기존엔 함수 스코프
 *  안에서 include 했기 때문에 GLOBALS 를 reference 로 끌어오는 비용이 컸음.)
 */
function theme_include($slot, $rel_path)
{
    $theme = get_slot_theme($slot);
    if ($theme === 'default') return '';
    $tb = theme_resolve_base($theme);
    if ($tb['abs'] === '') return '';
    $abs = $tb['abs'].'/'.ltrim($rel_path, '/\\');
    if (!is_file($abs)) return '';
    // 무한 재진입 방지: 동일 슬롯·파일이 이미 분기 스택에 있으면 스킵
    static $__stack = array();
    $__key = $slot.'|'.$abs;
    if (isset($__stack[$__key])) return '';
    $__stack[$__key] = true;
    // 호출자 호환: include 된 파일 안에서 다시 같은 슬롯이 매칭되어도 무한루프 안 되게
    // 스택은 요청 단위로 유지(함수가 끝난 후에도 남음). 같은 파일을 두번 include 시도하면 ''.
    return $abs;
}

/**
 * 스킨 디렉토리 해석: 슬롯의 테마에 해당 폴더 있으면 그 절대경로, 없으면 default
 */
function theme_skin_dir($skin_slot, $default_path)
{
    if (!isset($GLOBALS['THEME_SLOT_MAP'][$skin_slot])) return $default_path;
    $def = $GLOBALS['THEME_SLOT_MAP'][$skin_slot];
    if ($def['type'] !== 'skin') return $default_path;
    $theme = get_slot_theme($skin_slot);
    if ($theme === 'default') return $default_path;
    $tb = theme_resolve_base($theme);
    if ($tb['abs'] === '') return $default_path;
    $abs = $tb['abs'].'/'.$def['path'];
    if (is_dir($abs)) return $abs;
    return $default_path;
}

/**
 * 스킨 URL 해석
 */
function theme_skin_url($skin_slot, $default_url)
{
    if (!isset($GLOBALS['THEME_SLOT_MAP'][$skin_slot])) return $default_url;
    $def = $GLOBALS['THEME_SLOT_MAP'][$skin_slot];
    if ($def['type'] !== 'skin') return $default_url;
    $theme = get_slot_theme($skin_slot);
    if ($theme === 'default') return $default_url;
    $tb = theme_resolve_base($theme);
    if ($tb['abs'] === '') return $default_url;
    $abs = $tb['abs'].'/'.$def['path'];
    if (is_dir($abs)) return $tb['url'].'/'.$def['path'];
    return $default_url;
}

/**
 * 게시판 스킨 매핑: bo_skin → 슬롯
 */
function theme_resolve_board_skin($skin_name, $default_path)
{
    $map = array(
        'mmb'       => 'skin_board_mmb',
        'basic'     => 'skin_board_basic',
        'qna'       => 'skin_board_qna',
        'calculate' => 'skin_board_calculate',
    );
    if (!isset($map[$skin_name])) return $default_path;
    return theme_skin_dir($map[$skin_name], $default_path);
}

function theme_resolve_board_skin_url($skin_name, $default_url)
{
    $map = array(
        'mmb'       => 'skin_board_mmb',
        'basic'     => 'skin_board_basic',
        'qna'       => 'skin_board_qna',
        'calculate' => 'skin_board_calculate',
    );
    if (!isset($map[$skin_name])) return $default_url;
    return theme_skin_url($map[$skin_name], $default_url);
}

// =============================================================================
// 자동 디스패처 — 진입점에서 1회 실행, 슬롯 매칭 시 테마 파일 include 후 exit
// =============================================================================
function theme_dispatch_override()
{
    static $done = false;
    if ($done) return;
    $done = true;

    // CLI / SCRIPT_FILENAME 미정 환경 무시
    if (empty($_SERVER['SCRIPT_FILENAME'])) return;

    $script = $_SERVER['SCRIPT_FILENAME'];
    $real = @realpath($script);
    if ($real === false) $real = $script;

    $g5_path_real = @realpath(G5_PATH);
    if ($g5_path_real === false) $g5_path_real = G5_PATH;

    // 정규화: G5_PATH 기준 상대경로 (forward slash)
    $real_n  = str_replace('\\', '/', $real);
    $base_n  = str_replace('\\', '/', $g5_path_real);
    $base_n  = rtrim($base_n, '/');
    if ($base_n !== '' && strpos($real_n, $base_n.'/') === 0) {
        $rel = substr($real_n, strlen($base_n) + 1);
    } else {
        return; // G5_PATH 외부 (서브디렉토리 진입 등)
    }

    // 매핑되면 안 되는 영역
    if ($rel === '' || strpos($rel, 'adm/') === 0 || strpos($rel, 'adm_module/') === 0
        || strpos($rel, 'plugin/') === 0 || strpos($rel, 'install/') === 0
        || strpos($rel, 'data/') === 0 || strpos($rel, 'theme/') === 0
        || strpos($rel, 'extend/') === 0 || strpos($rel, 'lib/') === 0) {
        return;
    }

    if (empty($GLOBALS['THEME_SLOT_MAP'])) return;

    $matched_slot = '';
    foreach ($GLOBALS['THEME_SLOT_MAP'] as $slot => $def) {
        $type = isset($def['type']) ? $def['type'] : '';
        $path = isset($def['path']) ? $def['path'] : '';
        if ($path === '') continue;
        if ($type === 'file') {
            if ($rel === $path) { $matched_slot = $slot; break; }
        } else if ($type === 'dir') {
            if (strpos($rel, $path.'/') === 0) { $matched_slot = $slot; break; }
        }
        // skin: 디스패처 무시
    }
    if ($matched_slot === '') return;

    $theme = get_slot_theme($matched_slot);
    if ($theme === 'default') return;

    $tb = theme_resolve_base($theme);
    if ($tb['abs'] === '') return;
    $abs = $tb['abs'].'/'.$rel;
    if (!is_file($abs)) return;

    // top-level 처럼 동작하려면 글로벌 변수를 함수 스코프에 reference 주입.
    // extract 는 자식 파일의 동명 로컬 변수 오염 이슈로 사용 안함. 수동 루프로 이미 세팅된 변수는 건드리지 않음.
    foreach ($GLOBALS as $__k => $__v) {
        if ($__k === 'GLOBALS' || $__k === '__k' || $__k === '__v' || $__k === 'matched_slot' || $__k === 'rel' || $__k === 'real' || $__k === 'real_n' || $__k === 'base_n' || $__k === 'g5_path_real' || $__k === 'script' || $__k === 'theme' || $__k === 'tb' || $__k === 'abs' || $__k === 'def' || $__k === 'slot' || $__k === 'type' || $__k === 'path' || $__k === 'done') continue;
        if (!isset($$__k)) $$__k = &$GLOBALS[$__k];
    }
    unset($__k, $__v);
    include $abs;
    exit;
}

/**
 * 임의 경로(루트 기준 상대) 가 활성 테마에 존재하면 그 절대경로 반환, 없으면 G5_PATH 기준.
 * dir 슬롯 안의 sub 파일 (예: member/skin/list.skin.php) 를 테마로 분기할 때 사용.
 */
function theme_dir_file($rel)
{
    $rel = ltrim((string)$rel, '/\\');
    if ($rel === '' || empty($GLOBALS['THEME_SLOT_MAP'])) return G5_PATH.'/'.$rel;

    // 결과 캐시 (동일 요청 내 여러번 호출되는 list.skin.php 등을 따로 철스판하지 않도록)
    static $cache = array();
    if (isset($cache[$rel])) return $cache[$rel];

    // dir 슬롯만 메모이제이션 (최대 5개)
    static $dir_slots = null;
    if ($dir_slots === null) {
        $dir_slots = array();
        foreach ($GLOBALS['THEME_SLOT_MAP'] as $slot => $def) {
            if (isset($def['type']) && $def['type'] === 'dir' && !empty($def['path'])) {
                $dir_slots[$slot] = $def['path'];
            }
        }
    }

    foreach ($dir_slots as $slot => $path) {
        if (strpos($rel, $path.'/') !== 0) continue;
        $theme = get_slot_theme($slot);
        if ($theme === 'default') return $cache[$rel] = G5_PATH.'/'.$rel;
        $tb = theme_resolve_base($theme);
        if ($tb['abs'] === '') return $cache[$rel] = G5_PATH.'/'.$rel;
        $abs = $tb['abs'].'/'.$rel;
        if (is_file($abs)) return $cache[$rel] = $abs;
        return $cache[$rel] = G5_PATH.'/'.$rel;
    }
    return $cache[$rel] = G5_PATH.'/'.$rel;
}

// 디스패처는 모든 extend 파일이 로드된 이후에 실행되어야 함
// (community.config.php 의 check_site_auth() 등 다른 extend 의존 함수가 필요)
// → 별도 파일 zz_theme_dispatch.php 가 자동 로드 끝에서 호출함
