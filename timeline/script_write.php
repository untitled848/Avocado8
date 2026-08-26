<?php
/**
 * 타임라인 스크립트 작성/수정 (스마트에디터)
 */
include_once('./_common.php');

$my_ch_id = ses($character, 'ch_id', 0, 'int');
if (!$is_member || !$my_ch_id) {
    alert('로그인이 필요합니다.', './index.php?tl_table='.$tl_table);
}
if (!can_write_timeline($timeline, $my_ch_id, $is_admin)) {
    alert('글쓰기 권한이 없습니다.', './index.php?tl_table='.$tl_table);
}

$wr_id = ses($_GET, 'wr_id', 0, 'int');
$is_edit = false;
$edit_content = '';
$post = null;

if ($wr_id > 0) {
    $post = sql_fetch("SELECT * FROM {$write_table} WHERE wr_id = '{$wr_id}' AND wr_deleted = 0");
    if (!$post || empty($post['wr_id'])) {
        alert('글을 찾을 수 없습니다.');
    }
    if (!can_edit_timeline_post($post, $my_ch_id, $is_admin)) {
        alert('수정 권한이 없습니다.');
    }
    if (!function_exists('timeline_row_is_script') || !timeline_row_is_script($post)) {
        alert('스크립트 글만 이 화면에서 수정할 수 있습니다.');
    }
    $is_edit = true;
    $edit_content = ses($post, 'wr_content', '', 'raw');
    // 에디터 표시용 복구 (이중 이스케이프/엔티티)
    $guard = 0;
    while ($guard < 3 && (strpos($edit_content, '\\"') !== false || strpos($edit_content, "\\'") !== false || strpos($edit_content, '\\\\') !== false)) {
        $edit_content = stripslashes($edit_content);
        $guard++;
    }
    $guard = 0;
    while ($guard < 3 && strpos($edit_content, '&lt;') !== false
        && preg_match('/&lt;(p|div|h[1-6]|table|ul|ol|span|br|b|i|u|strong|em|font|img)\b/i', $edit_content)) {
        $edit_content = html_entity_decode($edit_content, ENT_QUOTES, 'UTF-8');
        $guard++;
    }
}

include_once(G5_EDITOR_LIB);

$g5['title'] = ($is_edit ? '스크립트 수정' : '스크립트 작성').' - '.ses($timeline, 'tl_subject', '타임라인', 'raw');
include_once(G5_PATH.'/head.sub.php');

$is_dhtml_editor = !empty($config['cf_editor']);
$editor_html = editor_html('wr_content', $edit_content, $is_dhtml_editor);

// 사진첨부 팝업이 타임라인 전용 업로드를 쓰도록 (중첩 팝업 논스 이슈 회피)
$tl_editor_upload_url = G5_URL.'/timeline/ajax/editor_upload.php';
$tl_back_url = './index.php?tl_table='.urlencode($tl_table);
?>
<script>
(function () {
    var m = document.querySelector('meta[name="viewport"]');
    if (!m) {
        m = document.createElement('meta');
        m.setAttribute('name', 'viewport');
        document.head.appendChild(m);
    }
    m.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
})();
var g5_se2_upload_url = <?php echo json_encode($tl_editor_upload_url); ?>;
</script>
<?php timeline_print_css_head(); ?>

<style>
html, body {
    margin: 0;
    padding: 0;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
    background: #fff;
}
.tl-script-write {
    box-sizing: border-box;
    width: 100%;
    max-width: 900px;
    margin: 0 auto;
    padding: 12px 12px 24px;
}
.tl-script-write h1 {
    font-size: 18px;
    margin: 0 0 12px;
    line-height: 1.3;
}
.tl-script-write .tl-script-actions {
    margin-top: 12px;
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    flex-wrap: wrap;
}
.tl-script-write .ui-btn { cursor: pointer; }

/* SmartEditor: 뷰포트에 맞춤 + 툴바만 가로 스크롤 */
.tl-script-write textarea.smarteditor2,
.tl-script-write #wr_content {
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box;
}
.tl-script-write iframe,
.tl-script-write .cke_sc + iframe,
.tl-script-write [id^="smart_editor2"] {
    max-width: 100% !important;
}
.tl-script-write .se2_conversion_mode,
.tl-script-write .husky_seditor_editing_area_container,
.tl-script-write #smart_editor2 {
    max-width: 100% !important;
    box-sizing: border-box;
}

