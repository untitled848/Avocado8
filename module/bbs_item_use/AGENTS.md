# bbs_item_use — 보드별 아이템 폼

같은 훅(`bbs_write_form_after_action`, `bbs_comment_form_after_action`)을 쓰되, 렌더에서 보드·원글/댓글을 나눈다.

| | 사용 | 선물 | UI |
|--|------|------|-----|
| mmb 원글 | O | O | Action 과 같이 좌측 `Item`, 우측 라디오+드롭다운. 중첩 `dl>dt` 금지 (`#bo_w dt` 가 absolute). |
| mmb 댓글 | O | X | `사용할 아이템` 문구 없이 드롭다운만. POST `item_mode=gift` 는 서버에서 거절. |
| 타임라인 원글/댓글 | O | O | 현행. `사용할 아이템` 라벨·사용/선물 라디오 유지. |

선물 대상: 타임라인은 `get_timeline_users`, mmb 는 승인 캐릭터 목록(`get_character_list`).
