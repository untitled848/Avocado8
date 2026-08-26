# 모듈 제작 가이드 (에이전트용)

코딩 에이전트가 모듈을 쓰거나 고칠 때 보는 정본입니다.  
운영자·테마 제작자는 [AUTHORING.md](AUTHORING.md) 를 봅니다. 관리자 UI: [000 · 모듈 관리](../adm/000-module.md).

> v1.0: 호환 레이어·deprecated 별칭 없음. 훅·스키마는 그 자리에서 고친다.

## 문서 지도

| 상황 | 문서 |
|------|------|
| 켜기/위젯/아이템 운영 | [AUTHORING.md](AUTHORING.md) |
| 이 파일 | 엔진 부트, 레이아웃, 훅 카탈로그, 위젯, BBS/타임라인, 아이템 `it_type`([§7](#7-아이템-기능--두-입구)) |
| 타임라인 AJAX·FormData·어셋·rename | **[AGENTS.timeline.md](../../module/AGENTS.timeline.md) 를 먼저** |
| 함정 점검 (A–P) | **[HOOK_CHECKLIST.md](../../module/HOOK_CHECKLIST.md)** |
| 액션 골격 복제 | [`bbs_action_sample/`](../../module/bbs_action_sample/) · [그 README](../../module/bbs_action_sample/README.md) |

본보기 동작은 **기본 제공**이다. `MDL_CORE_MODULES`: `bbs_action_search`(액션), `bbs_action_combine`(조합), `bbs_dice`, `bbs_item_use`.  
새 액션 폴더를 만들 때는 [`bbs_action_sample`](../../module/bbs_action_sample/) 을 복사한다 (기본 제공이 아님. [§12](#12-레퍼런스-모듈)). 그 외 선택 패키지는 함정 기록만 [HOOK_CHECKLIST.md](../../module/HOOK_CHECKLIST.md).

다른 모듈에도 같은 함정이면 코드만 고치지 말고 [HOOK_CHECKLIST.md](../../module/HOOK_CHECKLIST.md) 를 갱신한다.

### 죽은 훅 이름 (문서·옛 코드에 남아 있음)

| 쓰지 말 것 | 실제 |
|------------|------|
| `write_update_item_use` / `comment_update_item_use` | `write_after` / `comment_after` (`bbs_item_use` 가 구독) |
| `bbs_list_top` / `bbs_view_top` | `list_top` / `view_top` |
| `mdl_widget_{$module}` / `"widget_".$module` | 리터럴 `mdl_widget` + `get_widget()` |
| `mdl_check_override` / `module/<name>/override/` | 폐지. 훅만 |

`cdn_download_url` 은 아직 코어가 안 쏜다. `_hook_list` `uses` 에 넣지 말 것.

---

## 0. 작업 시작 전

1. 폴더명 = `module.php` `'name'` = `mdl_board_enabled()` = `mdl_setting_*($module)` — **동일**.
2. 게시판 훅 접두사는 **`bbs_`**. `mmb_` 는 코어 mmb 전용 발사처에만 있고, 새 훅에 쓰지 말 것.
3. 새 컬럼은 `install.php` 멱등 ALTER. 타임라인 write 모듈 컬럼은 [AGENTS.timeline.md §1](../../module/AGENTS.timeline.md) — 코어 스키마에 넣지 말 것.
4. PHP 파일 첫 줄 `if (!defined('_GNUBOARD_')) exit;`. UTF-8 BOM 없음.
5. 자체 테이블이면 CREATE 가 ALTER/`SHOW COLUMNS` 보다 먼저. PHP 8 은 `$error=false` 가 예외를 안 막는다. [HOOK_CHECKLIST.md §I](../../module/HOOK_CHECKLIST.md).

---

## 1. 파일 레이아웃

```
module/<name>/
├── module.php          [필수] return array(...) 메타
├── bootstrap.php       [필수에 가깝다] 매 요청, 훅 등록만
├── _hook_list.php      [강력 권장] uses / fires
├── install.php         [선택] 첫 활성 + 재설치 — DB, 설정 기본값
├── uninstall.php       [선택] 제거 시. 컬럼 DROP 금지
├── upgrade.php         [선택] tar.gz 버전 상승 시 (없으면 install)
├── admin/              [선택] ?m=<name>&p=<page>
├── views/              [선택] 로그·위젯 HTML
├── pages/              [선택] 프론트 자체 페이지
└── theme/<theme>/      [선택] 모듈 자체 UI만 (사이트 theme/·mtheme 아님). 예: ORPG
    ├── theme.php
    └── … (모듈 문서 따름)
```

`mdl_activate()` 는 유효한 `module.php` 만 있으면 성공한다. 훅 모듈은 bootstrap 이 있어야 동작한다.

액션 샘플은 `inc/` 에 기능별로 나누고 `bootstrap.php` 는 훅 등록 + include 만 한다. [`bbs_action_sample/README.md`](../../module/bbs_action_sample/README.md) · [§12.2](#122-액션-샘플-bbs_action_sample).

### 1.1 엔진 부트

```
common.php
  └─ module/_loader.php
        ├─ _hooks.php           mdl_add_action / mdl_apply_filters / mdl_do_action
        ├─ _widget.php          get_widget() → 필터 mdl_widget
        ├─ _registry.php        메타, 활성, board_scope, 훅 점검
        ├─ _setting.php         avo_module_setting KV
        ├─ _db.php              mdl_db_add_column / mdl_db_add_write_column
        ├─ _skin.php            bbs_render_row_extra() → bbs_view_row_extra
        ├─ _hook_registry.php
        └─ 활성 모듈 bootstrap.php 후 mdl_do_action('bootstrap')
```

상태: `data/module/state.json`, `data/module/board_state.json`.  
관리: `/adm_module/module_list.php`, `module.php?m=&p=`, 훅 인덱스 `hook_registry.php`.

확장 패턴 요약: (1) 훅만 (`bootstrap` 등록) (2) `board_scope` + 핸들러 첫 줄 `mdl_board_enabled` 후 lazy require (3) 위젯은 `get_widget` / 리터럴 `mdl_widget` (4) 새 `it_type` 은 [§7](#7-아이템-기능--두-입구) — `inventory/extend/` 스위치는 레거시.

---

## 2. `module.php` 메타

```php
<?php
if (!defined('_GNUBOARD_')) exit;

return array(
    'name'           => 'bbs_xxx',
    'title'          => '한글 제목',
    'description'    => '1~2줄',
    'version'        => '1.0.0',
    'author'         => '',
    'board_scope'    => true,   // bbs_* 훅이면 거의 true. mypage/member 만이면 false
    'board_default'  => 'off',  // 무거운 기능은 off
    'scopes'         => array('write', 'comment'), // 글·댓글을 따로 켬. 기본 제공과 같음. 한 액션의 단계면 write 만 — [HOOK_CHECKLIST.md §G-5](../../module/HOOK_CHECKLIST.md)
    'admin_menu'     => true,
    'module_type'    => '',     // 'cdn' / 'search' / 'item_bbs' — 같은 type 1개만 활성
    'version_banner' => '',
    'version_info_url' => '',
    'requires_hooks' => array(
        'bbs_write_form_action_options',
        'bbs_custom_action',
        'bbs_view_row_extra',
    ),
    'depends'        => array(),
    'standalone'     => false,  // 자체 페이지만이면 true (검증/보드 대상 제외)
    'admin_menus'    => array(
        'auto' => array(
            array('000', '내 모듈', G5_MDL_ADMIN_URL.'/module.php?m=bbs_xxx&p=setting'),
            array('100', '설정',    G5_MDL_ADMIN_URL.'/module.php?m=bbs_xxx&p=setting'),
        ),
    ),
);
```

**`requires_hooks`**

- 모듈이 **수신**하는 훅만. 모듈이 발사하는 훅은 `_hook_list` `fires`.
- **mmb 와 타임라인 둘이면 교집합만.** mmb 전용(`bbs_write_form_before_action`, `wr_type_*`, `mmb_*`)이나 타임라인 전용(`bbs_*_composer_icons` / `composer_panels`)을 넣으면 반대쪽 `board_form` 체크박스가 disabled. [HOOK_CHECKLIST.md §G-1](../../module/HOOK_CHECKLIST.md).
- `mdl_widget` 는 게시판 스킨 훅이 아니다. requires 에 넣지 말고 `_hook_list` `uses` 에만.

**`admin_menus`**

- 기본은 `'auto'` + suffix 3자리. 설치 시 그룹 100–899 할당.
- 탐색·조합·주사위만 그룹 `'002'` 고정 6자리. 복붙으로 `002`/`15x` 훔치지 말 것.
- 페이지 안 탭만 사이드바에 안 넣으려면 6번째 `'tab'`. [HOOK_CHECKLIST.md §B-6](../../module/HOOK_CHECKLIST.md).

---

## 3. 훅 API

```php
mdl_add_action($hook, $callback, $priority = 10);
mdl_add_filter($hook, $callback, $priority = 10);
mdl_do_action('event', $a, $b);
$html = mdl_apply_filters('filter', $value, $extra);
```

발사 이름은 **리터럴 문자열**. 변수·보간은 점검 스캐너가 못 찾는다. [HOOK_CHECKLIST.md §A-2](../../module/HOOK_CHECKLIST.md).

`_hook_list.php`:

```php
return array(
    'uses'  => array('bbs_custom_action', 'mdl_widget'),
    'fires' => array('item_use_pre'),
);
```

평면 배열은 `uses` 로 본다. `uses_bbs` 는 `uses` 에 합친 뒤 prefix `bbs_`/`mmb_`/`item_` 로 다시 나눈다.

코어가 훅을 쏘는 위치는 `mdl_hook_scan_paths()` (`bbs`, `timeline`, `module/_widget.php`, 스킨 mmb/basic/qna/calculate, `skin/timeline/basic` 등). 새 발사처가 이 목록 밖이면 점검이 “훅 없음”이 된다. [HOOK_CHECKLIST.md §A-1](../../module/HOOK_CHECKLIST.md).

최신 목록은 관리자 **훅 인덱스** 재스캔이 소스와 같다. 아래 카탈로그는 문서용 스냅샷이다.

### 3.1 반복 훅에서 캐시

`bbs_view_row_extra`, `bbs_comment_form_*` 처럼 행마다 도는 훅 안에서 설정·잔여 횟수를 매번 SELECT 하지 말 것. 헬퍼에 `static $cache`. 페이지 1회 안내는 `list_top` / `view_top` (이름은 `bbs_list_top` 이 아님).

---

## 4. 코어 훅 카탈로그

인자는 첫 값이 필터 체이닝 대상이다.

### 4.1 레이아웃 · 부트

| 훅 | 종류 | 위치 | 인자 |
|----|------|------|------|
| `bootstrap` | action | `module/_loader.php` (모든 bootstrap 후) | — |
| `head` / `tail` | action | `head.sub.php` / `tail.sub.php` | — |
| `admin_head` / `admin_tail` / `admin_common` | action | `adm/` | — |
| `mypage_common` | action | `mypage/_common.php` 등 | — |
| `shop_common` / `inventory_common` | action | 각 `_common.php` | — |
| `bbs_enqueue_assets` | action | `bbs/_head.php`, `timeline_print_css_head()` | `$bo_table` |

로그 CSS는 모듈 전역 ON 이면 항상. 작성 JS만 write/comment 스코프. 타임라인은 게시판 `_head` 를 안 탄다. [AGENTS.timeline.md §6](../../module/AGENTS.timeline.md) · [HOOK_CHECKLIST.md §F-2](../../module/HOOK_CHECKLIST.md).

### 4.2 위젯

| 훅 | 종류 | 위치 | 인자 |
|----|------|------|------|
| `mdl_widget` | filter | `get_widget()` (`module/_widget.php`) | `$html, $module, $widget, $ch, $size` |

### 4.3 회원 · 캐릭터

| 훅 | 종류 | 위치 | 인자 |
|----|------|------|------|
| `member_register_after` | action | `bbs/register_form_update.php` | `$mb_id, $_POST` |
| `member_login_after` | action | `bbs/login_check.php` | `$mb_id, $mb` |
| `member_logout` | action | `bbs/logout.php` | `$mb_id` |
| `member_leave_after` | action | `bbs/member_leave.php` | `$mb_id` |
| `member_delete_after` | action | `adm/member_list_update.php` | `$mb_id` |
| `member_profile_view` | action | `member/index.php` | `$mb_id` |
| `character_create_after` | action | mypage·adm 캐릭터 저장 | `$ch_id, $ctx` (`mb_id`, `context`) |
| `character_update_after` | action | mypage 저장 성공 직전 | `$ch_id, $ctx` |
| `character_delete_after` | action | mypage·adm 삭제 | `$ch_id, $ctx` |
| `character_form_extra` | filter | `mypage/character/character_form.php` | `$html, $ctx` |
| `relation_save_columns` | filter | `relation_update.php` | `$sql, $ctx` |
| `relation_save_after` | action | 동일 | `$rm_id, $mode, $_POST` |
| `status_sub_text` | filter | `mypage/character/status.inc.php` | `$sub_text, $ctx` |

### 4.4 마이페이지 · 공개 뷰어

| 훅 | 종류 | 위치 | 인자 |
|----|------|------|------|
| `mypage_submenu` | filter | `mypage/_head.php` `#submenu` | `$html, $ctx` — `<li>` 누적. 자체 목록 페이지는 여기 또는 `{label,href}` |
| `mypage_character_viewer_tabs` | filter | `mypage/character/viewer.php` | `$tabs, $ctx` — `{id,label,html}` 또는 `{label,href}` |
| `mypage_character_viewer_tools` | filter | 동일 | `$tools, $ctx` — `{label,href}` 옷장 옆. 기본 `window.open` |
| `mypage_index_widgets` | filter | `mypage/index.php` | `$html` |
| `member_list_item_extra` | filter | `member/skin/list.skin.php` | `$html, $ctx` (`ch`) |
| `member_viewer_info` / `bag` / `relation` | filter | `member/viewer.php` 스킨 전 | `$html, $ctx` |
| `member_viewer_tabs` | filter | 동일 | `$tabs, $ctx` — HTML id `mv_{id}_area`. 예약 id `info`/`bag`/`relation`/`extra` 무시 |
| `member_viewer_tools` | filter | 동일 | `$tools, $ctx` — `{label,href}` 를 옷장 옆에. 기본 새 창 |

뷰어 탭 vs 팝업 메뉴 vs 링크 vs 서브메뉴: [HOOK_CHECKLIST.md §G-6](../../module/HOOK_CHECKLIST.md). 테마가 `viewer.skin.php` 를 덮으면 `$viewer_*` 변수를 그려야 한다. [§J-9](../../module/HOOK_CHECKLIST.md).

### 4.5 글/댓글 생명주기 (스킨 무관)

| 훅 | 종류 | 위치 | 인자 |
|----|------|------|------|
| `write_after` | action | `bbs/write_update.php`, `timeline/ajax/ajax_post.php` | `$bo_table, $wr_id, $w` — `$w` 없음. 아이템 자비사용은 여기 |
| `write_delete_after` | action | `bbs/delete.php` | `$bo_table, $wr_id` |
| `write_good_after` / `write_scrap_after` | action | `good.php` / `scrap_popin_update.php` | … |
| `comment_after` | action | `write_comment_update.php`, `ajax_comment.php` | `$bo_table, $comment_id, $wr_id` — ID만. 행 데이터 필요하면 캐시 SELECT |
| `comment_delete_after` | action | `bbs/delete_comment.php` | `$bo_table, $comment_id` |
| `bbs_post_after` | action | mmb/basic `write_update` | `$bo_table, $wr_id, $w` |
| `bbs_comment_after` | action | mmb/basic `write_comment_update` | `$bo_table, $comment_id, $wr_id, $w` |
| `list_top` / `list_bottom` | filter | `bbs/list.php`, `list.mmb.php`, 타임라인 `write.skin.php` | `$html, $bo_table` |
| `view_top` / `view_bottom` | filter | `bbs/view.php` | `$html, $bo_table, $wr_id` |

타임라인 `$bo_table` 은 `$tl_table` 이다. 게시판 id 를 하드코딩하지 말 것.

### 4.6 작성 폼 (mmb · basic · 타임라인)

**원글**

| 훅 | mmb | basic | 타임라인 | 용도 |
|----|-----|-------|----------|------|
| `bbs_write_form_before` | action | action | — | 폼 직전 |
| `bbs_write_form_wr_type_options` / `wr_type_panels` | O | — | — | mmb `wr_type` 전용. requires 금지(타임라인에 없음) |
| `bbs_write_form_before_action` | O | — | — | Action 위. requires 금지 |
| `bbs_write_form_action_options` | O | O | O | `<option>` |
| `bbs_write_form_action_panels` | O | O | O | `.comment-data` |
| `bbs_write_form_after_action` | O | — | O | 아이템 영역. mmb 원글은 `dt`/`dd` (중첩 `dl>dt` 금지) |
| `bbs_write_form_options` | O | O | O | Option fieldset |
| `bbs_write_form_extra` | O (`$html, $bo_table`) | — | — | 제출 후 |
| `bbs_write_form_after_buttons` | O | — | — | `</form>` 직전 |
| `bbs_write_form_after` | action | — | — | 폼 직후 |
| `bbs_write_form_composer_icons` | — | — | O | 아이콘 버튼 `data-tl-panel` |
| `bbs_write_form_composer_panels` | — | — | O | `#tl-composer-extra-panels` |

**댓글 / 인라인 답글**

| 훅 | mmb 댓글 | basic 댓글 | 타임라인 답글 |
|----|----------|------------|----------------|
| `bbs_comment_form_before` | action | — | — |
| `bbs_comment_form_action_options` | `<option>` (`action_ui=select`) | 라디오 가능 | `<option>` (`action_ui=select`) |
| `bbs_comment_form_after_action` | O | O | O |
| `bbs_comment_form_options` | O | O | O |
| `bbs_comment_form_extra` | 패널 대용 | O | 패널 대용 |
| `bbs_comment_form_composer_icons` / `panels` | — | — | O |

- 한 페이지에 댓글 폼이 N개다. DOM `id`/`for` 는 `scope_id`(mmb: `list_item.wr_id`, basic: `wr_id`) suffix. POST `name` 은 고정.
- 타임라인 인라인 답글은 원글과 같이 `action=TYPE` 을 쓸 수 있다. `w=c` 라고 거절하지 말 것.
- 아이콘 HTML: `<button type="button" class="tl-btns" data-tl-panel="{id}" …>`. 패널: `<div class="tl-composer-extra-area" id="{id}">`. 답글은 `$ctx['id_prefix']` (`ir_{wr_id}`).

### 4.7 저장 글루 `bbs/_module_write_update.inc.php`

모든 스킨 `write_update` / `write_comment_update` 가 require.

1. `bbs_write_update_before` (action)
2. `bbs_custom_action` (filter) — 글·댓글 작성/수정 **항상**. `handled=true` 일 때만 `customer_sql`/`log` 반영. `w=u`/`cu` 이고 기존 `wr_log` 가 있으면 글루가 `action` POST 만 비움 (다이스 등 다른 키는 통과)
3. `bbs_customer_sql` / `bbs_memo_custom_sql` (filter) — handled 없이 SQL 을 붙일 때
4. `bbs_write_update_after` (action)

`$ctx['existing']` — 수정(`w=u`/`cu`) 시 기존 행. 자기 컬럼이 차 있으면 재추첨 금지.

`$result['customer_sql']` 는 `" , wr_xxx = '...'"` (선두 콤마). 부가 필드만 쓰는 모듈은 `handled` 를 켜지 말고 `bbs_customer_sql` 로 덧붙인다. [HOOK_CHECKLIST.md §G-8](../../module/HOOK_CHECKLIST.md).

mmb `wr_type` 저장: `mmb_write_update_by_type`. 목록 이미지: `mmb_list_log_img`.

타임라인 `ajax_post.php` 는 INSERT **전**에 글루를 부른다. `$wr_id`/`$wr_num` 은 0. 연결은 `write_after`. 게시판 mmb 는 INSERT **후**라 fail 시 빈 글 롤백이 필요하다. [HOOK_CHECKLIST.md §F-7](../../module/HOOK_CHECKLIST.md) · [§F-8](../../module/HOOK_CHECKLIST.md).

### 4.8 행 부가 렌더

스킨은 `bbs_render_row_extra($row, $ctx)` (`module/_skin.php`). 내부에서 `bbs_view_row_extra` 필터 + `wr_item_log` 면 `item_log_render`.

- HTML 은 `views/` include. bootstrap 에는 분기만.
- 본인 컬럼/토큰이 아니면 `$html` 그대로. 누적 시 `$html.ob_get_clean()`.
- **로그는 write/comment 스코프·`mdl_board_enabled` 로 막지 말 것.** mmb 목록 원글은 `scope=post`. 타임라인 피드는 댓글도 `content_type=post` 이므로 `wr_is_comment` 로 extra scope. [HOOK_CHECKLIST.md §F-1](../../module/HOOK_CHECKLIST.md) · [§F-4](../../module/HOOK_CHECKLIST.md).

타임라인 카드 클래스: `timeline_item_class` (`$class, $row`). 헤더만 접기: `timeline_item_compact_header` (`$compact, $row`). 공지(`wr_script=1`)와 모듈 나레이션을 섞지 말 것. [HOOK_CHECKLIST.md §F-5](../../module/HOOK_CHECKLIST.md) · [§F-6](../../module/HOOK_CHECKLIST.md).

### 4.9 아이템 · 인벤

| 훅 | 종류 | 위치 | 인자 |
|----|------|------|------|
| `inventory_use_dispatch` | filter | `inventory/use_item.php` | `$result, $ctx` — `handled` + `echo` |
| `inventory_use_after` | action | 코어 사용 후 / 모듈 직접 | `$in_id, $ch_id, $type` |
| `item_use_pre` | filter | `module/bbs_item_use/bbs/use.php` | `$result, $ctx` — `handled` 시 코어 D로그 스킵 |
| `item_use_core` | filter | 동일 | 로그/SQL 가공 |
| `item_use_post` | action | 동일 | `$ctx, $it, $item_log, $use_item` |
| `item_log_render` / `item_log_render_extra` | filter | render.php / 템플릿 | `$html, $item_log` |
| `item_type_options` / `item_category_options` | filter | `adm/item_form.php` 등 | `$opts` — `cf_item_category` 쓰지 말 것 |
| `admin_item_form_extra_fields` | action | `adm/item_form.php` | `$item, $w, $opts` |
| `admin_item_form_save_data` | filter | `adm/item_form_update.php` | `$data, $w` |
| `inventory_send_after` | action | 선물 | `$in_id, $from, $to` |
| `inventory_insert_after` / `inventory_delete_after` | action | `extend/item.lib.php` | … |
| `inventory_list_where` | filter | `inventory/list.inc.php` | `$sql_fragment` |
| `inventory_item_loaded` / `item_loaded` | filter | `item.lib.php` | `$row, $id` |
| `item_img_url` / `item_detail_img_url` | filter | `item.lib.php` | `$url, $it_id` |

자비사용 파이프라인은 `bbs_item_use` 가 보드 ON 일 때만 `use.php` 를 lazy load 한다.

### 4.10 상점 · 포인트 · 기타

| 훅 | 종류 | 위치 |
|----|------|------|
| `shop_index_top` / `shop_index_bottom` | filter | `shop/index.php` |
| `shop_purchase_after` | action | `shop/_ajax.shop_update.php` |
| `point_insert_after` / `point_delete_after` | action | `lib/common.lib.php` |
| `site_content` | filter | `extend/community.config.php` |
| `memo_send_after` | action | 동일 |
| `push_call_after` | action | `extend/call.lib.php` |
| `conv_subject` / `conv_content` / `sql_search_where` | filter | `common.lib.php` |
| `board_file_row` / `view_file_link` / `sideview_html` | filter | `common.lib.php` |
| `payout_form_extra_types` | action | `adm/calc_form.php` |
| `payout_preset_payload_save` | filter | `adm/calc_preset_form_update.php` |

### 4.11 CDN

| 훅 | 종류 | 위치 |
|----|------|------|
| `cdn_base_url` / `cdn_resolve_url` / `cdn_resolve_content` | filter | `extend/00_cdn.lib.php` |
| `cdn_css_rewrite_enabled` | filter | 동일 |
| `cdn_after_upload` / `cdn_after_delete` | action | 동일 |

업로드 후 `cdn_after_upload($abs)`. 삭제는 `cdn_unlink()`. `@unlink` 만 하면 R2 잔여.

---

## 5. 위젯 — `get_widget()` / `mdl_widget`

테마·스킨·뷰어 탭은 모듈 함수명을 직접 쓰지 않는다. 넣고 싶은 위치에 `get_widget()` 한 줄을 붙인다.

```php
<?php echo get_widget('my_mod'); ?>
<?php echo get_widget('my_mod', 'default', $ch); ?>
<?php echo get_widget('my_mod', 'default', $ch, 160); ?>
```

기본 제공 네 모듈에는 위젯이 없다. 위젯을 만들 때만 아래 필터를 건다. 테마에 심을 키는 그 모듈 README.  
`_hook_list` `uses` 에 `mdl_widget` 가 있으면 관리자 모듈 목록 카드에 **위젯** 태그가 붙는다.

```php
mdl_add_filter('mdl_widget', function ($html, $module, $widget, $ch, $size = null) {
    global $g5;
    if ($module !== 'my_mod' || $widget !== 'default') {
        return $html;
    }
    ob_start();
    include G5_MDL_PATH.'/my_mod/views/widget.php';
    return $html.ob_get_clean();
});
```

규칙: [HOOK_CHECKLIST.md §J-9](../../module/HOOK_CHECKLIST.md) · [§J-10](../../module/HOOK_CHECKLIST.md) (`--d-*`).

- `$widget` 빈 값 → `default`.
- `$ch` 생략 → 페이지 `$ch` → `$character`. 목록 루프는 `$ctx['ch']` 를 넘긴다.
- `$size = null` 로 받아 안 쓰는 모듈이 깨지지 않게.
- 자기 모듈·위젯일 때만 `$html` 에 **누적**. 입력을 버리지 말 것.
- `my_mod_widget()` 같은 본체 함수·테마 `include` 뷰 금지. 모듈 OFF 시 fatal.
- `_hook_list` `uses` 에 `mdl_widget`. `requires_hooks` 에는 넣지 않음.
- include 파일에 `global $g5` (필요하면 `$is_admin`). [HOOK_CHECKLIST.md §J-2](../../module/HOOK_CHECKLIST.md).

뷰어에 심을 때:

```php
mdl_add_filter('mypage_character_viewer_tabs', function ($tabs, $ctx) {
    $html = get_widget('my_mod');
    if (trim($html) === '') return $tabs;
    $tabs[] = array('id' => 'my_mod', 'label' => '내 기능', 'html' => $html);
    return $tabs;
});
```

옷장처럼 **새 창**이면 탭이 아니라 `member_viewer_tools` / `mypage_character_viewer_tools` 의 `{label, href}`. `s=sub` 팝업 URL. `popup=>false` 만 같은 창.

자체 `pages/index.php` 로 **같은 창에서 떠나면** 탭 `{label, href}` 또는 `mypage_submenu` 의 `<li>`.

---

## 6. 게시판 vs 타임라인 — 액션 모듈

### 6.1 `bbs_custom_action`

`action` 이 비어도 **항상** 호출된다. 트리거는 `action=S`(탐색) / `H`(조합) 또는 자체 POST (`wr_dice`, `use_item`).

```php
mdl_add_filter('bbs_custom_action', function ($result, $ctx) {
    if (!empty($result['handled'])) return $result;
    $bo_table = isset($ctx['bo_table']) ? $ctx['bo_table'] : '';
    $w = isset($ctx['w']) ? $ctx['w'] : '';
    if (function_exists('mdl_board_enabled_for_w')) {
        if (!mdl_board_enabled_for_w($bo_table, 'bbs_xxx', $w)) return $result;
    } else {
        $scope = ($w === 'c' || $w === 'cu') ? 'comment' : 'write';
        if (!mdl_board_enabled($bo_table, 'bbs_xxx', $scope)) return $result;
    }

    $existing = isset($ctx['existing']) && is_array($ctx['existing']) ? $ctx['existing'] : array();
    if (!empty($existing['wr_log'])) return $result;

    $post = isset($ctx['post']) ? $ctx['post'] : array();
    if (isset($result['action']) && $result['action'] === 'X') { /* … */ }
    if (isset($post['wr_xxx']) && (string)$post['wr_xxx'] === '1') { /* … */ }

    $result['handled']      = true;
    $result['customer_sql'] = " , wr_xxx = '...' ";
    $result['log']          = 'X||...';
    return $result;
}, 10);
```

실패: 타임라인이면 JSON, 게시판이면 alert + **빈 글 롤백**. 한 함수에서 `SCRIPT_NAME` 분기. [AGENTS.timeline.md §3](../../module/AGENTS.timeline.md) · [HOOK_CHECKLIST.md §G-8](../../module/HOOK_CHECKLIST.md) · [§F-8](../../module/HOOK_CHECKLIST.md).

### 6.2 `scopes`

기본 제공(`bbs_action_search`, `bbs_action_combine`, `bbs_dice`, `bbs_item_use`)은 **`scopes` = write 와 comment**. 글 훅은 `'write'`, 댓글 훅은 `'comment'`. `mdl_board_enabled_for_w($bo_table, $module, $w)` 가 맞다.

한 액션의 원글+댓글 단계를 **같은 스위치**로 묶을 때만 write 만 둔다. 그때는 댓글 훅 게이트도 `'write'`. comment 를 넘기면 메타에 키가 없어 `board_default` 로 꺼진다. [HOOK_CHECKLIST.md §G-5](../../module/HOOK_CHECKLIST.md).

로그 렌더는 스코프 게이트 금지.

### 6.3 컨텍스트

| | 게시판 | 타임라인 |
|--|--------|----------|
| `$bo_table` | 게시판 id | `$tl_table` |
| 저장 | INSERT 후 훅 (mmb) | INSERT **전** 훅 + JSON |
| 컴포저 | tpl select | `#tl-form` FormData 수동. 새 `name` 이 폼 안에 있어야 POST 에 실림 |
| 파일 | 일반 form | `appendTimelineFormFields` — `images[]` 가 아닌 `type=file` 도 실림. [HOOK_CHECKLIST.md §G-9](../../module/HOOK_CHECKLIST.md) |
| 어셋 | `bbs/_head.php` | `timeline_print_css_head()` |
| extra | `bbs_render_row_extra` | `item.content.skin.php` 동일 훅 |

**POST 이스케이프:** `common.php` 가 `$_POST`/`$_GET` 전체에 이미 `addslashes` 한다. 저장은 `stripslashes` **1회** 후 `sql_real_escape_string` 만. PHP에서 만든 값(`json_encode`, DB에서 읽은 행)에는 stripslashes 를 넣지 않는다. `addslashes`/`htmlspecialchars` 를 저장 경로에 추가하지 말 것. 관리자 목록에서 이름만 고쳐도 JSON/textarea 를 SET 하면 재escape 로 값이 날아간다 — JSON 은 unslash 후 **유효할 때만** UPDATE, 깨졌으면 컬럼 건너뛴다. [HOOK_CHECKLIST.md §G-3](../../module/HOOK_CHECKLIST.md).

보드 URL을 `board.php` 로 고정하지 말 것. 타임라인이면 타임라인 경로. [HOOK_CHECKLIST.md §G-11](../../module/HOOK_CHECKLIST.md).

### 6.4 `bbs_view_row_extra` 골격

```php
mdl_add_filter('bbs_view_row_extra', function ($html, $row, $ctx) {
    if (empty($row['wr_log'])) return $html;
    $data_log = explode('||', $row['wr_log']);
    if (!isset($data_log[0]) || $data_log[0] !== 'X') return $html;
    $view = __DIR__.'/views/log_X.php';
    if (!is_file($view)) return $html;
    ob_start();
    include $view;
    return $html.ob_get_clean();
}, 10);
```

include 스킨이 `$html`/`$block` 을 덮으면 로그가 두 줄이 된다. [HOOK_CHECKLIST.md §J-2](../../module/HOOK_CHECKLIST.md).

### 6.5 새 write 컬럼

**게시판 `g5_write_*`:** `install.php` `mdl_db_add_write_column` + `adm/sql_write.sql`.

**타임라인 `{prefix}timeline_{tl}` — 모듈 컬럼:** `mdl_db_add_write_column` 만. `get_timeline_write_schema()` / `install/gnuboard5.sql` 에 모듈 컬럼 금지. 별칭 폴백: [HOOK_CHECKLIST.md §I-5](../../module/HOOK_CHECKLIST.md).

**타임라인 코어 스키마** (시스템 테이블·`timeline_default` 코어 write 컬럼)를 바꿀 때는 `install/gnuboard5.sql` 과 `get_timeline_write_schema()` / `get_timeline_write_indexes()` 를 같이 수정한다. 런타임 `CREATE`/`ALTER`/`SHOW COLUMNS` 후 자동 추가 금지. `config_table`·`character_table` ALTER 는 타임라인 스키마에 넣지 않는다. 상세: [AGENTS.timeline.md §1](../../module/AGENTS.timeline.md).

uninstall 에서 DROP 하지 않음.

---

## 7. 아이템 기능 — 두 입구

새 `it_type` 효과를 모듈로 추가할 때. 본보기: `bbs_action_search`(`탐색횟수`), `bbs_action_combine`(`조합횟수`), 학습 `bbs_action_sample`(`샘플횟수`).

| 입구 | 트리거 | 훅 | `handled=true` |
|------|--------|-----|----------------|
| 인벤 | 마이페이지 [사용] | `inventory_use_dispatch` | 코어 switch 스킵 + `$result['echo']` |
| 자비 | 글/댓글 `use_item` | `item_use_pre` | 코어 D로그·차감 스킵 |

**둘 다 등록.** 자비사용은 그 보드에서 `bbs_item_use` ON. 코어 훅 `write_update_item_use` 는 없다 — 게이트는 `write_after` / `comment_after`.

```
item_use_pre  → (미처리 시) 코어 기본 → item_use_core → SQL → item_use_post
```

### 7.1 규칙

- `handled=true` 는 자기 `it_type` 만. 아니면 `$result` 그대로.
- **`it_type` 문자열로만 분기.**
- 인벤: 직접 `delete_inventory($in_id, $ever ? 1 : 0)`. (`it_use_ever` 면 두 번째 인자 1)
- 자비: `customer_sql` 은 `wr_item` / `wr_item_log` 만. 다른 컬럼은 install 로 추가한 뒤 사용.
- `item_log` 형식: `D||it_id||it_name||it_type||it_value||extra…` (첫 토큰 `D` = 사용)
- `item_type_options` 로 관리자 종류 라벨 추가. `cf_item_category` 연결 금지.
- AJAX 가 `in_id` 만 보면 남의 인벤을 쓴다. 서버에서 `ch_id`·타입 재검증. [HOOK_CHECKLIST.md §N](../../module/HOOK_CHECKLIST.md).

`bbs_item_use` 는 `write_after` / `comment_after` + `bbs_*_form_after_action` 이다. 폼 훅은 mmb 원글/댓글/타임라인을 **렌더에서 분기**한다. [`bbs_item_use/AGENTS.md`](../../module/bbs_item_use/AGENTS.md).

아이템만 다루는 모듈은 보통 `board_scope=false`. `requires_hooks` 예: `item_use_pre`, `inventory_use_dispatch`.

### 7.2 `bootstrap.php` 골격

```php
<?php
if (!defined('_GNUBOARD_')) exit;

mdl_add_filter('inventory_use_dispatch', function ($result, $ctx) {
    if (!empty($result['handled'])) return $result;
    $in = isset($ctx['in']) ? $ctx['in'] : array();
    $function = isset($ctx['inven_function']) ? $ctx['inven_function'] : '';
    if ($function !== '<IT_TYPE>') return $result;

    $in_id = (int)(isset($ctx['in_id']) ? $ctx['in_id'] : 0);
    $ch_id = (int)(isset($in['ch_id']) ? $in['ch_id'] : 0);
    $value = (int)(isset($in['it_value']) ? $in['it_value'] : 0);
    $name  = (string)(isset($in['it_name']) ? $in['it_name'] : '아이템');
    $ever  = !empty($in['it_use_ever']);
    $ret_url = (string)(isset($ctx['return_url']) ? $ctx['return_url'] : '');

    // your_effect_apply($ch_id, $value);

    if (function_exists('delete_inventory')) delete_inventory($in_id, $ever ? 1 : 0);
    if (function_exists('mdl_do_action')) mdl_do_action('inventory_use_after', $in_id, $ch_id, '<IT_TYPE>');

    $result['handled'] = true;
    $result['echo'] = 'LOCATIONURL||||'.$ret_url.'&amp;tabs=i&amp;msg='.urlencode('『'.$name.'』 사용 완료');
    return $result;
}, 10);

mdl_add_filter('item_use_pre', function ($result, $ctx) {
    if (!empty($result['handled'])) return $result;
    $it = isset($result['it']) ? $result['it'] : array();
    if ((isset($it['it_type']) ? $it['it_type'] : '') !== '<IT_TYPE>') return $result;

    $character = isset($ctx['character']) ? $ctx['character'] : array();
    $ch_id = (int)(isset($character['ch_id']) ? $character['ch_id'] : 0);
    $value = (int)(isset($it['it_value']) ? $it['it_value'] : 0);
    $name  = (string)(isset($it['it_name']) ? $it['it_name'] : '아이템');
    $use_item = (int)(isset($result['use_item']) ? $result['use_item'] : 0);

    // your_effect_apply($ch_id, $value);

    if (empty($it['it_use_ever']) && function_exists('delete_inventory') && $use_item > 0) {
        delete_inventory($use_item);
    }

    $item_log = 'D||'.(isset($it['it_id']) ? $it['it_id'] : 0).'||'.$name.'||<IT_TYPE>||'.$value.'||';
    $result['handled'] = true;
    $result['item_log'] = $item_log;
    $result['customer_sql'] = " , wr_item = '".sql_real_escape_string((string)(isset($it['it_id']) ? $it['it_id'] : ''))."', wr_item_log = '".sql_real_escape_string($item_log)."' ";
    return $result;
}, 10);
```

참고 구현: `module/bbs_action_search/bootstrap.php`, `bbs_action_combine/_combine_item.inc.php`, `bbs_action_sample/inc/item_type.inc.php`. 자비 코어: `module/bbs_item_use/bbs/use.php`. 인벤 코어: `inventory/use_item.php`.

### 7.3 아이템 종류 검증

- [ ] 관리자 종류 셀렉트에 `<IT_TYPE>` (`item_type_options`)
- [ ] 인벤 사용 → 효과 + 차감 (`it_use_ever=1` 이면 유지)
- [ ] 자비 사용 → 효과 + 차감 + `bbs_item_use` 보드 ON
- [ ] 모듈 OFF 시 같은 아이템이 잘못 효과를 내지 않음

---

## 8. `bootstrap.php` · `install.php`

bootstrap 첫 줄 `$module_url = G5_MDL_URL.'/<name>';`. 훅만 등록. 파일 IO·DDL 금지.

모든 보드 콜백 첫 줄 게이트. 무거운 require 는 게이트 후. 클로저에서 `$g5` 는 `global` 또는 `$GLOBALS`.

```php
mdl_add_filter('bbs_write_form_options', function ($html, $ctx) use ($module_url) {
    if (!mdl_board_enabled($ctx['bo_table'], 'bbs_xxx', 'write')) return $html;
    return $html.'…';
}, 10);
```

**install.php**

- 첫 활성 + 목록 **재설치** (`mdl_run_install`). 이미 켠 모듈은 끄고 켜기로 안 돈다. [HOOK_CHECKLIST.md §I-6](../../module/HOOK_CHECKLIST.md).
- **`bootstrap.php` 를 require 금지** (훅 이중 등록 + CREATE 전 ALTER Fatal). [§I-3](../../module/HOOK_CHECKLIST.md) · [§I-8](../../module/HOOK_CHECKLIST.md).
- 자체 테이블: 별칭 (`lib.php` 또는 install 인라인) → `CREATE IF NOT EXISTS` → `mdl_db_add_column`. 요청 경로 `schema_ensure` 금지.
- CREATE 에 넣은 UNIQUE/INDEX 를 ALTER ADD 로 반복하지 말 것. [§I-7](../../module/HOOK_CHECKLIST.md).
- `$g5['character_table']` 등 코어 테이블 ALTER 는 타임라인 스키마에 넣지 않음.

```php
mdl_db_add_write_column('wr_xxx', "int(11) NOT NULL DEFAULT '0'");
mdl_db_add_column($g5['character_table'], 'ch_xxx', "int(11) NOT NULL DEFAULT '0'");
if (mdl_setting_get('bbs_xxx', 'limit', null) === null) {
    mdl_setting_set('bbs_xxx', 'limit', 5);
}
```

uninstall: `mdl_setting_drop_module('bbs_xxx');` 컬럼은 보존.

설정 KV: `mdl_setting_get/set/all/drop_module`. 테이블 `avo_module_setting`.

---

## 9. 어드민 · (구 mtheme)

라우터: `adm_module/module.php?m=<name>&p=<page>` → `module/<name>/admin/<page>.php`.

GET 폼에 hidden `m`, `p`. include 는 **PATH** (`__DIR__`, `K_XXX_PATH`). URL 상수를 include 하지 말 것. [HOOK_CHECKLIST.md §H-1](../../module/HOOK_CHECKLIST.md).

**사이트 테마**는 `theme/{name}/` + 테마 프리셋 슬롯만 사용한다. `mtheme:` / `cf_theme = mtheme:…` / DB `bo_skin = mtheme/…` 경로 모델은 **폐지** (`is_valid_theme` 거부). 모듈이 자체 UI 테마 폴더를 쓰는 경우(예: ORPG `module/k_timeline_orpg/theme/`)는 사이트 프리셋과 무관하다.  
사이트 테마 작성: [`docs/theme/AUTHORING.md`](../theme/AUTHORING.md).

게시판 설정은 `g5_board` 여분필드가 아니라 모듈 설정 테이블. [HOOK_CHECKLIST.md §H-2](../../module/HOOK_CHECKLIST.md). 한 모듈만 끌 때 `mdl_set_board_modules()` 부분 맵 금지. [§H-4](../../module/HOOK_CHECKLIST.md).

---

## 10. 배포

`.tar.gz` 만. 최상위 한 폴더 + `module.php`. path traversal 거부. 켠 모듈 버전 상승 → `upgrade.php` 또는 `install.php`. bootstrap 은 안 부름.

---

## 11. 작업 후 검증

코드 (로컬 Laragon 예 — `php` 가 PATH 에 없을 때):

```powershell
$php = "Z:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe"
Get-ChildItem module/bbs_xxx -Recurse -Filter *.php | ForEach-Object { & $php -n -l $_.FullName }
Select-String -Path module/bbs_xxx/*.php -Pattern 'mmb_|write_update_item_use|bbs_list_top|mdl_widget_' 
```

문법만 볼 때는 `-n -l` 이면 충분하다. DB 가 필요하면 같은 폴더 `ext\` 에 `extension_dir` 을 지정하고 `mysqli` 등을 켠다.
브라우저:

1. `/adm_module/module_list.php` 에 보이는지 → 켜기 → [점검] 에서 uses ✅
2. mmb `board_form` **과** 타임라인 `board_form` 체크박스가 disabled 가 아닌지 (§G-1)
3. 글·댓글(인라인 답글) 폼 → POST → DB
4. 타임라인이면 [AGENTS.timeline.md §11](../../module/AGENTS.timeline.md)
5. 게시판 액션이면 [HOOK_CHECKLIST.md §F](../../module/HOOK_CHECKLIST.md)
6. wr_type+action+자체 프론트면 [§G](../../module/HOOK_CHECKLIST.md)
7. 덮어쓰기 패키지 이식이면 [§H](../../module/HOOK_CHECKLIST.md)
8. 자체 테이블이면 **테이블 없는 DB에서 켜기** [§I](../../module/HOOK_CHECKLIST.md)
9. 팝업·위젯·모달·AJAX 면 [§J](../../module/HOOK_CHECKLIST.md)
10. 레거시 `<?` 면 [§K](../../module/HOOK_CHECKLIST.md)
11. `wr_link1`/`wr_10` 업무 필드면 [§L](../../module/HOOK_CHECKLIST.md)
12. HTML 로그·가시성·빈 글 가드면 [§M](../../module/HOOK_CHECKLIST.md)
13. 인벤/장비 AJAX 면 [§N](../../module/HOOK_CHECKLIST.md)
14. 관리자 분기면 [§O](../../module/HOOK_CHECKLIST.md) (`=== 'super'` 금지)
15. 동적 컬럼 SET 이면 [§P](../../module/HOOK_CHECKLIST.md) (예약 컬럼·컬럼별 UPDATE)
16. 다른 모듈에도 해당하는 함정을 체크리스트에 넣었는가

### 체크리스트 맵 (HOOK_CHECKLIST)

| 절 | 언제 |
|----|------|
| **A** | 코어에 훅을 새로 쏠 때 (스캔 경로, 리터럴 이름, prefix) |
| **B** | 새 모듈 파일·메타·`module_type`·`'tab'`·버전 배너 |
| **C** | 회고 표 (증상 → 절) |
| **F** | 액션 로그/CSS/mmb 원글 본문/피드 extra/`wr_num`/게시판 fail 롤백 |
| **G** | mmb+타임라인+자체 프론트, FormData file, 참가 URL |
| **H** | 루트 덮어쓰기·여분필드 모듈화 |
| **I** | 자체 테이블 최초 설치·재설치·타임라인 write 컬럼 |
| **J** | 팝업 URL, include `$g5`, 모달 innerHTML, `get_widget`, `--d-*`, unique ID vs CSS |
| **K** | `<?` 단축태그 |
| **L** | `wr_link1`/`wr_10` |
| **M** | XSS 로그, 가시성 헬퍼, 빈 글 `customer_sql`, 자식 CRUD |
| **N** | AJAX 소유·타입 재검증 |
| **O** | 관리자 분기는 `$is_admin` 참 (`=== 'super'` 만 보지 않음) |
| **P** | 동적 컬럼명을 SQL SET 에 넣을 때 (예약 컬럼·컬럼별 UPDATE) |

---

## 12. 레퍼런스 모듈

### 12.1 기본 제공

선택 패키지를 실전 본보기로 복사하지 않는다. 함정이 그 패키지에서 발견됐으면 [HOOK_CHECKLIST.md](../../module/HOOK_CHECKLIST.md) 해당 절만 본다.

| 모듈 | 학습 포인트 |
|------|-----------|
| `bbs_action_search` | `action=S`, KV 설정, 어드민, `it_type=탐색횟수` 인벤+자비, 글/댓글 스코프 따로, `mdl_board_enabled_for_w` |
| `bbs_action_combine` | `action=H`, action 패널, `it_type=조합횟수`, mmb select disable 는 `.make-imtem` 만 |
| `bbs_dice` | 자체 POST `wr_dice` (action 값 없음). 글/댓글 폼 `bbs_*_form_options` |
| `bbs_item_use` | `write_after` / `comment_after` 게이트 + lazy `bbs/`. `bbs_*_form_after_action`. 자비 `item_use_pre` |

가상 예시 이름 `welcome_bonus` 는 폴더가 없다.

### 12.2 액션 샘플 (`bbs_action_sample`)

기본 제공이 아니다. 끄기·삭제가 된다. **새 `bbs_action_*` 의 폴더 골격**이다. 운영 안내는 [AUTHORING.md §7](AUTHORING.md#7-액션-샘플-학습용), 폴더 설명은 [`bbs_action_sample/README.md`](../../module/bbs_action_sample/README.md).

한 모듈 안에 세 패턴을 같이 보여 준다. `scopes` 는 기본 제공과 같이 `write` + `comment`.

| `action` | 역할 |
|----------|------|
| `SAMPLE` | 글·댓글 공통. `character_sample` 카운터. `it_type=샘플횟수` |
| `SAMPLE_W` | 글만 (`bbs_write_form_action_*`) |
| `SAMPLE_C` | 댓글만. 원글 `wr_log` 가 `SAMPLE_W\|\|` 일 때만 옵션 (한 액션 단계의 댓글 후속) |

그밖에 샘플이 가르치는 것:

- `bootstrap.php` 는 훅 등록 + `inc/*.inc.php` include 만. HTML·긴 로직 금지.
- include 파일에서 `return` 금지 (콜백 전체가 끝난다). `if { }` 로 가드.
- 로그 CSS(`sample.log.css`)는 보드 스코프 OFF여도 로드. UI/작성 CSS·JS는 write 또는 comment ON. [AGENTS.timeline.md §6](../../module/AGENTS.timeline.md) · [HOOK_CHECKLIST.md §F-2](../../module/HOOK_CHECKLIST.md).
- 로그 렌더는 `mdl_board_enabled` 로 막지 않음. `list_top` 배너는 보드 ON 만.
- 패널 토글은 코어 `js/action_panel.js`. 모듈 JS는 검증·힌트만 (`js/sample.js`).
- 관리자 `admin_menus` 는 `'auto'` (탐색·조합·주사위의 `'002'` 를 훔치지 말 것).

복제: 폴더와 `module.php` `name` 을 바꾸고, action·로그 토큰·카운터·`it_type`·설정 키를 일괄 치환한 뒤 안 쓰는 `SAMPLE_W`/`SAMPLE_C` 를 뺀다. 실전 저장·횟수·아이템 효과는 기본 제공 넷을 본다.

---

## 13. 흔한 함정 (요약)

전체 표는 [HOOK_CHECKLIST.md §C](../../module/HOOK_CHECKLIST.md). 모듈 작업 중 자주 터지는 것만.

| 증상 | 원인 | 문서 |
|------|------|------|
| 점검 “훅 없음” | 발사처가 스캔 경로 밖, 또는 보간 훅명 | §A |
| 타임라인/mmb 체크박스 disabled | `requires_hooks` 가 한쪽 전용 | §G-1 |
| 글만 되고 답글 없음 | comment 훅 미등록. 또는 scopes comment 없음인데 `'comment'` 게이트 | §G-5 |
| 별 탭이 아이템 패널에 섞임 | 타임라인 `after_action` 은 가방. 탭은 `composer_icons` | §G-5 |
| 로그 HTML 없음 / 스타일만 없음 | extra 를 comment 만, 또는 CSS 를 write ON 에 묶음 | §F-1 · §F-2 |
| 타임라인 JSON 오류 | `alert()` / 워닝 / FormData 필드 누락 | AGENTS.timeline §3–4 |
| 모듈 켜자마자 Table doesn't exist | CREATE 전 ALTER. install 이 bootstrap require | §I |
| 컬럼이 없음 | 이미 켠 모듈 — **재설치** | §I-6 |
| 위젯 점검 X / OFF 시 fatal | 본체 위젯 함수 직접 호출 또는 `widget_{$mod}` | §J-9 |
| 위젯/팝업이 사이트와 다른 고정 색 | 모듈 `:root` 독립 팔레트·크롬 hex | [§J-10](../../module/HOOK_CHECKLIST.md) |
| 미니룸이 프로필 탭에 붙음 | 옷장 옆은 `member_viewer_tools` | §G-6 |
| 옷장 옆 도구가 팝업 대신 일반 링크 | `onclick="..."` 안 `json_encode` 큰따옴표 | §G-6 |
| 액션 선택 버튼 스타일만 없음 | unique ID suffix, CSS 는 옛 `#id` | [§J-11](../../module/HOOK_CHECKLIST.md) |
| 멤버 목록 위젯이 전원 동일 | 루프 `$ch` 미전달 | §J-9 |
| 팝업 AJAX 무반응 | `head.sub` 에 모듈 URL 전역이 없음 | §J-1 |
| `\` 누적 / JSON 빔 | POST 재escape | §G-3 |
| 남의 장비 소모 | AJAX 가 `in_id` 만 | §N |
| 관리자인데 수정·대행이 막힘 | `=== 'super'` 만 봄 (`$is_admin` 이 true/`group`) | [§O](../../module/HOOK_CHECKLIST.md) |
| 가입자가 자기 레벨/포인트를 지정함 | 추가 입력 코드가 코어 컬럼과 겹침 | [§P](../../module/HOOK_CHECKLIST.md) |
| 추가 입력값이 오류 없이 전부 사라짐 | 동적 컬럼 다건을 `@sql_query(..., false)` 한 방에 묶음 | [§P](../../module/HOOK_CHECKLIST.md) |
| 재지급이 남의 아이템을 줌 | `reuse_it_id` 만 믿음 (`maker_ch_id` 미검증) | [§N](../../module/HOOK_CHECKLIST.md) |
| 빈 글이 등록됨 | 게시판 fail 후 롤백 없음 | §F-8 |
