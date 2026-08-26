<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 아이템 로그 표시 / 폼 select 출력
 */

/**
 * 캐릭터의 mmb-사용가능 인벤토리 아이템 목록을 배열로 반환.
 *
 * @param  array $character  현재 캐릭터
 * @return array
 */
function inventory_get_usable_items($character) {
    global $g5;

    if (empty($character['ch_id'])) return array();
    if (!isset($g5['inventory_table'], $g5['item_table'])) return array();

    $ch_id = addslashes($character['ch_id']);
    $sql = "select inven.in_id, inven.it_id, inven.se_ch_id, inven.se_ch_name, it.it_name, it.it_type, it.it_value, it.it_content2
              from {$g5['inventory_table']} inven
         left join {$g5['item_table']} it on it.it_id = inven.it_id
             where it.it_use_mmb_able = '1'
               and inven.ch_id = '{$ch_id}'
             order by inven.in_id asc";
    $res = sql_query($sql);

    $items = array();
    while ($row = sql_fetch_array($res)) {
        $items[] = $row;
    }
    return $items;
}

/**
 * 선물 가능 아이템 (it_has = 0 / 비귀속)
 *
 * @param  array $character
 * @return array
 */
function inventory_get_giftable_items($character) {
    global $g5;

    if (empty($character['ch_id'])) return array();
    if (!isset($g5['inventory_table'], $g5['item_table'])) return array();

    $ch_id = addslashes($character['ch_id']);
    $sql = "select inven.in_id, inven.it_id, inven.se_ch_id, inven.se_ch_name, it.it_name, it.it_type, it.it_value, it.it_content2, it.it_has
              from {$g5['inventory_table']} inven
         left join {$g5['item_table']} it on it.it_id = inven.it_id
             where inven.ch_id = '{$ch_id}'
               and (it.it_has = '0' OR it.it_has = '' OR it.it_has IS NULL)
             order by inven.in_id asc";
    $res = sql_query($sql);

    $items = array();
    while ($row = sql_fetch_array($res)) {
        $items[] = $row;
    }
    return $items;
}

/**
 * 타임라인 선물 대상 사용자 (본인 제외)
 */
function inventory_get_timeline_gift_targets($tl_table, $my_ch_id) {
    $my_ch_id = (int)$my_ch_id;
    $list = array();
    if ($tl_table === '' || !function_exists('get_timeline_users')) return $list;

    $users = get_timeline_users($tl_table);
    foreach ($users as $u) {
        $ch_id = (int)(isset($u['ch_id']) ? $u['ch_id'] : 0);
        if ($ch_id <= 0 || $ch_id === $my_ch_id) continue;
        $list[] = array(
            'ch_id'   => $ch_id,
            'ch_name' => isset($u['ch_name']) ? $u['ch_name'] : '',
        );
    }
    return $list;
}

/**
 * mmb 선물 대상 (승인 캐릭터, 본인 제외)
 */
function inventory_get_mmb_gift_targets($my_ch_id) {
    $my_ch_id = (int)$my_ch_id;
    $list = array();

    if (function_exists('get_character_list')) {
        $chars = get_character_list('', '', '승인');
        if (!is_array($chars)) {
            return $list;
        }
        foreach ($chars as $c) {
            $ch_id = (int)(isset($c['ch_id']) ? $c['ch_id'] : 0);
            if ($ch_id <= 0 || $ch_id === $my_ch_id) continue;
            $list[] = array(
                'ch_id'   => $ch_id,
                'ch_name' => isset($c['ch_name']) ? $c['ch_name'] : '',
            );
        }
        return $list;
    }

    global $g5;
    if (empty($g5['character_table'])) return $list;
    $sql = "select ch_id, ch_name from {$g5['character_table']}
             where ch_state = '승인'
               and ch_type != 'test'
               and ch_id != '{$my_ch_id}'
             order by ch_name asc";
    $res = sql_query($sql);
    while ($row = sql_fetch_array($res)) {
        $list[] = array(
            'ch_id'   => (int)$row['ch_id'],
            'ch_name' => isset($row['ch_name']) ? $row['ch_name'] : '',
        );
    }
    return $list;
}

/**
 * 보드 종류에 맞는 선물 대상
 */
function inventory_get_gift_targets($bo_table, $my_ch_id) {
    if (function_exists('item_use_is_timeline') && item_use_is_timeline($bo_table)) {
        return inventory_get_timeline_gift_targets($bo_table, $my_ch_id);
    }
    return inventory_get_mmb_gift_targets($my_ch_id);
}

/**
 * select option HTML (data-effect 포함)
 */
