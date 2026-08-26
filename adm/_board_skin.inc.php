<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 게시판 스킨 업로드/삭제 공통.
 * 설치 위치: skin/board/{name}/
 */
if (!defined('BOARD_SKIN_MAX_BYTES')) {
    define('BOARD_SKIN_MAX_BYTES', 20 * 1024 * 1024);
}

function board_skin_protected_names()
{
    return array('basic', 'mmb', 'qna', 'calculate');
}

function board_skin_is_protected($name)
{
    return in_array((string)$name, board_skin_protected_names(), true);
}

function board_skin_valid_name($name)
{
    $name = (string)$name;
    if ($name === '' || $name[0] === '_' || $name[0] === '.') return false;
    return (bool)preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{0,50}$/', $name);
}

function board_skin_root()
{
    return rtrim(str_replace('\\', '/', G5_SKIN_PATH), '/').'/board';
}

function board_skin_required_files()
{
    return array('list.skin.php', 'view.skin.php', 'write.skin.php');
}

function board_skin_has_entry_files($dir)
{
    foreach (board_skin_required_files() as $f) {
        if (is_file(rtrim($dir, '/').'/'.$f)) return true;
    }
    return false;
}

function board_skin_rm_rf($path)
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
        board_skin_rm_rf($path.'/'.$it);
    }
    @rmdir($path);
}
