<?php
if (!defined('_GNUBOARD_')) exit;
global $g5;

require_once __DIR__.'/lib.php';
if (!function_exists('mdl_db_column_exists') && defined('G5_PATH') && is_file(G5_PATH.'/module/_db.php')) {
    include_once G5_PATH.'/module/_db.php';
}

// 구 모듈명(bbs_map) KV 설정 이관 — 신규 키가 없을 때만 복사
$_old_settings = function_exists('mdl_setting_all') ? mdl_setting_all('bbs_map') : array();
if (!empty($_old_settings)) {
    foreach ($_old_settings as $_k => $_v) {
        if ($_k === 'map_image' && is_string($_v)) {
            $_v = str_replace('/module/bbs_map/', '/module/bbs_action_map/', $_v);
        }
        if (mdl_setting_get('bbs_action_map', $_k, null) === null) {
            mdl_setting_set('bbs_action_map', $_k, $_v);
        }
    }
    mdl_setting_drop_module('bbs_map');
}

// 모듈 설정 — explore_mode (없을 때만 등록)
if (mdl_setting_get('bbs_action_map', 'explore_mode', null) === null) {
    mdl_setting_set('bbs_action_map', 'explore_mode', 'minus');
    mdl_setting_set('bbs_action_map', 'info_panel', '1');
    mdl_setting_set('bbs_action_map', 'map_image', '');
    mdl_setting_set('bbs_action_map', 'map_image_width', '');
    mdl_setting_set('bbs_action_map', 'map_image_height', '');
}

