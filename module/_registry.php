<?php
/**
 * Module Registry — 모듈 메타 읽기, 활성/비활성, 의존성, 상태 저장
 */

function mdl_state_path() {
    return G5_DATA_PATH.'/module/state.json';
}

function mdl_load_state($force_reload = false) {
    static $cache = null;
    if ($cache !== null && !$force_reload) return $cache;
    $f = mdl_state_path();
    if (!is_file($f)) { $cache = array('active' => array(), 'versions' => array()); return $cache; }
    $raw = @file_get_contents($f);
    if ($raw === false) { $cache = array('active' => array(), 'versions' => array()); return $cache; }
    $j = @json_decode($raw, true);
    if (!is_array($j)) { $cache = array('active' => array(), 'versions' => array()); return $cache; }
    if (!isset($j['active'])) $j['active'] = array();
    if (!isset($j['versions'])) $j['versions'] = array();
    $cache = $j;
    return $cache;
}

function mdl_save_state($state) {
    $f = mdl_state_path();
    $dir = dirname($f);
    if (!is_dir($dir)) {
        @mkdir($dir, defined('G5_DIR_PERMISSION') ? G5_DIR_PERMISSION : 0755, true);
    }
    $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ok = @file_put_contents($f, $json, LOCK_EX) !== false;
    if ($ok) {
        // 캐시 무효화
        mdl_load_state(true);
        mdl_active_modules(true);
    }
    return $ok;
}

function mdl_read_meta($name) {
    static $cache = array();
    if (array_key_exists($name, $cache)) return $cache[$name];
    $f = G5_PATH.'/module/'.$name.'/module.php';
    if (!is_file($f)) { $cache[$name] = null; return null; }
    $meta = include $f;
    if (!is_array($meta)) return null;

    $defaults = array(
        'name'              => $name,
        'title'             => $name,
        'version'           => '0.0.0',
        'author'            => '',
        'description'       => '',
        'requires'          => array(),
        'depends'           => array(),
        'admin_menu'        => false,  // bool: 모듈 목록에 "설정" 버튼 노출 여부
        'admin_menus'       => array(), // array: 어드민 사이드바에 추가할 메뉴 그룹
        'module_type'       => '',     // 단독 활성화 그룹 (같은 type 은 1개만 활성 가능, 예: 'cdn')
        'version_banner'    => '',     // 외부 버전 안내 이미지 URL. 모듈 목록 카드 안에 표시
        'version_info_url'  => '',     // 배너 클릭 시 이동 (릴리즈 노트 등)
    );
    foreach ($defaults as $k => $v) {
        if (!isset($meta[$k])) $meta[$k] = $v;
    }
    $meta['name'] = $name;
    $cache[$name] = $meta;
    return $meta;
}

function mdl_safe_http_url($url)
{
    $url = trim((string)$url);
    if ($url === '' || preg_match('/[\s<>"\']/', $url)) {
        return '';
    }
    if (!preg_match('#^https?://[a-zA-Z0-9][^\s]*$#', $url)) {
        return '';
    }
    return $url;
}

/**
 * 모듈 메타의 version_banner 를 모은다. 이미지 URL 이 같으면 한 장만 남긴다.
 *
 * @param array|null $modules mdl_scan() 결과. null 이면 전체 스캔.
 * @return array list of array(img, href, alt, modules)
 */
function mdl_version_banners($modules = null)
{
    if ($modules === null) {
        $modules = function_exists('mdl_scan') ? mdl_scan() : array();
    }
    if (!is_array($modules)) {
        return array();
    }
    $out = array();
    $seen = array();
    foreach ($modules as $name => $meta) {
        if (!is_array($meta)) {
            continue;
        }
        $img = mdl_safe_http_url(isset($meta['version_banner']) ? $meta['version_banner'] : '');
        if ($img === '') {
            continue;
        }
        $key = strtolower($img);
        if (isset($seen[$key])) {
            $out[$seen[$key]]['modules'][] = (string)$name;
            continue;
        }
        $href = mdl_safe_http_url(isset($meta['version_info_url']) ? $meta['version_info_url'] : '');
        $title = (isset($meta['title']) && $meta['title'] !== '') ? (string)$meta['title'] : (string)$name;
        $seen[$key] = count($out);
        $out[] = array(
            'img'     => $img,
            'href'    => $href,
            'alt'     => $title.' 버전 정보',
            'modules' => array((string)$name),
        );
    }
    return $out;
}

function mdl_print_version_banners($modules = null)
{
    $banners = mdl_version_banners($modules);
    if (!$banners) {
        return;
    }
    $esc = function ($v) {
        return function_exists('h') ? h($v) : htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    };
    echo '<div class="ml-ver-banners">';
    foreach ($banners as $b) {
        $img = '<img src="'.$esc($b['img']).'" alt="'.$esc($b['alt']).'" referrerpolicy="no-referrer">';
        if ($b['href'] !== '') {
            echo '<a href="'.$esc($b['href']).'" target="_blank" rel="noopener noreferrer">'.$img.'</a>';
        } else {
            echo $img;
        }
    }
    echo '</div>';
}

function mdl_scan() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $base = G5_PATH.'/module';
    $modules = array();
    if (!is_dir($base)) return $modules;
    $d = @dir($base);
    if (!$d) return $modules;
    while (false !== ($entry = $d->read())) {
        if ($entry === '' || $entry[0] === '.' || $entry[0] === '_') continue;
        if (!is_dir($base.'/'.$entry)) continue;
        $meta = mdl_read_meta($entry);
        if ($meta) $modules[$entry] = $meta;
    }
    $d->close();
    ksort($modules);
    $cache = $modules;
    return $cache;
}

function mdl_check_requires($meta) {
    $req = isset($meta['requires']) ? $meta['requires'] : array();
    if (!empty($req['php'])) {
        if (version_compare(PHP_VERSION, $req['php'], '<')) {
            return 'PHP '.$req['php'].' 이상이 필요합니다 (현재 '.PHP_VERSION.')';
        }
    }
    if (!empty($req['g5']) && defined('G5_GNUBOARD_VER')) {
        if (version_compare(G5_GNUBOARD_VER, $req['g5'], '<')) {
            return 'Gnuboard '.$req['g5'].' 이상이 필요합니다';
        }
    }
    return true;
}

function mdl_check_depends($meta, $active) {
    $deps = isset($meta['depends']) ? $meta['depends'] : array();
    foreach ($deps as $dep) {
        if (!in_array($dep, $active, true)) {
            return '의존 모듈 "'.$dep.'" 가 활성화되지 않았습니다';
        }
    }
    return true;
}

