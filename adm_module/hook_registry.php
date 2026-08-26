<?php
/**
 * 훅 인덱스 — 검색/목록 + 재스캔
 */
$sub_menu = '001200';
include_once('./_common.php');

// 재스캔 AJAX 핸들
if (isset($_POST['rescan']) && $_POST['rescan'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    if (!function_exists('mdl_hook_registry_rescan')) {
        echo json_encode(array('error' => '_hook_registry.php 미로드'));
        exit;
    }
    $result = mdl_hook_registry_rescan();
    echo json_encode($result);
    exit;
}

$g5['title'] = '훅 인덱스';
include_once(G5_MDL_ADMIN_PATH.'/admin.head.php');

$filter = array(
    'name'     => isset($_GET['hr_name']) ? trim($_GET['hr_name']) : '',
    'type'     => isset($_GET['hr_type']) ? $_GET['hr_type'] : '',
    'scope'    => isset($_GET['hr_scope']) ? $_GET['hr_scope'] : '',
    'obsolete' => isset($_GET['hr_obsolete']) ? $_GET['hr_obsolete'] : '0',
    'limit'    => 200,
    'offset'   => 0,
);

$rows = function_exists('mdl_hook_registry_search') ? mdl_hook_registry_search($filter) : array();
$total = count($rows);

// 첫 진입 시(검색 조건 없고 결과 0건) 자동 재스캔
$auto_rescanned = false;
if ($total === 0 && $filter['name'] === '' && $filter['type'] === '' && $filter['scope'] === '') {
    if (function_exists('mdl_hook_registry_rescan')) {
        mdl_hook_registry_rescan();
        $rows = mdl_hook_registry_search($filter);
        $total = count($rows);
        $auto_rescanned = true;
    }
}
?>
<div class="tbl_head01 tbl_wrap">
    <div class="local_desc01 local_desc">
        <p>소스 파일에서 자동 추출된 훅 목록입니다. <strong>재스캔</strong> 버튼으로 최신 상태로 갱신하세요.</p>
        <?php if ($auto_rescanned): ?>
        <p style="color:#0a7abf;">인덱스가 비어있어 자동으로 재스캔했습니다. (<?php echo $total; ?>건 등록)</p>
        <?php endif; ?>
    </div>

    <form id="frm_search" method="get" action="<?php echo G5_MDL_ADMIN_URL; ?>/hook_registry.php">
    <div class="local_ov01 local_ov">
        <div class="ov_wrap">
            <label>훅명</label>
            <input type="text" name="hr_name" value="<?php echo htmlspecialchars($filter['name'], ENT_QUOTES); ?>" size="20">

            <label>타입</label>
            <select name="hr_type">
                <option value="">전체</option>
                <option value="action" <?php echo $filter['type']==='action'?'selected':''; ?>>action</option>
                <option value="filter" <?php echo $filter['type']==='filter'?'selected':''; ?>>filter</option>
            </select>

            <label>스코프</label>
            <select name="hr_scope">
                <option value="">전체</option>
                <option value="general" <?php echo $filter['scope']==='general'?'selected':''; ?>>general</option>
                <option value="bbs" <?php echo $filter['scope']==='bbs'?'selected':''; ?>>bbs</option>
            </select>

            <label>상태</label>
            <select name="hr_obsolete">
                <option value="0" <?php echo $filter['obsolete']==='0'?'selected':''; ?>>유효</option>
                <option value="" <?php echo $filter['obsolete']===''?'selected':''; ?>>전체</option>
                <option value="1" <?php echo $filter['obsolete']==='1'?'selected':''; ?>>obsolete</option>
            </select>

            <input type="submit" value="검색" class="btn_submit btn">
        </div>

        <div class="ov_btn">
            <button type="button" id="btn_rescan" class="btn btn_01">재스캔</button>
            <span id="rescan_status" style="margin-left:8px;color:#666;font-size:12px;"></span>
        </div>
    </div>
    </form>

    <p class="total_count">총 <strong><?php echo $total; ?></strong>개</p>

    <table class="tbl_list table_board_list">
    <caption>훅 인덱스 목록</caption>
    <colgroup>
        <col style="width:26%">
        <col style="width:7%">
        <col style="width:7%">
        <col style="width:26%">
        <col style="width:5%">
        <col style="width:20%">
        <col style="width:9%">
    </colgroup>
    <thead>
    <tr>
        <th>훅 이름</th>
        <th>타입</th>
        <th>스코프</th>
        <th>위치 (파일:라인)</th>
        <th>비고</th>
        <th>설명</th>
        <th>사용 모듈</th>
    </tr>
    </thead>
    <tbody>
<?php
if (empty($rows)) { ?>
    <tr><td colspan="7" class="empty_table">데이터 없음 — [재스캔]을 실행해 주세요.</td></tr>
<?php
} else {
    foreach ($rows as $row) {
        $obsolete_class = $row['hr_obsolete'] ? ' style="opacity:.45"' : '';
        $doc  = htmlspecialchars((string)$row['hr_doc'], ENT_QUOTES);
        $file = htmlspecialchars($row['hr_file'], ENT_QUOTES);
        $sig  = htmlspecialchars($row['hr_param_signature'], ENT_QUOTES);

        // 사용 모듈 (캐시 없이 조회 — 목록이 길면 느릴 수 있음)
        $used = function_exists('mdl_hook_registry_used_by')
            ? mdl_hook_registry_used_by($row['hr_name']) : array();
        $used_str = $used ? implode(', ', array_map('htmlspecialchars', $used)) : '';
        ?>
        <tr<?php echo $obsolete_class; ?>>
            <td><?php echo htmlspecialchars($row['hr_name'], ENT_QUOTES); ?></td>
            <td><?php echo htmlspecialchars($row['hr_type'], ENT_QUOTES); ?></td>
            <td><?php echo htmlspecialchars($row['hr_scope'], ENT_QUOTES); ?></td>
            <td style="font-size:11px;word-break:break-all"><?php echo $file; ?> <small style="color:#888">:<?php echo (int)$row['hr_line']; ?></small>
                <?php if ($sig) echo '<br><span style="color:#777;font-size:10px">'.htmlspecialchars($sig).'</span>'; ?>
            </td>
            <td><?php if ($row['hr_obsolete']) echo '<span style="color:#c00">삭제됨</span>'; ?></td>
            <td>
                <span><?php echo $doc ?: '<em style="color:#aaa">없음</em>'; ?></span>
                <a href="<?php echo G5_MDL_ADMIN_URL; ?>/hook_doc_form.php?hr_id=<?php echo (int)$row['hr_id']; ?>" class="btn btn_02 btn_small">편집</a>
            </td>
            <td style="font-size:11px"><?php echo $used_str; ?></td>
        </tr>
    <?php }
}
?>
    </tbody>
    </table>
</div>

<script>
document.getElementById('btn_rescan').addEventListener('click', function () {
    var btn = this;
    var status = document.getElementById('rescan_status');
    btn.disabled = true;
    status.textContent = '스캔 중…';

    var fd = new FormData();
    fd.append('rescan', '1');

    fetch('<?php echo G5_MDL_ADMIN_URL; ?>/hook_registry.php', {
        method: 'POST',
        body: fd
    })
    .then(function(r){ return r.json(); })
    .then(function(d) {
        if (d.error) {
            status.textContent = '오류: ' + d.error;
        } else {
            status.textContent = '완료 — 신규:' + d.inserted + ', 갱신:' + d.updated + ', obsolete:' + d.obsolete;
        }
        btn.disabled = false;
        setTimeout(function(){ location.reload(); }, 1500);
    })
    .catch(function(e) {
        status.textContent = '요청 실패';
        btn.disabled = false;
    });
});
</script>

<?php include_once(G5_MDL_ADMIN_PATH.'/admin.tail.php'); ?>
