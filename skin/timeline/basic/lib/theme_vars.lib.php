<?php
/**
 * 타임라인 스킨 CSS :root 변수
 *
 * 적용 우선순위 (높음 → 낮음):
 *   1) JSON 지정값  (data/timeline/{tl_table}/css_vars.json)
 *   2) skin/css/style.css 기본값
 *   3) timeline/timeline.css 기본값
 */
if (!defined('_GNUBOARD_')) exit;

if (!function_exists('tl_theme_css_paths')) {
/**
 * @return array{timeline:string,skin:string}
 */
function tl_theme_css_paths() {
    global $timeline_skin_path;
    $skin_css = '';
    if (!empty($timeline_skin_path) && is_file($timeline_skin_path.'/css/style.css')) {
        $skin_css = $timeline_skin_path.'/css/style.css';
    } elseif (defined('G5_SKIN_PATH') && is_file(G5_SKIN_PATH.'/timeline/basic/css/style.css')) {
        $skin_css = G5_SKIN_PATH.'/timeline/basic/css/style.css';
    }
    return array(
        'timeline' => defined('G5_PATH') ? G5_PATH.'/timeline/timeline.css' : '',
        'skin'     => $skin_css,
    );
}
}

if (!function_exists('tl_theme_parse_root_vars')) {
/**
 * CSS 파일의 :root 블록에서 변수 맵 추출
 * @return array<string,string> --name => value
 */
function tl_theme_parse_root_vars($css_path) {
    $out = array();
    if ($css_path === '' || !is_file($css_path)) return $out;
    $css = @file_get_contents($css_path);
    if ($css === false || $css === '') return $out;
    if (!preg_match('/:root\s*\{([^}]*)\}/s', $css, $m)) return $out;
    if (preg_match_all('/(--[a-zA-Z0-9_-]+)\s*:\s*([^;]+);/', $m[1], $mm, PREG_SET_ORDER)) {
        foreach ($mm as $row) {
            $out[$row[1]] = trim($row[2]);
        }
    }
    return $out;
}
}

if (!function_exists('tl_theme_parse_usages')) {
/**
 * CSS 파일에서 var(--name) 사용처(선택자 추정) 수집
 * @return array<string,array<int,array{file:string,selector:string,line:int}>>
 */
function tl_theme_parse_usages($css_path, $file_label = '') {
    $out = array();
    if ($css_path === '' || !is_file($css_path)) return $out;
    $raw = @file_get_contents($css_path);
    if ($raw === false || $raw === '') return $out;

    $label = $file_label !== '' ? $file_label : basename($css_path);
    // 주석 제거 (:root 정의는 사용처에서 제외)
    $css = preg_replace('/\/\*[\s\S]*?\*\//', '', $raw);
    $css = preg_replace('/:root\s*\{[^}]*\}/s', '', $css);

    $lines = preg_split("/\r\n|\n|\r/", $css);
    $selector = '';
    $brace = 0;
    $pending_sel = '';

    foreach ($lines as $i => $line) {
        $line_no = $i + 1;
        $trim = trim($line);
        if ($trim === '') continue;

        // 선택자 추적 (단순 휴리스틱)
        $open = substr_count($line, '{');
        $close = substr_count($line, '}');
        if ($brace === 0 && $open > 0) {
            $before = trim(strstr($line, '{', true));
            if ($before === false) $before = '';
            $sel = trim($pending_sel.' '.$before);
            $sel = preg_replace('/\s+/', ' ', $sel);
            $selector = $sel !== '' ? $sel : '(unknown)';
            $pending_sel = '';
        } elseif ($brace === 0 && $open === 0 && $close === 0 && strpos($line, ':') === false) {
            $pending_sel = trim($pending_sel.' '.$trim);
        }

        if (preg_match_all('/var\(\s*(--[a-zA-Z0-9_-]+)/', $line, $mm)) {
            foreach ($mm[1] as $var) {
                if (!isset($out[$var])) $out[$var] = array();
                $out[$var][] = array(
                    'file'     => $label,
                    'selector' => $selector !== '' ? $selector : '(rule)',
                    'line'     => $line_no,
                );
            }
        }

        $brace += $open - $close;
        if ($brace < 0) $brace = 0;
        if ($brace === 0 && $close > 0) {
            $selector = '';
            $pending_sel = '';
        }
    }
    return $out;
}
}

