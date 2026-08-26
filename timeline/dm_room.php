<?php
/**
 * DM 채팅방 페이지
 */
require_once('./_common.php');

$my_ch_id = ses($character, 'ch_id', 0, 'int');
if (!$is_member || !$my_ch_id) {
    alert('로그인이 필요합니다.', G5_BBS_URL.'/login.php?url='.urlencode($_SERVER['REQUEST_URI']));
}
if (!can_use_timeline_dm($tl_table, $my_ch_id, $is_admin)) {
    alert('이 타임라인의 DM을 사용할 수 없습니다.', G5_URL.'/timeline/?tl_table='.urlencode($tl_table));
}

$room_id = ses($_GET, 'room_id', 0, 'int');
if (!$room_id) {
    alert('잘못된 접근입니다.', './dm.php?tl_table='.urlencode($tl_table));
}

$room = get_dm_room($tl_table, $room_id, $my_ch_id, ($is_admin));
if (!$room) {
    alert('접근 권한이 없거나 존재하지 않는 방입니다.', './dm.php?tl_table='.urlencode($tl_table));
}

$participants = get_dm_participants($tl_table, $room_id);
$messages = get_dm_messages($tl_table, $room_id, $my_ch_id, 50, 0, 0, ($is_admin));

$page_title = $room['room_name'];
include_once(G5_PATH.'/_head.php');
?>

<?php timeline_print_css_head(); ?>

<style>
body,#body{
    margin:0;
    height:calc(100% - 50px);
    min-height:calc(100% - 50px);
}
@media (max-width: 1024px) {
    #body{
        margin-top:50px;
    }
}
#goto_top{display:none;}
</style>
<a href="./index.php?tl_table=<?php echo $tl_table; ?>" class="tl-back-btn">&larr; 타임라인</a>
        
<div class="dm-room-container">
    <div class="dm-room-header">
        <div class="dm-room-header-left">
            <?php include __DIR__.'/_dm_account_switch.inc.php'; ?>
            <div>
                <h1 class="dm-room-title" id="roomTitle"><?php echo h($room['room_name']); ?></h1>
                <div class="dm-room-meta"><?php echo count($participants); ?>명 참가</div>
            </div>
        </div>
        <div class="dm-room-actions">
            <button class="dm-room-btn" onclick="showParticipants()">참가자</button>
            <?php if ($room['is_creator'] || $is_admin) { ?>
            <button class="dm-room-btn" onclick="editRoomName()">방 제목 수정</button>
            <button class="dm-room-btn" onclick="showAddParticipant()">초대</button>
            <?php } ?>
            <a href="./dm.php?tl_table=<?php echo urlencode($tl_table); ?>" class="dm-room-btn">목록</a>
        </div>
    </div>
    
    <div class="dm-messages" id="messages">
        <?php if (empty($messages)) { ?>
        <div class="dm-loading">대화를 시작해보세요!</div>
        <?php } else { ?>
        <?php foreach ($messages as $msg) {
            $avatar = ses($msg, 'avatar', '', 'raw');
            if ($avatar === '') {
                $avatar = G5_URL.'/img/no_profile.png';
            }
            $mine_class = $msg['is_mine'] ? ' mine' : '';
            $time = get_time_ago($msg['created_at']);
        ?>
        <div class="dm-message<?php echo $mine_class; ?>" data-msg-id="<?php echo $msg['msg_id']; ?>">
            <img src="<?php echo $avatar; ?>" class="dm-message-avatar">
            <div class="dm-message-content">
                <span class="dm-message-author"><?php echo h($msg['ch_name']); ?></span>
                <div class="dm-message-bubble"><?php echo nl2br(h($msg['content'])); ?></div>
                <span class="dm-message-time"><?php echo $time; ?></span>
            </div>
        </div>
        <?php } ?>
        <?php } ?>
    </div>
    
    <div class="dm-input-area">
        <div class="dm-input-form">
            <textarea class="dm-input" id="messageInput" placeholder="메시지를 입력하세요..." maxlength="1000" rows="1" oninput="autoResize(this)" onkeydown="checkEnter(event)"></textarea>
            <button type="button" class="dm-send-btn" id="sendBtn" onclick="sendMessage(event)" disabled>전송</button>
        </div>
    </div>
</div>

