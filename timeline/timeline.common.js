/**
 * 타임라인 공통 JavaScript 함수
 * index.php, profile.php, view.php 등에서 공통으로 사용
 * 
 * 필수 전역 변수 (각 페이지에서 정의 필요):
 * - tlTable: 타임라인 테이블명
 * - myChId: 현재 로그인 캐릭터 ID
 * 
 * 선택적 설정:
 * - tlConfig.isStandalonePage: 독립 페이지 모드 (true면 view.php로 이동, false면 모달)
 * - tlConfig.targetChId: 프로필 페이지용 대상 캐릭터 ID
 * - tlConfig.contentMaxLength: 글자수 제한
 */

// 타임라인 설정 객체
var tlConfig = window.tlConfig || {
    isStandalonePage: false,  // true: view.php 등 독립 페이지, false: 모달 모드
    targetChId: 0,            // 프로필 페이지용 대상 캐릭터 ID
    contentMaxLength: 500,    // 글자수 제한
    focusWrId: 0              // view.php에서 스크롤할 글 ID
};

// 타임라인 상태 변수 (각 페이지에서 초기화 가능)
var currentTab = window.currentTab || 'all';
var lastId = window.lastId || 0;
var isLoading = window.isLoading || false;
var hasMore = window.hasMore !== undefined ? window.hasMore : true;
var lastCheckTime = window.lastCheckTime || Math.floor(Date.now() / 1000);
var newPostPollToken = 0; // 목록 리셋 시 진행 중 폴링 응답을 폐기하기 위한 세대 번호
var currentSearchQuery = ''; // 검색어 상태 변수

function hideNewPostsBar() {
    var newPostsBar = document.getElementById('tl-new-posts-bar');
    if (newPostsBar) newPostsBar.style.display = 'none';
}

function hideMentionBadge() {
    var badge = document.getElementById('tl-mention-badge');
    if (badge) {
        badge.textContent = '';
        badge.style.display = 'none';
    }
}

function invalidateTimelinePoll() {
    newPostPollToken++;
}

/**
 * 새 글 폴링 기준 시각 리셋.
 * 타임라인을 최신으로 다시 받은 뒤에는 그 시각 이전 글을 새 글로 치지 않는다.
 * @param {number} [serverTime] 서버 unix timestamp (없으면 기준 시각은 유지하고 진행 중 폴링만 폐기)
 */
function resetNewPostPolling(serverTime) {
    invalidateTimelinePoll();
    var ts = parseInt(serverTime, 10);
    if (ts > 0) {
        lastCheckTime = ts;
    }
    hideNewPostsBar();
}

/**
 * 글 ⋮ 메뉴 (프로필 등 write.skin 미포함 페이지에서도 동작)
 */
function toggleMenu(btn) {
    if (!btn) return;
    var dropdown = btn.nextElementSibling;
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.closest || (!e.target.closest('.tl-menu') && !e.target.closest('.tl-composer-avatar-switch'))) {
        document.querySelectorAll('.tl-dropdown.show').forEach(function(d) {
            d.classList.remove('show');
        });
    }
    var panelBtn = e.target.closest ? e.target.closest('.tl-composer-extra-icons [data-tl-panel]') : null;
    if (panelBtn) {
        e.preventDefault();
        toggleComposerPanel(panelBtn.getAttribute('data-tl-panel'));
    }
});

function toggleComposerPanel(panelId) {
    if (!panelId) return;
    var area = document.getElementById(panelId);
    if (!area) return;
    area.style.display = (window.getComputedStyle(area).display === 'none') ? 'block' : 'none';
}

/**
 * 타임라인 계정 전환 (세션). 성공 시 새로고침하여 컴포저/모듈을 새 캐릭터 기준으로 다시 그림.
 */
function switchTimelineAccount(chId) {
    chId = parseInt(chId, 10) || 0;
    if (!chId || typeof tlTable === 'undefined' || !tlTable) return;
    if (typeof myChId !== 'undefined' && chId === parseInt(myChId, 10)) return;
    if (window._tlSwitchingAccount) return;
    window._tlSwitchingAccount = true;

    fetch('./ajax/ajax_switch_account.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        credentials: 'same-origin',
        body: 'tl_table=' + encodeURIComponent(tlTable) + '&ch_id=' + chId
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data && data.success) {
            window.location.reload();
            return;
        }
        window._tlSwitchingAccount = false;
        alert((data && data.message) ? data.message : '계정 전환에 실패했습니다.');
    })
    .catch(function() {
        window._tlSwitchingAccount = false;
        alert('계정 전환에 실패했습니다.');
    });
}

// body 스크롤 잠금 (overflow 토글 시 스크롤이 맨 위로 튀는 것 방지)
var tlBodyScrollLockCount = 0;
var tlBodyScrollY = 0;

function tlLockBodyScroll() {
    if (tlBodyScrollLockCount === 0) {
        tlBodyScrollY = window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
        document.body.style.overflow = 'hidden';
        // iOS/일부 브라우저: overflow만으로는 스크롤이 움직이므로 position fixed로 고정
        document.body.style.position = 'fixed';
        document.body.style.top = '-' + tlBodyScrollY + 'px';
        document.body.style.left = '0';
        document.body.style.right = '0';
        document.body.style.width = '100%';
    }
    tlBodyScrollLockCount++;
}

function tlUnlockBodyScroll() {
    if (tlBodyScrollLockCount <= 0) return;
    tlBodyScrollLockCount--;
    if (tlBodyScrollLockCount > 0) return;

    document.body.style.overflow = '';
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.left = '';
    document.body.style.right = '';
    document.body.style.width = '';
    window.scrollTo(0, tlBodyScrollY);
}

// ============================================
// 타임라인 로드 함수
// ============================================

/**
 * 타임라인 목록 로드
 * @param {boolean} reset - true면 처음부터 다시 로드
 */
function loadTimeline(reset) {
    if (isLoading) return;
    if (!hasMore && !reset) return;
    
    isLoading = true;
    
    var listEl = document.getElementById('tl-list');
    var loadMoreEl = document.getElementById('tl-load-more');
    var endEl = document.getElementById('tl-end');
    
    if (reset) {
        lastId = 0;
        hasMore = true;
        // 목록을 최신으로 다시 받는 동안/직후, 이전 last_check 기준 폴링이 알림을 다시 띄우지 않게 함
        resetNewPostPolling();
        if (listEl) listEl.innerHTML = '<div class="tl-loading">로딩 중...</div>';
        if (endEl) endEl.style.display = 'none';
    } else {
        if (loadMoreEl) loadMoreEl.style.display = 'block';
    }
    
    var url = './ajax/ajax_load.php?tl_table=' + tlTable + '&tab=' + currentTab + '&last_id=' + lastId;
    
    // 목록 data-tab이 있으면 우선 (프로필 퍼블릭 등 페이지 탭과 불일치 방지)
    if (listEl) {
        var dataTab = listEl.getAttribute('data-tab');
        if (dataTab) {
            currentTab = dataTab;
            url = './ajax/ajax_load.php?tl_table=' + tlTable + '&tab=' + encodeURIComponent(dataTab) + '&last_id=' + lastId;
        }
    }
    
    // 프로필 페이지용 대상 캐릭터 ID
    if (tlConfig.targetChId > 0) {
        url += '&ch_id=' + tlConfig.targetChId;
    }
    
    // 검색어
    if (currentSearchQuery) {
        url += '&stx=' + encodeURIComponent(currentSearchQuery);
    }
    
    fetch(url)
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (reset && listEl) {
            listEl.innerHTML = '';
        }

        // 서버 시각으로 폴링 기준을 맞춤 (클라이언트 Date.now()와 어긋나면 방금 로드한 글이 새 글로 잡힘)
        if (reset) {
            resetNewPostPolling(data.server_time);
        }
        
        if (data.html && listEl) {
            listEl.insertAdjacentHTML('beforeend', data.html);
        }
        
        if (data.last_id) {
            lastId = data.last_id;
        }
        
        if (!data.has_more) {
            hasMore = false;
        }
        
        if (reset && !data.html && listEl) {
            listEl.innerHTML = '<div class="tl-empty">글이 없습니다.</div>';
        }
        
        if (loadMoreEl) loadMoreEl.style.display = 'none';
        isLoading = false;

        // 멘션 탭 첫 로드: 해당 타임라인 알람 팝업/소리 중지 (서버에서 읽음 처리됨)
        if (reset && currentTab === 'mentions' && typeof window.stopCallAlarm === 'function') {
            window.stopCallAlarm(true);
        }

        applyUnrepliedFilter();
    })
    .catch(function(err) {
        console.error('타임라인 로드 오류:', err);
        if (loadMoreEl) loadMoreEl.style.display = 'none';
        isLoading = false;
    });
}

/**
 * 새 글 로드 (새 글 알림 바 클릭 시)
 */
function loadNewPosts() {
    loadTimeline(true);
}

/**
 * 새 글 폴링 시작 (간격: tlConfig.pollInterval, 기본 10000ms)
 */
function startPolling() {
    setInterval(function() {
        if (isLoading) return;

        var token = newPostPollToken;
        var checkAt = lastCheckTime;

        fetch('./ajax/ajax_check_new.php?tl_table=' + tlTable + '&last_check=' + checkAt + '&tab=' + currentTab)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            // 목록 리셋/멘션 읽음 이후의 오래된 폴링 응답은 쓰지 않음
            if (token !== newPostPollToken || checkAt !== lastCheckTime || isLoading) return;

            var badge = document.getElementById('tl-mention-badge');
            if (badge) {
                var mentionCount = parseInt(data.mention_count, 10) || 0;
                if (mentionCount > 0 && currentTab !== 'mentions') {
                    badge.textContent = mentionCount;
                    badge.style.display = 'inline-block';
                } else {
                    badge.textContent = '';
                    badge.style.display = 'none';
                }
            }

            if (data.new_count > 0 && currentTab !== 'mentions' && currentTab !== 'bookmarks') {
                var newCountEl = document.getElementById('tl-new-count');
                var newPostsBar = document.getElementById('tl-new-posts-bar');
                if (newCountEl) newCountEl.textContent = data.new_count;
                if (newPostsBar) newPostsBar.style.display = 'block';
            }
        })
        .catch(function(err) {
            console.error('폴링 오류:', err);
        });
    }, (window.tlConfig && parseInt(tlConfig.pollInterval, 10) > 0) ? parseInt(tlConfig.pollInterval, 10) : 10000);
}

/**
 * 타임라인 리스트의 댓글 수 갱신
 * @param {number} wrId - 글 ID
 */
function updatePostCommentCount(wrId) {
    var postItem = document.querySelector('.tl-item[data-id="' + wrId + '"]');
    if (!postItem) return;
    
    var replyBtn = postItem.querySelector('.tl-action.reply .tl-action-count');
    if (replyBtn) {
        var currentCount = parseInt(replyBtn.textContent) || 0;
        replyBtn.textContent = currentCount + 1;
    }
}

// ============================================
// 이미지 미리보기 / 드래그 앤 드롭
// ============================================

// 메인 글쓰기 폼용 선택된 파일 배열
var selectedFiles = [];

/**
 * 이미지 미리보기 (input file 선택 시) - 단일 이미지용 (레거시)
 * @param {HTMLInputElement} input - 파일 입력 요소
 */
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var previewImg = document.getElementById('tl-preview-img');
            var previewContainer = document.getElementById('tl-image-preview');
            var submitBtn = document.getElementById('tl-submit');
            if (previewImg) previewImg.src = e.target.result;
            if (previewContainer) previewContainer.style.display = 'block';
            if (submitBtn) submitBtn.disabled = false;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * 복수 이미지 미리보기 (input file 선택 시)
 * @param {HTMLInputElement} input - 파일 입력 요소
 * @param {number} maxCount - 최대 이미지 수
 */
function previewImages(input, maxCount) {
    if (!input.files) return;
    
    var files = Array.from(input.files);
    var remaining = maxCount - selectedFiles.length;
    
    if (files.length > remaining) {
        alert('최대 ' + maxCount + '장까지 업로드할 수 있습니다.');
        files = files.slice(0, remaining);
    }
    
    files.forEach(function(file) {
        if (file.type.startsWith('image/')) {
            selectedFiles.push(file);
        }
    });
    
    updateImagePreview();
    input.value = ''; // 같은 파일 재선택 가능하도록
}

/**
 * 이미지 미리보기 업데이트 (메인 글쓰기 폼)
 */
function updateImagePreview() {
    var previewContainer = document.getElementById('tl-preview-container');
    var previewWrapper = document.getElementById('tl-image-preview');
    var countEl = document.getElementById('tl-image-count');
    var submitBtn = document.getElementById('tl-submit');
    var content = document.getElementById('tl-content');
    
    if (!previewContainer) return;
    
    previewContainer.innerHTML = '';
    
    // 이미지 카운트 표시
    if (countEl) {
        countEl.textContent = selectedFiles.length > 0 ? selectedFiles.length : '';
    }
    
    // 미리보기 영역 표시/숨김
    if (previewWrapper) {
        previewWrapper.style.display = selectedFiles.length > 0 ? 'block' : 'none';
    }
    
    // 제출 버튼 활성화
    if (submitBtn) {
        var hasContent = content && content.value.length > 0;
        submitBtn.disabled = !hasContent && selectedFiles.length === 0;
    }
    
    selectedFiles.forEach(function(file, index) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var div = document.createElement('div');
            div.className = 'tl-preview-item';
            div.innerHTML = '<img src="' + e.target.result + '" alt="">' +
                '<button type="button" class="tl-remove-image" onclick="removeImageAt(' + index + ')">&times;</button>';
            previewContainer.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

/**
 * 특정 인덱스의 이미지 제거
 * @param {number} index - 제거할 이미지 인덱스
 */
function removeImageAt(index) {
    selectedFiles.splice(index, 1);
    updateImagePreview();
}

/**
 * 이미지 제거 (레거시 - 단일 이미지)
 */
function removeImage() {
    var imageInput = document.getElementById('tl-image');
    var previewContainer = document.getElementById('tl-image-preview');
    var content = document.getElementById('tl-content');
    var submitBtn = document.getElementById('tl-submit');
    
    // 복수 이미지 모드인 경우
    if (selectedFiles.length > 0) {
        selectedFiles = [];
        updateImagePreview();
        return;
    }
    
    // 단일 이미지 모드
    if (imageInput) imageInput.value = '';
    if (previewContainer) previewContainer.style.display = 'none';
    if (submitBtn) submitBtn.disabled = !content || content.value.length === 0;
}

/**
 * 드래그 앤 드롭 초기화
 */
function initDragDrop() {
    var composer = document.querySelector('.tl-composer-form');
    if (!composer) return;
    
    // 폼 전체에 드래그 이벤트
    composer.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        composer.classList.add('dragover');
    });
    
    composer.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        // 자식 요소로 이동할 때는 무시
        if (e.relatedTarget && composer.contains(e.relatedTarget)) return;
        composer.classList.remove('dragover');
    });
    
    composer.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        composer.classList.remove('dragover');
        
        if (e.dataTransfer.files.length) {
            var files = Array.from(e.dataTransfer.files);
            var maxCount = 4;
            
            // 복수 이미지 input이 있는지 확인
            var multiInput = document.getElementById('tl-images');
            if (multiInput) {
                // 복수 이미지 모드
                var remaining = maxCount - selectedFiles.length;
                files.forEach(function(file) {
                    if (file.type.startsWith('image/') && selectedFiles.length < maxCount) {
                        selectedFiles.push(file);
                    }
                });
                updateImagePreview();
            } else {
                // 단일 이미지 모드
                var file = files[0];
                if (file && file.type.startsWith('image/')) {
                    var input = document.getElementById('tl-image');
                    if (input) {
                        var dt = new DataTransfer();
                        dt.items.add(file);
                        input.files = dt.files;
                        previewImage(input);
                    }
                }
            }
        }
    });
    
    // textarea에도 드래그 이벤트 처리 (textarea가 기본 동작 방지)
    var textarea = composer.querySelector('textarea');
    if (textarea) {
        textarea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
        textarea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            // 부모 폼의 drop 이벤트 트리거
            composer.dispatchEvent(new DragEvent('drop', {
                dataTransfer: e.dataTransfer,
                bubbles: true
            }));
        });
    }
}

// ============================================
// 글 작성 함수
// ============================================

/**
 * 모듈 액션 패널 값(낚시 맵 등)을 FormData에 붙인다.
 * submitPost 는 FormData를 수동 구성하므로 select/checkbox 가 빠지지 않게 한다.
 */
