<?php
/**
 * 타임라인 내 설정 페이지
 * timeline_user 프로필 설정 관리
 */
include_once('./_common.php');

// 로그인 체크
if (!$is_member) {
    alert('로그인이 필요합니다.', './index.php?tl_table='.$tl_table);
}

// 캐릭터 체크
$my_ch_id = ses($character, 'ch_id', 0, 'int');
if (!$my_ch_id) {
    alert('캐릭터 정보가 없습니다.', './index.php?tl_table='.$tl_table);
}

$my_character = sql_fetch("SELECT * FROM {$g5['character_table']} WHERE ch_id = '{$my_ch_id}'");
$tl_safe = sql_escape_string($tl_table);
$tu = sql_fetch("
    SELECT * FROM {$g5['timeline_user_table']}
    WHERE tl_table = '{$tl_safe}' AND ch_id = '{$my_ch_id}'
", false);
if (!is_array($tu)) {
    $tu = array();
}

$g5['title'] = '내 설정 - '.$timeline['tl_subject'];
include_once(G5_PATH.'/_head.php');

$ch_name = ses($my_character, 'ch_name', '', 'raw');
$tu_name = ses($tu, 'tu_name', '', 'raw');
$tu_men_id = ses($tu, 'tu_men_id', '', 'raw');
$tu_thumb = ses($tu, 'tu_thumb', '', 'raw');
$tu_header = ses($tu, 'tu_header', '', 'raw');
$tu_bio = ses($tu, 'tu_bio', '', 'raw');
$tu_profile = ses($tu, 'tu_profile', '', 'raw');
// 임시: 스레드 보기 비활성화 — 항상 normal
$tl_render_mode = 'normal';

if ($tu_name === '') $tu_name = $ch_name;
if ($tu_men_id === '') {
    $tu_men_id = function_exists('timeline_default_men_id_from_name')
        ? timeline_default_men_id_from_name($ch_name)
        : $ch_name;
}
?>

<?php timeline_print_css_head(); ?>

<div class="tl-settings-container">
    <div class="tl-settings-header">
        <a href="./profile.php?tl_table=<?php echo $tl_table; ?>&ch_id=<?php echo $my_ch_id; ?>" class="tl-back-btn">&larr; 프로필로 돌아가기</a>
    </div>
    
    <div id="tl-save-message" class="tl-save-message"></div>
    
    <form id="tl-settings-form">
        <!-- 프로필 설정 -->
        <div class="tl-settings-section">
            <h2>프로필 설정</h2>

            <div class="tl-settings-row">
                <label for="tu_name">닉네임</label>
                <input type="text" id="tu_name" name="tu_name" value="<?php echo h($tu_name); ?>" maxlength="50" placeholder="타임라인에 표시될 이름">
                <p class="tl-help">글·프로필에 표시되는 이름입니다.</p>
            </div>
            
            <div class="tl-settings-row">
                <label for="tu_men_id">멘션용 아이디</label>
                <input type="text" id="tu_men_id" name="tu_men_id" value="<?php echo h($tu_men_id); ?>" maxlength="6" placeholder="@멘션시 사용될 아이디" autocomplete="off">
                <p class="tl-help">다른 사용자가 @멘션할 때 검색·사용되는 아이디입니다. 띄어쓰기 없이 최대 6자입니다.</p>
            </div>
            
            <div class="tl-settings-row">
                <label for="tu_thumb">프로필 썸네일</label>
                <div class="tl-header-upload-wrap">
                    <div class="tl-header-tabs">
                        <button type="button" class="tl-header-tab active" onclick="switchThumbTab('url')">URL 입력</button>
                        <button type="button" class="tl-header-tab" onclick="switchThumbTab('file')">파일 업로드</button>
                    </div>
                    <div id="thumb-url-input" class="tl-header-input-area">
                        <input type="text" id="tu_thumb" name="tu_thumb" value="<?php echo h($tu_thumb); ?>" placeholder="https://example.com/thumb.jpg">
                    </div>
                    <div id="thumb-file-input" class="tl-header-input-area">
                        <label class="tl-file-upload-btn">
                            <input type="file" id="thumb_file" name="thumb_file" accept="image/*" onchange="previewThumbFile(this)">
                            <span>이미지 선택</span>
                        </label>
                        <span id="thumb-file-name" class="tl-file-name"></span>
                    </div>
                </div>
                <p class="tl-help">타임라인과 멘션 자동완성에 표시될 썸네일 이미지입니다. 큰 이미지는 업로드 시 자동으로 축소됩니다.</p>
                <div class="tl-header-preview">
                    <div class="tl-header-preview-img" id="thumb-preview" style="<?php echo $tu_thumb ? 'background-image: url('.h($tu_thumb).')' : ''; ?>; height: 80px;">
                    </div>
                </div>
            </div>
            
            <div class="tl-settings-row">
                <label for="tu_header">프로필 헤더 이미지</label>
                <div class="tl-header-upload-wrap">
                    <div class="tl-header-tabs">
                        <button type="button" class="tl-header-tab active" onclick="switchHeaderTab('url')">URL 입력</button>
                        <button type="button" class="tl-header-tab" onclick="switchHeaderTab('file')">파일 업로드</button>
                    </div>
                    <div id="header-url-input" class="tl-header-input-area">
                        <input type="text" id="tu_header" name="tu_header" value="<?php echo h($tu_header); ?>" placeholder="https://example.com/header.jpg">
                    </div>
                    <div id="header-file-input" class="tl-header-input-area">
                        <label class="tl-file-upload-btn">
                            <input type="file" id="header_file" name="header_file" accept="image/*" onchange="previewHeaderFile(this)">
                            <span>이미지 선택</span>
                        </label>
                        <span id="header-file-name" class="tl-file-name"></span>
                    </div>
                </div>
                <p class="tl-help">프로필 상단에 표시될 배경 이미지입니다. URL을 입력하거나 파일을 업로드하세요.</p>
                <div class="tl-header-preview">
                    <div class="tl-header-preview-img" id="header-preview" style="<?php echo $tu_header ? 'background-image: url('.h($tu_header).')' : ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="tl-settings-row">
                <label for="tu_bio">프로필 소개</label>
                <textarea id="tu_bio" name="tu_bio" maxlength="500" placeholder="자기소개를 입력하세요..."><?php echo h($tu_bio); ?></textarea>
                <p class="tl-help">프로필 페이지에 표시될 소개글입니다.</p>
            </div>

            <div class="tl-settings-row">
                <label for="tu_profile">프로필 주소</label>
                <input type="text" id="tu_profile" name="tu_profile" value="<?php echo h($tu_profile); ?>" maxlength="255" placeholder="/member/viewer.php?ch_id=<?php echo (int)$my_ch_id; ?>">
                <p class="tl-help">사용자 목록·프로필의 프로필 버튼이 연결될 주소입니다. 비워 두면 버튼이 숨겨집니다.</p>
            </div>
        </div>
        
        <?php /* 임시: 스레드 보기 비활성화 — 항상 normal
        <!-- 보기 설정 -->
        <div class="tl-settings-section">
            <h2>보기 설정</h2>
            
            <div class="tl-settings-row">
                <div class="tl-switch-row">
                    <div class="tl-switch-label">
                        <strong>스레드 보기</strong>
                        <span>답글에 원글을 함께 표시합니다. 끄면 답글만 표시됩니다.</span>
                    </div>
                    <label class="tl-switch">
                        <input type="checkbox" id="tl_render_mode" name="tl_render_mode" <?php echo $tl_render_mode == 'thread' ? 'checked' : ''; ?>>
                        <span class="tl-switch-slider"></span>
                    </label>
                </div>
            </div>
        </div>
        */ ?>
        
        <!-- 저장 버튼 -->
        <div class="tl-settings-actions">
            <a href="./profile.php?tl_table=<?php echo $tl_table; ?>&ch_id=<?php echo $my_ch_id; ?>" class="tl-btn tl-btn-secondary">취소</a>
            <button type="submit" id="tl-save-btn" class="tl-btn tl-btn-primary">저장</button>
        </div>
    </form>
</div>

<script>
var headerUploadMode = 'url'; // 'url' or 'file'
var selectedHeaderFile = null;
var thumbUploadMode = 'url'; // 'url' or 'file'
var selectedThumbFile = null;

// 탭 전환 (헤더)
function switchHeaderTab(mode) {
    headerUploadMode = mode;
    var wrap = document.getElementById('header-url-input').closest('.tl-header-upload-wrap');
    var tabs = wrap.querySelectorAll('.tl-header-tab');

    tabs.forEach(function(tab) {
        tab.classList.remove('active');
    });

    if (mode === 'url') {
        tabs[0].classList.add('active');
        document.getElementById('header-url-input').style.display = 'flex';
        document.getElementById('header-file-input').style.display = 'none';
    } else {
        tabs[1].classList.add('active');
        document.getElementById('header-url-input').style.display = 'none';
        document.getElementById('header-file-input').style.display = 'flex';
    }
}

// 탭 전환 (썸네일)
function switchThumbTab(mode) {
    thumbUploadMode = mode;
    var wrap = document.getElementById('thumb-url-input').closest('.tl-header-upload-wrap');
    var tabs = wrap.querySelectorAll('.tl-header-tab');

    tabs.forEach(function(tab) {
        tab.classList.remove('active');
    });

    if (mode === 'url') {
        tabs[0].classList.add('active');
        document.getElementById('thumb-url-input').style.display = 'flex';
        document.getElementById('thumb-file-input').style.display = 'none';
    } else {
        tabs[1].classList.add('active');
        document.getElementById('thumb-url-input').style.display = 'none';
        document.getElementById('thumb-file-input').style.display = 'flex';
    }
}

// 파일 선택 미리보기 (헤더)
function previewHeaderFile(input) {
    var preview = document.getElementById('header-preview');
    var fileName = document.getElementById('header-file-name');
    
    if (input.files && input.files[0]) {
        selectedHeaderFile = input.files[0];
        fileName.textContent = selectedHeaderFile.name;
        
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.style.backgroundImage = 'url(' + e.target.result + ')';
        };
        reader.readAsDataURL(selectedHeaderFile);
    } else {
        selectedHeaderFile = null;
        fileName.textContent = '';
        preview.style.backgroundImage = '';
    }
}

