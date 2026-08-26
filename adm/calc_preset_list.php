<?php
$sub_menu = "520020";
include_once('./_common.php');

$g5['title'] = '정산 프리셋';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$rs = sql_query("SELECT * FROM `{$g5['calc_preset_table']}` ORDER BY cp_order ASC, cp_id ASC", false);
?>
<div class="local_desc01 local_desc">
    <p>일괄 정산에 사용할 항목 묶음을 미리 등록합니다. <b>정산 신청</b> 체크된 프리셋은 calculate 게시판의 신청 글쓰기 폼 드롭다운에 노출됩니다.</p>
</div>
<div class="btn_add01 btn_add">
    <a href="./calc_preset_form.php" class="btn btn_01">새 프리셋</a>
</div>
<div class="tbl_head01 tbl_wrap">
<table>
    <thead><tr><th>ID</th><th>이름</th><th>항목수</th><th>정산신청</th><th>정렬</th><th>관리</th></tr></thead>
    <tbody>
    <?php $cnt = 0; while ($r = sql_fetch_array($rs)) { $cnt++;
        $payload = function_exists('payout_preset_decode') ? payout_preset_decode($r['cp_payload']) : array();
    ?>
        <tr>
            <td class="td_num"><?php echo (int)$r['cp_id']; ?></td>
            <td><?php echo htmlspecialchars($r['cp_name'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="td_num"><?php echo count($payload); ?></td>
            <td class="td_num"><?php echo $r['cp_use_request'] ? 'Y' : '-'; ?></td>
            <td class="td_num"><?php echo (int)$r['cp_order']; ?></td>
            <td class="td_mng">
                <a href="./calc_preset_form.php?cp_id=<?php echo (int)$r['cp_id']; ?>" class="btn btn_03">수정</a>
                <a href="./calc_preset_delete.php?cp_id=<?php echo (int)$r['cp_id']; ?>&amp;<?php echo $token_query ?? ''; ?>" class="btn btn_03" onclick="return confirm('삭제하시겠습니까?');">삭제</a>
            </td>
        </tr>
    <?php }
    if ($cnt === 0) echo '<tr><td colspan="6" class="empty_table">등록된 프리셋이 없습니다.</td></tr>';
    ?>
    </tbody>
</table>
</div>
<?php include_once(G5_ADMIN_PATH.'/admin.tail.php'); ?>