function appendTimelineFormFields(formData, form) {
    if (!formData || !form) return;
    var els = form.querySelectorAll('input[name], select[name], textarea[name]');
    for (var i = 0; i < els.length; i++) {
        var el = els[i];
        var name = el.name;
        if (!name) continue;
        var type = (el.type || '').toLowerCase();
        if (type === 'file') {
            if (name === 'images[]' || name === 'image') continue;
            if (el.disabled) continue;
            if (el.files && el.files.length) {
                for (var fi = 0; fi < el.files.length; fi++) {
                    formData.append(name, el.files[fi]);
                }
            }
            continue;
        }
        if (type === 'submit' || type === 'button' || type === 'reset' || type === 'image') continue;
        if (el.disabled) continue;
        if (el.tagName === 'SELECT' && el.multiple) {
            for (var oi = 0; oi < el.options.length; oi++) {
                if (el.options[oi].selected && el.options[oi].value !== '') {
                    formData.append(name, String(el.options[oi].value));
                }
            }
            continue;
        }
        if ((type === 'checkbox' || type === 'radio') && !el.checked) continue;
        if (name === 'content' || name === 'images[]' || name === 'image' || name === 'parent_id') continue;
        if (typeof formData.has === 'function' && formData.has(name) && type !== 'checkbox') continue;
        formData.append(name, el.value == null ? '' : String(el.value));
    }
}

function ensureTimelineFishingMap(form) {
    if (!form) return;
    var action = form.querySelector('select[name="action"]');
    if (!action || action.value !== 'FISHING') return;
    var mapSel = form.querySelector('select[name="fishing_map"]');
    if (!mapSel || mapSel.value) return;
    var found = [];
    for (var i = 0; i < mapSel.options.length; i++) {
        var opt = mapSel.options[i];
        if (opt.disabled || !opt.value) continue;
        found.push(opt.value);
    }
    if (found.length === 1) {
        mapSel.value = found[0];
    }
}

/**
 * 글 작성
 * @param {number} parentId - 답글인 경우 원글 ID
 */
function submitPost(parentId) {
    if (tlSubmitLock) return;

    var contentEl = document.getElementById('tl-content');
    if (!contentEl) return;

    // FormData 구성 전부터 버튼 잠금 (더블클릭 방지)
    var submitBtnEarly = document.getElementById('tl-submit');
    if (submitBtnEarly) submitBtnEarly.disabled = true;
    
    var formData = new FormData();
    formData.append('content', contentEl.value);
    if (parentId) formData.append('parent_id', parentId);
    
    // 복수 이미지 처리 - selectedFiles 배열 사용 (드래그앤드롭 지원)
    if (selectedFiles && selectedFiles.length > 0) {
        selectedFiles.forEach(function(file) {
            formData.append('images[]', file);
        });
    } else {
        // input에서 직접 파일 가져오기 (레거시 지원)
        var imagesInput = document.getElementById('tl-images');
        if (imagesInput && imagesInput.files && imagesInput.files.length) {
            for (var i = 0; i < imagesInput.files.length; i++) {
                formData.append('images[]', imagesInput.files[i]);
            }
        } else {
            // 단일 이미지 하위 호환
            var imageInput = document.getElementById('tl-image');
            if (imageInput && imageInput.files && imageInput.files.length) {
                formData.append('image', imageInput.files[0]);
            }
        }
    }
    
    // 액션/게임 파라미터 (MMB 호환) — select[name=action] / #tl-action 둘 다 지원
    var actionSelect = document.querySelector('#tl-form select[name="action"]') || document.getElementById('action');
    var actionInput = document.getElementById('tl-action');
    var gameInput = document.getElementById('tl-game');
    var useItemInput = document.getElementById('use_item');
    var actionVal = '';
    if (actionSelect && actionSelect.value) actionVal = actionSelect.value;
    else if (actionInput && actionInput.value) actionVal = actionInput.value;
    
    if (actionVal) formData.append('action', actionVal);
    if (gameInput && gameInput.value) formData.append('game', gameInput.value);

    // 아이템 사용/선물
    var itemModeEl = document.querySelector('#tl-form input[name="item_mode"]:checked');
    var itemMode = itemModeEl ? itemModeEl.value : 'use';
    formData.append('item_mode', itemMode);
    if (itemMode === 'gift') {
        var giftItem = document.getElementById('gift_item');
        var giftTo = document.getElementById('gift_to_ch_id');
        if (giftItem && giftItem.value) formData.append('gift_item', giftItem.value);
        if (giftTo && giftTo.value) formData.append('gift_to_ch_id', giftTo.value);
    } else if (useItemInput && useItemInput.value) {
        formData.append('use_item', useItemInput.value);
    }
    
    // 주사위 체크 (bbs_dice 모듈)
    var wrDice = document.querySelector('#tl-form input[name="wr_dice"]');
    if (wrDice && wrDice.checked) formData.append('wr_dice', wrDice.value || '1');

    // 이미지 접기 / 글 가리기
    var wrImgHidden = document.querySelector('#tl-form input[name="wr_img_hidden"]');
    if (wrImgHidden && wrImgHidden.checked) formData.append('wr_img_hidden', '1');
    var wrTextHidden = document.querySelector('#tl-form input[name="wr_text_hidden"]');
    if (wrTextHidden && wrTextHidden.checked) formData.append('wr_text_hidden', '1');
    var wrWaringText = document.querySelector('#tl-form input[name="wr_waring_text"]');
    if (wrWaringText && wrWaringText.value) formData.append('wr_waring_text', wrWaringText.value);
    
    // 조합 재료
    var make1 = document.getElementById('make_1');
    var make2 = document.getElementById('make_2');
    var make3 = document.getElementById('make_3');
    if (make1 && make1.value) formData.append('make_1', make1.value);
    if (make2 && make2.value) formData.append('make_2', make2.value);
    if (make3 && make3.value) formData.append('make_3', make3.value);

    var tlForm = document.getElementById('tl-form');
    ensureTimelineFishingMap(tlForm);
    appendTimelineFormFields(formData, tlForm);
    
    submitTimelineContent({
        formData: formData,
        endpoint: './ajax/ajax_post.php',
        submitBtn: document.getElementById('tl-submit'),
        onSuccess: function(data) {
            resetWriteComposerLocal();

            // 본문은 유지하지 않고, 아이템/액션(낚시 횟수·선물 목록)만 서버 기준으로 패치
            if (typeof reloadComposerFields === 'function') {
                reloadComposerFields();
            }

            // 서버 시각으로 폴링 기준을 맞춘 뒤 목록을 최신으로 다시 받음
            resetNewPostPolling(data && data.server_time);
            loadTimeline(true);
        }
    });
}

/**
 * 글자수 카운트 초기화 (글쓰기 폼용)
 */
function initCharCount() {
    var tlContent = document.getElementById('tl-content');
    if (!tlContent) return;
    
    tlContent.addEventListener('input', function() {
        var len = this.value.length;
        var charCount = document.getElementById('tl-char-count');
        if (charCount) charCount.textContent = len + '/' + tlConfig.contentMaxLength;
        
        var imageInput = document.getElementById('tl-image');
        var hasImage = imageInput && imageInput.files && imageInput.files.length;
        var submitBtn = document.getElementById('tl-submit');
        if (submitBtn) submitBtn.disabled = len === 0 && !hasImage;
    });
}

/**
 * 글쓰기 폼 초기화
 */
function initComposerForm() {
    var tlForm = document.getElementById('tl-form');
    if (tlForm) {
        tlForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (tlSubmitLock) return false;
            submitPost();
            return false;
        });
    }
    
    initCharCount();
    initDragDrop();
}

function tlParseJsonPayload(text) {
    var raw = (text || '').replace(/^\uFEFF/, '');
    try {
        return JSON.parse(raw);
    } catch (e1) {
        var start = raw.indexOf('{');
        var end = raw.lastIndexOf('}');
        if (start < 0 || end <= start) throw e1;
        return JSON.parse(raw.substring(start, end + 1));
    }
}

function tlAreaIsOpen(id) {
    var el = document.getElementById(id);
    if (!el) return false;
    return window.getComputedStyle(el).display !== 'none';
}

function tlSyncWriteSubmitState() {
    var submitBtn = document.getElementById('tl-submit');
    if (!submitBtn) return;
    var content = document.getElementById('tl-content');
    var hasContent = !!(content && content.value && content.value.length > 0);
    var hasImage = (typeof selectedFiles !== 'undefined' && selectedFiles.length > 0);
    submitBtn.disabled = !hasContent && !hasImage;
}

/**
 * 원글 게시 성공 후 본문·이미지·체크만 비운다. 아이템/액션 HTML은 reloadComposerFields 가 패치한다.
 */
