<?php
include_once('./_common.php');

$g5['title'] = "상점";
include_once('./_head.php');

if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('shop_index_top', '');

// 기본값 처리
$config['cf_shop_category'] = isset($config['cf_shop_category']) ? $config['cf_shop_category'] : '';
$qstr   = isset($qstr) ? $qstr : '';
$page   = isset($page) && (int)$page > 0 ? (int)$page : 1;

// 상점 카테고리 정보
$shop_cate = array();
if ($config['cf_shop_category'] !== '') {
    $shop_cate = explode("||", $config['cf_shop_category']);
}

// 선택 카테고리
if (!isset($cate) || $cate === '') {
    $cate = isset($shop_cate[0]) ? $shop_cate[0] : '';
}

// 캐릭터 정보 기본값
$character_side  = (isset($character['ch_side']) && $character['ch_side'] !== '') ? $character['ch_side'] : '';
$character_class = (isset($character['ch_class']) && $character['ch_class'] !== '') ? $character['ch_class'] : '';

$sql_common = " FROM {$g5['shop_table']} ";
$sql_search = " WHERE sh_use = '1'";

if ($cate !== '') {
    $sql_search .= " AND ca_name = '{$cate}'";
}

$sql_order = " ORDER BY sh_order ASC ";
$sql_limit = "";

// --- 진열 기간 사용 시

// 1. 날짜
$today = date('Ymd');
$sql_search .= " AND (sh_date_s <= '{$today}' OR sh_date_s = '')";
$sql_search .= " AND (sh_date_e >= '{$today}' OR sh_date_e = '')";

// 2. 시간
$cur_hour = date('H');
$sql_search .= " AND (sh_time_s <= '{$cur_hour}' OR sh_time_s = '')";
$sql_search .= " AND (sh_time_e >= '{$cur_hour}' OR sh_time_e = '')";

// 3. 요일
$cur_week = date('w');
$sql_search .= " AND (sh_week LIKE '%||{$cur_week}||%' OR sh_week = '')";

// 4. 진열 시기 (sh_time)
if (function_exists('shop_time_sql_where') && function_exists('get_current_time')) {
    $sql_search .= shop_time_sql_where('sh_time', get_current_time());
}

// -- 구매 제한 사용 시
// 1. 세력 제한
if ($character_side !== '') {
    $sql_search .= " AND ((sh_side LIKE '%||{$character_side}||%' AND sh_use_side = '1') OR sh_use_side = '0')";
} else {
    $sql_search .= " AND (sh_use_side = '0')";
}

// 2. 종족 제한
if ($character_class !== '') {
    $sql_search .= " AND ((sh_class LIKE '%||{$character_class}||%' AND sh_use_class = '1') OR sh_use_class = '0')";
} else {
    $sql_search .= " AND (sh_use_class = '0')";
}

// --- 페이징 처리
$sql = "SELECT COUNT(*) AS cnt {$sql_common} {$sql_search} {$sql_order}";
$row = sql_fetch($sql);
$total_count = isset($row['cnt']) ? (int)$row['cnt'] : 0;

$page_rows  = max(1, (int)($config['cf_shop_page_rows'] ?? 10));
$total_page = $total_count > 0 ? (int)ceil($total_count / $page_rows) : 1;
if ($page < 1) {
    $page = 1;
}
if ($page > $total_page) {
    $page = $total_page;
}

$from_record = ($page - 1) * $page_rows;

$sql_limit = " LIMIT {$from_record}, {$page_rows}";

$write_pages = get_paging(
    5,
    $page,
    $total_page,
    './index.php?cate='.urlencode($cate).$qstr.'&amp;page='
);

// 상점 목록 조회
$shop_sql    = "SELECT * {$sql_common} {$sql_search} {$sql_order} {$sql_limit}";
$shop_result = sql_query($shop_sql);

$shop_list = array();
for ($i = 0; $shop = sql_fetch_array($shop_result); $i++) {
    $shop_list[$i] = $shop;
    $shop_list[$i]['item'] = isset($shop['it_id']) ? get_item($shop['it_id']) : array();
}

