<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/member.css">', 0);

// 그리드 설정 폴백
if (!isset($member_thumb_w))    $member_thumb_w    = 120;
if (!isset($member_thumb_h))    $member_thumb_h    = 160;
if (!isset($member_grid_mode))  $member_grid_mode  = 'auto-fill';
if (!isset($member_grid_count)) $member_grid_count = 4;
if (!isset($npc_mode))          $npc_mode          = 'split';
if (!isset($npc_pos))           $npc_pos           = 'top';
if (!isset($npc_list))          $npc_list          = array();

if ($member_grid_mode === 'fixed') {
    $grid_tpl = 'repeat('.(int)$member_grid_count.', 1fr)';
} else {
    $grid_tpl = 'repeat('.htmlspecialchars($member_grid_mode, ENT_QUOTES).', minmax('.(int)$member_thumb_w.'px, 1fr))';
}

/** 캐릭터 목록을 그리드 ul 로 렌더링 */
if (!function_exists('_render_member_grid')) {
function _render_member_grid($ch_list) {
    global $member_thumb_w, $member_thumb_h, $_view_time;
    echo '<ul class="ui-thumb-grid">';
    if (count($ch_list) === 0) {
        echo '<li class="no-data" style="grid-column:1/-1;">등록된 멤버가 없습니다.</li>';
    } else {
        foreach ($ch_list as $ch) { ?>
        <li>
            <div class="item theme-box ui-thumb-item">
                <div class="ui-thumb" style="aspect-ratio:<?php echo (int)$member_thumb_w; ?>/<?php echo (int)$member_thumb_h; ?>;">
                    <a href="./viewer.php?ch_id=<?php echo $ch['ch_id']?>&time=<?php echo $_view_time; ?>">
                        <?php if ($ch['ch_thumb']) { ?>
                            <img src="<?php echo htmlspecialchars($ch['ch_thumb'], ENT_QUOTES)?>" alt="">
                        <?php } ?>
                    </a>
                </div>
                <div class="ui-profile">
                    <a href="<?php echo G5_BBS_URL?>/memo_form.php?me_recv_mb_id=<?php echo $ch['mb_id']?>" class='send_memo'>
                        <strong><?php echo htmlspecialchars($ch['ch_name'], ENT_QUOTES)?></strong>
                    </a>
                    <?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('member_list_item_extra', '', array('ch' => $ch)); ?>
                </div>
            </div>
        </li>
        <?php }
    }
    echo '</ul>';
}
}
?>
<style>
.ui-thumb-grid {
    display: grid;
    grid-template-columns: <?php echo $grid_tpl; ?>;
    gap: 12px;
    list-style: none;
    padding: 0;
    margin: 0;
}
.ui-thumb-item .ui-thumb img { width: 100%; height: 100%; object-fit: cover; }
.ml-npc-section { margin-bottom: 16px; }
.ml-npc-section .ml-npc-label { font-weight: bold; margin-bottom: 6px; text-align: center; }
.ml-npc-section .ui-thumb-grid { justify-content: center; display:flex; }
.ml-npc-section .ui-thumb-grid > li { width: <?php echo (int)$member_thumb_w + 30; ?>px; }
</style>

<?php

// 시기 탭 (멤버 목록은 본인 여부 무관 — false)
print_time_tabs($_view_time, (bool)$is_admin, false);


// NPC 별도 섹션 출력 조건:
//   - split 모드: 기본 (기존 동작)
//   - merge 모드 + npc_pos !== 'inline' : 통합이지만 위치 지정 → 별도 분리
$_npc_show_separate = (count($npc_list) > 0) && (
    $npc_mode === 'split' ||
    ($npc_mode === 'merge' && $npc_pos !== 'inline')
);
?>

<?php if ($_npc_show_separate && $npc_pos === 'top') { ?>
    <div class="memberWrap">
        <div class="member-box ml-npc-section">
            <div class="ml-npc-label">NPC</div>
            <?php _render_member_grid($npc_list); ?>
        </div>
    </div>
<?php } ?>

<div class="memberWrap">

<?php for ($i = 0; $i < count($list); $i++) {
    $ch_list = $list[$i];
?>
<div class="member-box">
    <?php if (is_array($side[$i]) && $side[$i]['si_name']) {
        echo "<div class='title'>".htmlspecialchars($side[$i]['si_name'], ENT_QUOTES)."</div>";
    } ?>
    <?php _render_member_grid($ch_list); ?>
</div>
<?php } ?>
</div>

<?php if ($_npc_show_separate && $npc_pos === 'bottom') { ?>
    <div class="memberWrap">
        <div class="member-box ml-npc-section">
            <div class="ml-npc-label">NPC</div>
            <?php _render_member_grid($npc_list); ?>
        </div>
    </div>
<?php } ?>

<script>
$('.send_memo').on('click', function() {
    var target = $(this).attr('href');
    window.open(target, 'memo', "width=500, height=300");
    return false;
});
</script>
