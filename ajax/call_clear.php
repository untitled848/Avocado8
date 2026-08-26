<?php
/**
 * 통합 알림 읽음 처리 엔드포인트
 * 로그인 회원의 미확인 알림 전체 bc_check=1 + mb_memo_call=0
 */
include_once('../common.php');

if ($is_member) {
    call_check_clear($member['mb_id']);
}
echo 'ok';
