# Avocado 8

아보카도 에디션을 기반으로 만들어진 에디션입니다.  
게시판(MMB)·캐릭터·인벤토리·상점·세계관과 함께, X/Bluesky 스타일 **타임라인**과 **훅 기반 모듈**을 제공합니다.

버전 표기: `config.php` 의 `G5_VERSION` (현재 **아보카도 8**).  
PHP 7.x ~ 8.x 를 전제로 합니다.

---

## 설치

웹 루트에 파일을 올린 뒤 브라우저로 `/install/` 에 접속합니다.  
스키마 정본은 `install/gnuboard5.sql` 이며, 이후 보정은 관리자 `adm/db_check.php` 가 담당합니다.

DB 접속 정보는 설치 후 `data/dbconfig.php` 에 생성됩니다 (커밋 대상 아님).

상세 운영 문서:


| 대상           | 문서                                                                      |
| ------------ | ----------------------------------------------------------------------- |
| 관리자 UI       | `[docs/adm/README.md](docs/adm/README.md)`                              |
| 모듈 사용·제작     | `[docs/module/](docs/module/)` · `[module/README.md](module/README.md)` |
| 테마(부분 오버라이드) | `[docs/theme/](docs/theme/)`                                            |
| 타임라인 구현      | `[timeline/AGENTS.md](timeline/AGENTS.md)`                              |


---

## 디렉터리 구조

```
/
├── adm/            사이트 관리자
├── adm_module/     모듈 전용 관리자
├── ajax/           사이트 공통 AJAX
├── bbs/            게시판·회원 핵심
├── couple/         커플 관계
├── css/            공통·동적 CSS
├── data/           런타임 데이터 (업로드·세션·설정)
├── docs/           운영·제작 문서
├── extend/         코어 확장 라이브러리 (자동 로드)
├── img/            공용 이미지
├── install/        최초 설치·SQL 스키마
├── inventory/      아이템 인벤토리 UI
├── js/             공통 JavaScript
├── lib/            그누보드·에디션 핵심 라이브러리
├── member/         회원란(프로필·뷰어 등)
├── module/         훅 기반 기능 모듈
├── mypage/         마이페이지
├── notice/         공지
├── plugin/         외부 플러그인
├── shop/           상점
├── skin/           스킨(게시판·타임라인·멤버 등)
├── sound/          효과음 등 오디오 자산
├── templete/       루트 템플릿 조각
├── theme/          부분 테마 패키지
├── timeline/       소셜 타임라인
└── world/          세계관
```

### 애플리케이션


| 디렉터리              | 역할                                                                                                           |
| ----------------- | ------------------------------------------------------------------------------------------------------------ |
| `**adm/**`        | 사이트 관리자. 커뮤니티·시기/디자인·회원·게시판·타임라인·세계관·캐릭터·아이템·상점·정산·DB 점검 등. 메뉴 설명은 `[docs/adm/](docs/adm/)`.                 |
| `**adm_module/**` | 모듈 목록·업로드·검증·훅 문서 라우터. URL은 `/adm_module/`. 모듈 관리 UI 안내는 `[docs/adm/000-module.md](docs/adm/000-module.md)`. |
| `**ajax/**`       | 루트 공통 AJAX(통합 알림 폴링 등). 타임라인 AJAX는 `timeline/ajax/` 에 둔다.                                                    |
| `**bbs/**`        | 게시판·로그인·회원가입 등 그누보드 핵심. `basic/`, `mmb/` 로 컨트롤러·템플릿을 나눈다.                                                    |
| `**couple/**`     | 커플(관계) 기능 페이지.                                                                                               |
| `**inventory/**`  | 인벤토리 화면. `extend/` 에 아이템 사용 확장 포인트. 글/댓글 자비사용 코어는 `module/bbs_item_use/`.                                    |
| `**member/**`     | 회원란(프로필·경험치·옷장·뷰어 등). 스킨은 `member/skin/` 또는 테마 슬롯 `member`.                                                  |
| `**mypage/**`     | 로그인 회원 전용. `character/`(캐릭터), `memo/`, `money/`, `log/`, `admin/`(테스트 모드) 등.                                 |
| `**notice/**`     | 공지사항 프론트.                                                                                                    |
| `**shop/**`       | 상점 프론트.                                                                                                      |
| `**timeline/**`   | 소셜 타임라인(글·멘션·DM·설정·AJAX). 함수 라이브러리 `timeline/_functions.php` 는 타임라인·관리자 타임라인 메뉴에서만 로드한다. `extend/` 에 두지 않는다. |
| `**world/**`      | 세계관 문서·메뉴 프론트.                                                                                               |


