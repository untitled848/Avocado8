<?php
/**
 * notice_grid_migrate.php
 * 공지 그리드 설정용 config 컬럼 추가 마이그레이션.
 * 브라우저에서 한 번만 실행 → 이후 삭제 권장.
 */
$sub_menu = "";
include_once('./_common.php');
if (!$is_admin) alert('관리자만 실행할 수 있습니다.');

$table = $g5['config_table'];
$columns = array(
    'cf_notice_list_mode'  => "VARCHAR(10)  NOT NULL DEFAULT 'list'      COMMENT '공지 목록 표시 방식 list|grid'",
    'cf_notice_grid_mode'  => "VARCHAR(20)  NOT NULL DEFAULT 'auto-fill' COMMENT '그리드 모드'",
    'cf_notice_grid_count' => "TINYINT      NOT NULL DEFAULT 3            COMMENT '고정 컬럼 수'",
    'cf_notice_card_min_w' => "VARCHAR(20)  NOT NULL DEFAULT '240px'      COMMENT '카드 최소 너비(px/%/em 등)'",
    'cf_notice_ar_w'       => "TINYINT      NOT NULL DEFAULT 16           COMMENT '썸네일 비율 가로'",
    'cf_notice_ar_h'       => "TINYINT      NOT NULL DEFAULT 9            COMMENT '썸네일 비율 세로'",
);

$log = array();
foreach ($columns as $col => $def) {
    $r = sql_fetch("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME   = '".sql_real_escape_string($table)."'
                      AND COLUMN_NAME  = '".sql_real_escape_string($col)."'", false);
    if (empty($r['c'])) {
        sql_query("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$def}", false);
        $log[] = array('ok' => true,  'msg' => "추가 완료: {$col}");
    } else {
        $log[] = array('ok' => false, 'msg' => "이미 존재함: {$col}");
    }
}

// 단독 쿼리 버전 (복사 후 MySQL 클라이언트에서 직접 실행 가능)
$raw_sql = "-- 공지 그리드 설정 컬럼 추가 (테이블 접두어 확인 필요)\n";
foreach ($columns as $col => $def) {
    $raw_sql .= "ALTER TABLE `{$table}` ADD COLUMN IF NOT EXISTS `{$col}` {$def};\n";
}

$g5['title'] = '공지 그리드 설정 마이그레이션';
include_once(G5_ADMIN_PATH.'/admin.head.php');
?>
<div style="padding:20px; max-width:720px;">
<h2>공지 그리드 설정 컬럼 추가 마이그레이션</h2>
<ul style="line-height:2; font-family:monospace;">
<?php foreach ($log as $item): ?>
<li style="color:<?php echo $item['ok'] ? '#197d18' : '#888'; ?>;">
    <?php echo $item['ok'] ? '✓' : '–'; ?> <?php echo htmlspecialchars($item['msg'], ENT_QUOTES); ?>
</li>
<?php endforeach; ?>
</ul>

<h3 style="margin-top:20px;">Raw SQL (클라이언트 직접 실행용)</h3>
<textarea style="width:100%;height:180px;font-family:monospace;font-size:12px;"><?php echo htmlspecialchars($raw_sql, ENT_QUOTES); ?></textarea>

<p style="margin-top:16px;">
    <a href="./notice_list.php" class="btn btn_02" style="padding:8px 18px;">공지 목록 관리로 이동</a>
</p>
</div>
<?php include_once(G5_ADMIN_PATH.'/admin.tail.php'); ?>
