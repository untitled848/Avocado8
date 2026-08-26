# 탐색 액션 (`bbs_action_search`)

글·댓글에서 **탐색(S)** 을 고르면 결과로 아이템을 얻는 기본 제공 모듈입니다.  
켜기·보드 스코프·아이템(`탐색횟수`) 안내는 [모듈 사용 안내](../../docs/module/AUTHORING.md) 를 봅니다.

설정: `/adm_module/module.php?m=bbs_action_search&p=setting`

---

## 화면 커스텀

플레이어가 보는 화면의 마크업·스타일을 바꿀 때 엽니다. 경로 기준 `module/bbs_action_search/`.  
글쓰기 옵션 문구는 `bootstrap.php` 훅에 인라인입니다. 전용 CSS 파일은 없습니다.

| 화면 | PHP (마크업) | CSS |
|------|--------------|-----|
| 글쓰기·댓글 「탐색」 옵션 | `bootstrap.php` (`bbs_*_form_action_options`) | (게시판/타임라인 스킨) |
| 탐색 결과 로그 | `views/log_S.php` | (게시판/타임라인 스킨) |