function resetWriteComposerLocal() {
    var form = document.getElementById('tl-form');
    var contentEl = document.getElementById('tl-content');
    if (contentEl) contentEl.value = '';

    var max = (typeof tlConfig !== 'undefined' && tlConfig.contentMaxLength) ? tlConfig.contentMaxLength : 500;
    var charCount = document.getElementById('tl-char-count');
    if (charCount) charCount.textContent = '0/' + max;

    if (typeof selectedFiles !== 'undefined') selectedFiles = [];
    var imagesInput = document.getElementById('tl-images');
    if (imagesInput) imagesInput.value = '';
    var singleImage = document.getElementById('tl-image');
    if (singleImage) singleImage.value = '';
    if (typeof updateImagePreview === 'function') updateImagePreview();

    if (form) {
        var checks = form.querySelectorAll('input[type="checkbox"]');
        for (var i = 0; i < checks.length; i++) checks[i].checked = false;
        var warningInput = form.querySelector('input[name="wr_waring_text"]');
        if (warningInput) warningInput.value = '';
        var warningWrap = document.getElementById('tl-warning-text-wrap');
        if (warningWrap) warningWrap.style.display = 'none';

        var actionSel = form.querySelector('select[name="action"]');
        if (actionSel) {
            actionSel.value = '';
            if (typeof actionSel.dispatchEvent === 'function') {
                actionSel.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    }

    tlSyncWriteSubmitState();
    setTimeout(tlSyncWriteSubmitState, 0);
}

function tlSetHtmlWithScripts(el, html) {
    if (!el) return;
    el.innerHTML = html;
    var scripts = el.querySelectorAll('script');
    for (var i = 0; i < scripts.length; i++) {
        var old = scripts[i];
        var s = document.createElement('script');
        if (old.src) {
            s.src = old.src;
        } else {
            s.textContent = old.textContent;
        }
        if (old.parentNode) {
            old.parentNode.replaceChild(s, old);
        }
    }
}

function tlPatchActionSelect(root, actionOptions, skipDispatch) {
    if (!root || typeof actionOptions !== 'string') return;
    var sel = root.querySelector('select[name="action"]');
    if (!sel) return;
    var prev = sel.value || '';
    sel.innerHTML = '<option value="">일반행동</option>' + actionOptions;
    if (prev) {
        var keep = false;
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value === prev && !sel.options[i].disabled) {
                keep = true;
                break;
            }
        }
        if (keep) sel.value = prev;
        else sel.value = '';
    }
    var chosen = sel.options[sel.selectedIndex];
    if (chosen && chosen.disabled) sel.value = '';
    if (!skipDispatch && typeof sel.dispatchEvent === 'function') {
        sel.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

function tlPatchActionPanels(actionArea, panelsHtml, keepOpen) {
    if (!actionArea) return;
    var oldPanels = actionArea.querySelectorAll('.comment-data');
    for (var i = 0; i < oldPanels.length; i++) {
        if (oldPanels[i].parentNode) oldPanels[i].parentNode.removeChild(oldPanels[i]);
    }
    if (typeof panelsHtml === 'string' && panelsHtml !== '') {
        var wrap = document.createElement('div');
        wrap.innerHTML = panelsHtml;
        while (wrap.firstChild) {
            actionArea.appendChild(wrap.firstChild);
        }
    }
    if (keepOpen) actionArea.style.display = 'block';
}

function tlEnsureItemArea(root, itemId, itemClass) {
    var itemArea = document.getElementById(itemId);
    if (itemArea) return itemArea;
    if (!root) return null;
    itemArea = document.createElement('div');
    itemArea.className = itemClass || 'tl-item-area';
    itemArea.id = itemId;
    var extra = root.querySelector('.tl-composer-extra-panels');
    var actions = root.querySelector('.tl-composer-actions');
    if (extra && extra.parentNode === root) {
        root.insertBefore(itemArea, extra);
    } else if (actions && actions.parentNode === root) {
        root.insertBefore(itemArea, actions);
    } else {
        root.appendChild(itemArea);
    }
    return itemArea;
}

function tlEnsureItemToggle(root, toggleJs) {
    if (!root) return;
    var left = root.querySelector('.tl-composer-left');
    if (!left) return;
    if (left.querySelector('.tl-item-toggle')) return;
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'tl-item-toggle tl-btns';
    btn.title = '아이템';
    btn.setAttribute('onclick', toggleJs || 'toggleItemArea()');
    btn.innerHTML = '<i class="material-icons" aria-hidden="true">backpack</i>';
    var extraIcons = left.querySelector('.tl-composer-extra-icons');
    if (extraIcons) {
        left.insertBefore(btn, extraIcons);
    } else {
        left.appendChild(btn);
    }
}

function applyComposerInventory(data, opts) {
    opts = opts || {};
    var mode = opts.mode || 'write';
    var root;
    var actionId = 'tl-action-area';
    var itemId = 'tl-item-area';
    var itemClass = 'tl-item-area';
    var toggleJs = 'toggleItemArea()';
    if (mode === 'reply') {
        var pfx = 'ir_' + (opts.replyFormId || '');
        var form = document.getElementById('tl_reply_' + (opts.replyFormId || ''));
        root = form ? form.querySelector('.tl-inline-reply-form') : null;
        actionId = pfx + '_action_area';
        itemId = pfx + '_item_area';
        itemClass = 'tl-item-area tl-inline-item-area';
        toggleJs = 'irToggleItem(\'' + pfx + '\')';
    } else {
        root = document.getElementById('tl-form');
    }
    if (!root) return false;

    var keepAction = tlAreaIsOpen(actionId);
    var keepItem = tlAreaIsOpen(itemId);

    tlPatchActionSelect(root, data.action_options, true);
    tlPatchActionPanels(document.getElementById(actionId), data.action_panels, keepAction);
    var actionSel = root.querySelector('select[name="action"]');
    if (actionSel && typeof actionSel.dispatchEvent === 'function') {
        actionSel.dispatchEvent(new Event('change', { bubbles: true }));
    }

    var itemHtml = (typeof data.item_html === 'string') ? data.item_html : '';
    var itemArea = document.getElementById(itemId);
    if (itemHtml !== '') {
        itemArea = tlEnsureItemArea(root, itemId, itemClass);
        tlEnsureItemToggle(root, toggleJs);
    }
    if (itemArea) {
        tlSetHtmlWithScripts(itemArea, itemHtml);
        if (keepItem && itemHtml !== '') itemArea.style.display = 'block';
    }

    var extraIconsId = (mode === 'reply') ? pfx + '_composer_extra_icons' : 'tl-composer-extra-icons';
    var extraPanelsId = (mode === 'reply') ? pfx + '_composer_extra_panels' : 'tl-composer-extra-panels';
    var extraPanels = document.getElementById(extraPanelsId);
    var keepExtra = [];
    if (extraPanels) {
        for (var ei = 0; ei < extraPanels.children.length; ei++) {
            var extraChild = extraPanels.children[ei];
            if (extraChild.id && tlAreaIsOpen(extraChild.id)) keepExtra.push(extraChild.id);
        }
    }
    var extraIcons = document.getElementById(extraIconsId);
    if (extraIcons && typeof data.composer_icons === 'string') {
        extraIcons.innerHTML = data.composer_icons;
    }
    if (extraPanels && typeof data.composer_panels === 'string') {
        tlSetHtmlWithScripts(extraPanels, data.composer_panels);
        for (var ej = 0; ej < keepExtra.length; ej++) {
            var openExtra = document.getElementById(keepExtra[ej]);
            if (openExtra) openExtra.style.display = 'block';
        }
    }

    return true;
}

/**
 * 컴포저 필드 AJAX 새로고침 (아이템/조합 재료·낚시 횟수 등 서버 상태 반영)
 * 기본은 아이템·액션 영역만 패치해서 본문 초안을 지우지 않는다.
 * @param {Object} [opts]
 * @param {string} [opts.mode=write] write|reply
 * @param {boolean} [opts.replaceAll] true면 폼 전체 HTML 교체
 * @param {number} [opts.replyFormId]
 * @param {number} [opts.parentId]
 * @param {Function} [opts.onDone]
 */
function reloadComposerFields(opts) {
    opts = opts || {};
    var mode = opts.mode || 'write';
    var replaceAll = !!opts.replaceAll;
    var url = './ajax/ajax_composer_fields.php?tl_table=' + encodeURIComponent(tlTable)
        + '&mode=' + encodeURIComponent(mode)
        + '&_=' + Date.now();
    if (!replaceAll) {
        url += '&fragment=inventory';
    }
    if (mode === 'reply') {
        if (opts.replyFormId) url += '&reply_form_id=' + encodeURIComponent(opts.replyFormId);
        if (opts.parentId) url += '&parent_id=' + encodeURIComponent(opts.parentId);
    }

    return fetch(url, { credentials: 'same-origin', cache: 'no-store' })
        .then(function(res) { return res.text(); })
        .then(function(text) {
            var data = tlParseJsonPayload(text);
            if (!data || !data.success) {
                if (typeof opts.onDone === 'function') opts.onDone(false);
                return false;
            }

            if (!replaceAll) {
                var ok = applyComposerInventory(data, opts);
                if (typeof opts.onDone === 'function') opts.onDone(ok);
                return ok;
            }

            if (typeof data.html !== 'string') {
                if (typeof opts.onDone === 'function') opts.onDone(false);
                return false;
            }

            var actionId = 'tl-action-area';
            var itemId = 'tl-item-area';
            if (mode === 'reply') {
                var pfx = 'ir_' + (opts.replyFormId || '');
                actionId = pfx + '_action_area';
                itemId = pfx + '_item_area';
            }
            var keepAction = tlAreaIsOpen(actionId);
            var keepItem = tlAreaIsOpen(itemId);

            if (mode === 'reply') {
                var prefix = 'ir_' + (opts.replyFormId || '');
                var form = document.getElementById('tl_reply_' + (opts.replyFormId || ''));
                var wrap = form ? form.querySelector('.tl-inline-reply-form') : null;
                if (!wrap) {
                    if (typeof opts.onDone === 'function') opts.onDone(false);
                    return false;
                }
                tlSetHtmlWithScripts(wrap, data.html);
                if (typeof initMentionSearchForReply === 'function') {
                    initMentionSearchForReply(prefix);
                }
                if (typeof loadTimelineTemp === 'function') {
                    loadTimelineTemp(form);
                }
            } else {
                var tlForm = document.getElementById('tl-form');
                if (!tlForm) {
                    if (typeof opts.onDone === 'function') opts.onDone(false);
                    return false;
                }
                tlSetHtmlWithScripts(tlForm, data.html);
                if (typeof selectedFiles !== 'undefined') selectedFiles = [];
                initCharCount();
                if (typeof initMentionSearch === 'function') initMentionSearch();
                var mainTa = document.getElementById('tl-content');
                if (mainTa && typeof initMentionAutocomplete === 'function') {
                    initMentionAutocomplete(mainTa);
                }
            }

            if (keepAction) {
                var actionEl = document.getElementById(actionId);
                if (actionEl) actionEl.style.display = 'block';
            }
            if (keepItem) {
                var itemEl = document.getElementById(itemId);
                if (itemEl) itemEl.style.display = 'block';
            }

            if (typeof opts.onDone === 'function') opts.onDone(true);
            return true;
        })
        .catch(function(err) {
            console.error('[Timeline] 컴포저 필드 새로고침 실패:', err);
            if (typeof opts.onDone === 'function') opts.onDone(false);
            return false;
        });
}

// ============================================
// 무한 스크롤
// ============================================

function initInfiniteScroll() {
    window.addEventListener('scroll', function() {
        if (isLoading || !hasMore) return;

        var docEl = document.documentElement;
        var scrollTop = window.scrollY || window.pageYOffset || docEl.scrollTop || 0;
        var viewport = window.innerHeight || docEl.clientHeight || 0;
        var docHeight = Math.max(
            document.body.scrollHeight || 0,
            docEl.scrollHeight || 0,
            document.body.offsetHeight || 0,
            docEl.offsetHeight || 0
        );

        // 스크롤이 문서 맨 아래에 닿았을 때만 다음 페이지 로드
        if (scrollTop + viewport >= docHeight - 1) {
            loadTimeline();
        }
    });
}

// ============================================
// 멘션 탭 미답장 필터
// ============================================

var TL_UNREPLIED_STORAGE_KEY = 'tlUnreplied';

function getUnrepliedStorageKey() {
    return TL_UNREPLIED_STORAGE_KEY + ':' + (typeof tlTable !== 'undefined' ? tlTable : '');
}

function isUnrepliedFilterOn() {
    var sw = document.getElementById('tl-unreplied-switch');
    return !!(sw && sw.checked);
}

function initUnrepliedFilter() {
    var sw = document.getElementById('tl-unreplied-switch');
    if (!sw) return;

    try {
        if (sessionStorage.getItem(getUnrepliedStorageKey()) === '1') {
            sw.checked = true;
        }
    } catch (e) {}

    sw.addEventListener('change', function() {
        try {
            sessionStorage.setItem(getUnrepliedStorageKey(), sw.checked ? '1' : '0');
        } catch (e) {}
        applyUnrepliedFilter();
    });
}

function setMentionEntryHidden(item, hide) {
    if (!item) return;
    item.classList.toggle('tl-unreplied-hidden', hide);

    var prev = item.previousElementSibling;
    if (prev && prev.classList.contains('tl-thread-connector')) {
        prev.classList.toggle('tl-unreplied-hidden', hide);
        var origin = prev.previousElementSibling;
        if (origin && origin.classList.contains('tl-item-origin')) {
            origin.classList.toggle('tl-unreplied-hidden', hide);
        }
    }
}

function getVisibleMentionItems(listEl) {
    var items = listEl.querySelectorAll('.tl-item:not(.tl-item-origin)');
    var visible = [];
    for (var i = 0; i < items.length; i++) {
        if (!items[i].classList.contains('tl-unreplied-hidden')) {
            visible.push(items[i]);
        }
    }
    return visible;
}

function updateUnrepliedEmpty(listEl, filterOn) {
    var emptyEl = document.getElementById('tl-unreplied-empty');
    if (!emptyEl) return;

    if (!filterOn) {
        emptyEl.style.display = 'none';
        return;
    }

    var hasServerEmpty = !!(listEl.querySelector('.tl-empty'));
    var visible = getVisibleMentionItems(listEl);
    var show = !hasServerEmpty && visible.length === 0 && !isLoading && !hasMore;
    emptyEl.style.display = show ? 'block' : 'none';
}

function fillUnrepliedIfNeeded() {
    if (!isUnrepliedFilterOn() || isLoading || !hasMore) return;
    if (currentTab !== 'mentions') return;

    var listEl = document.getElementById('tl-list');
    if (!listEl) return;

    var visible = getVisibleMentionItems(listEl);
    var docEl = document.documentElement;
    var viewport = window.innerHeight || docEl.clientHeight || 0;
    var docHeight = Math.max(
        document.body.scrollHeight || 0,
        docEl.scrollHeight || 0
    );

    // 필터로 가려져 스크롤이 생기지 않으면 다음 페이지를 이어서 로드 (스위치 상태는 유지)
    if (visible.length === 0 || docHeight <= viewport + 8) {
        loadTimeline();
    }
}

function applyUnrepliedFilter() {
    var listEl = document.getElementById('tl-list');
    if (!listEl || currentTab !== 'mentions') return;
    if (!document.getElementById('tl-unreplied-switch')) return;

    var on = isUnrepliedFilterOn();
    var items = listEl.querySelectorAll('.tl-item:not(.tl-item-origin)');

    for (var i = 0; i < items.length; i++) {
        var item = items[i];
        var hide = false;
        if (on) {
            var myReply = item.getAttribute('data-my-reply') === '1';
            var myLike = !!item.querySelector('.tl-action.like.active');
            hide = myReply || myLike;
        }
        setMentionEntryHidden(item, hide);
    }

    updateUnrepliedEmpty(listEl, on);
    fillUnrepliedIfNeeded();
}

// ============================================
// 범용 콘텐츠 제출 함수
// ============================================

/** 글/댓글 제출 중복 방지 잠금 */
var tlSubmitLock = false;

/**
 * 타임라인 콘텐츠 제출 (글/댓글 통합)
 * @param {Object} options
 * @param {FormData} options.formData - 전송할 폼 데이터
 * @param {string} options.endpoint - API 엔드포인트 (기본: ./ajax/ajax_post.php)
 * @param {HTMLElement} options.submitBtn - 비활성화할 버튼 (선택)
 * @param {Function} options.onSuccess - 성공 콜백 (data 전달)
 * @param {Function} options.onError - 실패 콜백 (message 전달)
 * @param {Function} options.onComplete - 완료 콜백 (성공/실패 무관)
 */
function submitTimelineContent(options) {
    // 더블클릭/연타 방지
    if (tlSubmitLock) return;
    tlSubmitLock = true;

    var formData = options.formData || new FormData();
    var endpoint = options.endpoint || './ajax/ajax_post.php';
    var submitBtn = options.submitBtn || null;
    var onSuccess = options.onSuccess || function() {};
    var onError = options.onError || function(msg) { alert(msg); };
    var onComplete = options.onComplete || function() {};
    var prevLabel = '';

    // tl_table 자동 추가
    if (!formData.has('tl_table') && typeof tlTable !== 'undefined') {
        formData.append('tl_table', tlTable);
    }
    
    if (submitBtn) {
        submitBtn.disabled = true;
        prevLabel = submitBtn.getAttribute('data-tl-label') || submitBtn.textContent || '';
        submitBtn.setAttribute('data-tl-label', prevLabel);
        if (prevLabel && prevLabel.indexOf('중') === -1) {
            submitBtn.textContent = '게시 중...';
        }
    }

    function releaseSubmitLock() {
        tlSubmitLock = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            var label = submitBtn.getAttribute('data-tl-label');
            if (label) submitBtn.textContent = label;
        }
    }
    
    fetch(endpoint, {
        method: 'POST',
        body: formData
    })
    .then(function(response) { 
        return response.text();
    })
    .then(function(text) {
        try {
            // 워닝/BOM이 JSON 앞에 붙은 경우 복구 시도
            var raw = (text || '').replace(/^\uFEFF/, '');
            var data;
            try {
                data = JSON.parse(raw);
            } catch (e1) {
                var start = raw.indexOf('{');
                var end = raw.lastIndexOf('}');
                if (start < 0 || end <= start) throw e1;
                data = JSON.parse(raw.substring(start, end + 1));
            }

            if (data.success) {
                // 성공 시에는 잠금 유지한 채 콜백 → 폼 리셋 후 해제
                // onSuccess 예외는 JSON 파싱 실패로 오인하지 않음
                try { onSuccess(data); } catch (cbErr) {
                    console.error('[Timeline] onSuccess 오류:', cbErr);
                } finally {
                    onComplete();
                    releaseSubmitLock();
                }
            } else {
                releaseSubmitLock();
                onComplete();
                onError(data.message || '등록에 실패했습니다.');
            }
        } catch(e) {
            console.error('[Timeline] JSON 파싱 오류:', e);
            console.error('[Timeline] 응답 텍스트:', text);
            releaseSubmitLock();
            onComplete();
            onError('서버 응답 형식이 올바르지 않습니다.');
        }
    })
    .catch(function(err) {
        releaseSubmitLock();
        onComplete();
        console.error('등록 오류:', err);
        onError('등록 중 오류가 발생했습니다.');
    });
}

/**
 * 인라인 답글 폼 제출 (form 기반)
 * @param {HTMLFormElement} form - 폼 요소
 * @param {number} parentId - 원글 ID
 * @returns {boolean} false (폼 기본 동작 방지)
 */
function submitInlineReplyForm(form, parentId) {
    var formData = new FormData(form);
    
    // 내용 검증
    var content = formData.get('content');
    var allowEmptyAction = String(formData.get('k_attack') || '') === '1';
    if ((!content || !String(content).trim()) && !allowEmptyAction) {
        alert('내용을 입력해주세요.');
        return false;
    }
    
    // 폼 ID에서 prefix 추출 (tl_reply_xxx -> ir_xxx)
    var formId = form.id || '';
    var prefix = 'ir_' + formId.replace('tl_reply_', '');
    
    // irSelectedFiles에서 이미지 추가 (file input은 FormData에 자동 포함 안됨)
    var files = irSelectedFiles[prefix] || [];
    files.forEach(function(file) {
        formData.append('images[]', file);
    });
    
    var submitBtn = form.querySelector('button[type="submit"]');
    
    submitTimelineContent({
        formData: formData,
        endpoint: './ajax/ajax_comment.php',
        submitBtn: submitBtn,
        onSuccess: function(data) {
            // 폼 초기화
            form.reset();
            irSelectedFiles[prefix] = [];
            irCurrentAction[prefix] = '';
            setReplyTempBtnState(form, false);
            
            var preview = document.getElementById(prefix + '_preview');
            if (preview) preview.style.display = 'none';
            var previewContainer = document.getElementById(prefix + '_preview_container');
            if (previewContainer) previewContainer.innerHTML = '';
            var charCount = document.getElementById(prefix + '_char_count');
            if (charCount) charCount.textContent = '0/500';
            
            // comment-data 패널 닫기
            form.querySelectorAll('.comment-data.on').forEach(function(el) {
                el.classList.remove('on');
            });
            var actionArea = document.getElementById(prefix + '_action_area');
            if (actionArea) actionArea.style.display = 'none';
            var itemArea = document.getElementById(prefix + '_item_area');
            if (itemArea) itemArea.style.display = 'none';

            // 메인 글쓰기 폼 + 이 답글 폼의 아이템/액션 목록 동기화
            if (typeof reloadComposerFields === 'function') {
                reloadComposerFields();
                var replyFormId = parseInt(formId.replace('tl_reply_', ''), 10) || 0;
                if (replyFormId && parentId) {
                    reloadComposerFields({
                        mode: 'reply',
                        replyFormId: replyFormId,
                        parentId: parentId
                    });
                }
            }

            goToPostedReply(data, parentId);
        }
    });
    
    return false;
}

/**
 * 인라인 답글 폼 초기화
 * @param {string} prefix - 폼 요소 prefix
 */
function resetInlineReplyForm(prefix) {
    var textarea = document.getElementById(prefix + '_content');
    if (textarea) textarea.value = '';
    
    irSelectedFiles[prefix] = [];
    irCurrentAction[prefix] = '';
    
    var previewContainer = document.getElementById(prefix + '_preview_container');
    if (previewContainer) previewContainer.innerHTML = '';
    
    var preview = document.getElementById(prefix + '_preview');
    if (preview) preview.style.display = 'none';
    
    var actionInput = document.getElementById(prefix + '_action');
    if (actionInput) actionInput.value = '';
    
    var charCount = document.getElementById(prefix + '_char_count');
    if (charCount) charCount.textContent = '0/500';
}

// ============================================
// 좋아요 / 북마크
// ============================================

function toggleLike(wrId, btn) {
    fetch('./ajax/ajax_like.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'tl_table=' + tlTable + '&wr_id=' + wrId
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            var countEl = btn.querySelector('.tl-action-count');
            if (data.liked) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
            if (countEl && typeof data.count !== 'undefined') {
                countEl.textContent = data.count > 0 ? data.count : '';
            }
            applyUnrepliedFilter();
        }
    });
}

function toggleBookmark(wrId, btn) {
    fetch('./ajax/ajax_bookmark.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'tl_table=' + tlTable + '&wr_id=' + wrId
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            if (data.bookmarked) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        }
    });
}

// ============================================
// 모달 (글 상세보기)
// ============================================

/**
 * 특정 글을 화면 중앙(또는 상단)으로 스크롤
 * @param {number} wrId
 * @param {{smooth?: boolean}} [options]
 * @returns {boolean}
 */
function focusTimelinePost(wrId, options) {
    wrId = parseInt(wrId, 10) || 0;
    if (!wrId) return false;

    var el = document.getElementById('tl_c_' + wrId);
    if (!el) return false;

    options = options || {};
    var smooth = !!options.smooth;
    var tooTall = el.offsetHeight > (window.innerHeight * 0.8);

    el.scrollIntoView({
        behavior: smooth ? 'smooth' : 'auto',
        block: tooTall ? 'start' : 'center'
    });
    return true;
}

