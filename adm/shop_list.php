<?php
$sub_menu = "510100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

// ─── 상점 카테고리 관리 (community_form 에서 이전) ──────────────────
if (isset($_POST['shop_cat_update']) && check_admin_token()) {
    $parts = [];
    foreach (($_POST['cf_shop_category_spr'] ?? []) as $v) {
        $v = trim($v);
        if ($v !== '') $parts[] = sql_escape_string($v);
    }
    $cf = implode('||', $parts);
    sql_query("UPDATE `{$g5['config_table']}` SET cf_shop_category = '{$cf}'");
    goto_url('./shop_list.php');
}

// ─── 상점 출력 옵션 (페이지당/그리드/NPC 이미지) ─────────────────────────
if (isset($_POST['shop_opt_update']) && check_admin_token()) {
    $cf_shop_page_rows  = max(1, (int)($_POST['cf_shop_page_rows']  ?? 10));
    $cf_shop_grid_mode  = preg_replace('/[^a-z\-]/', '', strtolower((string)($_POST['cf_shop_grid_mode'] ?? 'auto-fill')));
    if (!in_array($cf_shop_grid_mode, array('auto-fit','auto-fill','fixed'), true)) $cf_shop_grid_mode = 'auto-fill';
    $cf_shop_grid_pc    = max(1, (int)($_POST['cf_shop_grid_pc']    ?? 4));
    $cf_shop_grid_item_w = max(40, (int)($_POST['cf_shop_grid_item_w'] ?? 50));
    $cf_shop_grid_item_h = max(0,  (int)($_POST['cf_shop_grid_item_h'] ?? 50)); // 0 = 자동
    $cf_shop_npc        = isset($config['cf_shop_npc']) ? $config['cf_shop_npc'] : '';

    // NPC 이미지 업로드 (data/site 에 저장)
    if (!empty($_FILES['cf_shop_npc_file']['name']) && empty($_FILES['cf_shop_npc_file']['error'])) {
        $site_style_path = G5_DATA_PATH.'/site';
        $site_style_url  = G5_DATA_URL.'/site';
        @mkdir($site_style_path, G5_DIR_PERMISSION);
        @chmod($site_style_path, G5_DIR_PERMISSION);
        $exp = pathinfo($_FILES['cf_shop_npc_file']['name'], PATHINFO_EXTENSION);
        $exp = preg_replace('/[^a-zA-Z0-9]/', '', $exp);
        if ($exp !== '' && in_array(strtolower($exp), array('png','jpg','jpeg','gif','webp','svg'), true)) {
            $image_name = 'shop_npc.'.$exp;
            upload_file($_FILES['cf_shop_npc_file']['tmp_name'], $image_name, $site_style_path);
            if (function_exists('cdn_after_upload')) cdn_after_upload($site_style_path.'/'.$image_name);
            $cf_shop_npc = $site_style_url.'/'.$image_name;
        }
    }
    // NPC 이미지 제거
    if (!empty($_POST['cf_shop_npc_clear'])) {
        $cf_shop_npc = '';
    }

    $cf_shop_npc_e = sql_escape_string($cf_shop_npc);
    sql_query("UPDATE `{$g5['config_table']}` SET
        cf_shop_page_rows   = '{$cf_shop_page_rows}',
        cf_shop_grid_mode   = '{$cf_shop_grid_mode}',
        cf_shop_grid_pc     = '{$cf_shop_grid_pc}',
        cf_shop_grid_item_w = '{$cf_shop_grid_item_w}',
        cf_shop_grid_item_h = '{$cf_shop_grid_item_h}',
        cf_shop_npc         = '{$cf_shop_npc_e}'");
    goto_url('./shop_list.php');
}

/* 요청값 기본 처리: PHP 8에서 undefined 방지 */
if (!isset($cate))   $cate = $_GET['cate'] ?? '';
if (!isset($cate2))  $cate2 = $_GET['cate2'] ?? '';
if (!isset($map_id)) $map_id = $_GET['map_id'] ?? '';
if (!isset($sfl))    $sfl = $_GET['sfl'] ?? 'it_name';
if (!isset($stx))    $stx = trim($_GET['stx'] ?? '');
$f_time = isset($_GET['f_time']) ? preg_replace('/[^0-9a-zA-Z]/', '', (string)$_GET['f_time']) : '';
if (!isset($sst))    $sst = $_GET['sst'] ?? '';
if (!isset($sod))    $sod = $_GET['sod'] ?? '';
if (!isset($page))   $page = (int)($_GET['page'] ?? 1);
if ($page < 1)       $page   = 1;
if (!isset($qstr))   $qstr   = '';

/* 검색 컬럼 화이트리스트 */
$allow_sfl = array('it_name', 'it_content');
if (!in_array($sfl, $allow_sfl)) {
    $sfl = 'it_name';
}

/* 기본 배열 처리 */
$category = explode("||", isset($config['cf_shop_category']) ? $config['cf_shop_category'] : '');

/* 랭킹 사용 여부 */
$use_level_row = sql_fetch("select ad_use_rank from {$g5['article_default_table']}");
$use_level = (isset($use_level_row['ad_use_rank']) && $use_level_row['ad_use_rank']) ? true : false;

/* 검색 쿼리 구성 */
$sql_common = " from {$g5['shop_table']} ";
$sql_search = " where 1 ";

/* 검색어 처리 */
if ($stx !== '') {
    if (function_exists('sql_real_escape_string')) {
        $stx_escaped = sql_real_escape_string($stx);
    } else {
        $stx_escaped = addslashes($stx);
    }

    $sql_search .= " and ( {$sfl} like '%{$stx_escaped}%' ) ";
}

/* 카테고리 검색 */
if ($cate !== '') {
    if (function_exists('sql_real_escape_string')) {
        $cate_escaped = sql_real_escape_string($cate);
    } else {
        $cate_escaped = addslashes($cate);
    }
    $sql_search .= " and ca_name = '{$cate_escaped}' ";
}

/* 시기별 필터 */
if ($f_time !== '' && function_exists('shop_time_sql_where')) {
    if ($f_time === 'all') {
        $sql_search .= " and (sh_time = '' or sh_time = 'all' or sh_time is null) ";
    } else {
        $sql_search .= shop_time_sql_where('sh_time', (int)$f_time);
    }
}

/* 정렬 설정 */
if ($sst === '') {
    $sst = "sh_id";
    $sod = "asc";
}
$sql_order = " order by sh_order desc, {$sst} {$sod} ";

/* 전체 개수 */
$sql = " select count(*) as cnt {$sql_common} {$sql_search} ";
$row = sql_fetch($sql);
$total_count = isset($row['cnt']) ? (int)$row['cnt'] : 0;

/* 페이징 */
$rows = isset($config['cf_page_rows']) ? (int)$config['cf_page_rows'] : 20;
if ($rows < 1) $rows = 20;
$total_page  = $total_count > 0 ? ceil($total_count / $rows) : 1;
if ($page > $total_page) $page = $total_page;
$from_record = ($page - 1) * $rows;

/* 목록 조회 */
$sql = " select * {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

/* 전체목록 링크 */
$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

$g5['title'] = '상점 관리';
include_once('./admin.head.php');
$colspan = 19;

/* 요일 배열 보정 */
if (!isset($yoil) || !is_array($yoil)) {
    $yoil = array('일','월','화','수','목','금','토');
}

/* 버튼용 기본값 */
$one_copy = '';
?>

<div class="local_ov01 local_ov">
    <?php echo $listall; ?>
    진열된 아이템 수 <?php echo number_format($total_count); ?>개
</div>

<details class="adm-panel cat-chips-panel" open>
<summary>상점 카테고리 관리</summary>
<div class="adm-panel-body">
<form method="post" action="">
<?php echo get_admin_token_field(); ?>
<input type="hidden" name="shop_cat_update" value="1">
<p class="adm-panel-help">상점 아이템 분류용 <strong>카테고리</strong>를 관리합니다. 칩을 드래그하여 순서를 바꾸거나, ×로 삭제, 텍스트를 클릭해 바로 수정할 수 있습니다.</p>
<?php
$_cat_list = explode('||', isset($config['cf_shop_category']) ? $config['cf_shop_category'] : '');
?>
<div class="cat-chips" data-name="cf_shop_category_spr[]">
<?php foreach ($_cat_list as $c): if (trim($c) === '') continue; ?>
	<span class="cat-chip" draggable="true">
		<span class="cat-chip-handle" title="드래그">⋮⋮</span>
		<input type="text" class="cat-chip-edit" value="<?php echo htmlspecialchars($c, ENT_QUOTES); ?>">
		<button type="button" class="cat-chip-del" title="삭제">×</button>
		<input type="hidden" name="cf_shop_category_spr[]" value="<?php echo htmlspecialchars($c, ENT_QUOTES); ?>">
	</span>
<?php endforeach; ?>
</div>
<div class="cat-add-row">
	<input type="text" class="frm_input cat-add-input" placeholder="새 카테고리 이름 입력 후 Enter 또는 [추가]" maxlength="32">
	<button type="button" class="btn_03 cat-add-btn">추가</button>
	<span style="font-size:11px;color:#888;">변경 후 반드시 [카테고리 저장]을 눌러야 적용됩니다.</span>
</div>
<div class="adm-panel-actions">
	<button type="submit" class="btn_submit">카테고리 저장</button>
</div>
</form>
</div>
</details>

<details class="adm-panel" open>
<summary>상점 출력 옵션</summary>
<div class="adm-panel-body">
<form method="post" action="" enctype="multipart/form-data">
<?php echo get_admin_token_field(); ?>
<input type="hidden" name="shop_opt_update" value="1">
<?php
$_cf_pr   = (int)($config['cf_shop_page_rows'] ?? 10);
$_cf_gpc  = (int)($config['cf_shop_grid_pc']   ?? 4);
if ($_cf_gpc < 1) $_cf_gpc = 4;
$_cf_gmd  = isset($config['cf_shop_grid_mode']) ? $config['cf_shop_grid_mode'] : 'auto-fill';
if (!in_array($_cf_gmd, array('auto-fit','auto-fill','fixed'), true)) $_cf_gmd = 'auto-fill';
$_cf_giw  = (int)($config['cf_shop_grid_item_w'] ?? 160); if ($_cf_giw < 40) $_cf_giw = 160;
$_cf_gih  = (int)($config['cf_shop_grid_item_h'] ?? 0);   if ($_cf_gih < 0)  $_cf_gih = 0;
$_cf_npc  = isset($config['cf_shop_npc']) ? trim($config['cf_shop_npc']) : '';
?>
<div class="adm-opt-grid">
	<label class="adm-opt-label" for="cf_shop_page_rows">페이지당 아이템 수</label>
	<div>
		<input type="number" name="cf_shop_page_rows" id="cf_shop_page_rows" value="<?php echo $_cf_pr; ?>" min="1" max="200" class="frm_input" size="5"> 개
	</div>

	<label class="adm-opt-label" for="cf_shop_grid_mode">한 줄당 아이템 수</label>
	<div>
		<select name="cf_shop_grid_mode" id="cf_shop_grid_mode" onchange="document.getElementById('cf_shop_grid_pc').disabled = (this.value !== 'fixed');">
			<option value="auto-fill" <?php echo $_cf_gmd==='auto-fill'?'selected':''; ?>>자동 채움 (auto-fill — 빈 칸 유지)</option>
			<option value="auto-fit"  <?php echo $_cf_gmd==='auto-fit' ?'selected':''; ?>>자동 맞춤 (auto-fit — 늘려서 채움)</option>
			<option value="fixed"     <?php echo $_cf_gmd==='fixed'    ?'selected':''; ?>>직접 지정 (고정 컬럼 수)</option>
		</select>
		<input type="number" name="cf_shop_grid_pc" id="cf_shop_grid_pc" value="<?php echo $_cf_gpc; ?>" min="1" max="20" class="frm_input" size="3" <?php echo $_cf_gmd!=='fixed'?'disabled':''; ?>> 개
		<span class="adm-opt-help">'직접 지정' 시 컬럼 수가 적용됩니다. 자동 모드는 화면 폭에 맞춰 동적으로 결정됩니다.</span>
	</div>

	<label class="adm-opt-label" for="cf_shop_grid_item_w">아이템 가로 (px)</label>
	<div>
		<input type="number" name="cf_shop_grid_item_w" id="cf_shop_grid_item_w" value="<?php echo $_cf_giw; ?>" min="40" max="600" class="frm_input" size="5"> px
		<span class="adm-opt-help">자동 모드에서는 최소 칸 너비, 직접 지정 모드에서는 셀 기준 너비.</span>
	</div>

	<label class="adm-opt-label" for="cf_shop_grid_item_h">아이템 세로 (px)</label>
	<div>
		<input type="number" name="cf_shop_grid_item_h" id="cf_shop_grid_item_h" value="<?php echo $_cf_gih; ?>" min="0" max="600" class="frm_input" size="5"> px
		<span class="adm-opt-help">0 이면 내용에 따라 자동 (세로 고정 없음).</span>
	</div>

	<label class="adm-opt-label" for="cf_shop_npc_file">상점 NPC 이미지</label>
	<div>
		<?php if ($_cf_npc !== ''): ?>
			<div style="margin-bottom:6px;"><img src="<?php echo h($_cf_npc); ?>" alt="상점 NPC 미리보기" style="max-height:120px;border:1px solid #ddd;background:#fafafa;padding:4px;"></div>
			<label><input type="checkbox" name="cf_shop_npc_clear" value="1"> 현재 이미지 제거 (기본 npc.png 으로 복귀)</label>
		<?php endif; ?>
		<input type="file" name="cf_shop_npc_file" id="cf_shop_npc_file" accept="image/*" class="frm_input">
		<span class="adm-opt-help">PNG/JPG/GIF/WEBP/SVG 허용. 비워두면 기본값(<code>/img/shop/npc.png</code>) 이 사용됩니다.</span>
	</div>
</div>
<div class="adm-panel-actions">
	<button type="submit" class="btn_submit">출력 옵션 저장</button>
</div>
</form>
</div>
</details>
<script src="<?php echo G5_ADMIN_URL; ?>/js/category-chips.js"></script>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
<?php
// 시기 필터 옵션
$_time_rows = array();
if (isset($g5['time_table'])) {
    $_tr = sql_query("SELECT ti_order, ti_name FROM `{$g5['time_table']}` WHERE ti_open=1 ORDER BY ti_order ASC", false);
    if ($_tr) { while ($_t = sql_fetch_array($_tr)) $_time_rows[] = $_t; }
}
?>
<select name="f_time" id="f_time">
    <option value="">시기 (전체)</option>
    <option value="all"<?php echo $f_time==='all'?' selected':''; ?>>전체시기 진열만</option>
    <?php foreach ($_time_rows as $_tr): ?>
    <option value="<?php echo (int)$_tr['ti_order']; ?>"<?php echo $f_time !== '' && $f_time !== 'all' && (int)$f_time === (int)$_tr['ti_order'] ? ' selected' : ''; ?>><?php echo get_text($_tr['ti_name']); ?></option>
    <?php endforeach; ?>
</select>

<select name="cate" id="cate">
    <option value="">카테고리</option>
    <?php
    $category_count = count($category);
    for ($i = 0; $i < $category_count; $i++) {
        if (!$category[$i]) continue;
        $selected = ($cate === $category[$i]) ? 'selected' : '';
        ?>
        <option value="<?php echo $category[$i]; ?>" <?php echo $selected; ?>>
            <?php echo $category[$i]; ?>
        </option>
    <?php } ?>
</select>

<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
    <option value="it_name"<?php echo get_selected($sfl, "it_name", true); ?>>아이템 이름</option>
    <option value="it_content"<?php echo get_selected($sfl, "it_content"); ?>>아이템 설명</option>
</select>

<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo get_text($stx); ?>" id="stx">
<input type="submit" value="검색" class="btn_submit">
</form>

<?php if ($is_admin) { ?>
<div class="btn_add01 btn_add">
    <a href="./shop_form.php" id="bo_add">진열 추가</a>
    <a href="./shop_time_bulk.php" style="margin-left:6px;">진열 시기 일괄 설정</a>
</div>
<?php } ?>

<form name="fshoplist" id="fshoplist" action="./shop_list_update.php" onsubmit="return fshoplist_submit(this);" method="post">
<input type="hidden" name="sst" value="<?php echo $sst; ?>">
<input type="hidden" name="sod" value="<?php echo $sod; ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
<input type="hidden" name="cate" value="<?php echo $cate; ?>">
<input type="hidden" name="stx" value="<?php echo get_text($stx); ?>">
<input type="hidden" name="page" value="<?php echo $page; ?>">
<input type="hidden" name="token" value="<?php echo $token; ?>">

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?> 목록</caption>
        <thead>
            <tr>
                <th scope="col" class="bo-right">
                    <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
                </th>
                <th scope="col">분류</th>
                <th scope="col" colspan="2">아이템</th>
                <th scope="col">진열기간</th>
                <th scope="col" colspan="2">구매금액</th>
                <th scope="col" colspan="2">구매<?php echo $config['cf_exp_name']; ?></th>
                <th scope="col" colspan="4">교환아이템</th>
                <th scope="col" colspan="2">교환타이틀</th>
                <th scope="col">구매갯수</th>
                <th scope="col">재고갯수</th>
                <th scope="col">순서</th>
                <th scope="col">관리</th>
            </tr>
        </thead>
        <tbody>
        <?php
        for ($i = 0; $shop = sql_fetch_array($result); $i++) {
            $one_update = '<a href="./shop_form.php?w=u&amp;sh_id='.$shop['sh_id'].'&amp;'.$qstr.'">수정</a>';
            $bg = 'bg'.($i % 2);
            $is_order_limit = false;
            ?>
            <tr class="<?php echo $bg; ?>">
                <td style="width:30px;padding:0;" class="txt-center">
                    <input type="checkbox" name="chk[]" value="<?php echo $i; ?>" id="chk_<?php echo $i; ?>">
                    <input type="hidden" name="sh_id[<?php echo $i; ?>]" value="<?php echo $shop['sh_id']; ?>">
                </td>
                <td style="width:100px;">
                    <select name="ca_name[<?php echo $i; ?>]" style="width:100%;">
                        <option value="">카테고리</option>
                        <?php
                        for ($k = 0; $k < $category_count; $k++) {
                            if (!$category[$k]) continue;
                            $sel = ($shop['ca_name'] == $category[$k]) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $category[$k]; ?>" <?php echo $sel; ?>>
                                <?php echo $category[$k]; ?>
                            </option>
                        <?php } ?>
                    </select>
                </td>
                <td style="width:27px;padding:0;border-right-width:0;">
                    <?php if (!empty($shop['it_id'])) { ?>
                        <img src="<?php echo get_item_img($shop['it_id']); ?>" style="max-width:20px;max-height:20px;">
                    <?php } ?>
                </td>
                <td style="text-align:left;padding-left:0;">
                    <?php
                    if (!empty($shop['it_id'])) {
                        echo get_item_name($shop['it_id']);
                    }
                    ?>
                </td>
                <td class="txt-left">
                    <?php if (!empty($shop['sh_date_s'])) {
                        $is_order_limit = true;
                        ?>
                        <p><?php echo date('Y-m-d', strtotime($shop['sh_date_s'])); ?>
                        ~
                        <?php echo date('Y-m-d', strtotime($shop['sh_date_e'])); ?></p>
                    <?php } ?>
                    <?php if (!empty($shop['sh_time_s'])) {
                        $is_order_limit = true;
                        ?>
                        <p><?php echo $shop['sh_time_s']; ?>시 ~ <?php echo $shop['sh_time_e']; ?>시</p>
                    <?php } ?>
                    <?php
                    if (!empty($shop['sh_week'])) {
                        $is_order_limit = true;
                        echo '<p>';
                        $str_week = explode('||', $shop['sh_week']);
                        $add_str = '';
                        $str_week_count = count($str_week);
                        for ($k = 0; $k < $str_week_count; $k++) {
                            if ($str_week[$k] === '') continue;
                            $idx = (int)$str_week[$k];
                            if (isset($yoil[$idx])) {
                                echo $add_str.$yoil[$idx];
                                $add_str = ', ';
                            }
                        }
                        echo '</p>';
                    }
                    // 진열 시기 (sh_time)
                    if (function_exists('shop_time_from_db') && isset($shop['sh_time']) && $shop['sh_time'] !== '' && strtolower($shop['sh_time']) !== 'all') {
                        $sht_arr = shop_time_from_db($shop['sh_time']);
                        if (!empty($sht_arr)) {
                            $is_order_limit = true;
                            static $_sht_name_map = null;
                            if ($_sht_name_map === null) {
                                $_sht_name_map = array();
                                if (!empty($g5['time_table'])) {
                                    $_r = sql_query("SELECT ti_order, ti_name FROM `{$g5['time_table']}` ORDER BY ti_order ASC", false);
                                    if ($_r) { while ($_x = sql_fetch_array($_r)) { $_sht_name_map[(int)$_x['ti_order']] = $_x['ti_name']; } }
                                }
                            }
                            $labels = array();
                            foreach ($sht_arr as $tio) { $labels[] = '#'.$tio.' '.(isset($_sht_name_map[$tio]) ? $_sht_name_map[$tio] : ''); }
                            echo '<p style="color:#7a5500;">시기: '.htmlspecialchars(implode(', ', $labels), ENT_QUOTES, 'UTF-8').'</p>';
                        }
                    }
                    if (!$is_order_limit) {
                        echo "<span style='opacity:.5;'>상시판매</span>";
                    }
                    ?>
                </td>
                <td style="width:30px;padding:0;border-right-width:0;">
                    <input type="checkbox" name="sh_use_money[<?php echo $i; ?>]" value="1" <?php echo !empty($shop['sh_use_money']) ? 'checked' : ''; ?>>
                </td>
                <td style="width:80px;padding-left:0;">
                    <input type="text" name="sh_money[<?php echo $i; ?>]" value="<?php echo get_text($shop['sh_money']); ?>" style="width:100%;">
                </td>
                <td style="width:30px;padding:0;border-right-width:0;">
                    <input type="checkbox" name="sh_use_exp[<?php echo $i; ?>]" value="1" <?php echo !empty($shop['sh_use_exp']) ? 'checked' : ''; ?>>
                </td>
                <td style="width:80px;padding-left:0;">
                    <input type="text" name="sh_exp[<?php echo $i; ?>]" value="<?php echo get_text($shop['sh_exp']); ?>" style="width:100%;">
                </td>
                <td style="width:30px;padding:0;border-right-width:0;">
                    <input type="checkbox" name="sh_use_has_item[<?php echo $i; ?>]" value="1" <?php echo !empty($shop['sh_use_has_item']) ? 'checked' : ''; ?>>
                </td>
                <td style="width:27px;padding:0;border-right-width:0;">
                    <?php if (!empty($shop['sh_has_item'])) { ?>
                        <img src="<?php echo get_item_img($shop['sh_has_item']); ?>" style="max-width:20px;max-height:20px;">
                    <?php } ?>
                </td>
                <td style="text-align:left;padding-left:0;padding-right:0;border-right-width:0;">
                    <?php
                    if (!empty($shop['sh_has_item'])) {
                        echo get_item_name($shop['sh_has_item']);
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
                <td style="width:60px;">
                    <input type="text" name="sh_has_item_count[<?php echo $i; ?>]" value="<?php echo get_text($shop['sh_has_item_count']); ?>" style="width:70%;">개
                </td>
                <td style="width:30px;padding:0;border-right-width:0;">
                    <input type="checkbox" name="sh_use_has_title[<?php echo $i; ?>]" value="1" <?php echo !empty($shop['sh_use_has_title']) ? 'checked' : ''; ?>>
                </td>
                <td style="text-align:left;padding-left:0;">
                    <?php
                    if (!empty($shop['sh_has_title'])) {
                        echo get_title_image($shop['sh_has_title']);
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
                <td style="width:50px;">
                    <input type="text" name="sh_limit[<?php echo $i; ?>]" value="<?php echo get_text($shop['sh_limit']); ?>" style="width:100%;">
                </td>
                <td style="width:50px;">
                    <input type="text" name="sh_qty[<?php echo $i; ?>]" value="<?php echo get_text($shop['sh_qty']); ?>" style="width:100%;">
                </td>
                <td style="width:50px;">
                    <input type="text" name="sh_order[<?php echo $i; ?>]" value="<?php echo get_text($shop['sh_order']); ?>" style="width:100%;">
                </td>
                <td style="width:50px;" class="td_mngsmall">
                    <?php echo $one_update; ?>
                    <?php echo $one_copy; ?>
                </td>
            </tr>
        <?php
        }
        if ($i == 0) {
            echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>

<div class="btn_list01 btn_list">
    <input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value">
    <?php if ($is_admin) { ?>
    <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
    <?php } ?>
</div>

</form>

<?php
echo get_paging(
    G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'],
    $page,
    $total_page,
    $_SERVER['PHP_SELF'].'?'.$qstr.'&amp;cate='.$cate.'&amp;cate2='.$cate2.'&amp;map_id='.$map_id.'&amp;page='
);
?>

<script>
function fshoplist_submit(f){
    if (!is_checked("chk[]")) {
        alert(document.pressed + " 하실 항목을 하나 이상 선택하세요.");
        return false;
    }
    if (document.pressed == "선택삭제") {
        if (!confirm("선택한 자료를 정말 삭제하시겠습니까?")) {
            return false;
        }
    }
    return true;
}
</script>

<?php
include_once('./admin.tail.php');
?>
