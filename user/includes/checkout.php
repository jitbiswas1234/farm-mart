<?php
session_start();

// Database & Core includes
require_once("../../includes/config.php");
require_once("../../includes/auth.php");
require_once("../../includes/mail.php");

// Security Check: Only users can checkout
if($_SESSION['role'] != "user") {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. FETCH CART ITEMS
$stmt = $conn->prepare("
    SELECT 
        c.quantity,
        p.id product_id,
        p.name,
        p.price,
        p.unit,
        p.image,
        p.farmer_id
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$cart_items = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Redirect if cart is empty
if(count($cart_items) == 0) {
    header("Location: cart.php");
    exit();
}

// 2. CALCULATE TOTALS
$subtotal = 0;
foreach($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Grab discount from session if applied on cart page
$coupon_discount = isset($_SESSION['checkout_discount']) ? $_SESSION['checkout_discount'] : 0;
$applied_coupon = isset($_SESSION['applied_coupon']) ? $_SESSION['applied_coupon'] : '';

// Calculate shipping logic (Free shipping over 499)
$delivery = ($subtotal >= 499) ? 0 : 50;

$tax = ($subtotal - $coupon_discount) * 0.05;
$total = ($subtotal - $coupon_discount) + $delivery + $tax;
$error = "";

// 3. ORDER PROCESSING (POST)
if($_SERVER['REQUEST_METHOD'] == "POST") {
    $address = $_POST['address'];
    $city = $_POST['city'];
    $zip = $_POST['zip'];
    $payment = $_POST['payment_method'];
    $payment_status = "pending";
    $transaction = NULL;

    // Razorpay verification
    if($payment == "Razorpay") {
        if(!empty($_POST['razorpay_payment_id'])) {
            $transaction = $_POST['razorpay_payment_id'];
            $payment_status = "paid";
        } else {
            $error = "Payment failed. Please try again.";
        }
    }

    if(empty($error)) {
        // Insert Order
        $order = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, city, zip_code, payment_method, payment_status, transaction_id, status) VALUES(?,?,?,?,?,?,?,?,'pending')");
        $order->bind_param("idssssss", $user_id, $total, $address, $city, $zip, $payment, $payment_status, $transaction);
        $order->execute();
        $order_id = $conn->insert_id;
        $order->close();

        // Send internal notification to user about successful order placement
        $notif_title = "Order Placed";
        $notif_msg = "Order Confirmed! Your order #ORD-" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . " has been successfully placed.";
        $notif_type = "user"; // Enum match
        $n_stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        if($n_stmt) {
            $n_stmt->bind_param("isss", $user_id, $notif_type, $notif_title, $notif_msg);
            $n_stmt->execute();
            $n_stmt->close();
        }

        // Stock Update and Order Items
        $stock_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, farmer_id, quantity, price, subtotal) VALUES(?,?,?,?,?,?)");
        
        $email_products = "";
        foreach($cart_items as $c) {
            $item_total = $c['price'] * $c['quantity'];
            
            // Insert Item
            $item_stmt->bind_param("iiiidd", $order_id, $c['product_id'], $c['farmer_id'], $c['quantity'], $c['price'], $item_total);
            $item_stmt->execute();

            // Update Stock
            $stock_stmt->bind_param("di", $c['quantity'], $c['product_id']);
            $stock_stmt->execute();

            // Build Email Table Rows
            $email_products .= "
                <tr>
                    <td style='padding:15px; border-bottom:1px solid #f1f3f5; font-family: sans-serif; font-size: 14px;'>
                        <strong>{$c['name']}</strong><br>
                        <small style='color:#6b7280;'>Qty: ".number_format($c['quantity'],2)." {$c['unit']}</small>
                    </td>
                    <td style='padding:15px; border-bottom:1px solid #f1f3f5; font-family: sans-serif; font-size: 15px; text-align:right; font-weight:600;'>
                        ₹".number_format($item_total,2)."
                    </td>
                </tr>";
        }
        $item_stmt->close();
        $stock_stmt->close();

        // Clear Cart & Session Discounts
        $conn->query("DELETE FROM cart WHERE user_id = $user_id");
        unset($_SESSION['checkout_discount']);
        unset($_SESSION['applied_coupon']);

        // Fetch User Info for Email
        $u_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
        $u_stmt->bind_param("i", $user_id);
        $u_stmt->execute();
        $user_data = $u_stmt->get_result()->fetch_assoc();
        $name = $user_data['name'];
        $email = $user_data['email'];

        // EMAIL TEMPLATE
        // Determine correct base URL logic for images in emails to work externally
        // Using HTTP_HOST to build absolute URL dynamically if BASE_URL isn't fully absolute
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        // We assume BASE_URL might be '/login1/' or similar.
        // We combine to make it absolute if it doesn't start with http
        $absolute_base = (strpos(BASE_URL, 'http') === 0) ? BASE_URL : $protocol . $host . BASE_URL;
        
        $logo = $absolute_base . "uploads/profile/logo.png";
        $tracking_url = $absolute_base . "user/includes/order_tracking.php?order_id=" . $order_id;
        
        $discount_html = "";
        if($coupon_discount > 0) {
            $discount_html = "
            <tr>
                <td style='padding:8px 0; color:#6b7280;'>Discount ($applied_coupon)</td>
                <td style='padding:8px 0; text-align:right; color:#047857; font-weight:600;'>-₹".number_format($coupon_discount,2)."</td>
            </tr>";
        }

        $mail_body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1'>
            <style>
                body { margin: 0; padding: 0; background-color: #f9fafb; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
                .wrapper { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
                .header { background-color: #047857; padding: 35px 40px; text-align: center; }
                .header img { max-height: 45px; margin-bottom: 15px; }
                .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: 600; letter-spacing: 0.5px; }
                .content { padding: 40px; }
                .greeting { font-size: 18px; color: #111827; font-weight: 600; margin-top: 0; margin-bottom: 15px; }
                .message { font-size: 15px; color: #4b5563; line-height: 1.6; margin-bottom: 30px; }
                
                .order-box { border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; margin-bottom: 30px; }
                .order-meta { background-color: #f9fafb; padding: 15px 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; }
                .order-meta p { margin: 0; font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
                .order-meta strong { display: block; color: #111827; font-size: 15px; margin-top: 4px; }
                
                .items-table { width: 100%; border-collapse: collapse; }
                
                .summary-box { background-color: #f9fafb; padding: 25px 30px; border-radius: 10px; margin-bottom: 35px; }
                .summary-table { width: 100%; border-collapse: collapse; font-size: 15px; }
                .total-row td { border-top: 2px solid #e5e7eb; padding-top: 15px !important; margin-top: 15px; font-weight: 700; color: #111827; font-size: 18px; }
                
                .delivery-info { margin-bottom: 35px; }
                .delivery-info h3 { font-size: 16px; color: #111827; margin-bottom: 10px; }
                .delivery-info p { font-size: 14px; color: #4b5563; line-height: 1.5; margin: 0; }
                
                .btn-track { display: block; width: 100%; background-color: #047857; color: #ffffff; text-align: center; padding: 16px 0; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; margin-bottom: 40px; }
                
                .footer { background-color: #111827; padding: 30px 40px; text-align: center; }
                .footer p { color: #9ca3af; font-size: 13px; margin: 0 0 10px 0; }
                .footer-brand { color: #ffffff; font-weight: 600; font-size: 16px; margin-bottom: 10px; display: block; }
            </style>
        </head>
        <body>
            <div class='wrapper'>
                <div class='header'>
                    <img src='$logo' alt='FarmMart Logo'>
                    <h1>Order Confirmed</h1>
                </div>
                
                <div class='content'>
                    <p class='greeting'>Hello $name,</p>
                    <p class='message'>Thank you for shopping with FarmMart! Your order has been placed successfully and is currently being processed. Here are the details of your purchase:</p>
                    
                    <div class='order-box'>
                        <div class='order-meta'>
                            <table style='width: 100%;'><tr>
                                <td style='width: 50%;'>
                                    <p>Order ID <strong>#ORD-" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . "</strong></p>
                                </td>
                                <td style='width: 50%; text-align: right;'>
                                    <p>Payment Method <strong>" . strtoupper($payment) . "</strong></p>
                                </td>
                            </tr></table>
                        </div>
                        <table class='items-table'>
                            $email_products
                        </table>
                    </div>
                    
                    <div class='summary-box'>
                        <table class='summary-table'>
                            <tr>
                                <td style='padding:8px 0; color:#6b7280;'>Subtotal</td>
                                <td style='padding:8px 0; text-align:right; font-weight:600; color:#111827;'>₹".number_format($subtotal,2)."</td>
                            </tr>
                            $discount_html
                            <tr>
                                <td style='padding:8px 0; color:#6b7280;'>Delivery Charge</td>
                                <td style='padding:8px 0; text-align:right; font-weight:600; color:#111827;'>₹".number_format($delivery,2)."</td>
                            </tr>
                            <tr>
                                <td style='padding:8px 0; color:#6b7280;'>Tax (5%)</td>
                                <td style='padding:8px 0; text-align:right; font-weight:600; color:#111827;'>₹".number_format($tax,2)."</td>
                            </tr>
                            <tr class='total-row'>
                                <td style='padding:15px 0 0 0;'>Grand Total</td>
                                <td style='padding:15px 0 0 0; text-align:right; color:#047857;'>₹".number_format($total,2)."</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class='delivery-info'>
                        <h3>Delivery Details</h3>
                        <p><strong>Shipping to:</strong><br>
                        " . nl2br(htmlspecialchars($address)) . "<br>" . htmlspecialchars($city) . " - " . htmlspecialchars($zip) . "</p>
                        <p style='margin-top: 15px;'><strong>Expected Delivery:</strong><br>
                        Within 2-3 Business Days</p>
                    </div>
                    
                    <a href='$tracking_url' class='btn-track' style='color:#ffffff !important;'>Track Your Order</a>
                    
                    <p style='text-align: center; color: #6b7280; font-size: 14px; margin: 0;'>
                        Need help? Contact our support team at <br><strong>support@farmmart.com</strong>
                    </p>
                </div>
                
                <div class='footer'>
                    <span class='footer-brand'>FarmMart 🌱</span>
                    <p>Fresh Products Direct From Farmers</p>
                    <p style='font-size: 11px; opacity: 0.7;'>© " . date('Y') . " FarmMart. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";

        sendMail($email, "Order Confirmed - FarmMart #ORD-" . str_pad($order_id, 6, '0', STR_PAD_LEFT), $mail_body);

        header("Location: order_success.php?order_id=".$order_id);
        exit();
    }
}

$page_title = "Checkout - FarmMart";
require_once("../../includes/header.php");
require_once("../../includes/navbar.php");
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
/* ModernAdmin Inspired Theme for Checkout */
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

body {
    background-color: var(--theme-bg);
    font-family: var(--font-body);
    color: var(--theme-text-muted);
}

.checkout-header {
    background: #fff;
    padding: 30px 0;
    margin-bottom: 40px;
    border-bottom: 1px solid var(--theme-border);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.checkout-header h2 {
    font-family: var(--font-heading);
    color: var(--theme-text-dark);
    font-weight: 700;
    margin: 0;
}

.card-custom { 
    border: none; 
    border-radius: 20px; 
    box-shadow: var(--theme-shadow); 
    background: var(--theme-surface);
    margin-bottom: 30px;
}
.card-custom .card-body {
    padding: 30px;
}

.section-title {
    font-family: var(--font-heading);
    font-weight: 600;
    color: var(--theme-text-dark);
    font-size: 18px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.section-title i {
    color: var(--theme-primary);
}

/* Form Styles */
.form-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--theme-text-dark);
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.form-control { 
    border-radius: 12px; 
    padding: 14px 20px; 
    border: 1px solid var(--theme-border); 
    font-size: 14px;
    background-color: #f9fafb;
    transition: var(--transition);
}
.form-control:focus { 
    border-color: var(--theme-primary); 
    box-shadow: 0 0 0 4px rgba(4, 120, 87, 0.1); 
    background-color: #fff;
}

/* Payment Options */
.payment-option {
    border: 2px solid var(--theme-border); 
    border-radius: 12px; 
    padding: 16px 20px; 
    margin-bottom: 15px;
    cursor: pointer; 
    transition: var(--transition); 
    display: flex; 
    align-items: center; 
    gap: 15px;
    background: #fff;
}
.payment-option:hover { 
    border-color: #d1d5db; 
}
.payment-option.active { 
    border-color: var(--theme-primary); 
    background: #ecfdf5; 
}
.payment-option input[type="radio"] {
    width: 20px;
    height: 20px;
    accent-color: var(--theme-primary);
}
.payment-option span {
    font-weight: 600;
    color: var(--theme-text-dark);
    font-size: 15px;
}

/* Summary Card */
.summary-card { 
    position: sticky; 
    top: 100px; 
}
.item-img { 
    width: 60px; 
    height: 60px; 
    object-fit: cover; 
    border-radius: 10px; 
    border: 1px solid var(--theme-border);
}
.item-list {
    max-height: 350px; 
    overflow-y: auto;
    padding-right: 10px;
}
.item-list::-webkit-scrollbar { width: 4px; }
.item-list::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px dashed var(--theme-border);
}
.summary-row:last-child {
    border-bottom: none;
}
.summary-label {
    color: var(--theme-text-muted);
    font-size: 14px;
}
.summary-value {
    color: var(--theme-text-dark);
    font-weight: 600;
    font-size: 15px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 20px;
    margin-top: 10px;
    border-top: 2px solid var(--theme-border);
}
.total-label {
    font-family: var(--font-heading);
    font-weight: 700;
    font-size: 18px;
    color: var(--theme-text-dark);
}
.total-value {
    font-size: 24px;
    font-weight: 800;
    color: var(--theme-primary);
}

.btn-premium {
    background: var(--theme-primary);
    color: #fff;
    padding: 16px 20px;
    border-radius: 50px;
    font-family: var(--font-heading);
    font-weight: 600;
    font-size: 15px;
    letter-spacing: 0.5px;
    transition: var(--transition);
    border: none;
    box-shadow: 0 10px 20px rgba(4, 120, 87, 0.2);
}
.btn-premium:hover {
    background: var(--theme-primary-hover);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 15px 25px rgba(4, 120, 87, 0.3);
}
</style>

<div class="checkout-header">
    <div class="container d-flex justify-content-between align-items-center">
        <h2>Secure Checkout</h2>
        <a href="cart.php" class="text-decoration-none text-muted fw-bold"><i class="bi bi-arrow-left me-1"></i> Back to Cart</a>
    </div>
</div>

<main class="container pb-5">

    <?php if($error): ?>
        <div class="alert alert-danger border-0 rounded-3 shadow-sm mb-4">
            <i class="bi bi-exclamation-octagon-fill me-2"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="row g-5">
        <div class="col-lg-7">
            <form method="POST" id="checkoutForm">
                
                <div class="card card-custom">
                    <div class="card-body">
                        <div class="section-title"><i class="bi bi-geo-alt-fill"></i> Delivery Address</div>
                        <div class="mb-4">
                            <label class="form-label">Full Street Address</label>
                            <textarea name="address" class="form-control" rows="3" required placeholder="House number, Street name, Landmark"></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">City / District</label>
                                <input name="city" class="form-control" required placeholder="E.g., Mumbai">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">PIN / ZIP Code</label>
                                <input name="zip" class="form-control" required placeholder="E.g., 400001">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-custom">
                    <div class="card-body">
                        <div class="section-title"><i class="bi bi-credit-card-fill"></i> Payment Method</div>
                        <label class="payment-option active">
                            <input type="radio" name="payment_method" value="COD" checked>
                            <span><i class="bi bi-cash-stack text-success me-1 fs-5"></i> Cash on Delivery (COD)</span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="Razorpay">
                            <span><i class="bi bi-shield-lock-fill text-primary me-1 fs-5"></i> Secure Online Payment (Razorpay)</span>
                        </label>
                        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                    </div>
                </div>

                <button class="btn btn-premium w-100 mt-2">
                    CONFIRM ORDER — ₹<?= number_format($total, 2) ?> <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="card card-custom summary-card">
                <div class="card-body">
                    <div class="section-title"><i class="bi bi-bag-check-fill"></i> Order Summary</div>
                    
                    <div class="item-list mb-4">
                        <?php foreach($cart_items as $c): ?>
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?= !empty($c['image']) ? '../../uploads/products/'.$c['image'] : 'https://via.placeholder.com/60' ?>" class="item-img me-3">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold text-dark" style="font-size:14px;"><?= htmlspecialchars($c['name']) ?></h6>
                                <small class="text-muted"><?= number_format($c['quantity'], 2) ?> <?= htmlspecialchars($c['unit']) ?> × ₹<?= number_format($c['price'], 2) ?></small>
                            </div>
                            <div class="fw-bold" style="color:var(--theme-dark);">₹<?= number_format($c['price'] * $c['quantity'], 2) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-row">
                        <span class="summary-label">Subtotal</span>
                        <span class="summary-value">₹<?= number_format($subtotal, 2) ?></span>
                    </div>
                    
                    <?php if($coupon_discount > 0): ?>
                    <div class="summary-row">
                        <span class="summary-label" style="color: var(--theme-primary); font-weight: 600;">Discount (<?= htmlspecialchars($applied_coupon) ?>)</span>
                        <span class="summary-value" style="color: var(--theme-primary);">-₹<?= number_format($coupon_discount, 2) ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="summary-row">
                        <span class="summary-label">Delivery Fee</span>
                        <span class="summary-value"><?= $delivery == 0 ? '<span class="text-success">FREE</span>' : '₹'.number_format($delivery, 2) ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Estimated Tax (5%)</span>
                        <span class="summary-value">₹<?= number_format($tax, 2) ?></span>
                    </div>
                    
                    <div class="total-row">
                        <span class="total-label">Total to Pay</span>
                        <span class="total-value">₹<?= number_format($total, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    // Toggle active class for payment options
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('active'));
            this.closest('.payment-option').classList.add('active');
        });
    });

    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        let method = document.querySelector('input[name="payment_method"]:checked').value;
        if(method == "Razorpay") {
            e.preventDefault();
            var options = {
                "key": "<?= RAZORPAY_KEY_ID ?>",
                "amount": "<?= $total * 100 ?>",
                "currency": "INR",
                "name": "FarmMart",
                "description": "Order Payment",
                "handler": function(response) {
                    document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                    document.getElementById('checkoutForm').submit();
                },
                "theme": { "color": "#047857" } // Updated to match Emerald theme
            };
            var rzp = new Razorpay(options);
            rzp.open();
        }
    });
</script>

<?php require_once("../../includes/footer.php"); ?>