<?php
$sub_menu = "310101";
include_once('./_common.php');

auth_check($auth[$sub_menu], "w");
check_admin_token();

@mkdir(G5_DATA_PATH.'/world/menu', G5_DIR_PERMISSION);
@chmod(G5_DATA_PATH.'/world/menu', G5_DIR_PERMISSION);

$main_data  = is_array($_POST['main'] ?? null)  ? $_POST['main']  : array();
$sub_data   = is_array($_POST['sub'] ?? null)   ? $_POST['sub']   : array();
$first_page = $_POST['first_page'] ?? '';

$allowed_img = array('jpg','jpeg','png','gif','webp');

// ─── Helper: 이미지 업로드 처리 ─────────────────────────────────────────
// 성공 시 저장된 basename(확장자 포함) 반환, 실패 시 false.
// 같은 wm_id+suffix 의 이전 파일들(확장자 다르거나 구형식 무확장자 포함)은 모두 정리.
function wm_save_img($file_arr, $wm_id, $suffix) {
    global $allowed_img;
    if (empty($file_arr['name']) || $file_arr['error']) return false;
    $ext = strtolower(pathinfo($file_arr['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_img)) return false;
    wm_purge_img($wm_id, $suffix);
    $basename = $wm_id.'_'.$suffix.'.'.$ext;
    $dest = G5_DATA_PATH.'/world/menu/'.$basename;
    if (@move_uploaded_file($file_arr['tmp_name'], $dest)) {
        @chmod($dest, G5_FILE_PERMISSION);
        if (function_exists('cdn_after_upload')) cdn_after_upload($dest);
        return $basename;
    }
    return false;
}
// 주어진 wm_id+suffix 에 해당하는 모든 변형 파일 삭제 (구형식 무확장자 포함).
// 로컬 + CDN 양쪽 정리.
function wm_purge_img($wm_id, $suffix) {
    $base = G5_DATA_PATH.'/world/menu/'.$wm_id.'_'.$suffix;
    $targets = array();
    if (is_file($base)) $targets[] = $base;
    foreach ((array)glob($base.'.*') as $p) { if (is_file($p)) $targets[] = $p; }
    foreach ($targets as $t) {
        if (function_exists('cdn_unlink')) { @cdn_unlink($t); } else { @unlink($t); }
    }
}

// ─── 메인 처리 ─────────────────────────────────────────────────────────
$main_id_map = array(); // idx => wm_id (-1 = deleted)
$del_main_ids = array();

foreach ($main_data as $idx => $m) {
    $idx  = (int)$idx;
    $mid  = (int)($m['id'] ?? 0);
    $del  = (int)($m['del'] ?? 0);
    $title = trim($m['title'] ?? '');
    $link  = preg_match('/^[a-z0-9_]+$/i', $m['link_wo_id'] ?? '') ? $m['link_wo_id'] : '';
    $order = (int)($m['order'] ?? 0);

    if ($del) {
        if ($mid > 0) $del_main_ids[] = $mid;
        $main_id_map[$idx] = -1;
        continue;
    }

    if ($mid == 0) {
        sql_query("INSERT INTO `{$g5['world_menu_table']}` SET wm_depth=0, wm_parent=0, wm_order='{$order}', wm_title='".sql_escape_string($title)."', wm_link_wo_id='".sql_escape_string($link)."', wm_is_first=0");
        $main_id_map[$idx] = (int)sql_insert_id();
    } else {
        sql_query("UPDATE `{$g5['world_menu_table']}` SET wm_order='{$order}', wm_title='".sql_escape_string($title)."', wm_link_wo_id='".sql_escape_string($link)."' WHERE wm_id='{$mid}' AND wm_depth=0");
        $main_id_map[$idx] = $mid;
    }
}

// 삭제 (메인 + 하위 서브 모두)
foreach ($del_main_ids as $did) {
    $rs = sql_query("SELECT wm_id FROM `{$g5['world_menu_table']}` WHERE wm_parent='{$did}'");
    while ($sr = sql_fetch_array($rs)) {
        wm_purge_img((int)$sr['wm_id'], 'd');
        wm_purge_img((int)$sr['wm_id'], 'h');
    }
    sql_query("DELETE FROM `{$g5['world_menu_table']}` WHERE wm_parent='{$did}'");
    wm_purge_img($did, 'd');
    wm_purge_img($did, 'h');
    sql_query("DELETE FROM `{$g5['world_menu_table']}` WHERE wm_id='{$did}'");
}

// ─── 서브 처리 ─────────────────────────────────────────────────────────
$sub_id_map   = array();
$del_sub_ids  = array();

foreach ($sub_data as $idx => $s) {
    $idx      = (int)$idx;
    $sid      = (int)($s['id'] ?? 0);
    $main_idx = (int)($s['main_idx'] ?? -1);
    $del      = (int)($s['del'] ?? 0);
    $title    = trim($s['title'] ?? '');
    $link     = preg_match('/^[a-z0-9_]+$/i', $s['link_wo_id'] ?? '') ? $s['link_wo_id'] : '';
    $order    = (int)($s['order'] ?? 0);
    $parent_wm_id = $main_id_map[$main_idx] ?? -1;

    if ($del || $parent_wm_id <= 0) {
        if ($sid > 0) $del_sub_ids[] = $sid;
        $sub_id_map[$idx] = -1;
        continue;
    }

    if ($sid == 0) {
        sql_query("INSERT INTO `{$g5['world_menu_table']}` SET wm_depth=1, wm_parent='{$parent_wm_id}', wm_order='{$order}', wm_title='".sql_escape_string($title)."', wm_link_wo_id='".sql_escape_string($link)."', wm_is_first=0");
        $sub_id_map[$idx] = (int)sql_insert_id();
    } else {
        sql_query("UPDATE `{$g5['world_menu_table']}` SET wm_order='{$order}', wm_title='".sql_escape_string($title)."', wm_link_wo_id='".sql_escape_string($link)."', wm_parent='{$parent_wm_id}' WHERE wm_id='{$sid}' AND wm_depth=1");
        $sub_id_map[$idx] = $sid;
    }
}

foreach ($del_sub_ids as $did) {
    wm_purge_img($did, 'd');
    wm_purge_img($did, 'h');
    sql_query("DELETE FROM `{$g5['world_menu_table']}` WHERE wm_id='{$did}'");
}

// ─── 첫 페이지 설정 ──────────────────────────────────────────────────
sql_query("UPDATE `{$g5['world_menu_table']}` SET wm_is_first=0");
if (preg_match('/^(m|s)_(\d+)$/', $first_page, $fp)) {
    $fp_type = $fp[1];
    $fp_idx  = (int)$fp[2];
    $fp_wm   = ($fp_type === 'm') ? ($main_id_map[$fp_idx] ?? 0) : ($sub_id_map[$fp_idx] ?? 0);
    if ($fp_wm > 0) {
        sql_query("UPDATE `{$g5['world_menu_table']}` SET wm_is_first=1 WHERE wm_id='{$fp_wm}'");
    }
}

// ─── 이미지 업로드 (메인) ─────────────────────────────────────────────
foreach ($main_id_map as $idx => $wm_id) {
    if ($wm_id <= 0) continue;
    foreach (array('d', 'h') as $sfx) {
        $col = ($sfx === 'd') ? 'wm_img' : 'wm_img_hover';
        if (!empty($_FILES['main_img']['name'][$idx][$sfx])) {
            $file = array(
                'name'     => $_FILES['main_img']['name'][$idx][$sfx],
                'tmp_name' => $_FILES['main_img']['tmp_name'][$idx][$sfx],
                'error'    => $_FILES['main_img']['error'][$idx][$sfx],
            );
            $saved = wm_save_img($file, $wm_id, $sfx);
            if ($saved !== false) {
                $saved_e = sql_escape_string($saved);
                sql_query("UPDATE `{$g5['world_menu_table']}` SET {$col}='{$saved_e}' WHERE wm_id='{$wm_id}'");
            }
        }
        if (!empty($_POST['main_img_del'][$idx][$sfx])) {
            wm_purge_img($wm_id, $sfx);
            sql_query("UPDATE `{$g5['world_menu_table']}` SET {$col}='' WHERE wm_id='{$wm_id}'");
        }
    }
}

// ─── 이미지 업로드 (서브) ─────────────────────────────────────────────
foreach ($sub_id_map as $idx => $wm_id) {
    if ($wm_id <= 0) continue;
    foreach (array('d', 'h') as $sfx) {
        $col = ($sfx === 'd') ? 'wm_img' : 'wm_img_hover';
        if (!empty($_FILES['sub_img']['name'][$idx][$sfx])) {
            $file = array(
                'name'     => $_FILES['sub_img']['name'][$idx][$sfx],
                'tmp_name' => $_FILES['sub_img']['tmp_name'][$idx][$sfx],
                'error'    => $_FILES['sub_img']['error'][$idx][$sfx],
            );
            $saved = wm_save_img($file, $wm_id, $sfx);
            if ($saved !== false) {
                $saved_e = sql_escape_string($saved);
                sql_query("UPDATE `{$g5['world_menu_table']}` SET {$col}='{$saved_e}' WHERE wm_id='{$wm_id}'");
            }
        }
        if (!empty($_POST['sub_img_del'][$idx][$sfx])) {
            wm_purge_img($wm_id, $sfx);
            sql_query("UPDATE `{$g5['world_menu_table']}` SET {$col}='' WHERE wm_id='{$wm_id}'");
        }
    }
}

goto_url("./world_menu_list.php");
?>
