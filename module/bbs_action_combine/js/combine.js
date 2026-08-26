/**
 * bbs_action_combine — 클라이언트 제어
 *
 * .make-imtem select 들 사이 같은 옵션 중복 선택 방지
 *    - **패널 단위 스코프** (.comment-data 컨테이너) — 코멘트가 여러 개여도 서로 간섭 X
 *
 * ※ action 변경 → .comment-data 패널 토글은 코어(js/action_panel.js)가 전역 처리.
 *
 * 위임(delegation) 방식: 동적으로 추가되는 폼/패널에도 동작.
 * jQuery 가 로드된 페이지에서만 동작 (bbs/_head.php 가 보장).
 */
(function ($) {
    if (!$) return;

    // .make-imtem 중복 선택 방지 — 패널 단위 스코프
    $(document).on('change', 'select.make-imtem', function () {
        var $panel = $(this).closest('.comment-data');
        if (!$panel.length) $panel = $(this).closest('form');     // fallback

        var $sels = $panel.find('select.make-imtem');
        $sels.find('option').prop('disabled', false);
        $sels.each(function () {
            var v = $(this).val();
            if (v) $sels.not(this).find('option[value="' + v + '"]').prop('disabled', true);
        });
    });
})(typeof jQuery !== 'undefined' ? jQuery : null);
