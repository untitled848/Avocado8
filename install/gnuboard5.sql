
-- --------------------------------------------------------
--
-- Table structure for table `avo_board`
--

DROP TABLE IF EXISTS `avo_board`;
CREATE TABLE IF NOT EXISTS `avo_board` (
  `bo_table` varchar(20) NOT NULL DEFAULT '',
  `bo_type` varchar(20) NOT NULL DEFAULT '',
  `gr_id` varchar(200) NOT NULL DEFAULT '',
  `bo_subject` varchar(200) NOT NULL DEFAULT '',
  `bo_mobile_subject` varchar(200) NOT NULL DEFAULT '',
  `bo_device` enum('both','pc','mobile') NOT NULL DEFAULT 'both',
  `bo_admin` varchar(200) NOT NULL DEFAULT '',
  `bo_list_level` tinyint(4) NOT NULL DEFAULT '0',
  `bo_read_level` tinyint(4) NOT NULL DEFAULT '0',
  `bo_write_level` tinyint(4) NOT NULL DEFAULT '0',
  `bo_reply_level` tinyint(4) NOT NULL DEFAULT '0',
  `bo_comment_level` tinyint(4) NOT NULL DEFAULT '0',
  `bo_upload_level` tinyint(4) NOT NULL DEFAULT '0',
  `bo_download_level` tinyint(4) NOT NULL DEFAULT '0',
  `bo_html_level` tinyint(4) NOT NULL DEFAULT '0',
  `bo_link_level` tinyint(4) NOT NULL DEFAULT '0',
  `bo_count_delete` tinyint(4) NOT NULL DEFAULT '0',
  `bo_count_modify` tinyint(4) NOT NULL DEFAULT '0',
  `bo_read_point` int(11) NOT NULL DEFAULT '0',
  `bo_write_point` int(11) NOT NULL DEFAULT '0',
  `bo_comment_point` int(11) NOT NULL DEFAULT '0',
  `bo_download_point` int(11) NOT NULL DEFAULT '0',
  `bo_use_category` tinyint(4) NOT NULL DEFAULT '0',
  `bo_category_list` text NOT NULL,
  `bo_use_sideview` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_file_content` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_secret` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_emoticon` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_dhtml_editor` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_rss_view` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_good` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_nogood` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_name` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_signature` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_ip_view` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_list_view` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_list_file` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_list_content` tinyint(4) NOT NULL DEFAULT '0',
  `bo_table_width` int(11) NOT NULL DEFAULT '0',
  `bo_subject_len` int(11) NOT NULL DEFAULT '0',
  `bo_mobile_subject_len` int(11) NOT NULL DEFAULT '0',
  `bo_page_rows` int(11) NOT NULL DEFAULT '0',
  `bo_mobile_page_rows` int(11) NOT NULL DEFAULT '0',
  `bo_new` int(11) NOT NULL DEFAULT '0',
  `bo_hot` int(11) NOT NULL DEFAULT '0',
  `bo_image_width` int(11) NOT NULL DEFAULT '0',
  `bo_skin` varchar(200) NOT NULL DEFAULT '',
  `bo_mobile_skin` varchar(200) NOT NULL DEFAULT '',
  `bo_include_head` varchar(200) NOT NULL DEFAULT '',
  `bo_include_tail` varchar(200) NOT NULL DEFAULT '',
  `bo_content_head` text NOT NULL,
  `bo_mobile_content_head` text NOT NULL,
  `bo_content_tail` text NOT NULL,
  `bo_mobile_content_tail` text NOT NULL,
  `bo_insert_content` text NOT NULL,
  `bo_gallery_cols` int(11) NOT NULL DEFAULT '0',
  `bo_gallery_width` int(11) NOT NULL DEFAULT '0',
  `bo_gallery_height` int(11) NOT NULL DEFAULT '0',
  `bo_mobile_gallery_width` int(11) NOT NULL DEFAULT '0',
  `bo_mobile_gallery_height` int(11) NOT NULL DEFAULT '0',
  `bo_upload_size` int(11) NOT NULL DEFAULT '0',
  `bo_reply_order` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_search` tinyint(4) NOT NULL DEFAULT '0',
  `bo_order` int(11) NOT NULL DEFAULT '0',
  `bo_count_write` int(11) NOT NULL DEFAULT '0',
  `bo_count_comment` int(11) NOT NULL DEFAULT '0',
  `bo_write_min` int(11) NOT NULL DEFAULT '0',
  `bo_write_max` int(11) NOT NULL DEFAULT '0',
  `bo_comment_min` int(11) NOT NULL DEFAULT '0',
  `bo_comment_max` int(11) NOT NULL DEFAULT '0',
  `bo_comment_limit_count` int(11) NOT NULL DEFAULT '0',
  `bo_comment_limit_time`  int(11) NOT NULL DEFAULT '0',
  `bo_comment_limit_text`  int(11) NOT NULL DEFAULT '0',
  `bo_comment_limit_text_exc` varchar(200) NOT NULL DEFAULT 'ㅋ,ㅠ,ㅎ,ㅜ,.,!,?,~',
  `bo_write_limit_text` int(11) NOT NULL DEFAULT '0',
  `bo_notice` text NOT NULL,
  `bo_upload_count` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_email` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_cert` enum('','cert','adult','hp-cert','hp-adult') NOT NULL DEFAULT '',
  `bo_use_sns` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_chick` tinyint(4) NOT NULL DEFAULT '0',
  `bo_use_noname` tinyint(4) NOT NULL DEFAULT '0',
  `bo_sort_field` varchar(200) NOT NULL DEFAULT '',
  `bo_1_subj` varchar(200) NOT NULL DEFAULT '',
  `bo_2_subj` varchar(200) NOT NULL DEFAULT '',
  `bo_3_subj` varchar(200) NOT NULL DEFAULT '',
  `bo_4_subj` varchar(200) NOT NULL DEFAULT '',
  `bo_5_subj` varchar(200) NOT NULL DEFAULT '',
  `bo_6_subj` varchar(200) NOT NULL DEFAULT '',
  `bo_7_subj` varchar(200) NOT NULL DEFAULT '',
  `bo_8_subj` varchar(200) NOT NULL DEFAULT '',
  `bo_9_subj` varchar(200) NOT NULL DEFAULT '',
  `bo_10_subj` varchar(200) NOT NULL DEFAULT '',
  `bo_1` varchar(200) NOT NULL DEFAULT '',
  `bo_2` varchar(200) NOT NULL DEFAULT '',
  `bo_3` varchar(200) NOT NULL DEFAULT '',
  `bo_4` varchar(200) NOT NULL DEFAULT '',
  `bo_5` varchar(200) NOT NULL DEFAULT '',
  `bo_6` varchar(200) NOT NULL DEFAULT '',
  `bo_7` varchar(200) NOT NULL DEFAULT '',
  `bo_8` varchar(200) NOT NULL DEFAULT '',
  `bo_9` varchar(200) NOT NULL DEFAULT '',
  `bo_10` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`bo_table`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avo_board_file`
--

DROP TABLE IF EXISTS `avo_board_file`;
CREATE TABLE IF NOT EXISTS `avo_board_file` (
  `bo_table` varchar(20) NOT NULL default '',
  `wr_id` int(11) NOT NULL default '0',
  `bf_no` int(11) NOT NULL default '0',
  `bf_source` varchar(200) NOT NULL default '',
  `bf_file` varchar(200) NOT NULL default '',
  `bf_download` int(11) NOT NULL DEFAULT '0',
  `bf_content` text NOT NULL,
  `bf_filesize` int(11) NOT NULL default '0',
  `bf_width` int(11) NOT NULL default '0',
  `bf_height` smallint(6) NOT NULL default '0',
  `bf_type` tinyint(4) NOT NULL default '0',
  `bf_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`bo_table`,`wr_id`,`bf_no`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avo_board_good`
--

DROP TABLE IF EXISTS `avo_board_good`;
CREATE TABLE IF NOT EXISTS `avo_board_good` (
  `bg_id` int(11) NOT NULL auto_increment,
  `bo_table` varchar(20) NOT NULL default '',
  `wr_id` int(11) NOT NULL default '0',
  `mb_id` varchar(20) NOT NULL default '',
  `bg_flag` varchar(200) NOT NULL default '',
  `bg_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`bg_id`),
  UNIQUE KEY `fkey1` (`bo_table`,`wr_id`,`mb_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avo_board_new`
--

DROP TABLE IF EXISTS `avo_board_new`;
CREATE TABLE IF NOT EXISTS `avo_board_new` (
  `bn_id` int(11) NOT NULL auto_increment,
  `bo_table` varchar(20) NOT NULL default '',
  `wr_id` int(11) NOT NULL default '0',
  `ca_name` varchar(200) NOT NULL default '',
  `wr_parent` int(11) NOT NULL default '0',
  `bn_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  `mb_id` varchar(20) NOT NULL default '',
  PRIMARY KEY  (`bn_id`),
  KEY `mb_id` (`mb_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avo_config`
--

DROP TABLE IF EXISTS `avo_config`;
CREATE TABLE IF NOT EXISTS `avo_config` (
  `cf_title` varchar(200) NOT NULL DEFAULT '',
  `cf_theme` varchar(200) NOT NULL DEFAULT '',
  `cf_admin` varchar(200) NOT NULL DEFAULT '',
  `cf_add_script` text NOT NULL,
  `cf_use_point` tinyint(4) NOT NULL DEFAULT '1',
  `cf_login_point` int(11) NOT NULL DEFAULT '0',
  `cf_cut_name` tinyint(4) NOT NULL DEFAULT '15',
  `cf_write_pages` int(11) NOT NULL DEFAULT '10',
  `cf_mobile_pages` int(11) NOT NULL DEFAULT '5',
  `cf_link_target` varchar(200) NOT NULL DEFAULT '_blank',
  `cf_delay_sec` int(11) NOT NULL DEFAULT '30',
  `cf_filter` text NOT NULL,
  `cf_possible_ip` text NOT NULL,
  `cf_intercept_ip` text NOT NULL,
  `cf_register_level` tinyint(4) NOT NULL DEFAULT '2',
  `cf_register_point` int(11) NOT NULL DEFAULT '0',
  `cf_leave_day` int(11) NOT NULL DEFAULT '30',
  `cf_search_part` int(11) NOT NULL DEFAULT '10000',
  `cf_prohibit_id` text NOT NULL,
  `cf_memo_del` int(11) NOT NULL DEFAULT '180',
  `cf_use_memo_emoticon` tinyint(4) NOT NULL DEFAULT '0',
  `cf_emoticon_apply_mode` varchar(16) NOT NULL DEFAULT 'disabled',
  `cf_optimize_date` date NOT NULL default '0000-00-00',
  `cf_image_extension` varchar(200) NOT NULL DEFAULT 'gif|jpg|jpeg|png',
  `cf_movie_extension` varchar(200) NOT NULL DEFAULT 'asx|asf|wmv|wma|mpg|mpeg|mov|avi|mp3',
  `cf_page_rows` int(11) NOT NULL DEFAULT '15',
  `cf_mobile_page_rows` int(11) NOT NULL DEFAULT '15',
  `cf_stipulation` text NOT NULL,
  `cf_privacy` text NOT NULL,
  `cf_editor` varchar(200) NOT NULL DEFAULT 'smarteditor2',
  `cf_bgm` varchar(200) NOT NULL DEFAULT '',
  `cf_open_scope` varchar(10) NOT NULL DEFAULT 'all',
  `cf_menu_text` text NOT NULL,
  `cf_mobile_menu_text` text NOT NULL,
  `cf_side_title` varchar(200) NOT NULL DEFAULT '소속',
  `cf_class_title` varchar(200) NOT NULL DEFAULT '종족',
  `cf_shop_category` varchar(200) NOT NULL DEFAULT '일반||이벤트',
  `cf_notice_category` varchar(255) NOT NULL DEFAULT '',
  `cf_item_category` varchar(200) NOT NULL DEFAULT '일반||프로필수정||아이템추가||스탯회복',
  `cf_shop_page_rows`    int(11)      NOT NULL DEFAULT '10',
  `cf_shop_grid_pc`      int(11)      NOT NULL DEFAULT '0',
  `cf_shop_grid_mode`    varchar(16)  NOT NULL DEFAULT 'auto-fill',
  `cf_shop_grid_item_w`  int(11)      NOT NULL DEFAULT '50',
  `cf_shop_grid_item_h`  int(11)      NOT NULL DEFAULT '50',
  `cf_shop_npc`          varchar(200) NOT NULL DEFAULT '',
  `cf_site_descript` varchar(200) NOT NULL DEFAULT '',
  `cf_site_img` varchar(200) NOT NULL DEFAULT '',
  `cf_favicon` varchar(200) NOT NULL DEFAULT '',
  `cf_search_count` int(11) NOT NULL DEFAULT '5',
  `cf_status_point` int(11) NOT NULL DEFAULT '0',
  `cf_money` varchar(200) NOT NULL DEFAULT '화폐',
  `cf_money_pice` varchar(200) NOT NULL DEFAULT '원',
  `cf_exp_name` varchar(200) NOT NULL DEFAULT '경험치',
  `cf_exp_pice` varchar(200) NOT NULL DEFAULT 'exp',
  `cf_rank_name` varchar(200) NOT NULL DEFAULT '랭킹',
  `cf_profile_group` text NOT NULL,
  `cf_add_fonts` text NOT NULL,
  `cf_time` int(11) NOT NULL DEFAULT '1',
  `cf_preset_id` int(11) NOT NULL DEFAULT '0',
  `cf_perm_fallback_url` varchar(200) NOT NULL DEFAULT '',
  `cf_character_count` int(11) NOT NULL DEFAULT '1',
  `cf_character_edit_admin_only` tinyint(1) NOT NULL DEFAULT '0',
  `cf_perm_ready` tinyint(1) NOT NULL DEFAULT '1',
  `cf_perm_member` tinyint(1) NOT NULL DEFAULT '1',
  `cf_perm_memo` tinyint(1) NOT NULL DEFAULT '1',
  `cf_perm_shop` tinyint(1) NOT NULL DEFAULT '1',
  `cf_call_interval` int(11) NOT NULL DEFAULT '30',
  `cf_call_index_rows` int(11) NOT NULL DEFAULT '5',
  `cf_call_page_rows` int(11) NOT NULL DEFAULT '20',
  `cf_register_nick_min`     int(11) NOT NULL DEFAULT '2',
  `cf_register_nick_max`     int(11) NOT NULL DEFAULT '5',
  `cf_register_nick_filter`  varchar(20) NOT NULL DEFAULT '',
  `cf_register_birth_mode`   varchar(20) NOT NULL DEFAULT 'year_only',
  `cf_register_extra_fields` text NOT NULL,
  `cf_register_help`         text NOT NULL,
  `cf_member_thumb_w`    smallint(5) unsigned NOT NULL DEFAULT '120',
  `cf_member_thumb_h`    smallint(5) unsigned NOT NULL DEFAULT '160',
  `cf_member_grid_mode`  varchar(20) NOT NULL DEFAULT 'auto-fill',
  `cf_member_grid_count` tinyint(3) unsigned NOT NULL DEFAULT '4',
  `cf_member_side_merge` tinyint(1) NOT NULL DEFAULT '0',
  `cf_member_order`      varchar(200) NOT NULL DEFAULT 'ch_id asc',
  `cf_member_npc_mode`   varchar(10) NOT NULL DEFAULT 'split',
  `cf_member_npc_pos`    varchar(10) NOT NULL DEFAULT 'top',
  `cf_notice_ar_w`    smallint(5) unsigned NOT NULL DEFAULT '16',
  `cf_notice_ar_h`    smallint(5) unsigned NOT NULL DEFAULT '9',
  `cf_notice_card_min_w` VARCHAR(20)  NOT NULL DEFAULT '240px',
  `cf_notice_grid_mode`  varchar(20) NOT NULL DEFAULT 'auto-fill',
  `cf_notice_grid_count` tinyint(3) unsigned NOT NULL DEFAULT '4',
  `cf_notice_list_mode` VARCHAR(10)  NOT NULL DEFAULT 'list',
  `cf_1` varchar(200) NOT NULL DEFAULT '',
  `cf_2` varchar(200) NOT NULL DEFAULT '',
  `cf_3` varchar(200) NOT NULL DEFAULT '',
  `cf_4` varchar(200) NOT NULL DEFAULT '',
  `cf_5` varchar(200) NOT NULL DEFAULT '',
  `cf_6` varchar(200) NOT NULL DEFAULT '',
  `cf_7` varchar(200) NOT NULL DEFAULT '',
  `cf_8` varchar(200) NOT NULL DEFAULT '',
  `cf_9` varchar(200) NOT NULL DEFAULT '',
  `cf_10` varchar(200) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avo_group`
--

DROP TABLE IF EXISTS `avo_group`;
CREATE TABLE IF NOT EXISTS `avo_group` (
  `gr_id` varchar(10) NOT NULL default '',
  `gr_subject` varchar(200) NOT NULL default '',
  `gr_device` ENUM('both','pc','mobile') NOT NULL DEFAULT 'both',
  `gr_admin` varchar(200) NOT NULL default '',
  `gr_use_access` tinyint(4) NOT NULL default '0',
  `gr_order` int(11) NOT NULL default '0',
  `gr_1_subj` varchar(200) NOT NULL default '',
  `gr_2_subj` varchar(200) NOT NULL default '',
  `gr_3_subj` varchar(200) NOT NULL default '',
  `gr_4_subj` varchar(200) NOT NULL default '',
  `gr_5_subj` varchar(200) NOT NULL default '',
  `gr_6_subj` varchar(200) NOT NULL default '',
  `gr_7_subj` varchar(200) NOT NULL default '',
  `gr_8_subj` varchar(200) NOT NULL default '',
  `gr_9_subj` varchar(200) NOT NULL default '',
  `gr_10_subj` varchar(200) NOT NULL default '',
  `gr_1` varchar(200) NOT NULL default '',
  `gr_2` varchar(200) NOT NULL default '',
  `gr_3` varchar(200) NOT NULL default '',
  `gr_4` varchar(200) NOT NULL default '',
  `gr_5` varchar(200) NOT NULL default '',
  `gr_6` varchar(200) NOT NULL default '',
  `gr_7` varchar(200) NOT NULL default '',
  `gr_8` varchar(200) NOT NULL default '',
  `gr_9` varchar(200) NOT NULL default '',
  `gr_10` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`gr_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avo_group_member`
--

DROP TABLE IF EXISTS `avo_group_member`;
CREATE TABLE IF NOT EXISTS `avo_group_member` (
  `gm_id` int(11) NOT NULL auto_increment,
  `gr_id` varchar(200) NOT NULL default '',
  `mb_id` varchar(20) NOT NULL default '',
  `gm_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`gm_id`),
  KEY `gr_id` (`gr_id`),
  KEY `mb_id` (`mb_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avo_login`
--

DROP TABLE IF EXISTS `avo_login`;
CREATE TABLE IF NOT EXISTS `avo_login` (
  `lo_ip` varchar(200) NOT NULL default '',
  `mb_id` varchar(20) NOT NULL default '',
  `lo_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  `lo_location` text NOT NULL,
  `lo_url` text NOT NULL,
  PRIMARY KEY  (`lo_ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------


--
-- Table structure for table `avo_member`
--

DROP TABLE IF EXISTS `avo_member`;
CREATE TABLE IF NOT EXISTS `avo_member` (
  `mb_no` int(11) NOT NULL auto_increment,
  `mb_id` varchar(20) NOT NULL default '',
  `ch_id` int(11) NOT NULL default '0',
  `mb_password` varchar(200) NOT NULL default '',
  `mb_password2` varchar(200) NOT NULL default '',
  `mb_name` varchar(200) NOT NULL default '',
  `mb_nick` varchar(200) NOT NULL default '',
  `mb_level` tinyint(4) NOT NULL default '0',
  `mb_sex` char(1) NOT NULL default '',
  `mb_birth` varchar(200) NOT NULL default '',
  `mb_point` int(11) NOT NULL default '0',
  `mb_today_login` datetime NOT NULL default '0000-00-00 00:00:00',
  `mb_login_ip` varchar(200) NOT NULL default '',
  `mb_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  `mb_ip` varchar(200) NOT NULL default '',
  `mb_leave_date` varchar(8) NOT NULL default '',
  `mb_intercept_date` varchar(8) NOT NULL default '',
  `mb_memo` text NOT NULL,
  `mb_memo_call` varchar(200) NOT NULL default '',
  `mb_board_call` varchar(200) NOT NULL default '',
  `mb_board_link` varchar(200) NOT NULL default '',
  `mb_1` varchar(200) NOT NULL default '',
  `mb_2` varchar(200) NOT NULL default '',
  `mb_3` varchar(200) NOT NULL default '',
  `mb_4` varchar(200) NOT NULL default '',
  `mb_5` varchar(200) NOT NULL default '',
  `mb_6` varchar(200) NOT NULL default '',
  `mb_7` varchar(200) NOT NULL default '',
  `mb_8` varchar(200) NOT NULL default '',
  `mb_9` varchar(200) NOT NULL default '',
  `mb_10` varchar(200) NOT NULL default '',
  `mb_error_cnt` int(11) NOT NULL DEFAULT '0',
  `mb_error_content` varchar(200) NOT NULL DEFAULT '',
  `mb_time_override` int(11) NOT NULL DEFAULT '0',
  `mb_mode` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`mb_no`),
  UNIQUE KEY `mb_id` (`mb_id`),
  KEY `mb_today_login` (`mb_today_login`),
  KEY `mb_datetime` (`mb_datetime`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avo_memo`
--

DROP TABLE IF EXISTS `avo_memo`;
CREATE TABLE IF NOT EXISTS `avo_memo` (
  `me_id` int(11) NOT NULL default '0',
  `me_recv_mb_id` varchar(20) NOT NULL default '',
  `me_send_mb_id` varchar(20) NOT NULL default '',
  `me_send_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  `me_read_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  `me_memo` text NOT NULL,
  PRIMARY KEY  (`me_id`),
  KEY `me_recv_mb_id` (`me_recv_mb_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avo_point`
--

DROP TABLE IF EXISTS `avo_point`;
CREATE TABLE IF NOT EXISTS `avo_point` (
  `po_id` int(11) NOT NULL auto_increment,
  `mb_id` varchar(20) NOT NULL default '',
  `po_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  `po_content` varchar(200) NOT NULL default '',
  `po_point` int(11) NOT NULL default '0',
  `po_use_point` int(11) NOT NULL default '0',
  `po_mb_point` int(11) NOT NULL default '0',
  `po_rel_table` varchar(20) NOT NULL default '',
  `po_rel_id` varchar(20) NOT NULL default '',
  `po_rel_action` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`po_id`),
  KEY `index1` (`mb_id`,`po_rel_table`,`po_rel_id`,`po_rel_action`(170))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avo_scrap`
--

DROP TABLE IF EXISTS `avo_scrap`;
CREATE TABLE IF NOT EXISTS `avo_scrap` (
  `ms_id` int(11) NOT NULL auto_increment,
  `mb_id` varchar(20) NOT NULL default '',
  `bo_table` varchar(20) NOT NULL default '',
  `wr_id` varchar(15) NOT NULL default '',
  `ms_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`ms_id`),
  KEY `mb_id` (`mb_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------


--
-- Table structure for table `avo_unique`
--

DROP TABLE IF EXISTS `avo_uniqid`;
CREATE TABLE IF NOT EXISTS `avo_uniqid` (
  `uq_id` bigint(20) unsigned NOT NULL,
  `uq_ip` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`uq_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avo_autosave`
--

DROP TABLE IF EXISTS `avo_autosave`;
CREATE TABLE IF NOT EXISTS `avo_autosave` (
  `as_id` int(11) NOT NULL AUTO_INCREMENT,
  `mb_id` varchar(20) NOT NULL,
  `as_uid` bigint(20) unsigned NOT NULL,
  `as_subject` varchar(200) NOT NULL DEFAULT '',
  `as_content` text NOT NULL,
  `as_datetime` datetime NOT NULL,
  PRIMARY KEY (`as_id`),
  UNIQUE KEY `as_uid` (`as_uid`),
  KEY `mb_id` (`mb_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------



--
-- Table structure for table `avo_content`
--

DROP TABLE IF EXISTS `avo_content`;
CREATE TABLE IF NOT EXISTS `avo_content` (
  `co_id` varchar(20) NOT NULL DEFAULT '',
  `co_html` tinyint(4) NOT NULL DEFAULT '0',
  `co_subject` varchar(200) NOT NULL DEFAULT '',
  `co_content` longtext NOT NULL,
  `co_mobile_content` longtext NOT NULL,
  `co_skin` varchar(200) NOT NULL DEFAULT '',
  `co_mobile_skin` varchar(200) NOT NULL DEFAULT '',
  `co_tag_filter_use` tinyint(4) NOT NULL DEFAULT '0',
  `co_hit` int(11) NOT NULL DEFAULT '0',
  `co_include_head` varchar(200) NOT NULL DEFAULT '',
  `co_include_tail` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`co_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `avo_menu`
--

DROP TABLE IF EXISTS `avo_menu`;
CREATE TABLE IF NOT EXISTS `avo_menu` (
  `me_id` int(11) NOT NULL AUTO_INCREMENT,
  `me_code` varchar(200) NOT NULL DEFAULT '',
  `me_name` varchar(200) NOT NULL DEFAULT '',
  `me_link` varchar(200) NOT NULL DEFAULT '',
  `me_target` varchar(200) NOT NULL DEFAULT '',
  `me_order` int(11) NOT NULL DEFAULT '0',
  `me_use` tinyint(4) NOT NULL DEFAULT '0',
  `me_mobile_use` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`me_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

--
-- Table structure for table `avo_exp`
--
DROP TABLE IF EXISTS `avo_exp`;
CREATE TABLE IF NOT EXISTS `avo_exp` (
  `ex_id` int(11) NOT NULL AUTO_INCREMENT,
  `ch_id` varchar(20) NOT NULL DEFAULT '',
  `ch_name` varchar(200) NOT NULL DEFAULT '',
  `ex_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ex_content` varchar(200) NOT NULL DEFAULT '',
  `ex_point` int(11) NOT NULL DEFAULT '0',
  `ex_ch_exp` int(11) NOT NULL DEFAULT '0',
  `ex_rel_action` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`ex_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------

--
-- Table structure for table `avo_character`
--
DROP TABLE IF EXISTS `avo_character`;
CREATE TABLE IF NOT EXISTS `avo_character` (
  `ch_id` int(11) NOT NULL AUTO_INCREMENT,
  `ch_name` varchar(200) NOT NULL DEFAULT '',
  `ch_thumb` varchar(200) NOT NULL DEFAULT '',
  `ch_head` varchar(200) NOT NULL DEFAULT '',
  `ch_body` varchar(200) NOT NULL DEFAULT '',
  `ch_title` varchar(200) NOT NULL DEFAULT '',
  `mb_id` varchar(200) NOT NULL DEFAULT '',
  `ch_side` varchar(200) NOT NULL DEFAULT '0',
  `ch_class` varchar(200) NOT NULL DEFAULT '0',
  `ch_rank` char(4) NOT NULL DEFAULT '',
  `ch_exp` int(11) NOT NULL DEFAULT '0',
  `ch_point` int(11) NOT NULL DEFAULT '0',
  `ch_type` varchar(200) NOT NULL DEFAULT '',
  `ch_search_date` varchar(200) NOT NULL DEFAULT '',
  `ch_search` int(11) NOT NULL DEFAULT '0',
  `ch_state` varchar(200) NOT NULL DEFAULT '',
  `ma_id` int(11) NOT NULL DEFAULT '0',
  `ch_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ch_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------

--
-- Table structure for table `avo_character_class`
--
DROP TABLE IF EXISTS `avo_character_class`;
CREATE TABLE IF NOT EXISTS `avo_character_class` (
  `cl_id` int(11) NOT NULL AUTO_INCREMENT,
  `cl_name` varchar(200) NOT NULL DEFAULT '',
  `cl_img` varchar(200) NOT NULL DEFAULT '',
  `cl_auth` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`cl_id`),
  KEY `cl_id` (`cl_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `avo_character_side`
--
DROP TABLE IF EXISTS `avo_character_side`;
CREATE TABLE IF NOT EXISTS `avo_character_side` (
  `si_id` int(11) NOT NULL AUTO_INCREMENT,
  `si_name` varchar(200) NOT NULL DEFAULT '',
  `si_img` varchar(200) NOT NULL DEFAULT '',
  `si_auth` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`si_id`),
  KEY `si_id` (`si_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------

--
-- 테이블 구조 `avo_character_closthes`
--
DROP TABLE IF EXISTS `avo_character_closthes`;
CREATE TABLE IF NOT EXISTS `avo_character_closthes` (
  `cl_id` int(11) NOT NULL AUTO_INCREMENT,
  `ch_id` int(11) NOT NULL DEFAULT '0',
  `cl_subject` varchar(200) NOT NULL DEFAULT '',
  `cl_path` varchar(200) NOT NULL DEFAULT '',
  `cl_use` int(4) NOT NULL DEFAULT '0',
  `cl_type` varchar(10) NOT NULL DEFAULT '',
  `cl_time` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY  (`cl_id`),
  KEY `cl_id` (`cl_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------

--
-- 테이블 구조 `avo_character_time` (시기 메타)
--
DROP TABLE IF EXISTS `avo_character_time`;
CREATE TABLE IF NOT EXISTS `avo_character_time` (
  `ti_id` int(11) NOT NULL AUTO_INCREMENT,
  `ti_name` varchar(100) NOT NULL DEFAULT '',
  `ti_order` int(11) NOT NULL DEFAULT '0',
  `ti_open` tinyint(1) NOT NULL DEFAULT '1',
  `ti_type` varchar(20) NOT NULL DEFAULT 'renewal',
  `ti_preset_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ti_id`),
  UNIQUE KEY `ti_order` (`ti_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `avo_character_time` (`ti_id`, `ti_name`, `ti_order`, `ti_open`, `ti_type`, `ti_preset_id`) VALUES (1, '기본', 1, 1, 'renewal', 0);

-- --------------------------------------------------------

--
-- 테이블 구조 `avo_theme_preset` (테마 프리셋)
--
DROP TABLE IF EXISTS `avo_theme_preset`;
CREATE TABLE IF NOT EXISTS `avo_theme_preset` (
  `pr_id` int(11) NOT NULL AUTO_INCREMENT,
  `pr_name` varchar(100) NOT NULL DEFAULT '',
  `pr_design` varchar(64) NOT NULL DEFAULT '1',
  `pr_world_design` varchar(64) NOT NULL DEFAULT '1',
  `pr_content_design` varchar(64) NOT NULL DEFAULT '',
  `pr_bgm_type` varchar(20) NOT NULL DEFAULT 'playlist',
  `pr_bgm_id` varchar(64) NOT NULL DEFAULT '',
  `pr_bgm_volume` tinyint NOT NULL DEFAULT '50',
  `pr_font_family` varchar(200) NOT NULL DEFAULT '',
  `pr_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pr_id`),
  KEY `pr_order` (`pr_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `avo_theme_preset` (`pr_id`, `pr_name`, `pr_design`, `pr_world_design`, `pr_content_design`, `pr_order`) VALUES (1, 'default', '1', '1', '', 1);

-- --------------------------------------------------------

--
-- 테이블 구조 `avo_theme_preset_slot` (프리셋별 슬롯-테마 매핑)
--
DROP TABLE IF EXISTS `avo_theme_preset_slot`;
CREATE TABLE IF NOT EXISTS `avo_theme_preset_slot` (
  `ps_id` int(11) NOT NULL AUTO_INCREMENT,
  `pr_id` int(11) NOT NULL DEFAULT '0',
  `ps_slot` varchar(64) NOT NULL DEFAULT '',
  `ps_theme` varchar(64) NOT NULL DEFAULT 'default',
  PRIMARY KEY (`ps_id`),
  UNIQUE KEY `pr_slot` (`pr_id`, `ps_slot`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `avo_character_time_value` (시기별 캐릭터 기본 정보 override)
--
DROP TABLE IF EXISTS `avo_character_time_value`;
CREATE TABLE IF NOT EXISTS `avo_character_time_value` (
  `ctv_id` int(11) NOT NULL AUTO_INCREMENT,
  `ch_id` int(11) NOT NULL DEFAULT '0',
  `ti_id` int(11) NOT NULL DEFAULT '0',
  `ctv_name` varchar(200) NOT NULL DEFAULT '',
  `ctv_thumb` varchar(200) NOT NULL DEFAULT '',
  `ctv_head` varchar(200) NOT NULL DEFAULT '',
  `ctv_body` varchar(200) NOT NULL DEFAULT '',
  `ctv_side` varchar(200) NOT NULL DEFAULT '',
  `ctv_class` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`ctv_id`),
  UNIQUE KEY `ch_ti` (`ch_id`,`ti_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 시기별 side 활성 매핑 (renewal/au 시기마다 활성될 side들)
--
DROP TABLE IF EXISTS `avo_character_side_time`;
CREATE TABLE IF NOT EXISTS `avo_character_side_time` (
  `si_id` int(11) NOT NULL DEFAULT '0',
  `ti_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`si_id`,`ti_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 시기별 class 활성 매핑
--
DROP TABLE IF EXISTS `avo_character_class_time`;
CREATE TABLE IF NOT EXISTS `avo_character_class_time` (
  `cl_id` int(11) NOT NULL DEFAULT '0',
  `ti_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cl_id`,`ti_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 시기별 NPC 활성 매핑 (Wave10 #7)
--   - 매핑이 비어 있는 시기는 모든 NPC 표시 (fallback)
--   - 비어 있지 않은 시기는 매핑된 NPC 만 표시
--
DROP TABLE IF EXISTS `avo_character_npc_time`;
CREATE TABLE IF NOT EXISTS `avo_character_npc_time` (
  `ch_id` int(11) NOT NULL DEFAULT '0',
  `ti_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ch_id`,`ti_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `avo_character_time_article` (시기별 article 값 override)
-- ar_code 는 utf8mb4 + MyISAM 1000-byte 키 제한 회피를 위해 VARCHAR(190)
--
DROP TABLE IF EXISTS `avo_character_time_article`;
CREATE TABLE IF NOT EXISTS `avo_character_time_article` (
  `cta_id` int(11) NOT NULL AUTO_INCREMENT,
  `ch_id` int(11) NOT NULL DEFAULT '0',
  `ti_id` int(11) NOT NULL DEFAULT '0',
  `ar_code` varchar(190) NOT NULL DEFAULT '',
  `cta_value` text NOT NULL,
  PRIMARY KEY (`cta_id`),
  UNIQUE KEY `ch_ti_ar` (`ch_id`,`ti_id`,`ar_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `avo_couple`
--
DROP TABLE IF EXISTS `avo_couple`;
CREATE TABLE IF NOT EXISTS `avo_couple` (
  `co_id` int(11) NOT NULL AUTO_INCREMENT,
  `co_left` int(11) NOT NULL DEFAULT '0',
  `co_right` int(11) NOT NULL DEFAULT '0',
  `co_order` int(11) NOT NULL DEFAULT '0',
  `co_date` date NOT NULL DEFAULT '0000-00-00',
  PRIMARY KEY  (`co_id`),
  KEY `co_id` (`co_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------

--
-- 테이블 구조 `avo_emoticon`
--
DROP TABLE IF EXISTS `avo_emoticon`;
CREATE TABLE IF NOT EXISTS `avo_emoticon` (
  `me_id` int(11) NOT NULL AUTO_INCREMENT,
  `me_text` varchar(200) NOT NULL DEFAULT '',
  `me_img` varchar(200) NOT NULL DEFAULT '',
  `em_use` tinyint(4) NOT NULL DEFAULT '1',
  `em_mb_name` varchar(255) NOT NULL DEFAULT '',
  `em_datetime` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  PRIMARY KEY (`me_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------

--
-- 테이블 구조 `avo_inventory`
--
DROP TABLE IF EXISTS `avo_inventory`;
CREATE TABLE IF NOT EXISTS `avo_inventory` (
  `in_id` int(11) NOT NULL AUTO_INCREMENT,
  `it_id` varchar(200) NOT NULL DEFAULT '',
  `it_name` varchar(200) NOT NULL DEFAULT '',
  `it_rel` varchar(200) NOT NULL DEFAULT '',
  `ch_id` varchar(200) NOT NULL DEFAULT '',
  `ch_name` varchar(200) NOT NULL DEFAULT '',
  `se_ch_id` varchar(200) NOT NULL DEFAULT '',
  `se_ch_name` varchar(200) NOT NULL DEFAULT '',
  `re_ch_id` varchar(200) NOT NULL DEFAULT '',
  `re_ch_name` varchar(200) NOT NULL DEFAULT '',
  `in_sdatetime` datetime NOT NULL DEFAULT '0000-00-00',
  `in_edatetime` datetime NOT NULL DEFAULT '0000-00-00',
  `in_memo` varchar(200) NOT NULL DEFAULT '',
  `in_use` varchar(200) NOT NULL DEFAULT '',
  `in_1` varchar(200) NOT NULL DEFAULT '',
  `in_2` varchar(200) NOT NULL DEFAULT '',
  `in_3` varchar(200) NOT NULL DEFAULT '',
  `in_4` varchar(200) NOT NULL DEFAULT '',
  `in_5` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (`in_id`),
  KEY `in_id` (`in_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `avo_item`
--
DROP TABLE IF EXISTS `avo_item`;
CREATE TABLE IF NOT EXISTS `avo_item` (
  `it_id` int(11) NOT NULL DEFAULT '0',
  `it_name` varchar(200) NOT NULL DEFAULT '',
  `it_category` varchar(200) NOT NULL DEFAULT '',
  `it_content` varchar(200) NOT NULL DEFAULT '',
  `it_content2` text NOT NULL,
  `it_use_class` varchar(200) NOT NULL DEFAULT '',
  `it_use_side` varchar(200) NOT NULL DEFAULT '',
  `it_use_able` varchar(200) NOT NULL DEFAULT '',
  `it_use_mmb_able` int(4) NOT NULL DEFAULT '0',
  `it_img` varchar(200) NOT NULL DEFAULT '',
  `it_has` int(11) NOT NULL DEFAULT '0',
  `it_sell` int(11) NOT NULL DEFAULT '0',
  `it_use_sell` int(11) NOT NULL DEFAULT '0',
  `it_use_ever` int(11) NOT NULL DEFAULT '0',
  `it_use` char(4) NOT NULL DEFAULT '',
  `it_type` varchar(200) NOT NULL DEFAULT '',
  `it_value` varchar(200) NOT NULL DEFAULT '',
  `it_use_recepi` int(11) NOT NULL DEFAULT '0',
  `it_seeker` int(4) NOT NULL DEFAULT '0',
  `it_seeker_per_s` int(11) NOT NULL DEFAULT '0',
  `it_seeker_per_e` int(11) NOT NULL DEFAULT '0',
  `st_id` int(11) NOT NULL DEFAULT '0',
  `it_1` varchar(200) NOT NULL DEFAULT '',
  `it_2` varchar(200) NOT NULL DEFAULT '',
  `it_3` varchar(200) NOT NULL DEFAULT '',
  `it_4` varchar(200) NOT NULL DEFAULT '',
  `it_5` varchar(200) NOT NULL DEFAULT '',
  `maker_ch_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`it_id`),
  KEY `it_id` (`it_id`),
  KEY `maker_ch_id` (`maker_ch_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `avo_item_recepi`
--
DROP TABLE IF EXISTS `avo_item_recepi`;
CREATE TABLE IF NOT EXISTS `avo_item_recepi` (
  `re_id` int(11) NOT NULL AUTO_INCREMENT,
  `re_item_order` varchar(200) NOT NULL DEFAULT '',
  `it_id` int(11) NOT NULL DEFAULT '0',
  `re_use` int(4) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`re_id`),
  KEY `re_id` (`re_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

--
-- 테이블 구조 `avo_order`
--
DROP TABLE IF EXISTS `avo_order`;
CREATE TABLE IF NOT EXISTS `avo_order` (
  `or_id` int(11) NOT NULL AUTO_INCREMENT,
  `ch_id` varchar(200) NOT NULL DEFAULT '',
  `it_id` varchar(200) NOT NULL DEFAULT '',
  `or_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `mb_id` varchar(200) NOT NULL DEFAULT '',
  `or_use` varchar(11) NOT NULL DEFAULT '',
  `add_state` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`or_id`),
  KEY `or_id` (`or_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `avo_relation_character`
--
DROP TABLE IF EXISTS `avo_relation_character`;
CREATE TABLE IF NOT EXISTS `avo_relation_character` (
  `rm_id` int(11) NOT NULL AUTO_INCREMENT,
  `ch_id` int(11) NOT NULL DEFAULT '0',
  `re_ch_id` int(11) NOT NULL DEFAULT '0',
  `rm_memo` text NOT NULL,
  `rm_like` int(11) NOT NULL DEFAULT '0',
  `rm_link` text NOT NULL,
  `rm_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`rm_id`),
  KEY `rm_id` (`rm_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------

--
-- 테이블 구조 `avo_banner`
--
DROP TABLE IF EXISTS `avo_banner`;
CREATE TABLE IF NOT EXISTS `avo_banner` (
  `bn_id` int(11) NOT NULL AUTO_INCREMENT,
  `bn_img` varchar(200) NOT NULL DEFAULT '',
  `bn_m_img` varchar(200) NOT NULL DEFAULT '',
  `bn_alt` varchar(200) NOT NULL DEFAULT '',
  `bn_url` varchar(200) NOT NULL DEFAULT '',
  `bn_new_win` tinyint(4) NOT NULL DEFAULT '0',
  `bn_begin_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `bn_end_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `bn_time` varchar(200) NOT NULL DEFAULT '',
  `bn_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`bn_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------

--
-- 테이블 구조 `avo_intro`
--
DROP TABLE IF EXISTS `avo_intro`;
CREATE TABLE IF NOT EXISTS `avo_intro` (
  `bn_id` int(11) NOT NULL AUTO_INCREMENT,
  `bn_img` varchar(200) NOT NULL DEFAULT '',
  `bn_m_img` varchar(200) NOT NULL DEFAULT '',
  `bn_alt` varchar(200) NOT NULL DEFAULT '',
  `bn_url` varchar(200) NOT NULL DEFAULT '',
  `bn_new_win` tinyint(4) NOT NULL DEFAULT '0',
  `bn_begin_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `bn_end_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `bn_time` varchar(200) NOT NULL DEFAULT '',
  `bn_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`bn_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- 테이블 구조 `avo_call_board`
--
DROP TABLE IF EXISTS `avo_call_board`;
CREATE TABLE IF NOT EXISTS `avo_call_board` (
  `bc_id` int(11) NOT NULL AUTO_INCREMENT,
  `bo_table` varchar(20) NOT NULL DEFAULT '',
  `wr_id` int(11) NOT NULL DEFAULT '0',
  `wr_num` int(11) NOT NULL DEFAULT '0',
  `mb_id` varchar(20) NOT NULL DEFAULT '',
  `mb_name` varchar(200) NOT NULL DEFAULT '',
  `ch_side` int(11) NOT NULL DEFAULT '0',
  `re_mb_id` varchar(20) NOT NULL DEFAULT '',
  `re_mb_name` varchar(200) NOT NULL DEFAULT '',
  `memo` text NOT NULL,
  `bc_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `bc_check` int(4) NOT NULL DEFAULT '0',
  -- avo8.0 추가 컬럼: 호출 타입/링크 (extend/call.lib.php)
  `bc_type` varchar(32) NOT NULL DEFAULT 'board',
  `bc_link` varchar(200) NOT NULL DEFAULT '',
  `bc_1on1` varchar(16) NOT NULL DEFAULT '',
  PRIMARY KEY  (`bc_id`),
  KEY `bc_id` (`bc_id`),
  KEY `idx_bc_type` (`bc_type`),
  KEY `idx_re_mb_check` (`re_mb_id`,`bc_check`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- avo8.0 추가 테이블: 호출 타입 메타 (extend/call.lib.php)
--
DROP TABLE IF EXISTS `avo_call_type_map`;
CREATE TABLE IF NOT EXISTS `avo_call_type_map` (
  `ct_id` int(11) NOT NULL AUTO_INCREMENT,
  `ct_code` varchar(32) NOT NULL DEFAULT '',
  `ct_name` varchar(64) NOT NULL DEFAULT '',
  `ct_memo` varchar(200) NOT NULL DEFAULT '',
  `ct_link` varchar(200) NOT NULL DEFAULT '',
  `ct_use` tinyint(1) NOT NULL DEFAULT '1',
  `ct_system` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ct_id`),
  UNIQUE KEY `uniq_ct_code` (`ct_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `avo_call_type_map` (`ct_code`,`ct_name`,`ct_memo`,`ct_link`,`ct_use`,`ct_system`) VALUES
('board',  '게시판 호출', '새 호출이 있습니다.', '/mypage/call.php',                              1, 1),
('memo',   '쪽지',      '새 쪽지가 있습니다.', '/mypage/memo',                  1, 1),
('present','선물',      '선물을 받았습니다.', '/mypage/call.php',            1, 1),
('notice', '공지',      '새 공지가 있습니다.', '/notice',                                1, 1),
('mention','멘션',      '타임라인에서 멘션되었습니다.', '/timeline/view.php?tl_table={tl_table}&wr_id={wr_id}', 1, 1);

--
-- avo8.0 추가 테이블: 훅 인덱스 (module/_hook_registry.php)
--
DROP TABLE IF EXISTS `avo_hook_registry`;
CREATE TABLE IF NOT EXISTS `avo_hook_registry` (
  `hr_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `hr_name` VARCHAR(100) NOT NULL,
  `hr_type` ENUM('action','filter') NOT NULL,
  `hr_scope` VARCHAR(32) NOT NULL DEFAULT 'general',
  `hr_file` varchar(100) NOT NULL,
  `hr_line` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `hr_param_signature` VARCHAR(500) NOT NULL DEFAULT '',
  `hr_doc` TEXT NULL,
  `hr_first_seen` DATETIME NOT NULL,
  `hr_last_seen` DATETIME NOT NULL,
  `hr_obsolete` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`hr_id`),
  UNIQUE KEY `uniq_name_file_line` (`hr_name`,`hr_file`,`hr_line`),
  KEY `idx_name` (`hr_name`),
  KEY `idx_scope` (`hr_scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `avo_character_title`;
CREATE TABLE IF NOT EXISTS `avo_character_title` (
  `ti_id` int(11) NOT NULL AUTO_INCREMENT,
  `ti_title` varchar(200) NOT NULL DEFAULT '',
  `ti_img` varchar(200) NOT NULL DEFAULT '',
  `ti_use` char(4) NOT NULL DEFAULT '',
  `ti_type` varchar(200) NOT NULL DEFAULT 'img',
  `ti_style` TEXT NOT NULL,
  PRIMARY KEY  (`ti_id`),
  KEY `ti_id` (`ti_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `avo_has_title`;
CREATE TABLE IF NOT EXISTS `avo_has_title` (
  `hi_id` int(11) NOT NULL AUTO_INCREMENT,
  `ti_id` int(11) NOT NULL DEFAULT '0',
  `ch_id`  int(11) NOT NULL DEFAULT '0',
  `ch_name` varchar(200) NOT NULL DEFAULT '',
  `hi_use` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`hi_id`),
  KEY `hi_id` (`hi_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `avo_css_config`;
CREATE TABLE IF NOT EXISTS `avo_css_config` (
  `cs_id` int(11) NOT NULL AUTO_INCREMENT,
  `cs_name` varchar(200) NOT NULL DEFAULT '',
  `cs_value` text NOT NULL,
  `cs_descript` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_1` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_2` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_3` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_4` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_5` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_6` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_7` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_8` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_9` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_10` varchar(200) NOT NULL DEFAULT '',
  `pd_id` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY  (`cs_id`),
  KEY `cs_id` (`cs_id`),
  KEY `pd_id` (`pd_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `avo_design_preset` (디자인 프리셋)
--
DROP TABLE IF EXISTS `avo_design_preset`;
CREATE TABLE IF NOT EXISTS `avo_design_preset` (
  `pd_id` int(11) NOT NULL AUTO_INCREMENT,
  `pd_name` varchar(100) NOT NULL DEFAULT '',
  `pd_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pd_id`),
  KEY `pd_order` (`pd_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `avo_design_preset` (`pd_id`, `pd_name`, `pd_order`) VALUES (1, 'default', 1);

DROP TABLE IF EXISTS `avo_article`;
CREATE TABLE IF NOT EXISTS `avo_article` (
  `ar_id` INT(11) NOT NULL AUTO_INCREMENT,
  `ar_code` varchar(200) NOT NULL DEFAULT '',
  `ar_name` varchar(200) NOT NULL DEFAULT '',
  `ar_type` varchar(200) NOT NULL DEFAULT '',
  `ar_size` INT(11) NOT NULL DEFAULT '0',
  `ar_text` varchar(200) NOT NULL DEFAULT '',
  `ar_help` varchar(200) NOT NULL DEFAULT '',
  `ar_order` INT(11) NOT NULL DEFAULT '0',
  `ar_secret` varchar(200) NOT NULL DEFAULT '',
  `ar_time` varchar(200) NOT NULL DEFAULT '',
  `ar_min_length` INT(11) NOT NULL DEFAULT '0',
  `ar_max_length` INT(11) NOT NULL DEFAULT '0',
  `ar_required` TINYINT(1) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`ar_id`),
  KEY `ar_id` (`ar_id`)
) ENGINE = MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `avo_article` (`ar_id`, `ar_code`, `ar_name`, `ar_type`, `ar_size`, `ar_text`, `ar_help`, `ar_order`, `ar_secret`, `ar_time`, `ar_min_length`, `ar_max_length`, `ar_required`) VALUES
(1, 'voice', '한마디', 'text', 300, '', '', 1, '', '', 0, 0, 0),
(2, 'age', '나이', 'text', 100, '', '', 2, '', '', 0, 0, 0),
(3, 'height', '신장', 'text', 100, '', '', 3, '', '', 0, 0, 0),
(4, 'gender', '성별', 'select', 0, '기타||여성||남성', '', 4, '', '', 0, 0, 0);

DROP TABLE IF EXISTS `avo_article_default`;
CREATE TABLE IF NOT EXISTS `avo_article_default` (
  `ad_id` INT(11) NOT NULL AUTO_INCREMENT ,
  `ad_use_thumb` INT(11) NOT NULL DEFAULT '0',
  `ad_use_head` INT(11) NOT NULL DEFAULT '0',
  `ad_use_body` INT(11) NOT NULL DEFAULT '0',
  `ad_use_name` INT(11) NOT NULL DEFAULT '0',
  `ad_text_thumb` varchar(200) NOT NULL DEFAULT '',
  `ad_text_head` varchar(200) NOT NULL DEFAULT '',
  `ad_text_body` varchar(200) NOT NULL DEFAULT '',
  `ad_text_name` varchar(200) NOT NULL DEFAULT '',
  `ad_help_thumb` varchar(200) NOT NULL DEFAULT '',
  `ad_help_head` varchar(200) NOT NULL DEFAULT '',
  `ad_help_body` varchar(200) NOT NULL DEFAULT '',
  `ad_help_name` varchar(200) NOT NULL DEFAULT '',
  `ad_url_thumb` INT(11) NOT NULL DEFAULT '0',
  `ad_url_head` INT(11) NOT NULL DEFAULT '0',
  `ad_url_body` INT(11) NOT NULL DEFAULT '0',
  `ad_use_title` INT(11) NOT NULL DEFAULT '0',
  `ad_use_closet` INT(11) NOT NULL DEFAULT '0',
  `ad_use_inven` INT(11) NOT NULL DEFAULT '0',
  `ad_use_money` INT(11) NOT NULL DEFAULT '0',
  `ad_use_rank` INT(11) NOT NULL DEFAULT '0',
  `ad_use_exp` INT(11) NOT NULL DEFAULT '0',
  `ad_use_status` INT(11) NOT NULL DEFAULT '0',
  `ad_required_thumb` INT(11) NOT NULL DEFAULT '0',
  `ad_required_head` INT(11) NOT NULL DEFAULT '0',
  `ad_required_body` INT(11) NOT NULL DEFAULT '0',
  `ad_required_name` INT(11) NOT NULL DEFAULT '1',
  `ad_min_length_name` INT(11) NOT NULL DEFAULT '0',
  `ad_max_length_name` INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`ad_id`),
  KEY `ad_id` (`ad_id`)
) ENGINE = MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `avo_article_value`;
CREATE TABLE IF NOT EXISTS `avo_article_value` (
  `av_id` INT(11) NOT NULL AUTO_INCREMENT ,
  `ch_id` INT(11) NOT NULL DEFAULT '0',
  `ar_code` varchar(200) NOT NULL DEFAULT '',
  `av_value` TEXT NOT NULL ,
  `av_1` varchar(200) NOT NULL DEFAULT '',
  `av_2` varchar(200) NOT NULL DEFAULT '',
  `av_3` varchar(200) NOT NULL DEFAULT '',
  `av_4` varchar(200) NOT NULL DEFAULT '',
  `av_5` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (`av_id`),
  KEY `av_id` (`av_id`)
) ENGINE = MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `avo_level_setting`;
CREATE TABLE IF NOT EXISTS `avo_level_setting` (
  `lv_id` INT(11) NOT NULL AUTO_INCREMENT ,
  `lv_name` varchar(200) NOT NULL DEFAULT '',
  `lv_exp` INT(11) NOT NULL DEFAULT '0',
  `lv_add_state` INT(11) NOT NULL DEFAULT '0',
  `lv_1` varchar(200) NOT NULL DEFAULT '',
  `lv_2` varchar(200) NOT NULL DEFAULT '',
  `lv_3` varchar(200) NOT NULL DEFAULT '',
  `lv_4` varchar(200) NOT NULL DEFAULT '',
  `lv_5` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (`lv_id`),
  KEY `lv_id` (`lv_id`)
) ENGINE = MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `avo_shop`;
CREATE TABLE IF NOT EXISTS `avo_shop` (
  `sh_id` int(11) NOT NULL AUTO_INCREMENT ,
  `it_id` int(11) NOT NULL DEFAULT '0',
  `ca_name` varchar(200) NOT NULL DEFAULT '',
  `sh_limit` int(11) NOT NULL DEFAULT '0',
  `sh_qty` int(11) NOT NULL DEFAULT '0',
  `sh_money` int(11) NOT NULL DEFAULT '0',
  `sh_use_money` int(11) NOT NULL DEFAULT '0',
  `sh_exp` int(11) NOT NULL DEFAULT '0',
  `sh_use_exp` int(11) NOT NULL DEFAULT '0',
  `sh_content` varchar(200) NOT NULL DEFAULT '',
  `sh_side` varchar(200) NOT NULL DEFAULT '',
  `sh_use_side` int(11) NOT NULL DEFAULT '0',
  `sh_class` varchar(200) NOT NULL DEFAULT '',
  `sh_use_class` int(11) NOT NULL DEFAULT '0',
  `sh_rank` varchar(200) NOT NULL DEFAULT '',
  `sh_use_rank` int(11) NOT NULL DEFAULT '0',
  `sh_has_item` int(11) NOT NULL DEFAULT '0',
  `sh_has_item_count` int(11) NOT NULL DEFAULT '0',
  `sh_use_has_item` int(11) NOT NULL DEFAULT '0',
  `sh_has_title` int(11) NOT NULL DEFAULT '0',
  `sh_use_has_title` int(11) NOT NULL DEFAULT '0',
  `sh_date_s` varchar(200) NOT NULL DEFAULT '',
  `sh_date_e` varchar(200) NOT NULL DEFAULT '',
  `sh_time_s` int(11) NOT NULL DEFAULT '0',
  `sh_time_e` int(11) NOT NULL DEFAULT '0',
  `sh_week` varchar(200) NOT NULL DEFAULT '',
  `sh_time` varchar(200) NOT NULL DEFAULT '',
  `sh_order` int(11) NOT NULL DEFAULT '0',
  `sh_use` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`sh_id`),
  KEY `lv_id` (`sh_id`)
) ENGINE = MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `avo_item_explorer`;
CREATE TABLE IF NOT EXISTS `avo_item_explorer` (
  `ie_id` int(11) NOT NULL AUTO_INCREMENT,
  `it_id` int(11) NOT NULL DEFAULT '0',
  `re_it_id` int(11) NOT NULL DEFAULT '0',
  `ie_per_s` int(11) NOT NULL DEFAULT '0',
  `ie_per_e` int(11) NOT NULL DEFAULT '0',
  `ma_id` int(11) NOT NULL DEFAULT '0',
  `ie_1` int(11) NOT NULL DEFAULT '0',
  `ie_2` int(11) NOT NULL DEFAULT '0',
  `ie_3` int(11) NOT NULL DEFAULT '0',
  `ie_4` int(11) NOT NULL DEFAULT '0',
  `ie_5` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`ie_id`),
  KEY `lv_id` (`ie_id`)
) ENGINE = MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `avo_status`;
CREATE TABLE IF NOT EXISTS `avo_status` (
  `st_id` int(11) NOT NULL AUTO_INCREMENT,
  `st_name`  varchar(200) NOT NULL DEFAULT '',
  `st_max` int(11) NOT NULL DEFAULT '0',
  `st_min` int(11) NOT NULL DEFAULT '0',
  `st_use_max` int(11) NOT NULL DEFAULT '0',
  `st_order` int(11) NOT NULL DEFAULT '0',
  `st_help`  varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (`st_id`),
  KEY `lv_id` (`st_id`)
) ENGINE = MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `avo_status_character`;
CREATE TABLE IF NOT EXISTS `avo_status_character` (
  `sc_id` int(11) NOT NULL AUTO_INCREMENT,
  `st_id` int(11) NOT NULL DEFAULT '0',
  `ch_id` int(11) NOT NULL DEFAULT '0',
  `sc_max` int(11) NOT NULL DEFAULT '0',
  `sc_value` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`sc_id`),
  KEY `sc_id` (`sc_id`)
) ENGINE = MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  Table structure for `avo_module_setting`
-- ----------------------------
DROP TABLE IF EXISTS `avo_module_setting`;
CREATE TABLE `avo_module_setting` (
  `ms_module` varchar(64) NOT NULL DEFAULT '',
  `ms_key`    varchar(64) NOT NULL DEFAULT '',
  `ms_value`  text,
  PRIMARY KEY (`ms_module`,`ms_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  Table structure for `avo_calc_preset`
-- ----------------------------
DROP TABLE IF EXISTS `avo_calc_preset`;
CREATE TABLE `avo_calc_preset` (
  `cp_id`           int(11) NOT NULL AUTO_INCREMENT,
  `cp_name`         varchar(200) NOT NULL DEFAULT '',
  `cp_use`          tinyint(4) NOT NULL DEFAULT '1',
  `cp_use_request`  tinyint(4) NOT NULL DEFAULT '0',
  `cp_payload`      mediumtext,
  `cp_memo`         varchar(255) NOT NULL DEFAULT '',
  `cp_order`        int(11) NOT NULL DEFAULT '0',
  `cp_datetime`     datetime DEFAULT NULL,
  PRIMARY KEY (`cp_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  Table structure for `avo_world`
-- ----------------------------
DROP TABLE IF EXISTS `avo_world`;
CREATE TABLE IF NOT EXISTS `avo_world` (
  `wo_id` varchar(20) NOT NULL DEFAULT '',
  `wo_html` tinyint(4) NOT NULL DEFAULT '0',
  `wo_subject` varchar(200) NOT NULL DEFAULT '',
  `wo_content` longtext NOT NULL,
  `wo_mobile_content` longtext NOT NULL,
  `wo_skin` varchar(200) NOT NULL DEFAULT '',
  `wo_mobile_skin` varchar(200) NOT NULL DEFAULT '',
  `wo_tag_filter_use` tinyint(4) NOT NULL DEFAULT '0',
  `wo_include_head` varchar(200) NOT NULL DEFAULT '',
  `wo_include_tail` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`wo_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  Table structure for `avo_world_menu`
-- ----------------------------
DROP TABLE IF EXISTS `avo_world_menu`;
CREATE TABLE IF NOT EXISTS `avo_world_menu` (
  `wm_id` int(11) NOT NULL AUTO_INCREMENT,
  `wm_parent` int(11) NOT NULL DEFAULT '0',
  `wm_depth` tinyint(1) NOT NULL DEFAULT '0',
  `wm_order` int(11) NOT NULL DEFAULT '0',
  `wm_title` varchar(200) NOT NULL DEFAULT '',
  `wm_img` varchar(200) NOT NULL DEFAULT '',
  `wm_img_hover` varchar(200) NOT NULL DEFAULT '',
  `wm_link_wo_id` varchar(20) NOT NULL DEFAULT '',
  `wm_is_first` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`wm_id`),
  KEY `wm_parent_order` (`wm_parent`,`wm_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  Table structure for `avo_world_preset`
-- ----------------------------
DROP TABLE IF EXISTS `avo_world_preset`;
CREATE TABLE IF NOT EXISTS `avo_world_preset` (
  `pd_id` int(11) NOT NULL AUTO_INCREMENT,
  `pd_name` varchar(100) NOT NULL DEFAULT '',
  `pd_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pd_id`),
  KEY `pd_order` (`pd_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `avo_world_preset` (`pd_id`, `pd_name`, `pd_order`) VALUES (1, 'default', 1);

-- ----------------------------
--  Table structure for `avo_world_config`
-- ----------------------------
DROP TABLE IF EXISTS `avo_world_config`;
CREATE TABLE IF NOT EXISTS `avo_world_config` (
  `cs_id` int(11) NOT NULL AUTO_INCREMENT,
  `cs_name` varchar(200) NOT NULL DEFAULT '',
  `cs_value` text NOT NULL,
  `cs_descript` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_1` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_2` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_3` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_4` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_5` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_6` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_7` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_8` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_9` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_10` varchar(200) NOT NULL DEFAULT '',
  `pd_id` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`cs_id`),
  KEY `cs_id` (`cs_id`),
  KEY `pd_id` (`pd_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  Table structure for `avo_content_preset` (문서 디자인 프리셋)
-- ----------------------------
DROP TABLE IF EXISTS `avo_content_preset`;
CREATE TABLE IF NOT EXISTS `avo_content_preset` (
  `pd_id` int(11) NOT NULL AUTO_INCREMENT,
  `pd_name` varchar(100) NOT NULL DEFAULT '',
  `pd_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pd_id`),
  KEY `pd_order` (`pd_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `avo_content_preset` (`pd_id`, `pd_name`, `pd_order`) VALUES (1, 'default', 1);

-- ----------------------------
--  Table structure for `avo_content_design_config` (문서 디자인 슬롯 값)
-- ----------------------------
DROP TABLE IF EXISTS `avo_content_design_config`;
CREATE TABLE IF NOT EXISTS `avo_content_design_config` (
  `cs_id` int(11) NOT NULL AUTO_INCREMENT,
  `cs_name` varchar(200) NOT NULL DEFAULT '',
  `cs_value` text NOT NULL,
  `cs_descript` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_1` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_2` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_3` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_4` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_5` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_6` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_7` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_8` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_9` varchar(200) NOT NULL DEFAULT '',
  `cs_etc_10` varchar(200) NOT NULL DEFAULT '',
  `pd_id` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`cs_id`),
  KEY `pd_id` (`pd_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  Table structure for `avo_notice` (공지사항)
-- ----------------------------
DROP TABLE IF EXISTS `avo_notice`;
CREATE TABLE IF NOT EXISTS `avo_notice` (
  `no_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `no_subject` varchar(200) NOT NULL DEFAULT '',
  `no_subtext` varchar(200) NOT NULL DEFAULT '',
  `no_thumb` text NOT NULL,
  `no_content` mediumtext NOT NULL,
  `no_html` tinyint(1) NOT NULL DEFAULT '1',
  `no_time` varchar(200) NOT NULL DEFAULT '',
  `mb_id` varchar(20) NOT NULL DEFAULT '',
  `no_writer` varchar(200) NOT NULL DEFAULT '',
  `no_use` tinyint(1) NOT NULL DEFAULT '1',
  `no_order` int(11) NOT NULL DEFAULT '0',
  `no_pinned` tinyint(1) NOT NULL DEFAULT '0',
  `no_widget` tinyint(1) NOT NULL DEFAULT '0',
  `no_skin` varchar(200) NOT NULL DEFAULT '',
  `nc_name` varchar(64) NOT NULL DEFAULT '',
  `no_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`no_id`),
  KEY `idx_use_pin_dt` (`no_use`,`no_pinned`,`no_datetime`),
  KEY `idx_use_widget` (`no_use`,`no_widget`),
  KEY `idx_order` (`no_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  Table structure for `avo_reset_setting` (주기적 리셋 카운터 정의)
-- ----------------------------
DROP TABLE IF EXISTS `avo_reset_setting`;
CREATE TABLE IF NOT EXISTS `avo_reset_setting` (
  `rs_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rs_name` varchar(64) NOT NULL DEFAULT '',
  `rs_label` varchar(128) NOT NULL DEFAULT '',
  `rs_last` int(11) NOT NULL DEFAULT '0',
  `rs_next` int(11) NOT NULL DEFAULT '0',
  `rs_period` varchar(32) NOT NULL DEFAULT 'daily',
  `rs_default` int(11) NOT NULL DEFAULT '0',
  `rs_plus` int(11) NOT NULL DEFAULT '0',
  `rs_max` int(11) NOT NULL DEFAULT '2147483647',
  `rs_min` int(11) NOT NULL DEFAULT '0',
  `rs_type` varchar(16) NOT NULL DEFAULT 'member',
  `setting_url` varchar(200) NOT NULL DEFAULT '',
  `rs_use` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rs_id`),
  UNIQUE KEY `uniq_name` (`rs_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  Table structure for `avo_reset_manager` (주기적 리셋 카운터 값)
-- ----------------------------
DROP TABLE IF EXISTS `avo_reset_manager`;
CREATE TABLE IF NOT EXISTS `avo_reset_manager` (
  `rm_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rs_id` int(11) unsigned NOT NULL DEFAULT '0',
  `rm_subkey` varchar(64) NOT NULL DEFAULT '',
  `rm_value` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rm_id`),
  UNIQUE KEY `uniq_rs_sub` (`rs_id`,`rm_subkey`),
  KEY `idx_rs_id` (`rs_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  Timeline (install/gnuboard5.sql · timeline/_functions.php write 스키마와 동기화)
-- ----------------------------

DROP TABLE IF EXISTS `avo_timeline`;
CREATE TABLE IF NOT EXISTS `avo_timeline` (
  `tl_table` varchar(20) NOT NULL DEFAULT '' COMMENT '타임라인 테이블명',
  `gr_id` varchar(255) NOT NULL DEFAULT '' COMMENT '그룹 ID (게시판 호환용)',
  `tl_subject` varchar(255) NOT NULL DEFAULT '' COMMENT '타임라인 제목',
  `tl_skin` varchar(255) NOT NULL DEFAULT 'basic' COMMENT '스킨',
  `tl_write_level` tinyint(4) NOT NULL DEFAULT 2 COMMENT '쓰기 권한 레벨',
  `tl_read_level` tinyint(4) NOT NULL DEFAULT 1 COMMENT '읽기 권한 레벨',
  `tl_use_image` tinyint(1) NOT NULL DEFAULT 1 COMMENT '이미지 첨부 사용',
  `tl_image_count` tinyint(4) NOT NULL DEFAULT 4 COMMENT '최대 첨부 이미지 수',
  `tl_image_width` smallint(6) NOT NULL DEFAULT 600 COMMENT '이미지 최대 너비',
  `tl_content_max` smallint(6) NOT NULL DEFAULT 500 COMMENT '내용 최대 글자수',
  `tl_use_dm` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'DM 사용',
  `tl_ti_use` tinyint(1) NOT NULL DEFAULT 0 COMMENT '타이틀(ti_id) 표시',
  `tl_poll_interval` int(11) NOT NULL DEFAULT 10000 COMMENT '타임라인 폴링 간격(ms)',
  `tl_dm_poll_interval` int(11) NOT NULL DEFAULT 3000 COMMENT 'DM 폴링 간격(ms)',
  `tl_write_point` int(11) NOT NULL DEFAULT 0 COMMENT '글쓰기 포인트',
  `tl_count_write` int(11) NOT NULL DEFAULT 0 COMMENT '총 글 수',
  `tl_count_comment` int(11) NOT NULL DEFAULT 0 COMMENT '총 댓글 수',
  `tl_1_subj` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드1 제목',
  `tl_2_subj` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드2 제목',
  `tl_3_subj` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드3 제목',
  `tl_4_subj` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드4 제목',
  `tl_5_subj` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드5 제목',
  `tl_6_subj` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드6 제목',
  `tl_7_subj` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드7 제목',
  `tl_8_subj` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드8 제목',
  `tl_9_subj` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드9 제목',
  `tl_10_subj` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드10 제목',
  `tl_1` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드1',
  `tl_2` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드2',
  `tl_3` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드3',
  `tl_4` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드4',
  `tl_5` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드5',
  `tl_6` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드6',
  `tl_7` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드7',
  `tl_8` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드8',
  `tl_9` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드9',
  `tl_10` varchar(255) NOT NULL DEFAULT '' COMMENT '여분필드10',
  PRIMARY KEY (`tl_table`),
  KEY `idx_gr_id` (`gr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='타임라인 목록 (게시판과 유사)';

INSERT INTO `avo_timeline` (`tl_table`, `tl_subject`, `tl_skin`, `tl_read_level`, `tl_write_level`, `tl_image_count`)
VALUES ('default', '타임라인', 'basic', 1, 2, 4);

DROP TABLE IF EXISTS `avo_timeline_good`;
CREATE TABLE IF NOT EXISTS `avo_timeline_good` (
  `tl_good_id` int(11) NOT NULL AUTO_INCREMENT,
  `tl_table` varchar(20) NOT NULL DEFAULT '' COMMENT '타임라인 테이블',
  `wr_id` int(11) NOT NULL DEFAULT 0 COMMENT '글 ID',
  `mb_id` varchar(20) NOT NULL DEFAULT '' COMMENT '회원 ID',
  `ch_id` int(11) NOT NULL DEFAULT 0 COMMENT '캐릭터 ID',
  `tl_good_datetime` datetime DEFAULT NULL COMMENT '등록일시',
  PRIMARY KEY (`tl_good_id`),
  UNIQUE KEY `uk_table_wr_ch` (`tl_table`, `wr_id`, `ch_id`),
  KEY `idx_ch_id` (`ch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='타임라인 좋아요';

DROP TABLE IF EXISTS `avo_timeline_bookmark`;
CREATE TABLE IF NOT EXISTS `avo_timeline_bookmark` (
  `tl_bm_id` int(11) NOT NULL AUTO_INCREMENT,
  `tl_table` varchar(20) NOT NULL DEFAULT '' COMMENT '타임라인 테이블',
  `wr_id` int(11) NOT NULL DEFAULT 0 COMMENT '글 ID',
  `mb_id` varchar(20) NOT NULL DEFAULT '' COMMENT '회원 ID',
  `ch_id` int(11) NOT NULL DEFAULT 0 COMMENT '캐릭터 ID',
  `tl_bm_datetime` datetime DEFAULT NULL COMMENT '등록일시',
  PRIMARY KEY (`tl_bm_id`),
  UNIQUE KEY `uk_table_wr_ch` (`tl_table`, `wr_id`, `ch_id`),
  KEY `idx_ch_id` (`ch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='타임라인 북마크';

DROP TABLE IF EXISTS `avo_timeline_mention`;
CREATE TABLE IF NOT EXISTS `avo_timeline_mention` (
  `tl_mt_id` int(11) NOT NULL AUTO_INCREMENT,
  `tl_table` varchar(20) NOT NULL DEFAULT '' COMMENT '타임라인 테이블',
  `wr_id` int(11) NOT NULL DEFAULT 0 COMMENT '글 ID',
  `mb_id` varchar(20) NOT NULL DEFAULT '' COMMENT '멘션된 회원 ID',
  `ch_id` int(11) NOT NULL DEFAULT 0 COMMENT '멘션된 캐릭터 ID',
  `tl_mt_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '읽음 여부',
  `tl_mt_datetime` datetime DEFAULT NULL COMMENT '멘션 일시',
  PRIMARY KEY (`tl_mt_id`),
  UNIQUE KEY `uk_table_wr_ch` (`tl_table`, `wr_id`, `ch_id`),
  KEY `idx_ch_id` (`ch_id`),
  KEY `idx_read` (`tl_mt_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='타임라인 멘션';

DROP TABLE IF EXISTS `avo_timeline_temp`;
CREATE TABLE IF NOT EXISTS `avo_timeline_temp` (
  `tt_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '임시저장 ID',
  `tl_table` varchar(20) NOT NULL DEFAULT '' COMMENT '타임라인 테이블',
  `parent_wr_id` int(11) NOT NULL DEFAULT 0 COMMENT '답글 대상 글 ID',
  `tu_id` int(11) NOT NULL DEFAULT 0 COMMENT '타임라인 사용자(캐릭터) ID',
  `wr_content` text DEFAULT NULL COMMENT '임시저장 원본 텍스트',
  `tt_datetime` datetime DEFAULT NULL COMMENT '저장일시',
  PRIMARY KEY (`tt_id`),
  UNIQUE KEY `uk_table_parent_tu` (`tl_table`, `parent_wr_id`, `tu_id`),
  KEY `idx_tu_id` (`tu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='타임라인 답글 임시저장';

DROP TABLE IF EXISTS `avo_timeline_user`;
CREATE TABLE IF NOT EXISTS `avo_timeline_user` (
  `tl_user_id` int(11) NOT NULL AUTO_INCREMENT,
  `tl_table` varchar(20) NOT NULL DEFAULT '' COMMENT '타임라인 테이블',
  `mb_id` varchar(20) NOT NULL DEFAULT '' COMMENT '회원 ID',
  `ch_id` int(11) NOT NULL DEFAULT 0 COMMENT '캐릭터 ID',
  `tu_men_id` varchar(100) NOT NULL DEFAULT '' COMMENT '멘션 ID',
  `tu_name` varchar(255) NOT NULL DEFAULT '' COMMENT '표시 이름',
  `tu_thumb` varchar(255) NOT NULL DEFAULT '' COMMENT '프로필 썸네일',
  `tu_header` varchar(255) NOT NULL DEFAULT '' COMMENT '헤더 이미지',
  `tu_bio` text DEFAULT NULL COMMENT '바이오',
  `tu_profile` varchar(255) NOT NULL DEFAULT '' COMMENT '프로필 주소',
  `tl_render_mode` varchar(20) NOT NULL DEFAULT 'normal' COMMENT '렌더링 모드 (thread/normal)',
  `tl_user_datetime` datetime DEFAULT NULL COMMENT '등록일시',
  PRIMARY KEY (`tl_user_id`),
  UNIQUE KEY `uk_table_ch` (`tl_table`, `ch_id`),
  KEY `idx_ch_id` (`ch_id`),
  KEY `idx_mb_id` (`mb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='타임라인 허용 사용자';

DROP TABLE IF EXISTS `avo_timeline_dm_rooms`;
CREATE TABLE IF NOT EXISTS `avo_timeline_dm_rooms` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '방 ID',
  `tl_table` varchar(50) NOT NULL DEFAULT '' COMMENT '타임라인 테이블명',
  `room_name` varchar(100) DEFAULT NULL COMMENT '방 이름 (NULL이면 참가자 이름 자동 생성)',
  `created_by` int(11) NOT NULL COMMENT '방장 캐릭터 ID',
  `created_at` datetime NOT NULL COMMENT '생성일시',
  `last_message_at` datetime DEFAULT NULL COMMENT '마지막 메시지 시간',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0 COMMENT '삭제 여부',
  PRIMARY KEY (`room_id`),
  KEY `idx_tl_table` (`tl_table`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_last_message` (`last_message_at`),
  KEY `idx_deleted` (`is_deleted`),
  KEY `idx_tl_active` (`tl_table`, `is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='타임라인 DM 방';

DROP TABLE IF EXISTS `avo_timeline_dm_participants`;
CREATE TABLE IF NOT EXISTS `avo_timeline_dm_participants` (
  `participant_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '참가자 ID',
  `room_id` int(11) NOT NULL COMMENT '방 ID',
  `ch_id` int(11) NOT NULL COMMENT '캐릭터 ID',
  `joined_at` datetime NOT NULL COMMENT '참가일시',
  `last_read_at` datetime DEFAULT NULL COMMENT '마지막 읽은 시간',
  `is_creator` tinyint(1) NOT NULL DEFAULT 0 COMMENT '방장 여부',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '활성 여부 (나가기 시 0)',
  PRIMARY KEY (`participant_id`),
  UNIQUE KEY `unique_room_user` (`room_id`, `ch_id`),
  KEY `idx_ch_id` (`ch_id`),
  KEY `idx_room_active` (`room_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='타임라인 DM 참가자';

DROP TABLE IF EXISTS `avo_timeline_dm_messages`;
CREATE TABLE IF NOT EXISTS `avo_timeline_dm_messages` (
  `msg_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '메시지 ID',
  `room_id` int(11) NOT NULL COMMENT '방 ID',
  `ch_id` int(11) NOT NULL COMMENT '발신자 캐릭터 ID',
  `msg_name` varchar(255) NOT NULL DEFAULT '' COMMENT '작성 시점 표시명 스냅샷 (tu_name)',
  `msg_thumb` varchar(255) NOT NULL DEFAULT '' COMMENT '작성 시점 프로필 썸네일 스냅샷',
  `content` text NOT NULL COMMENT '메시지 내용',
  `created_at` datetime NOT NULL COMMENT '전송일시',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0 COMMENT '삭제 여부',
  PRIMARY KEY (`msg_id`),
  KEY `idx_room_created` (`room_id`, `created_at`),
  KEY `idx_ch_id` (`ch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='타임라인 DM 메시지';

DROP TABLE IF EXISTS `avo_timeline_dm_read`;
CREATE TABLE IF NOT EXISTS `avo_timeline_dm_read` (
  `read_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '읽음 ID',
  `msg_id` int(11) NOT NULL COMMENT '메시지 ID',
  `ch_id` int(11) NOT NULL COMMENT '읽은 캐릭터 ID',
  `read_at` datetime NOT NULL COMMENT '읽은 시간',
  PRIMARY KEY (`read_id`),
  UNIQUE KEY `unique_msg_user` (`msg_id`, `ch_id`),
  KEY `idx_ch_id` (`ch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='타임라인 DM 읽음 표시';

DROP TABLE IF EXISTS `avo_timeline_default`;
CREATE TABLE IF NOT EXISTS `avo_timeline_default` (
  `wr_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '글 ID',
  `wr_num` int(11) NOT NULL DEFAULT 0 COMMENT '글 그룹 번호 (음수, 범프용)',
  `wr_parent` int(11) NOT NULL DEFAULT 0 COMMENT '부모글 ID (원글 ID, 원글은 음수)',
  `wr_is_comment` tinyint(1) NOT NULL DEFAULT 0 COMMENT '댓글 여부 (원글=0, 댓글=1)',
  `wr_comment` int(11) NOT NULL DEFAULT 0 COMMENT '댓글/답글 수',
  `wr_mention_root` int(11) NOT NULL DEFAULT 0 COMMENT '멘션 타래 루트 (원글=0, 1차댓글=1~N, 2차이상=부모와동일)',
  `wr_reply_to` int(11) NOT NULL DEFAULT 0 COMMENT '답장 대상 글 ID (직전 댓글)',
  `mb_id` varchar(20) NOT NULL DEFAULT '' COMMENT '회원 ID',
  `ch_id` int(11) NOT NULL DEFAULT 0 COMMENT '캐릭터 ID',
  `wr_name` varchar(255) NOT NULL DEFAULT '' COMMENT '작성자명 (캐릭터명)',
  `wr_content` text DEFAULT NULL COMMENT '글 내용',
  `wr_file` tinyint(4) NOT NULL DEFAULT 0 COMMENT '첨부파일 개수',
  `wr_image1` varchar(255) NOT NULL DEFAULT '' COMMENT '첨부 이미지1',
  `wr_image2` varchar(255) NOT NULL DEFAULT '' COMMENT '첨부 이미지2',
  `wr_image3` varchar(255) NOT NULL DEFAULT '' COMMENT '첨부 이미지3',
  `wr_image4` varchar(255) NOT NULL DEFAULT '' COMMENT '첨부 이미지4',
  `wr_good` int(11) NOT NULL DEFAULT 0 COMMENT '좋아요 수',
  `wr_log` text DEFAULT NULL COMMENT '액션 로그',
  `wr_item` varchar(20) NOT NULL DEFAULT '' COMMENT '사용 아이템 ID',
  `wr_item_log` text DEFAULT NULL COMMENT '아이템 사용 로그',
  `wr_dice` varchar(255) NOT NULL DEFAULT '',
  `wr_dice1` tinyint(4) NOT NULL DEFAULT 0 COMMENT '주사위1',
  `wr_dice2` tinyint(4) NOT NULL DEFAULT 0 COMMENT '주사위2',
  `wr_like_count` int(11) NOT NULL DEFAULT 0 COMMENT '좋아요 수',
  `wr_bookmark_count` int(11) NOT NULL DEFAULT 0 COMMENT '북마크 수',
  `wr_reply_count` int(11) NOT NULL DEFAULT 0 COMMENT '답글 수',
  `wr_datetime` datetime DEFAULT NULL COMMENT '작성일시',
  `wr_last` varchar(19) NOT NULL DEFAULT '' COMMENT '최종 수정일',
  `wr_bump_datetime` datetime DEFAULT NULL COMMENT '범프일시',
  `wr_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '작성 IP',
  `wr_deleted` tinyint(4) NOT NULL DEFAULT 0 COMMENT '삭제 여부',
  `cf_time` int(11) NOT NULL DEFAULT 0 COMMENT '작성 시점 시기(時期)',
  `ti_id` int(11) NOT NULL DEFAULT 0 COMMENT '작성 시점 타이틀(캐릭터 타이틀)',
  `wr_img_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `wr_text_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `wr_modified` tinyint(1) NOT NULL DEFAULT 0,
  `wr_waring_text` varchar(255) NOT NULL DEFAULT '',
  `wr_thumb` varchar(255) NOT NULL DEFAULT '',
  `wr_1` varchar(255) NOT NULL DEFAULT '',
  `wr_2` varchar(255) NOT NULL DEFAULT '',
  `wr_3` varchar(255) NOT NULL DEFAULT '',
  `wr_4` varchar(255) NOT NULL DEFAULT '',
  `wr_5` varchar(255) NOT NULL DEFAULT '',
  `wr_6` varchar(255) NOT NULL DEFAULT '',
  `wr_7` varchar(255) NOT NULL DEFAULT '',
  `wr_8` varchar(255) NOT NULL DEFAULT '',
  `wr_9` varchar(255) NOT NULL DEFAULT '',
  `wr_10` varchar(255) NOT NULL DEFAULT '',
  `wr_script` int(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`wr_id`),
  KEY `idx_wr_num` (`wr_num`),
  KEY `idx_wr_parent` (`wr_parent`),
  KEY `idx_mb_id` (`mb_id`),
  KEY `idx_ch_id` (`ch_id`),
  KEY `idx_wr_is_comment` (`wr_is_comment`),
  KEY `idx_wr_datetime` (`wr_datetime`),
  KEY `idx_wr_last` (`wr_last`),
  KEY `idx_wr_bump_datetime` (`wr_bump_datetime`),
  KEY `idx_wr_mention_root` (`wr_parent`, `wr_mention_root`),
  KEY `idx_wr_reply_to` (`wr_reply_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='타임라인 기본';