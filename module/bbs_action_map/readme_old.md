# bbs_action_map — 지역 탐색 (에이전트용)

> AI/에이전트가 이 모듈을 **수정·확장**할 때 읽는 문서.  
> 사용자용 설명: [`README.md`](README.md)  
> 공통 모듈 규칙: [`docs/module/AGENTS.md`](../../docs/module/AGENTS.md)  
> 원본: `F:\laragon\www\AvocadoEdition.MapManager` (스킨 액션 + adm)

---

## 0. 한 줄 요약

게시판/타임라인에서 **위치이동(`MAP`)** 과 **몬스터 공략(`MAP_MON`)** 을 제공.  
`scopes` 는 **write 만**. write ON 이면 원글은 위치이동, 댓글은 원글 `wr_mon_state=S` 일 때 몬스터 공략. comment 체크박스는 두지 않는다.  
통행은 맵의 `dpt_ma_ids`(CSV), 위치는 `character.ma_id`, 결과는 `wr_log` + (몬스터 시) `wr_mon_*`.

| 식별자 | 값 | 절대 바꾸지 말 것 (호환) |
|--------|-----|--------------------------|
| 모듈명 | `bbs_action_map` | 폴더 = `module.php` name = `mdl_*` 인자 |
| action | `MAP`, `MAP_MON` | 옵션 value · 패널 id · `wr_log` 첫 토큰 |
| 카운터 | `character_map_explore` | `bbs_action_search`의 `character_search` 와 **다름** |
| 설정 모드 | `explore_mode` | `minus` / `plus` |

---

## 1. 디렉터리 맵 (수정 시 어디를 열지)

```
module/bbs_action_map/
├── README.md                 ← 사용자용
├── readme_old.md             ← 이 문서 (에이전트용)
├── module.php                메타·admin_menus·requires_hooks
├── _hook_list.php            사용 훅 선언 (문서/검증용, bootstrap과 동기화)
├── bootstrap.php             훅 등록만 (로직은 lib / views)
├── lib.php                   DB 헬퍼·이동/몬스터 처리·패널 HTML
├── install.php               활성화 시 스키마·설정·카운터 (bootstrap 금지)
├── uninstall.php             비활성 시 — 데이터 DROP 안 함
├── css/map.css               패널·오버뷰·로그 스타일
├── js/map.js                 보조(액션 토글은 보드/combine 공통)
├── views/
│   ├── log_MAP.php           wr_log MAP 렌더
│   └── log_MAP_MON.php       wr_log MAP_MON 렌더
└── admin/
    ├── setting.php           횟수·info_panel·맵 이미지
    ├── map_list.php          맵 목록(+통행·좌표·이벤트 링크)
    ├── map_list_update.php
    ├── map_form.php / map_form_update.php
    ├── event_list.php        ?ma_id= 지역별 이벤트
    ├── event_list_update.php
    ├── event_form.php / event_form_update.php
    ├── member_list.php       캐릭터 ma_id 일괄
    └── member_list_update.php
```

업로드 파일 저장: `module/bbs_action_map/data/` (맵 이미지·이벤트 이미지).

---

## 2. 책임 분리 (어디를 고칠지)

| 목적 | 파일 | 비고 |
|------|------|------|
| 훅 추가/게이트 | `bootstrap.php` | `bbs_action_map_board_enabled` (write). `mdl_board_enabled_for_w` / `'comment'` 금지 |
| 이동·이벤트·몬스터 규칙 | `lib.php` | `bbs_action_map_process_*` |
| 로그 HTML | `views/log_*.php` | include 시 `$row`, `$data_log`, `$ctx` |
| 패널 UI | `lib.php` → `bbs_action_map_render_action_panel` | id=`action_MAP`, name=`re_ma_id` |
| 목록 상단 맵 | `bbs_action_map_render_overview` + `list_top` | `map_image` 설정 있을 때만 의미 |
| 스키마/기본설정 | `install.php` | 컬럼은 `mdl_db_add_*` / CREATE IF NOT EXISTS |
| 관리 UI | `admin/*` | 라우트: `adm_module/module.php?m=bbs_action_map&p=<파일명>` |
| 메타·메뉴 | `module.php` | 새 admin 페이지면 여기 + 파일 동시 |

