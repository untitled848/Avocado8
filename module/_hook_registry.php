<?php
/**
 * 훅 레지스트리 — 훅 메타정보 DB 관리
 *
 * 주요 함수:
 *   mdl_hook_registry_rescan()       — 소스 파일 스캔 → DB 갱신
 *   mdl_hook_registry_search($filter) — DB 조회 헬퍼
 *   mdl_hook_registry_doc($name, $doc) — hr_doc 수동 편집
 *   mdl_hook_registry_used_by($name)  — 해당 훅을 선언한 모듈 리스트
 */

if (!defined('_GNUBOARD_')) exit;

/* ----------------------------------------------------------
 * 스코프 판정 (bbs_ / mmb_ / item_ 접두사 → 'bbs', 그 외 'general')
 * ---------------------------------------------------------- */
function _hr_scope($name) {
    foreach (array('bbs_', 'mmb_', 'item_') as $prefix) {
        if (strpos($name, $prefix) === 0) return 'bbs';
    }
    return 'general';
}

/* ----------------------------------------------------------
 * 파라미터 시그니처 단순 추출
 *   mdl_do_action('hook', $a, $b) → '$a, $b'
 * ---------------------------------------------------------- */
function _hr_param_signature($raw_after_name) {
    // raw_after_name = 부분 소스: 첫 인자(훅명) 이후 남은 텍스트
    // 쉼표 뒤를 정리하고, 닫는 괄호 앞까지만 가져옴
    $s = trim($raw_after_name, " \t\n\r,");
    // 최대 500자
    return mb_substr($s, 0, 500);
}

/* ----------------------------------------------------------
 * 스캔 대상 디렉토리 (workspace-relative)
 * ---------------------------------------------------------- */
/**
 * 훅 레지스트리 스캔 경로.
 * 모듈 점검(`mdl_hook_scan_paths`)과 같은 코어/스킨/테마를 쓰고,
 * 비활성 모듈의 발사처까지 넣기 위해 `module/` 전체를 추가한 뒤 하위 경로는 접는다.
 */
function _hr_scan_dirs() {
    $paths = array();
    if (function_exists('mdl_hook_scan_paths')) {
        $paths = mdl_hook_scan_paths();
    }
    $mod = G5_PATH.'/module';
    if (is_dir($mod)) {
        $paths[] = $mod;
    }
    if (empty($paths)) {
        $base = G5_PATH;
        $paths = array(
            $base.'/lib',
            $base.'/extend',
            $base.'/bbs',
            $base.'/member',
            $base.'/mypage',
            $base.'/adm',
            $base.'/adm_module',
            $base.'/skin/board/mmb',
            $mod,
        );
    }

    $kinds = array();
    foreach ($paths as $p) {
        $rp = @realpath($p);
        if ($rp === false) {
            continue;
        }
        $kinds[$rp] = is_dir($rp) ? 'dir' : 'file';
    }

    $out = array();
    $sep = DIRECTORY_SEPARATOR;
    foreach ($kinds as $p => $kind) {
        $skip = false;
        foreach ($kinds as $other => $okind) {
            if ($okind !== 'dir' || $other === $p) {
                continue;
            }
            if (strpos($p, $other.$sep) === 0) {
                $skip = true;
                break;
            }
        }
        if (!$skip) {
            $out[] = $p;
        }
    }
    return $out;
}

/* ----------------------------------------------------------
 * 재귀 파일 목록 (*.php)
 * ---------------------------------------------------------- */
function _hr_php_files($dir) {
    $result = array();
    if (!is_dir($dir)) return $result;

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
            $result[] = $file->getPathname();
        }
    }
    return $result;
}

/* ----------------------------------------------------------
 * 소스 파일 1개를 스캔하여 훅 호출 목록 반환
 *   return array of [ 'type', 'name', 'sig', 'file', 'line' ]
 * ---------------------------------------------------------- */
