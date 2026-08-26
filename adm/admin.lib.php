<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 스킨 디렉토리 스캔 — 통합 헬퍼
 *
 * 정책 (2026-05-07~):
 *   스킨은 코어(skin/<gubun>/<skin>) 만 사용자 노출 — 어드민에서 선택 가능한 스킨은 코어뿐.
 *   theme/<name>/skin/ 는 THEME_SLOT_MAP 화이트리스트 슬롯에 한해
 *   "활성 프리셋의 슬롯 테마"가 자동으로 그 경로를 사용하도록만 동작 (사용자 선택지 X).
 *   module/<mod>/theme/, module/<mod>/skin/ 은 폐지 — 모듈은 기능만 담당.
 *
 * 반환 형식 (역호환): array('core'=>[...], 'theme'=>[], 'mtheme'=>[]).
 *   theme/mtheme 는 항상 빈 배열 — 호출처가 키 존재를 기대할 수 있어 키 자체는 유지.
 *
 * 캐시: 요청 단위 static + filemtime 검증 (디렉터리 변경 시 무효).
 */
function av_get_skin_dirs_grouped($skin_gubun)
{
    static $cache = array();
    $core_root = G5_SKIN_PATH.'/'.$skin_gubun;
    $mtime = is_dir($core_root) ? @filemtime($core_root) : 0;
    $key = $skin_gubun.'|'.$mtime;
    if (isset($cache[$key])) return $cache[$key];

    $out = array('core'=>array(), 'theme'=>array(), 'mtheme'=>array());
    if (is_dir($core_root)) {
        foreach (scandir($core_root) as $d) {
            if ($d === '' || $d[0] === '.') continue;
            if (is_dir($core_root.'/'.$d)) $out['core'][] = $d;
        }
        sort($out['core']);
    }
    return $cache[$key] = $out;
}

/** 저장 값(prefix 포함) → 사람이 읽는 라벨 + 해당 그룹 추정 (레거시 DB 값 표시용) */
function av_skin_value_label($value)
{
    // 레거시: 'theme/<name>/<skin>' 또는 'mtheme/...' — 더 이상 새 옵션으로 노출되지 않음.
    // get_skin_path 가 prefix 를 떼고 코어 스킨으로 흡수 → 자동 오버라이드 적용.
    if (preg_match('#^theme/([^/]+)/(.+)$#', $value, $m))          return array('group'=>'core', 'label'=>$m[2].' (구 테마 매핑 → 자동)');
    if (preg_match('#^mtheme/([^/]+)/([^/]+)/(.+)$#', $value, $m)) return array('group'=>'core', 'label'=>$m[3].' (구 모듈테마 → 자동)');
    if (preg_match('#^mtheme/([^/]+)/(.+)$#', $value, $m))         return array('group'=>'core', 'label'=>$m[2].' (구 모듈테마 → 자동)');
    if (preg_match('#^module/([^/]+)/(.+)$#', $value, $m))         return array('group'=>'core', 'label'=>$m[2].' (구 모듈 → 자동)');
    return array('group'=>'core', 'label'=>$value);
}

/** "코어에서 자동 테마 오버라이드되는" core 스킨 이름 (board=mmb/basic/qna/calculate, content/world/outlogin/timeline=basic) */
function av_core_auto_override_skins($skin_gubun)
{
    if ($skin_gubun === 'board') return array('mmb','basic','qna','calculate');
    return array('basic');
}

/** 모듈 스킨 — 폐지(항상 빈 배열). 기존 호출처 호환용 stub. */
function mdl_get_skin_dirs($skin_gubun)
{
    return array();
}

