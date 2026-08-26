<?php
$sub_menu = "500200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

// ─── 아이템 카테고리 관리 (community_form 에서 이전) ──────────────────
if (isset($_POST['item_cat_update']) && check_admin_token()) {
    $parts = [];
    foreach (($_POST['cf_item_category_spr'] ?? []) as $v) {
        $v = trim($v);
        if ($v !== '') $parts[] = sql_escape_string($v);
    }
    $cf = implode('||', $parts);
    sql_query("UPDATE `{$g5['config_table']}` SET cf_item_category = '{$cf}'");
    goto_url('./item_list.php');
}

// 입력값 초기화 (PHP 8 호환)
$type = trim($_GET['type'] ?? '');
$cate = trim($_GET['cate'] ?? '');
$cate2 = trim($_GET['cate2'] ?? '');
$map_id = trim($_GET['map_id'] ?? '');

$sfl = trim($_GET['sfl'] ?? 'it_name');
$stx = trim($_GET['stx'] ?? '');
$sst = trim($_GET['sst'] ?? '');
$sod = trim($_GET['sod'] ?? '');
$page = (int)($_GET['page'] ?? 1);

// 허용된 검색필드만 사용 (기본 보호)
$allowed_sfl = array('it_name','it_content');
if (!in_array($sfl, $allowed_sfl, true)) {
    $sfl = 'it_name';
}

// 카테고리 설정 (기능 칩 — it_type 필터용)
$category = explode("||", (string)$config['cf_item_category']);

$_cat_core = array('일반', '개인', '출석');
$_cat_mod  = function_exists('mdl_apply_filters') ? mdl_apply_filters('item_category_options', array()) : array();
$item_category_options = $_cat_core;
foreach ((array)$_cat_mod as $_mc) {
    $_lbl = is_array($_mc) ? (isset($_mc['label']) ? (string)$_mc['label'] : '') : (string)$_mc;
    if ($_lbl !== '' && !in_array($_lbl, $item_category_options, true)) {
        $item_category_options[] = $_lbl;
    }
}
unset($_cat_core, $_cat_mod, $_mc, $_lbl);

$sql_common = " FROM {$g5['item_table']} ";
$sql_search = " WHERE 1 ";

// 검색어 처리
if ($stx !== '') {
    $like = '%'.sql_real_escape_string($stx).'%';
    $sql_search .= " AND ({$sfl} LIKE '{$like}') ";
}

// 카테고리 필터
if ($cate !== '') {
    $cate_esc = sql_real_escape_string($cate);
    $sql_search .= " AND it_category = '{$cate_esc}' ";
}

// 타입 필터
if ($type !== '') {
    $type_esc = sql_real_escape_string($type);
    $sql_search .= " AND it_type = '{$type_esc}' ";
}

// 정렬 기본값
if ($sst === '') {
    $sst = 'it_id';
    $sod = 'asc';
}

