<?php
/**
 * bgm.php — 테마 프리셋별 YouTube BGM 플레이어 (IFrame API 기반)
 *
 * 동작:
 *  - 활성 테마 프리셋의 pr_bgm_type / pr_bgm_id / pr_bgm_volume 을 사용
 *  - 비어 있으면 $config['cf_bgm'] (플레이리스트) 로 폴백
 *  - 부모창에서 postMessage 로 제어 가능: {bgm:'play'|'pause'|'stop'|'volume', value:N}
 *  - 페이지 단순 GET 으로 ?action=play 호출 시 즉시 자동재생 시작
 */

include_once('./_common.php');

$action = isset($action) ? $action : (isset($_GET['action']) ? $_GET['action'] : '');

// ── 활성 프리셋 BGM 결정 ──────────────────────────────────────
$bgm_type = 'playlist';
$bgm_id   = '';
$bgm_vol  = 50;

if (function_exists('get_preset')) {
    $pr = get_preset();
    if (!empty($pr['pr_bgm_id'])) {
        $bgm_type = !empty($pr['pr_bgm_type']) ? $pr['pr_bgm_type'] : 'playlist';
        $bgm_id   = $pr['pr_bgm_id'];
        $bgm_vol  = isset($pr['pr_bgm_volume']) ? (int)$pr['pr_bgm_volume'] : 50;
    }
}

// 폴백: cf_bgm
if ($bgm_id === '' && !empty($config['cf_bgm'])) {
    $bgm_type = 'playlist';
    $bgm_id   = $config['cf_bgm'];
}

// 안전 필터
$bgm_id = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$bgm_id);
if (!in_array($bgm_type, array('playlist','video'), true)) $bgm_type = 'playlist';
if ($bgm_vol < 0)   $bgm_vol = 0;
if ($bgm_vol > 100) $bgm_vol = 100;

if ($action !== 'play') {
    // play 가 아닌 경우는 빈 페이지 (호환)
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><body></body>';
    exit;
}

// 자동 재생 모드
$origin_url = G5_URL;
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>BGM Player</title>
<style>html,body{margin:0;padding:0;background:#000;overflow:hidden;}#ytplayer{width:100%;height:100vh;display:block;}</style>
</head>
<body>

<?php if ($bgm_id === ''): ?>
<!-- BGM 미설정 -->
<div style="color:#888;font:12px sans-serif;padding:8px;">BGM 미설정</div>
<?php else: ?>

<div id="ytplayer"></div>

<script>
// YouTube IFrame API 비동기 로드
(function(){
    var tag = document.createElement('script');
    tag.src = 'https://www.youtube.com/iframe_api';
    var first = document.getElementsByTagName('script')[0];
    first.parentNode.insertBefore(tag, first);
})();

var BGM_TYPE  = <?php echo json_encode($bgm_type); ?>;
var BGM_ID    = <?php echo json_encode($bgm_id); ?>;
var BGM_VOL   = <?php echo (int)$bgm_vol; ?>;
var ORIGIN    = <?php echo json_encode($origin_url); ?>;

var ytPlayer = null;
var ytReady  = false;

function onYouTubeIframeAPIReady() {
    var playerVars = {
        autoplay:    1,
        controls:    0,
        disablekb:   1,
        loop:        1,
        playsinline: 1,
        rel:         0,
        modestbranding: 1,
        origin:      ORIGIN
    };
    var opts = {
        height: '100%',
        width:  '100%',
        playerVars: playerVars,
        events: {
            'onReady':       onYtReady,
            'onStateChange': onYtStateChange,
            'onError':       onYtError
        }
    };

    if (BGM_TYPE === 'playlist') {
        playerVars.listType = 'playlist';
        playerVars.list     = BGM_ID;
        opts.playerVars     = playerVars;
        ytPlayer = new YT.Player('ytplayer', opts);
    } else {
        // 단일 영상 — playlist 파라미터에 자기자신을 넣어 loop 동작 보장
        playerVars.playlist = BGM_ID;
        opts.videoId        = BGM_ID;
        opts.playerVars     = playerVars;
        ytPlayer = new YT.Player('ytplayer', opts);
    }
}

function onYtReady(e) {
    ytReady = true;
    try { e.target.setVolume(BGM_VOL); } catch(_) {}
    try { e.target.playVideo(); } catch(_) {}
    notifyParent('ready');
}

function onYtStateChange(e) {
    // 단일 영상이 끝났을 때 다시 재생 (loop=1 + playlist=self 로도 안 되는 케이스 보호)
    if (e.data === YT.PlayerState.ENDED && BGM_TYPE === 'video') {
        try { ytPlayer.seekTo(0); ytPlayer.playVideo(); } catch(_) {}
    }
    notifyParent('state', e.data);
}

function onYtError(e) {
    notifyParent('error', e.data);
}

function notifyParent(type, data) {
    try {
        if (window.parent && window.parent !== window) {
            window.parent.postMessage({ bgm:'event', type:type, data:data }, '*');
        }
    } catch(_) {}
}

// 부모창 → iframe 메시지 수신
window.addEventListener('message', function(ev) {
    var d = ev.data;
    if (!d || typeof d !== 'object' || d.bgm === undefined) return;
    if (!ytPlayer || !ytReady) return;
    try {
        switch (d.bgm) {
            case 'play':   ytPlayer.playVideo();  break;
            case 'pause':  ytPlayer.pauseVideo(); break;
            case 'stop':   ytPlayer.stopVideo();  break;
            case 'volume':
                var v = parseInt(d.value, 10);
                if (!isNaN(v)) {
                    if (v < 0) v = 0; if (v > 100) v = 100;
                    ytPlayer.setVolume(v);
                }
                break;
            case 'mute':   ytPlayer.mute();   break;
            case 'unmute': ytPlayer.unMute(); break;
        }
    } catch(_) {}
});
</script>

<?php endif; ?>
</body>
</html>
