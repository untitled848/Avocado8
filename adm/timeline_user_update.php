<?php
include_once('./_common.php');

// 관리자만 접근 가능
if (!$is_admin) {
    alert('관리자만 접근 가능합니다.');
}

include_once(__DIR__.'/timeline_require.inc.php');

$tl_table = ses($_REQUEST, 'tl_table', '', 'string');
if (!$tl_table) {
    alert('타임라인이 지정되지 않았습니다.');
}

$timeline = get_timeline($tl_table);
if (!$timeline) {
    alert('존재하지 않는 타임라인입니다.');
}

$action = ses($_REQUEST, 'action', '', 'string');
$return_url = './timeline_user.php?tl_table='.urlencode($tl_table);

// 토큰 체크
check_admin_token();

switch ($action) {
    case 'read_level':
        // 열람 권한 변경
        $tl_read_level = ses($_POST, 'tl_read_level', 1, 'int');
        if (!in_array($tl_read_level, array(1, 2, 3, 10))) $tl_read_level = 1;
        
        sql_query("UPDATE {$g5['timeline_table']} SET tl_read_level = '{$tl_read_level}' WHERE tl_table = '{$tl_table}'");
        
        goto_url($return_url);
        break;
    
    case 'write_level':
        // 쓰기 권한 변경
        $tl_write_level = ses($_POST, 'tl_write_level', 1, 'int');
        if (!in_array($tl_write_level, array(1, 2))) $tl_write_level = 1;
        
        sql_query("UPDATE {$g5['timeline_table']} SET tl_write_level = '{$tl_write_level}' WHERE tl_table = '{$tl_table}'");
        
        goto_url($return_url);
        break;
        
    case 'remove':
        // 단일 사용자 제거
        $ch_id = ses($_REQUEST, 'ch_id', 0, 'int');
        if ($ch_id) {
            remove_timeline_user($tl_table, $ch_id);
        }
        
        goto_url($return_url);
        break;
        
    case 'bulk':
        // 일괄 처리
        $chk = ses($_POST, 'chk', array(), 'array');
        $act_button = ses($_POST, 'act_button', '', 'string');
        
        if (empty($chk)) {
            alert('선택된 캐릭터가 없습니다.');
        }
        
        $sfl = ses($_POST, 'sfl', '', 'string');
        $stx = ses($_POST, 'stx', '', 'string');
        $page = ses($_POST, 'page', 1, 'int');
        
        foreach ($chk as $ch_id) {
            $ch_id = (int)$ch_id;
            if (!$ch_id) continue;
            
            if ($act_button == '선택 추가') {
                add_timeline_user($tl_table, $ch_id);
            } elseif ($act_button == '선택 제거') {
                remove_timeline_user($tl_table, $ch_id);
            }
        }
        
        $msg = ($act_button == '선택 추가') ? '사용자를 추가했습니다.' : '사용자를 제거했습니다.';
        $return_url .= '&sfl='.$sfl.'&stx='.urlencode($stx).'&page='.$page;
        
        alert($msg, $return_url);
        break;
        
    default:
        alert('잘못된 요청입니다.');
}
