# bbs_action_search — 탐색 액션 (에이전트용)

> AI가 **탐색(S) / 카운터 / 탐색횟수 아이템**을 읽거나 수정할 때 보는 문서.
> 정본은 이 폴더. 추정 금지.

공통: [`docs/module/AGENTS.md`](../../docs/module/AGENTS.md).  
함정: [`../HOOK_CHECKLIST.md`](../HOOK_CHECKLIST.md).  
사람용: [`README.md`](README.md).

코어 모듈(`MDL_CORE_MODULES`). `module_type=search` — 같은 type 단독 활성.  
`scopes`: **write + comment**. `depends` 없음.

---

## 0. 한 줄 / 작업 전

글·댓글에서 탐색(`S`) → `it_seeker=1` 아이템을 확률로 지급. 실패해도 횟수 소모.

| 식별자 | 값 | 절대 바꾸지 말 것 |
|--------|-----|-------------------|
| action | `S` | option · radio · `wr_log` 첫 토큰 |
| 카운터 | `character_search` | map의 `character_map_explore` 와 **다름** |
| 모드 KV | `search_mode` | `minus` / `plus` |
| it_type | `탐색횟수` | 인벤·자비 |

**금지:** 카운터 키를 map 과 섞기 · `cf_item_category` 에 `탐색횟수` 재주입 · 이미 `wr_log` 있는 글 수정 시 재탐색.

스키마: **`install.php`**. 재설치 I-6.

---

## 1. 디렉터리

```
module.php · _hook_list.php · bootstrap.php (로직 전부)
install.php · uninstall.php · admin/setting.php · views/log_S.php
```

lib.php 없음.

---

## 2. 훅

| 훅 | 역할 |
|----|------|
| `bbs_write/comment_form_action_options` | `<option value="S">` / radio |
| `bbs_custom_action` | `action=S` 처리 |
| `bbs_view_row_extra` | `log_S.php` — **보드 스코프 OFF여도** 로그 표시 |
| `character_create_after` | 카운터 초기값 |
| `item_type_options` / `inventory_use_dispatch` / `item_use_pre` | `탐색횟수` |

---

## 3. 데이터

- write: `wr_log` text
- `wr_log` 성공: `S||S||{it_id}||{it_name}||` / 실패: `S||F`
- 아이템 조건: `it_use=Y`, `it_seeker='1'`, `it_seeker_per_s`~`e` vs `rand(0,100)`
- 카운터: `reset_counter_*('character_search', ch_id, mode)`

---

## 4. 런타임

```
option S → remain>0 → seed → 아이템 1건 or 실패
  → insert_inventory(성공) → reset_counter_consume → wr_log SET
수정(u/cu)+기존 wr_log → no-op
```

자비 `탐색횟수`: `wr_item`/`wr_item_log` (`D||…||탐색횟수||…`).

---

## 5. 관리

`?m=bbs_action_search&p=setting` — 모드·분기·횟수. `admin_menus` 그룹 `002`.

---

## 6. 함정

| 증상 | 원인 |
|------|------|
| 다른 탐색형과 충돌 | `module_type=search` |
| 횟수 안 깎임 | 다른 rs_name / remain 0에 handled만 |
| map과 횟수 섞임 | `character_map_explore` 오용 |
| 실패인데 빈 글 | fail 없이 handled만 (검색은 remain 0 시 handled) |

---

## 7. 의도적으로 안 하는 것

uninstall DROP · 패널 UI(옵션만) · 자체 테이블
