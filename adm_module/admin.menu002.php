<?php
/**
 * 게시판 액션 모듈 어드민 메뉴 (그룹 002)
 *
 * 그룹 헤더(인덱스 0) 만 정의합니다.
 * 기본 모듈만 이 그룹에 고정 코드를 붙입니다:
 *   bbs_action_search 002100, bbs_action_combine 002200, bbs_dice 002300
 * 그 외 모듈은 module.php 의 admin_menus 'auto' 를 사용하세요.
 */
$menu['menu002'] = array(
    array('002000', '게시판 액션', G5_MDL_ADMIN_URL.'/module_list.php', 'bbs_action_top'),
);
?>
