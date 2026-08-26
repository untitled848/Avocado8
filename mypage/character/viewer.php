<?php
include_once('./_common.php');
include_once('./_head.php');

// ---------------- 기본 값 / 입력값 정리 ----------------

// ch_id 결정
$ch_id = (int)($_GET['ch_id'] ?? 0);
if (!$ch_id && isset($character['ch_id'])) {
    $ch_id = (int)$character['ch_id'];
}

// 탭 파라미터
$tabs = trim($_GET['tabs'] ?? '');
if ($tabs === '') {
    $tabs = 'c';
}

// 관리자 플래그 (그누보드 패턴 유지)
$is_admin = isset($is_admin) && $is_admin;

// ---------------- 캐릭터 조회 및 권한 체크 ----------------

if ($ch_id <= 0) {
    alert('캐릭터 정보가 존재하지 않습니다.');
}

if (!function_exists('get_character')) {
    alert('캐릭터 정보를 읽는 함수가 정의되어 있지 않습니다.');
}

$ch = get_character($ch_id);

if (empty($ch['ch_id'])) {
    alert('캐릭터 정보가 존재하지 않습니다.');
}

if (!isset($member['mb_id']) || ($ch['mb_id'] !== $member['mb_id'] && !$is_admin)) {
    alert('본인 소유의 캐릭터가 아닙니다.');
}

// 오너 정보
$mb = $member;

// ---------------- 시기(time) 결정 및 권한 체크 ----------------
$time = get_current_time();
$is_owner = (isset($member['mb_id']) && $ch['mb_id'] === $member['mb_id']);
if (!check_time_access($time, (int)$config['cf_time'], (bool)$is_admin, $is_owner)) {
    time_safe_redirect(G5_URL.'/mypage/character/viewer.php', array('ch_id' => $ch_id, 'tabs' => $tabs));
}

// 시기별 override 는 get_character() 에서 이미 적용됨

// ---------------- 기본 설정(프로필/스탯) 로드 ----------------

// $article (프로필/기능 사용 설정) 없을 수 있으므로 안전하게 로드
if (!isset($article) || !is_array($article)) {
    $tmp = sql_fetch("SELECT * FROM {$g5['article_default_table']} LIMIT 1");
    $article = is_array($tmp) ? $tmp : array();
}
$article += array(
    'ad_use_status' => 0,
    'ad_use_closet' => 0,
    'ad_use_body'   => 0,
    'ad_use_title'  => 0,
    'ad_use_inven'  => 0,
);

// 스탯 설정값
$status = array();
if (!empty($article['ad_use_status']) && !empty($g5['status_config_table'])) {
    $st_result = sql_query("SELECT * FROM {$g5['status_config_table']} ORDER BY st_order ASC");
    for ($i = 0; $row = sql_fetch_array($st_result); $i++) {
        $status[$i] = $row;
    }
}

// ---------------- 추가 항목 설정값 로드 ----------------

$ch_ar = array();
$str_secret = " WHERE 1 ";

if (isset($member['mb_id']) && isset($mb['mb_id']) && $member['mb_id'] === $mb['mb_id']) {
    $str_secret .= " AND ar_secret != 'H' ";
} else {
    $str_secret .= " AND ar_secret = '' ";
}

if (!empty($g5['article_table'])) {
    $ar_result = sql_query("SELECT * FROM {$g5['article_table']} {$str_secret} ORDER BY ar_order ASC");
    for ($i = 0; $row = sql_fetch_array($ar_result); $i++) {
        $ch_ar[$i] = $row;
    }
}

// ---------------- 캐릭터별 추가 항목 값 로드 (시기 적용) ----------------

if (!empty($g5['value_table'])) {
    if ((int)$time <= 1) {
        $av_result = sql_query("SELECT * FROM {$g5['value_table']} WHERE ch_id = '{$ch['ch_id']}'");
        for ($i = 0; $row = sql_fetch_array($av_result); $i++) {
            $ch[$row['ar_code']] = $row['av_value'];
        }
    } else {
        // 시기 N: 사이드 테이블 값만 (폴백 안 함)
        foreach ($ch_ar as $ar) {
            if (!empty($ar['ar_code'])) {
                $ch[$ar['ar_code']] = get_character_time_article((int)$ch['ch_id'], $ar['ar_code'], $time);
            }
        }
    }
}

// ---------------- 캐릭터 의상 (closthes) 적용 (시기별) ----------------

