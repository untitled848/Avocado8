# Timeline 시스템 — 에이전트 레퍼런스 (실제 코드 기준)

> **타임라인**: X(Twitter)/Bluesky 스타일 소셜 타임라인. 아보카도 에디션(그누보드5 기반).
> **로드**: `extend/` 전역 include가 아니다. `timeline/_functions.php` 를 `timeline/_common.php` 와 관리자 타임라인 메뉴에서만 로드한다.
> **문서 정책**: 실제 구현(`timeline/`, `skin/timeline/basic/`, `adm/timeline_*`, `install/gnuboard5.sql`)을 반영한다. 추정 금지.
> **액션 모듈 함정**: [`module/AGENTS.timeline.md`](../module/AGENTS.timeline.md). 운영자 UI: [`docs/adm/301-timeline.md`](../docs/adm/301-timeline.md).

---

## 목차

0. [한눈에 보기](#0-한눈에-보기)
1. [시스템 개요](#1-시스템-개요)
2. [DB 테이블](#2-db-테이블)
3. [부트스트랩](#3-부트스트랩--전역-변수-timeline_commonphp)
4. [함수 레퍼런스](#4-함수-레퍼런스-timeline_functionsphp)
5. [디렉토리 구조](#5-디렉토리-구조)
6. [AJAX](#6-ajax-엔드포인트-timelineajax)
7. [스킨](#7-스킨-skintimelinebasic)
8. [관리자](#8-관리자-페이지-admtimeline_)
9. [모듈 훅](#9-모듈-훅-연동)
10. [특수 기능](#10-특수-기능-요약)
11. [DM](#11-dm)
12. [코딩 규칙](#12-코딩-규칙)
13. [잔존물·TODO](#13-잔존물--todo)

---

## 0. 한눈에 보기

| 항목 | 실제 |
|------|------|
| 함수 라이브러리 | `timeline/_functions.php` (`G5_TIMELINE_LIB` 가드) |
| 페이지 진입점 | `timeline/*.php` — `_common.php` → `../common.php` + `_functions.php` |
| 관리자 로드 | `adm/timeline_require.inc.php` (미설치면 `db_check.php` 로) |
| AJAX | `timeline/ajax/*.php` (`../_common.php` include) |
| 스킨 | `skin/timeline/{tl_skin}/` (`basic` 은 테마 프리셋 슬롯 `skin_timeline_basic` 으로 오버라이드 가능) |
| 관리자 | `adm/timeline_*` |
| 스키마 설치 | `install/gnuboard5.sql` + `adm/db_check.php` (프론트/AJAX에서 CREATE/ALTER 금지) |
| write 컬럼 소스 | `get_timeline_write_schema()` / `get_timeline_write_indexes()` |
| 테이블 접두사 | `G5_TABLE_PREFIX` (이 설치는 `avo_`) |
| PHP 호환 | `ses()` 사용, `??` 지양, `sql_escape_string()` 이스케이프 |
| DM | §11 — `dm.php` / `dm_room.php` / `ajax_dm_*` |

---

## 1. 시스템 개요

### 1.1 핵심 특징

- **게시판 호환 구조**: 그누보드5 board/write를 흉내내 `$board`, `$write_table`, `wr_*` 컬럼을 씀 (MMB 스킨/모듈 재사용).
- **멘션 타래**: 댓글을 `wr_mention_root`(타래 번호) + `wr_reply_to`(직전 대상)로 트리 구성.
- **렌더 모드 2종**: `timeline_user.tl_render_mode` = `normal`(모든 글 평면 노출) / `thread`(내 글·나를 멘션한 글·퍼블릭 원글만).
- **스크립트(HTML) 글**: `wr_script=1` 이면 스마트에디터 HTML을 그대로 출력(기믹·멘션 파싱 생략).
- **타임라인별 프로필**: 표시명/멘션ID/썸/헤더/바이오는 `character`가 아니라 `timeline_user` (`tu_*`).
- **세션 계정 전환**: `$_SESSION['tl_active_ch'][$tl_table]`. 요청동안 `$character`만 바꾸고 `member.ch_id`는 유지.
- **모듈 필터**: `timeline_item_html_class` → `mdl_apply_filters('timeline_item_class')`, `timeline_item_compact_header` → `mdl_apply_filters('timeline_item_compact_header')` (ORPG `se_script` 등).

### 1.2 원글/댓글 판별

```php
$write['wr_parent'] < 0   // 원글 (INSERT 후 wr_parent = -wr_id)
$write['wr_parent'] > 0   // 댓글 ($write['wr_parent'] 가 원글 wr_id)
```

---

## 2. DB 테이블

### 2.1 공유 테이블 (`$g5`, `_functions.php` 상단)

| `$g5` 키 | 테이블 | 용도 |
|----------|--------|------|
| `timeline_table` / `timeline_list_table` | `{prefix}timeline` | 타임라인 목록 (board 대응, PK=`tl_table`) |
| `timeline_write_prefix` | `{prefix}timeline_` | 글 테이블 접두사 |
| `timeline_good_table` | `{prefix}timeline_good` | 좋아요 |
| `timeline_bookmark_table` | `{prefix}timeline_bookmark` | 북마크 |
| `timeline_mention_table` | `{prefix}timeline_mention` | 멘션(`tl_mt_read`) |
| `timeline_user_table` | `{prefix}timeline_user` | 허용 캐릭터 + 프로필 |
| `timeline_temp_table` | `{prefix}timeline_temp` | 답글 임시저장 |
| `timeline_dm_rooms` / `participants` / `messages` / `read` | `{prefix}timeline_dm_*` | DM. `read` 는 **미사용** |

시스템 테이블 CREATE/컬럼 보정은 `install/gnuboard5.sql` + `adm/db_check.php`. `database*.sql` 은 저장소에 없다.  
`config_table`, `character_table` ALTER는 타임라인 설치에 넣지 않는다.

목록 테이블 주요 설정 컬럼: `tl_use_dm`(기본 1), `tl_ti_use`, `tl_poll_interval`(ms, 기본 10000), `tl_dm_poll_interval`(ms, 기본 3000), `tl_use_image`, `tl_image_count`, `tl_content_max`, `tl_read_level`, `tl_write_level`, `tl_1`~`tl_10`.

### 2.2 `timeline_user`

| 컬럼 | 설명 |
|------|------|
| `tl_user_id` | PK |
| `tl_table`, `mb_id`, `ch_id` | UNIQUE `(tl_table, ch_id)` |
| `tu_men_id` | 멘션 ID (`@멘션ID` 매칭 우선). 기본: 이름 첫 토큰 최대 6자 |
| `tu_name` | 표시 이름 (작성 시 `wr_name` 스냅샷 원본) |
| `tu_thumb` / `tu_header` | 프로필 썸 / 헤더 |
| `tu_bio` | 바이오 |
| `tu_profile` | 외부 프로필 URL (기본 `/member/viewer.php?ch_id=…`) |
| `tl_render_mode` | `thread` / `normal` |
| `tl_user_datetime` | 등록일시 |

`add_timeline_user()` 기본값: `tu_name` = `ch_name`, `tu_men_id` = `timeline_default_men_id_from_name(ch_name)`, `tu_thumb` = `get_character_head()`, `tu_profile` = viewer URL, `tl_render_mode='normal'`.

### 2.3 `timeline_temp`

답글 임시저장. UNIQUE `(tl_table, parent_wr_id, tu_id)`. `tu_id` = 활성 캐릭터 ID. 원본 텍스트만 저장.

### 2.4 글 테이블 (`{prefix}timeline_{tl_table}`)

소스: `get_timeline_write_schema()`. 모듈 전용 `wr_event_log`, `wr_mon_*`, `ma_id` 등은 **포함하지 않음**.

| 컬럼 | 원글 | 댓글 | 설명 |
|------|------|------|------|
| `wr_id` | PK | PK | |
| `wr_parent` | **-wr_id** | 원글 wr_id | |
| `wr_is_comment` | 0 | 1 | |
| `wr_num` | MIN-1 (음수) | 원글과 동일 | |
| `wr_mention_root` | 0 | 1~N | 타래 번호 |
| `wr_reply_to` | 0 | 직전 대상 wr_id | |

주요 컬럼: `ch_id`/`mb_id`/`wr_name`, `wr_content`, `wr_file`/`wr_image1~4`, `wr_like_count`/`wr_bookmark_count`/`wr_reply_count`, `wr_good`, `wr_comment`, `wr_log`/`wr_item`/`wr_item_log`/`wr_dice`/`wr_dice1`/`wr_dice2`, `wr_datetime`/`wr_last`/`wr_bump_datetime`(목록 정렬 `wr_datetime DESC, wr_id DESC`), `wr_ip`/`wr_deleted`, `cf_time`, `ti_id`, `wr_img_hidden`/`wr_text_hidden`/`wr_waring_text`, `wr_thumb`, `wr_modified`, `wr_script`, `wr_1`~`wr_10`.

인덱스 (`get_timeline_write_indexes()`): `idx_wr_num`, `idx_wr_parent`, `idx_mb_id`, `idx_ch_id`, `idx_wr_is_comment`, `idx_wr_datetime`, `idx_wr_last`, `idx_wr_bump_datetime`, `idx_wr_mention_root` (`wr_parent`,`wr_mention_root`), `idx_wr_reply_to`.

새 write 테이블: `create_timeline_write_table()` — 설치, `timeline_form_update.php`, `timeline_copy_update.php`.  
`ensure_timeline_write_columns()`: 빠진 컬럼/인덱스 ALTER. **설치·관리자·마이그레이션만.** 프론트/AJAX에서 호출 금지.  
`create_timeline_user_table()` / `create_timeline_temp_table()`: 설치 이관 후 **no-op stub**.

---

## 3. 부트스트랩 & 전역 변수 (`timeline/_common.php`)

`../common.php` 후 `_functions.php` require. **런타임 ALTER 없음.**

1. `$tl_table`: `$_REQUEST['tl_table']` → `bo_table` → `'default'` (`[a-z0-9_]`만).
2. `$timeline` 로드(없으면 default 폴백, 그래도 없으면 `_TIMELINE_ALLOW_NO_TABLE_` 미정의 시 alert).
3. `$board` 를 `$timeline`으로 매핑(MMB 호환). 포함: `tl_use_image`, `tl_image_count`, `tl_content_max`, `tl_use_dm`, `tl_ti_use`, `tl_poll_interval`, `tl_dm_poll_interval`. (`tl_use_action`/`tl_use_dice`/`tl_use_item` 컬럼은 **없음** — 모듈 스코프는 `board_state`.)
4. `$g5['board_table']` = timeline_table, `$write_table` = `{prefix}timeline_{tl_table}`.
5. `$wr_id` 있으면 `$write` 로드.
6. 스킨 (`tl_skin`, 없으면 `basic`). `$board_skin_path` = `$timeline_skin_path`.
7. `G5_TIMELINE_PATH` / `G5_TIMELINE_URL` (`data/timeline`).
8. **항상** `define('_TIMELINE_ALLOW_NO_CHARACTER_', true)`.
9. 게스트: `_TIMELINE_ALLOW_GUEST_` 미정의이고 `tl_read_level > 1` 이면 로그인 요구.
10. 회원이면 `apply_timeline_active_character($tl_table)` → `$my_timeline_users`.
11. `_TIMELINE_SKIP_VIEW_CHECK_` 가 아니면 `can_view_timeline()`.
12. `$can_write = can_write_timeline(...)`.
13. `$qstr` 에 `&tl_table=` 누적, `$tl_qstr = 'tl_table=...'`.

| 변수 | 설명 |
|------|------|
| `$tl_table` / `$timeline` / `$write_table` | 현재 타임라인·설정·글 테이블 |
| `$write`, `$wr_id` | 현재 글 |
| `$board` | 게시판 호환 배열 |
| `$timeline_skin_path` / `$timeline_skin_url` | 스킨 |
| `$my_ch_id` / `$my_profile` | 활성 캐릭터 / 썸 |
| `$my_timeline_users` | 이 회원·이 타임라인 등록 캐릭터 |
| `$can_write`, `$is_approved`, `$ch_state` | 쓰기 권한 / 승인 |

`timeline_print_css_head()`: 스킨 `css_head.inc.php` 우선, 없으면 `timeline/timeline.css` + 스킨 `css/style.css`. 여기서 `bbs_enqueue_assets` 도 쏨.

게스트·뷰 스킵 플래그는 진입점이 `_common.php` **이전**에 정의: `_TIMELINE_ALLOW_GUEST_`, `_TIMELINE_SKIP_VIEW_CHECK_`, `_TIMELINE_ALLOW_NO_TABLE_`.

### 3.1 권한

- 읽기 `tl_read_level` (`can_view_timeline`): `1`=전체, `2`=회원, `3`=사용자(등록 캐릭터 또는 같은 `mb_id`의 등록 캐릭터), `10`=운영자(`super`).
- 쓰기 `tl_write_level` (`can_write_timeline`): `super` 항상. 등록자 0명이면 모든 회원. 그 외 이 `ch_id` 또는 같은 `mb_id`의 등록 캐릭터. 그다음 `1`=허용 사용자, `2`=`super`/`group`만.

### 3.2 활성 캐릭터

세션: `$_SESSION['tl_active_ch'][$tl_table]`.  
`resolve_timeline_active_ch_id()`: 세션 ch(내 사용자) → `member` 대표(내 사용자) → `get_my_timeline_users()` 첫 항목(main→sub→npc).  
전환: `ajax_switch_account.php` POST `ch_id` → `set_timeline_session_ch_id`. JS `switchTimelineAccount` 후 리로드.

---

## 4. 함수 레퍼런스 (`timeline/_functions.php`)

### 4.1 타임라인/테이블

| 함수 | 설명 |
|------|------|
| `timeline_list_table_exists()` | 목록 테이블 존재 (캐시) |
| `timeline_admin_require_installed($as_json)` | 없으면 설치 페이지로 |
| `get_timeline` / `get_timeline_list_all` / `get_timeline_write_table` | 조회 |
| `get_timeline_write_schema()` / `get_timeline_write_indexes()` | write 정의 |
| `create_timeline_write_table` / `ensure_timeline_write_columns` | CREATE / ALTER(**설치에서만**) |

### 4.2 사용자/권한/세션

| 함수 | 설명 |
|------|------|
| `get_timeline_users` / `is_timeline_user` / `add_timeline_user` / `remove_timeline_user` / `count_timeline_users` | 등록 |
| `get_my_timeline_users` / `is_my_timeline_user` | 이 회원 캐릭터 |
| `get_timeline_session_ch_id` / `set_timeline_session_ch_id` | 세션 |
| `resolve_timeline_active_ch_id` / `apply_timeline_active_character` | 활성 ch → `$character` |
| `timeline_default_men_id_from_name($ch_name)` | 멘션 ID 기본값(첫 토큰, 최대 6자) |
| `get_timeline_user_render_mode` / `get_timeline_user_display_name` / `get_timeline_user_men_id` | 프로필 |
| `timeline_html_men_handle($tl_table,$ch_id)` | `@멘션ID` HTML 핸들 |
| `can_write_timeline` / `can_view_timeline` / `can_use_timeline_dm` | 권한 |
| `timeline_dm_enabled` / `get_timeline_poll_interval_ms` | DM·폴링 |
| `get_timeline_read_level_label` / `get_timeline_write_level_label` | 라벨 |

### 4.3 글 작성/수정/삭제

| 함수 | 설명 |
|------|------|
| `insert_timeline_post($tl_table,$ch_id,$content,$options)` | 반환: `success,message,wr_id,mention_root,is_comment` |
| `can_edit_timeline_post` / `update_timeline_post` | 본문/이미지/숨김만. `wr_thumb`/`wr_name`/`wr_log`/dice/`ch_id` **불변**. 기믹 재실행 없음 |
| `delete_timeline_post` | **하드 DELETE**. 원글이면 댓글·good·bookmark·mention 연쇄 |

`$options`: `parent_id`, `reply_to`, `wr_image1~4`, `wr_file`, `log`, `item_log`, `dice1`, `dice2`, `wr_1~10`, `wr_img_hidden`, `wr_text_hidden`, `wr_waring_text`, `is_script`/`wr_script`, `customer_sql`.

요점: 본문 `stripslashes` → 기믹 → 멘션 → SQL 직전 1회 escape. **스크립트 글은 기믹/멘션 생략.** `wr_name`=`tu_name`, `wr_thumb`=`get_timeline_post_thumb_url()`. 원글 INSERT 후 `wr_parent=-wr_id`.

삭제: `ajax_delete.php` → 하드. `ajax_comment_delete.php` → `wr_deleted=1` **소프트**.

### 4.4 목록/상세/타래

| 함수 | 설명 |
|------|------|
| `get_timeline_posts` | `type`: `all`,`public`,`mentions`,`profile`/`profile_replies`,`profile_public`,`bookmarks` |
| `get_my_mention_threads` | 원글에서 내 관련 타래별 최신 1건 |
| `get_timeline_post` / `get_timeline_thread_more` | 상세·이전 답글 |

`get_timeline_posts('all')`: `normal` → 전체, `thread` → 내 글 OR 퍼블릭 원글 OR 멘션. `public`/`profile_public` 은 ajax_load에서 `render_mode=normal` 강제. 프로필 타입은 `wr_script=0`만.

### 4.5 좋아요/북마크/멘션/폴링

| 함수 | 설명 |
|------|------|
| `toggle_timeline_like` / `toggle_timeline_bookmark` | 토글 |
| `parse_timeline_mentions` | `@id`, `@"공백 id"`, `[[id]]` |
| `resolve_timeline_mention_ch_id` | `tu_men_id` → `tu_name`. **character.ch_name 폴백 없음** |
| `insert_timeline_mention` | + `push_call('mention',…)`. 본인 생략 |
| `check_timeline_new` / `mark_timeline_mentions_read` | 폴링·읽음 |

로드 시 `ensure_call_type('mention', …)` 시드.

### 4.6 렌더링/유틸

| 함수 | 설명 |
|------|------|
| `render_timeline_content` / `render_timeline_script_content` | 본문 렌더 |
| `timeline_apply_inline_markdown` | `*bold*` / `**italic*` / `***둘다***` (일반 MD와 볼드/이탤릭 반대) |
| `timeline_apply_monolog` | `(텍스트)` → `.monolog` |
| `timeline_process_content_gimmicks` | `[NdM]`→`[*합*]`, `[A/B/C]` 랜덤. **작성 시 1회** |
| `timeline_row_is_script` / `timeline_content_looks_like_script` | 스크립트 판별 |
| `timeline_item_html_class` / `timeline_item_compact_header` | 모듈 필터 훅 |
| `get_time_ago` | 상대 시간 |
| `get_timeline_thumb` / `get_timeline_row_name` / `get_timeline_row_thumb` / `get_timeline_post_thumb_url` / `get_timeline_profile_image` | 표시·스냅샷 |
| `timeline_thumb_url_to_local_path` / `timeline_resize_uploaded_image` | 업로드(애니 GIF 보존, EXIF 회전) |

### 4.7 답글 임시저장

`get_timeline_temp_tu_id`, `resolve_timeline_temp_parent_wr_id`, `get_timeline_temp` / `save_timeline_temp` / `delete_timeline_temp`, `timeline_temp_row_payload`.

### 4.8 DM

§11. 메시지 스냅샷 `msg_name`/`msg_thumb` = 글의 `wr_name`/`wr_thumb`과 동일 취지.

### 4.9 호환 래퍼

`insert_timeline()`, `toggle_like()`, `toggle_bookmark()`, `check_new_posts()`, `delete_timeline()` — 전역 `$tl_table` 위임.

---

## 5. 디렉토리 구조

```
timeline/
├── _common.php / _functions.php
├── index.php / view.php / profile.php / settings.php / users.php
├── script_write.php / script_write_update.php
├── theme_vars.php
├── _item.php              # ajax_profile 전용 (피드 미사용)
├── _view_detail.php       # 스킨 없을 때 상세 fallback
├── dm.php / dm_room.php / _dm_account_switch.inc.php
├── ajax/                  # §6
├── timeline.common.js / timeline.css
└── AGENTS.md              # 이 문서

skin/timeline/basic/       # §7
adm/timeline_*             # §8
```

---

## 6. AJAX 엔드포인트 (`timeline/ajax/`)

각 파일: `../_common.php` → `echo json_encode(...)`.

| 파일 | Method | 주요 파라미터 | 응답 |
|------|--------|--------------|------|
| `ajax_load.php` | GET | `tab`,`last_id`,`limit`,`ch_id`,`stx` | `success,html,has_more,last_id,count,server_time` |
| `ajax_post.php` | POST | `content`,`parent_id`,숨김,`images[]`, 모듈 `action`/`game`/`use_item` | insert + `html` |
| `ajax_post_update.php` | POST | `wr_id`,`content`,`keep_images[]`,`images[]`,숨김 | update + `html` |
| `ajax_post_get.php` | GET | `wr_id` | 편집용 원본 |
| `ajax_comment.php` | POST | `wr_id`,`reply_to`,`content`,`wr_1~10`,`images[]`,숨김 | `comment_id,parent_id,mention_root` |
| `ajax_comment_list.php` | GET/POST | `wr_id`,`mention_root`,`last_id`,`limit` | `comments[],threads,has_more` |
| `ajax_comment_update.php` | POST | `comment_id`/`wr_id`,`content`,이미지/숨김 | update |
| `ajax_comment_delete.php` | POST | `comment_id` | **소프트** `wr_deleted=1` |
| `ajax_delete.php` | POST | `wr_id`/`tl_id` | **하드** |
| `ajax_like.php` / `ajax_bookmark.php` | POST | `wr_id`/`tl_id` | 토글 |
| `ajax_check_new.php` | GET | `last_check`,`tab` | `new_count,mention_count` |
| `ajax_get_comment_count.php` | GET | `wr_id` | `count` |
| `ajax_mention_search.php` | GET | `q` | `{users:[…]}`. `_TIMELINE_ALLOW_GUEST_` + `SKIP_VIEW` |
| `ajax_thread_more.php` | GET | `from_wr_id`,`limit` | `html,has_more,next_from` |
| `ajax_update_render_mode.php` | POST | `render_mode` | `tl_render_mode` |
| `ajax_update_settings.php` | POST | `tu_*`,`tl_render_mode`, thumb/header | `success` + URL |
| `ajax_switch_account.php` | POST | `ch_id` | 세션만 |
| `ajax_temp.php` | GET/POST | `act`=`get`/`save`/`delete`,`parent_wr_id`,`wr_content` | temp |
| `ajax_theme_vars_save.php` | POST | `vars`(JSON) | 테마 변수 |
| `ajax_theme_vars_export.php` | GET | `tl_table` | `format: timeline-theme` JSON |
| `ajax_theme_vars_import.php` | POST | `json_file` | 가져오기 |
| `ajax_composer_fields.php` | GET/POST | `mode`,`reply_form_id`,`parent_id` | composer HTML |
| `editor_upload.php` | POST | `files[]`/`file` | SmartEditor |
| `ajax_dm_*` | — | — | **§11.4** |

`ajax_profile.php` + `_item.php`: **호출처 없음**. 프로필 피드는 `profile.php` → `ajax_load.php`.

`ajax_post.php` / `ajax_comment.php`: INSERT 전 `bbs/_module_write_update.inc.php`. 성공 후 `mdl_do_action('write_after'|'comment_after', …)`. 댓글 성공 시 해당 부모 temp 삭제.

---

## 7. 스킨 (`skin/timeline/basic/`)

경로: `get_skin_path('timeline', $tl_skin)`. `tl_skin=basic` + 프리셋 `skin_timeline_basic` 테마 매핑이 있으면 테마 경로 우선.

```
index.php → write.skin.php → composer_fields.skin.php
ajax_composer_fields.php → composer_fields.skin.php

ajax_load → item.skin.php | item.origin.skin.php
              └→ item.content.skin.php  (+ bbs_render_row_extra)

view.php → view_detail.skin.php
             ├→ item.content.skin.php
             ├→ inline_reply.skin.php → composer_fields.skin.php
             └→ view_comment.php → view_comment.skin.php
                                    └→ item.comment.skin.php
```

| 파일 | 역할 |
|------|------|
| `item.skin.php` | 피드 카드 → `item.content` (`timeline_item_html_class`) |
| `item.origin.skin.php` | thread 모드 원글 축약(본문 100자) |
| `item.content.skin.php` | 본문/이미지/스포일러/액션. 스크립트 아니면 `bbs_render_row_extra`. compact header 필터 |
| `item.comment.skin.php` | 상세 댓글 1건 |
| `view_comment.php` / `.skin.php` | `$list_item['replies']` |
| `view_detail.skin.php` | 상세·모달 |
| `inline_reply.skin.php` | 인라인 답글 (`bbs_comment_form_*`) |
| `write.skin.php` | 글쓰기 셸. 복수 계정이면 전환 UI |
| `composer_fields.skin.php` | 공통 입력. 답글만 임시저장 버튼 |
| `theme_vars.skin.php` / `lib/theme_vars.lib.php` | CSS 변수 JSON (`data/timeline/{tl_table}/css_vars.json`). 우선순위 **JSON > skin style.css > timeline.css** |
| `css_head.inc.php` | `tl_theme_print_stylesheets()` |
| `css/style.css` / `css/theme_vars.css` | 스킨 / 편집 페이지 전용 |
| `action/log.*.skin.php` | 아이템/탐색(S)/조합(H) 로그 |

**레거시 미연결**: `write_update.inc.php`, `write_comment_update.inc.php`, `write_comment.skin.php`.

스포일러: `wr_img_hidden` / `wr_text_hidden` / `wr_waring_text` → `<details class="tl-spoiler">`.  
스크립트 글: 아바타 숨김, `tl-item-script`, 모듈 extras 숨김, 작성자 메뉴만.

---

## 8. 관리자 페이지 (`adm/timeline_*`)

공통: `timeline_require.inc.php` → `_functions.php` → 목록 없으면 `db_check.php`.

| 파일 | 역할 |
|------|------|
| `adm/db_check.php` + `install/gnuboard5.sql` | 시스템 테이블 CREATE + 컬럼/인덱스 보정 |
| `timeline_list.php` / `timeline_list_update.php` | 목록. 일괄 수정·truncate·삭제. **자동 설치 없음** |
| `timeline_form.php` / `timeline_form_update.php` | 생성·수정. 생성 시 `create_timeline_write_table`. `tl_use_dm`, `tl_ti_use`, 폴링 간격 |
| `timeline_form_module_check.ajax.php` | 스킨별 모듈 호환성 |
| `timeline_user.php` / `timeline_user_update.php` | 허용 사용자 + 읽기/쓰기 레벨 |
| `timeline_copy.php` / `timeline_copy_update.php` | 복사 |
| `timeline_delete.inc.php` | 목록·관련 행·temp·**DROP write**·이미지·모듈 state |
| `timeline_skin_list.php` / `timeline_skin_upload.php` / `timeline_skin_delete.php` | 스킨 tar.gz 목록·업로드·삭제 |

---

## 9. 모듈 훅 연동

| 지점 | 연동 | 근거 |
|------|------|------|
| `bbs_custom_action` / `bbs_customer_sql` 등 | O | `ajax_post`/`ajax_comment` → `bbs/_module_write_update.inc.php` |
| `write_after` / `comment_after` | O | INSERT 성공 후 `mdl_do_action` |
| `bbs_write_form_*` / `bbs_comment_form_*` | O | write / inline_reply / ajax_composer_fields |
| `bbs_render_row_extra` | O | `item.content.skin.php` (스크립트 제외) |
| `bbs_enqueue_assets` | O | `timeline_print_css_head()` |
| `timeline_item_class` / `timeline_item_compact_header` | O | `mdl_apply_filters` |
| 스킨 `write_update.inc.php` 등 | 미연동 | include 없음 |

composer는 항상 신규(`w=''`). 수정은 `ajax_post_update` 모달(모듈 write_update 미경유). `$ctx['bo_table']` = `$tl_table`.

액션 모듈 붙일 때: [`module/AGENTS.timeline.md`](../module/AGENTS.timeline.md).

---

## 10. 특수 기능 요약

- **멘션**: `@멘션ID`, `@"공백 있는 ID"`, `[[멘션ID]]`. `tu_men_id` → `tu_name`. `push_call('mention')`.
- **기믹**(작성 1회): `[3d6]`→`[*합*]`, `[A/B/C]`→랜덤 볼드.
- **인라인 MD**: `*bold*` / `**italic**` / `***둘다***`.
- **모노로그**: `(텍스트)` → `.monolog`.
- **스크립트 글**: `wr_script=1` → `script_write.php` / `script_write_update.php`.
- **시기/타이틀**: `cf_time`, `ti_id` 스냅샷. `tl_ti_use=1`이면 `get_title_image(ti_id)`.
- **렌더 모드**: 설정 또는 `ajax_update_render_mode.php`.
- **테마 변수**: `theme_vars.php` + JSON 내보내기/가져오기.
- **답글 임시저장**: `timeline_temp` + `ajax_temp.php` (메인 글쓰기에는 버튼 없음).
- **계정 전환**: `write.skin.php` / DM 헤더 → `ajax_switch_account.php`.

---

## 11. DM

### 11.1 정책

- 세션·활성 캐릭터는 메인과 동일 (`$_SESSION['tl_active_ch'][$tl_table]`). `member.ch_id` 불변.
- 목록·전송·읽음: **활성 `ch_id`가 참가 중인 방만**. super도 목록은 동일.
- 방 URL 직접 접근: 참가자 또는 `super`. 비참가 super는 `is_creator=0`.
- 헤더 스위처: `count($my_timeline_users)>1` 이면 `_dm_account_switch.inc.php` + 리로드. 전환 후 비참가면 목록으로.
- DM 페이지는 `_TIMELINE_SKIP_VIEW_CHECK_` / `_TIMELINE_ALLOW_NO_TABLE_` 미사용. `$_SESSION['tl_table']` 키 미사용.
- CSS: `timeline/timeline.css` DM 섹션, `--tl-*`만. 페이지 인라인 `<style>` 없음.

### 11.2 표시명·아바타

| 대상 | 이름 | 아바타 |
|------|------|--------|
| 메시지 | 스냅샷 `msg_name` / `msg_thumb` | 동일 |
| 참가자·초대·자동 방 이름 | 현재 `tu_name`(없으면 `ch_name`) | `get_timeline_thumb()` |

옛 메시지 빈 스냅샷은 `hydrate_dm_message_row`가 현재 `tu_*`로 폴백.  
`character.ch_name` / `get_character_name()` / `ch_thumb`를 DM 표시에 쓰지 않는다.

### 11.3 권한

- 페이지: 로그인 + 활성 `ch_id` + `can_view_timeline` + `can_use_timeline_dm`.
- AJAX: `timeline_dm_ajax_require()` → 로그인 + `can_use_timeline_dm`.
- `can_use_timeline_dm`: `tl_use_dm` off면 불가 → `super` 항상 → 등록 0명이면 회원 캐릭터 → 그 외 `is_timeline_user`.
- 초대 대상: `is_valid_dm_participant`. 메시지 전송은 **참가자만**.

### 11.4 테이블

| `$g5` 키 | 용도 |
|----------|------|
| `timeline_dm_rooms` | `room_name`(빈 값=자동), `created_by`, `last_message_at`, `is_deleted`. **`updated_at` 없음** |
| `timeline_dm_participants` | UNIQUE `(room_id,ch_id)`. `last_read_at`, `is_creator`, `is_active` |
| `timeline_dm_messages` | `msg_name`, `msg_thumb`, `content`, `is_deleted` |
| `timeline_dm_read` | 설치만. **코드 미사용**. 읽음은 `participants.last_read_at` |

### 11.5 페이지

- `dm.php`: `get_dm_rooms` → 안읽음은 `last_read_at` 이후 메시지 수. 방 이름 없으면 `get_dm_room_auto_name`.
- `dm_room.php`: 메시지·폴링(`tl_dm_poll_interval`, 기본 3000, 최소 1000). `config.cf_dm_poll_interval` 미사용. 방장/`super`: 제목·초대.
- `_dm_account_switch.inc.php`: `write.skin.php`와 같은 클래스·`switchTimelineAccount`.

### 11.6 AJAX DM

공통: `_common.php` → `timeline_dm_ajax_require()`. `$tl_table` 재파싱 금지.

| 파일 | Method | 동작 |
|------|--------|------|
| `ajax_dm_get_users.php` | GET | `get_dm_candidate_users` |
| `ajax_dm_create.php` | POST | `create_dm_room` (`participants[]`, `room_name`) |
| `ajax_dm_send.php` | POST | 참가자만 `send_dm_message` |
| `ajax_dm_load_messages.php` | GET | `after` 폴링. JSON: `msg_id,ch_id,ch_name,content,avatar,is_mine,created_at` |
| `ajax_dm_add_participant.php` | POST | 방장/`super` → `add_dm_participant`. 시스템 메시지·`ch_id=0` 없음 |
| `ajax_dm_edit_room.php` | POST | 방 이름 최대 50자 |

계정 전환은 `ajax_switch_account.php`(공용).

### 11.7 DM 함수

`can_use_timeline_dm`, `timeline_dm_enabled`, `get_timeline_poll_interval_ms`, `is_valid_dm_participant`, `timeline_dm_ajax_require`, `hydrate_dm_message_row`, `get_dm_candidate_users`, `create_dm_room`, `get_dm_rooms`, `get_dm_room_auto_name`, `get_dm_room`, `send_dm_message`, `get_dm_messages`, `add_dm_participant`, `get_dm_participants`.

### 11.8 DM에 없는 기능

나가기 UI(`is_active=0` 컬럼만 있음), 메시지 삭제 UI, 메시지별 `timeline_dm_read`, 관리자 DM 전용 페이지, 스킨 분리.

---

## 12. 코딩 규칙

1. PHP 상단: `if (!defined('_GNUBOARD_')) exit;`
2. PHP 7.x~8.x: `ses()`, `isset` 확인. `match`/named args 회피.
3. 입력: `sql_escape_string()`. `common.php` addslashes → 본문은 `stripslashes` 후, SQL 직전 1회 escape. (더블 이스케이프 금지 — `.cursor/rules/g5-post-escape.mdc`)
4. 출력: `htmlspecialchars(..., ENT_QUOTES)` 또는 `h()` (스크립트 글 제외).
5. 테이블: `{$g5['timeline_*']}` 또는 `get_timeline_write_table()`.
6. **스키마**: 시스템 = `install/gnuboard5.sql` (+ `db_check.php`). write 코어 = `get_timeline_write_schema()` / `indexes()`도 함께. 모듈 전용 컬럼은 `mdl_db_add_write_column()`만. 프론트·AJAX에서 CREATE/ALTER/`SHOW COLUMNS` 후 자동 추가 금지.
7. 함수 라이브러리는 `timeline/_functions.php`. `extend/`에 두지 않는다.
8. DM: `$tl_table`/`$character`는 `_common.php` 이후 것. 초대는 `add_dm_participant()`만. `updated_at` UPDATE·`ch_id=0` 시스템 메시지 금지.

---

## 13. 잔존물 · TODO

### 잔존물

- `skin/timeline/basic/view_comment.php` — HTML `<!-- view_comment.php DEBUG: ... -->`.
- 레거시 미연결 스킨: `write_update.inc.php`, `write_comment_update.inc.php`, `write_comment.skin.php`.
- 미호출: `timeline/ajax/ajax_profile.php`, `timeline/_item.php`.

`_debug*` / `_test*` / `database*.sql` / `extend/timeline.php` / `timeline_debug.log` 기록은 **코드에 없다**.

### 모듈화 방향 (미구현)

1. 타임라인 페이지에서만 lazy require 하는 상위 게이트.
2. AJAX의 `bbs/_module_write_update.inc.php` 직접 require 유지 vs 타임라인 전용 훅.
3. 관리자 `adm/timeline_*` → 모듈 `admin/` + `admin_menus`.

상세 계획: [`.github/plans/timeline-module.md`](../.github/plans/timeline-module.md).

### TODO

- [ ] 타임라인 모듈화
- [ ] §13 잔존물 정리
- [ ] 해시태그, 인용 게시, 이미지 갤러리, 팔로우
- [ ] DM 나가기·메시지 삭제 UI
