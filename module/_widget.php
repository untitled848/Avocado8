<?php
/**
 * 모듈 공용 위젯 — 테마/스킨 한 줄 출력
 *
 *   echo get_widget('notice');
 *   echo get_widget('k_pet');
 *   echo get_widget('k_pokemon', 'entry', $ch);
 *   echo get_widget('k_spacemaker', 'default', $ch, 160);
 *
 * 세 번째 인자는 캐릭터 행. 생략(또는 ch_id 없음)이면 페이지 $ch, 없으면 $character.
 * 네 번째 인자는 선택. 모듈이 쓰는 옵션(스냅샷 크기 등). 안 쓰면 무시.
 * 모듈은 bootstrap 에서 mdl_add_filter('mdl_widget', ...) 로 수신한다.
 * 훅 이름은 리터럴 'mdl_widget' 만. widget_{$module} 보간은 스캐너가 못 찾는다.
 */
if (!defined('_GNUBOARD_')) exit;

function mdl_widget_ch($ch = null)
{
    if (is_numeric($ch)) {
        $ch_id = (int)$ch;
        if ($ch_id > 0) {
            if (function_exists('get_character')) {
                $row = get_character($ch_id);
                if (is_array($row) && !empty($row['ch_id'])) {
                    return $row;
                }
            }
            return array('ch_id' => $ch_id);
        }
    } elseif (is_array($ch) && !empty($ch['ch_id'])) {
        return $ch;
    }
    if (isset($GLOBALS['ch']) && is_array($GLOBALS['ch']) && !empty($GLOBALS['ch']['ch_id'])) {
        return $GLOBALS['ch'];
    }
    if (isset($GLOBALS['character']) && is_array($GLOBALS['character']) && !empty($GLOBALS['character']['ch_id'])) {
        return $GLOBALS['character'];
    }
    return array();
}

/**
 * 모듈 위젯 HTML. 모듈이 꺼져 있거나 핸들러가 없으면 빈 문자열.
 *
 * @param string         $module 모듈 폴더명
 * @param string         $widget 위젯 키 (생략 시 default)
 * @param array|int|null $ch     캐릭터 행 또는 ch_id. 생략 시 $ch → $character
 * @param mixed          $size   선택. 스냅샷 크기 등 모듈 옵션. 안 쓰면 null
 * @return string
 */
function get_widget($module, $widget = 'default', $ch = null, $size = null)
{
    $module = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$module);
    $widget = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$widget);
    if ($widget === '') {
        $widget = 'default';
    }
    // 코어 공지 위젯은 모듈 ON 여부와 무관하다.
    $core_widgets = array('notice' => true);
    if ($module === '' || (empty($core_widgets[$module]) && empty($GLOBALS['mdl_active'][$module]))) {
        return '';
    }
    $ch = mdl_widget_ch($ch);
    $html = '';
    if (function_exists('mdl_apply_filters')) {
        $html = mdl_apply_filters('mdl_widget', $html, $module, $widget, $ch, $size);
    }
    return is_string($html) ? $html : '';
}

/**
 * 모듈이 get_widget() 으로 붙일 위젯을 제공하는가.
 * _hook_list uses 의 mdl_widget, 또는 bootstrap 의 mdl_widget 필터.
 */
function mdl_module_has_widget($name)
{
    $name = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$name);
    if ($name === '') {
        return false;
    }
    if (function_exists('mdl_module_hooks_used')) {
        $used = mdl_module_hooks_used($name);
        if (!empty($used['uses']) && in_array('mdl_widget', $used['uses'], true)) {
            return true;
        }
    }
    $bf = G5_PATH.'/module/'.$name.'/bootstrap.php';
    if (is_file($bf)) {
        $content = @file_get_contents($bf);
        if ($content !== false && preg_match('/mdl_add_filter\s*\(\s*[\'"]mdl_widget[\'"]/', $content)) {
            return true;
        }
    }
    return false;
}
