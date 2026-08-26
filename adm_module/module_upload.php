<?php
/**
 * 모듈 tar.gz 업로드 처리
 *
 * PHP 내장 PharData(Phar 확장)를 사용 — ZipArchive 불필요.
 *
 * 안전 검증:
 *  - super 관리자만
 *  - .tar.gz 만 허용
 *  - 엔트리 path traversal 차단 (../, 절대경로 거부)
 *  - 최상위 디렉토리 1개에 module.php 가 있어야 함
 *  - 동일 이름 폴더가 있으면 확인 후 파일만 덮어씀 (DB 데이터는 유지)
 */
$sub_menu = '001100';
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');

if (!$is_admin) {
    alert('관리자만 모듈을 업로드할 수 있습니다.');
}
if (!function_exists('mdl_scan')) {
    alert('모듈 시스템이 로드되지 않았습니다.');
}

function mdl_upload_pending_dir()
{
    $dir = G5_DATA_PATH.'/module/_pending';
    if (!is_dir($dir)) {
        @mkdir($dir, defined('G5_DIR_PERMISSION') ? G5_DIR_PERMISSION : 0755, true);
        @chmod($dir, defined('G5_DIR_PERMISSION') ? G5_DIR_PERMISSION : 0755);
    }
    $idx = $dir.'/index.php';
    if (!is_file($idx)) {
        @file_put_contents($idx, '<?php exit;');
    }
    return $dir;
}

function mdl_upload_rm_rf($path)
{
    if ($path === '' || !file_exists($path)) {
        return;
    }
    if (is_link($path) || is_file($path)) {
        @unlink($path);
        return;
    }
    if (!is_dir($path)) {
        return;
    }
    $items = @scandir($path);
    if ($items === false) {
        return;
    }
    foreach ($items as $it) {
        if ($it === '.' || $it === '..') {
            continue;
        }
        mdl_upload_rm_rf($path.'/'.$it);
    }
    @rmdir($path);
}

function mdl_upload_clear_pending()
{
    if (empty($_SESSION['mdl_upload_pending']) || !is_array($_SESSION['mdl_upload_pending'])) {
        unset($_SESSION['mdl_upload_pending']);
        return;
    }
    $p = $_SESSION['mdl_upload_pending'];
    unset($_SESSION['mdl_upload_pending']);
    if (!empty($p['file']) && is_file($p['file'])) {
        @unlink($p['file']);
    }
}

function mdl_upload_cleanup_stale()
{
    $dir = mdl_upload_pending_dir();
    $now = time();
    foreach (glob($dir.'/*.tar.gz') ?: array() as $f) {
        if (is_file($f) && ($now - (int)@filemtime($f) > 3600)) {
            @unlink($f);
        }
    }
}

function mdl_upload_phar_meta($tmp_path, $module_name)
{
    $meta_path = 'phar://'.str_replace('\\', '/', $tmp_path).'/'.$module_name.'/module.php';
    if (!is_file($meta_path)) {
        return null;
    }
    $meta = include $meta_path;
    return is_array($meta) ? $meta : null;
}

function mdl_upload_validate_tar($tmp_path)
{
    try {
        $phar = new PharData($tmp_path);
    } catch (Exception $e) {
        @unlink($tmp_path);
        alert('tar.gz 파일을 열 수 없습니다: '.h($e->getMessage()));
    }

    $module_name = '';
    $has_meta    = false;
    $phar_base   = 'phar://'.str_replace('\\', '/', $tmp_path).'/';

    try {
        $rit = new RecursiveIteratorIterator($phar, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($rit as $entry_info) {
            $entry_path = str_replace('\\', '/', (string)$entry_info);
            $rel = ltrim(substr($entry_path, strlen($phar_base)), '/');
            if ($rel === '' || $rel === '.' || $rel === '..') {
                continue;
            }
            if (strpos($rel, '..') !== false || $rel[0] === '/' || preg_match('/^[a-zA-Z]:/', $rel)) {
                @unlink($tmp_path);
                alert('안전하지 않은 경로가 포함되어 있습니다: '.h($rel));
            }
            $parts = explode('/', rtrim($rel, '/'));
            $top   = $parts[0];
            if ($module_name === '') {
                $module_name = $top;
            } elseif ($top !== $module_name) {
                @unlink($tmp_path);
                alert('tar.gz 안에 여러 개의 최상위 폴더가 있습니다. 모듈 패키지는 하나의 최상위 폴더만 가져야 합니다.');
            }
            if ($rel === $module_name.'/module.php') {
                $has_meta = true;
            }
        }
    } catch (Exception $e) {
        @unlink($tmp_path);
        alert('tar.gz 파일 파싱 오류: '.h($e->getMessage()));
    }

    if ($module_name === '') {
        @unlink($tmp_path);
        alert('tar.gz 안에 파일이 없거나 구조를 파악할 수 없습니다.');
    }
    if (!$has_meta) {
        @unlink($tmp_path);
        alert('최상위 폴더 (<strong>'.h($module_name).'</strong>) 에 <code>module.php</code> 가 없습니다. 모듈 패키지 형식이 아닙니다.');
    }
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{1,50}$/', $module_name)) {
        @unlink($tmp_path);
        alert('모듈 폴더 이름이 유효하지 않습니다: '.h($module_name).' (영문 시작, 영숫자·_ 만 허용)');
    }
    if ($module_name[0] === '_') {
        @unlink($tmp_path);
        alert('밑줄 _ 로 시작하는 폴더 이름은 사용할 수 없습니다 (코어 예약).');
    }

    return array($phar, $module_name);
}