// 파일 선택 미리보기 (썸네일)
function previewThumbFile(input) {
    var preview = document.getElementById('thumb-preview');
    var fileName = document.getElementById('thumb-file-name');
    
    if (input.files && input.files[0]) {
        selectedThumbFile = input.files[0];
        fileName.textContent = selectedThumbFile.name;
        
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.style.backgroundImage = 'url(' + e.target.result + ')';
        };
        reader.readAsDataURL(selectedThumbFile);
    } else {
        selectedThumbFile = null;
        fileName.textContent = '';
        preview.style.backgroundImage = '';
    }
}

// 헤더 URL 입력 미리보기 업데이트
document.getElementById('tu_header').addEventListener('input', function() {
    var url = this.value.trim();
    var preview = document.getElementById('header-preview');
    if (url) {
        preview.style.backgroundImage = 'url(' + url + ')';
    } else {
        preview.style.backgroundImage = '';
    }
});

// 썸네일 URL 입력 미리보기 업데이트
document.getElementById('tu_thumb').addEventListener('input', function() {
    var url = this.value.trim();
    var preview = document.getElementById('thumb-preview');
    if (url) {
        preview.style.backgroundImage = 'url(' + url + ')';
    } else {
        preview.style.backgroundImage = '';
    }
});

var menIdInput = document.getElementById('tu_men_id');
menIdInput.addEventListener('keydown', function(e) {
    if (e.key === ' ' || e.key === 'Spacebar' || e.code === 'Space') {
        e.preventDefault();
    }
});
menIdInput.addEventListener('input', function() {
    var cleaned = this.value.replace(/\s+/g, '');
    if (cleaned !== this.value) {
        this.value = cleaned;
    }
});
menIdInput.addEventListener('paste', function(e) {
    var text = (e.clipboardData || window.clipboardData).getData('text') || '';
    if (/\s/.test(text)) {
        e.preventDefault();
        var start = this.selectionStart;
        var end = this.selectionEnd;
        var cleaned = text.replace(/\s+/g, '');
        this.value = (this.value.slice(0, start) + cleaned + this.value.slice(end)).slice(0, 6);
    }
});

