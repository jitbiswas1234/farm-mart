<?php
session_start();
require_once '../../includes/config.php';

// Security: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'User';
$first_name = explode(' ', $user_name)[0];

// Variables for Sidebar
$orders_count = 0;
$cart_count = 0;
$notif_count = 0;
$has_photo = false;
$profile_url = '';
$initials = strtoupper(substr($first_name, 0, 1) . substr(explode(' ', $user_name)[1] ?? '', 0, 1));

// Greeting
$current_hour = date('H');
$greeting = 'Good Morning';
$greeting_icon = 'sunrise-fill';
if ($current_hour >= 12 && $current_hour < 18) {
    $greeting = 'Good Afternoon';
    $greeting_icon = 'sun-fill';
} elseif ($current_hour >= 18 || $current_hour < 5) {
    $greeting = 'Good Evening';
    $greeting_icon = 'moon-stars-fill';
}

// --- Fetch Dashboard Data ---
$total_orders = 0;
$pending_orders = 0;
$delivered_orders = 0;
$total_spent = 0;

$stmt_stats = $conn->prepare("
    SELECT
        COUNT(id) AS total_orders,
        SUM(CASE WHEN status IN ('pending', 'processing') THEN 1 ELSE 0 END) AS pending_orders,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered_orders,
        COALESCE(SUM(total_amount), 0) AS total_spent
    FROM orders
    WHERE user_id = ?
");
$stmt_stats->bind_param("i", $user_id);
$stmt_stats->execute();
$result_stats = $stmt_stats->get_result();
$stats = $result_stats->fetch_assoc();
$stmt_stats->close();

if ($stats) {
    $total_orders = $stats['total_orders'];
    $pending_orders = $stats['pending_orders'];
    $delivered_orders = $stats['delivered_orders'];
    $total_spent = $stats['total_spent'];
}

$aov = $total_orders > 0 ? $total_spent / $total_orders : 0;

// Recent Orders
$recent_orders = [];
$stmt_recent = $conn->prepare("
    SELECT id, total_amount, status, created_at
    FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 6
");
$stmt_recent->bind_param("i", $user_id);
$stmt_recent->execute();
$recent_orders = $stmt_recent->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_recent->close();

// Notifications
$notifications = [];
$stmt_notif = $conn->prepare("
    SELECT message, is_read, created_at
    FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 4
");
$stmt_notif->bind_param("i", $user_id);
$stmt_notif->execute();
$notifications = $stmt_notif->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_notif->close();
$notif_count = count($notifications);

// Count pending orders for sidebar badge
$orders_count = $pending_orders;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmMart | Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
    /* FarmMart User Dashboard Theme */
    :root {
        --theme-primary: #047857; /* Emerald Green */
        --theme-primary-hover: #059669;
        --theme-bg: #f3f4f6;
        --theme-surface: #ffffff;
        --theme-text-dark: #111827;
        --theme-text-muted: #6b7280;
        --theme-border: #e5e7eb;
        --theme-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        --theme-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
        
        --font-heading: 'Poppins', sans-serif;
        --font-body: 'Inter', sans-serif;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        letter-spacing: -0.02em;
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
    .header-greeting span { color: var(--theme-text-muted); font-weight: 500; }

    .header-right { display: flex; align-items: center; gap: 15px; }

    .btn-premium {
        background: var(--theme-primary);
        color: #fff;
        padding: 10px 24px;
        border-radius: 50px;
        font-family: var(--font-heading);
        font-weight: 600;
        font-size: 14px;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        border: none;
        box-shadow: 0 4px 10px rgba(4, 120, 87, 0.15);
    }
    .btn-premium:hover {
        background: var(--theme-primary-hover);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(4, 120, 87, 0.25);
    }

    .header-icon {
        width: 40px; height: 40px; border-radius: 12px; background: #f3f4f6;
        color: var(--theme-text-dark); border: 1px solid transparent;
        display: flex; align-items: center; justify-content: center; cursor: pointer;
        transition: var(--transition); position: relative; font-size: 1.1rem;
    }
    .header-icon:hover { border-color: var(--theme-border); background: #fff; transform: translateY(-2px); }
    .header-icon .dot {
        position: absolute; top: 8px; right: 8px; width: 8px; height: 8px;
        background: #ef4444; border-radius: 50%; box-shadow: 0 0 0 2px #f3f4f6;
    }

    /* === CONTENT === */
    .main-content { padding: 30px 32px; flex: 1; }

    /* Welcome Banner */
    .welcome-banner {
        background: linear-gradient(135deg, var(--theme-primary) 0%, #064e3b 100%);
        border-radius: 20px;
        padding: 30px 40px;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        box-shadow: var(--theme-shadow-lg);
        position: relative;
        overflow: hidden;
    }
    .welcome-banner::after {
        content: '';
        position: absolute;
        right: -5%;
        top: -20%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
        border-radius: 50%;
    }
    .welcome-text h2 { color: white; margin-bottom: 5px; font-size: 28px; }
    .welcome-text p { color: #d1fae5; margin: 0; font-size: 15px; }

    /* === PANELS === */
    .panel {
        background: var(--theme-surface);
        border-radius: 20px;
        padding: 24px;
        transition: var(--transition);
        position: relative;
        margin-bottom: 24px;
        box-shadow: var(--theme-shadow);
        border: 1px solid rgba(0,0,0,0.02);
    }

    .panel-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 20px;
    }
    .panel-title { 
        font-size: 18px; font-weight: 700; color: var(--theme-text-dark); 
        display: flex; align-items: center; gap: 10px;
    }
    .panel-title i { color: var(--theme-primary); font-size: 1.2rem; background: rgba(4, 120, 87, 0.1); padding: 6px; border-radius: 8px; }
    .panel-link {
        font-size: 13px; font-weight: 600; color: var(--theme-primary);
        text-decoration: none; transition: var(--transition);
        background: rgba(4, 120, 87, 0.05); padding: 6px 12px; border-radius: 20px;
    }
    .panel-link:hover { background: var(--theme-primary); color: white; }

    /* === KPI GRID === */
    .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 24px; }

    .kpi-card { padding: 24px; display: flex; flex-direction: column; gap: 15px; }
    .kpi-card:hover { transform: translateY(-5px); box-shadow: var(--theme-shadow-lg); }
    .kpi-top { display: flex; justify-content: space-between; align-items: center; width: 100%; }
    
    .kpi-icon {
        width: 48px; height: 48px; border-radius: 12px; display: flex;
        align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;
    }
    .kpi-label { font-size: 14px; color: var(--theme-text-muted); font-weight: 500; }
    
    .icon-wallet { background: rgba(4, 120, 87, 0.1); color: var(--theme-primary); }
    .icon-bag { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .icon-clock { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .icon-box { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }

    .kpi-value { font-size: 28px; font-weight: 800; color: var(--theme-text-dark); line-height: 1.2; font-family: var(--font-heading); }

    /* === GRID LAYOUTS === */
    .grid-2-1 { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }

    /* === TABLE === */
    .table-responsive { overflow-x: auto; }
    .data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .data-table th {
        font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
        color: var(--theme-text-muted); padding: 12px 16px; text-align: left; 
        border-bottom: 1px solid var(--theme-border);
    }
    .data-table td {
        padding: 16px; font-size: 14.5px; color: var(--theme-text-dark); font-weight: 500;
        border-bottom: 1px solid #f3f4f6; vertical-align: middle;
    }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table tbody tr:hover td { background: #f9fafb; }

    .td-id { color: var(--theme-text-muted); font-weight: 600; font-family: monospace; font-size: 13px; }
    .td-amount { font-weight: 700; color: var(--theme-primary); }

    .status-badge {
        display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px;
        border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
    
    .status-badge.delivered { background: #ecfdf5; color: #047857; }
    .status-badge.delivered::before { background: #047857; }
    
    .status-badge.pending, .status-badge.processing { background: #fffbeb; color: #b45309; }
    .status-badge.pending::before, .status-badge.processing::before { background: #b45309; }
    
    .status-badge.cancelled { background: #fef2f2; color: #b91c1c; }
    .status-badge.cancelled::before { background: #b91c1c; }

    /* === NOTIFICATION LIST === */
    .notif-list { display: flex; flex-direction: column; gap: 15px; }
    .notif-item {
        display: flex; gap: 15px; padding: 16px;
        border-radius: 12px; background: #fff;
        transition: var(--transition); border: 1px solid var(--theme-border);
    }
    .notif-item:hover { border-color: var(--theme-primary); box-shadow: 0 4px 12px rgba(4, 120, 87, 0.05); }
    .notif-item.unread { background: #f0fdf4; border-color: rgba(4, 120, 87, 0.2); }
    .notif-icon-sm {
        width: 36px; height: 36px; border-radius: 10px; display: flex;
        align-items: center; justify-content: center; flex-shrink: 0; font-size: 16px;
        background: rgba(245, 158, 11, 0.1); color: #f59e0b;
    }
    .notif-text { font-size: 13.5px; margin-bottom: 4px; color: var(--theme-text-dark); line-height: 1.4; }
    .notif-item.unread .notif-text { font-weight: 600; }
    .notif-time { font-size: 11px; color: var(--theme-text-muted); display: flex; align-items: center; gap: 4px; }

    /* === EMPTY STATE === */
    .empty-state { text-align: center; padding: 40px 20px; }
    .empty-state i { font-size: 48px; display: block; margin-bottom: 15px; color: #e5e7eb; }
    .empty-state p { color: var(--theme-text-muted); margin-bottom: 20px; font-size: 15px; }

    /* === RESPONSIVE === */
    @media (max-width: 1400px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 1200px) { .grid-2-1 { grid-template-columns: 1fr; } }
    @media (max-width: 992px) {
        .main-wrapper { margin-left: 0; }
        .sidebar-wrapper { position: absolute; left: -260px; }
        .main-content { padding: 20px; }
        .top-header { padding: 0 20px; }
        .welcome-banner { flex-direction: column; text-align: center; gap: 20px; }
    }
    @media (max-width: 768px) {
        .kpi-grid { grid-template-columns: 1fr; }
        .header-greeting { display: none; }
        .kpi-card { padding: 20px; }
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
                    Dashboard <span>/ Overview</span>
                </div>
            </div>
            <div class="header-right">
                <a href="../../products.php" class="btn-premium">
                    <i class="bi bi-shop"></i> Explore Market
                </a>
                <a href="notifications.php" class="header-icon" title="Notifications">
                    <i class="bi bi-bell"></i>
                    <?php if ($notif_count > 0): ?><span class="dot"></span><?php endif; ?>
                </a>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="main-content">

            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h2><?= $greeting ?>, <?= htmlspecialchars($first_name) ?>! 👋</h2>
                    <p>Welcome back to your FarmMart portal. Here's what's happening with your farm fresh orders today.</p>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="panel kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-icon icon-bag"><i class="bi bi-bag-heart"></i></div>
                        <div class="kpi-label">Total Orders</div>
                    </div>
                    <div class="kpi-value"><?= $total_orders ?></div>
                </div>
                <div class="panel kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-icon icon-wallet"><i class="bi bi-cash-coin"></i></div>
                        <div class="kpi-label">Total Spent</div>
                    </div>
                    <div class="kpi-value">₹<?= number_format($total_spent, 2) ?></div>
                </div>
                <div class="panel kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-icon icon-clock"><i class="bi bi-hourglass-split"></i></div>
                        <div class="kpi-label">Processing</div>
                    </div>
                    <div class="kpi-value"><?= $pending_orders ?></div>
                </div>
                <div class="panel kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-icon icon-box"><i class="bi bi-box2-heart"></i></div>
                        <div class="kpi-label">Delivered</div>
                    </div>
                    <div class="kpi-value"><?= $delivered_orders ?></div>
                </div>
            </div>

            <div class="grid-2-1">
                <!-- Orders Table -->
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title"><i class="bi bi-receipt"></i> Recent Orders</div>
                        <a href="orders.php" class="panel-link">View All Orders</a>
                    </div>
                    <?php if (!empty($recent_orders)): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date Ordered</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><span class="td-id">#ORD-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></span></td>
                                    <td><i class="bi bi-calendar3 text-muted me-1"></i> <?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                    <td><span class="td-amount">₹<?= number_format($order['total_amount'], 2) ?></span></td>
                                    <td>
                                        <span class="status-badge <?= strtolower($order['status']) ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-cart-x"></i>
                            <p>You haven't placed any orders yet.</p>
                            <a href="../../products.php" class="btn-premium d-inline-flex">Start Shopping</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Notifications -->
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title"><i class="bi bi-bell"></i> Recent Alerts</div>
                        <a href="notifications.php" class="panel-link">See All</a>
                    </div>
                    <?php if (!empty($notifications)): ?>
                        <div class="notif-list">
                            <?php foreach ($notifications as $notif): ?>
                            <div class="notif-item <?= $notif['is_read'] ? '' : 'unread' ?>">
                                <div class="notif-icon-sm"><i class="bi bi-info-circle"></i></div>
                                <div>
                                    <div class="notif-text"><?= htmlspecialchars($notif['message']) ?></div>
                                    <div class="notif-time"><i class="bi bi-clock"></i> <?= date('M j, h:i A', strtotime($notif['created_at'])) ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-check2-circle text-success"></i>
                            <p>You're all caught up!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

</body>
</html>