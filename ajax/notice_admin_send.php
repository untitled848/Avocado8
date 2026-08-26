<?php
/**
 * ajax/notice_admin_send.php — avo_notice 공지 전체 알림 발송 (관리자 전용)
 *
 * POST: no_id, token
 * - 모든 활성 회원(본인 포함)에게 'notice' 타입 알림 push.
 * - 링크: /notice/?no_id=N
 */
// JSON 응답 보호: PHP notice/warning/HTML 출력 차단
@ini_set('display_errors', '0');
@ini_set('html_errors', '0');
@error_reporting(E_ERROR | E_PARSE);
ob_start();

include_once('../common.php');

// common.php / 모듈 로드 중 발생한 모든 출력(경고 HTML 등) 폐기
while (ob_get_level() > 0) { @ob_end_clean(); }
ob_start();

// 치명적 오류 시에도 JSON 으로 응답 (디버그용 메시지 포함)
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
        while (ob_get_level() > 0) { @ob_end_clean(); }
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        // 로그 기록
        @error_log('[notice_admin_send fatal] '.$err['message'].' @ '.$err['file'].':'.$err['line']);
        @file_put_contents(G5_DATA_PATH.'/notice_admin_send.err.log',
            date('c').' '.$err['message'].' @ '.$err['file'].':'.$err['line'].PHP_EOL,
            FILE_APPEND);
        $msg = '서버 오류: '.$err['message'].' ('.basename($err['file']).':'.$err['line'].')';
        echo json_encode(array('ok' => false, 'msg' => $msg), JSON_UNESCAPED_UNICODE);
    }
});

header('Content-Type: application/json; charset=utf-8');

if (empty($is_admin)) {
    echo json_encode(['ok' => false, 'msg' => '권한 없음']);
    exit;
}
if (function_exists('check_token') && !check_token()) {
    echo json_encode(['ok' => false, 'msg' => '토큰 오류']);
    exit;
}

$no_id = (int)($_POST['no_id'] ?? 0);
if ($no_id <= 0) {
    echo json_encode(['ok' => false, 'msg' => '잘못된 요청']);
    exit;
}

@include_once(G5_LIB_PATH.'/notice.lib.php');
$no = function_exists('notice_get_one') ? notice_get_one($no_id) : false;
if (!$no) {
    echo json_encode(['ok' => false, 'msg' => '공지가 없습니다']);
    exit;
}

if (!function_exists('push_call')) {
    echo json_encode(['ok' => false, 'msg' => 'push_call 함수 없음']);
    exit;
}

// 'notice' 타입이 등록·사용중인지 확인
$ct = sql_fetch("SELECT ct_use FROM `{$g5['call_type_map_table']}` WHERE ct_code='notice'", false);
if (!$ct) {
    echo json_encode(['ok' => false, 'msg' => "'notice' 알림 종류가 등록되어 있지 않습니다."]);
    exit;
}

// 전체 활성 회원 (관리자 본인 포함)
$res = sql_query("SELECT mb_id FROM `{$g5['member_table']}` WHERE mb_leave_date='' AND mb_intercept_date=''", false);
$ids = [];
while ($r = sql_fetch_array($res)) { $ids[] = $r['mb_id']; }

if (empty($ids)) {
    echo json_encode(['ok' => true, 'count' => 0]);
    exit;
}

$admin_name=isset($config['cf_title'])?$config['cf_title']:'관리자';

// 본인 포함하려면 sender mb_id 를 빈 문자열로 두어 push_call 의 자기제외 로직을 우회
$count = push_call(
    'notice',
    ['mb_id' => '', 'mb_name' => $admin_name],
    $ids,
    cut_str(strip_tags((string)$no['no_subject']), 50),
    G5_URL.'/notice/?no_id='.(int)$no_id,
    ['no_id' => (int)$no_id]
);

echo json_encode(['ok' => true, 'count' => (int)$count]);

