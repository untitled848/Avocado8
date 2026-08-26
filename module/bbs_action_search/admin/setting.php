<?php
if (!defined('_GNUBOARD_')) exit;

$sub_menu = "002100";

global $g5;
$st_table = $g5['reset_setting_table'];

// 현재 reset_setting('character_search') 가 없으면 install 기본값으로 보장
if (function_exists('reset_setting_get') && !reset_setting_get('character_search')
    && function_exists('reset_setting_register')) {
    reset_setting_register('character_search', array(
        'label'       => '캐릭터 탐색 카운터',
        'period'      => 'daily',
        'default'     => 0,
        'plus'        => 0,
        'min'         => 0,
        'max'         => 5,
        'type'        => 'character',
        'setting_url' => G5_MDL_ADMIN_URL.'/module.php?m=bbs_action_search&p=setting',
    ));
}

// 저장
$saved = false;
$mode_was_changed = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_mode = mdl_setting_get('bbs_action_search', 'search_mode', 'minus');
    $mode   = (isset($_POST['search_mode']) && $_POST['search_mode'] === 'plus') ? 'plus' : 'minus';

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

    mdl_setting_set('bbs_action_search', 'search_mode', $mode);

    // reset_setting 직접 갱신
    $name_e = sql_real_escape_string('character_search');
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

    $saved = true;
}

$set    = sql_fetch("select * from `{$st_table}` where rs_name = '".sql_real_escape_string('character_search')."'", false);
$mode   = mdl_setting_get('bbs_action_search', 'search_mode', 'minus');
$period = !empty($set['rs_period']) ? $set['rs_period'] : 'daily';
$rs_max = isset($set['rs_max'])     ? (int)$set['rs_max']  : 5;
$rs_plus= isset($set['rs_plus'])    ? (int)$set['rs_plus'] : 0;

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

<form method="post" action="" id="bas-setting-form" data-init-mode="<?php echo htmlspecialchars($mode, ENT_QUOTES); ?>">
<table class="tbl_frm01 tbl_wrap">
<tbody>
<tr>
    <th scope="row">탐색 방식</th>
    <td>
        <label><input type="radio" name="search_mode" value="minus" <?php echo $mode==='minus'?'checked':''; ?>> 차감식 (기존)</label>
        &nbsp;&nbsp;
        <label><input type="radio" name="search_mode" value="plus" <?php echo $mode==='plus'?'checked':''; ?>> 누적식 (행동력)</label>
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

<div class="btn_confirm01 btn_confirm">
    <button type="submit" class="btn_submit">저장</button>
</div>
</form>

<script>
(function(){
    var radios = document.getElementsByName('search_mode');
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