function initStandalonePostFocus() {
    if (!tlConfig.isStandalonePage) return;
    var wrId = parseInt(tlConfig.focusWrId, 10) || 0;
    if (!wrId) return;

    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }

    focusTimelinePost(wrId, { smooth: false });

    var realign = function() {
        focusTimelinePost(wrId, { smooth: false });
    };
    if (document.readyState === 'complete') {
        realign();
    } else {
        window.addEventListener('load', realign);
    }
}

/**
 * 답글 등록 후 이동
 * view.php: 새 답글 wr_id로 리다이렉트
 * 모달: 해당 댓글 포커스로 상세 새로고침
 * @param {Object} data - ajax_comment 응답
 * @param {number} parentId - 원글 ID (모달 새로고침용)
 */
function goToPostedReply(data, parentId) {
    var newCommentId = (data && data.comment_id) ? parseInt(data.comment_id, 10) : 0;

    if (tlConfig.isStandalonePage) {
        var targetId = newCommentId > 0 ? newCommentId : (parseInt(parentId, 10) || 0);
        if (targetId > 0 && typeof tlTable !== 'undefined') {
            location.href = './view.php?tl_table=' + encodeURIComponent(tlTable) + '&wr_id=' + targetId;
        } else {
            location.reload();
        }
        return;
    }

    openPost(parentId, newCommentId > 0 ? newCommentId : true);

    if (typeof updatePostCommentCount === 'function' && parentId) {
        updatePostCommentCount(parentId);
    }
    if (typeof loadTimeline === 'function') {
        loadTimeline(true);
    }
}

/**
 * 글 상세 모달 열기
 * @param {number} wrId - 글 ID
 * @param {boolean|number} showReply - true: 답글 폼 열기, 숫자: 해당 댓글로 스크롤
 */
function openPost(wrId, showReply) {
    var url = './view.php?tl_table=' + tlTable + '&wr_id=' + wrId + '&ajax=1';
    
    // showReply가 숫자면 focus_id로 전달 (해당 댓글 기준 스레드 표시)
    if (typeof showReply === 'number' && showReply > 0) {
        url += '&focus_id=' + showReply;
    }

    // view.php가 멘션을 읽음 처리할 때까지, 이전 폴링이 배지를 다시 켜지 않게 함
    invalidateTimelinePoll();
    hideMentionBadge();
    
    fetch(url)
    .then(function(response) { return response.text(); })
    .then(function(html) {
        invalidateTimelinePoll();
        hideMentionBadge();

        var modalBody = document.getElementById('tl-modal-body');
        modalBody.innerHTML = html;
        var postModal = document.getElementById('tl-modal');
        var wasOpen = postModal.style.display === 'flex' || postModal.style.display === 'block';
        postModal.style.display = 'flex';
        if (!wasOpen) {
            tlLockBodyScroll();
        }
        
        setTimeout(function() {
            // showReply가 숫자면 해당 댓글로 스크롤 + 하이라이트
            if (typeof showReply === 'number' && showReply > 0) {
                focusTimelinePost(showReply, { smooth: true });
            }
            // showReply가 true면 답글 폼 자동 열기 + 스크롤
            else if (showReply === true) {
                var replyForm = document.getElementById('tl_reply_' + wrId);
                if (replyForm) {
                    replyForm.style.display = 'block';
                    replyForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    loadTimelineTemp(replyForm);
                }
            }
        }, 150);
    });
}

function closeModal() {
    var postModal = document.getElementById('tl-modal');
    if (!postModal || postModal.style.display === 'none') return;

    if (document.activeElement && typeof document.activeElement.blur === 'function') {
        document.activeElement.blur();
    }
    postModal.style.display = 'none';

    // 이미지 모달이 남아 있으면 잠금 유지
    var imgModal = document.getElementById('tl-image-modal');
    var imgOpen = imgModal && imgModal.style.display !== 'none' && imgModal.style.display !== '';
    if (imgOpen) {
        if (tlBodyScrollLockCount > 1) {
            tlBodyScrollLockCount--;
        }
    } else {
        tlUnlockBodyScroll();
    }
    
    // 모달 내부의 인라인 답글 폼 이미지 초기화
    for (var prefix in irSelectedFiles) {
        if (irSelectedFiles.hasOwnProperty(prefix)) {
            irSelectedFiles[prefix] = [];
        }
    }
    irCurrentAction = {};
}

// ============================================
// 댓글 작성 (submitTimelineContent 사용)
// ============================================

// 댓글 작성 (폼 submit용 - 스킨에서 사용)
function submitComment(form) {
    var formData = new FormData(form);
    var wrId = formData.get('wr_id');
    var content = formData.get('content');
    var allowEmptyAction = String(formData.get('k_attack') || '') === '1';
    
    if ((!content || !String(content).trim()) && !allowEmptyAction) {
        alert('내용을 입력해주세요.');
        return false;
    }
    
    submitTimelineContent({
        formData: formData,
        endpoint: './ajax/ajax_comment.php',
        onSuccess: function(data) {
            goToPostedReply(data, wrId);
            if (typeof reloadComposerFields === 'function') {
                reloadComposerFields();
            }
        }
    });
    
    return false;
}

// 답글 작성 (기본 뷰용)
function submitReply(parentId) {
    var textarea = document.querySelector('.tl-reply-input[data-parent="' + parentId + '"]');
    if (!textarea) return;
    
    var content = textarea.value;
    if (!content.trim()) {
        alert('내용을 입력해주세요.');
        return;
    }
    
    var formData = new FormData();
    formData.append('content', content);
    formData.append('wr_id', parentId);
    
    submitTimelineContent({
        formData: formData,
        endpoint: './ajax/ajax_comment.php',
        onSuccess: function(data) {
            goToPostedReply(data, parentId);
            if (typeof reloadComposerFields === 'function') {
                reloadComposerFields();
            }
        }
    });
}

// ============================================
// 인라인 답글 폼
// ============================================

function getReplyParentWrId(form) {
    if (!form) return 0;
    var fromData = parseInt(form.getAttribute('data-parent-wr-id'), 10) || 0;
    if (fromData > 0) return fromData;
    var formId = form.id || '';
    if (formId.indexOf('tl_reply_') === 0) {
        return parseInt(formId.replace('tl_reply_', ''), 10) || 0;
    }
    return 0;
}

function getReplyTextarea(form) {
    if (!form) return null;
    return form.querySelector('textarea[name="content"]') || form.querySelector('textarea');
}

function getReplyTempBtn(form) {
    if (!form) return null;
    return form.querySelector('.tl-temp-btn');
}

function setReplyTempBtnState(form, hasTemp) {
    var btn = getReplyTempBtn(form);
    if (!btn) return;
    if (hasTemp) {
        btn.setAttribute('data-mode', 'delete');
        btn.textContent = '임시저장 삭제';
    } else {
        btn.setAttribute('data-mode', 'save');
        btn.textContent = '임시저장';
    }
}

function bindReplyTempInput(form) {
    var textarea = getReplyTextarea(form);
    if (!textarea || textarea.getAttribute('data-temp-input-bound')) return;
    textarea.setAttribute('data-temp-input-bound', '1');
    textarea.addEventListener('input', function() {
        var saved = form.getAttribute('data-temp-content');
        if (saved === null || typeof saved === 'undefined') return;
        if (textarea.value === saved) {
            setReplyTempBtnState(form, true);
        } else {
            setReplyTempBtnState(form, false);
        }
    });
}

function refreshReplyCharCount(form) {
    if (!form) return;
    var formId = (form.id || '').replace('tl_reply_', '');
    var prefix = 'ir_' + formId;
    var max = (typeof tlConfig !== 'undefined' && tlConfig.contentMaxLength) ? tlConfig.contentMaxLength : 500;
    if (typeof irUpdateCharCount === 'function') {
        irUpdateCharCount(prefix, max);
    }
}

function toggleTimelineTemp(btn) {
    var form = btn && btn.closest ? btn.closest('form') : null;
    if (!form) return;
    var mode = btn.getAttribute('data-mode') || 'save';
    if (mode === 'delete') {
        deleteTimelineTemp(form);
    } else {
        saveTimelineTemp(form);
    }
}

function saveTimelineTemp(form) {
    var parentWrId = getReplyParentWrId(form);
    var textarea = getReplyTextarea(form);
    var btn = getReplyTempBtn(form);
    var content = textarea ? textarea.value : '';
    if (!parentWrId) {
        alert('답글 대상 글 정보가 없습니다.');
        return;
    }
    if (!content || !content.trim()) {
        alert('저장할 내용이 없습니다.');
        return;
    }
    if (btn) btn.disabled = true;

    var formData = new FormData();
    formData.append('act', 'save');
    formData.append('parent_wr_id', parentWrId);
    formData.append('wr_content', content);
    if (typeof tlTable !== 'undefined') formData.append('tl_table', tlTable);

    fetch('./ajax/ajax_temp.php', { method: 'POST', body: formData, credentials: 'same-origin' })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (btn) btn.disabled = false;
            if (!data || !data.success) {
                alert((data && data.message) ? data.message : '임시저장에 실패했습니다.');
                return;
            }
            setReplyTempBtnState(form, true);
            form.setAttribute('data-temp-content', content);
            bindReplyTempInput(form);
        })
        .catch(function() {
            if (btn) btn.disabled = false;
            alert('임시저장 중 오류가 발생했습니다.');
        });
}

function deleteTimelineTemp(form) {
    var parentWrId = getReplyParentWrId(form);
    var textarea = getReplyTextarea(form);
    var btn = getReplyTempBtn(form);
    if (!parentWrId) return;
    if (btn) btn.disabled = true;

    var formData = new FormData();
    formData.append('act', 'delete');
    formData.append('parent_wr_id', parentWrId);
    if (typeof tlTable !== 'undefined') formData.append('tl_table', tlTable);

    fetch('./ajax/ajax_temp.php', { method: 'POST', body: formData, credentials: 'same-origin' })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (btn) btn.disabled = false;
            if (!data || !data.success) {
                alert((data && data.message) ? data.message : '삭제에 실패했습니다.');
                return;
            }
            if (textarea) textarea.value = '';
            form.removeAttribute('data-temp-content');
            setReplyTempBtnState(form, false);
            refreshReplyCharCount(form);
            if (textarea) textarea.focus();
        })
        .catch(function() {
            if (btn) btn.disabled = false;
            alert('임시저장 삭제 중 오류가 발생했습니다.');
        });
}

function loadTimelineTemp(form) {
    if (!form) return;
    var parentWrId = getReplyParentWrId(form);
    var textarea = getReplyTextarea(form);
    if (!parentWrId || !textarea) return;
    textarea.focus();

    var url = './ajax/ajax_temp.php?act=get&parent_wr_id=' + encodeURIComponent(parentWrId);
    if (typeof tlTable !== 'undefined') {
        url += '&tl_table=' + encodeURIComponent(tlTable);
    }

    fetch(url, { credentials: 'same-origin' })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!data || !data.success) {
                setReplyTempBtnState(form, false);
                return;
            }
            var raw = (typeof data.wr_content === 'string') ? data.wr_content : '';
            if (data.has_temp && raw !== '') {
                textarea.value = raw;
                form.setAttribute('data-temp-content', raw);
                setReplyTempBtnState(form, true);
                refreshReplyCharCount(form);
            } else {
                form.removeAttribute('data-temp-content');
                setReplyTempBtnState(form, false);
            }
            bindReplyTempInput(form);
        })
        .catch(function() {
            setReplyTempBtnState(form, false);
        });
}

// 인라인 답글 폼 표시 (해당 댓글 아래에)
function showInlineReply(commentId, chName, isOrigin) {
    // 다른 열린 인라인 폼들 닫기
    document.querySelectorAll('.tl-inline-reply').forEach(function(el) {
        el.style.display = 'none';
    });
    
    // 해당 댓글의 인라인 폼 표시
    var replyForm = document.getElementById('tl_reply_' + commentId);
    if (replyForm) {
        replyForm.style.display = 'block';
        
        // 인라인 답글 폼에 멘션 기능 초기화
        var formPrefix = 'ir_' + commentId;
        if (!replyForm.getAttribute('data-mention-initialized')) {
            initMentionSearchForReply(formPrefix);
            replyForm.setAttribute('data-mention-initialized', 'true');
        }

        loadTimelineTemp(replyForm);
    }
}

// 인라인 답글 폼 숨기기
function hideInlineReply(commentId) {
    var replyForm = document.getElementById('tl_reply_' + commentId);
    if (replyForm) {
        replyForm.style.display = 'none';
    }
}

// 인라인 답글 제출 (해당 댓글에 대한 대댓글)
// parentId: 원글 ID, replyToId: 대댓글 대상 (0이면 원글에 직접 답글)
function submitInlineReply(parentId, replyToId) {
    var textareaId = replyToId > 0 ? replyToId : parentId;
    var textarea = document.getElementById('tl_reply_content_' + textareaId);
    if (!textarea) return;
    
    var replyForm = document.getElementById('tl_reply_' + textareaId);
    var content = textarea.value.trim();
    var allowEmptyAction = !!(replyForm && replyForm.querySelector('input[name="k_attack"]:checked'));
    if (!content && !allowEmptyAction) {
        alert('내용을 입력해주세요.');
        return;
    }
    if (!content && allowEmptyAction) {
        content = '';
    }
    
    var formData = new FormData();
    formData.append('content', content);
    formData.append('wr_id', parentId);
    if (replyToId > 0) {
        formData.append('reply_to', replyToId);
    }
    appendTimelineFormFields(formData, replyForm);
    
    submitTimelineContent({
        formData: formData,
        endpoint: './ajax/ajax_comment.php',
        onSuccess: function(data) {
            goToPostedReply(data, parentId);
            if (typeof reloadComposerFields === 'function') {
                reloadComposerFields();
            }
        }
    });
}

// 기존 방식 호환용 (상단 폼 사용)
function replyToComment(commentId, chName) {
    showInlineReply(commentId, chName);
}

// 답글 대상 취소 (기존 호환)
function cancelReplyTo() {
    document.querySelectorAll('.tl-inline-reply').forEach(function(el) {
        el.style.display = 'none';
    });
}

// ============================================
// 확장된 인라인 답글 (이미지, 액션 포함)
// ============================================

var irSelectedFiles = {};
var irCurrentAction = {};

// 글자수 카운트
function irUpdateCharCount(prefix, max) {
    var textarea = document.getElementById(prefix + '_content');
    var counter = document.getElementById(prefix + '_char_count');
    if (textarea && counter) {
        counter.textContent = textarea.value.length + '/' + max;
    }
}

// 액션 영역 토글
function irToggleAction(prefix) {
    var area = document.getElementById(prefix + '_action_area');
    if (area) {
        area.style.display = (window.getComputedStyle(area).display === 'none') ? 'block' : 'none';
    }
}

// 아이템 영역 토글
function irToggleItem(prefix) {
    var area = document.getElementById(prefix + '_item_area');
    if (area) {
        area.style.display = (window.getComputedStyle(area).display === 'none') ? 'block' : 'none';
    }
}

// 액션 선택
function irSelectAction(prefix, action) {
    irCurrentAction[prefix] = action;
    var actionInput = document.getElementById(prefix + '_action');
    if (actionInput) actionInput.value = action;
    
    var area = document.getElementById(prefix + '_action_area');
    if (area) {
        area.querySelectorAll('.tl-action-tab').forEach(function(tab) {
            tab.classList.remove('active');
        });
        if (event && event.target) event.target.classList.add('active');
        
        area.querySelectorAll('.tl-action-content').forEach(function(content) {
            content.style.display = 'none';
        });
        if (action) {
            var actionContent = document.getElementById(prefix + '_action_' + action);
            if (actionContent) actionContent.style.display = 'block';
        }
    }
}

// 이미지 미리보기 (input 파일 선택 시)
function irPreviewImages(prefix, input, maxCount) {
    if (!irSelectedFiles[prefix]) irSelectedFiles[prefix] = [];
    
    var files = Array.from(input.files);
    var remaining = maxCount - irSelectedFiles[prefix].length;
    
    if (files.length > remaining) {
        alert('최대 ' + maxCount + '장까지 업로드할 수 있습니다.');
        files = files.slice(0, remaining);
    }
    
    files.forEach(function(file) {
        if (file.type.startsWith('image/')) {
            irSelectedFiles[prefix].push(file);
        }
    });
    
    irUpdateImagePreview(prefix);
    input.value = '';
}

// 드래그 앤 드롭
function irDragOver(e, prefix) {
    e.preventDefault();
    var form = document.getElementById('tl_reply_' + prefix.replace('ir_', ''));
    if (form) form.classList.add('dragover');
}