if (!function_exists('tl_theme_json_path')) {
/**
 * 타임라인별 JSON 저장 경로
 */
function tl_theme_json_path($tl_table) {
    $tl_table = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tl_table);
    if ($tl_table === '') return '';
    $dir = (defined('G5_DATA_PATH') ? G5_DATA_PATH : '').'/timeline/'.$tl_table;
    return $dir.'/css_vars.json';
}
}

if (!function_exists('tl_theme_extract_vars')) {
/**
 * 내보내기 래퍼(timeline-theme) 또는 기존 평면 맵에서 CSS 변수만 추출
 *
 * @param mixed $data
 * @return array{ok:bool,message:string,vars:array<string,mixed>}
 */
function tl_theme_extract_vars($data) {
    if (!is_array($data)) {
        return array('ok' => false, 'message' => 'JSON 객체가 아닙니다.', 'vars' => array());
    }
    if (isset($data['format'])) {
        $fmt = (string)$data['format'];
        if ($fmt === 'timeline-design') {
            return array('ok' => false, 'message' => '사이트 디자인 JSON입니다. 타임라인 테마 JSON을 선택하세요.', 'vars' => array());
        }
        if ($fmt !== 'timeline-theme') {
            return array('ok' => false, 'message' => '이 파일은 타임라인 테마 JSON이 아닙니다.', 'vars' => array());
        }
        if (!isset($data['vars']) || !is_array($data['vars'])) {
            return array('ok' => false, 'message' => '테마 변수(vars)가 없습니다.', 'vars' => array());
        }
        $data = $data['vars'];
    } elseif (isset($data['settings']) && is_array($data['settings']) && isset($data['preset'])) {
        return array('ok' => false, 'message' => '사이트 디자인 JSON입니다. 타임라인 테마 JSON을 선택하세요.', 'vars' => array());
    }
    return array('ok' => true, 'message' => '', 'vars' => $data);
}
}

if (!function_exists('tl_theme_clean_vars')) {
/**
 * CSS 변수 키·값 정리 (위험 문자 차단)
 *
 * @param mixed $data
 * @return array<string,string>
 */
function tl_theme_clean_vars($data) {
    $clean = array();
    if (!is_array($data)) return $clean;
    foreach ($data as $k => $v) {
        if (is_array($v) || is_object($v)) continue;
        $k = (string)$k;
        if ($k === '') continue;
        if (strpos($k, '--') !== 0) $k = '--'.ltrim($k, '-');
        if (!preg_match('/^--[a-zA-Z0-9_-]+$/', $k)) continue;
        $v = trim((string)$v);
        if ($v === '') continue;
        if (preg_match('/[<>{};]|url\s*\(|expression\s*\(|@import/i', $v)) continue;
        $clean[$k] = $v;
    }
    ksort($clean);
    return $clean;
}
}

if (!function_exists('tl_theme_build_export')) {
/**
 * 임포트/익스포트용 래퍼 JSON
 *
 * @param array<string,string> $vars
 * @return array<string,mixed>
 */
function tl_theme_build_export($tl_table, $vars) {
    global $timeline, $timeline_skin;
    $tl_table = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tl_table);
    $subject = '';
    $skin = '';
    if (isset($timeline) && is_array($timeline)) {
        if (!empty($timeline['tl_subject'])) $subject = (string)$timeline['tl_subject'];
        if (!empty($timeline['tl_skin'])) $skin = (string)$timeline['tl_skin'];
    }
    if ($skin === '' && !empty($timeline_skin)) $skin = (string)$timeline_skin;
    if (!is_array($vars)) $vars = array();
    return array(
        'format'      => 'timeline-theme',
        'version'     => 1,
        'exported_at' => date('Y-m-d H:i:s'),
        'timeline'    => array(
            'tl_table'   => $tl_table,
            'tl_subject' => $subject,
            'tl_skin'    => $skin,
        ),
        'vars'        => $vars,
    );
}
}

