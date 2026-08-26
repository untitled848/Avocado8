<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if (!function_exists('det_render_stat_radar')) {
    function det_render_stat_radar($items) {
        $n = count($items);
        if ($n < 1) {
            return '';
        }

        if ($n < 3) {
            $html = '<div class="stat-radar-fallback">';
            foreach ($items as $it) {
                $pct = (int)round($it['ratio'] * 100);
                $html .= '<div class="stat-bar-row"><span class="n">'.htmlspecialchars($it['name'], ENT_QUOTES, 'UTF-8').'</span>';
                $html .= '<p><span style="width:'.$pct.'%;"></span></p></div>';
            }
            $html .= '</div>';
            return $html;
        }

        $cx = 100;
        $cy = 100;
        $r  = 62;
        $lr = 68;
        $grid = array();
        $value = array();
        $labels = array();
        $step = 2 * M_PI / $n;

        for ($i = 0; $i < $n; $i++) {
            $ang = -M_PI / 2 + $step * $i;
            $cos = cos($ang);
            $sin = sin($ang);
            $grid[] = round($cx + $r * $cos).','.round($cy + $r * $sin);
            $value[] = round($cx + $r * $items[$i]['ratio'] * $cos).','.round($cy + $r * $items[$i]['ratio'] * $sin);
            $labels[] = array(
                round($cx + $lr * $cos),
                round($cy + $lr * $sin),
                htmlspecialchars($items[$i]['name'], ENT_QUOTES, 'UTF-8'),
            );
        }

        $html  = '<svg class="stat-radar-svg" viewBox="0 0 200 200" aria-label="캐릭터 스탯">';
        $html .= '<polygon class="grid" points="'.implode(' ', $grid).'"/>';
        $html .= '<polygon class="value" points="'.implode(' ', $value).'"/>';
        foreach ($labels as $lb) {
            $anchor = ($lb[0] > $cx + 8) ? 'start' : (($lb[0] < $cx - 8) ? 'end' : 'middle');
            $html .= '<text class="label-name" x="'.$lb[0].'" y="'.$lb[1].'" text-anchor="'.$anchor.'">'.$lb[2].'</text>';
        }
        $html .= '</svg>';
        return $html;
    }
}

$det_stats = array();
if (!empty($article['ad_use_status']) && isset($status) && is_array($status)) {
    for ($i = 0; $i < count($status); $i++) {
        $has = $status[$i]['has'] ? $status[$i]['has'] : $status[$i]['min'];
        $max = isset($status[$i]['max']) ? $status[$i]['max'] : 0;
        $det_stats[] = array(
            'name'  => isset($status[$i]['name']) ? $status[$i]['name'] : '',
            'has'   => $has,
            'max'   => $max,
            'ratio' => $max ? max(0, min(1, $has / $max)) : 0,
        );
    }
}
$det_stat_n = count($det_stats);
$det_stat_svg = $det_stat_n ? det_render_stat_radar($det_stats) : '';

$viewer_info = isset($viewer_info) && is_string($viewer_info) ? $viewer_info : '';
$viewer_bag = isset($viewer_bag) && is_string($viewer_bag) ? $viewer_bag : '';
$viewer_relation = isset($viewer_relation) && is_string($viewer_relation) ? $viewer_relation : '';
if (!isset($viewer_extra_tabs) || !is_array($viewer_extra_tabs)) {
    $viewer_extra_tabs = array();
}
if (!isset($viewer_tools) || !is_array($viewer_tools)) {
    $viewer_tools = array();
}
$_det_tab = isset($_GET['tabs']) ? preg_replace('/[^a-zA-Z0-9_]/', '', (string)$_GET['tabs']) : '';
$det_open = 'prof';
if ($_det_tab === 'bag') {
    $det_open = 'equip';
} elseif ($_det_tab === 'relation') {
    $det_open = 'relation';
} elseif ($_det_tab !== '' && $_det_tab !== 'info') {
    foreach ($viewer_extra_tabs as $_xtab) {
        if (!empty($_xtab['id']) && $_xtab['id'] === $_det_tab) {
            $det_open = 'mv_'.$_xtab['id'];
            break;
        }
    }
}
unset($_det_tab, $_xtab);
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Mrs+Saint+Delafield&display=swap" rel="stylesheet">

