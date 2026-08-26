<?php
include_once('./_common.php');
include_once('./_head.php');

define('_CHARACTER_FORM_', true);

// 기본 환경 방어
$member = isset($member) && is_array($member) ? $member : array();
$config = isset($config) && is_array($config) ? $config : array();
$g5     = isset($g5) && is_array($g5) ? $g5 : array();

$mb = $member;

// 요청값 정리
$w = trim($_REQUEST['w'] ?? '');
$ch_id = (int)($_REQUEST['ch_id'] ?? 0);
$it_id = trim($_REQUEST['it_id'] ?? '');
$in_id = (int)($_REQUEST['in_id'] ?? 0);

// 시기(time) 결정: 신규(w='') 는 무조건 1, 수정 시만 ?time= 허용
$time = ($w === 'u') ? (int)ses($_REQUEST, 'time', 1, 'int') : 1;
if ($time < 1) $time = 1;
if ($time > 1) {
    $info = get_time_by_order($time);
    if (empty($info['ti_id'])) $time = 1;
}

// 옵션 플래그 (정의 안 되어 있으면 false 처리)
$is_add_character = !empty($is_add_character);
$is_mod_character = !empty($is_mod_character);
$is_admin         = !empty($is_admin);

// 기본 프로필 설정값 로드 (없으면 기본값)
if (!isset($ad) || !is_array($ad)) {
    if (!empty($g5['article_default_table'])) {
        $ad = sql_fetch("SELECT * FROM {$g5['article_default_table']} LIMIT 1");
    }
    if (!is_array($ad)) {
        $ad = array();
    }
}
$ad += array(
    'ad_use_thumb' => 0,
    'ad_use_head'  => 0,
    'ad_use_body'  => 0,
    'ad_use_status'=> 0,
    'ad_text_name' => '캐릭터명',
    'ad_help_name' => '',
    'ad_text_thumb'=> '프로필',
    'ad_help_thumb'=> '',
    'ad_text_head' => '상반신',
    'ad_help_head' => '',
    'ad_text_body' => '전신',
    'ad_help_body' => '',
    'ad_url_thumb' => '',
    'ad_url_head'  => '',
    'ad_url_body'  => '',
);

// 인벤토리 아이템
$in = array();
if ($in_id > 0 && function_exists('get_inventory_item')) {
    $in = get_inventory_item($in_id);
    if (!is_array($in)) $in = array();
}

// 추가 항목 설정
$ch_ar = array();
$str_secret = " WHERE 1 ";

if (!empty($member['mb_id']) && $member['mb_id'] === $mb['mb_id']) {
    $str_secret .= " AND ar_secret != 'H' ";
} else {
    $str_secret .= " AND ar_secret = '' ";
}

if (!empty($g5['article_table'])) {
    $ar_result = sql_query("SELECT * FROM {$g5['article_table']} {$str_secret} ORDER BY ar_order ASC");
    for ($i = 0, $j = 0; $row = sql_fetch_array($ar_result); $i++) {
        // ar_time 필터 — 폼 시기($time) 기준으로 판별. ar_time 비어있으면 전체 시기 노출
        if (function_exists('shop_time_matches') && !shop_time_matches($row['ar_time'], $time)) {
            continue;
        }
        $ch_ar[$j++] = $row;
    }
}

// 스탯 설정
$status = array();
if (!empty($ad['ad_use_status']) && !empty($g5['status_config_table'])) {
    $st_result = sql_query("SELECT * FROM {$g5['status_config_table']} ORDER BY st_order ASC");
    for ($i = 0; $row = sql_fetch_array($st_result); $i++) {
        $status[$i] = $row;
    }
}

// 세력 정보 — 시기(${time}) 에서 활성화된 side 만 노출
require_once G5_EXTEND_PATH.'/02_side_class_time.lib.php';
$ch_si = array();
if (!empty($config['cf_side_title']) && !empty($g5['side_table'])) {
    $_sides = function_exists('get_sides_for_time') ? get_sides_for_time((int)$time, (int)$member['mb_level']) : null;
    if ($_sides === null) {
        // 폴백: 전체 활성 (매핑 릿을 안 쓴 상태)
        $side_result = sql_query("SELECT si_id, si_name FROM {$g5['side_table']} WHERE si_auth <= '".(int)$member['mb_level']."' ORDER BY si_id ASC");
        for ($i = 0; $row = sql_fetch_array($side_result); $i++) {
            $ch_si[$i] = array('id' => $row['si_id'], 'name' => $row['si_name']);
        }
    } else {
        foreach ($_sides as $i => $row) {
            $ch_si[$i] = array('id' => $row['si_id'], 'name' => $row['si_name']);
        }
    }
}

