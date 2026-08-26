-- =====================================================================
-- avo8.0default Wave 10 마이그레이션 — 시기별 side/class 시스템
-- 적용 대상: 기존 DB (신규 설치는 install/gnuboard5.sql 사용)
-- 실행: phpMyAdmin 또는 mysql CLI 에서 1회 실행
-- =====================================================================

-- 1) character_time_value 에 ctv_side / ctv_class 컬럼 추가
ALTER TABLE `avo_character_time_value`
    ADD COLUMN `ctv_side`  varchar(200) NOT NULL DEFAULT '' AFTER `ctv_body`,
    ADD COLUMN `ctv_class` varchar(200) NOT NULL DEFAULT '' AFTER `ctv_side`;

-- 2) 시기별 side 활성 매핑 테이블
CREATE TABLE IF NOT EXISTS `avo_character_side_time` (
    `si_id` int(11) NOT NULL DEFAULT '0',
    `ti_id` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`si_id`,`ti_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) 시기별 class 활성 매핑 테이블
CREATE TABLE IF NOT EXISTS `avo_character_class_time` (
    `cl_id` int(11) NOT NULL DEFAULT '0',
    `ti_id` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`cl_id`,`ti_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 시드 정책 ⓘ
--   - 매핑 테이블이 비어있으면 헬퍼 함수가 "전체 시기에 활성" 으로 폴백 처리.
--   - 따라서 별도 시드 INSERT 없이 곧바로 안전.
--   - 운영자가 adm/side_list.php / adm/class_list.php 에서 시기별로 토글 시점부터
--     매핑 행이 채워짐.
-- =====================================================================