function mdl_activate($name) {
    $meta = mdl_read_meta($name);
    if (!$meta) return '모듈 메타파일이 없습니다';

    $r = mdl_check_requires($meta);
    if ($r !== true) return $r;

    $state  = mdl_load_state();
    $active = $state['active'];

    $r = mdl_check_depends($meta, $active);
    if ($r !== true) return $r;

    if (in_array($name, $active, true)) {
        // 이미 활성: auto 메뉴 미할당이면 lazy ensure
        if (mdl_admin_menus_has_auto(isset($meta['admin_menus']) ? $meta['admin_menus'] : null)) {
            mdl_admin_menu_ensure_group($name);
        }
        return true;
    }

    // module_type 단독 그룹 처리: 같은 type 의 기존 활성 모듈을 자동 비활성화
    $my_type = isset($meta['module_type']) ? trim((string)$meta['module_type']) : '';
    if ($my_type !== '') {
        $modules = mdl_scan();
        foreach ($active as $other) {
            if ($other === $name) continue;
            if (!isset($modules[$other])) continue;
            $other_type = isset($modules[$other]['module_type']) ? trim((string)$modules[$other]['module_type']) : '';
            if ($other_type !== '' && $other_type === $my_type) {
                // uninstall 은 실행하지 않고 단순 비활성화 만
                mdl_deactivate($other, false);
            }
        }
        // 비활성화 이후 상태 재로드
        $state  = mdl_load_state();
        $active = $state['active'];
    }

    // install/upgrade 실행
    $old_ver = isset($state['versions'][$name]) ? $state['versions'][$name] : null;
    $cur_ver = $meta['version'];

    if ($old_ver === null) {
        // 신규 설치
        $f = G5_PATH.'/module/'.$name.'/install.php';
        if (is_file($f)) {
            global $g5, $config;
            include $f;
        }
    } else if (version_compare($old_ver, $cur_ver, '<')) {
        // 업그레이드
        $f = G5_PATH.'/module/'.$name.'/upgrade.php';
        if (is_file($f)) {
            global $g5, $config;
            $mdl_old_version = $old_ver;
            include $f;
        }
    }

    // auto admin_menus: 첫 설치·미할당 시 그룹 번호 영속 할당
    if (mdl_admin_menus_has_auto(isset($meta['admin_menus']) ? $meta['admin_menus'] : null)) {
        $ag = mdl_admin_menu_ensure_group($name);
        if ($ag === false) {
            return '어드민 메뉴 그룹 번호(100–899)가 모두 사용 중입니다';
        }
    }

    $active[] = $name;
    $state['active']            = $active;
    $state['versions'][$name]   = $cur_ver;
    mdl_save_state($state);
    return true;
}

function mdl_deactivate($name, $run_uninstall = false) {
    $state  = mdl_load_state();
    $active = array_values(array_diff($state['active'], array($name)));
    $state['active'] = $active;

    if ($run_uninstall) {
        $f = G5_PATH.'/module/'.$name.'/uninstall.php';
        if (is_file($f)) {
            global $g5, $config;
            include $f;
        }
        unset($state['versions'][$name]);
    }
    mdl_save_state($state);
    return true;
}

/**
 * install.php 만 실행. bootstrap 을 부르지 않는다.
 * 훅 이중 등록·lib 로드 시 schema_ensure 가 CREATE 보다 먼저 도는 것을 막는다.
 * $g5['*_table'] 별칭은 각 모듈 install 인라인 또는 lib.php 가 세팅한다.
 *
 * @return true|string 성공 true, 실패 메시지
 */
function mdl_run_install($name)
{
    $name = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$name);
    if ($name === '') {
        return '모듈 이름이 없습니다';
    }
    $f = G5_PATH.'/module/'.$name.'/install.php';
    if (!is_file($f)) {
        return $name.' 모듈에 install.php 가 없습니다';
    }
    global $g5, $config;
    include $f;
    return true;
}

function mdl_active_modules($force_reload = false) {
    static $cache = null;
    if ($cache !== null && !$force_reload) return $cache;
    if ($force_reload) { mdl_load_state(true); }
    $state    = mdl_load_state();
    $modules  = mdl_scan();
    $active   = array();
    foreach ($state['active'] as $n) {
        if (isset($modules[$n])) $active[$n] = $modules[$n];
    }

    // 의존성 위상정렬 (간단 버전: 의존 대상이 먼저 오도록 반복 정렬)
    $sorted = array();
    $remain = $active;
    $guard = 0;
    while (!empty($remain) && $guard < 100) {
        $guard++;
        foreach ($remain as $n => $m) {
            $deps_ok = true;
            foreach ($m['depends'] as $d) {
                if (isset($remain[$d])) { $deps_ok = false; break; }
            }
            if ($deps_ok) {
                $sorted[$n] = $m;
                unset($remain[$n]);
            }
        }
    }
    foreach ($remain as $n => $m) $sorted[$n] = $m; // 순환 의존시 그대로 추가
    $cache = $sorted;
    return $cache;
}

/**
 * 어드민 메뉴 등록 (admin.lib.php 가변 메뉴 로드 직후 호출)
 *
 * 활성 모듈의 module.php 에 정의된 'admin_menus' 배열을 읽어
 * $GLOBALS['menu'] / $GLOBALS['amenu'] 에 병합한다.
 *
 * 형식:
 *   - 고정 그룹(예: 기본 액션 '002'): 3자리 키 + 6자리 코드
 *   - 자동 그룹 'auto': 항목 코드는 뒤 3자리 suffix 만. 설치 시 할당된 그룹과 합쳐 6자리가 된다.
 *
 *   'admin_menus' => array(
 *       'auto' => array(
 *           array('000', '내 모듈', G5_MDL_ADMIN_URL.'/module.php?m=mymod&p=setting'),
 *           array('100', '설정',    G5_MDL_ADMIN_URL.'/module.php?m=mymod&p=setting'),
 *       ),
 *   ),
 */
define('MDL_ADMIN_MENU_GROUP_KEY', '_admin_menu_group');
define('MDL_ADMIN_MENU_GROUP_MIN', 100);
define('MDL_ADMIN_MENU_GROUP_MAX', 899);

/**
 * admin_menus 6번째 값이 'tab' 이면 사이드바에 안 넣고, 페이지 안 탭 발견용으로만 둔다.
 */
function mdl_admin_menu_is_tab($entry)
{
    return is_array($entry) && isset($entry[5]) && (string)$entry[5] === 'tab';
}

function mdl_admin_menus_has_auto($admin_menus)
{
    return is_array($admin_menus) && isset($admin_menus['auto']) && is_array($admin_menus['auto']);
}

/**
 * 시드 admin.menuNNN.php + 저장된 auto 그룹 + 메타의 고정 3자리 키.
 */
function mdl_admin_menu_used_groups()
{
    $used = array();

    // 시드 (adm_module: 000/001/002). 코어 adm/ 은 UI가 달라 번호를 점유하지 않는다.
    if (defined('G5_MDL_ADMIN_PATH') && is_dir(G5_MDL_ADMIN_PATH)) {
        $dh = @opendir(G5_MDL_ADMIN_PATH);
        if ($dh) {
            while (($entry = readdir($dh)) !== false) {
                if (preg_match('/^admin\.menu([0-9]{3}).*\.php$/', $entry, $m)) {
                    $used[$m[1]] = true;
                }
            }
            closedir($dh);
        }
    }

    $modules = function_exists('mdl_scan') ? mdl_scan() : array();
    foreach ($modules as $name => $meta) {
        if (!is_array($meta) || empty($meta['admin_menus']) || !is_array($meta['admin_menus'])) {
            continue;
        }
        foreach ($meta['admin_menus'] as $gk => $entries) {
            $gk = (string)$gk;
            if (preg_match('/^[0-9]{3}$/', $gk)) {
                $used[$gk] = true;
            }
        }
        if (function_exists('mdl_setting_get')) {
            $g = mdl_setting_get($name, MDL_ADMIN_MENU_GROUP_KEY, null);
            if ($g !== null && $g !== '' && preg_match('/^[0-9]{3}$/', (string)$g)) {
                $used[(string)$g] = true;
            }
        }
    }

    return $used;
}

/**
 * auto 모듈의 3자리 그룹을 확보·반환. 고정 그룹만 있으면 null.
 * @return string|null|false  성공 시 '100' 등, auto 아님 null, 슬롯 소진 false
 */
