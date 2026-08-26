<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 메뉴 트리 반환
 * @return array [['main'=>row, 'subs'=>[row,...]], ...]
 */
function world_get_menu_tree() {
    global $g5;
    $tree = array();
    $r = sql_query("SELECT * FROM `{$g5['world_menu_table']}` WHERE wm_depth=0 ORDER BY wm_order, wm_id");
    if (!$r) return $tree;
    while ($main = sql_fetch_array($r)) {
        $mid  = (int)$main['wm_id'];
        $subs = array();
        $rs   = sql_query("SELECT * FROM `{$g5['world_menu_table']}` WHERE wm_parent='{$mid}' AND wm_depth=1 ORDER BY wm_order, wm_id");
        if ($rs) { while ($sub = sql_fetch_array($rs)) { $subs[] = $sub; } }
        $tree[] = array('main' => $main, 'subs' => $subs);
    }
    return $tree;
}

/**
 * 현재 페이지의 메뉴 활성 위치 반환
 * @return array [main_wm_id, sub_wm_id|null]
 */
function world_get_active($tree, $current_wo_id) {
    if (empty($current_wo_id)) return array(0, null);
    foreach ($tree as $item) {
        $main = $item['main'];
        foreach ($item['subs'] as $sub) {
            if ($sub['wm_link_wo_id'] === $current_wo_id) {
                return array((int)$main['wm_id'], (int)$sub['wm_id']);
            }
        }
        if ($main['wm_link_wo_id'] === $current_wo_id) {
            return array((int)$main['wm_id'], null);
        }
    }
    return array(0, null);
}

/**
 * 메인 메뉴 클릭 URL
 * 지정 wm_link_wo_id 우선, 없으면 첫 서브 링크로 폴백
 */
function world_resolve_main_link($main_row, $subs) {
    if (!empty($main_row['wm_link_wo_id'])) {
        return G5_WORLD_URL . '/?wo_id=' . urlencode($main_row['wm_link_wo_id']);
    }
    foreach ($subs as $sub) {
        if (!empty($sub['wm_link_wo_id'])) {
            return G5_WORLD_URL . '/?wo_id=' . urlencode($sub['wm_link_wo_id']);
        }
    }
    return G5_WORLD_URL . '/';
}

/**
 * 서브 메뉴 URL
 */
function world_resolve_sub_link($sub_row) {
    if (!empty($sub_row['wm_link_wo_id'])) {
        return G5_WORLD_URL . '/?wo_id=' . urlencode($sub_row['wm_link_wo_id']);
    }
    return G5_WORLD_URL . '/';
}

/**
 * 메뉴 아이템 이미지 URL (파일 없으면 null)
 * 안전장치: 호버 컬럼값이 메인과 동일하면 null 반환 (잘못된 데이터 잔재 대응)
 */
function world_menu_img_url($wm_row, $suffix = 'd') {
    $col  = ($suffix === 'd') ? 'wm_img' : 'wm_img_hover';
    $fname = $wm_row[$col] ?? '';
    if (empty($fname)) return null;
    if ($suffix === 'h' && $fname === ($wm_row['wm_img'] ?? '')) return null;
    $path = G5_DATA_PATH . '/world/menu/' . $fname;
    if (!file_exists($path)) return null;
    return G5_DATA_URL . '/world/menu/' . $fname;
}

/**
 * 디자인 설정 배열 반환
 * @return array ['cs_name' => [0=>cs_value, 1..10=>cs_etc_1..10], ...]
 */
function world_get_config($pd_id) {
    global $g5;
    $pd_id = (int)$pd_id;
    if ($pd_id <= 0) $pd_id = 1;
    $cfg = array();
    $r = sql_query("SELECT * FROM `{$g5['world_config_table']}` WHERE pd_id = '{$pd_id}'");
    if (!$r) return $cfg;
    while ($row = sql_fetch_array($r)) {
        $entry = array($row['cs_value']);
        for ($i = 1; $i <= 10; $i++) { $entry[$i] = $row['cs_etc_'.$i]; }
        $cfg[$row['cs_name']] = $entry;
    }
    return $cfg;
}

/**
 * 첫 페이지 wo_id 반환 (없으면 첫 메인 메뉴의 링크)
 */
function world_get_first_wo_id() {
    global $g5;
    $row = sql_fetch("SELECT wm_link_wo_id FROM `{$g5['world_menu_table']}` WHERE wm_is_first=1 ORDER BY wm_id LIMIT 1");
    if (!empty($row['wm_link_wo_id'])) return $row['wm_link_wo_id'];
    $row = sql_fetch("SELECT wm_link_wo_id FROM `{$g5['world_menu_table']}` WHERE wm_depth=0 ORDER BY wm_order, wm_id LIMIT 1");
    if (!empty($row['wm_link_wo_id'])) return $row['wm_link_wo_id'];
    return null;
}

/**
 * default 스킨용 메뉴 HTML 생성
 *
 * 위치별 렌더링 정책:
 *   상/하 (top/bottom): 메인 행과 활성 메인의 서브 행을 분리해 출력.
 *      <nav>
 *          <div class="world-menu-main-row"> [main1][main2][main3] </div>
 *          <div class="world-menu-sub-row">  [active subs] </div>
 *      </nav>
 *   좌/우 (left/right): 각 메인 아래에 자체 서브를 둔다 (펼침 설정에 따라 활성/모두).
 */