function irDragLeave(e, prefix) {
    var form = document.getElementById('tl_reply_' + prefix.replace('ir_', ''));
    if (form) form.classList.remove('dragover');
}

function irDrop(e, prefix) {
    e.preventDefault();
    var form = document.getElementById('tl_reply_' + prefix.replace('ir_', ''));
    if (form) form.classList.remove('dragover');
    
    if (e.dataTransfer.files.length) {
        if (!irSelectedFiles[prefix]) irSelectedFiles[prefix] = [];
        
        var files = Array.from(e.dataTransfer.files);
        files.forEach(function(file) {
            if (file.type.startsWith('image/') && irSelectedFiles[prefix].length < 4) {
                irSelectedFiles[prefix].push(file);
            }
        });
        
        irUpdateImagePreview(prefix);
    }
}

// 확장된 인라인 답글 제출 (레거시 호환 - form 기반으로 위임)
function submitInlineReplyFull(parentId, replyToId, prefix) {
    var formId = prefix.replace('ir_', '');
    var form = document.getElementById('tl_reply_' + formId);
    if (form) {
        submitInlineReplyForm(form, parentId);
    }
}

// 인라인 답글 이미지 미리보기 업데이트
function irUpdateImagePreview(prefix) {
    // 스킨과 일치하는 ID 사용: prefix_preview_container
    var previewContainer = document.getElementById(prefix + '_preview_container');
    var previewWrapper = document.getElementById(prefix + '_preview');
    if (!previewContainer) return;
    
    previewContainer.innerHTML = '';
    var files = irSelectedFiles[prefix] || [];
    
    // 이미지 카운트 표시
    var countEl = document.getElementById(prefix + '_image_count');
    if (countEl) {
        countEl.textContent = files.length > 0 ? files.length : '';
    }
    
    // 미리보기 영역 표시/숨김
    if (previewWrapper) {
        previewWrapper.style.display = files.length > 0 ? 'block' : 'none';
    }
    
    files.forEach(function(file, index) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var div = document.createElement('div');
            div.className = 'tl-preview-item';
            div.innerHTML = '<img src="' + e.target.result + '" alt="">' +
                '<button type="button" class="tl-remove-preview" onclick="irRemoveImage(\'' + prefix + '\', ' + index + ')">&times;</button>';
            previewContainer.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

// 인라인 답글 이미지 제거
function irRemoveImage(prefix, index) {
    if (irSelectedFiles[prefix]) {
        irSelectedFiles[prefix].splice(index, 1);
        irUpdateImagePreview(prefix);
    }
}

// 인라인 답글 이미지 선택
function irSelectImages(prefix) {
    var input = document.getElementById(prefix + '_images');
    if (input) input.click();
}

// 인라인 답글 이미지 변경 핸들러
function irHandleImageChange(prefix, input) {
    if (!irSelectedFiles[prefix]) {
        irSelectedFiles[prefix] = [];
    }
    
    var files = input.files;
    for (var i = 0; i < files.length; i++) {
        var file = files[i];
        if (file.type.startsWith('image/')) {
            if (irSelectedFiles[prefix].length < 4) {
                irSelectedFiles[prefix].push(file);
            }
        }
    }
    
    irUpdateImagePreview(prefix);
}

// ============================================
// 삭제
// ============================================

// 댓글 삭제
function deleteComment(commentId, parentId) {
    if (!confirm('정말 삭제하시겠습니까?')) return;
    
    var formData = new FormData();
    formData.append('wr_id', commentId);
    
    submitTimelineContent({
        formData: formData,
        endpoint: './ajax/ajax_comment_delete.php',
        onSuccess: function() {
            openPost(parentId);
        }
    });
}

// 글 삭제
function deletePost(wrId) {
    if (!confirm('정말 삭제하시겠습니까?')) return;
    
    var formData = new FormData();
    formData.append('wr_id', wrId);
    
    submitTimelineContent({
        formData: formData,
        endpoint: './ajax/ajax_delete.php',
        onSuccess: function() {
            if (tlConfig.isStandalonePage) {
                location.href = './index.php?tl_table=' + tlTable;
            } else {
                // 목록에서 제거
                var item = document.querySelector('.tl-item[data-id="' + wrId + '"]');
                if (item) item.remove();
                
                // 모달 닫기
                closeModal();
            }
        }
    });
}

// ============================================
// 글/댓글 수정 모달
// ============================================

var editKeepImages = []; // {name, url}
var editNewFiles = [];
var editCurrentWrId = 0;

function ensureEditModal() {
    var modal = document.getElementById('tl-edit-modal');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = 'tl-edit-modal';
    modal.className = 'tl-modal tl-edit-modal';
    modal.style.display = 'none';
    modal.innerHTML =
        '<div class="tl-modal-backdrop" onclick="closeEditModal()"></div>' +
        '<div class="tl-modal-content tl-edit-modal-content">' +
        '  <button type="button" class="tl-modal-close" onclick="closeEditModal()">&times;</button>' +
        '  <div class="tl-edit-modal-body">' +
        '    <h3 class="tl-edit-title">글 수정</h3>' +
        '    <form id="tl-edit-form" enctype="multipart/form-data">' +
        '      <input type="hidden" name="wr_id" id="tl-edit-wr-id" value="">' +
        '      <textarea id="tl-edit-content" name="content" class="tl-edit-textarea" rows="6" placeholder="내용을 입력하세요"></textarea>' +
        '      <div id="tl-edit-image-preview" class="tl-image-preview" style="display:none;">' +
        '        <div id="tl-edit-preview-container" class="tl-preview-container"></div>' +
        '      </div>' +
        '      <div class="tl-form-options tl-edit-options">' +
        '        <fieldset>' +
        '          <input type="checkbox" id="tl-edit-img-hidden" name="wr_img_hidden" value="1">' +
        '          <label for="tl-edit-img-hidden">이미지 접기</label>' +
        '        </fieldset>' +
        '        <fieldset>' +
        '          <input type="checkbox" id="tl-edit-text-hidden" name="wr_text_hidden" value="1">' +
        '          <label for="tl-edit-text-hidden">글 가리기</label>' +
        '        </fieldset>' +
        '        <div id="tl-edit-warning-wrap" class="tl-warning-text-wrap" style="display:none;">' +
        '          <input type="text" id="tl-edit-waring-text" name="wr_waring_text" maxlength="255" placeholder="경고 문구 (선택)">' +
        '        </div>' +
        '      </div>' +
        '      <div class="tl-edit-actions-bar">' +
        '        <label class="tl-image-btn tl-btns" id="tl-edit-image-label" title="이미지 첨부">' +
        '          <input type="file" id="tl-edit-images" accept="image/*" multiple style="display:none;">' +
        '          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-5-7l-3 3.72L9 13l-3 4h12l-4-5z"/></svg>' +
        '          <span id="tl-edit-image-count"></span>' +
        '        </label>' +
        '        <span id="tl-edit-char-count" class="tl-edit-char-count">0/500</span>' +
        '        <button type="button" class="tl-btn tl-btn-cancel" onclick="closeEditModal()">취소</button>' +
        '        <button type="submit" id="tl-edit-submit" class="tl-btn tl-btn-primary">수정</button>' +
        '      </div>' +
        '    </form>' +
        '  </div>' +
        '</div>';
    document.body.appendChild(modal);

    document.getElementById('tl-edit-text-hidden').addEventListener('change', function() {
        var wrap = document.getElementById('tl-edit-warning-wrap');
        wrap.style.display = this.checked ? 'block' : 'none';
        if (!this.checked) document.getElementById('tl-edit-waring-text').value = '';
    });

    document.getElementById('tl-edit-images').addEventListener('change', function() {
        editPreviewImages(this);
    });

    document.getElementById('tl-edit-content').addEventListener('input', function() {
        updateEditCharCount();
    });

    document.getElementById('tl-edit-form').addEventListener('submit', function(e) {
        e.preventDefault();
        submitEditPost();
    });

    return modal;
}

/**
 * 스크립트 작성/수정 팝업 (스마트에디터)
 * 모바일에서는 팝업 대신 동일 창 이동 — SE2 툴바 가로 잘림 방지
 */
function openScriptWrite(url) {
    if (!url) return true;
    var isMobile = (typeof g5_is_mobile !== 'undefined' && String(g5_is_mobile) === '1')
        || window.matchMedia('(max-width: 768px)').matches
        || /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent || '');
    if (isMobile) {
        location.href = url;
        return false;
    }
    var w = Math.min(960, window.screen.availWidth - 40);
    var h = Math.min(780, window.screen.availHeight - 60);
    var left = Math.max(0, (window.screen.availWidth - w) / 2);
    var top = Math.max(0, (window.screen.availHeight - h) / 2);
    var win = window.open(url, 'tl_script_write', 'width=' + w + ',height=' + h + ',left=' + left + ',top=' + top + ',scrollbars=yes,resizable=yes');
    if (win) {
        win.focus();
        return false;
    }
    return true;
}

function openEditPost(wrId) {
    if (!wrId) return;
    ensureEditModal();
    editCurrentWrId = wrId;
    editKeepImages = [];
    editNewFiles = [];

    fetch('./ajax/ajax_post_get.php?tl_table=' + encodeURIComponent(tlTable) + '&wr_id=' + wrId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data || !data.success) {
                alert((data && data.message) ? data.message : '글을 불러오지 못했습니다.');
                return;
            }

            // 스크립트 글 → 스마트에디터 팝업
            if (data.wr_script) {
                var scriptUrl = './script_write.php?tl_table=' + encodeURIComponent(tlTable) + '&wr_id=' + encodeURIComponent(data.wr_id);
                openScriptWrite(scriptUrl);
                return;
            }

            document.getElementById('tl-edit-wr-id').value = data.wr_id;
            document.getElementById('tl-edit-content').value = data.wr_content || '';
            document.getElementById('tl-edit-img-hidden').checked = !!data.wr_img_hidden;
            document.getElementById('tl-edit-text-hidden').checked = !!data.wr_text_hidden;
            document.getElementById('tl-edit-waring-text').value = data.wr_waring_text || '';
            document.getElementById('tl-edit-warning-wrap').style.display = data.wr_text_hidden ? 'block' : 'none';

            var maxLen = data.content_max || tlConfig.contentMaxLength || 500;
            var contentEl = document.getElementById('tl-edit-content');
            contentEl.setAttribute('maxlength', maxLen);
            tlConfig._editContentMax = maxLen;
            tlConfig._editImageMax = data.image_max || tlConfig.imageMaxCount || 4;
            tlConfig._editUseImage = !!data.use_image;

            var imgLabel = document.getElementById('tl-edit-image-label');
            if (imgLabel) imgLabel.style.display = tlConfig._editUseImage ? '' : 'none';

            editKeepImages = (data.images || []).map(function(img) {
                return { name: img.name, url: img.url };
            });
            editNewFiles = [];
            updateEditImagePreview();
            updateEditCharCount();

            document.querySelector('.tl-edit-title').textContent = data.is_comment ? '답글 수정' : '글 수정';
            document.getElementById('tl-edit-modal').style.display = 'flex';
        })
        .catch(function() {
            alert('글을 불러오지 못했습니다.');
        });
}

function closeEditModal() {
    var modal = document.getElementById('tl-edit-modal');
    if (modal) modal.style.display = 'none';
    editCurrentWrId = 0;
    editKeepImages = [];
    editNewFiles = [];
}

function updateEditCharCount() {
    var el = document.getElementById('tl-edit-content');
    var count = document.getElementById('tl-edit-char-count');
    if (!el || !count) return;
    var max = tlConfig._editContentMax || tlConfig.contentMaxLength || 500;
    count.textContent = el.value.length + '/' + max;
}

function editPreviewImages(input) {
    if (!input.files) return;
    var max = tlConfig._editImageMax || tlConfig.imageMaxCount || 4;
    var remaining = max - editKeepImages.length - editNewFiles.length;
    var files = Array.from(input.files);
    if (files.length > remaining) {
        alert('최대 ' + max + '장까지 업로드할 수 있습니다.');
        files = files.slice(0, remaining);
    }
    files.forEach(function(file) {
        if (file.type && file.type.indexOf('image/') === 0) editNewFiles.push(file);
    });
    input.value = '';
    updateEditImagePreview();
}

function updateEditImagePreview() {
    var wrap = document.getElementById('tl-edit-image-preview');
    var container = document.getElementById('tl-edit-preview-container');
    var countEl = document.getElementById('tl-edit-image-count');
    if (!container) return;

    container.innerHTML = '';
    var total = editKeepImages.length + editNewFiles.length;
    if (wrap) wrap.style.display = total > 0 ? 'block' : 'none';
    if (countEl) countEl.textContent = total > 0 ? String(total) : '';

    editKeepImages.forEach(function(img, index) {
        var div = document.createElement('div');
        div.className = 'tl-preview-item';
        div.innerHTML = '<img src="' + img.url + '" alt="">' +
            '<button type="button" class="tl-remove-image" aria-label="제거">&times;</button>';
        (function(i) {
            div.querySelector('button').onclick = function() {
                editKeepImages.splice(i, 1);
                updateEditImagePreview();
            };
        })(index);
        container.appendChild(div);
    });

    editNewFiles.forEach(function(file, index) {
        var div = document.createElement('div');
        div.className = 'tl-preview-item';
        div.innerHTML = '<img alt="">' +
            '<button type="button" class="tl-remove-image" aria-label="제거">&times;</button>';
        var imgEl = div.querySelector('img');
        (function(i) {
            div.querySelector('button').onclick = function() {
                editNewFiles.splice(i, 1);
                updateEditImagePreview();
            };
        })(index);
        container.appendChild(div);
        var reader = new FileReader();
        reader.onload = function(e) { imgEl.src = e.target.result; };
        reader.readAsDataURL(file);
    });
}

function submitEditPost() {
    var wrId = document.getElementById('tl-edit-wr-id').value;
    var content = document.getElementById('tl-edit-content').value || '';
    if (!content.trim() && editKeepImages.length === 0 && editNewFiles.length === 0) {
        alert('내용을 입력해주세요.');
        return;
    }

    var formData = new FormData();
    formData.append('wr_id', wrId);
    formData.append('content', content);
    if (document.getElementById('tl-edit-img-hidden').checked) formData.append('wr_img_hidden', '1');
    if (document.getElementById('tl-edit-text-hidden').checked) formData.append('wr_text_hidden', '1');
    var warn = document.getElementById('tl-edit-waring-text').value || '';
    if (warn) formData.append('wr_waring_text', warn);

    editKeepImages.forEach(function(img) {
        formData.append('keep_images[]', img.name);
    });
    editNewFiles.forEach(function(file) {
        formData.append('images[]', file);
    });

    var submitBtn = document.getElementById('tl-edit-submit');
    submitTimelineContent({
        formData: formData,
        endpoint: './ajax/ajax_post_update.php',
        submitBtn: submitBtn,
        onSuccess: function(data) {
            closeEditModal();

            if (tlConfig.isStandalonePage) {
                location.reload();
                return;
            }

            // 목록 아이템 교체
            if (data && data.html) {
                var nodes = document.querySelectorAll('.tl-item[data-id="' + wrId + '"]');
                nodes.forEach(function(node) {
                    // 상세 모달 안 댓글/원글은 구조가 다를 수 있어 목록(.tl-list)만 교체
                    if (node.closest && node.closest('#tl-list')) {
                        var tmp = document.createElement('div');
                        tmp.innerHTML = data.html.trim();
                        var neu = tmp.firstElementChild;
                        if (neu) node.parentNode.replaceChild(neu, node);
                    }
                });
            }

            // 상세 모달이 열려 있으면 새로고침
            var detailModal = document.getElementById('tl-modal');
            if (detailModal && detailModal.style.display !== 'none' && detailModal.style.display !== '') {
                var refreshId = wrId;
                if (data && data.is_comment && data.parent_id > 0) {
                    refreshId = Math.abs(data.parent_id);
                }
                openPost(refreshId);
            } else if (!data || !data.html) {
                if (typeof loadTimeline === 'function') loadTimeline(true);
            }
        }
    });
}

// 레거시 인라인 수정 진입점 → 모달로 위임
function toggleCommentEdit(commentId) {
    openEditPost(commentId);
}
function saveCommentEdit(commentId) {
    openEditPost(commentId);
}
function cancelCommentEdit() {}

// ============================================
// 댓글 수 갱신
// ============================================