// 종족 정보 — 시기 활성 class 만
$ch_cl = array();
if (!empty($config['cf_class_title']) && !empty($g5['class_table'])) {
    $_classes = function_exists('get_classes_for_time') ? get_classes_for_time((int)$time, (int)$member['mb_level']) : null;
    if ($_classes === null) {
        $class_result = sql_query("SELECT cl_id, cl_name FROM {$g5['class_table']} WHERE cl_auth <= '".(int)$member['mb_level']."' ORDER BY cl_id ASC");
        for ($i = 0; $row = sql_fetch_array($class_result); $i++) {
            $ch_cl[$i] = array('id' => $row['cl_id'], 'name' => $row['cl_name']);
        }
    } else {
        foreach ($_classes as $i => $row) {
            $ch_cl[$i] = array('id' => $row['cl_id'], 'name' => $row['cl_name']);
        }
    }
}

// 캐릭터 기본 데이터 준비
$ch = array();

if ($w === '') {
    // 신규
    if (!$is_add_character && !$is_admin) {
        alert("현재 캐릭터 생성 기간이 아닙니다.", "./index.php");
    }

    if (!empty($g5['character_table'])) {
        $cnt_row = sql_fetch("SELECT COUNT(ch_id) AS cnt FROM {$g5['character_table']} WHERE mb_id = '{$member['mb_id']}'");
        $ch_cnt = (int)($cnt_row['cnt'] ?? 0);
    } else {
        $ch_cnt = 0;
    }

    $max_cnt = (int)($config['cf_character_count'] ?? 0);
    if ($max_cnt > 0 && $ch_cnt > $max_cnt && !$is_admin) {
        alert("생성 가능한 캐릭터 갯수를 초과하였습니다.", "./index.php");
    }

    $ch['ch_state'] = '수정중';
    $ch['ch_type']  = 'main';
    $ch['mb_id']    = $member['mb_id'] ?? '';
    $ch['ch_point'] = (int)($config['cf_status_point'] ?? 0);

} else {
    // 수정
    if (empty($g5['character_table'])) {
        alert("캐릭터 정보 테이블이 정의되지 않았습니다.");
    }

    $ch = sql_fetch("SELECT * FROM {$g5['character_table']} WHERE ch_id = '{$ch_id}'");
    if (empty($ch['ch_id'])) {
        alert("캐릭터 내역이 존재하지 않습니다.");
    }

    if (!$is_mod_character && !$is_admin) {
        // 아이템 검증
        if (empty($in['ch_id']) || empty($in['it_type']) || $in['ch_id'] != $ch['ch_id'] || $in['it_type'] !== '프로필수정') {
            alert("캐릭터 수정 기간이 아닙니다.");
        }
    }

    // 시기 쓰기 권한 체크 (시기 N 수정 폼)
    if ($time > 1) {
        $_is_owner = !empty($member['mb_id']) && $ch['mb_id'] === $member['mb_id'];
        if (!check_time_write_access($time, (bool)$is_admin, $_is_owner)) {
            time_safe_redirect(G5_URL.'/mypage/character/viewer.php', array('ch_id' => $ch_id));
        }
    }

    // 추가 항목 값 로드
    if (!empty($g5['value_table'])) {
        $av_result = sql_query("SELECT * FROM {$g5['value_table']} WHERE ch_id = '{$ch['ch_id']}'");
        for ($i = 0; $row = sql_fetch_array($av_result); $i++) {
            $code = $row['ar_code'];
            $ch[$code] = $row['av_value'];
        }
    }

    // 시기 N(>1): 사이드 테이블 값으로 override.
    // 이름(ch_name)만 빈 값일 경우 시기 1→이름 폴백. 나머지는 빈 값이면 빈 값 그대로 (폴백 안 함).
    if ($time > 1) {
        // 이름 폴백용 원본(시기 1) 이름 보관
        $name_fallback = isset($ch['ch_name']) ? (string)$ch['ch_name'] : '';

        // 기본 정보 (이름/썸네일/머리/전신)
        $ctv = get_character_time_value((int)$ch['ch_id'], $time);
        if (!empty($ctv)) {
            $ch['ch_name']  = $ctv['ctv_name'] !== '' ? $ctv['ctv_name'] : $name_fallback;
            $ch['ch_thumb'] = $ctv['ctv_thumb'];
            $ch['ch_head']  = $ctv['ctv_head'];
            $ch['ch_body']  = $ctv['ctv_body'];
        } else {
            // 시기 N 레코드 없음 → 이름만 원본 유지, 나머지 빈 값
            $ch['ch_name']  = $name_fallback;
            $ch['ch_thumb'] = '';
            $ch['ch_head']  = '';
            $ch['ch_body']  = '';
        }

        // 시기 N: ch_side / ch_class override 값 적용 (ctv_side / ctv_class 우선, 폴백은 resolve_character_side_class)
        if (function_exists('resolve_character_side_class')) {
            $ch['ch_side']  = resolve_character_side_class($ch, $time, 'side');
            $ch['ch_class'] = resolve_character_side_class($ch, $time, 'class');
        }

        // article 값: 시기 N 값 그대로 (없으면 빈 값). 이미 로드된 시기 1 값 덮어쓰기.
        foreach ($ch_ar as $ar) {
            if (empty($ar['ar_code'])) continue;
            $code = $ar['ar_code'];
            $ch[$code] = get_character_time_article((int)$ch['ch_id'], $code, $time);
        }
    }
}
?>

