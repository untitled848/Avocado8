# 게시판 아이템 사용 (`bbs_item_use`)

글·댓글에서 **아이템 사용·선물** 칸을 붙이는 기본 제공 모듈입니다.  
다른 모듈의 `it_type` 효과가 글에서 돌아가려면 이 모듈이 그 보드에서 켜져 있어야 합니다.  
켜기·보드 스코프 안내는 [모듈 사용 안내](../../docs/module/AUTHORING.md) 를 봅니다.

---

## 화면 커스텀

플레이어가 보는 화면의 마크업을 바꿀 때 엽니다. 경로 기준 `module/bbs_item_use/`.  
전용 CSS 파일은 없습니다. 로그 템플릿은 게시판 스킨에 `template/log.item.tpl.php` 가 있으면 그쪽이 우선입니다.

| 화면 | PHP (마크업) | CSS |
|------|--------------|-----|
| 사용·선물 폼 | `bbs/render.php` (`item_form_select`, `item_form_select_comment`) | (게시판/타임라인 스킨) |
| 아이템 사용 로그 | `bbs/template/log.item.tpl.php` (또는 스킨 `template/`) | (게시판/타임라인 스킨) |
| 처리 로직 | `bbs/use.php` | — |
