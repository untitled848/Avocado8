<?php
/**
 * 타임라인 글 상세 페이지
 * 게시판 호환 구조
 * 
 * AJAX 요청시 (?ajax=1) 모달용 HTML만 반환
 * focus_id 파라미터: 특정 댓글 기준으로 스레드 표시
 */
include_once('./_common.php');

$wr_id = ses($_REQUEST, 'wr_id', 0, 'int');
$focus_id = ses($_REQUEST, 'focus_id', 0, 'int');  // 새 댓글 포커스용
$is_ajax = ses($_REQUEST, 'ajax', 0, 'int');

// focus_id가 있으면 해당 댓글 기준으로 스레드 표시
if ($focus_id > 0) {
    $wr_id = $focus_id;
}

if (!$wr_id) {
    if ($is_ajax) {
        echo '<div class="tl-error">글을 찾을 수 없습니다.</div>';
    } else {
        alert('글을 찾을 수 없습니다.', './index.php?tl_table='.$tl_table);
    }
    exit;
}

// 글 조회
$write = get_timeline_post($tl_table, $wr_id, $character['ch_id']);

if (!$write) {
    if ($is_ajax) {
        echo '<div class="tl-error">글을 찾을 수 없습니다.</div>';
    } else {
        alert('글을 찾을 수 없습니다.', './index.php?tl_table='.$tl_table);
    }
    exit;
}

// 멘션 읽음 처리
mark_timeline_mentions_read($tl_table, $character['ch_id']);

// 변수 설정 (스킨에서 사용)
$row = $write;
$my_ch_id = $character['ch_id'];
$is_mine = ($write['ch_id'] == $my_ch_id);

// AJAX 요청이면 HTML만 반환
if ($is_ajax) {
    // 스킨 사용
    if (file_exists($timeline_skin_path.'/view_detail.skin.php')) {
        include($timeline_skin_path.'/view_detail.skin.php');
    }
    exit;
}

// 일반 페이지
$g5['title'] = h(get_timeline_row_name($write)).'의 글 - '.$timeline['tl_subject'];
include_once(G5_PATH.'/_head.php');
?>

<?php timeline_print_css_head(); ?>

<div class="timeline-container timeline-view">
    <div class="tl-view-header">
        <a href="./index.php?tl_table=<?php echo $tl_table; ?>" class="tl-back-btn">&larr; 목록으로</a>
    </div>
    
    <div class="tl-view-content">
        <?php
        if (file_exists($timeline_skin_path.'/view_detail.skin.php')) {
            include($timeline_skin_path.'/view_detail.skin.php');
        }
        ?>
    </div>
</div>

<script>
var tlTable = '<?php echo $tl_table; ?>';
var myChId = <?php echo (int)$character['ch_id']; ?>;
var tlConfig = {
    isStandalonePage: true,
    contentMaxLength: <?php echo (int)ses($timeline, 'tl_content_max', 500, 'int'); ?>,
    imageMaxCount: <?php echo (int)ses($timeline, 'tl_image_count', 4, 'int'); ?>,
    useImage: <?php echo ses($timeline, 'tl_use_image', 0, 'int') ? 'true' : 'false'; ?>,
    focusWrId: <?php echo (int)ses($write, 'focused_id', 0, 'int'); ?>
};
if (tlConfig.focusWrId && 'scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
}
(function() {
    var id = parseInt(tlConfig.focusWrId, 10) || 0;
    if (!id) return;
    var el = document.getElementById('tl_c_' + id);
    if (!el) return;
    var block = (el.offsetHeight > window.innerHeight * 0.8) ? 'start' : 'center';
    el.scrollIntoView({ block: block });
})();
</script>
<script src="<?php echo G5_URL; ?>/timeline/timeline.common.js"></script>

<?php
include_once(G5_PATH.'/_tail.php');
