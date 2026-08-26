# 주사위 (`bbs_dice`)

글·댓글 작성 시 **주사위**를 굴리는 기본 제공 모듈입니다. action 값이 아니라 `wr_dice` 체크입니다.  
켜기·보드 스코프 안내는 [모듈 사용 안내](../../docs/module/AUTHORING.md) 를 봅니다.

면 이미지·문구 설정: `/adm_module/module.php?m=bbs_dice&p=dice_form`

---

## 화면 커스텀

플레이어가 보는 화면의 마크업을 바꿀 때 엽니다. 경로 기준 `module/bbs_dice/`.  
전용 CSS 파일은 없고, 면 그림은 `img/` (관리자에서 업로드) 입니다.

| 화면 | PHP (마크업) | CSS / 이미지 |
|------|--------------|--------------|
| 글쓰기·댓글 주사위 체크 | `bootstrap.php` (`bbs_*_form_options`) | (게시판/타임라인 스킨) |
| 굴림 결과 행 | `views/row.php` | `img/` 면 이미지 |
