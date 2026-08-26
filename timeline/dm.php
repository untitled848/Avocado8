<?php
/**
 * 타임라인 DM (Direct Message) 메인 페이지
 */
require_once('./_common.php');

$my_ch_id = ses($character, 'ch_id', 0, 'int');
if (!$is_member || !$my_ch_id) {
    alert('로그인이 필요합니다.', G5_BBS_URL.'/login.php?url='.urlencode($_SERVER['REQUEST_URI']));
}
if (!can_use_timeline_dm($tl_table, $my_ch_id, $is_admin)) {
    alert('이 타임라인의 DM을 사용할 수 없습니다.', G5_URL.'/timeline/?tl_table='.urlencode($tl_table));
}

$rooms = get_dm_rooms($tl_table, $my_ch_id);

$page_title = 'DM';
include_once(G5_PATH.'/_head.php');
?>

<?php timeline_print_css_head(); ?>
<style>
body,#body{
    height:calc(100% - 50px);
    min-height:calc(100% - 50px);
}
#goto_top{display:none;}
</style>
<a href="./index.php?tl_table=<?php echo $tl_table; ?>" class="tl-back-btn">&larr; 타임라인</a>
        
<div class="dm-container">
    <div class="dm-header">
        <div class="dm-header-left">
            <?php include __DIR__.'/_dm_account_switch.inc.php'; ?>
            <h2>메시지</h2>
        </div>
        <button class="dm-create-btn" onclick="openCreateModal()">새 메시지</button>
    </div>
    
    <div id="dm-room-list-container">
        <?php if (empty($rooms)) { ?>
        <div class="dm-empty">
            <div class="dm-empty-icon">💬</div>
            <p>아직 메시지가 없습니다</p>
            <p style="font-size: 14px;">새 메시지 버튼을 눌러 대화를 시작해보세요!</p>
        </div>
        <?php } else { ?>
        <div class="dm-room-list">
            <?php foreach ($rooms as $room) { 
                $unread = isset($room['unread_count']) ? (int)$room['unread_count'] : 0;
                $last_time = $room['last_message_at'] ? get_time_ago($room['last_message_at']) : get_time_ago($room['created_at']);
            ?>
            <a href="dm_room.php?tl_table=<?php echo urlencode($tl_table); ?>&room_id=<?php echo $room['room_id']; ?>" class="dm-room-item">
                <div class="dm-room-avatar">💬</div>
                <div class="dm-room-info">
                    <div class="dm-room-name"><?php echo h($room['room_name']); ?></div>
                    <div class="dm-room-meta">
                        <span><?php echo $room['participant_count']; ?>명</span>
                        <span><?php echo $last_time; ?></span>
                    </div>
                </div>
                <?php if ($unread > 0) { ?>
                <span class="dm-room-badge"><?php echo $unread; ?></span>
                <?php } ?>
            </a>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
</div>

<!-- 방 생성 모달 -->
<div class="dm-modal" id="createModal">
    <div class="dm-modal-content">
        <div class="dm-modal-header">
            <h3>새 메시지</h3>
            <button class="dm-modal-close" onclick="closeCreateModal()">×</button>
        </div>
        <div class="dm-modal-body">
            <div class="dm-form-group" style="margin-bottom: 15px;">
                <label for="roomName" style="display: block; margin-bottom: 5px; font-weight: 500;">방 제목 (선택)</label>
                <input type="text" class="dm-search-input" id="roomName" placeholder="방 제목을 입력하세요 (미입력시 참가자 이름으로 표시)" maxlength="50">
            </div>
            <div class="dm-search-box">
                <input type="text" class="dm-search-input" id="userSearch" placeholder="사용자 이름 검색..." oninput="filterUsers()">
            </div>
            <div class="dm-user-list" id="userList">
                <!-- JavaScript로 동적 로드 -->
            </div>
        </div>
        <div class="dm-modal-footer">
            <button class="dm-btn dm-btn-cancel" onclick="closeCreateModal()">취소</button>
            <button class="dm-btn dm-btn-primary" id="createBtn" onclick="createRoom()" disabled>생성</button>
        </div>
    </div>
</div>

<script>
var tlTable = '<?php echo $tl_table; ?>';
var myChId = <?php echo (int)$my_ch_id; ?>;
</script>
<script src="<?php echo G5_URL; ?>/timeline/timeline.common.js"></script>
<script>
var allUsers = [];
var selectedUsers = new Set();

function openCreateModal() {
    document.getElementById('createModal').classList.add('show');
    loadUsers();
}

function closeCreateModal() {
    document.getElementById('createModal').classList.remove('show');
    document.getElementById('roomName').value = '';
    selectedUsers.clear();
    updateCreateBtn();
}

function loadUsers() {
    fetch('./ajax/ajax_dm_get_users.php?tl_table=' + encodeURIComponent(tlTable))
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            allUsers = data.users;
            renderUsers(allUsers);
        }
    })
    .catch(function(err) {
        console.error('사용자 로드 오류:', err);
    });
}

