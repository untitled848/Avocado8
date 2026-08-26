<?php
/**
 * 모듈 시스템 어드민 메뉴 (코어 진입점)
 *
 * 개별 모듈이 정의한 admin_menus 는 mdl_register_admin_menus() 가 자동 병합합니다.
 * 이 파일은 "모듈 관리자 페이지" 자체로 가는 진입점만 제공합니다.
 */
$menu['menu000'] = array (
    array('000000', '←관리자 페이지로',       G5_ADMIN_URL, 'module'),
    array('000000', '관리자 페이지',  G5_ADMIN_URL, 'module_list'),
);
?>
