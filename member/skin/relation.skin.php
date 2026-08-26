<?php
/**
 * 관계 리스트 / 등록 / 수정 (통합)
 *
 * 인자(상위 스코프):
 *   $g5, $member, $ch, $ch_id, $is_owner, [$mb], [$time]
 *
 * 동작:
 *   - 항상 관계 리스트를 출력
 *   - 소유자면 등록 폼 + 행마다 수정 모달 출력
 *
 * 모듈 훅:
 *   filter relation_form_extra        ($html, $ctx=['mode'=>'add'|'edit','row'=>$row])
 *   filter relation_list_item_extra   ($html, $ctx=['row'=>$row,'re_ch'=>$re_ch,'is_owner'=>bool])
 */
if (!defined('_GNUBOARD_')) exit;


if (empty($g5['relation_table']) || ($ch['ch_state'] ?? '') !== '승인') return;

if(get_time_by_order($time)['ti_open']!=1&&!$is_admin){
    echo '<p class="empty">비공개 시기의 관계란에 접근할 수 없습니다.</p>';
    return;
}

if (!isset($is_owner)) {
    $is_owner = (!empty($member['mb_id']) && !empty($ch['mb_id']) && $member['mb_id'] === $ch['mb_id']);
}
$_rel_time   = isset($time) ? (int)$time : (function_exists('get_current_time') ? get_current_time() : 1);
$_rel_action = G5_URL.'/mypage/character/relation_update.php';
$_rel_delete = G5_URL.'/mypage/character/relation_delete.php';
$_rel_return = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

// 관계 데이터
$_rel_rs = sql_query("SELECT * FROM `{$g5['relation_table']}` WHERE ch_id='".(int)$ch_id."' ORDER BY rm_order ASC, rm_id DESC");
$_rel_rows = array();
if ($_rel_rs) while ($row = sql_fetch_array($_rel_rs)) $_rel_rows[] = $row;

$_has_filter = function_exists('mdl_apply_filters');
?>
<style>@import url(<?php echo G5_URL; ?>/css/member_relation.css);</style>
<h3>STORY</h3>
<div class="relation-box">

<?php if ($is_owner): ?>
    <details>
    <summary class="ui-btn">관계 등록</summary>
        <form name="frmRemember" class="rm-add-form" method="post" action="<?php echo htmlspecialchars($_rel_action, ENT_QUOTES); ?>" onsubmit="return fwrite_submit(this);">
            <input type="hidden" name="w" value="">
            <input type="hidden" name="ch_id" value="<?php echo (int)$ch_id; ?>">
            <input type="hidden" name="re_ch_id" id="search_re_ch_id" value="">
            <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_rel_return, ENT_QUOTES); ?>">

            <table class="theme-form rm-form-table">
                <colgroup>
                    <col style="width:100px;">
                    <col>
                </colgroup>
                <tbody>
                    <tr>
                        <th>대상캐릭터</th>
                        <td>
                            <input type="text" name="search_re_ch_name" value="" id="search_re_ch_name" autocomplete="off"
                                onkeyup="get_ajax_character(this, 'character_list', 'search_re_ch_id');">
                            <div id="character_list" class="ajax-list-box theme-box"><div class="list"></div></div>
                        </td>
                    </tr>
                    <tr>
                        <th>호감도</th>
                        <td>
                            <select name="rm_like" style="width:100%; margin-bottom:5px;">
                                <option value="">호감도</option>
                                <option value="5">5 : ♡♡♡♡♡</option>
                                <option value="4">4 : ♡♡♡♡</option>
                                <option value="3">3 : ♡♡♡</option>
                                <option value="2">2 : ♡♡</option>
                                <option value="1">1 : ♡</option>
                                <option value="0">0</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>관계요약</th>
                        <td><textarea name="rm_memo" placeholder="관계요약입력" class="full" rows="6"></textarea></td>
                    </tr>
                    <tr>
                        <th>관계링크입력</th>
                        <td>
                            <textarea name="rm_link" placeholder="관계링크입력 : 여러개를 입력할 경우, 엔터로 구분해 주시길 바랍니다." class="full" rows="4"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th>순서</th>
                        <td><input type="text" name="rm_order" value="0" class="required frm_input" style="width:50px;"></td>
                    </tr>
                    <?php
                        if ($_has_filter) {
                            echo mdl_apply_filters('relation_form_extra', '', array('mode' => 'add', 'row' => array()));
                        }
                    ?>
                </tbody>
            </table>

            <button type="submit" class="ui-btn full point">추가</button>
            <hr class="padding">
        </form>
     </details>
<?php endif; ?>

<ul class="relation-member-list">
<?php foreach ($_rel_rows as $row):
    $re_ch = get_character($row['re_ch_id'], $_rel_time);
    if (empty($row['rm_memo'])) continue;
