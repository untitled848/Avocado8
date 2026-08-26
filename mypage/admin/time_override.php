<?php
include_once('./_common.php');

// 업데이트 처리
if (isset($_POST['act']) && $_POST['act'] === 'save') {
    check_demo();

    $ov = isset($_POST['mb_time_override']) ? (int)$_POST['mb_time_override'] : 0;
    if ($ov < 0) $ov = 0;

    if ($ov > 0) {
        $row = sql_fetch("SELECT ti_id FROM `{$g5['time_table']}` WHERE ti_order = '{$ov}'", false);
        if (empty($row['ti_id'])) {
            alert('선택한 시기가 존재하지 않습니다.');
        }
    }

    $mb_id = sql_escape_string($member['mb_id']);
    sql_query("UPDATE `{$g5['member_table']}` SET mb_time_override = '{$ov}' WHERE mb_id = '{$mb_id}'", false);

    goto_url(G5_URL.'/mypage/admin/time_override.php?msg='.urlencode($ov === 0 ? '동기화로 되돌렸습니다.' : '테스트 시기로 전환했습니다.'));
}

include_once(function_exists('theme_dir_file') ? theme_dir_file('mypage/_head.php') : (G5_PATH.'/mypage/_head.php'));

$cur_ov   = (int)$member['mb_time_override'];
$cf_real  = (int)$config['cf_time_real'];

$times = array();
$res = sql_query("SELECT * FROM `{$g5['time_table']}` ORDER BY ti_order ASC", false);
if ($res) { while ($r = sql_fetch_array($res)) $times[] = $r; }

$msg = isset($_GET['msg']) ? (string)$_GET['msg'] : '';
?>

<h2 class="page-title">
    <strong>테스트 시기 보기</strong>
    <span>Time Test Override (관리자 전용)</span>
</h2>


<?php if ($msg !== ''): ?>
<div style="background:#e7f7ec;border:1px solid #b6e3c5;color:#0a7;padding:8px 14px;border-radius:4px;margin:0 0 16px;"><?php echo h($msg); ?></div>
<?php endif; ?>

<section style="background:#f0f7ff;border:1px solid #b3d4ff;padding:14px 18px;border-radius:6px;margin:0 0 18px;font-size:13px;line-height:1.7;color:#234;">
    <strong>이 기능은?</strong><br>
    아직 공개되지 않은 시기를 관리자가 미리 둘러보고 점검하기 위한 기능입니다.
    설정한 시기는 <u>이 계정에서만</u> 적용되며, 일반 사용자에게는 영향이 없습니다.
    <br><br>
    설정을 바꾸면 사이트 전체(프론트 + 관리자 페이지)가 선택한 시기 기준으로 표시됩니다.
    혼동을 막기 위해 화면 상단에 안내 배너가 항상 노출됩니다.
</section>

<form method="post" autocomplete="off">
<input type="hidden" name="act" value="save">

<table class="theme-form">
    <colgroup>
        <col style="width:180px;">
        <col>
    </colgroup>
    <tbody>
        <tr>
            <th>실제 사이트 시기</th>
            <td>
                <?php
                $real_info = $cf_real ? sql_fetch("SELECT ti_name FROM `{$g5['time_table']}` WHERE ti_order = '{$cf_real}'", false) : array();
                $real_name = !empty($real_info['ti_name']) ? $real_info['ti_name'] : ('시기 '.$cf_real);
                ?>
                <strong><?php echo h($real_name); ?></strong> <span style="color:#888;">(#<?php echo $cf_real; ?>)</span>
                <span style="margin-left:10px;color:#888;font-size:12px;">— 시기 관리에서 바꿀 수 있습니다</span>
            </td>
        </tr>
        <tr>
            <th>지금 보는 시기</th>
            <td>
                <select name="mb_time_override" style="min-width:280px;padding:6px 10px;">
                    <option value="0" <?php if ($cur_ov === 0) echo 'selected'; ?>>현재 시기와 동기화 (기본값)</option>
                    <?php foreach ($times as $t):
                        $tio = (int)$t['ti_order'];
                        $type = isset($t['ti_type']) ? (string)$t['ti_type'] : 'renewal';
                        $type_lbl = function_exists('time_type_label') ? time_type_label($type) : $type;
                        $open = (int)$t['ti_open'];
                        $open_lbl = ($open === 1 ? '공개' : ($open === 2 ? '비공개' : '미사용'));
                    ?>
                    <option value="<?php echo $tio; ?>" <?php if ($cur_ov === $tio) echo 'selected'; ?>>
                        #<?php echo $tio; ?> — <?php echo h($t['ti_name']); ?> [<?php echo h($type_lbl); ?> · <?php echo $open_lbl; ?>]
                    </option>
                    <?php endforeach; ?>
                </select>
                <p style="margin:8px 0 0;color:#666;font-size:12px;">
                    "현재 시기와 동기화" 를 선택하면 일반 사용자와 동일한 화면을 보게 됩니다.
                </p>
            </td>
        </tr>
    </tbody>
</table>

<div style="margin:18px 0;text-align:center;">
    <button type="submit" class="ui-btn point" style="padding:10px 28px;font-size:14px;">저장</button>
    <a href="<?php echo G5_URL; ?>/mypage/admin/" class="ui-btn" style="padding:10px 28px;font-size:14px;margin-left:6px;">취소</a>
</div>

</form>

<?php include_once(function_exists('theme_dir_file') ? theme_dir_file('mypage/_tail.php') : (G5_PATH.'/mypage/_tail.php'));
