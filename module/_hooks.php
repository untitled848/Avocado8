<?php
/**
 * Module Hook Engine (WordPress 스타일 Action / Filter)
 *
 * 사용:
 *   mdl_add_action('event', 'callback', $priority);
 *   mdl_add_filter('hook',  'callback', $priority);
 *   mdl_do_action ('event', $arg1, $arg2);
 *   $v = mdl_apply_filters('hook', $value, $extra);
 */

if (!isset($GLOBALS['mdl_hooks'])) $GLOBALS['mdl_hooks'] = array();

function mdl_add_action($hook, $callback, $priority = 10) {
    if (!isset($GLOBALS['mdl_hooks'][$hook])) $GLOBALS['mdl_hooks'][$hook] = array();
    if (!isset($GLOBALS['mdl_hooks'][$hook][$priority])) $GLOBALS['mdl_hooks'][$hook][$priority] = array();
    $GLOBALS['mdl_hooks'][$hook][$priority][] = $callback;
}

function mdl_add_filter($hook, $callback, $priority = 10) {
    mdl_add_action($hook, $callback, $priority);
}

function mdl_remove_action($hook, $callback, $priority = 10) {
    if (!isset($GLOBALS['mdl_hooks'][$hook][$priority])) return false;
    foreach ($GLOBALS['mdl_hooks'][$hook][$priority] as $i => $cb) {
        if ($cb === $callback) {
            unset($GLOBALS['mdl_hooks'][$hook][$priority][$i]);
            return true;
        }
    }
    return false;
}

function mdl_remove_filter($hook, $callback, $priority = 10) {
    return mdl_remove_action($hook, $callback, $priority);
}

function mdl_has_action($hook, $callback = null) {
    if (!isset($GLOBALS['mdl_hooks'][$hook])) return false;
    if ($callback === null) return true;
    foreach ($GLOBALS['mdl_hooks'][$hook] as $bucket) {
        foreach ($bucket as $cb) {
            if ($cb === $callback) return true;
        }
    }
    return false;
}

function mdl_do_action($hook /* , ...args */) {
    if (!isset($GLOBALS['mdl_hooks'][$hook])) return;
    $args = array_slice(func_get_args(), 1);
    $bucket = $GLOBALS['mdl_hooks'][$hook];
    ksort($bucket);
    foreach ($bucket as $callbacks) {
        foreach ($callbacks as $cb) {
            call_user_func_array($cb, $args);
        }
    }
}

function mdl_apply_filters($hook, $value /* , ...extra */) {
    if (!isset($GLOBALS['mdl_hooks'][$hook])) return $value;
    $extra = array_slice(func_get_args(), 2);
    $bucket = $GLOBALS['mdl_hooks'][$hook];
    ksort($bucket);
    foreach ($bucket as $callbacks) {
        foreach ($callbacks as $cb) {
            $args = array_merge(array($value), $extra);
            $value = call_user_func_array($cb, $args);
        }
    }
    return $value;
}

// 모듈 코어 파일 override 시스템은 폐지됨 (실사용 0건, 성능 비용만 발생).
// 기존 mdl_check_override() 호출부는 이미 코어에서 제거됨.