function mdl_upload_extract_to_module($phar, $module_name, $overwrite)
{
    $dest_root = G5_PATH.'/module/'.$module_name;
    if (!$overwrite) {
        $phar->extractTo(G5_PATH.'/module/');
        return;
    }

    $perm = defined('G5_DIR_PERMISSION') ? G5_DIR_PERMISSION : 0755;
    $stage_parent = mdl_upload_pending_dir().'/ex_'.bin2hex(random_bytes(4));
    if (!@mkdir($stage_parent, $perm, true)) {
        alert('임시 폴더를 만들 수 없습니다.');
    }

    try {
        $phar->extractTo($stage_parent);
    } catch (Exception $e) {
        mdl_upload_rm_rf($stage_parent);
        alert('압축 해제 실패: '.h($e->getMessage()).'. 쓰기 권한을 확인하세요.');
    }

    $staged = $stage_parent.'/'.$module_name;
    if (!is_dir($staged)) {
        mdl_upload_rm_rf($stage_parent);
        alert('압축 내용이 올바르지 않습니다.');
    }

    $replaced = false;
    $bak = '';
    if (is_dir($dest_root)) {
        $bak = G5_PATH.'/module/.bak_'.$module_name.'_'.bin2hex(random_bytes(4));
        if (@rename($dest_root, $bak) && @rename($staged, $dest_root)) {
            $replaced = true;
        } elseif (is_dir($bak) && !is_dir($dest_root)) {
            @rename($bak, $dest_root);
        }
    } elseif (@rename($staged, $dest_root)) {
        $replaced = true;
    }

    if (!$replaced) {
        try {
            $phar->extractTo(G5_PATH.'/module/', null, true);
            $replaced = true;
        } catch (Exception $e) {
            mdl_upload_rm_rf($stage_parent);
            if ($bak !== '' && is_dir($bak) && !is_dir($dest_root)) {
                @rename($bak, $dest_root);
            }
            alert('덮어쓰기에 실패했습니다: '.h($e->getMessage()).'. module/ 쓰기 권한을 확인하세요.');
        }
    }

    mdl_upload_rm_rf($stage_parent);
    if ($bak !== '' && is_dir($bak)) {
        mdl_upload_rm_rf($bak);
    }
}

function mdl_upload_show_confirm($pending)
{
    global $g5;
    $g5['title'] = '모듈 덮어쓰기 확인';
    include_once G5_MDL_ADMIN_PATH.'/admin.head.php';

    $name     = $pending['name'];
    $old_ver  = $pending['old_file_ver'];
    $new_ver  = $pending['new_ver'];
    $title    = $pending['title'];
    $active   = !empty($pending['active']);
    $core     = !empty($pending['core']);
    $list_url = G5_MDL_ADMIN_URL.'/module_list.php';
    $self     = G5_MDL_ADMIN_URL.'/module_upload.php';
    ?>
<div style="background:#fff8e8;border:1px solid #e6c98a;border-left:4px solid #c80;padding:16px 18px;border-radius:4px;margin:0 0 18px;font-size:13px;line-height:1.8;max-width:720px;">
    <strong style="color:#a60;">이미 설치된 모듈을 덮어씁니다</strong>
    <p style="margin:8px 0 0;">
        <code><?php echo h($name); ?></code><?php echo $title !== '' ? ' ('.h($title).')' : ''; ?>
        가 <code>module/<?php echo h($name); ?>/</code> 에 있습니다.
        확인하면 <strong>파일만</strong> 패키지 내용으로 교체합니다.
        DB 테이블과 저장된 데이터는 지우지 않습니다.
    </p>
    <ul style="margin:10px 0 0;padding-left:18px;">
        <li>현재 버전: <strong><?php echo h($old_ver); ?></strong></li>
        <li>패키지 버전: <strong><?php echo h($new_ver); ?></strong></li>
        <li>상태: <?php echo $active ? '<strong>켜짐</strong> — 덮어쓴 뒤 버전이 올라가면 스키마 보강(upgrade.php, 없으면 install.php)을 실행합니다.' : '꺼짐 — 파일만 교체합니다. 켤 때 버전에 따라 upgrade.php 가 실행됩니다.'; ?></li>
        <?php if ($core) { ?>
        <li style="color:#a40;">기본 제공 모듈입니다. 덮어쓰면 코어 파일이 교체됩니다.</li>
        <?php } ?>
    </ul>
</div>
<form method="post" action="<?php echo $self; ?>" onsubmit="return confirm('기존 모듈 파일을 덮어씁니다. 계속할까요?');">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <input type="hidden" name="overwrite" value="1">
    <input type="hidden" name="pending_token" value="<?php echo h($pending['token']); ?>">
    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="덮어쓰기" class="btn_submit">
        <a href="<?php echo $self; ?>?cancel=1" class="btn_frmline">취소</a>
    </div>
</form>
<p class="frm_info"><a href="<?php echo $list_url; ?>">모듈 목록으로</a></p>
    <?php
    include_once G5_MDL_ADMIN_PATH.'/admin.tail.php';
    exit;
}

