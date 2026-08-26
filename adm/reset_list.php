<?php
$sub_menu = "400800";
include_once('./_common.php');

$g5['title'] = '리셋 카운터 관리';
include_once('./admin.head.php');
$reset_hub_tab = 'list';
include_once('./_reset_hub_tabs.inc.php');

$st = $g5['reset_setting_table'];
$mn = $g5['reset_manager_table'];

$rows = array();
$rs = sql_query("select s.*,
                        (select count(*) from `{$mn}` m where m.rs_id=s.rs_id) as cnt
                   from `{$st}` s
                   order by s.rs_use desc, s.rs_name asc", false);
if ($rs) while ($r = sql_fetch_array($rs)) $rows[] = $r;

function _reset_period_label($p) {
    $map = array('hourly'=>'매시', 'daily'=>'매일', 'weekly'=>'매주(월)', 'monthly'=>'매월', 'yearly'=>'매년');
    if (isset($map[$p])) return $map[$p];
    if (preg_match('/^(\d+)\s*h$/i', (string)$p, $m)) return $m[1].'시간 간격';
    return $p;
}
function _reset_type_label($t) {
    $map = array('admin'=>'관리자', 'member'=>'회원', 'character'=>'캐릭터');
    return isset($map[$t]) ? $map[$t] : $t;
}
?>

<div class="tbl_head01 tbl_wrap">
<table class="rl-table">
    <caption>리셋 설정 일람</caption>
    <thead>
    <tr>
        <th scope="col">#</th>
        <th scope="col">이름</th>
        <th scope="col">설명</th>
        <th scope="col">유형</th>
        <th scope="col">주기</th>
        <th scope="col">기본값</th>
        <th scope="col">증감</th>
        <th scope="col">최소</th>
        <th scope="col">최대</th>
        <th scope="col">마지막 리셋</th>
        <th scope="col">다음 리셋</th>
        <th scope="col">사용</th>
        <th scope="col">행 수</th>
        <th scope="col">관리</th>
    </tr>
    </thead>
    <tbody>
<?php if (!$rows): ?>
    <tr><td colspan="14" class="empty_table">등록된 리셋 항목이 없습니다.</td></tr>
<?php else: foreach ($rows as $r): ?>
    <tr>
        <td><?php echo (int)$r['rs_id']; ?></td>
        <td><strong><?php echo htmlspecialchars($r['rs_name'], ENT_QUOTES); ?></strong></td>
        <td><?php echo htmlspecialchars($r['rs_label'], ENT_QUOTES); ?></td>
        <td><?php echo _reset_type_label($r['rs_type']); ?></td>
        <td><?php echo _reset_period_label($r['rs_period']); ?></td>
        <td><?php echo (int)$r['rs_default']; ?></td>
        <td><?php echo ((int)$r['rs_plus'] === 0) ? '-' : ((int)$r['rs_plus']); ?></td>
        <td><?php echo (int)$r['rs_min']; ?></td>
        <td><?php echo (int)$r['rs_max']; ?></td>
        <td><?php echo $r['rs_last'] ? date('Y-m-d H:i', (int)$r['rs_last']) : '-'; ?></td>
        <td><?php echo $r['rs_next'] ? date('Y-m-d H:i', (int)$r['rs_next']) : '-'; ?></td>
        <td><?php echo ((int)$r['rs_use']) ? '<span class="rl-on">ON</span>' : '<span class="rl-off">OFF</span>'; ?></td>
        <td><?php echo (int)$r['cnt']; ?></td>
        <td>
            <?php if (!empty($r['setting_url'])): ?>
                <a href="<?php echo htmlspecialchars($r['setting_url'], ENT_QUOTES); ?>" class="btn btn_03">설정</a>
            <?php endif; ?>
            <a href="<?php echo G5_ADMIN_URL; ?>/reset_manage.php?rs_id=<?php echo (int)$r['rs_id']; ?>" class="btn btn_01">값 관리</a>
        </td>
    </tr>
<?php endforeach; endif; ?>
    </tbody>
</table>
</div>

<?php
include_once('./admin.tail.php');
?>