// 스킨디렉토리를 SELECT 형식으로 얻음 — preset + core 만 (theme/mtheme 폐지)
//   $opts:
//     - 'allow_empty' (bool) "선택 안 함" 옵션 표시 여부 (default true)
//     - 'empty_label' (string) 그 라벨
//     - 'preset_type' (string) DESIGN_PRESET_TYPES 키 — 디자인 프리셋도 묶어서 표시
//     - 'group_order' (array) optgroup 출력 순서. default: preset, core
function get_skin_select($skin_gubun, $id, $name, $selected = '', $event = '', $opts = array())
{
    // 역호환: 6번째 인자가 문자열이면 옛 시그니처(empty_label)로 간주
    if (is_string($opts)) $opts = array('empty_label' => $opts);

    $allow_empty = !array_key_exists('allow_empty', $opts) || $opts['allow_empty'];
    $empty_label = isset($opts['empty_label']) && $opts['empty_label'] !== '' ? $opts['empty_label'] : '선택';
    $preset_type = isset($opts['preset_type']) ? $opts['preset_type'] : '';
    $order       = isset($opts['group_order']) ? (array)$opts['group_order'] : array('preset','core');

    $grouped = av_get_skin_dirs_grouped($skin_gubun);
    $auto_override = av_core_auto_override_skins($skin_gubun);

    // 디자인 프리셋 (선택)
    $preset_opts = array();
    if ($preset_type !== '' && function_exists('get_design_choice_options')) {
        foreach (get_design_choice_options($preset_type) as $po) {
            if ($po['group'] === 'preset') $preset_opts[] = $po;
        }
    }

    $group_label = array(
        'preset' => '디자인 프리셋',
        'core'   => '코어',
    );

    $str = "<select id=\"{$id}\" name=\"{$name}\" {$event}>\n";
    if ($allow_empty) {
        $str .= '<option value=""'.($selected === '' ? ' selected' : '').'>'.htmlspecialchars($empty_label, ENT_QUOTES).'</option>'."\n";
    }

    foreach ($order as $gk) {
        $body = '';
        if ($gk === 'preset') {
            foreach ($preset_opts as $po) {
                $sel = ($selected === $po['value']) ? ' selected' : '';
                $body .= '<option value="'.htmlspecialchars($po['value'], ENT_QUOTES).'"'.$sel.'>'.htmlspecialchars($po['label']).'</option>'."\n";
            }
        } else if ($gk === 'core') {
            foreach ($grouped['core'] as $sk) {
                $label = in_array($sk, $auto_override, true) ? $sk.' (테마에 따라 변동)' : $sk;
                $sel = ($selected === $sk) ? ' selected' : '';
                $body .= '<option value="'.htmlspecialchars($sk, ENT_QUOTES).'"'.$sel.'>'.htmlspecialchars($label).'</option>'."\n";
            }
        }
        // theme/mtheme 그룹은 폐지되어 더 이상 출력하지 않음
        if ($body === '') continue;
        $str .= "<optgroup label=\"".htmlspecialchars(isset($group_label[$gk]) ? $group_label[$gk] : $gk, ENT_QUOTES)."\">\n";
        $str .= $body;
        $str .= "</optgroup>\n";
    }

    // 선택 값이 위 그룹에 없으면 (구버전 / 삭제된 폴더 / 레거시 theme/mtheme prefix) 폴백 옵션
    if ($selected !== '' && strpos($str, ' value="'.htmlspecialchars($selected, ENT_QUOTES).'" selected') === false) {
        $info = av_skin_value_label($selected);
        $str .= '<option value="'.htmlspecialchars($selected, ENT_QUOTES).'" selected>(현재값) '.htmlspecialchars($info['label']).'</option>'."\n";
    }

    $str .= "</select>";
    return $str;
}

// 모바일 스킨디렉토리 (모바일 gubun 으로 위 함수 위임)
function get_mobile_skin_select($skin_gubun, $id, $name, $selected = '', $event = '', $opts = array())
{
    return get_skin_select('mobile_'.$skin_gubun, $id, $name, $selected, $event, $opts);
}

// 스킨경로를 얻는다
function get_skin_dir($skin, $skin_path = G5_SKIN_PATH)
{
    $result_array = array();

    $dirname = rtrim($skin_path, '/').'/'.$skin.'/';
    if (!is_dir($dirname)) {
        return $result_array;
    }

    $handle = opendir($dirname);
    if ($handle === false) {
        return $result_array;
    }

    while (false !== ($file = readdir($handle))) {
        if ($file === '.' || $file === '..') continue;
        if (is_dir($dirname.$file)) {
            $result_array[] = $file;
        }
    }

    closedir($handle);
    sort($result_array);

    return $result_array;
}

