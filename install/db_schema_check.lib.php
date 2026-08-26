<?php
/**
 * 사이트 코어 DB 스키마 점검/보정 (멱등)
 *
 * 기준: install/gnuboard5.sql 의 CREATE TABLE
 * - 없는 테이블: CREATE TABLE IF NOT EXISTS
 * - 빠진 컬럼: ALTER TABLE … ADD COLUMN (AFTER 이전 컬럼)
 * - 빠진 인덱스: ALTER TABLE … ADD [UNIQUE] KEY / PRIMARY KEY
 * - 컬럼 DROP / 타입 변경 / INSERT 시드 는 하지 않음
 *
 * 관리자 페이지(adm/db_check.php) 및 CLI 점검에서 사용.
 */
if (!defined('_GNUBOARD_') && !defined('G5_DB_SCHEMA_CHECK_CLI')) {
    exit;
}

/**
 * 스키마 SQL 경로
 */
function db_schema_sql_path() {
    if (defined('G5_PATH')) {
        return G5_PATH.'/install/gnuboard5.sql';
    }
    return dirname(__FILE__).'/gnuboard5.sql';
}

/**
 * 테이블 접두어 (기본 avo_)
 */
function db_schema_table_prefix() {
    if (defined('G5_TABLE_PREFIX') && G5_TABLE_PREFIX !== '') {
        return G5_TABLE_PREFIX;
    }
    return 'avo_';
}

/**
 * SQL 안전 식별자
 */
function db_schema_ident($name) {
    return preg_replace('/[^a-zA-Z0-9_]/', '', (string)$name);
}

/**
 * mysqli 예외를 삼키는 조회/실행
 */
function db_schema_sql_fetch($sql) {
    try {
        return sql_fetch($sql, false);
    } catch (Throwable $e) {
        return null;
    }
}