function updatePostCommentCount(wrId) {
    // AJAX로 해당 글의 댓글 수 조회
    fetch('./ajax/ajax_get_comment_count.php?tl_table=' + tlTable + '&wr_id=' + wrId)
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            // 리스트에서 해당 글 찾아서 댓글 수 갱신
            var item = document.querySelector('.tl-item[data-id="' + wrId + '"]');
            if (item) {
                var commentBtn = item.querySelector('.tl-action-btn.comment .tl-action-count');
                if (commentBtn) {
                    commentBtn.textContent = data.count > 0 ? data.count : '';
                }
            }
        }
    })
    .catch(function(err) {
        console.error('댓글 수 조회 오류:', err);
    });
}

// ============================================
// 이미지 뷰어 (확대/팬 — 휠·터치)
// ============================================

var currentImages = [];
var currentImageIndex = 0;

var tlImgZoom = {
    scale: 1,
    min: 1,
    max: 5,
    minPinchDist: 28,
    x: 0,
    y: 0,
    bound: false,
    dragging: false,
    pointers: {},
    pinchStartDist: 0,
    pinchStartScale: 1,
    panStartX: 0,
    panStartY: 0,
    originX: 0,
    originY: 0,
    lastTap: 0,
    // 제스처 분리: pan(확대 이동) vs slide(여러 장 넘기기)
    gestureMode: 'none', // none | pan | slide | pinch
    gestureStartScale: 1,
    activePointerId: null,
    movedDist: 0
};

function tlImgGetStage() {
    return document.getElementById('tl-image-zoom-stage');
}

function tlImgGetImg() {
    return document.getElementById('tl-image-modal-img');
}

function tlImgIsZoomed() {
    return tlImgZoom.scale > 1.02 || tlImgZoom.gestureStartScale > 1.02;
}

function tlImgResetZoom() {
    tlImgZoom.scale = 1;
    tlImgZoom.x = 0;
    tlImgZoom.y = 0;
    tlImgZoom.dragging = false;
    tlImgZoom.pointers = {};
    tlImgZoom.pinchStartDist = 0;
    tlImgZoom.gestureMode = 'none';
    tlImgZoom.gestureStartScale = 1;
    tlImgZoom.activePointerId = null;
    tlImgZoom.movedDist = 0;
    tlImgApplyTransform();
    var stage = tlImgGetStage();
    if (stage) {
        stage.classList.remove('is-zoomed', 'is-dragging', 'is-sliding');
    }
}

function tlImgApplyTransform() {
    var img = tlImgGetImg();
    if (!img) return;
    img.style.transform = 'translate(' + tlImgZoom.x + 'px, ' + tlImgZoom.y + 'px) scale(' + tlImgZoom.scale + ')';
    var stage = tlImgGetStage();
    if (stage) {
        if (tlImgZoom.scale > 1.01) stage.classList.add('is-zoomed');
        else stage.classList.remove('is-zoomed');
    }
}

function tlImgClampPan() {
    if (tlImgZoom.scale <= 1) {
        tlImgZoom.x = 0;
        tlImgZoom.y = 0;
        return;
    }
    var stage = tlImgGetStage();
    var img = tlImgGetImg();
    if (!stage || !img) return;
    var sw = stage.clientWidth || 1;
    var sh = stage.clientHeight || 1;
    var iw = img.offsetWidth || 1;
    var ih = img.offsetHeight || 1;
    var maxX = Math.max(0, (iw * tlImgZoom.scale - sw) / 2 + 40);
    var maxY = Math.max(0, (ih * tlImgZoom.scale - sh) / 2 + 40);
    tlImgZoom.x = Math.max(-maxX, Math.min(maxX, tlImgZoom.x));
    tlImgZoom.y = Math.max(-maxY, Math.min(maxY, tlImgZoom.y));
}

function tlImgZoomAt(clientX, clientY, nextScale) {
    var stage = tlImgGetStage();
    if (!stage) return;
    var rect = stage.getBoundingClientRect();
    var cx = clientX - rect.left - rect.width / 2;
    var cy = clientY - rect.top - rect.height / 2;
    var prev = tlImgZoom.scale;
    nextScale = Math.max(tlImgZoom.min, Math.min(tlImgZoom.max, nextScale));
    if (prev === nextScale) return;

    // 커서/핀치 지점 기준으로 확대
    var ratio = nextScale / prev;
    tlImgZoom.x = cx - (cx - tlImgZoom.x) * ratio;
    tlImgZoom.y = cy - (cy - tlImgZoom.y) * ratio;
    tlImgZoom.scale = nextScale;
    if (tlImgZoom.scale <= 1.01) {
        tlImgZoom.scale = 1;
        tlImgZoom.x = 0;
        tlImgZoom.y = 0;
    } else {
        tlImgClampPan();
    }
    tlImgApplyTransform();
}

function tlImgPointerList() {
    var list = [];
    for (var id in tlImgZoom.pointers) {
        if (tlImgZoom.pointers.hasOwnProperty(id)) list.push(tlImgZoom.pointers[id]);
    }
    return list;
}

function tlImgDistance(a, b) {
    var dx = a.x - b.x;
    var dy = a.y - b.y;
    return Math.sqrt(dx * dx + dy * dy);
}

function tlImgMidpoint(a, b) {
    return { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
}

function tlImgBeginPinchIfReady(pts) {
    if (pts.length !== 2) return false;
    var dist = tlImgDistance(pts[0], pts[1]);
    if (dist < tlImgZoom.minPinchDist) {
        tlImgZoom.pinchStartDist = 0;
        return false;
    }
    tlImgZoom.pinchStartDist = dist;
    tlImgZoom.pinchStartScale = tlImgZoom.scale;
    return true;
}

function tlImgEnsureZoomBound() {
    if (tlImgZoom.bound) return;
    var stage = tlImgGetStage();
    if (!stage) return;
    tlImgZoom.bound = true;

    stage.addEventListener('wheel', function(e) {
        var modal = document.getElementById('tl-image-modal');
        if (!modal || modal.style.display === 'none') return;
        e.preventDefault();
        var delta = e.deltaY;
        // 트랙패드/마우스 휠 모두 대응
        var factor = delta > 0 ? 0.9 : 1.1;
        if (Math.abs(delta) < 8) {
            factor = delta > 0 ? 0.97 : 1.03;
        }
        tlImgZoomAt(e.clientX, e.clientY, tlImgZoom.scale * factor);
        // 휠 확대 중에는 슬라이드 제스처 비활성
        tlImgZoom.gestureMode = 'pan';
        tlImgZoom.gestureStartScale = tlImgZoom.scale;
    }, { passive: false });

    stage.addEventListener('pointerdown', function(e) {
        var modal = document.getElementById('tl-image-modal');
        if (!modal || modal.style.display === 'none') return;
        if (e.button !== undefined && e.button !== 0) return;

        // 더블탭/더블클릭 토글 (다른 손가락이 이미 눌려 있으면 멀티터치 — 더블탭 아님)
        var now = Date.now();
        if (tlImgPointerList().length === 0 && now - tlImgZoom.lastTap < 280 && tlImgZoom.movedDist < 8) {
            e.preventDefault();
            if (tlImgZoom.scale > 1.05) {
                tlImgResetZoom();
            } else {
                tlImgZoomAt(e.clientX, e.clientY, 2.5);
            }
            tlImgZoom.lastTap = 0;
            tlImgZoom.gestureMode = 'none';
            return;
        }
        tlImgZoom.lastTap = now;

        stage.setPointerCapture(e.pointerId);
        tlImgZoom.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
        tlImgZoom.activePointerId = e.pointerId;
        tlImgZoom.panStartX = e.clientX;
        tlImgZoom.panStartY = e.clientY;
        tlImgZoom.originX = tlImgZoom.x;
        tlImgZoom.originY = tlImgZoom.y;
        tlImgZoom.movedDist = 0;
        tlImgZoom.gestureStartScale = tlImgZoom.scale;
        // 이미 확대된 상태면 무조건 팬 모드로 시작 (슬라이드와 분리)
        tlImgZoom.gestureMode = tlImgIsZoomed() ? 'pan' : 'none';
        tlImgZoom.dragging = true;

        var pts = tlImgPointerList();
        if (pts.length === 2) {
            tlImgZoom.gestureMode = 'pinch';
            tlImgZoom.dragging = false;
            tlImgBeginPinchIfReady(pts);
            stage.classList.remove('is-dragging', 'is-sliding');
        } else {
            stage.classList.add('is-dragging');
            stage.classList.remove('is-sliding');
        }
    });

    stage.addEventListener('pointermove', function(e) {
        if (!tlImgZoom.pointers[e.pointerId]) return;
        tlImgZoom.pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
        var pts = tlImgPointerList();

        if (pts.length === 2 || tlImgZoom.gestureMode === 'pinch') {
            e.preventDefault();
            tlImgZoom.gestureMode = 'pinch';
            if (pts.length === 2) {
                var dist = tlImgDistance(pts[0], pts[1]);
                if (tlImgZoom.pinchStartDist <= 0) {
                    tlImgBeginPinchIfReady(pts);
                } else {
                    var mid = tlImgMidpoint(pts[0], pts[1]);
                    var next = tlImgZoom.pinchStartScale * (dist / tlImgZoom.pinchStartDist);
                    tlImgZoomAt(mid.x, mid.y, next);
                }
            }
            return;
        }

        if (pts.length !== 1 || !tlImgZoom.dragging) return;
        if (tlImgZoom.activePointerId !== null && e.pointerId !== tlImgZoom.activePointerId) return;

        var dx = e.clientX - tlImgZoom.panStartX;
        var dy = e.clientY - tlImgZoom.panStartY;
        tlImgZoom.movedDist = Math.max(tlImgZoom.movedDist, Math.sqrt(dx * dx + dy * dy));

        // 확대 상태(시작 시점 포함)면 항상 팬 — 슬라이드 로직 진입 금지
        if (tlImgIsZoomed() || tlImgZoom.gestureMode === 'pan') {
            e.preventDefault();
            tlImgZoom.gestureMode = 'pan';
            tlImgZoom.x = tlImgZoom.originX + dx;
            tlImgZoom.y = tlImgZoom.originY + dy;
            tlImgClampPan();
            tlImgApplyTransform();
            stage.classList.remove('is-sliding');
            return;
        }

        // 1배율: 방향이 정해지기 전 임계값
        if (tlImgZoom.gestureMode === 'none' && tlImgZoom.movedDist > 12) {
            if (Math.abs(dx) > Math.abs(dy) * 1.15 && currentImages.length > 1) {
                tlImgZoom.gestureMode = 'slide';
                stage.classList.add('is-sliding');
            } else {
                // 세로 위주 드래그는 제스처 없음 (닫기 등과 충돌 방지)
                tlImgZoom.gestureMode = 'none';
            }
        }

        // 슬라이드 예고: 이미지를 살짝 따라가게 (선택 UX)
        if (tlImgZoom.gestureMode === 'slide') {
            e.preventDefault();
            var img = tlImgGetImg();
            if (img) {
                img.style.transform = 'translate(' + (dx * 0.35) + 'px, 0) scale(1)';
            }
        }
    });

    function tlImgPointerEnd(e) {
        if (!tlImgZoom.pointers[e.pointerId]) return;
        var wasPts = tlImgPointerList();
        var mode = tlImgZoom.gestureMode;
        var startScale = tlImgZoom.gestureStartScale;
        var startX = tlImgZoom.panStartX;
        var startY = tlImgZoom.panStartY;
        var moved = tlImgZoom.movedDist;
        delete tlImgZoom.pointers[e.pointerId];
        var pts = tlImgPointerList();

        // 핀치 종료 후 한 손가락 남으면 팬으로 이어감
        if (pts.length === 1) {
            var p = pts[0];
            tlImgZoom.dragging = true;
            tlImgZoom.activePointerId = null;
            for (var id in tlImgZoom.pointers) {
                if (tlImgZoom.pointers.hasOwnProperty(id)) {
                    tlImgZoom.activePointerId = Number(id) || id;
                    break;
                }
            }
            tlImgZoom.panStartX = p.x;
            tlImgZoom.panStartY = p.y;
            tlImgZoom.originX = tlImgZoom.x;
            tlImgZoom.originY = tlImgZoom.y;
            tlImgZoom.pinchStartDist = 0;
            tlImgZoom.gestureMode = tlImgIsZoomed() ? 'pan' : 'none';
            tlImgZoom.gestureStartScale = tlImgZoom.scale;
            stage.classList.add('is-dragging');
            stage.classList.remove('is-sliding');
            return;
        }

        // 모든 포인터 종료
        tlImgZoom.dragging = false;
        tlImgZoom.pinchStartDist = 0;
        tlImgZoom.activePointerId = null;
        stage.classList.remove('is-dragging', 'is-sliding');

        // 확대 중이거나 팬/핀치였으면 절대 슬라이드하지 않음
        if (mode === 'pan' || mode === 'pinch' || startScale > 1.02 || tlImgZoom.scale > 1.02) {
            tlImgZoom.gestureMode = 'none';
            tlImgClampPan();
            tlImgApplyTransform();
            return;
        }

        // 1배율 + 가로 스와이프만 이전/다음
        if (mode === 'slide' && wasPts.length === 1 && currentImages.length > 1 && moved > 50) {
            var dx = e.clientX - startX;
            var dy = e.clientY - startY;
            if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.2) {
                if (dx < 0) nextImage();
                else prevImage();
                tlImgZoom.gestureMode = 'none';
                return;
            }
        }

        // 슬라이드 예고 트랜스폼 원복
        if (mode === 'slide') {
            tlImgApplyTransform();
        }
        tlImgZoom.gestureMode = 'none';
    }

    stage.addEventListener('pointerup', tlImgPointerEnd);
    stage.addEventListener('pointercancel', tlImgPointerEnd);
    stage.addEventListener('lostpointercapture', function(e) {
        // 캡처 상실 시에도 포인터 정리 (팬 도중 슬라이드로 떨어지지 않게 mode 유지)
        if (tlImgZoom.pointers[e.pointerId]) {
            tlImgPointerEnd(e);
        }
    });

    stage.addEventListener('dblclick', function(e) {
        e.preventDefault();
        if (tlImgZoom.scale > 1.05) tlImgResetZoom();
        else tlImgZoomAt(e.clientX, e.clientY, 2.5);
        tlImgZoom.gestureMode = 'pan';
        tlImgZoom.gestureStartScale = tlImgZoom.scale;
    });
}

// 이미지 보기 (클릭한 이미지의 부모 컨테이너에서 모든 이미지 수집)
function viewImage(imgUrl, containerEl) {
    currentImages = [];
    currentImageIndex = 0;
    
    if (containerEl) {
        var mediaRoot = containerEl.closest ? containerEl.closest('.tl-media') : null;
        if (mediaRoot) {
            var mediaItems = mediaRoot.querySelectorAll('.tl-media-item img');
            mediaItems.forEach(function(img, idx) {
                currentImages.push(img.src);
                if (img.src === imgUrl || img.getAttribute('src') === imgUrl) {
                    currentImageIndex = idx;
                }
            });
        }
    }
    
    // 이미지가 없으면 클릭한 이미지만 표시
    if (currentImages.length === 0) {
        currentImages = [imgUrl];
        currentImageIndex = 0;
    }
    
    showImageModal();
}