function mdl_admin_menu_ensure_group($name)
{
    $name = (string)$name;
    $meta = mdl_read_meta($name);
    if (!$meta || !mdl_admin_menus_has_auto(isset($meta['admin_menus']) ? $meta['admin_menus'] : null)) {
        return null;
    }
    if (!function_exists('mdl_setting_get') || !function_exists('mdl_setting_set')) {
        return false;
    }

    $cur = mdl_setting_get($name, MDL_ADMIN_MENU_GROUP_KEY, null);
    if ($cur !== null && $cur !== '' && preg_match('/^[0-9]{3}$/', (string)$cur)) {
        return (string)$cur;
    }

    $used = mdl_admin_menu_used_groups();
    for ($i = MDL_ADMIN_MENU_GROUP_MIN; $i <= MDL_ADMIN_MENU_GROUP_MAX; $i++) {
        $gk = sprintf('%03d', $i);
        if (isset($used[$gk])) {
            continue;
        }
        if (!mdl_setting_set($name, MDL_ADMIN_MENU_GROUP_KEY, $gk)) {
            return false;
        }
        return $gk;
    }
    return false;
}

/**
 * 모듈 그룹 코드(할당분). auto 가 아니면 null.
 */
function mdl_admin_menu_get_group($name)
{
    $name = (string)$name;
    $meta = mdl_read_meta($name);
    if (!$meta || !mdl_admin_menus_has_auto(isset($meta['admin_menus']) ? $meta['admin_menus'] : null)) {
        return null;
    }
    if (!function_exists('mdl_setting_get')) {
        return null;
    }
    $cur = mdl_setting_get($name, MDL_ADMIN_MENU_GROUP_KEY, null);
    if ($cur !== null && $cur !== '' && preg_match('/^[0-9]{3}$/', (string)$cur)) {
        return (string)$cur;
    }
    $ensured = mdl_admin_menu_ensure_group($name);
    return ($ensured === false) ? null : $ensured;
}

/**
 * suffix(3자리) 또는 레거시 6자리 → 현재 모듈의 6자리 메뉴 코드.
 */
function mdl_admin_menu_code($name, $suffix)
{
    $suffix = preg_replace('/\D/', '', (string)$suffix);
    if (strlen($suffix) >= 6) {
        $suffix = substr($suffix, -3);
    }
    $suffix = str_pad(substr($suffix, -3), 3, '0', STR_PAD_LEFT);

    $group = mdl_admin_menu_get_group($name);
    if ($group === null || $group === '') {
        // 고정 그룹 모듈: 메타에서 suffix 맞는 코드 검색, 없으면 그대로 반환 불가
        $meta = mdl_read_meta($name);
        if ($meta && !empty($meta['admin_menus']) && is_array($meta['admin_menus'])) {
            foreach ($meta['admin_menus'] as $gk => $entries) {
                if (!preg_match('/^[0-9]{3}$/', (string)$gk) || !is_array($entries)) {
                    continue;
                }
                foreach ($entries as $entry) {
                    if (!is_array($entry) || empty($entry[0])) {
                        continue;
                    }
                    $code = (string)$entry[0];
                    if (strlen($code) === 6 && substr($code, -3) === $suffix) {
                        return $code;
                    }
                }
                return (string)$gk.$suffix;
            }
        }
        return $suffix;
    }
    return $group.$suffix;
}

/**
 * admin_menus 를 사이드바·resolve 용으로 전개.
 * 'auto' → 할당 그룹 + suffix 6자리. 숫자 키는 그대로.
 */
function mdl_admin_menus_normalize($name, $admin_menus)
{
    if (!is_array($admin_menus) || empty($admin_menus)) {
        return array();
    }

    $out = array();
    $group = null;
    if (mdl_admin_menus_has_auto($admin_menus)) {
        $group = mdl_admin_menu_ensure_group($name);
        if ($group === false) {
            $group = null;
        }
    }

    foreach ($admin_menus as $gk => $entries) {
        if (!is_array($entries)) {
            continue;
        }
        $gk = (string)$gk;
        if ($gk === 'auto') {
            if ($group === null || $group === '') {
                continue;
            }
            $resolved = array();
            foreach ($entries as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $e = $entry;
                $raw = isset($e[0]) ? preg_replace('/\D/', '', (string)$e[0]) : '';
                if ($raw === '') {
                    continue;
                }
                $suffix = (strlen($raw) >= 6) ? substr($raw, -3) : str_pad(substr($raw, -3), 3, '0', STR_PAD_LEFT);
                $e[0] = $group.$suffix;
                $resolved[] = $e;
            }
            if (!empty($resolved)) {
                $out[$group] = $resolved;
            }
            continue;
        }
        if (!preg_match('/^[0-9]{3}$/', $gk)) {
            continue;
        }
        $out[$gk] = $entries;
    }
    return $out;
}

/**
 * 모듈 메타의 admin_menus 전개본.
 */
function mdl_admin_menus_for($name, $meta = null)
{
    if ($meta === null) {
        $meta = mdl_read_meta($name);
    }
    if (!$meta || empty($meta['admin_menus']) || !is_array($meta['admin_menus'])) {
        return array();
    }
    return mdl_admin_menus_normalize($name, $meta['admin_menus']);
}

/**
 * 등록 시 중복 6자리 코드 (나중 항목 스킵용).
 * @return array list of array(code, module, label)
 */
function mdl_admin_menu_collect_code_collisions()
{
    $seen = array();
    $collisions = array();
    $active = function_exists('mdl_active_modules') ? mdl_active_modules() : array();
    foreach ($active as $name => $meta) {
        $menus = mdl_admin_menus_for($name, $meta);
        foreach ($menus as $entries) {
            if (!is_array($entries)) {
                continue;
            }
            foreach ($entries as $entry) {
                if (!is_array($entry) || empty($entry[0])) {
                    continue;
                }
                if (mdl_admin_menu_is_tab($entry)) {
                    continue;
                }
                $code = (string)$entry[0];
                if (isset($seen[$code])) {
                    $collisions[] = array(
                        'code'   => $code,
                        'module' => $name,
                        'label'  => isset($entry[1]) ? (string)$entry[1] : '',
                        'first'  => $seen[$code],
                    );
                } else {
                    $seen[$code] = $name;
                }
            }
        }
    }
    return $collisions;
}

function mdl_register_admin_menus() {
    if (!isset($GLOBALS['menu']))  $GLOBALS['menu']  = array();
    if (!isset($GLOBALS['amenu'])) $GLOBALS['amenu'] = array();

    $seen_codes = array();
    $active = mdl_active_modules();
    foreach ($active as $name => $meta) {
        if (empty($meta['admin_menus']) || !is_array($meta['admin_menus'])) continue;

        $menus = mdl_admin_menus_normalize($name, $meta['admin_menus']);
        foreach ($menus as $gk => $entries) {
            if (!is_array($entries)) continue;
            $gk = (string)$gk;
            if (!preg_match('/^[0-9]{3}$/', $gk)) continue;

            $menu_key = 'menu'.$gk;
            if (!isset($GLOBALS['menu'][$menu_key])) {
                $GLOBALS['menu'][$menu_key] = array();
            }
            foreach ($entries as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                if (mdl_admin_menu_is_tab($entry)) {
                    continue;
                }
                $code = isset($entry[0]) ? (string)$entry[0] : '';
                if ($code !== '' && isset($seen_codes[$code])) {
                    continue; // 중복 6자리: 먼저 등록된 항목 유지
                }
                if ($code !== '') {
                    $seen_codes[$code] = $name;
                }
                $GLOBALS['menu'][$menu_key][] = $entry;
            }
            if (!isset($GLOBALS['amenu'][$gk])) {
                $GLOBALS['amenu'][$gk] = 'module:'.$name;
            }
        }
    }

    if (!empty($GLOBALS['amenu']) && is_array($GLOBALS['amenu'])) {
        ksort($GLOBALS['amenu']);
    }
}

/**
 * 관리 페이지 p 를 사이드바 메뉴의 p 로 대응 (폼/업데이트 등).
 */
