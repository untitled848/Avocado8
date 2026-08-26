<?php
$sub_menu = '400100';

include_once './_common.php';
check_demo();
check_token();

// ---------------- 입력값 정리 ----------------

// 기본 프로필 필드 (단일값)
$ad_use_thumb  = $_POST['ad_use_thumb']  ?? '';
$ad_use_head   = $_POST['ad_use_head']   ?? '';
$ad_use_body   = $_POST['ad_use_body']   ?? '';
$ad_use_name   = $_POST['ad_use_name']   ?? '';

$ad_text_thumb = $_POST['ad_text_thumb'] ?? '';
$ad_text_head  = $_POST['ad_text_head']  ?? '';
$ad_text_body  = $_POST['ad_text_body']  ?? '';
$ad_text_name  = $_POST['ad_text_name']  ?? '';

$ad_help_thumb = htmlspecialchars($_POST['ad_help_thumb'] ?? '', ENT_QUOTES);
$ad_help_head  = htmlspecialchars($_POST['ad_help_head']  ?? '', ENT_QUOTES);
$ad_help_body  = htmlspecialchars($_POST['ad_help_body']  ?? '', ENT_QUOTES);
$ad_help_name  = htmlspecialchars($_POST['ad_help_name']  ?? '', ENT_QUOTES);

$ad_url_thumb  = $_POST['ad_url_thumb']  ?? '';
$ad_url_head   = $_POST['ad_url_head']   ?? '';
$ad_url_body   = $_POST['ad_url_body']   ?? '';

$ad_use_title  = $_POST['ad_use_title']  ?? '';
$ad_use_closet = $_POST['ad_use_closet'] ?? '';
$ad_use_inven  = $_POST['ad_use_inven']  ?? '';
$ad_use_money  = $_POST['ad_use_money']  ?? '';
$ad_use_exp    = $_POST['ad_use_exp']    ?? '';
$ad_use_rank   = $_POST['ad_use_rank']   ?? '';
$ad_use_status = $_POST['ad_use_status'] ?? '';

$ad_required_thumb = !empty($_POST['ad_required_thumb']) ? 1 : 0;
$ad_required_head  = !empty($_POST['ad_required_head'])  ? 1 : 0;
$ad_required_body  = !empty($_POST['ad_required_body'])  ? 1 : 0;
$ad_required_name  = !empty($_POST['ad_required_name'])  ? 1 : 0;
$ad_min_length_name = max(0, (int)($_POST['ad_min_length_name'] ?? 0));
$ad_max_length_name = max(0, (int)($_POST['ad_max_length_name'] ?? 0));

// 멤버 리스트 표시 설정 (community_form 에서 이전 → config 테이블)
$cf_member_thumb_w    = (int)($_POST['cf_member_thumb_w'] ?? 120);
$cf_member_thumb_h    = (int)($_POST['cf_member_thumb_h'] ?? 160);
$cf_member_grid_mode  = preg_replace('/[^a-zA-Z\-]/', '', (string)($_POST['cf_member_grid_mode'] ?? 'auto-fill'));
$cf_member_grid_count = max(1, min(20, (int)($_POST['cf_member_grid_count'] ?? 4)));
$cf_member_side_merge = empty($_POST['cf_member_side_merge']) ? 0 : 1;
$cf_member_order      = addslashes(stripslashes((string)($_POST['cf_member_order'] ?? 'ch_id asc')));
$cf_member_npc_mode   = preg_replace('/[^a-zA-Z]/', '', (string)($_POST['cf_member_npc_mode'] ?? 'split'));
$cf_member_npc_pos    = preg_replace('/[^a-zA-Z]/', '', (string)($_POST['cf_member_npc_pos'] ?? 'top'));
if (!in_array($cf_member_grid_mode, array('auto-fill', 'auto-fit', 'fixed'), true)) {
    $cf_member_grid_mode = 'auto-fill';
}
if (!in_array($cf_member_npc_mode, array('split', 'merge'), true)) {
    $cf_member_npc_mode = 'split';
}
if (!in_array($cf_member_npc_pos, array('top', 'bottom', 'inline'), true)) {
    $cf_member_npc_pos = 'top';
}