// 이미지 모달 표시
function showImageModal() {
    var modal = document.getElementById('tl-image-modal');
    
    // 모달이 없으면 생성
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'tl-image-modal';
        modal.className = 'tl-image-modal';
        modal.style.display = 'none';
        modal.innerHTML = '<div class="tl-image-modal-backdrop" onclick="closeImageModal()"></div>' +
            '<button class="tl-image-modal-close" onclick="closeImageModal()">&times;</button>' +
            '<button class="tl-image-nav tl-image-prev" onclick="prevImage()">&lsaquo;</button>' +
            '<button class="tl-image-nav tl-image-next" onclick="nextImage()">&rsaquo;</button>' +
            '<div class="tl-image-modal-content">' +
            '  <div class="tl-image-zoom-stage" id="tl-image-zoom-stage">' +
            '    <img id="tl-image-modal-img" src="" alt="">' +
            '  </div>' +
            '</div>' +
            '<div class="tl-image-zoom-hint">휠/핀치로 확대 · 드래그로 이동</div>' +
            '<div class="tl-image-counter"><span id="tl-image-current">1</span> / <span id="tl-image-total">1</span></div>';
        document.body.appendChild(modal);
        tlImgZoom.bound = false;
    } else if (!document.getElementById('tl-image-zoom-stage')) {
        var content = modal.querySelector('.tl-image-modal-content');
        if (content) {
            var oldImg = document.getElementById('tl-image-modal-img');
            var src = oldImg ? oldImg.src : '';
            content.innerHTML = '<div class="tl-image-zoom-stage" id="tl-image-zoom-stage"><img id="tl-image-modal-img" src="' + src + '" alt=""></div>';
            tlImgZoom.bound = false;
        }
        if (!modal.querySelector('.tl-image-zoom-hint')) {
            var hint = document.createElement('div');
            hint.className = 'tl-image-zoom-hint';
            hint.textContent = '휠/핀치로 확대 · 드래그로 이동';
            modal.appendChild(hint);
        }
    }
    
    var img = document.getElementById('tl-image-modal-img');
    var currentSpan = document.getElementById('tl-image-current');
    var totalSpan = document.getElementById('tl-image-total');
    var prevBtn = modal.querySelector('.tl-image-prev');
    var nextBtn = modal.querySelector('.tl-image-next');
    
    tlImgResetZoom();
    img.src = currentImages[currentImageIndex];
    currentSpan.textContent = currentImageIndex + 1;
    totalSpan.textContent = currentImages.length;
    
    // 이미지가 1개면 네비게이션 숨김
    if (currentImages.length <= 1) {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
    } else {
        prevBtn.style.display = 'flex';
        nextBtn.style.display = 'flex';
    }
    
    var wasOpen = modal.style.display === 'flex' || modal.style.display === 'block';
    modal.style.display = 'flex';
    tlImgEnsureZoomBound();
    if (!wasOpen) {
        tlLockBodyScroll();
    }
}

// 이미지 모달 닫기
function closeImageModal() {
    if (document.activeElement && typeof document.activeElement.blur === 'function') {
        document.activeElement.blur();
    }
    var modal = document.getElementById('tl-image-modal');
    if (!modal || modal.style.display === 'none') return;
    modal.style.display = 'none';
    tlImgResetZoom();
    
    // 글 상세 모달이 열려있으면 스크롤 잠금 유지 (카운트만 감소)
    var postModal = document.getElementById('tl-modal');
    var postOpen = postModal && postModal.style.display !== 'none' && postModal.style.display !== '';
    if (postOpen) {
        if (tlBodyScrollLockCount > 1) {
            tlBodyScrollLockCount--;
        }
    } else {
        tlUnlockBodyScroll();
    }
}

// 이전 이미지
function prevImage() {
    if (currentImages.length <= 1) return;
    currentImageIndex = (currentImageIndex - 1 + currentImages.length) % currentImages.length;
    showImageModal();
}

// 다음 이미지
function nextImage() {
    if (currentImages.length <= 1) return;
    currentImageIndex = (currentImageIndex + 1) % currentImages.length;
    showImageModal();
}

// ============================================
// 키보드 이벤트
// ============================================

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        // 이미지 모달 먼저 닫기
        var imgModal = document.getElementById('tl-image-modal');
        if (imgModal && imgModal.style.display !== 'none') {
            closeImageModal();
            return;
        }
        // 글 상세 모달 닫기
        closeModal();
    }
    
    // 이미지 모달에서 좌우 화살표 키
    var imgModal = document.getElementById('tl-image-modal');
    if (imgModal && imgModal.style.display !== 'none') {
        if (e.key === 'ArrowLeft') prevImage();
        if (e.key === 'ArrowRight') nextImage();
    }
});

// ============================================
// 스레드 더 보기
// ============================================

/**
 * 스레드 더 보기 (이전 대화 로드)
 * @param {boolean|number} loadAll - true면 남은 대화를 한 번에 로드. 숫자면 구버전 from_wr_id
 */
function loadMoreThread(loadAll) {
    var moreBox = document.getElementById('tl_thread_more');
    if (!moreBox) return;

    var fromWrId = parseInt(moreBox.getAttribute('data-from'), 10) || 0;
    if (typeof loadAll === 'number' && loadAll > 1) {
        fromWrId = loadAll;
        loadAll = false;
    }
    loadAll = !!loadAll;
    if (!fromWrId) return;

    var moreBtn = moreBox.querySelector('.tl-thread-more-btn');
    var allBtn = moreBox.querySelector('.tl-thread-all-btn');
    var moreLabel = '<span>이전 대화 더 보기</span><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 8l-6 6 1.41 1.41L12 10.83l4.59 4.58L18 14z"/></svg>';
    var allLabel = '<span>전체 대화 보기</span>';

    function restoreThreadMoreBtns() {
        if (moreBtn) {
            moreBtn.disabled = false;
            moreBtn.innerHTML = moreLabel;
        }
        if (allBtn) {
            allBtn.disabled = false;
            allBtn.innerHTML = allLabel;
        }
    }

    if (moreBtn) {
        moreBtn.disabled = true;
        if (!loadAll) moreBtn.innerHTML = '<span>로딩 중...</span>';
    }
    if (allBtn) {
        allBtn.disabled = true;
        if (loadAll) allBtn.innerHTML = '<span>로딩 중...</span>';
    }

    var url = './ajax/ajax_thread_more.php?tl_table=' + encodeURIComponent(tlTable)
        + '&from_wr_id=' + fromWrId
        + (loadAll ? '&all=1' : '&limit=5');

    fetch(url)
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success && data.html) {
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = data.html;

            var nextElement = moreBox.nextSibling;
            while (tempDiv.firstChild) {
                moreBox.parentNode.insertBefore(tempDiv.firstChild, nextElement);
            }

            if (data.has_more && data.next_from > 0) {
                moreBox.setAttribute('data-from', data.next_from);
                if (loadAll) {
                    loadMoreThread(true);
                    return;
                }
                restoreThreadMoreBtns();
            } else {
                moreBox.remove();
            }
        } else if (data.success) {
            moreBox.remove();
        } else {
            restoreThreadMoreBtns();
        }
    })
    .catch(function(err) {
        console.error('스레드 로드 오류:', err);
        restoreThreadMoreBtns();
    });
}

// ============================================
// 검색 함수
// ============================================

/**
 * 검색 폼 제출
 */
function submitSearch(e) {
    if (e) e.preventDefault();
    var input = document.getElementById('tl-search-input');
    var clearBtn = document.getElementById('tl-search-clear');
    
    if (input) {
        var newQuery = input.value.trim();
        
        // 클리어 버튼 표시/숨기기
        if (clearBtn) {
            clearBtn.style.display = newQuery ? 'block' : 'none';
        }
        
        // 검색어가 변경된 경우에만 로드
        if (newQuery !== currentSearchQuery) {
            currentSearchQuery = newQuery;
            loadTimeline(true);
        }
    }
    return false;
}

/**
 * 검색어 초기화
 */
function clearSearch() {
    var input = document.getElementById('tl-search-input');
    var clearBtn = document.getElementById('tl-search-clear');
    
    currentSearchQuery = '';
    
    if (input) {
        input.value = '';
    }
    if (clearBtn) {
        clearBtn.style.display = 'none';
    }
    
    // 전체 목록 다시 로드
    loadTimeline(true);
}

// ============================================
// 멘션 검색 (간소화 버전)
// ============================================

/**
 * 멘션 검색 초기화
 */
function initMentionSearch() {
    var searchBox = document.getElementById('tl-mention-search-box');
    var searchInput = document.getElementById('tl-mention-search');
    var resultsDiv = document.getElementById('tl-mention-results');
    var textarea = document.getElementById('tl-content');
    
    if (!searchBox || !searchInput || !resultsDiv || !textarea) {
        return;
    }
    
    
    var searchTimer = null;
    var currentResults = [];
    
    // @ 버튼 추가
    var mentionBtn = document.createElement('button');
    mentionBtn.type = 'button';
    mentionBtn.className = 'tl-mention-btn tl-btns';
    mentionBtn.innerHTML = '@';
    mentionBtn.title = '멘션';
    mentionBtn.onclick = function(e) {
        e.preventDefault();
        if (searchBox.style.display === 'none' || !searchBox.style.display) {
            searchBox.style.display = 'block';
            searchInput.focus();
        } else {
            searchBox.style.display = 'none';
            resultsDiv.classList.remove('show');
        }
    };
    
    // 액션 버튼 영역에 추가 (왼쪽 영역 첫 번째에)
    var leftActions = document.querySelector('.tl-composer-left');
    if (leftActions) {
        leftActions.insertBefore(mentionBtn, leftActions.firstChild);
    }
    
    // 검색 입력 이벤트
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        var query = this.value.trim();
        
        if (!query) {
            resultsDiv.classList.remove('show');
            return;
        }
        
        searchTimer = setTimeout(function() {
            searchMentionUsersNew(query, resultsDiv, textarea);
        }, 200);
    });
    
    // ESC 키로 닫기
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchBox.style.display = 'none';
            resultsDiv.classList.remove('show');
            textarea.focus();
        }
    });
}

/**
 * 멘션 사용자 검색 (새 버전)
 */
function searchMentionUsersNew(query, resultsDiv, textarea) {
    if (query.length > 6) return;
    
    var url = (window.g5_url || '') + '/timeline/ajax/ajax_mention_search.php?tl_table=' + tlTable + '&q=' + encodeURIComponent(query);
    
    fetch(url)
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.users && data.users.length > 0) {
            showMentionResults(data.users, resultsDiv, textarea);
        } else {
            resultsDiv.innerHTML = '<div class="tl-mention-item" style="padding:15px;text-align:center;color:#999;">검색 결과가 없습니다</div>';
            resultsDiv.classList.add('show');
        }
    })
    .catch(function(err) {
        console.error('멘션 검색 오류:', err);
    });
}

/**
 * 검색 결과 표시
 */
function showMentionResults(users, resultsDiv, textarea) {
    try {
        resultsDiv.innerHTML = '';
        
        users.forEach(function(user) {
            var item = document.createElement('div');
            item.className = 'tl-mention-item';
            item.style.cursor = 'pointer';
            item.style.pointerEvents = 'auto';
        
        var img = document.createElement('img');
        img.src = user.avatar;
        img.className = 'tl-mention-avatar';
        img.onerror = function() { 
            this.onerror = null; // 무한 루프 방지
            this.src = (window.g5_url || '') + '/img/no_profile.png'; 
        };
        
        var info = document.createElement('div');
        info.className = 'tl-mention-info';
        
        var name = document.createElement('span');
        name.className = 'tl-mention-name';
        name.textContent = user.name;
        info.appendChild(name);
        
        if (user.mention_name && user.mention_name !== user.name) {
            var handle = document.createElement('span');
            handle.className = 'tl-mention-handle';
            handle.textContent = '@' + user.mention_name;
            info.appendChild(handle);
        }
        
        if (user.bio) {
            var bio = document.createElement('span');
            bio.className = 'tl-mention-bio';
            bio.textContent = user.bio;
            info.appendChild(bio);
        }
        
        item.appendChild(img);
        item.appendChild(info);
        
        // 클릭 이벤트 (중요: 클로저로 mentionName 캡처)
        (function(mentionName) {
            item.onclick = function(e) {
                e.stopPropagation(); // 외부 클릭 이벤트 전파 방지
                insertMention(mentionName);
            };
        })(user.mention_name);
        
            resultsDiv.appendChild(item);
        });
        
        resultsDiv.classList.add('show');
    } catch(err) {
        console.error('[멘션] 결과 표시 오류:', err);
    }
}

/**
 * 멘션 삽입
 */
function insertMention(mentionName) {
    var textarea = document.getElementById('tl-content');
    var searchBox = document.getElementById('tl-mention-search-box');
    var searchInput = document.getElementById('tl-mention-search');
    var resultsDiv = document.getElementById('tl-mention-results');
    
    if (!textarea) return;
    
    // 현재 커서 위치에 @멘션 삽입
    var cursorPos = textarea.selectionStart;
    var textBefore = textarea.value.substring(0, cursorPos);
    var textAfter = textarea.value.substring(cursorPos);
    
    var mentionText = '@' + mentionName + ' ';
    textarea.value = textBefore + mentionText + textAfter;
    
    // 커서 위치 조정
    var newCursorPos = cursorPos + mentionText.length;
    textarea.setSelectionRange(newCursorPos, newCursorPos);
    textarea.focus();
    
    // 검색 초기화
    searchInput.value = '';
    searchBox.style.display = 'none';
    resultsDiv.classList.remove('show');
    
    // 글자수 카운터 업데이트
    if (typeof updateCharCount === 'function') {
        updateCharCount(textarea);
    } else {
        // 수동으로 글자수 업데이트
        var charCount = document.getElementById('tl-char-count');
        if (charCount) {
            var count = textarea.value.length;
            var max = textarea.getAttribute('maxlength') || contentMaxLength || 500;
            charCount.textContent = count + '/' + max;
        }
        
        // 제출 버튼 활성화/비활성화
        var submitBtn = document.getElementById('tl-submit');
        if (submitBtn) {
            submitBtn.disabled = textarea.value.trim().length === 0;
        }
    }
}

/**
 * 인라인 답글 폼용 멘션 검색 초기화
 * @param {string} formPrefix - 폼 접두사 (예: 'ir_44')
 */
function initMentionSearchForReply(formPrefix) {
    var searchBox = document.getElementById(formPrefix + '_mention_search');
    var searchInput = document.getElementById(formPrefix + '_mention_input');
    var resultsDiv = document.getElementById(formPrefix + '_mention_results');
    var textarea = document.getElementById(formPrefix + '_content');
    var composerLeft = document.getElementById(formPrefix + '_composer_left');
    
    if (!searchBox || !searchInput || !resultsDiv || !textarea) {
        return;
    }
    
    
    var searchTimer = null;
    
    // @ 버튼 추가
    var mentionBtn = document.createElement('button');
    mentionBtn.type = 'button';
    mentionBtn.className = 'tl-mention-btn';
    mentionBtn.innerHTML = '@';
    mentionBtn.title = '멘션';
    mentionBtn.style.cssText = 'padding: 8px 12px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: bold;';
    mentionBtn.onclick = function(e) {
        e.preventDefault();
        if (searchBox.style.display === 'none' || !searchBox.style.display) {
            searchBox.style.display = 'block';
            searchInput.focus();
        } else {
            searchBox.style.display = 'none';
            resultsDiv.classList.remove('show');
        }
    };
    
    // 액션 버튼 영역 첫 번째에 추가
    if (composerLeft) {
        composerLeft.insertBefore(mentionBtn, composerLeft.firstChild);
    }
    
    // 검색 입력 이벤트
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        var query = this.value.trim();
        
        if (!query) {
            resultsDiv.classList.remove('show');
            return;
        }
        
        searchTimer = setTimeout(function() {
            searchMentionUsersForReply(query, resultsDiv, textarea, formPrefix);
        }, 200);
    });
    
    // ESC 키로 닫기
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchBox.style.display = 'none';
            resultsDiv.classList.remove('show');
            textarea.focus();
        }
    });
}

/**
 * 인라인 답글용 멘션 검색
 */
function searchMentionUsersForReply(query, resultsDiv, textarea, formPrefix) {
    if (query.length > 6) return;
    
    var url = (window.g5_url || '') + '/timeline/ajax/ajax_mention_search.php?tl_table=' + tlTable + '&q=' + encodeURIComponent(query);
    
    fetch(url)
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.users && data.users.length > 0) {
            showMentionResultsForReply(data.users, resultsDiv, textarea, formPrefix);
        } else {
            resultsDiv.innerHTML = '<div class="tl-mention-item" style="padding:15px;text-align:center;color:#999;">검색 결과가 없습니다</div>';
            resultsDiv.classList.add('show');
        }
    })
    .catch(function(err) {
        console.error('멘션 검색 오류:', err);
    });
}

/**
 * 인라인 답글용 검색 결과 표시
 */
function showMentionResultsForReply(users, resultsDiv, textarea, formPrefix) {
    try {
        resultsDiv.innerHTML = '';
        
        users.forEach(function(user) {
            var item = document.createElement('div');
            item.className = 'tl-mention-item';
            item.style.cursor = 'pointer';
            item.style.pointerEvents = 'auto';
            
            var img = document.createElement('img');
            img.src = user.avatar;
            img.className = 'tl-mention-avatar';
            img.onerror = function() { 
                this.onerror = null;
                this.src = (window.g5_url || '') + '/img/no_profile.png'; 
            };
            
            var info = document.createElement('div');
            info.className = 'tl-mention-info';
            
            var name = document.createElement('span');
            name.className = 'tl-mention-name';
            name.textContent = user.name;
            info.appendChild(name);
            
            if (user.mention_name && user.mention_name !== user.name) {
                var handle = document.createElement('span');
                handle.className = 'tl-mention-handle';
                handle.textContent = '@' + user.mention_name;
                info.appendChild(handle);
            }
            
            if (user.bio) {
                var bio = document.createElement('span');
                bio.className = 'tl-mention-bio';
                bio.textContent = user.bio;
                info.appendChild(bio);
            }
            
            item.appendChild(img);
            item.appendChild(info);
            
            // 클릭 이벤트
            (function(mentionName) {
                item.onclick = function(e) {
                    e.stopPropagation();
                    insertMentionForReply(mentionName, formPrefix);
                };
            })(user.mention_name);
            
            resultsDiv.appendChild(item);
        });
        
        resultsDiv.classList.add('show');
    } catch(err) {
        console.error('[멘션 답글] 결과 표시 오류:', err);
    }
}

