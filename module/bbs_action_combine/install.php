<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * bbs_action_combine install — wr_log 컬럼 + 조합 횟수 reset_setting + combine_mode
 *
 *   설정 (avo_module_setting)
 *     bbs_action_combine.combine_mode : 'minus' (차감식, 기본) | 'plus' (누적식, 행동력)
 *
 *   카운터 (avo_reset_setting / avo_reset_manager)
 *     rs_name = 'character_combine', rm_subkey = ch_id
 *     - minus: rm_value = "사용 횟수"   → 남은 = rs_max - rm_value, 조합시 +1
 *     - plus : rm_value = "남은 행동력" → 남은 = rm_value,         조합시 -1
 */

mdl_db_add_write_column('wr_log', "text NOT NULL");

// 모듈 설정 — combine_mode (없을 때만 등록)
if (mdl_setting_get('bbs_action_combine', 'combine_mode', null) === null) {
    mdl_setting_set('bbs_action_combine', 'combine_mode', 'minus');
}

// 조합 횟수 카운터 등록
if (function_exists('reset_setting_register')) {
    reset_setting_register('character_combine', array(
        'label'       => '아이템 조합 횟수',
        'period'      => 'daily',
        'default'     => 0,
        'plus'        => 0,
        'max'         => 5,
        'min'         => 0,
        'type'        => 'character',
        'setting_url' => G5_MDL_ADMIN_URL.'/module.php?m=bbs_action_combine&p=setting',
    ));

    // 기존 캐릭터 일괄 등록 — rm_value 초기값은 모드에 따라 결정
    global $g5;
    if (!empty($g5['character_table']) && !empty($g5['reset_manager_table'])) {
        $_set = function_exists('reset_setting_get') ? reset_setting_get('character_combine') : null;
        if (!empty($_set)) {
            $_mode = mdl_setting_get('bbs_action_combine', 'combine_mode', 'minus');
            $_init = ($_mode === 'plus') ? (int)$_set['rs_plus'] : 0;
            sql_query("INSERT IGNORE INTO `{$g5['reset_manager_table']}` (rs_id, rm_subkey, rm_value)
                       SELECT ".(int)$_set['rs_id'].", ch_id, {$_init} FROM `{$g5['character_table']}`", false);
        }
    }
}
