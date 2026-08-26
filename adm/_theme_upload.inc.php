<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 테마 tar.gz 업로드/삭제 공통.
 * 설치 위치: theme/{name}/
 */
if (!defined('THEME_UPLOAD_MAX_BYTES')) {
    define('THEME_UPLOAD_MAX_BYTES', 50 * 1024 * 1024);
}

function theme_upload_protected_names()
{
    // default 는 가상 테마(루트 파일). 폴더로 만들거나 지울 수 없음.
    return array('default');
}

function theme_upload_is_protected($name)
{
    return in_array((string)$name, theme_upload_protected_names(), true);
}

function theme_upload_valid_name($name)
{
    $name = (string)$name;
    if ($name === '' || $name[0] === '_' || $name[0] === '.') return false;
    if (theme_upload_is_protected($name)) return false;
    return (bool)preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{0,50}$/', $name);
}

function theme_upload_root()
{
    return rtrim(str_replace('\\', '/', G5_PATH.'/'.G5_THEME_DIR), '/');
}

/** 테마 루트에서 인정하는 파일/디렉터리 — 하나라도 있으면 테마 패키지로 본다. */
function theme_upload_markers()
{
    return array(
        'files' => array(
            'override_slots.php', 'theme.config.php',
            'head.php', 'tail.php', 'head.sub.php', 'tail.sub.php',
            'menu.php', 'index.php', 'intro.php', 'main.php', 'enter.php',
        ),
        'dirs' => array(
            'css', 'extend', 'member', 'mypage', 'shop', 'inventory', 'world', 'skin', 'bbs',
        ),
    );
}

function theme_upload_has_markers($dir)
{
    $dir = rtrim(str_replace('\\', '/', $dir), '/');
    $m = theme_upload_markers();
    foreach ($m['files'] as $f) {
        if (is_file($dir.'/'.$f)) return true;
    }
    foreach ($m['dirs'] as $d) {
        if (is_dir($dir.'/'.$d)) return true;
    }
    if (is_file($dir.'/bbs/login.php')) return true;
    return false;
}

function theme_upload_rm_rf($path)
{
    if (is_link($path)) {
        @unlink($path);
        return;
    }
    if (is_file($path)) {
        @unlink($path);
        return;
    }
    if (!is_dir($path)) return;
    $items = @scandir($path);
    if ($items === false) return;
    foreach ($items as $it) {
        if ($it === '.' || $it === '..') continue;
        theme_upload_rm_rf($path.'/'.$it);
    }
    @rmdir($path);
}
