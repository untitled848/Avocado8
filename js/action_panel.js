/**
 * 액션 패널 토글 — 코어 공용 (js/action_panel.js)
 *
 * 게시판/타임라인 어디서든 액션 선택 UI 와 부가 패널(.comment-data)을 연결한다.
 * 예전에는 각 모듈(bbs_action_combine / bbs_events)·스킨(write.tpl / write.skin)이
 * 같은 토글을 중복 구현했으나, 이 파일 하나로 코어에서 처리한다.
 *
 * 지원 UI:
 *   - 글쓰기 폼 : <select name="action">            → 패널 id = action_<value>
 *   - 댓글 폼   : <select name="action"> (mmb) 또는 radio → 패널 id = action_<wr_id>_<value>
 *   - 타임라인  : 위 두 형태 모두 (.tl-action-area 안의 .comment-data)
 *
 * 위임(delegation) 방식이라 AJAX 로 동적 삽입된 폼/패널(인라인 답글 등)에도 동작.
 * 순수 JS — jQuery 없이도 동작 (타임라인 호환).
 */
(function () {
    'use strict';

    function closestForm(el) {
        if (el.closest) return el.closest('form');
        while (el && el.tagName !== 'FORM') el = el.parentNode;
        return (el && el.tagName === 'FORM') ? el : null;
    }

    function toggle(el) {
        var form = closestForm(el);
        if (!form) return;

        var v = '';
        if (el.tagName === 'SELECT') {
            v = el.value || '';
        } else if (el.type === 'radio') {
            if (!el.checked) return;   // 라디오 uncheck 이벤트 무시
            v = el.value || '';
        } else {
            return;
        }

        var panels = form.querySelectorAll('.comment-data');
        for (var i = 0; i < panels.length; i++) panels[i].classList.remove('on');
        if (!v) return;

        // 패널 id 두 형식 지원: action_<v> (글쓰기) / *_<v> (댓글: action_<wr_id>_<v>)
        var suffix = '_' + v;
        for (var j = 0; j < panels.length; j++) {
            var id = panels[j].id || '';
            if (id === 'action_' + v || id.slice(-suffix.length) === suffix) {
                panels[j].classList.add('on');
            }
        }
    }

    document.addEventListener('change', function (e) {
        var t = e.target;
        if (!t || t.name !== 'action') return;
        if (t.tagName !== 'SELECT' && !(t.tagName === 'INPUT' && t.type === 'radio')) return;
        toggle(t);
    });
})();
