<?php
$sub_menu = "520010";
include_once('./_common.php');

$g5['title'] = '통합 정산';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$ti_order = function_exists('get_current_time') ? (int)get_current_time() : 1;
$sides   = function_exists('get_sides_for_time')   ? get_sides_for_time($ti_order)   : array();
$classes = function_exists('get_classes_for_time') ? get_classes_for_time($ti_order) : array();
if (!is_array($sides))   $sides   = array();
if (!is_array($classes)) $classes = array();

$presets = function_exists('payout_preset_list') ? payout_preset_list(false) : array();
?>
<div class="local_desc01 local_desc">
    <p>여러 캐릭터에게 한꺼번에 아이템 / 타이틀 / 화폐 / 경험치를 지급합니다.<br>
       <b>대상 선택</b> + <b>항목 선택</b> + <b>정산내용</b> 을 입력하고 [지급] 을 누르세요.</p>
</div>

<form name="fcalc" id="fcalc" action="./calc_form_update.php" method="post" onsubmit="return calc_submit(this);">
<?php check_admin_token(); ?>

<section class="cf-box">
    <h2 class="cf-h">1. 대상 캐릭터</h2>
    <div class="cf-row">
        <label><input type="radio" name="target_mode" value="all"    checked> 전체</label>
        <label><input type="radio" name="target_mode" value="side">   진영별</label>
        <label><input type="radio" name="target_mode" value="class">  분류별</label>
        <label><input type="radio" name="target_mode" value="direct"> 직접 지정</label>
        <label style="margin-left:20px;"><input type="checkbox" name="include_npc" value="1"> NPC 포함</label>
    </div>
    <div class="cf-row cf-side" style="display:none">
        <strong>진영:</strong>
        <?php foreach ($sides as $si) { ?>
            <label><input type="checkbox" name="side_ids[]" value="<?php echo (int)$si['si_id']; ?>"> <?php echo htmlspecialchars($si['si_name'], ENT_QUOTES, 'UTF-8'); ?></label>
        <?php } ?>
        <?php if (empty($sides)) echo '<em class="cf-mute">현재 시기에 등록된 진영이 없습니다.</em>'; ?>
    </div>
    <div class="cf-row cf-class" style="display:none">
        <strong>분류:</strong>
        <?php foreach ($classes as $cl) { ?>
            <label><input type="checkbox" name="class_ids[]" value="<?php echo (int)$cl['cl_id']; ?>"> <?php echo htmlspecialchars($cl['cl_name'], ENT_QUOTES, 'UTF-8'); ?></label>
        <?php } ?>
        <?php if (empty($classes)) echo '<em class="cf-mute">현재 시기에 등록된 분류가 없습니다.</em>'; ?>
    </div>
    <div class="cf-row cf-direct" style="display:none">
        <strong>캐릭터 검색:</strong>
        <span class="cf-ch-search">
            <input type="text" id="cf_ch_name" placeholder="캐릭터명" autocomplete="off">
            <ul id="cf_ch_suggest" class="cpe-suggest" style="display:none"></ul>
        </span>
        <ul id="cf_ch_list" class="cf-ch-list"></ul>
    </div>
</section>

