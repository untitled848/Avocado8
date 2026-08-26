<?php
if (!defined('_GNUBOARD_')) exit;

$sub_menu = function_exists('mdl_admin_menu_code') ? mdl_admin_menu_code('bbs_action_map', '201') : '003201';

$w = isset($_GET['w']) ? $_GET['w'] : (isset($w) ? $w : '');
$me_id = isset($_GET['me_id']) ? (int)$_GET['me_id'] : (isset($me_id) ? (int)$me_id : 0);
$ma_id = isset($_GET['ma_id']) ? (int)$_GET['ma_id'] : (isset($ma_id) ? (int)$ma_id : 0);

$me = array(
    'me_use' => '1',
    'me_type' => '',
    'me_title' => '',
    'me_img' => '',
    'me_content' => '',
    'me_get_item' => 0,
    'me_get_money' => 0,
    'me_move_map' => 0,
    'me_mon_hp' => 0,
    'me_mon_attack' => 0,
    'me_per_s' => 0,
    'me_per_e' => 0,
    'me_replay_cnt' => 0,
    'me_now_cnt' => 0,
);

if ($w === 'u') {
    $me = sql_fetch("SELECT * FROM `{$g5['module_map_event_table']}` WHERE me_id = '{$me_id}' LIMIT 1");
    if (empty($me['me_id'])) alert('존재하지 않는 이벤트입니다.');
    $ma_id = (int)$me['ma_id'];
}

$ma = sql_fetch("SELECT * FROM `{$g5['module_map_table']}` WHERE ma_id = '{$ma_id}' LIMIT 1");
if (empty($ma['ma_id'])) alert('지역정보를 확인할 수 없습니다.');

$maps = array();
$mrs = sql_query("SELECT ma_id, ma_name FROM `{$g5['module_map_table']}` ORDER BY ma_id ASC");
if ($mrs) {
    while ($m = sql_fetch_array($mrs)) {
        $maps[] = array('id' => (int)$m['ma_id'], 'name' => (string)$m['ma_name']);
    }
}

$it_name = '';
if (!empty($me['me_get_item']) && function_exists('get_item_name')) {
    $it_name = get_item_name($me['me_get_item']);
}

$g5['title'] = '['.$ma['ma_name'].'] 지역 이벤트 '.($w === 'u' ? '수정' : '등록');
$list_url = G5_MDL_ADMIN_URL.'/module.php?m=bbs_action_map&p=event_list&ma_id='.(int)$ma_id;
?>
<form name="feventform" method="post" action="?m=bbs_action_map&amp;p=event_form_update" enctype="multipart/form-data">
<input type="hidden" name="w" value="<?php echo htmlspecialchars($w, ENT_QUOTES); ?>">
<input type="hidden" name="me_id" value="<?php echo (int)$me_id; ?>">
<input type="hidden" name="ma_id" value="<?php echo (int)$ma_id; ?>">

