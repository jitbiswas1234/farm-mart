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

// Handle quantity update via AJAX
if (isset($_POST['action']) && $_POST['action'] === 'update_quantity') {
    $cart_id = intval($_POST['cart_id']);
    $quantity = floatval($_POST['quantity']); // updated to floatval to support weights
    
    if ($quantity > 0) {
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("dii", $quantity, $cart_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    exit;
}

// Handle item removal
if (isset($_POST['action']) && $_POST['action'] === 'remove_item') {
    $cart_id = intval($_POST['cart_id']);
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $stmt->close();
    exit;
}

// Fetch cart items
$cart_items = [];
$stmt_cart = $conn->prepare("
    SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.unit, p.image, p.stock, p.is_organic
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ? AND p.status = 'active'
    ORDER BY c.created_at DESC
");
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$cart_items = $stmt_cart->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_cart->close();

// Handle Coupon Application
$coupon_discount = 0;
$coupon_code = '';
$coupon_msg = '';
$coupon_type = '';

if (isset($_POST['apply_coupon'])) {
    $code = strtoupper(trim($_POST['coupon_code']));
    $_SESSION['applied_coupon'] = $code;
}

if (isset($_POST['remove_coupon'])) {
    unset($_SESSION['applied_coupon']);
}

// Calculate totals
$subtotal = 0;
$organic_subtotal = 0;

foreach ($cart_items as $item) {
    $item_total = $item['price'] * $item['quantity'];
    $subtotal += $item_total;
    if ($item['is_organic']) {
        $organic_subtotal += $item_total;
    }
}

// Process Active Coupon
if (isset($_SESSION['applied_coupon']) && $subtotal > 0) {
    $code = $_SESSION['applied_coupon'];
    
    if ($code === 'ORGANIC10' && $organic_subtotal > 0) {
        $coupon_discount = $organic_subtotal * 0.10;
        $coupon_code = $code;
        $coupon_msg = "10% off applied to Organic products!";
        $coupon_type = "success";
    } 
    elseif ($code === 'FARM100' && $subtotal >= 500) {
        // Simple logic for first order simulation (requires > 500 cart value)
        $coupon_discount = 100;
        $coupon_code = $code;
        $coupon_msg = "₹100 First Order Discount Applied!";
        $coupon_type = "success";
    }
    elseif ($code === 'SUMMER15') {
        $coupon_discount = $subtotal * 0.15;
        $coupon_code = $code;
        $coupon_msg = "15% Summer Fresh Sale Applied!";
        $coupon_type = "success";
    }
    else {
        $coupon_msg = "Invalid coupon code or criteria not met.";
        $coupon_type = "danger";
        unset($_SESSION['applied_coupon']);
    }
}

// Shipping Logic (Free shipping over 499)
$shipping_cost = 0;
$shipping_msg = "";
if (count($cart_items) > 0) {
    if ($subtotal >= 499) {
        $shipping_cost = 0;
        $shipping_msg = "You unlocked Free Delivery!";
    } else {
        $shipping_cost = 50;
        $short = 499 - $subtotal;
        $shipping_msg = "Add ₹" . number_format($short, 2) . " more for Free Delivery!";
    }
}

$tax_rate = 0.05; // 5% tax
$tax = ($subtotal - $coupon_discount) * $tax_rate;
$total = ($subtotal - $coupon_discount) + $tax + $shipping_cost;

// Store final total in session for checkout page
$_SESSION['checkout_discount'] = $coupon_discount;

// Get pending orders count for sidebar
$stmt_pending = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status IN ('pending', 'processing')");
$stmt_pending->bind_param("i", $user_id);
$stmt_pending->execute();
$orders_count = $stmt_pending->get_result()->fetch_assoc()['count'];
$stmt_pending->close();

$cart_count = count($cart_items);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmMart | Shopping Cart</title>
    
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
        --theme-shadow-lg: 0 15px 35px rgba(0,0,0,0.05);
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

    /* === OFFERS BANNER === */
    .shipping-banner {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
        padding: 12px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
    }
    
    .offers-scroll {
        display: flex;
        gap: 15px;
        overflow-x: auto;
        padding-bottom: 10px;
        margin-bottom: 24px;
        scrollbar-width: none; /* Firefox */
    }
    .offers-scroll::-webkit-scrollbar { display: none; } /* Chrome */
    
    .offer-card {
        min-width: 250px;
        background: #fff;
        border: 1px dashed var(--theme-primary);
        border-radius: 12px;
        padding: 15px;
        position: relative;
        overflow: hidden;
    }
    .offer-card::before {
        content: ''; position: absolute; left: -5px; top: 50%; transform: translateY(-50%);
        width: 10px; height: 10px; border-radius: 50%; background: var(--theme-bg);
        border-right: 1px dashed var(--theme-primary);
    }
    .offer-card::after {
        content: ''; position: absolute; right: -5px; top: 50%; transform: translateY(-50%);
        width: 10px; height: 10px; border-radius: 50%; background: var(--theme-bg);
        border-left: 1px dashed var(--theme-primary);
    }
    .offer-icon { font-size: 20px; margin-bottom: 5px; display: block; }
    .offer-title { font-size: 13px; font-weight: 700; color: var(--theme-text-dark); margin-bottom: 2px;}
    .offer-desc { font-size: 11px; color: var(--theme-text-muted); margin-bottom: 8px;}
    .offer-code { 
        background: #f3f4f6; padding: 4px 8px; border-radius: 6px; 
        font-family: monospace; font-weight: 700; color: var(--theme-primary); 
        font-size: 12px; display: inline-block; cursor: pointer;
    }

    /* === GRID LAYOUT === */
    .grid-2-1 { display: grid; grid-template-columns: 2.2fr 1fr; gap: 24px; align-items: start;}

    /* === PANELS === */
    .panel {
        background: var(--theme-surface);
        border-radius: 20px;
        padding: 24px;
        box-shadow: var(--theme-shadow);
        border: 1px solid rgba(0,0,0,0.02);
        margin-bottom: 24px;
    }

    .panel-header {
        margin-bottom: 24px;
        border-bottom: 1px solid #f3f4f6;
        padding-bottom: 16px;
    }
    .panel-title { 
        font-size: 18px; font-weight: 700; color: var(--theme-text-dark); 
        display: flex; align-items: center; gap: 10px;
    }
    .panel-title i { color: var(--theme-primary); font-size: 1.2rem; background: rgba(4, 120, 87, 0.1); padding: 6px; border-radius: 8px; }

    /* === TABLE === */
    .table-responsive { overflow-x: auto; }
    .data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .data-table th {
        font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
        color: var(--theme-text-muted); padding: 12px 16px; text-align: left; 
        border-bottom: 1px solid var(--theme-border);
    }
    .data-table td {
        padding: 20px 16px; font-size: 14.5px; color: var(--theme-text-dark); font-weight: 500;
        border-bottom: 1px solid #f3f4f6; vertical-align: middle;
    }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table tbody tr:hover td { background: #f9fafb; }

    .product-col { display: flex; align-items: center; gap: 16px; }
    .product-img { width: 70px; height: 70px; border-radius: 12px; object-fit: cover; border: 1px solid #f3f4f6; }
    .product-name { font-size: 15px; font-weight: 600; color: var(--theme-text-dark); display: flex; align-items: center; gap: 8px;}
    .product-unit { display: block; font-size: 12px; color: var(--theme-text-muted); font-weight: 400; margin-top: 4px; }
    
    .organic-badge { font-size: 10px; background: #ecfdf5; color: #047857; padding: 2px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase; border: 1px solid #a7f3d0;}

    .product-price { font-weight: 600; color: var(--theme-text-muted); }
    .product-subtotal { font-weight: 700; color: var(--theme-text-dark); font-size: 16px; }

    /* Quantity Input */
    .quantity-wrap {
        display: inline-flex;
        align-items: center;
        background: #f3f4f6;
        border-radius: 10px;
        padding: 4px;
    }
    .quantity-wrap button {
        width: 28px; height: 28px; border-radius: 8px;
        border: none; background: #fff; color: var(--theme-text-dark);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .quantity-wrap button:hover { background: #e5e7eb; }
    .quantity-wrap input {
        width: 45px; border: none; background: transparent;
        text-align: center; font-weight: 700; font-size: 14px;
        color: var(--theme-text-dark); pointer-events: none;
    }

    .btn-remove {
        background: rgba(239, 68, 68, 0.1); border: none; color: #ef4444;
        width: 35px; height: 35px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: var(--transition);
    }
    .btn-remove:hover { background: #ef4444; color: #fff; transform: scale(1.05); }

    /* === CART SUMMARY === */
    .cart-summary { background: #fff; border: 2px solid var(--theme-border); }

    .summary-table { width: 100%; margin-bottom: 24px; }
    .summary-table th, .summary-table td { padding: 12px 0; border-bottom: 1px solid #f3f4f6; }
    .summary-table th { text-align: left; color: var(--theme-text-muted); font-weight: 500; }
    .summary-table td { text-align: right; color: var(--theme-text-dark); font-weight: 600; }

    .summary-table .discount-row th, .summary-table .discount-row td { color: var(--theme-primary); }

    .summary-table .total th { color: var(--theme-text-dark); font-size: 16px; font-weight: 700; border-bottom: none; padding-top: 16px;}
    .summary-table .total td { color: var(--theme-primary); font-size: 22px; font-weight: 800; border-bottom: none; padding-top: 16px;}

    /* Coupon Form */
    .coupon-box { margin-bottom: 24px; }
    .coupon-input-group { display: flex; gap: 8px; }
    .coupon-input-group input {
        flex: 1; border: 1px solid var(--theme-border); padding: 10px 15px;
        border-radius: 8px; font-size: 13px; text-transform: uppercase;
        outline: none; transition: 0.3s;
    }
    .coupon-input-group input:focus { border-color: var(--theme-primary); }
    .coupon-input-group button {
        background: var(--theme-text-dark); color: #fff; border: none;
        padding: 0 15px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer;
    }

    .applied-coupon {
        background: #ecfdf5; border: 1px dashed var(--theme-primary);
        padding: 10px 15px; border-radius: 8px; display: flex; justify-content: space-between;
        align-items: center; margin-bottom: 24px;
    }

    /* === EMPTY STATE === */
    .empty-state { text-align: center; padding: 60px 20px; }
    .empty-state i { font-size: 60px; display: block; margin-bottom: 20px; color: #e5e7eb; }
    .empty-state h3 { color: var(--theme-text-dark); margin-bottom: 10px; font-size: 22px; }

    /* === RESPONSIVE === */
    @media (max-width: 1200px) {
        .grid-2-1 { grid-template-columns: 1fr; }
    }
    @media (max-width: 992px) {
        .main-wrapper { margin-left: 0; }
        .sidebar-wrapper { position: absolute; left: -260px; }
        .main-content { padding: 20px; }
        .top-header { padding: 0 20px; }
    }
    @media (max-width: 768px) {
        .header-greeting { display: none; }
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
                    Cart <span>/ Checkout</span>
                </div>
            </div>
            <div class="header-right">
                <a href="../../products.php" class="btn-premium" style="background: transparent; color: var(--theme-text-dark); box-shadow: none; border: 1px solid var(--theme-border);">
                    <i class="bi bi-arrow-left"></i> Continue Shopping
                </a>
                <a href="notifications.php" class="header-icon" title="Notifications">
                    <i class="bi bi-bell"></i>
                    <?php if ($notif_count > 0): ?><span class="dot"></span><?php endif; ?>
                </a>
            </div>
        </header>

        <!-- Cart Content -->
        <main class="main-content">

            <?php if (!empty($cart_items)): ?>

                <!-- Shipping Banner -->
                <div class="shipping-banner">
                    <i class="bi bi-truck fs-4"></i>
                    <div>
                        <?= $shipping_msg ?>
                        <?php if($shipping_cost > 0): ?>
                            <div style="width: 100%; height: 4px; background: rgba(4, 120, 87, 0.2); border-radius: 2px; margin-top: 5px;">
                                <div style="width: <?= min(100, ($subtotal/499)*100) ?>%; height: 100%; background: var(--theme-primary); border-radius: 2px;"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Offers Horizontal Scroll -->
                <div class="offers-scroll">
                    <div class="offer-card">
                        <span class="offer-icon">🌱</span>
                        <div class="offer-title">10% OFF Organic</div>
                        <div class="offer-desc">Valid on all organic products.</div>
                        <div class="offer-code" onclick="copyAndApply('ORGANIC10')">ORGANIC10</div>
                    </div>
                    <div class="offer-card">
                        <span class="offer-icon">🎉</span>
                        <div class="offer-title">First Order Offer</div>
                        <div class="offer-desc">Get ₹100 OFF on orders above ₹500.</div>
                        <div class="offer-code" onclick="copyAndApply('FARM100')">FARM100</div>
                    </div>
                    <div class="offer-card">
                        <span class="offer-icon">🥭</span>
                        <div class="offer-title">Summer Fresh Sale</div>
                        <div class="offer-desc">Flat 15% OFF on entire cart.</div>
                        <div class="offer-code" onclick="copyAndApply('SUMMER15')">SUMMER15</div>
                    </div>
                </div>
                
                <div class="grid-2-1">
                    
                    <!-- Cart Items Table -->
                    <div class="panel" style="padding: 0; overflow: hidden;">
                        <div class="panel-header" style="padding: 24px 24px 0; border-bottom: none; margin-bottom: 0;">
                            <div class="panel-title"><i class="bi bi-basket3"></i> Shopping Cart (<?= $cart_count ?>)</div>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="padding-left: 24px;">Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                        <th style="padding-right: 24px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                    <tr>
                                        <td style="padding-left: 24px;">
                                            <div class="product-col">
                                                <img src="../../uploads/products/<?= htmlspecialchars($item['image'] ?? 'default.jpg') ?>" 
                                                     alt="<?= htmlspecialchars($item['name']) ?>" class="product-img"
                                                     onerror="this.src='https://via.placeholder.com/80?text=FM'">
                                                <div>
                                                    <span class="product-name">
                                                        <?= htmlspecialchars($item['name']) ?>
                                                        <?php if($item['is_organic']): ?>
                                                            <span class="organic-badge">Organic</span>
                                                        <?php endif; ?>
                                                    </span>
                                                    <span class="product-unit">Sold in <?= htmlspecialchars($item['unit']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="product-price">₹<?= number_format($item['price'], 2) ?></td>
                                        <td>
                                            <div class="quantity-wrap">
                                                <!-- Step defaults to 0.25 to support kg inputs -->
                                                <?php $step = ($item['unit'] == 'g' || $item['unit'] == 'piece') ? 1 : 0.25; ?>
                                                <button type="button" onclick="updateQtyFrontend(<?= $item['id'] ?>, -<?= $step ?>, <?= $item['stock'] ?>)">
                                                    <i class="bi bi-dash"></i>
                                                </button>
                                                <input type="text" value="<?= $item['quantity'] ?>" data-cart-id="<?= $item['id'] ?>" readonly>
                                                <button type="button" onclick="updateQtyFrontend(<?= $item['id'] ?>, <?= $step ?>, <?= $item['stock'] ?>)">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="product-subtotal">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                        <td style="padding-right: 24px;">
                                            <button class="btn-remove" onclick="removeItem(<?= $item['id'] ?>)" title="Remove Item">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div>
                        
                        <!-- Coupon Form -->
                        <div class="panel" style="padding: 20px;">
                            <?php if ($coupon_msg != '' && $coupon_type == 'danger'): ?>
                                <div class="alert alert-danger" style="font-size: 13px; padding: 10px; border-radius: 8px; margin-bottom: 15px;"><?= $coupon_msg ?></div>
                            <?php endif; ?>

                            <?php if(isset($_SESSION['applied_coupon']) && $coupon_discount > 0): ?>
                                <div class="applied-coupon">
                                    <div>
                                        <div style="font-weight: 700; color: var(--theme-primary); font-size: 14px;"><i class="bi bi-check-circle-fill me-1"></i> <?= $coupon_code ?> Applied</div>
                                        <div style="font-size: 11px; color: var(--theme-text-muted);"><?= $coupon_msg ?></div>
                                    </div>
                                    <form method="POST" style="margin:0;">
                                        <button type="submit" name="remove_coupon" class="btn-close" style="font-size: 12px;"></button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <form method="POST" class="coupon-box" style="margin: 0;">
                                    <div class="coupon-input-group">
                                        <input type="text" name="coupon_code" id="couponInput" placeholder="Enter Coupon Code">
                                        <button type="submit" name="apply_coupon">APPLY</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>

                        <!-- Summary Card -->
                        <div class="panel cart-summary">
                            <div class="panel-header" style="border-bottom: none; margin-bottom: 0; padding-bottom:0;">
                                <div class="panel-title" style="font-size: 20px;">Order Summary</div>
                            </div>

                            <table class="summary-table">
                                <tbody>
                                    <tr>
                                        <th>Subtotal</th>
                                        <td>₹<?= number_format($subtotal, 2) ?></td>
                                    </tr>
                                    
                                    <?php if($coupon_discount > 0): ?>
                                    <tr class="discount-row">
                                        <th>Discount (<?= $coupon_code ?>)</th>
                                        <td>-₹<?= number_format($coupon_discount, 2) ?></td>
                                    </tr>
                                    <?php endif; ?>

                                    <tr>
                                        <th>Tax (5%)</th>
                                        <td>₹<?= number_format($tax, 2) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Shipping</th>
                                        <td><?= $shipping_cost == 0 ? '<span class="text-success fw-bold">FREE</span>' : '₹'.number_format($shipping_cost, 2) ?></td>
                                    </tr>
                                    <tr class="total">
                                        <th>Total Payment</th>
                                        <td>₹<?= number_format($total, 2) ?></td>
                                    </tr>
                                </tbody>
                            </table>

                            <a href="checkout.php" class="btn-premium" style="width: 100%; justify-content: center; font-size: 15px; padding: 14px; border-radius: 12px;">
                                Proceed To Checkout <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                </div>

            <?php else: ?>
                <!-- Empty Cart -->
                <div class="panel">
                    <div class="empty-state">
                        <i class="bi bi-basket text-muted" style="opacity: 0.2;"></i>
                        <h3>Your Basket is Empty</h3>
                        <p>Looks like you haven't added any fresh produce to your cart yet.</p>
                        <a href="../../products.php" class="btn-premium d-inline-flex px-5 py-3 mt-3">Browse Harvest <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<script>
// Coupon apply shortcut
function copyAndApply(code) {
    document.getElementById('couponInput').value = code;
    // Optional: auto submit
    // document.querySelector('button[name="apply_coupon"]').click();
}

function updateQtyFrontend(cartId, change, maxStock) {
    const input = document.querySelector(`input[data-cart-id="${cartId}"]`);
    let currentQty = parseFloat(input.value);
    
    let newQty = currentQty + change;
    
    // Minimum quantity is the step size (either 0.25 or 1)
    let minQty = Math.abs(change);
    
    if (newQty < minQty) {
        newQty = minQty;
    }
    
    if (newQty > maxStock) {
        alert("Maximum stock limit reached.");
        newQty = maxStock;
    }
    
    // Only fire request if value actually changed
    if (newQty !== currentQty) {
        updateQuantity(cartId, newQty);
    }
}

function updateQuantity(cartId, quantity) {
    fetch('cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=update_quantity&cart_id=' + cartId + '&quantity=' + quantity
    }).then(response => {
        location.reload();
    });
}

function removeItem(cartId) {
    if (confirm('Are you sure you want to remove this item from your basket?')) {
        fetch('cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=remove_item&cart_id=' + cartId
        }).then(response => {
            location.reload();
        });
    }
}
</script>

</body>
</html>