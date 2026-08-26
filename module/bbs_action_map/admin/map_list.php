<?php
if(!defined('_GNUBOARD_'))exit;
$sub_menu = function_exists('mdl_admin_menu_code') ? mdl_admin_menu_code('bbs_action_map', '200') : '003200';


// 입력값 초기화 (PHP 8 호환)
$sfl = trim($_GET['sfl'] ?? 'ma_name');
$stx = trim($_GET['stx'] ?? '');
$sst = trim($_GET['sst'] ?? '');
$sod = trim($_GET['sod'] ?? '');
$page = (int)($_GET['page'] ?? 1);

// 허용된 검색필드만 사용 (기본 보호)
$allowed_sfl = array('ma_name');
if (!in_array($sfl, $allowed_sfl, true)) {
    $sfl = 'ma_name';
}

$sql_common = " FROM {$g5['module_map_table']} ";
$sql_search = " WHERE 1 ";

// 검색어 처리
if ($stx !== '') {
    $like = '%'.sql_real_escape_string($stx).'%';
    $sql_search .= " AND ({$sfl} LIKE '{$like}') ";
}
    
// 정렬 기본값
if ($sst === '') {
    $sst = 'ma_id';
    $sod = 'asc';
}

// 정렬 필드 화이트리스트
$allowed_sst = array('ma_id');
if (!in_array($sst, $allowed_sst, true)) {
    $sst = 'ma_id';
}
$sod = strtolower($sod) === 'desc' ? 'desc' : 'asc';

$sql_order = " ORDER BY {$sst} {$sod} ";

// 전체 개수
$sql = " SELECT COUNT(*) AS cnt {$sql_common} {$sql_search} ";
$row = sql_fetch($sql);
$total_count = (int)$row['cnt'];

$rows = (int)$config['cf_page_rows'];
if ($rows <= 0) $rows = 20;

$total_page  = (int)ceil($total_count / $rows);
if ($page < 1) $page = 1;
if ($page > $total_page && $total_page > 0) $page = $total_page;

$from_record = ($page - 1) * $rows;

// 목록 조회
$sql = " SELECT * {$sql_common} {$sql_search} {$sql_order} LIMIT {$from_record}, {$rows} ";
$result = sql_query($sql);

// 검색 상태 유지용 qstr — 라우터 m/p 필수 (HOOK_CHECKLIST §H-1)
$qstr = http_build_query(array(
    'm'   => 'bbs_action_map',
    'p'   => 'map_list',
    'sfl' => $sfl,
    'stx' => $stx,
    'sst' => $sst,
    'sod' => $sod,
));

$listall = '<a href="?m=bbs_action_map&amp;p=map_list" class="ov_listall">전체목록</a>';

$g5['title'] = '맵 리스트 관리';
?>
<?php if ($is_admin) { ?>
<div class="btn_add01 btn_add">
    <a href="?m=bbs_action_map&p=map_form" id="bo_add">맵 추가</a>
</div>
<?php } ?>
<div class="local_ov01 local_ov">
    <?php echo $listall ?>
    추가된 맵 수 <?php echo number_format($total_count) ?>개
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
    <input type="hidden" name="m" value="bbs_action_map">
    <input type="hidden" name="p" value="map_list">
    <label for="sfl" class="sound_only">검색대상</label>
    <select name="sfl" id="sfl">
        <option value="ma_name"<?php echo get_selected($sfl, "ma_name", true); ?>>맵 이름</option>
    </select>
    <label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
    <input type="text" name="stx" value="<?php echo get_text($stx) ?>" id="stx">
    <input type="submit" value="검색" class="btn_submit">
</form>

<?php 

$result_list=array();
$departure_map_ids = array();

while ($row = sql_fetch_array($result)) {
    $result_list[] = $row;
    $departure_map_ids[$row['ma_id']]=$row['ma_name'];
}


$cols=array(
    'ma_name' => array('width' => '100px', 'label' => '이름', 'type' => 'text'),
    'ma_thumb' => array('width' => '80px', 'label' => '썸네일', 'type' => 'html', 'html' => function($row) {
        $thumb_url = $row['ma_thumb'] ? htmlspecialchars($row['ma_thumb'], ENT_QUOTES) : G5_MDL_URL.'/img/no_image.png';
        return '<img src="'.$thumb_url.'" style="max-width:60px; max-height:60px;">';
    }),
    'ma_text' => array('width' => '', 'label' => '설명', 'type' => 'textarea'),
);

if(mdl_setting_get('bbs_action_map', 'map_image', '') !== '') {
    $cols['ma_x'] = array('width' => '60px', 'label' => 'X좌표', 'type' => 'number');
    $cols['ma_y'] = array('width' => '60px', 'label' => 'Y좌표', 'type' => 'number');
    $cols['ma_width'] = array('width' => '60px', 'label' => '너비', 'type' => 'number');
    $cols['ma_height'] = array('width' => '60px', 'label' => '높이', 'type' => 'number');
}

$cols['dpt_ma_ids'] = array('width' => '120px', 'label' => '통행', 'type' => 'checkbox_multiple' , 'options' => $departure_map_ids);
$cols['ma_use'] = array('width' => '60px', 'label' => '사용', 'type' => 'checkbox');
$cols['modify'] = array('width' => '100px', 'label' => '관리', 'type' => 'html', 'html' => function($row) {
    $id = (int)$row['ma_id'];
    return '<a href="?m=bbs_action_map&p=map_form&w=u&ma_id='.$id.'">수정</a>'
        .' | <a href="?m=bbs_action_map&p=event_list&ma_id='.$id.'">이벤트</a>';
});


$type='list';
$form_name="map_list_form";
$form_action="?m=bbs_action_map&p=map_list_update";
$hidden_id='ma_id';

// 검색 상태 유지용 hidden — 함수 스코프 밖(여기)에서 구성해서 넘긴다.
//   (함수 내부에서는 $sfl/$stx/$sst/$sod 에 접근할 수 없으므로)
$list_hidden = array(
    'sfl' => $sfl,
    'stx' => $stx,
    'sst' => $sst,
    'sod' => $sod,
);

echo set_insert_form($type, $form_name, $form_action, $result_list, $list_hidden, $cols, $hidden_id);

echo get_paging(
    G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'],
    $page,
    $total_page,
    $_SERVER['PHP_SELF'].'?'.$qstr.'&amp;page='
);
?>
