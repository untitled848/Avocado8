<?php
/**
 * css/world_root.php — 세계관 디자인 프리셋의 cs_value 들을 CSS 커스텀 프로퍼티로 emit
 * (css/root.php 와 동일 구조 — world_config_table 사용)
 */

@include_once(__DIR__ . '/../common.php');

header('Content-Type: text/css; charset=utf-8');

$pd_id = (int)($_GET['pd_id'] ?? 0);
if ($pd_id <= 0) {
    $pd_id = function_exists('get_active_design') ? (int)get_active_design('world') : 1;
}
if ($pd_id <= 0) $pd_id = 1;

global $g5;
$tbl = isset($g5['world_config_table']) ? $g5['world_config_table'] : 'g5_world_config';

$out = ":root {\n";
$out .= "    /* world preset #{$pd_id} */\n";

$res = sql_query("SELECT cs_name, cs_value, cs_etc_1, cs_etc_2, cs_etc_3, cs_etc_4, cs_etc_5, cs_etc_6, cs_etc_7, cs_etc_8, cs_etc_9, cs_etc_10 FROM `{$tbl}` WHERE pd_id = '{$pd_id}'", false);

if ($res) {
    while ($row = sql_fetch_array($res)) {
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$row['cs_name']);
        if ($name === '') continue;

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
            // CDN 토큰 치환
            if (function_exists('cdn_resolve_url')) $val = cdn_resolve_url($val);
            if (preg_match('/[{};]|\*\//', $val)) continue;
            $out .= "    --w-{$name}{$suf}: {$val};\n";

            // 이미지 경로 → url() 래핑 보조 변수
            if (preg_match('#^(/|https?://|data:)#i', $val) && strpos($val, "'") === false) {
                $imgKey = '-img' . $suf;
                $out .= "    --w-{$name}{$imgKey}: url('{$val}');\n";
            }

            // 방향 리스트 → per-side 플래그 (root.php 와 동일)
            if ($suf === '-5' && strpos($val, '||') !== false) {
                $sides = array_filter(array_map('trim', explode('||', $val)));
                foreach ($sides as $side) {
                    if (in_array($side, array('top','right','bottom','left'), true)) {
                        $out .= "    --w-{$name}-side-{$side}: 1;\n";
                    }
                }
            }
        }
    }
}

$out .= "}\n";

$etag = '"' . md5($out) . '"';
header('Cache-Control: public, max-age=300');
header('ETag: ' . $etag);
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
    header('HTTP/1.1 304 Not Modified');
    exit;
}

echo $out;