<section>
    <h2 class="h2_frm">기본 설정</h2>
    <div class="tbl_frm01 tbl_wrap">
    <table>
    <colgroup><col style="width:140px"><col></colgroup>
    <tbody>
    <tr>
        <th>이벤트명</th>
        <td>
            <input type="text" name="me_title" value="<?php echo get_text(isset($me['me_title']) ? $me['me_title'] : ''); ?>" class="frm_input" style="width:60%;">
            <label><input type="checkbox" name="me_use" value="1" <?php echo !empty($me['me_use']) ? 'checked' : ''; ?>> 사용하기</label>
        </td>
    </tr>
    <tr>
        <th>이벤트타입</th>
        <td>
            <select name="me_type" class="frm_input">
                <option value="" <?php echo (isset($me['me_type']) && $me['me_type'] === '') ? 'selected' : ''; ?>>일반</option>
                <option value="아이템" <?php echo (isset($me['me_type']) && $me['me_type'] === '아이템') ? 'selected' : ''; ?>>아이템획득</option>
                <option value="화폐" <?php echo (isset($me['me_type']) && $me['me_type'] === '화폐') ? 'selected' : ''; ?>>화폐변동</option>
                <option value="이동" <?php echo (isset($me['me_type']) && $me['me_type'] === '이동') ? 'selected' : ''; ?>>지역이동</option>
                <option value="몬스터" <?php echo (isset($me['me_type']) && $me['me_type'] === '몬스터') ? 'selected' : ''; ?>>몬스터공격</option>
            </select>
        </td>
    </tr>
    <tr>
        <th>이벤트 이미지</th>
        <td>
            <?php if (!empty($me['me_img'])) { ?>
            <div style="margin-bottom:6px;"><img src="<?php echo htmlspecialchars($me['me_img'], ENT_QUOTES); ?>" style="max-width:80px;" alt=""></div>
            <?php } ?>
            <p>파일 업로드: <input type="file" name="me_img_file" accept="image/*"></p>
            <p>또는 URL: <input type="text" name="me_img" value="<?php echo get_text(isset($me['me_img']) ? $me['me_img'] : ''); ?>" class="frm_input" style="width:70%;"></p>
        </td>
    </tr>
    <tr>
        <th>이벤트텍스트</th>
        <td><textarea name="me_content" class="frm_input" style="width:100%;height:120px;"><?php echo get_text(isset($me['me_content']) ? $me['me_content'] : ''); ?></textarea></td>
    </tr>
    <tr>
        <th>획득 구간</th>
        <td>
            <p class="frm_info">100면체 기준 구간 (0~100). 겹치면 랜덤 1건.</p>
            <input type="number" name="me_per_s" value="<?php echo (int)(isset($me['me_per_s']) ? $me['me_per_s'] : 0); ?>" class="frm_input" style="width:80px;"> ~
            <input type="number" name="me_per_e" value="<?php echo (int)(isset($me['me_per_e']) ? $me['me_per_e'] : 0); ?>" class="frm_input" style="width:80px;">
        </td>
    </tr>
    <tr>
        <th>획득가능갯수</th>
        <td>
            <input type="number" name="me_replay_cnt" value="<?php echo (int)(isset($me['me_replay_cnt']) ? $me['me_replay_cnt'] : 0); ?>" class="frm_input" style="width:100px;">
            <span class="frm_info">0이면 무제한</span>
        </td>
    </tr>
    <tr>
        <th>현재획득갯수</th>
        <td><input type="number" name="me_now_cnt" value="<?php echo (int)(isset($me['me_now_cnt']) ? $me['me_now_cnt'] : 0); ?>" class="frm_input" style="width:100px;"></td>
    </tr>
    </tbody>
    </table>
    </div>
</section>

<section>
    <h2 class="h2_frm">이벤트 효과</h2>
    <div class="tbl_frm01 tbl_wrap">
    <table>
    <colgroup><col style="width:140px"><col></colgroup>
    <tbody>
    <tr>
        <th>획득 아이템</th>
        <td>
            <input type="hidden" name="me_get_item" id="me_get_item" value="<?php echo (int)(isset($me['me_get_item']) ? $me['me_get_item'] : 0); ?>">
            <input type="text" name="it_name" id="it_name" value="<?php echo get_text($it_name); ?>" class="frm_input" style="width:50%;"
                <?php if (function_exists('get_ajax_item') || true) { ?>
                placeholder="아이템명 (it_id 수동 입력도 가능)"
                <?php } ?>>
            <p class="frm_info">아이템 ID를 직접 넣으려면 숨김 필드 me_get_item 값을 관리하거나, 아래 ID를 입력하세요.</p>
            <label>아이템 ID
                <input type="number" name="me_get_item_direct" value="<?php echo (int)(isset($me['me_get_item']) ? $me['me_get_item'] : 0); ?>" class="frm_input" style="width:120px;"
                    onchange="document.getElementById('me_get_item').value=this.value;">
            </label>
        </td>
    </tr>
    <tr>
        <th>화폐 변동</th>
        <td><input type="number" name="me_get_money" value="<?php echo (int)(isset($me['me_get_money']) ? $me['me_get_money'] : 0); ?>" class="frm_input" style="width:120px;"></td>
    </tr>
    <tr>
        <th>위치이동</th>
        <td>
            <select name="me_move_map" class="frm_input">
                <option value="0">-</option>
                <?php foreach ($maps as $m) { ?>
                <option value="<?php echo (int)$m['id']; ?>" <?php echo get_selected($m['id'], isset($me['me_move_map']) ? $me['me_move_map'] : 0); ?>><?php echo htmlspecialchars($m['name'], ENT_QUOTES); ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>
    <tr>
        <th>몬스터 HP</th>
        <td><input type="number" name="me_mon_hp" value="<?php echo (int)(isset($me['me_mon_hp']) ? $me['me_mon_hp'] : 0); ?>" class="frm_input" style="width:120px;"></td>
    </tr>
    <tr>
        <th>몬스터 공격력</th>
        <td><input type="number" name="me_mon_attack" value="<?php echo (int)(isset($me['me_mon_attack']) ? $me['me_mon_attack'] : 0); ?>" class="frm_input" style="width:120px;"></td>
    </tr>
    </tbody>
    </table>
    </div>
</section>

<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="확인" class="btn_submit" accesskey="s">
    <a href="<?php echo $list_url; ?>">목록</a>
</div>
</form>
