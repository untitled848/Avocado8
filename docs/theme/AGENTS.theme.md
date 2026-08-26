# 사이트 테마 (`theme/`) — 에이전트 가이드

운영·제작자용: [`docs/theme/AUTHORING.md`](docs/theme/AUTHORING.md)  
관리자 UI: [`docs/adm/110-design.md`](docs/adm/110-design.md)  
심층 스펙(일부 구문 주의): [`.github/features/theme-design.md`](.github/features/theme-design.md)

**정본 코드:** [`extend/01_theme.lib.php`](extend/01_theme.lib.php) (`THEME_SLOT_MAP`, `theme_supported_slots`, `theme_include`, `theme_dispatch_override`, `theme_dir_file`).  
디스패처 기동: [`extend/zz_theme_dispatch.php`](extend/zz_theme_dispatch.php).

---

## 1. 멘탈 모델

- 테마 = `theme/{name}/` **부분 오버라이드** 패키지. 전 페이지를 채울 필요 없음.
- 활성 매핑 = **테마 프리셋**의 슬롯 → 테마명 (`avo_theme_preset` + `avo_theme_preset_slot`).
- `'default'` = 가상. 루트 파일 사용. `theme/default/` 폴더는 만들지 않음.
- **디자인 프리셋**(`pd_id`, `css/root.php` `--d-*`) ≠ 테마 폴더. 색은 디자인, 마크업/스킨은 테마.
- **`mtheme:` / 모듈 사이트 테마 폐지.** `is_valid_theme()` / `theme_resolve_base()` 가 거부. ORPG `module/*/theme/` 은 **별 시스템**(세션 UI).

시기/`cf_preset_id`/`?preset=` 우선순위는 `get_active_preset_id()`.

---

## 2. 부분 테마 (필수 이해)

멤버란만 배포:

```
theme/foo/
├── override_slots.php   → <?php return array('member');
└── member/skin/viewer.skin.php  (+ list/relation/css 선택)
```

프리셋에서 슬롯 `member` = `foo`, 나머지 `default`.

게시판·타임라인 스킨만이면 `skin/board/mmb/` 또는 `skin/timeline/basic/` + 해당 `skin_*` 슬롯만.

**금지:** “테마니까” 루트 `member/`, `skin/`, `head.php` 전체를 무조건 복사.

---

## 3. `theme_supported_slots()` (코드 기준)

문서 주석/옛 가이드의 “파일 없으면 전체 지원”은 **틀림**. 실제:

1. `override_slots.php` 가 배열 return → 화이트리스트 교집합 (`*`/`all` = 전 슬롯)
2. 없으면 **FS 스캔**: file 슬롯은 파일 존재, dir/skin 슬롯은 디렉터리 존재

관리자 드롭다운: `theme_list_for_slot()` / `theme_list_for_group()`.

부분 테마는 `override_slots.php` 권장.

---

## 4. 슬롯 맵 (요약)

| type | 예 | 동작 |
|------|-----|------|
| `file` | `head`, `menu`, `main`, `bbs_login`… | `theme_include` 또는 디스패처가 동일 상대경로 include |
| `dir` | `member`, `mypage`, `shop`, `inventory`, `world` | 디스패처 + `theme_dir_file('member/skin/…')` |
| `skin` | `skin_board_mmb`, `skin_timeline_basic`… | 디스패처 무시. `get_skin_path` / `theme_skin_dir` |

**frame 그룹:** `head`+`tail`+`head_sub`+`tail_sub` — 항상 동일 테마.

**신규 슬롯:** `$GLOBALS['THEME_SLOT_MAP']` 에 한 줄. 폼·디스패처 자동 반영. 스키마 SQL 불필요(슬롯은 DB 행으로만 저장).

**미등록:** `skin/member/basic`(그누 회원), `schedule2`, `latest`/`banner` 등 — 슬롯 추가 전까지 테마 오버라이드 없음.

**커뮤니티 멤버란 ≠ `skin/member`.** 목록/뷰어는 dir 슬롯 `member` + `theme_dir_file`.

---

## 5. 로드 경로 (건드리는 코드)

