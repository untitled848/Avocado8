<?php
/**
 * 사이트 DB 점검/보정
 *
 * install/gnuboard5.sql 기준으로 코어 테이블·컬럼·인덱스를 멱등 점검·보정한다.
 * - 없는 테이블 CREATE / 빠진 컬럼·인덱스 ADD
 * - 컬럼 DROP·타입 변경·INSERT 시드·동적 write_* 테이블·모듈 전용 테이블은 대상 아님
 * - 타임라인 코어 테이블도 gnuboard5.sql 에 포함되어 여기서 함께 보정된다
 */
$sub_menu = '900400';
include_once('./_common.php');

if (!$is_admin) {
    alert('관리자만 접근 가능합니다.');
}

require_once G5_PATH.'/install/db_schema_check.lib.php';

$g5['title'] = '사이트 DB 점검/보정';
include_once('./admin.head.php');

$sql_path = db_schema_sql_path();
$specs = db_schema_load_specs($sql_path, db_schema_table_prefix());

$run_log = array();
$ran = false;
$run_ok = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_repair']) && check_admin_token()) {
    $ran = true;
    $result = db_schema_repair_all($specs);
    $run_log = $result['log'];
    $run_ok = $result['all_ok'];
    // 보정 후 재점검
    $specs = db_schema_load_specs($sql_path, db_schema_table_prefix());
}

$inspect = db_schema_inspect_all($specs);
$all_ok = $inspect['all_ok'];
$status_rows = $inspect['rows'];
$spec_count = count($specs);
?>

<div class="local_desc01 local_desc">
    <p>
        기준 스키마: <code><?php echo h($sql_path); ?></code>
        (테이블 <?php echo (int)$spec_count; ?>개, prefix <code><?php echo h(db_schema_table_prefix()); ?></code>)
    </p>
    <p>
        없는 테이블·빠진 컬럼·인덱스를 <strong>추가만</strong> 합니다. 컬럼 삭제·타입 변경·데이터 INSERT는 하지 않습니다.<br>
        게시판/타임라인 동적 <code>write_*</code>·모듈 전용 테이블은 여기 대상이 아닙니다.
        일회성 SQL은 <a href="./migrate.php">DB 마이그레이션</a>, 타임라인 관리는 <a href="./timeline_list.php">타임라인 목록</a>을 사용하세요.
    </p>
</div>

<?php if (!is_file($sql_path)) { ?>
<div class="local_desc02 local_desc" style="background:#f2dede;border:1px solid #ebccd1;padding:10px;margin-bottom:12px;">
    gnuboard5.sql 파일을 읽을 수 없습니다.
</div>
<?php } elseif ($spec_count === 0) { ?>
<div class="local_desc02 local_desc" style="background:#f2dede;border:1px solid #ebccd1;padding:10px;margin-bottom:12px;">
    CREATE TABLE 스펙을 파싱하지 못했습니다. SQL 파일 형식을 확인하세요.
</div>
<?php } ?>

<?php if ($ran) { ?>
<div class="local_desc02 local_desc" style="background:<?php echo $run_ok ? '#e8ffe8' : '#fff8e8'; ?>;border:1px solid <?php echo $run_ok ? '#cde6cd' : '#e6d9b8'; ?>;padding:10px;margin-bottom:12px;">
    스키마 점검/보정을 실행했습니다.<?php echo $run_ok ? '' : ' 일부 항목에 잔여 차이가 있습니다.'; ?>
</div>
<?php } ?>

<div class="local_desc02 local_desc" style="margin-bottom:12px;">
    <?php if ($all_ok) { ?>
        상태: <strong style="color:#080;">전체 정상</strong>
    <?php } else { ?>
        상태: <strong style="color:#c00;">보정 필요</strong>
        — 테이블 없음 <?php echo (int)$inspect['missing_tables']; ?> /
        컬럼·인덱스 부족 <?php echo (int)$inspect['drift']; ?>
    <?php } ?>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption>테이블 상태</caption>
        <thead>
            <tr>
                <th>구분</th>
                <th>테이블</th>
                <th>상태</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($status_rows)) { ?>
            <tr>
                <td colspan="3" class="empty_table">스펙이 없습니다.</td>
            </tr>
        <?php } else { foreach ($status_rows as $row) { ?>
            <tr<?php echo $row['ok'] ? '' : ' style="background:#fff8f8;"'; ?>>
                <td><?php echo h($row['label']); ?></td>
                <td><code><?php echo h($row['table']); ?></code></td>
                <td style="color:<?php echo $row['ok'] ? '#080' : '#c00'; ?>">
                    <?php echo h($row['status']); ?>
                </td>
            </tr>
        <?php } } ?>
        </tbody>
    </table>
</div>

<?php if (!empty($run_log)) { ?>
<div class="tbl_head01 tbl_wrap">
    <table>
        <caption>실행 결과</caption>
        <thead>
            <tr>
                <th>구분</th>
                <th>테이블</th>
                <th>결과</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($run_log as $r) { ?>
            <tr>
                <td><?php echo h($r['label']); ?></td>
                <td><code><?php echo h($r['table']); ?></code></td>
                <td style="color:<?php echo !empty($r['ok']) ? '#080' : '#c00'; ?>">
                    <?php echo h($r['message']); ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<?php } ?>

<div class="btn_confirm01 btn_confirm">
    <form method="post" action="./db_check.php" style="display:inline;">
        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
        <input type="hidden" name="run_repair" value="1">
        <input type="submit"
               value="<?php echo $all_ok ? '스키마 다시 확인/보정' : '빠진 테이블·컬럼·인덱스 보정'; ?>"
               class="btn_submit btn"
               onclick="return confirm('gnuboard5.sql 기준으로 사이트 DB를 점검/보정할까요?\n(추가만 수행, 삭제·타입변경 없음)');"
               <?php echo ($spec_count === 0) ? ' disabled' : ''; ?>>
    </form>
    <a href="./migrate.php" class="btn btn_02">DB 마이그레이션</a>
    <a href="./timeline_list.php" class="btn btn_02">타임라인 목록</a>
</div>

<?php
include_once('./admin.tail.php');