<!-- 참가자 모달 -->
<div class="dm-participants-modal" id="participantsModal">
    <div class="dm-participants-content">
        <div class="dm-participants-header">
            <h3>참가자 (<?php echo count($participants); ?>)</h3>
            <button class="dm-modal-close" onclick="hideParticipants()">×</button>
        </div>
        <div class="dm-participants-list">
            <?php foreach ($participants as $p) {
                $avatar = ses($p, 'avatar', '', 'raw');
                if ($avatar === '') {
                    $avatar = G5_URL.'/img/no_profile.png';
                }
            ?>
            <div class="dm-participant-item">
                <img src="<?php echo $avatar; ?>" class="dm-participant-avatar">
                <span class="dm-participant-name"><?php echo h($p['ch_name']); ?></span>
                <?php if ($p['is_creator']) { ?>
                <span class="dm-participant-badge">방장</span>
                <?php } ?>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<!-- 초대 모달 -->
<div class="dm-participants-modal" id="inviteModal">
    <div class="dm-participants-content">
        <div class="dm-participants-header">
            <h3>참가자 초대</h3>
            <button class="dm-modal-close" onclick="hideInviteModal()">×</button>
        </div>
        <div class="dm-search-box">
            <input type="text" class="dm-search-input" id="inviteSearch" placeholder="사용자 이름 검색..." oninput="filterInviteUsers()">
        </div>
        <div class="dm-participants-list dm-invite-list" id="inviteUserList">
            <!-- JavaScript로 동적 로드 -->
        </div>
        <div class="dm-invite-footer">
            <button class="dm-room-btn" onclick="hideInviteModal()">취소</button>
            <button class="dm-room-btn dm-btn-primary" id="inviteBtn" onclick="inviteUsers()" disabled>초대</button>
        </div>
    </div>
</div>

<script>
var tlTable = '<?php echo $tl_table; ?>';
var myChId = <?php echo (int)$my_ch_id; ?>;
</script>
<script src="<?php echo G5_URL; ?>/timeline/timeline.common.js"></script>
<script>
var roomId = <?php echo (int)$room_id; ?>;
var pollInterval = null;
<?php
$dm_last = array();
if (!empty($messages)) {
    $dm_last = end($messages);
}
?>
var lastMsgId = <?php echo (int)ses($dm_last, 'msg_id', 0, 'int'); ?>;
var pollIntervalMs = <?php echo (int)get_timeline_poll_interval_ms($timeline, 'tl_dm_poll_interval', 3000); ?>;

// 메시지 입력 자동 높이 조절
function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
    
    var btn = document.getElementById('sendBtn');
    btn.disabled = !textarea.value.trim();
}

// Enter 키 처리 (Shift+Enter는 줄바꿈)
function checkEnter(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage(event);
    }
}

// 메시지 전송
function sendMessage(e) {
    var input = document.getElementById('messageInput');
    var content = input.value.trim();
    
    if (!content) {
        return;
    }
    
    var formData = new FormData();
    formData.append('tl_table', tlTable);
    formData.append('room_id', roomId);
    formData.append('content', content);
    
    fetch('./ajax/ajax_dm_send.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            input.value = '';
            autoResize(input);
            loadNewMessages();
        } else {
            alert(data.message || '전송 실패');
        }
    })
    .catch(function() {
        alert('전송 중 오류가 발생했습니다.');
    });
}

// 새 메시지 로드
function loadNewMessages() {
    fetch('./ajax/ajax_dm_load_messages.php?tl_table=' + encodeURIComponent(tlTable) + '&room_id=' + roomId + '&after=' + lastMsgId)
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success && data.messages.length > 0) {
            data.messages.forEach(function(msg) {
                appendMessage(msg);
                lastMsgId = Math.max(lastMsgId, msg.msg_id);
            });
            scrollToBottom();
        }
    })
    .catch(function(err) {
        console.error('메시지 로드 오류:', err);
    });
}

// 메시지 DOM 추가
function appendMessage(msg) {
    var messagesDiv = document.getElementById('messages');
    var msgDiv = document.createElement('div');
    msgDiv.className = 'dm-message' + (msg.is_mine ? ' mine' : '');
    msgDiv.setAttribute('data-msg-id', msg.msg_id);
    
    // 서버 스냅샷 (msg_name / msg_thumb). 빈 값은 라이브 tu_* 폴백
    var avatar = msg.avatar || '<?php echo G5_URL; ?>/img/no_profile.png';
    
    msgDiv.innerHTML = 
        '<img src="' + avatar + '" class="dm-message-avatar">' +
        '<div class="dm-message-content">' +
        '<span class="dm-message-author">' + escapeHtml(msg.ch_name) + '</span>' +
        '<div class="dm-message-bubble">' + escapeHtml(msg.content).replace(/\n/g, '<br>') + '</div>' +
        '<span class="dm-message-time">방금</span>' +
        '</div>';
    
    messagesDiv.appendChild(msgDiv);
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function scrollToBottom() {
    var messagesDiv = document.getElementById('messages');
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function showParticipants() {
    document.getElementById('participantsModal').classList.add('show');
}

function hideParticipants() {
    document.getElementById('participantsModal').classList.remove('show');
}

var allInviteUsers = [];
var selectedInviteUsers = new Set();
var currentParticipantIds = [<?php echo implode(',', array_map(function($p) { return $p['ch_id']; }, $participants)); ?>];

function showAddParticipant() {
    document.getElementById('inviteModal').classList.add('show');
    loadInviteUsers();
}

function hideInviteModal() {
    document.getElementById('inviteModal').classList.remove('show');
    selectedInviteUsers.clear();
    document.getElementById('inviteSearch').value = '';
    updateInviteBtn();
}

function loadInviteUsers() {
    fetch('./ajax/ajax_dm_get_users.php?tl_table=' + encodeURIComponent(tlTable))
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            // 이미 참가중인 사람 제외
            allInviteUsers = data.users.filter(function(user) {
                return currentParticipantIds.indexOf(parseInt(user.ch_id)) === -1;
            });
            renderInviteUsers(allInviteUsers);
        }
    })
    .catch(function(err) {
        console.error('사용자 목록 로드 오류:', err);
    });
}

