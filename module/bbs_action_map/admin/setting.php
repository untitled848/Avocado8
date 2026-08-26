<?php
if (!defined('_GNUBOARD_')) exit;

$sub_menu = function_exists('mdl_admin_menu_code') ? mdl_admin_menu_code('bbs_action_map', '100') : '003100';

global $g5;
$st_table = $g5['reset_setting_table'];

// 현재 reset_setting('character_map_explore') 가 없으면 install 기본값으로 보장
if (function_exists('reset_setting_get') && !reset_setting_get('character_map_explore')
    && function_exists('reset_setting_register')) {
    reset_setting_register('character_map_explore', array(
        'label'       => '지역 탐색 횟수',
        'period'      => 'daily',
        'default'     => 0,
        'plus'        => 0,
        'min'         => 0,
        'max'         => 5,
        'type'        => 'character',
        'setting_url' => G5_MDL_ADMIN_URL.'/module.php?m=bbs_action_map&p=setting',
    ));
}

// 저장
$saved = false;
$mode_was_changed = false;
$map_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_mode = mdl_setting_get('bbs_action_map', 'explore_mode', 'minus');
    $mode   = (isset($_POST['explore_mode']) && $_POST['explore_mode'] === 'plus') ? 'plus' : 'minus';

    // 분기 단위(시간/일/주/월) + 시간 모드 N 입력
    $unit  = isset($_POST['rs_period_unit']) ? trim($_POST['rs_period_unit']) : 'daily';
    $hours = isset($_POST['rs_period_hours']) ? (int)$_POST['rs_period_hours'] : 1;
    if ($hours < 1) $hours = 1;
    if (!in_array($unit, array('hour','daily','weekly','monthly'), true)) {
        $unit = 'daily';
    }
    if ($unit === 'hour') {
        $period = ($hours <= 1) ? 'hourly' : ($hours.'h');
    } else {
        $period = $unit;
    }

    $rs_max = max(0, (int)(isset($_POST['rs_max']) ? $_POST['rs_max'] : 0));
    $rs_plus= max(0, (int)(isset($_POST['rs_plus']) ? $_POST['rs_plus'] : 0));

    mdl_setting_set('bbs_action_map', 'explore_mode', $mode);

    // reset_setting 직접 갱신
    $name_e = sql_real_escape_string('character_map_explore');
    $period_e = sql_real_escape_string($period);
    if ($mode === 'plus') {
        // 누적식: rs_default=0, rs_plus=입력, rs_max=입력
        sql_query("update `{$st_table}` set
            rs_period   = '{$period_e}',
            rs_default  = 0,
            rs_plus     = {$rs_plus},
            rs_max      = {$rs_max}
            where rs_name = '{$name_e}'");
    } else {
        // 차감식: rs_default=0, rs_plus=0, rs_max=입력
        sql_query("update `{$st_table}` set
            rs_period   = '{$period_e}',
            rs_default  = 0,
            rs_plus     = 0,
            rs_max      = {$rs_max}
            where rs_name = '{$name_e}'");
    }

    // 모드가 바뀌었다면 모든 reset_manager.rm_value 를 새 모드의 초기값으로 재설정
    if ($mode !== $old_mode) {
        $mode_was_changed = true;
        $set_now = sql_fetch("select rs_id, rs_plus from `{$st_table}` where rs_name = '{$name_e}' limit 1", false);
        if (!empty($set_now)) {
            $init_v = ($mode === 'plus') ? (int)$set_now['rs_plus'] : 0;
            sql_query("update `{$g5['reset_manager_table']}`
                       set rm_value = {$init_v}
                       where rs_id = ".(int)$set_now['rs_id'], false);
        }
    }

    // 지역 정보 패널
    $info_panel = (isset($_POST['info_panel']) && $_POST['info_panel'] === '0') ? '0' : '1';
    mdl_setting_set('bbs_action_map', 'info_panel', $info_panel);

    // 맵 이미지 크기
    $mi_w = max(0, (int)(isset($_POST['map_image_width'])  ? $_POST['map_image_width']  : 0));
    $mi_h = max(0, (int)(isset($_POST['map_image_height']) ? $_POST['map_image_height'] : 0));
    mdl_setting_set('bbs_action_map', 'map_image_width',  (string)$mi_w);
    mdl_setting_set('bbs_action_map', 'map_image_height', (string)$mi_h);

    // 맵 이미지 — 파일 업로드 / URL 입력 / 삭제 처리
    //   업로드 파일은 module/bbs_action_map/data 에 저장하고, 저장값(map_image)에는
    //   웹 접근 URL 을 기록한다. URL 입력 시에는 입력값을 그대로 기록한다.
    $cur_map_image = mdl_setting_get('bbs_action_map', 'map_image', '');
    $map_data_dir  = G5_PATH . '/module/bbs_action_map/data';
    $map_data_url  = G5_URL  . '/module/bbs_action_map/data';

    // 로컬(업로드) 파일이면 실제 파일을 삭제
    $del_local_map = function ($val) use ($map_data_dir) {
        if ($val !== '' && strpos($val, '/module/bbs_action_map/data/') !== false) {
            $fp = $map_data_dir . '/' . basename($val);
            if (is_file($fp)) @unlink($fp);
        }
    };

    if (!empty($_POST['map_image_del'])) {
        // 등록된 맵 이미지 삭제
        $del_local_map($cur_map_image);
        mdl_setting_set('bbs_action_map', 'map_image', '');
    } else if (isset($_FILES['map_image_file'])
               && $_FILES['map_image_file']['error'] === UPLOAD_ERR_OK
               && $_FILES['map_image_file']['tmp_name']) {
        // 파일 업로드
        $f = $_FILES['map_image_file'];
        $allowed_exts  = array('png','jpg','jpeg','gif','webp');
        $allowed_mimes = array('image/png','image/jpeg','image/gif','image/webp');
        $max_size = 5 * 1024 * 1024; // 5MB
        $oname = basename($f['name']);
        $ext   = strtolower(pathinfo($oname, PATHINFO_EXTENSION));
        $ok    = true;
        if ($f['size'] > $max_size) { $map_msg = '맵 이미지 파일이 너무 큽니다 (최대 5MB).'; $ok = false; }
        if ($ok && !in_array($ext, $allowed_exts, true)) {
            $map_msg = '허용된 이미지 확장자: '.implode('/', $allowed_exts); $ok = false;
        }
        if ($ok && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = $finfo ? finfo_file($finfo, $f['tmp_name']) : '';
            if ($finfo) finfo_close($finfo);
            if (!in_array($mime, $allowed_mimes, true)) {
                $map_msg = '이미지 MIME 타입이 올바르지 않습니다: '.$mime; $ok = false;
            }
        }
        if ($ok) {
            if (!is_dir($map_data_dir)) @mkdir($map_data_dir, 0755, true);
            $fname = 'map_'.date('YmdHis').'.'.$ext;
            $dest  = $map_data_dir.'/'.$fname;
            if (move_uploaded_file($f['tmp_name'], $dest)) {
                @chmod($dest, 0644);
                $del_local_map($cur_map_image); // 기존 로컬 파일 정리
                mdl_setting_set('bbs_action_map', 'map_image', $map_data_url.'/'.$fname);
                // 가로/세로 미입력 시 자동 추출
                if (!$mi_w || !$mi_h) {
                    $sz = @getimagesize($dest);
                    if ($sz) {
                        if (!$mi_w) { $mi_w = (int)$sz[0]; mdl_setting_set('bbs_action_map','map_image_width',(string)$mi_w); }
                        if (!$mi_h) { $mi_h = (int)$sz[1]; mdl_setting_set('bbs_action_map','map_image_height',(string)$mi_h); }
                    }
                }
            } else {
                $map_msg = '맵 이미지 저장에 실패했습니다.';
            }
        }
    } else if (isset($_POST['map_image_url']) && trim($_POST['map_image_url']) !== '') {
        // URL 입력 (외부 이미지)
        $url = trim($_POST['map_image_url']);
        if (preg_match('#^https?://#i', $url) || strpos($url, '/') === 0) {
            $del_local_map($cur_map_image); // 외부 URL 로 교체 시 기존 로컬 파일 정리
            mdl_setting_set('bbs_action_map', 'map_image', $url);
        } else {
            $map_msg = '맵 이미지 URL 형식이 올바르지 않습니다. (http:// 또는 / 로 시작)';
        }
    }

    $saved = true;
}

$set    = sql_fetch("select * from `{$st_table}` where rs_name = '".sql_real_escape_string('character_map_explore')."'", false);
$mode   = mdl_setting_get('bbs_action_map', 'explore_mode', 'minus');
$period = !empty($set['rs_period']) ? $set['rs_period'] : 'daily';
$rs_max = isset($set['rs_max'])     ? (int)$set['rs_max']  : 5;
$rs_plus= isset($set['rs_plus'])    ? (int)$set['rs_plus'] : 0;

$info_panel   = mdl_setting_get('bbs_action_map', 'info_panel', 1);
$map_image    = mdl_setting_get('bbs_action_map', 'map_image', '');
$map_image_width  = mdl_setting_get('bbs_action_map', 'map_image_width', '');
$map_image_height = mdl_setting_get('bbs_action_map', 'map_image_height', '');

// 현재 period 를 unit + hours 로 분해
if (preg_match('/^(\d+)h$/i', $period, $m)) {
    $cur_unit  = 'hour';
    $cur_hours = max(1, (int)$m[1]);
} elseif ($period === 'hourly') {
    $cur_unit  = 'hour';
    $cur_hours = 1;
} else {
    $cur_unit  = in_array($period, array('daily','weekly','monthly'), true) ? $period : 'daily';
    $cur_hours = 1;
}

$unit_options = array(
    'hour'    => 'N 시간 간격',
    'daily'   => '매일 자정',
    'weekly'  => '매주 월요일 0시',
    'monthly' => '매월 1일 0시',
);
?>
<?php if ($saved): ?>
<div class="local_desc01 local_desc">
    저장되었습니다.<?php if ($mode_was_changed): ?> 모드 변경에 따라 모든 캐릭터의 카운터가 초기값으로 재설정되었습니다.<?php endif; ?>
</div>
<?php endif; ?>
<?php if ($map_msg !== ''): ?>
<div class="local_desc01 local_desc" style="color:#c33;">
    <?php echo htmlspecialchars($map_msg, ENT_QUOTES); ?>
</div>
<?php endif; ?>

<form method="post" action="" id="bas-setting-form" enctype="multipart/form-data" data-init-mode="<?php echo htmlspecialchars($mode, ENT_QUOTES); ?>">
<table class="tbl_frm01 tbl_wrap">
<tbody>
<tr>
    <th scope="row">탐색 방식</th>
    <td>
        <label><input type="radio" name="explore_mode" value="minus" <?php echo $mode==='minus'?'checked':''; ?>> 차감식 (기존)</label>
        &nbsp;&nbsp;
        <label><input type="radio" name="explore_mode" value="plus" <?php echo $mode==='plus'?'checked':''; ?>> 누적식 (행동력)</label>
        <p class="frm_info" style="margin-top:6px;">
            <strong>차감식</strong>: 분기마다 카운터가 0 으로 초기화되며, 탐색할 때마다 1 씩 증가합니다. 분기당 최대 횟수에 도달하면 더 이상 탐색할 수 없습니다.<br>
            <strong>누적식</strong>: 분기마다 카운터가 정해진 양만큼 증가(누적)되며, 탐색할 때마다 1 씩 차감됩니다. 행동력 시스템처럼 사용할 수 있습니다.<br>
            <strong style="color:#c33;">※ 모드를 바꾸면 현재 모든 캐릭터의 카운터(rm_value)가 새 모드의 초기값으로 재설정됩니다.</strong>
        </p>
    </td>
</tr>

<tr>
    <th scope="row"><label for="rs_period_unit">탐색 분기</label></th>
    <td>
        <select name="rs_period_unit" id="rs_period_unit" class="frm_input">
            <?php foreach ($unit_options as $k => $v): ?>
            <option value="<?php echo $k; ?>" <?php echo $cur_unit===$k?'selected':''; ?>><?php echo $v; ?></option>
            <?php endforeach; ?>
        </select>
        <span id="bas-hours-wrap" style="<?php echo $cur_unit==='hour'?'':'display:none;'; ?>">
            &nbsp; N =
            <input type="number" name="rs_period_hours" id="rs_period_hours" value="<?php echo (int)$cur_hours; ?>" min="1" class="frm_input" style="width:90px;">
            <span class="frm_info" style="display:inline;">시간 (예: 6, 36, 288 등)</span>
        </span>
        <p class="frm_info" style="margin-top:6px;">카운터가 자동으로 초기화 / 누적되는 주기입니다. 시간 모드는 24시간 이하면 자정 기준으로 정렬되며, 24보다 크면 설정 시점부터 N시간마다 실행됩니다.</p>
    </td>
</tr>

<!-- minus 모드 전용 -->
<tr class="bas-row bas-mode-minus" <?php echo $mode==='plus'?'style="display:none;"':''; ?>>
    <th scope="row"><label for="rs_max_minus">탐색 가능 횟수 (분기별)</label></th>
    <td>
        <input type="number" name="rs_max" id="rs_max_minus" value="<?php echo (int)$rs_max; ?>" min="0" class="frm_input">
        <span class="frm_info">한 분기 동안 캐릭터 1명이 탐색을 시도할 수 있는 최대 횟수입니다. 0 이면 비활성.</span>
    </td>
</tr>

<!-- plus 모드 전용 -->
<tr class="bas-row bas-mode-plus" <?php echo $mode==='minus'?'style="display:none;"':''; ?>>
    <th scope="row"><label for="rs_plus_input">분기별 누적 횟수</label></th>
    <td>
        <input type="number" name="rs_plus" id="rs_plus_input" value="<?php echo (int)$rs_plus; ?>" min="0" class="frm_input">
        <span class="frm_info">분기마다 행동력에 더해지는 양입니다. (예: 매일 +3 → 누적되어 최대치까지)</span>
    </td>
</tr>
<tr class="bas-row bas-mode-plus" <?php echo $mode==='minus'?'style="display:none;"':''; ?>>
    <th scope="row"><label for="rs_max_plus">최대 누적 횟수</label></th>
    <td>
        <input type="number" name="rs_max" id="rs_max_plus" value="<?php echo (int)$rs_max; ?>" min="0" class="frm_input">
        <span class="frm_info">행동력의 최대치입니다. 누적이 이 값을 넘지 않습니다.</span>
    </td>
</tr>
</tbody>
</table>


<table class="tbl_frm01 tbl_wrap">
<tbody>
<tr>
    <th scope="row">지역 정보 패널</th>
    <td>
        <label><input type="radio" name="info_panel" value="0" <?php echo $info_panel==='0'?'checked':''; ?>> 미사용</label>
        &nbsp;&nbsp;
        <label><input type="radio" name="info_panel" value="1" <?php echo $info_panel==='1'?'checked':''; ?>> 사용</label>
        <p class="frm_info" style="margin-top:6px;">
            액션 메뉴에서 맵 선택시 지역 정보가 보이게 합니다.
        </p>
    </td>
</tr>
<tr>
    <th scope="row">맵 이미지</th>
    <td>
        <p class="frm_info" style="margin-top:0;">
            파일을 등록하면 자비란 상단에 전체 맵을 출력합니다. 파일 업로드 또는 외부 이미지 URL 중 하나를 사용할 수 있습니다.
        </p>

        <?php if ($map_image): ?>
        <div style="margin:8px 0;">
            <img src="<?php echo htmlspecialchars($map_image, ENT_QUOTES); ?>" alt="현재 맵 이미지" style="max-width:320px;max-height:200px;border:1px solid #ddd;display:block;">
            <label style="display:inline-block;margin-top:6px;">
                <input type="checkbox" name="map_image_del" value="1"> 등록된 맵 이미지 삭제
            </label>
        </div>
        <?php endif; ?>

        <p style="margin:6px 0;">
            <label>파일 업로드 : <input type="file" name="map_image_file" accept="image/*" class="frm_input"></label>
        </p>
        <p style="margin:6px 0;">
            <label>또는 URL : <input type="text" name="map_image_url" value="" class="frm_input" style="width:60%;" placeholder="https://example.com/map.png"></label>
        </p>
        <p class="frm_info">파일 업로드와 URL 을 동시에 입력하면 파일 업로드가 우선합니다. 업로드 파일은 module/bbs_action_map/data 에 저장됩니다.</p>

        <p style="margin:6px 0;">
            가로 <input type="text" name="map_image_width" id="map_image_width" value="<?php echo (int)$map_image_width; ?>" min="0" class="frm_input">px
            세로 <input type="text" name="map_image_height" id="map_image_height" value="<?php echo (int)$map_image_height; ?>" min="0" class="frm_input">px
            <span class="frm_info" style="display:inline;">파일 업로드 시 비워두면 자동으로 채워집니다.</span>
        </p>
    </td>
</tr>

</tbody>
</table>

<div class="btn_confirm01 btn_confirm">
    <button type="submit" class="btn_submit">저장</button>
</div>
</form>

<script>
(function(){
    var radios = document.getElementsByName('explore_mode');
    var form   = document.getElementById('bas-setting-form');
    var initMode = form ? form.getAttribute('data-init-mode') : 'minus';

    function sync(){
        var mode = 'minus';
        for (var i=0;i<radios.length;i++) if (radios[i].checked) mode = radios[i].value;
        var minusRows = document.querySelectorAll('.bas-mode-minus');
        var plusRows  = document.querySelectorAll('.bas-mode-plus');
        for (var i=0;i<minusRows.length;i++) minusRows[i].style.display = (mode==='minus' ? '' : 'none');
        for (var i=0;i<plusRows.length;i++)  plusRows[i].style.display  = (mode==='plus'  ? '' : 'none');

        // 두 input(rs_max) 중 표시되는 것만 form 에 전송되도록 disable 토글
        var minusMax = document.getElementById('rs_max_minus');
        var plusMax  = document.getElementById('rs_max_plus');
        if (minusMax) minusMax.disabled = (mode!=='minus');
        if (plusMax)  plusMax.disabled  = (mode!=='plus');
        var plusInp  = document.getElementById('rs_plus_input');
        if (plusInp)  plusInp.disabled  = (mode!=='plus');
    }
    for (var i=0;i<radios.length;i++) radios[i].addEventListener('change', sync);
    sync();

    // 시간 모드 N 입력 토글
    var unitSel = document.getElementById('rs_period_unit');
    var hoursWrap = document.getElementById('bas-hours-wrap');
    function syncUnit(){
        if (!unitSel || !hoursWrap) return;
        hoursWrap.style.display = (unitSel.value === 'hour') ? '' : 'none';
    }
    if (unitSel) unitSel.addEventListener('change', syncUnit);
    syncUnit();

    // 모드 변경 시 경고 confirm
    if (form) {
        form.addEventListener('submit', function(e){
            var cur = 'minus';
            for (var i=0;i<radios.length;i++) if (radios[i].checked) cur = radios[i].value;
            if (cur !== initMode) {
                if (!confirm('탐색 방식을 ' + (initMode==='minus'?'차감식':'누적식') + ' → ' + (cur==='minus'?'차감식':'누적식') + ' 로 변경합니다.\n\n현재 모든 캐릭터의 탐색 카운터(rm_value)가 새 모드의 초기값으로 재설정됩니다. 진행할까요?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
})();
</script>

