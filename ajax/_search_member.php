<?php
include_once("./_common.php");

// 파라미터 정리
$keyword = trim($_REQUEST['keyword'] ?? '');
$list_obj = trim($_REQUEST['list_obj'] ?? '');
$input_obj = trim($_REQUEST['input_obj'] ?? '');
$output_obj = trim($_REQUEST['output_obj'] ?? '');

// 비회원 처리
if (!$is_member) {
    echo "<ul><li class='no-data'>회원만 검색 가능합니다.</li></ul>";
    exit;
}

// 키워드 검사
if ($keyword === '') {
    echo "<ul><li class='no-data'>키워드를 입력해 주시길 바랍니다.</li></ul>";
    exit;
}

// 검색어 이스케이프
$keyword_sql = sql_escape_string($keyword);

echo "<ul>";

// 회원 검색
$sql = "
    SELECT mb_nick, mb_name, mb_id, ch_id
    FROM {$g5['member_table']}
    WHERE mb_name LIKE '%{$keyword_sql}%'
      AND mb_level > 1
    ORDER BY mb_nick ASC
";
$result = sql_query($sql);

$count = 0;

for ($i = 0; $row = sql_fetch_array($result); $i++) {

    $ch = array(
        'ch_thumb' => '',
        'ch_name'  => ''
    );

    if (!empty($row['ch_id'])) {
        $ch = sql_fetch("
            SELECT ch_id, ch_thumb, ch_name
            FROM {$g5['character_table']}
            WHERE ch_id = '".sql_escape_string($row['ch_id'])."'
        ");
        if (is_array($ch) && function_exists('apply_character_time')) {
            apply_character_time($ch, get_current_time());
        }
    }

    $mb_id    = htmlspecialchars($row['mb_id']    ?? '', ENT_QUOTES, 'UTF-8');
    $mb_name  = htmlspecialchars($row['mb_name']  ?? '', ENT_QUOTES, 'UTF-8');
    $ch_name  = htmlspecialchars($ch['ch_name']   ?? '', ENT_QUOTES, 'UTF-8');
    $ch_thumb_raw = (function_exists('cdn_resolve_url') ? cdn_resolve_url($ch['ch_thumb'] ?? '') : ($ch['ch_thumb'] ?? ''));
    $ch_thumb = htmlspecialchars($ch_thumb_raw, ENT_QUOTES, 'UTF-8');

    $list_js   = htmlspecialchars($list_obj,   ENT_QUOTES, 'UTF-8');
    $input_js  = htmlspecialchars($input_obj,  ENT_QUOTES, 'UTF-8');
    $output_js = htmlspecialchars($output_obj, ENT_QUOTES, 'UTF-8');

    $display_ch_name = $ch_name !== '' ? $ch_name : '보유캐릭터 없음';

    // 썸네일 없는 경우 처리(선택)
    if ($ch_thumb === '') {
        $ch_thumb = G5_IMG_URL . '/no_profile.png';
    }

    ?>
    <li>
        <a href="#"
           onclick="select_item('<?php echo $list_js; ?>',
                                '<?php echo $input_js; ?>',
                                '<?php echo $mb_name; ?>',
                                '<?php echo $output_js; ?>',
                                '<?php echo $mb_id; ?>'); return false;">
            <div class="ui-thumb">
                <img src="<?php echo $ch_thumb; ?>" alt="">
            </div>
            <div class="ui-info">
                <p class="point">
                    <strong>대표캐릭명</strong>: <?php echo $display_ch_name; ?>
                </p>
                <p>
                    <strong>오너명</strong>: <?php echo $mb_name; ?>
                </p>
            </div>
        </a>
    </li>
    <?php

    $count++;
}

if ($count === 0) {
    $safe_keyword = htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8');
    echo "<li class='no-data'>[ {$safe_keyword} ]에 대한 검색결과가 존재하지 않습니다.</li>";
}

echo "</ul>";
