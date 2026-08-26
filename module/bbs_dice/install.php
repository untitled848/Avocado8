<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * bbs_dice install (v2)
 *
 * - wr_dice (varchar 통합 컬럼) 추가
 * - 기존 wr_dice1 / wr_dice2 데이터가 있으면 wr_dice 로 마이그레이션
 *   (wr_dice1, wr_dice2 컬럼은 호환을 위해 즉시 삭제하지 않음 — 운영자가 안전 확인 후 수동 제거 권장)
 */

require_once __DIR__ . '/lib.php';

mdl_db_add_write_column('wr_dice', "varchar(255) NOT NULL DEFAULT ''");

// 기존 1/2 컬럼 데이터 마이그레이션
mdl_db_each_write_table(function ($wt) {
    $has1 = mdl_db_column_exists($wt, 'wr_dice1');
    $has2 = mdl_db_column_exists($wt, 'wr_dice2');
    $has  = mdl_db_column_exists($wt, 'wr_dice');
    if (!$has || (!$has1 && !$has2)) return;

    if ($has1 && $has2) {
        sql_query("UPDATE `{$wt}` SET wr_dice = CONCAT(wr_dice1, ',', wr_dice2) WHERE (wr_dice IS NULL OR wr_dice = '') AND (wr_dice1 > 0 OR wr_dice2 > 0)", false);
    } else if ($has1) {
        sql_query("UPDATE `{$wt}` SET wr_dice = CAST(wr_dice1 AS CHAR) WHERE (wr_dice IS NULL OR wr_dice = '') AND wr_dice1 > 0", false);
    } else if ($has2) {
        sql_query("UPDATE `{$wt}` SET wr_dice = CAST(wr_dice2 AS CHAR) WHERE (wr_dice IS NULL OR wr_dice = '') AND wr_dice2 > 0", false);
    }
});