<style>
@import url(//fonts.googleapis.com/earlyaccess/nanummyeongjo.css);
@import url("<?php echo G5_URL;?>/theme/timeline/member/skin/member.css");

</style>

<div class="background">
    <a href="<?php echo G5_URL;?>/member" class="back-btn">
        <span class="material-icons">arrow_back</span>
    </a>
    <div class="prof-area">
        <div class="cha-area">
            <div class="info-area back">
                <div class="axis">
                    <div class="enname-area">
                        <?php echo $ch['en_name']?>
                    </div>
                    <div class="name-area">
                        <?php echo $ch['ch_name']?>
                    </div>
                </div> 
            </div>

            <div class="body-area">
                <img src="<?php echo $ch['ch_body']?>" style="max-width:<?php echo $ch['ha_companion']?>;" />
            </div>

            <div class="info-area front">
                <div class="axis">
                    <div class="info-box pers">
                        <div class="info-name">personality</div>
                            <?php echo nl2br($ch['pers']); ?>
                    </div>

                    <div class="info-box appr">
                        <div class="info-name">appearance</div>
                            <?php echo nl2br($ch['app']); ?>
                    </div>

                    <div class="info-box etc">
                        <div class="info-name">et cetera</div>
                            <?php echo nl2br($ch['etc']); ?>
                    </div>

                    <?php if ($det_stat_n) { ?>
                    <div class="info-box status">
                        <div class="info-name">status</div>
                        <ul class="stat-area">
                            <?php foreach ($det_stats as $i => $st) { ?>
                                <?php if ($i) { ?><li class="line">&nbsp;</li><?php } ?>
                                <li><span><?php echo htmlspecialchars($st['name'], ENT_QUOTES, 'UTF-8'); ?></span><?php echo (int)$st['has']; ?></li>
                            <?php } ?>
                        </ul>
                    </div>
                    <?php } ?>

                    <div class="word-area">
                    "<?php echo nl2br($ch['comment']); ?>"
                    </div>

                </div>
            </div>

        </div>
        <div class="bottom-area">
            <div class="tag-row">
                <?php if (function_exists('print_time_tabs')) { print_time_tabs($time, (bool)$is_admin, !empty($is_owner)); } ?>
                <div class="tag" id="up">상세 조사 파일</div>
            </div>
            <div class="detail-area" id="files">
                <div class="detail-inner">
                    <div class="menu-area">
                        <div class="menu-name">Profile</div>
                        <span class="material-icons det_btn" onclick="menuChange('prof');">fingerprint</span>
                        <span class="material-icons det_btn" onclick="menuChange('relation');">people</span>
                        <span class="material-icons det_btn" onclick="menuChange('equip');">inventory</span>
                        <?php
                        if (!empty($viewer_tools) && function_exists('character_viewer_tools_html')) {
                            echo character_viewer_tools_html($viewer_tools, 'det_btn det_tab');
                        }
                        foreach ($viewer_extra_tabs as $_xtab) {
                            if (!empty($_xtab['href'])) {
                                echo '<a class="det_btn det_tab" href="'.htmlspecialchars($_xtab['href'], ENT_QUOTES).'">'.htmlspecialchars($_xtab['label'], ENT_QUOTES).'</a>';
                                continue;
                            }
                            if (empty($_xtab['id'])) {
                                continue;
                            }
                            echo '<button type="button" class="det_btn det_tab" onclick="menuChange(\'mv_'.htmlspecialchars($_xtab['id'], ENT_QUOTES).'\');">'.htmlspecialchars($_xtab['label'], ENT_QUOTES).'</button>';
                        }
                        unset($_xtab);
                        ?>
                    </div>

                    <div class="tab-area">
            
                        <div class="detprof-area" id="prof"<?php echo $det_open === 'prof' ? '' : ' style="display:none;"'; ?>>
                            <div class="name codename">
                            <?php echo $ch['ch_name']?> :  <?php echo $ch['en_name']?></div>
                            <?php if ($det_stat_n) { ?>
                            <div class="name">스테이터스</div>
                            <div class="box stat-radar-box">
                                <?php echo $det_stat_svg; ?>
                            </div>
                            <?php } ?>
                            <div class="name">외모</div>
                            <div class="box"><?php echo nl2br($ch['app']); ?></div>
                            <div class="name">성격</div>
                            <div class="box"><?php echo nl2br($ch['pers']); ?></div>
                            <div class="name">기타</div>
                            <div class="box"><?php echo nl2br($ch['etc']); ?></div>
                            <div class="name">추가 정보</div>
                            <div class="box"><?php echo nl2br($ch['etc_detail']); ?></div>
                            <?php echo $viewer_info; ?>
                        </div>

                        <div class="detprof-area" id="equip"<?php echo $det_open === 'equip' ? '' : ' style="display:none;"'; ?>>
                            <div class="name codename">타이틀</div>
                                <div class="box">
                                <?php for($i=0; $i < count($title); $i++) { ?>
                                    <img src="<?php echo $title[$i]['ti_img']?>" />
                                    <?php }if($i == 0) { 
                                        echo "보유중인 타이틀이 없습니다.";
                                    }?>
                                </div>
                            <?php include(function_exists('theme_dir_file') ? theme_dir_file('inventory/list.inc.php') : (G5_PATH."/inventory/list.inc.php")); ?>
                            <?php echo $viewer_bag; ?>
                        </div>

                        <div class="detprof-area" id="relation"<?php echo $det_open === 'relation' ? '' : ' style="display:none;"'; ?>>
                                <?php include function_exists('theme_dir_file') ? theme_dir_file('member/skin/relation.skin.php') : (G5_PATH.'/member/skin/relation.skin.php'); ?>
                                <?php echo $viewer_relation; ?>
                        </div>
                        <?php
                        foreach ($viewer_extra_tabs as $_xtab) {
                            if (empty($_xtab['id']) || !empty($_xtab['href'])) {
                                continue;
                            }
                            $_xid = 'mv_'.$_xtab['id'];
                            echo '<div class="detprof-area" id="'.htmlspecialchars($_xid, ENT_QUOTES).'"'.($det_open === $_xid ? '' : ' style="display:none;"').'>';
                            echo $_xtab['html'];
                            echo '</div>';
                        }
                        unset($_xtab, $_xid);
                        ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

</div>


		

<script>
var fileBtn = false;
var $files = $('#files');

function setFilesOpen(isOpen) {
    $files.stop(true, true);
    $files.toggleClass('is-open', isOpen);
    fileBtn = isOpen;
}

$('#up').on('click', function() {
    setFilesOpen(!fileBtn);
});

function updateBodyCenterState() {
    var $img = $('.cha-area .body-area img');
    if (!$img.length) return;

    var img = $img.get(0);
    if (!img) return;

    var isMobile = window.innerWidth <= 500;
    var currentHeight = isMobile ? (img.getBoundingClientRect().height || img.offsetHeight || img.clientHeight || 0)
                                 : (img.naturalHeight || img.height || img.offsetHeight || 0);
    var isShort = currentHeight > 0 && currentHeight < window.innerHeight;

    $('.cha-area').toggleClass('is-short', isShort);
    $('.cha-area .body-area').css('overflow', isShort ? 'hidden' : 'auto');
}

$(window).on('resize load orientationchange', updateBodyCenterState);
$('.cha-area .body-area img').on('load', updateBodyCenterState);

function menuChange(type){
    if (!fileBtn) {
        setFilesOpen(true);
    }

    $('#'+type).css('display','flex');
    $('.tab-area .detprof-area').not('#'+type).css('display','none');
};
<?php if (!empty($det_open) && $det_open !== 'prof') { ?>
setFilesOpen(true);
<?php } ?>
</script>