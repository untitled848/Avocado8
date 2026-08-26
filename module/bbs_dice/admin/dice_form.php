<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 주사위 설정 — module/bbs_dice/admin/dice_form.php
 * 진입: /adm_module/module.php?m=bbs_dice&p=dice_form
 */

$sub_menu = '002300';

require_once G5_PATH.'/module/bbs_dice/lib.php';

$saved = false;
$img_msg  = '';
$img_ok   = null;
$msg   = '';

// --- 이미지 관리 POST 처리 ---
$act = $_POST['act'] ?? '';
if (in_array($act, array('upload_image', 'delete_image', 'replace_image'), true)) {
    if (function_exists('check_admin_token')) check_admin_token();

    $img_dir = G5_PATH . '/module/bbs_dice/img';
    $images  = bbs_dice_images();

    if ($act === 'upload_image') {
        if (!empty($_FILES['dice_img']['tmp_name'])) {
            $res = bbs_dice_image_upload($_FILES['dice_img']);
            if ($res['ok']) {
                $images[] = $res['filename'];
                mdl_setting_set('bbs_dice', 'images', implode('|', $images));
                $img_ok  = true;
                $img_msg = '이미지 업로드 완료: ' . htmlspecialchars($res['filename']);
            } else {
                $img_ok  = false;
                $img_msg = '업로드 실패: ' . $res['msg'];
            }
        }
    } elseif ($act === 'delete_image') {
        $face = (int)($_POST['face'] ?? 0) - 1; // 0-based
        if ($face >= 0 && $face < count($images)) {
            $f = $images[$face];
            $fp = $img_dir . '/' . $f;
            if (is_file($fp)) @unlink($fp);
            array_splice($images, $face, 1);
            mdl_setting_set('bbs_dice', 'images', implode('|', $images));
            $img_ok  = true;
            $img_msg = '면 ' . ($face+1) . ' 이미지(' . htmlspecialchars($f) . ') 삭제. 이후 면 번호가 당겨졌습니다.';
        }
    } elseif ($act === 'replace_image') {
        $face = (int)($_POST['face'] ?? 0) - 1; // 0-based
        if ($face >= 0 && $face < count($images) && !empty($_FILES['dice_img']['tmp_name'])) {
            $old_f = $images[$face];
            $res = bbs_dice_image_upload($_FILES['dice_img']);
            if ($res['ok']) {
                $old_fp = $img_dir . '/' . $old_f;
                if (is_file($old_fp)) @unlink($old_fp);
                $images[$face] = $res['filename'];
                mdl_setting_set('bbs_dice', 'images', implode('|', $images));
                $img_ok  = true;
                $img_msg = '면 ' . ($face+1) . ' 이미지 교체 완료: ' . htmlspecialchars($res['filename']);
            } else {
                $img_ok  = false;
                $img_msg = '교체 실패: ' . $res['msg'];
            }
        }
    }
}

// --- 기본 설정 POST 처리 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === '') {
    if (function_exists('check_admin_token')) check_admin_token();

    $count  = max(1, min(10,  (int)($_POST['count']  ?? 2)));
    $sides  = max(2, min(100, (int)($_POST['sides']  ?? 6)));
    $render_mode_save = ($_POST['render_mode'] ?? 'image') === 'number' ? 'number' : 'image';
    $images_post = trim((string)($_POST['images'] ?? ''));
    $img_arr = array_values(array_filter(array_map('trim', explode('|', $images_post)), 'strlen'));
    $images_save = implode('|', $img_arr);

    mdl_setting_set('bbs_dice', 'count',       $count);
    mdl_setting_set('bbs_dice', 'sides',       $sides);
    mdl_setting_set('bbs_dice', 'images',      $images_save);
    mdl_setting_set('bbs_dice', 'render_mode', $render_mode_save);

    // 숫자 박스 스타일
    $sanitize_css = function ($v) {
        $v = trim((string)$v);
        if (preg_match('/[{};]|\*\//', $v)) return '';
        return $v;
    };
    mdl_setting_set('bbs_dice', 'num_border',     $sanitize_css($_POST['num_border']     ?? ''));
    mdl_setting_set('bbs_dice', 'num_background', $sanitize_css($_POST['num_background'] ?? ''));
    mdl_setting_set('bbs_dice', 'num_color',      $sanitize_css($_POST['num_color']      ?? ''));
    mdl_setting_set('bbs_dice', 'num_padding',    $sanitize_css($_POST['num_padding']    ?? ''));

    $saved = true;
}

