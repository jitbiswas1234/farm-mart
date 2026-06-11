<?php
require_once("../includes/auth.php");
require_once("../includes/config.php");

// Protect the page: Ensure the user is logged in AND is an admin
if ($_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit();
}

$page_title = "Admin Dashboard - FarmMart";

// Fetch Quick Stats safely
$total_users = 0;
$total_farmers = 0;
$total_approved_farmers = 0;
$pending_farmers = 0;
$pending_orders = 0;
$total_products = 0;
$total_orders = 0;
$total_revenue = 0;
$today_revenue = 0;

// Get all statistics
$u_res = $conn->query("SELECT COUNT(id) as count FROM users WHERE role = 'user'");
if ($u_res) $total_users = $u_res->fetch_assoc()['count'];

$f_res = $conn->query("SELECT COUNT(id) as count FROM farmers");
if ($f_res) $total_farmers = $f_res->fetch_assoc()['count'];

$af_res = $conn->query("SELECT COUNT(id) as count FROM farmers WHERE status = 'approved'");
if ($af_res) $total_approved_farmers = $af_res->fetch_assoc()['count'];

$pf_res = $conn->query("SELECT COUNT(id) as count FROM farmers WHERE status = 'pending'");
if ($pf_res) $pending_farmers = $pf_res->fetch_assoc()['count'];

$po_res = $conn->query("SELECT COUNT(id) as count FROM orders WHERE status = 'pending'");
if ($po_res) $pending_orders = $po_res->fetch_assoc()['count'];

$p_res = $conn->query("SELECT COUNT(id) as count FROM products WHERE status = 'active'");
if ($p_res) $total_products = $p_res->fetch_assoc()['count'];

$o_res = $conn->query("SELECT COUNT(id) as count FROM orders");
if ($o_res) $total_orders = $o_res->fetch_assoc()['count'];

$rev_res = $conn->query("SELECT 
    SUM(total_amount) as total_revenue,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total_amount ELSE 0 END) as today_revenue
    FROM orders WHERE payment_status = 'completed'");
if ($rev_res) {
    $rev_data = $rev_res->fetch_assoc();
    $total_revenue = $rev_data['total_revenue'] ?? 0;
    $today_revenue = $rev_data['today_revenue'] ?? 0;
}

// Get recent orders
$recent_orders_query = "SELECT o.*, u.name as user_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC LIMIT 8";
$recent_orders = $conn->query($recent_orders_query);

// Get top selling products
$top_products_query = "SELECT p.name, p.image, p.id, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    GROUP BY oi.product_id 
    ORDER BY total_sold DESC LIMIT 5";
$top_products = $conn->query($top_products_query);

// Get monthly sales data
$monthly_sales_query = "SELECT 
    MONTH(created_at) as month,
    SUM(total_amount) as amount,
    COUNT(*) as orders
    FROM orders 
    WHERE YEAR(created_at) = YEAR(CURDATE()) AND payment_status = 'completed'
    GROUP BY MONTH(created_at)
    ORDER BY month";
$monthly_sales = $conn->query($monthly_sales_query);
$chart_data = array_fill(1, 12, 0);
while ($row = $monthly_sales->fetch_assoc()) {
    $chart_data[$row['month']] = $row['amount'];
}

// Helper function to format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

// Helper function to format date
function formatDate($date) {
    return date('d M Y, h:i A', strtotime($date));
}