// 테마
function get_theme_dir()
{
    $result_array = array();

    $dirname = G5_PATH.'/'.G5_THEME_DIR.'/';
    if (!is_dir($dirname)) {
        return $result_array;
    }

    $handle = opendir($dirname);
    if ($handle === false) {
        return $result_array;
    }

    while (false !== ($file = readdir($handle))) {
        if ($file === '.' || $file === '..') continue;

        if (is_dir($dirname.$file)) {
            $theme_path = $dirname.$file;
            if (is_file($theme_path.'/index.php')
                && is_file($theme_path.'/head.php')
                && is_file($theme_path.'/tail.php')) {
                $result_array[] = $file;
            }
        }
    }

    closedir($handle);
    if (!empty($result_array)) {
        natsort($result_array);
        $result_array = array_values($result_array);
    }

    return $result_array;
}

// 테마디렉토리를 SELECT 형식으로 얻음
function get_theme_select($id, $name, $selected = '', $event = '')
{
    $theme = get_theme_dir();

    $str = "<select id=\"{$id}\" name=\"{$name}\" {$event}>\n";
    $str .= "<option value=\"\">-</option>";

    if (!empty($theme)) {
        foreach ($theme as $v) {
            $str .= option_selected($v, $selected, $v);
        }
    }

    $str .= "</select>";

    return $str;
}

// 테마정보
function get_theme_info($dir)
{
    $info = array();
    $path = G5_PATH.'/'.G5_THEME_DIR.'/'.$dir;

    if (!is_dir($path)) {
        return $info;
    }

    $screenshot_url = '';
    $screenshot = $path.'/screenshot.png';
    if (is_file($screenshot)) {
        $size = @getimagesize($screenshot);
        if (is_array($size) && isset($size[2]) && $size[2] == IMAGETYPE_PNG) {
            $screenshot_url = str_replace(G5_PATH, G5_URL, $screenshot);
        }
    }
    $info['screenshot'] = $screenshot_url;

    $text = $path.'/readme.txt';
    if (is_file($text)) {
        $content = file($text, FILE_IGNORE_NEW_LINES);
        $content = array_map('trim', $content);

        $get = function($idx, $pattern) use ($content) {
            if (!isset($content[$idx])) return '';
            if (preg_match($pattern, $content[$idx], $m)) {
                return trim($m[1]);
            }
            return '';
        };

        $info['theme_name']  = $get(0, '#^Theme Name:(.+)$#i');
        $info['theme_uri']   = $get(1, '#^Theme URI:(.+)$#i');
        $info['maker']       = $get(2, '#^Maker:(.+)$#i');
        $info['maker_uri']   = $get(3, '#^Maker URI:(.+)$#i');
        $info['version']     = $get(4, '#^Version:(.+)$#i');
        $info['detail']      = $get(5, '#^Detail:(.+)$#i');
        $info['license']     = $get(6, '#^License:(.+)$#i');
        $info['license_uri'] = $get(7, '#^License URI:(.+)$#i');
    }

    if (empty($info['theme_name'])) {
        $info['theme_name'] = $dir;
    }

    return $info;
}

// 테마설정 정보
function get_theme_config_value($dir, $key='*')
{
    $tconfig = array();
    $theme_config = array();

    $theme_config_file = G5_PATH.'/'.G5_THEME_DIR.'/'.$dir.'/theme.config.php';
    if (is_file($theme_config_file)) {
        include $theme_config_file;

        if (!isset($theme_config) || !is_array($theme_config)) {
            $theme_config = array();
        }

        if ($key === '*') {
            $tconfig = $theme_config;
        } else {
            $keys = array_map('trim', explode(',', $key));
            foreach ($keys as $v) {
                $tconfig[$v] = isset($theme_config[$v]) ? trim($theme_config[$v]) : '';
            }
        }
    }

    return $tconfig;
}

