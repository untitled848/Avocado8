<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * lib/notice.lib.php — 공지사항 조회/렌더 함수
 */

/**
 * 단일 공지글 fetch
 * @return array|false
 */
function notice_get_one($no_id) {
    global $g5;
    $no_id = (int)$no_id;
    if ($no_id <= 0) return false;
    if (empty($g5['notice_table'])) return false;
    $row = sql_fetch("SELECT * FROM `{$g5['notice_table']}` WHERE no_id = '{$no_id}'", false);
    return $row ?: false;
}

/**
 * 활성 공지 목록 (현재 시기 노출 가능한 것만).
 *
 * @param int  $limit  0 이면 전체
 * @param int  $offset
 * @param array $opts  ['ignore_time' => bool]   true 면 시기 무관 전체
 *                     ['widget' => bool]        true 면 no_widget=1 만
 *                     ['cate' => string]|['cates' => array]
 * @return array
 */
function notice_get_list($limit = 0, $offset = 0, $opts = array()) {
    global $g5, $config;
    if (empty($g5['notice_table'])) return array();

    $where = " WHERE no_use = 1 ";
    if (empty($opts['ignore_time'])) {
        $cur = isset($config['cf_time']) ? (int)$config['cf_time'] : 0;
        if (function_exists('notice_time_sql_where')) {
            $where .= notice_time_sql_where('no_time', $cur);
        }
    }
    // 카테고리 필터: opts['cate'] (단일) 또는 opts['cates'] (배열, OR)
    if (!empty($opts['cate'])) {
        $c = sql_real_escape_string((string)$opts['cate']);
        $where .= " AND nc_name = '{$c}' ";
    } else if (!empty($opts['cates']) && is_array($opts['cates'])) {
        $parts = array();
        foreach ($opts['cates'] as $c) {
            $c = trim((string)$c);
            if ($c !== '') $parts[] = "'".sql_real_escape_string($c)."'";
        }
        if (!empty($parts)) $where .= " AND nc_name IN (".implode(',', $parts).") ";
    }
    if (!empty($opts['widget'])) {
        $where .= " AND no_widget = 1 ";
    }
    $order  = " ORDER BY no_pinned DESC, no_order ASC, no_datetime DESC, no_id DESC ";
    $limsql = '';
    $limit  = (int)$limit;
    $offset = (int)$offset;
    if ($limit > 0) $limsql = " LIMIT {$offset}, {$limit} ";

    $rows = array();
    $r = sql_query("SELECT * FROM `{$g5['notice_table']}` {$where} {$order} {$limsql}", false);
    if ($r) { while ($row = sql_fetch_array($r)) { $rows[] = $row; } }
    return $rows;
}

/**
 * 활성 공지 개수 (페이지네이션용).
 */
function notice_get_count($opts = array()) {
    global $g5, $config;
    if (empty($g5['notice_table'])) return 0;
    $where = " WHERE no_use = 1 ";
    if (empty($opts['ignore_time'])) {
        $cur = isset($config['cf_time']) ? (int)$config['cf_time'] : 0;
        if (function_exists('notice_time_sql_where')) {
            $where .= notice_time_sql_where('no_time', $cur);
        }
    }
    if (!empty($opts['cate'])) {
        $c = sql_real_escape_string((string)$opts['cate']);
        $where .= " AND nc_name = '{$c}' ";
    } else if (!empty($opts['cates']) && is_array($opts['cates'])) {
        $parts = array();
        foreach ($opts['cates'] as $c) {
            $c = trim((string)$c);
            if ($c !== '') $parts[] = "'".sql_real_escape_string($c)."'";
        }
        if (!empty($parts)) $where .= " AND nc_name IN (".implode(',', $parts).") ";
    }
    $row = sql_fetch("SELECT COUNT(*) AS c FROM `{$g5['notice_table']}` {$where}", false);
    return (int)($row['c'] ?? 0);
}

/**
 * 공지 행의 본문 HTML (문서 스킨·프리셋 CSS 없음).
 */
function notice_render_body($row) {
    if (!is_array($row)) return '';
    $body = isset($row['no_content']) ? $row['no_content'] : '';
    if (empty($row['no_html'])) {
        $body = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
    } else if (function_exists('cdn_resolve_content')) {
        $body = cdn_resolve_content($body);
    }
    return $body;
}

/**
 * 공지글 본문 HTML 렌더 (스마트에디터 작성, no_html=1 이면 그대로 출력).
 *
 *   메인 박스 위젯 / 상세 페이지 양쪽에서 호출 가능.
 *
 * @param int   $no_id
 * @param array $opts  ['wrap' => bool, 'class' => string]
 *                     wrap=true 시 래퍼 div 출력 (class 지정 가능)
 * @return string  공지가 없거나 비활성이면 ''
 */
function notice_render_one($no_id, $opts = array()) {
    $row = notice_get_one($no_id);
    if (!$row) return '';

    $body = notice_render_body($row);

    if (!empty($opts['wrap'])) {
        $cls = isset($opts['class']) ? trim($opts['class']) : 'notice-body';
        return '<div class="'.htmlspecialchars($cls).'">'.$body.'</div>';
    }
    return $body;
}

/**
 * 메인 공지 위젯 HTML.
 * 위젯 사용 + 사용 + 현재 시기인 글을 제목/본문 세로 나열.
 * 공지 문서 디자인(프리셋 CSS·꾸밈)은 쓰지 않는다.
 */
function notice_widget_html() {
    ob_start();
    include G5_PATH.'/notice/skin/widget.skin.php';
    return ob_get_clean();
}