// Helper function to get status badge
function getStatusBadge($status) {
    $badges = [
        'pending' => ['class' => 'warning', 'icon' => 'hourglass-half'],
        'approved' => ['class' => 'success', 'icon' => 'check-circle'],
        'rejected' => ['class' => 'danger', 'icon' => 'x-circle'],
        'active' => ['class' => 'success', 'icon' => 'check-circle'],
        'inactive' => ['class' => 'secondary', 'icon' => 'dash-circle'],
        'processing' => ['class' => 'info', 'icon' => 'arrow-repeat'],
        'completed' => ['class' => 'success', 'icon' => 'check-lg'],
        'cancelled' => ['class' => 'danger', 'icon' => 'x-lg'],
        'delivered' => ['class' => 'primary', 'icon' => 'box-seam']
    ];
    
    $badge = $badges[$status] ?? ['class' => 'secondary', 'icon' => 'question-circle'];
    return "<span class='badge bg-{$badge['class']}'><i class='bi bi-{$badge['icon']} me-1'></i>" . ucfirst($status) . "</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-color: #10b981;
            --primary-dark: #059669;
            --light-bg: #f9fafb;
            --border-color: #e5e7eb;
            --text-muted: #6b7280;
            --card-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            --card-hover-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        body {
            background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .dashboard-shell {
            display: block !important;
            position: relative;
            gap: 0;
        }

        main.container-fluid .dashboard-shell > .col-lg-3,
        main.container-fluid .dashboard-shell > .admin-sidebar-column {
            position: fixed !important;
            top: 1.5rem !important;
            left: 1.5rem !important;
            width: 280px !important;
            max-width: 280px !important;
            flex: 0 0 280px !important;
            z-index: 10 !important;
        }

        main.container-fluid .dashboard-shell > .col-lg-9,
        main.container-fluid .dashboard-shell > .dashboard-main-column {
            margin-left: 320px !important;
            width: calc(100% - 320px) !important;
            max-width: calc(100% - 320px) !important;
            flex: none !important;
            min-width: 0 !important;
        }

        .admin-hero {
            background: linear-gradient(135deg, #0f172a 0%, #0f766e 45%, #10b981 100%);
            color: #ffffff;
            padding: 2.1rem;
            border-radius: 24px;
            box-shadow: 0 22px 40px rgba(15, 23, 42, 0.15);
            margin-bottom: 2rem;
        }

        main.container-fluid .admin-sidebar {
            width: 100% !important;
            min-height: calc(100vh - 3rem) !important;
        }

        .admin-hero__eyebrow {
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: rgba(255, 255, 255, 0.82);
            margin-bottom: 0.5rem;
        }

        .admin-hero h1 {
            color: #ffffff;
            font-size: 2rem;
        }

        .admin-hero p {
            color: rgba(255, 255, 255, 0.88);
        }

        .admin-hero__card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(8px);
            color: white;
            padding: 1.5rem;
            border-radius: 18px;
            text-align: center;
        }

        .admin-hero__card h3 {
            color: white;
            margin-bottom: 0.25rem;
        }

        /* Stats Cards */
        .admin-stat-card {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid #dbe3ef;
            border-radius: 20px;
            padding: 1.3rem 1.25rem;
            text-align: left;
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
        }

        .admin-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #10b981, #3b82f6);
        }

        .admin-stat-card:hover {
            box-shadow: 0 20px 35px rgba(15, 23, 42, 0.12);
            transform: translateY(-3px);
            border-color: #93c5fd;
        }

        .admin-stat-card__label {
            font-size: 0.82rem;
            color: #6b7280;
            font-weight: 700;
            margin-bottom: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .admin-stat-card h2 {
            font-size: 1.85rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 0;
        }

        .admin-stat-card--warning {
            border-top-color: #f59e0b;
        }

        .admin-stat-card--warning::before {
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }

        .admin-stat-card--danger {
            border-top-color: #ef4444;
        }

        .admin-stat-card--danger::before {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }

        .admin-stat-card--info {
            border-top-color: #3b82f6;
        }

        .admin-stat-card--info::before {
            background: linear-gradient(90deg, #3b82f6, #2563eb);
        }

        /* Dashboard Cards */
        .dashboard-card {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 22px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.08);
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }

        .dashboard-card:hover {
            box-shadow: 0 22px 38px rgba(15, 23, 42, 0.12);
            transform: translateY(-1px);
        }

        .dashboard-card__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 0.9rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .dashboard-card__title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        .dashboard-card__action {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .dashboard-card__action:hover {
            color: var(--primary-dark);
        }

        /* Table Styles */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background-color: var(--light-bg);
            border-bottom: 2px solid var(--border-color);
        }

        .table thead th {
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
        }

        .table tbody td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: #1f2937;
        }

        .table tbody tr:hover {
            background-color: var(--light-bg);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Product Image */
        .product-thumbnail {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 6px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 0.5rem;
        }

        /* Badge Styles */
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
        }

        /* Charts */
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1rem;
        }

        .dashboard-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: rgba(16, 185, 129, 0.12);
            color: #047857;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .dashboard-shell {
                display: block !important;
            }

            .dashboard-shell > .col-lg-3,
            .dashboard-shell > .col-lg-9,
            .dashboard-shell > .admin-sidebar-column,
            .dashboard-shell > .dashboard-main-column {
                position: static !important;
                width: 100% !important;
                max-width: 100% !important;
                margin-left: 0 !important;
                flex: none !important;
            }
        }

        @media (max-width: 768px) {
            .admin-hero {
                padding: 1rem;
            }

            .admin-hero h1 {
                font-size: 1.5rem;
            }

            .dashboard-card {
                padding: 1.5rem;
            }

            .admin-stat-card {
                padding: 1rem;
            }

            .admin-stat-card h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<main class="container-fluid py-5">
    <div class="row dashboard-shell align-items-start" style="display:block; position:relative;">

        <?php require_once("../includes/admin_sidebar.php"); ?>

        <!-- Main Admin Content -->
        <div class="col-lg-9" style="margin-left: 320px; width: calc(100% - 320px); max-width: calc(100% - 320px);">
            
            <!-- Hero Section -->
            <div class="admin-hero">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-8">
                        <span class="dashboard-pill"><i class="bi bi-speedometer2"></i> Dashboard Overview</span>
                        <h1 class="fw-bold mb-2 mt-3">Welcome back, <?= htmlspecialchars($_SESSION['name'] ?? 'Administrator') ?>!</h1>
                        <p class="mb-0 text-muted">Monitor user activity, farmer approvals, orders, and marketplace performance.</p>
                    </div>
                    <div class="col-lg-4">
                        <div class="admin-hero__card">
                            <p class="small text-uppercase mb-2" style="opacity: 0.9;">Today</p>
                            <h3 class="fw-bold mb-1"><?= date('M d, Y') ?></h3>
                            <p class="mb-0" style="opacity: 0.9;"><?= date('l') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="row g-4 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="admin-stat-card">
                        <p class="admin-stat-card__label">Total Users</p>
                        <h2 class="mb-0"><?= $total_users ?></h2>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="admin-stat-card">
                        <p class="admin-stat-card__label">Active Farmers</p>
                        <h2 class="mb-0"><?= $total_approved_farmers ?></h2>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="admin-stat-card">
                        <p class="admin-stat-card__label">Total Products</p>
                        <h2 class="mb-0"><?= $total_products ?></h2>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="admin-stat-card admin-stat-card--warning">
                        <p class="admin-stat-card__label">Pending Orders</p>
                        <h2 class="mb-0"><?= $pending_orders ?></h2>
                    </div>
                </div>
            </div>

            <!-- Revenue Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="admin-stat-card admin-stat-card--info">
                        <p class="admin-stat-card__label">Total Revenue</p>
                        <h2 class="mb-0"><?= formatCurrency($total_revenue) ?></h2>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="admin-stat-card">
                        <p class="admin-stat-card__label">Today's Revenue</p>
                        <h2 class="mb-0"><?= formatCurrency($today_revenue) ?></h2>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="dashboard-card">
                        <div class="dashboard-card__header">
                            <h3 class="dashboard-card__title">Monthly Sales Overview</h3>
                        </div>
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="dashboard-card">
                        <div class="dashboard-card__header">
                            <h3 class="dashboard-card__title">Key Metrics</h3>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Total Orders</span>
                                <strong><?= $total_orders ?></strong>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" style="width: 100%; background: var(--primary-color);"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Pending Approvals</span>
                                <strong><?= $pending_farmers ?></strong>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" style="width: <?= $pending_farmers > 0 ? min(100, $pending_farmers * 10) : 0 ?>%; background: #f59e0b;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="dashboard-card">
                <div class="dashboard-card__header">
                    <h3 class="dashboard-card__title">Recent Orders</h3>
                    <a href="<?= BASE_URL ?>admin/all_orders.php" class="dashboard-card__action">
                        View All <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                                <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?= $order['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($order['user_name']) ?></td>
                                    <td><?= formatCurrency($order['total_amount']) ?></td>
                                    <td><?= getStatusBadge($order['status']) ?></td>
                                    <td><?= getStatusBadge($order['payment_status']) ?></td>
                                    <td><?= formatDate($order['created_at']) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>admin/view_order.php?id=<?= $order['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        No orders found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Products -->
            <div class="dashboard-card">
                <div class="dashboard-card__header">
                    <h3 class="dashboard-card__title">Top Selling Products</h3>
                    <a href="<?= BASE_URL ?>admin/products.php" class="dashboard-card__action">
                        View All <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="row g-3">
                    <?php if ($top_products && $top_products->num_rows > 0): ?>
                        <?php while ($product = $top_products->fetch_assoc()): ?>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 border rounded-2">
                                <img src="<?= BASE_URL ?>uploads/products/<?= !empty($product['image']) ? $product['image'] : 'default.jpg' ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>" class="product-thumbnail">
                                <div class="ms-3 flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars(substr($product['name'], 0, 25)) ?></h6>
                                    <small class="text-muted">Sold: <?= $product['total_sold'] ?> units</small>
                                </div>
                                <strong class="text-primary"><?= formatCurrency($product['revenue']) ?></strong>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12 text-center text-muted py-4">
                            <i class="bi bi-box-seam fs-3 d-block mb-2"></i>
                            No products found
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CTA Card -->
            <div class="dashboard-card text-center">
                <i class="bi bi-gear-fill fs-1 text-primary mb-3 d-block" style="color: var(--primary-color);"></i>
                <h4 class="fw-bold mb-2">Manage Your Marketplace</h4>
                <p class="text-muted mb-4">Access all admin tools from the sidebar to manage users, farmers, orders, and products.</p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="<?= BASE_URL ?>admin/manage_farmers.php" class="btn btn-primary" style="background: var(--primary-color); border: none;">
                        <i class="bi bi-person-check"></i> Approve Farmers
                    </a>
                    <a href="<?= BASE_URL ?>admin/all_orders.php" class="btn btn-outline-primary">
                        <i class="bi bi-bag-check"></i> Review Orders
                    </a>
                </div>
            </div>

        </div>
    </div>
</main>

<script>
    // Sales Chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Monthly Sales',
                data: <?= json_encode(array_values($chart_data)) ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#10b981',
                pointBorderColor: 'white',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
</script>

<?php require_once("../includes/footer.php"); ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>