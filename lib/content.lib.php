<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * lib/content.lib.php — 문서(content) 디자인 프리셋 관련 헬퍼
 *
 *   (1-[3] 3 단계: bbs/content.php 에서 디자인 CSS / 꾸밈 이미지 출력에 사용)
 */

/**
 * 디자인 설정 배열 반환
 * @return array ['cs_name' => [0=>cs_value, 1..10=>cs_etc_1..10], ...]
 */
function content_get_config($pd_id) {
    global $g5;
    $pd_id = (int)$pd_id;
    if ($pd_id <= 0) $pd_id = 1;
    $cfg = array();
    if (empty($g5['content_design_config_table'])) return $cfg;
    $r = sql_query("SELECT * FROM `{$g5['content_design_config_table']}` WHERE pd_id = '{$pd_id}'", false);
    if (!$r) return $cfg;
    while ($row = sql_fetch_array($r)) {
        $entry = array($row['cs_value']);
        for ($i = 1; $i <= 10; $i++) { $entry[$i] = $row['cs_etc_'.$i]; }
        $cfg[$row['cs_name']] = $entry;
    }
    return $cfg;
}

/**
 * 꾸밈 이미지 HTML 반환
 * @param string $where  'top' | 'bottom'
 * @param int    $pd_id
 * @return string  비어 있으면 ''
 */
function content_render_decoration($where, $pd_id) {
    static $_cache = array();
    $pd_id = (int)$pd_id;
    if ($pd_id <= 0) $pd_id = 1;
    if (!isset($_cache[$pd_id])) {
        $_cache[$pd_id] = content_get_config($pd_id);
    }
    $cfg = $_cache[$pd_id];

    $key = ($where === 'top') ? 'decoration_top' : 'decoration_bottom';
    if (empty($cfg[$key][0])) return '';

    $url = $cfg[$key][0];
    $cls = ($where === 'top') ? 'content-deco--top' : 'content-deco--bottom';
    $alt = ($where === 'top') ? '상단 꾸밈' : '하단 꾸밈';

    return '<div class="content-deco '.$cls.'"><img src="'.htmlspecialchars($url).'" alt="'.$alt.'"></div>';
}

/**
 * 현재 적용 중인 문서 디자인 프리셋 ID 반환
 * (get_active_design('content') 단순 래퍼 — null 안전)
 */
function content_active_pd_id() {
    if (function_exists('get_active_design')) {
        $v = (int)get_active_design('content');
        if ($v > 0) return $v;
    }
    return 1;
}
