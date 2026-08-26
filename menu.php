<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 테마 menu.php 있으면 해당 파일에서 처리
if (function_exists('theme_include') && ($_p = theme_include('menu', 'menu.php'))) { include $_p; return; }

// cf_menu_text / cf_mobile_menu_text 가 설정되어 있으면 우선 출력
$_menu_html = '';
if (defined('G5_IS_MOBILE') && G5_IS_MOBILE && !empty($config['cf_mobile_menu_text'])) {
    $_menu_html = $config['cf_mobile_menu_text'];
} elseif (!empty($config['cf_menu_text'])) {
    $_menu_html = $config['cf_menu_text'];
}
if ($_menu_html !== '') {
    echo $_menu_html;
    return;
}

$menu_content = function_exists('get_site_content') ? get_site_content('site_menu') : '';
if ($menu_content) {
    echo $menu_content;
} else {
?>
<header id="header">
    <div class="fix-layout">
        <!-- 로고 영역 -->
        <h1 id="logo">
            <a href="<?php echo G5_URL; ?>/main.php">
                <?php echo $logo_data; ?>
            </a>
        </h1>

        <!-- 모바일 메뉴 열고 닫기 버튼 -->
        <a href="#gnb" id="gnb_control_box">
            <img src="<?php echo G5_IMG_URL; ?>/ico_menu_control_pannel.png" alt="메뉴열고닫기">
        </a>
        <script>
        (function($){
            $('#gnb_control_box').on('click', function(){
                $('body').toggleClass('open-gnb');
                return false;
            });
        })(jQuery);
        </script>
        <!-- // 모바일 메뉴 열고 닫기 버튼 -->

        <div id="gnb">
           <ul id="no_design_gnb">
                <li><a href="<?php echo G5_URL; ?>/notice">공지</a></li>
                <li><a href="<?php echo G5_URL; ?>/world">세계관</a></li>
                <li><a href="<?php echo G5_URL; ?>/member">멤버목록</a></li>
                <li><a href="<?php echo G5_URL; ?>/member/ready.php">신청자목록</a></li>
                <li><a href="<?php echo G5_URL; ?>/bbs/board.php?bo_table=test">테스트자비</a></li>
                <li><a href="<?php echo G5_URL; ?>/timeline">타임라인</a></li>
                <li><a href="<?php echo G5_URL; ?>/shop">상점</a></li>
                <li><a href="<?php echo G5_URL; ?>/mypage">마이페이지</a></li>
            </ul>
            <?php include G5_PATH.'/templete/txt.bgm.php'; ?>
        </div>
    </div>
</header>
   
<?php } ?>
