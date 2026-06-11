<?php

require_once("../../includes/auth.php");

if($_SESSION['role']!="user")
{
header("Location: ../../login.php");
exit();
}

require_once("../../includes/config.php");

// Get the order ID from the URL safely
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id === 0) {
    header("Location: dashboard.php");
    exit;
}

$page_title = "Order Successful - FarmMart";
require_once("../../includes/header.php");
?>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ModernAdmin Inspired Theme */
:root {
    --theme-primary: #047857; /* Emerald Green */
    --theme-primary-hover: #059669;
    --theme-bg: #f9fafb;
    --theme-surface: #ffffff;
    --theme-text-dark: #111827;
    --theme-text-muted: #6b7280;
    --theme-border: #e5e7eb;
    --font-heading: 'Poppins', sans-serif;
    --font-body: 'Inter', sans-serif;
    --transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
}

body {
    background-color: var(--theme-bg);
    font-family: var(--font-body);
}

.success-card {
    background: var(--theme-surface);
    border-radius: 24px;
    border: none;
    box-shadow: 0 20px 40px rgba(0,0,0,0.06);
    padding: 60px 40px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.success-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 8px;
    background: linear-gradient(to right, #047857, #10b981);
}

.icon-wrapper {
    width: 120px;
    height: 120px;
    background: #ecfdf5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 30px;
    position: relative;
    box-shadow: 0 0 0 10px rgba(16, 185, 129, 0.1);
    animation: popIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

@keyframes popIn {
    0% { transform: scale(0.5); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.icon-wrapper i {
    font-size: 60px;
    color: var(--theme-primary);
}

.success-title {
    font-family: var(--font-heading);
    color: var(--theme-text-dark);
    font-weight: 800;
    font-size: 32px;
    margin-bottom: 15px;
}

.success-text {
    color: var(--theme-text-muted);
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 40px;
    max-width: 400px;
    margin-inline: auto;
}

.order-ref {
    display: inline-block;
    background: #f3f4f6;
    padding: 8px 20px;
    border-radius: 50px;
    font-family: monospace;
    font-size: 18px;
    font-weight: 700;
    color: var(--theme-text-dark);
    margin: 10px 0;
    border: 1px dashed #d1d5db;
}

.btn-premium {
    background: var(--theme-primary);
    color: #fff;
    padding: 14px 30px;
    border-radius: 50px;
    font-family: var(--font-heading);
    font-weight: 600;
    font-size: 15px;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    border: none;
    box-shadow: 0 8px 20px rgba(4, 120, 87, 0.2);
}
.btn-premium:hover {
    background: var(--theme-primary-hover);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(4, 120, 87, 0.3);
}

.btn-outline {
    background: transparent;
    color: var(--theme-text-dark);
    border: 2px solid var(--theme-border);
    padding: 12px 30px;
    border-radius: 50px;
    font-family: var(--font-heading);
    font-weight: 600;
    font-size: 15px;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}
.btn-outline:hover {
    background: #f9fafb;
    border-color: #d1d5db;
    transform: translateY(-2px);
}
</style>
<?php require_once("../../includes/navbar.php"); ?>

<main class="container py-5 mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="success-card">
                
                <div class="icon-wrapper">
                    <i class="bi bi-check-lg"></i>
                </div>
                
                <h2 class="success-title">Order Successful!</h2>
                
                <p class="success-text">
                    Thank you for supporting local farmers! Your order has been securely placed and an email receipt has been sent to you.
                    <br><br>
                    <span class="d-block text-muted" style="font-size: 13px; text-transform: uppercase; font-weight: 600;">Reference Number</span>
                    <span class="order-ref">#ORD-<?= str_pad(htmlspecialchars($order_id), 6, '0', STR_PAD_LEFT) ?></span>
                </p>
                
                <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 mt-4">
                    <a href="order_tracking.php?order_id=<?= htmlspecialchars($order_id) ?>" class="btn-premium">
                        <i class="bi bi-geo-alt"></i> Track Order
                    </a>
                    <a href="../../products.php" class="btn-outline">
                        Continue Shopping
                    </a>
                </div>
                
                <div class="mt-4 pt-4 border-top">
                    <a href="orders.php" class="text-muted text-decoration-none" style="font-size: 14px; font-weight: 500;">
                        <i class="bi bi-clock-history me-1"></i> View Order History
                    </a>
                </div>

            </div>
        </div>
    </div>
</main>

<?php require_once("../../includes/footer.php"); ?>