if (!function_exists('tl_theme_load_json')) {
/**
 * @return array<string,string>
 */
function tl_theme_load_json($tl_table) {
    $path = tl_theme_json_path($tl_table);
    if ($path === '' || !is_file($path)) return array();
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') return array();
    if (substr($raw, 0, 3) === "\xEF\xBB\xBF") $raw = substr($raw, 3);
    $data = json_decode($raw, true);
    if (!is_array($data)) return array();
    $extracted = tl_theme_extract_vars($data);
    if (!$extracted['ok']) return array();
    return tl_theme_clean_vars($extracted['vars']);
}
}

if (!function_exists('tl_theme_save_json')) {
/**
 * @param array|string $data 연관배열 또는 JSON 문자열
 * @return array{ok:bool,message:string,path:string,count:int}
 */
function tl_theme_save_json($tl_table, $data) {
    $path = tl_theme_json_path($tl_table);
    if ($path === '') {
        return array('ok' => false, 'message' => '타임라인 테이블이 없습니다.', 'path' => '', 'count' => 0);
    }

    if (is_string($data)) {
        $data = trim($data);
        if ($data === '') $data = '{}';
        if (substr($data, 0, 3) === "\xEF\xBB\xBF") $data = substr($data, 3);
        $decoded = json_decode($data, true);
        if (!is_array($decoded)) {
            return array('ok' => false, 'message' => 'JSON 형식이 올바르지 않습니다.', 'path' => $path, 'count' => 0);
        }
        $data = $decoded;
    }
    if (!is_array($data)) {
        return array('ok' => false, 'message' => '저장 데이터가 올바르지 않습니다.', 'path' => $path, 'count' => 0);
    }

    $extracted = tl_theme_extract_vars($data);
    if (!$extracted['ok']) {
        return array('ok' => false, 'message' => $extracted['message'], 'path' => $path, 'count' => 0);
    }
    $clean = tl_theme_clean_vars($extracted['vars']);

    $dir = dirname($path);
    $dir_perm = defined('G5_DIR_PERMISSION') ? G5_DIR_PERMISSION : 0755;
    $file_perm = defined('G5_FILE_PERMISSION') ? G5_FILE_PERMISSION : 0644;
    if (!is_dir($dir)) {
        @mkdir($dir, $dir_perm, true);
        @chmod($dir, $dir_perm);
    }

    $json = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false) {
        return array('ok' => false, 'message' => 'JSON 인코딩에 실패했습니다.', 'path' => $path, 'count' => 0);
    }
    $json .= "\n";
    if (@file_put_contents($path, $json) === false) {
        return array('ok' => false, 'message' => '파일 저장에 실패했습니다.', 'path' => $path, 'count' => 0);
    }
    @chmod($path, $file_perm);

    return array('ok' => true, 'message' => '저장되었습니다.', 'path' => $path, 'count' => count($clean));
}
}