// 폼 제출
document.getElementById('tl-settings-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var btn = document.getElementById('tl-save-btn');
    var msg = document.getElementById('tl-save-message');
    var menId = document.getElementById('tu_men_id').value;

    if (/\s/.test(menId)) {
        msg.style.display = 'block';
        msg.className = 'tl-save-message error';
        msg.textContent = '멘션용 아이디에는 띄어쓰기를 넣을 수 없습니다.';
        return;
    }
    
    btn.disabled = true;
    btn.textContent = '저장 중...';
    
    var formData = new FormData();
    formData.append('tl_table', <?php echo json_encode($tl_table); ?>);
    formData.append('tu_name', document.getElementById('tu_name').value);
    formData.append('tu_men_id', menId);
    formData.append('tu_bio', document.getElementById('tu_bio').value);
    formData.append('tu_profile', document.getElementById('tu_profile').value);
    formData.append('tl_render_mode', 'normal'); // 임시: 스레드 보기 비활성화
    
    // 썸네일 이미지: 모드에 따라 URL 또는 파일 전송
    formData.append('thumb_mode', thumbUploadMode);
    if (thumbUploadMode === 'file' && selectedThumbFile) {
        formData.append('thumb_file', selectedThumbFile);
    } else {
        formData.append('tu_thumb', document.getElementById('tu_thumb').value);
    }
    
    // 헤더 이미지: 모드에 따라 URL 또는 파일 전송
    formData.append('header_mode', headerUploadMode);
    if (headerUploadMode === 'file' && selectedHeaderFile) {
        formData.append('header_file', selectedHeaderFile);
    } else {
        formData.append('tu_header', document.getElementById('tu_header').value);
    }
    
    fetch('./ajax/ajax_update_settings.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.textContent = '저장';
        
        msg.style.display = 'block';
        if (data.success) {
            msg.className = 'tl-save-message success';
            msg.textContent = '설정이 저장되었습니다.';
            
            // 파일 업로드로 저장한 경우 URL 입력란 업데이트
            if (data.thumb_url) {
                document.getElementById('tu_thumb').value = data.thumb_url;
                selectedThumbFile = null;
                document.getElementById('thumb-file-name').textContent = '';
            }
            if (data.header_url) {
                document.getElementById('tu_header').value = data.header_url;
                selectedHeaderFile = null;
                document.getElementById('header-file-name').textContent = '';
            }
            
            // 3초 후 메시지 숨기기
            setTimeout(function() {
                msg.style.display = 'none';
            }, 3000);
        } else {
            msg.className = 'tl-save-message error';
            msg.textContent = data.message || '저장에 실패했습니다.';
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.textContent = '저장';
        
        msg.style.display = 'block';
        msg.className = 'tl-save-message error';
        msg.textContent = '오류가 발생했습니다.';
    });
});
</script>

<?php
include_once(G5_PATH.'/_tail.php');
