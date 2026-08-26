<?php
/**
 * 타임라인 메인 페이지
 * 게시판 호환 구조 사용
 * 
 * URL: timeline/?tl_table=xxx 또는 timeline/?bo_table=xxx
 */
include_once('./_common.php');

$tl_subject = ses($timeline, 'tl_subject', '타임라인', 'raw');
$tl_content_max = ses($timeline, 'tl_content_max', 500, 'int');
$tl_use_image = ses($timeline, 'tl_use_image', 0, 'int');

$g5['title'] = $tl_subject;
include_once(G5_PATH.'/_head.php');

// 프로필 이미지 경로 (스킨에서 사용)
$my_ch_id = ses($character, 'ch_id', 0, 'int');

// 현재 탭
$tab = ses($_GET, 'tab', 'all', 'raw');
if ($tab === 'bookmarks' && $my_ch_id) {
    goto_url('./profile.php?tl_table='.urlencode($tl_table).'&ch_id='.$my_ch_id.'&tab=bookmarks');
}
$valid_tabs = array('all', 'public', 'mentions');
if (!in_array($tab, $valid_tabs)) {
    $tab = 'all';
}

// 멘션 탭 진입 시 읽음 처리 (배지가 폴링으로 다시 뜨지 않도록)
if ($tab === 'mentions' && $my_ch_id && function_exists('mark_timeline_mentions_read')) {
    mark_timeline_mentions_read($tl_table, $my_ch_id);
}
$my_profile_img = get_timeline_thumb($my_ch_id, 1);
if(!$my_profile_img){
    $my_profile_img = G5_URL.'/img/no_profile.png';
}
// 쿼리스트링 (tl_table 포함)
$tl_qstr = 'tl_table='.$tl_table;
?>

<?php timeline_print_css_head(); ?>

<!-- 타임라인 공통 JS - 스킨보다 먼저 로드해야 함 -->
<script>
var tlTable = '<?php echo $tl_table; ?>';
var currentTab = '<?php echo $tab; ?>';
var lastCheckTime = <?php echo G5_SERVER_TIME; ?>;
var myChId = <?php echo $my_ch_id; ?>;
var tlConfig = {
    isStandalonePage: false,
    targetChId: 0,
    contentMaxLength: <?php echo $tl_content_max; ?>,
    imageMaxCount: <?php echo (int)ses($timeline, 'tl_image_count', 4, 'int'); ?>,
    useImage: <?php echo $tl_use_image ? 'true' : 'false'; ?>,
    pollInterval: <?php echo (int)get_timeline_poll_interval_ms($timeline, 'tl_poll_interval', 10000); ?>
};
</script>
<script src="<?php echo G5_URL; ?>/timeline/timeline.common.js"></script>

<?php if($my_ch_id&&$can_write){?>
    <a href="./profile.php?tl_table=<?php echo $tl_table; ?>&ch_id=<?php echo $my_ch_id; ?>" class="tl-back-btn">프로필</a>
<?php } ?>
<?php if ($is_admin) { ?>
    <a href="./script_write.php?tl_table=<?php echo $tl_table; ?>" class="tl-back-btn" onclick="return openScriptWrite(this.href);" style="left:58px;">공지</a>
    <a href="./theme_vars.php?tl_table=<?php echo $tl_table; ?>" class="tl-back-btn" style="left:<?php echo ($my_ch_id&&$can_write) ? '101px' : '5px'; ?>;">테마</a>
<?php } ?>

