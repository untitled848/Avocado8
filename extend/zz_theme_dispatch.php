<?php
/**
 * extend/zz_theme_dispatch.php
 *
 * (과거) 이 파일에서 theme_dispatch_override() 를 호출했으나,
 * extend/* 로드 시점은 common.php 가 아직 완성되지 않은 상태이다.
 * 특히 $html_process / $board_skin_path 등이 아직 설정되지 않아
 * 디스패처 exit 후 해당 변수들이 누락됨.
 *
 * → common.php 마지막에서 직접 호출하도록 이동. 이 파일은 더이상 호출하지 않음.
 */
// 의도적으로 비움 — 하위 호환을 위해 파일만 유지