function mdl_admin_page_alias($page)
{
    $page = (string)$page;
    $map = array(
        'skill_form'                => 'skill',
        'skill_form_update'         => 'skill',
        'skill_update'              => 'skill',
        'skill_info'                => 'skill',
        'stat_func_update'          => 'stat_func',
        'monster_update'            => 'monster',
        'mo_skill_update'           => 'mo_skill',
        'ch_skill_update'           => 'ch_skill',
        'raid_list_update'          => 'raid_list',
        'unit_insert'               => 'raid_list',
        'unit_insert_update'        => 'raid_list',
        'unit_update'               => 'raid_list',
        'setting_update'            => 'setting',
        'equip_list_update'         => 'equip_list',
        'equip_form'                => 'equip_list',
        'equip_form_update'         => 'equip_list',
        'equip_upgrade_update'      => 'equip_upgrade',
        'upgrade'                   => 'equip_upgrade',
        'equip_upgrade_log_delete'  => 'equip_upgrade_log',
        'upgrade_log'               => 'equip_upgrade_log',
        'mo_pattern_update'         => 'mo_pattern',
    );
    if (isset($map[$page])) {
        return $map[$page];
    }
    // *_form / *_form_update → 메뉴에 있는 *_list (사이드바는 head 전에 p 로 고름)
    if (strlen($page) > 12 && substr($page, -12) === '_form_update') {
        return substr($page, 0, -12).'_list';
    }
    if (strlen($page) > 5 && substr($page, -5) === '_form') {
        return substr($page, 0, -5).'_list';
    }
    if (strlen($page) > 7 && substr($page, -7) === '_update') {
        return substr($page, 0, -7);
    }
    if (strlen($page) > 7 && substr($page, -7) === '_delete') {
        return substr($page, 0, -7);
    }
    return $page;
}

/**
 * admin_menus 의 'tab' 항목 p 를, 같은 그룹에서 바로 앞의 사이드바 허브 p 로 대응.
 * 탭이 허브와 다른 PHP(p) 를 쓰면 resolve 가 메뉴를 못 찾아 sub_menu 가 001100 으로 떨어진다.
 */
function mdl_admin_tab_hub_page($m, $p)
{
    $m = (string)$m;
    $p = (string)$p;
    if ($m === '' || $p === '') {
        return '';
    }
    $meta = function_exists('mdl_read_meta') ? mdl_read_meta($m) : null;
    if (!$meta) {
        return '';
    }
    $menus = mdl_admin_menus_for($m, $meta);
    foreach ($menus as $entries) {
        if (!is_array($entries)) {
            continue;
        }
        $hub_p = '';
        foreach ($entries as $entry) {
            if (!is_array($entry) || empty($entry[2])) {
                continue;
            }
            $link = str_replace('&amp;', '&', (string)$entry[2]);
            $qs = parse_url($link, PHP_URL_QUERY);
            if ($qs === null || $qs === false || $qs === '') {
                continue;
            }
            $menu_q = array();
            parse_str($qs, $menu_q);
            if (empty($menu_q['p'])) {
                continue;
            }
            $ep = (string)$menu_q['p'];
            if (mdl_admin_menu_is_tab($entry)) {
                if ($ep === $p && $hub_p !== '') {
                    return $hub_p;
                }
            } else {
                $hub_p = $ep;
            }
        }
    }
    return '';
}

/**
 * 현재 요청에 맞는 admin_menus 코드(sub_menu)를 고른다.
 * 같은 PHP 를 unit_type / category / raid_type 탭으로 나누는 메뉴를
 * 쿼리스트링까지 비교해 가장 구체적인 항목에 포인트를 맞춘다.
 */
function mdl_resolve_admin_sub_menu($m, $p, $params = null)
{
    $m = (string)$m;
    $p = (string)$p;
    if ($params === null) {
        $params = array();
        if (isset($_GET) && is_array($_GET)) {
            $params = array_merge($params, $_GET);
        }
        if (isset($_POST) && is_array($_POST)) {
            $params = array_merge($params, $_POST);
        }
    }
    if (!is_array($params)) {
        $params = array();
    }

    if ((!isset($params['unit_type']) || $params['unit_type'] === '') && isset($params['back_unit_type'])) {
        $params['unit_type'] = $params['back_unit_type'];
    }
    if ((!isset($params['raid_type']) || $params['raid_type'] === '' || $params['raid_type'] === 'all') && isset($params['back_raid_type']) && $params['back_raid_type'] !== '' && $params['back_raid_type'] !== 'all') {
        $params['raid_type'] = $params['back_raid_type'];
    }
    if (isset($params['raid_type']) && is_array($params['raid_type'])) {
        $params['raid_type'] = reset($params['raid_type']);
    }
    if (isset($params['raid_type']) && ($params['raid_type'] === '' || $params['raid_type'] === 'all')) {
        unset($params['raid_type']);
    }
    if (isset($params['category']) && $params['category'] === '') {
        unset($params['category']);
    }

    $pages = array($p);
    $alias = mdl_admin_page_alias($p);
    if ($alias !== $p) {
        $pages[] = $alias;
    }
    $tab_hub = mdl_admin_tab_hub_page($m, $p);
    if ($tab_hub !== '' && !in_array($tab_hub, $pages, true)) {
        $pages[] = $tab_hub;
    }
    // 몬스터 보유 스킬은 사이드바에서 캐릭터 보유 스킬과 한 메뉴로 묶인다.
    if ($p === 'mo_skill' || $alias === 'mo_skill') {
        $pages[] = 'ch_skill';
    }

    $best_code  = '001100';
    $best_score = -1;

    $active = function_exists('mdl_active_modules') ? mdl_active_modules() : array();
    foreach ($active as $mod_name => $meta) {
        if (empty($meta['admin_menus']) || !is_array($meta['admin_menus'])) {
            continue;
        }
        $menus = mdl_admin_menus_for($mod_name, $meta);
        foreach ($menus as $entries) {
            if (!is_array($entries)) {
                continue;
            }
            foreach ($entries as $entry) {
                if (!is_array($entry) || empty($entry[0]) || empty($entry[2])) {
                    continue;
                }
                if (mdl_admin_menu_is_tab($entry)) {
                    continue;
                }
                $code = (string)$entry[0];
                $link = str_replace('&amp;', '&', (string)$entry[2]);
                $qs   = parse_url($link, PHP_URL_QUERY);
                if ($qs === null || $qs === false || $qs === '') {
                    continue;
                }
                $menu_q = array();
                parse_str($qs, $menu_q);
                if (empty($menu_q['m']) || empty($menu_q['p'])) {
                    continue;
                }
                if ((string)$menu_q['m'] !== $m) {
                    continue;
                }
                if (!in_array((string)$menu_q['p'], $pages, true)) {
                    continue;
                }

                $score     = 0;
                $mismatch  = false;
                foreach ($menu_q as $k => $v) {
                    if ($k === 'm' || $k === 'p') {
                        continue;
                    }
                    // 캐릭터/몬스터는 페이지 안 탭. 사이드바는 스킬 등록·보유 스킬 허브만 가리킨다.
                    if ($k === 'unit_type') {
                        continue;
                    }
                    $req_v = isset($params[$k]) ? $params[$k] : '';
                    if (is_array($req_v)) {
                        $req_v = reset($req_v);
                    }
                    if ((string)$req_v === (string)$v) {
                        $score += 10;
                    } else {
                        $mismatch = true;
                        break;
                    }
                }
                if ($mismatch) {
                    continue;
                }
                if (substr($code, -3) === '000') {
                    $score -= 1;
                }
                if ($score > $best_score) {
                    $best_score = $score;
                    $best_code  = $code;
                }
            }
        }
    }

    return $best_code;
}

