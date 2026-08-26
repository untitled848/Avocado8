<?php
/**
 * 타임라인 사용자 목록 페이지
 * 등록된 사용자들의 아이콘, 이름, 바이오 표시
 */
include_once('./_common.php');

$g5['title'] = '사용자 목록 - '.$timeline['tl_subject'];
include_once(G5_PATH.'/_head.php');

// 등록된 사용자 목록 가져오기
$timeline_users = get_timeline_users($tl_table);
$user_count = count($timeline_users);

// 내 렌더링 모드 설정 (프로필 페이지와 동일)
$my_render_mode = get_timeline_user_render_mode($tl_table, (int)ses($character, 'ch_id', 0, 'int'));
?>

<?php timeline_print_css_head(); ?>

<div class="timeline-container">
    <div class="tl-users-container">
        <!-- 헤더 -->
        <div class="tl-users-header">
            <a href="./index.php?tl_table=<?php echo $tl_table; ?>" class="tl-back-btn">
                <span>&larr;</span> 타임라인
            </a>
         </div>
        
        <?php if ($user_count == 0) { ?>
        <!-- 사용자 없음 -->
        <div class="tl-users-empty">
            <div class="icon">👥</div>
            <p>등록된 사용자가 없습니다.</p>
            <p class="tl-users-empty-hint">이 타임라인은 모든 회원이 이용할 수 있습니다.</p>
        </div>
        <?php } else { ?>
        <!-- 사용자 그리드 -->
        <div class="tl-users-grid">
            <?php foreach ($timeline_users as $user) { 
                $ch_id = ses($user, 'ch_id', 0, 'int');
                if (!$ch_id) continue;

                // 프로필 이미지
                $avatar = get_timeline_thumb($ch_id, 1);

                // 이름 / 멘션 / 바이오 (timeline_user)
                $ch_name = h(ses($user, 'tu_name', '', 'raw'));
                if ($ch_name === '') {
                    $ch_name = h(ses($user, 'ch_name', '', 'raw'));
                }

                $mention_name = ses($user, 'tu_men_id', '', 'raw');
                if ($mention_name) {
                    $mention_name = '@' . h($mention_name);
                }

                $bio = nl2br(h(ses($user, 'tu_bio', '', 'raw')));
                $tu_profile = trim(ses($user, 'tu_profile', '', 'raw'));
            ?>
            <div class="tl-user-card">
                <div class="tl-user-card-header">
                    <img src="<?php echo $avatar; ?>" alt="<?php echo $ch_name; ?>" class="tl-user-avatar" onerror="this.src='<?php echo G5_IMG_URL; ?>/no_profile.png';">
                    <div class="tl-user-info">
                        <h3 class="tl-user-name"><?php echo $ch_name; ?></h3>
                        <?php if ($mention_name) { ?>
                        <span class="tl-user-mention"><?php echo $mention_name; ?></span>
                        <?php } ?>
                    </div>
                </div>
                
                <div class="tl-user-bio"><?php echo $bio; ?></div>
                
                <div class="tl-user-links">
                    <a href="./profile.php?tl_table=<?php echo $tl_table; ?>&amp;ch_id=<?php echo $ch_id; ?>" class="tl-user-link timeline-link">
                        홈
                    </a>
                    <?php if ($tu_profile !== '') { ?>
                    <a href="<?php echo h($tu_profile); ?>" class="tl-user-link profile-link">
                        프로필
                    </a>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
</div>

<?php
include_once(G5_PATH.'/_tail.php');
?>
