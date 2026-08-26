<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 테마 변수 설정 UI
 * 필요: $layers, $catalog, $tl_table, $timeline
 */

$source_label = array(
    'json'     => '직접 설정',
    'skin'     => '스킨 기본',
    'timeline' => '공통 기본',
);

$group_order = array('배경', '테두리', '글자', '포인트', '상태', '오버레이', '기타');
$grouped = array();
foreach ($layers['keys'] as $key) {
    $meta = isset($catalog[$key]) ? $catalog[$key] : array(
        'label' => $key,
        'group' => '기타',
        'hint'  => '',
    );
    $g = $meta['group'];
    if (!isset($grouped[$g])) $grouped[$g] = array();
    $grouped[$g][] = $key;
}

$payload = array();
foreach ($layers['keys'] as $key) {
    $meta = isset($catalog[$key]) ? $catalog[$key] : array('label' => $key, 'group' => '기타', 'hint' => '');
    $uses = tl_theme_usage_summary(isset($layers['usages'][$key]) ? $layers['usages'][$key] : array());
    $payload[$key] = array(
        'label'     => $meta['label'],
        'group'     => $meta['group'],
        'hint'      => $meta['hint'],
        'effective' => isset($layers['effective'][$key]) ? $layers['effective'][$key] : '',
        'skin'      => isset($layers['skin'][$key]) ? $layers['skin'][$key] : '',
        'timeline'  => isset($layers['timeline'][$key]) ? $layers['timeline'][$key] : '',
        'json'      => isset($layers['json'][$key]) ? $layers['json'][$key] : null,
        'source'    => isset($layers['source'][$key]) ? $layers['source'][$key] : '',
        'uses'      => $uses,
    );
}

