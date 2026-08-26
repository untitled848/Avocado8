<?php
if (!defined('_GNUBOARD_')) exit;

$begin_time = get_microtime();
include_once(G5_PATH.'/head.sub.php');

// 전역 기본 방어
if (!isset($menu) || !is_array($menu)) {
    $menu = array();
}
if (!isset($amenu) || !is_array($amenu)) {
    $amenu = array();
}
if (!isset($auth_menu) || !is_array($auth_menu)) {
    $auth_menu = array();
}
if (!isset($auth) || !is_array($auth)) {
    $auth = array();
}

// 1차 메뉴 출력
function print_menu1($key, $no)
{
    global $menu;

    if (!isset($menu[$key]) || !is_array($menu[$key])) {
        return '';
    }

    return print_menu2($key, $no);
}

// 2차 메뉴 출력
function print_menu2($key, $no)
{
    global $menu, $auth_menu, $is_admin, $auth, $g5, $sub_menu;

    if (!isset($menu[$key]) || !is_array($menu[$key])) {
        return '';
    }

    $str = '<div class="gnb_2dul"><ul>';
    $gnb_grp_style = '';

    $cnt = count($menu[$key]);

    for ($i = 1; $i < $cnt; $i++) {

        if (!isset($menu[$key][$i]) || !is_array($menu[$key][$i])) {
            continue;
        }

        // [0]=sub_menu 코드, [1]=이름, [2]=링크, [3]=정렬용, [4]=그룹 구분 플래그
        $code     = isset($menu[$key][$i][0]) ? $menu[$key][$i][0] : '';
        $name     = isset($menu[$key][$i][1]) ? $menu[$key][$i][1] : '';
        $link     = isset($menu[$key][$i][2]) ? $menu[$key][$i][2] : '';
        $grp_flag = isset($menu[$key][$i][4]) ? (int)$menu[$key][$i][4] : 0;

        if ($name === '' || $link === '') {
            continue;
        }

        // 권한 체크
        if (!$is_admin) {
            if (!$code || !isset($auth[$code]) || strpos($auth[$code], 'r') === false) {
                continue;
            }
        }

        // 그룹 스타일 처리
        $gnb_grp_div = '';
        if (
            ($grp_flag === 1 && !$gnb_grp_style)
            || ($grp_flag !== 1 && $gnb_grp_style)
        ) {
            $gnb_grp_div = 'gnb_grp_div';
        }

        $gnb_grp_style = ($grp_flag === 1) ? 'on' : '';

        // 현재 서브메뉴 표시
        $check_gnb_grp_style = '';
        if ($code && isset($sub_menu) && $code === $sub_menu) {
            $check_gnb_grp_style = 'check';
        }

        $str .= '<li class="gnb_2dli '.$check_gnb_grp_style.'">'
             .  '<a href="'.$link.'" class="gnb_2da '.$gnb_grp_style.' '.$gnb_grp_div.'" data-text="'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'">'
             .  $name
             .  '</a></li>';

        if ($code) {
            $auth_menu[$code] = $name;
        }
    }

    $str .= '</ul></div>';

    return $str;
}
?>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

<div class="adminWrap">