/* ==========================================================================
 * 게시판별 모듈 on/off
 *
 * - 모듈 메타에 'board_scope' => true 선언 시 board_form 화면에 노출됨
 * - data/module/board_state.json 에 게시판별 항목만 기록 (default 와 다른 값만)
 *   { "bo_table": { "module_name": false }, ... }
 * - 키가 없으면 모듈 메타의 'board_default'(기본 'on') 따름
 *   'on'  : 기본 활성   (board_state 에 false 로 저장 시 비활성)
 *   'off' : 기본 비활성 (board_state 에 true  로 저장 시 활성)
 * ========================================================================== */

function mdl_board_state_path() {
    return G5_DATA_PATH.'/module/board_state.json';
}

function mdl_load_board_state($force_reload = false) {
    if (!$force_reload && isset($GLOBALS['mdl_board_state_cache'])) {
        return $GLOBALS['mdl_board_state_cache'];
    }
    $f = mdl_board_state_path();
    $state = array();
    if (is_file($f)) {
        $raw = @file_get_contents($f);
        if ($raw !== false) {
            $j = @json_decode($raw, true);
            if (is_array($j)) $state = $j;
        }
    }
    $GLOBALS['mdl_board_state_cache'] = $state;
    return $state;
}

function mdl_save_board_state($state) {
    $f = mdl_board_state_path();
    $dir = dirname($f);
    if (!is_dir($dir)) {
        @mkdir($dir, defined('G5_DIR_PERMISSION') ? G5_DIR_PERMISSION : 0755, true);
    }
    $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ok = (@file_put_contents($f, $json, LOCK_EX) !== false);
    if ($ok) $GLOBALS['mdl_board_state_cache'] = $state;
    return $ok;
}

/**
 * 게시판별 모듈 활성 여부.
 *
 * @param string      $bo_table     게시판 ID
 * @param string      $module_name  모듈명
 * @param string|null $scope        ('write'|'comment'|null) — null 이거나 모듈이 scopes 메타가 없으면 모듈 단위 토글.
 *                                  scopes 가 정의된 모듈은 scope 별로 별도 ON/OFF 가능.
 *                                  state key: "<module>@<scope>" (없으면 "<module>" 으로 fallback).
 */
function mdl_board_enabled($bo_table, $module_name, $scope = null) {
    if ($bo_table === '' || $bo_table === null) return false;
    if (empty($GLOBALS['mdl_active'][$module_name])) return false;

    $meta  = $GLOBALS['mdl_active'][$module_name];
    $state = mdl_load_board_state();
    $scopes = isset($meta['scopes']) && is_array($meta['scopes']) ? $meta['scopes'] : array();

    // scope 분리 토글
    if ($scope !== null && in_array($scope, $scopes, true)) {
        $key = $module_name.'@'.$scope;
        if (isset($state[$bo_table][$key])) {
            return (bool)$state[$bo_table][$key];
        }
        // scope 별 별도 키가 없으면 모듈 단위 키 fallback
    }

    if (isset($state[$bo_table][$module_name])) {
        return (bool)$state[$bo_table][$module_name];
    }
    $default = isset($meta['board_default']) ? $meta['board_default'] : 'on';
    return ($default !== 'off');
}

/**
 * 글/댓글 작성 모드에 맞는 모듈 활성 여부.
 * w=c/cu → comment 스코프, 그 외 → write 스코프 (mmb와 동일).
 *
 * @param string $bo_table
 * @param string $module_name
 * @param string $w  ''|'u'|'c'|'cu' 등
 */
function mdl_board_enabled_for_w($bo_table, $module_name, $w = '') {
    $scope = ($w === 'c' || $w === 'cu') ? 'comment' : 'write';
    return mdl_board_enabled($bo_table, $module_name, $scope);
}

/**
 * board_scope=true 인 활성 모듈 목록 (board_form 화면용).
 */
function mdl_board_scope_modules() {
    $list = array();
    if (empty($GLOBALS['mdl_active']) || !is_array($GLOBALS['mdl_active'])) return $list;
    foreach ($GLOBALS['mdl_active'] as $name => $meta) {
        if (!empty($meta['board_scope'])) $list[$name] = $meta;
    }
    return $list;
}

/**
 * 게시판 1개의 모듈 on/off 일괄 저장.
 *   $enabled_map: array(
 *      'module_name'           => bool,    // 모듈 단위 토글
 *      'module_name@write'     => bool,    // scope 분리 모듈은 scope 키 사용
 *      'module_name@comment'   => bool,
 *   )
 *   메타 default 와 동일한 값은 저장하지 않음 (JSON 비대화 방지).
 */
function mdl_set_board_modules($bo_table, $enabled_map) {
    if ($bo_table === '' || !is_array($enabled_map)) return false;
    $state = mdl_load_board_state();
    $scope = mdl_board_scope_modules();

    if (!isset($state[$bo_table]) || !is_array($state[$bo_table])) {
        $state[$bo_table] = array();
    }

    foreach ($scope as $name => $meta) {
        $default_on = (!isset($meta['board_default']) || $meta['board_default'] !== 'off');
        $scopes = isset($meta['scopes']) && is_array($meta['scopes']) ? $meta['scopes'] : array();

        if (!empty($scopes)) {
            // scope 분리 모듈: 각 scope 별 키 처리
            foreach ($scopes as $sc) {
                $key = $name.'@'.$sc;
                $enabled = !empty($enabled_map[$key]);
                if ($enabled === $default_on) {
                    unset($state[$bo_table][$key]);
                } else {
                    $state[$bo_table][$key] = $enabled;
                }
            }
            // 모듈 단위 키는 정리 (scope 모듈에서는 사용 안 함)
            unset($state[$bo_table][$name]);
        } else {
            // 모듈 단위 토글
            $enabled = !empty($enabled_map[$name]);
            if ($enabled === $default_on) {
                unset($state[$bo_table][$name]);
            } else {
                $state[$bo_table][$name] = $enabled;
            }
        }
    }

    if (empty($state[$bo_table])) {
        unset($state[$bo_table]);
    }
    return mdl_save_board_state($state);
}

/**
 * 게시판 삭제 시 호출.
 */
function mdl_delete_board_state($bo_table) {
    if ($bo_table === '') return false;
    $state = mdl_load_board_state();
    if (!isset($state[$bo_table])) return true;
    unset($state[$bo_table]);
    return mdl_save_board_state($state);
}

/* ==========================================================================
 * 스킨 훅 감지
 *
 * 주어진 경로(들)의 PHP 파일을 재귀적으로 스캔하여
 * mdl_apply_filters('hook'/mdl_do_action('hook' 호출이 있는지 검사.
 * 경로별로 캐시 (요청당 1회).
 *
 * 스킨 외부에서 발생하는 훅(코어 bbs/, bbs/<skin>/ 템플릿 등)도
 * "사용 가능"으로 간주하기 위해 호출측에서 여러 경로를 합쳐 전달.
 * ========================================================================== */
function _mdl_scan_hooks_in_path($path) {
    static $cache = array();
    if (!is_dir($path)) return array();
    $key = realpath($path);
    if ($key === false) return array();
    if (isset($cache[$key])) return $cache[$key];

    $found = array();
    try {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iter as $f) {
            $name = $f->getFilename();
            if (substr($name, -4) !== '.php') continue;
            $content = @file_get_contents($f->getPathname());
            if ($content === false) continue;
            if (preg_match_all('/mdl_(?:apply_filters|do_action)\s*\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $content, $m)) {
                foreach ($m[1] as $hk) $found[$hk] = true;
            }
            // 프록시 함수 — 내부에서 특정 훅을 발사하는 헬퍼.
            // 스킨/컨트롤러가 이 함수를 호출하면 해당 훅을 지원한 것으로 간주.
            //   bbs_render_row_extra() → bbs_view_row_extra
            // 단, 함수 정의 자체(`function bbs_render_row_extra`)만 들어있는 파일은 제외.
            if (strpos($content, 'bbs_render_row_extra(') !== false
                && strpos($content, 'function bbs_render_row_extra') === false) {
                $found['bbs_view_row_extra'] = true;
            }
            // 공용 글루 require — bbs/_module_write_update.inc.php 가 발사하는 훅들
            // 타임라인 ajax / bbs 컨트롤러가 이 파일을 require 하면 지원으로 간주.
            if (strpos($content, '_module_write_update.inc.php') !== false) {
                $found['bbs_custom_action']      = true;
                $found['bbs_customer_sql']       = true;
                $found['bbs_memo_custom_sql']    = true;
                $found['bbs_write_update_before'] = true;
                $found['bbs_write_update_after']  = true;
            }
        }
    } catch (Exception $e) {
        // 무시
    }
    $cache[$key] = $found;
    return $found;
}