if (!function_exists('tl_theme_layers')) {
/**
 * 우선순위별 레이어 + 유효값/출처
 * @return array{
 *   keys:string[],
 *   timeline:array<string,string>,
 *   skin:array<string,string>,
 *   json:array<string,string>,
 *   effective:array<string,string>,
 *   source:array<string,string>,
 *   usages:array
 * }
 */
function tl_theme_layers($tl_table) {
    $paths = tl_theme_css_paths();
    $timeline = tl_theme_parse_root_vars($paths['timeline']);
    $skin = tl_theme_parse_root_vars($paths['skin']);
    $json = tl_theme_load_json($tl_table);

    $keys = array_unique(array_merge(array_keys($timeline), array_keys($skin), array_keys($json)));
    sort($keys);

    $effective = array();
    $source = array();
    foreach ($keys as $k) {
        if (array_key_exists($k, $json)) {
            $effective[$k] = $json[$k];
            $source[$k] = 'json';
        } elseif (array_key_exists($k, $skin)) {
            $effective[$k] = $skin[$k];
            $source[$k] = 'skin';
        } elseif (array_key_exists($k, $timeline)) {
            $effective[$k] = $timeline[$k];
            $source[$k] = 'timeline';
        }
    }

    $usages = array();
    $u1 = tl_theme_parse_usages($paths['timeline'], 'timeline/timeline.css');
    $u2 = tl_theme_parse_usages($paths['skin'], 'skin/.../css/style.css');
    foreach (array($u1, $u2) as $map) {
        foreach ($map as $var => $rows) {
            if (!isset($usages[$var])) $usages[$var] = array();
            foreach ($rows as $row) {
                $usages[$var][] = $row;
            }
        }
    }

    return array(
        'keys'      => $keys,
        'timeline'  => $timeline,
        'skin'      => $skin,
        'json'      => $json,
        'effective' => $effective,
        'source'    => $source,
        'usages'    => $usages,
        'paths'     => $paths,
        'json_path' => tl_theme_json_path($tl_table),
    );
}
}