// 회원권한을 SELECT 형식으로 얻음
function get_member_level_select($name, $start_id=0, $end_id=10, $selected="", $event="")
{
    $level_name = array();
    $level_name[1]  = "방문자";
    $level_name[2]  = "일반멤버";
    $level_name[10] = "운영자";

    $str = "\n<select id=\"{$name}\" name=\"{$name}\"";
    if ($event) $str .= " {$event}";
    $str .= ">\n";

    for ($i = $start_id; $i <= $end_id; $i++) {
        if (!isset($level_name[$i])) continue;

        $str .= '<option value="'.$i.'"';
        if ((string)$i === (string)$selected) {
            $str .= ' selected="selected"';
        }
        $str .= '>'.$level_name[$i]."&nbsp;&nbsp;</option>\n";
    }

    $str .= "</select>\n";
    return $str;
}

// 회원아이디를 SELECT 형식으로 얻음
function get_member_id_select($name, $level, $selected="", $event="")
{
    global $g5;

    $sql = " SELECT mb_id FROM {$g5['member_table']} WHERE mb_level >= '".(int)$level."' ";
    $result = sql_query($sql);
    $str = '<select id="'.$name.'" name="'.$name.'" '.$event.'><option value="">선택안함</option>';

    for ($i = 0; $row = sql_fetch_array($result); $i++) {
        $mb_id = $row['mb_id'];
        $str .= '<option value="'.$mb_id.'"';
        if ($mb_id == $selected) $str .= ' selected';
        $str .= '>'.$mb_id.'</option>';
    }

    $str .= '</select>';
    return $str;
}

// 권한 검사 — 정책상 운영자 전체 허용 (항상 통과). $return=true 면 빈 메시지.
//   레거시 페이지 호환을 위해 함수만 유지하고 동작은 no-op.
function auth_check($auth, $attr, $return=false)
{
    if ($return) return '';
    return;
}

// 작업아이콘 출력
function icon($act, $link = '', $target = '_parent')
{
    $img = array(
        '입력' => 'insert',
        '추가' => 'insert',
        '생성' => 'insert',
        '수정' => 'modify',
        '삭제' => 'delete',
        '이동' => 'move',
        '그룹' => 'move',
        '보기' => 'view',
        '미리보기' => 'view',
        '복사' => 'copy'
    );

    if (!isset($img[$act])) {
        return '';
    }

    $icon = '<img src="'.G5_ADMIN_PATH.'/img/icon_'.$img[$act].'.gif" title="'.$act.'">';

    if ($link) {
        return '<a href="'.$link.'" target="'.$target.'">'.$icon.'</a>';
    }

    return $icon;
}

// rm -rf 옵션
function rm_rf($file)
{
    if (!file_exists($file)) {
        return;
    }

    if (is_dir($file)) {
        $handle = opendir($file);
        if ($handle !== false) {
            while (false !== ($filename = readdir($handle))) {
                if ($filename === '.' || $filename === '..') continue;
                rm_rf($file.'/'.$filename);
            }
            closedir($handle);
        }

        @chmod($file, G5_DIR_PERMISSION);
        @rmdir($file);
    } else {
        @chmod($file, G5_FILE_PERMISSION);
        @unlink($file);
    }
}

// 출력순서
function order_select($fld, $sel = '')
{
    $s = '<select name="'.$fld.'" id="'.$fld.'">';
    for ($i = 1; $i <= 100; $i++) {
        $s .= '<option value="'.$i.'"';
        if ($sel) {
            if ((int)$i === (int)$sel) $s .= ' selected';
        } else {
            if ($i === 50) $s .= ' selected';
        }
        $s .= '>'.$i.'</option>';
    }
    $s .= '</select>';

    return $s;
}

// 불법접근 방지 토큰 생성
function get_admin_token()
{
    $token = md5(uniqid((string)mt_rand(), true));
    set_session('ss_admin_token', $token);
    return $token;
}

// 폼용 hidden 토큰 필드 반환
function get_admin_token_field()
{
    return '<input type="hidden" name="token" value="'.get_admin_token().'">';
}

