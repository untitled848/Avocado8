<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 아이템 전체 정보
function get_item($it_id)
{
    global $g5;

    $it_id = (int)$it_id;
    if ($it_id <= 0) return array();

    $row = sql_fetch("SELECT * FROM {$g5['item_table']} WHERE it_id = '{$it_id}' ");
    if (!is_array($row)) return array();
    return $row;
}

// 아이템 이미지
function get_item_img($it_id)
{
    global $g5;

    $it_id = (int)$it_id;
    if ($it_id <= 0) return '';

    $row = sql_fetch("SELECT it_img FROM {$g5['item_table']} WHERE it_id = '{$it_id}' ");
    $val = isset($row['it_img']) ? $row['it_img'] : '';
    if (function_exists('mdl_apply_filters')) {
        $val = mdl_apply_filters('item_img_url', $val, $it_id);
    }
    return $val;
}

// 아이템 상세 이미지(추가 이미지)
function get_item_detail_img($it_id)
{
    global $g5;

    $it_id = (int)$it_id;
    if ($it_id <= 0) return '';

    $row = sql_fetch("SELECT it_1 FROM {$g5['item_table']} WHERE it_id = '{$it_id}' ");
    $val = isset($row['it_1']) ? $row['it_1'] : '';
    if (function_exists('mdl_apply_filters')) {
        $val = mdl_apply_filters('item_detail_img_url', $val, $it_id);
    }
    return $val;
}

// 아이템 이름
function get_item_name($it_id)
{
    global $g5;

    $it_id = (int)$it_id;
    if ($it_id <= 0) return '';

    $row = sql_fetch("SELECT it_name FROM {$g5['item_table']} WHERE it_id = '{$it_id}' ");
    return isset($row['it_name']) ? $row['it_name'] : '';
}

// 인벤토리 + 아이템 조인 정보
function get_inventory_item($in_id)
{
    global $g5;

    $in_id = (int)$in_id;
    if ($in_id <= 0) return array();

    $sql = "
        SELECT inven.*, item.*
        FROM {$g5['inventory_table']} AS inven
        JOIN {$g5['item_table']} AS item ON inven.it_id = item.it_id
        WHERE inven.in_id = '{$in_id}'
        LIMIT 1
    ";

    $row = sql_fetch($sql);
    if (is_array($row)) {
        $data_url = defined('CDN_TOKEN') ? CDN_TOKEN : '[DATA_URL]';
        if (isset($row['it_img'])) $row['it_img'] = str_replace($data_url, G5_DATA_URL, $row['it_img']);
        if (isset($row['it_1']))   $row['it_1']   = str_replace($data_url, G5_DATA_URL, $row['it_1']);
    }
    if (function_exists('mdl_apply_filters')) {
        $row = mdl_apply_filters('inventory_item_loaded', $row, $in_id);
    }
    if (function_exists('mdl_apply_filters')) {
        $row = mdl_apply_filters('item_loaded', $row, $it_id);
    }
    return $row;
}

// 아이템 삭제 (장비 연동도 같이 제거)
function delete_inventory($in_id, $is_del = 0)
{
    global $g5;

    $in_id = (int)$in_id;
    if ($in_id <= 0) return;

    $row = sql_fetch("SELECT * FROM {$g5['inventory_table']} WHERE in_id = '{$in_id}' ");

    if ((string)$is_del === '0') {
        sql_query("DELETE FROM {$g5['inventory_table']} WHERE in_id = '{$in_id}' ");
    }

    if (function_exists('mdl_do_action')) {
        mdl_do_action('inventory_delete_after', $in_id, $is_del, $row);
    }
}

// 인벤토리에 아이템 추가
function insert_inventory($ch_id, $it_id, $item = null, $count = 1)
{
    global $g5;

    $ch_id  = (int)$ch_id;
    $it_id  = (int)$it_id;
    $count  = (int)$count;

    if ($ch_id <= 0 || $it_id <= 0) return 0;
    if ($count <= 0) $count = 1;

    if (!$item || empty($item['it_id'])) {
        $item = get_item($it_id);
    }
    if (empty($item['it_id'])) return 0;

    if (!function_exists('get_character')) {
        // get_character 미정의 환경 방지
        return 0;
    }

    $ch = get_character($ch_id);
    if (empty($ch['ch_id'])) return 0;

    $ch_id_sql   = sql_real_escape_string($ch['ch_id']);
    $ch_name_sql = sql_real_escape_string($ch['ch_name']);
    $it_id_sql   = sql_real_escape_string($item['it_id']);
    $it_name_sql = sql_real_escape_string($item['it_name']);

    $last_in_id = 0;
    for ($i = 0; $i < $count; $i++) {
        $sql = "
            INSERT INTO {$g5['inventory_table']}
                SET ch_id   = '{$ch_id_sql}',
                    it_id   = '{$it_id_sql}',
                    it_name = '{$it_name_sql}',
                    ch_name = '{$ch_name_sql}'
        ";
        sql_query($sql);
        $last_in_id = (int)sql_insert_id();
    }
    if (function_exists('mdl_do_action')) {
        mdl_do_action('inventory_insert_after', $ch_id, $it_id, $count, $last_in_id, $item);
    }
    return $count;
}

