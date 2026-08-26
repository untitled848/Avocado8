<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 타임라인 스킨 글쓰기 폼
 * 게시판 호환 구조 (wr_ 컬럼명 사용)
 * MMB 게시판과 호환되는 action/game 기능 포함
 * 
 * 사용 변수:
 * - $character : 현재 캐릭터 정보
 * - $my_profile_img : 프로필 이미지 URL (index.php에서 설정)
 * - $is_admin : 관리자 여부
 * - $timeline : 타임라인 설정
 * - $tl_table : 타임라인 테이블명
 * 
 * _common.php에서 로드된 변수:
 * - $is_approved, $search_remain, $tl_make_items, $tl_items
 */

global $is_approved, $my_ch_id, $character, $timeline, $bo_table, $my_timeline_users;

$ch_name = ses($character, 'ch_name', '', 'raw');
if (!isset($my_timeline_users) || !is_array($my_timeline_users)) {
    $my_timeline_users = array();
}
foreach ($my_timeline_users as $_tu) {
    if ((int)ses($_tu, 'ch_id', 0, 'int') === (int)$my_ch_id) {
        $_tu_name = ses($_tu, 'tu_name', '', 'raw');
        if ($_tu_name !== '') {
            $ch_name = $_tu_name;
        }
        break;
    }
}
$can_switch_account = count($my_timeline_users) > 1;
$my_profile_url = G5_URL.'/timeline/profile.php?tl_table='.$tl_table.'&ch_id='.(int)$my_ch_id;
if (empty($my_profile_img)) {
    $my_profile_img = !empty($my_profile) ? $my_profile : G5_URL.'/img/no_profile.png';
}

// 최대 글자수
$content_max = ses($timeline, 'tl_content_max', 500, 'int');
if (!$content_max) $content_max = 500;

// 최대 이미지 수 (기본 4)
$image_max = ses($timeline, 'tl_image_count', 4, 'int');
if ($image_max < 1) $image_max = 4;
if ($image_max > 4) $image_max = 4;

$tl_use_image = ses($timeline, 'tl_use_image', 0, 'int');

// 모듈 훅 컨텍스트 (mmb write.controller.php의 $mmb_form_ctx와 동일 구조)
$tl_form_ctx = array(
    'bo_table'  => $bo_table,
    'w'         => '',
    'wr_id'     => 0,
    'write'     => array(),
    'character' => $character,
    'id_prefix' => '',
    'ui'        => 'composer',
);

// 모듈이 주입할 액션 옵션/패널/아이템 폼 미리 수집
$_mdl_action_options = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_write_form_action_options', '', $tl_form_ctx) : '';
$_mdl_action_panels  = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_write_form_action_panels', '', $tl_form_ctx) : '';
$_mdl_after_action   = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_write_form_after_action', '', $tl_form_ctx) : '';
$_mdl_form_options   = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_write_form_options', '', $tl_form_ctx) : '';
$_mdl_composer_icons = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_write_form_composer_icons', '', $tl_form_ctx) : '';
$_mdl_composer_panels = function_exists('mdl_apply_filters') ? mdl_apply_filters('bbs_write_form_composer_panels', '', $tl_form_ctx) : '';

$tl_cf = array(
    'prefix'             => '',
    'content_max'        => $content_max,
    'image_max'          => $image_max,
    'use_image'          => $tl_use_image,
    'is_approved'        => $is_approved,
    'placeholder'         => '*굵게*|**기울기**|***굵고 기울기***|[선택1/선택2/선택3]|[NdM]',
    'mention_placeholder' => '캐릭터 이름 검색 (최대 6자)',
    'form_options'       => $_mdl_form_options,
    'action_options'     => $_mdl_action_options,
    'action_panels'      => $_mdl_action_panels,
    'after_action'       => $_mdl_after_action,
    'composer_icons'     => $_mdl_composer_icons,
    'composer_panels'    => $_mdl_composer_panels,
    'submit_label'       => '게시',
    'submit_disabled'    => true,
    'submit_class'       => 'tl-submit-btn',
    'image_onchange'     => 'previewImages(this, '.$image_max.')',
    'toggle_action_js'   => 'toggleActionArea()',
    'toggle_item_js'     => 'toggleItemArea()',
);
?>

<?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('list_top', '', $bo_table); ?>

<div class="tl-composer">
    <div class="tl-composer-avatar<?php echo $can_switch_account ? ' tl-composer-avatar-switch' : ''; ?>">
        <?php if ($can_switch_account) { ?>
        <button type="button" class="tl-account-switch-btn" onclick="toggleMenu(this)" title="계정 전환" aria-haspopup="true">
            <img src="<?php echo $my_profile_img; ?>" alt="<?php echo h($ch_name); ?>">
        </button>
        <div class="tl-dropdown tl-account-dropdown">
            <?php foreach ($my_timeline_users as $acc) {
                $acc_ch_id = (int)ses($acc, 'ch_id', 0, 'int');
                if (!$acc_ch_id) continue;
                $acc_name = ses($acc, 'tu_name', '', 'raw');
                if ($acc_name === '') {
                    $acc_name = ses($acc, 'ch_name', '', 'raw');
                }
                $acc_img = get_timeline_thumb($acc_ch_id, 1);
                $acc_current = ($acc_ch_id === (int)$my_ch_id);
            ?>
            <button type="button" class="tl-account-item<?php echo $acc_current ? ' is-current' : ''; ?>"<?php echo $acc_current ? '' : ' onclick="switchTimelineAccount('.$acc_ch_id.')"'; ?>>
                <img src="<?php echo $acc_img; ?>" alt="">
                <span class="tl-account-name"><?php echo h($acc_name); ?></span>
                <?php if ($acc_current) { ?><span class="tl-account-current">사용 중</span><?php } ?>
            </button>
            <?php } ?>
            <a href="<?php echo $my_profile_url; ?>" class="tl-account-profile">프로필</a>
        </div>
        <?php } else { ?>
        <a href="<?php echo $my_profile_url; ?>">
            <img src="<?php echo $my_profile_img; ?>" alt="<?php echo h($ch_name); ?>">
        </a>
        <?php } ?>
    </div>
    <form id="tl-form" class="tl-composer-form" enctype="multipart/form-data">
        <?php include __DIR__.'/composer_fields.skin.php'; ?>
    </form>
</div>

<script>
var contentMaxLength = <?php echo $content_max; ?>;
var imageMaxCount = <?php echo $image_max; ?>;

function toggleActionArea() {
    var area = document.getElementById('tl-action-area');
    if (area) {
        area.style.display = (window.getComputedStyle(area).display === 'none') ? 'block' : 'none';
    }
}

function toggleItemArea() {
    var area = document.getElementById('tl-item-area');
    if (area) {
        area.style.display = (window.getComputedStyle(area).display === 'none') ? 'block' : 'none';
    }
}

function toggleWarningTextInput(cb) {
    if (!cb) return;
    var root = (cb.closest && cb.closest('form')) || document;
    var wrap = root.querySelector('.tl-warning-text-wrap');
    var input = wrap ? wrap.querySelector('input[name="wr_waring_text"]') : null;
    if (!wrap) return;
    wrap.style.display = cb.checked ? 'block' : 'none';
    if (!cb.checked && input) input.value = '';
}

/* action 변경 → .comment-data 패널 토글은 코어(js/action_panel.js)가 전역 처리.
   (글쓰기 select / 댓글 라디오 / 인라인 답글 모두 위임 방식으로 커버) */
</script>
