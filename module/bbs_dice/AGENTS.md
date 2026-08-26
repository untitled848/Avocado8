# bbs_dice — 주사위 (에이전트용)

> AI가 **체크박스 굴림 / wr_dice / 면·이미지 설정**을 읽거나 수정할 때 보는 문서.
> 정본은 이 폴더. 추정 금지.

공통: [`docs/module/AGENTS.md`](../../docs/module/AGENTS.md).  
함정: [`../HOOK_CHECKLIST.md`](../HOOK_CHECKLIST.md).  
사람용: [`README.md`](README.md).

코어 모듈. `module_type=dice`. `scopes`: **write + comment**.  
**action 토큰 없음** — POST `wr_dice=1`.

---

## 0. 한 줄 / 작업 전

글·댓글 옵션 영역의 체크박스로 N면체 × count 개를 굴려 `wr_dice`에 콤마 저장.

| 식별자 | 값 | 절대 바꾸지 말 것 |
|--------|-----|-------------------|
| POST | `wr_dice=1` | 체크 value |
| 컬럼 | `wr_dice` | varchar, `"3,5,2"` |
| 설정 | `count` `sides` `images` `render_mode` `num_*` | KV |
| 훅 폼 | `bbs_*_form_options` | **action_options 아님** |

**금지:** action select에 주사위 넣기 · 이미 굴린 글 수정 시 덮어쓰기 · 레거시 `wr_dice1`/`2` DROP을 install에 넣기(수동).

스키마: **`install.php`**. 재설치 I-6.

---

## 1. 디렉터리

```
lib.php · bootstrap.php · admin/dice_form.php · views/row.php · img/ · install
```

---

## 2. 훅

| 훅 | 역할 |
|----|------|
| `bbs_write_form_options` / `bbs_comment_form_options` | 체크박스 |
| `bbs_custom_action` | `wr_dice=1` → roll → `customer_sql` |
| `bbs_view_row_extra` | `views/row.php` (+ 레거시 dice1/2 폴백) |

---

## 3. 설정·저장

| KV | 범위/의미 |
|----|-----------|
| `count` | 1~10 (기본 2) |
| `sides` | 2~100 (기본 6) |
| `images` | `\|` 구분 URL (면별) |
| `render_mode` | `image` / `number` |
| `num_*` | 숫자 모드 CSS |

`bbs_dice_roll_all()` → 콤마 문자열. 이미지 ≤512KB, png/jpg/gif/webp → `module/bbs_dice/img/`.

API: `bbs_dice_count/sides/render_mode/num_style/num_css/images/roll_all/parse/image_url_for/image_upload`.

---

## 4. 런타임

```
체크 wr_dice=1 → custom_action → wr_dice SET
기존 wr_dice 있으면 옵션 숨김·덮어쓰기 안 함
표시: parse → 이미지 또는 숫자
```

다른 액션과 독립(action 공유 안 함).

---

## 5. 관리

`?m=bbs_dice&p=dice_form` (setting 아님). 그룹 `002`.

---

## 6. 함정

| 증상 | 원인 |
|------|------|
| 옵션 칸에 안 나옴 | action_options에 등록함 |
| 수정 시 또 굴림 | existing 가드 누락 |
| 이미지만 깨짐 | sides와 images 개수 불일치 |
| 옛 글만 보임 | wr_dice1/2 폴백 — 신규는 wr_dice만 |

---

## 7. 의도적으로 안 하는 것

uninstall DROP · wr_dice1/2 자동 DROP · 횟수 카운터
