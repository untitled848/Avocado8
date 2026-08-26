<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 전체 행 반환 (ti_use = 'Y' 조건 포함)
function get_title($ti_id)
{
    global $g5;

    $sql = " select * 
               from {$g5['title_table']} 
              where ti_id = '".sql_real_escape_string($ti_id)."'
                and ti_use = 'Y' ";
    $ti = sql_fetch($sql);

    // 실패 시 항상 배열 형태로 반환하도록 정규화
    if (!is_array($ti)) {
        return array();
    }

    return $ti;
}

// 값만 반환
function get_title_value($ti_id)
{
    global $g5;

    $sql = " select * 
               from {$g5['title_table']} 
              where ti_id = '".sql_real_escape_string($ti_id)."' ";
    $ti = sql_fetch($sql);

    $src="";
    $text="";

    if(is_array($ti)){
        $ti_img=(isset($ti['ti_img']) && $ti['ti_img'] !== '') ? $ti['ti_img'] : '';
        $ti_title=(isset($ti['ti_title']) && $ti['ti_title'] !== '') ? $ti['ti_title'] : '';
        $ti_type=(isset($ti['ti_type']) && $ti['ti_type'] !== '') ? $ti['ti_type'] : '';

        if ($ti_img && ($ti_type == 'img' || $ti_type == 'both')) {
            // XSS 방지용 이스케이프
            $url = $ti['ti_img'];

            if (function_exists('mdl_apply_filters')) {
                $url = mdl_apply_filters('title_image_url', $url, $ti_id);
            }

            $src = "<img class='title-image' src='".htmlspecialchars($url, ENT_QUOTES)."'>";
        }
        if ($ti_title && ($ti_type == 'text' || $ti_type == 'both')) {
            $ti_style = "";
            $ti_style=(isset($ti['ti_style']) && $ti['ti_style'] !== '') ? "style='{$ti['ti_style']}'" : '';
            $text = "<i class='title-text' ".$ti_style.">".htmlspecialchars($ti_title, ENT_QUOTES)."</i>";
        }
        return "<span class='title-box type-{$ti_type}'>{$src}{$text}</span>";
    }

    return "";

}

// 이미지 HTML 반환
function get_title_image($ti_id)
{
    return get_title_value($ti_id);
}
