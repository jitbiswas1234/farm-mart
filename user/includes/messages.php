<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = 'user';

$conversations = [];
$messages = [];
$active_contact = null;

$active_chat_id = isset($_GET['chat']) ? intval($_GET['chat']) : 0;
// We now ignore the types to match the fix implemented on the vendor side.

/* ===== FETCH CONVERSATIONS ===== */
// We use a simplified query that just looks at IDs and maps them to a name
$sql = "
SELECT 
    CASE 
        WHEN sender_id = ? THEN receiver_id
        ELSE sender_id
    END as contact_id,
    MAX(created_at) as last_time
FROM messages
WHERE 
sender_id = ? OR receiver_id = ?
GROUP BY contact_id
ORDER BY last_time DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii",
    $user_id,
    $user_id,
    $user_id
);
$stmt->execute();
$res = $stmt->get_result();

$raw_convos = [];
$contact_ids = [];

while ($row = $res->fetch_assoc()) {
    $raw_convos[] = $row;
    $contact_ids[] = (int)$row['contact_id'];
}

$farmers_data = [];
$users_data = [];

if (!empty($contact_ids)) {
    $ids = implode(',', array_unique($contact_ids));
    // Check farmers
    $f_res = $conn->query("SELECT id, name, profile_photo as avatar FROM farmers WHERE id IN ($ids)");
    if ($f_res) {
        while ($f = $f_res->fetch_assoc()) {
            $farmers_data[$f['id']] = $f;
        }
    }
    // Check users
    $u_res = $conn->query("SELECT id, name, profile_picture as avatar FROM users WHERE id IN ($ids)");
    if ($u_res) {
        while ($u = $u_res->fetch_assoc()) {
            $users_data[$u['id']] = $u;
        }
    }
}

foreach ($raw_convos as $row) {
    $contact = $farmers_data[$row['contact_id']] ?? $users_data[$row['contact_id']] ?? null;
    if ($contact) {
        $row['name'] = $contact['name'];
        $row['avatar'] = $contact['avatar'] ?? 'default.png';
        $conversations[] = $row;
    }
}

/* AUTO SELECT */
if ($active_chat_id === 0 && !empty($conversations)) {
    $active_chat_id = $conversations[0]['contact_id'];
}