function inventory_item_options_html($items, $ch_id) {
    $opts = '';
    if (!is_array($items)) return $opts;
    foreach ($items as $row) {
        $label = inventory_item_option_label($row, $ch_id);
        $effect = htmlspecialchars((string)(isset($row['it_content2']) ? $row['it_content2'] : ''), ENT_QUOTES);
        $opts .= '<option value="'.(int)$row['in_id'].'" data-effect="'.$effect.'">'.$label.'</option>';
    }
    return $opts;
}

/**
 * wr_item_log 문자열을 HTML로 렌더한다.
 *
 * @param string $item_log_raw
 * @param string $board_skin_path
 */
function item_log_render($item_log_raw, $board_skin_path = '') {
    if ($item_log_raw === '' || $item_log_raw === null) return;

    $item_log = is_array($item_log_raw) ? $item_log_raw : explode('||', $item_log_raw);
    if (empty($item_log[0])) return;

    if (function_exists('mdl_apply_filters')) {
        $html = mdl_apply_filters('item_log_render', '', $item_log);
        if ($html !== '') {
            echo $html;
            return;
        }
    }

    require item_template('log.item.tpl.php', $board_skin_path);
}

/**
 * 아이템 옵션 라벨 (선물받음 표시)
 */
function inventory_item_option_label($row, $my_ch_id) {
    $name = isset($row['it_name']) ? $row['it_name'] : '';
    $label = htmlspecialchars($name, ENT_QUOTES);
    $se_ch_id = isset($row['se_ch_id']) ? $row['se_ch_id'] : '';
    if ($se_ch_id !== '' && (int)$se_ch_id !== (int)$my_ch_id) {
        $label .= ' (선물받음)';
    }
    return $label;
}

/**
 * 사용/선물 통합 폼
 *
 * $ctx['id_prefix'] 가 있으면 요소 id 를 접두사로 고유화 (인라인 답글 다중 폼용).
 * $ctx['item_form'] === 'comment' 이면 댓글/답글 레이아웃.
 * POST name 은 항상 동일 (item_mode, use_item, gift_item, gift_to_ch_id).
 *
 * 레이아웃:
 *   타임라인     — 사용/선물. "사용할 아이템" 라벨 유지.
 *   mmb 원글     — 사용/선물. Action 과 같이 좌측 Item / 우측 라디오+드롭다운.
 *   mmb 댓글     — 사용만. 라벨 없이 드롭다운만.
 */