<header class="adminHeader">
    <div class="inner">
        <h1>
            <a href="<?php echo G5_ACTIVE_ADMIN_URL; ?>">
                <strong>관리 페이지</strong>
            </a>
            <i><?php echo G5_GNUBOARD_VER; ?></i>
        </h1>
        <aside>
            <?php
            // 현재 시기 / 활성 테마 프리셋 표시
            $_adm_status = array();
            if (function_exists('get_current_time') && function_exists('get_time_by_order')) {
                $_cur_time = (int)get_current_time();
                $_ti = get_time_by_order($_cur_time);
                $_ti_name = !empty($_ti['ti_name']) ? $_ti['ti_name'] : ('시기 '.$_cur_time);
                $_adm_status[] = '시기: <strong>'.htmlspecialchars($_ti_name, ENT_QUOTES, 'UTF-8').'</strong>';
            }
            if (function_exists('get_preset')) {
                $_pr = get_preset();
                $_pr_name = !empty($_pr['pr_name']) ? $_pr['pr_name'] : 'default';
                $_adm_status[] = '테마: <strong>'.htmlspecialchars($_pr_name, ENT_QUOTES, 'UTF-8').'</strong>';
            }
            if (!empty($_adm_status)) {
                echo '<span class="adm-status" style="display:inline-flex;gap:10px;align-items:center;font-size:12px;color:#fff;background:rgba(0,0,0,.25);padding:6px 12px;border-radius:20px;margin-right:8px;    width: 200px;">';
                echo implode('<span style="opacity:.5;">|</span>', $_adm_status);
                echo '</span>';
            }
            ?>
            <a href="<?php echo G5_BBS_URL; ?>/logout.php">
                <span class="material-symbols-outlined">logout</span>
            </a>
            <a href="<?php echo G5_URL; ?>" target="_blank">
                <span class="material-symbols-outlined">home</span>
            </a>
            <a href="https://avocado-edition-rout.postype.com/" target="_blank">
                <span class="material-symbols-outlined">developer_guide</span>
            </a>
        </aside>
    </div>
</header>

<nav class="adminGnbArea auto-horiz">
    <div id="gnb" class="inner">
        <?php
        global $menu, $amenu, $sub_menu;

        $gnb_str = '<ul>';

        foreach ($amenu as $key => $value) {

            $menu_key = 'menu' . $key;

            if (
                !isset($menu[$menu_key][0])
                || !is_array($menu[$menu_key][0])
                || empty($menu[$menu_key][0][2])
            ) {
                continue;
            }

            $top_code = isset($menu[$menu_key][0][0]) ? $menu[$menu_key][0][0] : '';
            $top_name = isset($menu[$menu_key][0][1]) ? $menu[$menu_key][0][1] : '';
            $top_link = $menu[$menu_key][0][2];

            if ($top_name === '' || $top_link === '') {
                continue;
            }

            // 메뉴 000 그룹은 "반대 관리자 페이지로 이동" 용 하이퍼링크 — 서브메뉴 열지 않고 바로 이동
            $extra_class = ($key === '000') ? ' no-submenu' : '';

            // 서브메뉴(인덱스 1 이상) 가 1개도 없으면 "서브 펼치기" 가 의미 없으므로 직접 링크 처리
            $has_submenu = false;
            if (isset($menu[$menu_key]) && is_array($menu[$menu_key])) {
                $_cnt_mk = count($menu[$menu_key]);
                for ($_i = 1; $_i < $_cnt_mk; $_i++) {
                    if (!empty($menu[$menu_key][$_i][1]) && !empty($menu[$menu_key][$_i][2])) {
                        $has_submenu = true;
                        break;
                    }
                }
            }
            if (!$has_submenu) {
                $extra_class .= ' no-submenu';
            }

            $href1 = '<a href="'.$top_link.'" class="gnb_1da'.$extra_class.'" data-text="'.htmlspecialchars($top_name, ENT_QUOTES, 'UTF-8').'">';
            $href2 = '</a>';

            $current_class = '';
            if ($top_code && isset($sub_menu) && substr($sub_menu, 0, 3) === substr($top_code, 0, 3)) {
                $current_class = ' on';
            }

            $gnb_str .= '<li class="gnb_1dli'.$current_class.'">'.PHP_EOL;
            $gnb_str .= $href1.$top_name.$href2;
            if ($has_submenu) {
                $gnb_str .= print_menu1($menu_key, 1);
            }
            $gnb_str .= '</li>';
        }

        $gnb_str .= '</ul>';

        echo $gnb_str;
        ?>
    </div>
</nav>

<section class="adminBody">
    <?php
    // head.sub.php 는 이미 배너를 출력하지 않음(admin 맥락에서는 admin.head.php 여기서만 출력)
    if (function_exists('print_admin_status_banner')) print_admin_status_banner();
    ?>
    <?php if (isset($g5['title']) && $g5['title'] !== '관리자메인') { ?>
    <div class="pageTitle">
        <h2><?php echo $g5['title']; ?></h2>
    </div>
    <?php } ?>
    <?php if (function_exists('mdl_do_action')) mdl_do_action('admin_head'); ?>
    <div class="container">
