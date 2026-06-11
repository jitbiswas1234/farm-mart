<?php

require_once("../includes/auth.php");

if($_SESSION['role']!="admin")
{
header("Location: ../login.php");
exit();

}
require_once("../includes/config.php");

// Protect the page: Ensure the user is logged in AND is an admin

$page_title = "Analytics & Reports - Admin - FarmMart";
require_once("../includes/header.php");
?>
<link rel="stylesheet" href="assets/css/admin.css">
<?php
require_once("../includes/navbar.php");

// Fetch General Analytics
$total_revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed' OR status = 'delivered'")->fetch_assoc()['total'] ?? 0;
$total_orders = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'] ?? 0;
$total_products_sold = $conn->query("SELECT SUM(quantity) as total FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.status = 'completed' OR o.status = 'delivered'")->fetch_assoc()['total'] ?? 0;

// Fetch Top Farmers
$top_farmers = $conn->query("
    SELECT f.name, f.village, SUM(oi.price * oi.quantity) as revenue
    FROM farmers f
    JOIN products p ON f.id = p.farmer_id
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed' OR o.status = 'delivered'
    GROUP BY f.id
    ORDER BY revenue DESC
    LIMIT 5
");

// Fetch Top Products
$top_products = $conn->query("
    SELECT p.name, p.image, c.name as category, SUM(oi.quantity) as sold_qty, SUM(oi.price * oi.quantity) as revenue
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE o.status = 'completed' OR o.status = 'delivered'
    GROUP BY p.id
    ORDER BY sold_qty DESC
    LIMIT 5
");

?>

<main class="container-fluid py-4 px-md-5">
    <div class="row g-4">
        
        <!-- Include Admin Sidebar -->
        <div class="col-lg-3">
            <?php require_once("../includes/admin_sidebar.php"); ?>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="pagetitle mb-4">
              <h1>Analytics & Reports</h1>
              <nav>
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                  <li class="breadcrumb-item active">Reports</li>
                </ol>
              </nav>
            </div>

            <!-- Key Metrics -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card admin-card h-100" style="border-left: 4px solid var(--admin-primary); margin-bottom: 0;">
                        <div class="card-body pt-3">
                            <h5 class="card-title pb-1">Gross Revenue <span>| Lifetime</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="admin-card-icon" style="color: var(--admin-primary); background: var(--admin-primary-light);">
                                    <i class="bi bi-currency-rupee"></i>
                                </div>
                                <div class="ps-3">
                                    <h6 class="fw-bold">₹<?= number_format($total_revenue, 2) ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card admin-card h-100" style="border-left: 4px solid #10b981; margin-bottom: 0;">
                        <div class="card-body pt-3">
                            <h5 class="card-title pb-1">Total Orders <span>| All time</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="admin-card-icon" style="color: #10b981; background: #d1fae5;">
                                    <i class="bi bi-cart-check"></i>
                                </div>
                                <div class="ps-3">
                                    <h6 class="fw-bold"><?= number_format($total_orders) ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card admin-card h-100" style="border-left: 4px solid #f59e0b; margin-bottom: 0;">
                        <div class="card-body pt-3">
                            <h5 class="card-title pb-1">Items Sold <span>| Delivered</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="admin-card-icon" style="color: #f59e0b; background: #fef3c7;">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                                <div class="ps-3">
                                    <h6 class="fw-bold"><?= number_format($total_products_sold) ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts/Tables -->
            <div class="row g-4">
                <!-- Top Products -->
                <div class="col-xl-8">
                    <div class="card admin-table-card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Top Selling Products</h5>
                            <div class="table-responsive">
                                <table class="table admin-table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-4">Product</th>
                                            <th>Category</th>
                                            <th class="text-center">Units Sold</th>
                                            <th class="text-end pe-4">Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($top_products && $top_products->num_rows > 0): ?>
                                            <?php while($p = $top_products->fetch_assoc()): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?= !empty($p['image']) ? BASE_URL . 'uploads/products/' . htmlspecialchars($p['image']) : 'https://via.placeholder.com/30' ?>" 
                                                             style="width:36px;height:36px;object-fit:cover" class="rounded shadow-sm me-3">
                                                        <span class="fw-bold text-dark"><?= htmlspecialchars($p['name']) ?></span>
                                                    </div>
                                                </td>
                                                <td><span class="text-muted small"><?= htmlspecialchars($p['category'] ?? 'N/A') ?></span></td>
                                                <td class="text-center"><span class="badge bg-primary rounded-pill"><?= $p['sold_qty'] ?></span></td>
                                                <td class="pe-4 text-end text-success fw-bold">₹<?= number_format($p['revenue'], 2) ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center text-muted py-4">No sales data available yet.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Farmers -->
                <div class="col-xl-4">
                    <div class="card admin-table-card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Top Performing Farmers</h5>
                            <div class="list-group list-group-flush mt-3">
                                <?php if ($top_farmers && $top_farmers->num_rows > 0): ?>
                                    <?php while($f = $top_farmers->fetch_assoc()): ?>
                                    <div class="list-group-item px-0 py-3 border-bottom border-light d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar-circle m-0 me-3" style="width: 35px; height: 35px; font-size: 14px;">
                                                <?= strtoupper(substr($f['name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold" style="color: var(--admin-dark-blue); font-size: 14px;"><?= htmlspecialchars($f['name']) ?></h6>
                                                <small class="text-muted" style="font-size: 11px;"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($f['village'] ?? 'N/A') ?></small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-success" style="font-size: 14px;">₹<?= number_format($f['revenue'], 2) ?></div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted py-4">No farmer data available yet.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>
<?php require_once("../includes/footer.php"); ?>