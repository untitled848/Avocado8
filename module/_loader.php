<?php
/**
 * Module Loader — common.php 끝에서 자동 호출됨
 */
if (!defined('_GNUBOARD_')) exit;

require_once(__DIR__.'/_hooks.php');
require_once(__DIR__.'/_widget.php');
require_once(__DIR__.'/_registry.php');
require_once(__DIR__.'/_setting.php');
require_once(__DIR__.'/_db.php');
require_once(__DIR__.'/_skin.php');
require_once(__DIR__.'/_hook_registry.php');

$GLOBALS['mdl_active'] = mdl_active_modules();

foreach ($GLOBALS['mdl_active'] as $name => $meta) {
    $bootstrap = G5_PATH.'/module/'.$name.'/bootstrap.php';
    if (is_file($bootstrap)) {
        include_once $bootstrap;
    }
}

// 모든 모듈 부트스트랩 로드 후 신호
mdl_do_action('bootstrap');
