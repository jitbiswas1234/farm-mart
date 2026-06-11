<?php

require_once("../includes/auth.php");

if($_SESSION['role']!="admin")
{
    header("Location: ../login.php");
    exit();
}

require_once("../includes/config.php");

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id === 0) {
    header("Location: all_orders.php");
    exit;
}

$order = null;
$order_items = [];

// Fetch main order details
$stmt = $conn->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $order = $result->fetch_assoc();
}
$stmt->close();

if (!$order) {
    $page_title = "Order Not Found - Admin - FarmMart";
    require_once("../includes/header.php");
    require_once("../includes/navbar.php");
    ?>
    <main class='container py-5'>
        <div class='alert alert-danger'>Order not found or you do not have permission to view it.</div>
        <a href='all_orders.php' class='btn btn-farmmart'>Back to All Orders</a>
    </main>
    <?php
    require_once("../includes/footer.php");
    exit;
}

// Fetch order items
$stmt_items = $conn->prepare("SELECT oi.*, p.name as product_name, p.image as product_image, f.name as farmer_name FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN farmers f ON oi.farmer_id = f.id WHERE oi.order_id = ?");
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
if ($result_items) {
    $order_items = $result_items->fetch_all(MYSQLI_ASSOC);
}
$stmt_items->close();

// Helper function for status badge (re-defined here for self-containment, or could be in a common utility file)
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

$page_title = "View Order #ORD-" . htmlspecialchars($order['id']) . " - Admin - FarmMart";
require_once("../includes/header.php");
require_once("../includes/navbar.php");
?>

<main class="container py-5">
    <div class="row g-5">
        <?php require_once("../includes/admin_sidebar.php"); ?>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Order Details #ORD-<?= htmlspecialchars($order['id']) ?></h3>
                <a href="all_orders.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to All Orders</a>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary-light fw-bold">Order Information</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Order ID:</strong> #ORD-<?= htmlspecialchars($order['id']) ?></p>
                            <p><strong>Order Date:</strong> <?= date('M d, Y H:i A', strtotime($order['created_at'])) ?></p>
                            <p><strong>Status:</strong> <?= getStatusBadge($order['status']) ?></p>
                            <p><strong>Total Amount:</strong> ₹<?= number_format($order['total_amount'], 2) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
                            <p><strong>Payment Status:</strong> <?= htmlspecialchars($order['payment_status']) ?></p>
                            <?php if (!empty($order['transaction_id'])): ?>
                                <p><strong>Transaction ID:</strong> <?= htmlspecialchars($order['transaction_id']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary-light fw-bold">Customer Information</div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone'] ?? 'N/A') ?></p>
                    <p><strong>Shipping Address:</strong> <?= htmlspecialchars($order['shipping_address']) ?>, <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['zip_code']) ?></p>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary-light fw-bold">Order Items</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3">Product</th>
                                    <th class="py-3">Farmer</th>
                                    <th class="py-3">Price</th>
                                    <th class="py-3">Quantity</th>
                                    <th class="pe-4 py-3 text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($order_items) > 0): ?>
                                    <?php foreach($order_items as $item): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <img src="<?= $item['product_image'] ? BASE_URL . 'uploads/products/' . htmlspecialchars($item['product_image']) : 'https://via.placeholder.com/40' ?>" 
                                                     alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                                     style="width:40px;height:40px;object-fit:cover" class="rounded me-2">
                                                <?= htmlspecialchars($item['product_name']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($item['farmer_name']) ?></td>
                                            <td>₹<?= number_format($item['price'], 2) ?></td>
                                            <td><?= htmlspecialchars($item['quantity']) ?></td>
                                            <td class="pe-4 text-end">₹<?= number_format($item['subtotal'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No items found for this order.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Action buttons (e.g., Change Status) -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary-light fw-bold">Order Actions</div>
                <div class="card-body">
                    <form action="update_order_status.php" method="POST">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                        <div class="row align-items-center">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="status_update" class="form-label fw-bold">Update Status:</label>
                                <select name="status" id="status_update" class="form-select">
                                    <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-farmmart w-100">Save Status</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</main>

<?php require_once("../includes/footer.php"); ?>