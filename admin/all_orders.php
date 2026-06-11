<?php

require_once("../includes/auth.php");

if($_SESSION['role']!="admin")
{
header("Location: ../login.php");
exit();

}
require_once("../includes/config.php");

// Protect the page: Ensure the user is logged in AND is an admin

$page_title = "All Orders - Admin - FarmMart";
require_once("../includes/header.php");
require_once("../includes/navbar.php");

$orders = [];
// Use basic try-catch/if check in case orders table doesn't exist yet
$res = $conn->query("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
if ($res) {
    $orders = $res->fetch_all(MYSQLI_ASSOC);
}

function getStatusBadge($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'pending': return '<span class="badge bg-warning text-dark">Pending</span>';
        case 'processing': return '<span class="badge bg-info text-dark">Processing</span>';
        case 'completed':
        case 'delivered': return '<span class="badge bg-success">Delivered</span>';
        case 'cancelled': return '<span class="badge bg-danger">Cancelled</span>';
        default: return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}
?>

<main class="container py-5">
    <div class="row g-5">
        
        <!-- Include Admin Sidebar -->
        <?php require_once("../includes/admin_sidebar.php"); ?>

        <!-- Main Content -->
        <div class="col-lg-9">
            <h3 class="fw-bold mb-4">All Orders</h3>
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3">Order ID</th>
                                    <th class="py-3">Customer</th>
                                    <th class="py-3">Date</th>
                                    <th class="py-3">Total</th>
                                    <th class="py-3">Status</th>
                                    <th class="pe-4 py-3 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($orders) > 0): ?>
                                    <?php foreach($orders as $o): ?>
                                        <tr>
                                            <td class="ps-4 fw-medium">#ORD-<?= htmlspecialchars($o['id']) ?></td>
                                            <td><?= htmlspecialchars($o['customer_name']) ?></td>
                                            <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                                            <td class="fw-bold text-primary-theme">₹<?= number_format($o['total_amount'] ?? 0, 2) ?></td>
                                            <td><?= getStatusBadge($o['status'] ?? 'pending') ?></td>
                                            <td class="pe-4 text-end">
                                                <a href="view_order.php?id=<?= htmlspecialchars($o['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-5 text-muted">No orders found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require_once("../includes/footer.php"); ?>