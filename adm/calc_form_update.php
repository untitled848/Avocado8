<?php
$sub_menu = "520010";
include_once('./_common.php');
check_admin_token();

if (!function_exists('payout_apply')) {
    alert('정산 라이브러리가 로드되지 않았습니다.', G5_ADMIN_URL.'/calc_form.php');
}

$mode        = isset($_POST['target_mode']) ? (string)$_POST['target_mode'] : 'all';
$include_npc = !empty($_POST['include_npc']) ? 1 : 0;
$side_ids    = isset($_POST['side_ids'])  && is_array($_POST['side_ids'])  ? $_POST['side_ids']  : array();
$class_ids   = isset($_POST['class_ids']) && is_array($_POST['class_ids']) ? $_POST['class_ids'] : array();
$ch_ids      = isset($_POST['ch_ids'])    && is_array($_POST['ch_ids'])    ? $_POST['ch_ids']   : array();
$memo        = isset($_POST['memo']) ? trim((string)$_POST['memo']) : '';

$item_mode   = isset($_POST['item_mode']) ? (string)$_POST['item_mode'] : 'direct';
$preset_id   = isset($_POST['preset_id']) ? (int)$_POST['preset_id'] : 0;

// 1) targets
$targets = payout_resolve_targets(array(
    'mode'        => $mode,
    'side_ids'    => $side_ids,
    'class_ids'   => $class_ids,
    'ch_ids'      => $ch_ids,
    'include_npc' => $include_npc,
));
if (empty($targets)) {
    alert('대상 캐릭터가 0명입니다.', G5_ADMIN_URL.'/calc_form.php');
}

// 2) payload
$payload_list = array();
if ($item_mode === 'preset') {
    if ($preset_id <= 0) alert('프리셋을 선택하세요.', G5_ADMIN_URL.'/calc_form.php');
    $preset = payout_preset_get($preset_id);
    if (!$preset) alert('프리셋을 찾을 수 없습니다.', G5_ADMIN_URL.'/calc_form.php');
    $payload_list = isset($preset['payload']) && is_array($preset['payload']) ? $preset['payload'] : array();
    if ($memo === '' && !empty($preset['cp_memo'])) $memo = $preset['cp_memo'];
} else {
    $pl = isset($_POST['payload']) && is_array($_POST['payload']) ? $_POST['payload'] : array();
    $types = isset($pl['type']) && is_array($pl['type']) ? $pl['type'] : array();
    $ids   = isset($pl['id'])   && is_array($pl['id'])   ? $pl['id']   : array();
    $qtys  = isset($pl['qty'])  && is_array($pl['qty'])  ? $pl['qty']  : array();
    $n = count($types);
    for ($i = 0; $i < $n; $i++) {
        $t = (string)$types[$i];
        if ($t === '') continue;
        $payload_list[] = array(
            'type' => $t,
            'id'   => isset($ids[$i])  ? (int)$ids[$i]  : 0,
            'qty'  => isset($qtys[$i]) ? (int)$qtys[$i] : 1,
        );
    }
}
if (empty($payload_list)) {
    alert('지급 항목이 비어 있습니다.', G5_ADMIN_URL.'/calc_form.php');
}

// 3) apply
$stats = payout_apply($targets, $payload_list, $memo);

// 결과 페이지
$g5['title'] = '통합 정산 결과';
include_once(G5_ADMIN_PATH.'/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <p>정산이 완료되었습니다.<br>
       대상 <b><?php echo (int)$stats['targets']; ?></b>명 × 항목 <b><?php echo (int)$stats['payloads']; ?></b>개 ·
       성공 <b><?php echo (int)$stats['success']; ?></b> · 실패 <b><?php echo (int)$stats['failed']; ?></b></p>
</div>
<div class="cf-box">
    <h2 class="cf-h">상세 로그</h2>
    <pre class="cf-log" style="background:#f7f7f7;padding:10px;max-height:400px;overflow:auto;font-size:12px;"><?php
        foreach ($stats['logs'] as $line) echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8')."\n";
    ?></pre>
</div>
<div class="btn_confirm01 btn_confirm">
    <a href="./calc_form.php" class="btn btn_01">다시 정산</a>
</div>
<?php include_once(G5_ADMIN_PATH.'/admin.tail.php'); ?>
