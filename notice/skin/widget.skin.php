<?php
if (!defined('_GNUBOARD_')) exit;

global $g5, $config;

if (!function_exists('notice_get_list')) {
    include_once G5_LIB_PATH.'/notice.lib.php';
}

$list = notice_get_list(0, 0, array('widget' => true));
if (empty($list)) {
    return;
}
?>
<style>
.notice-widget { display:flex; flex-direction:column; gap:1.25em; height: 100%; overflow: auto; line-height:1.5; -webkit-overflow-scrolling:touch;}
.notice-widget-item { display:flex; flex-direction:column; gap:.5em; }
.notice-widget-title { margin:0; font-size:1.15em; font-weight:700; line-height:1.35; color:var(--d-color_default, inherit); }
.notice-widget-title a { color:inherit; text-decoration:none; }
.notice-widget-body { line-height:1.6; word-break:break-word; }
.notice-widget-body p:first-child { margin-top:0; }
.notice-widget-body p:last-child { margin-bottom:0; }
</style>
<div class="notice-widget">
<?php foreach ($list as $row) {
    $nid  = (int)$row['no_id'];
    $href = G5_URL.'/notice/?no_id='.$nid;
    $subj = htmlspecialchars((string)$row['no_subject'], ENT_QUOTES, 'UTF-8');
    $body = function_exists('notice_render_body') ? notice_render_body($row) : '';
?>
    <article class="notice-widget-item">
        <h3 class="notice-widget-title"><a href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $subj; ?></a></h3>
        <div class="notice-widget-body"><?php echo $body; ?></div>
    </article>
<?php } ?>
</div>
