<?php
/**
 * css/root.php — 디자인 프리셋의 cs_value 들을 CSS 커스텀 프로퍼티로 emit
 *
 * 목적: 동적으로 페이지마다 새 CSS 파일을 생성/include 하지 않고, 단일
 * <link rel="stylesheet" href="/css/root.php?pd_id=N"> 만으로 모든 디자인 변수를
 * 브라우저에 전달한다.  preset.css / style.css 등 정적 CSS는 이 변수들을
 * var(--d-{cs_name}, fallback) 형태로 사용한다.
 *
 * 슬롯 구조:
 *   --d-{cs_name}        ← cs_value
 *   --d-{cs_name}-1..10  ← cs_etc_1..cs_etc_10
 *
 * 빈 값은 출력하지 않으므로 var(... , fallback) 의 fallback 이 동작한다.
 *
 * 캐시: pd_id 단위로 ETag/Last-Modified 처리.
 */

@include_once(__DIR__ . '/../common.php');

// inline 모드 (head.sub.php 에서 include) 와 제공되는 파일 이미지 둘 다 지원.
//   - inline: $_INLINE_ROOT = true; 이면 헤더/캐시 헤더를 출력하지 않음 (이미 HTML 내부)
if (empty($_INLINE_ROOT)) {
    header('Content-Type: text/css; charset=utf-8');
}

$pd_id = (int)($_GET['pd_id'] ?? 0);
if ($pd_id <= 0) {
    $pd_id = function_exists('get_active_design') ? (int)get_active_design('main') : 1;
}
if ($pd_id <= 0) $pd_id = 1;

global $g5;
$css_table = isset($g5['css_table']) ? $g5['css_table'] : 'g5_css_config';

$out = ":root {\n";
$out .= "    /* design preset #{$pd_id} */\n";

// 레이아웃 분기(메뉴 위치)에 사용할 cs_name 값을 별도로 수집
$layout_vals = array(
    'menu_pos'        => '',
    'menu_width'      => 0,
    'menu_height'     => 0,
    'use_header'      => '',
);

$res = sql_query("SELECT cs_name, cs_value, cs_etc_1, cs_etc_2, cs_etc_3, cs_etc_4, cs_etc_5, cs_etc_6, cs_etc_7, cs_etc_8, cs_etc_9, cs_etc_10 FROM `{$css_table}` WHERE pd_id = '{$pd_id}'", false);

if ($res) {
    while ($row = sql_fetch_array($res)) {
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$row['cs_name']);
        if ($name === '') continue;

        // 레이아웃 분기 값 캡처
        if (array_key_exists($name, $layout_vals)) {
            $layout_vals[$name] = (string)$row['cs_value'];
        }

        $slots = array(
            '' => $row['cs_value'],
            '-1' => $row['cs_etc_1'],
            '-2' => $row['cs_etc_2'],
            '-3' => $row['cs_etc_3'],
            '-4' => $row['cs_etc_4'],
            '-5' => $row['cs_etc_5'],
            '-6' => $row['cs_etc_6'],
            '-7' => $row['cs_etc_7'],
            '-8' => $row['cs_etc_8'],
            '-9' => $row['cs_etc_9'],
            '-10' => $row['cs_etc_10'],
        );
        foreach ($slots as $suf => $val) {
            $val = trim((string)$val);
            if ($val === '') continue;
            // CDN 토큰([DATA_URL]) 치환 — CSS 출력 직전
            if (function_exists('cdn_resolve_url')) $val = cdn_resolve_url($val);
            // CSS 주석/세미콜론/중괄호 차단
            if (preg_match('/[{};]|\*\//', $val)) continue;
            $out .= "    --d-{$name}{$suf}: {$val};\n";

            // 이미지 경로처럼 보이는 값(슬래시/http/data:)은 url() 래핑된 보조 변수도 emit.
            //   정적 CSS 에서 background-image: var(--d-{name}-img, none); 으로 사용.
            //   따옴표는 작은따옴표 내 문자만 허용 (CSS injection 방지).
            if (preg_match('#^(/|https?://|data:)#i', $val) && strpos($val, "'") === false) {
                $imgKey = '-img' . $suf; // cs_value -> -img, cs_etc_1 -> -img-1, ...
                $out .= "    --d-{$name}{$imgKey}: url('{$val}');\n";
            }

            // 방향 리스트(예: cs_etc_5 = "top||right||bottom||left") 를 per-side 플래그로 분해.
            //   border 등을 var() 만으로 동적 처리할 수 있게 보조 변수를 추가한다.
            //   --d-{name}-side-top / -right / -bottom / -left = 1 (해당 방향 활성)
            //   주의: PHP 가 array key '-5' 를 int -5 로 캐스팅하므로 == 또는 (string) 비교 사용.
            if ((string)$suf === '-5' && strpos($val, '||') !== false) {
                $_sides = array_filter(array_map('trim', explode('||', $val)));
                foreach ($_sides as $_side) {
                    if (in_array($_side, array('top','right','bottom','left'), true)) {
                        $out .= "    --d-{$name}-side-{$_side}: 1;\n";
                    }
                }
                unset($_sides, $_side);
            }
        }
    }
}

$out .= "}\n";

// === 레이아웃 분기 (CSS 변수만으로 표현 불가능한 규칙) =====================
// menu_pos: 'left' (사이드바) | 'top' (상단바). 기본 'left'.
$mpos = ($layout_vals['menu_pos'] === 'top') ? 'top' : 'left';
$mw   = (int)$layout_vals['menu_width'];
$mh   = (int)$layout_vals['menu_height'];
if ($mw <= 0) $mw = 200;
if ($mh <= 0) $mh = 60;

if ($mpos === 'left') {
    $out .= "#body { margin-left: {$mw}px; margin-top: 0; }\n";
    $out .= "#header { position: fixed; top:0; left:0; bottom:0; right:auto; overflow-y:auto; width: {$mw}px; height:auto; }\n";
    $out .= "#logo { padding:20px 0; text-align:center; }\n";
} else {
    $out .= "#body { margin-left: 0; margin-top: {$mh}px; }\n";
    $out .= "#header { position: relative; margin:0; padding:0; clear:both; width:auto; height: {$mh}px; left:auto; right:auto; bottom:auto; top:0; }\n";
    $out .= "#header .fix-layout { height:100%; }\n";
    $out .= "#logo { display:block; position:absolute; top:50%; left:0%; transform:translateY(-50%); padding:0; text-align:left; }\n";
}

// use_header == 'N' 일 때 헤더/푸터 숨김
if ($layout_vals['use_header'] === 'N') {
    $out .= "#header, #footer { display:none !important; }\n";
    $out .= "#body { margin:0 !important; }\n";
}
// =========================================================================

// 약식 캐싱 (inline 모드에서는 HTML 응답이 캡괄하므로 따로 처리하지 않음)
if (empty($_INLINE_ROOT)) {
    $etag = '"' . md5($out) . '"';
    header('Cache-Control: public, max-age=300');
    header('ETag: ' . $etag);
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
        header('HTTP/1.1 304 Not Modified');
        exit;
    }
}

echo $out;