<?php if ($w === 'u') { print_time_tabs($time, (bool)$is_admin, true); } ?>
<form name="fwrite" id="fwrite" action="./character_form_update.php" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off" class="ui-register-form">
    <input type="hidden" name="w" value="<?php echo htmlspecialchars($w, ENT_QUOTES); ?>">
    <input type="hidden" name="time" value="<?php echo (int)$time; ?>">
    <input type="hidden" name="mb_id" value="<?php echo htmlspecialchars($ch['mb_id'] ?? '', ENT_QUOTES); ?>">
    <input type="hidden" name="it_id" value="<?php echo htmlspecialchars($it_id, ENT_QUOTES); ?>">
    <input type="hidden" name="in_id" value="<?php echo (int)$in_id; ?>">

    <input type="hidden" name="ch_id" value="<?php echo (int)($ch['ch_id'] ?? 0); ?>">
    <input type="hidden" name="ch_point" value="<?php echo (int)($ch['ch_point'] ?? 0); ?>">

<?php
// 관리자는 캐릭터 타입을 임의 지정 가능
$is_admin_for_form = !empty($is_admin);
$ch_member_auto_approve = !empty($ch_member_auto_approve);
$hide_member_state = !$is_admin_for_form && ($ch_member_auto_approve || (($ch['ch_state'] ?? '') === '승인'));
?>