### 모듈·테마·스킨


| 디렉터리            | 역할                                                                                                                                                                                                                                           |
| --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `**module/**`   | 훅 기반 모듈 엔진(`_loader.php` 등)과 개별 모듈 폴더. 기본 제공 예: `bbs_action_search`, `bbs_action_combine`, `bbs_dice`, `bbs_item_use`. 제작 정본 `[docs/module/AGENTS.md](docs/module/AGENTS.md)`, 함정 점검 `[module/HOOK_CHECKLIST.md](module/HOOK_CHECKLIST.md)`. |
| `**theme/**`    | **부분 오버라이드** 테마 (`theme/{name}/`). 슬롯 단위로 head/menu/member/skin 등을 덮어쓴다. `'default'` 는 가상(루트 파일). 자세한 내용 `[docs/theme/](docs/theme/)`.                                                                                                       |
| `**skin/**`     | 사이트 공용 스킨. `board/`, `timeline/`, `member/`, `world/` 등. 테마 프리셋의 `skin_*` 슬롯으로 테마 쪽 경로를 쓸 수 있다.                                                                                                                                              |
| `**templete/**` | 루트 레벨 작은 템플릿 조각(`txt.*.php` 등).                                                                                                                                                                                                              |


### 코어·부트·자산


| 디렉터리          | 역할                                                                                 |
| ------------- | ---------------------------------------------------------------------------------- |
| `**lib/**`    | 그누보드·에디션 공용 라이브러리 (`common.lib.php`, 권한·공지·콘텐츠 등).                                 |
| `**extend/**` | `common.php` 가 숫자 접두사 순으로 자동 include. 테마·디자인·커뮤니티·캐릭터 등 코어 확장. 타임라인 함수는 여기 두지 않는다. |
| `**css/**`    | 공통 CSS. `root.php` 등으로 디자인 프리셋 변수(`--d-*`)를 동적 출력.                                 |
| `**js/**`     | 사이트 공통 JS.                                                                         |
| `**img/**`    | 공용 정적 이미지.                                                                         |
| `**sound/**`  | 효과음 등 오디오 파일.                                                                      |
| `**plugin/**` | 스마트에디터, HTMLPurifier, jQuery UI 등 외부 패키지.                                          |


### 설치·데이터·문서


| 디렉터리           | 역할                                                                                           |
| -------------- | -------------------------------------------------------------------------------------------- |
| `**install/**` | 최초 설치 UI·스크립트. **시스템 테이블·`timeline_default` 스키마 정본** `gnuboard5.sql`. 테이블/컬럼 변경 시 반드시 함께 수정. |
| `**data/**`    | 런타임 전용. `dbconfig.php`, 세션, 업로드, 캐시, 모듈 `state.json` 등. 배포 시 쓰기 권한 필요. gitignore 대상이 많다.     |
| `**docs/**`    | 사람이 읽는 문서. `adm/`(관리자), `module/`(모듈), `theme/`(테마).                                         |


---

## 루트 주요 파일


| 파일                                      | 역할                           |
| --------------------------------------- | ---------------------------- |
| `index.php` / `enter.php` / `intro.php` | 진입·인트로                       |
| `common.php` / `config.php`             | 전역 부트·상수                     |
| `head.php` · `tail.php` · `*.sub.php`   | HTML 뼈대 (`theme` 슬롯으로 대체 가능) |
| `main.php` / `menu.php`                 | 메인·메뉴                        |
| `bgm.php` / `cdn_css.php`               | BGM·CDN CSS 헬퍼               |


부트 흐름(요약): 페이지 → `_common.php` → `common.php` → `config`·DB·세션 → `extend/*.php` → `inventory/extend` → `module/_loader.php`.

---

## 스키마·모듈 작업 시

- 타임라인 **시스템** 테이블·코어 write 컬럼: `install/gnuboard5.sql` + `timeline/_functions.php` 의 `get_timeline_write_schema()` / `get_timeline_write_indexes()`.
- 모듈 전용 write 컬럼: 해당 모듈 `install.php` 의 `mdl_db_add_write_column()` 만. 코어 스키마에 넣지 않음.
- 프론트/AJAX에서 `CREATE`/`ALTER`/`SHOW COLUMNS` 후 자동 추가 금지. 보정은 `adm/db_check.php`.

자세한 규칙은 `[module/AGENTS.timeline.md](module/AGENTS.timeline.md)` 규칙을 따릅니다.