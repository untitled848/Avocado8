<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// $call_view_mode : 'index'(요약) | 'full'(전체 페이지). 미지정시 index
$_call_view = isset($call_view_mode) && $call_view_mode === 'full' ? 'full' : 'index';
$_rows = $_call_view === 'full'
    ? max(1, (int)($config['cf_call_page_rows']  ?? 20))
    : max(1, (int)($config['cf_call_index_rows'] ?? 5));

$mb_id_esc = sql_escape_string($member['mb_id']);
/*$call_result = sql_query("SELECT *
    FROM (
            SELECT cb.*, t.ct_name
            FROM `{$g5['call_board_table']}` cb
            LEFT JOIN `{$g5['call_type_map_table']}` t ON t.ct_code = cb.bc_type
            WHERE cb.re_mb_id = '{$mb_id_esc}'
                AND cb.bc_type <> 'memo'

            UNION ALL

            SELECT cb.*, t.ct_name
            FROM `{$g5['call_board_table']}` cb
            INNER JOIN (
                    SELECT mb_id, MAX(bc_id) AS bc_id
                    FROM `{$g5['call_board_table']}`
                    WHERE re_mb_id = '{$mb_id_esc}'
                        AND bc_type = 'memo'
                    GROUP BY mb_id
            ) memo_group ON memo_group.bc_id = cb.bc_id
            LEFT JOIN `{$g5['call_type_map_table']}` t ON t.ct_code = cb.bc_type
    ) call_list
    ORDER BY bc_id DESC LIMIT {$_rows}");
*/
$call_result = sql_query("SELECT *
                            FROM (
                                    SELECT cb.*, t.ct_name
                                    FROM `{$g5['call_board_table']}` cb
                                    LEFT JOIN `{$g5['call_type_map_table']}` t ON t.ct_code = cb.bc_type
                                    WHERE cb.re_mb_id = '{$mb_id_esc}'
                                        AND cb.bc_type <> 'memo'
                                        
                            ) call_list
                            ORDER BY bc_id DESC LIMIT {$_rows}");
 ?>
<h2 class="page-title">
    <strong>알림</strong>
    <span>Notifications<a href="<?php echo G5_URL; ?>/mypage/call.php">(전체보기)</a></span>
</h2>
<?php
$_call_sound_on = function_exists('is_call_sound_enabled') ? is_call_sound_enabled($member['mb_id']) : true;
?>
<section class="call-sound-pref" style="margin-bottom:5px;">
    <table class="theme-form">
        <colgroup>
            <col style="width:110px;" />
            <col />
        </colgroup>
        <tbody>
            <tr>
                <th scope="row">알림 소리</th>
                <td>
                    <label style="margin-right:12px;">
                        <input type="checkbox" id="call-sound-toggle" value="1" <?php echo $_call_sound_on ? 'checked' : ''; ?>>
                        호출/멘션 알림 시 소리 재생
                    </label>
                    <button type="button" class="ui-btn" id="call-sound-preview">시험 재생</button>
                    <span id="call-sound-msg" style="margin-left:8px;color:#666;font-size:12px;"></span>
                </td>
            </tr>
        </tbody>
    </table>
</section>
<script>
(function () {
    var $toggle = $('#call-sound-toggle');
    var $msg = $('#call-sound-msg');
    var prefUrl = '<?php echo G5_URL; ?>/ajax/call_pref.php';

    function flash(text) {
        $msg.text(text);
        setTimeout(function () { $msg.text(''); }, 2000);
    }

    $toggle.on('change', function () {
        var on = $toggle.is(':checked') ? 1 : 0;
        $.post(prefUrl, { call_sound: on }, function (res) {
            if (res && res.ok) {
                if (typeof window.setCallSoundEnabled === 'function') {
                    window.setCallSoundEnabled(!!res.call_sound);
                }
                flash(res.message || '저장되었습니다.');
            } else {
                flash((res && res.message) || '저장 실패');
                $toggle.prop('checked', !on);
            }
        }, 'json').fail(function () {
            flash('저장 실패');
            $toggle.prop('checked', !on);
        });
    });

    $('#call-sound-preview').on('click', function () {
        if (typeof window.playCallNotifyPreview === 'function') {
            window.playCallNotifyPreview();
        } else {
            var url = <?php echo json_encode(function_exists('get_call_notify_sound_url') ? get_call_notify_sound_url() : (G5_URL.'/sound/call_notify.wav')); ?>;
            try { new Audio(url).play(); } catch (e) {}
        }
    });
})();
</script>
<section>
<table class="theme-list">
    <colgroup>
        <col style="width:60px">
        <col style="width:60px">
        <col style="width:100%">
        <col style="width:80px">
    </colgroup>
    <thead>
        <tr>
            <th scope="col">종류</th>
            <th scope="col">보낸이</th>
            <th scope="col">내용</th>
            <th scope="col">날짜</th>
        </tr>
    </thead>
    <tbody>
<?php
$count = 0;
while ($row = sql_fetch_array($call_result)):
    $count++;
    $row_class = $row['bc_check'] ? '' : 'check';
    $type_name = $row['ct_name'] ?: $row['bc_type'];
    $sender    = htmlspecialchars($row['mb_name']);

    // board/mention 이면 본문 미리보기로 memo 치환 (100자, 이후 ...)
    // 타임라인 멘션은 bo_table=tl_table 이라 write_* 가 없을 수 있음 → 테이블 존재 확인 후 조회
    $memo_raw = (string)$row['memo'];
    if (in_array($row['bc_type'], array('board', 'mention'), true) && !empty($row['bo_table']) && !empty($row['wr_id'])) {
        $bo = preg_replace('/[^a-zA-Z0-9_]/', '', $row['bo_table']);
        $wr = (int)$row['wr_id'];
        if ($bo !== '' && $wr > 0) {
            $candidates = array();
            $tl_prefix = !empty($g5['timeline_write_prefix']) ? $g5['timeline_write_prefix'] : (G5_TABLE_PREFIX.'timeline_');
            $wr_prefix = !empty($g5['write_prefix']) ? $g5['write_prefix'] : (G5_TABLE_PREFIX.'write_');

            if ($row['bc_type'] === 'mention') {
                $candidates[] = $tl_prefix.$bo;
                $candidates[] = $wr_prefix.$bo;
            } else {
                $candidates[] = $wr_prefix.$bo;
                $candidates[] = $tl_prefix.$bo; // 예전 타임라인 멘션이 board 타입으로 들어간 경우
            }

            foreach ($candidates as $write_table) {
                $exists = sql_fetch("SHOW TABLES LIKE '".sql_escape_string($write_table)."'", false);
                if (!$exists) {
                    continue;
                }
                $w = sql_fetch("SELECT wr_content FROM `{$write_table}` WHERE wr_id = '{$wr}'", false);
                if ($w && isset($w['wr_content'])) {
                    $memo_raw = strip_tags(html_entity_decode($w['wr_content'], ENT_QUOTES, 'UTF-8'));
                }
                break;
            }
        }
    }
    $memo_text = htmlspecialchars(cut_str($memo_raw, 100, '...'));
    $dt        = $row['bc_datetime'] ? date('m/d H:i', strtotime($row['bc_datetime'])) : '';
    $link      = htmlspecialchars($row['bc_link']);
?>
        <tr class="<?php echo $row_class; ?>">
            <td><?php echo htmlspecialchars($type_name); ?></td>
            <td><?php echo $sender; ?></td>
            <td>
<?php if ($link): ?>
                <p style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <a href="<?php echo $link; ?>"><?php echo $memo_text; ?></a>
                </p>
<?php else: ?>
                <p style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo $memo_text; ?></p>
<?php endif; ?>
            </td>
            <td><?php echo $dt; ?></td>
        </tr>
<?php endwhile; ?>
<?php if ($count === 0): ?>
        <tr>
            <td colspan="5" class="no-data">알림 내역이 없습니다.</td>
        </tr>
<?php endif; ?>
    </tbody>
</table>
</section>
<?php
// 페이지 진입 시 읽음 처리 (member 테이블 mb_memo_call 도 함께 클리어 → 중복 호출 방지)
if (function_exists('call_check_clear')) {
    call_check_clear($member['mb_id']);
}
?>
<script>
jQuery(function () {
    if (typeof window.stopCallAlarm === 'function') {
        window.stopCallAlarm(false);
    }
});
</script>
