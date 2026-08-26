# 테마 만들기 (제작자용)

사이트 **테마**는 전체 화면을 통째로 바꾸는 폴더가 아닙니다.  
`theme/이름/` 안에 **바꾸고 싶은 페이지만** 넣으면, 그 부분만 덮어씁니다. 나머지는 사이트 기본 파일을 그대로 씁니다.

색·로고·버튼 느낌은 **디자인 프리셋**(관리자 → 시기/디자인 → 페이지 디자인)이 담당합니다.  
테마는 **레이아웃·HTML·스킨 파일**을 바꿉니다. 둘을 헷갈리지 마세요.

관리자 화면(시기·디자인 프리셋·테마 프리셋): [110 · 시기/디자인 관리](../adm/110-design.md)

---

## 한 줄 요약


| 하고 싶은 일 | 폴더에 넣을 것 | 프리셋에서 고를 슬롯 |
|-------------|----------------|----------------------|
| 멤버란만 새 스타일 | `theme/이름/member/…` | **멤버란** |
| 메뉴만 | `theme/이름/menu.php` (+ CSS) | **메뉴** |
| 헤더·푸터 | `head.php` / `tail.php` / `head.sub.php` / `tail.sub.php` | **기본 세팅** (네 파일 한 묶음) |
| MMB 게시판만 | `theme/이름/skin/board/mmb/…` | **MMB 게시판 스킨** |
| 타임라인 basic 스킨만 | `theme/이름/skin/timeline/basic/…` | **타임라인 기본 스킨** |


**전부 복사할 필요 없습니다.** 멤버란만 배포하려면 `member` 관련 파일만 있으면 됩니다.

---

## 폴더 이름 · 위치

- 위치: 사이트 루트의 `theme/이름/`
- 이름: 영문·숫자·`_`·`-` (예: `member_dark`, `verd`). `default` 는 예약(가상 이름 = “루트 기본 파일”)
- 경로 규칙은 **사이트 루트와 동일**합니다.  
  예: 루트의 `member/skin/viewer.skin.php` → 테마에서는 `theme/이름/member/skin/viewer.skin.php`

참고 패키지: `theme/timeline/`, `theme/verd/` (메뉴·메인·멤버 뷰어 일부만 보유)

---

## 부분 테마 예시: 멤버란만

```
theme/my_member/
├── override_slots.php          ← 권장 (아래 설명)
└── member/
    └── skin/
        ├── viewer.skin.php     ← 프로필 화면
        ├── list.skin.php       ← 목록 (선택)
        ├── relation.skin.php   ← 관계 (선택)
        ├── ready_list.skin.php ← 신청자 목록 (선택)
        └── member.css          ← 스타일 (선택)
```

`override_slots.php` 내용:

```php
<?php
return array('member');
```

### 적용 방법

1. 폴더를 `theme/my_member/` 에 둡니다. (또는 관리자 **테마 업로드**로 `.tar.gz` 설치)
2. **관리자 → 시기/디자인 관리 → 테마 프리셋** 편집
3. 슬롯 **멤버란**만 `my_member` 로 지정, 나머지는 `default`
4. 그 프리셋을 글로벌 기본 또는 **시기**에 연결

테마에 없는 파일(예: `list.skin.php` 를 안 넣음)은 자동으로 사이트 기본 파일을 씁니다.

---

## `override_slots.php` (권장)

이 파일이 있으면 “이 테마가 지원하는 슬롯”을 **명시**합니다.  
관리자 드롭다운에도 그 슬롯만 이 테마가 나타납니다.


| 작성 | 동작 |
|------|------|
| `return array('member');` | 멤버란만 지원 |
| `return array('menu', 'main');` | 메뉴·메인만 |
| `return array('*');` 또는 `array('all');` | 모든 슬롯 지원으로 표시 |
| **파일 없음** | 폴더에 **실제로 있는** 파일/디렉터리만 지원으로 스캔 |


부분 테마를 배포할 때는 파일을 두는 쪽을 권장합니다. 의도가 분명해집니다.

---

## 바꿀 수 있는 영역 (슬롯)

### 페이지 파일


| 슬롯 | 테마 안 경로 | 설명 |
|------|--------------|------|
| 기본 세팅 (frame) | `head.php`, `tail.php`, `head.sub.php`, `tail.sub.php` | 사이트 골격. **네 개는 항상 같은 테마** |
| 메뉴 | `menu.php` | |
| 인덱스 | `index.php` | |
| 인트로 / 메인 / 엔터 | `intro.php`, `main.php`, `enter.php` | |
| 접근 거부 | `warning.php` | |
| 로그인 | `bbs/login.php` | 사이트 닫힘 시 등 |


### 영역 폴더 (하위 파일 포함)


