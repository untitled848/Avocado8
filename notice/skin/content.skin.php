<?php
// === 문서 디자인 프리셋 CSS + 꾸번 이미지 (content 동일) ===
$content_pd_id      = function_exists('content_active_pd_id') ? content_active_pd_id() : 1;
$content_deco_top   = function_exists('content_render_decoration') ? content_render_decoration('top',    $content_pd_id) : '';
$content_deco_bottom= function_exists('content_render_decoration') ? content_render_decoration('bottom', $content_pd_id) : '';
add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/css/content_design.php?pd_id='.(int)$content_pd_id.'">', 0);

// === 상세 페이지 ===
$no = function_exists('notice_get_one') ? notice_get_one($no_id) : false;
if (!$no || empty($no['no_use'])) {
    alert('등록된 공지가 없거나 사용 중이 아닙니다.');
}
// 시기 노출 정책 검사
if (function_exists('notice_time_matches')) {
    $cur = isset($config['cf_time']) ? (int)$config['cf_time'] : 0;
    if (!notice_time_matches($no['no_time'], $cur) && (!isset($is_admin) || !$is_admin)) {
        alert('현재 시기에 노출되는 공지가 아닙니다.');
    }
}

$g5['title'] = $no['no_subject'];

include_once(G5_PATH.'/_head.php');

// 개별 공지 스킨 / 프리셋 적용 (no_skin) — 내용관리과 스킨 공유
$notice_skin_dir = trim((string)($no['no_skin'] ?? ''));
$notice_skin_file = '';
if ($notice_skin_dir !== '') {
    if (preg_match('/^preset:(\d+)$/', $notice_skin_dir, $m)) {
        $content_pd_id = (int)$m[1];
        // 프리셋 CSS 재적용
        add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/css/content_design.php?pd_id='.(int)$content_pd_id.'">', 0);
    } else {
        $sk = preg_match('/^skin:(.+)$/', $notice_skin_dir, $m) ? $m[1] : $notice_skin_dir;
        $notice_skin_path = get_skin_path('content', $sk);
        if (is_file($notice_skin_path.'/notice.skin.php')) {
            $notice_skin_file = $notice_skin_path.'/notice.skin.php';
        } else if (is_file($notice_skin_path.'/content.skin.php')) {
            $notice_skin_file = $notice_skin_path.'/content.skin.php';
        }
    }
}

if ($is_admin) {
    // 'notice' 알림 종류가 켜져 있으면 발송 버튼 노출
    $ct_notice = sql_fetch("SELECT ct_use FROM `{$g5['call_type_map_table']}` WHERE ct_code='notice'", false);
    $can_send  = !empty($ct_notice) && !empty($ct_notice['ct_use']);
    echo '<div class="ctt_admin">';
    echo '<a href="'.G5_ADMIN_URL.'/notice_form.php?w=u&amp;no_id='.(int)$no['no_id'].'" class="ui-btn admin">공지 수정</a>';
    if ($can_send) {
        echo ' <button type="button" id="notice_admin_send_btn" class="ui-btn admin" data-no-id="'.(int)$no['no_id'].'">📣 전체 알림 발송</button>';
    } else {
        echo ' <span style="color:#888;font-size:12px;">(알림 종류 「공지」가 꺼져 있어 발송 불가)</span>';
    }
    echo '</div>';
    if ($can_send) {
        $token_send = function_exists('get_token') ? get_token() : '';
    ?>
    <script>
    (function(){
        var btn = document.getElementById('notice_admin_send_btn');
        if (!btn) return;
        btn.addEventListener('click', function(){
            if (btn.disabled) return;
            if (!confirm('이 공지를 모든 회원에게 알림 발송하시겠습니까?\n(되돌릴 수 없습니다)')) return;
            btn.disabled = true;
            var orig = btn.textContent;
            btn.textContent = '발송 중…';
            var fd = new FormData();
            fd.append('no_id', btn.getAttribute('data-no-id'));
            fd.append('token', '<?php echo htmlspecialchars($token_send, ENT_QUOTES); ?>');
            fetch('<?php echo G5_URL; ?>/ajax/notice_admin_send.php', { method:'POST', body: fd, credentials:'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(j){
                    if (j && j.ok) {
                        alert('알림이 발송되었습니다. (' + (j.count || 0) + '건)');
                        btn.textContent = '✓ 발송 완료';
                    } else {
                        alert('발송 실패: ' + ((j && j.msg) || '알 수 없는 오류'));
                        btn.disabled = false;
                        btn.textContent = orig;
                    }
                })
                .catch(function(e){
                    alert('네트워크 오류: ' + e);
                    btn.disabled = false;
                    btn.textContent = orig;
                });
        });
    })();
    </script>
    <?php
    }
}

$body = function_exists('notice_render_one')
        ? notice_render_one($no_id, array('wrap' => false))
        : (string)$no['no_content'];

if ($notice_skin_file) {
    // 스킨에 변수 전달 후 include
    $str = $body;
    $co  = $no;
    include($notice_skin_file);
}else{
?>
    <article class="notice-detail">
        <?php echo $content_deco_top; ?>
        <div id="content_body" class="ctt_box notice-body"><?php echo $body; ?></div>
        <?php echo $content_deco_bottom; ?>
    </article>

<?php    
    }
include_once(G5_PATH.'/_tail.php');
?>