if (!function_exists('tlv_is_color_value')) {
function tlv_is_color_value($v) {
    return (bool)preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$|^rgba?\(/i', (string)$v);
}
}

if (!function_exists('tlv_color_input_value')) {
function tlv_color_input_value($v) {
    $v = trim((string)$v);
    if (preg_match('/^#([0-9a-fA-F]{3})$/', $v, $m)) {
        $h = $m[1];
        return '#'.$h[0].$h[0].$h[1].$h[1].$h[2].$h[2];
    }
    if (preg_match('/^#([0-9a-fA-F]{6})$/', $v)) return $v;
    if (preg_match('/^#([0-9a-fA-F]{8})$/', $v)) return '#'.substr($v, 1, 6);
    return '#888888';
}
}
?>

<div class="tlv-page" id="tlv-page"
     data-tl-table="<?php echo h($tl_table); ?>"
     data-tl-subject="<?php echo h(isset($timeline['tl_subject']) ? $timeline['tl_subject'] : ''); ?>"
     data-tl-skin="<?php echo h(isset($timeline_skin) ? $timeline_skin : ''); ?>"
     data-save-url="./ajax/ajax_theme_vars_save.php"
     data-export-url="./ajax/ajax_theme_vars_export.php"
     data-import-url="./ajax/ajax_theme_vars_import.php">

    <div class="tlv-top">
        <div>
            <a href="./index.php?tl_table=<?php echo h($tl_table); ?>" class="tl-back-btn">&larr; 타임라인</a>
            <h1>테마 색 설정</h1>
            <p>
                <?php echo h($timeline['tl_subject']); ?>에 쓰이는 색·스타일 변수입니다.<br>
                적용 순서: <strong>이 페이지 설정</strong> → 스킨 기본 → 공통 기본. 바꾼 값만 저장됩니다.<br>
                JSON 내보내기는 <strong>저장된 직접 설정</strong>만 포함합니다. 저장하지 않은 변경은 파일에 들어가지 않습니다.
            </p>
        </div>
        <div class="tlv-actions">
            <button type="button" class="tlv-btn" id="tlv-reset-all">변경 취소</button>
            <button type="button" class="tlv-btn tlv-btn-ghost" id="tlv-clear-custom">직접 설정 모두 지우기</button>
            <button type="button" class="tlv-btn" id="tlv-export">JSON 내보내기</button>
            <button type="button" class="tlv-btn" id="tlv-import">JSON 가져오기</button>
            <button type="button" class="tlv-btn tlv-btn-primary" id="tlv-save">저장</button>
        </div>
        <input type="file" id="tlv-import-file" accept="application/json,.json" style="display:none">
    </div>

    <div class="tlv-toolbar">
        <input type="search" class="tlv-search" id="tlv-search" placeholder="변수명, 설명, 사용처 검색…" autocomplete="off">
        <div class="tlv-filter" id="tlv-filter">
            <button type="button" class="tlv-chip is-on" data-group="*">전체</button>
            <?php foreach ($group_order as $g) {
                if (empty($grouped[$g])) continue;
            ?>
            <button type="button" class="tlv-chip" data-group="<?php echo h($g); ?>"><?php echo h($g); ?></button>
            <?php } ?>
        </div>
        <span class="tlv-status" id="tlv-status">준비됨</span>
    </div>

    <div class="tlv-preview" aria-hidden="true">
        <div class="tlv-preview-card">
            <strong>포인트</strong>
            <div class="tlv-preview-swatch" style="background:var(--tl-accent)"></div>
            <span class="tlv-preview-btn">게시</span>
        </div>
        <div class="tlv-preview-card">
            <strong>좋아요</strong>
            <div class="tlv-preview-swatch" style="background:var(--tl-bg-like-soft)"></div>
            <span class="tlv-preview-like">♥ 좋아요</span>
        </div>
        <div class="tlv-preview-card">
            <strong>글자</strong>
            <div class="tlv-preview-swatch" style="background:var(--tl-bg-secondary)"></div>
            <span style="color:var(--tl-text-primary);font-size:13px;">본문</span>
            <span class="tlv-preview-muted"> · 메타</span>
        </div>
        <div class="tlv-preview-card">
            <strong>경고</strong>
            <div class="tlv-preview-swatch" style="background:var(--tl-warning-bg)"></div>
            <span style="color:var(--tl-text-warning);font-size:12px;background:var(--tl-warning-bg);padding:2px 6px;border-radius:4px;">경고</span>
        </div>
    </div>

    <?php foreach ($group_order as $g) {
        if (empty($grouped[$g])) continue;
    ?>
    <section class="tlv-group" data-group-section="<?php echo h($g); ?>">
        <h2 class="tlv-group-title"><?php echo h($g); ?></h2>
        <div class="tlv-list">
            <?php foreach ($grouped[$g] as $key) {
                $row = $payload[$key];
                $eff = $row['effective'];
                $src = $row['source'];
                $src_txt = isset($source_label[$src]) ? $source_label[$src] : $src;
                $is_color = tlv_is_color_value($eff);
                $is_custom = ($src === 'json');
                $uses = $row['uses'];
            ?>
            <article class="tlv-item<?php echo $is_custom ? ' is-custom' : ''; ?>"
                     data-key="<?php echo h($key); ?>"
                     data-group="<?php echo h($row['group']); ?>"
                     data-label="<?php echo h($row['label']); ?>"
                     data-hint="<?php echo h($row['hint']); ?>">
                <div class="tlv-item-head">
                    <div class="tlv-item-title">
                        <span class="tlv-label"><?php echo h($row['label']); ?></span>
                        <span class="tlv-key"><?php echo h($key); ?></span>
                        <?php if ($row['hint'] !== '') { ?>
                        <span class="tlv-hint"><?php echo h($row['hint']); ?></span>
                        <?php } ?>
                        <div class="tlv-uses">
                            <?php if (empty($uses)) { ?>
                            <span class="tlv-use-empty">사용처 없음 (정의만 존재)</span>
                            <?php } else {
                                foreach ($uses as $block) {
                                    $sels = array_slice($block['selectors'], 0, 4);
                                    $extra = count($block['selectors']) - count($sels);
                                    $sel_txt = implode(', ', $sels);
                                    if ($extra > 0) $sel_txt .= ' 외 '.$extra;
                            ?>
                            <span class="tlv-use" title="<?php echo h(implode("\n", $block['selectors'])); ?>">
                                <?php echo h($block['file']); ?>
                                · <code><?php echo h($sel_txt); ?></code>
                            </span>
                            <?php }
                            } ?>
                        </div>
                    </div>
                    <span class="tlv-src" data-src><?php echo h($src_txt); ?></span>
                </div>
                <div class="tlv-item-body">
                    <div class="tlv-swatch-wrap">
                        <?php if ($is_color) { ?>
                        <input type="color" class="tlv-color" value="<?php echo h(tlv_color_input_value($eff)); ?>" aria-label="<?php echo h($row['label']); ?> 색">
                        <?php } else { ?>
                        <span class="tlv-color" style="background:<?php echo h($eff); ?>;pointer-events:none;"></span>
                        <?php } ?>
                    </div>
                    <input type="text" class="tlv-input" value="<?php echo h($eff); ?>" spellcheck="false" autocomplete="off">
                    <button type="button" class="tlv-reset-one" data-reset>기본값</button>
                </div>
            </article>
            <?php } ?>
        </div>
    </section>
    <?php } ?>

    <details class="tlv-advanced">
        <summary>고급: JSON 직접 편집</summary>
        <p class="tlv-json-help">직접 설정한 변수만 들어 있습니다. 내보내기 파일(<code>timeline-theme</code>)을 붙여넣어도 됩니다.</p>
        <textarea class="tlv-json" id="tlv-json" spellcheck="false"><?php
            echo h(json_encode($layers['json'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        ?></textarea>
    </details>
</div>

<script>
(function () {
    var page = document.getElementById('tlv-page');
    if (!page) return;

    var DATA = <?php echo json_encode($payload, JSON_UNESCAPED_UNICODE); ?>;
    var SOURCE_LABEL = <?php echo json_encode($source_label, JSON_UNESCAPED_UNICODE); ?>;
    var tlTable = page.getAttribute('data-tl-table') || '';
    var saveUrl = page.getAttribute('data-save-url') || '';
    var exportUrl = page.getAttribute('data-export-url') || '';
    var importUrl = page.getAttribute('data-import-url') || '';
    var statusEl = document.getElementById('tlv-status');
    var searchEl = document.getElementById('tlv-search');
    var jsonEl = document.getElementById('tlv-json');
    var importFileEl = document.getElementById('tlv-import-file');
    var styleEl = document.getElementById('tl-css-vars-json');
    if (!styleEl) {
        styleEl = document.createElement('style');
        styleEl.id = 'tl-css-vars-json';
        document.head.appendChild(styleEl);
    }

    var state = {}; // key -> override string | null(means inherit)
    Object.keys(DATA).forEach(function (k) {
        state[k] = (DATA[k].json != null) ? String(DATA[k].json) : null;
    });

    function setStatus(msg, type) {
        statusEl.textContent = msg || '';
        statusEl.className = 'tlv-status' + (type === 'ok' ? ' is-ok' : (type === 'err' ? ' is-err' : ''));
    }

    function defaultValue(key) {
        var d = DATA[key] || {};
        if (d.skin) return d.skin;
        if (d.timeline) return d.timeline;
        return d.effective || '';
    }

    function effectiveValue(key) {
        if (state[key] != null && state[key] !== '') return state[key];
        return defaultValue(key);
    }

    function sourceOf(key) {
        if (state[key] != null && state[key] !== '') return 'json';
        var d = DATA[key] || {};
        if (d.skin) return 'skin';
        if (d.timeline) return 'timeline';
        return d.source || '';
    }

    function buildJsonObject() {
        var o = {};
        Object.keys(state).forEach(function (k) {
            if (state[k] != null && String(state[k]).trim() !== '') o[k] = String(state[k]).trim();
        });
        return o;
    }

    function extractVarsFromPayload(o) {
        if (!o || typeof o !== 'object' || Array.isArray(o)) {
            throw new Error('객체여야 합니다');
        }
        if (o.format) {
            if (o.format === 'timeline-design') {
                throw new Error('사이트 디자인 JSON입니다. 타임라인 테마 JSON을 선택하세요');
            }
            if (o.format !== 'timeline-theme') {
                throw new Error('타임라인 테마 JSON이 아닙니다');
            }
            if (!o.vars || typeof o.vars !== 'object' || Array.isArray(o.vars)) {
                throw new Error('테마 변수(vars)가 없습니다');
            }
            return o.vars;
        }
        if (o.settings && typeof o.settings === 'object' && o.preset) {
            throw new Error('사이트 디자인 JSON입니다. 타임라인 테마 JSON을 선택하세요');
        }
        return o;
    }

    function applyImportedVars(vars) {
        Object.keys(state).forEach(function (k) { state[k] = null; });
        Object.keys(vars || {}).forEach(function (k) {
            if (DATA[k] == null) return;
            var v = vars[k];
            if (v == null || typeof v === 'object') return;
            v = String(v).trim();
            if (v === '') return;
            state[k] = v;
        });
        syncAll();
    }

    function applySaveResult(res) {
        Object.keys(DATA).forEach(function (k) {
            DATA[k].json = (res.json && res.json[k] != null) ? res.json[k] : null;
            if (res.effective && res.effective[k] != null) DATA[k].effective = res.effective[k];
            if (res.source && res.source[k] != null) DATA[k].source = res.source[k];
            state[k] = (DATA[k].json != null) ? String(DATA[k].json) : null;
        });
        syncAll();
    }

    function isDirty() {
        var dirty = false;
        Object.keys(DATA).forEach(function (k) {
            var cur = state[k] != null ? String(state[k]) : '';
            var saved = DATA[k].json != null ? String(DATA[k].json) : '';
            if (cur !== saved) dirty = true;
        });
        return dirty;
    }

    function applyLiveCss() {
        var o = buildJsonObject();
        var css = ':root{';
        Object.keys(o).forEach(function (k) { css += k + ':' + o[k] + ';'; });
        css += '}';
        styleEl.textContent = css;
        if (jsonEl && document.activeElement !== jsonEl) {
            jsonEl.value = JSON.stringify(o, null, 2);
        }
    }

    function syncItem(item) {
        var key = item.getAttribute('data-key');
        var input = item.querySelector('.tlv-input');
        var color = item.querySelector('input.tlv-color');
        var src = item.querySelector('[data-src]');
        var val = effectiveValue(key);
        if (input && document.activeElement !== input) input.value = val;
        if (color && /^#([0-9a-fA-F]{6})$/.test(val)) color.value = val;
        else if (color && /^#([0-9a-fA-F]{3})$/.test(val)) {
            var h = val.slice(1);
            color.value = '#' + h[0]+h[0]+h[1]+h[1]+h[2]+h[2];
        }
        if (src) src.textContent = SOURCE_LABEL[sourceOf(key)] || sourceOf(key);
        item.classList.toggle('is-custom', sourceOf(key) === 'json');
        item.classList.toggle('is-dirty', state[key] != null && String(state[key]) !== String(DATA[key].json || ''));
    }

    function syncAll() {
        page.querySelectorAll('.tlv-item').forEach(syncItem);
        applyLiveCss();
    }

    function setOverride(key, value, asCustom) {
        value = String(value == null ? '' : value).trim();
        if (!asCustom) {
            state[key] = null;
        } else if (value === '' || value === defaultValue(key)) {
            // 기본과 같으면 오버라이드 제거
            state[key] = null;
        } else {
            state[key] = value;
        }
        var item = page.querySelector('.tlv-item[data-key="' + key.replace(/"/g, '\\"') + '"]');
        if (item) syncItem(item);
        applyLiveCss();
        setStatus('미리보기 반영됨 · 저장을 눌러 확정하세요');
    }

    page.querySelectorAll('.tlv-item').forEach(function (item) {
        var key = item.getAttribute('data-key');
        var input = item.querySelector('.tlv-input');
        var color = item.querySelector('input.tlv-color');
        var reset = item.querySelector('[data-reset]');

        if (input) {
            input.addEventListener('input', function () {
                setOverride(key, input.value, true);
            });
        }
        if (color) {
            color.addEventListener('input', function () {
                setOverride(key, color.value, true);
                if (input) input.value = color.value;
            });
        }
        if (reset) {
            reset.addEventListener('click', function () {
                setOverride(key, defaultValue(key), false);
            });
        }
    });

    // search / filter
    var activeGroup = '*';
    function filterList() {
        var q = (searchEl.value || '').trim().toLowerCase();
        page.querySelectorAll('.tlv-item').forEach(function (item) {
            var g = item.getAttribute('data-group') || '';
            var text = (
                (item.getAttribute('data-key') || '') + ' ' +
                (item.getAttribute('data-label') || '') + ' ' +
                (item.getAttribute('data-hint') || '') + ' ' +
                item.textContent
            ).toLowerCase();
            var okGroup = (activeGroup === '*' || activeGroup === g);
            var okSearch = !q || text.indexOf(q) !== -1;
            item.style.display = (okGroup && okSearch) ? '' : 'none';
        });
        page.querySelectorAll('[data-group-section]').forEach(function (sec) {
            var g = sec.getAttribute('data-group-section');
            var any = false;
            sec.querySelectorAll('.tlv-item').forEach(function (it) {
                if (it.style.display !== 'none') any = true;
            });
            sec.style.display = any ? '' : 'none';
        });
    }
    searchEl.addEventListener('input', filterList);
    document.getElementById('tlv-filter').addEventListener('click', function (e) {
        var btn = e.target.closest('.tlv-chip');
        if (!btn) return;
        activeGroup = btn.getAttribute('data-group') || '*';
        document.querySelectorAll('#tlv-filter .tlv-chip').forEach(function (c) {
            c.classList.toggle('is-on', c === btn);
        });
        filterList();
    });

    document.getElementById('tlv-reset-all').addEventListener('click', function () {
        Object.keys(DATA).forEach(function (k) {
            state[k] = (DATA[k].json != null) ? String(DATA[k].json) : null;
        });
        syncAll();
        setStatus('저장본으로 되돌렸습니다', 'ok');
    });

    document.getElementById('tlv-clear-custom').addEventListener('click', function () {
        if (!confirm('직접 설정한 값을 모두 지울까요? (저장해야 반영됩니다)')) return;
        Object.keys(state).forEach(function (k) { state[k] = null; });
        syncAll();
        setStatus('직접 설정을 비웠습니다 · 저장하세요');
    });

    if (jsonEl) {
        jsonEl.addEventListener('change', function () {
            try {
                var parsed = JSON.parse(jsonEl.value || '{}');
                var o = extractVarsFromPayload(parsed);
                applyImportedVars(o);
                setStatus('JSON을 반영했습니다', 'ok');
            } catch (err) {
                setStatus('JSON 오류: ' + err.message, 'err');
            }
        });
    }

    var exportBtn = document.getElementById('tlv-export');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            if (isDirty() && !confirm('저장되지 않은 변경은 파일에 들어가지 않습니다.\n지금 저장된 값으로 내보낼까요?')) return;
            if (!exportUrl) return;
            location.href = exportUrl + '?tl_table=' + encodeURIComponent(tlTable);
        });
    }

    var importBtn = document.getElementById('tlv-import');
    if (importBtn && importFileEl) {
        importBtn.addEventListener('click', function () {
            if (!confirm('현재 직접 설정을 JSON 파일 내용으로 덮어쓰고 저장합니다.\n(스킨 기본값은 파일에 없는 변수로 돌아갑니다.)\n계속할까요?')) return;
            importFileEl.value = '';
            importFileEl.click();
        });
        importFileEl.addEventListener('change', function () {
            if (!this.files || !this.files[0]) return;
            if (!importUrl) {
                setStatus('가져오기 주소가 없습니다', 'err');
                return;
            }
            var body = new FormData();
            body.append('tl_table', tlTable);
            body.append('json_file', this.files[0]);
            setStatus('가져오는 중…');
            fetch(importUrl + '?tl_table=' + encodeURIComponent(tlTable), {
                method: 'POST',
                body: body,
                credentials: 'same-origin'
            }).then(function (r) { return r.json(); }).then(function (res) {
                if (!res || !res.success) {
                    setStatus((res && res.message) || '가져오기 실패', 'err');
                    return;
                }
                applySaveResult(res);
                setStatus(res.message || '가져왔습니다', 'ok');
            }).catch(function () {
                setStatus('가져오기 요청에 실패했습니다', 'err');
            });
        });
    }

    document.getElementById('tlv-save').addEventListener('click', function () {
        var body = new FormData();
        body.append('tl_table', tlTable);
        body.append('vars', JSON.stringify(buildJsonObject()));
        setStatus('저장 중…');
        fetch(saveUrl + '?tl_table=' + encodeURIComponent(tlTable), {
            method: 'POST',
            body: body,
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (res) {
            if (!res || !res.success) {
                setStatus((res && res.message) || '저장 실패', 'err');
                return;
            }
            applySaveResult(res);
            setStatus(res.message || '저장되었습니다', 'ok');
        }).catch(function () {
            setStatus('저장 요청에 실패했습니다', 'err');
        });
    });

    syncAll();
})();
</script>