| 슬롯 | 테마 안 경로 |
|------|--------------|
| 마이페이지 | `mypage/…` |
| 멤버란 | `member/…` |
| 상점 | `shop/…` |
| 인벤토리 | `inventory/…` |
| 세계관(기능 쪽) | `world/…` |


### 스킨


| 슬롯 | 테마 안 경로 |
|------|--------------|
| MMB / 기본 / QnA / 정산 게시판 | `skin/board/mmb/` 등 |
| outlogin | `skin/outlogin/basic/` |
| 세계관·문서 basic | `skin/world/basic/`, `skin/content/basic/` |
| 타임라인 basic | `skin/timeline/basic/` |


슬롯 목록의 정본은 `extend/01_theme.lib.php` 의 `$GLOBALS['THEME_SLOT_MAP']` 입니다.

### 지금 테마로 안 바뀌는 것

- 관리자(`/adm/`) 화면
- 그누보드 가입·로그인 스킨(`skin/member/basic/` — 커뮤니티 **멤버란**과 별개)
- `schedule2` 등 슬롯 맵에 없는 게시판 스킨
- **모듈 전용 UI** (예: ORPG `module/k_timeline_orpg/theme/`) — 사이트 `theme/` 과 다른 시스템

---

## 디자인(색) vs 테마(파일)


| | 디자인 프리셋 | 테마 |
|--|---------------|------|
| 바꾸는 것 | CSS 변수(색·버튼·로고 등) | PHP/HTML/CSS 파일 |
| 설정 | 페이지 디자인 설정 | `theme/이름/` + 테마 프리셋 슬롯 |
| 함께 쓰기 | 테마 프리셋에 디자인 프리셋을 연결 | |


테마 CSS는 `theme/이름/css/` 에 두고, 가능하면 하드코딩 URL 대신 헬퍼를 씁니다.

```php
<?php echo theme_asset_link_tag('css/menu.css', 'menu'); ?>
```

다른 테마 폴더명(`theme/timeline/...`)을 경로에 박아 넣지 마세요. 테마를 복제·배포하면 깨집니다.

---

## 멤버 프로필(`viewer.skin.php`)을 덮을 때

모듈이 넣는 **탭·가방·관계·팝업 메뉴**가 사라지지 않게, 코어와 같이 아래를 출력하세요.

- `$viewer_info` — 기본정보 영역 추가 HTML  
- `$viewer_bag` — 가방 영역  
- `$viewer_relation` — 관계 영역  
- `$viewer_extra_tabs` — 추가 탭 버튼·패널  
- `$viewer_tools` — 옷장 옆 팝업 메뉴 (`character_viewer_tools_html()` 등)

레퍼런스: 루트 `member/skin/viewer.skin.php`, `theme/timeline/`, `theme/verd/`

모듈 UI는 테마에서 `include module/...` 하지 말고:

```php
<?php echo get_widget('모듈명', '위젯키', $ch); ?>
```

(기본 제공 모듈에는 위젯이 없을 수 있음. 패키지 README 확인)

---

## 헤더·푸터(기본 세팅) 주의

`head` / `tail` / `head_sub` / `tail_sub` 네 슬롯은 **한 테마로 묶여** 있습니다.  
하나만 바꾸고 나머지는 default 로 두면 레이아웃이 어긋날 수 있습니다.  
프리셋 화면의 **기본 세팅** 드롭다운으로 네 개를 함께 지정하세요.

---

## 업로드 · 배포

- 관리자: 테마 업로드 목록 (`/adm/theme_upload_list.php`) — [110 · 테마 업로드](../adm/110-design.md#테마-업로드)
- 형식: `.tar.gz`, 최상위에 테마 폴더 하나 (또는 마커 파일이 있는 루트)
- 인정 마커 예: `override_slots.php`, `member/`, `skin/`, `menu.php`, `css/` 등

패키지 예 (멤버만):

```
my_member.tar.gz
└── my_member/
    ├── override_slots.php
    └── member/skin/...
```

---

## 체크리스트 (배포 전)

- [ ] 필요한 슬롯 파일만 넣었는가 (불필요한 전체 복사 없음)
- [ ] `override_slots.php` 가 실제 지원 범위와 맞는가
- [ ] 프리셋에서 **해당 슬롯만** 이 테마로 지정했는가
- [ ] 멤버 뷰어면 `$viewer_*` / `$viewer_extra_tabs` / `$viewer_tools` 출력
- [ ] CSS/이미지 URL에 다른 테마 이름 하드코딩 없음
- [ ] frame(헤더·푸터)을 건드리면 네 파일 모두 같은 테마
- [ ] 관리자 또는 `?preset=N`(관리자 로그인)으로 미리보기 확인
