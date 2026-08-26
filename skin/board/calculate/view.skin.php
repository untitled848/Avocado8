<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

/**
 * calculate 스킨 — view
 *
 * 관리자에게 [정산 지급] UI 노출:
 *   1) bo_1=1 + wr_link1 에 신청 JSON 이 있으면 자동 채움
 *   2) 직접/프리셋 선택해서 [확인] 누르면 ajax 로 지급
 *   3) 지급 대상은 글 작성자의 캐릭터 (write['ch_id'])
 */

if (!$is_admin) return;

$_view_ch_id = isset($view['ch_id']) ? (int)$view['ch_id'] : 0;
if ($_view_ch_id <= 0) return;

$_calc_request = array();
if (!empty($view['wr_link1'])) {
    $_t = json_decode($view['wr_link1'], true);
    if (is_array($_t) && !empty($_t['_calc_req'])) $_calc_request = $_t;
}
$_already_paid = !empty($_calc_request['paid']);

$_presets = function_exists('payout_preset_list') ? payout_preset_list(false) : array();
?>
<div class="calc-admin-payout" style="margin:14px 0; padding:12px; background:#fffbe5; border:1px solid #e8c97a; border-radius:5px;">
    <h4 style="margin:0 0 10px;">관리자 정산 지급
        <?php if (!empty($_calc_request)) {
            echo '<span style="font-size:12px; color:'.($_already_paid?'#080':'#a40').'; margin-left:10px;">'.($_already_paid?'(이미 지급됨)':'(작성자 신청 있음)').'</span>';
        } ?>
    </h4>

    <form name="fcalcpay" id="fcalcpay" onsubmit="return calc_payout_submit(this);">
        <input type="hidden" name="bo_table" value="<?php echo htmlspecialchars($bo_table, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="wr_id"    value="<?php echo (int)$view['wr_id']; ?>">

        <div style="margin:4px 0;">
            <label><input type="radio" name="mode" value="preset" <?php echo (!empty($_calc_request) ? 'checked' : ''); ?>> 프리셋</label>
            <label style="margin-left:14px;"><input type="radio" name="mode" value="direct" <?php echo (empty($_calc_request) ? 'checked' : ''); ?>> 직접 지정</label>
        </div>

        <div class="calc-pay-preset" style="margin:6px 0;">
            <select name="preset_id">
                <option value="0">- 프리셋 선택 -</option>
                <?php foreach ($_presets as $p) {
                    $sel = (!empty($_calc_request['preset_id']) && (int)$_calc_request['preset_id'] === (int)$p['cp_id']) ? 'selected' : '';
                ?>
                    <option value="<?php echo (int)$p['cp_id']; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($p['cp_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php } ?>
            </select>
            <span style="margin-left:14px;">
                <label><?php echo $config['cf_money']; ?>: <input type="number" name="extra_money" value="<?php echo isset($_calc_request['money'])?(int)$_calc_request['money']:0; ?>" style="width:100px;"></label>
                <label style="margin-left:10px;"><?php echo $config['cf_exp_name']; ?>: <input type="number" name="extra_exp" value="<?php echo isset($_calc_request['exp'])?(int)$_calc_request['exp']:0; ?>" style="width:100px;"></label>
            </span>
        </div>

        <div class="calc-pay-direct" style="margin:6px 0; display:none;">
            <?php $payload_field='payload'; $payload_uid='vw'; include G5_ADMIN_PATH.'/_partial/calc_payload_editor.inc.php'; ?>
        </div>

        <div style="margin:6px 0;">
            <label>정산내용: <input type="text" name="memo" value="<?php echo htmlspecialchars($view['wr_subject'], ENT_QUOTES, 'UTF-8'); ?>" style="width:60%;"></label>
        </div>

        <div style="margin:8px 0 0;">
            <button type="submit" class="btn btn_01" <?php echo $_already_paid ? 'disabled' : ''; ?>>확인 (지급 실행)</button>
            <span class="calc-pay-result" style="margin-left:10px; color:#080;"></span>
        </div>
    </form>
</div>
<script>
(function(){
    function show(sel, on){ var e=document.querySelector(sel); if(e) e.style.display = on?'':'none'; }
    var radios = document.querySelectorAll('#fcalcpay input[name="mode"]');
    function refresh(){
        var v = (document.querySelector('#fcalcpay input[name="mode"]:checked')||{}).value;
        show('.calc-pay-preset', v==='preset');
        show('.calc-pay-direct', v==='direct');
    }
    radios.forEach(function(r){ r.addEventListener('change', refresh); });
    refresh();
})();

function calc_payout_submit(f){
    if (!confirm('지급을 실행합니다. 계속하시겠습니까?')) return false;
    var fd = new FormData(f);
    fetch('<?php echo G5_HTTP_BBS_URL; ?>/calc_payout.ajax.php', {
        method:'POST', body: fd, credentials:'same-origin'
    }).then(function(r){ return r.json(); })
      .then(function(d){
        var msg = f.querySelector('.calc-pay-result');
        if (d && d.ok) {
            msg.style.color = '#080';
            msg.textContent = '지급 완료 (성공 ' + (d.success||0) + ' / 실패 ' + (d.failed||0) + ')';
            f.querySelector('button[type=submit]').disabled = true;
        } else {
            msg.style.color = '#a00';
            msg.textContent = (d && d.message) ? d.message : '지급 실패';
        }
    }).catch(function(e){
        var msg = f.querySelector('.calc-pay-result');
        msg.style.color = '#a00';
        msg.textContent = '오류: ' + e;
    });
    return false;
}
</script>
