<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 디자인 선택값 통합 처리
 *
 * theme_preset 의 pr_*_design 컬럼은 다음 형식 중 하나를 가집니다:
 *   ''            → 기본 (= 프리셋 ID 1)
 *   'default'     → 기본 (= 프리셋 ID 1)
 *   'none'        → 사용 안 함 (allow_none 인 경우만)
 *   '1','2',...   → 디자인 프리셋 ID
 *   'skin:DIRNAME' → 스킨 디렉토리 사용 (DIRNAME 은 skin/{gubun}/ 하위 폴더)
 *
 * 헬퍼:
 *   parse_design_value($v)              → array('kind'=>preset|skin|none, 'value'=>...)
 *   make_design_value($kind, $value)    → 컬럼에 저장할 문자열
 *   get_design_choice_options($type)    → 드롭다운용 옵션 배열 (프리셋 + 스킨 통합)
 *   get_active_design_resolved($type)   → 현재 적용해야 할 결과 array
 *
 * 메인('main')은 스킨 개념이 없어 프리셋만, world/content 는 프리셋+스킨 통합.
 */

if (!function_exists('parse_design_value')) {
    function parse_design_value($v)
    {
        $v = trim((string)$v);
        if ($v === '' || $v === 'default') return array('kind' => 'preset', 'value' => 1);
        if ($v === 'none') return array('kind' => 'none', 'value' => '');
        if (strpos($v, 'skin:') === 0) {
            $skin = substr($v, 5);
            return array('kind' => 'skin', 'value' => $skin);
        }
        // 숫자만이면 프리셋 ID
        if (ctype_digit($v)) {
            $pd = (int)$v;
            if ($pd <= 0) $pd = 1;
            return array('kind' => 'preset', 'value' => $pd);
        }
        // 알 수 없는 값 → 기본값
        return array('kind' => 'preset', 'value' => 1);
    }
}

if (!function_exists('make_design_value')) {
    function make_design_value($kind, $value)
    {
        switch ($kind) {
            case 'none':   return 'none';
            case 'skin':   return 'skin:'.preg_replace('#[^A-Za-z0-9_./-]#', '', (string)$value);
            case 'preset':
            default:       return (string)(int)$value;
        }
    }
}

/**
 * 드롭다운용 통합 옵션 배열
 *
 * 반환값: array of array(value, label, kind, group)
 *   - value: <option value="..."> 에 들어갈 문자열 ('1', 'skin:basic', 'none' ...)
 *   - label: 표시 라벨
 *   - kind:  'preset' | 'skin' | 'none'
 *   - group: 'preset' | 'skin' | 'none' (optgroup 분류용)
 *
 * @param string $type DESIGN_PRESET_TYPES 키
 */
if (!function_exists('get_design_choice_options')) {
    function get_design_choice_options($type)
    {
        global $g5;
        $reg = isset($GLOBALS['DESIGN_PRESET_TYPES']) ? $GLOBALS['DESIGN_PRESET_TYPES'] : array();
        if (!isset($reg[$type])) return array();
        $def = $reg[$type];

        $opts = array();

        if (!empty($def['allow_none'])) {
            $opts[] = array('value' => 'none', 'label' => '사용 안 함', 'kind' => 'none', 'group' => 'none');
        }

        // 1) 프리셋
        $presets = array();
        if (!empty($def['list_func']) && function_exists($def['list_func'])) {
            $presets = call_user_func($def['list_func']);
        } else if (!empty($def['table_key']) && !empty($g5[$def['table_key']])) {
            $tbl = $g5[$def['table_key']];
            $r = sql_query("SELECT pd_id, pd_name FROM `{$tbl}` ORDER BY pd_order, pd_id", false);
            if ($r) { while ($pr = sql_fetch_array($r)) { $presets[(int)$pr['pd_id']] = $pr['pd_name']; } }
        }
        if (empty($presets)) $presets[1] = 'default';
        foreach ($presets as $pd_id => $pd_name) {
            $opts[] = array(
                'value' => (string)(int)$pd_id,
                'label' => '#'.(int)$pd_id.' '.$pd_name.($pd_id == 1 ? ' (기본)' : ''),
                'kind'  => 'preset',
                'group' => 'preset',
            );
        }

        // 2) 스킨 (skin_gubun 이 있는 타입만) — 코어만 (theme/mtheme 그룹은 폐지)
        if (!empty($def['skin_gubun']) && function_exists('av_get_skin_dirs_grouped')) {
            $g = av_get_skin_dirs_grouped($def['skin_gubun']);
            $auto = function_exists('av_core_auto_override_skins') ? av_core_auto_override_skins($def['skin_gubun']) : array();
            foreach ($g['core'] as $sk) {
                $label = in_array($sk, $auto, true) ? $sk.' (테마에 따라 변동)' : $sk;
                $opts[] = array('value'=>'skin:'.$sk, 'label'=>'[코어] '.$label, 'kind'=>'skin', 'group'=>'skin_core');
            }
        } else if (!empty($def['skin_gubun']) && function_exists('get_skin_dir')) {
            // 폴백 (admin.lib.php 미로드 시 — 일반적으론 미발생)
            $dirs = get_skin_dir($def['skin_gubun']);
            if (is_array($dirs)) {
                foreach ($dirs as $d) {
                    $opts[] = array('value' => 'skin:'.$d, 'label' => '[스킨] '.$d, 'kind' => 'skin', 'group' => 'skin');
                }
            }
        }

        return $opts;
    }
}

/**
 * 현재 활성 디자인 결정값 (시기 > 글로벌 > 기본 우선순위)
 *
 * @param string $type
 * @return array array('kind'=>'preset'|'skin'|'none', 'value'=>pd_id|skin_dir|'')
 */
if (!function_exists('get_active_design_resolved')) {
    function get_active_design_resolved($type = 'main')
    {
        $reg = isset($GLOBALS['DESIGN_PRESET_TYPES']) ? $GLOBALS['DESIGN_PRESET_TYPES'] : array();
        if (!isset($reg[$type])) {
            return array('kind' => 'preset', 'value' => 1);
        }
        $col = $reg[$type]['column'];

        $preset = function_exists('get_preset') ? get_preset() : array();
        $raw = isset($preset[$col]) ? $preset[$col] : '';
        $parsed = parse_design_value($raw);

        // allow_none 이 아니면 'none' 도 기본값으로 폴백
        if ($parsed['kind'] === 'none' && empty($reg[$type]['allow_none'])) {
            $parsed = array('kind' => 'preset', 'value' => 1);
        }
        return $parsed;
    }
}
