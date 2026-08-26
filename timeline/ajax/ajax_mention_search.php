<?php
/**
 * 타임라인 멘션 자동완성 검색 API
 * 사용자 리스트에 등록된 캐릭터 검색 (tu_men_id / tu_name)
 *
 * GET 파라미터:
 * - tl_table: 타임라인 테이블명
 * - q: 검색어 (멘션 ID 또는 표시 이름)
 */
define('_TIMELINE_ALLOW_GUEST_', true);
define('_TIMELINE_ALLOW_NO_CHARACTER_', true);
define('_TIMELINE_SKIP_VIEW_CHECK_', true);

include_once(__DIR__.'/../_common.php');

header('Content-Type: application/json; charset=utf-8');

// 검색어
$query = ses($_GET, 'q', '', 'raw');
$query = trim($query);

// 6자 초과 시 빈 결과 반환 (멘션명 최대 길이)
if (mb_strlen($query) > 6) {
    echo json_encode(array('users' => array()));
    exit;
}

$query_escaped = sql_real_escape_string($query);
$tl_safe = sql_escape_string($tl_table);

$timeline_users = get_timeline_users($tl_table);
$user_count = count($timeline_users);

$results = array();

if ($user_count > 0) {
    // 등록된 사용자: timeline_user.tu_men_id / tu_name 검색
    if ($query_escaped) {
        $search_where = "AND (tu.tu_men_id LIKE '%{$query_escaped}%' OR tu.tu_name LIKE '%{$query_escaped}%')";
        $order_case = "CASE
                WHEN tu.tu_men_id LIKE '{$query_escaped}%' THEN 1
                WHEN tu.tu_name LIKE '{$query_escaped}%' THEN 2
                ELSE 3
            END,";
    } else {
        $search_where = '';
        $order_case = '';
    }

    $sql = "SELECT tu.ch_id, tu.tu_men_id, tu.tu_name, tu.tu_thumb, tu.tu_bio
            FROM {$g5['timeline_user_table']} tu
            WHERE tu.tl_table = '{$tl_safe}'
              {$search_where}
            ORDER BY {$order_case} tu.tu_name ASC
            LIMIT 10";

    $result = sql_query($sql);

    while ($row = sql_fetch_array($result)) {
        $ch_id = (int)$row['ch_id'];
        $avatar = get_timeline_thumb($ch_id);

        $mention_name = ses($row, 'tu_men_id', '', 'raw');
        if ($mention_name === '') {
            $mention_name = ses($row, 'tu_name', '', 'raw');
        }
        if (mb_strlen($mention_name) > 6) {
            $mention_name = mb_substr($mention_name, 0, 6);
        }

        $display_name = ses($row, 'tu_name', '', 'raw');
        if ($display_name === '') {
            $display_name = $mention_name;
        }

        $bio = ses($row, 'tu_bio', '', 'raw');
        $bio = $bio ? mb_substr(strip_tags($bio), 0, 50) : '';

        $results[] = array(
            'ch_id' => $ch_id,
            'name' => $display_name,
            'mention_name' => $mention_name,
            'avatar' => $avatar,
            'bio' => $bio
        );
    }
} else {
    // 사용자 제한 없음 - 승인 캐릭터 ch_name 검색 (tu_men_id 없음)
    if (!$query_escaped) {
        echo json_encode(array('users' => array()));
        exit;
    }

    $sql = "SELECT ch_id, ch_name
            FROM {$g5['character_table']}
            WHERE ch_state = '승인'
              AND ch_name LIKE '%{$query_escaped}%'
            ORDER BY CASE WHEN ch_name LIKE '{$query_escaped}%' THEN 1 ELSE 2 END, ch_name ASC
            LIMIT 10";
    $result = sql_query($sql);

    while ($row = sql_fetch_array($result)) {
        $ch_id = (int)$row['ch_id'];
        $avatar = get_timeline_thumb($ch_id);

        $mention_name = ses($row, 'ch_name', '', 'raw');
        if (mb_strlen($mention_name) > 6) {
            $mention_name = mb_substr($mention_name, 0, 6);
        }

        $results[] = array(
            'ch_id' => $ch_id,
            'name' => $row['ch_name'],
            'mention_name' => $mention_name,
            'avatar' => $avatar,
            'bio' => ''
        );
    }
}

echo json_encode(array('users' => $results));
