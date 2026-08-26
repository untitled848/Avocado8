<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * bbs_action_search install
 *   - 모든 게시판 write 테이블에 wr_log 컬럼 보장
 *   - 탐색 카운터는 reset_setting/reset_manager 로 분리 (캐릭터 컬럼 사용 안 함)
 *   - 모듈 설정 search_mode:
 *       'minus' 차감식(기존)  : rm_value = 사용 횟수 (rs_period 마다 0 으로 리셋)
 *       'plus'  누적식(행동력) : rm_value = 남은 행동력 (rs_period 마다 rs_plus 만큼 증가, 최대 rs_max)
 *   - reset_setting('character_search') 기본값:
 *       rs_period='daily', rs_default=0, rs_plus=0, rs_min=0, rs_max=5
 */

mdl_db_add_write_column('wr_log', "text NOT NULL");

// 아이템 기능 카테고리는 모듈 'item_type_options' 필터로만 노출 (config 테이블 통합 폐기)
// 과거 install 에서 cf_item_category 에 '탐색횟수' 를 추가했었다면 정리
if (function_exists('sql_fetch')) {
    $cf = sql_fetch("SELECT cf_item_category FROM {$g5['config_table']} LIMIT 1", false);
    if ($cf !== false) {
        $cats = array_values(array_filter(array_map('trim', explode('||', (string)($cf['cf_item_category'] ?? ''))), function ($v) {
            return $v !== '' && $v !== '탐색횟수';
        }));
        $new_val = sql_real_escape_string(implode('||', $cats));
        sql_query("UPDATE {$g5['config_table']} SET cf_item_category = '{$new_val}'", false);
    }
}

// 모듈 설정 — search_mode (없을 때만 등록)
if (mdl_setting_get('bbs_action_search', 'search_mode', null) === null) {
    mdl_setting_set('bbs_action_search', 'search_mode', 'minus');
}

// reset_setting 등록 — 신규 설치시에만 기본값 적용 (이미 운영 중이면 관리자가 손으로 조정한 값 보존)
if (function_exists('reset_setting_register') && function_exists('reset_setting_get')) {
    if (!reset_setting_get('character_search')) {
        reset_setting_register('character_search', array(
            'label'       => '캐릭터 탐색 카운터',
            'period'      => 'daily',
            'default'     => 0,
            'plus'        => 0,
            'min'         => 0,
            'max'         => 5,
            'type'        => 'character',
            'setting_url' => G5_MDL_ADMIN_URL.'/module.php?m=bbs_action_search&p=setting',
        ));
    }

    // 기존 캐릭터 일괄 등록 — rm_value 초기값은 모드에 따라 결정
    $_set = reset_setting_get('character_search');
    if (!empty($_set) && !empty($g5['character_table']) && !empty($g5['reset_manager_table'])) {
        $_rs_id = (int)$_set['rs_id'];
        $_mode  = mdl_setting_get('bbs_action_search', 'search_mode', 'minus');
        $_init  = ($_mode === 'plus') ? (int)$_set['rs_plus'] : 0;
        sql_query("insert ignore into `{$g5['reset_manager_table']}` (rs_id, rm_subkey, rm_value)
                   select {$_rs_id}, ch_id, {$_init} from `{$g5['character_table']}`", false);
    }
}

