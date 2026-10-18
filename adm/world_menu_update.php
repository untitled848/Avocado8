<?php
$sub_menu = "310101";
include_once('./_common.php');

auth_check($auth[$sub_menu], "w");
check_admin_token();

@mkdir(G5_DATA_PATH.'/world/menu', G5_DIR_PERMISSION);
@chmod(G5_DATA_PATH.'/world/menu', G5_DIR_PERMISSION);

// 일부 호스팅(예: 카페24)의 웹방화벽(WAF)이 "name=\"main[0][title]\"" 같은 PHP 배열
// 표기 POST 파라미터를 의심 패턴으로 간주해 요청 자체를 차단하는 사례가 있다(애플리케이션
// 레벨에는 아무 로그도 남지 않고 HTTP 500만 발생). 그래서 폼에서는 "main_0_title" 처럼
// 평탄한(flat) 이름으로 전송받고, 여기서 다시 배열 구조로 재조립한다.
function wm_collect_flat($prefix) {
    $out = array();
    foreach ($_POST as $key => $val) {
        if (preg_match('/^'.preg_quote($prefix, '/').'_(\d+)_(.+)$/', $key, $mt)) {
            $out[(int)$mt[1]][$mt[2]] = $val;
        }
    }
    ksort($out);
    return $out;
}
// 파일 입력도 "main_img_0_d" 형태의 평탄한 이름으로 전송되므로 $_FILES 에서
// idx(0,1,2...) / suffix(d,h) 별로 재구성한다.
function wm_collect_flat_files($prefix) {
    $out = array();
    foreach ($_FILES as $key => $info) {
        if (preg_match('/^'.preg_quote($prefix, '/').'_(\d+)_([dh])$/', $key, $mt)) {
            $out[(int)$mt[1]][$mt[2]] = $info;
        }
    }
    return $out;
}

$main_data  = wm_collect_flat('main');
$sub_data   = wm_collect_flat('sub');
$first_page = $_POST['first_page'] ?? '';

$main_files   = wm_collect_flat_files('main_img');
$sub_files    = wm_collect_flat_files('sub_img');
$main_img_del = wm_collect_flat('main_img_del');
$sub_img_del  = wm_collect_flat('sub_img_del');

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
    // wm_title/wm_link_wo_id 컬럼 길이(200/20)를 초과하면 strict SQL 모드(MariaDB 기본값)에서
    // "Data too long for column" 오류가 발생해 저장이 실패하므로 미리 컬럼 길이에 맞춰 자른다.
    $title = mb_substr(trim($m['title'] ?? ''), 0, 200);
    $link  = preg_match('/^[a-z0-9_]+$/i', $m['link_wo_id'] ?? '') ? mb_substr($m['link_wo_id'], 0, 20) : '';
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
    // wm_title/wm_link_wo_id 컬럼 길이(200/20)를 초과하면 strict SQL 모드(MariaDB 기본값)에서
    // "Data too long for column" 오류가 발생해 저장이 실패하므로 미리 컬럼 길이에 맞춰 자른다.
    $title    = mb_substr(trim($s['title'] ?? ''), 0, 200);
    $link     = preg_match('/^[a-z0-9_]+$/i', $s['link_wo_id'] ?? '') ? mb_substr($s['link_wo_id'], 0, 20) : '';
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
        $col  = ($sfx === 'd') ? 'wm_img' : 'wm_img_hover';
        $file = $main_files[$idx][$sfx] ?? null;
        if ($file && !empty($file['name'])) {
            $saved = wm_save_img($file, $wm_id, $sfx);
            if ($saved !== false) {
                $saved_e = sql_escape_string($saved);
                sql_query("UPDATE `{$g5['world_menu_table']}` SET {$col}='{$saved_e}' WHERE wm_id='{$wm_id}'");
            }
        }
        if (!empty($main_img_del[$idx][$sfx])) {
            wm_purge_img($wm_id, $sfx);
            sql_query("UPDATE `{$g5['world_menu_table']}` SET {$col}='' WHERE wm_id='{$wm_id}'");
        }
    }
}

// ─── 이미지 업로드 (서브) ─────────────────────────────────────────────
foreach ($sub_id_map as $idx => $wm_id) {
    if ($wm_id <= 0) continue;
    foreach (array('d', 'h') as $sfx) {
        $col  = ($sfx === 'd') ? 'wm_img' : 'wm_img_hover';
        $file = $sub_files[$idx][$sfx] ?? null;
        if ($file && !empty($file['name'])) {
            $saved = wm_save_img($file, $wm_id, $sfx);
            if ($saved !== false) {
                $saved_e = sql_escape_string($saved);
                sql_query("UPDATE `{$g5['world_menu_table']}` SET {$col}='{$saved_e}' WHERE wm_id='{$wm_id}'");
            }
        }
        if (!empty($sub_img_del[$idx][$sfx])) {
            wm_purge_img($wm_id, $sfx);
            sql_query("UPDATE `{$g5['world_menu_table']}` SET {$col}='' WHERE wm_id='{$wm_id}'");
        }
    }
}

goto_url("./world_menu_list.php");
?>
