<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * extend/02_notice.lib.php — 공지사항 시스템 스키마 + 헬퍼
 *
 *  - 단일 테이블 avo_notice (no_time 컬럼에 시기 직렬화 저장)
 *  - 정책: 상점 sh_time / 메인슬라이드 bn_time 과 동일 통일
 *      '' 또는 'all' = 전체 시기 노출, '||1||2||' = 해당 시기만
 *      (사용/안함 토글은 no_use 컬럼으로 별도 제어)
 */

global $g5;

// $g5['notice_table'] 은 install/install_db.php 에서 data/dbconfig.php 에 등록됨.

// 자동 마이그레이션: no_skin 컬럼 (Wave 9+) — 스킨 지정용
if (function_exists('alter_table_add_column')) {
    @alter_table_add_column($g5['notice_table'], 'no_skin', "varchar(255) NOT NULL DEFAULT ''", 'no_pinned');
    @alter_table_add_column($g5['notice_table'], 'no_widget', "tinyint(1) NOT NULL DEFAULT '0'", 'no_pinned');
    // 카테고리 컬럼 (Wave 10+)
    @alter_table_add_column($g5['notice_table'], 'nc_name', "varchar(64) NOT NULL DEFAULT ''", 'no_skin');
    // config_table 에 카테고리 정의(||구분)
    @alter_table_add_column($g5['config_table'], 'cf_notice_category', "varchar(255) NOT NULL DEFAULT ''", 'cf_shop_category');
}

// 스키마는 install/gnuboard5.sql 에서 일괄 생성됩니다.

/**
 * 공지의 출력 시기(no_time)가 현재 시기와 매치되는지 검사.
 *   상점/메인슬라이드와 동일 정책 — shop_time_matches() 위임.
 */
function notice_time_matches($db_value, $cur_time) {
    if (function_exists('shop_time_matches')) return shop_time_matches($db_value, $cur_time);
    $s = trim((string)$db_value);
    if ($s === '' || strtolower($s) === 'all') return true;
    $cur = (int)$cur_time;
    if ($cur <= 0) return true;
    return (strpos($s, '||'.$cur.'||') !== false);
}

/**
 * SQL WHERE 절 단편 (상점/메인슬라이드와 동일 정책 — shop_time_sql_where 위임).
 */
function notice_time_sql_where($column, $cur_time) {
    if (function_exists('shop_time_sql_where')) return shop_time_sql_where($column, $cur_time);
    return '';
}

/**
 * 등록된 공지사항 카테고리 목록.
 *   config.cf_notice_category 의 ||구분 값을 배열로 반환 (빈 항목 제외).
 */
function notice_categories() {
    global $config;
    $raw = isset($config['cf_notice_category']) ? (string)$config['cf_notice_category'] : '';
    if ($raw === '') return array();
    $out = array();
    foreach (explode('||', $raw) as $v) {
        $v = trim($v);
        if ($v !== '') $out[] = $v;
    }
    return $out;
}

include_once G5_LIB_PATH.'/notice.lib.php';

if (function_exists('mdl_add_filter')) {
    mdl_add_filter('mdl_widget', function ($html, $module, $widget, $ch, $size = null) {
        global $g5;
        if ($module !== 'notice' || $widget !== 'default') {
            return $html;
        }
        return $html.(function_exists('notice_widget_html') ? notice_widget_html() : '');
    });
}