// 지역 탐색 횟수 카운터 등록
if (function_exists('reset_setting_register') && function_exists('reset_setting_get')) {
    if (!reset_setting_get('character_map_explore')) {
        reset_setting_register('character_map_explore', array(
            'label'       => '지역 탐색 횟수',
            'period'      => 'daily',
            'default'     => 0,
            'plus'        => 0,
            'max'         => 5,
            'min'         => 0,
            'type'        => 'character',
            'setting_url' => G5_MDL_ADMIN_URL.'/module.php?m=bbs_action_map&p=setting',
        ));
    }

    if (!empty($g5['character_table']) && !empty($g5['reset_manager_table'])) {
        $_set = reset_setting_get('character_map_explore');
        if (!empty($_set)) {
            $_mode = mdl_setting_get('bbs_action_map', 'explore_mode', 'minus');
            $_init = ($_mode === 'plus') ? (int)$_set['rs_plus'] : 0;
            sql_query("INSERT IGNORE INTO `{$g5['reset_manager_table']}` (rs_id, rm_subkey, rm_value)
                       SELECT ".(int)$_set['rs_id'].", ch_id, {$_init} FROM `{$g5['character_table']}`", false);
        }
    }

    if (!empty($g5['reset_setting_table'])) {
        sql_query("UPDATE `{$g5['reset_setting_table']}`
                   SET setting_url = REPLACE(setting_url, 'm=bbs_map', 'm=bbs_action_map')
                   WHERE rs_name = 'character_map_explore'", false);
    }
}

// 캐릭터 위치 컬럼
if (!empty($g5['character_table'])) {
    mdl_db_add_column($g5['character_table'], 'ma_id', "int(11) NOT NULL DEFAULT '0'");
}

// 게시판 write 테이블 — 맵/몬스터 컬럼
mdl_db_add_write_column('wr_log', "text NOT NULL");
mdl_db_add_write_column('ma_id', "int(11) NOT NULL DEFAULT '0'");
mdl_db_add_write_column('wr_mon_state', "varchar(255) NOT NULL DEFAULT ''");
mdl_db_add_write_column('wr_mon_hp', "int(11) NOT NULL DEFAULT '0'");
mdl_db_add_write_column('wr_mon_now_hp', "int(11) NOT NULL DEFAULT '0'");
mdl_db_add_write_column('wr_mon_attack', "int(11) NOT NULL DEFAULT '0'");

// 맵 테이블
@sql_query("CREATE TABLE IF NOT EXISTS `{$g5['module_map_table']}` (
    `ma_id` int(11) NOT NULL AUTO_INCREMENT,
    `ma_name` varchar(255) NOT NULL DEFAULT '',
    `ma_thumb` text NOT NULL,
    `ma_text` text NOT NULL,
    `ma_use` int(11) NOT NULL DEFAULT '1',
    `ma_x` int(11) NOT NULL DEFAULT '0',
    `ma_y` int(11) NOT NULL DEFAULT '0',
    `ma_width` int(11) NOT NULL DEFAULT '0',
    `ma_height` int(11) NOT NULL DEFAULT '0',
    `ma_time` varchar(255) NOT NULL DEFAULT '',
    `dpt_ma_ids` varchar(255) NOT NULL DEFAULT '',
    PRIMARY KEY (`ma_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", false);

// 캐릭터 위치 보조 테이블
@sql_query("CREATE TABLE IF NOT EXISTS `{$g5['module_map_character_table']}` (
    `mc_id` int(11) NOT NULL AUTO_INCREMENT,
    `ch_id` int(11) NOT NULL DEFAULT '0',
    `ma_id` int(11) NOT NULL DEFAULT '0',
    `ch_name` varchar(255) NOT NULL DEFAULT '',
    PRIMARY KEY (`mc_id`),
    UNIQUE KEY `ch_id` (`ch_id`),
    KEY `ma_id` (`ma_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", false);

// 이벤트 테이블 (AvocadoEdition.MapManager map_event 스키마)
@sql_query("CREATE TABLE IF NOT EXISTS `{$g5['module_map_event_table']}` (
    `me_id` int(11) NOT NULL AUTO_INCREMENT,
    `ma_id` int(11) NOT NULL DEFAULT '0',
    `me_type` varchar(255) NOT NULL DEFAULT '',
    `me_title` varchar(255) NOT NULL DEFAULT '',
    `me_img` varchar(255) NOT NULL DEFAULT '',
    `me_content` text NOT NULL,
    `me_get_item` int(11) NOT NULL DEFAULT '0',
    `me_get_money` int(11) NOT NULL DEFAULT '0',
    `me_move_map` int(11) NOT NULL DEFAULT '0',
    `me_get_hp` int(11) NOT NULL DEFAULT '0',
    `me_mon_hp` int(11) NOT NULL DEFAULT '0',
    `me_mon_attack` int(11) NOT NULL DEFAULT '0',
    `me_per_s` int(11) NOT NULL DEFAULT '0',
    `me_per_e` int(11) NOT NULL DEFAULT '0',
    `me_replay_cnt` int(11) NOT NULL DEFAULT '0',
    `me_now_cnt` int(11) NOT NULL DEFAULT '0',
    `me_use` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`me_id`),
    KEY `ma_id` (`ma_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", false);

// 기존(깨진) 이벤트 테이블이 있어도 MapManager 컬럼을 보강
$ev = $g5['module_map_event_table'];
mdl_db_add_column($ev, 'me_type', "varchar(255) NOT NULL DEFAULT ''");
mdl_db_add_column($ev, 'me_title', "varchar(255) NOT NULL DEFAULT ''");
mdl_db_add_column($ev, 'me_img', "varchar(255) NOT NULL DEFAULT ''");
mdl_db_add_column($ev, 'me_content', "text NOT NULL");
mdl_db_add_column($ev, 'me_get_item', "int(11) NOT NULL DEFAULT '0'");
mdl_db_add_column($ev, 'me_get_money', "int(11) NOT NULL DEFAULT '0'");
mdl_db_add_column($ev, 'me_move_map', "int(11) NOT NULL DEFAULT '0'");
mdl_db_add_column($ev, 'me_get_hp', "int(11) NOT NULL DEFAULT '0'");
mdl_db_add_column($ev, 'me_mon_hp', "int(11) NOT NULL DEFAULT '0'");
mdl_db_add_column($ev, 'me_mon_attack', "int(11) NOT NULL DEFAULT '0'");
mdl_db_add_column($ev, 'me_per_s', "int(11) NOT NULL DEFAULT '0'");
mdl_db_add_column($ev, 'me_per_e', "int(11) NOT NULL DEFAULT '0'");
mdl_db_add_column($ev, 'me_replay_cnt', "int(11) NOT NULL DEFAULT '0'");
mdl_db_add_column($ev, 'me_now_cnt', "int(11) NOT NULL DEFAULT '0'");
mdl_db_add_column($ev, 'me_use', "int(11) NOT NULL DEFAULT '0'");

$map_data_dir = G5_PATH.'/module/bbs_action_map/data';
if (!is_dir($map_data_dir)) {
    @mkdir($map_data_dir, 0755, true);
}
