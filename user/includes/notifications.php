<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'User';
$first_name = explode(' ', $user_name)[0];

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_as_read') {
        $notif_id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notif_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

/* FETCH NOTIFICATIONS */
$stmt = $conn->prepare("
    SELECT id, title, message, is_read, created_at 
    FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count unread
$unread_count = 0;
foreach ($notifications as $n) {
    if (!$n['is_read']) $unread_count++;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmMart | Notifications</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
    /* ModernAdmin Inspired Theme for Dashboard */
    :root {
        --theme-primary: #047857; /* Emerald Green */
        --theme-primary-hover: #059669;
        --theme-bg: #f9fafb;
        --theme-surface: #ffffff;
        --theme-text-dark: #111827;
        --theme-text-muted: #6b7280;
        --theme-border: #e5e7eb;
        --theme-shadow: 0 10px 30px rgba(0,0,0,0.03);
        --font-heading: 'Poppins', sans-serif;
        --font-body: 'Inter', sans-serif;
        --transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: var(--font-body);
        background-color: var(--theme-bg);
        color: var(--theme-text-muted);
        font-size: 14px;
        line-height: 1.6;
        overflow-x: hidden;
    }

    h1, h2, h3, h4, h5, h6 {
        font-family: var(--font-heading);
        color: var(--theme-text-dark);
        font-weight: 700;
        margin: 0;
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
        height: 70px; background: var(--theme-surface);
        border-bottom: 1px solid var(--theme-border);
        display: flex; align-items: center; justify-content: space-between;
        padding: 0 32px; position: sticky; top: 0; z-index: 50;
    }
    .header-left { display: flex; align-items: center; gap: 20px; }
    .header-greeting { font-size: 18px; font-weight: 600; color: var(--theme-text-dark); font-family: var(--font-heading); }
    .header-greeting span { color: var(--theme-text-muted); font-weight: 500; font-family: var(--font-body);}

    /* === CONTENT === */
    .main-content { padding: 30px 32px; flex: 1; }

    /* === PANELS === */
    .panel {
        background: var(--theme-surface);
        border-radius: 16px;
        padding: 24px;
        transition: var(--transition);
        position: relative;
        margin-bottom: 24px;
        box-shadow: var(--theme-shadow);
        border: 1px solid rgba(0,0,0,0.02);
    }

    .panel-header {
        margin-bottom: 24px;
        border-bottom: 1px solid #f3f4f6;
        padding-bottom: 16px;
        display: flex; justify-content: space-between; align-items: center;
    }
    .panel-title { 
        font-size: 16px; font-weight: 700; color: var(--theme-text-dark); 
        display: flex; align-items: center; gap: 10px;
    }
    .panel-title i { color: var(--theme-primary); font-size: 1.2rem; background: rgba(4, 120, 87, 0.1); padding: 6px; border-radius: 8px; }
    .panel-subtitle { font-size: 13px; color: var(--theme-text-muted); font-weight: 600;}

    /* === NOTIFICATIONS LIST === */
    .notifications-list { display: flex; flex-direction: column; gap: 16px; }

    .notification-card {
        background: #f9fafb; border: 1px solid var(--theme-border); border-radius: 12px;
        padding: 16px 20px; transition: var(--transition);
        display: flex; gap: 16px; align-items: flex-start;
    }
    .notification-card:hover { border-color: #d1d5db; background: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .notification-card.unread { background: #fff; border-color: rgba(4, 120, 87, 0.2); box-shadow: 0 4px 10px rgba(0,0,0,0.02); }

    .notif-icon {
        width: 42px; height: 42px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; font-size: 1.2rem; background: rgba(245, 158, 11, 0.1); color: #f59e0b;
    }
    
    .notification-card.unread .notif-icon { background: rgba(4, 120, 87, 0.1); color: var(--theme-primary); }

    .notif-content { flex: 1; }
    .notif-title { font-weight: 600; font-family: var(--font-heading); font-size: 15px; color: var(--theme-text-dark); margin-bottom: 2px;}
    .notif-message { font-size: 14px; color: var(--theme-text-muted); margin-bottom: 6px; line-height: 1.5; }
    .notification-card.unread .notif-message { color: var(--theme-text-dark); font-weight: 500;}
    .notif-time { font-size: 12px; color: #9ca3af; display: flex; align-items: center; gap: 4px;}

    .btn-outline {
        background: transparent;
        color: var(--theme-text-dark);
        border: 1px solid var(--theme-border);
        padding: 6px 12px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 12px;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-outline:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
        color: var(--theme-primary);
    }

    /* === EMPTY STATE === */
    .empty-state { text-align: center; padding: 60px 20px; }
    .empty-state i { font-size: 60px; display: block; margin-bottom: 20px; color: #e5e7eb; }
    .empty-state h3 { color: var(--theme-text-dark); margin-bottom: 10px; font-size: 22px; }
    .empty-state p { color: var(--theme-text-muted); font-size: 15px; }

    /* === RESPONSIVE === */
    @media (max-width: 992px) {
        .main-wrapper { margin-left: 0; }
        .sidebar-wrapper { position: absolute; left: -260px; }
        .main-content { padding: 20px; }
        .top-header { padding: 0 20px; }
    }
    @media (max-width: 768px) {
        .header-greeting { display: none; }
        .notification-card { flex-direction: column; gap: 10px;}
    }
    </style>
</head>
<body>

<div class="app-layout">
    
    <!-- Include the Sidebar -->
    <?php include '../../includes/user_sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-left">
                <div class="header-greeting">
                    Notifications <span>/ Inbox</span>
                </div>
            </div>
        </header>

        <!-- Notifications Content -->
        <main class="main-content">

            <?php if (!empty($notifications)): ?>

                <!-- Notifications List -->
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title"><i class="bi bi-bell"></i> System Alerts</div>
                        <span class="panel-subtitle">
                            <?= $unread_count ?> unread message<?= $unread_count != 1 ? 's' : '' ?>
                        </span>
                    </div>

                    <div class="notifications-list">
                        <?php foreach($notifications as $n): ?>
                        <div class="notification-card <?= $n['is_read'] ? '' : 'unread' ?>">
                            <div class="notif-icon">
                                <i class="bi <?= $n['is_read'] ? 'bi-info-circle' : 'bi-bell-fill' ?>"></i>
                            </div>
                            <div class="notif-content">
                                <?php if(isset($n['title']) && !empty($n['title'])): ?>
                                    <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
                                <?php endif; ?>
                                <div class="notif-message"><?= htmlspecialchars($n['message']) ?></div>
                                <div class="notif-time"><i class="bi bi-clock"></i> <?= date('M j, Y \a\t H:i', strtotime($n['created_at'])) ?></div>
                            </div>
                            <?php if(!$n['is_read']): ?>
                            <form method="POST" style="margin: 0; align-self: center;">
                                <input type="hidden" name="action" value="mark_as_read">
                                <input type="hidden" name="id" value="<?= $n['id'] ?>">
                                <button type="submit" class="btn-outline"><i class="bi bi-check2"></i> Mark Read</button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php else: ?>
                <!-- Empty State -->
                <div class="panel">
                    <div class="empty-state">
                        <i class="bi bi-check2-circle text-success" style="opacity: 0.8;"></i>
                        <h3>Inbox is Empty</h3>
                        <p>You're all caught up! Come back later for updates regarding your orders.</p>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>