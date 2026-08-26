<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
if (!defined('G5_IS_ADMIN') && function_exists('theme_include') && ($_p = theme_include('tail_sub', 'tail.sub.php'))) { include $_p; return; }
unset($ch, $mb, $row, $row2, $character, $member);

?>
<?php if (function_exists('mdl_do_action')) mdl_do_action('tail'); ?>
</body>
</html>
<?php echo html_end(); ?>

