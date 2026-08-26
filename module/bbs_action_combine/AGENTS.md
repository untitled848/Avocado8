# bbs_action_combine — 조합 액션 (에이전트용)

> AI가 **조합(H) / 재료 패널 / 레시피**를 읽거나 수정할 때 보는 문서.
> 정본은 이 폴더. 추정 금지.

공통: [`docs/module/AGENTS.md`](../../docs/module/AGENTS.md).  
함정: [`../HOOK_CHECKLIST.md`](../HOOK_CHECKLIST.md).  
사람용: [`README.md`](README.md).

코어 모듈. `module_type=combine`. `scopes`: **write + comment**. `depends` 없음.

---

## 0. 한 줄 / 작업 전

글·댓글에서 조합(`H`) → 인벤 재료 3개 → `{item_table}_recepi` 매칭 → 결과 아이템.  
**실패해도 재료·횟수 소모.**

| 식별자 | 값 | 절대 바꾸지 말 것 |
|--------|-----|-------------------|
| action | `H` | option · `#action_H` · `wr_log` |
| POST | `make_1`~`make_3` | name 고정(id만 scope 접두) |
| 카운터 | `character_combine` | |
| 모드 | `combine_mode` | minus/plus |
| it_type | `조합횟수` | |
| 레시피 | `{prefix}item_recepi` (`_recepi`) | `re_item_order`, `re_use=1` |

**금지:** POST name을 `make_*` 외로 바꾸기 · 패널 id `action_H` / `action_{wr_id}_H` 깨기 · 레시피 CREATE를 이 모듈에만 두고 코어 경로와 불일치.

스키마: **`install.php`** (`wr_log` + 카운터). 레시피 테이블은 코어/다른 경로 — 이 install이 CREATE 안 함. 재설치 I-6.

---

## 1. 디렉터리

```
bootstrap.php · _combine_item.inc.php · js/combine.js
admin/setting.php · views/log_H.php · install/uninstall
```

---

## 2. 훅

| 훅 | 역할 |
|----|------|
| `bbs_write/comment_form_action_options` | `H` |
| `bbs_write_form_action_panels` / `bbs_comment_form_extra` | 재료 select 패널 |
| `bbs_enqueue_assets` | `combine.js` |
| `bbs_custom_action` | `action=H` |
| `bbs_view_row_extra` | `log_H.php` |
| `character_create_after` | 카운터 초기 |

`_combine_item.inc.php`가 `item_type_options` / `inventory_use_dispatch` / `item_use_pre` 등록 — **`_hook_list` uses에 없음** (추가 시 동기화).

---

## 3. `wr_log` / 레시피

- 성공: `H||S||{it_id}||{name}||||{re_item_order}`
- 실패: `H||F||NON||NON||{re_item_order}`
- `re_item_order` = 세 it_id를 정렬해 `||` 연결
- 재료: `it_use_recepi=1` 인벤. `it_use_ever`면 삭제 안 함

API: `bbs_action_combine_render_panel()`, `combine_item_mode()`, `combine_item_apply()`.  
remain: `unlimited_when_no_max`.

---

## 4. 런타임

```
option H → panel make_1..3
  → custom_action: 레시피 조회 → 재료 delete → 결과 insert → consume → wr_log
JS: .make-imtem 토글 (combine.js)
```

custom_action 게이트가 scope 없이 `mdl_board_enabled($bo)` 인 구간 있음 — 폼은 write/comment 분리. 수정 시 기존 `wr_log` 보존.

---

## 5. 관리

`?m=bbs_action_combine&p=setting`. 그룹 `002`.

---

## 6. 함정

| 증상 | 원인 |
|------|------|
| 패널 안 열림 | id ≠ `action_H` |
| 재료 안 읽힘 | POST name ≠ `make_1`~`3` |
| 레시피 없음 | `_recepi` 미생성 / `re_use≠1` / order 정렬 불일치 |
| module_type 충돌 | 다른 combine형 동시 활성 |

---

## 7. 의도적으로 안 하는 것

uninstall DROP · 레시피 어드민 CRUD(이 모듈 setting만) · 실패 시 환불