// 탐색/뽑기 아이템 지급
function get_item_explo($ch_id, $it_id)
{
    global $g5;

    $ch_id = (int)$ch_id;
    $it_id = (int)$it_id;

    $result = array();

    if ($ch_id <= 0 || $it_id <= 0) {
        return $result;
    }

    // 0~100 퍼센트 구간
    $seed = rand(0, 100);

    $item_row = sql_fetch("
        SELECT re_it_id AS it_id
        FROM {$g5['explorer_table']}
        WHERE it_id = '{$it_id}'
          AND ie_per_s <= '{$seed}'
          AND ie_per_e >= '{$seed}'
        ORDER BY RAND()
        LIMIT 1
    ");

    if (!empty($item_row['it_id'])) {
        $reward_it_id   = (int)$item_row['it_id'];
        $reward_it_name = get_item_name($reward_it_id);

        if ($reward_it_id > 0) {
            // 교차 호환 — 운명 토큰 모듈이 활성화되어 있고 매핑이 있으면 cross 적용
            if (function_exists('ft_apply_cross_gacha')) {
                $base = array(
                    'pool'    => 'item',
                    'it_id'   => $reward_it_id,
                    'it_name' => $reward_it_name,
                    // base in_id 는 비워두어 cross 헬퍼가 직접 지급/대체하도록 함
                );
                $picked = ft_apply_cross_gacha('item', $it_id, $ch_id, $base);
                if (!empty($picked['it_id'])) {
                    $result['it_id']   = (int)$picked['it_id'];
                    $result['it_name'] = $picked['it_name'];
                    if (!empty($picked['pool'])) $result['pool'] = $picked['pool'];
                }
                return $result;
            }
            insert_inventory($ch_id, $reward_it_id);
            $result['it_id']   = $reward_it_id;
            $result['it_name'] = $reward_it_name;
        }
    } else if (function_exists('ft_apply_cross_gacha')) {
        // 코어 explorer 가 미당첨이어도 cross 매핑만으로 결과가 결정될 수 있음
        $picked = ft_apply_cross_gacha('item', $it_id, $ch_id, null);
        if (!empty($picked['it_id'])) {
            $result['it_id']   = (int)$picked['it_id'];
            $result['it_name'] = $picked['it_name'];
            if (!empty($picked['pool'])) $result['pool'] = $picked['pool'];
        }
    }

    return $result;
}

/**
 * 캐릭터가 제작한 아이템 목록 (아이템추가/가구커스텀 재지급 드롭다운).
 *
 * @param int          $ch_id
 * @param string|array $it_types     it_type 필터. 문자열이면 1개.
 * @param string       $it_category  빈 문자열이면 카테고리 조건 생략.
 * @return array
 */
function get_maker_item_list($ch_id, $it_types = '일반', $it_category = '개인')
{
    global $g5;

    $ch_id = (int)$ch_id;
    if ($ch_id <= 0 || empty($g5['item_table'])) return array();

    if (!is_array($it_types)) {
        $it_types = array($it_types);
    }
    $type_in = array();
    foreach ($it_types as $t) {
        $t = trim((string)$t);
        if ($t === '') continue;
        $type_in[] = "'".sql_real_escape_string($t)."'";
    }
    if (!$type_in) return array();

    $cat_sql = '';
    $it_category = trim((string)$it_category);
    if ($it_category !== '') {
        $cat_sql = " AND it_category = '".sql_real_escape_string($it_category)."' ";
    }

    $sql = "SELECT it_id, it_name
            FROM {$g5['item_table']}
            WHERE maker_ch_id = '{$ch_id}'
              AND it_type IN (".implode(',', $type_in).")
              {$cat_sql}
            ORDER BY it_name ASC, it_id ASC";
    $result = sql_query($sql, false);
    if (!$result) return array();

    $list = array();
    while ($row = sql_fetch_array($result)) {
        if (empty($row['it_id'])) continue;
        $list[] = $row;
    }
    return $list;
}

/**
 * 재지급 대상이 이 캐릭터의 제작 아이템인지 (드롭다운 필터와 동일 조건).
 */
function item_is_maker_reusable($item, $ch_id, $it_types = '일반', $it_category = '개인')
{
    if (!is_array($item) || empty($item['it_id'])) return false;
    if ((int)$item['maker_ch_id'] !== (int)$ch_id) return false;

    if (!is_array($it_types)) {
        $it_types = array($it_types);
    }
    $types = array();
    foreach ($it_types as $t) {
        $t = trim((string)$t);
        if ($t !== '') $types[] = $t;
    }
    if (!$types || !in_array((string)$item['it_type'], $types, true)) return false;

    $it_category = trim((string)$it_category);
    if ($it_category !== '' && (string)$item['it_category'] !== $it_category) return false;

    return true;
}

/**
 * 제작 아이템 1개를 인벤에 지급하고 티켓(아이템추가/가구커스텀)을 소모한다.
 *
 * @return bool
 */
function item_grant_maker_reuse($ch_id, $reuse_it_id, $ticket_in_id, $it_types = '일반', $it_category = '개인')
{
    $ch_id = (int)$ch_id;
    $reuse_it_id = (int)$reuse_it_id;
    $ticket_in_id = (int)$ticket_in_id;
    if ($ch_id <= 0 || $reuse_it_id <= 0 || $ticket_in_id <= 0) return false;
    if (!function_exists('get_item') || !function_exists('insert_inventory') || !function_exists('delete_inventory')) {
        return false;
    }

    $item = get_item($reuse_it_id);
    if (!item_is_maker_reusable($item, $ch_id, $it_types, $it_category)) {
        return false;
    }
    if (insert_inventory($ch_id, $reuse_it_id, $item, 1) <= 0) {
        return false;
    }
    delete_inventory($ticket_in_id);
    return true;
}
