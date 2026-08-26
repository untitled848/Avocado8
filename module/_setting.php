<?php
/**
 * Module Setting (KV) — 모듈 설정값 영속 저장
 *
 *   테이블: avo_module_setting
 *     - ms_module varchar(64)  : 모듈명
 *     - ms_key    varchar(64)  : 설정키
 *     - ms_value  text         : 설정값(문자열로 직렬화)
 *     - PRIMARY KEY (ms_module, ms_key)
 *
 *   사용:
 *     $amount = (int)mdl_setting_get('welcome_bonus', 'amount', 1000);
 *     mdl_setting_set('welcome_bonus', 'amount', 500);
 *     mdl_setting_delete('welcome_bonus', 'amount');
 *     $all = mdl_setting_all('welcome_bonus');           // [key => value]
 *
 *   값은 항상 문자열로 저장됨. 호출측에서 (int)/(bool)/json_decode 등으로 변환.
 */
if (!defined('_GNUBOARD_')) exit;

function mdl_setting_table_ensure() {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    if (empty($GLOBALS['g5']['module_setting_table'])) return;
    $t = $GLOBALS['g5']['module_setting_table'];
    sql_query("create table if not exists `{$t}` (
        `ms_module` varchar(64) not null default '',
        `ms_key`    varchar(64) not null default '',
        `ms_value`  text,
        primary key (`ms_module`, `ms_key`)
    ) engine=MyISAM default charset=utf8mb4 collate=utf8mb4_unicode_ci", false);
}

function mdl_setting_get($module, $key, $default = null) {
    mdl_setting_table_ensure();
    $t = $GLOBALS['g5']['module_setting_table'];
    $m = sql_real_escape_string($module);
    $k = sql_real_escape_string($key);
    $row = sql_fetch("select ms_value from `{$t}` where ms_module='{$m}' and ms_key='{$k}'", false);
    if (!$row) return $default;
    return $row['ms_value'];
}

function mdl_setting_set($module, $key, $value) {
    mdl_setting_table_ensure();
    $t = $GLOBALS['g5']['module_setting_table'];
    $m = sql_real_escape_string($module);
    $k = sql_real_escape_string($key);
    $v = sql_real_escape_string((string)$value);
    return (bool)sql_query(
        "insert into `{$t}` (ms_module, ms_key, ms_value) values ('{$m}', '{$k}', '{$v}')
         on duplicate key update ms_value = values(ms_value)", false);
}

function mdl_setting_delete($module, $key) {
    mdl_setting_table_ensure();
    $t = $GLOBALS['g5']['module_setting_table'];
    $m = sql_real_escape_string($module);
    $k = sql_real_escape_string($key);
    return (bool)sql_query("delete from `{$t}` where ms_module='{$m}' and ms_key='{$k}'", false);
}

function mdl_setting_all($module) {
    mdl_setting_table_ensure();
    $t = $GLOBALS['g5']['module_setting_table'];
    $m = sql_real_escape_string($module);
    $rs = sql_query("select ms_key, ms_value from `{$t}` where ms_module='{$m}'", false);
    $out = array();
    if ($rs) {
        while ($r = sql_fetch_array($rs)) {
            $out[$r['ms_key']] = $r['ms_value'];
        }
    }
    return $out;
}

/**
 * 모듈 일괄 제거 (uninstall.php 에서 사용 권장).
 */
function mdl_setting_drop_module($module) {
    mdl_setting_table_ensure();
    $t = $GLOBALS['g5']['module_setting_table'];
    $m = sql_real_escape_string($module);
    return (bool)sql_query("delete from `{$t}` where ms_module='{$m}'", false);
}