function db_schema_sql_query($sql) {
    try {
        return sql_query($sql, false);
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * 주석 제거 (-- 라인 / # 라인). 문자열 안의 -- 는 CREATE 본문에 거의 없음.
 */
function db_schema_strip_comments($sql) {
    $lines = preg_split('/\r\n|\r|\n/', (string)$sql);
    $out = array();
    foreach ($lines as $ln) {
        $lt = ltrim($ln);
        if ($lt === '' || strpos($lt, '--') === 0 || strpos($lt, '#') === 0) {
            continue;
        }
        $out[] = $ln;
    }
    return implode("\n", $out);
}

/**
 * 괄호·따옴표를 고려해 최상위 콤마로 분할
 */
function db_schema_split_top_commas($body) {
    $parts = array();
    $buf = '';
    $depth = 0;
    $quote = '';
    $len = strlen($body);
    for ($i = 0; $i < $len; $i++) {
        $ch = $body[$i];
        if ($quote !== '') {
            $buf .= $ch;
            if ($ch === '\\' && $i + 1 < $len) {
                $buf .= $body[$i + 1];
                $i++;
                continue;
            }
            if ($ch === $quote) {
                $quote = '';
            }
            continue;
        }
        if ($ch === "'" || $ch === '"' || $ch === '`') {
            $quote = $ch;
            $buf .= $ch;
            continue;
        }
        if ($ch === '(') {
            $depth++;
            $buf .= $ch;
            continue;
        }
        if ($ch === ')') {
            $depth--;
            $buf .= $ch;
            continue;
        }
        if ($ch === ',' && $depth === 0) {
            $t = trim($buf);
            if ($t !== '') {
                $parts[] = $t;
            }
            $buf = '';
            continue;
        }
        $buf .= $ch;
    }
    $t = trim($buf);
    if ($t !== '') {
        $parts[] = $t;
    }
    return $parts;
}

/**
 * CREATE 본문 → columns / indexes
 *
 * @return array{columns: array<string,string>, indexes: array<int,array>}
 *   columns: col => definition (컬럼명 제외)
 *   indexes: list of [type => primary|unique|key, name => string, cols_sql => '(`a`,`b`)']
 */
function db_schema_parse_create_body($body) {
    $columns = array();
    $indexes = array();
    foreach (db_schema_split_top_commas($body) as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        if (preg_match('/^PRIMARY\s+KEY\s*(\(.+\))$/is', $part, $m)) {
            $indexes[] = array(
                'type' => 'primary',
                'name' => 'PRIMARY',
                'cols_sql' => $m[1],
            );
            continue;
        }
        if (preg_match('/^(?:UNIQUE\s+(?:KEY|INDEX)|UNIQUE)\s+(?:`([^`]+)`|([a-zA-Z0-9_]+))\s*(\(.+\))$/is', $part, $m)) {
            $indexes[] = array(
                'type' => 'unique',
                'name' => $m[1] !== '' ? $m[1] : $m[2],
                'cols_sql' => $m[3],
            );
            continue;
        }
        if (preg_match('/^(?:KEY|INDEX)\s+(?:`([^`]+)`|([a-zA-Z0-9_]+))\s*(\(.+\))$/is', $part, $m)) {
            $indexes[] = array(
                'type' => 'key',
                'name' => $m[1] !== '' ? $m[1] : $m[2],
                'cols_sql' => $m[3],
            );
            continue;
        }
        if (preg_match('/^CONSTRAINT\b/i', $part)) {
            continue;
        }
        if (preg_match('/^`([^`]+)`\s+(.+)$/s', $part, $m)) {
            $columns[$m[1]] = trim($m[2]);
            continue;
        }
        if (preg_match('/^([a-zA-Z0-9_]+)\s+(.+)$/s', $part, $m)) {
            $columns[$m[1]] = trim($m[2]);
        }
    }
    return array('columns' => $columns, 'indexes' => $indexes);
}

/**
 * CREATE TABLE 문에서 괄호 본문 추출 (시작 위치 = CREATE 키워드)
 *
 * @return array{table:string,body:string,suffix:string}|null
 */
function db_schema_extract_create_at($sql, $offset) {
    if (!preg_match('/\GCREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`([^`]+)`\s*\(/is', $sql, $m, 0, $offset)) {
        return null;
    }
    $table = $m[1];
    $open = $offset + strlen($m[0]) - 1; // '('
    $depth = 0;
    $quote = '';
    $len = strlen($sql);
    for ($i = $open; $i < $len; $i++) {
        $ch = $sql[$i];
        if ($quote !== '') {
            if ($ch === '\\' && $i + 1 < $len) {
                $i++;
                continue;
            }
            if ($ch === $quote) {
                $quote = '';
            }
            continue;
        }
        if ($ch === "'" || $ch === '"' || $ch === '`') {
            $quote = $ch;
            continue;
        }
        if ($ch === '(') {
            $depth++;
            continue;
        }
        if ($ch === ')') {
            $depth--;
            if ($depth === 0) {
                $body = substr($sql, $open + 1, $i - $open - 1);
                $j = $i + 1;
                while ($j < $len && ctype_space($sql[$j])) {
                    $j++;
                }
                $suffix = '';
                while ($j < $len && $sql[$j] !== ';') {
                    $suffix .= $sql[$j];
                    $j++;
                }
                return array(
                    'table' => $table,
                    'body' => $body,
                    'suffix' => trim($suffix),
                    'end' => $j,
                );
            }
        }
    }
    return null;
}

/**
 * gnuboard5.sql → 스펙 목록
 *
 * @return array<int,array{table:string,label:string,create:string,columns:array,indexes:array}>
 */
function db_schema_load_specs($sql_path = null, $prefix = null) {
    if ($sql_path === null) {
        $sql_path = db_schema_sql_path();
    }
    if ($prefix === null) {
        $prefix = db_schema_table_prefix();
    }
    if (!is_file($sql_path) || !is_readable($sql_path)) {
        return array();
    }
    $raw = file_get_contents($sql_path);
    if ($raw === false) {
        return array();
    }
    $sql = db_schema_strip_comments($raw);
    $sql = preg_replace('/`avo_([^`]+`)/', '`'.$prefix.'$1', $sql);

    $specs = array();
    $offset = 0;
    $len = strlen($sql);
    while ($offset < $len) {
        if (!preg_match('/CREATE\s+TABLE\b/is', $sql, $m, PREG_OFFSET_CAPTURE, $offset)) {
            break;
        }
        $start = (int)$m[0][1];
        $ex = db_schema_extract_create_at($sql, $start);
        if ($ex === null) {
            $offset = $start + 11;
            continue;
        }
        $parsed = db_schema_parse_create_body($ex['body']);
        $suffix = $ex['suffix'] !== '' ? ' '.$ex['suffix'] : '';
        $create = 'CREATE TABLE IF NOT EXISTS `'.$ex['table'].'` ('."\n"
            .$ex['body']."\n"
            .')'.$suffix;
        $short = $ex['table'];
        if (strpos($short, $prefix) === 0) {
            $short = substr($short, strlen($prefix));
        }
        $specs[] = array(
            'table' => $ex['table'],
            'label' => $short,
            'create' => $create,
            'columns' => $parsed['columns'],
            'indexes' => $parsed['indexes'],
        );
        $offset = isset($ex['end']) ? (int)$ex['end'] + 1 : $start + 11;
    }
    return $specs;
}

function db_schema_table_exists($table) {
    $t = db_schema_ident($table);
    if ($t === '') {
        return false;
    }
    $like = addcslashes($t, '%_\\');
    $row = db_schema_sql_fetch("SHOW TABLES LIKE '{$like}'");
    return !empty($row);
}

/**
 * @return array<string,true> lowercase column => true
 */
function db_schema_existing_columns($table) {
    $t = db_schema_ident($table);
    $out = array();
    if ($t === '' || !db_schema_table_exists($t)) {
        return $out;
    }
    $rs = db_schema_sql_query("SHOW COLUMNS FROM `{$t}`");
    if (!$rs) {
        return $out;
    }
    while ($row = sql_fetch_array($rs)) {
        if (!empty($row['Field'])) {
            $out[strtolower($row['Field'])] = true;
        }
    }
    return $out;
}

/**
 * @return array<string,true> Key_name => true (PRIMARY included)
 */
function db_schema_existing_indexes($table) {
    $t = db_schema_ident($table);
    $out = array();
    if ($t === '' || !db_schema_table_exists($t)) {
        return $out;
    }
    $rs = db_schema_sql_query("SHOW INDEX FROM `{$t}`");
    if (!$rs) {
        return $out;
    }
    while ($row = sql_fetch_array($rs)) {
        if (!empty($row['Key_name'])) {
            $out[$row['Key_name']] = true;
        }
    }
    return $out;
}

/**
 * 한 테이블 점검
 *
 * @return array{
 *   table:string,label:string,exists:bool,
 *   missing_columns:string[],missing_indexes:string[],
 *   ok:bool,status:string
 * }
 */
function db_schema_inspect($spec) {
    $table = $spec['table'];
    $label = isset($spec['label']) ? $spec['label'] : $table;
    $exists = db_schema_table_exists($table);
    $missing_cols = array();
    $missing_idx = array();

    if (!$exists) {
        return array(
            'table' => $table,
            'label' => $label,
            'exists' => false,
            'missing_columns' => array_keys($spec['columns']),
            'missing_indexes' => array_map(function ($ix) {
                return $ix['name'];
            }, $spec['indexes']),
            'ok' => false,
            'status' => '테이블 없음',
        );
    }

    $have_cols = db_schema_existing_columns($table);
    foreach ($spec['columns'] as $col => $_def) {
        if (!isset($have_cols[strtolower($col)])) {
            $missing_cols[] = $col;
        }
    }

    $have_idx = db_schema_existing_indexes($table);
    foreach ($spec['indexes'] as $ix) {
        $name = $ix['name'];
        if ($ix['type'] === 'primary') {
            if (!isset($have_idx['PRIMARY'])) {
                $missing_idx[] = 'PRIMARY';
            }
            continue;
        }
        if (!isset($have_idx[$name])) {
            $missing_idx[] = $name;
        }
    }

    $ok = empty($missing_cols) && empty($missing_idx);
    $bits = array();
    if ($missing_cols) {
        $bits[] = '컬럼 부족: '.implode(', ', $missing_cols);
    }
    if ($missing_idx) {
        $bits[] = '인덱스 부족: '.implode(', ', $missing_idx);
    }
    return array(
        'table' => $table,
        'label' => $label,
        'exists' => true,
        'missing_columns' => $missing_cols,
        'missing_indexes' => $missing_idx,
        'ok' => $ok,
        'status' => $ok ? '정상' : implode(' / ', $bits),
    );
}

/**
 * @return array{rows:array,all_ok:bool,missing_tables:int,drift:int}
 */
function db_schema_inspect_all($specs) {
    $rows = array();
    $all_ok = true;
    $missing_tables = 0;
    $drift = 0;
    foreach ($specs as $spec) {
        $row = db_schema_inspect($spec);
        $rows[] = $row;
        if (!$row['ok']) {
            $all_ok = false;
            if (!$row['exists']) {
                $missing_tables++;
            } else {
                $drift++;
            }
        }
    }
    return array(
        'rows' => $rows,
        'all_ok' => $all_ok,
        'missing_tables' => $missing_tables,
        'drift' => $drift,
    );
}

/**
 * 한 테이블 보정 (멱등)
 *
 * @return array{label:string,table:string,message:string,ok:bool}
 */
function db_schema_repair($spec) {
    $table = $spec['table'];
    $label = isset($spec['label']) ? $spec['label'] : $table;
    $was = db_schema_table_exists($table);

    db_schema_sql_query($spec['create']);
    $now = db_schema_table_exists($table);
    if (!$now) {
        return array(
            'label' => $label,
            'table' => $table,
            'message' => '생성 실패',
            'ok' => false,
        );
    }

    $added_cols = array();
    $added_idx = array();
    $have_cols = db_schema_existing_columns($table);
    $prev = '';
    foreach ($spec['columns'] as $col => $def) {
        $key = strtolower($col);
        if (!isset($have_cols[$key])) {
            $c = db_schema_ident($col);
            $sql = "ALTER TABLE `".db_schema_ident($table)."` ADD COLUMN `{$c}` {$def}";
            if ($prev !== '' && isset($have_cols[strtolower($prev)])) {
                $sql .= ' AFTER `'.db_schema_ident($prev).'`';
            }
            if (db_schema_sql_query($sql) !== false) {
                $added_cols[] = $col;
                $have_cols[$key] = true;
            }
        }
        $prev = $col;
    }

    $have_idx = db_schema_existing_indexes($table);
    $t = db_schema_ident($table);
    foreach ($spec['indexes'] as $ix) {
        if ($ix['type'] === 'primary') {
            if (isset($have_idx['PRIMARY'])) {
                continue;
            }
            if (db_schema_sql_query("ALTER TABLE `{$t}` ADD PRIMARY KEY {$ix['cols_sql']}") !== false) {
                $added_idx[] = 'PRIMARY';
                $have_idx['PRIMARY'] = true;
            }
            continue;
        }
        $name = $ix['name'];
        if (isset($have_idx[$name])) {
            continue;
        }
        $n = db_schema_ident($name);
        if ($n === '') {
            continue;
        }
        if ($ix['type'] === 'unique') {
            $sql = "ALTER TABLE `{$t}` ADD UNIQUE KEY `{$n}` {$ix['cols_sql']}";
        } else {
            $sql = "ALTER TABLE `{$t}` ADD KEY `{$n}` {$ix['cols_sql']}";
        }
        if (db_schema_sql_query($sql) !== false) {
            $added_idx[] = $name;
            $have_idx[$name] = true;
        }
    }

    $msg = !$was ? '생성' : '확인';
    if ($added_cols) {
        $msg .= ' / 컬럼 추가: '.implode(', ', $added_cols);
    }
    if ($added_idx) {
        $msg .= ' / 인덱스 추가: '.implode(', ', $added_idx);
    }
    if ($was && !$added_cols && !$added_idx) {
        $msg = '정상 (변경 없음)';
    }

    $after = db_schema_inspect($spec);
    return array(
        'label' => $label,
        'table' => $table,
        'message' => $msg.(!$after['ok'] ? ' / 잔여: '.$after['status'] : ''),
        'ok' => $after['ok'],
    );
}

/**
 * @return array{log:array,all_ok:bool}
 */
function db_schema_repair_all($specs) {
    $log = array();
    $all_ok = true;
    foreach ($specs as $spec) {
        $r = db_schema_repair($spec);
        $log[] = $r;
        if (empty($r['ok'])) {
            $all_ok = false;
        }
    }
    return array('log' => $log, 'all_ok' => $all_ok);
}
