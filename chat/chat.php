<?php
session_start();
require_once("../includes/config.php");

if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$receiver = isset($_GET['user']) ? (int)$_GET['user'] : 0;

/* FETCH RECEIVER */
$stmt = $conn->prepare("SELECT name, profile_picture FROM users WHERE id=?");
$stmt->bind_param("i", $receiver);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if(!$user) {
    header("Location: chat_list.php");
    exit;
}

$profile_pic = BASE_URL . "assets/default-user.png";
if(!empty($user['profile_picture'])){
    $profile_pic = BASE_URL . "uploads/users/" . $user['profile_picture'];
}

$page_title = "Chat - " . htmlspecialchars($user['name']);
require_once("../includes/header.php");
?>
<!-- NiceAdmin Dashboard Styles & Layout Tweaks -->
<style>
/* Dashboard Base Colors & Setup inspired by NiceAdmin */
:root {
  --nav-bg: #fff;
  --nav-color: #012970;
  --card-shadow: 0px 0 30px rgba(1, 41, 112, 0.1);
  --sidebar-bg: #fff;
  --sidebar-active: #f6f9ff;
  --sidebar-active-color: #4154f1;
}

body {
  background: #f6f9ff;
  color: #444444;
  font-family: "Open Sans", sans-serif;
}

/* Override existing sidebar for this dashboard to match NiceAdmin */
.dashboard-sidebar .list-group-item {
    border: none;
    margin-bottom: 5px;
    border-radius: 4px;
    color: #012970;
    font-weight: 600;
    padding: 12px 15px;
    transition: 0.3s;
}

.dashboard-sidebar .list-group-item:hover,
.dashboard-sidebar .list-group-item.active {
    background-color: var(--sidebar-active);
    color: var(--sidebar-active-color);
}

.dashboard-sidebar .list-group-item i {
    font-size: 18px;
    margin-right: 10px;
    color: #899bbd;
}

.dashboard-sidebar .list-group-item.active i {
    color: var(--sidebar-active-color);
}

/* NiceAdmin Dashboard Cards */
.card {
  border: none;
  border-radius: 5px;
  box-shadow: var(--card-shadow);
  margin-bottom: 30px;
}

/* Chat Layout Styles */
.chat-window {
    height: calc(100vh - 180px);
    display: flex;
    flex-direction: column;
}

/* HEADER */
.chat-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ebeef4;
    display: flex;
    align-items: center;
    gap: 15px;
    background-color: #fff;
    border-radius: 5px 5px 0 0;
}

.chat-header img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #f6f9ff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* CHAT AREA */
.chat-box {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 15px;
    background-color: #fcfcfc;
}