<?php if ($hide_member_state) { ?>
    <input type="hidden" name="ch_state" value="<?php echo $ch_member_auto_approve ? '승인' : htmlspecialchars($ch['ch_state'] ?? '승인', ENT_QUOTES); ?>">
    <input type="hidden" name="ch_type" value="<?php echo htmlspecialchars($ch['ch_type'] ?? 'main', ENT_QUOTES); ?>">
<?php } else { ?>

    <h3 class="sub-title">신청서 상태</h3>
    <table class="theme-form">
        <colgroup>
            <col style="width:110px;">
            <col>
        </colgroup>
        <tbody>
            <?php
            if ($is_admin_for_form):
                $ct = $ch['ch_type'] ?? 'main';
            ?>
                <tr>
                    <th>캐릭터 타입</th>
                    <td>
                        <select name="ch_type" style="margin-bottom:6px;">
                            <option value="main" <?php echo $ct==='main'?'selected':''; ?>>main (메인)</option>
                            <option value="sub"  <?php echo $ct==='sub' ?'selected':''; ?>>sub (서브)</option>
                            <option value="npc"  <?php echo $ct==='npc' ?'selected':''; ?>>npc</option>
                        </select>
                    </td>
                </tr>
            <?php else: ?>
                <input type="hidden" name="ch_type" value="<?php echo htmlspecialchars($ch['ch_type'] ?? 'main', ENT_QUOTES); ?>">
            <?php endif; ?>
          
            <tr>
                <th>신청서상태</th>
                <td>
                    <?php echo help('※ 신청서 작성 완료 후, <span class="txt-point">[수정완료]</span>로 변경해 주시길 바랍니다.'); ?>
                    <select name="ch_state">
                        <option value="수정중" <?php echo (($ch['ch_state'] ?? '') === "수정중") ? 'selected' : ''; ?>>수정중</option>
                        <option value="대기"   <?php echo (($ch['ch_state'] ?? '') === "대기")   ? 'selected' : ''; ?>>수정완료</option>
                        <?php if ($is_admin_for_form): ?>
                        <option value="승인"   <?php echo (($ch['ch_state'] ?? '') === "승인")   ? 'selected' : ''; ?>>승인 (관리자 전용)</option>
                        <?php endif; ?>
                    </select>
                </td>
            </tr>
        </tbody>
    </table>
    <hr class="padding">
<?php } ?>

    <h3 class="sub-title">캐릭터 기본정보</h3>
    <table class="theme-form">
        <colgroup>
            <col style="width:110px;">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><?php echo htmlspecialchars($ad['ad_text_name'], ENT_QUOTES); ?><?php echo !empty($ad['ad_required_name']) ? ' <span style="color:#e74c3c;">*</span>' : ''; ?></th>
            <td>
                <p class="ui-helper"><?php echo $ad['ad_help_name']; ?></p>
                <?php
                $_n_min = (int)($ad['ad_min_length_name'] ?? 0);
                $_n_max = (int)($ad['ad_max_length_name'] ?? 0);
                $_n_attrs = '';
                if ($_n_min > 0) $_n_attrs .= ' minlength="'.$_n_min.'"';
                if ($_n_max > 0) $_n_attrs .= ' maxlength="'.$_n_max.'"';
                if (!empty($ad['ad_required_name'])) $_n_attrs .= ' required';
                $_n_hint = '';
                if ($_n_min > 0 && $_n_max > 0)      $_n_hint = $_n_min.'~'.$_n_max.'자';
                else if ($_n_max > 0)                $_n_hint = '최대 '.$_n_max.'자';
                else if ($_n_min > 0)                $_n_hint = '최소 '.$_n_min.'자';
                ?>
                <input type="text" name="ch_name" id="ch_name" value="<?php echo htmlspecialchars($ch['ch_name'] ?? '', ENT_QUOTES); ?>"<?php echo $_n_attrs; ?>>
                <?php if ($_n_hint !== '') echo '<div class="ar-len-hint">'.$_n_hint.'</div>'; ?>
            </td>
        </tr>