?>
    <li id="rm_<?php echo (int)$row['rm_id']; ?>">
        <div class="ui-thumb">
            <a href="<?php echo G5_URL; ?>/member/viewer.php?ch_id=<?php echo (int)$re_ch['ch_id']; ?>" target="_blank">
                <img src="<?php echo $re_ch['ch_thumb']; ?>">
            </a>
        </div>
        <div class="info">
            <div class="rm-name">
                <?php echo $re_ch['ch_name']; ?>
                <?php if ($is_owner): ?>
                    <a href="#" class="btn-modify ui-btn small" data-rm-id="<?php echo (int)$row['rm_id']; ?>">수정</a>
                    <a href="<?php echo $_rel_delete; ?>?rm_id=<?php echo (int)$row['rm_id']; ?>" class="btn-delete ui-btn small"
                        onclick="return confirm('삭제된 데이터는 복구할 수 없습니다. 삭제 하시겠습니까?');">삭제</a>
                <?php endif; ?>
            </div>
            <div class="rm-like-style">
                <p>
                <?php for ($j = 0; $j < 5; $j++):
                    $cls = ($j < (int)$row['rm_like']) ? 'txt-point' : '';
                    $sty = ($j < (int)$row['rm_like']) ? '' : 'opacity:.2;';
                ?>
                    <i class="<?php echo $cls; ?>" style="<?php echo $sty; ?>"></i>
                <?php endfor; ?>
                </p>
            </div>
        </div>
        <div class="memo theme-box">
            <div class="ori-content"><?php echo nl2br($row['rm_memo']); ?></div>
        </div>

        <ol>
        <?php
            $rm_link_br = nl2br((string)$row['rm_link']);
            $link_list  = explode('<br />', $rm_link_br);
            foreach ($link_list as $r_row):
                if (!$r_row) continue;
        ?>
            <li><a href="<?php echo trim($r_row); ?>" class="btn-log" target="_blank"></a></li>
        <?php endforeach; ?>
        </ol>

        <?php
            if ($_has_filter) {
                echo mdl_apply_filters('relation_list_item_extra', '', array(
                    'row'     => $row,
                    're_ch'   => $re_ch,
                    'is_owner'=> (bool)$is_owner,
                ));
            }
        ?>
    </li>
<?php endforeach; ?>
</ul>

</div><!-- /.relation-box -->

<?php if ($is_owner): ?>
<?php foreach ($_rel_rows as $row):
    if (empty($row['rm_memo'])) continue;
    $rm_id = (int)$row['rm_id'];
?>
<div class="rm-modal" id="rm-modal-<?php echo $rm_id; ?>" aria-hidden="true">
    <div class="rm-modal-backdrop" data-rm-close="1"></div>
    <div class="rm-modal-dialog" role="dialog" aria-modal="true">
        <div class="rm-modal-header">
            <h3 class="rm-modal-title">관계 수정</h3>
            <button type="button" class="ui-btn small rm-modal-close" data-rm-close="1">닫기</button>
        </div>
        <form class="rm-edit-form" method="post" action="<?php echo htmlspecialchars($_rel_action, ENT_QUOTES); ?>" onsubmit="return fwrite_submit(this);">
            <input type="hidden" name="w" value="u">
            <input type="hidden" name="ch_id" value="<?php echo (int)$ch_id; ?>">
            <input type="hidden" name="rm_id" value="<?php echo $rm_id; ?>">
            <input type="hidden" name="re_ch_id" value="<?php echo (int)$row['re_ch_id']; ?>">
            <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_rel_return, ENT_QUOTES); ?>">

            <table class="theme-form rm-form-table">
                <colgroup>
                    <col style="width:100px;">
                    <col>
                </colgroup>
                <tbody>
                    <tr>
                        <th>호감도</th>
                        <td>
                            <select name="rm_like" style="width:100%;">
                                <option value="">호감도</option>
                                <?php foreach (array(5,4,3,2,1,0) as $lv):
                                    $sel = ((string)$row['rm_like'] === (string)$lv) ? 'selected' : ''; ?>
                                    <option value="<?php echo $lv; ?>" <?php echo $sel; ?>><?php echo $lv; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>관계요약</th>
                        <td><textarea name="rm_memo" class="full" rows="6"><?php echo htmlspecialchars((string)$row['rm_memo'], ENT_QUOTES); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>관계링크</th>
                        <td><textarea name="rm_link" class="full" rows="4"><?php echo htmlspecialchars((string)$row['rm_link'], ENT_QUOTES); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>순서</th>
                        <td><input type="text" name="rm_order" value="<?php echo (int)$row['rm_order']; ?>" class="frm_input" style="width:50px;"></td>
                    </tr>
                    <?php
                        if ($_has_filter) {
                            echo mdl_apply_filters('relation_form_extra', '', array('mode' => 'edit', 'row' => $row));
                        }
                    ?>
                </tbody>
            </table>

            <div class="rm-modal-actions">
                <button type="submit" class="ui-btn full point">UPDATE</button>
                <button type="button" class="ui-btn full" data-rm-close="1">취소</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<script>
(function(){
    function open(id){
        var m = document.getElementById('rm-modal-' + id);
        if (!m) return;
        m.setAttribute('aria-hidden', 'false');
        m.classList.add('rm-modal-open');
    }
    function close(modal){
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('rm-modal-open');
    }
    document.querySelectorAll('.btn-modify').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            open(btn.getAttribute('data-rm-id'));
        });
    });
    document.querySelectorAll('.rm-modal').forEach(function(m){
        m.querySelectorAll('[data-rm-close]').forEach(function(el){
            el.addEventListener('click', function(){ close(m); });
        });
    });
})();
function fwrite_submit(f){ return true; }
</script>
<?php endif; ?>
