<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if (!$is_member) return;

// 활성 알림 종류가 하나도 없으면 폴링 자체 스킵
$_call_any_on = (int) sql_fetch("SELECT COUNT(*) AS cnt FROM `{$g5['call_type_map_table']}` WHERE ct_use = 1")['cnt'];
if ($_call_any_on <= 0) return;

$call_interval_ms = max(5000, intval($config['cf_call_interval'] ?? 30) * 1000);
$site_title = addslashes($config['cf_title'] ?? '');
$img_url    = G5_IMG_URL;
$clear_url  = G5_URL . '/ajax/call_clear.php';
$list_url   = G5_URL . '/mypage/call.php';
$call_sound_on = function_exists('is_call_sound_enabled') ? is_call_sound_enabled($member['mb_id']) : true;
$call_sound_url = function_exists('get_call_notify_sound_url') ? get_call_notify_sound_url() : (G5_URL.'/sound/call_notify.wav');
?>
<script>
(function () {
    var _callTimer;
    var _callBusy = false;
    var callAudio = null;
    var callAudioCtx = null;
    var callOsc = null;
    var callSoundOn = <?php echo $call_sound_on ? 'true' : 'false'; ?>;
    var callSoundUrl = <?php echo json_encode($call_sound_url); ?>;

    function startCallPolling() {
        if (_callTimer) {
            clearInterval(_callTimer);
        }
        _callTimer = setInterval(checkCall, <?php echo $call_interval_ms; ?>);
    }

    function stopCallSound() {
        if (callAudio) {
            try {
                callAudio.pause();
                callAudio.currentTime = 0;
            } catch (e) {}
            callAudio = null;
        }
        if (callOsc) {
            try { callOsc.stop(); } catch (e2) {}
            callOsc = null;
        }
        if (callAudioCtx) {
            try {
                if (typeof callAudioCtx.close === 'function') {
                    callAudioCtx.close();
                }
            } catch (e3) {}
            callAudioCtx = null;
        }
    }

    function playCallSound() {
        stopCallSound();
        if (!callSoundOn) return;
        try {
            callAudio = new Audio(callSoundUrl);
            callAudio.loop = false;
            callAudio.volume = 0.55;
            var p = callAudio.play();
            if (p && typeof p.catch === 'function') {
                p.catch(function () {
                    // 자동재생 차단 시 Web Audio 폴백
                    try {
                        callAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
                        callOsc = callAudioCtx.createOscillator();
                        var g = callAudioCtx.createGain();
                        callOsc.type = 'sine';
                        callOsc.frequency.setValueAtTime(880, callAudioCtx.currentTime);
                        callOsc.frequency.setValueAtTime(1100, callAudioCtx.currentTime + 0.12);
                        g.gain.setValueAtTime(0.0001, callAudioCtx.currentTime);
                        g.gain.exponentialRampToValueAtTime(0.2, callAudioCtx.currentTime + 0.02);
                        g.gain.exponentialRampToValueAtTime(0.0001, callAudioCtx.currentTime + 0.28);
                        callOsc.connect(g);
                        g.connect(callAudioCtx.destination);
                        callOsc.start();
                        callOsc.stop(callAudioCtx.currentTime + 0.3);
                    } catch (e2) {}
                });
            }
        } catch (e) {}
    }

    function checkCall() {
        if (_callBusy) return;
        $.getJSON('<?php echo G5_URL; ?>/ajax/load_call.php', function (data) {
            if (_callBusy) return;
            if (data && data.ok) {
                _callBusy = true;
                if (_callTimer) {
                    clearInterval(_callTimer);
                    _callTimer = null;
                }
                showCallNotify(data);
            }
        });
    }

    function showCallNotify(data) {
        var typeName = data.type_name || '알림';
        var sender   = data.sender   || '';
        var memo     = data.memo     || '';
        var link     = data.link     || '';

        $('#ui-call-notify').remove();
        playCallSound();

        var $box = $('<div class="ui-call-alram-box theme-box none-trans" id="ui-call-notify"></div>');
        $box.attr('data-call-type', data.type || '');
        $box.attr('data-bo-table', data.bo_table || '');
        var $pop = $('<div class="ui-alram-popup"><div class="alram-content"></div></div>');
        var $msg = $('<p></p>');

        $msg.append($('<strong>').text('[' + typeName + ']'));
        if (sender) $msg.append(' ').append($('<strong>').text(sender));
        if (memo)   $msg.append($('<br>')).append(document.createTextNode(memo));

        $pop.find('.alram-content').append($msg);

        if (link) {
            $pop.find('.alram-content').append(
                $('<a href="#" class="ui-btn">').text('바로가기').on('click', function (e) {
                    e.preventDefault(); callGo(link);
                })
            );
        }
        $pop.find('.alram-content').append(
            $('<a class="ui-btn">').attr('href', '<?php echo $list_url; ?>').text('알림 목록').on('click', function (e) {
                e.preventDefault(); callGo('<?php echo $list_url; ?>');
            })
        );
        $pop.find('.alram-content').append(
            $('<a href="#" class="ui-btn">').text('닫기').on('click', function (e) {
                e.preventDefault(); callClose();
            })
        );

        $box.append($pop);
        $('body').append($box);
        $box.fadeIn(300);

        // Web Notification
        if ('Notification' in window && Notification.permission === 'granted') {
            try {
                var n = new Notification('<?php echo $site_title; ?>', {
                    body: (sender ? sender + ': ' : '') + memo,
                    icon: '<?php echo $img_url; ?>/notify_icon.png'
                });
                if (link) {
                    n.onclick = function () { callGo(link); };
                }
            } catch (e) {}
        }
    }

    function dismissCallNotify() {
        stopCallSound();
        $('#ui-call-notify').remove();
        _callBusy = false;
    }

    window.callGo = function (url) {
        dismissCallNotify();
        $.post('<?php echo $clear_url; ?>').always(function () {
            location.href = url;
        });
    };

    window.callClose = function () {
        dismissCallNotify();
        $.post('<?php echo $clear_url; ?>').always(function () {
            startCallPolling();
        });
    };

    /**
     * 알람 팝업/소리를 즉시 중지한다.
     * @param {boolean} resumePolling true면 폴링을 다시 시작한다.
     */
    window.stopCallAlarm = function (resumePolling) {
        dismissCallNotify();
        if (resumePolling) {
            startCallPolling();
        }
    };

    window.setCallSoundEnabled = function (on) {
        callSoundOn = !!on;
        if (!callSoundOn) {
            stopCallSound();
        }
    };

    window.playCallNotifyPreview = function () {
        var prev = callSoundOn;
        callSoundOn = true;
        playCallSound();
        callSoundOn = prev;
    };

    $(function () { startCallPolling(); });
}());
</script>
