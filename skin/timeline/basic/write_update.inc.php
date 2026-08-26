<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 타임라인 글쓰기 처리 (write_update.inc.php)
 * 게시판 호환 구조 (wr_ 컬럼명 사용)
 * MMB 게시판의 action/game 처리 로직 이식
 * 
 * 입력 변수:
 * - $content : 글 내용
 * - $action : 액션 타입 (S:탐색, H:조합)
 * - $game : 게임 타입 (dice)
 * - $use_item : 사용할 아이템 인벤토리 ID
 * - $make_1, $make_2, $make_3 : 조합 재료 인벤토리 ID
 * - $ch_id : 캐릭터 ID (ajax_post.php에서 전달)
 * - $tl_table : 타임라인 테이블명
 * - $wr_id : 글 INSERT 후 전달되는 ID (ajax_post.php에서 설정)
 * 
 * 출력 변수:
 * - $wr_log : 액션 로그
 * - $wr_item_log : 아이템 사용 로그
 * - $dice1, $dice2 : 주사위 결과
 * - $customer_sql : INSERT 후 추가 UPDATE할 SQL 조각
 */

$wr_log = '';
$wr_item_log = '';
$dice1 = 0;
$dice2 = 0;
$customer_sql = '';

// 입력값 처리
$action = ses($_POST, 'action', '', 'raw');
$game = ses($_POST, 'game', '', 'raw');
$use_item = ses($_POST, 'use_item', '', 'raw');
$make_1 = ses($_POST, 'make_1', '', 'raw');
$make_2 = ses($_POST, 'make_2', '', 'raw');
$make_3 = ses($_POST, 'make_3', '', 'raw');

// ch_id 확인
if (!isset($ch_id) || !$ch_id) {
    $ch_id = ses($character, 'ch_id', 0, 'int');
}

// wr_id가 설정되어 있으면 INSERT 후 추가 처리 모드
// (알림은 insert_timeline_mention → push_call 경로만 사용)

// =====================================================
// 호출(멘션) 처리 - MMB와 동일한 방식
// =====================================================
// 치환 문자로 사용될 문자가 본문에 사용 시 제거 (오류 방지)
$str = str_replace("||", "", $content);
$str = str_replace("&&", "", $str);

// 괄호 치환
$str = str_replace("[[", "||&&", $str);
$str = str_replace("]]", "&&", $str);

// explode로 해당 문자만 추출
$str = explode("||", $str);

// 정규식으로 닉네임 추출
$call_pattern = "/&&(.*)&&/";
$mb_nick_array = array();

for ($i = 0; $i < count($str); $i++) {
    preg_match_all($call_pattern, $str[$i], $matches);
    if (isset($matches[1][0]) && $matches[1][0]) {
        $mb_nick_array[] = $matches[1][0];
    }
}

// 배열 중복값 제거
$mb_nick_array = array_unique($mb_nick_array);

// [[캐릭터]] 유효성만 검사 (알림/멘션 DB는 insert_timeline_mention → push_call('mention'))
if (count($mb_nick_array) > 0) {
    for ($i = 0; $i < count($mb_nick_array); $i++) {
        $memo_search_ch_id = resolve_timeline_mention_ch_id($tl_table, $mb_nick_array[$i]);
        if (!$memo_search_ch_id) {
            $content = str_replace("[[".$mb_nick_array[$i]."]]", "[[???]]", $content);
            $customer_sql .= ", wr_content = '".sql_escape_string($content)."'";
        }
    }
}