function world_render_menu($tree, $active, $position, $expand = 0) {
    $active_main = $active[0];
    $active_sub  = $active[1];
    $is_vertical = in_array($position, array('left', 'right'));

    $html = '<nav class="world-menu world-menu--pos-' . $position . '">';

    if (!$is_vertical) {
        // === 상/하 위치: 메인 행 + 서브 행 분리 ===
        $html .= '<div class="world-menu-main-row">';
        $active_subs = array();
        foreach ($tree as $item) {
            $main  = $item['main'];
            $subs  = $item['subs'];
            $mid   = (int)$main['wm_id'];
            $is_am = ($mid === (int)$active_main);
            $href  = world_resolve_main_link($main, $subs);
            $img_d = world_menu_img_url($main, 'd');
            $img_h = world_menu_img_url($main, 'h');

            $html .= '<div class="world-menu-main-item' . ($is_am ? ' active' : '') . '">';
            $html .= '<a href="' . htmlspecialchars($href) . '" class="world-menu-main' . ($is_am ? ' active' : '') . '">';
            if ($img_d && $img_h) {
                $html .= '<img src="' . htmlspecialchars($img_d) . '" alt="' . htmlspecialchars($main['wm_title']) . '" class="wm-img-d">';
                $html .= '<img src="' . htmlspecialchars($img_h) . '" alt="" class="wm-img-h" style="display:none">';
            } elseif ($img_d) {
                $html .= '<img src="' . htmlspecialchars($img_d) . '" alt="' . htmlspecialchars($main['wm_title']) . '">';
            } else {
                $html .= htmlspecialchars($main['wm_title']);
            }
            $html .= '</a>';
            $html .= '</div>';

            if ($is_am && !empty($subs)) {
                $active_subs = $subs;
            }
        }
        $html .= '</div>'; // end main-row

        $html .= '<div class="world-menu-sub-row">';
        if (!empty($active_subs)) {
            $html .= '<div class="world-menu-sub-wrap">';
            foreach ($active_subs as $sub) {
                $sid    = (int)$sub['wm_id'];
                $is_as  = ($sid === (int)$active_sub);
                $href_s = world_resolve_sub_link($sub);
                $s_d    = world_menu_img_url($sub, 'd');
                $s_h    = world_menu_img_url($sub, 'h');

                $html .= '<a href="' . htmlspecialchars($href_s) . '" class="world-menu-sub' . ($is_as ? ' active' : '') . '">';
                if ($s_d && $s_h) {
                    $html .= '<img src="' . htmlspecialchars($s_d) . '" alt="' . htmlspecialchars($sub['wm_title']) . '" class="wm-img-d">';
                    $html .= '<img src="' . htmlspecialchars($s_h) . '" alt="" class="wm-img-h" style="display:none">';
                } elseif ($s_d) {
                    $html .= '<img src="' . htmlspecialchars($s_d) . '" alt="' . htmlspecialchars($sub['wm_title']) . '">';
                } else {
                    $html .= htmlspecialchars($sub['wm_title']);
                }
                $html .= '</a>';
            }
            $html .= '</div>';
        }
        $html .= '</div>'; // end sub-row
    } else {
        // === 좌/우 위치: 메인 아래에 각자 서브 ===
        foreach ($tree as $item) {
            $main  = $item['main'];
            $subs  = $item['subs'];
            $mid   = (int)$main['wm_id'];
            $is_am = ($mid === (int)$active_main);
            $href  = world_resolve_main_link($main, $subs);
            $img_d = world_menu_img_url($main, 'd');
            $img_h = world_menu_img_url($main, 'h');

            $html .= '<div class="world-menu-main-item' . ($is_am ? ' active' : '') . '">';
            $html .= '<a href="' . htmlspecialchars($href) . '" class="world-menu-main' . ($is_am ? ' active' : '') . '">';
            if ($img_d && $img_h) {
                $html .= '<img src="' . htmlspecialchars($img_d) . '" alt="' . htmlspecialchars($main['wm_title']) . '" class="wm-img-d">';
                $html .= '<img src="' . htmlspecialchars($img_h) . '" alt="" class="wm-img-h" style="display:none">';
            } elseif ($img_d) {
                $html .= '<img src="' . htmlspecialchars($img_d) . '" alt="' . htmlspecialchars($main['wm_title']) . '">';
            } else {
                $html .= htmlspecialchars($main['wm_title']);
            }
            $html .= '</a>';

            $show_subs = !empty($subs) && ($expand == 1 || $is_am);
            if ($show_subs) {
                $html .= '<div class="world-menu-sub-wrap">';
                foreach ($subs as $sub) {
                    $sid    = (int)$sub['wm_id'];
                    $is_as  = ($sid === (int)$active_sub);
                    $href_s = world_resolve_sub_link($sub);
                    $s_d    = world_menu_img_url($sub, 'd');
                    $s_h    = world_menu_img_url($sub, 'h');
                    $sub_no_hover = ($s_d && !$s_h) ? ' wm-no-hover' : '';

                    $html .= '<a href="' . htmlspecialchars($href_s) . '" class="world-menu-sub' . ($is_as ? ' active' : '') . $sub_no_hover . '">';
                    if ($s_d && $s_h) {
                        $html .= '<img src="' . htmlspecialchars($s_d) . '" alt="' . htmlspecialchars($sub['wm_title']) . '" class="wm-img-d">';
                        $html .= '<img src="' . htmlspecialchars($s_h) . '" alt="" class="wm-img-h" style="display:none">';
                    } elseif ($s_d) {
                        $html .= '<img src="' . htmlspecialchars($s_d) . '" alt="' . htmlspecialchars($sub['wm_title']) . '">';
                    } else {
                        $html .= htmlspecialchars($sub['wm_title']);
                    }
                    $html .= '</a>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }
    }

    $html .= '</nav>';
    return $html;
}
