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

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

// Build query
$query = "SELECT id, total_amount, status, created_at FROM orders WHERE user_id = ?";
$params = [$user_id];
$types = "i";

// Apply status filter
if ($status_filter !== 'all') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Apply sorting
switch ($sort_by) {
    case 'oldest':
        $query .= " ORDER BY created_at ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY total_amount DESC";
        break;
    case 'price_low':
        $query .= " ORDER BY total_amount ASC";
        break;
    default: // latest
        $query .= " ORDER BY created_at DESC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$all_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get order statistics
$stmt_stats = $conn->prepare("
    SELECT
        COUNT(id) AS total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled
    FROM orders WHERE user_id = ?
");
$stmt_stats->bind_param("i", $user_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

$orders_count = $stats['pending'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmMart | My Orders</title>
    
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
    .header-greeting span { color: var(--theme-text-muted); font-weight: 500; }

    .header-right { display: flex; align-items: center; gap: 15px; }

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
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 20px;
    }
    .panel-title { 
        font-size: 16px; font-weight: 700; color: var(--theme-text-dark); 
        display: flex; align-items: center; gap: 8px;
    }
    .panel-title i { color: var(--theme-primary); font-size: 1.2rem; background: rgba(4, 120, 87, 0.1); padding: 6px; border-radius: 8px; }

    /* === FILTERS === */
    .filters-section {
        display: flex; gap: 20px; align-items: center; margin-bottom: 0;
        flex-wrap: wrap;
    }

    .filter-group {
        display: flex; gap: 10px; align-items: center;
    }

    .filter-label {
        font-size: 13px; font-weight: 600; color: var(--theme-text-dark);
        text-transform: uppercase;
    }

    .filter-select {
        background: #f9fafb; border: 1px solid var(--theme-border);
        color: var(--theme-text-dark); padding: 10px 16px; border-radius: 8px;
        font-size: 14px; font-weight: 500; font-family: var(--font-body); transition: var(--transition);
        cursor: pointer; min-width: 200px;
    }

    .filter-select:focus {
        outline: none; border-color: var(--theme-primary); box-shadow: 0 0 0 3px rgba(4, 120, 87, 0.1);
        background: #fff;
    }

    /* === KPI GRID (Tabs Style) === */
    .kpi-grid { display: flex; gap: 16px; margin-bottom: 30px; flex-wrap: wrap;}

    .kpi-stat {
        background: #fff; border: 1px solid var(--theme-border); border-radius: 16px;
        padding: 20px 24px; text-align: left; transition: var(--transition);
        cursor: pointer; display: flex; flex-direction: column; justify-content: center;
        flex: 1; min-width: 140px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
    }
    .kpi-stat:hover { border-color: #d1d5db; transform: translateY(-3px); box-shadow: var(--theme-shadow); }
    .kpi-stat.active { background: var(--theme-primary); border-color: var(--theme-primary); box-shadow: 0 8px 15px rgba(4, 120, 87, 0.2); }
    
    .kpi-stat-label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; color: var(--theme-text-muted); }
    .kpi-stat.active .kpi-stat-label { color: rgba(255,255,255,0.8); }
    
    .kpi-stat-value { font-size: 28px; font-weight: 700; font-family: var(--font-heading); color: var(--theme-text-dark); line-height: 1;}
    .kpi-stat.active .kpi-stat-value { color: #fff; }

    /* === ORDERS LIST TABLE === */
    .table-responsive { overflow-x: auto; }
    .data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .data-table th {
        font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
        color: var(--theme-text-muted); padding: 16px; text-align: left; 
        border-bottom: 1px solid var(--theme-border); background: #f9fafb;
    }
    .data-table td {
        padding: 16px; font-size: 14.5px; color: var(--theme-text-dark); font-weight: 500;
        border-bottom: 1px solid #f3f4f6; vertical-align: middle;
    }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table tbody tr:hover td { background: #f9fafb; }

    .td-id { color: var(--theme-text-dark); font-weight: 700; font-size: 15px; font-family: monospace; }
    .td-amount { color: var(--theme-primary); font-weight: 700; }

    .status-badge {
        display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px;
        border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
    
    .status-badge.delivered { background: #ecfdf5; color: #047857; }
    .status-badge.delivered::before { background: #047857; }
    
    .status-badge.pending, .status-badge.processing { background: #fffbeb; color: #b45309; }
    .status-badge.pending::before, .status-badge.processing::before { background: #b45309; }
    
    .status-badge.cancelled { background: #fef2f2; color: #b91c1c; }
    .status-badge.cancelled::before { background: #b91c1c; }

    /* Action Buttons */
    .btn-premium {
        background: var(--theme-primary);
        color: #fff;
        padding: 10px 24px;
        border-radius: 50px;
        font-family: var(--font-heading);
        font-weight: 600;
        font-size: 13px;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        justify-content: center;
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
    
    .btn-outline {
        background: transparent;
        color: var(--theme-text-dark);
        border: 1px solid var(--theme-border);
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
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
    .empty-state p { color: var(--theme-text-muted); margin-bottom: 24px; font-size: 15px; }

    /* === RESPONSIVE === */
    @media (max-width: 992px) {
        .main-wrapper { margin-left: 0; }
        .sidebar-wrapper { position: absolute; left: -260px; }
        .main-content { padding: 20px; }
        .top-header { padding: 0 20px; }
    }
    @media (max-width: 768px) {
        .main-content { padding: 20px 16px; }
        .filters-section { flex-direction: column; align-items: stretch; }
        .filter-select { width: 100%; }
        .header-greeting { display: none; }
        .kpi-grid { flex-direction: column; }
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
                    Order History <span>/ <?= ucfirst($status_filter) ?> Orders</span>
                </div>
            </div>
            <div class="header-right">
                <a href="../../products.php" class="btn-premium" style="background: transparent; color: var(--theme-text-dark); box-shadow: none; border: 1px solid var(--theme-border);">
                    <i class="bi bi-shop"></i> Browse Store
                </a>
                <a href="notifications.php" class="header-icon" title="Notifications">
                    <i class="bi bi-bell"></i>
                    <?php if ($notif_count > 0): ?><span class="dot"></span><?php endif; ?>
                </a>
            </div>
        </header>

        <!-- Orders Content -->
        <main class="main-content">

            <!-- Order Statistics (Tabs) -->
            <div class="kpi-grid">
                <div class="kpi-stat <?= $status_filter === 'all' ? 'active' : '' ?>" onclick="window.location.href='?status=all'">
                    <div class="kpi-stat-label">Total Orders</div>
                    <div class="kpi-stat-value"><?= $stats['total'] ?? 0 ?></div>
                </div>
                <div class="kpi-stat <?= $status_filter === 'pending' ? 'active' : '' ?>" onclick="window.location.href='?status=pending'">
                    <div class="kpi-stat-label">Pending</div>
                    <div class="kpi-stat-value"><?= $stats['pending'] ?? 0 ?></div>
                </div>
                <div class="kpi-stat <?= $status_filter === 'processing' ? 'active' : '' ?>" onclick="window.location.href='?status=processing'">
                    <div class="kpi-stat-label">Processing</div>
                    <div class="kpi-stat-value"><?= $stats['processing'] ?? 0 ?></div>
                </div>
                <div class="kpi-stat <?= $status_filter === 'delivered' ? 'active' : '' ?>" onclick="window.location.href='?status=delivered'">
                    <div class="kpi-stat-label">Delivered</div>
                    <div class="kpi-stat-value"><?= $stats['delivered'] ?? 0 ?></div>
                </div>
                <div class="kpi-stat <?= $status_filter === 'cancelled' ? 'active' : '' ?>" onclick="window.location.href='?status=cancelled'">
                    <div class="kpi-stat-label">Cancelled</div>
                    <div class="kpi-stat-value"><?= $stats['cancelled'] ?? 0 ?></div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header" style="flex-wrap: wrap; gap: 15px;">
                    <div class="panel-title"><i class="bi bi-receipt"></i> Order Records</div>
                    <!-- Filters -->
                    <form method="GET" style="margin: 0;">
                        <div class="filters-section">
                            <div class="filter-group">
                                <label class="filter-label">Sort By:</label>
                                <select name="sort" class="filter-select" onchange="this.form.submit()">
                                    <option value="latest" <?= $sort_by === 'latest' ? 'selected' : '' ?>>Latest First</option>
                                    <option value="oldest" <?= $sort_by === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                    <option value="price_high" <?= $sort_by === 'price_high' ? 'selected' : '' ?>>Highest Price</option>
                                    <option value="price_low" <?= $sort_by === 'price_low' ? 'selected' : '' ?>>Lowest Price</option>
                                </select>
                                <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                            </div>
                        </div>
                    </form>
                </div>

                <?php if (!empty($all_orders)): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="padding-left: 24px;">Order Ref</th>
                                    <th>Date Placed</th>
                                    <th>Total Items</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th style="text-align: right; padding-right: 24px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_orders as $order): 
                                    // Get order items count
                                    $stmt_items = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ?");
                                    $stmt_items->bind_param("i", $order['id']);
                                    $stmt_items->execute();
                                    $items_count = $stmt_items->get_result()->fetch_assoc()['count'];
                                    $stmt_items->close();
                                ?>
                                <tr>
                                    <td style="padding-left: 24px;"><span class="td-id">#ORD-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></span></td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--theme-text-dark);"><?= date('M j, Y', strtotime($order['created_at'])) ?></div>
                                        <div style="font-size: 12px; color: var(--theme-text-muted);"><?= date('h:i A', strtotime($order['created_at'])) ?></div>
                                    </td>
                                    <td><i class="bi bi-box-seam me-1 text-muted"></i> <?= $items_count ?> Item<?= $items_count > 1 ? 's' : '' ?></td>
                                    <td><span class="td-amount">₹<?= number_format($order['total_amount'], 2) ?></span></td>
                                    <td>
                                        <span class="status-badge <?= strtolower($order['status']) ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; padding-right: 24px;">
                                        <a href="view_order.php?id=<?= $order['id'] ?>" class="btn-outline">
                                            <i class="bi bi-eye"></i> Details
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h3>No Orders Found</h3>
                        <p>You haven't placed any orders matching this criteria yet.</p>
                        <a href="../../products.php" class="btn-premium d-inline-flex px-4 py-3 mt-2" style="font-size: 15px;">
                            <i class="bi bi-shop me-2"></i> Browse Store
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

</body>
</html>