/**
 * 단일 또는 여러 경로에서 훅 지원 여부 검사.
 *  $skin_path: string | array
 */
function mdl_skin_supports_hooks($skin_path, $hooks) {
    $result = array();
    foreach ($hooks as $h) $result[$h] = false;

    $paths = is_array($skin_path) ? $skin_path : array($skin_path);
    $merged = array();
    foreach ($paths as $p) {
        if (!$p) continue;
        $found = _mdl_scan_hooks_in_path($p);
        foreach ($found as $hk => $_) $merged[$hk] = true;
    }

    foreach ($hooks as $h) {
        if (!empty($merged[$h])) $result[$h] = true;
    }
    return $result;
}

/**
 * 모듈의 requires_hooks 가 해당 스킨에서 모두 지원되는지 검사.
 * 반환: array('supported'=>bool, 'missing'=>array('hook',...))
 */
function mdl_module_skin_compat($module_meta, $skin_path) {
    $req = isset($module_meta['requires_hooks']) ? $module_meta['requires_hooks'] : array();
    if (!is_array($req) || empty($req)) {
        return array('supported' => true, 'missing' => array());
    }

    // 스킨 외부 훅 발생 위치도 함께 스캔:
    //  1) 코어 bbs/ 최상위 PHP (write_update.php, write_comment_update.php, _head.php 등)
    //  2) bbs/<skin_basename>/ 템플릿 (mmb 등 스킨 컨트롤러/템플릿이 분리된 경우)
    $paths = array($skin_path);
    if (defined('G5_PATH')) {
        $paths[] = G5_PATH.'/bbs';
        $skin_name = basename(rtrim($skin_path, '/\\'));
        if ($skin_name !== '' && is_dir(G5_PATH.'/bbs/'.$skin_name)) {
            $paths[] = G5_PATH.'/bbs/'.$skin_name;
        }
    }

    $sup = mdl_skin_supports_hooks($paths, $req);
    $missing = array();
    foreach ($req as $h) {
        if (empty($sup[$h])) $missing[] = $h;
    }
    return array('supported' => empty($missing), 'missing' => $missing);
}

/* ==========================================================================
 * 훅 위치 맵 (hook_location_map)
 *
 * 사이트 전체에서 mdl_do_action / mdl_apply_filters 가 호출되는 위치를
 * 사전에 스캔하여 "어느 훅이 어느 파일/라인에서 발사되는가" 의 맵을 만든다.
 * 모듈 검증 시 "현재 홈페이지 상태(테마 오버라이드 반영)" 를 따져야 하므로,
 * 스캔 경로는 활성 프리셋의 슬롯 매핑을 반영한 실제 파일 경로 집합이어야 한다.
 *
 * 반환: array(
 *     '<hook_name>' => array(
 *          array('file'=>'<workspace 상대경로>', 'line'=>123, 'kind'=>'action|filter'),
 *          ...
 *     ),
 *     ...
 * )
 *
 * 캐시: 요청당 1회. data/cache/mdl_hooks.json 디스크 캐시는 사용하지 않음
 * (테마/스킨 오버라이드가 변경될 때마다 신선해야 하므로).
 * ========================================================================== */

/**
 * "현재 홈페이지 상태" 를 반영한 스캔 대상 경로 집합을 반환.
 *  - 코어 디렉토리들 (bbs, shop, inventory, mypage, member, world, adm, ajax, head/tail/main 등)
 *  - 활성 프리셋의 file/dir 슬롯에 매핑된 테마 디렉토리 (해당 슬롯 경로만)
 *  - 활성 프리셋의 skin 슬롯에 매핑된 테마 스킨 디렉토리
 *  - skin/board/{mmb,basic,qna,calculate} (테마 오버라이드된 경로 우선)
 *  - 활성 모듈의 bootstrap.php 자체 (자기 모듈이 발사하는 훅)
 */
function mdl_hook_scan_paths() {
    if (!defined('G5_PATH')) return array();
    $base = G5_PATH;
    $paths = array();

    // 코어 디렉토리
    // - extend / lib : 코어 라이브러리에서 발사하는 훅 (예: cdn_*, time, notice, world)
    // - css : 동적 CSS 가 발사하는 훅 (예: world_root.php 의 cdn_*)
    // - couple / notice : 자체 진입점이 있는 모듈성 페이지
    $core_dirs = array('bbs', 'shop', 'inventory', 'mypage', 'member', 'world', 'adm', 'adm_module', 'ajax', 'extend', 'lib', 'css', 'couple', 'notice', 'timeline');
    foreach ($core_dirs as $d) {
        if (is_dir($base.'/'.$d)) $paths[] = $base.'/'.$d;
    }
    // 코어 단일 파일
    $core_files = array('head.sub.php', 'tail.sub.php', 'head.php', 'tail.php', 'main.php', 'index.php', 'common.php', 'enter.php', 'module/_widget.php');
    foreach ($core_files as $f) {
        if (is_file($base.'/'.$f)) $paths[] = $base.'/'.$f;
    }

    // 게시판 스킨 — 테마 오버라이드 반영
    $board_skins = array('mmb', 'basic', 'qna', 'calculate');
    foreach ($board_skins as $sk) {
        $default = $base.'/skin/board/'.$sk;
        if (function_exists('get_skin_path')) {
            $resolved = get_skin_path('board', $sk);
            if ($resolved && is_dir($resolved)) {
                $paths[] = $resolved;
                continue;
            }
        }
        if (is_dir($default)) $paths[] = $default;
    }
    // outlogin / world / timeline 스킨 (skin 타입)
    $skin_slots_extra = array(
        'skin_outlogin_basic' => 'skin/outlogin/basic',
        'skin_world_basic'    => 'skin/world/basic',
        'skin_timeline_basic' => 'skin/timeline/basic',
    );
    foreach ($skin_slots_extra as $slot => $rel) {
        if (function_exists('theme_skin_dir')) {
            $resolved = theme_skin_dir($slot, $base.'/'.$rel);
            if (is_dir($resolved)) $paths[] = $resolved;
        } else if (is_dir($base.'/'.$rel)) {
            $paths[] = $base.'/'.$rel;
        }
    }

    // 활성 프리셋의 file/dir 슬롯이 default 가 아닌 경우, 그 테마 경로도 스캔 대상
    if (function_exists('get_preset') && !empty($GLOBALS['THEME_SLOT_MAP'])) {
        $preset = get_preset();
        $slots  = isset($preset['slots']) ? $preset['slots'] : array();
        foreach ($GLOBALS['THEME_SLOT_MAP'] as $slot_key => $slot_def) {
            if (!isset($slots[$slot_key])) continue;
            $theme_name = $slots[$slot_key];
            if ($theme_name === '' || $theme_name === 'default') continue;
            if (!function_exists('is_valid_theme') || !is_valid_theme($theme_name)) continue;
            $type = isset($slot_def['type']) ? $slot_def['type'] : 'file';
            $rel  = isset($slot_def['path']) ? $slot_def['path'] : '';
            if ($rel === '') continue;
            if (function_exists('theme_resolve_base')) {
                $tb = theme_resolve_base($theme_name);
                if ($tb['abs'] === '') continue;
                $abs = $tb['abs'].'/'.$rel;
            } else {
                $abs = $base.'/'.G5_THEME_DIR.'/'.$theme_name.'/'.$rel;
            }
            if ($type === 'file' && is_file($abs)) $paths[] = $abs;
            else if (($type === 'dir' || $type === 'skin') && is_dir($abs)) $paths[] = $abs;
        }
    }

    // 활성 모듈의 bootstrap.php (자기 발사 훅 검출용)
    if (!empty($GLOBALS['mdl_active']) && is_array($GLOBALS['mdl_active'])) {
        foreach ($GLOBALS['mdl_active'] as $mn => $_meta) {
            $bf = $base.'/module/'.$mn;
            if (is_dir($bf)) $paths[] = $bf;
        }
    }

    // 중복 제거 (realpath)
    $unique = array();
    foreach ($paths as $p) {
        $rp = @realpath($p);
        if ($rp === false) continue;
        $unique[$rp] = true;
    }
    return array_keys($unique);
}