// 정렬 필드 화이트리스트
$allowed_sst = array('it_id','it_name','it_category','it_type');
if (!in_array($sst, $allowed_sst, true)) {
    $sst = 'it_id';
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

// 전체목록 링크용 qstr 구성
$qstr = http_build_query(array(
    'type'  => $type,
    'cate'  => $cate,
    'cate2' => $cate2,
    'map_id'=> $map_id,
    'sfl'   => $sfl,
    'stx'   => $stx,
    'sst'   => $sst,
    'sod'   => $sod,
));

// 출력 준비
$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

$g5['title'] = '아이템 관리';
include_once('./admin.head.php');

$colspan = 15;
?>

<div class="local_ov01 local_ov">
    <?php echo $listall ?>
    추가된 아이템 수 <?php echo number_format($total_count) ?>개
</div>

<details class="adm-panel cat-chips-panel" open>
<summary>아이템 카테고리(기능) 관리</summary>
<div class="adm-panel-body">
<form method="post" action="">
<?php echo get_admin_token_field(); ?>
<input type="hidden" name="item_cat_update" value="1">
<p class="adm-panel-help">아이템에 부여할 <strong>기능 카테고리</strong>를 관리합니다. 칩을 드래그하여 순서를 바꾸거나, ×로 삭제, 텍스트를 클릭해 바로 수정할 수 있습니다.</p>
<?php
$_cat_list = explode('||', isset($config['cf_item_category']) ? $config['cf_item_category'] : '');
?>
<div class="cat-chips" data-name="cf_item_category_spr[]">
<?php foreach ($_cat_list as $c): if (trim($c) === '') continue; ?>
	<span class="cat-chip" draggable="true">
		<span class="cat-chip-handle" title="드래그">⋮⋮</span>
		<input type="text" class="cat-chip-edit" value="<?php echo htmlspecialchars($c, ENT_QUOTES); ?>">
		<button type="button" class="cat-chip-del" title="삭제">×</button>
		<input type="hidden" name="cf_item_category_spr[]" value="<?php echo htmlspecialchars($c, ENT_QUOTES); ?>">
	</span>
<?php endforeach; ?>
</div>
<div class="cat-add-row">
	<input type="text" class="frm_input cat-add-input" placeholder="새 카테고리 이름 입력 후 Enter 또는 [추가]" maxlength="32">
	<button type="button" class="btn_03 cat-add-btn">추가</button>
	<span style="font-size:11px;color:#888;">변경 후 반드시 [카테고리 저장]을 눌러야 적용됩니다.</span>
</div>

<?php
// 모듈이 추가하는 기능(읽기전용) — 'item_type_options' 필터 (모듈 컨벤션)
$_mod_cats = function_exists('mdl_apply_filters') ? mdl_apply_filters('item_type_options', array()) : array();
if (!is_array($_mod_cats)) $_mod_cats = array();
if (!empty($_mod_cats)):
?>
<div class="cat-readonly-section">
	<h4>모듈에서 추가된 기능 (잠금 — 모듈에서 관리)</h4>
	<div class="cat-chips" style="background:#f4f4f8;">
<?php foreach ($_mod_cats as $_mc):
	if (is_array($_mc)) {
		$_label = isset($_mc['label']) ? $_mc['label'] : '';
		$_src   = isset($_mc['module']) ? $_mc['module'] : '';
	} else {
		$_label = (string)$_mc;
		$_src   = '';
	}
	if ($_label === '') continue; ?>
		<span class="cat-chip chip-readonly" title="<?php echo htmlspecialchars($_src, ENT_QUOTES); ?>">
			<span class="cat-chip-text"><?php echo htmlspecialchars($_label); ?></span>
<?php if ($_src): ?>			<small class="cat-chip-src"><?php echo htmlspecialchars($_src); ?></small><?php endif; ?>
		</span>
<?php endforeach; ?>
	</div>
</div>
<?php endif; ?>

<div class="adm-panel-actions">
	<button type="submit" class="btn_submit">카테고리 저장</button>
</div>
</form>
</div>
</details>
<script src="<?php echo G5_ADMIN_URL; ?>/js/category-chips.js"></script>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
    <select name="cate" id="cate">
        <option value="">아이템 종류</option>
        <?php
        $_cat_labels = array('일반' => '일반 아이템', '개인' => '개인 아이템', '출석' => '출석 아이템');
        foreach ($item_category_options as $_c) {
            $_c_label = isset($_cat_labels[$_c]) ? $_cat_labels[$_c] : $_c;
            ?>
            <option value="<?php echo htmlspecialchars($_c, ENT_QUOTES); ?>" <?php echo $cate === $_c ? 'selected' : ''; ?>><?php echo htmlspecialchars($_c_label, ENT_QUOTES); ?></option>
        <?php }
        unset($_cat_labels, $_c, $_c_label);
        ?>
    </select>
    <select name="type">
        <option value="">아이템 기능</option>
        <?php for($k=0; $k<count($category); $k++) {
            if(!$category[$k]) continue; ?>
            <option value="<?php echo $category[$k]?>" <?php echo $type === $category[$k] ? "selected" : ""?>><?php echo $category[$k]?></option>
        <?php } ?>
        <option value="뽑기" <?php echo $type === "뽑기" ? "selected" : ""?>>뽑기</option>
        <?php
        // 모듈에서 추가된 기능
        $_filter_mod = function_exists('mdl_apply_filters') ? mdl_apply_filters('item_type_options', array()) : array();
        if (!is_array($_filter_mod)) $_filter_mod = array();
        foreach ($_filter_mod as $_mc):
            if (is_array($_mc)) {
                $_lbl = isset($_mc['label']) ? $_mc['label'] : '';
                $_src = isset($_mc['module']) ? $_mc['module'] : '';
            } else {
                $_lbl = (string)$_mc; $_src = '';
            }
            if ($_lbl === '') continue;
            $_disp = $_src ? $_lbl.' ('.$_src.')' : $_lbl;
        ?>
            <option value="<?php echo htmlspecialchars($_lbl, ENT_QUOTES); ?>" <?php echo $type === $_lbl ? 'selected' : ''; ?>><?php echo htmlspecialchars($_disp); ?></option>
        <?php endforeach; ?>
    </select>

    <label for="sfl" class="sound_only">검색대상</label>
    <select name="sfl" id="sfl">
        <option value="it_name"<?php echo get_selected($sfl, "it_name", true); ?>>아이템 이름</option>
        <option value="it_content"<?php echo get_selected($sfl, "it_content"); ?>>아이템 설명</option>
    </select>
    <label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
    <input type="text" name="stx" value="<?php echo get_text($stx) ?>" id="stx">
    <input type="submit" value="검색" class="btn_submit">
</form>

<?php if ($is_admin) { ?>
<div class="btn_add01 btn_add">
    <a href="./item_form.php" id="bo_add">아이템 추가</a>
</div>
<?php } ?>

<form name="fitemlist" id="fitemlist" action="./item_list_update.php" onsubmit="return fitemlist_submit(this);" method="post">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="cate" value="<?php echo $cate ?>">
<input type="hidden" name="map_id" value="<?php echo $map_id ?>">
<input type="hidden" name="stx" value="<?php echo get_text($stx) ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<input type="hidden" name="token" value="<?php echo get_token(); ?>">

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?> 목록</caption>
        <colgroup>
            <col style="width:40px" />
            <col style="width:80px" />
            <col style="width:120px" />
            <col style="width:100px" />
            <col style="width:60px" />
            <col />
            <col style="width:50px" />
            <col style="width:50px" />
            <col style="width:50px" />
            <col style="width:50px" />
            <col style="width:70px" />
            <col style="width:50px" />
            <col style="width:80px" />
            <col style="width:50px" />
            <col style="width:60px" />
        </colgroup>
        <thead>
        <tr>
            <th scope="col" class="bo-right">
                <label for="chkall" class="sound_only">아이템 전체</label>
                <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
            </th>
            <th scope="col">분류</th>
            <th scope="col" colspan="2">기능</th>
            <th scope="col" colspan="2">아이템</th>
            <th scope="col">귀속</th>
            <th scope="col">영구</th>
            <th scope="col">인벤</th>
            <th scope="col">자비</th>
            <th scope="col">레시피</th>
            <th scope="col" colspan="2">되팔기</th>
            <th scope="col">사용</th>
            <th scope="col">관리</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $one_copy = ''; // PHP 8 대비: 미정의 변수 방지
        for ($i=0; $item = sql_fetch_array($result); $i++) {
            $one_update = '<a href="./item_form.php?w=u&amp;it_id='.$item['it_id'].'&amp;'.$qstr.'">수정</a>';
            $bg = 'bg'.($i % 2);
        ?>
        <tr class="<?php echo $bg; ?>">
            <td>
                <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo get_text($item['it_name']) ?></label>
                <input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
                <input type="hidden" name="it_id[<?php echo $i ?>]" value="<?php echo $item['it_id'] ?>">
            </td>
            <td>
                <select name="it_category[<?php echo $i ?>]" id="it_category_<?php echo $i ?>" style="width:100%;">
                    <?php
                    $_row_cats = $item_category_options;
                    $_cur_cat = isset($item['it_category']) ? (string)$item['it_category'] : '';
                    if ($_cur_cat !== '' && !in_array($_cur_cat, $_row_cats, true)) {
                        $_row_cats[] = $_cur_cat;
                    }
                    $_cat_short = array('일반' => '일반', '개인' => '개인', '출석' => '출석');
                    foreach ($_row_cats as $_c) {
                        $_c_label = isset($_cat_short[$_c]) ? $_cat_short[$_c] : $_c;
                        ?>
                        <option value="<?php echo htmlspecialchars($_c, ENT_QUOTES); ?>" <?php echo $_cur_cat === $_c ? 'selected' : ''; ?>><?php echo htmlspecialchars($_c_label, ENT_QUOTES); ?></option>
                    <?php }
                    unset($_row_cats, $_cur_cat, $_cat_short, $_c, $_c_label);
                    ?>
                </select>
            </td>
            <td>
                <select name="it_type[<?php echo $i ?>]" style="width:100%;">
                    <?php for($k=0; $k<count($category); $k++) {
                        if(!$category[$k]) continue; ?>
                        <option value="<?php echo $category[$k]?>" <?php echo $item['it_type'] === $category[$k] ? "selected" : ""?>><?php echo $category[$k]?></option>
                    <?php } ?>
                    <option value="뽑기" <?php echo $item['it_type'] === "뽑기" ? "selected" : ""?>>뽑기 아이템</option>
                    <?php
                    // 모듈에서 추가된 기능 (행마다 동일 — 캐시된 $_filter_mod 재사용)
                    foreach ($_filter_mod as $_mc):
                        $_lbl = is_array($_mc) ? (isset($_mc['label']) ? $_mc['label'] : '') : (string)$_mc;
                        if ($_lbl === '') continue;
                    ?>
                        <option value="<?php echo htmlspecialchars($_lbl, ENT_QUOTES); ?>" <?php echo $item['it_type'] === $_lbl ? 'selected' : ''; ?>><?php echo htmlspecialchars($_lbl); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="text" name="it_value[<?php echo $i ?>]" value="<?php echo get_text($item['it_value']) ?>" id="it_value_<?php echo $i ?>" size="8">
            </td>
            <td class="txt-center">
                <?php if ($item['it_img']) { ?>
                    <img src="<?php echo htmlspecialchars($item['it_img'], ENT_QUOTES, 'UTF-8')?>" style="max-width:40px;">
                <?php } else { ?>
                    이미지없음
                <?php } ?>
            </td>
            <td class="txt-left">
                <input type="text" name="it_name[<?php echo $i ?>]" value="<?php echo get_text($item['it_name']) ?>" id="it_name_<?php echo $i ?>" style="width:100%;">
            </td>
            <td><input type="checkbox" name="it_has[<?php echo $i ?>]" value="1" id="it_has_<?php echo $i ?>" <?php echo $item['it_has']?'checked':''; ?>></td>
            <td><input type="checkbox" name="it_use_ever[<?php echo $i ?>]" value="1" id="it_use_ever_<?php echo $i ?>" <?php echo $item['it_use_ever']?'checked':''; ?>></td>
            <td><input type="checkbox" name="it_use_able[<?php echo $i ?>]" value="1" id="it_use_able_<?php echo $i ?>" <?php echo $item['it_use_able']?'checked':''; ?>></td>
            <td><input type="checkbox" name="it_use_mmb_able[<?php echo $i ?>]" value="1" id="it_use_mmb_able_<?php echo $i ?>" <?php echo $item['it_use_mmb_able']?'checked':''; ?>></td>
            <td><input type="checkbox" name="it_use_recepi[<?php echo $i ?>]" value="1" id="it_use_recepi_<?php echo $i ?>" <?php echo $item['it_use_recepi']?'checked':''; ?>></td>
            <td><input type="checkbox" name="it_use_sell[<?php echo $i ?>]" value="1" id="it_use_sell_<?php echo $i ?>" <?php echo $item['it_use_sell']?'checked':''; ?>></td>
            <td><input type="text" name="it_sell[<?php echo $i ?>]" value="<?php echo get_text($item['it_sell']) ?>" id="it_sell_<?php echo $i ?>" size="5"></td>
            <td><input type="checkbox" name="it_use[<?php echo $i ?>]" value="Y" id="it_use_<?php echo $i ?>" <?php echo $item['it_use']=='Y'?'checked':''; ?>></td>
            <td class="td_mngsmall">
                <?php echo $one_update ?>
                <?php echo $one_copy ?>
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
    $_SERVER['PHP_SELF'].'?'.$qstr.'&amp;type='.$type.'&amp;cate='.$cate.'&amp;cate2='.$cate2.'&amp;map_id='.$map_id.'&amp;page='
);

include_once('./admin.tail.php');
?>
