<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 타임라인 스킨 업로드/삭제 공통.
 * 설치 위치: skin/timeline/{name}/
 */
if (!defined('TIMELINE_SKIN_MAX_BYTES')) {
    define('TIMELINE_SKIN_MAX_BYTES', 20 * 1024 * 1024);
}

function timeline_skin_protected_names()
{
    return array('basic');
}

function timeline_skin_is_protected($name)
{
    return in_array((string)$name, timeline_skin_protected_names(), true);
}

function timeline_skin_valid_name($name)
{
    $name = (string)$name;
    if ($name === '' || $name[0] === '_' || $name[0] === '.') return false;
    return (bool)preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{0,50}$/', $name);
}

function timeline_skin_root()
{
    return rtrim(str_replace('\\', '/', G5_SKIN_PATH), '/').'/timeline';
}

function timeline_skin_required_files()
{
    return array('write.skin.php', 'item.skin.php', 'view_detail.skin.php');
}

function timeline_skin_has_entry_files($dir)
{
    foreach (timeline_skin_required_files() as $f) {
        if (is_file(rtrim($dir, '/').'/'.$f)) return true;
    }
    return false;
}

function timeline_skin_rm_rf($path)
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
        timeline_skin_rm_rf($path.'/'.$it);
    }
    @rmdir($path);
}
