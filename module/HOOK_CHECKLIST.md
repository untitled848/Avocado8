# 모듈 / 훅 추가 체크리스트 (For AI Agents)

> 운영자 안내: [docs/module/AUTHORING.md](../docs/module/AUTHORING.md). 모듈 제작 정본: [docs/module/AGENTS.md](../docs/module/AGENTS.md) (훅 카탈로그 §4, 위젯 §5, BBS/타임라인 §6, 아이템 §7). 타임라인 AJAX: [AGENTS.timeline.md](AGENTS.timeline.md).
>
> **본보기 모듈**은 기본 제공 넷이다: `bbs_action_search`, `bbs_action_combine`, `bbs_dice`, `bbs_item_use`. 새 액션 폴더 골격은 [`bbs_action_sample`](bbs_action_sample/).
>
> 아래 절에 적힌 다른 패키지명은 **그 함정이 발견된 곳**이다. 선택 설치이며, 작성 본보기가 아니다.
>
> 새 훅을 발사하거나 새 모듈을 추가할 때 반드시 검토해야 할 항목.
> 이 체크리스트가 있는 이유: 모듈 점검 페이지가 "훅 없음" 으로 잘못 판정한 사례가 발생했음. 원인은 훅 발사처가 스캔 대상 디렉토리에 없었기 때문 (`extend/`, `lib/` 등이 누락).
>
> 게시판 액션을 `module/` 로 옮기거나 로그/CSS를 붙일 때는 **§F**. mmb 원글 본문·타임라인 피드 댓글 extra scope·**작성자 스냅샷은 저장 시점**·**§F-6 tl-item 클래스는 wr_script 와 분리**·**§F-7 wr_num 만으로 모듈 로그를 묶지 말 것**·**§F-8 게시판 fail 후 빈 글 롤백** 에서 반복된 함정이다.
>
> mmb `wr_type` + 타임라인 `action` + 자체 프론트(마이페이지 링크)를 한 모듈에 붙일 때는 **§G**. 타임라인 컴포저 `input[type=file]` 은 **§G-9**. 표시명 키 폼은 **§G-10**. **보드 URL 의 게시판 vs 타임라인**은 **§G-11**.
>
> 루트 덮어쓰기 패키지(`extend/`+`adm/9xx_*`+전용 스킨)를 `module/` 로 옮기거나, 게시판 여분필드에 설정을 넣던 기능을 모듈화할 때는 **§H**. `k_battle` / `k_realtime` / `bbs_k_mmbraid` 작업에서 나온 함정이다.
>
> 자체 `{prefix}xxx` 테이블을 만들고 모듈을 **처음 켜거나**, 그런 모듈을 복제해 모듈화할 때는 **§I**. `pers_blog` 최초 인스톨에서 PHP 8 `SHOW COLUMNS` Fatal 이 났다. 훅 모듈이 아니어도 동일하다. write 컬럼을 타임라인에도 넣을 때는 **§I-5** (`mdl_db_add_write_column` 이 별칭 없이 게시판만 ALTER). 이미 켜진 모듈의 스키마 보강은 **§I-6** (목록 **재설치**). CREATE 에 넣은 UNIQUE/INDEX 를 ALTER ADD 로 반복하면 **§I-7**. **재설치·업로드가 bootstrap 을 먼저 부르거나 `lib.php` 가 매 요청 DDL 하면 §I-8**.
>
> `window.open` 팝업, 마이페이지 탭 위젯, AJAX 조각 HTML, 함수 안 `include` 로 동적 CSS/JS 를 심는 모듈은 **§J**. `k_battle` 강화 팝업 목록 미출력·마이페이지 단계 CSS 미적용에서 나왔고, URL 전역·`$g5` 스코프·필터 `$html` 덮어쓰기는 다른 모듈화에도 그대로 적용된다. 타임라인 모달 `innerHTML` 은 **§J-6**. 프론트 팝업이 `adm/admin.lib.php` 를 통째 include 하면 **§J-7**. 모듈 `pages/` 의 `alert()` 는 **§J-8**. 테마 위젯은 **§J-9** (`get_widget` + 리터럴 `mdl_widget`). 모듈 프론트 색은 **§J-10** (`/css/root.php` `--d-*`, 모듈 `:root` 독립 팔레트 금지). 공유 스킨 ID 를 suffix 하면 CSS/JS 는 **§J-11**.
>
> 옛 플러그인 PHP 를 그대로 옮길 때는 **§K**. `k_pokemon` 배틀 팝업이 `<?` 단축태그로 Parse error 가 났고, 관리자 셀렉트는 빈 칸으로만 보였다. Laragon PHP 8 기본은 `short_open_tag=Off`.
>
> 게시판 스킨이 `wr_link1` / `wr_10` 을 링크·여분이 아닌 **업무 상태 JSON** 으로 쓰면 **§L**. `calculate` 에서 목록 수정·댓글 수정 시 신청/정산완료가 지워진 사례.
>
> 자유 입력 식을 HTML 로그로 쓰거나, 비공개 플래그를 호출부에만 두거나, `bbs_customer_sql` 로 빈 글 가드를 뚫거나, 테마 폴백 파일에 테마 클래스를 하드코딩하거나, 관리자 자식 CRUD 가 PK 만으로 UPDATE/DELETE 하면 **§M**. 이미지 keep 이 필드 `dir` 과 다를 때 초상이 지워지면 **§M-6**. `k_timeline_orpg` 검증에서 나왔다.
>
> 모듈 AJAX·팝업 액션이 목록 UI 의 필터만 믿고 `in_id`/`ch_id`/`rm_id`/`lo_id`/`reuse_it_id` 로 쓰면 **§N**. `k_battle` 강화·커스텀·제작, `k_realtime` 실시간 레이드 행동, `k_pokemon` 배틀 수락·기술 선택·리본/볼캡 장착에서 타 캐릭터 장비/재료/턴이 소모될 수 있었다. 아이템추가/가구커스텀 재지급도 같다.
>
> 관리자 분기를 `=== 'super'` 만 보면 **§O**. `$is_admin` 이 boolean `true` 이거나 `group`/`board` 이면 관리자인데 수정·대행·업로드가 막힌다.
>
> 관리자 설정이 만든 동적 컬럼명을 그대로 SQL `SET` 에 넣거나, 다건을 하나의 `@sql_query(..., false)` 로 묶으면 **§P**. 회원가입 추가 입력 `level` → `mb_level` 권한 상승, 컬럼 하나 누락에 전체 무음 실패.
>
> 관리자에서 같은 화면을 사이드바 대신 교차 탭으로 묶을 때는 **§B-6**. 외부 버전 배너는 **§B-7**.
>
> **유지:** 모듈 작업 중 다른 모듈에도 같은 점검이 필요한 함정을 찾으면 코드만 고치지 말고 이 문서에 절/행을 추가한다.

---

## A. 새 훅을 코어에 추가할 때

### A-1. 훅을 어디서 발사하는가? (스캔 경로 검사)

`module/_registry.php` `mdl_hook_scan_paths()` 의 `$core_dirs` / `$core_files` / `$board_skins` / `$skin_slots_extra` 에 **포함된 위치** 인지 확인.

현재 스캔되는 위치:
- 코어 디렉토리: `bbs/`, `shop/`, `inventory/`, `mypage/`, `member/`, `world/`, `adm/`, `adm_module/`, `ajax/`, `extend/`, `lib/`, `css/`, `couple/`, `notice/`, `timeline/`
- 코어 단일 파일: `head.sub.php`, `tail.sub.php`, `head.php`, `tail.php`, `main.php`, `index.php`, `common.php`, `enter.php`, `module/_widget.php`
- 게시판 스킨: `skin/board/{mmb,basic,qna,calculate}` (테마 오버라이드 자동)
- 추가 스킨: `skin/outlogin/basic`, `skin/world/basic`, `skin/timeline/basic` (테마 오버라이드 자동)
- 활성 프리셋의 file/dir/skin 슬롯 매핑된 테마 경로
- 활성 모듈의 `module/<name>/`

**누락된 위치라면**: `mdl_hook_scan_paths()` 의 `$core_dirs` 등에 추가. 훅 레지스트리 `_hr_scan_dirs()` 는 이 함수를 재사용한다 (추가로 `module/` 전체).

### A-2. 발사 표현식이 정규식에 매칭되는가?

스캐너 정규식: `/mdl_(do_action|apply_filters)\s*\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/`

