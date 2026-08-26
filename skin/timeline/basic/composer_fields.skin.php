<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 타임라인 글쓰기/답글 공통 폼 필드
 * write.skin.php 기준 — 인라인 답글에서도 동일 구조 사용
 *
 * 필수: $tl_cf (array)
 * - prefix            : ''(원글) | 'ir_{id}'(답글) — 요소 id 접두사
 * - content_max       : int
 * - image_max         : int
 * - use_image         : bool/int
 * - is_approved       : bool
 * - placeholder       : textarea placeholder
 * - textarea_class    : 추가 class (답글: tl-comment-textarea)
 * - textarea_attrs    : 추가 속성 문자열 (oninput 등)
 * - form_options      : 모듈 폼 옵션 HTML (주사위 등)
 * - action_options    : 액션 select 옵션 HTML (원글) / 라디오 HTML (답글 comment 훅)
 * - action_panels     : 액션 패널 HTML (write: action_panels / comment: form_extra)
 * - after_action      : 아이템 영역 HTML
 * - composer_icons    : 행동/아이템 토글 옆 추가 아이콘 HTML
 * - composer_panels   : 추가 아이콘이 여는 토글 패널 HTML
 * - action_as_radio   : true면 mmb 댓글처럼 라디오 액션 UI
 * - submit_label      : 버튼 문구
 * - submit_disabled   : bool
 * - submit_class      : 버튼 class
 * - image_onchange    : file input onchange 핸들러
 * - toggle_action_js  : 행동 토글 onclick
 * - toggle_item_js    : 아이템 토글 onclick
 * - mention_placeholder
 */

if (!isset($tl_cf) || !is_array($tl_cf)) {
    return;
}

$pfx = isset($tl_cf['prefix']) ? (string)$tl_cf['prefix'] : '';
$is_reply = ($pfx !== '');

$content_max = (int)ses($tl_cf, 'content_max', 500, 'int');
if ($content_max < 1) $content_max = 500;
$image_max = (int)ses($tl_cf, 'image_max', 4, 'int');
if ($image_max < 1) $image_max = 4;
if ($image_max > 4) $image_max = 4;
$use_image = !empty($tl_cf['use_image']);
$is_approved = !empty($tl_cf['is_approved']);

$form_options   = isset($tl_cf['form_options']) ? $tl_cf['form_options'] : '';
$action_options = isset($tl_cf['action_options']) ? $tl_cf['action_options'] : '';
$action_panels  = isset($tl_cf['action_panels']) ? $tl_cf['action_panels'] : '';
$after_action   = isset($tl_cf['after_action']) ? $tl_cf['after_action'] : '';
$composer_icons = isset($tl_cf['composer_icons']) ? $tl_cf['composer_icons'] : '';
$composer_panels = isset($tl_cf['composer_panels']) ? $tl_cf['composer_panels'] : '';
$action_as_radio = !empty($tl_cf['action_as_radio']);
$has_action_ui = ($action_options !== '' || ($action_as_radio && $action_panels !== ''));

$placeholder = isset($tl_cf['placeholder']) ? $tl_cf['placeholder'] : '*굵게*|**기울기**|***굵고 기울기***|[선택1/선택2/선택3]|[NdM]';
$textarea_class = isset($tl_cf['textarea_class']) ? trim($tl_cf['textarea_class']) : '';
$textarea_attrs = isset($tl_cf['textarea_attrs']) ? $tl_cf['textarea_attrs'] : '';
$mention_ph = isset($tl_cf['mention_placeholder']) ? $tl_cf['mention_placeholder'] : '캐릭터 이름 검색 (최대 6자)...';

$submit_label = isset($tl_cf['submit_label']) ? $tl_cf['submit_label'] : '게시';
$submit_disabled = !empty($tl_cf['submit_disabled']);
$submit_class = isset($tl_cf['submit_class']) ? $tl_cf['submit_class'] : 'tl-submit-btn';

$image_onchange = isset($tl_cf['image_onchange']) ? $tl_cf['image_onchange'] : ('previewImages(this, '.$image_max.')');
$toggle_action_js = isset($tl_cf['toggle_action_js']) ? $tl_cf['toggle_action_js'] : 'toggleActionArea()';
$toggle_item_js = isset($tl_cf['toggle_item_js']) ? $tl_cf['toggle_item_js'] : 'toggleItemArea()';

