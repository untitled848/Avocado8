<?php
$sub_menu = "301200";
include_once('./_common.php');

// 관리자만 접근 가능
if (!$is_admin) {
    alert('관리자만 접근 가능합니다.');
}

include_once(__DIR__.'/timeline_require.inc.php');

$html_title = '타임라인';
$required = '';
$readonly = '';
$sound_only = '';
$required_valid = '';

// 기본값 설정
$timeline = array(
    'tl_table' => '',
    'gr_id' => '',
    'tl_subject' => '',
    'tl_skin' => 'basic',
    'tl_read_level' => 1,
    'tl_write_level' => 2,
    'tl_use_image' => 1,
    'tl_image_count' => 4,
    'tl_image_width' => 600,
    'tl_content_max' => 500,
    'tl_use_dm' => 1,
    'tl_ti_use' => 0,
    'tl_poll_interval' => 10000,
    'tl_dm_poll_interval' => 3000,
    'tl_write_point' => 0,
    'tl_count_write' => 0,
    'tl_count_comment' => 0,
    'tl_1_subj' => '',
    'tl_2_subj' => '',
    'tl_3_subj' => '',
    'tl_4_subj' => '',
    'tl_5_subj' => '',
    'tl_6_subj' => '',
    'tl_7_subj' => '',
    'tl_8_subj' => '',
    'tl_9_subj' => '',
    'tl_10_subj' => '',
    'tl_1' => '',
    'tl_2' => '',
    'tl_3' => '',
    'tl_4' => '',
    'tl_5' => '',
    'tl_6' => '',
    'tl_7' => '',
    'tl_8' => '',
    'tl_9' => '',
    'tl_10' => ''
);

$tl_table = ses($_REQUEST, 'tl_table', '', 'string');

if ($w === '') {
    $html_title .= ' 생성';
    $required = 'required';
    $required_valid = 'alnum_';
    $sound_only = '<strong class="sound_only">필수</strong>';
} elseif ($w === 'u') {
    $html_title .= ' 수정';
    
    if (!$tl_table) {
        alert('타임라인 테이블명이 없습니다.');
    }
    
    $row = sql_fetch(" SELECT * FROM {$g5['timeline_table']} WHERE tl_table = '{$tl_table}' ");
    if (!$row['tl_table']) {
        alert('존재하지 않는 타임라인입니다.');
    }
    
    $timeline = $row;
    $readonly = 'readonly';
}

$g5['title'] = $html_title;
include_once('./admin.head.php');

$pg_anchor = '<ul class="anchor">
    <li><a href="#anc_001">기본 설정</a></li>
    <li><a href="#anc_002">권한 설정</a></li>
    <li><a href="#anc_003">기능 설정</a></li>
    <li><a href="#anc_004">여분필드</a></li>
    <li><a href="#anc_005">모듈</a></li>
</ul>';

$frm_submit = '<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="확인" class="btn_submit" accesskey="s">
    <a href="./timeline_list.php?'.$qstr.'">목록</a>'.PHP_EOL;

if ($w === 'u') {
    $tl_table_safe = h($timeline['tl_table']);
    $frm_submit .= '<a href="./timeline_copy.php?tl_table='.$tl_table_safe.'" id="timeline_copy" target="win_timeline_copy">타임라인복사</a>
    <a href="'.G5_URL.'/timeline/?tl_table='.$tl_table_safe.'" class="btn_frmline" target="_blank">타임라인 바로가기</a>'.PHP_EOL;
}
$frm_submit .= '</div>';
?>

<form name="ftimelineform" id="ftimelineform" action="./timeline_form_update.php" onsubmit="return ftimelineform_submit(this)" method="post">
<input type="hidden" name="w" value="<?php echo h($w); ?>">
<input type="hidden" name="sfl" value="<?php echo h($sfl); ?>">
<input type="hidden" name="stx" value="<?php echo h($stx); ?>">
<input type="hidden" name="sst" value="<?php echo h($sst); ?>">
<input type="hidden" name="sod" value="<?php echo h($sod); ?>">
<input type="hidden" name="page" value="<?php echo (int)$page; ?>">
<input type="hidden" name="token" value="">

