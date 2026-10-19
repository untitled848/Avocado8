<?php
/**
 * templete/txt.bgm.php — 메인 페이지 BGM 컨트롤 UI
 * (재생/정지/볼륨 슬라이더, postMessage 로 bgm.php iframe 제어)
 */

// 활성 프리셋의 BGM 또는 cf_bgm 이 있을 때만 노출
$_has_bgm = !empty($config['cf_bgm']);
if (function_exists('get_preset')) {
    $_pr = get_preset();
    if (!empty($_pr['pr_bgm_id'])) $_has_bgm = true;
}
$_init_vol = 50;
if (!empty($_pr) && isset($_pr['pr_bgm_volume'])) $_init_vol = (int)$_pr['pr_bgm_volume'];
if ($_init_vol < 0 || $_init_vol > 100) $_init_vol = 50;

if ($_has_bgm) :
?>
<nav class="bgm-player">
    <div class="bar-equalizer">
        <?php $equal_count = 30; while ($equal_count > 0) { echo "<i></i>"; $equal_count--; } ?>
    </div>
    <ul>
        <li style="min-width:27px;">
            <a href="<?php echo G5_URL; ?>/bgm.php?action=play" target="bgm_frame" class="play" onclick="return fn_control_bgm('play')">재생</a>
        </li>
        <li style="min-width:27px;">
            <a href="<?php echo G5_URL; ?>/bgm.php" target="bgm_frame" class="stop" onclick="return fn_control_bgm('stop')">정지</a>
        </li>
        <li class="bgm-vol">
            <label title="볼륨" style="display:flex;align-items:center;gap:4px;font-size:11px;">
                <span class="bgm-vol-icon" aria-hidden="true">♪</span>
                <input type="range" min="0" max="100" step="1" value="<?php echo $_init_vol; ?>" class="bgm-vol-slider" oninput="fn_bgm_volume(this.value)" style="vertical-align:middle;">
            </label>
        </li>
    </ul>
</nav>

<script>
var bgm_effect = null;
var set_equalizer = function () {
    $('.bar-equalizer i').each(function(i) {
        var height = Math.random() * 20 + 5;
        $(this).css('height', height);
    });
};
function fn_control_bgm(state) {
    if (state === 'play') {
        $('.bar-equalizer').removeClass('stop');
        if (bgm_effect) clearInterval(bgm_effect);
        bgm_effect = setInterval(set_equalizer, 300);
    } else {
        $('.bar-equalizer').addClass('stop');
        clearInterval(bgm_effect);
        $('.bar-equalizer i').css('height', '2px');
    }
    if ($('html').hasClass('single')) return false;
    return true;
}
function fn_bgm_volume(v) {
    try {
        var fr = window.parent.document.getElementsByName('bgm_frame')[0];
        if (fr && fr.contentWindow) {
            fr.contentWindow.postMessage({ bgm:'volume', value: parseInt(v,10) }, '*');
        }
    } catch (_) {}
    try { localStorage.setItem('bgm_vol', String(parseInt(v,10))); } catch (_) {}
}
$(function(){
    // 저장된 볼륨 복원
    try {
        var v = localStorage.getItem('bgm_vol');
        if (v !== null) {
            var n = parseInt(v, 10);
            if (!isNaN(n)) $('.bgm-vol-slider').val(n);
        }
    } catch (_) {}
    bgm_effect = setInterval(set_equalizer, 300);
});
// iframe 이 ready 알리면 초기 볼륨 재전송 (자동재생 시 마지막 사용자 값 적용)
window.addEventListener('message', function(ev){
    var d = ev.data;
    if (d && d.bgm === 'event' && d.type === 'ready') {
        var cur = parseInt($('.bgm-vol-slider').val(), 10);
        if (!isNaN(cur)) fn_bgm_volume(cur);
    }
});
</script>
<?php endif; ?>
