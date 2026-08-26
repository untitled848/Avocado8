<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
?>
<style>
    @import url('<?php echo G5_URL;?>/theme/timeline/css/menu.css');
</style>
<header id="header">
    <ul class="main-menu">
        <li><a href="<?php echo G5_URL; ?>/timeline/" data-tooltip="타임라인" aria-label="타임라인"><span class="material-icons" aria-hidden="true">home</span></a></li>
        <li><a href="<?php echo G5_URL; ?>/member/" data-tooltip="프로필" aria-label="프로필"><span class="material-icons" aria-hidden="true">person</span></a></li>
        <?php if($is_member){?>
         <li><a href="<?php echo G5_URL; ?>/mypage/" data-tooltip="마이페이지" aria-label="마이페이지"><span class="material-icons" aria-hidden="true">settings</span></a></li>
         <li><a href="<?php echo G5_URL; ?>/bbs/logout.php" data-tooltip="로그아웃" aria-label="로그아웃"><span class="material-icons" aria-hidden="true">logout</span></a></li>
        <?php }else{?>
        <li><a href="<?php echo G5_URL; ?>/bbs/login.php" data-tooltip="로그인" aria-label="로그인"><span class="material-icons" aria-hidden="true">login</span></a></li>
        <?php if (!empty($is_add_register)) { ?>
        <li><a href="<?php echo G5_URL; ?>/bbs/register.php" data-tooltip="회원가입" aria-label="회원가입"><span class="material-icons" aria-hidden="true">person_add</span></a></li>
        <?php } ?>
        <?php } ?>
        <?php if($is_admin){?> <li><a href="<?php echo G5_URL; ?>/adm/" target="_blank" data-tooltip="관리자" aria-label="관리자"><span class="material-icons" aria-hidden="true">key</span></a></li> 
        <?php }?>
    </ul>
    <script>
    (function(){
        var menu = document.querySelector('#header .main-menu');
        if (!menu) return;

        var tip = document.createElement('span');
        tip.className = 'main-menu-tip';
        tip.setAttribute('aria-hidden', 'true');

        function hideTip(){
            tip.classList.remove('is-on');
            if (tip.parentNode) tip.parentNode.removeChild(tip);
        }

        function showTip(anchor){
            var text = anchor.getAttribute('data-tooltip');
            if (!text) return;
            tip.textContent = text;
            anchor.appendChild(tip);
            // reflow so transition plays
            void tip.offsetWidth;
            tip.classList.add('is-on');
        }

        menu.querySelectorAll('a[data-tooltip]').forEach(function(anchor){
            anchor.addEventListener('mouseenter', function(){ showTip(anchor); });
            anchor.addEventListener('mouseleave', hideTip);
            anchor.addEventListener('focus', function(){ showTip(anchor); });
            anchor.addEventListener('blur', hideTip);
        });
    })();
    </script>
</header>