/**
 * 단일 파일에서 mdl_do_action / mdl_apply_filters 호출 라인 추출.
 * 반환: array(array('hook','line','kind'), ...)
 */
function _mdl_extract_hook_calls_in_file($file) {
    $out = array();
    $content = @file_get_contents($file);
    if ($content === false) return $out;
    // 빠른 게이트: mdl_* 호출이나 등록된 프록시 함수 호출이 하나도 없으면 건너뜀
    $has_mdl   = (strpos($content, 'mdl_do_action') !== false) || (strpos($content, 'mdl_apply_filters') !== false);
    $has_proxy = (strpos($content, 'bbs_render_row_extra(') !== false)
              || (strpos($content, '_module_write_update.inc.php') !== false);
    if (!$has_mdl && !$has_proxy) {
        return $out;
    }
    // 라인 번호 추출을 위해 줄 단위 매칭
    $lines = preg_split("/\r\n|\n|\r/", $content);
    foreach ($lines as $i => $ln) {
        if (preg_match_all('/mdl_(do_action|apply_filters)\s*\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $ln, $m, PREG_SET_ORDER)) {
            foreach ($m as $mm) {
                $kind = ($mm[1] === 'do_action') ? 'action' : 'filter';
                $out[] = array('hook' => $mm[2], 'line' => $i + 1, 'kind' => $kind);
            }
        }
        // 프록시: bbs_render_row_extra(...) → bbs_view_row_extra
        // 단, 함수 정의 라인은 제외 (module/_skin.php 의 function bbs_render_row_extra)
        if (strpos($ln, 'bbs_render_row_extra(') !== false
            && strpos($ln, 'function bbs_render_row_extra') === false) {
            $out[] = array('hook' => 'bbs_view_row_extra', 'line' => $i + 1, 'kind' => 'filter');
        }
        // 프록시: require .../_module_write_update.inc.php → bbs_custom_action 등
        if (strpos($ln, '_module_write_update.inc.php') !== false) {
            foreach (array(
                array('bbs_custom_action', 'filter'),
                array('bbs_customer_sql', 'filter'),
                array('bbs_memo_custom_sql', 'filter'),
                array('bbs_write_update_before', 'action'),
                array('bbs_write_update_after', 'action'),
            ) as $_hk) {
                $out[] = array('hook' => $_hk[0], 'line' => $i + 1, 'kind' => $_hk[1]);
            }
        }
    }
    return $out;
}

/**
 * 디렉토리 또는 파일을 재귀 스캔하여 훅 호출 위치 수집.
 */
function _mdl_collect_hook_calls($path, &$out, $base_path) {
    if (is_file($path)) {
        if (substr($path, -4) !== '.php') return;
        $rel = ltrim(str_replace('\\', '/', substr($path, strlen($base_path))), '/');
        foreach (_mdl_extract_hook_calls_in_file($path) as $c) {
            $c['file'] = $rel;
            $out[$c['hook']][] = $c;
        }
        return;
    }
    if (!is_dir($path)) return;
    try {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iter as $f) {
            if (substr($f->getFilename(), -4) !== '.php') continue;
            $abs = $f->getPathname();
            $rel = ltrim(str_replace('\\', '/', substr($abs, strlen($base_path))), '/');
            foreach (_mdl_extract_hook_calls_in_file($abs) as $c) {
                $c['file'] = $rel;
                $out[$c['hook']][] = $c;
            }
        }
    } catch (Exception $e) {
        // 무시
    }
}

/**
 * "현재 홈페이지 상태" 를 반영한 hook → locations 맵.
 * 요청당 1회 캐시.
 */
function mdl_hook_location_map() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = array();
    $base_path = realpath(G5_PATH);
    if ($base_path === false) return $cache;

    $paths = mdl_hook_scan_paths();
    foreach ($paths as $p) {
        _mdl_collect_hook_calls($p, $cache, $base_path);
    }
    return $cache;
}

/**
 * 모듈이 사용한다고 선언한 훅 목록을 반환.
 *
 * 우선순위:
 *  1) module/<name>/_hook_list.php — array return (선언적)
 *      형식: <?php return array(
 *                'uses' => array('hook1','hook2'),    // 모듈이 거는 훅
 *                'fires'=> array('hook3'),            // 모듈이 발사하는 훅 (선택)
 *            );
 *      또는 단순 평면 array('hook1','hook2', ...) — uses 만 선언.
 *  2) module.php 메타의 'requires_hooks' (board_scope 모듈 호환)
 *  3) bootstrap.php 본문 정규식 스캔 (mdl_add_action / mdl_add_filter 의 첫 인자)
 */
