<?php
include_once('./_common.php');

if (isset($_POST['act']) && $_POST['act'] === 'save') {
    check_demo();
    $on = isset($_POST['mb_mode']) && (int)$_POST['mb_mode'] === 1 ? 1 : 0;
    $mb_id = sql_escape_string($member['mb_id']);
    sql_query("UPDATE `{$g5['member_table']}` SET mb_mode = '{$on}' WHERE mb_id = '{$mb_id}'", false);
    goto_url(G5_URL.'/mypage/admin/member_mode.php?msg='.urlencode($on ? '멤버 모드를 켰습니다.' : '멤버 모드를 껐습니다.'));
}

include_once(function_exists('theme_dir_file') ? theme_dir_file('mypage/_head.php') : (G5_PATH.'/mypage/_head.php'));

$cur = !empty($member['mb_mode']) ? 1 : 0;
$msg = isset($_GET['msg']) ? (string)$_GET['msg'] : '';
?>

<h2 class="page-title">
    <strong>멤버 모드</strong>
    <span>Member Mode (관리자 전용)</span>
</h2>


<?php if ($msg !== ''): ?>
<div style="background:#e7f7ec;border:1px solid #b6e3c5;color:#0a7;padding:8px 14px;border-radius:4px;margin:0 0 16px;"><?php echo h($msg); ?></div>
<?php endif; ?>

<section style="background:#f0f7ff;border:1px solid #b3d4ff;padding:14px 18px;border-radius:6px;margin:0 0 18px;font-size:13px;line-height:1.7;color:#234;">
    <strong>이 기능은?</strong><br>
    관리자 권한을 잠시 끄고 일반 회원의 시점으로 사이트를 둘러볼 수 있습니다.
    멤버 모드가 켜져 있는 동안에는:
    <ul style="margin:8px 0 0 18px;padding:0;">
        <li>관리자 페이지(/adm, /adm_module)에 접근할 수 없습니다.</li>
        <li>관리자에게만 보이던 콘텐츠/메뉴가 모두 숨겨집니다.</li>
        <li>비공개/미사용 시기 데이터는 일반 회원처럼 차단됩니다.</li>
        <li>단, 마이페이지 → 관리자 메뉴 자체는 계속 접근 가능합니다 (이 페이지 포함).</li>
    </ul>
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
            <th>현재 상태</th>
            <td>
                <strong style="font-size:14px;color:<?php echo $cur ? '#1a4870' : '#0a7'; ?>;">
                    <?php echo $cur ? '👤 멤버 모드 켜짐 (일반 회원 시점)' : '⚙ 관리자 모드 (정상)'; ?>
                </strong>
            </td>
        </tr>
        <tr>
            <th>변경</th>
            <td>
                <label style="display:inline-block;margin-right:18px;cursor:pointer;">
                    <input type="radio" name="mb_mode" value="0" <?php if (!$cur) echo 'checked'; ?>>
                    관리자 모드 (기본)
                </label>
                <label style="display:inline-block;cursor:pointer;">
                    <input type="radio" name="mb_mode" value="1" <?php if ($cur) echo 'checked'; ?>>
                    멤버 모드로 전환
                </label>
            </td>
        </tr>
    </tbody>
</table>

<div style="margin:18px 0;text-align:center;">
    <button type="submit" class="ui-btn point" style="padding:10px 28px;font-size:14px;">저장</button>
    <a href="<?php echo G5_URL; ?>/mypage/" class="ui-btn" style="padding:10px 28px;font-size:14px;margin-left:6px;">취소</a>
</div>

</form>

<?php include_once(function_exists('theme_dir_file') ? theme_dir_file('mypage/_tail.php') : (G5_PATH.'/mypage/_tail.php'));
