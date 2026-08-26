<?php
/**
 * adm/cdn_migrate.php
 *
 * 화이트리스트 카테고리의 DB 이미지 경로를 일괄 변환.
 *
 *   · 토큰화(tokenize)   : G5_URL/data/... → [DATA_URL]/...   (CDN 켜기 준비)
 *   · 역토큰화(detokenize): [DATA_URL]/...  → G5_DATA_URL/...  (CDN 제거/롤백)
 *
 * 대상 화이트리스트는 extend/00_cdn.lib.php 의 cdn_migration_targets() 가 관리.
 * (아이템 g5_item 은 CDN 대상에서 제외됨)
 *
 * 진입: /adm/cdn_migrate.php
 *
 * 안전:
 *  - 미리보기(preview) 모드 기본
 *  - 토큰화: 이미 [DATA_URL] 포함된 행은 건너뜀
 *  - 역토큰화: [DATA_URL] 없는 행은 건너뜀
 *  - 토큰화는 cdn_tokenize_*($v, true) 강제 모드로 CDN off 상태에서도 동작
 */

$sub_menu = '950100';
include_once('./_common.php');

// 메모리 규칙대로 auth_check 미사용 — admin.lib.php 가 이미 가드
// check_admin_token() 은 POST 시점에서

if (!function_exists('cdn_run_migration')) {
    alert('CDN 코어 라이브러리가 없습니다. extend/00_cdn.lib.php 를 확인하세요.');
}

$mode   = (isset($_REQUEST['mode']) && $_REQUEST['mode'] === 'detokenize') ? 'detokenize' : 'tokenize';
$apply  = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply']) && $_POST['apply'] === '1';
$report = array();

if ($apply) check_admin_token();

if ($apply || (isset($_GET['preview']) && $_GET['preview'] === '1')) {
    $report = cdn_run_migration($mode, $apply);
}

$g5['title'] = 'CDN 데이터 마이그레이션';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$is_detok = ($mode === 'detokenize');
?>

<style>
.cdn-mig-help { background:#fafafa; border:1px solid #ddd; padding:14px 18px; margin-bottom:16px; line-height:1.7; }
.cdn-mig-help h2 { font-size:15px; margin:0 0 6px; }
.cdn-mig-help ul { margin:6px 0 0 18px; padding:0; }
.cdn-mig-help code { background:#eee; padding:1px 4px; }
.cdn-mig-report { width:100%; border-collapse:collapse; margin-top:10px; }
.cdn-mig-report th, .cdn-mig-report td { border:1px solid #ddd; padding:6px 10px; font-size:12px; }
.cdn-mig-report th { background:#f0f0f0; }
.cdn-mig-report .col-changed { color:#c00; font-weight:bold; }
.cdn-mig-report .col-skipped { color:#888; }
.cdn-mig-tabs { margin-bottom:14px; }
.cdn-mig-tabs a { display:inline-block; padding:8px 16px; border:1px solid #ccc; border-radius:6px; margin-right:6px; text-decoration:none; color:#444; }
.cdn-mig-tabs a.on { background:#1976d2; color:#fff; border-color:#1976d2; font-weight:600; }
</style>

<div class="cdn-mig-tabs">
    <a href="./cdn_migrate.php?mode=tokenize"   class="<?php echo $is_detok ? '' : 'on'; ?>">① 토큰화 (CDN 켜기 준비)</a>
    <a href="./cdn_migrate.php?mode=detokenize" class="<?php echo $is_detok ? 'on' : ''; ?>">② 역토큰화 (CDN 제거·롤백)</a>
</div>

<div class="cdn-mig-help">
    <?php if ($is_detok): ?>
    <h2>역토큰화 — [DATA_URL] 을 실제 경로로 되돌리기</h2>
    <p>
        DB 안에 저장된 <code>[DATA_URL]/...</code> 토큰을
        홈페이지 실제 주소(<code><?php echo htmlspecialchars(rtrim(G5_DATA_URL, '/'), ENT_QUOTES, 'UTF-8'); ?>/...</code>) 로 일괄 복원합니다.
    </p>
    <p>CDN 을 완전히 제거하거나 되돌릴 때 사용합니다. (CDN 모듈 데이터 제거 시 자동 실행됩니다.)</p>
    <?php else: ?>
    <h2>토큰화 — 실제 경로를 [DATA_URL] 로 바꾸기</h2>
    <p>
        DB 안에 절대주소(<code>https://내사이트/data/...</code>) 형태로 저장된 이미지 경로를
        <code>[DATA_URL]/...</code> 토큰 형태로 일괄 변환합니다.
    </p>
    <p>
        한 번 변환해 두면 <strong>CDN 켜기/끄기 토글이 즉시</strong> 반영됩니다.
        (이미 토큰으로 저장된 행은 건너뜀 — 여러 번 실행해도 안전)
    </p>
    <?php endif; ?>
    <h2>실행 순서</h2>
    <ul>
        <li>먼저 <strong>미리보기</strong> 로 변환 대상 건수 확인</li>
        <li>이상 없으면 <strong>실행</strong> 클릭</li>
    </ul>
    <p style="color:#a00;">※ 이미 운영 중인 데이터를 건드립니다. 가능하면 실행 전에 DB를 백업하세요.</p>
</div>

<form method="post" action="./cdn_migrate.php?preview=1&amp;mode=<?php echo $mode; ?>" style="display:inline-block; margin-right:8px;">
    <button type="submit" class="btn02">미리보기 (변경 안 함)</button>
</form>
<form method="post" action="./cdn_migrate.php?mode=<?php echo $mode; ?>" style="display:inline-block;">
    <input type="hidden" name="token" value="<?php echo function_exists('get_admin_token') ? get_admin_token() : ''; ?>">
    <input type="hidden" name="mode" value="<?php echo $mode; ?>">
    <input type="hidden" name="apply" value="1">
    <button type="submit" class="btn_submit" onclick="return confirm('정말 <?php echo $is_detok ? '역토큰화' : '토큰화'; ?> 를 실행할까요? DB가 수정됩니다.');">실제 실행</button>
</form>

<?php if (!empty($report)): ?>
<h2 style="margin-top:18px;"><?php echo $apply ? '실행 결과' : '미리보기'; ?> — <?php echo $is_detok ? '역토큰화' : '토큰화'; ?></h2>
<table class="cdn-mig-report">
    <thead>
        <tr><th>테이블</th><th>컬럼</th><th>대상 행</th><th>변환</th><th>건너뜀</th><th>오류</th></tr>
    </thead>
    <tbody>
    <?php
    $sum_total=0; $sum_changed=0;
    foreach ($report as $r):
        list($tbl, $col, $stat) = $r;
        list($total, $changed, $skipped, $err) = $stat;
        $sum_total += $total; $sum_changed += $changed;
    ?>
        <tr>
            <td><?php echo htmlspecialchars($tbl, ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($col, ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo (int)$total; ?></td>
            <td class="col-changed"><?php echo (int)$changed; ?></td>
            <td class="col-skipped"><?php echo (int)$skipped; ?></td>
            <td><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="2">합계</th>
            <th><?php echo (int)$sum_total; ?></th>
            <th class="col-changed"><?php echo (int)$sum_changed; ?></th>
            <th colspan="2"></th>
        </tr>
    </tfoot>
</table>
<?php endif; ?>

<?php include_once(G5_ADMIN_PATH.'/admin.tail.php'); ?>