// 스킨 로드 (기본 shop/skin/, 단 활성 테마가 shop 슬롯을 오버라이드하면 그쪽 사용)
$_shop_skin_dir = G5_PATH.'/shop/skin';
if (function_exists('get_slot_theme') && function_exists('theme_resolve_base')) {
    $_th = get_slot_theme('shop');
    if ($_th !== '' && $_th !== 'default') {
        $_tb = theme_resolve_base($_th);
        if ($_tb['abs'] !== '' && is_file($_tb['abs'].'/shop/skin/shop.skin.php')) {
            $_shop_skin_dir = $_tb['abs'].'/shop/skin';
        }
    }
}
// 그리드 CSS 출력 (mode: auto-fill / auto-fit / fixed)
$_grid_mode = isset($config['cf_shop_grid_mode']) ? $config['cf_shop_grid_mode'] : 'auto-fill';
if (!in_array($_grid_mode, array('auto-fit','auto-fill','fixed'), true)) $_grid_mode = 'auto-fill';
$_grid_pc   = (int)($config['cf_shop_grid_pc'] ?? 4);
if ($_grid_pc < 1) $_grid_pc = 4;
$_grid_min  = (int)($config['cf_shop_grid_item_w'] ?? 160);
if ($_grid_min < 40) $_grid_min = 160;
$_grid_h    = (int)($config['cf_shop_grid_item_h'] ?? 0); // 0 = 자동
echo "\n<style>\n";
if ($_grid_mode === 'fixed') {
    echo "#shop_item_list ul{display:grid;grid-template-columns:repeat({$_grid_pc},minmax(0,1fr));gap:8px;list-style:none;padding:0;}\n";
} else {
    echo "#shop_item_list ul{display:grid;grid-template-columns:repeat({$_grid_mode},minmax({$_grid_min}px,1fr));gap:8px;list-style:none;padding:0;}\n";
}
if ($_grid_h > 0) {
    echo "#shop_item_list ul li{margin:0;height:{$_grid_h}px;overflow:hidden;}\n";
} else {
    echo "#shop_item_list ul li{margin:0;}\n";
}
echo "</style>\n";
include $_shop_skin_dir."/shop.skin.php";
?>

<script>
function view_shop_item(shop_idx) {
    var h_link = "./_ajax.shop_item.php?sh_id=" + shop_idx;

    $.ajax({
        async: true,
        url: h_link,
        beforeSend: function() {},
        success: function(data) {
            $('#item_info').empty().append(data);
            if (typeof view_shop_item_complete === "function") {
                view_shop_item_complete();
            }
        },
        error: function() {
            $('#item_info').empty();
        },
        complete: function() {}
    });
}

function fn_buy_item(shop_idx) {
    var h_link = "./_ajax.shop_update.php?sh_id=" + shop_idx;

    $.ajax({
        async: true,
        url: h_link,
        beforeSend: function() {},
        success: function(data) {
            $('#item_info').empty().append(data);
            if (typeof view_shop_item_buy === "function") {
                view_shop_item_buy();
            }
        },
        error: function() {
            $('#item_info').empty();
        },
        complete: function() {}
    });
}

$('.ajax-link a').on('click', function() {
    $(this).addClass('point')
        .parent().siblings().find('a').removeClass('point');

    ajax_shop_link_url($(this).attr('href'), '#shop_item_list');
    return false;
});

function ajax_shop_link_url(url, obj_id) {
    var h_link = url;

    if (typeof(history.pushState) !== "undefined") {
        $.ajax({
            async: true,
            url: h_link,
            beforeSend: function() {},
            success: function(data) {
                var temp_content = $(data).find(obj_id).clone();
                $(obj_id).fadeOut(300, function() {
                    $(this).empty().append(temp_content.html());
                }).fadeIn(300, function() {
                    var link_obj = { Title: '', Url: url };
                    history.pushState(link_obj, link_obj.Title, link_obj.Url);

                    $(obj_id).find('.ajax-link').find('a').on('click', function() {
                        $(this).parents('li').addClass('on').siblings().removeClass('on');
                        ajax_shop_link_url($(this).attr('href'), obj_id);
                        return false;
                    });
                });
            },
            error: function() {
                $(obj_id).fadeOut(300, function() {
                    $(this).empty();
                });
            },
            complete: function() {}
        });
    } else {
        location.href = url;
    }
}
</script>

<?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('shop_index_bottom', ''); ?>

<?php
include_once('./_tail.php');
?>
