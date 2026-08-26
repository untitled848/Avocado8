<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if (function_exists('theme_include') && ($_p = theme_include('tail', 'tail.php'))) { include $_p; return; }
?>
    </div>
</section>

<a href="#header" id="goto_top">
    <img src="<?php echo G5_IMG_URL; ?>/btn_top.png" alt="위로가기">
</a>

<script>
jQuery(function($){
    $('#goto_top').on('click', function(){
        $('html, body').animate({scrollTop:0}, 800);
        return false;
    });
});
</script>

<script src="<?php echo G5_JS_URL; ?>/swiper.js"></script>
<script src="<?php echo G5_JS_URL; ?>/_custom.js"></script>

<?php
// 호출/알림 폴링 — call_polling.php 내부에서 ct_use 합으로 자체 스킵 여부 결정
include_once G5_PATH.'/ajax/call_polling.php';
include_once G5_PATH.'/ajax/inventory_popup.php';
include_once G5_PATH.'/tail.sub.php';