if (!empty($g5['closthes_table'])) {
    $time_int = (int)$time;
    $temp_cl = sql_fetch("SELECT * FROM {$g5['closthes_table']} WHERE ch_id = '{$ch_id}' AND cl_use = '1' AND cl_time = '{$time_int}'");
    if (!empty($temp_cl['cl_path'])) {
        $ch['ch_body'] = $temp_cl['cl_path'];
    }
}

// ---------------- 탭 기본값 ----------------

$viewer_extra_tabs = array();
$viewer_tools = array();
if (function_exists('mdl_apply_filters')) {
    $viewer_ctx = array(
        'ch'      => $ch,
        'ch_id'   => $ch_id,
        'tabs'    => $tabs,
        'article' => $article,
        'mb'      => $mb,
    );
    $viewer_extra_tabs = mdl_apply_filters('mypage_character_viewer_tabs', array(), $viewer_ctx);
    if (!is_array($viewer_extra_tabs)) {
        $viewer_extra_tabs = array();
    }
    $viewer_tools = mdl_apply_filters('mypage_character_viewer_tools', array(), $viewer_ctx);
    if (function_exists('character_viewer_normalize_tools')) {
        $viewer_tools = character_viewer_normalize_tools($viewer_tools);
    } elseif (!is_array($viewer_tools)) {
        $viewer_tools = array();
    }
}

$allowed_tabs = array('c', 's', 't', 'i', 'r');
foreach ($viewer_extra_tabs as $_xtab) {
    if (!is_array($_xtab) || empty($_xtab['id'])) {
        continue;
    }
    if (!empty($_xtab['href'])) {
        continue;
    }
    $_xid = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$_xtab['id']);
    if ($_xid !== '') {
        $allowed_tabs[] = $_xid;
    }
}
unset($_xtab, $_xid);
if (!in_array($tabs, $allowed_tabs, true)) {
    $tabs = 'c';
}
?>
<style>
.tab-nav {text-align:center; padding:10px!important; margin-bottom:30px;}
.tab-nav button,
.tab-nav a.ui-btn {font-size:14px; height:30px; padding:0 20px; margin:2px 0;}
.tab-nav a.ui-btn {display:inline-block; line-height:30px; vertical-align:middle; text-decoration:none; box-sizing:border-box;}
@media all and (max-width:640px) {
    .tab-nav {padding:10px 0;}
    .tab-nav img {width:14px;}
    .tab-nav button,
    .tab-nav a.ui-btn {font-size:12px;}
}
@media all and (max-width:520px) {
    .profile-viewer .prof,
    .tab-nav {margin-left:-16px; margin-right:-16px;}
}
.closet-list {}
.closet-list fieldset {position:relative; margin-top:10px; padding-left:120px; padding-right:80px;}
.closet-list fieldset input#cl_sibject {position:absolute; top:0; left:0; width:115px;}
.closet-list fieldset input[type="submit"] {position:absolute; top:0; right:0; line-height:1.0em; height:30px; width:75px;}
.closet-list ul {text-align:left;}
.closet-list ul li {position:relative; padding:10px 80px 10px 10px; border-bottom:1px solid rgba(255,255,255,0.1);}
.closet-list ul .ui-btn-box {position:absolute; top:5px; right:0; bottom:5px; width:80px;}
.closet-list ul .ui-btn-box a {display:block; position:relative; height:100%; width:30px; float:left; margin-left:5px; overflow:hidden; text-indent:-999px; border-radius:2px;}
.closet-list ul .ui-btn-box a:before {display:block; position:absolute; font-family:'icon'; font-size:15px; width:20px; height:20px; left:50%; top:50%; margin-top:-10px; margin-left:-10px; text-indent:0; text-align:center; line-height:20px;}
.closet-list ul .ui-btn-box a.btn-use:before {content:"\e9c3"; font-size:20px; line-height:23px;}
.closet-list ul .ui-btn-box a.btn-del:before {content:"\e9ac";}
.closet-list ul .selected .ui-btn-box {display:none;}
.closet-list ul .selected:after {content:"사용중"; display:block; position:absolute; width:70px; top:10px; right:10px; text-align:center;}
</style>

