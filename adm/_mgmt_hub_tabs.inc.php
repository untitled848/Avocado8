<?php
/**
 * 관리 허브 탭 (게시판 / 타임라인)
 *
 * 사용:
 *   $mgmt_hub = 'board';   // board | timeline
 *   $mgmt_hub_tab = 'list'; // list | skin
 *   include G5_ADMIN_PATH.'/_mgmt_hub_tabs.inc.php';
 */
if (!defined('_GNUBOARD_')) exit;

if (empty($mgmt_hub) || empty($mgmt_hub_tab)) return;

$mgmt_defs = array(
    'board' => array(
        'aria' => '게시판 관리 탭',
        'tabs' => array(
            'list' => array('label' => '게시판 목록', 'url' => './board_list.php',      'hint' => '생성·설정'),
            'skin' => array('label' => '스킨 업로드', 'url' => './board_skin_list.php', 'hint' => 'tar.gz 설치'),
        ),
        'help' => array(
            'list' => '게시판을 만들고 설정을 바꿉니다. 스킨 파일은 <a href="./board_skin_list.php">스킨 업로드</a> 탭에서 설치합니다.',
            'skin' => '게시판 스킨을 tar.gz로 설치합니다. 설치한 스킨은 게시판 설정의 <strong>스킨 디렉토리</strong>에서 고릅니다. 목록으로 돌아가려면 <a href="./board_list.php">게시판 목록</a> 탭을 누르세요.',
        ),
    ),
    'timeline' => array(
        'aria' => '타임라인 관리 탭',
        'tabs' => array(
            'list' => array('label' => '타임라인 목록', 'url' => './timeline_list.php', 'hint' => '생성·설정'),
            'skin' => array('label' => '스킨 업로드',   'url' => './timeline_skin_list.php',  'hint' => 'tar.gz 설치'),
        ),
        'help' => array(
            'list' => '타임라인을 만들고 설정을 바꿉니다. 스킨 파일은 <a href="./timeline_skin_list.php">스킨 업로드</a> 탭에서 설치합니다.',
            'skin' => '타임라인 스킨을 tar.gz로 설치합니다. 설치한 스킨은 타임라인 설정의 <strong>스킨</strong>에서 고릅니다. 목록으로 돌아가려면 <a href="./timeline_list.php">타임라인 목록</a> 탭을 누르세요.',
        ),
    ),
);

if (!isset($mgmt_defs[$mgmt_hub])) return;

$hub_tabs = $mgmt_defs[$mgmt_hub]['tabs'];
$hub_aria = $mgmt_defs[$mgmt_hub]['aria'];
$hub_help = isset($mgmt_defs[$mgmt_hub]['help'][$mgmt_hub_tab]) ? $mgmt_defs[$mgmt_hub]['help'][$mgmt_hub_tab] : '';
$hub_tab  = $mgmt_hub_tab;

include G5_ADMIN_PATH.'/_admin_hub_tabs.inc.php';

unset($mgmt_defs, $hub_tabs, $hub_aria, $hub_help, $hub_tab);
