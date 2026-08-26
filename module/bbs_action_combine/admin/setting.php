<?php
if (!defined('_GNUBOARD_')) exit;

$sub_menu = "002200";

global $g5;
$st_table = $g5['reset_setting_table'];

// 현재 reset_setting('character_combine') 가 없으면 install 기본값으로 보장
$auto_initialized = false;
if (function_exists('reset_setting_get') && !reset_setting_get('character_combine')
    && function_exists('reset_setting_register')) {
    reset_setting_register('character_combine', array(
        'label'       => '아이템 조합 횟수',
        'period'      => 'daily',
        'default'     => 0,
        'plus'        => 0,
        'min'         => 0,
        'max'         => 5,
        'type'        => 'character',
        'setting_url' => G5_MDL_ADMIN_URL.'/module.php?m=bbs_action_combine&p=setting',
    ));
    $auto_initialized = true;
}

// reset_manager 가 비어 있으면 (= 기존 캐릭터 일괄 등록이 누락된 상태이면) 지금 일괄 채워둔다.
// install.php 가 모듈 활성화 시점에 한번 채우지만, 활성화 이후에 만들어진 캐릭터 또는
// 활성화 당시 reset_setting 등록이 실패한 케이스를 보강한다.
if (function_exists('reset_setting_get') && !empty($g5['character_table']) && !empty($g5['reset_manager_table'])) {
    $_set = reset_setting_get('character_combine');
    if (!empty($_set)) {
        $_cnt_row = sql_fetch("select count(*) as cnt from `{$g5['reset_manager_table']}` where rs_id=".(int)$_set['rs_id'], false);
        $_have    = $_cnt_row ? (int)$_cnt_row['cnt'] : 0;
        $_chr_row = sql_fetch("select count(*) as cnt from `{$g5['character_table']}`", false);
        $_chars   = $_chr_row ? (int)$_chr_row['cnt'] : 0;
        if ($_have < $_chars) {
            $_mode = mdl_setting_get('bbs_action_combine', 'combine_mode', 'minus');
            $_init = ($_mode === 'plus') ? (int)$_set['rs_plus'] : 0;
            sql_query("INSERT IGNORE INTO `{$g5['reset_manager_table']}` (rs_id, rm_subkey, rm_value)
                       SELECT ".(int)$_set['rs_id'].", ch_id, {$_init} FROM `{$g5['character_table']}`", false);
            $auto_initialized = true;
        }
    }
}

// 저장
$saved = false;
$mode_was_changed = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_mode = mdl_setting_get('bbs_action_combine', 'combine_mode', 'minus');
    $mode   = (isset($_POST['combine_mode']) && $_POST['combine_mode'] === 'plus') ? 'plus' : 'minus';

    $unit  = isset($_POST['rs_period_unit']) ? trim($_POST['rs_period_unit']) : 'daily';
    $hours = isset($_POST['rs_period_hours']) ? (int)$_POST['rs_period_hours'] : 1;
    if ($hours < 1) $hours = 1;
    if (!in_array($unit, array('hour','daily','weekly','monthly'), true)) $unit = 'daily';
    if ($unit === 'hour') {
        $period = ($hours <= 1) ? 'hourly' : ($hours.'h');
    } else {
        $period = $unit;
    }

    $rs_max  = max(0, (int)(isset($_POST['rs_max'])  ? $_POST['rs_max']  : 0));
    $rs_plus = max(0, (int)(isset($_POST['rs_plus']) ? $_POST['rs_plus'] : 0));

    mdl_setting_set('bbs_action_combine', 'combine_mode', $mode);

    $name_e   = sql_real_escape_string('character_combine');
    $period_e = sql_real_escape_string($period);
    if ($mode === 'plus') {
        sql_query("update `{$st_table}` set
            rs_period   = '{$period_e}',
            rs_default  = 0,
            rs_plus     = {$rs_plus},
            rs_max      = {$rs_max}
            where rs_name = '{$name_e}'");
    } else {
        sql_query("update `{$st_table}` set
            rs_period   = '{$period_e}',
            rs_default  = 0,
            rs_plus     = 0,
            rs_max      = {$rs_max}
            where rs_name = '{$name_e}'");
    }

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

$set    = sql_fetch("select * from `{$st_table}` where rs_name = '".sql_real_escape_string('character_combine')."'", false);
$mode   = mdl_setting_get('bbs_action_combine', 'combine_mode', 'minus');
$period = !empty($set['rs_period']) ? $set['rs_period'] : 'daily';
$rs_max = isset($set['rs_max'])     ? (int)$set['rs_max']  : 5;
$rs_plus= isset($set['rs_plus'])    ? (int)$set['rs_plus'] : 0;

if (preg_match('/^(\d+)h$/i', $period, $m)) {
    $cur_unit  = 'hour'; $cur_hours = max(1, (int)$m[1]);
} elseif ($period === 'hourly') {
    $cur_unit  = 'hour'; $cur_hours = 1;
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
<?php elseif ($auto_initialized): ?>
<div class="local_desc01 local_desc" style="background:#fff8e1;border-color:#ffd54f;">
    <strong>안내:</strong> 조합 카운터(reset_setting / reset_manager) 가 자동으로 등록·보강되었습니다.<br>
    이 페이지를 한 번 열기 전까지는 일부 캐릭터에 조합 카운터 row 가 없어 조합이 동작하지 않을 수 있습니다.
    <strong>모듈 활성화 직후에는 반드시 이 페이지를 1회 방문</strong>하거나 아래 [저장] 을 눌러 카운터를 초기화하세요.
</div>
<?php else: ?>
<div class="local_desc01 local_desc">
    <strong>안내:</strong> 새 캐릭터가 추가되면 자동으로 카운터 row 가 생성되지만, 모듈을 처음 활성화한 직후에는
    <strong>이 페이지에서 [저장] 을 한 번 눌러야</strong> 모든 기존 캐릭터에 조합 카운터가 등록됩니다.
</div>
<?php endif; ?>

<form method="post" action="" id="bac-setting-form" data-init-mode="<?php echo htmlspecialchars($mode, ENT_QUOTES); ?>">
<table class="tbl_frm01 tbl_wrap">
<tbody>
<tr>
    <th scope="row">조합 방식</th>
    <td>
        <label><input type="radio" name="combine_mode" value="minus" <?php echo $mode==='minus'?'checked':''; ?>> 차감식 (기존)</label>
        &nbsp;&nbsp;
        <label><input type="radio" name="combine_mode" value="plus" <?php echo $mode==='plus'?'checked':''; ?>> 누적식 (행동력)</label>
        <p class="frm_info" style="margin-top:6px;">
            <strong>차감식</strong>: 분기마다 카운터가 0 으로 초기화되며, 조합할 때마다 1 씩 증가합니다. 분기당 최대 횟수에 도달하면 더 이상 조합할 수 없습니다.<br>
            <strong>누적식</strong>: 분기마다 카운터가 정해진 양만큼 증가(누적)되며, 조합할 때마다 1 씩 차감됩니다. 행동력 시스템처럼 사용할 수 있습니다.<br>
            <strong style="color:#c33;">※ 모드를 바꾸면 현재 모든 캐릭터의 카운터(rm_value)가 새 모드의 초기값으로 재설정됩니다.</strong>
        </p>
    </td>
</tr>

<tr>
    <th scope="row"><label for="rs_period_unit">조합 분기</label></th>
    <td>
        <select name="rs_period_unit" id="rs_period_unit" class="frm_input">
            <?php foreach ($unit_options as $k => $v): ?>
            <option value="<?php echo $k; ?>" <?php echo $cur_unit===$k?'selected':''; ?>><?php echo $v; ?></option>
            <?php endforeach; ?>
        </select>
        <span id="bac-hours-wrap" style="<?php echo $cur_unit==='hour'?'':'display:none;'; ?>">
            &nbsp; N =
            <input type="number" name="rs_period_hours" id="rs_period_hours" value="<?php echo (int)$cur_hours; ?>" min="1" class="frm_input" style="width:90px;">
            <span class="frm_info" style="display:inline;">시간</span>
        </span>
        <p class="frm_info" style="margin-top:6px;">카운터가 자동으로 초기화 / 누적되는 주기입니다.</p>
    </td>
</tr>

<tr class="bac-row bac-mode-minus" <?php echo $mode==='plus'?'style="display:none;"':''; ?>>
    <th scope="row"><label for="rs_max_minus">조합 가능 횟수 (분기별)</label></th>
    <td>
        <input type="number" name="rs_max" id="rs_max_minus" value="<?php echo (int)$rs_max; ?>" min="0" class="frm_input">
        <span class="frm_info">한 분기 동안 캐릭터 1명이 조합할 수 있는 최대 횟수입니다. 0 이면 무제한.</span>
    </td>
</tr>

<tr class="bac-row bac-mode-plus" <?php echo $mode==='minus'?'style="display:none;"':''; ?>>
    <th scope="row"><label for="rs_plus_input">분기별 누적 횟수</label></th>
    <td>
        <input type="number" name="rs_plus" id="rs_plus_input" value="<?php echo (int)$rs_plus; ?>" min="0" class="frm_input">
        <span class="frm_info">분기마다 행동력에 더해지는 양입니다.</span>
    </td>
</tr>
<tr class="bac-row bac-mode-plus" <?php echo $mode==='minus'?'style="display:none;"':''; ?>>
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
    var radios = document.getElementsByName('combine_mode');
    var form   = document.getElementById('bac-setting-form');
    var initMode = form ? form.getAttribute('data-init-mode') : 'minus';

    function sync(){
        var mode = 'minus';
        for (var i=0;i<radios.length;i++) if (radios[i].checked) mode = radios[i].value;
        var minusRows = document.querySelectorAll('.bac-mode-minus');
        var plusRows  = document.querySelectorAll('.bac-mode-plus');
        for (var i=0;i<minusRows.length;i++) minusRows[i].style.display = (mode==='minus' ? '' : 'none');
        for (var i=0;i<plusRows.length;i++)  plusRows[i].style.display  = (mode==='plus'  ? '' : 'none');

        var minusMax = document.getElementById('rs_max_minus');
        var plusMax  = document.getElementById('rs_max_plus');
        if (minusMax) minusMax.disabled = (mode!=='minus');
        if (plusMax)  plusMax.disabled  = (mode!=='plus');
        var plusInp  = document.getElementById('rs_plus_input');
        if (plusInp)  plusInp.disabled  = (mode!=='plus');
    }
    for (var i=0;i<radios.length;i++) radios[i].addEventListener('change', sync);
    sync();

    var unitSel = document.getElementById('rs_period_unit');
    var hoursWrap = document.getElementById('bac-hours-wrap');
    function syncUnit(){
        if (!unitSel || !hoursWrap) return;
        hoursWrap.style.display = (unitSel.value === 'hour') ? '' : 'none';
    }
    if (unitSel) unitSel.addEventListener('change', syncUnit);
    syncUnit();

    if (form) {
        form.addEventListener('submit', function(e){
            var cur = 'minus';
            for (var i=0;i<radios.length;i++) if (radios[i].checked) cur = radios[i].value;
            if (cur !== initMode) {
                if (!confirm('조합 방식을 ' + (initMode==='minus'?'차감식':'누적식') + ' → ' + (cur==='minus'?'차감식':'누적식') + ' 로 변경합니다.\n\n현재 모든 캐릭터의 조합 카운터(rm_value)가 새 모드의 초기값으로 재설정됩니다. 진행할까요?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
})();
</script>