function mdl_upload_apply_schema($name, $old_state_ver, $was_active)
{
    $meta = mdl_read_meta($name);
    $new_ver = (is_array($meta) && isset($meta['version'])) ? (string)$meta['version'] : '0.0.0';
    if (!$was_active) {
        return $new_ver;
    }

    $old = ($old_state_ver !== null && $old_state_ver !== '') ? (string)$old_state_ver : '0.0.0';
    if (version_compare($old, $new_ver, '<')) {
        $up   = G5_PATH.'/module/'.$name.'/upgrade.php';
        $inst = G5_PATH.'/module/'.$name.'/install.php';
        global $g5, $config;
        // bootstrap 금지. 별칭은 upgrade/install 또는 lib.php. HOOK §I-8.
        if (is_file($up)) {
            $mdl_old_version = $old;
            include $up;
        } elseif (is_file($inst)) {
            include $inst;
        }
    }

    $state = mdl_load_state();
    $state['versions'][$name] = $new_ver;
    mdl_save_state($state);
    return $new_ver;
}

mdl_upload_cleanup_stale();

$list_url = G5_MDL_ADMIN_URL.'/module_list.php';

if (isset($_GET['confirm']) && (string)$_GET['confirm'] === '1') {
    $pending = isset($_SESSION['mdl_upload_pending']) && is_array($_SESSION['mdl_upload_pending'])
        ? $_SESSION['mdl_upload_pending'] : null;
    if (!$pending || empty($pending['file']) || !is_file($pending['file'])) {
        mdl_upload_clear_pending();
        alert('덮어쓰기 확인 정보가 없습니다. 다시 업로드하세요.', $list_url);
    }
    mdl_upload_show_confirm($pending);
}

if (isset($_GET['cancel']) && (string)$_GET['cancel'] === '1') {
    mdl_upload_clear_pending();
    goto_url($list_url.'?msg='.urlencode('모듈 업로드를 취소했습니다'));
}

if (isset($_GET['action']) && (string)$_GET['action'] === 'finish') {
    $fin = isset($_SESSION['mdl_upload_finish']) && is_array($_SESSION['mdl_upload_finish'])
        ? $_SESSION['mdl_upload_finish'] : null;
    $t = isset($_GET['t']) ? (string)$_GET['t'] : '';
    unset($_SESSION['mdl_upload_finish']);
    if (!$fin || $t === '' || empty($fin['token']) || !hash_equals((string)$fin['token'], $t)) {
        alert('덮어쓰기 마무리를 확인할 수 없습니다. 모듈 목록에서 상태를 확인하세요.', $list_url);
    }
    $name = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$fin['name']);
    $old_state_ver = isset($fin['old_state_ver']) ? $fin['old_state_ver'] : null;
    $was_active = !empty($fin['active']);
    $new_ver = mdl_upload_apply_schema($name, $old_state_ver, $was_active);
    if ($was_active) {
        goto_url($list_url.'?msg='.urlencode($name.' 파일을 덮어썼습니다 (v'.$new_ver.')'));
    }
    goto_url($list_url.'?msg='.urlencode($name.' 파일을 덮어썼습니다. 사용하려면 [켜기]를 누르세요'));
}