<?php if (!empty($config['cf_side_title']) && !empty($ch_si)) { ?>
        <tr>
            <th><?php echo htmlspecialchars($config['cf_side_title'], ENT_QUOTES); ?></th>
            <td>
                <select name="ch_side" id="ch_side">
                    <option value=""><?php echo htmlspecialchars($config['cf_side_title'], ENT_QUOTES); ?> 선택</option>
<?php foreach ($ch_si as $si) { ?>
                    <option value="<?php echo (int)$si['id']; ?>" <?php echo (($ch['ch_side'] ?? '') == $si['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($si['name'], ENT_QUOTES); ?>
                    </option>
<?php } ?>
                </select>
            </td>
        </tr>
<?php } ?>

<?php if (!empty($config['cf_class_title']) && !empty($ch_cl)) { ?>
        <tr>
            <th><?php echo htmlspecialchars($config['cf_class_title'], ENT_QUOTES); ?></th>
            <td>
                <select name="ch_class" id="ch_class">
                    <option value=""><?php echo htmlspecialchars($config['cf_class_title'], ENT_QUOTES); ?> 선택</option>
<?php foreach ($ch_cl as $cl) { ?>
                    <option value="<?php echo (int)$cl['id']; ?>" <?php echo (($ch['ch_class'] ?? '') == $cl['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cl['name'], ENT_QUOTES); ?>
                    </option>
<?php } ?>
                </select>
            </td>
        </tr>
<?php } ?>

        </tbody>
    </table>
    <hr class="padding">
<?php if (!empty($ad['ad_use_thumb'])||!empty($ad['ad_use_head'])||!empty($ad['ad_use_body'])) { ?>
        <h3 class="sub-title">캐릭터 이미지 정보</h3>
        <table class="theme-form">
            <colgroup>
                <col style="width:110px;">
                <col>
            </colgroup>
            <tbody>
    <?php if (!empty($ad['ad_use_thumb'])) { ?>
            <tr>
                <th><?php echo htmlspecialchars($ad['ad_text_thumb'], ENT_QUOTES); ?><?php echo !empty($ad['ad_required_thumb']) ? ' <span style="color:#e74c3c;">*</span>' : ''; ?></th>
                <td>
                    <?php echo help($ad['ad_help_thumb']); ?>
    <?php if (!empty($ad['ad_url_thumb'])) { ?>
                    <input type="text" name="ch_thumb" value="<?php echo htmlspecialchars($ch['ch_thumb'] ?? '', ENT_QUOTES); ?>"<?php echo !empty($ad['ad_required_thumb']) ? ' required' : ''; ?>>
    <?php } else { ?>
                    <input type="file" name="ch_thumb_file" accept="image/*"<?php echo !empty($ad['ad_required_thumb']) && empty($ch['ch_thumb']) ? ' required' : ''; ?>>
                    <input type="hidden" name="ch_thumb" value="<?php echo htmlspecialchars($ch['ch_thumb'] ?? '', ENT_QUOTES); ?>">
    <?php } ?>
    <?php if (!empty($ch['ch_thumb'])) { ?>
                    <a href="<?php echo htmlspecialchars($ch['ch_thumb'], ENT_QUOTES); ?>" class="ui-btn" target="_blank">
                        <?php echo htmlspecialchars($ad['ad_text_thumb'], ENT_QUOTES); ?> 이미지 확인
                    </a>
    <?php } ?>
                </td>
            </tr>
    <?php } ?>

    <?php if (!empty($ad['ad_use_head'])) { ?>
            <tr>
                <th><?php echo htmlspecialchars($ad['ad_text_head'], ENT_QUOTES); ?><?php echo !empty($ad['ad_required_head']) ? ' <span style="color:#e74c3c;">*</span>' : ''; ?></th>
                <td>
                    <?php echo help($ad['ad_help_head']); ?>
    <?php if (!empty($ad['ad_url_head'])) { ?>
                    <input type="text" name="ch_head" value="<?php echo htmlspecialchars($ch['ch_head'] ?? '', ENT_QUOTES); ?>"<?php echo !empty($ad['ad_required_head']) ? ' required' : ''; ?>>
    <?php } else { ?>
                    <input type="file" name="ch_head_file" accept="image/*"<?php echo !empty($ad['ad_required_head']) && empty($ch['ch_head']) ? ' required' : ''; ?>>
                    <input type="hidden" name="ch_head" value="<?php echo htmlspecialchars($ch['ch_head'] ?? '', ENT_QUOTES); ?>">
    <?php } ?>
    <?php if (!empty($ch['ch_head'])) { ?>
                    <a href="<?php echo htmlspecialchars($ch['ch_head'], ENT_QUOTES); ?>" class="ui-btn" target="_blank">
                        <?php echo htmlspecialchars($ad['ad_text_head'], ENT_QUOTES); ?> 이미지 확인
                    </a>
    <?php } ?>
                </td>
            </tr>
    <?php } ?>

    <?php if (!empty($ad['ad_use_body'])) { ?>
            <tr>
                <th><?php echo htmlspecialchars($ad['ad_text_body'], ENT_QUOTES); ?><?php echo !empty($ad['ad_required_body']) ? ' <span style="color:#e74c3c;">*</span>' : ''; ?></th>
                <td>
                    <?php echo help($ad['ad_help_body']); ?>
    <?php if (!empty($ad['ad_url_body'])) { ?>
                    <input type="text" name="ch_body" value="<?php echo htmlspecialchars($ch['ch_body'] ?? '', ENT_QUOTES); ?>"<?php echo !empty($ad['ad_required_body']) ? ' required' : ''; ?>>
    <?php } else { ?>
                    <input type="file" name="ch_body_file" accept="image/*"<?php echo !empty($ad['ad_required_body']) && empty($ch['ch_body']) ? ' required' : ''; ?>>
                    <input type="hidden" name="ch_body" value="<?php echo htmlspecialchars($ch['ch_body'] ?? '', ENT_QUOTES); ?>">
    <?php } ?>
    <?php if (!empty($ch['ch_body'])) { ?>
                    <a href="<?php echo htmlspecialchars($ch['ch_body'], ENT_QUOTES); ?>" class="ui-btn" target="_blank">
                        <?php echo htmlspecialchars($ad['ad_text_body'], ENT_QUOTES); ?> 이미지 확인
                    </a>
    <?php } ?>
                </td>
            </tr>
    <?php } ?>
            </tbody>
        </table>
        <hr class="padding">
<?php } ?>
<?php if (!empty($ad['ad_use_status'])) { ?>
    <h3 class="sub-title">
        캐릭터 스탯 정보
        <span style="float:right;">
            <em class="txt-point" data-type="point_space">
                <?php echo function_exists('get_space_status') ? get_space_status($ch['ch_id'] ?? 0) : 0; ?>
            </em>
            /
            <?php echo (int)($config['cf_status_point'] ?? 0); ?>
        </span>
    </h3>
    <div class="theme-box">
        <?php include_once('./status.inc.php'); ?>
    </div>
    <hr class="padding">
<?php } ?>

<?php if (!empty($ch_ar)) { ?>
    <h3 class="sub-title">추가 캐릭터 프로필 정보</h3>
    <table class="theme-form">
        <colgroup>
            <col style="width:110px;">
            <col>
        </colgroup>
        <tbody>
<?php foreach ($ch_ar as $i => $ar) {
    $key = $ar['ar_code'];
    $val = $ch[$key] ?? '';
    $style = '';

    if (!empty($ar['ar_size'])) {
        if ($ar['ar_type'] !== 'textarea') {
            $style = "style=\"width:{$ar['ar_size']}px;\"";
        } else {
            $style = "style=\"width:100%;height:{$ar['ar_size']}px;\"";
        }
    } else {
        $style = "style=\"width:100%;\"";
    }

    // 글자수/필수 속성 (text/textarea 만 적용)
    $ar_attrs = '';
    $ar_len_hint = '';
    if ($ar['ar_type'] === 'text' || $ar['ar_type'] === 'textarea') {
        if (!empty($ar['ar_min_length'])) $ar_attrs .= ' minlength="'.(int)$ar['ar_min_length'].'"';
        if (!empty($ar['ar_max_length'])) $ar_attrs .= ' maxlength="'.(int)$ar['ar_max_length'].'"';
        $_amin = (int)($ar['ar_min_length'] ?? 0);
        $_amax = (int)($ar['ar_max_length'] ?? 0);
        if ($_amin > 0 && $_amax > 0)      $ar_len_hint = $_amin.'~'.$_amax.'자';
        else if ($_amax > 0)               $ar_len_hint = '최대 '.$_amax.'자';
        else if ($_amin > 0)               $ar_len_hint = '최소 '.$_amin.'자';
    }
    if (!empty($ar['ar_required'])) $ar_attrs .= ' required';
?>
        <tr>
            <th>
                <input type="hidden" name="ar_code[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($ar['ar_code'], ENT_QUOTES); ?>">
                <?php echo htmlspecialchars($ar['ar_name'], ENT_QUOTES); ?><?php echo !empty($ar['ar_required']) ? ' <span style="color:#e74c3c;">*</span>' : ''; ?>
            </th>
<?php if ($ar['ar_type'] === 'file' || $ar['ar_type'] === 'url') { ?>
            <td>
                <?php echo help($ar['ar_help']); ?>
<?php if ($ar['ar_type'] === 'url') { ?>
                <input type="text" name="av_value[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($val, ENT_QUOTES); ?>" <?php echo $style; ?><?php echo $ar_attrs; ?>>
<?php } else { ?>
                <input type="file" name="av_value_file[<?php echo $i; ?>]"<?php echo !empty($ar['ar_required']) && empty($val) ? ' required' : ''; ?>>
                <input type="hidden" name="av_value[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($val, ENT_QUOTES); ?>">
<?php } ?>
<?php if (!empty($val)) { ?>
                <a href="<?php echo htmlspecialchars($val, ENT_QUOTES); ?>" class="ui-btn" target="_blank">
                    <?php echo htmlspecialchars($ar['ar_name'], ENT_QUOTES); ?> 확인
                </a>
<?php } ?>
            </td>
<?php } else { ?>
            <td>
                <?php echo help($ar['ar_help']); ?>
<?php if ($ar['ar_type'] === 'text') { ?>
                <input type="text" name="av_value[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($val, ENT_QUOTES); ?>" <?php echo $style; ?><?php echo $ar_attrs; ?>>
                <?php echo htmlspecialchars($ar['ar_text'], ENT_QUOTES); ?>
                <?php if ($ar_len_hint !== '') echo '<div class="ar-len-hint">'.$ar_len_hint.'</div>'; ?>
<?php } elseif ($ar['ar_type'] === 'textarea') { ?>
                <textarea name="av_value[<?php echo $i; ?>]" <?php echo $style; ?><?php echo $ar_attrs; ?>><?php echo htmlspecialchars($val, ENT_QUOTES); ?></textarea>
                <?php if ($ar_len_hint !== '') echo '<div class="ar-len-hint">'.$ar_len_hint.'</div>'; ?>
<?php } elseif ($ar['ar_type'] === 'select') {
    $option = explode('||', (string)$ar['ar_text']);
?>
                <select name="av_value[<?php echo $i; ?>]" <?php echo $style; ?><?php echo !empty($ar['ar_required']) ? ' required' : ''; ?>>
<?php foreach ($option as $opt) {
    $opt = trim($opt);
    if ($opt === '') continue;
?>
                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES); ?>" <?php echo ($opt === $val) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($opt, ENT_QUOTES); ?>
                    </option>
<?php } ?>
                </select>
<?php } ?>
            </td>
<?php } ?>
        </tr>
<?php } ?>
        </tbody>
    </table>
<?php } ?>

    <?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('character_form_extra', '', array(
        'w'  => $w,
        'ch' => isset($ch) && is_array($ch) ? $ch : array(),
        'time' => isset($time) ? (int)$time : 0,
    )); ?>

    <hr class="padding">
    <div class="txt-center">
        <button type="submit" class="ui-btn point" id="btn_submit">
            캐릭터 정보 <?php echo ($w === 'u') ? '수정' : '등록'; ?>
        </button>
        <a href="./index.php" class="ui-btn">취소</a>
    </div>
</form>

<hr class="padding">

<script>
function fwrite_submit(f) {
    var btn = document.getElementById("btn_submit");
    if (btn) btn.disabled = true;
    return true;
}

$('.preview-image > a').on('click', function() {
    $(this).parent().children('div').stop().slideToggle();
    return false;
});

/* 실시간 글자수 카운터 — minlength/maxlength 가 있는 input/textarea 에 부착 */
(function(){
    function attach(el){
        var minL = parseInt(el.getAttribute('minlength') || '0', 10);
        var maxL = parseInt(el.getAttribute('maxlength') || '0', 10);
        if (!minL && !maxL) return;
        var counter = document.createElement('span');
        counter.className = 'ar-len-counter';
        counter.style.cssText = 'display:inline-block;margin-left:8px;font-size:12px;color:#666;';
        // 힌트 div 가 있으면 그 끝에, 없으면 input 다음에
        var hint = el.parentNode.querySelector('.ar-len-hint');
        if (hint) hint.appendChild(counter);
        else el.parentNode.insertBefore(counter, el.nextSibling);
        function update(){
            var len = (el.value || '').length;
            var label = '현재 ' + len + '자';
            counter.textContent = ' / ' + label;
            var bad = (maxL && len > maxL) || (minL && len > 0 && len < minL);
            counter.style.color = bad ? '#e74c3c' : '#666';
        }
        el.addEventListener('input', update);
        el.addEventListener('change', update);
        update();
    }
    var sel = '#ch_name, input[name^="av_value["], textarea[name^="av_value["]';
    document.querySelectorAll(sel).forEach(attach);
})();
</script>

<?php
include_once('./_tail.php');