sql_query("
    UPDATE `{$g5['config_table']}`
       SET cf_member_thumb_w    = {$cf_member_thumb_w},
           cf_member_thumb_h    = {$cf_member_thumb_h},
           cf_member_grid_mode  = '{$cf_member_grid_mode}',
           cf_member_grid_count = {$cf_member_grid_count},
           cf_member_side_merge = {$cf_member_side_merge},
           cf_member_order      = '{$cf_member_order}',
           cf_member_npc_mode   = '{$cf_member_npc_mode}',
           cf_member_npc_pos    = '{$cf_member_npc_pos}'
");

// 추가 프로필 필드 (배열)
$ar_id     = isset($_POST['ar_id'])     && is_array($_POST['ar_id'])     ? $_POST['ar_id']     : array();
$ar_code   = isset($_POST['ar_code'])   && is_array($_POST['ar_code'])   ? $_POST['ar_code']   : array();
$ar_name   = isset($_POST['ar_name'])   && is_array($_POST['ar_name'])   ? $_POST['ar_name']   : array();
$ar_type   = isset($_POST['ar_type'])   && is_array($_POST['ar_type'])   ? $_POST['ar_type']   : array();
$ar_size   = isset($_POST['ar_size'])   && is_array($_POST['ar_size'])   ? $_POST['ar_size']   : array();
$ar_text   = isset($_POST['ar_text'])   && is_array($_POST['ar_text'])   ? $_POST['ar_text']   : array();
$ar_help   = isset($_POST['ar_help'])   && is_array($_POST['ar_help'])   ? $_POST['ar_help']   : array();
$ar_order  = isset($_POST['ar_order'])  && is_array($_POST['ar_order'])  ? $_POST['ar_order']  : array();
$ar_secret = isset($_POST['ar_secret']) && is_array($_POST['ar_secret']) ? $_POST['ar_secret'] : array();
$ar_time   = isset($_POST['ar_time'])   && is_array($_POST['ar_time'])   ? $_POST['ar_time']   : array();
$ar_min_length = isset($_POST['ar_min_length']) && is_array($_POST['ar_min_length']) ? $_POST['ar_min_length'] : array();
$ar_max_length = isset($_POST['ar_max_length']) && is_array($_POST['ar_max_length']) ? $_POST['ar_max_length'] : array();
$ar_required   = isset($_POST['ar_required'])   && is_array($_POST['ar_required'])   ? $_POST['ar_required']   : array();
$ar_delete     = isset($_POST['ar_delete'])     && is_array($_POST['ar_delete'])     ? $_POST['ar_delete']     : array();

// ---------------- 기본 프로필 양식 등록 ----------------

$profile = sql_fetch("SELECT ad_id FROM {$g5['article_default_table']}");

if (!empty($profile['ad_id'])) {
    // 업데이트
    $sql = "
        UPDATE {$g5['article_default_table']}
        SET ad_use_thumb = '{$ad_use_thumb}',
            ad_use_head  = '{$ad_use_head}',
            ad_use_body  = '{$ad_use_body}',
            ad_use_name  = '{$ad_use_name}',
            ad_text_thumb = '{$ad_text_thumb}',
            ad_text_head  = '{$ad_text_head}',
            ad_text_body  = '{$ad_text_body}',
            ad_text_name  = '{$ad_text_name}',
            ad_help_thumb = '{$ad_help_thumb}',
            ad_help_head  = '{$ad_help_head}',
            ad_help_body  = '{$ad_help_body}',
            ad_help_name  = '{$ad_help_name}',
            ad_url_thumb  = '{$ad_url_thumb}',
            ad_url_head   = '{$ad_url_head}',
            ad_url_body   = '{$ad_url_body}',
            ad_use_title  = '{$ad_use_title}',
            ad_use_closet = '{$ad_use_closet}',
            ad_use_inven  = '{$ad_use_inven}',
            ad_use_money  = '{$ad_use_money}',
            ad_use_exp    = '{$ad_use_exp}',
            ad_use_rank   = '{$ad_use_rank}',
            ad_use_status = '{$ad_use_status}',
            ad_required_thumb = '{$ad_required_thumb}',
            ad_required_head  = '{$ad_required_head}',
            ad_required_body  = '{$ad_required_body}',
            ad_required_name  = '{$ad_required_name}',
            ad_min_length_name = '{$ad_min_length_name}',
            ad_max_length_name = '{$ad_max_length_name}'
    ";
    sql_query($sql);

} else {
    // 신규등록
    $sql = "
        INSERT INTO {$g5['article_default_table']}
        SET ad_use_thumb = '{$ad_use_thumb}',
            ad_use_head  = '{$ad_use_head}',
            ad_use_body  = '{$ad_use_body}',
            ad_use_name  = '{$ad_use_name}',
            ad_text_thumb = '{$ad_text_thumb}',
            ad_text_head  = '{$ad_text_head}',
            ad_text_body  = '{$ad_text_body}',
            ad_text_name  = '{$ad_text_name}',
            ad_help_thumb = '{$ad_help_thumb}',
            ad_help_head  = '{$ad_help_head}',
            ad_help_body  = '{$ad_help_body}',
            ad_help_name  = '{$ad_help_name}',
            ad_url_thumb  = '{$ad_url_thumb}',
            ad_url_head   = '{$ad_url_head}',
            ad_url_body   = '{$ad_url_body}',
            ad_use_title  = '{$ad_use_title}',
            ad_use_closet = '{$ad_use_closet}',
            ad_use_inven  = '{$ad_use_inven}',
            ad_use_money  = '{$ad_use_money}',
            ad_use_exp    = '{$ad_use_exp}',
            ad_use_rank   = '{$ad_use_rank}',
            ad_use_status = '{$ad_use_status}',
            ad_required_thumb = '{$ad_required_thumb}',
            ad_required_head  = '{$ad_required_head}',
            ad_required_body  = '{$ad_required_body}',
            ad_required_name  = '{$ad_required_name}',
            ad_min_length_name = '{$ad_min_length_name}',
            ad_max_length_name = '{$ad_max_length_name}'
    ";
    sql_query($sql);
}

// ---------------- 추가 프로필 양식 수정 ----------------

$cnt = count($ar_id);

for ($i = 0; $i < $cnt; $i++) {

    $id    = trim($ar_id[$i]     ?? '');
    $code  = trim($ar_code[$i]   ?? '');
    // 코드 검증: 영문 대/소문자, _ 만 허용
    $code  = preg_replace('/[^A-Za-z_]/', '', $code);
    $name  = trim($ar_name[$i]   ?? '');
    $type  = trim($ar_type[$i]   ?? '');
    $size  = trim($ar_size[$i]   ?? '');
    $text  = $ar_text[$i]        ?? '';
    $help  = $ar_help[$i]        ?? '';
    $order = trim($ar_order[$i]  ?? '');
    $secret= trim($ar_secret[$i] ?? '');
    $time_db = isset($ar_time[$i]) && function_exists('shop_time_to_db') ? shop_time_to_db($ar_time[$i]) : '';
    $minl  = (int)($ar_min_length[$i] ?? 0);
    $maxl  = (int)($ar_max_length[$i] ?? 0);
    $req   = !empty($ar_required[$i]) ? 1 : 0;

    if ($id === '') {
        continue;
    }

    // 명시적 삭제 체크박스 사용 시
    if (!empty($ar_delete[$i])) {
        $code = '';
    }

    // 기존 코드 조회
    $sql = "
        SELECT ar_code
        FROM {$g5['article_table']}
        WHERE ar_id = '{$id}'
    ";
    $ar = sql_fetch($sql);

    // 코드가 비어 있으면 항목 삭제
    if ($code === '') {

        if (!empty($ar['ar_code'])) {
            // 등록된 캐릭터의 항목값 삭제
            $sql = "DELETE FROM {$g5['value_table']} WHERE ar_code = '{$ar['ar_code']}'";
            sql_query($sql);
        }

        // 등록된 항목 삭제
        $sql = "DELETE FROM {$g5['article_table']} WHERE ar_id = '{$id}'";
        sql_query($sql);

        continue;
    }

    // 코드명이 변경된 경우 value_table 갱신
    if (!empty($ar['ar_code']) && $ar['ar_code'] !== $code) {
        $sql = "
            UPDATE {$g5['value_table']}
               SET ar_code = '{$code}'
             WHERE ar_code = '{$ar['ar_code']}'
        ";
        sql_query($sql);
    }

    $help = htmlspecialchars($help, ENT_QUOTES);

    // 항목 업데이트
    $sql = "
        UPDATE {$g5['article_table']}
           SET ar_code   = '{$code}',
               ar_name   = '{$name}',
               ar_type   = '{$type}',
               ar_size   = '{$size}',
               ar_text   = '{$text}',
               ar_help   = '{$help}',
               ar_order  = '{$order}',
               ar_secret = '{$secret}',
               ar_time   = '{$time_db}',
               ar_min_length = '{$minl}',
               ar_max_length = '{$maxl}',
               ar_required   = '{$req}'
         WHERE ar_id    = '{$id}'
    ";
    sql_query($sql);
}

$url = './character_article_list.php';
if (!empty($qstr)) {
    $url .= '?'.$qstr;
}

goto_url($url);
exit;