<?php
session_start();
require_once("../includes/config.php");

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* SAFE QUERY */
$stmt = $conn->prepare("
SELECT 
    u.id,
    u.name,
    u.profile_picture,

    (
        SELECT m.message 
        FROM messages m
        WHERE (m.sender_id = u.id AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = u.id)
        ORDER BY m.created_at DESC
        LIMIT 1
    ) AS last_message,

    (
        SELECT m.created_at 
        FROM messages m
        WHERE (m.sender_id = u.id AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = u.id)
        ORDER BY m.created_at DESC
        LIMIT 1
    ) AS last_time

FROM users u

WHERE u.id IN (
    SELECT sender_id FROM messages WHERE receiver_id = ?
    UNION
    SELECT receiver_id FROM messages WHERE sender_id = ?
)
AND u.id != ?

ORDER BY last_time DESC
");

$stmt->bind_param("iiiiiii",
    $user_id, $user_id,
    $user_id, $user_id,
    $user_id, $user_id,
    $user_id
);

$stmt->execute();
$res = $stmt->get_result();

include '../includes/header.php';
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

.card-title {
  padding: 20px 0 15px 0;
  font-size: 18px;
  font-weight: 500;
  color: #012970;
  font-family: "Poppins", sans-serif;
}

/* USER ITEM Custom to Chat */
.chat-user {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 20px;
    border-bottom: 1px solid #ebeef4;
    text-decoration: none;
    color: inherit;
    transition: .3s;
}

.chat-user:hover {
    background: #f6f9ff;
}

.chat-user:last-child {
    border-bottom: none;
}

.avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.name {
    color: #012970;
    font-weight: 600;
    font-size: 16px;
}

.last-msg {
    font-size: 14px;
    color: #899bbd;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 300px;
}

.empty {
    text-align: center;
    padding: 60px 20px;
}
</style>

<?php include '../includes/navbar.php'; ?>

<main class="container-fluid py-4 px-4">
    <div class="row g-4">
        
        <!-- Load correct sidebar based on role -->
        <div class="col-lg-3 dashboard-sidebar">
            <?php 
                if($_SESSION['role'] == 'farmer') {
                    require_once("../vendor/sidebar.php"); 
                } elseif ($_SESSION['role'] == 'admin') {
                    require_once("../includes/admin_sidebar.php");
                }
            ?>
        </div>

        <div class="col-lg-9">
            <div class="pagetitle mb-4">
              <h1 class="fw-bold" style="color: #012970; font-size: 24px;">Messages Inbox</h1>
              <nav>
                <ol class="breadcrumb" style="background: transparent; padding: 0;">
                  <li class="breadcrumb-item"><a href="../index.php" style="color: #899bbd; text-decoration: none;">Home</a></li>
                  <li class="breadcrumb-item active" style="color: #444444; font-weight: 600;">Messages</li>
                </ol>
              </nav>
            </div><!-- End Page Title -->

            <section class="section">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body p-0">
                                <?php if($res->num_rows > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php while($row = $res->fetch_assoc()): 
                                            $img = !empty($row['profile_picture']) 
                                                ? "../uploads/users/".$row['profile_picture'] 
                                                : "../assets/default-user.png";
                                        ?>
                                        <a href="chat.php?user=<?= $row['id'] ?>" class="chat-user">
                                            <img src="<?= $img ?>" class="avatar">
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <div class="name"><?= htmlspecialchars($row['name']) ?></div>
                                                    <small class="text-muted" style="font-size: 12px;">
                                                        <?= $row['last_time'] ? date('h:i A', strtotime($row['last_time'])) : '' ?>
                                                    </small>
                                                </div>
                                                <div class="last-msg">
                                                    <?= htmlspecialchars($row['last_message'] ?? 'No messages yet') ?>
                                                </div>
                                            </div>
                                        </a>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty">
                                        <i class="bi bi-chat-left-text text-muted d-block mb-3" style="font-size: 40px; opacity: 0.5;"></i>
                                        <h5 style="color: #012970;">No Conversations</h5>
                                        <p class="text-muted">Start chatting with users</p>
                                    </div>
                                <?php endif; ?>
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

<?php require_once("../includes/footer.php"); ?>