@media (max-width: 768px) {
    .tl-script-write {
        max-width: 100%;
        padding: 10px 8px 20px;
    }
    .tl-script-write h1 { font-size: 16px; }
    .tl-script-write .tl-script-actions {
        position: sticky;
        bottom: 0;
        background: #fff;
        padding: 10px 0 4px;
        border-top: 1px solid #eee;
        justify-content: stretch;
    }
    .tl-script-write .tl-script-actions .ui-btn {
        flex: 1;
        min-height: 44px;
    }
    /* SE2 툴바 가로 스크롤 허용 (잘림 대신 스크롤) */
    .tl-script-write iframe {
        width: 100% !important;
    }
}
</style>

<div class="tl-script-write">
    <h1><?php echo $is_edit ? '스크립트 수정' : '스크립트 작성'; ?></h1>
    <form name="fscript" id="fscript" method="post" action="./script_write_update.php" onsubmit="return fscript_submit(this);">
        <input type="hidden" name="tl_table" value="<?php echo h($tl_table); ?>">
        <?php if ($is_edit) { ?>
        <input type="hidden" name="wr_id" value="<?php echo (int)$wr_id; ?>">
        <?php } ?>
        <?php echo $editor_html; ?>
        <div class="tl-script-actions">
            <button type="button" class="ui-btn" onclick="tlScriptCancel();">취소</button>
            <button type="submit" class="ui-btn point"><?php echo $is_edit ? '수정' : '등록'; ?></button>
        </div>
    </form>
</div>

<script>
function tlScriptCancel() {
    if (window.opener && !window.opener.closed) {
        window.close();
        return;
    }
    location.href = <?php echo json_encode($tl_back_url); ?>;
}

function fscript_submit(f) {
    <?php echo get_editor_js('wr_content', $is_dhtml_editor); ?>
    <?php echo chk_editor_js('wr_content', $is_dhtml_editor); ?>
    return true;
}

// SE2 iframe/툴바 폭을 뷰포트에 맞춤 (로드 후·리사이즈 시)
(function() {
    function fitEditor() {
        var wrap = document.querySelector('.tl-script-write');
        if (!wrap) return;
        var w = wrap.clientWidth;
        if (w < 1) return;
        var frames = wrap.getElementsByTagName('iframe');
        for (var i = 0; i < frames.length; i++) {
            try {
                frames[i].style.width = '100%';
                frames[i].style.maxWidth = '100%';
                var doc = frames[i].contentDocument || frames[i].contentWindow.document;
                if (!doc) continue;
                var html = doc.documentElement;
                var body = doc.body;
                if (html) {
                    html.style.maxWidth = '100%';
                    html.style.overflowX = 'auto';
                }
                if (body) {
                    body.style.maxWidth = '100%';
                    body.style.overflowX = 'auto';
                    body.style.margin = '0';
                }
                // 툴바 영역 가로 스크롤
                var toolbar = doc.querySelector('.se2_tool, .husky_seditor_tool, #smart_editor2 .se2_tool');
                if (toolbar) {
                    toolbar.style.maxWidth = '100%';
                    toolbar.style.overflowX = 'auto';
                    toolbar.style.overflowY = 'hidden';
                    toolbar.style.webkitOverflowScrolling = 'touch';
                }
                var se2 = doc.getElementById('smart_editor2') || doc.querySelector('#smart_editor2, .smart_editor2');
                if (se2) {
                    se2.style.maxWidth = '100%';
                    se2.style.width = '100%';
                    se2.style.boxSizing = 'border-box';
                }
            } catch (e) {}
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        fitEditor();
        setTimeout(fitEditor, 400);
        setTimeout(fitEditor, 1200);
    });
    window.addEventListener('resize', fitEditor);
    window.addEventListener('orientationchange', function() {
        setTimeout(fitEditor, 200);
    });
})();
</script>

<?php
include_once(G5_PATH.'/tail.sub.php');