<section class="cf-box">
    <h2 class="cf-h">2. 지급 항목</h2>
    <div class="cf-row">
        <label><input type="radio" name="item_mode" value="direct" checked> 직접 지정</label>
        <label><input type="radio" name="item_mode" value="preset"> 프리셋 사용</label>
    </div>
    <div class="cf-row cf-item-direct">
        <?php $payload_field='payload'; $payload_uid='cf'; include __DIR__.'/_partial/calc_payload_editor.inc.php'; ?>
    </div>
    <div class="cf-row cf-item-preset" style="display:none">
        <select name="preset_id">
            <option value="0">- 프리셋 선택 -</option>
            <?php foreach ($presets as $p) { ?>
                <option value="<?php echo (int)$p['cp_id']; ?>"><?php echo htmlspecialchars($p['cp_name'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php } ?>
        </select>
        <?php if (function_exists('mdl_do_action')) mdl_do_action('payout_form_extra_types', array('context'=>'calc_form')); ?>
    </div>
</section>

<section class="cf-box">
    <h2 class="cf-h">3. 정산 내용</h2>
    <div class="cf-row">
        <input type="text" name="memo" id="cf_memo" placeholder="예: 4월 운영 보상" maxlength="200" style="width:60%;">
    </div>
</section>

<div class="btn_confirm01 btn_confirm">
    <button type="submit" class="btn btn_01">지급</button>
</div>
</form>

<script>
(function(){
    function show(sel, on){ var e=document.querySelector(sel); if(e) e.style.display = on?'':'none'; }
    document.querySelectorAll('input[name="target_mode"]').forEach(function(r){
        r.addEventListener('change', function(){
            show('.cf-side',   this.value==='side'   && this.checked);
            show('.cf-class',  this.value==='class'  && this.checked);
            show('.cf-direct', this.value==='direct' && this.checked);
        });
    });
    document.querySelectorAll('input[name="item_mode"]').forEach(function(r){
        r.addEventListener('change', function(){
            show('.cf-item-direct', this.value==='direct' && this.checked);
            show('.cf-item-preset', this.value==='preset' && this.checked);
        });
    });

    // 캐릭터 자동완성
    var nameI = document.getElementById('cf_ch_name');
    var sugg  = document.getElementById('cf_ch_suggest');
    var list  = document.getElementById('cf_ch_list');
    var dt = null;
    if (nameI) nameI.addEventListener('input', function(){
        var kw = nameI.value.trim();
        if (kw.length < 1) { sugg.style.display='none'; return; }
        clearTimeout(dt);
        dt = setTimeout(function(){
            var fd = new FormData(); fd.append('keyword', kw);
            fd.append('list_obj','cf'); fd.append('input_obj','x'); fd.append('output_obj','y');
            fetch('<?php echo G5_URL; ?>/ajax/_search_character.php', {method:'POST', body:fd, credentials:'same-origin'})
                .then(function(r){return r.text();})
                .then(function(html){
                    sugg.innerHTML = html;
                    sugg.style.display='block';
                    sugg.querySelectorAll('a').forEach(function(a){
                        a.addEventListener('click', function(e){
                            e.preventDefault();
                            var oc = a.getAttribute('onclick') || '';
                            var m = oc.match(/select_item\(\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*\)/);
                            if (!m) return;
                            var nm = m[3], chId = parseInt(m[5],10) || 0;
                            if (chId <= 0) return;
                            // dup check
                            if (list.querySelector('input[value="'+chId+'"]')) { sugg.style.display='none'; return; }
                            var li = document.createElement('li');
                            li.innerHTML = '<span></span> <input type="hidden" name="ch_ids[]" value="'+chId+'"> <button type="button" class="cf-ch-del">×</button>';
                            li.querySelector('span').textContent = nm;
                            li.querySelector('.cf-ch-del').addEventListener('click', function(){ li.parentNode.removeChild(li); });
                            list.appendChild(li);
                            nameI.value=''; sugg.style.display='none';
                        });
                    });
                });
        }, 200);
    });
})();

function calc_submit(f){
    var mode = f.querySelector('input[name="target_mode"]:checked');
    if (!mode) { alert('대상 모드를 선택하세요.'); return false; }
    if (mode.value === 'direct' && !f.querySelector('#cf_ch_list input[name="ch_ids[]"]')) {
        alert('대상 캐릭터를 1명 이상 추가하세요.'); return false;
    }
    if (mode.value === 'side' && !f.querySelector('input[name="side_ids[]"]:checked')) {
        alert('진영을 선택하세요.'); return false;
    }
    if (mode.value === 'class' && !f.querySelector('input[name="class_ids[]"]:checked')) {
        alert('분류를 선택하세요.'); return false;
    }
    var imode = f.querySelector('input[name="item_mode"]:checked');
    if (imode && imode.value === 'preset') {
        if (!f.preset_id || (parseInt(f.preset_id.value,10)||0) <= 0) {
            alert('프리셋을 선택하세요.'); return false;
        }
    } else {
        if (!f.querySelector('.cf-item-direct .cpe-list .cpe-item')) {
            alert('지급 항목을 1개 이상 추가하세요.'); return false;
        }
    }
    if (!confirm('정산을 실행합니다. 진행하시겠습니까?')) return false;
    return true;
}
</script>

<?php include_once(G5_ADMIN_PATH.'/admin.tail.php'); ?>
