# 타임라인에 액션 모듈을 붙일 때 (함정 모음)

기본 제공 액션(`bbs_action_search`, `bbs_action_combine`, `bbs_dice`, `bbs_item_use`)을 **타임라인 AJAX 컴포저**에 붙일 때 반복되는 실수다.  
운영자 안내는 [`docs/module/AUTHORING.md`](../docs/module/AUTHORING.md). 일반 모듈 규칙은 [`docs/module/AGENTS.md`](../docs/module/AGENTS.md) (훅 카탈로그 §4, 폼 매트릭스 §6).

레퍼런스: [`bbs_action_search/`](bbs_action_search/), [`bbs_action_combine/`](bbs_action_combine/), [`bbs_dice/`](bbs_dice/), [`bbs_item_use/`](bbs_item_use/).

1. [스키마](#1-스키마--게시판-write-와-타임라인-write-는-다르다)
2. [스테이징 vs 라이브](#2-스테이징-패키지를-라이브로-착각하지-말-것)
3. [AJAX JSON](#3-타임라인-ajax는-json만-받는다)
4. [FormData](#4-타임라인-글쓰기는-formdata를-수동으로-만든다)
5. [컴포저 패치](#5-아이템횟수-ui는-폼-전체를-갈아끼우지-말-것)
6. [어셋 훅](#6-bbs_enqueue_assets--게시판-_head-와-타임라인은-별개)
7. [모듈 rename](#7-모듈-이름-변경은-폴더만-바꾸면-안-된다)
8. [게시판 vs 타임라인 컨텍스트](#8-컨텍스트-차이-게시판-vs-타임라인)
9. [선택 연동 로그](#9-선택-연동이-꺼져도-로그-본문은-비우지-말-것)
10. [캐릭터 횟수 메모리](#10-db를-고친-뒤-메모리-character-를-다시-읽어라)
11. [작업 후 확인](#11-작업-후-최소-확인)

---

## 1. 스키마 — 게시판 write 와 타임라인 write 는 다르다

타임라인 글 테이블은 `{prefix}timeline_{tl_table}` 이다. `g5_write_*` 가 아니다.

| 무엇을 바꾸는가 | 어디만 고치는가 | 넣지 말 곳 |
|----------------|-----------------|------------|
| 타임라인 **시스템** 테이블·코어 컬럼 | `install/gnuboard5.sql` (+ `adm/db_check.php`) + (write면) `timeline/_functions.php` 의 `get_timeline_write_schema()` / `get_timeline_write_indexes()` | 프론트/ajax/`SHOW COLUMNS` 후 자동 ALTER |
| **모듈 전용** write 컬럼 | 그 모듈 `install.php` 의 `mdl_db_add_write_column()` | `get_timeline_write_schema()`, 타임라인 설치 페이지, `adm/sql_write.sql` |
| `config` / `character` 컬럼 | 해당 모듈 install 또는 원래 소유 모듈 | 타임라인 설치 페이지 |

- 프론트·ajax·관리자 목록/폼 PHP에서 `CREATE TABLE` / `ALTER TABLE` / `SHOW COLUMNS` 후 자동 추가 금지.
- 새 타임라인 생성은 `get_timeline_write_schema()` 만 본다. 모듈 컬럼을 여기에 넣으면 **모든 타임라인에 모듈 스키마가 강제**된다. 모듈은 `mdl_db_add_write_column` 로 충분하다. (주사위 `wr_dice` 는 코어 스키마에 이미 있는 예외. 새 모듈은 따라 하지 말 것.)
- 함수 라이브러리는 `timeline/_functions.php`. `extend/` 에 두지 않는다. 로드는 `timeline/_common.php` 와 관리자 타임라인 메뉴만.

---

## 2. 스테이징 패키지를 라이브로 착각하지 말 것

사이트 루트에 있는 배포용 복사본 폴더는 **런타임이 아니다.** 라이브는 `module/<name>/` 만.

- 버그 수정은 `module/<name>/` 만. 스테이징 폴더·`skin/board/mmb/`·`extend/character.lib.php` 를 고치지 않는다.
- 스테이징을 사이트 루트로 복사·머지하지 않는다. 파일명 충돌로 다른 기능이 사라진다.
- 액션 모듈 폴더명 관례: `bbs_action_search`, `bbs_action_combine`. 주사위는 `bbs_dice`, 아이템 게이트는 `bbs_item_use`.

---

## 3. 타임라인 AJAX는 JSON만 받는다

글/댓글은 `timeline/ajax/ajax_post.php`, `ajax_comment.php` → `timeline.common.js` 의 `submitTimelineContent()`.

**금지**

- 실패 시 `alert()` HTML을 그대로 출력. 프론트는 “서버 응답 형식이 올바르지 않습니다.”
- 처리 PHP에서 워닝/노티스를 JSON 앞에 붙이는 것 (`display_errors` 끄고 버퍼 정리).
- 성공 콜백에서 없는 변수(`data.server_time`)를 읽어 예외를 파싱 실패로 오인하게 하는 것.

**필수**

- 모듈 fail 헬퍼는 **게시판 `alert()` 와 타임라인 JSON을 한 함수에서 분기**. `SCRIPT_NAME` 이 `ajax_post.php` / `ajax_comment.php` 이면 `timeline_post_json()` / `timeline_comment_json()`, 아니면 기존 alert.
- 성공 경로도 동일. 타임라인에서 `alert()` 나 `goto_url()` HTML을 내면 JSON 파서가 죽는다.
- `insert_timeline_post()` 는 본문·로그·첨부가 모두 비면 거절한다. 액션만 있는 글은 `$options['customer_sql']`(또는 `log` / `item_log` / `wr_file`)가 있어야 통과한다.

탐색·조합은 횟수가 없으면 `handled=true` 후 조용히 돌아가거나 메시지를 남긴다. **HTML alert 를 echo 하지 말 것.**

---

## 4. 타임라인 글쓰기는 FormData를 수동으로 만든다

`submitPost()` 는 `<form>` submit 이 아니라 `FormData` 를 필드별로 붙인다. 모듈이 폼에 `<select name="make_1">` 를 넣어도 **JS가 안 붙이면 POST에 없다**.

- 새 POST 키는 `appendTimelineFormFields()` 가 스캔하는 `#tl-form` / 인라인 답글 form 안에 `name` 이 있어야 한다. 조합 재료 `make_1`/`make_2`/`make_3`, 주사위 `wr_dice`, 아이템 `use_item` 이 해당.
- `input[type=file]` 은 본문 `images[]`/`image` 가 아니면 FormData에 붙인다. disabled 파일은 안 실린다.
- 빈 `<option value="">` 를 필수 select 에 두지 말 것. 항상 유효한 값이 하나 선택된 상태.
- disabled 는 FormData에 안 실리므로, 전송 전에 활성 옵션을 보장하거나 hidden 을 둔다.

댓글 액션은 글쓰기 select 와 UI가 달라도 POST `name` 은 백엔드와 맞출 것. 타임라인 **인라인 답글**은 원글과 같이 `action=S` / `action=H` 를 쓴다. **원글 `action` 만 보고 댓글을 거절하지 말 것.**

---

## 5. 아이템/횟수 UI는 폼 전체를 갈아끼우지 말 것

액션 성공 후 인벤·남은 횟수가 안 바뀌면 “아이템을 못 받은 줄” 안다. 탐색 성공은 그 요청에서 `insert_inventory` 한다.

- `reloadComposerFields()` 기본은 `ajax_composer_fields.php?fragment=inventory`.
- 패치 대상: `#tl-item-area` (`bbs_write_form_after_action`), `select[name=action]` 옵션, `.tl-action-area .comment-data` 패널, `#tl-composer-extra-icons` / `#tl-composer-extra-panels` (`bbs_write_form_composer_*`).
- **하지 말 것**: `#tl-form` innerHTML 전체 교체 → 본문 초안 소실, 멘션 리스너 재바인딩 누락.
- 원글 게시 후: 본문·이미지만 로컬에서 비우고 인벤 패치. 댓글/답글 후: 메인 글쓰기 초안은 유지한 채 인벤만 패치.
- 패널을 열어 둔 상태면 패치 후에도 `display` 유지.
- 액션 옵션을 바꾼 뒤 **새 패널을 DOM에 넣은 다음** `change` 를 쏴야 `.comment-data.on` 이 새 노드에 붙는다.
- 아이템 패널 안 인라인 `<script>` 는 `innerHTML` 만으로는 안 돈다. `tlSetHtmlWithScripts()` 필요.

선물 목록 UX:

- `it_use_mmb_able=0` 아이템은 **사용** 탭에 안 나온다. 선물만 된다. 사용 가능 아이템이 있으면 기본 탭이 사용이라 새로 받은 선물이 안 보이는 것처럼 보인다.
- 선물 대상 멤버가 0명이어도 **아이템 select는 그린다**. 대상만 “선물할 수 있는 멤버가 없습니다.”
- 인벤 패치는 **실제로 `insert_inventory` / 차감한 직후**에만 의미가 있다.

---

## 6. `bbs_enqueue_assets` — 게시판 `_head` 와 타임라인은 별개

게시판은 `bbs/_head.php` 가 `bbs_enqueue_assets` 를 쏜다.  
타임라인은 그 경로를 안 탄다. 로그 HTML만 있고 CSS가 없으면 “스타일이 안 입혀진 로그”가 된다.

- 발사 위치: `timeline_print_css_head()` 안 (`timeline/_common.php`). 프로필·목록·상세처럼 이 함수를 호출하는 페이지에 CSS가 붙는다.
- `ajax_load.php` 조각 HTML은 부모 페이지 CSS를 상속한다. **부모**(`profile.php`, `index.php`) 가 훅을 안 쏘면 목록 로그가 깨진다.
- `add_stylesheet()` 은 `$html_process` 가 최종 HTML `<head>` 에 주입한다. `_head.php` 이후 호출해도 동작한다. 훅을 안 쏘는 게 문제지, 타이밍이 문제가 아니다.
- 로그 CSS와 작성 UI CSS/JS를 나눌 것. 골격은 [`bbs_action_sample/inc/assets.inc.php`](bbs_action_sample/inc/assets.inc.php): `sample.log.css` 는 보드 액션 스코프 OFF여도 로드(과거 로그). `sample.ui.css` / `sample.write.css` / `sample.js` 는 write 또는 comment ON. 타임라인 프로필은 **로그 CSS가 필수**. 조합의 작성 JS는 같은 훅에 `combine.js`.
- 게시판에서도 같다. `bbs_enqueue_assets` 전체를 `mdl_board_enabled(..., 'write')` 또는 comment 에 묶으면 스코프만 꺼도 로그 스타일이 사라진다. 점검: [HOOK_CHECKLIST.md §F-2](HOOK_CHECKLIST.md).
- `SCRIPT_NAME === 'list.php'` 로 로그 CSS를 제한하지 말 것. mmb 목록은 `board.php`.

---

## 7. 모듈 이름 변경은 폴더만 바꾸면 안 된다

폴더명 = `module.php` `'name'` = `mdl_board_enabled()` = `mdl_setting_*($module)` = `board_state.json` 의 `<name>@write` / `@comment` = 관리자 `?m=` = `G5_MDL_URL.'/<name>'`.

이름만 바꾸면:

- `state.json` 의 옛 이름은 활성인데 폴더가 없어 **모듈이 안 로드**된다.
- KV가 옛 `ms_module` 에 남으면 설정이 초기값으로 돌아간 것처럼 보인다 (`able=0`).
- `install.php` 는 `versions[new]` 가 이미 있으면 **다시 안 돈다**. 이관은 `install.php` + 라이브러리 로드 시 멱등 마이그레이션.

이관 체크리스트:

1. 폴더 이동
2. `data/module/state.json` `active` / `versions`
3. `data/module/board_state.json` 스코프 키
4. `avo_module_setting.ms_module` 복사 후 구키 삭제
5. `reset_setting.setting_url` 의 `m=옛이름` REPLACE
6. 관리자 hidden `m=`, 메뉴 URL, CSS 경로
7. `uninstall.php` 는 신·구 모듈명 KV 모두 drop

**유지해도 되는 것** (데이터·호출부 호환): DB 테이블명, PHP 함수 접두사, reset 키 (`character_search`, `character_combine`), POST 액션값 (`S`, `H`, `wr_dice`).

---

## 8. 컨텍스트 차이 (게시판 vs 타임라인)

| | 게시판 | 타임라인 |
|--|--------|----------|
| `$bo_table` | 게시판 id | `$tl_table` (`timeline/_common.php` 가 대입) |
| 글 저장 | `write.php` | `insert_timeline_post()` |
| 컴포저 | 스킨 `write.tpl.php` | `#tl-form` + `composer_fields.skin.php` |
| 어셋 | `bbs/_head.php` | `timeline_print_css_head()` |
| 목록 로그 | 스킨 `bbs_render_row_extra()` | 타임라인 아이템 스킨이 같은 훅을 호출하는지 확인. 피드 댓글 extra `scope` 는 `wr_is_comment` ([HOOK_CHECKLIST.md §F-4](HOOK_CHECKLIST.md)) |

`mdl_board_enabled($bo_table, $module, 'write'|'comment')` 의 `$bo_table` 은 타임라인에서도 타임라인 테이블명이다. 게시판 id를 하드코딩하지 말 것.

글쓰기 액션은 select(`action=S` / `H`). 댓글 UI는 스킨마다 다르다.

- 타임라인 **인라인 답글**은 `action_ui=select` 이고 POST `action` 은 원글과 같다. 옵션/패널은 `bbs_comment_form_action_options` / `bbs_comment_form_extra`. 패널 id 는 `#action_{scope}_TYPE`.
- mmb 댓글도 `action_ui=select`. 패널 id 는 `action_{wr_id}_{value}`.
- 기본 제공 네 모듈은 **`scopes` = write 와 comment**. 글 훅 `'write'`, 댓글 훅 `'comment'`. `mdl_board_enabled_for_w` 권장. 한 액션의 단계를 같은 스위치로 묶을 때만 write 만 — [HOOK_CHECKLIST.md §G-5](HOOK_CHECKLIST.md).
- 피드·프로필에서 댓글 카드 extra `scope` 는 `content_type` 만이 아니라 `wr_is_comment`.

연결(모듈 행 `wr_id`)은 `write_after` 가 `w=c` 를 건너뛰면 **`comment_after`**. 상세: [HOOK_CHECKLIST.md §G-5](HOOK_CHECKLIST.md).

`ajax_post.php` 는 **INSERT 전에** `_module_write_update.inc.php` 를 부른다. `$ctx['wr_id']` / `$ctx['wr_num']` 은 0. 글 번호를 지금 써야 하면 placeholder 를 넣고 `write_after` 에서 실제 `wr_id`/`wr_num` 으로 고친다. 댓글(`ajax_comment.php`)은 부모 `wr_id`/`wr_num` 이 이미 있다.

모듈 write 컬럼은 `mdl_db_add_write_column()` 이 타임라인 별칭을 채운 뒤에야 타임라인 테이블에도 붙는다. [HOOK_CHECKLIST.md §I-5](HOOK_CHECKLIST.md).

---

## 9. 선택 연동이 꺼져도 로그 본문은 비우지 말 것

다른 모듈이 꺼져 있으면 연동 문구가 빈 문자열이 된다. 액션은 성공인데 로그 칸이 비면 사용자는 실패로 본다.

- 연동 ON: 부가 정보를 **추가로** 붙인다.
- 연동 OFF: 모듈 자체 기본 문장을 **항상** 출력한다. 탐색 실패 로그 `S||F` 도 빈 칸이 아니다.
- 실패도 빈 칸이 아니라 명시적 miss 문구.

`bbs_view_row_extra` / `views/log_*.php` 가 타임라인 아이템 스킨에서 실제로 include 되는지도 확인할 것. 훅만 등록하고 스킨이 안 부르면 목록에 로그가 안 나온다.

---

## 10. DB를 고친 뒤 메모리 `$character` 를 다시 읽어라

일일 횟수처럼 캐릭터·reset 카운터를 쓰는 모듈(탐색 `character_search`, 조합 `character_combine`):

- 날짜가 바뀌면 DB `UPDATE` 만 하고 요청 중 메모리 값을 그대로 쓰면 **어제 값이 한 번 더 쓰인다**.
- 횟수 아이템 사용 시 **인벤 주인** `ch_id` 를 갱신한다. 현재 로그인 캐릭터가 아니다.
- 인벤 패치는 **실제로 지급·차감한 직후**에만 의미가 있다.

설정 저장 SQL 은 반드시 `WHERE cf_id=...` (또는 PK). 모듈 설정 페이지를 패키지에서 옮길 때 이 가드가 빠지기 쉽다.

---

## 11. 작업 후 최소 확인

- [ ] 타임라인에서 액션 POST 키가 네트워크 탭에 실리는지 (`action`, `make_1`/`make_2`/`make_3`, `wr_dice`, `use_item`)
- [ ] 실패·성공 응답이 JSON인지 (`alert(` HTML이 아닌지)
- [ ] 목록·**프로필**·상세에서 로그 CSS가 로드되는지 (액션 스코프 OFF 여도. [HOOK_CHECKLIST.md §F-2](HOOK_CHECKLIST.md))
- [ ] 프로필/멘션 등 피드 댓글 카드에 모듈 로그가 있는지 (extra `scope`. [HOOK_CHECKLIST.md §F-4](HOOK_CHECKLIST.md))
- [ ] 획득/사용 후 선물·사용 드롭다운이 폼 전체를 리셋하지 않고 갱신되는지
- [ ] 모듈 컬럼이 `get_timeline_write_schema()` 에 들어가지 않았는지
- [ ] `mdl_db_add_write_column` 후 `{prefix}timeline_{tl}` 에 컬럼이 생겼는지 ([HOOK_CHECKLIST.md §I-5](HOOK_CHECKLIST.md))
- [ ] 원글 액션이 `wr_id`/`wr_num` 을 쓰면 `write_after` 로 보정하는지 (`ajax_post` 는 INSERT 전)
- [ ] 스테이징 폴더를 패치하지 않았는지
- [ ] 선택 연동 OFF 일 때도 로그 기본 문구가 있는지
- [ ] 일일 리셋 직후 같은 요청에서 횟수가 어제 값으로 쓰이지 않는지
- [ ] 글/댓글 스코프를 기본 제공처럼 따로 켰는지 (한 액션 단계면 write 만 — [HOOK_CHECKLIST.md §G-5](HOOK_CHECKLIST.md))
