# 조합 액션 (`bbs_action_combine`)

글·댓글에서 **조합(H)** 을 고르면 레시피 기반으로 아이템을 만드는 기본 제공 모듈입니다.  
켜기·보드 스코프·아이템(`조합횟수`) 안내는 [모듈 사용 안내](../../docs/module/AUTHORING.md) 를 봅니다.

설정: `/adm_module/module.php?m=bbs_action_combine&p=setting`

---

## 화면 커스텀

플레이어가 보는 화면의 마크업·스타일을 바꿀 때 엽니다. 경로 기준 `module/bbs_action_combine/`.  
옵션·패널 문구는 `bootstrap.php` 와 `_combine_item.inc.php` 쪽에 있습니다. 전용 CSS 파일은 없습니다.

| 화면 | PHP (마크업) | CSS / JS |
|------|--------------|----------|
| 글쓰기·댓글 「조합」 옵션·재료 UI | `bootstrap.php`, `_combine_item.inc.php` | `js/combine.js` |
| 조합 결과 로그 | `views/log_H.php` | (게시판/타임라인 스킨) |
