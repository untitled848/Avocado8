<?php
/**
 * Module DB helpers — install.php / upgrade.php 에서 사용.
 *
 *   mdl_db_table_exists($table) : 테이블 존재 여부
 *   mdl_db_column_exists($table, $column) : 컬럼 존재 여부 (테이블 없으면 false)
 *   mdl_db_add_column($table, $column, $definition) : 없으면 ADD COLUMN (테이블 없으면 no-op)
 *   mdl_db_each_write_table(callable $fn) : 모든 게시판 write 테이블에 대해 콜백 실행
 *   mdl_db_add_write_column($column, $definition) : 모든 write 테이블에 컬럼 추가
 *
 * PHP 8.1+ 기본 mysqli 리포트(MYSQLI_REPORT_ERROR|STRICT) 에서는
 * 없는 테이블에 SHOW COLUMNS / ALTER 하면 $error=false 와 @ 를 무시하고
 * mysqli_sql_exception 이 난다. 헬퍼는 예외를 삼키고 false 를 반환한다.
 */
if (!defined('_GNUBOARD_')) exit;

function mdl_db_ident($name) {
    return preg_replace('/[^a-zA-Z0-9_]/', '', (string)$name);
}

function mdl_db_sql_fetch($sql) {
    try {
        return sql_fetch($sql, false);
    } catch (Throwable $e) {
        return null;
    }
}

function mdl_db_sql_query($sql) {
    try {
        return sql_query($sql, false);
    } catch (Throwable $e) {
        return false;
    }
}

function mdl_db_table_exists($table) {
    $t = mdl_db_ident($table);
    if ($t === '') return false;
    $like = addcslashes($t, '%_\\');
    $row = mdl_db_sql_fetch("SHOW TABLES LIKE '{$like}'");
    return !empty($row);
}

function mdl_db_column_exists($table, $column) {
    $t = mdl_db_ident($table);
    $c = mdl_db_ident($column);
    if ($t === '' || $c === '') return false;
    if (!mdl_db_table_exists($t)) return false;
    $like = addcslashes($c, '%_\\');
    $row = mdl_db_sql_fetch("SHOW COLUMNS FROM `{$t}` LIKE '{$like}'");
    return !empty($row);
}

function mdl_db_add_column($table, $column, $definition) {
    if (mdl_db_column_exists($table, $column)) return false;
    $t = mdl_db_ident($table);
    $c = mdl_db_ident($column);
    if ($t === '' || $c === '') return false;
    if (!mdl_db_table_exists($t)) return false;
    mdl_db_sql_query("ALTER TABLE `{$t}` ADD COLUMN `{$c}` {$definition}");
    return true;
}

function mdl_db_each_write_table($fn) {
    if (!is_callable($fn)) return 0;
    $g5 = $GLOBALS['g5'];
    $count = 0;

    // 게시판 write 테이블
    $rs = sql_query("select bo_table from `{$g5['board_table']}`", false);
    if ($rs) {
        while ($row = sql_fetch_array($rs)) {
            $write_table = $g5['write_prefix'].$row['bo_table'];
            $fn($write_table, $row['bo_table']);
            $count++;
        }
    }

    // 타임라인 write 테이블 (board_scope 모듈 공용).
    // 별칭은 timeline/_functions.php 에 있다. 모듈 install 은 common+_loader 만
    // 타므로 여기서 채우지 않으면 게시판만 ALTER 되고 타임라인 컬럼이 빠진다.
    if (empty($g5['timeline_table'])) {
        $g5['timeline_table'] = G5_TABLE_PREFIX.'timeline';
        $GLOBALS['g5']['timeline_table'] = $g5['timeline_table'];
    }
    if (empty($g5['timeline_write_prefix'])) {
        $g5['timeline_write_prefix'] = G5_TABLE_PREFIX.'timeline_';
        $GLOBALS['g5']['timeline_write_prefix'] = $g5['timeline_write_prefix'];
    }
    if (!empty($g5['timeline_table']) && !empty($g5['timeline_write_prefix'])) {
        $trs = sql_query("select tl_table from `{$g5['timeline_table']}`", false);
        if ($trs) {
            while ($trow = sql_fetch_array($trs)) {
                $tl = isset($trow['tl_table']) ? $trow['tl_table'] : '';
                if ($tl === '') continue;
                $write_table = $g5['timeline_write_prefix'].$tl;
                $fn($write_table, $tl);
                $count++;
            }
        }
    }

    return $count;
}

function mdl_db_add_write_column($column, $definition) {
    return mdl_db_each_write_table(function ($write_table) use ($column, $definition) {
        mdl_db_add_column($write_table, $column, $definition);
    });
}
