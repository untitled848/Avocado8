<?php
$sub_menu = "100500";
include_once('./_common.php');

check_admin_token();

// 1) 알림 종류 인라인 처리 (수정/삭제/신규)
$ids = isset($_POST['ct_ids']) && is_array($_POST['ct_ids']) ? $_POST['ct_ids'] : [];
foreach ($ids as $ct_id) {
    $ct_id = intval($ct_id);
    if ($ct_id <= 0) continue;

    if (!empty($_POST['ct_del_' . $ct_id])) {
        $sys = sql_fetch("SELECT ct_system FROM `{$g5['call_type_map_table']}` WHERE ct_id = '{$ct_id}'");
        if (!$sys || $sys['ct_system']) continue;
        sql_query("DELETE FROM `{$g5['call_type_map_table']}` WHERE ct_id = '{$ct_id}'");
        continue;
    }

    $cur = sql_fetch("SELECT ct_system FROM `{$g5['call_type_map_table']}` WHERE ct_id = '{$ct_id}'");
    if (!$cur) continue;

    $ct_name = sql_escape_string(trim($_POST['ct_name_' . $ct_id] ?? ''));
    $ct_memo = sql_escape_string(trim($_POST['ct_memo_' . $ct_id] ?? ''));
    $ct_link = sql_escape_string(trim($_POST['ct_link_' . $ct_id] ?? ''));
    $ct_use  = isset($_POST['ct_use_' . $ct_id]) ? 1 : 0;

    if ($cur['ct_system']) {
        sql_query("UPDATE `{$g5['call_type_map_table']}`
            SET ct_name = '{$ct_name}', ct_memo = '{$ct_memo}', ct_link = '{$ct_link}', ct_use = '{$ct_use}'
            WHERE ct_id = '{$ct_id}'");
    } else {
        $ct_code = preg_replace('/[^a-z0-9_]/', '', strtolower($_POST['ct_code_' . $ct_id] ?? ''));
        if ($ct_code === '') continue;
        $ct_code_esc = sql_escape_string($ct_code);
        sql_query("UPDATE `{$g5['call_type_map_table']}`
            SET ct_code = '{$ct_code_esc}', ct_name = '{$ct_name}', ct_memo = '{$ct_memo}',
                ct_link = '{$ct_link}', ct_use = '{$ct_use}'
            WHERE ct_id = '{$ct_id}'");
    }
}

$new_code = '';
$new_name = '';
// 새 알림 종류 추가는 모듈에서 담당 — 어드민 폼에서는 제거됨

// 2) 기본 설정
$cf_call_interval   = max(5, intval($_POST['cf_call_interval'] ?? 30));
$cf_call_index_rows = max(1, intval($_POST['cf_call_index_rows'] ?? 5));
$cf_call_page_rows  = max(1, intval($_POST['cf_call_page_rows']  ?? 20));
$cf_memo_del        = max(0, intval($_POST['cf_memo_del']  ?? 0));
$cf_use_memo_emoticon = !empty($_POST['cf_use_memo_emoticon']) ? 1 : 0;

sql_query("UPDATE {$g5['config_table']}
    SET cf_call_interval     = '{$cf_call_interval}',
        cf_call_index_rows   = '{$cf_call_index_rows}',
        cf_call_page_rows    = '{$cf_call_page_rows}',
        cf_memo_del          = '{$cf_memo_del}',
        cf_use_memo_emoticon = '{$cf_use_memo_emoticon}'");

goto_url('./config_call_form.php?saved=1', false);