function item_form_select($character, $ctx = array()) {
    if (empty($character['ch_id'])) return '';

    $ch_id = (int)$character['ch_id'];
    $usable = inventory_get_usable_items($character);

    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    $is_timeline = function_exists('item_use_is_timeline') && item_use_is_timeline($bo_table);
    $is_comment = (isset($ctx['item_form']) && $ctx['item_form'] === 'comment');
    $allow_gift = $is_timeline || !$is_comment;

    $giftable = $allow_gift ? inventory_get_giftable_items($character) : array();
    $targets = $allow_gift ? inventory_get_gift_targets($bo_table, $ch_id) : array();

    $has_use = !empty($usable);
    $has_gift_items = $allow_gift && !empty($giftable);
    $has_gift_targets = !empty($targets);

    $raw_pfx = isset($ctx['id_prefix']) ? (string)$ctx['id_prefix'] : '';
    $raw_pfx = preg_replace('/[^a-zA-Z0-9_]/', '', $raw_pfx);
    $id = function ($name) use ($raw_pfx) {
        return $raw_pfx === '' ? $name : $raw_pfx.'_'.$name;
    };

    $root_id = $id('tl_item_panel_root');
    $id_use = $id('use_item');
    $id_use_panel = $id('tl-item-panel-use');
    $id_use_eff = $id('mmb-item-effect');
    $id_use_eff_txt = $id('mmb-item-effect-text');
    $id_gift = $id('gift_item');
    $id_gift_panel = $id('tl-item-panel-gift');
    $id_gift_eff = $id('mmb-gift-item-effect');
    $id_gift_eff_txt = $id('mmb-gift-item-effect-text');
    $id_gift_to = $id('gift_to_ch_id');

    $jid = function ($s) {
        return json_encode((string)$s, JSON_UNESCAPED_UNICODE);
    };

    $h = function ($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES);
    };

    $effect_html = function ($box_id, $txt_id) use ($h) {
        return '<div class="mmb-item-effect" id="'.$h($box_id).'" style="display:none;"><span id="'.$h($txt_id).'"></span></div>';
    };

    $bind_js = 'function bindEffect(selId,boxId,txtId){var sel=document.getElementById(selId);var box=document.getElementById(boxId);var txt=document.getElementById(txtId);if(!sel||!box||!txt)return;function upd(){var opt=sel.options[sel.selectedIndex];var eff=opt&&opt.getAttribute("data-effect");if(eff&&eff.trim()){txt.textContent=eff;box.style.display="";}else{box.style.display="none";}}sel.addEventListener("change",upd);upd();}';

    $sync_js = function ($panel_sel) use ($jid, $root_id, $id_use, $id_gift, $id_gift_to) {
        $js = 'var root=document.getElementById('.$jid($root_id).');';
        $js .= 'if(!root)return;';
        $js .= 'function syncMode(){var mode="use";var radios=root.querySelectorAll("input[name=item_mode]");for(var i=0;i<radios.length;i++){if(radios[i].checked){mode=radios[i].value;break;}}var panels=root.querySelectorAll("'.$panel_sel.'");for(var j=0;j<panels.length;j++){panels[j].style.display=(panels[j].getAttribute("data-mode")===mode)?"":"none";}var useSel=document.getElementById('.$jid($id_use).');var giftSel=document.getElementById('.$jid($id_gift).');var giftTo=document.getElementById('.$jid($id_gift_to).');if(mode==="use"){if(giftSel)giftSel.value="";if(giftTo)giftTo.value="";}else{if(useSel)useSel.value="";}}';
        $js .= 'root.addEventListener("change",function(e){if(e.target&&e.target.name==="item_mode")syncMode();});';
        $js .= 'syncMode();';
        return $js;
    };

    // mmb 댓글: 사용만, 라벨 없이 드롭다운
    if (!$is_timeline && $is_comment) {
        if (!$has_use) {
            return '';
        }
        $html = '<label for="'.$h($id_use).'" class="sound_only">아이템</label>';
        $html .= '<select name="use_item" id="'.$h($id_use).'" class="mmb-item-comment-select">';
        $html .= '<option value="">아이템 선택</option>'.inventory_item_options_html($usable, $ch_id);
        $html .= '</select>';
        $html .= $effect_html($id_use_eff, $id_use_eff_txt);
        $html .= '<script>(function(){'.$bind_js;
        $html .= 'bindEffect('.$jid($id_use).','.$jid($id_use_eff).','.$jid($id_use_eff_txt).');';
        $html .= '})();</script>';
        return $html;
    }

    // mmb 원글: Action 행과 동일 — 좌측 Item, 우측 라디오+드롭다운
    if (!$is_timeline) {
        if (!$has_use && !$has_gift_items) {
            return '';
        }

        $use_checked = $has_use ? ' checked' : '';
        $gift_checked = $has_use ? '' : ' checked';
        $for_id = $has_use ? $id_use : $id_gift;
        $html = '<div class="inner mmb-item-write" id="'.$h($root_id).'">';
        $html .= '<dl>';
        $html .= '<dt><label for="'.$h($for_id).'"><i class="icon item"></i>Item</label></dt>';
        $html .= '<dd>';

        $html .= '<div class="mmb-item-mode">';
        $html .= '<label class="mmb-item-mode-opt"><input type="radio" name="item_mode" value="use"'.$use_checked.'> 아이템 사용</label>';
        $html .= '<label class="mmb-item-mode-opt"><input type="radio" name="item_mode" value="gift"'.$gift_checked.'> 아이템 선물</label>';
        $html .= '</div>';

        $html .= '<div class="mmb-item-mode-panel" id="'.$h($id_use_panel).'" data-mode="use">';
        if ($has_use) {
            $html .= '<select name="use_item" id="'.$h($id_use).'">';
            $html .= '<option value="">아이템 선택</option>'.inventory_item_options_html($usable, $ch_id);
            $html .= '</select>';
            $html .= $effect_html($id_use_eff, $id_use_eff_txt);
        } else {
            $html .= '<p class="mmb-item-empty">사용할 수 있는 아이템이 없습니다.</p>';
        }
        $html .= '</div>';

        $html .= '<div class="mmb-item-mode-panel" id="'.$h($id_gift_panel).'" data-mode="gift"'.($has_use ? ' style="display:none;"' : '').'>';
        if ($has_gift_items) {
            $html .= '<select name="gift_item" id="'.$h($id_gift).'">';
            $html .= '<option value="">선물할 아이템 선택</option>'.inventory_item_options_html($giftable, $ch_id);
            $html .= '</select>';
            $html .= $effect_html($id_gift_eff, $id_gift_eff_txt);
            if (!$has_gift_targets) {
                $html .= '<p class="mmb-item-empty">선물할 수 있는 멤버가 없습니다.</p>';
            } else {
                $html .= '<select name="gift_to_ch_id" id="'.$h($id_gift_to).'" class="mmb-item-gift-to">';
                $html .= '<option value="">선물 대상 선택</option>';
                foreach ($targets as $t) {
                    $html .= '<option value="'.(int)$t['ch_id'].'">'.$h($t['ch_name']).'</option>';
                }
                $html .= '</select>';
            }
        } else {
            $html .= '<p class="mmb-item-empty">선물할 수 있는 아이템이 없습니다.</p>';
        }
        $html .= '</div>';

        $html .= '</dd></dl>';
        $html .= '<script>(function(){';
        $html .= $bind_js;
        if ($has_use) {
            $html .= 'bindEffect('.$jid($id_use).','.$jid($id_use_eff).','.$jid($id_use_eff_txt).');';
        }
        if ($has_gift_items) {
            $html .= 'bindEffect('.$jid($id_gift).','.$jid($id_gift_eff).','.$jid($id_gift_eff_txt).');';
        }
        $html .= $sync_js('.mmb-item-mode-panel');
        $html .= '})();</script>';
        $html .= '</div>';
        return $html;
    }

    // 타임라인: 현행 UI 유지
    $html = '<div class="tl-item-panel-inner" id="'.$h($root_id).'">';

    if (!$has_use && !$has_gift_items) {
        $html .= '<p class="tl-item-empty">사용/선물 가능한 아이템이 없습니다.</p>';
        $html .= '</div>';
        return $html;
    }

    $html .= '<div class="tl-item-mode">';
    if ($has_use) {
        $html .= '<label class="tl-item-mode-opt"><input type="radio" name="item_mode" value="use" checked> 아이템 사용</label>';
    }
    if ($has_gift_items) {
        $checked = $has_use ? '' : ' checked';
        $html .= '<label class="tl-item-mode-opt"><input type="radio" name="item_mode" value="gift"'.$checked.'> 아이템 선물</label>';
    }
    $html .= '</div>';

    if ($has_use) {
        $html .= '<div class="tl-item-mode-panel" id="'.$h($id_use_panel).'" data-mode="use">';
        $html .= '<dl class="item-select"><dt><label for="'.$h($id_use).'">사용할 아이템</label></dt><dd>';
        $html .= '<select name="use_item" id="'.$h($id_use).'">';
        $html .= '<option value="">사용할 아이템 선택</option>'.inventory_item_options_html($usable, $ch_id);
        $html .= '</select>';
        $html .= $effect_html($id_use_eff, $id_use_eff_txt);
        $html .= '</dd></dl></div>';
    }

    if ($has_gift_items) {
        $display = $has_use ? ' style="display:none;"' : '';
        $html .= '<div class="tl-item-mode-panel" id="'.$h($id_gift_panel).'" data-mode="gift"'.$display.'>';
        $html .= '<dl class="item-select"><dt><label for="'.$h($id_gift).'">선물할 아이템</label></dt><dd>';
        $html .= '<select name="gift_item" id="'.$h($id_gift).'">';
        $html .= '<option value="">선물할 아이템 선택</option>'.inventory_item_options_html($giftable, $ch_id);
        $html .= '</select>';
        $html .= $effect_html($id_gift_eff, $id_gift_eff_txt);
        $html .= '</dd></dl>';
        if (!$has_gift_targets) {
            $html .= '<p class="tl-item-empty">선물할 수 있는 멤버가 없습니다.</p>';
        } else {
            $html .= '<dl class="item-select"><dt><label for="'.$h($id_gift_to).'">선물 대상</label></dt><dd>';
            $html .= '<select name="gift_to_ch_id" id="'.$h($id_gift_to).'">';
            $html .= '<option value="">선물 대상 선택</option>';
            foreach ($targets as $t) {
                $html .= '<option value="'.(int)$t['ch_id'].'">'.$h($t['ch_name']).'</option>';
            }
            $html .= '</select></dd></dl>';
        }
        $html .= '</div>';
    }

    $html .= '<script>(function(){';
    $html .= $bind_js;
    $html .= 'bindEffect('.$jid($id_use).','.$jid($id_use_eff).','.$jid($id_use_eff_txt).');';
    $html .= 'bindEffect('.$jid($id_gift).','.$jid($id_gift_eff).','.$jid($id_gift_eff_txt).');';
    $html .= $sync_js('.tl-item-mode-panel');
    $html .= '})();</script>';

    $html .= '</div>';
    return $html;
}

/**
 * 댓글/답글 폼용 — id 접두사로 고유화, item_form=comment
 */
function item_form_select_comment($character, $ctx = array()) {
    $ctx['item_form'] = 'comment';
    if (empty($ctx['id_prefix'])) {
        if (!empty($ctx['list_item']['wr_id'])) {
            $ctx['id_prefix'] = 'cm_'.(int)$ctx['list_item']['wr_id'];
        } elseif (!empty($ctx['wr_id'])) {
            $ctx['id_prefix'] = 'cm_'.(int)$ctx['wr_id'];
        } else {
            $ctx['id_prefix'] = 'cm_'.str_replace('.', '', uniqid('', true));
        }
    }
    return item_form_select($character, $ctx);
}