/**
 * 인라인 답글용 멘션 삽입
 */
function insertMentionForReply(mentionName, formPrefix) {
    var textarea = document.getElementById(formPrefix + '_content');
    var searchBox = document.getElementById(formPrefix + '_mention_search');
    var searchInput = document.getElementById(formPrefix + '_mention_input');
    var resultsDiv = document.getElementById(formPrefix + '_mention_results');
    
    if (!textarea) return;
    
    // 현재 커서 위치에 @멘션 삽입
    var cursorPos = textarea.selectionStart;
    var textBefore = textarea.value.substring(0, cursorPos);
    var textAfter = textarea.value.substring(cursorPos);
    
    var mentionText = '@' + mentionName + ' ';
    textarea.value = textBefore + mentionText + textAfter;
    
    // 커서 위치 조정
    var newCursorPos = cursorPos + mentionText.length;
    textarea.setSelectionRange(newCursorPos, newCursorPos);
    textarea.focus();
    
    // 검색 초기화
    searchInput.value = '';
    searchBox.style.display = 'none';
    resultsDiv.classList.remove('show');
    
    // 글자수 카운터 업데이트 (irUpdateCharCount 함수 사용)
    if (typeof irUpdateCharCount === 'function') {
        var maxLength = textarea.getAttribute('maxlength') || 500;
        irUpdateCharCount(formPrefix, maxLength);
    }
}

// ============================================
// 멘션 자동완성 (레거시 - 비활성화)
// ============================================

var MENTION_LEGACY_DISABLED = true;

var mentionState = {
    active: false,
    textarea: null,
    dropdown: null,
    startPos: 0,
    query: '',
    selectedIndex: 0,
    results: [],
    debounceTimer: null
};

/**
 * 멘션 자동완성 초기화
 * @param {HTMLTextAreaElement} textarea - 대상 textarea
 */
function initMentionAutocomplete(textarea) {
    if (!textarea) return;
    
    // 이미 초기화되었는지 체크 (이벤트 바인딩 여부로 확인)
    if (textarea._mentionInitialized) return;
    textarea._mentionInitialized = true;
    
    // 드롭다운 생성
    var dropdown = document.createElement('div');
    dropdown.className = 'tl-mention-dropdown';
    dropdown.style.display = 'none';
    textarea.parentNode.style.position = 'relative';
    textarea.parentNode.appendChild(dropdown);
    
    // 입력 이벤트 (최적화: @ 관련 입력만 처리)
    var inputHandler = function(e) {
        // 빠른 체크: @ 문자가 없으면 즉시 종료
        var value = textarea.value;
        var cursorPos = textarea.selectionStart;
        
        // 커서 주변 10자만 체크 (멘션명 최대 6자 + @)
        var checkStart = Math.max(0, cursorPos - 10);
        var checkText = value.substring(checkStart, cursorPos);
        
        if (checkText.indexOf('@') === -1) {
            // @ 문자가 없으면 드롭다운이 열려있을 때만 닫기
            if (mentionState.active) {
                hideMentionDropdown(dropdown);
            }
            return;
        }
        
        // @ 문자가 있으면 전체 처리
        handleMentionInput(textarea, dropdown);
    };
    
    var keydownHandler = function(e) {
        handleMentionKeydown(e, textarea, dropdown);
    };
    
    var blurHandler = function(e) {
        setTimeout(function() {
            hideMentionDropdown(dropdown);
        }, 200);
    };
    
    textarea.addEventListener('input', inputHandler, { passive: true });
    textarea.addEventListener('keydown', keydownHandler);
    textarea.addEventListener('blur', blurHandler, { passive: true });
    
    // 메모리 누수 방지: 정리 함수 저장
    textarea._mentionCleanup = function() {
        textarea.removeEventListener('input', inputHandler);
        textarea.removeEventListener('keydown', keydownHandler);
        textarea.removeEventListener('blur', blurHandler);
        if (dropdown && dropdown.parentNode) {
            dropdown.parentNode.removeChild(dropdown);
        }
    };
}

/**
 * 멘션 입력 처리 (최적화 버전)
 */
function handleMentionInput(textarea, dropdown) {
    var value = textarea.value;
    var cursorPos = textarea.selectionStart;
    
    // 최적화: 커서 이전 최대 20자만 검사 (멘션명 최대 6자 + 여유)
    var searchStart = Math.max(0, cursorPos - 20);
    var beforeCursor = value.substring(searchStart, cursorPos);
    var atIndex = beforeCursor.lastIndexOf('@');
    
    if (atIndex === -1) {
        if (mentionState.active) {
            hideMentionDropdown(dropdown);
        }
        return;
    }
    
    // 실제 @ 위치 (전체 텍스트 기준)
    var actualAtIndex = searchStart + atIndex;
    
    // @ 앞 문자 체크 (첫 문자이거나 공백/줄바꿈)
    if (actualAtIndex > 0) {
        var charBefore = value.charAt(actualAtIndex - 1);
        if (charBefore !== ' ' && charBefore !== '\n' && charBefore !== '\r' && charBefore !== '\t') {
            if (mentionState.active) {
                hideMentionDropdown(dropdown);
            }
            return;
        }
    }
    
    // @ 뒤 텍스트 (공백 미포함)
    var afterAt = beforeCursor.substring(atIndex + 1);
    
    // 6자 초과 시 즉시 종료 (멘션명 최대 길이)
    if (afterAt.length > 6) {
        if (mentionState.active) {
            hideMentionDropdown(dropdown);
        }
        return;
    }
    
    // 공백/줄바꿈 체크
    var spaceIndex = afterAt.search(/[\s\n]/);
    if (spaceIndex !== -1) {
        if (mentionState.active) {
            hideMentionDropdown(dropdown);
        }
        return;
    }
    
    // 검색어 설정
    mentionState.query = afterAt;
    mentionState.startPos = actualAtIndex;
    mentionState.textarea = textarea;
    mentionState.dropdown = dropdown;
    
    // 디바운스로 검색 (6자 제한으로 더 빠른 응답)
    clearTimeout(mentionState.debounceTimer);
    mentionState.debounceTimer = setTimeout(function() {
        searchMentionUsers(afterAt, dropdown);
    }, 200);
}

/**
 * 멘션 사용자 검색 (최적화: 중복 요청 방지)
 */
var mentionLastQuery = '';
var mentionFetchController = null;

function searchMentionUsers(query, dropdown) {
    // 같은 검색어면 중복 요청 방지
    if (query === mentionLastQuery) {
        return;
    }
    mentionLastQuery = query;
    
    // 이전 요청 취소
    if (mentionFetchController) {
        mentionFetchController.abort();
    }
    
    // 타임라인 URL 베이스 경로
    var baseUrl = (window.g5_url || '') + '/timeline';
    var url = baseUrl + '/ajax/ajax_mention_search.php?tl_table=' + tlTable + '&q=' + encodeURIComponent(query);
    
    // AbortController 지원 체크
    if (typeof AbortController !== 'undefined') {
        mentionFetchController = new AbortController();
        var signal = mentionFetchController.signal;
        
        fetch(url, { signal: signal })
        .then(function(response) { 
            return response.json(); 
        })
        .then(function(data) {
            mentionFetchController = null;
            if (data.users && data.users.length > 0) {
                mentionState.results = data.users;
                mentionState.selectedIndex = 0;
                showMentionDropdown(dropdown, data.users);
            } else {
                hideMentionDropdown(dropdown);
            }
        })
        .catch(function(err) {
            if (err.name !== 'AbortError') {
                console.error('[멘션] 검색 오류:', err);
            }
            mentionFetchController = null;
            hideMentionDropdown(dropdown);
        });
    } else {
        // AbortController 미지원 브라우저
        fetch(url)
        .then(function(response) { 
            return response.json(); 
        })
        .then(function(data) {
            if (data.users && data.users.length > 0) {
                mentionState.results = data.users;
                mentionState.selectedIndex = 0;
                showMentionDropdown(dropdown, data.users);
            } else {
                hideMentionDropdown(dropdown);
            }
        })
        .catch(function(err) {
            console.error('[멘션] 검색 오류:', err);
            hideMentionDropdown(dropdown);
        });
    }
}

/**
 * 멘션 드롭다운 표시 (최적화: DocumentFragment 사용)
 */
function showMentionDropdown(dropdown, users) {
    var fragment = document.createDocumentFragment();
    var noProfileUrl = (window.g5_url || '') + '/img/no_profile.png';
    
    users.forEach(function(user, index) {
        var item = document.createElement('div');
        item.className = 'tl-mention-item' + (index === mentionState.selectedIndex ? ' selected' : '');
        item.setAttribute('data-index', index);
        item.onclick = function() { selectMentionUser(index); };
        
        var img = document.createElement('img');
        img.src = user.avatar;
        img.className = 'tl-mention-avatar';
        img.onerror = function() { 
            this.onerror = null; // 무한 루프 방지
            this.src = noProfileUrl; 
        };
        
        var info = document.createElement('div');
        info.className = 'tl-mention-info';
        
        var name = document.createElement('span');
        name.className = 'tl-mention-name';
        name.textContent = user.name;
        info.appendChild(name);
        
        if (user.mention_name && user.mention_name !== user.name) {
            var handle = document.createElement('span');
            handle.className = 'tl-mention-handle';
            handle.textContent = '@' + user.mention_name;
            info.appendChild(handle);
        }
        
        if (user.bio) {
            var bio = document.createElement('span');
            bio.className = 'tl-mention-bio';
            bio.textContent = user.bio;
            info.appendChild(bio);
        }
        
        item.appendChild(img);
        item.appendChild(info);
        fragment.appendChild(item);
    });
    
    dropdown.innerHTML = '';
    dropdown.appendChild(fragment);
    dropdown.style.display = 'block';
    mentionState.active = true;
}

/**
 * 멘션 드롭다운 숨기기
 */
function hideMentionDropdown(dropdown) {
    if (dropdown) {
        dropdown.style.display = 'none';
    }
    mentionState.active = false;
    mentionState.results = [];
    mentionState.selectedIndex = 0;
    mentionLastQuery = ''; // 검색어 캐시 리셋
    
    // 진행 중인 fetch 요청 취소
    if (mentionFetchController) {
        mentionFetchController.abort();
        mentionFetchController = null;
    }
}

/**
 * 멘션 키보드 이벤트 처리
 */
function handleMentionKeydown(e, textarea, dropdown) {
    if (!mentionState.active) return;
    
    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            mentionState.selectedIndex = Math.min(mentionState.selectedIndex + 1, mentionState.results.length - 1);
            updateMentionSelection(dropdown);
            break;
            
        case 'ArrowUp':
            e.preventDefault();
            mentionState.selectedIndex = Math.max(mentionState.selectedIndex - 1, 0);
            updateMentionSelection(dropdown);
            break;
            
        case 'Enter':
        case 'Tab':
            if (mentionState.results.length > 0) {
                e.preventDefault();
                selectMentionUser(mentionState.selectedIndex);
            }
            break;
            
        case 'Escape':
            hideMentionDropdown(dropdown);
            break;
    }
}

/**
 * 멘션 선택 항목 업데이트
 */
function updateMentionSelection(dropdown) {
    var items = dropdown.querySelectorAll('.tl-mention-item');
    items.forEach(function(item, index) {
        if (index === mentionState.selectedIndex) {
            item.classList.add('selected');
            item.scrollIntoView({ block: 'nearest' });
        } else {
            item.classList.remove('selected');
        }
    });
}

/**
 * 멘션 사용자 선택
 */
function selectMentionUser(index) {
    var user = mentionState.results[index];
    if (!user) return;
    
    var textarea = mentionState.textarea;
    var dropdown = mentionState.dropdown;
    
    if (!textarea) return;
    
    var value = textarea.value;
    var startPos = mentionState.startPos;
    var cursorPos = textarea.selectionStart;
    
    // @부터 현재 커서까지 교체
    var mentionText = '@' + user.mention_name + ' ';
    var newValue = value.substring(0, startPos) + mentionText + value.substring(cursorPos);
    
    textarea.value = newValue;
    
    // 커서 위치 조정
    var newCursorPos = startPos + mentionText.length;
    textarea.setSelectionRange(newCursorPos, newCursorPos);
    textarea.focus();
    
    // 드롭다운 숨기기
    hideMentionDropdown(dropdown);
    
    // 글자수 카운터 업데이트
    updateCharCount(textarea);
}

/**
 * 글자수 카운터 업데이트 (범용)
 */
function updateCharCount(textarea) {
    var countEl = document.getElementById('tl-char-count');
    if (countEl && textarea) {
        var maxLen = tlConfig.contentMaxLength || 500;
        countEl.textContent = textarea.value.length + '/' + maxLen;
    }
    
    // 제출 버튼 상태 업데이트
    var submitBtn = document.getElementById('tl-submit');
    if (submitBtn && textarea) {
        var hasContent = textarea.value.trim().length > 0;
        var hasImages = selectedFiles && selectedFiles.length > 0;
        submitBtn.disabled = !hasContent && !hasImages;
    }
}

/**
 * HTML 이스케이프
 */
function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * 모든 textarea에 멘션 자동완성 초기화
 */
function initAllMentionAutocomplete() {
    // 메인 글쓰기 영역
    var mainTextarea = document.getElementById('tl-content');
    if (mainTextarea) {
        initMentionAutocomplete(mainTextarea);
    }
    
    // 기존에 있는 모든 댓글 textarea도 초기화
    var existingTextareas = document.querySelectorAll('textarea[name="comment_content"], textarea.tl-comment-input, textarea.tl-reply-input');
    existingTextareas.forEach(function(ta) {
        initMentionAutocomplete(ta);
    });
    
    // 댓글/답글 영역은 동적으로 생성되므로 MutationObserver 사용 (최적화)
    var observer = new MutationObserver(function(mutations) {
        var foundTextareas = [];
        
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length === 0) return;
            
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType !== 1) return;
                
                // 자기 자신이 textarea인 경우
                if (node.tagName === 'TEXTAREA' && !node._mentionInitialized) {
                    foundTextareas.push(node);
                    return;
                }
                
                // 자식 중 textarea 찾기 (특정 클래스/ID만)
                if (node.querySelectorAll) {
                    var textareas = node.querySelectorAll('textarea[name="comment_content"], textarea.tl-comment-input, textarea.tl-reply-input, textarea#tl-content');
                    for (var i = 0; i < textareas.length; i++) {
                        if (!textareas[i]._mentionInitialized) {
                            foundTextareas.push(textareas[i]);
                        }
                    }
                }
            });
        });
        
        // 일괄 초기화 (리플로우 최소화)
        if (foundTextareas.length > 0) {
            foundTextareas.forEach(function(ta) {
                initMentionAutocomplete(ta);
            });
        }
    });
    
    // 타임라인 컨테이너만 감시 (전체 body 대신)
    var tlContainer = document.getElementById('tl-container') || document.body;
    observer.observe(tlContainer, { 
        childList: true, 
        subtree: true 
    });
}

// DOM 로드 시 멘션 초기화
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        // 새로운 간소화 버전 초기화
        initMentionSearch();
        initStandalonePostFocus();
        
        // 레거시 자동완성 비활성화
        if (!MENTION_LEGACY_DISABLED) {
            setTimeout(initAllMentionAutocomplete, 100);
        }
    });
} else {
    // DOM이 이미 로드됨
    initMentionSearch();
    initStandalonePostFocus();
    
    // 레거시 자동완성 비활성화
    if (!MENTION_LEGACY_DISABLED) {
        if (typeof requestIdleCallback === 'function') {
            requestIdleCallback(initAllMentionAutocomplete, { timeout: 500 });
        } else {
            setTimeout(initAllMentionAutocomplete, 100);
        }
    }
}
