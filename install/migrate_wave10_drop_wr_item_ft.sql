-- =============================================================================
-- migrate_wave10_drop_wr_item_ft.sql
-- avo8.0default Wave 10+ : wr_item_ft / wr_item_ft_log 컬럼 제거
--
-- 배경:
--   - 구 bbs_fortune_token_use 모듈은 운명토큰 사용을 위해 별도 컬럼
--     (wr_item_ft, wr_item_ft_log) 을 사용했다.
--   - Wave 10+ 통합 모듈 bbs_item_use_helas 는 wr_item_log 단일 컬럼만 사용한다.
--     (한 글당 일반 아이템/운명토큰 둘 중 하나만 사용 가능)
--   - 따라서 모든 게시판 write_table 에서 두 컬럼을 DROP 한다.
--
-- 안전장치:
--   - 컬럼이 존재하지 않는 게시판도 있을 수 있으므로 information_schema 로 점검 후 ALTER.
--   - 운영자 임의 백업이 끝났음을 전제로 한다.
-- =============================================================================

-- 1) 동적 DROP — write_* 테이블에서 wr_item_ft / wr_item_ft_log 컬럼 제거
SET @db := DATABASE();

-- wr_item_ft 컬럼 DROP
SELECT GROUP_CONCAT(
    CONCAT('ALTER TABLE `', TABLE_NAME, '` DROP COLUMN `wr_item_ft`')
    SEPARATOR '; '
) INTO @sql_drop_ft
  FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = @db
   AND TABLE_NAME LIKE 'avo_write_%'
   AND COLUMN_NAME = 'wr_item_ft';

SET @sql_drop_ft := IFNULL(@sql_drop_ft, 'SELECT 1');
PREPARE stmt FROM @sql_drop_ft;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- wr_item_ft_log 컬럼 DROP
SELECT GROUP_CONCAT(
    CONCAT('ALTER TABLE `', TABLE_NAME, '` DROP COLUMN `wr_item_ft_log`')
    SEPARATOR '; '
) INTO @sql_drop_ft_log
  FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = @db
   AND TABLE_NAME LIKE 'avo_write_%'
   AND COLUMN_NAME = 'wr_item_ft_log';

SET @sql_drop_ft_log := IFNULL(@sql_drop_ft_log, 'SELECT 1');
PREPARE stmt FROM @sql_drop_ft_log;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
