<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * bbs_item_use(=inventory) install — wr_item, wr_item_log 컬럼을 모든 게시판 write 테이블에 보장.
 */

mdl_db_add_write_column('wr_item',     "int(11) NOT NULL DEFAULT '0'");
mdl_db_add_write_column('wr_item_log', "varchar(255) NOT NULL DEFAULT ''");
