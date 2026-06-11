<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar-wrapper">

<style>
:root {
    --sidebar-bg: #14171c;
    --sidebar-surface: #1b1f26;
    --border-subtle: rgba(255,255,255,0.06);

    --text-primary: #f5f5f5;
    --text-secondary: #b8bec9;
    --text-muted: #7e8794;

    --accent-emerald: #10b981;
    --accent-emerald-soft: rgba(16, 185, 129, 0.12);

    --radius: 12px;
}

.sidebar-wrapper {
    position: fixed;
    left: 0;
    top: 0;
    width: 260px;
    height: 100vh;
    z-index: 100;
}

.sidebar {
    height: 100vh;
    background: var(--sidebar-bg);
    border-right: 1px solid var(--border-subtle);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 28px 22px;
    width: 100%;
}

/* Logo */
.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 35px;
    text-decoration: none;
}

.logo-icon {
    width: 34px;
    height: 34px;
    background: var(--accent-emerald);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
}

.logo-text {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-primary);
}

.logo-text span {
    color: var(--accent-emerald);
}

/* Menu */
.menu-section {
    margin: 20px 0 8px;
}

.menu-label {
    font-size: 11px;
    text-transform: uppercase;
    color: var(--text-muted);
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 12px;
    border-radius: 10px;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 14px;
    transition: all 0.25s ease;
    border-left: 3px solid transparent;
}

.menu-item:hover {
    background: var(--sidebar-surface);
    color: var(--text-primary);
}

.menu-item.active {
    background: var(--accent-emerald-soft);
    color: var(--accent-emerald);
    border-left: 3px solid var(--accent-emerald);
}

.menu-icon {
    width: 22px;
    text-align: center;
}

.menu-badge {
    margin-left: auto;
    padding: 4px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.menu-badge.amber {
    background: var(--accent-emerald-soft);
    color: var(--accent-emerald);
}

.sidebar-footer {
    padding-top: 20px;
    border-top: 1px solid var(--border-subtle);
}

@media (max-width: 992px) {
    .sidebar-wrapper {
        width: 260px;
        left: -260px;
        box-shadow: 2px 0 8px rgba(0, 0, 0, 0.3);
    }
    
    .sidebar-wrapper.active {
        left: 0;
    }
}

@media (max-width: 768px) {
    .sidebar-wrapper {
        width: 240px;
        left: -240px;
    }
}
</style>

<div class="sidebar">

    <!-- TOP -->
    <div>

        <a href="../../index.php" class="sidebar-logo">
            <div class="logo-icon"><i class="bi bi-basket-fill"></i></div>
            <span class="logo-text">Farm<span>Mart</span></span>
        </a>

        <!-- Main Navigation -->
        <div class="menu-section">
            <span class="menu-label">Main</span>
        </div>

        <a href="dashboard.php" class="menu-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <div class="menu-icon"><i class="bi bi-grid-1x2"></i></div>
            Dashboard
        </a>

        <a href="orders.php" class="menu-item <?= $current_page == 'orders.php' || $current_page == 'view_order.php' ? 'active' : '' ?>">
            <div class="menu-icon"><i class="bi bi-box-seam"></i></div>
            My Orders
            <?php if (!empty($orders_count)): ?>
                <span class="menu-badge amber"><?= $orders_count ?></span>
            <?php endif; ?>
        </a>

        <!-- <a href="order_tracking.php" class="menu-item <?= $current_page == 'order_tracking.php' ? 'active' : '' ?>">
            <div class="menu-icon"><i class="bi bi-truck"></i></div>
            Order Tracking
        </a> -->

        <a href="cart.php" class="menu-item <?= $current_page == 'cart.php' || $current_page == 'checkout.php' ? 'active' : '' ?>">
            <div class="menu-icon"><i class="bi bi-cart3"></i></div>
            My Cart
            <?php if (!empty($cart_count)): ?>
                <span class="menu-badge amber"><?= $cart_count ?></span>
            <?php endif; ?>
        </a>

        <!-- Communication -->
        <div class="menu-section">
            <span class="menu-label">Communication</span>
        </div>

        <a href="notifications.php" class="menu-item <?= $current_page == 'notifications.php' ? 'active' : '' ?>">
            <div class="menu-icon"><i class="bi bi-bell"></i></div>
            Notifications
        </a>

        <a href="messages.php" class="menu-item <?= $current_page == 'messages.php' ? 'active' : '' ?>">
            <div class="menu-icon"><i class="bi bi-chat-dots"></i></div>
            Messages
        </a>

        <!-- Account -->
        <div class="menu-section">
            <span class="menu-label">Account</span>
        </div>

        <a href="profile.php" class="menu-item <?= $current_page == 'profile.php' ? 'active' : '' ?>">
            <div class="menu-icon"><i class="bi bi-person"></i></div>
            Profile
        </a>

    </div>

    <!-- FOOTER -->
    <div class="sidebar-footer">
        <a href="../../logout.php" class="menu-item" style="color: #ef4444;">
            <div class="menu-icon"><i class="bi bi-box-arrow-right"></i></div>
            Logout
        </a>
    </div>

</div>
</div>