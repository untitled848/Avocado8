<?php
// === 목록 페이지 ===
$rows  = isset($config['cf_page_rows']) ? (int)$config['cf_page_rows'] : 20;
if ($rows <= 0) $rows = 20;
$page  = max(1, (int)($_GET['page'] ?? 1));
$from  = ($page - 1) * $rows;

$f_cate = trim((string)($_GET['cate'] ?? ''));
$opts   = $f_cate !== '' ? array('cate' => $f_cate) : array();
$list  = function_exists('notice_get_list')  ? notice_get_list($rows, $from, $opts)  : array();
$total = function_exists('notice_get_count') ? notice_get_count($opts)               : 0;
$total_page = (int)ceil($total / $rows);

$nc_list = function_exists('notice_categories') ? notice_categories() : array();

// 그리드 설정 (컬럼 미존재 시 폴백)
$no_list_mode  = isset($config['cf_notice_list_mode'])  ? $config['cf_notice_list_mode']  : 'list';
$no_grid_mode  = isset($config['cf_notice_grid_mode'])  ? $config['cf_notice_grid_mode']  : 'auto-fill';
$no_grid_count = isset($config['cf_notice_grid_count']) ? max(1, (int)$config['cf_notice_grid_count']) : 3;
$no_card_min_w = (isset($config['cf_notice_card_min_w']) && $config['cf_notice_card_min_w'] !== '') ? $config['cf_notice_card_min_w'] : '240px';
$no_ar_w       = isset($config['cf_notice_ar_w'])       ? max(1, (int)$config['cf_notice_ar_w']) : 16;
$no_ar_h       = isset($config['cf_notice_ar_h'])       ? max(1, (int)$config['cf_notice_ar_h']) : 9;

$use_grid = ($no_list_mode === 'grid');
if ($use_grid) {
    if ($no_grid_mode === 'fixed') {
        $grid_tpl = 'repeat('.$no_grid_count.', 1fr)';
    } else {
        $grid_tpl = 'repeat('.htmlspecialchars($no_grid_mode, ENT_QUOTES).', minmax('.htmlspecialchars($no_card_min_w, ENT_QUOTES).', 1fr))';
    }
}

$g5['title'] = '공지사항';
add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/notice/notice.css">', 0);
include_once(G5_PATH.'/_head.php');
?>

<section class="notice-list">
    <?php if (!empty($nc_list)): ?>
    <nav class="notice-list-filters" aria-label="카테고리">
        <a class="notice-list-filter<?php echo $f_cate === '' ? ' is-active' : ''; ?>" href="./">전체</a>
        <?php foreach ($nc_list as $c):
            $on = ($f_cate === $c);
        ?>
        <a class="notice-list-filter<?php echo $on ? ' is-active' : ''; ?>" href="./?cate=<?php echo urlencode($c); ?>"><?php echo htmlspecialchars($c, ENT_QUOTES); ?></a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <?php if ($use_grid): ?>
    <?php /* ── 그리드 카드 모드 ── */ ?>
    <?php if (empty($list)): ?>
    <p class="notice-list-empty">공지가 없습니다.</p>
    <?php else: ?>
    <style>
        .notice-grid { grid-template-columns:<?php echo $grid_tpl; ?>; }
        .notice-card-thumb { aspect-ratio:<?php echo $no_ar_w; ?>/<?php echo $no_ar_h; ?>; }
    </style>
    <ul class="notice-grid">
        <?php foreach ($list as $row):
            $turl = !empty($row['no_thumb']) ? $row['no_thumb'] : '';
            if ($turl !== '') {
                $turl = str_replace('[DATA_URL]', G5_DATA_URL, $turl);
                if (function_exists('cdn_resolve_url')) $turl = cdn_resolve_url($turl);
            }
            $href = './?no_id='.(int)$row['no_id'];
            $cate = isset($row['nc_name']) ? trim((string)$row['nc_name']) : '';
            $sub  = isset($row['no_subtext']) ? trim((string)$row['no_subtext']) : '';
        ?>
        <li>
            <div class="notice-card">
                <div class="notice-card-thumb<?php echo $turl === '' ? ' is-empty' : ''; ?>">
                    <a class="notice-card-hit" href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>" tabindex="-1" aria-hidden="true"></a>
                    <?php if ($turl !== ''): ?>
                    <img src="<?php echo htmlspecialchars($turl, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                    <?php else: ?>
                    <span class="notice-card-noimg">no image</span>
                    <?php endif; ?>
                    <?php if ($cate !== ''): ?>
                    <span class="notice-card-cat"><?php echo htmlspecialchars($cate, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                    <div class="notice-card-info">
                        <a class="notice-card-title" href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row['no_subject'], ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php if ($sub !== ''): ?>
                        <p class="notice-card-sub"><?php echo htmlspecialchars($sub, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <?php else: ?>
    <?php /* ── 일반 목록 모드 (board theme-list) ── */ ?>
    <div id="bo_list" class="notice-bo-list">
        <table class="board-list theme-list">
            <caption>공지사항 목록</caption>
            <colgroup>
                <col style="width: 80px;">
                <col>
                <col style="width: 120px;">
            </colgroup>
            <thead>
            <tr>
                <th scope="col">No</th>
                <th scope="col">제목</th>
                <th scope="col">작성일</th>
            </tr>
            </thead>
            <tbody>
            <?php
            if (empty($list)) {
                echo '<tr><td colspan="3" class="empty_table">공지가 없습니다.</td></tr>';
            } else {
                $num = $total - $from;
                foreach ($list as $i => $row) {
                    $href = './?no_id='.(int)$row['no_id'];
                    $cate = isset($row['nc_name']) ? trim((string)$row['nc_name']) : '';
                    $sub  = isset($row['no_subtext']) ? trim((string)$row['no_subtext']) : '';
                    $pinned = !empty($row['no_pinned']);
                    $dt = (isset($row['no_datetime']) && strlen($row['no_datetime']) >= 10)
                        ? substr($row['no_datetime'], 5, 5)
                        : '';
            ?>
            <tr class="bg-<?php echo $i % 2; ?><?php echo $pinned ? ' bo_notice' : ''; ?>">
                <td class="td_num">
                    <?php echo $pinned ? '<strong>공지</strong>' : (int)$num; ?>
                </td>
                <td class="td_subject">
                    <?php if ($cate !== ''): ?>
                    <a href="./?cate=<?php echo urlencode($cate); ?>" class="bo_cate_link">[<?php echo htmlspecialchars($cate, ENT_QUOTES, 'UTF-8'); ?>]</a>
                    <?php endif; ?>
                    <a href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($row['no_subject'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <?php if ($sub !== ''): ?>
                    <span class="notice-subtext"><?php echo htmlspecialchars($sub, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </td>
                <td class="td_date"><?php echo htmlspecialchars($dt, ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <?php
                    $num--;
                }
            }
            ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php
    if ($total_page > 1 && function_exists('get_paging')) {
        $base = './?'.($f_cate !== '' ? 'cate='.urlencode($f_cate).'&' : '');
        echo get_paging($config['cf_write_pages'] ?? 10, $page, $total_page, $base);
    }
    ?>
</section>

<?php include_once(G5_PATH.'/_tail.php'); ?>