function renderUsers(users) {
    var html = '';
    users.forEach(function(user) {
        var userId = parseInt(user.ch_id); // 문자열 -> 숫자 변환
        var checked = selectedUsers.has(userId) ? 'checked' : '';
        var selectedClass = selectedUsers.has(userId) ? 'selected' : '';
        
        html += '<div class="dm-user-item ' + selectedClass + '" onclick="toggleUser(' + userId + ')">';
        html += '<input type="checkbox" class="dm-user-checkbox" ' + checked + ' readonly>';
        html += '<img src="' + user.avatar + '" class="dm-user-avatar">';
        html += '<span class="dm-user-name">' + escapeHtml(user.name) + '</span>';
        html += '</div>';
    });
    
    document.getElementById('userList').innerHTML = html || '<p style="text-align:center;color:#657786;">검색 결과가 없습니다</p>';
}

function toggleUser(chId) {
    chId = parseInt(chId); // 숫자로 명시적 변환
    
    if (selectedUsers.has(chId)) {
        selectedUsers.delete(chId);
    } else {
        selectedUsers.add(chId);
    }
    
    renderUsers(filterCurrentUsers());
    updateCreateBtn();
}

function filterUsers() {
    var query = document.getElementById('userSearch').value.toLowerCase();
    var filtered = allUsers.filter(function(user) {
        return user.name.toLowerCase().includes(query);
    });
    renderUsers(filtered);
}

function filterCurrentUsers() {
    var query = document.getElementById('userSearch').value.toLowerCase();
    return allUsers.filter(function(user) {
        return user.name.toLowerCase().includes(query);
    });
}

function updateCreateBtn() {
    var btn = document.getElementById('createBtn');
    btn.disabled = selectedUsers.size === 0;
}

function createRoom() {
    if (selectedUsers.size === 0) {
        alert('참가자를 선택해주세요.');
        return;
    }
    
    var roomName = document.getElementById('roomName').value.trim();
    
    var formData = new FormData();
    formData.append('tl_table', tlTable);
    formData.append('room_name', roomName);
    Array.from(selectedUsers).forEach(function(chId) {
        formData.append('participants[]', chId);
    });
    
    fetch('./ajax/ajax_dm_create.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { 
        if (!res.ok) {
            throw new Error('HTTP 오류: ' + res.status);
        }
        return res.json();
    })
    .then(function(data) {
        if (data.success) {
            location.href = 'dm_room.php?tl_table=' + encodeURIComponent(tlTable) + '&room_id=' + data.room_id;
        } else {
            alert(data.message || '방 생성에 실패했습니다.');
        }
    })
    .catch(function() {
        alert('방 생성 중 오류가 발생했습니다.');
    });
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 모달 외부 클릭 시 닫기
document.getElementById('createModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreateModal();
    }
});
</script>

<?php
include_once(G5_PATH.'/_tail.php');
?>
