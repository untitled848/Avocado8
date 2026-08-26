<?php
/**
 * 글로벌 디자인 핫링크 박스 (재사용 include)
 *
 * 사용법:
 *   $design_type = 'world';   // 'main' | 'world' | 'content'
 *   include G5_ADMIN_PATH.'/_design_global_box.inc.php';
 *
 * 기능:
 *   - "현재 default 테마 프리셋의 ○○ 디자인" 표시
 *   - 통합 드롭다운(프리셋+스킨)으로 즉시 변경
 *   - 저장은 ./design_global_update.php 로
 *
 * 의미:
 *   "글로벌 디자인" = 사이트 전체에 적용 중인 default 테마 프리셋(pr_id=1)의
 *   pr_*_design 값. 시기 테마가 활성일 때는 그쪽이 우선이지만,
 *   여기서는 어디에 진입해서든 "기본값"을 빠르게 바꿀 수 있도록 핫링크 제공.
 */
if (!defined('_GNUBOARD_')) exit;
if (empty($design_type)) return;

$_reg = isset($GLOBALS['DESIGN_PRESET_TYPES']) ? $GLOBALS['DESIGN_PRESET_TYPES'] : array();
if (!isset($_reg[$design_type])) return;
$_def = $_reg[$design_type];

// default 테마 프리셋 (pr_id=1) 의 현재 값
$_pr1 = sql_fetch("SELECT * FROM `{$g5['theme_preset_table']}` WHERE pr_id = 1", false);
$_col = $_def['column'];
$_cur_raw = isset($_pr1[$_col]) ? (string)$_pr1[$_col] : '';
if ($_cur_raw === '' || $_cur_raw === 'default') $_cur_raw = '1';
$_cur_parsed = parse_design_value($_cur_raw);

// 통합 옵션
$_choices = get_design_choice_options($design_type);
$_by_group = array('preset' => array(), 'skin' => array(), 'none' => array());
foreach ($_choices as $_opt) { $_by_group[$_opt['group']][] = $_opt; }

// 시기 테마 활성 여부 (안내용)
$_active_pr_id = function_exists('get_active_preset_id') ? (int)get_active_preset_id() : 1;
?>
<style>
.dgb-box{border:1px solid #b3d4ff;background:#f0f7ff;border-radius:6px;padding:14px 16px;margin:0 0 18px;}
.dgb-box h3{margin:0 0 6px;font-size:14px;color:#234;}
.dgb-box .dgb-desc{font-size:12px;color:#456;margin:0 0 10px;line-height:1.6;}
.dgb-box form{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.dgb-box select{min-width:280px;}
.dgb-box .dgb-cur{font-size:12px;color:#1f6feb;margin-left:4px;}
.dgb-box .dgb-warn{font-size:11px;color:#a07000;margin-top:8px;}
.dgb-box .dgb-link{font-size:11px;color:#666;margin-left:8px;}
</style>
<div class="dgb-box">
    <h3>글로벌 디자인 — <?php echo h($_def['label']); ?> (default 테마 프리셋의 기본값)</h3>
    <p class="dgb-desc">
        사이트 전체에 적용되는 <strong>default 테마 프리셋</strong>의 <?php echo h($_def['label']); ?>입니다.
        디자인 프리셋과 스킨 중 무엇이든 여기서 바로 바꿀 수 있습니다.
        세부 편집은 <a href="./theme_preset_form.php?pr_id=1">테마 프리셋 #1</a>에서도 가능합니다.
    </p>
    <form method="post" action="./design_global_update.php">
        <input type="hidden" name="type" value="<?php echo h($design_type); ?>">
        <select name="value">
            <?php if (!empty($_by_group['none'])): foreach ($_by_group['none'] as $opt): ?>
            <option value="<?php echo h($opt['value']); ?>"<?php echo ($_cur_raw === $opt['value']) ? ' selected' : ''; ?>><?php echo h($opt['label']); ?></option>
            <?php endforeach; endif; ?>
            <?php if (!empty($_by_group['preset'])): ?>
            <optgroup label="디자인 프리셋">
                <?php foreach ($_by_group['preset'] as $opt): ?>
                <option value="<?php echo h($opt['value']); ?>"<?php echo ($_cur_raw === $opt['value']) ? ' selected' : ''; ?>><?php echo h($opt['label']); ?></option>
                <?php endforeach; ?>
            </optgroup>
            <?php endif; ?>
            <?php if (!empty($_by_group['skin'])): ?>
            <optgroup label="스킨">
                <?php foreach ($_by_group['skin'] as $opt): ?>
                <option value="<?php echo h($opt['value']); ?>"<?php echo ($_cur_raw === $opt['value']) ? ' selected' : ''; ?>><?php echo h($opt['label']); ?></option>
                <?php endforeach; ?>
            </optgroup>
            <?php endif; ?>
        </select>
        <button type="submit" class="ui-btn admin">저장</button>
        <span class="dgb-cur">
            현재:
            <?php if ($_cur_parsed['kind'] === 'preset'): ?>
                <strong>프리셋 #<?php echo (int)$_cur_parsed['value']; ?></strong>
            <?php elseif ($_cur_parsed['kind'] === 'skin'): ?>
                <strong>스킨 <code><?php echo h($_cur_parsed['value']); ?></code></strong>
            <?php else: ?>
                <em>사용 안 함</em>
            <?php endif; ?>
        </span>
    </form>
    <?php if ($_active_pr_id !== 1): ?>
    <div class="dgb-warn">⚠ 현재 시기 테마 프리셋(#<?php echo $_active_pr_id; ?>)이 활성화되어 있어, 사이트에 표시되는 디자인은 그쪽 설정이 우선입니다. 위 값은 <strong>기본값(default)</strong>만 변경합니다.</div>
    <?php endif; ?>
</div>
