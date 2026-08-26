<?php
$sub_menu = "300700";
include_once('./_common.php');

check_demo();

auth_check($auth[$sub_menu], 'w');

$act = $_REQUEST['act'] ?? '';

// ─── 1) 신청 모드 저장 ───────────────────────────────────────────
if ($act === 'mode') {
    check_admin_token();
    $mode = $_POST['cf_emoticon_apply_mode'] ?? 'disabled';
    if (!in_array($mode, array('disabled','manual','auto'), true)) $mode = 'disabled';
    sql_query("UPDATE {$g5['config_table']} SET cf_emoticon_apply_mode = '{$mode}'");
    goto_url('./emoticon_list.php');
}

// ─── 2) 단건 승인 ────────────────────────────────────────────────
if ($act === 'one_approve') {
    check_token();
    $me_id = (int)($_GET['me_id'] ?? 0);
    if ($me_id > 0) sql_query("UPDATE {$g5['emoticon_table']} SET em_use = '1' WHERE me_id = '{$me_id}'");
    goto_url('./emoticon_list.php');
}

// ─── 3) 단건 거부(삭제) ─────────────────────────────────────────
if ($act === 'one_reject') {
    check_token();
    $me_id = (int)($_GET['me_id'] ?? 0);
    if ($me_id > 0) {
        $row = sql_fetch("SELECT * FROM {$g5['emoticon_table']} WHERE me_id = '{$me_id}'");
        if ($row) {
            sql_query("DELETE FROM {$g5['emoticon_table']} WHERE me_id = '{$me_id}'");
            $_p = G5_PATH.$row['me_img'];
            if (function_exists('cdn_unlink')) { @cdn_unlink($_p); } else { @unlink($_p); }
        }
    }
    goto_url('./emoticon_list.php');
}

// ─── 4) 일괄 승인/거부 ───────────────────────────────────────────
if ($act === 'approve') {
    check_admin_token();
    $bulk = $_POST['bulk'] ?? 'approve';
    $pids = $_POST['pid'] ?? array();
    if (!is_array($pids) || count($pids) === 0) {
        alert('항목을 하나 이상 선택하세요.');
    }
    foreach ($pids as $pid) {
        $pid = (int)$pid;
        if ($pid <= 0) continue;
        if ($bulk === 'reject') {
            $row = sql_fetch("SELECT * FROM {$g5['emoticon_table']} WHERE me_id = '{$pid}'");
            if ($row) {
                sql_query("DELETE FROM {$g5['emoticon_table']} WHERE me_id = '{$pid}'");
                $_p = G5_PATH.$row['me_img'];
                if (function_exists('cdn_unlink')) { @cdn_unlink($_p); } else { @unlink($_p); }
            }
        } else {
            sql_query("UPDATE {$g5['emoticon_table']} SET em_use = '1' WHERE me_id = '{$pid}'");
        }
    }
    goto_url('./emoticon_list.php');
}

// ─── 5) 이미지 교체 (단건) ──────────────────────────────────
if ($act === 'img_replace') {
    check_admin_token();
    $me_id = (int)($_POST['me_id'] ?? 0);
    if ($me_id <= 0) alert('잘못된 요청입니다.');
    $row = sql_fetch("SELECT * FROM {$g5['emoticon_table']} WHERE me_id = '{$me_id}'");
    if (!$row) alert('해당 이모티콘이 없습니다.');
    if (empty($_FILES['me_img']['tmp_name']) || !is_uploaded_file($_FILES['me_img']['tmp_name'])) {
        alert('교체할 이미지를 선택하세요.');
    }
    // 새 파일 저장 — 기존 me_img 와 같은 디렉토리, 새 파일명
    $orig = $row['me_img']; // 예: /data/emoticon/xxx.png
    $dir_url = dirname($orig);
    $dir_path = G5_PATH.$dir_url;
    if (!is_dir($dir_path)) @mkdir($dir_path, G5_DIR_PERMISSION, true);
    $ext = pathinfo($_FILES['me_img']['name'], PATHINFO_EXTENSION);
    $ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
    if ($ext === '') $ext = 'png';
    $newname = 'emo_'.$me_id.'_'.time().'.'.$ext;
    $newurl  = $dir_url.'/'.$newname;
    $newpath = $dir_path.'/'.$newname;
    if (!@move_uploaded_file($_FILES['me_img']['tmp_name'], $newpath)) {
        alert('이미지 업로드 실패.');
    }
    @chmod($newpath, G5_FILE_PERMISSION);
    // 기존 파일 삭제
    $oldpath = G5_PATH.$orig;
    if (is_file($oldpath)) {
        if (function_exists('cdn_unlink')) { @cdn_unlink($oldpath); } else { @unlink($oldpath); }
    }
    sql_query("UPDATE {$g5['emoticon_table']} SET me_img = '".addslashes($newurl)."' WHERE me_id = '{$me_id}'");
    goto_url('./emoticon_list.php');
}

// ─── 6) 일괄 수정 (사용/보류 + 명령어) ───────────────────────
if ($act === 'edit_bulk') {
    check_token();
    $count = isset($_POST['chk']) ? count($_POST['chk']) : 0;
    if (!$count) alert('수정 하실 항목을 하나 이상 체크하세요.');
    $updated = 0;
    for ($i = 0; $i < $count; $i++) {
        $k = (int)$_POST['chk'][$i];
        $me_id = (int)($_POST['me_id'][$k] ?? 0);
        if ($me_id <= 0) continue;
        $em_use = (isset($_POST['em_use'][$k]) && (int)$_POST['em_use'][$k] === 1) ? 1 : 0;
        $me_text = isset($_POST['me_text'][$k]) ? trim((string)$_POST['me_text'][$k]) : '';
        $me_text_esc = addslashes($me_text);
        sql_query("UPDATE {$g5['emoticon_table']} SET em_use = '{$em_use}', me_text = '{$me_text_esc}' WHERE me_id = '{$me_id}'");
        $updated++;
    }
    goto_url('./emoticon_list.php?'.$qstr);
}

// ─── 7) 기본: 선택삭제 (기존 동작) ────────────────────────────
check_token();

$count = isset($_POST['chk']) ? count($_POST['chk']) : 0;
if (!$count) alert(($_POST['act_button'] ?? '선택삭제').' 하실 항목을 하나 이상 체크하세요.');

for ($i = 0; $i < $count; $i++) {
    $k = $_POST['chk'][$i];
    $sql = " select * from {$g5['emoticon_table']} where me_id = '{$_POST['me_id'][$k]}' ";
    $row = sql_fetch($sql);
    if (!$row['me_id']) continue;
    sql_query(" delete from {$g5['emoticon_table']} where me_id = '{$_POST['me_id'][$k]}' ");
    $_p = G5_PATH.$row['me_img'];
    if (function_exists('cdn_unlink')) { @cdn_unlink($_p); } else { @unlink($_p); }
}

goto_url('./emoticon_list.php?'.$qstr);