// 메뉴 단위 권한 체크 — 정책상 운영자 전체 허용 (항상 통과, 에러 없음)
function auth_check_menu($auth, $sub_menu, $type = 'r', $return = false)
{
    return '';
}

// 토큰 검사
function check_admin_token()
{
    /*$token = get_session('ss_admin_token');
    set_session('ss_admin_token', '');

    $req_token = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';

    if (!$token || !$req_token || $token !== $req_token) {
        alert('올바른 방법으로 이용해 주십시오.', G5_URL);
    }*/

    return true;
}

// 관리자 페이지 referer 체크
function admin_referer_check($return = false)
{
    $referer = isset($_SERVER['HTTP_REFERER']) ? trim($_SERVER['HTTP_REFERER']) : '';
    if (!$referer) {
        $msg = '정보가 올바르지 않습니다.';
        if ($return) return $msg;
        alert($msg, G5_URL);
    }

    $p = @parse_url($referer);
    if (!is_array($p) || !isset($p['host'])) {
        $msg = '올바른 방법으로 이용해 주십시오.';
        if ($return) return $msg;
        alert($msg, G5_URL);
    }

    $host = preg_replace('/:[0-9]+$/', '', $_SERVER['HTTP_HOST']);
    if ($host !== $p['host']) {
        $msg = '올바른 방법으로 이용해 주십시오.';
        if ($return) return $msg;
        alert($msg, G5_URL);
    }
}

// ---------------- 접근 권한 및 메뉴 구성 ----------------

global $member, $is_admin, $g5;

if (!isset($member) || !is_array($member)) $member = array();
if (!isset($is_admin)) $is_admin = '';
if (!isset($g5) || !is_array($g5)) $g5 = array();

if (empty($member['mb_id'])) {
    // 로그인은 adm/ 으로 통합됨 — 어떤 컨텍스트에서든 canonical 으로 보냄
    goto_url(G5_ADMIN_URL.'/login.php');
}

// 관리자 세션 키 검사
$remote_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$mb_datetime = isset($member['mb_datetime']) ? $member['mb_datetime'] : '';

$admin_key = md5($mb_datetime.$remote_ip.$user_agent);
if (get_session('ss_mb_key') !== $admin_key) {
    session_destroy();
}

if (!isset($auth) || !is_array($auth)) {
    $auth = array();
}
ksort($auth);

// 가변 메뉴 — 현재 어드민 컨텍스트(adm/ 또는 adm_module/) 의 admin.menu*.php 만 로드
$auth_menu = array();
$menu = array();
$amenu = array();

if (is_dir(G5_ACTIVE_ADMIN_PATH)) {
    $tmp = dir(G5_ACTIVE_ADMIN_PATH);
    if ($tmp !== false) {
        while (false !== ($entry = $tmp->read())) {
            if (!preg_match('/^admin\.menu([0-9]{3}).*\.php$/', $entry, $m)) {
                continue;
            }
            $amenu[$m[1]] = $entry;
            include_once G5_ACTIVE_ADMIN_PATH.'/'.$entry;
        }
        $tmp->close();
    }
}

if (!empty($amenu) && is_array($amenu)) {
    ksort($amenu);
}

// 모듈 어드민(adm_module/) 컨텍스트에서만 모듈이 등록한 메뉴를 추가 병합.
// 코어 어드민(adm/)은 모듈 메뉴를 자동 포함하지 않는다.
if (defined('G5_MDL_ADMIN_PATH') && G5_ACTIVE_ADMIN_PATH === G5_MDL_ADMIN_PATH
    && function_exists('mdl_register_admin_menus')) {
    mdl_register_admin_menus();
}

$arr_query = array();
if (isset($sst))  $arr_query[] = 'sst='.$sst;
if (isset($sod))  $arr_query[] = 'sod='.$sod;
if (isset($sfl))  $arr_query[] = 'sfl='.$sfl;
if (isset($stx))  $arr_query[] = 'stx='.$stx;
if (isset($page)) $arr_query[] = 'page='.$page;
$qstr = implode('&amp;', $arr_query);