/* MESSAGE */
.msg {
    max-width: 75%;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 14px;
    position: relative;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.msg.sent {
    align-self: flex-end;
    background: #4154f1;
    color: #fff;
    border-bottom-right-radius: 2px;
}

.msg.received {
    align-self: flex-start;
    background: #fff;
    color: #444;
    border: 1px solid #ebeef4;
    border-bottom-left-radius: 2px;
}

.msg-time {
    font-size: 11px;
    opacity: 0.7;
    margin-top: 5px;
    display: block;
    text-align: right;
}
.msg.sent .msg-time {
    color: #e0e5ff;
}
.msg.received .msg-time {
    color: #899bbd;
}

/* INPUT */
.chat-input {
    padding: 15px 20px;
    border-top: 1px solid #ebeef4;
    display: flex;
    gap: 10px;
    background-color: #fff;
    border-radius: 0 0 5px 5px;
}

.chat-input .input-group {
    border-radius: 30px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.chat-input input {
    border: 1px solid #ced4da;
    border-right: none;
    padding: 12px 20px;
}
.chat-input input:focus {
    box-shadow: none;
    border-color: #ced4da;
}

.chat-input .btn-attach {
    background-color: #fff;
    border: 1px solid #ced4da;
    border-left: none;
    color: #899bbd;
    transition: 0.3s;
}
.chat-input .btn-attach:hover {
    color: #4154f1;
}

.send-btn {
    background: #4154f1;
    border: none;
    color: white;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.3s;
    flex-shrink: 0;
}
.send-btn:hover {
    background: #2a3dbb;
    transform: scale(1.05);
}

/* Custom Scrollbar for Chat Box */
.chat-box::-webkit-scrollbar {
    width: 6px;
}
.chat-box::-webkit-scrollbar-track {
    background: #f1f1f1;
}
.chat-box::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}
.chat-box::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<?php require_once("../includes/navbar.php"); ?>

<main class="container-fluid py-4 px-4">
    <div class="row g-4">
        
        <!-- Load correct sidebar based on role -->
        <div class="col-lg-3 dashboard-sidebar">
            <?php 
                if($_SESSION['role'] == 'farmer') {
                    require_once("../vendor/sidebar.php"); 
                } elseif ($_SESSION['role'] == 'admin') {
                    require_once("../includes/admin_sidebar.php");
                } elseif ($_SESSION['role'] == 'user') {
                    // For regular users, we might want a simple back button or their sidebar if they have one
                    // We'll leave it empty here or you can load user sidebar
                }
            ?>
        </div>

        <!-- If no sidebar (e.g. user), expand the chat area -->
        <div class="col-lg-<?= ($_SESSION['role'] == 'user') ? '12' : '9' ?>">
            <div class="pagetitle mb-4 d-flex justify-content-between align-items-center">
              <div>
                  <h1 class="fw-bold" style="color: #012970; font-size: 24px;">Chat</h1>
                  <nav>
                    <ol class="breadcrumb" style="background: transparent; padding: 0;">
                      <li class="breadcrumb-item"><a href="../index.php" style="color: #899bbd; text-decoration: none;">Home</a></li>
                      <li class="breadcrumb-item"><a href="chat_list.php" style="color: #899bbd; text-decoration: none;">Messages</a></li>
                      <li class="breadcrumb-item active" style="color: #444444; font-weight: 600;">Conversation</li>
                    </ol>
                  </nav>
              </div>
              <a href="chat_list.php" class="btn btn-outline-secondary px-3 py-2">
                  <i class="bi bi-arrow-left me-1"></i> Back to Inbox
              </a>
            </div>

            <section class="section">
                <div class="row">
                    <div class="col-12">
                        <div class="card chat-window">
                            <!-- HEADER -->
                            <div class="chat-header">
                                <img src="<?= $profile_pic ?>">
                                <div>
                                    <h6 class="fw-bold mb-0" style="color: #012970;"><?= htmlspecialchars($user['name']) ?></h6>
                                </div>
                            </div>

                            <!-- CHAT MESSAGES -->
                            <div class="chat-box" id="chatBox">
                                <!-- Messages load here via AJAX -->
                            </div>

                            <!-- INPUT AREA -->
                            <div class="chat-input">
                                <div class="input-group">
                                    <input type="text" id="msg" class="form-control" placeholder="Type your message here...">
                                    <input type="file" id="fileInput" hidden>
                                    <button class="btn btn-attach" type="button" onclick="document.getElementById('fileInput').click()" title="Attach File">
                                        <i class="bi bi-paperclip fs-5"></i>
                                    </button>
                                </div>
                                <button class="send-btn shadow-sm" onclick="sendMsg()" title="Send">
                                    <i class="bi bi-send-fill"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>

<!-- Adjust sidebar inner styles for NiceAdmin look -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let sidebarCard = document.querySelector('.dashboard-sidebar .card');
        if(sidebarCard) {
            sidebarCard.style.boxShadow = "none";
            
            // Note: Don't override background if it's the admin sidebar (which is dark)
            if(!sidebarCard.style.backgroundColor || sidebarCard.style.backgroundColor !== "rgb(43, 58, 74)") {
                 sidebarCard.style.backgroundColor = "transparent";
            }
            
            let links = sidebarCard.querySelectorAll('.list-group-item');
            links.forEach(link => {
                link.classList.remove('active', 'bg-success', 'text-white');
                if(link.getAttribute('href') && link.getAttribute('href').includes('chat_list.php')) {
                    link.classList.add('active');
                }
            });
        }
    });
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
const receiverId = <?= $receiver ?>;
const receiverType = "user"; // FIXED

/* LOAD MESSAGES */
function loadChat(){
    $.post("get_messages.php", {
        receiver_id: receiverId,
        receiver_type: receiverType
    }, function(data){
        let box = $("#chatBox");
        // Convert dark mode classes to new classes before injecting
        // (Assuming get_messages.php returns <div class="msg sent/received">)
        let formattedData = data.replace(/color:#000/g, '').replace(/background:#1a1a1a/g, ''); 
        
        let isBottom = box[0].scrollHeight - box.scrollTop() <= box.outerHeight()+50;
        box.html(formattedData);

        if(isBottom){
            box.scrollTop(box[0].scrollHeight);
        }
    });
}

/* SEND MESSAGE */
function sendMsg(){
    let msg = $("#msg").val().trim();
    let file = $("#fileInput")[0].files[0];

    if(!msg && !file) return;

    let fd = new FormData();
    fd.append("receiver_id", receiverId);
    fd.append("receiver_type", receiverType);
    fd.append("message", msg);

    if(file){
        fd.append("chat_file", file);
    }

    $.ajax({
        url:"send_message.php",
        method:"POST",
        data:fd,
        processData:false,
        contentType:false,
        success:function(res){
            $("#msg").val('');
            $("#fileInput").val('');
            loadChat();
        }
    });
}

/* ENTER KEY */
$("#msg").keypress(function(e){
    if(e.which==13) sendMsg();
});

/* FILE SELECTION FEEDBACK */
$("#fileInput").change(function(){
    if(this.files && this.files.length > 0) {
        $("#msg").attr("placeholder", "File selected: " + this.files[0].name);
    } else {
        $("#msg").attr("placeholder", "Type your message here...");
    }
});

/* AUTO REFRESH */
setInterval(loadChat, 2000);
loadChat();
</script>

<?php require_once("../includes/footer.php"); ?>