// =====================================================
// 아이템 사용 처리
// =====================================================
if ($use_item) {
    $it = sql_fetch("
        SELECT it.it_type, it.it_use_ever, it.it_name, it.it_id, it.it_value, 
               it.it_content, it.it_content2, it.it_image
        FROM {$g5['item_table']} it
        INNER JOIN {$g5['inventory_table']} inven ON inven.it_id = it.it_id
        WHERE inven.in_id = '".sql_escape_string($use_item)."'
          AND inven.ch_id = '{$ch_id}'
    ");
    
    if ($it['it_id']) {
        // 아이템 제거 (영구성 아이템이 아닐 시)
        if (!$it['it_use_ever']) {
            delete_inventory($use_item);
        }
        
        // 뽑기 아이템인 경우
        if ($it['it_type'] == '뽑기') {
            $seed = rand(0, 100);
            
            $item_result = sql_fetch("
                SELECT re_it_id AS it_id
                FROM {$g5['explorer_table']}
                WHERE it_id = '{$it['it_id']}'
                  AND (ie_per_s <= '{$seed}' AND ie_per_e >= '{$seed}')
                ORDER BY RAND()
                LIMIT 1
            ");
            
            if ($item_result['it_id']) {
                $item_result['it_name'] = get_item_name($item_result['it_id']);
                $in_id = insert_inventory($ch_id, $item_result['it_id']);
                $wr_item_log = "S||".$it['it_id']."||".$it['it_name']."||".$item_result['it_id']."||".$item_result['it_name'];
            } else {
                $wr_item_log = "F||".$it['it_id']."||".$it['it_name'];
            }
        } else {
            // 일반 아이템
            $wr_item_log = "D||".$it['it_id']."||".$it['it_name']."||".$it['it_type']."||".$it['it_value']."||".$it['it_content']."||".$it['it_content2'];
        }
        
        $customer_sql .= ", wr_item = '{$it['it_id']}', wr_item_log = '".sql_escape_string($wr_item_log)."'";
    }
}


// =====================================================
// 주사위 굴리기
// =====================================================
if ($game == 'dice' || $action == 'dice') {
    $dice1 = rand(1, 6);
    $dice2 = rand(1, 6);
    $customer_sql .= ", wr_dice1 = '{$dice1}', wr_dice2 = '{$dice2}'";
}


// =====================================================
// 탐색 액션
// =====================================================
if ($action == 'S') {
    // 캐릭터 현재 정보 조회
    $ch_info = sql_fetch("SELECT ch_search, ch_search_date FROM {$g5['character_table']} WHERE ch_id = '{$ch_id}'");
    
    // 날짜 리셋 체크
    if ($ch_info['ch_search_date'] != G5_TIME_YMD) {
        sql_query("UPDATE {$g5['character_table']} SET ch_search = 0, ch_search_date = '".G5_TIME_YMD."' WHERE ch_id = '{$ch_id}'");
        $ch_info['ch_search'] = 0;
    }
    
    $search_count = isset($config['cf_search_count']) ? (int)$config['cf_search_count'] : 3;
    
    if ($ch_info['ch_search'] < $search_count) {
        $seed = rand(0, 100);
        
        $item_result = sql_fetch("
            SELECT it_id, it_name 
            FROM {$g5['item_table']}
            WHERE it_use = 'Y'
              AND it_seeker = '1'
              AND (it_seeker_per_s <= '{$seed}' AND it_seeker_per_e >= '{$seed}')
            ORDER BY RAND()
            LIMIT 1
        ");
        
        if ($item_result['it_id']) {
            $in_id = insert_inventory($ch_id, $item_result['it_id']);
            $wr_log = $action."||S||".$item_result['it_id']."||".$item_result['it_name']."||".$in_id;
        } else {
            $wr_log = $action."||F";
        }
        
        // 탐색 횟수 업데이트
        sql_query("
            UPDATE {$g5['character_table']}
            SET ch_search = ch_search + 1,
                ch_search_date = '".G5_TIME_YMD."'
            WHERE ch_id = '{$ch_id}'
        ");
        
        $customer_sql .= ", wr_log = '".sql_escape_string($wr_log)."'";
    } else {
        $wr_log = $action."||X"; // 횟수 초과
    }
}


// =====================================================
// 조합 액션
// =====================================================
if ($action == 'H') {
    $make_1_item = get_inventory_item($make_1);
    $make_2_item = get_inventory_item($make_2);
    $make_3_item = get_inventory_item($make_3);
    
    // 소유자 확인
    if ($make_1_item['ch_id'] != $ch_id ||
        $make_2_item['ch_id'] != $ch_id ||
        $make_3_item['ch_id'] != $ch_id) {
        $wr_log = $action."||E"; // 권한 없음
    } else {
        $re_item = array();
        $re_item[0] = $make_1_item['it_id'];
        $re_item[1] = $make_2_item['it_id'];
        $re_item[2] = $make_3_item['it_id'];
        sort($re_item);
        $re_item_order = implode("||", $re_item);
        
        $re = sql_fetch("
            SELECT it_id 
            FROM {$g5['item_table']}_recepi 
            WHERE re_item_order = '{$re_item_order}' 
              AND re_use = '1'
        ");
        
        if (!$re['it_id']) {
            // 레시피 조합 실패
            $wr_log = $action."||F||NON||NON||".$re_item_order;
        } else {
            // 레시피 조합 성공
            $item = get_item($re['it_id']);
            $in_id = insert_inventory($ch_id, $item['it_id'], $item);
            $wr_log = $action."||S||".$re['it_id']."||".$item['it_name']."||".$in_id."||".$re_item_order;
        }
        
        // 재료 아이템 제거
        if (!$make_1_item['it_use_ever']) { delete_inventory($make_1_item['in_id']); }
        if (!$make_2_item['it_use_ever']) { delete_inventory($make_2_item['in_id']); }
        if (!$make_3_item['it_use_ever']) { delete_inventory($make_3_item['in_id']); }
        
        $customer_sql .= ", wr_log = '".sql_escape_string($wr_log)."'";
    }
}