$count  = bbs_dice_count();
$sides  = bbs_dice_sides();
$images = bbs_dice_images();
$render_mode = bbs_dice_render_mode();
$images_str = implode('|', $images);
$module_url = G5_MDL_URL.'/bbs_dice';

// 이미지 존재 여부 체크
$img_dir = G5_PATH.'/module/bbs_dice/img';
$missing = array();
foreach ($images as $f) {
    if (!is_file($img_dir.'/'.$f)) $missing[] = $f;
}

$warn_sides = ($sides > count($images));
?>
<style>
.dice-wrap { max-width: 900px; display:flex; flex-direction:column; gap:16px; }
.dice-flash { padding:10px 14px; border-radius:6px; font-weight:600; }
.dice-flash.ok { background:#e8f5e9; color:#197d18; border:1px solid #a5d6a7; }
.dice-flash.warn { background:#fff8e1; color:#a06800; border:1px solid #ffd54f; }
.dice-card { border:1px solid #e0e0e0; border-radius:8px; background:#fff; }
.dice-card > h3 { margin:0; padding:12px 16px; border-bottom:1px solid #eee; font-size:15px; background:#fafafa; border-radius:8px 8px 0 0; }
.dice-card .body { padding:16px; }
.dice-form table { width:100%; border-collapse:collapse; }
.dice-form th, .dice-form td { padding:10px 12px; border-bottom:1px solid #f0f0f0; vertical-align:top; text-align:left; }
.dice-form th { width:160px; color:#444; font-weight:600; background:#fafbfc; }
.dice-form .frm_input, .dice-form textarea { padding:7px 10px; border:1px solid #ccc; border-radius:5px; font-family:inherit; font-size:13px; }
.dice-form textarea { width:100%; min-height:80px; }
.dice-form .help { color:#888; font-size:12px; margin-top:6px; }
.dice-actions { display:flex; gap:10px; padding:12px 16px; background:#fafafa; border-top:1px solid #eee; border-radius:0 0 8px 8px; }
.dice-actions button { padding:9px 22px; border-radius:5px; border:0; cursor:pointer; font-weight:600; }
.dice-actions .save { background:#1976d2; color:#fff; }
.dice-preview { display:flex; flex-wrap:wrap; gap:10px; padding:14px; background:#f5f5f5; border-radius:6px; }
.dice-preview .item { display:flex; flex-direction:column; align-items:center; gap:4px; padding:6px; background:#fff; border:1px solid #ddd; border-radius:4px; min-width:64px; }
.dice-preview .item img { width:48px; height:48px; object-fit:contain; }
.dice-preview .item.miss { background:#ffebee; border-color:#ef9a9a; }
.dice-preview .item .name { font-size:11px; color:#666; word-break:break-all; max-width:80px; text-align:center; }
.dice-preview .item .face { font-size:11px; color:#1976d2; font-weight:600; }
.dice-preview .item .img-actions { display:flex; gap:4px; margin-top:4px; }
.dice-preview .item .img-actions button { font-size:11px; padding:2px 6px; border:1px solid #ccc; border-radius:3px; cursor:pointer; background:#f5f5f5; }
.dice-preview .item .img-actions button.del { border-color:#e57373; color:#c62828; }
.dice-upload-area { border:2px dashed #90caf9; border-radius:8px; padding:16px; text-align:center; margin-top:12px; }
.dice-upload-area label { cursor:pointer; font-weight:600; color:#1565c0; }
.dice-flash.ok2 { background:#e3f2fd; color:#0d47a1; border:1px solid #90caf9; }
</style>

<form method="post" class="dice-form" id="dice-settings-form">
<?php if (function_exists('get_admin_token_field')) echo get_admin_token_field(); ?>
<input type="hidden" name="act" value="">

<div class="dice-wrap">

    <h2>🎲 주사위 설정</h2>

    <?php if ($saved) { ?><div class="dice-flash ok">✓ 저장되었습니다.</div><?php } ?>
    <?php if ($img_msg !== '') { ?>
    <div class="dice-flash <?php echo $img_ok ? 'ok2' : 'warn'; ?>"><?php echo $img_msg; ?></div>
    <?php } ?>

    <?php if ($warn_sides) { ?>
    <div class="dice-flash warn">
        ⚠️ 면 수(<?php echo $sides; ?>)가 등록된 이미지 수(<?php echo count($images); ?>)보다 많습니다.
        면 번호 <?php echo count($images)+1; ?>~<?php echo $sides; ?> 은 이미지 대신 숫자로 표시됩니다.
    </div>
    <?php } ?>

    <?php if (!empty($missing)) { ?>
    <div class="dice-flash warn">
        ⚠️ 다음 이미지 파일이 <code>module/bbs_dice/img/</code> 안에 없습니다:
        <strong><?php echo htmlspecialchars(implode(', ', $missing)); ?></strong>
    </div>
    <?php } ?>

    <div class="dice-card">
        <h3>굴림 규칙</h3>
        <div class="body">
            <table>
                <tr>
                    <th>굴릴 개수</th>
                    <td>
                        <input type="number" name="count" value="<?php echo $count; ?>" min="1" max="10" class="frm_input" /> 개
                        <div class="help">한 번에 굴릴 주사위 개수 (1~10)</div>
                    </td>
                </tr>
                <tr>
                    <th>면 수</th>
                    <td>
                        <input type="number" name="sides" value="<?php echo $sides; ?>" min="2" max="100" class="frm_input" /> 면체
                        <div class="help">예: 6 → 1~6 사이의 숫자가 나옵니다 (2~100)</div>
                    </td>
                </tr>
                <tr>
                    <th>주사위 이미지</th>
                    <td>
                        <textarea name="images"><?php echo htmlspecialchars($images_str); ?></textarea>
                        <div class="help">
                            <code>module/bbs_dice/img/</code> 안의 파일 이름을 <code>|</code> 로 구분.<br>
                            예: <code>d1.png|d2.png|d3.png|d4.png|d5.png|d6.png</code><br>
                            첫 번째 = 면 1, 두 번째 = 면 2 … (아래 이미지 관리에서 직접 업로드도 가능)
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>표시 방식</th>
                    <td>
                        <label><input type="radio" name="render_mode" value="image" <?php echo $render_mode==='image'?'checked':''; ?>> 이미지</label>
                        &nbsp;&nbsp;
                        <label><input type="radio" name="render_mode" value="number" <?php echo $render_mode==='number'?'checked':''; ?>> 숫자만</label>
                        <div class="help">이미지 선택 시 이미지가 없는 면은 숫자로 대체됩니다.</div>
                    </td>
                </tr>
                <?php $num_style = bbs_dice_num_style(); ?>
                <tr>
                    <th>숫자 박스 스타일</th>
                    <td>
                        <div style="display:grid;grid-template-columns:90px 1fr;gap:8px 12px;align-items:center;max-width:520px;">
                            <label>테두리</label>
                            <input type="text" name="num_border" id="num_border" value="<?php echo htmlspecialchars($num_style['border']); ?>" class="frm_input" placeholder="1px solid #999">

                            <?php
                            // 컬러픽커는 hex(#rrggbb) 만 받으므로 저장값에서 hex 만 추출. 비-hex 값은 텍스트 입력 그대로 유지.
                            $_pick_hex = function($v) {
                                if (preg_match('/#([0-9a-fA-F]{6})\b/', (string)$v, $m)) return '#'.strtolower($m[1]);
                                if (preg_match('/#([0-9a-fA-F]{3})\b/', (string)$v, $m)) {
                                    $h = strtolower($m[1]);
                                    return '#'.$h[0].$h[0].$h[1].$h[1].$h[2].$h[2];
                                }
                                return '';
                            };
                            $_bg_hex = $_pick_hex($num_style['background']);
                            $_fg_hex = $_pick_hex($num_style['color']);
                            ?>
                            <label>배경색</label>
                            <span style="display:inline-flex;gap:6px;align-items:center;">
                                <input type="color" id="num_background_pick" value="<?php echo $_bg_hex !== '' ? $_bg_hex : '#ffffff'; ?>" style="width:42px;height:30px;padding:0;border:1px solid #ccc;border-radius:4px;cursor:pointer;">
                                <input type="text" name="num_background" id="num_background" value="<?php echo htmlspecialchars($num_style['background']); ?>" class="frm_input" placeholder="#ffffff" style="width:160px;">
                                <button type="button" onclick="document.getElementById('num_background').value='';document.getElementById('num_background_pick').value='#ffffff';" style="font-size:11px;padding:3px 8px;border:1px solid #ccc;border-radius:3px;background:#f5f5f5;cursor:pointer;">비움</button>
                            </span>

                            <label>글자색</label>
                            <span style="display:inline-flex;gap:6px;align-items:center;">
                                <input type="color" id="num_color_pick" value="<?php echo $_fg_hex !== '' ? $_fg_hex : '#222222'; ?>" style="width:42px;height:30px;padding:0;border:1px solid #ccc;border-radius:4px;cursor:pointer;">
                                <input type="text" name="num_color" id="num_color" value="<?php echo htmlspecialchars($num_style['color']); ?>" class="frm_input" placeholder="#222222" style="width:160px;">
                                <button type="button" onclick="document.getElementById('num_color').value='';document.getElementById('num_color_pick').value='#222222';" style="font-size:11px;padding:3px 8px;border:1px solid #ccc;border-radius:3px;background:#f5f5f5;cursor:pointer;">비움</button>
                            </span>

                            <label>안쪽 여백</label>
                            <input type="text" name="num_padding" id="num_padding" value="<?php echo htmlspecialchars($num_style['padding']); ?>" class="frm_input" placeholder="2px 6px">
                        </div>
                        <div class="help">
                            이미지가 없는 면이 숫자로 표시될 때(<code>.dice-num</code>)의 스타일입니다.
                            CSS 값을 그대로 입력 (예: 테두리 = <code>2px dashed #f08</code>).
                            빈 칸은 적용되지 않음. 페이지당 1번만 출력되어 코멘트마다 인라인되지 않습니다.
                        </div>
                        <div style="margin-top:8px;">
                            <span style="margin-right:8px;color:#666;">미리보기:</span>
                            <span id="num_style_preview" class="dice-num" style="display:inline-block;min-width:24px;margin:0 2px;border-radius:4px;text-align:center;font-weight:600;<?php
                                $p = bbs_dice_num_style();
                                if ($p['border']!=='')     echo 'border:'.htmlspecialchars($p['border']).';';
                                if ($p['background']!=='') echo 'background:'.htmlspecialchars($p['background']).';';
                                if ($p['color']!=='')      echo 'color:'.htmlspecialchars($p['color']).';';
                                if ($p['padding']!=='')    echo 'padding:'.htmlspecialchars($p['padding']).';';
                            ?>">5</span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="dice-actions">
            <button type="submit" class="save">저장</button>
        </div>
    </div>

</div>
</form>

<div class="dice-wrap">

    <div class="dice-card">
        <h3>이미지 관리</h3>
        <div class="body">
            <div class="dice-preview">
                <?php foreach ($images as $i => $f) {
                    $face   = $i + 1;
                    $exists = is_file($img_dir.'/'.$f);
                ?>
                <div class="item <?php echo $exists ? '' : 'miss'; ?>">
                    <?php if ($exists) { ?>
                        <img src="<?php echo $module_url; ?>/img/<?php echo htmlspecialchars($f); ?>?_=<?php echo filemtime($img_dir.'/'.$f); ?>" alt="" />
                    <?php } else { ?>
                        <div style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;color:#c33;">✗</div>
                    <?php } ?>
                    <span class="face">면 <?php echo $face; ?></span>
                    <span class="name"><?php echo htmlspecialchars($f); ?></span>
                    <div class="img-actions">
                        <button type="button" class="del" onclick="diceDeleteFace(<?php echo $face; ?>, '<?php echo addslashes($f); ?>')">삭제</button>
                        <label title="교체">
                            <span style="font-size:11px;padding:2px 6px;border:1px solid #ccc;border-radius:3px;cursor:pointer;background:#f5f5f5;">교체</span>
                            <input type="file" accept="image/*" style="display:none" onchange="diceReplaceFace(<?php echo $face; ?>, this)">
                        </label>
                    </div>
                </div>
                <?php } ?>
                <?php for ($f = count($images)+1; $f <= $sides; $f++) { ?>
                <div class="item miss">
                    <div style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;color:#c33;font-weight:700;font-size:18px;"><?php echo $f; ?></div>
                    <span class="face">면 <?php echo $f; ?></span>
                    <span class="name">(이미지 없음)</span>
                </div>
                <?php } ?>
            </div>

            <!-- 새 이미지 추가 -->
            <form method="post" enctype="multipart/form-data" class="dice-upload-area" style="margin-top:16px;">
                <?php if (function_exists('get_admin_token_field')) echo get_admin_token_field(); ?>
                <input type="hidden" name="act" value="upload_image">
                <label>
                    ＋ 새 이미지 추가 (PNG/JPG/GIF/WEBP, 최대 512KB)<br>
                    <input type="file" name="dice_img" accept="image/*" style="margin-top:8px;" onchange="this.form.submit()">
                </label>
                <div class="help" style="margin-top:8px;">업로드하면 자동으로 이미지 목록 끝에 추가됩니다.</div>
            </form>
        </div>
    </div>

    <!-- 숨김 폼: 삭제/교체 -->
    <form id="dice-img-action-form" method="post" enctype="multipart/form-data" style="display:none;">
        <?php if (function_exists('get_admin_token_field')) echo get_admin_token_field(); ?>
        <input type="hidden" name="act" id="dice-act" value="">
        <input type="hidden" name="face" id="dice-face" value="">
        <input type="file" name="dice_img" id="dice-replace-file" accept="image/*">
    </form>

    <script>
    function diceDeleteFace(face, fname) {
        if (!confirm('면 ' + face + ' 이미지(' + fname + ')를 삭제하시겠습니까?\n이후 면 번호가 당겨집니다.')) return;
        document.getElementById('dice-act').value = 'delete_image';
        document.getElementById('dice-face').value = face;
        document.getElementById('dice-img-action-form').submit();
    }
    function diceReplaceFace(face, fileInput) {
        if (!fileInput.files || !fileInput.files[0]) return;
        document.getElementById('dice-act').value = 'replace_image';
        document.getElementById('dice-face').value = face;
        var dt = new DataTransfer();
        dt.items.add(fileInput.files[0]);
        document.getElementById('dice-replace-file').files = dt.files;
        document.getElementById('dice-img-action-form').submit();
    }

    // 컬러픽커 ↔ 텍스트 입력 동기화
    (function(){
        function bind(pickId, textId){
            var p = document.getElementById(pickId);
            var t = document.getElementById(textId);
            if (!p || !t) return;
            p.addEventListener('input', function(){ t.value = p.value; });
            t.addEventListener('input', function(){
                var v = t.value.trim();
                if (/^#[0-9a-fA-F]{6}$/.test(v)) p.value = v.toLowerCase();
                else if (/^#[0-9a-fA-F]{3}$/.test(v)) {
                    var s = v.toLowerCase();
                    p.value = '#' + s[1]+s[1] + s[2]+s[2] + s[3]+s[3];
                }
            });
        }
        bind('num_background_pick', 'num_background');
        bind('num_color_pick',      'num_color');
    })();

    // 숫자 박스 스타일 미리보기 실시간 업데이트
    (function(){
        var ids = ['num_border', 'num_background', 'num_color', 'num_padding',
                   'num_background_pick', 'num_color_pick'];
        function updateNumPreview() {
            var prev = document.getElementById('num_style_preview');
            if (!prev) return;
            var border  = (document.getElementById('num_border')     || {}).value || '';
            var bg      = (document.getElementById('num_background') || {}).value || '';
            var color   = (document.getElementById('num_color')      || {}).value || '';
            var padding = (document.getElementById('num_padding')    || {}).value || '';
            prev.style.border     = border  || '';
            prev.style.background = bg      || '';
            prev.style.color      = color   || '';
            prev.style.padding    = padding || '';
        }
        ids.forEach(function(id){
            var el = document.getElementById(id);
            if (el) el.addEventListener('input', updateNumPreview);
        });
    })();
    </script>

</div>