if (!function_exists('tl_theme_var_catalog')) {
/**
 * 변수 한글 라벨·카테고리 (UI용)
 * @return array<string,array{label:string,group:string,hint:string}>
 */
function tl_theme_var_catalog() {
    return array(
        '--tl-bg-primary' => array('label' => '기본 배경', 'group' => '배경', 'hint' => '카드·입력창·본문 면'),
        '--tl-bg-secondary' => array('label' => '보조 배경', 'group' => '배경', 'hint' => '탭·답글·약한 구분 면'),
        '--tl-bg-tertiary' => array('label' => '3차 배경', 'group' => '배경', 'hint' => '패널·알림 박스'),
        '--tl-bg-hover' => array('label' => '호버 배경', 'group' => '배경', 'hint' => '마우스 올렸을 때'),
        '--tl-bg-muted' => array('label' => '뮤트 배경', 'group' => '배경', 'hint' => '비활성·보조 영역'),
        '--tl-bg-black' => array('label' => '블랙 배경', 'group' => '배경', 'hint' => '전체화면·오버레이 속'),
        '--tl-bg-accent-soft' => array('label' => '강조 연한 배경', 'group' => '배경', 'hint' => '선택·포커스 보조'),
        '--tl-bg-action-hover' => array('label' => '액션 호버', 'group' => '배경', 'hint' => '버튼/액션 바 호버'),
        '--tl-bg-focus' => array('label' => '포커스 배경', 'group' => '배경', 'hint' => '포커스된 글 하이라이트'),
        '--tl-bg-focus-pulse' => array('label' => '포커스 펄스', 'group' => '배경', 'hint' => '포커스 애니메이션 시작색'),
        '--tl-bg-connector' => array('label' => '연결선', 'group' => '배경', 'hint' => '스레드·답글 연결선'),
        '--tl-bg-tab' => array('label' => '탭 배경', 'group' => '배경', 'hint' => '상단 탭'),
        '--tl-bg-badge' => array('label' => '배지 배경', 'group' => '배경', 'hint' => '카운트·라벨 배지'),
        '--tl-bg-like-soft' => array('label' => '좋아요 연한 배경', 'group' => '배경', 'hint' => '좋아요 활성 상태'),
        '--tl-bg-muted-hover' => array('label' => '뮤트 호버', 'group' => '배경', 'hint' => '보조 버튼 호버'),
        '--tl-bg-mention-hover' => array('label' => '멘션 호버', 'group' => '배경', 'hint' => '멘션 자동완성 호버'),
        '--tl-bg-avatar' => array('label' => '아바타 배경', 'group' => '배경', 'hint' => '프로필 원 플레이스홀더'),

        '--tl-border' => array('label' => '기본 테두리', 'group' => '테두리', 'hint' => '카드·구분선'),
        '--tl-border-light' => array('label' => '연한 테두리', 'group' => '테두리', 'hint' => '입력·약한 구분'),
        '--tl-border-medium' => array('label' => '중간 테두리', 'group' => '테두리', 'hint' => '버튼·컨트롤'),
        '--tl-border-info' => array('label' => '정보 테두리', 'group' => '테두리', 'hint' => '안내·아이템 박스'),

        '--tl-text-primary' => array('label' => '본문 글자', 'group' => '글자', 'hint' => '메인 텍스트'),
        '--tl-text-secondary' => array('label' => '보조 글자', 'group' => '글자', 'hint' => '설명·서브 카피'),
        '--tl-text-muted' => array('label' => '흐린 글자', 'group' => '글자', 'hint' => '시간·메타'),
        '--tl-text-gray' => array('label' => '회색 글자', 'group' => '글자', 'hint' => '아이콘·보조 라벨'),
        '--tl-text-dark' => array('label' => '진한 글자', 'group' => '글자', 'hint' => '제목·강조 텍스트'),
        '--tl-text-meta' => array('label' => '메타 글자', 'group' => '글자', 'hint' => '타입·부가 정보'),
        '--tl-text-soft' => array('label' => '소프트 글자', 'group' => '글자', 'hint' => '플레이스홀더급 보조'),
        '--tl-text-strong' => array('label' => '강한 글자', 'group' => '글자', 'hint' => '이름·강한 강조'),
        '--tl-text-placeholder' => array('label' => '플레이스홀더', 'group' => '글자', 'hint' => '입력 안내 문구'),
        '--tl-text-name' => array('label' => '이름 글자', 'group' => '글자', 'hint' => '캐릭터/표시 이름'),
        '--tl-text-handle' => array('label' => '핸들 글자', 'group' => '글자', 'hint' => '@멘션·부가 식별'),
        '--tl-text-warning' => array('label' => '경고 글자', 'group' => '글자', 'hint' => '경고 배너 위 글자색'),

        '--tl-accent' => array('label' => '포인트 색', 'group' => '포인트', 'hint' => '링크·활성·주요 버튼'),
        '--tl-accent-hover' => array('label' => '포인트 호버', 'group' => '포인트', 'hint' => '버튼 호버'),
        '--tl-accent-hover-alt' => array('label' => '포인트 호버(대체)', 'group' => '포인트', 'hint' => '보조 버튼 호버'),
        '--tl-accent-disabled' => array('label' => '포인트 비활성', 'group' => '포인트', 'hint' => '비활성 버튼'),
        '--tl-accent-soft-20' => array('label' => '포인트 20%', 'group' => '포인트', 'hint' => '멘션 하이라이트 등'),
        '--tl-like' => array('label' => '좋아요 색', 'group' => '포인트', 'hint' => '하트·좋아요'),
        '--tl-info' => array('label' => '정보 색', 'group' => '포인트', 'hint' => '안내·효과 텍스트'),

        '--tl-success' => array('label' => '성공', 'group' => '상태', 'hint' => '성공 강조 글자'),
        '--tl-success-bg' => array('label' => '성공 배경', 'group' => '상태', 'hint' => '성공 알림 박스'),
        '--tl-success-border' => array('label' => '성공 테두리', 'group' => '상태', 'hint' => '성공 알림 테두리'),
        '--tl-success-text' => array('label' => '성공 글자', 'group' => '상태', 'hint' => '성공 알림 본문'),
        '--tl-danger' => array('label' => '위험', 'group' => '상태', 'hint' => '삭제·실패'),
        '--tl-danger-bg' => array('label' => '위험 배경', 'group' => '상태', 'hint' => '오류 알림 박스'),
        '--tl-danger-border' => array('label' => '위험 테두리', 'group' => '상태', 'hint' => '오류 알림 테두리'),
        '--tl-danger-text' => array('label' => '위험 글자', 'group' => '상태', 'hint' => '오류 알림 본문'),
        '--tl-warning-bg' => array('label' => '경고 배경', 'group' => '상태', 'hint' => '경고 배너'),
        '--tl-warning-border' => array('label' => '경고 테두리', 'group' => '상태', 'hint' => '경고 배너 테두리'),

        '--tl-overlay-dark' => array('label' => '오버레이(진함)', 'group' => '오버레이', 'hint' => '이미지 뷰어 배경'),
        '--tl-overlay-black-90' => array('label' => '검정 90%', 'group' => '오버레이', 'hint' => '모달 암막'),
        '--tl-overlay-black-70' => array('label' => '검정 70%', 'group' => '오버레이', 'hint' => '버튼·뱃지 위 반투명'),
        '--tl-overlay-black-60' => array('label' => '검정 60%', 'group' => '오버레이', 'hint' => '이미지 위 컨트롤'),
        '--tl-overlay-black-50' => array('label' => '검정 50%', 'group' => '오버레이', 'hint' => '그림자·반투명 면'),
        '--tl-overlay-black-15' => array('label' => '검정 15%', 'group' => '오버레이', 'hint' => '드롭다운 그림자'),
        '--tl-overlay-black-10' => array('label' => '검정 10%', 'group' => '오버레이', 'hint' => '약한 그림자'),
        '--tl-overlay-black-05' => array('label' => '검정 5%', 'group' => '오버레이', 'hint' => '아주 약한 면'),
        '--tl-overlay-white-10' => array('label' => '흰색 10%', 'group' => '오버레이', 'hint' => '다크 위 버튼'),
        '--tl-overlay-white-20' => array('label' => '흰색 20%', 'group' => '오버레이', 'hint' => '다크 위 호버'),
        '--tl-overlay-white-25' => array('label' => '흰색 25%', 'group' => '오버레이', 'hint' => '다크 위 강조'),
        '--tl-overlay-white-30' => array('label' => '흰색 30%', 'group' => '오버레이', 'hint' => '다크 위 강한 호버'),
        '--tl-overlay-white-90' => array('label' => '흰색 90%', 'group' => '오버레이', 'hint' => '밝은 반투명 면'),
        '--tl-overlay-danger' => array('label' => '위험 오버레이', 'group' => '오버레이', 'hint' => '삭제 확인 버튼'),
    );
}
}

