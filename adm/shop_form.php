<?php
$sub_menu = "510100";
include_once('./_common.php');
include_once(G5_EDITOR_LIB);

auth_check($auth[$sub_menu], 'w');

// 요청값 기본 처리 (PHP 8에서 undefined 방지)
if (!isset($w))     $w = $_REQUEST['w'] ?? '';
if (!isset($sh_id)) $sh_id = isset($_REQUEST['sh_id']) ? preg_replace('/[^0-9]/', '', $_REQUEST['sh_id']) : '';
if (!isset($sfl))   $sfl = $_REQUEST['sfl'] ?? '';
if (!isset($stx))   $stx = $_REQUEST['stx'] ?? '';
if (!isset($sst))   $sst = $_REQUEST['sst'] ?? '';
if (!isset($sod))   $sod = $_REQUEST['sod'] ?? '';
if (!isset($page))  $page = (int)($_REQUEST['page'] ?? 1);
if (!isset($qstr))  $qstr  = '';

// 카테고리
$category = explode("||", $config['cf_shop_category']);

$html_title = '상점 진열 아이템';
$shop = array(); // 기본 초기화

if ($w == '') {
    $html_title .= ' 등록';
    $sound_only = '<strong class="sound_only">필수</strong>';
    $shop['sh_use'] = '1';
    $shop['sh_use_money'] = '1';
} else if ($w == 'u') {
    $html_title .= ' 수정';
    $shop = sql_fetch("select * from {$g5['shop_table']} where sh_id = '{$sh_id}'");
    if (!isset($shop['sh_id']) || !$shop['sh_id']) {
        alert('존재하지 않는 진열정보 입니다.');
    }
    $readonly = 'readonly';
} else {
    // 정의되지 않은 w 값 보호
    $w = '';
}

$g5['title'] = $html_title;
include_once('./admin.head.php');

$pg_anchor = '<ul class="anchor">
    <li><a href="#anc_001">기본 설정</a></li>
    <li><a href="#anc_002">구매가 설정</a></li>
    <li><a href="#anc_003">구매제한 설정</a></li>
</ul>';

$frm_submit = '<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="확인" class="btn_submit" accesskey="s">
    <a href="./shop_list.php?'.$qstr.'">목록</a>' . PHP_EOL;
$frm_submit .= '</div>';

/** 세력 정보 **/
$ch_si = array();
if (!empty($config['cf_side_title'])) {
    $side_result = sql_query("select si_id, si_name from {$g5['side_table']} where si_auth <= '{$member['mb_level']}' order by si_id asc");
    for ($i = 0; $row = sql_fetch_array($side_result); $i++) {
        $ch_si[$i]['name'] = $row['si_name'];
        $ch_si[$i]['id'] = $row['si_id'];
    }
}

/** 종족 정보 **/
$ch_cl = array();
if (!empty($config['cf_class_title'])) {
    $class_result = sql_query("select cl_id, cl_name from {$g5['class_table']} where cl_auth <= '{$member['mb_level']}' order by cl_id asc");
    for ($i = 0; $row = sql_fetch_array($class_result); $i++) {
        $ch_cl[$i]['name'] = $row['cl_name'];
        $ch_cl[$i]['id'] = $row['cl_id'];
    }
}

include_once(G5_PLUGIN_PATH.'/jquery-ui/datepicker.php');

if (!isset($fr_date) || empty($fr_date)) {
    $fr_date = G5_TIME_YMD;
}
?>

<form name="fshopform" id="fshopform" action="./shop_form_update.php" onsubmit="return fshopform_submit(this)" method="post" enctype="multipart/form-data">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="sh_id" value="<?php echo $sh_id; ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
<input type="hidden" name="stx" value="<?php echo $stx; ?>">
<input type="hidden" name="sst" value="<?php echo $sst; ?>">
<input type="hidden" name="sod" value="<?php echo $sod; ?>">
<input type="hidden" name="page" value="<?php echo $page; ?>">

