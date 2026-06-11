<?php
session_start();
require_once("../../includes/auth.php");

if($_SESSION['role']!="user")
{
    header("Location: ../../login.php");
    exit();
}

require_once("../../includes/config.php");

$user_id=$_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'User';

$order_id=isset($_GET['id']) ? intval($_GET['id']) : 0;

if($order_id==0)
{
    header("Location: orders.php");
    exit();
}

$stmt=$conn->prepare("
    SELECT o.*,
    u.name customer_name,
    u.email customer_email,
    u.phone customer_phone
    FROM orders o
    JOIN users u
    ON o.user_id=u.id
    WHERE o.id=? AND o.user_id=?
");

$stmt->bind_param("ii",$order_id,$user_id);
$stmt->execute();
$order=$stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$order)
{
    echo "Order not found";
    exit();
}

// ITEMS
$stmt=$conn->prepare("
    SELECT 
    oi.*,
    p.name product_name,
    p.image product_image,
    p.unit,
    f.name farmer_name
    FROM order_items oi
    JOIN products p
    ON oi.product_id=p.id
    JOIN farmers f
    ON p.farmer_id=f.id
    WHERE oi.order_id=?
");

$stmt->bind_param("i",$order_id);
$stmt->execute();
$order_items=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmMart | Order Details</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
    /* ModernAdmin Inspired Theme for View Order */
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
    .header-title { font-size: 18px; font-weight: 600; color: var(--theme-text-dark); font-family: var(--font-heading);}
    .header-title span { color: var(--theme-text-muted); font-weight: 500; font-family: var(--font-body); }

    .header-right { display: flex; align-items: center; gap: 15px; }

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

    .panel-title { 
        font-size: 16px; font-weight: 700; color: var(--theme-text-dark); 
        margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
        border-bottom: 1px solid #f3f4f6; padding-bottom: 16px;
    }
    .panel-title i { color: var(--theme-primary); font-size: 1.2rem; background: rgba(4, 120, 87, 0.1); padding: 6px; border-radius: 8px; }

    /* === ORDER HEADER === */
    .order-header {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;
    }

    .order-info-group { 
        background: #f9fafb; border: 1px solid var(--theme-border);
        padding: 20px; border-radius: 12px;
    }
    .order-info-label { font-size: 12px; color: var(--theme-text-muted); text-transform: uppercase; letter-spacing: 0.5px;
        margin-bottom: 8px; font-weight: 600;}
    .order-info-value { font-size: 16px; color: var(--theme-text-dark); font-weight: 700; font-family: var(--font-heading); }

    /* === STATUS BADGE === */
    .status-badge {
        display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px;
        border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
    
    .status-badge.delivered, .status-badge.paid { background: #ecfdf5; color: #047857; }
    .status-badge.delivered::before, .status-badge.paid::before { background: #047857; }
    
    .status-badge.pending, .status-badge.processing, .status-badge.unpaid { background: #fffbeb; color: #b45309; }
    .status-badge.pending::before, .status-badge.processing::before, .status-badge.unpaid::before { background: #b45309; }
    
    .status-badge.cancelled { background: #fef2f2; color: #b91c1c; }
    .status-badge.cancelled::before { background: #b91c1c; }

    /* === PRODUCT TABLE === */
    .table-responsive { overflow-x: auto; }
    .products-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .products-table th {
        font-family: var(--font-heading); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;
        color: var(--theme-text-muted); padding: 12px 16px; border-bottom: 1px solid var(--theme-border);
        background: #f9fafb; font-weight: 600; text-align: left;
    }
    .products-table td {
        padding: 16px; border-bottom: 1px solid #f3f4f6;
        vertical-align: middle; color: var(--theme-text-dark); font-size: 14.5px; font-weight: 500;
    }
    .products-table tbody tr:last-child td { border-bottom: none; }
    .products-table tbody tr:hover td { background: #f9fafb; }

    .product-cell { display: flex; align-items: center; gap: 16px; }
    .product-image { width: 60px; height: 60px; object-fit: cover; border-radius: 12px; border: 1px solid #f3f4f6; }
    .product-name { color: var(--theme-text-dark); font-weight: 600; margin-bottom: 2px;}
    .product-farmer { font-size: 12px; color: var(--theme-text-muted); }

    .price-cell { font-weight: 600; color: var(--theme-text-muted);}
    .qty-cell { font-weight: 600; }
    .total-cell { color: var(--theme-text-dark); font-weight: 700; font-size: 16px; }

    /* === GRID === */
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .info-row { display: flex; flex-direction: column; gap: 5px; }
    .info-label { font-size: 12px; color: var(--theme-text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-value { color: var(--theme-text-dark); font-size: 15px; font-weight: 500; }

    /* === RESPONSIVE === */
    @media (max-width: 992px) {
        .main-wrapper { margin-left: 0; }
        .sidebar-wrapper { position: absolute; left: -260px; }
        .main-content { padding: 20px; }
        .top-header { padding: 0 20px; }
        .order-header { grid-template-columns: 1fr; }
        .info-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .product-cell { flex-direction: column; align-items: flex-start; gap: 8px;}
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
                <div class="header-title">
                    Order Details <span>/ #ORD-<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
                </div>
            </div>
            <div class="header-right">
                <a href="orders.php" class="btn-outline">
                    <i class="bi bi-arrow-left"></i> Back to Orders
                </a>
            </div>
        </header>

        <!-- Order Content -->
        <main class="main-content">

            <!-- Order Status & Summary -->
            <div class="panel">
                <div class="order-header">
                    <div class="order-info-group">
                        <div class="order-info-label">Order Status</div>
                        <div class="status-badge <?= strtolower($order['status']) ?>">
                            <?= ucfirst($order['status']) ?>
                        </div>
                    </div>
                    <div class="order-info-group">
                        <div class="order-info-label">Order Date</div>
                        <div class="order-info-value"><?= date('M j, Y', strtotime($order['created_at'])) ?></div>
                    </div>
                    <div class="order-info-group">
                        <div class="order-info-label">Total Amount</div>
                        <div class="order-info-value" style="color: var(--theme-primary);">₹<?= number_format($order['total_amount'], 2) ?></div>
                    </div>
                    <div class="order-info-group">
                        <div class="order-info-label">Payment Method</div>
                        <div class="order-info-value" style="text-transform: capitalize;"><?= str_replace('_', ' ', $order['payment_method']) ?></div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div class="order-info-group">
                        <div class="order-info-label">Payment Status</div>
                        <div class="status-badge <?= strtolower($order['payment_status']) === 'completed' || strtolower($order['payment_status']) === 'paid' ? 'paid' : 'unpaid' ?>">
                            <?= ucfirst($order['payment_status']) ?>
                        </div>
                    </div>
                    <?php if($order['transaction_id']): ?>
                    <div class="order-info-group">
                        <div class="order-info-label">Transaction ID</div>
                        <div class="order-info-value" style="font-family: monospace; font-size: 14px;"><?= htmlspecialchars($order['transaction_id']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Shipping Details -->
            <div class="panel">
                <div class="panel-title">
                    <i class="bi bi-geo-alt"></i> Shipping Details
                </div>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?= htmlspecialchars($order['customer_name']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?= htmlspecialchars($order['customer_email']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value"><?= htmlspecialchars($order['customer_phone'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Zip Code</div>
                        <div class="info-value"><?= htmlspecialchars($order['zip_code']) ?></div>
                    </div>
                </div>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--theme-border);">
                    <div class="info-label" style="margin-bottom: 8px;">Full Address</div>
                    <div class="info-value" style="line-height: 1.6;"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?><br><?= htmlspecialchars($order['city']) ?></div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="panel">
                <div class="panel-title">
                    <i class="bi bi-bag"></i> Order Items (<?= count($order_items) ?>)
                </div>
                <div class="table-responsive">
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Farmer</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($order_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="product-cell">
                                        <img 
                                            src="<?= $item['product_image'] ? '../../uploads/products/'.$item['product_image'] : 'https://via.placeholder.com/80?text=Product' ?>"
                                            alt="<?= htmlspecialchars($item['product_name']) ?>"
                                            class="product-image">
                                        <div>
                                            <div class="product-name"><?= htmlspecialchars($item['product_name']) ?></div>
                                            <div class="product-farmer">Sold in <?= htmlspecialchars($item['unit'] ?? 'unit') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span style="font-weight: 500;"><i class="bi bi-shop me-1 text-muted"></i> <?= htmlspecialchars($item['farmer_name']) ?></span></td>
                                <td class="price-cell">₹<?= number_format($item['price'], 2) ?></td>
                                <td class="qty-cell"><?= number_format($item['quantity'], 2) ?></td>
                                <td class="total-cell">₹<?= number_format($item['subtotal'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; gap: 15px; margin-top: 10px;">
                <a href="order_tracking.php?order_id=<?= $order['id'] ?>" class="btn-premium">
                    <i class="bi bi-geo-alt"></i> Track Order Status
                </a>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>