if ($is_reply) {
    $id_mention_box     = $pfx.'_mention_search';
    $id_mention_input   = $pfx.'_mention_input';
    $id_mention_results = $pfx.'_mention_results';
    $id_content         = $pfx.'_content';
    $id_preview         = $pfx.'_preview';
    $id_preview_box     = $pfx.'_preview_container';
    $id_img_hidden      = $pfx.'_wr_img_hidden';
    $id_text_hidden     = $pfx.'_wr_text_hidden';
    $id_warning_wrap    = $pfx.'_warning_text_wrap';
    $id_warning_text    = $pfx.'_wr_waring_text';
    $id_action_area     = $pfx.'_action_area';
    $id_action          = $pfx.'_action';
    $id_item_area       = $pfx.'_item_area';
    $id_extra_icons     = $pfx.'_composer_extra_icons';
    $id_extra_panels    = $pfx.'_composer_extra_panels';
    $id_images          = $pfx.'_images';
    $id_image_count     = $pfx.'_image_count';
    $id_char_count      = $pfx.'_char_count';
    $id_submit          = $pfx.'_submit';
    $images_name        = ''; // JS(irSelectedFiles)로 append
} else {
    $id_mention_box     = 'tl-mention-search-box';
    $id_mention_input   = 'tl-mention-search';
    $id_mention_results = 'tl-mention-results';
    $id_content         = 'tl-content';
    $id_preview         = 'tl-image-preview';
    $id_preview_box     = 'tl-preview-container';
    $id_img_hidden      = 'wr_img_hidden';
    $id_text_hidden     = 'wr_text_hidden';
    $id_warning_wrap    = 'tl-warning-text-wrap';
    $id_warning_text    = 'wr_waring_text';
    $id_action_area     = 'tl-action-area';
    $id_action          = 'action';
    $id_item_area       = 'tl-item-area';
    $id_extra_icons     = 'tl-composer-extra-icons';
    $id_extra_panels    = 'tl-composer-extra-panels';
    $id_images          = 'tl-images';
    $id_image_count     = 'tl-image-count';
    $id_char_count      = 'tl-char-count';
    $id_submit          = 'tl-submit';
    $images_name        = 'images[]';
}

