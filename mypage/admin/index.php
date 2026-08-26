<?php
include_once('./_common.php');

// ---- POST: 테스트 시기 저장 ----
if (isset($_POST['act']) && $_POST['act'] === 'save_time') {
    check_demo();
    $ov = isset($_POST['mb_time_override']) ? (int)$_POST['mb_time_override'] : 0;
    if ($ov < 0) $ov = 0;
    if ($ov > 0) {
        $chk = sql_fetch("SELECT ti_id FROM `{$g5['time_table']}` WHERE ti_order = '{$ov}'", false);
        if (empty($chk['ti_id'])) alert('선택한 시기가 존재하지 않습니다.');
    }
    $mb_id = sql_escape_string($member['mb_id']);
    sql_query("UPDATE `{$g5['member_table']}` SET mb_time_override = '{$ov}' WHERE mb_id = '{$mb_id}'", false);
    goto_url(G5_URL.'/mypage/admin/?msg='.urlencode($ov === 0 ? '동기화로 되돌렸습니다.' : '테스트 시기로 전환했습니다.'));
}

// ---- POST: 멤버 모드 저장 ----
if (isset($_POST['act']) && $_POST['act'] === 'save_mode') {
    check_demo();
    $on = (isset($_POST['mb_mode']) && (int)$_POST['mb_mode'] === 1) ? 1 : 0;
    $mb_id = sql_escape_string($member['mb_id']);
    sql_query("UPDATE `{$g5['member_table']}` SET mb_mode = '{$on}' WHERE mb_id = '{$mb_id}'", false);
    goto_url(G5_URL.'/mypage/admin/?msg='.urlencode($on ? '멤버 모드를 켰습니다.' : '멤버 모드를 껐습니다.'));
}

// ---- 데이터 준비 ----
$cur_ov  = (int)$member['mb_time_override'];
$cf_real = (int)($config['cf_time_real'] ?? $config['cf_time']);
$mb_mode = !empty($member['mb_mode']) ? 1 : 0;

$times = array();
$res = sql_query("SELECT * FROM `{$g5['time_table']}` ORDER BY ti_order ASC", false);
if ($res) { while ($r = sql_fetch_array($res)) $times[] = $r; }

$real_info = $cf_real ? sql_fetch("SELECT ti_name FROM `{$g5['time_table']}` WHERE ti_order = '{$cf_real}'", false) : array();
$real_name = !empty($real_info['ti_name']) ? $real_info['ti_name'] : ('시기 '.$cf_real);

$msg = isset($_GET['msg']) ? (string)$_GET['msg'] : '';

include_once(function_exists('theme_dir_file') ? theme_dir_file('mypage/_head.php') : (G5_PATH.'/mypage/_head.php'));
?>

<h2 class="page-title">
    <strong>관리자 메뉴</strong>
    <span>Admin Tools (관리자 전용)</span>
</h2>

<?php if ($msg !== ''): ?>
<div style="background:#e7f7ec;border:1px solid #b6e3c5;color:#0a7;padding:8px 14px;border-radius:4px;margin:0 0 18px;"><?php echo h($msg); ?></div>
<?php endif; ?>

<?php /* ===== 테스트 시기 ===== */ ?>
<section class="theme-box" style="margin-bottom:30px;">
    <h3 style="margin:0 0 4px;font-size:15px;">테스트 시기 보기</h3>
    <p style="margin:0 0 14px;font-size:12px;color:#666;line-height:1.6;">
        아직 공개되지 않은 시기를 미리 둘러보고 점검합니다.
        이 계정에서만 적용되며 일반 사용자에게는 영향이 없습니다.
    </p>
    <form method="post" autocomplete="off" style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;">
    <input type="hidden" name="act" value="save_time">
    <span style="font-size:13px;color:#555;">실제 시기: <strong><?php echo h($real_name); ?></strong> (#<?php echo $cf_real; ?>)</span>
    <select name="mb_time_override" style="padding:6px 10px;min-width:260px;">
        <option value="0" <?php if ($cur_ov === 0) echo 'selected'; ?>>현재 시기와 동기화 (기본값)</option>
        <?php foreach ($times as $t):
            $tio = (int)$t['ti_order'];
            $type_lbl = function_exists('time_type_label') ? time_type_label(isset($t['ti_type']) ? $t['ti_type'] : 'renewal') : '';
            $open_lbl = ((int)$t['ti_open'] === 1 ? '공개' : ((int)$t['ti_open'] === 2 ? '비공개' : '미사용'));
        ?>
        <option value="<?php echo $tio; ?>" <?php if ($cur_ov === $tio) echo 'selected'; ?>>
            #<?php echo $tio; ?> — <?php echo h($t['ti_name']); ?> [<?php echo h($type_lbl); ?> · <?php echo $open_lbl; ?>]
        </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="ui-btn point" style="padding:6px 20px;">저장</button>
    </form>
</section>

<?php /* ===== 멤버 모드 ===== */ ?>
<section class="theme-box">
    <h3 style="margin:0 0 4px;font-size:15px;">멤버 모드</h3>
    <p style="margin:0 0 14px;font-size:12px;color:#666;line-height:1.6;">
        관리자 권한을 잠시 끄고 일반 회원 시점으로 사이트를 확인합니다.
        멤버 모드 중에는 관리자 전용 메뉴와 콘텐츠가 숨겨집니다.
    </p>
    <form method="post" autocomplete="off" style="display:flex;flex-wrap:wrap;align-items:center;gap:14px;">
    <input type="hidden" name="act" value="save_mode">
    <label style="cursor:pointer;"><input type="radio" name="mb_mode" value="0" <?php if (!$mb_mode) echo 'checked'; ?>> 관리자 모드 (기본)</label>
    <label style="cursor:pointer;"><input type="radio" name="mb_mode" value="1" <?php if ($mb_mode) echo 'checked'; ?>> 멤버 모드로 전환</label>
    <span style="font-size:13px;color:<?php echo $mb_mode ? '#1a4870' : '#0a7'; ?>;">현재: <strong><?php echo $mb_mode ? '멤버 모드 켜짐' : '관리자 모드'; ?></strong></span>
    <button type="submit" class="ui-btn point" style="padding:6px 20px;">저장</button>
    </form>
</section>

<?php include_once(function_exists('theme_dir_file') ? theme_dir_file('mypage/_tail.php') : (G5_PATH.'/mypage/_tail.php'));