function mdl_module_hooks_used($name) {
    static $cache = array();
    if (isset($cache[$name])) return $cache[$name];

    $base = G5_PATH.'/module/'.$name;
    $uses  = array();
    $fires = array();

    // 1) _hook_list.php 우선
    $hl = $base.'/_hook_list.php';
    if (is_file($hl)) {
        $arr = @include $hl;
        if (is_array($arr)) {
            if (isset($arr['uses']) && is_array($arr['uses'])) {
                $u = $arr['uses'];
                // 연관배열(훅명 => 메타배열) 형태도 처리: 값이 배열이면 키를 훅명으로 사용
                if (count($u) > 0 && is_array(reset($u))) {
                    $uses = array_values(array_unique(array_keys($u)));
                } else {
                    $u = array_filter($u, 'is_scalar');
                    $uses = array_values(array_unique(array_map('strval', $u)));
                }
            } else if (isset($arr[0])) {
                // 평면 배열로 선언된 경우
                $uses = array_values(array_unique(array_map('strval', $arr)));
            }
            if (isset($arr['fires']) && is_array($arr['fires'])) {
                $f = $arr['fires'];
                // 연관배열(훅명 => 메타배열) 형태도 처리
                if (count($f) > 0 && is_array(reset($f))) {
                    $fires = array_values(array_unique(array_keys($f)));
                } else {
                    $f = array_filter($f, 'is_scalar');
                    $fires = array_values(array_unique(array_map('strval', $f)));
                }
            }
            // uses_bbs 는 점검이 prefix 로 다시 나눈다. 선언만 있고 uses 에 없으면 누락되던 구멍.
            if (isset($arr['uses_bbs']) && is_array($arr['uses_bbs'])) {
                $ub = $arr['uses_bbs'];
                if (count($ub) > 0 && is_array(reset($ub))) {
                    $extra = array_keys($ub);
                } else {
                    $ub = array_filter($ub, 'is_scalar');
                    $extra = array_map('strval', $ub);
                }
                $uses = array_values(array_unique(array_merge($uses, $extra)));
            }
        }
    }

    // 2) requires_hooks 폴백 / 보강
    if (empty($uses)) {
        $meta = mdl_read_meta($name);
        if (is_array($meta) && !empty($meta['requires_hooks']) && is_array($meta['requires_hooks'])) {
            $uses = array_values(array_unique(array_map('strval', $meta['requires_hooks'])));
        }
    }

    // 3) bootstrap.php 본문 스캔 폴백
    if (empty($uses)) {
        $bf = $base.'/bootstrap.php';
        if (is_file($bf)) {
            $content = @file_get_contents($bf);
            if ($content !== false && preg_match_all('/mdl_add_(action|filter)\s*\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $content, $m)) {
                $uses = array_values(array_unique($m[2]));
            }
        }
    }

    $cache[$name] = array('uses' => $uses, 'fires' => $fires);
    return $cache[$name];
}

/**
 * 훅이 "bbs(게시판) 컨텍스트에서 사용되는 훅" 인지 판정.
 *
 * 게시판 스킨 단위로 별도 검증되므로, 모듈 검증 페이지에서는
 * "이 훅들은 게시판 스킨 단위 확인" 으로 분리 표시한다.
 *
 * 판정 기준:
 *  1) 훅 이름 prefix: bbs_ / mmb_ / item_  (게시판 스킨에서 사용되는 훅 네이밍 컨벤션)
 *  prefix `bbs_` / `mmb_` / `item_` 인 훅만 게시판(스킨별) 훅으로 분류.
 *  파일 경로(bbs/ 등) 기반 추정은 false-positive(예: bbs/login_check.php 의 member_login_after,
 *  bbs/register_form_update.php 의 member_register_after)가 발생하므로 사용하지 않는다.
 *
 *  접두사 없는 게시판 훅(write_after, comment_after, list_top, view_top, write_delete_after,
 *  write_good_after, write_scrap_after, write_update_item_use, comment_update_item_use,
 *  comment_delete_after 등)은 스킨에 따라 다르지 않으므로 일반 훅으로 처리한다.
 */
function mdl_is_bbs_hook($hook, $locations = null) {
    return (bool) preg_match('/^(bbs_|mmb_|item_)/', $hook);
}

/**
 * scope 키를 사람이 읽기 쉬운 한글 이름으로 변환.
 */
function mdl_scope_label($scope) {
    static $map = array(
        'write'    => '글쓰기',
        'comment'  => '댓글쓰기',
        'view'     => '글 보기',
        'list'     => '목록',
        'use'      => '아이템 사용',
        'register' => '회원가입',
        'login'    => '로그인',
    );
    if (isset($map[$scope])) return $map[$scope];
    return $scope === '' ? '사용' : $scope;
}

/**
 * 모듈 1개를 "현재 홈페이지 상태" 기준으로 검증.
 *
 * 반환: array(
 *     'name'    => string,
 *     'ok'      => bool,
 *     'uses'    => array(
 *         '<hook>' => array(
 *             'found'  => bool,
 *             'count'  => int,             // 발사 위치 수
 *             'sample' => array('file:line', ...)  // 최대 3건
 *         ),
 *     ),
 *     'fires'   => array(...) (옵션, 선언된 경우만),
 *     'summary' => array('total'=>n, 'missing'=>n)
 * )
 */
function mdl_verify_module($name) {
    $hooks_info = mdl_module_hooks_used($name);
    $loc_map    = mdl_hook_location_map();

    $report_uses     = array();   // 일반(비-bbs) 훅 — 모듈 페이지에서 검증
    $report_uses_bbs = array();   // bbs 훅 — board_form 에서 게시판별 검증
    $missing_cnt     = 0;
    $bbs_total       = 0;

    foreach ($hooks_info['uses'] as $h) {
        $hits = isset($loc_map[$h]) ? $loc_map[$h] : array();
        $sample = array();
        foreach (array_slice($hits, 0, 3) as $hit) {
            $sample[] = $hit['file'].':'.$hit['line'];
        }
        $found = !empty($hits);
        $entry = array(
            'found'  => $found,
            'count'  => count($hits),
            'sample' => $sample,
        );

        if (mdl_is_bbs_hook($h, $hits)) {
            $report_uses_bbs[$h] = $entry;
            $bbs_total++;
        } else {
            $report_uses[$h] = $entry;
            if (!$found) $missing_cnt++;
        }
    }

    return array(
        'name'      => $name,
        'ok'        => ($missing_cnt === 0),
        'uses'      => $report_uses,       // 일반 훅 (이 페이지에서 판정)
        'uses_bbs'  => $report_uses_bbs,   // bbs 훅 (board_form 에서 판정 — 참고용)
        'fires'     => $hooks_info['fires'],
        'summary'   => array(
            'total'     => count($report_uses),
            'missing'   => $missing_cnt,
            'bbs_total' => $bbs_total,
        ),
    );
}

/**
 * 모듈이 사용된 게시판 목록을 반환 (bbs 훅을 가진 모듈에 한해 의미 있음).
 *
 * board_state.json 의 게시판별 모듈 비활성화 정보를 읽어,
 * 해당 모듈이 활성된 게시판들을 list 로 반환.
 *
 * 반환: array(
 *   array('bo_table'=>'free', 'bo_subject'=>'자유', 'bo_skin'=>'mmb', 'scopes'=>array('write','comment')),
 *   ...
 * )
 *
 * 주의: 모듈이 board_default=on 이면, board_state 에 명시적으로 off 가 없는 모든 게시판이 포함됨.
 *       board_default=off 이면, board_state 에 명시적으로 on 인 게시판만 포함됨.
 */
function mdl_module_used_in_boards($name) {
    global $g5;
    if (!isset($g5['board_table'])) return array();

    $meta = mdl_read_meta($name);
    if (!is_array($meta)) return array();

    $default_on = (!isset($meta['board_default']) || $meta['board_default'] !== 'off');
    $scopes = (isset($meta['scopes']) && is_array($meta['scopes'])) ? $meta['scopes'] : array();

    // board_state 읽기
    $bs_path = G5_DATA_PATH.'/module/board_state.json';
    $bs = array();
    if (is_file($bs_path)) {
        $raw = @file_get_contents($bs_path);
        if ($raw !== false) {
            $j = json_decode($raw, true);
            if (is_array($j)) $bs = $j;
        }
    }

    // 게시판 전체 (활성 게시판만)
    $sql = "SELECT bo_table, bo_subject, bo_skin FROM {$g5['board_table']} WHERE bo_use_search >= 0 ORDER BY bo_table";
    $res = sql_query($sql, false);
    $out = array();
    if (!$res) return $out;

    while ($row = sql_fetch_array($res)) {
        $bt = $row['bo_table'];
        $bs_row = isset($bs[$bt]) && is_array($bs[$bt]) ? $bs[$bt] : array();

        $active_scopes = array();
        if (empty($scopes)) {
            // 단일 토글
            $is_on = isset($bs_row[$name]) ? (bool)$bs_row[$name] : $default_on;
            if ($is_on) $active_scopes[] = '';
        } else {
            foreach ($scopes as $sc) {
                $key = $name.'@'.$sc;
                $is_on = isset($bs_row[$key]) ? (bool)$bs_row[$key] : $default_on;
                if ($is_on) $active_scopes[] = $sc;
            }
        }

        if (!empty($active_scopes)) {
            $out[] = array(
                'bo_table'   => $bt,
                'bo_subject' => $row['bo_subject'],
                'bo_skin'    => $row['bo_skin'],
                'scopes'     => $active_scopes,
            );
        }
    }
    return $out;
}