**금지:** `bootstrap.php`에 긴 SQL/이벤트 분기 넣지 말 것. `install.php`에서 `bootstrap.php` require 하지 말 것(훅 이중 등록).

---

## 3. 훅 계약

| 훅 | 역할 | 게이트 |
|----|------|--------|
| `bbs_enqueue_assets` | `map.css` / `map.js` | CSS 항상. JS 는 write ON |
| `list_top` | 전체 맵 오버뷰 HTML append | write ON |
| `bbs_write_form_action_options` | `<option value="MAP">` | write + `wr_log` 비어 있음 |
| `bbs_write_form_action_panels` | `#action_MAP` 패널 | 동일 + `ch_id` |
| `bbs_comment_form_action_options` | `MAP_MON` | write ON + 원글 `wr_mon_state=S` + 같은 `ma_id`. 타임라인은 `list_item` 재조회 |
| `bbs_custom_action` | `MAP`/`MAP_MON` 처리 → `log`/`customer_sql` | write 게이트. MAP 은 원글, MAP_MON 은 `$w=c/cu`. 실패는 `bbs_action_map_fail` |
| `write_after` / `comment_after` | 위치·보상·횟수 / 원글 HP·아이템 소모 | INSERT 성공 뒤에만. 타임라인 훅은 INSERT 전 |
| `bbs_view_row_extra` | `views/log_MAP*.php` | `wr_log` 첫 토큰 |
| `character_create_after` | 카운터 초기값 | `character_map_explore` 등록됨 |

`requires_hooks` / `_hook_list.php` 를 바꾸면 **스킨에 리터럴 호출이 있는지** 확인 (`AGENTS.md`). 누락 시 보드 모듈 체크박스가 disabled.

---

## 4. 데이터 모델

### 4.1 테이블 별칭 (`lib.php`)

| `$g5[...]` | 실제 테이블 |
|------------|-------------|
| `module_map_table` | `{prefix}module_map` |
| `module_map_event_table` | `{prefix}module_map_event` |
| `module_map_character_table` | `{prefix}module_map_character` |

원본 MapManager는 `map` / `map_event` / `map_flow`. 이 모듈은 **접두 `module_`** + 통행을 `dpt_ma_ids` 한 컬럼으로 통합(별도 flow 테이블 없음).

### 4.2 `module_map`

| 컬럼 | 용도 |
|------|------|
| `ma_id`, `ma_name`, `ma_thumb`, `ma_text`, `ma_use` | 기본 |
| `ma_x`, `ma_y`, `ma_width`, `ma_height` | 전체 맵 핫스팟 (% 변환은 overview) |
| `dpt_ma_ids` | 통행 가능 `ma_id` 콤마 목록 |

### 4.3 `module_map_event` (MapManager 스키마)

| 컬럼 | 용도 |
|------|------|
| `me_type` | `''` / `아이템` / `화폐` / `이동` / `몬스터` |
| `me_title`, `me_img`, `me_content` | 로그 표시 |
| `me_get_item`, `me_get_money`, `me_move_map` | 효과 |
| `me_mon_hp`, `me_mon_attack` | 몬스터 → write 컬럼 |
| `me_per_s`, `me_per_e` | 0~100 확률 구간 |
| `me_replay_cnt`, `me_now_cnt` | 0=무제한 / 현재 횟수 |
| `me_use` | 1=사용 |

### 4.4 캐릭터·글

- `character.ma_id` — 현재 위치 (주 저장소). 보조로 `module_map_character` 동기화.
- write: `wr_log`, `ma_id`, `wr_mon_state`(`S`/`E`/''), `wr_mon_hp`, `wr_mon_now_hp`, `wr_mon_attack`

스키마 변경 시: **코드 + `install.php` 동시**. 이미 활성 사이트는 재활성 또는 install 재실행 필요.

---

## 5. `wr_log` 포맷 (파서 계약)

### MAP

```
MAP||{ma_id}||{type}||{me_id}||{msg}
```

