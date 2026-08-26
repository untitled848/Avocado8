<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

function get_side_name($si_id)
{
    global $g5;

    if (empty($g5['side_table']) || $si_id === '' || $si_id === null) {
        return '-';
    }

    $si_id = (int)$si_id;
    $row = sql_fetch("SELECT si_name FROM {$g5['side_table']} WHERE si_id = '{$si_id}'");

    return (!empty($row['si_name'])) ? $row['si_name'] : '-';
}

function get_class_name($cl_id)
{
    global $g5;

    if (empty($g5['class_table']) || $cl_id === '' || $cl_id === null) {
        return '-';
    }

    $cl_id = (int)$cl_id;
    $row = sql_fetch("SELECT cl_name FROM {$g5['class_table']} WHERE cl_id = '{$cl_id}'");

    return (!empty($row['cl_name'])) ? $row['cl_name'] : '-';
}

function get_side_icon($si_id)
{
    global $g5;

    if (empty($g5['side_table']) || $si_id === '' || $si_id === null) {
        return '';
    }

    $si_id = (int)$si_id;
    $row = sql_fetch("SELECT si_img, si_name FROM {$g5['side_table']} WHERE si_id = '{$si_id}'");

    if (empty($row['si_img'])) {
        return '';
    }

    $url = $row['si_img'];
    $url = str_replace(defined('CDN_TOKEN') ? CDN_TOKEN : '[DATA_URL]', G5_DATA_URL, $url);
    if (function_exists('mdl_apply_filters')) $url = mdl_apply_filters('side_icon_url', $url, $si_id);

    $src = htmlspecialchars($url, ENT_QUOTES);
    $alt = htmlspecialchars($row['si_name'] ?? '', ENT_QUOTES);

    return "<img src=\"{$src}\" alt=\"{$alt}\" class=\"ui-side-icon\">";
}

function get_class_icon($cl_id)
{
    global $g5;

    if (empty($g5['class_table']) || $cl_id === '' || $cl_id === null) {
        return '';
    }

    $cl_id = (int)$cl_id;
    $row = sql_fetch("SELECT cl_img, cl_name FROM {$g5['class_table']} WHERE cl_id = '{$cl_id}'");

    if (empty($row['cl_img'])) {
        return '';
    }

    $url = $row['cl_img'];
    $url = str_replace(defined('CDN_TOKEN') ? CDN_TOKEN : '[DATA_URL]', G5_DATA_URL, $url);
    if (function_exists('mdl_apply_filters')) $url = mdl_apply_filters('class_icon_url', $url, $cl_id);

    $src = htmlspecialchars($url, ENT_QUOTES);
    $alt = htmlspecialchars($row['cl_name'] ?? '', ENT_QUOTES);

    return "<img src=\"{$src}\" alt=\"{$alt}\" class=\"ui-class-icon\">";
}
?>