$ta_class = 'tl-composer-textarea'.($textarea_class !== '' ? ' '.$textarea_class : '');
?>
        <!-- 멘션 검색 필드 -->
        <div class="tl-mention-search-box" id="<?php echo h($id_mention_box); ?>">
            <input type="text"
                   id="<?php echo h($id_mention_input); ?>"
                   class="tl-mention-search-input"
                   placeholder="<?php echo h($mention_ph); ?>"
                   maxlength="6"
                   autocomplete="off">
            <div class="tl-mention-results" id="<?php echo h($id_mention_results); ?>"></div>
        </div>

        <textarea id="<?php echo h($id_content); ?>"
                  name="content"
                  class="<?php echo h($ta_class); ?>"
                  placeholder="<?php echo h($placeholder); ?>"
                  maxlength="<?php echo $content_max; ?>"
                  <?php echo $textarea_attrs; ?>></textarea>

        <?php if ($use_image) { ?>
        <div id="<?php echo h($id_preview); ?>" class="tl-image-preview">
            <div id="<?php echo h($id_preview_box); ?>" class="tl-preview-container"></div>
        </div>
        <?php } ?>

        <!-- 추가 폼 옵션 (이미지 접기/글 가리기 + 주사위 등 모듈) -->
        <div class="tl-form-options">
            <?php echo $form_options; ?>
            <fieldset>
                <input type="checkbox" id="<?php echo h($id_img_hidden); ?>" name="wr_img_hidden" value="1">
                <label for="<?php echo h($id_img_hidden); ?>">이미지 접기</label>
            </fieldset>
            <fieldset>
                <input type="checkbox" id="<?php echo h($id_text_hidden); ?>" name="wr_text_hidden" value="1" onchange="toggleWarningTextInput(this)">
                <label for="<?php echo h($id_text_hidden); ?>">글 가리기</label>
            </fieldset>
            <div id="<?php echo h($id_warning_wrap); ?>" class="tl-warning-text-wrap" style="display:none;">
                <input type="text" id="<?php echo h($id_warning_text); ?>" name="wr_waring_text" maxlength="255" placeholder="경고 문구 (선택)">
            </div>
        </div>

        <?php if ($is_approved && $has_action_ui) { ?>
        <!-- 액션 영역 (모듈 주입) -->
        <div class="tl-action-area<?php echo $is_reply ? ' tl-inline-action-area' : ''; ?>" id="<?php echo h($id_action_area); ?>">
            <?php if ($action_as_radio) { ?>
            <div class="inner tl-comment-actions-check">
                <input type="radio" name="action" id="<?php echo h($id_action); ?>_" value="" checked>
                <label for="<?php echo h($id_action); ?>_">일반&nbsp;&nbsp;&nbsp;&nbsp;</label>
                <?php echo $action_options; ?>
            </div>
            <?php } else { ?>
            <div class="inner">
                <select name="action" id="<?php echo h($id_action); ?>">
                    <option value="">일반행동</option>
                    <?php echo $action_options; ?>
                </select>
            </div>
            <?php } ?>
            <?php echo $action_panels; ?>
        </div>
        <?php } ?>

        <?php if ($after_action !== '') { ?>
        <!-- 아이템 사용 영역 (모듈 주입, 토글) -->
        <div class="tl-item-area<?php echo $is_reply ? ' tl-inline-item-area' : ''; ?>" id="<?php echo h($id_item_area); ?>">
            <?php echo $after_action; ?>
        </div>
        <?php } ?>

        <div class="tl-composer-extra-panels<?php echo $is_reply ? ' tl-inline-extra-panels' : ''; ?>" id="<?php echo h($id_extra_panels); ?>">
            <?php echo $composer_panels; ?>
        </div>

        <div class="tl-composer-actions">
            <div class="tl-composer-left"<?php echo $is_reply ? ' id="'.h($pfx).'_composer_left"' : ''; ?>>
                <?php if ($use_image) { ?>
                <label class="tl-image-btn tl-btns" title="이미지 첨부 (최대 <?php echo $image_max; ?>장)">
                    <input type="file"
                           id="<?php echo h($id_images); ?>"
                           <?php if ($images_name !== '') { ?>name="<?php echo h($images_name); ?>"<?php } ?>
                           accept="image/*"
                           multiple
                           onchange="<?php echo $image_onchange; ?>">
                    <i class="material-icons" aria-hidden="true">image</i>
                    <span id="<?php echo h($id_image_count); ?>"></span>
                </label>
                <?php } ?>
                <?php if ($is_approved && $has_action_ui) { ?>
                <button type="button" class="tl-action-toggle tl-btns" title="행동" onclick="<?php echo $toggle_action_js; ?>">
                    <i class="material-icons" aria-hidden="true">sports_esports</i>
                </button>
                <?php } ?>
                <?php if ($after_action !== '') { ?>
                <button type="button" class="tl-item-toggle tl-btns" title="아이템" onclick="<?php echo $toggle_item_js; ?>">
                    <i class="material-icons" aria-hidden="true">backpack</i>
                </button>
                <?php } ?>
                <span class="tl-composer-extra-icons" id="<?php echo h($id_extra_icons); ?>">
                    <?php echo $composer_icons; ?>
                </span>
            </div>
            <div class="tl-composer-right">
                <span id="<?php echo h($id_char_count); ?>">0/<?php echo $content_max; ?></span>
                <?php if ($is_reply) { ?>
                <button type="button"
                        id="<?php echo h($pfx); ?>_temp_btn"
                        class="tl-btn tl-btn-secondary tl-temp-btn"
                        data-mode="save"
                        onclick="toggleTimelineTemp(this)">임시저장</button>
                <?php } ?>
                <button type="submit"
                        id="<?php echo h($id_submit); ?>"
                        class="<?php echo h($submit_class); ?>"
                        <?php echo $submit_disabled ? 'disabled' : ''; ?>><?php echo h($submit_label); ?></button>
            </div>
        </div>