/**
 * 시기 선택 체크박스 그룹 헬퍼
 *  - 상점/메인슬라이드/공지사항 등 "전체 시기 (all) + 시기별 체크박스" UI를 통일.
 *
 * @param string $name      입력 name (배열 입력. 끝에 [] 안 붙여도 됨 — 자동 처리)
 * @param mixed  $db_value  DB 저장 값 (shop_time_to_db 형식: '' / 'all' / '||1||2||')
 * @param array  $opts      선택 옵션
 *   - 'help' (string) help 안내 문구. 기본: 표준 안내. 빈 문자열이면 안내 숨김.
 *   - 'id_prefix' (string) 체크박스 id 접두사. 기본: $name 에서 [] 제거한 값.
 *   - 'ti_type' (string|array) 특정 ti_type 만 노출 (예: 'au' 또는 array('au','renewal')). 머좌면 전체.
 */
function av_time_checkbox_group($name, $db_value, $opts = array())
{
    global $g5;

    $base   = preg_replace('/\[\]$/', '', $name);
    $prefix = isset($opts['id_prefix']) ? $opts['id_prefix'] : $base;
    $iname  = $base . '[]';

    $arr     = function_exists('shop_time_from_db') ? shop_time_from_db($db_value) : array();
    $all_chk = empty($arr);

    $ti_filter = isset($opts['ti_type']) ? $opts['ti_type'] : null;
    if (is_string($ti_filter) && $ti_filter !== '') $ti_filter = array($ti_filter);

    $rows = array();
    if (!empty($g5['time_table'])) {
        $where = '';
        if (is_array($ti_filter) && !empty($ti_filter)) {
            $esc = array();
            foreach ($ti_filter as $t) { $esc[] = "'" . sql_real_escape_string($t) . "'"; }
            $where = ' WHERE ti_type IN (' . implode(',', $esc) . ') ';
        }
        $r = sql_query("SELECT ti_id, ti_order, ti_name FROM `{$g5['time_table']}`{$where} ORDER BY ti_order ASC", false);
        if ($r) { while ($row = sql_fetch_array($r)) { $rows[] = $row; } }
    }

    $help_text = isset($opts['help'])
        ? $opts['help']
        : '※ 전체 시기 체크 또는 모두 미체크 시, 모든 시기에 출력됩니다.';
    if ($help_text !== '' && function_exists('help')) {
        echo help($help_text);
    }

    $all_id = $prefix . '_all';
    ?>
    <div class="av-time-checks">
        <span class="av-time-check av-time-check--all">
            <input type="checkbox" name="<?php echo $iname; ?>" id="<?php echo $all_id; ?>" value="all" <?php echo $all_chk ? 'checked' : ''; ?>
                onclick="(function(c){var ck=document.querySelectorAll('input[name=\'<?php echo $iname; ?>\']:not(#<?php echo $all_id; ?>)');for(var i=0;i<ck.length;i++){ck[i].checked=false;ck[i].disabled=c.checked;}})(this);">
            <label for="<?php echo $all_id; ?>"><strong>전체 시기 (all)</strong></label>
        </span>
        <?php foreach ($rows as $row):
            $tio = (int)$row['ti_order'];
            $cid = $prefix . '_' . $tio;
            $chk = in_array($tio, $arr, true) ? 'checked' : '';
            $dis = $all_chk ? 'disabled' : '';
        ?>
        <span class="av-time-check">
            <input type="checkbox" name="<?php echo $iname; ?>" id="<?php echo $cid; ?>" value="<?php echo $tio; ?>" <?php echo $chk; ?> <?php echo $dis; ?>
                onclick="if(this.checked){document.getElementById('<?php echo $all_id; ?>').checked=false;}">
            <label for="<?php echo $cid; ?>"><?php echo $tio; ?>. <?php echo htmlspecialchars($row['ti_name'], ENT_QUOTES, 'UTF-8'); ?></label>
        </span>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><span class="av-empty">등록된 시기가 없습니다.</span><?php endif; ?>
    </div>
    <?php
}
?>