<!-- 섹션 1: 기본 설정 -->
<section id="anc_001">
    <h2 class="h2_frm">타임라인 기본 설정</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>타임라인 기본 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="tl_table">TABLE명<?php echo $sound_only; ?></label></th>
            <td>
                <input type="text" name="tl_table" value="<?php echo h($timeline['tl_table']); ?>" id="tl_table" <?php echo $required; ?> class="<?php echo $required_valid; ?> frm_input" maxlength="20" <?php echo $readonly; ?>>
                <?php if ($w === '') { ?>
                <span class="frm_info">영문자, 숫자, _ 만 가능 (공백없이 20자 이내)</span>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="tl_subject">타임라인 제목<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="tl_subject" value="<?php echo h($timeline['tl_subject']); ?>" id="tl_subject" required class="required frm_input" size="50">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="tl_skin">스킨</label></th>
            <td>
                <?php echo get_skin_select('timeline', 'tl_skin', 'tl_skin', $timeline['tl_skin']); ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="gr_id">그룹 (선택)</label></th>
            <td>
                <input type="text" name="gr_id" value="<?php echo h($timeline['gr_id']); ?>" id="gr_id" class="frm_input" size="20">
                <span class="frm_info">게시판 그룹과 연동시 사용 (선택사항)</span>
            </td>
        </tr>
        <?php if ($w === 'u') { ?>
        <tr>
            <th scope="row">글/댓글 수</th>
            <td>
                글 <strong><?php echo number_format($timeline['tl_count_write']); ?></strong>개 / 
                댓글 <strong><?php echo number_format($timeline['tl_count_comment']); ?></strong>개
            </td>
        </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<!-- 섹션 2: 권한 설정 -->
<section id="anc_002">
    <h2 class="h2_frm">타임라인 권한 설정</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>타임라인 권한 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="tl_read_level">열람 권한</label></th>
            <td>
                <select name="tl_read_level" id="tl_read_level" class="frm_input">
                    <option value="1"<?php echo ($timeline['tl_read_level'] == 1) ? ' selected' : ''; ?>>전체 (비회원 포함)</option>
                    <option value="2"<?php echo ($timeline['tl_read_level'] == 2) ? ' selected' : ''; ?>>회원만</option>
                    <option value="3"<?php echo ($timeline['tl_read_level'] == 3) ? ' selected' : ''; ?>>사용자만 (등록된 사용자)</option>
                    <option value="10"<?php echo ($timeline['tl_read_level'] == 10) ? ' selected' : ''; ?>>운영자만</option>
                </select>
                <span class="frm_info">타임라인 열람 권한 설정</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="tl_write_level">쓰기 권한</label></th>
            <td>
                <select name="tl_write_level" id="tl_write_level" class="frm_input">
                    <option value="1"<?php echo ($timeline['tl_write_level'] == 1) ? ' selected' : ''; ?>>모든 사용자</option>
                    <option value="2"<?php echo ($timeline['tl_write_level'] == 2) ? ' selected' : ''; ?>>운영자만</option>
                </select>
                <span class="frm_info">등록된 사용자 중 글을 작성할 수 있는 권한 (사용자가 없으면 모든 회원이 쓰기 가능)</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="tl_write_point">글쓰기 포인트</label></th>
            <td>
                <input type="text" name="tl_write_point" value="<?php echo $timeline['tl_write_point']; ?>" id="tl_write_point" class="frm_input" size="5">
                <span class="frm_info">글쓰기 시 지급/차감 포인트</span>
            </td>
        </tr>
        <?php if ($w === 'u') { 
            $user_count = count_timeline_users($timeline['tl_table']);
        ?>
        <tr>
            <th scope="row">사용자 관리</th>
            <td>
                등록된 사용자: <strong><?php echo number_format($user_count); ?></strong>명
                <?php if ($user_count == 0) { ?>
                <span style="color:#888;">(제한 없음 - 모든 회원 쓰기 가능)</span>
                <?php } ?>
                <a href="./timeline_user.php?tl_table=<?php echo h($timeline['tl_table']); ?>" class="btn_frmline">사용자 관리</a>
                <span class="frm_info">사용자를 등록하면 등록된 사용자만 글을 쓸 수 있습니다.</span>
            </td>
        </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<!-- 섹션 3: 기능 설정 -->
<section id="anc_003">
    <h2 class="h2_frm">타임라인 기능 설정</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>타임라인 기능 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row">이미지 첨부</th>
            <td>
                <input type="radio" name="tl_use_image" value="1" id="tl_use_image_1" <?php echo ($timeline['tl_use_image'] == 1) ? 'checked' : ''; ?>>
                <label for="tl_use_image_1">사용</label>
                <input type="radio" name="tl_use_image" value="0" id="tl_use_image_0" <?php echo ($timeline['tl_use_image'] == 0) ? 'checked' : ''; ?>>
                <label for="tl_use_image_0">미사용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="tl_image_count">최대 이미지 수</label></th>
            <td>
                <input type="text" name="tl_image_count" value="<?php echo $timeline['tl_image_count']; ?>" id="tl_image_count" class="frm_input" size="3">
                <span class="frm_info">한 글에 첨부 가능한 최대 이미지 수 (최대 4개)</span>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="tl_image_width">이미지 최대 너비</label></th>
            <td>
                <input type="text" name="tl_image_width" value="<?php echo $timeline['tl_image_width']; ?>" id="tl_image_width" class="frm_input" size="5"> px
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="tl_content_max">최대 글자수</label></th>
            <td>
                <input type="text" name="tl_content_max" value="<?php echo (int)ses($timeline, 'tl_content_max', 500, 'int'); ?>" id="tl_content_max" class="frm_input" size="5">
                <span class="frm_info">0이면 무제한</span>
            </td>
        </tr>
        <tr>
            <th scope="row">DM 사용</th>
            <td>
                <input type="radio" name="tl_use_dm" value="1" id="tl_use_dm_1" <?php echo (ses($timeline, 'tl_use_dm', 1, 'int') == 1) ? 'checked' : ''; ?>>
                <label for="tl_use_dm_1">사용</label>
                <input type="radio" name="tl_use_dm" value="0" id="tl_use_dm_0" <?php echo (ses($timeline, 'tl_use_dm', 1, 'int') == 0) ? 'checked' : ''; ?>>
                <label for="tl_use_dm_0">미사용</label>
                <span class="frm_info">타임라인 탭과 DM 페이지 노출</span>
            </td>
        </tr>
        <tr>
            <th scope="row">타이틀 표시</th>
            <td>
                <input type="radio" name="tl_ti_use" value="1" id="tl_ti_use_1" <?php echo (ses($timeline, 'tl_ti_use', 0, 'int') == 1) ? 'checked' : ''; ?>>
                <label for="tl_ti_use_1">사용</label>
                <input type="radio" name="tl_ti_use" value="0" id="tl_ti_use_0" <?php echo (ses($timeline, 'tl_ti_use', 0, 'int') == 0) ? 'checked' : ''; ?>>
                <label for="tl_ti_use_0">미사용</label>
                <span class="frm_info">글/댓글 작성자명 앞에 착용 타이틀(ti_id) 표시. 작성 시 캐릭터 타이틀을 스냅샷 저장</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="tl_poll_interval">타임라인 폴링 간격</label></th>
            <td>
                <input type="text" name="tl_poll_interval" value="<?php echo (int)ses($timeline, 'tl_poll_interval', 10000, 'int'); ?>" id="tl_poll_interval" class="frm_input" size="8"> ms
                <span class="frm_info">새 글/멘션 확인 주기. 최소 1000, 최대 300000. 기본 10000</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="tl_dm_poll_interval">DM 폴링 간격</label></th>
            <td>
                <input type="text" name="tl_dm_poll_interval" value="<?php echo (int)ses($timeline, 'tl_dm_poll_interval', 3000, 'int'); ?>" id="tl_dm_poll_interval" class="frm_input" size="8"> ms
                <span class="frm_info">채팅 새 메시지 확인 주기. 최소 1000, 최대 300000. 기본 3000</span>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<!-- 섹션 4: 여분필드 설정 -->
<section id="anc_004">
    <h2 class="h2_frm">타임라인 여분필드 설정</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>타임라인 여분필드 설정</caption>
        <colgroup>
            <col style="width:150px">
            <col>
        </colgroup>
        <tbody>
        <?php for ($i = 1; $i <= 10; $i++) { ?>
        <tr>
            <th scope="row">여분필드<?php echo $i; ?></th>
            <td class="td_extra">
                <label for="tl_<?php echo $i; ?>_subj">여분필드 <?php echo $i; ?> 제목</label>
                <input type="text" name="tl_<?php echo $i; ?>_subj" id="tl_<?php echo $i; ?>_subj" value="<?php echo h($timeline['tl_'.$i.'_subj']); ?>" class="frm_input">
                <label for="tl_<?php echo $i; ?>">여분필드 <?php echo $i; ?> 값</label>
                <input type="text" name="tl_<?php echo $i; ?>" id="tl_<?php echo $i; ?>" value="<?php echo h($timeline['tl_'.$i]); ?>" class="frm_input">
            </td>
        </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>
</section>

<?php
// ─── 스킨 설정 훅 (모듈 설정 섹션 위) ───────────────────────────────────────
$_h_tl_skin   = (isset($timeline['tl_skin']) && $timeline['tl_skin'] !== '') ? $timeline['tl_skin'] : 'basic';
$_h_skin_path = function_exists('get_skin_path') ? get_skin_path('timeline', $_h_tl_skin) : G5_SKIN_PATH.'/timeline/'.$_h_tl_skin;
$_h_hook_file = $_h_skin_path . '/setting.hook.php';
if (file_exists($_h_hook_file)) {
?>
<section id="anc_skinhook">
    <h2 class="h2_frm">스킨 설정 &mdash; <?php echo htmlspecialchars($_h_tl_skin, ENT_QUOTES); ?></h2>
    <?php include $_h_hook_file; // $timeline, $w 변수 사용 가능 ?>
</section>
<?php
}
unset($_h_tl_skin, $_h_skin_path, $_h_hook_file);
?>

<?php
/* 섹션 5: 타임라인별 모듈 on/off (board_scope=true 모듈이 있을 때만 노출)
 * 타임라인은 tl_table 을 bo_table 호환 키로 사용 → mdl_board_enabled / board_state.json 공유
 */
$mdl_scope = (function_exists('mdl_board_scope_modules') ? mdl_board_scope_modules() : array());
if (!empty($mdl_scope)) {
    $cur_tl_table = isset($timeline['tl_table']) ? $timeline['tl_table'] : '';
?>
<section id="anc_005">
    <h2 class="h2_frm">타임라인별 모듈 on/off</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>타임라인별 모듈 on/off</caption>
        <colgroup>
            <col style="width:200px">
            <col>
        </colgroup>
        <tbody>
        <?php foreach ($mdl_scope as $_mname => $_mmeta) {
            $_scopes = (isset($_mmeta['scopes']) && is_array($_mmeta['scopes'])) ? $_mmeta['scopes'] : array();
            $_default_on = (!isset($_mmeta['board_default']) || $_mmeta['board_default'] !== 'off');

            // 타임라인 스킨 훅 지원 검사 (스킨 + timeline/ 코어)
            $_compat = array('supported' => true, 'missing' => array());
            $_skin_overridden = false;
            $_skin_path_show  = '';
            if (function_exists('mdl_skin_supports_hooks') && !empty($_mmeta['requires_hooks'])) {
                $_tl_skin = (isset($timeline['tl_skin']) && $timeline['tl_skin'] !== '') ? $timeline['tl_skin'] : 'basic';
                $_skin_default = G5_PATH.'/skin/timeline/'.$_tl_skin;
                $_skin_path = function_exists('get_skin_path') ? get_skin_path('timeline', $_tl_skin) : $_skin_default;
                $_paths = array($_skin_path);
                if (is_dir(G5_PATH.'/timeline')) {
                    $_paths[] = G5_PATH.'/timeline';
                }
                $_sup = mdl_skin_supports_hooks($_paths, $_mmeta['requires_hooks']);
                $_missing = array();
                foreach ($_mmeta['requires_hooks'] as $_h) {
                    if (empty($_sup[$_h])) $_missing[] = $_h;
                }
                $_compat = array('supported' => empty($_missing), 'missing' => $_missing);
                if ($_skin_path && @realpath($_skin_path) !== @realpath($_skin_default)) {
                    $_skin_overridden = true;
                    $_skin_path_show  = ltrim(str_replace(array('\\', G5_PATH), array('/', ''), $_skin_path), '/');
                }
            }
            $_disabled = !$_compat['supported'];
            $_id_base = 'mdl_'.preg_replace('/[^a-zA-Z0-9_]/', '_', $_mname);
        ?>
        <tr class="mdl-row"
            data-mod="<?php echo htmlspecialchars($_mname, ENT_QUOTES); ?>"
            data-requires="<?php echo htmlspecialchars(implode(',', isset($_mmeta['requires_hooks']) ? $_mmeta['requires_hooks'] : array()), ENT_QUOTES); ?>"
            <?php echo $_disabled ? 'style="opacity:0.55;"' : ''; ?>>
            <th scope="row"><?php echo htmlspecialchars($_mmeta['title'], ENT_QUOTES); ?></th>
            <td>
            <?php if (empty($_scopes)) {
                // 모듈 단위 단일 토글
                if ($w === '') {
                    $_enabled = $_default_on;
                } else {
                    $_enabled = ($cur_tl_table !== '' && function_exists('mdl_board_enabled'))
                              ? mdl_board_enabled($cur_tl_table, $_mname)
                              : $_default_on;
                }
                if ($_disabled) $_enabled = false;
                $_id = $_id_base;
            ?>
                <input type="hidden" name="mdl_modules_present[]" value="<?php echo htmlspecialchars($_mname, ENT_QUOTES); ?>">
                <input type="checkbox" name="mdl_modules[<?php echo htmlspecialchars($_mname, ENT_QUOTES); ?>]" id="<?php echo $_id; ?>" value="1" <?php echo $_enabled ? 'checked' : ''; ?> <?php echo $_disabled ? 'disabled' : ''; ?>>
                <label for="<?php echo $_id; ?>">사용</label>
            <?php } else {
                // scope 분리 토글 (각 scope 별 체크박스)
                foreach ($_scopes as $_sc) {
                    if ($w === '') {
                        $_en = $_default_on;
                    } else {
                        $_en = ($cur_tl_table !== '' && function_exists('mdl_board_enabled'))
                             ? mdl_board_enabled($cur_tl_table, $_mname, $_sc)
                             : $_default_on;
                    }
                    if ($_disabled) $_en = false;
                    $_key = $_mname.'@'.$_sc;
                    $_id  = $_id_base.'_'.preg_replace('/[^a-zA-Z0-9_]/', '_', $_sc);
                    $_sc_label = function_exists('mdl_scope_label') ? mdl_scope_label($_sc) : $_sc;
            ?>
                <input type="hidden" name="mdl_modules_present[]" value="<?php echo htmlspecialchars($_key, ENT_QUOTES); ?>">
                <input type="checkbox" name="mdl_modules[<?php echo htmlspecialchars($_key, ENT_QUOTES); ?>]" id="<?php echo $_id; ?>" value="1" <?php echo $_en ? 'checked' : ''; ?> <?php echo $_disabled ? 'disabled' : ''; ?>>
                <label for="<?php echo $_id; ?>"><?php echo htmlspecialchars($_sc_label, ENT_QUOTES); ?></label>
                &nbsp;&nbsp;
            <?php } } ?>
                <span style="color:#888;font-size:12px;margin-left:10px;">
                    <?php echo htmlspecialchars($_mname, ENT_QUOTES); ?>
                    <?php if (!empty($_mmeta['description'])) { ?> &mdash; <?php echo htmlspecialchars($_mmeta['description'], ENT_QUOTES); ?><?php } ?>
                </span>
                <div class="mdl-status">
                <?php if ($_disabled) { ?>
                <div style="color:#c00;font-size:12px;margin-top:4px;">
                    ⚠ 현재 스킨(<strong class="cur-skin"><?php echo htmlspecialchars(isset($timeline['tl_skin']) ? $timeline['tl_skin'] : '', ENT_QUOTES); ?></strong>)이 필요한 훅을 제공하지 않아 사용할 수 없습니다.
                    누락 훅: <span class="missing-hooks"><?php echo htmlspecialchars(implode(', ', $_compat['missing']), ENT_QUOTES); ?></span>
                </div>
                <?php } else if (!empty($_mmeta['requires_hooks'])) { ?>
                <div style="color:#888;font-size:11px;margin-top:4px;">
                    필요 훅: <?php echo htmlspecialchars(implode(', ', $_mmeta['requires_hooks']), ENT_QUOTES); ?>
                    <?php if ($_skin_overridden) { ?>
                    <br><span style="color:#06a;">▶ 테마 오버라이드 적용된 스킨 경로 검사됨: <code class="skin-path-show"><?php echo htmlspecialchars($_skin_path_show, ENT_QUOTES); ?></code></span>
                    <?php } ?>
                </div>
                <?php } ?>
                </div>
            </td>
        </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>
<?php } /* end mdl_scope */ ?>

<?php if (empty($mdl_scope)) { /* 모듈 섹션이 없을 때도 제출 버튼은 1회 출력 */ ?>
<?php echo $frm_submit; ?>
<?php } ?>

</form>

<script>
// 타임라인 스킨 변경 시 모듈 호환성 라이브 재검증
(function(){
    var $skin = $('select[name="tl_skin"]');
    var $rows = $('tr.mdl-row');
    if (!$skin.length || !$rows.length) return;

    function esc(s){ return $('<i>').text(s == null ? '' : String(s)).html(); }

    function recheck() {
        var skin = $skin.val() || 'basic';
        $.getJSON('./timeline_form_module_check.ajax.php', { tl_skin: skin })
            .done(function(res){
                if (!res || !res.modules) return;
                $rows.each(function(){
                    var $tr = $(this);
                    var name = $tr.data('mod');
                    var info = res.modules[name];
                    var $status = $tr.find('.mdl-status');
                    var $cbs = $tr.find('input[type="checkbox"][name^="mdl_modules["]');
                    var requires = ($tr.attr('data-requires') || '').split(',').filter(Boolean);
                    var html = '';
                    if (!info) {
                        $tr.css('opacity', 1);
                        $cbs.prop('disabled', false);
                    } else if (!info.supported) {
                        $tr.css('opacity', 0.55);
                        $cbs.prop('disabled', true).prop('checked', false);
                        html = '<div style="color:#c00;font-size:12px;margin-top:4px;">'
                             + '⚠ 스킨(<strong>'+ esc(skin) +'</strong>)이 필요한 훅을 제공하지 않아 사용할 수 없습니다.<br>'
                             + '누락 훅: '+ esc((info.missing||[]).join(', '))
                             + '</div>';
                    } else {
                        $tr.css('opacity', 1);
                        $cbs.prop('disabled', false);
                        if (requires.length) {
                            html = '<div style="color:#888;font-size:11px;margin-top:4px;">'
                                 + '필요 훅: '+ esc(requires.join(', '));
                            if (res.overridden) {
                                html += '<br><span style="color:#06a;">▶ 테마 오버라이드 적용된 스킨 경로 검사됨: <code>'
                                      + esc(res.skin_path) +'</code></span>';
                            }
                            html += '</div>';
                        }
                    }
                    $status.html(html);
                });
            })
            .fail(function(){ /* 무시 */ });
    }

    var lastSkin = $skin.val();
    $skin.on('change', function(){
        if ($(this).val() === lastSkin) return;
        lastSkin = $(this).val();
        recheck();
    });
})();
</script>

<script>
$(function(){
    $("#timeline_copy").click(function(){
        window.open(this.href, "win_timeline_copy", "left=100,top=100,width=550,height=450");
        return false;
    });
});

function ftimelineform_submit(f)
{
    var tl_table = f.tl_table.value;
    
    // 테이블명 검증
    if (!/^[A-Za-z0-9_]{1,20}$/.test(tl_table)) {
        alert("타임라인 TABLE명은 공백없이 영문자, 숫자, _ 만 사용 가능합니다. (20자 이내)");
        f.tl_table.focus();
        return false;
    }
    
    // 이미지 수 검증
    var img_count = parseInt(f.tl_image_count.value, 10);
    if (img_count < 0 || img_count > 4) {
        alert("최대 이미지 수는 0~4 사이여야 합니다.");
        f.tl_image_count.focus();
        return false;
    }
    
    return true;
}
</script>

<?php
include_once('./admin.tail.php');
?>