<section id="anc_001">
    <h2 class="h2_frm">진열 기본 설정</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption>아이템 기본 설정</caption>
            <colgroup>
                <col style="width:130px;">
                <col style="width:80px;">
                <col>
            </colgroup>
            <tbody>
                <tr>
                    <th scope="row">판매여부</th>
                    <td colspan="2">
                        <input type="checkbox" name="sh_use" id="sh_use" value="1" <?php echo (isset($shop['sh_use']) && $shop['sh_use'] == '1') ? 'checked' : ''; ?>>
                    </td>
                </tr>
                <tr>
                    <th scope="row">상점 분류</th>
                    <td colspan="2">
                        <select name="ca_name">
                            <option value="">카테고리 선택</option>
                            <?php
                            $category_count = count($category);
                            for ($i = 0; $i < $category_count; $i++) {
                                if (!$category[$i]) continue;
                                $selected = (isset($shop['ca_name']) && $shop['ca_name'] == $category[$i]) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $category[$i]; ?>" <?php echo $selected; ?>>
                                    <?php echo $category[$i]; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">진열 아이템</th>
                    <td colspan="2">
                        <?php $shop_it_id = isset($shop['it_id']) ? $shop['it_id'] : ''; ?>
                        <input type="hidden" name="it_id" id="it_id" value="<?php echo $shop_it_id; ?>">
                        <input type="text" name="it_name" value="<?php echo $shop_it_id ? get_item_name($shop_it_id) : ''; ?>" id="it_name" onkeyup="get_ajax_item(this, 'item_list', 'it_id');">
                        <div id="item_list" class="ajax-list-box"><div class="list"></div></div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">아이템 설명</th>
                    <td colspan="2">
                        <input type="text" name="sh_content" value="<?php echo isset($shop['sh_content']) ? get_text($shop['sh_content']) : ''; ?>" size="80">
                    </td>
                </tr>
                <tr>
                    <th rowspan="3" scope="row">진열기간</th>
                    <td class="bo-right">날짜</td>
                    <td>
                        <?php echo help("※ 날짜를 지정하지 않을 시, 적용되지 않습니다."); ?>
                        <input type="text" name="sh_date_s" value="<?php echo isset($shop['sh_date_s']) ? get_text($shop['sh_date_s']) : ''; ?>" size="15" class="date">
                        ~
                        <input type="text" name="sh_date_e" value="<?php echo isset($shop['sh_date_e']) ? get_text($shop['sh_date_e']) : ''; ?>" size="15" class="date">
                    </td>
                </tr>
                <tr>
                    <td class="bo-right">시간</td>
                    <td>
                        <?php echo help("※ 시작시간과 종료시간이 모두 00 일 시, 적용되지 않습니다."); ?>
                        <select name="sh_time_s">
                            <?php
                            $sh_time_s = isset($shop['sh_time_s']) ? (int)$shop['sh_time_s'] : 0;
                            for ($i = 0; $i <= 24; $i++) {
                                $sel = ($i === $sh_time_s) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $i; ?>" <?php echo $sel; ?>>
                                    <?php echo str_pad($i, 2, "0", STR_PAD_LEFT); ?>
                                </option>
                            <?php } ?>
                        </select> 시
                        ~
                        <select name="sh_time_e">
                            <?php
                            $sh_time_e = isset($shop['sh_time_e']) ? (int)$shop['sh_time_e'] : 0;
                            for ($i = 0; $i <= 24; $i++) {
                                $sel = ($i === $sh_time_e) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $i; ?>" <?php echo $sel; ?>>
                                    <?php echo str_pad($i, 2, "0", STR_PAD_LEFT); ?>
                                </option>
                            <?php } ?>
                        </select> 시
                    </td>
                </tr>
                <tr>
                    <td class="bo-right">요일</td>
                    <td>
                        <?php echo help("※ 요일을 모두 체크하지 않을 시, 적용되지 않습니다."); ?>
                        <?php
                        if (!isset($yoil) || !is_array($yoil)) {
                            $yoil = array('일','월','화','수','목','금','토');
                        }
                        $shop_week = isset($shop['sh_week']) ? $shop['sh_week'] : '';
                        for ($i = 0; $i < 7; $i++) {
                            $checked = (strstr($shop_week, "||".$i."||")) ? 'checked' : '';
                            ?>
                            <span style="display:inline-block;padding-right:20px;">
                                <input type="checkbox" name="sh_week[]" id="sh_week_<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo $checked; ?>>
                                <label for="sh_week_<?php echo $i; ?>"><?php echo $yoil[$i]; ?>요일</label>
                            </span>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td class="bo-right">진열 시기</td>
                    <td colspan="2">
                        <?php av_time_checkbox_group('sh_time', isset($shop['sh_time']) ? $shop['sh_time'] : ''); ?>
                    </td>
                </tr>
                <tr>
                    <th>진열순서</th>
                    <td colspan="2">
                        <input type="text" name="sh_order" value="<?php echo isset($shop['sh_order']) ? $shop['sh_order'] : ''; ?>" id="sh_order">
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
<?php echo $frm_submit; ?>

<section id="anc_002">
    <h2 class="h2_frm">가격 설정</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption>아이템 기본 설정</caption>
            <colgroup>
                <col style="width:130px;">
                <col style="width:100px;">
                <col>
            </colgroup>
            <tbody>
                <tr>
                    <th scope="row" rowspan="2"><?php echo $config['cf_money']; ?> 설정</th>
                    <td class="bo-right">필요 <?php echo $config['cf_money']; ?></td>
                    <td>
                        <input type="text" name="sh_money" value="<?php echo isset($shop['sh_money']) ? $shop['sh_money'] : ''; ?>" size="15"> <?php echo $config['cf_money_pice']; ?>
                    </td>
                </tr>
                <tr>
                    <td class="bo-right">소모여부</td>
                    <td>
                        <input type="checkbox" name="sh_use_money" id="sh_use_money" value="1" <?php echo (isset($shop['sh_use_money']) && $shop['sh_use_money'] == '1') ? 'checked' : ''; ?>>
                        <label for="sh_use_money">구매 시 <?php echo $config['cf_money']; ?> 소모</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row" rowspan="2"><?php echo $config['cf_exp_name']; ?> 설정</th>
                    <td class="bo-right">필요 <?php echo $config['cf_exp_name']; ?></td>
                    <td>
                        <input type="text" name="sh_exp" value="<?php echo isset($shop['sh_exp']) ? $shop['sh_exp'] : ''; ?>" size="15"> <?php echo $config['cf_exp_pice']; ?>
                    </td>
                </tr>
                <tr>
                    <td class="bo-right">소모여부</td>
                    <td>
                        <input type="checkbox" name="sh_use_exp" id="sh_use_exp" value="1" <?php echo (isset($shop['sh_use_exp']) && $shop['sh_use_exp'] == '1') ? 'checked' : ''; ?>>
                        <label for="sh_use_exp">구매 시 <?php echo $config['cf_exp_name']; ?> 소모</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row" rowspan="2">교환아이템 설정</th>
                    <td class="bo-right">필요 아이템</td>
                    <td>
                        <?php $has_item_id = isset($shop['sh_has_item']) ? $shop['sh_has_item'] : ''; ?>
                        <input type="hidden" name="sh_has_item" id="sh_has_item" value="<?php echo $has_item_id; ?>">
                        <input type="text" name="sh_has_item_name" value="<?php echo $has_item_id ? get_item_name($has_item_id) : ''; ?>" id="sh_has_item_name" onkeyup="get_ajax_item(this, 'has_item_list', 'sh_has_item');">
                        <input type="text" name="sh_has_item_count" value="<?php echo isset($shop['sh_has_item_count']) ? $shop['sh_has_item_count'] : ''; ?>" size="5"> 개
                        <div id="has_item_list" class="ajax-list-box"><div class="list"></div></div>
                    </td>
                </tr>
                <tr>
                    <td class="bo-right">소모여부</td>
                    <td>
                        <input type="checkbox" name="sh_use_has_item" id="sh_use_has_item" value="1" <?php echo (isset($shop['sh_use_has_item']) && $shop['sh_use_has_item'] == '1') ? 'checked' : ''; ?>>
                        <label for="sh_use_has_item">구매 시 아이템 소모</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row" rowspan="2">교환타이틀 설정</th>
                    <td class="bo-right">필요 타이틀</td>
                    <td>
                        <?php $has_title_id = isset($shop['sh_has_title']) ? $shop['sh_has_title'] : ''; ?>
                        <input type="hidden" name="sh_has_title" id="sh_has_title" value="<?php echo $has_title_id; ?>">
                        <input type="text" name="sh_has_title_name" value="<?php echo $has_title_id ? get_title($has_title_id, true) : ''; ?>" id="sh_has_title_name" onkeyup="get_ajax_title(this, 'has_title_list', 'sh_has_title');">
                        <div id="has_title_list" class="ajax-list-box"><div class="list"></div></div>
                    </td>
                </tr>
                <tr>
                    <td class="bo-right">소모여부</td>
                    <td>
                        <input type="checkbox" name="sh_use_has_title" id="sh_use_has_title" value="1" <?php echo (isset($shop['sh_use_has_title']) && $shop['sh_use_has_title'] == '1') ? 'checked' : ''; ?>>
                        <label for="sh_use_has_title">구매 시 타이틀 소모</label>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
<?php echo $frm_submit; ?>

<section id="anc_003">
    <h2 class="h2_frm">구매제한 설정</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption>아이템 기본 설정</caption>
            <colgroup>
                <col style="width:130px;">
                <col style="width:100px;">
                <col>
            </colgroup>
            <tbody>
                <tr>
                    <th scope="row">구매갯수</th>
                    <td colspan="2">
                        <?php echo help("※ 1인당 구매가 가능한 갯수를 제한합니다. 0 입력 시 제한하지 않습니다."); ?>
                        <input type="text" name="sh_limit" value="<?php echo isset($shop['sh_limit']) ? $shop['sh_limit'] : ''; ?>" size="10">
                    </td>
                </tr>
                <tr>
                    <th scope="row">재고설정</th>
                    <td colspan="2">
                        <?php echo help("※ 총 판매 갯수를 제한합니다. 0 입력 시 제한하지 않습니다."); ?>
                        <input type="text" name="sh_qty" value="<?php echo isset($shop['sh_qty']) ? $shop['sh_qty'] : ''; ?>" size="10">
                    </td>
                </tr>
                <?php if (!empty($config['cf_side_title']) && !empty($ch_si)) { ?>
                <tr>
                    <th scope="row" rowspan="2"><?php echo $config['cf_side_title']; ?> 제한 설정</th>
                    <td class="bo-right">구매가능</td>
                    <td>
                        <?php
                        $shop_side = isset($shop['sh_side']) ? $shop['sh_side'] : '';
                        $ch_si_count = count($ch_si);
                        for ($i = 0; $i < $ch_si_count; $i++) {
                            $checked = strstr($shop_side, "||".$ch_si[$i]['id']."||") ? 'checked' : '';
                            ?>
                            <span style="display:inline-block;padding-right:20px;">
                                <input type="checkbox" name="sh_side[]" id="sh_side_<?php echo $i; ?>" value="<?php echo $ch_si[$i]['id']; ?>" <?php echo $checked; ?>>
                                <label for="sh_side_<?php echo $i; ?>"><?php echo $ch_si[$i]['name']; ?></label>
                            </span>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td class="bo-right">사용여부</td>
                    <td>
                        <input type="checkbox" name="sh_use_side" id="sh_use_side" value="1" <?php echo (isset($shop['sh_use_side']) && $shop['sh_use_side'] == '1') ? 'checked' : ''; ?>>
                        <label for="sh_use_side"><?php echo isset($config['sh_use_side']) ? $config['sh_use_side'] : ''; ?> 제한 설정을 사용합니다.</label>
                    </td>
                </tr>
                <?php } ?>
                <?php if (!empty($config['cf_class_title']) && !empty($ch_cl)) { ?>
                <tr>
                    <th scope="row" rowspan="2"><?php echo $config['cf_class_title']; ?> 제한 설정</th>
                    <td class="bo-right">구매가능</td>
                    <td>
                        <?php
                        $shop_class = isset($shop['sh_class']) ? $shop['sh_class'] : '';
                        $ch_cl_count = count($ch_cl);
                        for ($i = 0; $i < $ch_cl_count; $i++) {
                            $checked = strstr($shop_class, "||".$ch_cl[$i]['id']."||") ? 'checked' : '';
                            ?>
                            <span style="display:inline-block;padding-right:20px;">
                                <input type="checkbox" name="sh_class[]" id="sh_class_<?php echo $i; ?>" value="<?php echo $ch_cl[$i]['id']; ?>" <?php echo $checked; ?>>
                                <label for="sh_class_<?php echo $i; ?>"><?php echo $ch_cl[$i]['name']; ?></label>
                            </span>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td class="bo-right">사용여부</td>
                    <td>
                        <input type="checkbox" name="sh_use_class" id="sh_use_class" value="1" <?php echo (isset($shop['sh_use_class']) && $shop['sh_use_class'] == '1') ? 'checked' : ''; ?>>
                        <label for="sh_use_class"><?php echo isset($config['sh_use_class']) ? $config['sh_use_class'] : ''; ?> 제한 설정을 사용합니다.</label>
                    </td>
                </tr>
                <?php } ?>
                <?php
                $use_level = sql_fetch("select ad_use_rank from {$g5['article_default_table']}");
                if (isset($use_level['ad_use_rank']) && $use_level['ad_use_rank']) {
                    $level_result = sql_query("select lv_id, lv_name from {$g5['level_table']} order by lv_exp desc");
                    $shop_rank = isset($shop['sh_rank']) ? $shop['sh_rank'] : '';
                ?>
                <tr>
                    <th scope="row" rowspan="2">랭킹 제한 설정</th>
                    <td class="bo-right">구매가능</td>
                    <td>
                        <?php for ($i = 0; $lv = sql_fetch_array($level_result); $i++) {
                            // 기존 코드 버그 수정: "||id||" 누락되어 있었음
                            $checked = strstr($shop_rank, "||".$lv['lv_id']."||") ? 'checked' : '';
                            ?>
                            <span style="display:inline-block;padding-right:20px;">
                                <input type="checkbox" name="sh_rank[]" id="sh_rank_<?php echo $i; ?>" value="<?php echo $lv['lv_id']; ?>" <?php echo $checked; ?>>
                                <label for="sh_rank_<?php echo $i; ?>"><?php echo $lv['lv_name']; ?></label>
                            </span>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td class="bo-right">사용여부</td>
                    <td>
                        <input type="checkbox" name="sh_use_rank" id="sh_use_rank" value="1" <?php echo (isset($shop['sh_use_rank']) && $shop['sh_use_rank'] == '1') ? 'checked' : ''; ?>>
                        <label for="sh_use_rank">랭킹 제한 설정을 사용합니다.</label>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>
<?php echo $frm_submit; ?>

</form>

<script>
$(function(){
    $(".date").datepicker({
        changeMonth:true,
        changeYear:true,
        dateFormat:"yymmdd",
        showButtonPanel:true,
        yearRange:"c-99:c+99"
    });
});
function fshopform_submit(f){
    return true;
}
</script>

<?php
include_once('./admin.tail.php');
?>
