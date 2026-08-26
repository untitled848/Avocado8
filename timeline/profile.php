<?php
/**
 * 타임라인 프로필 페이지
 * 게시판 호환 구조
 * 
 * 특정 캐릭터의 타임라인 글 목록
 */
include_once('./_common.php');

$target_ch_id = ses($_REQUEST, 'ch_id', 0, 'int');

if (!$target_ch_id) {
    alert('캐릭터 정보가 없습니다.', './index.php?tl_table='.$tl_table);
}

// 대상 캐릭터 정보
$target_character = sql_fetch("SELECT * FROM {$g5['character_table']} WHERE ch_id = '{$target_ch_id}'");

if (!$target_character['ch_id']) {
    alert('존재하지 않는 캐릭터입니다.', './index.php?tl_table='.$tl_table);
}

$tl_safe = sql_escape_string($tl_table);
$target_tu = sql_fetch("
    SELECT * FROM {$g5['timeline_user_table']}
    WHERE tl_table = '{$tl_safe}' AND ch_id = '{$target_ch_id}'
", false);
if (!is_array($target_tu)) {
    $target_tu = array();
}

$display_name = ses($target_tu, 'tu_name', '', 'raw');
if ($display_name === '') {
    $display_name = ses($target_character, 'ch_name', '', 'raw');
}
$display_handle = function_exists('timeline_html_men_handle')
    ? timeline_html_men_handle($tl_table, $target_ch_id)
    : '';

$g5['title'] = h($display_name).' - '.$timeline['tl_subject'];
include_once(G5_PATH.'/_head.php');

// 프로필 이미지
$target_profile = get_timeline_thumb($target_ch_id, 1);

// 글 수 조회 (스크립트 제외)
$write_table = get_timeline_write_table($tl_table);
$script_sql = '';
if (function_exists('table_has_column') && table_has_column($write_table, 'wr_script')) {
    $script_sql = " AND IFNULL(wr_script,0) = 0";
}
$post_count = sql_fetch("SELECT COUNT(*) AS cnt FROM {$write_table} WHERE ch_id = '{$target_ch_id}' AND wr_is_comment = 0 AND wr_deleted = 0{$script_sql}");
$reply_count = sql_fetch("SELECT COUNT(*) AS cnt FROM {$write_table} WHERE ch_id = '{$target_ch_id}' AND wr_is_comment = 1 AND wr_deleted = 0{$script_sql}");

// 본인 여부
$is_me = ($target_ch_id == $character['ch_id']);

// 내 렌더링 모드 설정
$my_render_mode = get_timeline_user_render_mode($tl_table, (int)ses($character, 'ch_id', 0, 'int'));

// 탭
// profile_public = 퍼블릭(원글만), profile / profile_replies = 전체(원글+답글)
// profile_media = 이미지 첨부 글(원글+답글, 스크립트 제외)
// bookmarks = 내 북마크 (본인 프로필만)
$tab = ses($_GET, 'tab', 'profile_public', 'raw');
if ($tab === 'profile_replies') {
    $tab = 'profile';
}
$valid_profile_tabs = array('profile', 'profile_public', 'profile_media');
if ($is_me) {
    $valid_profile_tabs[] = 'bookmarks';
}
if (!in_array($tab, $valid_profile_tabs, true)) {
    $tab = 'profile_public';
}
?>

<?php timeline_print_css_head(); ?>

<!-- 타임라인 공통 JS -->
<script>
var tlTable = '<?php echo $tl_table; ?>';
var currentTab = '<?php echo $tab; ?>';
var myChId = <?php echo (int)$character['ch_id']; ?>;
var tlConfig = {
    isStandalonePage: false,
    targetChId: <?php echo $target_ch_id; ?>,
    contentMaxLength: 500,
    imageMaxCount: <?php echo (int)ses($timeline, 'tl_image_count', 4, 'int'); ?>,
    useImage: <?php echo ses($timeline, 'tl_use_image', 0, 'int') ? 'true' : 'false'; ?>
};
</script>
<script src="<?php echo G5_URL; ?>/timeline/timeline.common.js"></script>

<div class="timeline-container timeline-profile">
    <!-- 프로필 헤더 -->
    <div class="tl-profile-header">
        <?php 
        $header_img = ses($target_tu, 'tu_header', '', 'raw');
        ?>
    
        <div class="tl-profile-header-img" <?php if ($header_img) { ?> style="background-image: url(<?php echo h($header_img); ?>);"<?php } ?>></div>

        
        <a href="./index.php?tl_table=<?php echo $tl_table; ?>" class="tl-back-btn">&larr; 타임라인</a>
        
        <div class="tl-profile-info">
            <div class="tl-profile-avatar">
                <img src="<?php echo $target_profile; ?>" alt="<?php echo h($display_name); ?>">
            </div>
            <div class="tl-profile-details">
                <div class="tl-profile-name-row">
                    <h1 class="tl-profile-name"><?php echo h($display_name); ?></h1>
                    <?php echo $display_handle; ?>
                    <?php
                    $tu_profile = trim(ses($target_tu, 'tu_profile', '', 'raw'));
                    if ($tu_profile !== '') {
                    ?>
                    <a href="<?php echo h($tu_profile); ?>">프로필</a>
                    <?php } ?>
                    <?php if ($is_me) { ?>
                    <a href="./settings.php?tl_table=<?php echo $tl_table; ?>" class="tl-settings-link" title="내 설정" aria-label="내 설정"><span class="material-icons" aria-hidden="true">settings</span></a>
                    <?php } ?>
                </div>
                <?php 
                $display_bio = ses($target_tu, 'tu_bio', '', 'raw');
                if ($display_bio) { 
                ?>
                <p class="tl-profile-bio"><?php echo nl2br(h($display_bio)); ?></p>
                <?php } ?>
                <div class="tl-profile-stats">
                    <span><strong><?php echo number_format($post_count['cnt']); ?></strong> 글</span>
                    <span><strong><?php echo number_format($reply_count['cnt']); ?></strong> 답글</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 탭 -->
    <div class="tl-tabs">
        <a href="?tl_table=<?php echo $tl_table; ?>&ch_id=<?php echo $target_ch_id; ?>&tab=profile_public" class="tl-tab <?php echo $tab == 'profile_public' ? 'active' : ''; ?>">퍼블릭</a>
        <a href="?tl_table=<?php echo $tl_table; ?>&ch_id=<?php echo $target_ch_id; ?>&tab=profile" class="tl-tab <?php echo $tab == 'profile' ? 'active' : ''; ?>">전체</a>
        <a href="?tl_table=<?php echo $tl_table; ?>&ch_id=<?php echo $target_ch_id; ?>&tab=profile_media" class="tl-tab <?php echo $tab == 'profile_media' ? 'active' : ''; ?>">미디어</a>
        <?php if ($is_me) { ?>
        <a href="?tl_table=<?php echo $tl_table; ?>&ch_id=<?php echo $target_ch_id; ?>&tab=bookmarks" class="tl-tab <?php echo $tab == 'bookmarks' ? 'active' : ''; ?>">북마크</a>
        <?php } ?>
    </div>
    
    <!-- 글 목록 -->
    <div id="tl-list" class="tl-list" data-tab="<?php echo $tab; ?>">
        <div class="tl-loading">로딩 중...</div>
    </div>
    
    <div id="tl-load-more" class="tl-load-more" style="display:none;">
        <div class="tl-loading">더 불러오는 중...</div>
    </div>
    
    <div id="tl-end" class="tl-end" style="display:none;">
        더 이상 글이 없습니다.
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

<script>
// 초기 로드
document.addEventListener('DOMContentLoaded', function() {
    initInfiniteScroll();
    loadTimeline(true);
});
</script>

<?php
include_once(G5_PATH.'/_tail.php');