/* ===== FETCH ACTIVE CHAT ===== */
if ($active_chat_id > 0) {

    // Figure out if active chat is a farmer or user
    $active_contact = $farmers_data[$active_chat_id] ?? $users_data[$active_chat_id] ?? null;

    $msg_stmt = $conn->prepare("
        SELECT * FROM messages
        WHERE 
        (sender_id=? AND receiver_id=?)
        OR
        (sender_id=? AND receiver_id=?)
        ORDER BY created_at ASC
    ");

    $msg_stmt->bind_param("iiii",
        $user_id, $active_chat_id,
        $active_chat_id, $user_id
    );

    $msg_stmt->execute();
    $msg_res = $msg_stmt->get_result();
    while ($m_row = $msg_res->fetch_assoc()) {
        $messages[] = $m_row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmMart | Messages</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
    :root {
        --bg-root: #f0f2f5;
        --bg-surface: #ffffff;
        --bg-elevated: #f8f9fa;
        --bg-hover: #f1f3f5;
        --border-color: #e5e7eb;
        --border-highlight: #d1d5db;
        --text-primary: #1f2937;
        --text-secondary: #4b5563;
        --text-muted: #9ca3af;
        --accent-primary: #0084ff; /* Changed from green to standard blue */
        --accent-primary-glow: rgba(0, 132, 255, 0.1);
        --accent-primary-hover: #0073e6;
        --radius: 12px;
        --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Inter', sans-serif;
        background-color: var(--bg-root);
        color: var(--text-secondary);
        font-size: 14px;
        line-height: 1.5;
        overflow-x: hidden;
    }

    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }

    /* === LAYOUT === */
    .app-layout { display: flex; min-height: 100vh; position: relative; }

    .main-wrapper {
        flex: 1; display: flex; flex-direction: column;
        margin-left: 260px;
        transition: var(--transition);
        min-height: 100vh;
    }

    /* === HEADER === */
    .top-header {
        height: 70px; background: var(--bg-surface);
        border-bottom: 1px solid var(--border-color);
        display: flex; align-items: center; justify-content: space-between;
        padding: 0 32px; position: sticky; top: 0; z-index: 50;
    }
    .header-left { display: flex; align-items: center; gap: 20px; }
    .header-greeting { font-size: 0.95rem; font-weight: 600; color: var(--text-primary); }
    .header-greeting span { color: var(--text-muted); font-weight: 400; }

    .header-right { display: flex; align-items: center; gap: 12px; }

    .header-icon {
        width: 38px; height: 38px; border-radius: 8px; background: var(--bg-elevated);
        border: 1px solid var(--border-color); color: var(--text-secondary);
        display: flex; align-items: center; justify-content: center; cursor: pointer;
        transition: var(--transition); position: relative; font-size: 1.1rem;
    }
    .header-icon:hover { border-color: var(--border-highlight); color: var(--text-primary); }
    .header-icon .dot {
        position: absolute; top: 8px; right: 8px; width: 7px; height: 7px;
        background: var(--accent-primary); border-radius: 50%; border: 1.5px solid var(--bg-elevated);
        box-shadow: 0 0 8px rgba(0, 132, 255, 0.6);
    }

    /* === CONTENT === */
    .main-content { padding: 28px 32px; flex: 1; display: flex; flex-direction: column; }

    /* === CHAT CONTAINER === */
    .chat-container {
        display: flex; gap: 24px; flex: 1; height: calc(100vh - 126px);
    }

    .chat-sidebar {
        width: 280px; background: var(--bg-surface); border: 1px solid var(--border-color);
        border-radius: 16px; overflow: hidden; display: flex; flex-direction: column;
    }

    .chat-list {
        flex: 1; overflow-y: auto; display: flex; flex-direction: column;
    }

    .chat-user {
        display: flex; gap: 12px; padding: 14px 16px; align-items: center;
        cursor: pointer;
        transition: var(--transition); text-decoration: none; color: inherit;
    }
    .chat-user:hover { background: var(--bg-hover); }
    .chat-user.active { background: var(--accent-primary-glow); border-left: 3px solid var(--accent-primary); }

    .chat-avatar {
        width: 48px; height: 48px; border-radius: 10px;
        background: var(--bg-elevated); border: 1px solid var(--border-color);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; overflow: hidden; font-weight: 600; color: var(--text-muted);
    }
    .chat-avatar img { width: 100%; height: 100%; object-fit: cover; }

    .chat-user-info { flex: 1; min-width: 0; }
    .chat-user-name { font-size: 0.95rem; font-weight: 700; color: #000000; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .chat-user-time { font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px; }

    /* === CHAT MAIN === */
    .chat-main {
        flex: 1; background: var(--bg-surface); border: 1px solid var(--border-color);
        border-radius: 16px; overflow: hidden; display: flex; flex-direction: column;
    }

    .chat-header {
        padding: 16px 24px; border-bottom: 1px solid var(--border-color);
        display: flex; align-items: center; justify-content: space-between;
    }
    .chat-header-title { font-size: 1.05rem; font-weight: 700; color: #000000; }

    .messages {
        flex: 1; overflow-y: auto; padding: 24px; display: flex; flex-direction: column; gap: 12px;
    }

    /* Fixed Message Colors */
    .msg {
        max-width: 70%; padding: 12px 16px; border-radius: 18px; word-break: break-word;
        font-size: 0.95rem; line-height: 1.4; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .msg.sent {
        align-self: flex-end; 
        background: var(--accent-primary); /* Blue */
        color: #ffffff; /* White Text */
        border-bottom-right-radius: 4px;
    }
    .msg.received {
        align-self: flex-start; 
        background: #f1f3f5; /* Light Gray */
        color: #212529; /* Dark Text */
        border-bottom-left-radius: 4px;
        border: 1px solid #e9ecef;
    }
    .msg-time {
        font-size: 0.7rem;
        opacity: 0.8;
        margin-top: 4px;
        display: block;
        text-align: right;
    }
    .msg.sent .msg-time { color: #e9ecef; }
    .msg.received .msg-time { color: #6c757d; }

    .messages-empty {
        display: flex; align-items: center; justify-content: center;
        height: 100%; color: var(--text-muted); font-size: 0.95rem;
    }

    .chat-input-box {
        padding: 16px 24px; border-top: 1px solid var(--border-color);
        display: flex; gap: 12px; align-items: center; background: var(--bg-surface);
    }
    .chat-action-btn {
        background: none; border: none; color: var(--text-muted); font-size: 1.3rem; 
        cursor: pointer; transition: var(--transition); display: flex; align-items: center; justify-content: center;
    }
    .chat-action-btn:hover { color: var(--accent-primary); }
    
    .chat-input-box input {
        flex: 1; background: var(--bg-elevated); border: 1px solid var(--border-color);
        color: #000000; /* Dark black typing text */
        padding: 12px 20px; border-radius: 24px;
        font-size: 0.95rem; font-family: inherit; transition: var(--transition);
    }
    .chat-input-box input::placeholder {
        color: #6c757d; /* Standard placeholder gray */
    }
    .chat-input-box input:focus {
        outline: none; border-color: var(--border-highlight); background: #ffffff; box-shadow: 0 0 0 3px var(--accent-primary-glow);
    }
    .send-btn {
        background: var(--accent-primary); border: none; color: #ffffff;
        width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; 
        cursor: pointer; transition: var(--transition); font-size: 1.1rem; flex-shrink: 0;
    }
    .send-btn:hover { background: var(--accent-primary-hover); }

    .no-chat {
        display: flex; align-items: center; justify-content: center; flex-direction: column;
        height: 100%; color: var(--text-muted);
    }
    .no-chat i { font-size: 2.5rem; margin-bottom: 12px; opacity: 0.3; }

    /* === RESPONSIVE === */
    @media (max-width: 1200px) {
        .chat-container { flex-direction: column; height: auto; }
        .chat-sidebar { width: 100%; max-height: 300px; }
    }
    @media (max-width: 992px) {
        .main-wrapper { margin-left: 0; }
        .sidebar-wrapper { position: absolute; left: -260px; }
        .main-content { padding: 28px 20px; }
        .top-header { padding: 0 20px; }
    }
    @media (max-width: 768px) {
        .main-content { padding: 20px 16px; }
        .msg { max-width: 85%; }
        .header-greeting { display: none; }
    }

    /* === ANIMATIONS === */
    @keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .anim { animation: fadeUp 0.4s ease forwards; opacity: 0; }
    .d1 { animation-delay: 0.05s; } .d2 { animation-delay: 0.1s; }
    </style>
</head>
<body>

<div class="app-layout">
    
    <!-- Include the Premium Sidebar -->
    <?php include '../../includes/user_sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-left">
                <div class="header-greeting">
                    Messages <span>(<?= count($conversations) ?> conversation<?= count($conversations) !== 1 ? 's' : '' ?>)</span>
                </div>
            </div>
            <div class="header-right">
                <a href="notifications.php" class="header-icon" title="Notifications">
                    <i class="bi bi-bell"></i>
                </a>
            </div>
        </header>

        <!-- Chat Content -->
        <main class="main-content anim d1">

            <div class="chat-container">
                
                <!-- Chat List -->
                <div class="chat-sidebar">
                    <div style="padding: 16px; border-bottom: 1px solid var(--border-color);">
                        <h5 style="font-weight: 600; margin-bottom: 12px; color: var(--text-primary); font-size: 1.1rem;">Messages</h5>
                        <div style="position: relative;">
                            <i class="bi bi-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                            <input type="text" id="chatSearch" placeholder="Search chats..." style="width: 100%; padding: 10px 14px 10px 40px; border-radius: 20px; border: 1px solid var(--border-color); background: var(--bg-elevated); outline: none; font-size: 0.85rem; color: #000;">
                        </div>
                    </div>
                    
                    <div class="chat-list">
                        <?php if (!empty($conversations)): ?>
                            <?php foreach($conversations as $c): ?>
                            <a href="?chat=<?= $c['contact_id'] ?>" 
                               class="chat-user <?= ($c['contact_id']==$active_chat_id) ? 'active' : '' ?>">
                                <div class="chat-avatar">
                                    <?php if ($c['avatar'] && $c['avatar'] !== 'default.png'): ?>
                                        <img src="../../uploads/<?= htmlspecialchars($c['avatar']) ?>" alt="" onerror="this.parentElement.textContent='<?= addslashes(htmlspecialchars(mb_substr($c['name'], 0, 1))) ?>'">
                                    <?php else: ?>
                                        <?= htmlspecialchars(mb_substr($c['name'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="chat-user-info">
                                    <div class="chat-user-name"><?= htmlspecialchars($c['name']) ?></div>
                                    <div class="chat-user-time"><?= date('M j, H:i', strtotime($c['last_time'])) ?></div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="display: flex; flex: 1; align-items: center; justify-content: center; color: var(--text-muted);">
                                <div style="text-align: center;">
                                    <i class="bi bi-chat-dots" style="font-size: 2rem; opacity: 0.3;"></i>
                                    <p style="margin-top: 12px; font-size: 0.85rem;">No conversations yet</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chat Main -->
                <div class="chat-main anim d2">
                    <?php if($active_contact): ?>
                        <div class="chat-header">
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <div class="chat-avatar" style="width: 42px; height: 42px; border-radius: 50%;">
                                    <?php if (!empty($active_contact['avatar']) && $active_contact['avatar'] !== 'default.png'): ?>
                                        <img src="../../uploads/<?= htmlspecialchars($active_contact['avatar']) ?>" alt="" onerror="this.parentElement.textContent='<?= addslashes(htmlspecialchars(mb_substr($active_contact['name'], 0, 1))) ?>'">
                                    <?php else: ?>
                                        <?= htmlspecialchars(mb_substr($active_contact['name'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="chat-header-title"><?= htmlspecialchars($active_contact['name']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--accent-primary); font-weight: 500; display: flex; align-items: center; gap: 4px;">
                                        <span style="display: inline-block; width: 6px; height: 6px; background: var(--accent-primary); border-radius: 50%;"></span> Online
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 16px; color: var(--text-muted); font-size: 1.25rem;">
                                <i class="bi bi-telephone" style="cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='var(--text-muted)'"></i>
                                <i class="bi bi-camera-video" style="cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='var(--text-muted)'"></i>
                                <i class="bi bi-info-circle" style="cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='var(--text-muted)'"></i>
                            </div>
                        </div>

                        <div class="messages" id="chatMessages">
                            <?php foreach($messages as $m):
                            $is_me = ($m['sender_id']==$user_id); ?>
                            <div class="msg <?= $is_me?'sent':'received' ?>">
                                <?= htmlspecialchars($m['message']) ?>
                                <?php if($m['file_path']): 
                                    $fileName = htmlspecialchars(basename($m['file_path']));
                                    $fileLink = "../../uploads/chat/" . $m['file_path'];
                                    $ext = strtolower(pathinfo($m['file_path'], PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                ?>
                                    <br>
                                    <?php if($isImage): ?>
                                        <a href="<?= $fileLink ?>" target="_blank"><img src="<?= $fileLink ?>" style="max-width: 150px; border-radius: 8px; margin-top: 5px; border: 2px solid rgba(255,255,255,0.2);"></a>
                                    <?php else: ?>
                                        <a href="<?= $fileLink ?>" target="_blank" style="color: inherit; text-decoration: underline;"><i class="bi bi-paperclip"></i> <?= $fileName ?></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <span class="msg-time"><?= date('h:i A', strtotime($m['created_at'])) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <form id="chatForm" class="chat-input-box">
                            <input type="file" id="fileInput" hidden>
                            <button type="button" class="chat-action-btn" onclick="document.getElementById('fileInput').click()" title="Attach file">
                                <i class="bi bi-paperclip"></i>
                            </button>
                            <input type="text" id="msgInput" placeholder="Type your message..." required>
                            <button type="button" class="chat-action-btn" title="Choose emoji">
                                <i class="bi bi-emoji-smile"></i>
                            </button>
                            <button type="submit" class="send-btn" title="Send message">
                                <i class="bi bi-send-fill" style="margin-left: -2px;"></i>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="no-chat">
                            <i class="bi bi-chat-dots"></i>
                            <p>Select a conversation to start messaging</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </main>
    </div>
</div>

<script>
<?php if ($active_chat_id > 0): ?>
const chatBox = document.getElementById('chatMessages');
if (chatBox) chatBox.scrollTop = chatBox.scrollHeight; // Scroll to bottom on initial load

const receiverId = <?= $active_chat_id ?>;
const receiverType = "user"; // The endpoint expects this though it doesn't filter on it anymore

let lastChatData = chatBox ? chatBox.innerHTML : "";

/* SEND */
document.getElementById('chatForm')?.addEventListener('submit', function(e){
    e.preventDefault();
    let msg = document.getElementById('msgInput').value.trim();
    let file = document.getElementById('fileInput').files[0];
    
    if(!msg && !file) return;

    let fd = new FormData();
    fd.append('receiver_id', receiverId);
    fd.append('receiver_type', receiverType);
    fd.append('message', msg);
    
    if(file){
        fd.append("chat_file", file);
    }

    fetch('../../chat/send_message.php', {method:'POST', body:fd})
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            document.getElementById('msgInput').value='';
            document.getElementById('fileInput').value='';
            loadMessages();
        }
    });
});

/* FILE SELECTION FEEDBACK */
document.getElementById('fileInput')?.addEventListener('change', function() {
    if(this.files && this.files.length > 0) {
        document.getElementById('msgInput').placeholder = "File selected: " + this.files[0].name;
    } else {
        document.getElementById('msgInput').placeholder = "Type your message...";
    }
});

/* LOAD */
function loadMessages(){
    let fd = new FormData();
    fd.append('receiver_id', receiverId);
    fd.append('receiver_type', receiverType);

    fetch('../../chat/get_messages.php',{method:'POST',body:fd})
    .then(res=>res.text())
    .then(data=>{
        // Convert dark mode inline styles to our new light mode classes before injecting
        let formattedData = data.replace(/color:#000/g, '').replace(/background:#1a1a1a/g, ''); 
        
        if (chatBox && formattedData.trim() !== lastChatData.trim()) {
            // Only auto-scroll down if the user is already near the bottom
            let isAtBottom = chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 100;
            chatBox.innerHTML = formattedData;
            lastChatData = formattedData;
            if (isAtBottom) chatBox.scrollTop = chatBox.scrollHeight;
        }
    });
}

/* AUTO */
setInterval(loadMessages, 2000);
<?php endif; ?>

/* CHAT SEARCH FILTER */
const chatSearch = document.getElementById('chatSearch');
if (chatSearch) {
    chatSearch.addEventListener('input', function() {
        const term = this.value.toLowerCase();
        const chatUsers = document.querySelectorAll('.chat-user');
        
        chatUsers.forEach(user => {
            const userName = user.querySelector('.chat-user-name').textContent.toLowerCase();
            if (userName.includes(term)) {
                user.style.display = 'flex';
            } else {
                user.style.display = 'none';
            }
        });
    });
}
</script>

</body>
</html>