function _hr_scan_file($path) {
    $src = @file_get_contents($path);
    if ($src === false || $src === '') return array();

    $base  = G5_PATH;
    // workspace-relative 경로
    $rel   = str_replace('\\', '/', $path);
    $brel  = str_replace('\\', '/', $base);
    if (strpos($rel, $brel) === 0) {
        $rel = ltrim(substr($rel, strlen($brel)), '/');
    }

    $hooks  = array();
    $lines  = explode("\n", $src);
    $n      = count($lines);
    $joined = implode("\n", $lines); // 전체 소스(멀티라인 매칭용)

    // 정규식: mdl_do_action / mdl_apply_filters 호출 찾기
    $pattern = '/mdl_(do_action|apply_filters)\s*\(\s*[\'"]([^\'"]+)[\'"]([^;]*)/s';
    $offset  = 0;
    while (preg_match($pattern, $joined, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $type        = ($m[1][0] === 'do_action') ? 'action' : 'filter';
        $name        = $m[2][0];
        $after_name  = trim($m[3][0], " \t\n\r,()");
        // 시그니처: 첫 줄만 (최대 200자)
        $sig_lines   = explode("\n", $after_name);
        $sig         = trim($sig_lines[0], " \t\n\r,;)");
        $sig         = mb_substr($sig, 0, 200);

        // 라인 번호 계산
        $pos  = $m[0][1];
        $line = substr_count(substr($joined, 0, $pos), "\n") + 1;

        $hooks[] = array(
            'type'  => $type,
            'name'  => $name,
            'sig'   => $sig,
            'file'  => $rel,
            'line'  => (int)$line,
        );

        $offset = $m[0][1] + strlen($m[0][0]);
    }

    return $hooks;
}

/* ----------------------------------------------------------
 * 메인 스캔 함수
 * ---------------------------------------------------------- */
function mdl_hook_registry_rescan() {
    global $g5;

    if (empty($g5['hook_registry_table'])) return array('error' => 'hook_registry_table 미정의');

    $tbl = $g5['hook_registry_table'];

    // 1) 기존 항목을 모두 obsolete 표시
    sql_query("UPDATE `{$tbl}` SET hr_obsolete = 1", false);

    $inserted = 0;
    $updated  = 0;
    $now      = date('Y-m-d H:i:s');

    // 2) 디렉토리·단일 파일 순회 (mdl_hook_scan_paths 는 head.php 같은 파일을 포함)
    foreach (_hr_scan_dirs() as $dir) {
        $files = is_file($dir) ? array($dir) : _hr_php_files($dir);
        foreach ($files as $path) {
            foreach (_hr_scan_file($path) as $h) {
                $name   = sql_escape_string($h['name']);
                $type   = sql_escape_string($h['type']);
                $scope  = sql_escape_string(_hr_scope($h['name']));
                $file   = sql_escape_string($h['file']);
                $line   = (int)$h['line'];
                $sig    = sql_escape_string($h['sig']);

                // INSERT ... ON DUPLICATE KEY UPDATE
                $res = sql_query("
                    INSERT INTO `{$tbl}`
                        (hr_name, hr_type, hr_scope, hr_file, hr_line, hr_param_signature, hr_first_seen, hr_last_seen, hr_obsolete)
                    VALUES
                        ('{$name}', '{$type}', '{$scope}', '{$file}', {$line}, '{$sig}', '{$now}', '{$now}', 0)
                    ON DUPLICATE KEY UPDATE
                        hr_type             = VALUES(hr_type),
                        hr_scope            = VALUES(hr_scope),
                        hr_param_signature  = VALUES(hr_param_signature),
                        hr_last_seen        = '{$now}',
                        hr_obsolete         = 0
                ", false);

                if ($res) {
                    if (function_exists('mysqli_affected_rows') && isset($g5['connect_db'])) {
                        $aff = @mysqli_affected_rows($g5['connect_db']);
                        if ($aff === 1) $inserted++;
                        elseif ($aff === 2) $updated++;
                    } else {
                        $inserted++;
                    }
                }
            }
        }
    }

    // obsolete 수 확인
    $row     = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$tbl}` WHERE hr_obsolete = 1");
    $obsolete = (int)($row['cnt'] ?? 0);

    return array(
        'inserted' => $inserted,
        'updated'  => $updated,
        'obsolete' => $obsolete,
    );
}

/* ----------------------------------------------------------
 * 조회 헬퍼
 *   $filter = [
 *     'name'     => 'partial',   // hr_name LIKE
 *     'type'     => 'action',    // action|filter|''
 *     'scope'    => 'bbs',       // bbs|general|''
 *     'obsolete' => 0,           // 0|1|'' ('' = 전체)
 *     'order'    => 'hr_name',
 *     'limit'    => 200,
 *     'offset'   => 0,
 *   ]
 * ---------------------------------------------------------- */
function mdl_hook_registry_search($filter = array()) {
    global $g5;

    if (empty($g5['hook_registry_table'])) return array();
    $tbl = $g5['hook_registry_table'];

    $where = array();

    if (!empty($filter['name'])) {
        $like = sql_escape_string('%'.$filter['name'].'%');
        $where[] = "hr_name LIKE '{$like}'";
    }
    if (!empty($filter['type']) && in_array($filter['type'], array('action','filter'), true)) {
        $t = sql_escape_string($filter['type']);
        $where[] = "hr_type = '{$t}'";
    }
    if (!empty($filter['scope']) && in_array($filter['scope'], array('general','bbs'), true)) {
        $s = sql_escape_string($filter['scope']);
        $where[] = "hr_scope = '{$s}'";
    }
    if (isset($filter['obsolete']) && $filter['obsolete'] !== '') {
        $where[] = "hr_obsolete = " . (int)$filter['obsolete'];
    }

    $cond  = $where ? 'WHERE '.implode(' AND ', $where) : '';
    $order = !empty($filter['order']) ? sql_escape_string($filter['order']) : 'hr_name';
    $limit = isset($filter['limit']) ? (int)$filter['limit'] : 200;
    $off   = isset($filter['offset']) ? (int)$filter['offset'] : 0;

    $sql = "SELECT * FROM `{$tbl}` {$cond} ORDER BY {$order} ASC LIMIT {$off},{$limit}";
    $res = sql_query($sql, false);
    if (!$res) return array();

    $rows = array();
    while ($row = sql_fetch_array($res)) $rows[] = $row;
    return $rows;
}

/* ----------------------------------------------------------
 * hr_doc 수동 저장
 * ---------------------------------------------------------- */
function mdl_hook_registry_doc($hr_id, $doc) {
    global $g5;
    if (empty($g5['hook_registry_table'])) return false;
    $tbl  = $g5['hook_registry_table'];
    $id   = (int)$hr_id;
    $d    = sql_escape_string($doc);
    return sql_query("UPDATE `{$tbl}` SET hr_doc = '{$d}' WHERE hr_id = {$id}", false);
}

/* ----------------------------------------------------------
 * 해당 훅을 모듈이 사용한다고 선언한 목록 (`mdl_module_hooks_used`)
 * ---------------------------------------------------------- */
function mdl_hook_registry_used_by($hook_name) {
    $modules = mdl_scan();
    $result  = array();
    foreach ($modules as $name => $meta) {
        $info = function_exists('mdl_module_hooks_used') ? mdl_module_hooks_used($name) : array('uses' => array());
        $all_uses = isset($info['uses']) && is_array($info['uses']) ? $info['uses'] : array();
        if (in_array($hook_name, $all_uses, true)) {
            $result[] = $name;
        }
    }
    return $result;
}