| type | 의미 | 뷰 동작 |
|------|------|---------|
| `F` | 이벤트 없음 | “구역으로 이동했다” |
| `D` | 일반 텍스트 | title/content |
| `I` | 아이템 | + 아이템 썸네일, msg |
| `G` | 화폐 | + msg |
| `W` | 강제이동 | + msg (최종 ma_id) |
| `M` | 몬스터 | HP 바 (`row.wr_mon_*`) |

생성: `bbs_action_map_process_move()` · 렌더: `views/log_MAP.php`

### MAP_MON

```
MAP_MON||{state}||{mon_atk}||{user_atk}||유저의HP정보||{d1}+{d2}+{item_atk}
```

- `state`: 진행 `S` / 처치 `E` (원글 `wr_mon_state`와 맞춤)
- 공격력: 주사위 2개(1~6) + 선택 `use_item`(타입 `공격력추가`). `in_id` 는 **현재 캐릭터 소유**만. 적용 후 `delete_inventory`
- 판정: `mon_atk - user_atk` → `<0` 이면 몬스터 HP 감소 (원글 UPDATE 는 `comment_after`)

생성: `bbs_action_map_process_mon()` · 렌더: `views/log_MAP_MON.php`

토큰 순서를 바꾸면 **기존 글 로그가 깨짐**. 확장 시 뒤쪽 필드 append만.

---

## 6. 런타임 흐름

```
[글쓰기]
  option MAP → panel #action_MAP (re_ma_id)
       → bbs_custom_action
       → 카운터 remain 체크 (실패 시 bbs_action_map_fail)
       → bbs_action_map_process_move  (pending 만. 위치/보상은 아직 반영 안 함)
            · 통행 검증 (ma_id=0 이면 전체 사용맵 허용)
            · customer_sql: wr_log, ma_id, (wr_mon_*)
       → write_after → flush: character.ma_id · 이벤트 횟수 · 인벤/포인트 · reset_counter_consume

[댓글]  (write ON 이면 표시. comment 스코프 없음)
  원글 wr_mon_state=S & 같은 ma_id → option/radio MAP_MON
       (타임라인 list_item 은 wr_id 만 → bbs_action_map_comment_parent 로 원글 조회)
       → bbs_action_map_process_mon(write_table, parent wr_id, post, character)
       → comment_after → 원글 HP · use_item 소모
```

패널 id는 **반드시** `action_` + action value (`action_MAP`). combine/보드 스킨 토글 규칙.

---

## 7. 설정·카운터

| 저장소 | 키 | 설명 |
|--------|-----|------|
| `mdl_setting('bbs_action_map')` | `explore_mode` | `minus` 차감 / `plus` 누적 |
| | `info_panel` | `1`/`0` 지역 정보 블록 |
| | `map_image`, `map_image_width`, `map_image_height` | 목록 오버뷰 |
| reset_setting | `character_map_explore` | period/max/plus — **setting.php와 install 동일 키** |

모드 API: `reset_counter_mode('bbs_action_map', 'explore_mode', 'minus')`  
소모: `reset_counter_consume('character_map_explore', ch_id, mode)`  
`rs_max=0`이면 무제한 취급(`unlimited_when_no_max`).

---

## 8. lib API 빠른 참조

| 함수 | 역할 |
|------|------|
| `bbs_action_map_board_enabled` | write 게이트 (댓글 MAP_MON 포함) |
| `bbs_action_map_drop_comment_scope` | 구 `bbs_action_map@comment` 키 제거 |
| `bbs_action_map_get($ma_id)` | 맵 1건 |
| `bbs_action_map_char_ma_id` / `bbs_action_map_set_char_ma_id` | 위치 get/set (+보조테이블) |
| `bbs_action_map_parse_dpt_ids` / `bbs_action_map_able_destinations` | 통행 |
| `bbs_action_map_ensure_write_columns` | 글 테이블 컬럼 보장 |
| `bbs_action_map_pick_event` | 확률 1건 |
| `bbs_action_map_fail` | 실패 JSON/롤백/alert |
| `bbs_action_map_comment_parent` | 타임라인 미완 list_item → 원글 |
| `bbs_action_map_process_move` | → `{ok, log, customer_sql, ma_id, message}` (효과는 write_after) |
| `bbs_action_map_process_mon` | → 동일 shape. `use_item` 소유 검증 (효과는 comment_after) |
| `bbs_action_map_render_action_panel` | 글쓰기 패널 HTML |
| `bbs_action_map_render_overview` | list_top용 |