<div class="timeline-container" data-tl-table="<?php echo h($tl_table); ?>">
    <!-- 글쓰기 영역 (스킨) -->
    <?php 
    // 권한 체크 (사용자 목록 기반)
    // $can_write는 _common.php에서 설정됨
    
    if ($can_write && file_exists($timeline_skin_path.'/write.skin.php')) {
        include($timeline_skin_path.'/write.skin.php');
    } ?>
    
    <!-- 새 글 알림 바 -->
    <div id="tl-new-posts-bar" class="tl-new-posts-bar" style="display:none;" onclick="loadNewPosts()">
        <span id="tl-new-count">0</span>개의 새 글이 있습니다
    </div>
    
    <!-- 검색 영역 -->
    <div class="tl-search-area">
        <form id="tl-search-form" class="tl-search-form" onsubmit="return submitSearch(event)">
            <div class="tl-search-input-wrap">
                <svg class="tl-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                <input type="text" id="tl-search-input" name="stx" placeholder="글 검색..." autocomplete="off">
                <button type="button" id="tl-search-clear" class="tl-search-clear" style="display:none;" onclick="clearSearch()">&times;</button>
                <button type="submit" class="tl-search-btn">검색</button>
            </div>
        </form>
    </div>
    
    <!-- 탭 네비게이션 -->
    <div class="tl-tabs">
        <a href="?<?php echo $tl_qstr; ?>&tab=all" class="tl-tab <?php echo $tab == 'all' ? 'active' : ''; ?>">전체</a>
        <a href="?<?php echo $tl_qstr; ?>&tab=public" class="tl-tab <?php echo $tab == 'public' ? 'active' : ''; ?>">퍼블릭</a>
        <a href="?<?php echo $tl_qstr; ?>&tab=mentions" class="tl-tab <?php echo $tab == 'mentions' ? 'active' : ''; ?>">
            멘션
            <span id="tl-mention-badge" class="tl-badge" style="display:none;">0</span>
        </a>
        <?php if (timeline_dm_enabled($timeline, $tl_table)) { ?>
        <a href="./dm.php?<?php echo $tl_qstr; ?>" class="tl-tab">DM</a>
        <?php } ?>
        <?php 
        $user_count = count_timeline_users($tl_table);
        if ($user_count > 0) { 
        ?>
        <a href="./users.php?<?php echo $tl_qstr; ?>" class="tl-tab tl-tab-users">
            멤버 <span class="tl-user-count"><?php echo $user_count; ?></span>
        </a>
        <?php } ?>
    </div>
    
    <?php if ($tab === 'mentions') { ?>
    <div class="tl-mention-toolbar">
        <label class="tl-unreplied-switch">
            <span>미답장</span>
            <span class="tl-switch tl-switch-sm">
                <input type="checkbox" id="tl-unreplied-switch">
                <span class="tl-switch-slider"></span>
            </span>
        </label>
    </div>
    <?php } ?>
    
    <!-- 타임라인 목록 -->
    <div id="tl-list" class="tl-list" data-tab="<?php echo $tab; ?>">
        <div class="tl-loading">로딩 중...</div>
    </div>
    
    <!-- 무한스크롤 로딩 인디케이터 -->
    <div id="tl-load-more" class="tl-load-more" style="display:none;">
        <div class="tl-loading">더 불러오는 중...</div>
    </div>
    
    <!-- 더 이상 글 없음 -->
    <div id="tl-end" class="tl-end" style="display:none;">
        더 이상 글이 없습니다.
    </div>
    
    <div id="tl-unreplied-empty" class="tl-empty" style="display:none;">
        미답장 멘션이 없습니다.
    </div>
</div>

<!-- 글 상세 모달 -->
<div id="tl-modal" class="tl-modal" style="display:none;">
    <div class="tl-modal-backdrop" onclick="closeModal()"></div>
    <div class="tl-modal-content">
        <button class="tl-modal-close" onclick="closeModal()">&times;</button>
        <div id="tl-modal-body" class="tl-modal-body"></div>
    </div>
</div>

<!-- 이미지 뷰어 모달 -->
<div id="tl-image-modal" class="tl-image-modal" style="display:none;">
    <div class="tl-image-modal-backdrop" onclick="closeImageModal()"></div>
    <button class="tl-image-modal-close" onclick="closeImageModal()">&times;</button>
    <button class="tl-image-nav tl-image-prev" onclick="prevImage()">&lsaquo;</button>
    <button class="tl-image-nav tl-image-next" onclick="nextImage()">&rsaquo;</button>
    <div class="tl-image-modal-content">
        <div class="tl-image-zoom-stage" id="tl-image-zoom-stage">
            <img id="tl-image-modal-img" src="" alt="">
        </div>
    </div>
    <div class="tl-image-zoom-hint">휠/핀치로 확대 · 드래그로 이동</div>
    <div class="tl-image-counter"><span id="tl-image-current">1</span> / <span id="tl-image-total">1</span></div>
</div>

<script>
// 초기 로드
document.addEventListener('DOMContentLoaded', function() {
    initComposerForm();
    initInfiniteScroll();
    initUnrepliedFilter();
    loadTimeline(true);
    startPolling();
<?php if ($tab === 'mentions') { ?>
    if (typeof window.stopCallAlarm === 'function') {
        window.stopCallAlarm(true);
    }
<?php } ?>
});
</script>

<?php
include_once(G5_PATH.'/_tail.php');