| 방식 | 용도 |
|------|------|
| `theme_include($slot,$rel)` | 루트 `head.php`/`menu.php` 등이 early return |
| `theme_dispatch_override()` | SCRIPT_FILENAME ↔ file/dir 슬롯. 매칭 시 include 후 **exit**. adm/plugin/install/data/theme/extend/lib 스킵 |
| `theme_dir_file($rel)` | dir 슬롯 하위 include (member skin 등) |
| `get_skin_path` → `theme_resolve_board_skin` / `skin_{dir}_{skin}` | skin 슬롯 |

모바일 `index.php` 는 디스패처가 `index`를 보므로 `main` 은 `theme_include('main',…)` 별도 처리되는 패턴에 주의.

자산: `theme_asset_url($rel,$slot)` / `theme_css_link_tag` / `theme_asset_link_tag`.  
`/theme/다른이름/` 하드코딩 금지. `theme/verd` → `theme/timeline` CSS `@import` 같은 교차 의존 금지.

`theme/{name}/extend/` 는 **자동 로드되지 않음** (업로드 마커일 뿐).

---

## 6. 작성 규칙 (에이전트 체크리스트)

- [ ] 폴더명: `^[a-zA-Z0-9][a-zA-Z0-9_-]{0,50}$`, not `default`, no leading `_`/`.`
- [ ] 경로 = 루트와 동일 상대경로만
- [ ] 필요한 슬롯 파일만 생성; `override_slots.php` 동기화
- [ ] 테마 파일 **안에서 같은 슬롯 `theme_include()` 재호출 금지** (재진입 가드 → 빈 문자열 → 깨진 fallback)
- [ ] 멤버 `viewer.skin.php` 덮으면 `$viewer_info` / `$viewer_bag` / `$viewer_relation` / `$viewer_extra_tabs` / `$viewer_tools` 출력 — [HOOK_CHECKLIST §J-9](module/HOOK_CHECKLIST.md)
- [ ] 모듈 UI: `get_widget('mod','key',$ch)` only. `include module/…`, `k_*_widget()`, `mdl_widget_{$x}` 금지
- [ ] frame 수정 시 네 파일 모두 + 프리셋 frame 그룹
- [ ] 사이트 테마와 ORPG/`ses_theme` 혼동하지 않기
- [ ] 슬롯·스키마 변경 시 `install/gnuboard5.sql` 은 테이블 정의용; 슬롯 키 추가는 PHP 맵만. 런타임 ALTER 추가하지 말 것 (타임라인 스키마 규칙과 동일 정신)
- [ ] 기존 `det`/`verd` 를 고칠 때도 하드코딩 URL·타 테마 import 새로 넣지 않기

---

## 7. 활성화·업로드

- UI: `adm/theme_preset_list.php`, `theme_preset_form.php`
- 업로드: `adm/theme_upload_list.php`, 마커 [`adm/_theme_upload.inc.php`](adm/_theme_upload.inc.php) (`member/`, `skin/`, `override_slots.php`…)
- 레거시 `cf_theme` / `adm/theme.php` → 프리셋으로 유도. 새 작업에 `cf_theme` 쓰지 말 것.

---

## 8. 레퍼런스 트리

```
theme/timeline/          menu, main, css/menu.css, member/skin/viewer + member.css
theme/verd/         + enter.php, 동일 멤버 스킨
member/skin/        코어 멤버 스킨 (폴백)
skin/board|timeline/…  코어 스킨 (폴백)
```

---

## 9. 관련 문서 상태

| 문서 | 역할 |
|------|------|
| 이 파일 | 에이전트 체크리스트 (우선) |
| `docs/theme/AUTHORING.md` | 사람용 제작 가이드 |
| `.github/THEME_GUIDE.md` | 이 문서로 위임 (구버전 경로 무시) |
| `.github/features/theme-design.md` | 심층 — `override_slots` 폴백·런타임 마이그레이션 서술은 코드와 불일치할 수 있음 → **코드 우선** |
| `docs/module/AGENTS.md` §9 | 어드민 라우터는 유효. **사이트 mtheme 서술은 폐기** — 사이트 테마는 여기 따름 |
