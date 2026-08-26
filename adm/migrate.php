<?php
/**
 * adm/migrate.php — 일회성 DB 마이그레이션 실행기 (개발 환경용)
 *
 * - install/migrate_*.sql 파일을 자동 발견하여 한 건씩 실행할 수 있는 페이지.
 * - 실행 이력은 data/migrate_log.json 에 저장.
 * - 신규 마이그레이션 추가 시: install/migrate_<wave>_<topic>.sql 한 파일 추가하면
 *   이 페이지에 자동 노출.
 */
$sub_menu = '900300';
include_once('./_common.php');

$migrate_dir   = G5_PATH.'/install';
$log_path      = G5_DATA_PATH.'/migrate_log.json';

// 로그 적재
$log = array();
if (is_file($log_path)) {
    $raw = @file_get_contents($log_path);
    $tmp = $raw ? json_decode($raw, true) : array();
    if (is_array($tmp)) $log = $tmp;
}

// POST: 실행
$run_msg = '';
$run_err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run']) && check_admin_token()) {
    $file = basename((string)$_POST['run']);
    $full = $migrate_dir.'/'.$file;
    if (!preg_match('/^migrate_[a-z0-9_]+\.sql$/i', $file) || !is_file($full)) {
        $run_err = '잘못된 파일: '.htmlspecialchars($file);
    } else {
        $sql_text = (string)file_get_contents($full);
        // 단순 split: 세미콜론 + 줄바꿈 기준 (마이그레이션 SQL 은 PROCEDURE/TRIGGER 미사용)
        $stmts = preg_split('/;\s*[\r\n]/', $sql_text);
        $ok = 0; $err = 0; $messages = array();
        foreach ($stmts as $s) {
            // 라인별 -- 주석 제거 후 빈 문자열이면 skip
            $lines = preg_split('/\r\n|\r|\n/', $s);
            $clean_lines = array();
            foreach ($lines as $ln) {
                $lt = ltrim($ln);
                if ($lt === '' || strpos($lt, '--') === 0 || strpos($lt, '#') === 0) continue;
                $clean_lines[] = $ln;
            }
            $s = trim(implode("\n", $clean_lines));
            if ($s === '') continue;
            $r = @sql_query($s, false);
            if ($r === false) {
                $err++;
                $messages[] = '['.$file.'] ERROR ('.substr($s, 0, 120).'...): '.sql_error();
            } else {
                $ok++;
            }
        }
        $log[$file] = array(
            'time' => date('Y-m-d H:i:s'),
            'ok'   => $ok,
            'err'  => $err,
            'mb_id'=> isset($member['mb_id']) ? $member['mb_id'] : '',
        );
        @file_put_contents($log_path, json_encode($log, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        $run_msg = $file.' 실행 완료 — 성공 '.$ok.' / 실패 '.$err;
        if (!empty($messages)) $run_msg .= "\n".implode("\n", $messages);
    }
}

// 파일 스캔
$files = array();
if (is_dir($migrate_dir)) {
    foreach (glob($migrate_dir.'/migrate_*.sql') as $fp) {
        $files[] = basename($fp);
    }
    sort($files);
}

$g5['title'] = 'DB 마이그레이션';
include_once('./admin.head.php');
?>

<details class="adm-panel" open>
<summary>DB 마이그레이션 실행 <span style="font-weight:normal;color:#666;">— install/migrate_*.sql 파일 자동 검출</span></summary>
<div class="adm-panel-body">
<p class="adm-panel-help">
	개발 환경에서 일회성 DB 변경(테이블/컬럼 추가)을 적용합니다.<br>
	각 파일은 <strong>여러 번 실행해도 안전</strong>해야 합니다(IF NOT EXISTS / DROP+CREATE 권장).<br>
	신규 변경 시 <code>install/migrate_&lt;wave&gt;_&lt;topic&gt;.sql</code> 파일을 추가하면 자동으로 이 페이지에 노출됩니다.
</p>

<?php if ($run_msg): ?>
<div style="background:#dff0d8;color:#3c763d;padding:10px;border:1px solid #d6e9c6;border-radius:3px;margin-bottom:10px;white-space:pre-wrap;"><?php echo htmlspecialchars($run_msg) ?></div>
<?php endif; ?>
<?php if ($run_err): ?>
<div style="background:#f2dede;color:#a94442;padding:10px;border:1px solid #ebccd1;border-radius:3px;margin-bottom:10px;"><?php echo $run_err ?></div>
<?php endif; ?>

<table class="tbl_head01" style="width:100%;">
<thead>
<tr><th style="text-align:left;padding:6px 8px;">파일</th><th style="padding:6px 8px;">최근 실행</th><th style="padding:6px 8px;">결과</th><th style="padding:6px 8px;">실행</th></tr>
</thead>
<tbody>
<?php if (empty($files)): ?>
<tr><td colspan="4" style="padding:14px;text-align:center;color:#888;">실행할 마이그레이션 파일이 없습니다.</td></tr>
<?php else: foreach ($files as $f):
    $info = isset($log[$f]) ? $log[$f] : null;
?>
<tr>
	<td style="padding:6px 8px;"><code><?php echo htmlspecialchars($f) ?></code></td>
	<td style="padding:6px 8px;text-align:center;"><?php echo $info ? htmlspecialchars($info['time']) : '<span style="color:#aaa;">미실행</span>' ?></td>
	<td style="padding:6px 8px;text-align:center;">
		<?php if ($info): ?>
			성공 <?php echo (int)$info['ok'] ?> / 실패 <?php echo (int)$info['err'] ?>
		<?php endif; ?>
	</td>
	<td style="padding:6px 8px;text-align:center;">
		<form method="post" action="" style="display:inline;" onsubmit="return confirm('<?php echo htmlspecialchars($f) ?> 을(를) 실행합니다. 계속할까요?');">
			<?php echo get_admin_token_field(); ?>
			<input type="hidden" name="run" value="<?php echo htmlspecialchars($f) ?>">
			<button type="submit" class="btn_submit"<?php echo $info ? ' style="background:#888;"' : '' ?>><?php echo $info ? '재실행' : '실행' ?></button>
		</form>
	</td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>

<p class="adm-panel-help" style="margin-top:14px;">
	ⓘ 신규 설치는 <code>install/gnuboard5.sql</code> 에 이미 반영되어 있으므로 마이그레이션이 필요하지 않습니다.
</p>
</div>
</details>

<?php
include_once('./admin.tail.php');