<div class="profile-viewer">
    <?php print_time_tabs($time, (bool)$is_admin, $is_owner); ?>
    <div class="body">
        <?php
        if (!empty($ch['ch_body']) && strpos($ch['ch_body'], '<img') !== false) {
            $ch['ch_body'] = '';
        }
        ?>
        <em style="background-image:url('<?php echo htmlspecialchars($ch['ch_body'] ?? '', ENT_QUOTES); ?>');"></em>
    </div>

    <div class="data">
        <div class="control">
            <?php if (($ch['ch_state'] ?? '') !== '승인' || !empty($is_mod_character) || $is_admin) { ?>
            <a href="./character_form.php?w=u&amp;ch_id=<?php echo (int)$ch['ch_id']; ?><?php echo ($time > 1 ? '&amp;time='.(int)$time : ''); ?>">수정</a>
            <?php } ?>
            <a href="<?php echo G5_URL; ?>/member/exp.php?ch_id=<?php echo (int)$ch['ch_id']; ?>" onclick="popup_window(this.href, 'exp', 'width=400, height=500'); return false;">경험치</a>
        </div>

        <div class="prof">
            <div class="thumb-item">
                <?php
                if (!empty($ch['ch_thumb']) && strpos($ch['ch_thumb'], '<img') !== false) {
                    $ch['ch_thumb'] = '';
                }
                ?>
                <a href="<?php echo G5_URL; ?>/member/viewer.php?ch_id=<?php echo (int)$ch['ch_id']; ?><?php echo ($time > 1 ? '&amp;time='.(int)$time : ''); ?>" class="character-picture" title="클릭 시 멤버란으로 이동됩니다.">
                    <div class="ui-thumb">
                        <?php if (!empty($ch['ch_thumb'])) { ?>
                        <em><img src="<?php echo htmlspecialchars($ch['ch_thumb'], ENT_QUOTES); ?>" alt=""></em>
                        <?php } ?>
                    </div>
                    <div class="name">
                        <strong><?php echo htmlspecialchars($ch['ch_name'] ?? '', ENT_QUOTES); ?></strong>
                    </div>
                </a>
            </div>

            <div class="prof-data">
                <p>
                    <strong><?php echo htmlspecialchars($ch['ch_name'] ?? '', ENT_QUOTES); ?></strong>
                    <?php echo function_exists('get_rank_name')  ? get_rank_name($ch['ch_rank'] ?? '') : ''; ?>
                    &middot;
                    <?php echo function_exists('get_side_name')  ? get_side_name($ch['ch_side'] ?? '') : ''; ?>
                    &middot;
                    <?php echo function_exists('get_class_name') ? get_class_name($ch['ch_class'] ?? '') : ''; ?>
                </p>
                <p>
                    <?php echo (int)($ch['ch_exp'] ?? 0); ?>
                    <?php echo htmlspecialchars($config['cf_exp_pice'] ?? 'exp', ENT_QUOTES); ?>
                </p>
            </div>
        </div>

        <div class="tab-nav">
            <?php if (!empty($article['ad_use_closet']) && !empty($article['ad_use_body'])) { ?>
                <button type="button" class="ui-btn" onclick="$('#closest_area').show().siblings().hide();">옷장</button>
            <?php } ?>
            <?php
            if (!empty($viewer_tools) && function_exists('character_viewer_tools_html')) {
                echo character_viewer_tools_html($viewer_tools, 'ui-btn');
            }
            ?>
            <?php if (!empty($article['ad_use_status'])) { ?>
                <button type="button" class="ui-btn" onclick="$('#status_area').show().siblings().hide();">스탯</button>
            <?php } ?>
            <?php if (!empty($article['ad_use_title'])) { ?>
                <button type="button" class="ui-btn" onclick="$('#title_area').show().siblings().hide();">타이틀</button>
            <?php } ?>
            <?php if (!empty($article['ad_use_inven'])) { ?>
                <button type="button" class="ui-btn" onclick="$('#inventory_area').show().siblings().hide();">아이템</button>
            <?php } ?>
            <?php
            if (!empty($viewer_extra_tabs)) {
                foreach ($viewer_extra_tabs as $_xtab) {
                    if (!is_array($_xtab) || !isset($_xtab['label']) || $_xtab['label'] === '') {
                        continue;
                    }
                    $_xhref = isset($_xtab['href']) ? trim((string)$_xtab['href']) : '';
                    if ($_xhref !== '') {
                        echo '<a class="ui-btn" href="'.htmlspecialchars($_xhref, ENT_QUOTES).'">'.htmlspecialchars((string)$_xtab['label'], ENT_QUOTES).'</a>'."\n";
                        continue;
                    }
                    if (empty($_xtab['id'])) {
                        continue;
                    }
                    $_xid = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$_xtab['id']);
                    if ($_xid === '') {
                        continue;
                    }
                    echo '<button type="button" class="ui-btn" onclick="$(\'#'.$_xid.'_area\').show().siblings().hide();">'.htmlspecialchars((string)$_xtab['label'], ENT_QUOTES).'</button>'."\n";
                }
                unset($_xtab, $_xid, $_xhref);
            }
            ?>
            <?php if (($ch['ch_state'] ?? '') === '승인') { ?>
                <button type="button" class="ui-btn" onclick="$('#relation_area').show().siblings().hide();">관계</button>
            <?php } ?>
        </div>

        <div class="tab-box-group" style="min-height:300px;">
            <?php if (!empty($article['ad_use_closet']) && !empty($article['ad_use_body'])) { ?>
                <div class="tab-box" id="closest_area" <?php echo $tabs === 'c' ? '' : "style='display:none;'"; ?>>
                    <?php include_once(function_exists('theme_dir_file') ? theme_dir_file('mypage/character/cloest.inc.php') : (G5_PATH.'/mypage/character/cloest.inc.php')); ?>
                </div>
            <?php } ?>

            <?php if (!empty($article['ad_use_status'])) { ?>
                <div class="tab-box" id="status_area" <?php echo $tabs === 's' ? '' : "style='display:none;'"; ?>>
                    <span style="float:right; display:block;">
                        <em class="txt-point" data-type="point_space">
                            <?php echo function_exists('get_space_status') ? get_space_status($ch['ch_id']) : 0; ?>
                        </em>
                        / <?php echo (int)($ch['ch_point'] ?? 0); ?>
                    </span>
                    <div class="mypage-box" style="overflow:hidden; clear:both;">
                        <?php include_once(function_exists('theme_dir_file') ? theme_dir_file('mypage/character/status.inc.php') : (G5_PATH.'/mypage/character/status.inc.php')); ?>
                    </div>
                </div>
            <?php } ?>

            <?php if (!empty($article['ad_use_title'])) { ?>
                <div class="tab-box" id="title_area" <?php echo $tabs === 't' ? '' : "style='display:none;'"; ?>>
                    <div class="mypage-box">
                        <?php include_once(function_exists('theme_dir_file') ? theme_dir_file('mypage/character/title.inc.php') : (G5_PATH.'/mypage/character/title.inc.php')); ?>
                    </div>
                </div>
            <?php } ?>

            <?php if (!empty($article['ad_use_inven'])) { ?>
                <div class="tab-box" id="inventory_area" <?php echo $tabs === 'i' ? '' : "style='display:none;'"; ?>>
                    <?php include_once(function_exists('theme_dir_file') ? theme_dir_file('inventory/list.inc.php') : (G5_PATH.'/inventory/list.inc.php')); ?>
                </div>
            <?php } ?>

            <?php
            if (!empty($viewer_extra_tabs)) {
                foreach ($viewer_extra_tabs as $_xtab) {
                    if (!is_array($_xtab) || empty($_xtab['id'])) {
                        continue;
                    }
                    if (!empty($_xtab['href'])) {
                        continue;
                    }
                    $_xid = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$_xtab['id']);
                    if ($_xid === '') {
                        continue;
                    }
                    $_xhide = ($tabs === $_xid) ? '' : "style='display:none;'";
                    echo '<div class="tab-box" id="'.$_xid.'_area" '.$_xhide.'>';
                    echo isset($_xtab['html']) ? $_xtab['html'] : '';
                    echo '</div>'."\n";
                }
                unset($_xtab, $_xid, $_xhide);
            }
            ?>

            <?php if (($ch['ch_state'] ?? '') === '승인') { ?>
                <div class="tab-box" id="relation_area" <?php echo $tabs === 'r' ? '' : "style='display:none;'"; ?>>
                    <div class="mypage-box relation-box">
                        <?php include (function_exists('theme_dir_file') ? theme_dir_file('member/skin/relation.skin.php') : (G5_PATH.'/member/skin/relation.skin.php')); ?>
                    </div>
                </div>
            <?php } ?>
        </div>

        <?php if (($ch['ch_state'] ?? '') !== '승인') { ?>
        <div style="padding-top:50px; text-align:center;">
            <a href="./character_delete.php?ch_id=<?php echo (int)$ch['ch_id']; ?>"
               class="ui-btn full"
               onclick="return confirm('정말 삭제 하시겠습니까?');">
                캐릭터 정보 삭제
            </a>
        </div>
        <?php } ?>
    </div>
</div>

<hr class="padding">

<?php
include_once('./_tail.php');
