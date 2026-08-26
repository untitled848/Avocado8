-- Wave10 #7: NPC 시기별 활성 매핑
-- 적용: phpMyAdmin 등에서 직접 실행
-- 정책: 매핑이 비어 있는 시기는 모든 NPC 표시 (fallback). 행이 1개라도 있으면 매핑된 NPC 만 표시.

DROP TABLE IF EXISTS `avo_character_npc_time`;
CREATE TABLE IF NOT EXISTS `avo_character_npc_time` (
  `ch_id` int(11) NOT NULL DEFAULT '0',
  `ti_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ch_id`,`ti_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