- ✅ `mdl_do_action('hook_name', $arg)` → 매칭
- ✅ `mdl_apply_filters('hook_name', $val)` → 매칭
- ❌ `$hook = 'hook_name'; mdl_do_action($hook, ...)` → 매칭 불가 (변수)
- ❌ `mdl_do_action("hook_{$type}", ...)` → 매칭 불가 (보간)
- ❌ `mdl_apply_filters('mdl_widget_'.$module, ...)` / `"widget_{$name}"` → 위젯도 동일. 고정 훅 `mdl_widget` + `get_widget($module, $widget)` 만. [J-9](#j-9-테마-위젯은-get_widget--고정-훅-mdl_widget)

훅 이름은 **리터럴 문자열** 로 작성할 것.

### A-3. 훅 이름 네이밍 컨벤션

| prefix | 분류 | 검증 위치 |
|--------|------|---------|
| `bbs_*` / `mmb_*` / `item_*` | 게시판 스킨 훅 | `board_form.php` 게시판별 검증 |
| 그 외 (예: `cdn_*`, `time_*`, `notice_*`) | 일반 훅 | 모듈 점검 페이지 |

`mdl_is_bbs_hook()` 가 prefix 만 보고 판정하므로, **게시판 종속 훅이 아니면 위 3개 prefix 사용 금지**.

### A-4. 발사 위치 안전 가드

훅 시스템이 로드되기 전(`common.php` 이전)에 호출될 가능성이 있다면:

```php
if (function_exists('mdl_do_action')) mdl_do_action('hook_name', $arg);
```

`extend/00_*.lib.php` 처럼 common.php 에서 자동 로드되는 라이브러리 함수 안이라면 가드 불필요(이미 모듈 시스템 로드 후).

모듈 `bootstrap.php` 는 `extend/` **보다 먼저** 로드된다. include 시점에 extend 헬퍼를 호출하면 함수가 없다. 훅 콜백 안(요청 후반)에서는 extend 가 이미 있다.

---

## B. 새 모듈을 추가할 때

### B-1. 필수 파일

```
module/<name>/
├── module.php          # 메타 (필수)
├── bootstrap.php       # 훅 등록 (선택, 활성화 시 자동 호출)
├── _hook_list.php      # 사용 훅 선언 (강력 권장 — 점검 정확도)
├── install.php         # 활성화 시 1회 (테이블 생성 등)
├── uninstall.php       # 비활성화 시 1회 (보통 보존, DROP 주석)
├── README.md
└── admin/              # (선택) 어드민 페이지
```

### B-2. `module.php` 메타 필수 키

- [ ] `admin_menus` 가 있는가? 기본 3모듈(탐색·조합·주사위)이 아니면 그룹 키는 **`'auto'`** + suffix 3자리. `'002'`/`15x` 등 하드코드 복붙 금지. [AGENTS.md §2](../docs/module/AGENTS.md)

```php
return array(
    'name'        => 'cdn_cloudflare',  // 폴더명과 동일
    'title'       => 'Cloudflare R2 CDN',
    'version'     => '1.0.0',
    'description' => '...',
    'board_scope' => false,             // true 면 게시판별 ON/OFF 가능
    'admin_menus' => array(             // (선택) adm_module 메뉴 — 기본은 auto
        'auto' => array(
            array('000', '클라우드플레어', G5_MDL_ADMIN_URL.'/module.php?m=cdn_cloudflare&p=cdn_form'),
            array('100', 'CDN 설정', G5_MDL_ADMIN_URL.'/module.php?m=cdn_cloudflare&p=cdn_form'),
        ),
    ),
    // 신규: 단독 활성화 그룹 (B-5 참고)
    // 'module_type' => 'cdn',
);
```

- 탐색·조합·주사위만 그룹 `'002'` 고정 6자리. 그 외는 `'auto'` + suffix. 복붙으로 `002`/`15x` 등을 훔치지 말 것.

### B-3. `_hook_list.php` — 점검 정확도의 핵심

```php
return array(
    'uses'  => array('hook1', 'hook2'),  // 모듈이 거는 훅
    'fires' => array('hook3'),           // 모듈이 발사하는 훅 (선택)
);
```

**주의**:
- `uses` 에 선언한 훅은 코어 또는 다른 활성 모듈에 **반드시 발사처가 있어야 함**. 없으면 점검 페이지가 빨간 X로 표시.
- 계획 단계 훅 (아직 코어에서 미발사) 은 주석 처리.
- `mdl_module_hooks_used()` 의 폴백: `_hook_list.php` 가 없으면 `bootstrap.php` 정규식 스캔 → **변수 보간이나 동적 훅명 누락 위험**. 명시 권장.
- `uses_bbs` 키는 `uses` 에 합친 뒤 `mdl_is_bbs_hook()` prefix 로 다시 나눈다. `uses_bbs` 만 넣고 `uses` 를 비우면 예전 점검은 훅을 못 봤다.

### B-4. 활성화 / 비활성화 안전성

- `install.php`: 멱등 (CREATE TABLE IF NOT EXISTS, INSERT IGNORE). **`bootstrap.php` 를 require 하지 말 것** — 훅 이중 등록 + 테이블 없는 상태에서 `SHOW COLUMNS` Fatal (§I). 테이블 별칭은 `lib.php` 또는 install 인라인. CREATE 다음에야 `mdl_db_add_column`.
- 목록 **재설치**와 업로드 스키마도 bootstrap 을 부르지 않는다 (`mdl_run_install`). §I-8.
- `uninstall.php`: 데이터 보존이 기본. DROP TABLE 은 주석 처리.
- 모듈 OFF 상태에서도 **DB에 남은 데이터/토큰이 페이지를 깨지 않아야 함**. 코어가 항상 폴백 경로 보장.
- 다른 모듈을 복제해 시작할 때: 기증 모듈의 `admin/map_*`, `'name'` 불일치를 그대로 두지 말 것. 폴더명 = `module.php` `'name'` = 게이트 인자. **`admin_menus` 는 `'auto'`** (기본 3모듈의 `002` 제외). 하드코드 그룹을 복붙하지 말 것.

### B-5. (신규) `module_type` — 단독 활성화 그룹

같은 카테고리(예: CDN 어댑터)는 동시에 여러 개 활성화되면 충돌하므로, `module.php` 에 `module_type` 을 지정하면 같은 type 내에서는 1개만 활성 가능하도록 강제됨.

```php
'module_type' => 'cdn',  // 같은 type 모듈 활성화 시 기존 type 모듈 자동 OFF
```

활성화 로직(`mdl_activate()`): 새 모듈이 type 을 가지면, 같은 type 의 기존 활성 모듈을 먼저 비활성화.

규칙:
- type 미지정(=`null`) 모듈은 제약 없음 (기존 동작)
- 같은 type 끼리만 상호 배타
- 어드민 UI에 "이 모듈은 `<type>` 그룹입니다. 활성화하면 같은 그룹의 다른 모듈이 꺼집니다" 안내

### B-6. admin_menus `'tab'` — 사이드바 없이 페이지 탭만

같은 화면을 레이드 종류 × 캐릭터/몬스터처럼 **교차 탭**으로 묶을 때, 탭 URL 을 `admin_menus` 에서 지우면 탭이 안 생긴다. 탭 맵은 `mdl_admin_menus_for()` 로 전개된 메뉴를 읽는다.

- 사이드바 허브 항목은 그대로 둔다 (예: 스킬 등록, 보유 스킬). 허브 URL 의 `unit_type=ch` / `p=ch_skill` 이 **캐릭터 탭** 이다.
- 탭 전용 항목은 6번째 값 `'tab'`: `array('410', '몬스터 스킬', $url, '', 0, 'tab')` (`auto` suffix; 전개 후 6자리).
- `mdl_register_admin_menus()` / `mdl_resolve_admin_sub_menu()` 는 `'tab'` 을 건너뛴다(전개된 메뉴 기준). 사이드바 하이라이트는 허브 코드에 남는다.
- 탭이 허브와 **다른 `p=`** 이면, 예전에는 매칭이 없어 `$sub_menu` 가 `001100` 이 되며 서브메뉴가 풀렸다. `mdl_admin_tab_hub_page()` 가 같은 그룹의 직전 허브 `p` 로 묶는다. 레퍼런스: `k_timeline_orpg` (룰/시나리오/세션 탭). 가능하면 허브와 같은 `p` + 쿼리로 나누는 쪽이 더 단순하다 (`k_battle` `unit_type`).
- **몬스터가 없는 레이드**(MMB)는 `unit_type=mo` / `mo_skill` 메뉴만 빼면 된다. 캐릭터 허브는 유지.
- 유닛 탭은 **1개여도** 그린다 (`k_battle_admin_cross_tabs`). 2개일 때만 그리면 캐릭터만 있는 레이드에서 탭 행이 사라져 “캐릭터 스킬”인지 안 보인다.
- 레퍼런스: `k_battle` + `k_realtime` + `bbs_k_mmbraid` 스킬/보유스킬.

### B-7. `version_banner` — 외부 이미지로 버전 공지

덮어쓰기 패키지가 설치 페이지에 외부 `version.png` 를 두던 것과 같다. 모듈은 `module.php` 에 URL 만 두고, **모듈 목록 카드 안**에 그린다.

```php
'version_banner'    => 'https://example.com/version.png',
'version_info_url'  => 'https://example.com/release-notes',
```

- http/https 만 허용. 같은 이미지 URL 은 한 장만 표시.
- 레퍼런스: `k_battle`.

---

## C. 자주 발생한 실수 (회고)

| 사례 | 원인 | 예방 |
|------|------|------|
| cdn 모듈 점검 → "훅 없음" | 훅 발사가 `extend/00_cdn.lib.php` 인데 `extend/` 가 스캔 경로에 없었음 | A-1 체크. 신규 위치는 `mdl_hook_scan_paths()` 에 추가 |
| `cdn_download_url` 점검 X | `_hook_list.php` 에는 선언했으나 코어에서 미발사 | A-2 체크. 미발사 훅은 주석 처리 |
| 게시판 무관 훅인데 `bbs_` prefix | `mdl_is_bbs_hook()` 가 게시판 훅으로 오분류 → 모듈 페이지 점검에서 누락 | A-3 prefix 컨벤션 준수 |
| bootstrap 정규식 스캔만 의존 | 동적 훅명 누락 | `_hook_list.php` 명시 |
| 어드민 폼 `@unlink` 만 호출 → R2 잔여 | 직접 `@unlink` 사용으로 `cdn_after_delete` 미발사 | `cdn_unlink()` 헬퍼 사용 (자동 키 추출 + 액션 발사) |
| 어드민 업로드 후 R2 미반영 | `upload_file/move_uploaded_file` 직후 `cdn_after_upload` 호출 누락 | 어드민 폼의 모든 업로드 성공 직후 `if (function_exists('cdn_after_upload')) cdn_after_upload($absolute_path);` 추가 |
| `/img/` 이미지가 CDN 으로 안감 | `G5_IMG_URL` 은 컴파일타임 상수, 동적 라우팅 불가 | 템플릿에서 `cdn_img('btn_top.png')` 사용. R2 키는 `_img/` prefix |
| 글 액션인데 mmb 목록에 로그 HTML 없음 | `bbs_view_row_extra` 가 `scope===comment` 만 통과. mmb 는 원글에 `scope=post` | F-1. 컬럼만 보고 렌더. 스코프는 뷰 종류 분기에만 |
| 액션 스코프 끄니 로그만 스타일 없이 남음 | `bbs_enqueue_assets` 가 write/comment ON 에 묶임. 또는 `list.php` 만 CSS | F-2. 로그 CSS는 모듈 ON 이면 항상. mmb 는 `board.php` |
| mmb 원글 `wr_content` 가 안 보임 | 이미지 옆 텍스트는 댓글 칸. 원글을 unshift 하지 않음 | F-3. 원글 extra 는 한 번만, 그 row 의 scope 는 `post` |
| 타임라인 댓글(프로필/멘션/피드)에 액션 로그 없음 | extra `scope` 를 `content_type===comment` 만 봄. 피드 `item.skin.php` 는 댓글도 post | F-4. `wr_is_comment` 로 scope. 1대1은 컬럼으로 시작/공격 분기 |
| 타임라인 보드에서 모듈 체크박스 disabled | `requires_hooks` 에 mmb 전용 훅(`wr_type_*`, `mmb_*`)이 있음 | G-1. requires 는 **모든 대상 스킨의 교집합** |
| mmb 보드에서 모듈 체크박스 disabled | `requires_hooks` 에 타임라인 전용 (`composer_icons`/`composer_panels`) | G-1. 타임라인 전용은 uses 만 |
| 글쓰기에만 액션 있고 댓글/답글엔 없음 | write 훅만 등록. 타임라인 인라인 답글은 comment 훅 | G-5 |
| 게시판에 댓글 ON 체크가 따로 생김 | 한 액션의 단계인데 `scopes` 에 `comment` | G-5. 단계는 write 만. **기본 제공은 글/댓글 따로라 comment 가 맞다** |
| 한 액션의 원글·댓글 단계인데 comment 체크가 따로 있음 | 단계를 write/comment 스코프로 나눔 | G-5. write ON 이면 원글+댓글 단계 |
| write만 켰는데 댓글 액션이 안 됨 | `mdl_board_enabled(..., 'comment')` / `mdl_board_enabled_for_w`. scopes 에 comment 없음 | G-5. 단계형이면 댓글 훅도 `'write'`. 기본 제공은 comment 스코프가 있으므로 `mdl_board_enabled_for_w` |
| mmb 댓글에만 레이드가 뜨거나, 타임라인 답글에 없음 | comment 훅을 빼거나 `scopes` comment 로 나눔 | G-5. 훅은 두고 타임라인 보드만 통과. `bbs_k_mmbraid`, `bbs_k_pokemon`, `bbs_action_k_dom` |
| mmb 댓글에 선물이 뜨거나, 원글 아이템이 타임라인 패널처럼 보임 | 같은 훅 HTML 을 mmb/타임라인에 그대로 씀 | G-5. 훅은 공통, 렌더에서 보드·원글/댓글 분기. `bbs_item_use` |
| mmb 원글에서 아이템/선물 대상이 비활성 | `$('#fwrite select')` 가 모든 select 의 같은 value 를 disable | G-5. 중복 금지는 `.make-imtem` 만. `bbs_action_combine` |
| 타임라인에서 별 탭이 아이템(가방) 안에 섞임 | `bbs_write_form_after_action` 은 `#tl-item-area`. mmb 의 Action 아래 `dl` 과 슬롯이 다름 | G-5. mmb 는 after_action, 타임라인 별 탭은 `composer_icons`/`panels`. `k_quest`, `k_timeline_orpg` |
| 댓글 저장 후 모듈 행이 안 연결됨 | `write_after` 가 `w=c` 를 스킵 | G-5. `comment_after` |
| 타임라인 답글에 원글 상태 옵션이 없음 | `list_item` 이 `wr_id` 만. 원글 업무 컬럼 없음 | G-5. ctx `wr_id` 로 원글 재조회 |
| 마이페이지에 자체 페이지가 탭으로 끼워짐 | `mypage_character_viewer_tabs` 의 `{id,html}` 기본이 토글 패널 | G-6. 페이지를 떠나면 `{label,href}`. 전역 네비는 `mypage_submenu` |
| 공개 멤버 뷰어에 모듈이 기타 탭에 몰림 | 옛 `member_viewer_extra` HTML 덤프 | G-6. 섹션은 `member_viewer_info`/`bag`/`relation`. 새 탭은 `member_viewer_tabs` |
| 프로필 미니룸이 탭에 들어가 옷장처럼 안 열림 | 팝업 메뉴를 탭 훅에 넣음. 옷장 옆 훅이 없었음 | G-6. `member_viewer_tools` / `mypage_character_viewer_tools`. 레퍼런스 `k_spacemaker` |
| 옷장 옆 도구가 팝업 대신 일반 링크 | `onclick="..."` 안 `json_encode` 큰따옴표가 속성을 끊음 | G-6. `character_viewer_tool_html` |
| MMB에서 액션 로그가 안 저장됨 | `bbs_custom_action` 의 `customer_sql` 은 `handled=true` 일 때만 코어가 반영. 부가 POST(`action_pokemon` 등)는 handled 를 켜면 다른 액션을 가로챔 | G-8. handled 없이 만든 SQL 은 `bbs_customer_sql` 로 덧붙임. 레퍼런스: `bbs_k_pokemon` |
| 에디터/HTML 저장마다 `\` 가 늘어남 | 그누보드 POST `addslashes` 후 다시 escape | G-3 |
| 활성화 시 훅이 두 번 돔 | `install.php` 가 `bootstrap.php` 를 require | B-4 / G-2 / I-3 |
| 모듈 켜자마자 `Table '…' doesn't exist` Fatal (`SHOW COLUMNS` / `mdl_db_add_column`) | 테이블 생성 전에 ALTER. PHP 8 은 `$error=false`·`@` 무시 | I |
| 모듈은 켜져 있는데 테이블이 없고 관리자 목록부터 Fatal | `state.json` 에만 active (install 미실행) + `schema_ensure` 가 없는 테이블에 ADD COLUMN | I-2 · I-3 |
| 이미 켠 모듈에 컬럼을 추가했는데 DB에 없음 | `install.php` 는 첫 활성만. 끄고 켜기는 스키마를 다시 안 돌림 | I-6 모듈 목록 재설치 |
| 재설치가 `Duplicate key name` Fatal | CREATE 에 있는 UNIQUE/INDEX 를 ALTER ADD 로 반복. PHP 8 은 `$error=false` 무시 | I-7. `bbs_action_k_dom` |
| 타임라인 원글 액션은 됐는데 로그 extra 가 빔 | 모듈 로그를 `wr_num` 만으로 조회. INSERT 전 `wr_num=0` | F-7. `log_id` 를 wr_log 에. `bbs_action_k_dom` |
| 게시판에서 액션 fail 인데 빈 글이 등록됨 | mmb `write_update` 는 INSERT **후** `bbs_custom_action` | F-8. fail 시 wr_log 없는 신규 글 롤백. `bbs_action_k_dom`, `bbs_action_map` |
| 실패했는데 글이 올라가거나 타임라인이 “서버 응답 형식” | `handled=true` 만 주고 JSON/alert 없음 | G-8. `k_*_fail` / `bbs_action_map_fail`. |
| 타임라인 답글마다 같은 액션 로그 | 답글이 부모 `wr_num` 을 공유 | F-7 |
| 타임라인에서 참가하면 없는 게시판으로 감 | `return_url` / 로그 링크가 `board.php` 고정 | G-11. `bbs_action_k_dom` |
| 관리자 목록/검색이 빈 화면 | GET 폼이 `m`/`p` 를 안 넘김 | H-1 |
| 맵/폼 수정이 같은 행이 아니라 새 행 | hidden `w` 없음. `set_update_form` 이 INSERT | H-1. `bbs_action_map` map_form |
| `include` 가 빈 화면/경고 | URL 상수를 파일 경로처럼 include | H-1 |
| 게시판 지웠는데 설정만 남거나, 목록에서 안 보임 | INNER JOIN board / 레거시 이관이 없는 보드까지 INSERT | H-2 · H-3 |
| 설정 행 지웠는데 다음 방문에 다시 생김 | `bo_*` 마커·옛 테이블 잔행이 migrate 를 재실행 | H-3 |
| 이 모듈만 OFF 하려다 다른 보드 모듈까지 꺼짐 | `mdl_set_board_modules()` 에 부분 맵 | H-4 |
| 전용 스킨/여분필드 없이는 설정이 안 붙음 | `g5_board` ALTER 또는 스킨 덮어쓰기 | H-2 · H-5 |
| 팝업에서 슬롯/버튼을 눌러도 목록이 안 나옴 | JS 가 `k_xxx_url` 을 쓰는데 `head.sub` 팝업에 변수가 없음. `add_javascript` 는 `html_end` 경로만 | J-1 |
| 테마에 위젯을 심었는데 점검이 훅 없음, 또는 모듈 OFF 인데 fatal | 뷰 include / `k_*_widget()` 직접 / `widget_{$mod}` 보간 | J-9. `get_widget('모듈','위젯')` + 리터럴 `mdl_widget` |
| 멤버 목록 위젯이 모든 행에 같은 캐릭터 | 루프 `$ch` 생략 → `$GLOBALS['ch']`/`$character` | J-9. 목록은 `$ctx['ch']` 를 넘긴다 |
| 코어 뷰어에는 위젯이 있고 테마 뷰어에는 없음 | 테마 `viewer.skin.php` 가 `$viewer_info` 등을 안 그림 | J-9. 테마 스킨에서 섹션 변수·`$viewer_extra_tabs`·`$viewer_tools` 를 출력 |
| 위젯/마이페이지가 사이트 테마와 다른 고정 색 | 모듈 CSS `:root` 에 `--point-color` 등 독립 팔레트, 또는 크롬 hex | J-10. `var(--d-color_point)` 등 root 토큰. `k_pokemon`, `bbs_k_pokemon`, `k_quest` |
| 글쓰기 액션 버튼이 리스트처럼만 보임 | 폼이 둘이라 `id="action-select_w"` 인데 CSS 는 `#action-select` | J-11. 클래스(`.action-select`)로 스타일. `bbs_k_mmbraid` |
| 관리자 미리보기는 되고 마이페이지 탭만 스타일/SQL 이 빔 | 위젯 **함수 안** include. `$g5` 가 함수 스코프에 없음 | J-2 |
| 팝업에 사이트 푸터·TOP·깨진 레이아웃 | 스킨이 본문 `tail.php` 를 한 번 더 include | J-3 |
| 글쓰기 컴포저 토글은 되고 모달 댓글 컴포저는 안 됨 | 모달 `innerHTML` 이 조각 안 `<script>` 를 실행하지 않음 | J-6 |
| 관리자에서 PL만 고쳤는데 초상이 사라짐 | img keep 이 필드 `dir` 이 아니면 빈 값 SET. 저널 초상은 `journal/` | M-6 |
| 강화/커스텀이 남의 장비·재료를 소모함 | AJAX 가 목록 필터만 믿고 `in_id` 만 UPDATE/DELETE | N |
| 배틀 수락·턴이 남의 포켓몬으로 진행됨 | `search_update` 가 `lo_id`/`wh_id` 만 봄 | 참가자 `ch_id`·`ph_id`/`wh_id` 재검증, 컬럼명 화이트리스트. N |
| 리본/볼캡이 남의 인벤에서 장착됨 | 포켓몬 소유만 보고 `in_id` 미검증 | `inven.ch_id`+`it_type`. N |
| 관리자 등록 폼이 PHP 8 TypeError | `count($_POST['chk'])` 가 없는 키를 먼저 평가 | `$chk` 배열로 정규화한 뒤 count. `k_pokemon` admin `*_update.php` |
| 관리자인데 수정·대행·업로드가 막힘 | `=== 'super'` 만 봄. `$is_admin` 이 `true`/`group`/`board` | `if ($is_admin)`. O |
| 가입자가 자기 레벨/포인트를 지정함 | 추가 입력 코드가 코어 컬럼(`mb_level`)과 겹침 | P |
| 추가 입력값이 오류 없이 전부 사라짐 | 동적 컬럼 다건을 `@sql_query(..., false)` 한 방에 묶음 | P |

---

## D. 검증 명령

새 훅/모듈 추가 후:
1. 어드민 → 모듈 관리 → 해당 모듈 [점검] 클릭
2. 모든 uses 훅이 ✅ 표시되는지 확인
3. 활성/비활성 토글 후 페이지가 정상 동작하는지 확인 (특히 OFF 시 폴백)

---

## E. 관련 파일

- 모듈 시스템: `module/_registry.php`, `module/_hooks.php`, `module/_widget.php`, `module/_loader.php`, `module/_skin.php`
- 운영자 안내: `docs/module/AUTHORING.md` (켜기, 위젯, 보드/타임라인, 아이템 사용, 기본 제공 모듈)
- 모듈 제작: `docs/module/AGENTS.md` (§4 훅 카탈로그, §5 위젯, §6 BBS/타임라인, §7 아이템, §11 검증, §12 기본 제공 레퍼런스)
- 타임라인 액션: `module/AGENTS.timeline.md` (§6 어셋, §8 댓글 POST)
- 기본 제공: `module/bbs_action_search/`, `bbs_action_combine/`, `bbs_dice/`, `bbs_item_use/`
- 액션 골격(학습): `module/bbs_action_sample/` (`inc/assets.inc.php` 로그/작성 CSS 분리)

함정 절(§F–O)의 패키지명은 발견 위치다. 동작 본보기는 기본 제공 넷. 폴더 복제는 샘플.

---

## F. 게시판 액션 모듈 — 로그 / CSS / mmb (이식 시 반복)

스테이징 패키지나 구 `log.*.skin.php` 를 `module/` 로 옮길 때. 로그 렌더는 `bbs_action_search` `views/log_S.php`, 패널·JS 는 `bbs_action_combine`. 로그/작성 어셋 분리는 [`bbs_action_sample/inc/assets.inc.php`](bbs_action_sample/inc/assets.inc.php) · §F-2.

### F-1. `bbs_view_row_extra` — 로그 HTML 을 스코프로 막지 말 것

mmb 목록(`skin/board/mmb/list.log.skin.php`)은 **원글**에 `bbs_render_row_extra(..., scope => 'post')` 를 호출한다. 액션이 글 폼만 있어도 로그는 여기로 온다.

- [ ] `if ($ctx['scope'] !== 'comment') return $html;` 같은 가드가 있는가? → **삭제**. 구 패키지가 댓글 스킨에만 로그를 두던 잔재다.
- [ ] `mdl_board_enabled(..., 'write'|'comment')` 로 extra 를 막고 있는가? → **삭제**. 액션 스코프를 꺼도 **이미 저장된** `wr_log` 는 보여야 한다.
- [ ] 스코프는 **어느 뷰를 고를지**에만 쓰는가? 렌더 자체를 skip 하지 않음. `k_1on1` 은 `wr_battlestate` 면 시작, 아니면 `wr_battleturn` 이면 공격 합. **scope=comment 가 아니면 공격 로그를 버리는 가드는 금지** — 타임라인 피드 `item.skin.php` 는 댓글도 `content_type=post` 로 extra 를 넘긴다.

```php
// ❌ 글 액션 로그가 mmb 목록에서 사라짐
if (!isset($ctx['scope']) || $ctx['scope'] !== 'comment') return $html;
if (!mdl_board_enabled($ctx['bo_table'], 'bbs_xxx', 'write')) return $html;

// ✅ 자기 컬럼/토큰이면 글·댓글 모두. 스코프 OFF 여도 과거 로그 유지
if (empty($row['wr_log'])) return $html;
$data_log = explode('||', $row['wr_log']);
if (!isset($data_log[0]) || $data_log[0] !== 'X') return $html;
```

`$ctx['scope']` 값: `'post'` | `'comment'`. `'post'` 를 write 로 보면 된다. 시작/공격처럼 뷰가 둘이면 **컬럼**으로 고른다 (`k_1on1`).

### F-2. 로그 CSS 는 액션 스코프·페이지 파일명에 묶지 말 것

`bbs_view_row_extra` 는 스코프와 무관하게 HTML 을 남기는데, `bbs_enqueue_assets` 만 write/comment ON 에 묶으면 **스타일만 빠진 로그**가 된다.

- [ ] 로그 CSS(`*.log.css`, 로그 전용 클래스)는 모듈이 **전역 활성**(bootstrap 로드)이면 **페이지당 1회** `add_stylesheet`. 보드 ON·write/comment 게이트 없음.
- [ ] 작성 폼·위젯·액션 JS 만 `mdl_board_enabled(..., 'write') || comment`.
- [ ] `SCRIPT_NAME === 'list.php'` / `'view.php'` 로 CSS 를 제한하지 않는가? mmb 목록은 **`board.php`**. 타임라인은 `_head.php` 를 안 탄다 — `AGENTS.timeline.md` §6.

```php
// ❌ 스코프 끄면 로그 CSS 도 증발. mmb 에서는 list.php 가 아니라서 CSS 자체가 안 붙을 수 있음
if (!mdl_board_enabled($bo_table, 'bbs_xxx', 'write')
    && !mdl_board_enabled($bo_table, 'bbs_xxx', 'comment')) return;
if (basename($_SERVER['SCRIPT_NAME']) !== 'list.php') return;
add_stylesheet('.../xxx.log.css');

// ✅ 로그 CSS 항상. 폼 JS 만 스코프
add_stylesheet('.../xxx.log.css');
$mod_on = mdl_board_enabled($bo_table, 'bbs_xxx', 'write')
       || mdl_board_enabled($bo_table, 'bbs_xxx', 'comment');
if ($mod_on) add_javascript('.../xxx.js');
```

모듈 **폴더 비활성**이면 bootstrap 이 안 로드되므로 로그 HTML/CSS 둘 다 없다. “보드 스코프 OFF”와 “모듈 전역 OFF”를 구분할 것.

### F-3. mmb 원글 본문은 댓글 칸이다

mmb 는 이미지 왼쪽, **텍스트는 댓글 컬럼**. 원글 `wr_content` 를 “안 쓰는 필드”로 가정하지 말 것.

스킨 쪽 (`skin/board/mmb/`):

- [ ] `view_comment.php` 가 본문 있는 원글을 댓글 배열 앞에 넣는가. `wr_is_comment = 1` 만 SELECT 하면 원글 텍스트는 안 나온다.
- [ ] 그 원글 row 의 extra `scope` 는 **`'post'`** (`wr_is_comment === 1` 일 때만 `'comment'`). 안 그러면 1대1처럼 시작/공격 뷰가 뒤집힌다.
- [ ] 원글을 댓글처럼 보여 줄 때 **extra 를 두 번** 부르지 않는가. `list.log.skin.php` 에서 이미 원글 extra 를 그렸으면, 댓글 칸으로 옮긴 뒤에는 목록 쪽 extra 를 건너뛴다.
- [ ] 원글 블록에 댓글 수정/삭제/답글 버튼을 켜지 않는가 (`is_edit` / `is_del` / `is_reply` = false).

모듈 쪽: F-1 과 같이 extra 가 `scope=post` 원글에도 그려져야, 글 액션 로그가 본문 옆이 아니라 **그 텍스트 블록**에 붙는다.

### F-4. 타임라인 피드 댓글 extra `scope`

상세 뷰 댓글은 `item.comment.skin.php` 가 `content_type=comment` 로 extra `scope=comment` 를 넘긴다. **목록·프로필·멘션** 은 `ajax_load.php` → `item.skin.php` 가 댓글도 `content_type=post` 로 그린다.

- [ ] extra `scope` 를 `content_type === 'comment'` 만으로 정하는가? → 피드 댓글은 항상 `post` 가 된다. `wr_is_comment === 1` 이면 `'comment'`.
- [ ] 1대1처럼 시작/공격 뷰가 다른 모듈이 `scope !== 'comment'` 이면 HTML 을 버리는가? → F-1. 컬럼으로 뷰를 고른다.
- 레퍼런스: `skin/timeline/basic/item.content.skin.php`, `module/bbs_action_k_1on1/inc/log_render.inc.php`

### F-5. 타임라인 작성자 스냅샷은 저장 시점에 고정한다

`wr_name` / `wr_thumb` 은 작성 시점 스냅샷이다. 세션 PL 표시명·초상으로 바꾸려면 **INSERT 의 `bbs_customer_sql` 에서 `pl_name`/`pl_thumb` 을 SET** 한다 (`orpg_player_identity`). 저널 폼을 다시 읽지 않는다. 피드는 `get_timeline_row_name` / `get_timeline_row_thumb` 이 그 컬럼을 그대로 읽는다. 표시 훅으로 현재 `pl_info` 를 덮으면 이후 저널 수정이 옛 글까지 바꿔 아카이브가 깨진다.

- [ ] 세션 중 글이 사이트 캐릭터명/썸으로 남는가? → onair INSERT 에서 `wr_name`/`wr_thumb` 을 `pl_name`/`pl_thumb` 으로 SET. 수정(`w=u`/`cu`) 에서는 다시 쓰지 않는다.
- [ ] 저널을 고쳤더니 지난 글 이름/초상이 같이 바뀌는가? → 표시 경로에서 라이브 `pl_info` 를 넣지 않는다.
- [ ] GM 이 NPC 로 썼는데 사이트 캐릭터명/썸이 남는가? → onair INSERT 에서 선택한 NPC 의 `pl_name`/`pl_thumb` 을 SET. `orpg_voice`.
- 레퍼런스: `module/k_timeline_orpg/` (`orpg_player_identity`, `orpg_composer_voice`, `orpg_append_se_customer_sql`)

### F-6. `tl-item-script` 클래스와 공지(`wr_script`)를 섞지 말 것

타임라인 공지(스크립트 작성)는 `wr_script=1` 이다. 그러면 스킨이 아바타·하단 메뉴(답글 등)·`bbs_view_row_extra` 로그를 숨기고 HTML 본문만 그린다.

ORPG 나레이션처럼 **같은 카드 스타일만** 필요하고 답글·TRPG 로그는 남겨야 하면 `wr_script` 를 쓰지 않는다. 모듈 write 컬럼(`se_script`) + `timeline_item_class` 필터로 `tl-item-script` 만 붙인다. `timeline_item_html_class()` 가 스킨에서 발사한다. 상단 이름/아이디/시간은 `timeline_item_compact_header` 로만 접고, 수정·삭제는 남긴다.

- [ ] 모듈 스크립트 글에서 답글/좋아요가 사라지는가? → `wr_script=1` 로 저장했는가. 공지가 아니면 그 컬럼을 건드리지 않는다.
- [ ] 클래스만 붙였는데 extra 로그가 비는가? → `ajax_load` 가 `timeline_row_is_script` 이면 `wr_log` 를 비운다. `wr_script` 와 분리.
- [ ] 스크립트 글 상단에 이름/아이디/시간이 남는가? → `timeline_item_compact_header` 필터. 헤더만 접고 extra/하단은 `wr_script` 로만 가린다.
- 레퍼런스: `timeline/_functions.php` `timeline_item_html_class` / `timeline_item_compact_header`, `module/k_timeline_orpg/` (`se_script`, `orpg_row_se_script`)

### F-7. 모듈 로그를 `wr_num` 만으로 묶지 말 것

타임라인 `ajax_post.php` 는 INSERT **전**에 `bbs_custom_action` 을 쏘며 `$wr_num = 0` 이다. 답글(`ajax_comment.php`)은 **부모** `wr_num` 을 쓴다. mmb 는 INSERT 뒤에 훅이 돌아 원글마다 `wr_num` 이 다르다.

모듈 테이블에 `wr_num` 만 넣고 extra 가 `abs(row.wr_num)` 으로 조회하면: (1) 타임라인 **원글** 로그가 비고 (2) 같은 스레드 답글이 **한 로그를 공유**한다.

- [ ] `wr_log` 에 모듈 PK(`log_id`) 를 넣는가. extra 는 그 id 로 조회. 옛 행만 `wr_num` 폴백.
- [ ] 원글은 `write_after` 에서 실제 `wr_num` 을 보정하는가. 답글은 `comment_after` 의 `$comment_id` 행. 레퍼런스: `bbs_action_k_1on1` `k_1on1_sync_start_turns`, `bbs_action_k_dom`.
- [ ] 참가·포탈 「글 보기」 링크가 `board.php` 로 고정돼 있는가? 타임라인이면 `timeline/view.php?tl_table=&wr_id=`. [G-11](#g-11-참가포탈-url-을-boardphp-로-고정하지-말-것).

### F-8. 게시판 `write_update` 의 `bbs_custom_action` 은 INSERT 이후다

타임라인 `ajax_post.php` 는 훅이 INSERT **전**이라 `k_*_fail` / JSON 이 글을 안 남긴다. mmb `write_update.controller.php` 는 코어가 이미 INSERT 한 뒤에 훅이 돈다. `alert()` 로 막아도 **wr_log 없는 원글**이 남는다.

- [ ] 게시판 fail 이 빈 글을 남기는가? 방금 넣은 `wr_id` 이고 `wr_log` 가 비었으면 DELETE + `board_new` + 글수 + 쓰기 포인트 롤백. 레퍼런스: `bbs_action_k_dom` `k_dom_rollback_failed_board_write`, `bbs_action_map` `bbs_action_map_fail`.
- [ ] 댓글 훅도 INSERT 이후인가? `write_comment_update` 의 신규 댓글(`w=c`)이고 `wr_log` 가 비었으면 댓글 DELETE + 댓글수 롤백. 레퍼런스: `bbs_action_map_rollback_failed_board_comment`.
- [ ] 이미 `wr_log` 가 있는 글·댓글·수정(`w=u`)을 지우지 않는가.
- 타임라인 JSON fail 경로에서는 이 롤백을 타지 않는다 (`write_update.php` 가 아님).

---

## G. mmb wr_type + 타임라인 action + 자체 프론트 (이식 시 반복)

`pers_blog` 처럼 **자체 테이블/페이지**를 mmb `wr_type` 과 타임라인 `action` 에 동시에 붙일 때. 레퍼런스: `module/pers_blog/`. 액션만 있는 모듈은 §F·[AGENTS.timeline.md](AGENTS.timeline.md) 가 우선.

### G-1. `requires_hooks` 는 대상 스킨의 **교집합**

`adm/board_form.php` 는 그 보드 스킨이 `requires_hooks` **전부**를 리터럴로 쏘는지 본다. 하나라도 없으면 체크박스가 disabled.

- [ ] mmb 전용(`bbs_write_form_wr_type_options/panels`, `mmb_write_update_by_type`, `mmb_list_log_img`, **`bbs_write_form_before_action`**)을 `requires_hooks` 에 넣었는가? → **빼라**. 타임라인 PHP 에 `before_action` 이 없다. 등록은 bootstrap + `_hook_list` `uses`. 레퍼런스: `bbs_k_pokemon`.
- [ ] 타임라인 전용(`bbs_write_form_composer_icons` / `bbs_write_form_composer_panels` 및 comment 짝)을 `requires_hooks` 에 넣었는가? → mmb 보드에서 disabled. 타임라인만 대상이면 넣어도 된다.
- [ ] 타임라인 전용만 requires 에 넣었는가? → mmb 보드에서 disabled.
- [ ] `requires_hooks` = 글·댓글·목록에 **양쪽 스킨이 공통으로** 쏘는 훅만 (`bbs_write_form_action_*`, `bbs_custom_action`, `bbs_view_row_extra`, `write_after` / `comment_after` 등).
- [ ] `_hook_list.php` `uses` 에는 mmb 전용을 **넣어도 된다**. 점검 페이지용. requires 와 uses 를 동일 배열로 맞추지 말 것.

```php
// ❌ 타임라인 board_form 에서 모듈 칸이 비활성
'requires_hooks' => array(
    'bbs_write_form_wr_type_options', // mmb 만
    'bbs_write_form_action_options',
),

// ✅ 공통만 requires. mmb 전용은 bootstrap + _hook_list uses
'requires_hooks' => array(
    'bbs_write_form_action_options',
    'bbs_write_form_action_panels',
    'bbs_comment_form_action_options',
    'bbs_comment_form_extra',
    'bbs_custom_action',
    'bbs_view_row_extra',
    'write_after',
    'comment_after',
),
```

보드 설정 체크박스가 disabled 면 “훅 누락”만 보지 말고 **requires 가 너무 넓은지**도 본다.

### G-2. `install.php` 에서 `bootstrap.php` 금지

활성화 요청은 install 다음에 bootstrap 을 이미 로드한다. install 이 bootstrap 을 require 하면 `mdl_add_*` 가 한 요청에 두 번 걸린다.

자체 테이블이 있으면 **더 위험**하다. bootstrap → `lib.php` 의 `schema_ensure()` / `mdl_db_add_column()` 이 CREATE 보다 먼저 돌아 `SHOW COLUMNS` Fatal. 상세 점검은 **§I**.

- [ ] install 은 스키마·시드만. 별칭은 `lib.php` 또는 install 인라인.
- [ ] 다른 모듈 ZIP/복붙의 `require bootstrap` 을 그대로 두지 않았는가.

### G-3. 그누보드 POST 는 이미 `addslashes`

에디터 HTML·JSON·긴 본문을 모듈 테이블에 넣을 때.

- [ ] 저장: `stripslashes` **1회** 후 `sql_real_escape_string` (또는 동등 escape) 만.
- [ ] `addslashes` / `htmlspecialchars` 를 저장 경로에 또 넣지 않았는가 → 수정할 때마다 `\` 누적. JSON(`pl_info` 등)은 `json_decode` 가 실패해 빈 배열로 보인다.
- [ ] 폼 로드는 원문을 에디터에 그대로. 저장용 escape 를 역으로 넣지 말 것.
- [ ] 관리자 목록에서 이름만 고쳐도 JSON 컬럼을 SET 하면, POST 재escape 시 저널 값이 날아간다. JSON 은 unslash 후 유효할 때만 UPDATE.

### G-4. 타임라인 write 에 호스트 컬럼을 만들지 말 것

mmb 는 `wr_type` / `wr_url` 이 있다. 타임라인 write 스키마에는 보통 없다. 없으면 **액션 로그**로 붙인다.

- [ ] `wr_url` / `wr_type` 을 `get_timeline_write_schema()` 나 타임라인 설치에 추가했는가? → **되돌리라**. [AGENTS.timeline.md §1](AGENTS.timeline.md).
- [ ] 타임라인 연결은 `wr_log = 'TYPE||{id}||…'` + 모듈 테이블의 `wr_id` / `bo_table`(= `$tl_table`).
- [ ] 한 리소스를 mmb 글 **그리고** 타임라인 글에 동시에 묶지 않는가. 연결 가능 목록은 “아직 호스트가 없는 행”만.

### G-5. 댓글·인라인 답글은 write 훅이 아니다

글쓰기만 달면 타임라인 **답글**과 mmb 댓글에 옵션이 안 나온다.

| | 원글 | 댓글 / 인라인 답글 |
|--|------|-------------------|
| 옵션 | `bbs_write_form_action_options` | `bbs_comment_form_action_options` |
| 패널 | `bbs_write_form_action_panels` | `bbs_comment_form_extra` |
| 컴포저 아이콘 | `bbs_write_form_composer_icons` (타임라인만) | `bbs_comment_form_composer_icons` (타임라인만) |
| 컴포저 토글 패널 | `bbs_write_form_composer_panels` (타임라인만) | `bbs_comment_form_composer_panels` (타임라인만) |
| 저장 연결 | `write_after` (`w=c` 스킵하는 구현이 많음) | `comment_after($bo_table, $comment_id, $wr_id)` |
| 패널 id | `#action_TYPE` | `#action_{scope}_TYPE` (`js/action_panel.js` suffix) |

기본 제공(`bbs_action_search`, `bbs_action_combine`, `bbs_dice`, `bbs_item_use`)은 **write 와 comment 를 따로** 둔다. 글 훅 `'write'`, 댓글 훅 `'comment'`. 아래 write-only 항목은 **한 액션의 원글+댓글 단계**를 같은 스위치로 묶을 때만.

- [ ] 댓글 옵션/패널 훅을 걸었는가. mmb 댓글과 타임라인 인라인 답글은 `action_ui=select` — 라디오만 넣으면 select 안에 안 보인다.
- [ ] 패널 `id` 가 글쓰기와 같은 `action_TYPE` 으로 댓글에 박혀 있는가? → 토글이 안 맞는다. `scope_id` / `list_item.wr_id` 를 suffix.
- [ ] 모듈 행 연결을 `write_after` 에만 넣었는가? `w=c` 면 return 하는 핸들러가 많다 → `comment_after` 로 댓글 wr_id 를 기록.
- [ ] `bbs_custom_action` 에서 `w=c` 를 거절하는가? 타임라인 인라인 답글은 write 와 같이 `action=TYPE` POST 를 쓴다. (mmb 댓글 select도 `name=action`.)
- [ ] 댓글 UI만 쓰려고 `scopes` 에 `comment` 를 추가했는가? 어드민에 체크박스가 하나 더 생긴다. **한 액션의 단계**라 글 스위치로 댓글까지 열어도 되면 write 만. 기본 제공처럼 글/댓글을 따로 끄려면 comment 를 둔다.
- [ ] 캐스팅/릴처럼 **한 액션의 단계**를 write/comment 체크박스로 나눴는가? → write 만. 원글/댓글 분기는 훅·`$w` 로.
- [ ] `scopes` 가 write 뿐인데 `mdl_board_enabled(..., 'comment')` 또는 `mdl_board_enabled_for_w` 를 쓰는가? comment 가 메타에 없으면 `board_default` 로 떨어져 댓글이 꺼진다. 댓글 훅도 `'write'`.
- [ ] mmb 댓글에는 없고 타임라인 인라인 답글에만 있어야 하는가? comment 훅은 유지하되 타임라인 보드인지 검사. `scopes` 에 comment 를 다시 넣지 말 것. 기본 제공은 mmb 댓글에도 같은 액션을 둔다.
- [ ] mmb 원글·댓글·타임라인의 아이템/액션 UI가 달라야 하는가? 훅은 공통, 렌더에서 보드 종류와 원글/댓글로 분기. mmb 원글 `bbs_write_form_after_action` 에 중첩 `dl>dt` 를 넣으면 `#bo_w dt` 가 absolute 70px 이라 깨진다. Action 과 같이 바깥 `dt`(라벨) / `dd`(컨트롤). 레퍼런스: `bbs_item_use`.
- [ ] 타임라인에서 아이템과 **다른 탭**인가? `bbs_write_form_after_action` 은 `#tl-item-area`(가방). 아이콘은 `bbs_write_form_composer_icons` (`data-tl-panel`) + `composer_panels`. mmb 는 after_action 에 바깥 `dl`. composer 훅은 `requires_hooks` 에 넣지 말 것(G-1). 레퍼런스: `k_timeline_orpg`, `k_quest`.
- [ ] mmb 원글에 아이템·대상 select 를 넣었는가? `write.tpl.php` 의 `$('#fwrite select')` 는 같은 value 를 전부 disable 한다. 중복 금지는 `.make-imtem` 만. 레퍼런스: `bbs_action_combine`.
- [ ] 타임라인 인라인 답글 `list_item` 이 `wr_id` 만인가? 원글 업무 컬럼은 `$ctx['wr_id']` 로 재조회.

### G-6. 캐릭터 뷰어 — 탭 토글 vs 옷장 옆 팝업 vs 하이퍼링크 vs 서브메뉴

`mypage_character_viewer_tabs` / `member_viewer_tabs` 기본은 `{id, label, html}` → 버튼이 패널을 show/hide. 자체 `index.php?p=list` 같은 **별도 페이지**를 html 에 넣지 말 것.

공개 멤버 뷰어는 고정 「기타」 탭이 없다. 기본정보·가방·관계에 붙일 HTML 은 `member_viewer_info` / `member_viewer_bag` / `member_viewer_relation`. 새 탭만 `member_viewer_tabs`.

**옷장·경험치처럼 새 창**이면 탭이 아니다. `member_viewer_tools` (공개 프로필 하단, 옷장 옆) / `mypage_character_viewer_tools` (마이페이지 옷장 버튼 옆). `{label, href}`. 코어가 `window.open` (옷장과 같은 크기 기본). `target` / `features` 선택. 같은 창이면 `popup => false`. 발사: [`member/viewer.php`](../member/viewer.php), [`mypage/character/viewer.php`](../mypage/character/viewer.php). 레퍼런스: `k_spacemaker`.

- [ ] 모듈 프론트로 이동해야 하는가? → `{label, href}` 만. `id`/`html` 생략. 코어가 `<a class="ui-btn">`.
- [ ] 위젯을 뷰어 안에 그리는가? → `{id, html}`.
- [ ] 옷장처럼 **새 창**인가? → 탭/`{label,href}` 가 아니라 `member_viewer_tools` / `mypage_character_viewer_tools`. URL 에 `s=sub`.
- [ ] `href` 항목을 탭 박스 루프에 넣지 않는가. 코어가 `href` 면 패널을 안 만든다 ([`mypage/character/viewer.php`](../mypage/character/viewer.php), [`member/viewer.php`](../member/viewer.php)).
- [ ] 도구 HTML 을 직접 짜면 `onclick="window.open(..., "name", ...)"` 처럼 **속성 안 큰따옴표**를 쓰지 않는가. 속성이 끊겨 팝업이 안 되고 일반 링크로 간다. 코어 [`character_viewer_tool_html`](../extend/character.lib.php) 은 옷장처럼 작은따옴표 JS 문자열.
- [ ] 마이페이지 **전역 서브메뉴**(계정/캐릭터/로그와 같은 줄)인가? → 뷰어 탭이 아니라 [`mypage/_head.php`](../mypage/_head.php) 의 `mypage_submenu` (`<li>` HTML). 관리자 메뉴 앞에 붙는다.
- [ ] 공개 뷰어에서 기존 탭에 붙이는가, 탭을 늘리는가, 옷장 옆인가? 기본정보·가방·관계는 `member_viewer_info` / `bag` / `relation`. 새 탭만 `member_viewer_tabs`. 팝업 메뉴는 `member_viewer_tools`.

모듈이 **전역 활성**(bootstrap 로드)이면 링크를 보여 주면 된다. `board_scope` 의 특정 보드 write ON 과는 별개. “모듈 목록에서 켜짐” ≠ “그 타임라인 write 체크”.

### G-7. 작업 후 확인

- [ ] 타임라인 `board_form` 과 mmb `board_form` 둘 다 모듈 체크박스가 **활성**(disabled 아님)
- [ ] 해당 보드/타임라인에서 write ON 후에야 글쓰기 옵션이 보이는지 (`board_default=off` 이면 전역 활성만으로는 안 뜸)
- [ ] 원글·댓글(인라인 답글) 모두 옵션·패널·저장·목록 렌더
- [ ] 마이페이지 캐릭터 뷰어에 탭이 아닌 링크가 붙고, 그 캐릭터 목록으로 가는지
- [ ] 옷장처럼 새 창이어야 하면 `member_viewer_tools` / `mypage_character_viewer_tools` 인지 (탭에 위젯을 넣지 않았는지)
- [ ] 에디터/HTML 필드를 한 번 저장·수정해도 백슬래시가 안 늘어나는지
- [ ] 컴포저 `input[type=file]` 이 타임라인 글/댓글 POST 에 실리는지 (`appendTimelineFormFields`, `images[]` 제외)

### G-8. `bbs_custom_action` 의 `customer_sql` 은 `handled` 일 때만 반영된다

`bbs/_module_write_update.inc.php` 는 필터 결과의 `handled` 가 참일 때만 `customer_sql` / `log` 를 누적한다.

MMB 에서 드롭다운 `action`(S 등)과 **별도 필드**(`action_pokemon`)를 같이 쓰는 모듈은 `handled=true` 를 주면 다른 액션 모듈을 가로챈다. `handled` 를 끄면 코어가 SQL 을 버린다.

- [ ] 주 액션(`action=PKM_*` / `FISHING` …)인가? → `handled=true` + `customer_sql`.
- [ ] 부가 필드만 쓰는가(MMB 탭, 알 부화 hidden)? → `handled` 를 켜지 말고 `bbs_customer_sql` 필터로 같은 SQL 을 덧붙인다.
- [ ] 검증 실패인데 `handled=true` 만 주고 return 하는가? 타임라인은 JSON 이 아니어서 “서버 응답 형식이…” 가 나고, mmb 는 빈 글이 남을 수 있다. `k_*_fail` / `bbs_action_map_fail` 로 중단.
- [ ] 타임라인 AJAX 는 INSERT **전**에 훅이 돌아 `wr_id=0` 이다. 조우 로그처럼 부가 테이블에 `wr_id` 를 넣는 모듈은 `write_after` / `comment_after` 로 보정하고, 폼 POST 의 `wr_id` 를 우선한다. 레퍼런스: `bbs_k_pokemon`. 위치·인벤·횟수도 INSERT 전에 반영하지 말고 after 훅에서. 레퍼런스: `bbs_action_map`.

### G-9. 타임라인 FormData 는 `images[]` 가 아닌 file 도 실어야 한다

`timeline.common.js` 의 `appendTimelineFormFields()` 는 submitPost / 인라인 답글이 폼을 직접 submit 하지 않아서 `name` 있는 필드를 훑는다. 예전에는 `type=file` 을 전부 건너뛰어 컴포저 이미지(`orpg_cutin_img` 등)가 `$_FILES` 에 없었다. `select[multiple]` 은 `el.value` 가 첫 선택만이라 공개 대상 등이 빠진다. 선택된 option 을 각각 `append` 하고 PHP 이름은 `foo[]`.

- [ ] 모듈 전용 `input[type=file]` 이 `#tl-form` / 답글 form 안에 있고, `name` 이 `images[]`/`image` 가 아닌가.
- [ ] 프리셋 선택 시 자유 파일 입력을 `disabled` 하는가. disabled 는 FormData에 안 실린다.
- [ ] 글·댓글 둘 다 `appendTimelineFormFields` 경로인지. `new FormData(form)` 경로는 파일이 자동 포함된다.
- [ ] `<select multiple>` 를 쓰면 선택된 값을 모두 붙이는가. `name` 은 `foo[]`. 토글 해제가 필요하면 체크박스 `name[]` 가 낫다. 레퍼런스: `orpg_handout_pl[]`.
- [ ] 타임라인 스킨이 `.tl-composer-form input[type=file]` 을 `display:none` 하는가. 모듈 파일 입력은 테마 CSS로 다시 보이게. 레퍼런스: `k_timeline_orpg` `.orpg-composer-panel input[type=file]:not([hidden])`.
- [ ] 복구 CSS에 `display:… !important` 를 쓰면 `[hidden]` 이 진다. 타입에 따라 숨기는 파일(능력치 `image`)은 `:not([hidden])` 으로 복구하고, `[hidden]` 은 `display:none !important`.

### G-10. 폼 필드를 표시명으로 키잉하면 같은 이름이 덮인다

능력치 HTML 을 `ra_name` 같은 표시명 키로 모으면, 구분이 다른 같은 이름(기본정보 `이름` / 파트너 `이름`)이 하나로 합쳐진다. 스킨이 그 입력을 두 칸에 그리면 `name="ra_value[id]"` 가 중복되어 저장 시 **마지막 값**이 이긴다.

- [ ] 같은 표시명이 있을 수 있으면 키는 id 또는 `cate+name`. 조회는 헬퍼로 구분한다. 레퍼런스: `orpg_jform_row()`.
- [ ] 한 폼에 같은 `name="field[id]"` 가 두 번 있지 않은가.

### G-11. 참가/포탈 URL 을 `board.php` 로 고정하지 말 것

`list_top` 참가·자체 팝업의 「로그 보기」가 `G5_BBS_URL.'/board.php?bo_table='.$bo_table` 이면, 타임라인 판(`tl_table`)에서 참가 후 없는 게시판으로 튕긴다. `return_url` 을 REQUEST 그대로 쓰면 오픈 리다이렉트다.

- [ ] `k_dom_is_timeline()` (또는 동등) 으로 분기하는가. 타임라인은 `/timeline/?tl_table=` · 글은 `view.php?tl_table=&wr_id=`.
- [ ] 참가 처리가 GET `action=in` 인가? CSRF 로 참가/취소된다 → POST.
- [ ] `return_url` 은 같은 사이트 경로만. 레퍼런스: `bbs_action_k_dom` `k_dom_return_url` / `k_dom_safe_return_url`.

---

## H. 덮어쓰기 패키지 / 여분필드 → 모듈 (이식 시 반복)

루트 덮어쓰기(`extend/`, `adm/9xx_*`, 전용 보드 스킨, `inventory/` 패치)나 `bo_1`/`bo_1_subj`/`bo_11` 같은 **게시판 여분필드**에 설정을 넣던 기능을 `module/` 로 옮길 때. 레퍼런스: `k_battle`, `k_realtime`, `bbs_k_mmbraid`.

### H-1. 경로·라우터 — URL 과 PATH 를 섞지 말 것

`adm_module/module.php?m=<name>&p=<page>` 가 `module/<name>/admin/<page>.php` 를 include 한다. 구 패키지는 `G5_ADMIN_URL.'/980_xxx.php'` 또는 `G5_URL.'/k_xxx/'` 를 전제한다.

- [ ] `K_<NAME>_URL` / `G5_MDL_URL` 을 `include`/`require` 에 쓰지 않는가. **include 는 PATH** (`K_<NAME>_PATH`, `__DIR__`). URL 은 `<script src>`·`<a href>`·`window.open` 만.
- [ ] 관리 화면 링크가 `G5_MDL_ADMIN_URL.'/module.php?m=<name>&p=...'` 인가. 헬퍼를 두면 `k_xxx_admin_url($page, $qstr)` 한곳에서 조립.
- [ ] 검색·탭·페이징 **GET 폼**에 hidden `m`, `p` 가 있는가. 빠지면 라우터를 벗어나 빈 페이지.
- [ ] `set_insert_form('form')` 수정 화면이면 hidden `w=u` (및 PK). 없으면 `set_update_form` 이 `$w===''` 를 신규로 보고 MAX+1 INSERT. 레퍼런스: `bbs_action_map` `map_form.php`.
- [ ] 이미 PHP 문자열 안에 `<?php echo k_xxx_admin_url(...) ?>` 를 또 넣지 않았는가 (중첩 PHP).
- [ ] 프론트 `index.php`/`list.php` 가 스킨을 **파일 경로**로 include 하는가.

### H-2. 게시판 설정은 여분필드가 아니라 모듈 테이블

`g5_board` 의 `bo_*` / `bo_*_subj` 는 사이트마다 이미 쓰일 수 있다. 모듈이 ALTER 하거나 런타임에 `SHOW COLUMNS` 후 자동 추가하면 다른 기능과 충돌한다.

- [ ] `g5_board` ALTER / 여분필드 쓰기를 **하지 않는가**.
- [ ] 보드당 설정은 `{prefix}<mod>_setting` (PK `bo_table`) 같은 **전용 테이블**. 생성은 그 모듈 `install.php` 만.
- [ ] 프론트·ajax·관리 목록·**글/댓글 저장**에서 `CREATE`/`ALTER`/`SHOW COLUMNS` 후 자동 추가를 하지 않는가. 컬럼 추가는 **install 만**. `lib.php` 의 `ensure_column` 은 install.php 에서만 호출. 모듈 write 컬럼(`wr_*`)은 `mdl_db_add_write_column`. 레퍼런스: `k_battle` 가 예전에 저장 시 write 테이블을 ALTER 했음.
- [ ] 관리자 목록의 기준은 **설정 테이블**인가. `FROM setting s LEFT JOIN g5_board b` — INNER JOIN 이면 게시판이 지워진 행이 목록에서 사라진다.
- [ ] 설정 행이 **언제 INSERT** 되는지 명시했는가. 모듈 ON, 목록 열기, 글 보기만으로 넣지 말 것. 명시 저장(초기설정) 또는 레거시 1회 이관만.

### H-3. 이관·삭제·고아 행

구 패키지는 `bo_1_subj='mmbraid'` 또는 별도 `k_battle_board` 같은 잔여 테이블을 남긴다. 이관이 없는 보드까지 INSERT 하면 고아가 생긴다. 삭제 때 마커를 안 지우면 다음 `prepare()` 가 행을 되살린다.

- [ ] install/migrate 가 **실존 `g5_board` 행만** 이관하는가. 게시판 없는 옛 키는 skip.
- [ ] 고아 설정(보드 없음)을 목록에 보이고, 일괄 삭제할 수 있는가. 삭제 시 그 `ra_id`/`bo_table` 전투·로그 데이터도 지우는가.
- [ ] 타임라인 `tl_table` 도 존재로 보는가. 보드만 JOIN 하면 타임라인 키가 고아로 지워진다.
- [ ] 설정 테이블(`utf8mb4_unicode_ci`)과 `avo_timeline`(`utf8mb4_0900_ai_ci` 등) JOIN 에 `COLLATE utf8mb4_unicode_ci` 를 넣었는가. 없으면 `Illegal mix of collations` 로 목록이 0건이 된다. 레퍼런스: `bbs_k_mmbraid` `k_mmbraid_sql_collate_unicode()`.
- [ ] 실존 보드의 설정 삭제 = 설정 행 삭제 + 관련 데이터 삭제 + **이 모듈만** 보드 OFF (H-4) + **레거시 마커 정리** (`bo_1_subj` 등, 옛 플러그인 테이블 행).
- [ ] 초기화와 삭제를 구분하는가. 초기화 = 기본값으로 UPDATE + 런타임 데이터 삭제, **행은 유지**. 삭제 = 행 제거.

### H-4. 보드 모듈 OFF 는 부분 맵 금지

`mdl_set_board_modules($bo_table, $enabled_map)` 는 **모든** `board_scope` 모듈을 순회하고, 맵에 없는 키는 OFF 로 저장한다. 이 모듈 키만 넘기면 주사위·검색 등 다른 보드 모듈이 같이 꺼진다.

- [ ] 한 모듈만 끌 때 `mdl_set_board_modules()` 부분 맵을 **쓰지 않는가**.
- [ ] `mdl_load_board_state()` → 해당 보드의 `<name>` / `<name>@write` / `<name>@comment` 만 수정 → `mdl_save_board_state()`.
- [ ] `board_default='off'` 인 모듈은 키를 지우거나 `false` 를 넣으면 OFF. `true` 를 남겨 두면 설정 행이 없어도 프론트가 살아 있다.

### H-5. 코어·스킨을 덮지 말 것 / 의존 순서

구 패키지는 `inventory/`, `mypage/`, `adm/admin.menu9xx.php`, 전용 `skin/board/k_mmbraid` 를 덮어썼다. 모듈은 훅으로만 끼어든다.

- [ ] 코어 `inventory/`·`mypage/`·`adm/` 를 덮어쓰지 않는가. 아이템 타입은 `item_type_options`, 사용은 `inventory_use_dispatch`, 스탯 줄은 `status_sub_text`.
- [ ] 전용 보드 스킨이 **필수 아닌가**. mmb/basic 에 붙이려면 이미 있는 훅만 (`list_top`, `bbs_write_form_before_action`/`options`, `bbs_comment_form_*`, `bbs_view_row_extra`, `write_after`/`comment_after`). 글 `action` 셀렉트가 아니면 `bbs_write_form_action_*` / `bbs_custom_action` 을 요구하지 말 것 — 넣으면 `requires_hooks` 때문에 그 훅 없는 스킨에서 체크박스가 disabled.
- [ ] 선행 코어가 있으면 `'depends' => array('k_xxx')`. 종속 `bootstrap.php` 는 선행 테이블/함수 없으면 **return** (경고로 사이트 전체를 깨지 않음). 활성화는 코어 먼저.
- [ ] 무거운 보드 기능은 `'board_scope'=>true`, `'board_default'=>'off'`, 필요 시 `scopes` 를 write/comment 로 나눈다. 전역 활성만으로 모든 게시판에 UI가 뜨면 안 된다.

### H-6. 작업 후 확인

- [ ] `php -l` 전 파일.
- [ ] 관리자 목록 검색·페이지·탭을 눌러도 URL 에 `m=` `p=` 가 남는가.
- [ ] 게시판이 없는 설정 행이 목록에 보이고 삭제되는가.
- [ ] 실존 보드 설정 삭제 후 그 보드만 이 모듈 OFF, 다른 보드 모듈은 그대로인가. 다음 페이지 로드에 설정 행이 되살아나지 않는가.
- [ ] 의존 모듈만 켠 상태(코어 OFF)에서 프론트가 fatal 이 아닌가.
- [ ] 스테이징 미러(`battle_plugin/` 등)를 고쳐도 런타임에 반영되지 않음을 알고, 정본 `module/<name>/` 만 수정하는가.

---

## I. 자체 테이블 최초 설치 (PHP 8 mysqli)

모듈이 `{prefix}blog` 처럼 **자기 테이블**을 CREATE 할 때. 게시판 write 컬럼만 ALTER 하는 모듈은 `mdl_db_add_write_column` 이면 충분하고, 이 절의 CREATE 순서는 해당 없음. 레퍼런스: `pers_blog` (`Table 'ktester.avo_blog' doesn't exist` at `mdl_db_column_exists` → 모듈 목록 Fatal). `k_spacemaker` / `dollmaker` 복붙 install 의 `require bootstrap` 도 같은 구멍.

### I-1. PHP 8 에서는 `$error=false` 와 `@` 가 SQL 예외를 막지 않는다

PHP 8.1+ 기본 `MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT`. `sql_query($sql, false)` · `@mysqli_query` 도 **없는 테이블**에 `SHOW COLUMNS` / `ALTER TABLE` 하면 `mysqli_sql_exception` → Uncaught Fatal. 관리자 `module_list.php` 로드(`_loader` → bootstrap → lib)에서 터지면 켜기/재설치 UI 자체에 못 들어간다.

- [ ] 컬럼 존재/추가는 직접 `SHOW COLUMNS` / `ALTER` 하지 말고 `mdl_db_column_exists()` / `mdl_db_add_column()` (`module/_db.php`). 헬퍼는 테이블 없으면 false, 예외를 삼킨다.
- [ ] 테이블 존재는 `mdl_db_table_exists()`. `SHOW TABLES LIKE` 의 `_` 는 와일드카드라 헬퍼가 escape 한다.
- [ ] 예외를 직접 삼켜야 하면 `mdl_db_sql_query()` / `mdl_db_sql_fetch()`. 코어 `sql_query(..., false)` 만으로 PHP 8 안전을 가정하지 말 것.

### I-2. CREATE 가 ALTER·`schema_ensure` 보다 먼저

- [ ] `install.php` 순서: `$g5['*_table']` 별칭 → `CREATE TABLE IF NOT EXISTS` → 그다음 `mdl_db_add_column` (구버전 컬럼 보정).
- [ ] `mdl_activate()` 는 bootstrap 을 **부르지 않는다**. 별칭을 bootstrap 에만 두면 CREATE 가 빈 이름으로 skip. 별칭은 `lib.php` 상단 또는 install 인라인. 정본: `bbs_helas_battle/install.php`.
- [ ] `lib.php` 끝(또는 bootstrap include 시) `*_schema_ensure()` 로 매 요청 ADD COLUMN 하는가? **하지 말 것.** 스키마는 install/재설치만. 요청 경로는 테이블 없으면 skip. [I-8](#i-8-재설치업로드는-bootstrap-을-부르지-않고-요청-경로에서-ddl-하지-말-것)
- [ ] 어드민 **재설치**는 `mdl_run_install()` 만. bootstrap 을 install 보다 먼저 include 하지 않는다.

### I-3. `install.php` 가 `bootstrap.php` 를 require 하지 말 것

B-4 / G-2 (훅 이중 등록) 와 동일 금지. 자체 테이블 모듈에서는 bootstrap → `schema_ensure` 가 CREATE 보다 먼저 돌아 I-1 Fatal 이 난다.

- [ ] `require`/`include` `bootstrap.php` 없음. 스키마·시드는 `lib.php` 또는 install 본문만.
- [ ] 다른 모듈 ZIP/복붙(`dollmaker`, `k_spacemaker` 등) 의 `require_once __DIR__.'/bootstrap.php'` 를 그대로 두지 않았는가.
- [ ] `data/module/state.json` 에 이미 `active`+`versions` 가 있으면 **켜기만으로는** `install.php` 는 다시 안 돈다. 소스만 배포하고 DB 테이블이 없어도 `_loader` 는 bootstrap 을 연다. `schema_ensure` 가 I-2 를 지키지 않으면 목록부터 Fatal. 스키마를 다시 돌리려면 [I-6](#i-6-이미-켠-모듈은-재설치로-installphp-를-다시-돈다).

### I-4. 작업 후 확인

- [ ] **해당 모듈 테이블이 없는 DB**에서 모듈 켜기 → 목록이 Fatal 없이 뜨고 테이블이 생기는가.
- [ ] 모듈이 `state.json` 에만 켜져 있고 테이블이 없는 상태로 목록을 새로고침해도 Fatal 이 아닌가.
- [ ] 재설치(install.php 재실행)도 동일. 훅이 한 요청에 두 번 걸리지 않는가 (install 이 bootstrap 을 안 부르는가).

### I-5. `mdl_db_add_write_column` 은 타임라인 별칭이 있어야 타임라인 write 를 돈다

`mdl_db_each_write_table()` 은 `$g5['timeline_table']` / `$g5['timeline_write_prefix']` 가 있을 때만 타임라인 write 를 순회한다. 그 별칭은 `timeline/_functions.php` 에 있고, 모듈 `install.php` 는 `common.php` + `_loader` 만 탄다. 별칭이 비면 **게시판만 ALTER 되고 타임라인 컬럼이 빠진다**. 증상: 타임라인 글에 `wr_battlelog` 등이 없어 액션 SQL 이 실패하거나 로그가 안 붙는다. 레퍼런스: `bbs_action_k_1on1`.

- [ ] `mdl_db_each_write_table()` 이 별칭이 비어 있으면 `G5_TABLE_PREFIX.'timeline'` / `'timeline_'` 로 채우는가. 정본: `module/_db.php`.
- [ ] 모듈 install 만으로 타임라인 write 에 컬럼이 생겼는가. `SHOW COLUMNS FROM {prefix}timeline_{tl}` 로 확인.

### I-6. 이미 켠 모듈은 **재설치**로 `install.php` 를 다시 돈다

`mdl_activate()` 는 이미 `active` 이면 즉시 return 한다. `versions` 가 있으면 끄고 켜도 신규 install 이 아니다. 컬럼·테이블을 `install.php` 에 추가한 뒤 반영이 안 되는 증상.

- [ ] `/adm_module/module_list.php` **재설치** (`action=reinstall`). `mdl_run_install()` → `install.php` 강제 재실행, `versions` 를 메타 버전으로 갱신. 확인 문구대로 데이터 유지. **bootstrap 을 먼저 include 하지 않음.**
- [ ] install 은 `CREATE IF NOT EXISTS` / `mdl_db_add_column` 멱등인가. 재설치가 DROP·TRUNCATE 를 하지 않는가.
- [ ] **언인스톨**(데이터 제거)과 재설치를 바꿔 쓰지 않았는가.
- [ ] `install.php` 가 bootstrap 을 require 하지 않는가 (I-3). 별칭은 lib/install 인라인.
- [ ] tar.gz 업로드로 버전이 오른 켠 모듈은 `upgrade.php`(없으면 `install.php`). 로컬에서 파일만 고친 뒤에는 재설치. 업로드 스키마도 bootstrap 금지 (I-8).

### I-7. CREATE 에 넣은 UNIQUE/INDEX 를 ALTER ADD 로 반복하지 말 것

`CREATE TABLE IF NOT EXISTS` 에 이미 `UNIQUE KEY ch_dom`, `INDEX dom_id` 가 있는데 install 끝에서 `ALTER … ADD UNIQUE/INDEX` 를 또 하면, 테이블이 있는 재설치(및 첫 설치 직후)가 PHP 8 에서 `Duplicate key name` Fatal 이다. `$error=false` 는 예외를 안 막는다 (I-1). 레퍼런스: `bbs_action_k_dom` `install_k_dom_tables`.

- [ ] CREATE 정의와 중복되는 `ADD UNIQUE` / `ADD INDEX` 가 없는가. 구버전 보정은 `mdl_db_sql_query` + 인덱스 존재 확인, 또는 컬럼만 `MODIFY`.
- [ ] 재설치가 Duplicate key 없이 끝나는가.

### I-8. 재설치·업로드는 bootstrap 을 부르지 않고, 요청 경로에서 DDL 하지 말 것

재설치가 별칭을 얻으려고 bootstrap 을 먼저 include 하면 훅이 한 요청에 두 번 걸리고, `lib.php` 끝 `schema_ensure` 가 CREATE 보다 먼저 `SHOW COLUMNS` 한다 (I-1 Fatal). 레퍼런스: `mdl_run_install()`, `adm_module/module_list.php` `action=reinstall`, `mdl_upload_apply_schema()`.

매 요청 `blog_schema_ensure()` / `SHOW COLUMNS` ALTER / bootstrap 이 `install.php` 를 include 하는 패턴은 이미 켠 사이트 보강용으로 보이지만, 권한 부족·PHP 8 STRICT 에서 **모든 페이지**가 죽는다. 컬럼이 없으면 목록 **재설치** (I-6).

- [ ] 재설치·업로드 스키마가 `bootstrap.php` 를 include 하지 않는가.
- [ ] `$g5['*_table']` 별칭이 bootstrap 전용이 아닌가. install 또는 `lib.php` 상단.
- [ ] `lib.php` / bootstrap 로드 시 CREATE·ALTER·`schema_ensure` 를 호출하지 않는가. 함수는 두고 install 에서만 호출.
- [ ] 테이블이 없을 때 요청 경로는 skip (훅 미등록 또는 기능 비활성). 그 자리에서 install 을 돌리지 않는가.

---

## J. 모듈 자체 프론트 — 팝업·위젯·AJAX (이식 시 반복)

`window.open` 팝업, 마이페이지 탭 위젯, `$.ajax` 로 끼워 넣는 조각 HTML, 함수 안에서 include 하는 동적 CSS/SQL. 레퍼런스: `k_battle` (강화 팝업 목록 미출력, 마이페이지 강화단계 CSS 미적용). 훅 모듈이 아니어도 동일하다.

### J-1. JS 모듈 URL 은 `add_javascript` 훅만 믿으면 팝업에서 빠진다

`add_javascript('<script>var k_xxx_url=...</script>')` 는 `html_end()` 가 출력 버퍼의 `</head>` 앞에 끼울 때만 보인다. `bbs_enqueue_assets` / `mypage_common` 은 게시판·마이페이지에서만 돈다. `head.sub.php` 팝업(`window.open`, 모듈 `_head.sub.php`)은 그 훅을 안 탄다. 클릭 핸들러가 `k_xxx_url + '/ajax/...'` 이면 **ReferenceError** 로 AJAX 자체가 안 나가고, UI 는 빈 목록으로만 보인다.

- [ ] JS 가 `k_<mod>_url` 같은 전역을 쓰는가. **그 변수를 심는 페이지 목록**을 적었는가 (팝업 / 위젯 / 보드 / AJAX 조각).
- [ ] 팝업은 모듈 `_head.sub.php`(G5 `head.sub` **직후**)에서 `<script>if (typeof k_xxx_url === 'undefined') { var k_xxx_url = ...; }</script>` 를 **직접** 심는가. `add_javascript` + `head` 훅은 보강이지 유일한 경로가 아님.
- [ ] 마이페이지 위젯 JS 에도 `typeof === 'undefined'` 폴백이 있는가.
- [ ] AJAX 로 `.append` 하는 조각(`*_info.php` 등) 스크립트에도 같은 폴백이 있는가. 부모 페이지만 믿는가.
- [ ] `g5_url` 로 모듈 경로를 대신하지 않는가. 모듈 파일은 `G5_MDL_URL.'/<name>'` (`K_<NAME>_URL`). include 는 PATH — [H-1](#h-1-경로라우터--url-과-path-를-섞지-말-것).
- [ ] 외부 JS 는 사용 직전에 `typeof k_xxx_url === 'string' && k_xxx_url` 가드. 없으면 콘솔 에러만 나고 화면은 무반응.

### J-2. 함수·훅 클로저 안 include 는 페이지 글로벌이 없다

페이지 본문에서 `include 'css/foo_custom.php'` 하면 `$g5` 가 있다. 위젯 헬퍼·`ob_start` 콜백·**필터 클로저** 안에서 같은 파일을 include 하면 PHP 스코프가 그 함수라 `$g5['*_table']` 가 비고, `$is_admin` / `$member` / `$character` 도 없다. SQL 이 실패하거나, 관리자인데 상대 전용 폼(배틀 수락 등)이 안 나온다. **관리자 미리보기만 되고 마이페이지/탭만 안 되는** 패턴과, **훅으로 끼운 로그 스킨만 권한 분기가 깨지는** 패턴.

같은 include 스코프에서 스킨이 `$html` / `$block` 을 쓰면 필터 클로저의 같은 이름을 덮어쓴다. `return $html.'<div>'.$block.'</div>'` 이 마지막 조각 HTML + 래퍼를 한 번 더 붙여 **로그가 두 줄**(판정 CALCULATE, 미니저널 값, 핸드아웃 content 가 `.orpg-log` 밖에 한 번 더) 나온다. 레퍼런스: `k_timeline_orpg/theme/basic/action_log.php`, `mini_journal.php`.

- [ ] include 되는 PHP(동적 CSS, 슬롯 라벨, SQL 조각, 로그 스킨) 상단에 `global $g5;` (필요하면 `$is_admin`, `$member`, `$character`, 모듈 설정 배열도).
- [ ] 감싸는 함수에도 `global $g5`.
- [ ] 관리자 페이지와 프론트 위젯 **둘 다** 해당 스타일·데이터가 나오는지 확인했는가.
- [ ] `bbs_view_row_extra` 등에서 include 한 스킨의 `$is_admin` 분기가, 페이지에 직접 include 하던 원본과 같이 동작하는가. 레퍼런스: `k_pokemon/pages/battle/action_skin.php`.
- [ ] 필터 클로저에서 include 하는 파일이 `$html` / `$block` / `$row` / `$ctx` 를 재할당하지 않는가. 조립용 변수는 `$orpg_log_html` / `$orpg_mj_html` / `$orpg_media` 처럼 훅·래퍼와 다른 이름.

### J-3. 팝업 스킨이 본문 `tail.php` 를 부르지 말 것

셸이 `_head.sub.php` + `_tail.sub.php` 인데 스킨이 `G5_PATH.'/tail.php'` 를 또 include 하면 사이트 푸터·TOP 버튼·`html_end()` 이중 호출·태그 불일치가 난다.

- [ ] 팝업 셸만 `_tail.sub.php` 로 닫는가. 스킨에 `_tail.php` / `tail.php` include 가 없는가.

### J-4. 빈 이미지 ≠ AJAX 실패

아이콘 URL 이 빈 칸이면 그리드 `li` 는 있는데 화면은 빈 박스로 보인다. J-1(요청 자체가 안 나감)과 혼동하지 말 것.

- [ ] 목록이 비어 보이면 DOM 에 `li` / `data-id` 가 있는지, 네트워크에 AJAX 2xx 가 있는지 먼저 본다.
- [ ] 목록 칸에 슬롯과 같은 배경·보더가 있는가 (빈 `src` 대비).

### J-5. 작업 후 확인

- [ ] `window.open` 팝업에서 슬롯/버튼 클릭 → 목록·AJAX. 콘솔에 `k_xxx_url is not defined` 가 없는가.
- [ ] 마이페이지(또는 탭)에서 관리자와 같은 동적 CSS(단계색 등)가 붙는가.
- [ ] 정본은 `module/<name>/`. `battle_plugin/` · 루트 동명 폴더 미러를 고치지 않았는가. [H-6](#h-6-작업-후-확인).
- [ ] 타임라인 상세 모달(댓글 컴포저)에서 모듈 패널 토글이 글쓰기와 같이 되는가. [J-6](#j-6-모달-innerhtml-은-조각-script-를-실행하지-않는다).
- [ ] 공유 스킨 ID 를 suffix 했다면 버튼/패널 CSS 가 클래스에 붙는가. [J-11](#j-11-공유-스킨-id-를-suffix-하면-cssjs-도-클래스로-맞춘다).

### J-6. 모달 `innerHTML` 은 조각 `<script>` 를 실행하지 않는다

타임라인 `openPost()` 는 `view.php?ajax=1` HTML 을 `#tl-modal-body.innerHTML` 에 넣는다. `innerHTML` 로 삽입된 `<script>` 는 실행되지 않는다. 글쓰기 컴포저는 페이지 최초 렌더라 패널 안 인라인 스크립트가 돌고, **댓글/인라인 답글 컴포저만** 토글·프리뷰가 죽은 것처럼 보인다.

- [ ] 토글/프리뷰/파일 입력을 패널 안 `<script>` 에 묶지 않았는가. `bbs_enqueue_assets` 로 한 번 로드되는 **위임** JS (`document` click/change).
- [ ] 체크박스가 여는 서브패널은 CSS `:has(.orpg-kind:checked)` 로도 열리는가. JS 가 없어도 메뉴는 나와야 한다.
- [ ] 아이콘 클릭이 `getElementById(접두사+id)` 만 쓰는가. 모달·다중 폼에서는 같은 `form` 안의 패널을 찾는다.
- 레퍼런스: `module/k_timeline_orpg/js/composer.js`, `theme/basic/write.php` 아이콘 `data-orpg-panel`, `openPost()` (`timeline/timeline.common.js`).

### J-7. 프론트 팝업에서 `adm/admin.lib.php` 를 include 하지 말 것

`get_admin_token()` / `check_admin_token()` 이 없다고 팝업·`pages/*.php` 에서 `include_once G5_ADMIN_PATH.'/admin.lib.php'` 하면 안 된다. 그 파일은 함수만 있는 게 아니라, include 시점에 관리자 부트스트랩이 돈다 (`G5_ACTIVE_ADMIN_PATH` 미정의 경고, `ss_mb_key` 불일치 시 `session_destroy()`, 비로그인이면 `adm/login.php` 로 보냄).

- [ ] 토큰이 필요하면 `function_exists('get_admin_token')` 일 때만 그 함수를 쓰는가. 없으면 `set_session('ss_admin_token', …)` 로 로컬 발급.
- [ ] `check_admin_token()` 이 없는 프론트 POST 는 세션 토큰을 직접 비교하는가. 관리자 목록(`adm_module`) 은 `_common.php` 가 이미 lib 를 로드한다.
- [ ] 팝업 HTML 소스에 `G5_ACTIVE_ADMIN_PATH` / `admin.lib.php` 워닝이 없는가.
- 레퍼런스: `bbs_k_mmbraid` `k_mmbraid_token_html()` / `k_mmbraid_check_admin_token()`.

### J-8. 모듈 `pages/` 에서 `alert()` 전에 `chdir(G5_PATH)`

`bbs/alert.php` 가 `include_once('./_common.php')` 를 쓴다. `./` 는 **CWD** 다. 요청 파일이 `module/<name>/pages/*.php` 이면 CWD 가 그 폴더라 `./_common.php` 가 없고, 안내 페이지 앞에 `failed to open stream` 워닝이 붙는다. 브라우저 GET 은 JS 로 이동해도 중간 HTML 에 워닝이 남는다.

- [ ] `common.php` 로드 직후 `chdir(G5_PATH)` 하는가. 레퍼런스: `bbs_k_mmbraid` `k_mmbraid_chdir_root()`.
- [ ] `alert()` / `alert_close()` 를 쓰는 `pages/*.php` 전부인가. `adm_module` 라우트는 CWD 가 `adm_module/` 이라 같은 문제가 난다.

### J-9. 테마 위젯은 `get_widget()` + 고정 훅 `mdl_widget`

테마/스킨에 `include module/<name>/views/widget.php` 하거나 `k_*_widget()` 를 직접 심으면 모듈 OFF 시 fatal 이고, 모듈명을 바꾸면 테마가 깨진다. `mdl_apply_filters('widget_'.$module)` / `"mdl_widget_{$name}"` 처럼 보간하면 점검 스캐너가 훅을 못 찾는다 ([A-2](#a-2-발사-표현식이-정규식에-매칭되는가)).

발사처는 `module/_widget.php` `get_widget()` 안의 리터럴 `mdl_apply_filters('mdl_widget', ...)`. 이 파일이 `mdl_hook_scan_paths()` `$core_files` 에 있어야 모듈 점검이 ✅ 한다.

- [ ] 테마 한 줄이 `<?php echo get_widget('모듈명'); ?>` 또는 `echo get_widget('모듈', '위젯', $ch);` 인가. 크기 등 옵션은 네 번째. 기본 제공 모듈에는 위젯이 없다.
- [ ] 세 번째 인자를 생략하면 페이지 `$ch`, 없으면 `$character` 인가. 멤버 목록처럼 `$ch` 가 함수 지역이면 행을 넘기는가.
- [ ] 모듈 `bootstrap` 이 `mdl_add_filter('mdl_widget', ...)` 에서 `$module`/`$widget` 이 자기 것일 때만 `$html` 에 누적하는가. 입력을 버리지 않는가. 네 번째 `$size = null` 을 받아 쓰지 않아도 되는가.
- [ ] 렌더는 그 필터에서 `views/` 를 include 하는가. `k_*_widget()` 본체 함수·테마 뷰 include 가 없는가.
- [ ] `_hook_list.php` `uses` 에 `mdl_widget` 가 있는가. `requires_hooks` 에는 **넣지 않았는가** (게시판 스킨 훅이 아님).
- [ ] 테마가 `member/skin/viewer.skin.php` 를 덮으면 `$viewer_info` / `$viewer_bag` / `$viewer_relation` 을 해당 영역에 echo 하고 `$viewer_extra_tabs` 로 버튼·패널을, `$viewer_tools` 로 옷장 옆 팝업 메뉴를 그리는가. 훅은 [`member/viewer.php`](../member/viewer.php) 가 발사한다. 레퍼런스 `theme/timeline`, `theme/verd`.
- 레퍼런스: `module/_widget.php`. 기본 제공 넷에는 `mdl_widget` 구독이 없다.

### J-10. 모듈 프론트 색은 사이트 `:root` `--d-*` 를 쓴다

`css/root.php` 가 디자인 프리셋의 `--d-color_default` / `--d-color_point` / `--d-color_bak` / `--d-btn_*` / `--d-box_style` 을 emit 한다. 모듈 CSS 가 `:root { --point-color:#2b6462; --default-color:#D5E8BB }` 처럼 **독립 팔레트**를 두면 위젯이 테마를 무시한다. 폼 `color:#000; background:#eee` 로 `_design.config.css` 의 `--d-input_bak` 를 덮는 것도 같다.

증상: 포켓몬 위젯·마이페이지가 사이트와 다른 청록/연두로 고정. 테마를 바꿔도 위젯만 안 따라감.

- [ ] 모듈 `:root` 에 하드코딩 색이 없는가. 별칭이 필요하면 `var(--d-color_point)` / `var(--d-box_style)` 처럼 **`--d-*` 만** 가리키는가. (`--d-btn_point-1` 은 테마에 따라 연한 버튼 배경이라 강조 채움에 쓰면 안 됨)
- [ ] 카드·탭·테두리·버튼 크롬이 `var(--d-color_point)` / `--d-box_style` / `--d-btn_*` / `--d-color_bak` 인가. Chart.js 등 JS 채움색도 `getComputedStyle` 로 `--d-color_point` 를 읽는가.
- [ ] 모듈 CSS 가 `.form-input` / `input` 색을 `#000`/`#eee` 로 덮지 않는가. 사이트 `_design.config.css` 가 적용되게 둔다.
- [ ] 종/속성 팔레트(포켓몬 타입 칩 등)는 테마와 무관한 **도메인 색**으로 남겨도 되는가. 크롬과 섞지 않는다.
- 레퍼런스: `k_pokemon/css/default.css`, `info.css`, `mypage.css`, `encount.css`. `bbs_k_pokemon/css/action.css`. `k_quest/pages/quest.css`. 사이트 토큰: `css/root.php`, `css/_design.config.css`.

### J-11. 공유 스킨 ID 를 suffix 하면 CSS/JS 도 클래스로 맞춘다

한 페이지에 글쓰기·댓글 폼이 같이 있으면 `id="action-select"` 가 중복된다. unique suffix (`action-select_w`) 를 붙이면 마크업은 살아 있는데, CSS/JS 가 옛 `#action-select` 만 보면 **버튼 크롬이 안 나온다**(불릿 리스트처럼만 보임). 클릭 핸들러가 `#action-info` 만 찾으면 설명칸도 비다.

- [ ] unique ID 를 쓰는가. 스타일·스크립트는 `#고정id` 가 아니라 **클래스** (또는 `[id^="접두"]`) 인가.
- [ ] 공유 스킨(`action_select.php` 등) 을 고치면 CSS(`action_select.css`)·JS(`action.js`) 를 같이 보는가. 한 모듈만 ID 를 바꿔도 공용 CSS 가 깨진다.
- 레퍼런스: `k_battle/skin_default/action_select.php` (`class="action-select"`), `css/action_select.css`, `bbs_k_mmbraid` write 폼.

---

## K. 레거시 PHP 단축태그 (`<?`)

옛 플러그인·스킨을 `module/` 로 옮기면 `<?if` / `<?}` / `<?foreach` 가 섞여 있는 경우가 많다. 웹 PHP 는 `short_open_tag=Off` 인 경우가 기본이다. `<?=` 와 `<?php` 만 동작한다.

증상: (1) `if {` 를 `<?}else{` 로 닫은 파일이 **Parse error: unexpected end of file**. (2) 페이지는 뜨는데 셀렉트/루프가 비어 있고 소스에 `<?foreach` 가 그대로 보임.

레퍼런스: `k_pokemon` 배틀 팝업 `pages/battle/index.php`, 관리자 `has_list.php` 성격/캐릭터 셀렉트.

### K-1. `<?` 를 `<?php` 로 고친다

- [ ] `module/<name>/` 에서 `<?` 이면서 `<?php` / `<?=` / `<?xml` 가 아닌 태그가 없는가.
- [ ] 특히 `<?}else{` · `<?include` · 파일 첫 줄 `<?include_once` — 단축태그가 꺼지면 **구문 오류** 가 난다. 루프만 단축태그인 파일은 구문 오류 없이 UI 만 빈다.
- [ ] `php -l` 은 단축태그 Off 로 검사한다. 페이지를 브라우저로 열어 Parse error / 빈 셀렉트를 확인했는가.

---

## L. 게시판 스킨이 `wr_link1` / `wr_10` 을 업무 필드로 쓸 때

코어 `bbs/write_update.php` 는 수정 시 POST 에 없는 `wr_link1`·`wr_10` 을 **빈 문자열로 UPDATE** 한다. `bbs/write_comment_update.php` 스킨이 원글 `wr_10` 을 `$state` 로 덮으면, 댓글 수정(`w=cu`) 폼에 `state` 가 없을 때 **정산완료 등이 삭제**된다.

증상: (1) 목록에서 내용만 고쳐도 신청 JSON(`wr_link1`)·지급 플래그가 사라짐. (2) 정산 완료 댓글 수정 후 원글 상태 배지/`wr_10` 이 빔.

레퍼런스: `skin/board/calculate/` — `write_update.skin.php`, `write_comment_update.skin.php`.

### L-1. 글 수정 시 업무 필드 복원

- [ ] `write_update.skin.php` 에서 코어 UPDATE 직전 스냅샷(`$wr`/`$write`)으로 `wr_link1`(및 상태용 `wr_10`)을 복원하는가. 신청 필드(`calc_req_*` 등)가 POST 에 **없을 때** 비우지 않는가.
- [ ] 이미 지급(`paid`)된 JSON 은 신청을 비워도 삭제하지 않는가.
- [ ] 전체 write 폼에 상태 hidden 이 없어도 기존 `wr_10` 이 유지되는가.

### L-2. 댓글 수정 시 원글 상태를 덮지 않음

- [ ] 원글 `wr_10` 갱신은 POST `state`(또는 동등 필드)가 **비어 있지 않을 때만** 수행하는가.
- [ ] 댓글 수정 전용 폼(`w=cu`)에 상태 라디오가 없어도 기존 정산완료/반려가 유지되는가.

---

## M. HTML 로그 · 가시성 · `customer_sql` 가드 · 테마 폴백 · 자식 CRUD

레퍼런스: `k_timeline_orpg` (자유 입력 판정 XSS, 비공개 아이템 팝업 누수, onair `se_*` 가 빈 글 가드를 뚫음, `list_top` JS 가 `.orpg-theme-basic` 만 찾음, `_child_crud` UPDATE/DELETE 가 PK 만 봄, 관리자 PL 저장이 `journal/` 초상을 지움).

### M-1. 자유 입력 식이 HTML 로그가 되면 토큰 밖을 escape 한다

주사위·수식 엔진이 `[2d6]` 같은 토큰만 HTML 로 바꾸고 **나머지 문자열을 그대로** `rd_log` 에 넣으면, 폼 자유 입력이 저장형 XSS 가 된다. 프리셋 관리자 HTML 과 섞지 말 것. 권한이 있는 역할(PL/GM)이 자유 입력을 쓰는 것이 설계여도 **이스케이프는 역할과 무관**하다.

- [ ] 토큰 구간만 엔진 HTML 을 만들고, 그 밖은 `htmlspecialchars`. 이미 엔진이 만든 `<span class="variable">` 만 예외.
- [ ] 과거 DB 행을 위해 표시 측에서도 태그 화이트리스트 (`span`/`i`/`br` + `class`) 를 한 번 더 건다.

### M-2. 가시성 플래그는 호출부가 아니라 헬퍼에서 강제한다

`_locked` / `orpg_handout_visible` 을 탭 A 에서만 검사하고 칩 헬퍼는 본문을 항상 넣으면, 다른 탭에서 내용이 샌다.

- [ ] `orpg_list_item_chip_html` 같은 표시 헬퍼가 `_locked` 이면 제목만 숨김 라벨, 팝업 본문 없음.
- [ ] 새 테마가 호출부를 빼먹어도 헬퍼가 막는지 확인.

### M-3. `bbs_customer_sql` 이 빈 글 가드를 뚫지 않게 한다

타임라인 `insert_timeline_post` 는 `$customer_sql === ''` 일 때만 "내용을 입력해주세요" 를 탄다. 세션이 onair 라고 `se_id` 를 **매번** 붙이면 빈 글이 통과한다.

- [ ] 본문 또는 실제 모듈 액션(주사위·컷인 등)이 있을 때만 `customer_sql` 에 컬럼을 붙인다.
- [ ] 로그 INSERT / `pl_info` UPDATE 는 글이 생긴 뒤 (`write_after` / `comment_after`). 글 저장 실패 시 `wr_id=0` 고아·저널만 바뀐 상태를 남기지 않는다.

### M-4. 테마 폴백 파일에서 테마 클래스를 하드코딩하지 않는다

`orpg_theme_file()` 은 없는 파일을 `theme/basic/` 로 폴백한다. 폴백된 `list_top.php` 가 `.orpg-theme-basic` 만 찾으면, 실제 래퍼는 `.orpg-theme-{다른폴더}` 라 탭 JS 가 죽는다.

- [ ] 인라인 JS 선택자는 `.orpg-list-top` 처럼 **테마 클래스 없는** 공통 루트. 또는 `bbs_enqueue_assets` 위임 JS.
- [ ] CSS 는 계속 `.orpg-theme-{폴더}` 아래만. JS 와 CSS 스코프를 섞지 않는다.

### M-5. 관리자 자식 CRUD 는 부모 키로 스코프한다

목록 폼의 hidden PK 를 바꿔 다른 룰/시나리오/세션 행을 수정·삭제할 수 있다. 저널 JSON / 스킬 CSV 처럼 폼이 깨기 쉬운 컬럼은 목록에서 고치면 데이터가 날아간다.

- [ ] UPDATE/DELETE/`SELECT` 이미지 경로에 `AND {parent_key} = {parent_id}`.
- [ ] 부모(룰·시나리오·세션) 삭제 시 자식 테이블·업로드 파일을 같이 지운다.
- [ ] 저널 JSON / 스킬 CSV 는 관리자 목록·등록 표에 두지 않는다. 수정은 저널 UI.

### M-6. 이미지 keep 은 필드 `dir` 과 달라도 유지한다

저널 초상은 `journal/…` 이고 관리자 PL 썸 필드는 `dir=player` 다. keep 을 그 `dir` 만 통과시키면 선택수정이 `pl_thumb=''` 로 덮는다. 새 파일도 삭제 체크도 없는데 img 컬럼을 SET 해도 같다.

- [ ] keep 은 유효 상대경로면 유지. `dir` 은 신규 업로드 위치만.
- [ ] 교체·삭제 시 unlink 는 그 `dir` 파일만. 저널 이미지를 같이 지우지 않는다.
- [ ] 목록 UPDATE 는 새 파일 또는 삭제 체크일 때만 img 컬럼을 SET.

## N. 모듈 AJAX 는 UI 필터를 믿지 말고 소유·타입을 서버에서 재검증

레퍼런스: `k_battle` (강화 `k_equip_upgrade`, 외형 `ajax/equip.php` `act=custom`, 제작 `equip_craft_update.php`, 레이드 아이템·스킬 `use_k_item` / `get_k_battle_skill`), `k_realtime` (`_action_realtime.php` `rm_id` 대행).

목록 AJAX 가 `ch_id` 로 거른 HTML 만 보여주고, 실행 엔드포인트는 `in_id`/`eq_id`/`bs_id`/`rm_id` 만 받으면 요청을 바꿔 타 캐릭터 장비·재료·스킬을 쓸 수 있다.

### N-1. 실행 경로에서 소유·타입을 다시 본다

- [ ] 장비/인벤 변경은 `inven.ch_id` (또는 장비행이 가리키는 인벤) 가 **현재 캐릭터**와 같은지. 관리자 예외는 `$is_admin` 이 참일 때만.
- [ ] 재료 삭제는 `it_id` 가 레시피/`ug_item` 과 같고, 타입이 그 액션용인지 (`커스텀장비(K)` 등).
- [ ] 레이드 아이템은 `inven.ch_id = 시전자 unit_id`. 캐릭터 행동은 `unit_id === $character['ch_id']`. 스킬 행은 `bs.rm_id === 시전자 rm_id`. **대행 예외는 `$is_admin` 이 참일 때**(슈퍼만 아님). 레퍼런스 `k_realtime` `_action_realtime.php`.
- [ ] 레이드 채팅 POST 는 unslash 1회 후 로그 HTML 은 `h()`. `ses(...,'string')` 으로 저장하면 `\` 가 쌓인다. SQL escape 는 `insert_k_log` 한 곳만. **§G-3**.
- [ ] POST `ch_id` 만으로 제작·지급하지 않는다. 로그인 회원이 그 캐릭터의 주인인지.
- [ ] 게시판 액션 POST `use_item`/`in_id` 도 `inven.ch_id` 와 타입을 재검증한 뒤 소모. UI 가 없어도 서버에서 막는다. 레퍼런스: `bbs_action_map` MAP_MON.
- [ ] 배틀 수락·기술 선택은 `lo_id` 만 보지 말고 호출자가 `lo_3`/`lo_5` 인지, `ph_id`/`wh_id` 가 그 측 것인지. SET 컬럼명은 화이트리스트. 대행은 `$is_admin`. 레퍼런스: `k_pokemon` `search_update.php`.
- [ ] 장착은 대상 포켓몬 소유만 보지 말고 `inven.ch_id` 와 슬롯 `it_type` 도 재검증. 레퍼런스: `k_pokemon` `item_info.php`.
- [ ] 아이템추가/가구커스텀 재지급은 POST `reuse_it_id` 만 믿지 말고 `maker_ch_id` 가 현재 캐릭터이며, 카테고리·타입이 그 폼 필터와 같은지 재검증. 레퍼런스: `inventory/item_form_update.php`, `k_spacemaker/item_form_update.php`.

### N-2. 작업 후 확인

- [ ] 다른 캐릭터 `in_id` 로 강화·커스텀·재료 소모가 거절된다.
- [ ] 타 `ch_id` POST 제작이 거절된다.
- [ ] 목록 UI 를 우회한 AJAX 만으로 재현해 본다 (필터 HTML 이 막아주던 경로).
- [ ] 배틀 참가자가 아닌 세션의 `search_update` POST 가 거절된다. 타 캐릭터 `in_id` 장착이 거절된다.
- [ ] 다른 캐릭터 `maker_ch_id` 아이템의 `reuse_it_id` 재지급이 거절된다.

## O. 관리자 분기는 `$is_admin` 의 참

`$is_admin` 은 `'super'` / `'group'` / `'board'` 이거나, 폼에서 `!empty($is_admin)` 으로 boolean `true` 가 된다. `=== 'super'` 만 보면 관리자인데 수정·삭제·대행·업로드가 막힌다. `auth_check` 는 운영자 전체 허용(no-op).

- [ ] 권한은 `if ($is_admin)` / `!empty($is_admin)` / `if (!$is_admin)`.
- [ ] `=== 'super'` 는 보드 맥락에서 그룹·게시판 관리자를 다시 판정할 때만 (`adm/board_form.php`).
- [ ] 대행·타인 글 수정 예외도 같다. 레퍼런스: `k_realtime_is_admin()`, `can_edit_timeline_post()`, `mypage/character/character_form.php`.

## P. 동적 컬럼명을 SQL SET 에 넣을 때

관리자 설정이 만든 필드 코드를 그대로 `UPDATE … SET` 에 쓰면 코어 컬럼을 덮어쓴다. 여러 동적 컬럼을 하나의 `@sql_query(..., false)` 로 묶으면 컬럼 하나 누락에 전체가 무음 실패한다.

재현: 회원가입 추가 입력 코드 `level` → 저장기가 `mb_` 를 붙여 `mb_level`. 가입 폼에 권한 칸이 노출되고 가입자가 자기 레벨을 지정한다. 컬럼 하나만 없어도 `@sql_query(..., false)` 한 방이 전부 실패해 추가 입력값이 오류 없이 사라진다.

- [ ] 예약 컬럼 블랙리스트 (코어 컬럼 + 여분 `mb_1`~`mb_10`). 관리자 저장 시 거부, 쓰기 경로에서 skip.
- [ ] POST 값은 stripslashes 1회 후 `sql_real_escape_string` 만. **§G-3**.
- [ ] 동적 컬럼은 **컬럼별 UPDATE**. 실패는 `error_log`. `@`+`false` 한 방에 묶지 말 것.
- [ ] 컬럼 자동 생성/타입 승격 실패를 관리자에게 보고.
- [ ] `code|label|type|req` 한 줄에서 type 에 `|` 가 있으면 (select 옵션 `||`) explode 를 앞에서부터 자르지 말고, 앞 2개와 끝 1개를 떼고 나머지를 type 으로.

레퍼런스: `extend/10_register.lib.php` `register_extra_save()`, `adm/member_register_form_update.php`.