if (isset($_POST['overwrite']) && (string)$_POST['overwrite'] === '1') {
    check_admin_token();
    $pending = isset($_SESSION['mdl_upload_pending']) && is_array($_SESSION['mdl_upload_pending'])
        ? $_SESSION['mdl_upload_pending'] : null;
    $pt = isset($_POST['pending_token']) ? (string)$_POST['pending_token'] : '';
    if (!$pending || $pt === '' || empty($pending['token']) || !hash_equals((string)$pending['token'], $pt)) {
        mdl_upload_clear_pending();
        alert('덮어쓰기 확인이 만료되었거나 유효하지 않습니다. 다시 업로드하세요.', $list_url);
    }
    if (time() - (int)$pending['time'] > 3600) {
        mdl_upload_clear_pending();
        alert('덮어쓰기 대기 시간이 지났습니다. 다시 업로드하세요.', $list_url);
    }
    $tmp_path = $pending['file'];
    $module_name = $pending['name'];
    if (!is_file($tmp_path)) {
        mdl_upload_clear_pending();
        alert('업로드 임시 파일이 없습니다. 다시 업로드하세요.', $list_url);
    }
    try {
        $phar = new PharData($tmp_path);
    } catch (Exception $e) {
        mdl_upload_clear_pending();
        alert('tar.gz 파일을 열 수 없습니다: '.h($e->getMessage()));
    }
    mdl_upload_extract_to_module($phar, $module_name, true);
    $finish_token = bin2hex(random_bytes(8));
    $_SESSION['mdl_upload_finish'] = array(
        'name'          => $module_name,
        'old_state_ver' => isset($pending['old_state_ver']) ? $pending['old_state_ver'] : null,
        'active'        => !empty($pending['active']),
        'token'         => $finish_token,
    );
    mdl_upload_clear_pending();
    goto_url(G5_MDL_ADMIN_URL.'/module_upload.php?action=finish&t='.urlencode($finish_token));
}

if (empty($_FILES['mdl_pkg']['name'])) {
    alert('업로드할 파일이 없습니다.');
}

check_admin_token();

$f = $_FILES['mdl_pkg'];
if (!empty($f['error']) || !is_uploaded_file($f['tmp_name'])) {
    alert('파일 업로드에 실패했습니다. (error='.(int)$f['error'].')');
}

if (!preg_match('/\.tar\.gz$/i', $f['name'])) {
    alert('.tar.gz 파일만 업로드 가능합니다.');
}

$tmp_path = sys_get_temp_dir().DIRECTORY_SEPARATOR
          .'mdl_'.bin2hex(random_bytes(8)).'.tar.gz';

if (!move_uploaded_file($f['tmp_name'], $tmp_path)) {
    alert('임시 파일 이동 실패. 서버 권한을 확인하세요.');
}

list($phar, $module_name) = mdl_upload_validate_tar($tmp_path);

$dest_root = G5_PATH.'/module/'.$module_name;
if (is_dir($dest_root)) {
    $pending_file = mdl_upload_pending_dir().'/'.bin2hex(random_bytes(8)).'.tar.gz';
    if (!@rename($tmp_path, $pending_file)) {
        if (!@copy($tmp_path, $pending_file)) {
            @unlink($tmp_path);
            alert('확인용 임시 파일 저장에 실패했습니다.');
        }
        @unlink($tmp_path);
    }
    $tmp_path = $pending_file;

    $state = mdl_load_state();
    $active = isset($state['active']) && is_array($state['active']) ? $state['active'] : array();
    $old_meta = mdl_read_meta($module_name);
    $new_meta = mdl_upload_phar_meta($tmp_path, $module_name);
    $core = array('bbs_action_combine', 'bbs_action_search', 'bbs_dice', 'bbs_item_use');

    mdl_upload_clear_pending();
    $pending = array(
        'file'          => $tmp_path,
        'name'          => $module_name,
        'token'         => bin2hex(random_bytes(8)),
        'time'          => time(),
        'old_file_ver'  => (is_array($old_meta) && isset($old_meta['version'])) ? (string)$old_meta['version'] : '(알 수 없음)',
        'old_state_ver' => isset($state['versions'][$module_name]) ? (string)$state['versions'][$module_name] : null,
        'new_ver'       => (is_array($new_meta) && isset($new_meta['version'])) ? (string)$new_meta['version'] : '(알 수 없음)',
        'title'         => (is_array($new_meta) && isset($new_meta['title'])) ? (string)$new_meta['title'] : '',
        'active'        => in_array($module_name, $active, true),
        'core'          => in_array($module_name, $core, true),
    );
    $_SESSION['mdl_upload_pending'] = $pending;
    goto_url(G5_MDL_ADMIN_URL.'/module_upload.php?confirm=1');
}

try {
    $phar->extractTo(G5_PATH.'/module/');
} catch (Exception $e) {
    @unlink($tmp_path);
    alert('압축 해제 실패: '.h($e->getMessage()).'. module/ 쓰기 권한을 확인하세요.');
}

@unlink($tmp_path);

$meta = mdl_read_meta($module_name);
if (!is_array($meta)) {
    alert('모듈이 추출되었으나 module.php 메타를 읽을 수 없습니다. module/'.$module_name.'/module.php 를 확인하세요.',
          $list_url);
}

goto_url($list_url.'?msg='.urlencode($module_name.' 업로드 완료 — 활성화하려면 [켜기] 버튼을 누르세요'));
