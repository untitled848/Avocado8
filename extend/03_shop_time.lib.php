<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 시기별 노출 헬퍼 (shop / banner / intro / notice 등 공용)
 *  - DB 저장 형식: '||1||2||' (ti_order 리스트), 'all' 또는 '' = 전체 시기
 *  - 사용처: shop.sh_time, banner.bn_time, intro.bn_time, notice.no_time, ...
 *
 * 스키마는 install/gnuboard5.sql 에서 일괄 생성됩니다 (런타임 마이그레이션 없음).
 */

/**
 * 폼 입력(배열 또는 'all') 을 DB 저장 형식으로 변환.
 *  - 'all' 포함 또는 빈 배열 → '' (전체 시기)
 *  - ['1','2'] → '||1||2||'
 */
function time_value_to_db($input)
{
    if (!is_array($input)) {
        $s = trim((string)$input);
        if ($s === '' || strtolower($s) === 'all') return '';
        return '||'.preg_replace('/[^0-9]/', '', $s).'||';
    }
    if (in_array('all', $input, true)) return '';
    $vals = array();
    foreach ($input as $v) {
        $n = (int)$v;
        if ($n > 0) $vals[$n] = $n;
    }
    if (empty($vals)) return '';
    sort($vals);
    return '||'.implode('||', $vals).'||';
}

/**
 * DB 저장 값을 ti_order 정수 배열로 디코드.
 *  - '' 또는 'all' → array() (= 전체 시기)
 */
function time_value_from_db($s)
{
    $s = trim((string)$s);
    if ($s === '' || strtolower($s) === 'all') return array();
    $parts = array_filter(array_map('intval', explode('||', $s)));
    return array_values(array_unique($parts));
}

/**
 * 현재 시기 ($cur_time) 이 조건을 만족하는지 검사.
 */
function time_value_matches($db_value, $cur_time)
{
    $list = time_value_from_db($db_value);
    if (empty($list)) return true; // 전체 시기
    return in_array((int)$cur_time, $list, true);
}

/**
 * SQL WHERE 절 단편 생성 ('AND (...)' 형태).
 *  - 컬럼 비어있거나 'all' 이면 통과
 *  - 그 외엔 LIKE '%||{cur}||%' 매칭
 */
function time_value_sql_where($column, $cur_time)
{
    $cur = (int)$cur_time;
    if ($cur <= 0) return '';
    $col = preg_replace('/[^a-z0-9_]/i', '', $column);
    return " AND ({$col} = '' OR {$col} = 'all' OR {$col} LIKE '%||{$cur}||%')";
}

/* ------------------------------------------------------------------ */
/*  하위 호환 alias (기존 shop_time_* 호출 코드 유지용)                  */
/* ------------------------------------------------------------------ */
if (!function_exists('shop_time_to_db'))    { function shop_time_to_db($v)        { return time_value_to_db($v); } }
if (!function_exists('shop_time_from_db'))  { function shop_time_from_db($v)      { return time_value_from_db($v); } }
if (!function_exists('shop_time_matches'))  { function shop_time_matches($v, $t)  { return time_value_matches($v, $t); } }
if (!function_exists('shop_time_sql_where')){ function shop_time_sql_where($c,$t) { return time_value_sql_where($c, $t); } }