새 이벤트 타입 추가 시: `process_move` 분기 + admin 셀렉트 + readme_old 표 + (필요 시) log 뷰.

---

## 9. 관리 URL

| p= | 용도 |
|----|------|
| `setting` | 탐색 횟수·맵 이미지 |
| `map_list` / `map_form` | 맵 CRUD |
| `event_list` / `event_form` | `ma_id` 필수(목록은 셀렉트로 전환) |
| `member_list` | 캐릭터 위치 |

`*_update.php` 도 같은 라우터로 include됨(헤드 출력 후 `goto_url`).  
폼 action: `?m=bbs_action_map&p=..._update`.

---

## 10. 원본 대비 의도적 차이

| MapManager | bbs_action_map |
|------------|---------|
| `map_flow` 통행 테이블 | `dpt_ma_ids` CSV |
| 테이블명 `map*` | `module_map*` |
| 스킨 하드코딩 | 모듈 훅 |
| 카운터 없음(원본) | `character_map_explore` |
| 별도 몬스터 관리 메뉴 | 이벤트 type=`몬스터`로 통합 |

원본 로직을 다시 옮길 때 참고 경로:

- 액션: `AvocadoEdition.MapManager/skin/board/mmb/write_update.inc.php` (`MAP`/`MAP_MON`)
- 로그: `.../action/log.map.skin.php`, `log.map_mon.skin.php`
- 이벤트 adm: `.../adm/map_event_*.php`
- 스키마: `.../extend/map.config.php`

---

## 11. 수정 체크리스트

1. action/`wr_log` 토큰/`#action_*`/`re_ma_id` 이름 일치하는가  
2. 카운터 키가 `character_map_explore`인가 (`character_search` 금지)  
3. 새 DB 필드 → `install.php` + `lib`/`admin`  
4. `module.php` `requires_hooks` ↔ `_hook_list.php` ↔ `bootstrap` 동기화  
5. 새 admin 페이지 → `admin/<p>.php` + `admin_menus`  
6. 보드에 write ON 후 수동 확인: 원글 MAP → 저장 → `wr_log` → 뷰. 몬스터면 댓글 MAP_MON  
7. 이미 활성 모듈이면 **재활성**으로 install 보강 반영

---

## 12. 흔한 함정

| 증상 | 원인 |
|------|------|
| 맵 수정이 복제됨 | form hidden `w` 없음 (`set_update_form` INSERT) |
| 통행이 저장 안 됨 | `map_form_update` `$cols` 에 `dpt_ma_ids` 없음 |
| 검색하면 모듈 목록으로 | GET 폼에 `m`/`p` 없음 |
| 실패인데 빈 글/JSON 깨짐 | `handled=true` 만. `bbs_action_map_fail` 필요 |
| 옵션은 있는데 처리 안 됨 | action 값이 `MAP`이 아님 / `handled` 선점 |
| 패널이 안 열림 | id가 `action_MAP`이 아님 |
| 횟수가 안 깎임 | setting이 다른 rs_name을 씀. 또는 write_after 미발사 |
| 통행 거부 | `dpt_ma_ids` 미설정 / `ma_use=0` |
| 몬스터 공략 옵션 없음 | write OFF / `wr_mon_state≠S` / 위치 불일치 / `'comment'` 게이트 잔재 / 타임라인 `list_item` 미조회 |
| 이벤트 안 뜸 | `me_use`·확률구간·`me_replay_cnt` |
| 이벤트 제목에 `\'` | POST 재escape. unslash 1회 후 escape |
| 오버뷰 없음 | `map_image` 비어 있음 / `list_top` 미호출 스킨 |
| 컬럼 없음 SQL 에러 | install 미재실행 |

---

## 13. 의도적으로 안 하는 것

- uninstall 시 테이블/글 컬럼 DROP (데이터 보존)
- 유저 HP 차감 (원본 플러그인 배포본과 동일 — 필요 시 `process_mon`에만 추가)
- 별도 `module_map_monster` / `module_map_log` 테이블 (과거 깨진 CREATE는 제거됨)