if (!function_exists('tl_theme_usage_summary')) {
/**
 * 사용처를 UI용으로 요약 (파일별 고유 selector)
 * @return array<int,array{file:string,selectors:string[]}>
 */
function tl_theme_usage_summary($usage_rows) {
    $by_file = array();
    foreach ((array)$usage_rows as $u) {
        $file = isset($u['file']) ? $u['file'] : '';
        $sel = isset($u['selector']) ? $u['selector'] : '';
        if ($file === '') continue;
        if (!isset($by_file[$file])) $by_file[$file] = array();
        if ($sel !== '' && !in_array($sel, $by_file[$file], true)) {
            $by_file[$file][] = $sel;
        }
    }
    $out = array();
    foreach ($by_file as $file => $sels) {
        $out[] = array('file' => $file, 'selectors' => $sels);
    }
    return $out;
}
}

if (!function_exists('tl_theme_print_stylesheets')) {
/**
 * CSS 링크 + JSON 오버라이드 스타일 출력
 * (timeline.css → skin style.css → JSON :root)
 */
function tl_theme_print_stylesheets($tl_table = '') {
    global $timeline_skin_path, $timeline_skin_url, $timeline;

    if ($tl_table === '' && is_array($timeline) && !empty($timeline['tl_table'])) {
        $tl_table = $timeline['tl_table'];
    }

    echo '<link rel="stylesheet" href="'.G5_URL.'/timeline/timeline.css">'."\n";
    if (!empty($timeline_skin_path) && is_file($timeline_skin_path.'/css/style.css')) {
        echo '<link rel="stylesheet" href="'.(isset($timeline_skin_url) ? $timeline_skin_url : '').'/css/style.css">'."\n";
    }

    $json = tl_theme_load_json($tl_table);
    if (empty($json)) return;

    echo '<style id="tl-css-vars-json">:root{';
    foreach ($json as $k => $v) {
        echo $k.':'.$v.';';
    }
    echo '}</style>'."\n";
}
}
