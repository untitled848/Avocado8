<?php
/**
 * [레거시 호환] 세계관 디자인 CSS 는 css/world_design.php (공개) 로 이관됨.
 *
 * 방문자(비로그인/비관리자)는 adm/ 부팅에서 login.php 로 리다이렉트되어
 * CSS 를 받지 못하므로, 실제 페이지(world/index.php 등)는 공개 엔드포인트를
 * 사용한다. 이 파일은 기존 URL 호환을 위해 유지하며 동일 출력을 위임한다.
 */
@include_once('./_common.php');
include __DIR__ . '/../css/world_design.php';