function renderInviteUsers(users) {
    var listDiv = document.getElementById('inviteUserList');
    if (users.length === 0) {
        listDiv.innerHTML = '<div class="dm-loading">초대 가능한 사용자가 없습니다.</div>';
        return;
    }
    
    listDiv.innerHTML = users.map(function(user) {
        var userId = parseInt(user.ch_id);
        var checked = selectedInviteUsers.has(userId) ? 'checked' : '';
        return '<div class="dm-participant-item" onclick="toggleInviteUser(' + userId + ')" style="cursor: pointer;">' +
            '<img src="' + user.avatar + '" class="dm-participant-avatar">' +
            '<span class="dm-participant-name">' + escapeHtml(user.name) + '</span>' +
            '<input type="checkbox" ' + checked + ' disabled style="pointer-events: none;">' +
            '</div>';
    }).join('');
}

function toggleInviteUser(chId) {
    if (selectedInviteUsers.has(chId)) {
        selectedInviteUsers.delete(chId);
    } else {
        selectedInviteUsers.add(chId);
    }
    renderInviteUsers(allInviteUsers);
    updateInviteBtn();
}

function filterInviteUsers() {
    var query = document.getElementById('inviteSearch').value.toLowerCase();
    var filtered = allInviteUsers.filter(function(user) {
        return user.name.toLowerCase().includes(query);
    });
    renderInviteUsers(filtered);
}

function updateInviteBtn() {
    document.getElementById('inviteBtn').disabled = selectedInviteUsers.size === 0;
}

function inviteUsers() {
    if (selectedInviteUsers.size === 0) {
        alert('초대할 참가자를 선택해주세요.');
        return;
    }
    
    var formData = new FormData();
    formData.append('tl_table', tlTable);
    formData.append('room_id', roomId);
    Array.from(selectedInviteUsers).forEach(function(chId) {
        formData.append('participants[]', chId);
    });
    
    fetch('./ajax/ajax_dm_add_participant.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            alert(data.message);
            hideInviteModal();
            // 페이지 새로고침하여 참가자 목록 갱신
            location.reload();
        } else {
            alert(data.message || '참가자 초대에 실패했습니다.');
        }
    })
    .catch(function(err) {
        console.error('참가자 초대 오류:', err);
        alert('참가자 초대 중 오류가 발생했습니다.');
    });
}

function editRoomName() {
    var currentName = document.getElementById('roomTitle').textContent;
    var newName = prompt('새 방 제목을 입력하세요 (최대 50자)', currentName);
    
    if (newName === null) return; // 취소
    
    newName = newName.trim();
    if (newName.length > 50) {
        alert('방 제목은 최대 50자까지 입력 가능합니다.');
        return;
    }
    
    var formData = new FormData();
    formData.append('tl_table', tlTable);
    formData.append('room_id', roomId);
    formData.append('room_name', newName);
    
    fetch('./ajax/ajax_dm_edit_room.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            document.getElementById('roomTitle').textContent = newName || '(제목 없음)';
            alert('방 제목이 변경되었습니다.');
        } else {
            alert(data.message || '방 제목 변경에 실패했습니다.');
        }
    })
    .catch(function() {
        alert('방 제목 변경 중 오류가 발생했습니다.');
    });
}

// 폴링 시작
function startPolling() {
    pollInterval = setInterval(loadNewMessages, pollIntervalMs); // 관리자 설정 간격으로 체크
}

// 페이지 로드 시
window.addEventListener('DOMContentLoaded', function() {
    scrollToBottom();
    startPolling();
});

// 페이지 언로드 시 폴링 중지
window.addEventListener('beforeunload', function() {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});

// 모달 외부 클릭 시 닫기
document.getElementById('participantsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideParticipants();
    }
});
document.getElementById('inviteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideInviteModal();
    }
});
</script>

<?php
include_once(G5_PATH.'/_tail.php');
?>
