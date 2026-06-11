<?php
session_start();
require_once("../../includes/config.php");
require_once("../../includes/auth.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "user") {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = intval($_GET['id'] ?? $_GET['order_id'] ?? 0);
$user_name = $_SESSION['name'] ?? 'User';
$first_name = explode(' ', $user_name)[0];

if ($order_id <= 0) {
    header("Location: orders.php");
    exit();
}

/* ===== ORDER ===== */
$stmt = $conn->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: orders.php");
    exit();
}

/* ===== ITEMS ===== */
$stmt = $conn->prepare("
SELECT oi.quantity, oi.price, p.name, p.image, p.unit
FROM order_items oi
JOIN products p ON oi.product_id = p.id
WHERE oi.order_id=?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ===== TOTAL ===== */
$subtotal = 0;
foreach($items as $i){
    $subtotal += $i['price'] * $i['quantity'];
}

$tax = $subtotal * 0.05; // Assuming 5% tax from cart logic
$shipping = $order['total_amount'] - $subtotal - $tax;
if($shipping < 0) $shipping = 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmMart | Track Order</title>
    
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

    /* === GRID LAYOUT === */
    .grid-2-1 { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;}

    /* === TIMELINE === */
    .timeline-wrap {
        padding: 40px 20px 20px;
    }
    .timeline {
        display: flex;
        justify-content: space-between;
        position: relative;
    }
    .timeline::before {
        content: '';
        position: absolute;
        top: 25px;
        left: 0;
        right: 0;
        height: 4px;
        background: #f3f4f6;
        z-index: 1;
        border-radius: 2px;
    }
    .timeline-step {
        text-align: center;
        position: relative;
        z-index: 2;
        flex: 1;
    }
    .step-icon {
        width: 54px;
        height: 54px;
        background: #fff;
        border: 4px solid #f3f4f6;
        border-radius: 50%;
        margin: 0 auto 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #d1d5db;
        transition: var(--transition);
        box-shadow: 0 0 0 4px #fff;
    }
    .timeline-step.active .step-icon {
        border-color: var(--theme-primary);
        background: #ecfdf5;
        color: var(--theme-primary);
    }
    .timeline-step.done .step-icon {
        border-color: var(--theme-primary);
        background: var(--theme-primary);
        color: #fff;
    }
    
    .timeline-step.done::after {
        content: '';
        position: absolute;
        top: 25px;
        left: 50%;
        width: 100%;
        height: 4px;
        background: var(--theme-primary);
        z-index: -1;
    }
    .timeline-step:last-child::after {
        display: none;
    }
    
    .step-text {
        font-family: var(--font-heading);
        font-weight: 600;
        color: var(--theme-text-dark);
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .timeline-step:not(.active):not(.done) .step-text { color: var(--theme-text-muted); }

    /* === ORDER DETAILS === */
    .order-details-box {
        display: flex; justify-content: space-between;
        background: #f9fafb; border-radius: 12px;
        padding: 20px; border: 1px solid var(--theme-border);
        margin-bottom: 30px;
    }
    .detail-item { flex: 1; padding: 0 20px; border-right: 1px dashed #d1d5db; }
    .detail-item:first-child { padding-left: 0; }
    .detail-item:last-child { border-right: none; padding-right: 0;}
    .detail-label { font-size: 12px; color: var(--theme-text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 5px; letter-spacing: 0.5px;}
    .detail-value { font-size: 16px; color: var(--theme-text-dark); font-weight: 700; font-family: var(--font-heading); }

    /* === CART ITEMS === */
    .cart-items-list { display: flex; flex-direction: column; gap: 15px; }

    .cart-item {
        border: 1px solid var(--theme-border); border-radius: 12px;
        padding: 16px; transition: var(--transition);
        display: flex; gap: 20px; align-items: center; background: #fff;
    }
    .cart-item:hover {
        border-color: #d1d5db; box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }

    .cart-item-image {
        width: 70px; height: 70px; border-radius: 10px;
        flex-shrink: 0; overflow: hidden; border: 1px solid #f3f4f6;
    }
    .cart-item-image img { width: 100%; height: 100%; object-fit: cover; }

    .cart-item-info { flex: 1; min-width: 0; }
    .cart-item-name { font-size: 15px; font-weight: 600; color: var(--theme-text-dark); margin-bottom: 2px; }
    .cart-item-qty { font-size: 13px; color: var(--theme-text-muted); }

    .cart-item-price { text-align: right; min-width: 100px; font-weight: 700; color: var(--theme-text-dark); font-size: 16px;}

    /* === SUMMARY PANEL === */
    .cart-summary { background: #fff; border: 2px solid var(--theme-border); }

    .summary-table { width: 100%; margin-bottom: 20px; }
    .summary-table th, .summary-table td { padding: 12px 0; border-bottom: 1px solid #f3f4f6; }
    .summary-table th { text-align: left; color: var(--theme-text-muted); font-weight: 500; }
    .summary-table td { text-align: right; color: var(--theme-text-dark); font-weight: 600; }

    .summary-table .total th { color: var(--theme-text-dark); font-size: 16px; font-weight: 700; border-bottom: none; padding-top: 16px;}
    .summary-table .total td { color: var(--theme-primary); font-size: 22px; font-weight: 800; border-bottom: none; padding-top: 16px;}

    .info-box { margin-top: 30px; background: #f9fafb; padding: 20px; border-radius: 12px; border: 1px solid var(--theme-border); }
    .info-box h6 { font-size: 13px; text-transform: uppercase; margin-bottom: 12px; font-weight: 700; letter-spacing: 0.5px; color: var(--theme-text-dark);}
    .info-box p { font-size: 14px; line-height: 1.6; color: var(--theme-text-muted); margin: 0; }

    .status-alert {
        background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;
        padding: 16px; border-radius: 12px; text-align: center; font-weight: 600;
        display: flex; align-items: center; justify-content: center; gap: 10px;
    }

    /* === RESPONSIVE === */
    @media (max-width: 1200px) {
        .grid-2-1 { grid-template-columns: 1fr; }
    }
    @media (max-width: 992px) {
        .main-wrapper { margin-left: 0; }
        .sidebar-wrapper { position: absolute; left: -260px; }
        .main-content { padding: 20px; }
        .top-header { padding: 0 20px; }
        .order-details-box { flex-direction: column; gap: 15px; }
        .detail-item { padding: 0; border-right: none; border-bottom: 1px dashed #d1d5db; padding-bottom: 15px;}
        .detail-item:last-child { border-bottom: none; padding-bottom: 0; }
    }
    @media (max-width: 768px) {
        .header-greeting { display: none; }
        .timeline { flex-direction: column; align-items: flex-start; gap: 20px; padding-left: 20px;}
        .timeline::before { left: 45px; top: 0; height: 100%; width: 4px;}
        .timeline-step { display: flex; align-items: center; gap: 20px; width: 100%; text-align: left; }
        .step-icon { margin: 0; }
        .timeline-step.done::after { left: 45px; top: 54px; height: calc(100% + 20px); width: 4px;}
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
                    Track Order <span>/ #ORD-<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
                </div>
            </div>
            <div class="header-right">
                <a href="orders.php" class="btn-outline">
                    <i class="bi bi-arrow-left"></i> Back To Orders
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">

            <div class="grid-2-1">
                <!-- Left Column -->
                <div>
                    <!-- Timeline Panel -->
                    <div class="panel">
                        <div class="panel-header">
                            <div class="panel-title"><i class="bi bi-geo-alt"></i> Order Tracking</div>
                        </div>

                        <div class="order-details-box">
                            <div class="detail-item">
                                <div class="detail-label">Order Reference</div>
                                <div class="detail-value">#ORD-<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Placement Date</div>
                                <div class="detail-value"><?= date('d M, Y', strtotime($order['created_at'])) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Expected Delivery</div>
                                <div class="detail-value text-success"><?= date('d M, Y', strtotime($order['created_at'] . ' + 3 days')) ?></div>
                            </div>
                        </div>

                        <div class="timeline-wrap">
                            <?php
                            $current_status = strtolower($order['status']);
                            if($current_status == 'cancelled'): ?>
                                <div class="status-alert">
                                    <i class="bi bi-x-octagon-fill fs-4"></i>
                                    <div>This order has been cancelled and will not be delivered.</div>
                                </div>
                            <?php else: ?>
                                <div class="timeline">
                                    <?php
                                    $steps=['pending' => 'bi-clipboard-check', 'processing' => 'bi-box-seam', 'shipped' => 'bi-truck', 'delivered' => 'bi-house-check'];
                                    $statuses = array_keys($steps);
                                    $current_index = array_search($current_status, $statuses);
                                    
                                    $i = 0;
                                    foreach($steps as $s => $icon):
                                        $status_class = '';
                                        if($i < $current_index) $status_class = 'done';
                                        elseif($i == $current_index) $status_class = 'active';
                                    ?>
                                    <div class="timeline-step <?= $status_class ?>">
                                        <div class="step-icon"><i class="bi <?= $icon ?>"></i></div>
                                        <div class="step-text"><?= ucfirst($s) ?></div>
                                    </div>
                                    <?php 
                                            $i++;
                                        endforeach; 
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Items Panel -->
                    <div class="panel">
                        <div class="panel-header">
                            <div class="panel-title"><i class="bi bi-bag"></i> Purchased Items</div>
                        </div>
                        <div class="cart-items-list">
                            <?php foreach($items as $it): ?>
                            <div class="cart-item">
                                <div class="cart-item-image">
                                    <img src="../../uploads/products/<?= $it['image'] ?>" onerror="this.src='https://via.placeholder.com/80?text=Product'">
                                </div>
                                <div class="cart-item-info">
                                    <div class="cart-item-name"><?= htmlspecialchars($it['name']) ?></div>
                                    <div class="cart-item-qty">Quantity: <?= $it['quantity'] ?> <?= $it['unit'] ?></div>
                                </div>
                                <div class="cart-item-price">
                                    ₹<?= number_format($it['price'] * $it['quantity'], 2) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <!-- Summary Panel -->
                    <div class="panel cart-summary">
                        <div class="panel-header" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                            <div class="panel-title" style="font-size: 18px;">Order Summary</div>
                        </div>
                        
                        <table class="summary-table">
                            <tbody>
                                <tr>
                                    <th>Subtotal</th>
                                    <td>₹<?= number_format($subtotal, 2) ?></td>
                                </tr>
                                <tr>
                                    <th>Tax (5%)</th>
                                    <td>₹<?= number_format($tax, 2) ?></td>
                                </tr>
                                <tr>
                                    <th>Shipping</th>
                                    <td>₹<?= number_format($shipping, 2) ?></td>
                                </tr>
                                <tr class="total">
                                    <th>Total Paid</th>
                                    <td>₹<?= number_format($order['total_amount'], 2) ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="info-box">
                            <h6><i class="bi bi-geo-alt me-1 text-success"></i> Delivery Address</h6>
                            <p><?= nl2br(htmlspecialchars($order['shipping_address'])) ?><br><?= htmlspecialchars($order['city']) ?> - <?= htmlspecialchars($order['zip_code']) ?></p>
                        </div>
                        
                        <div class="info-box">
                            <h6><i class="bi bi-credit-card me-1 text-success"></i> Payment Details</h6>
                            <p>Method: <span style="text-transform: capitalize; color: var(--theme-text-dark); font-weight: 600;"><?= str_replace('_', ' ', $order['payment_method']) ?></span><br>
                               Status: 
                               <?php if($order['payment_status'] == 'completed' || $order['payment_status'] == 'paid'): ?>
                                    <span style="color: #047857; font-weight: 600; text-transform: uppercase;">Paid</span>
                               <?php else: ?>
                                    <span style="color: #b45309; font-weight: 600; text-transform: uppercase;"><?= $order['payment_status'] ?></span>
                               <?php endif; ?>
                            </p>
                        </div>
                        
                    </div>
                </div>

            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>