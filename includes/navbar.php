<?php

$cart_item_count = 0;

$profile_img = BASE_URL."assets/default-user.png"; // default image


if(isset($_SESSION['user_id']))
{

$user_id=$_SESSION['user_id'];


// CART COUNT (only user)

if($_SESSION['role']=="user")
{

$stmt=$conn->prepare("

SELECT SUM(quantity) total_qty

FROM cart

WHERE user_id=?

");

$stmt->bind_param("i",$user_id);

$stmt->execute();

$res=$stmt->get_result();

$row=$res->fetch_assoc();

$cart_item_count=$row['total_qty'] ?? 0;

$stmt->close();

}



// USER OR ADMIN IMAGE

if($_SESSION['role']=="user" || $_SESSION['role']=="admin")
{

$stmt=$conn->prepare("

SELECT profile_picture

FROM users

WHERE id=?

");

$stmt->bind_param("i",$user_id);

$stmt->execute();

$data=$stmt->get_result()->fetch_assoc();

if(!empty($data['profile_picture']))
{

$profile_img=
BASE_URL."uploads/users/".
$data['profile_picture'];

}

$stmt->close();

}



// FARMER IMAGE

if($_SESSION['role']=="farmer")
{

// A farmer is also a user. The actual profile picture used across the application
// for farmers in the navbar is usually their base `users` table picture because
// they log in via the `users` table. However, if you explicitly want the `profile_photo`
// from the `farmers` table, we fetch it here.
$stmt=$conn->prepare("

SELECT profile_photo

FROM farmers

WHERE user_id=?

");

$stmt->bind_param("i",$user_id);

$stmt->execute();

$data=$stmt->get_result()->fetch_assoc();

if(!empty($data['profile_photo']))
{
    // Try to load farmer specific photo
    $profile_img= BASE_URL."uploads/users/". $data['profile_photo'];
}
else 
{
    // Fallback: If farmer doesn't have a specific farm photo, try loading their base user profile picture.
    $stmt2=$conn->prepare("SELECT profile_picture FROM users WHERE id=?");
    $stmt2->bind_param("i",$user_id);
    $stmt2->execute();
    $data2=$stmt2->get_result()->fetch_assoc();
    if(!empty($data2['profile_picture'])) {
        $profile_img= BASE_URL."uploads/users/". $data2['profile_picture'];
    }
    $stmt2->close();
}

$stmt->close();

}

}

?>

<style>
/* Premium Navbar Styling */
:root {
    --theme-primary: #047857; /* Deep emerald green */
    --theme-secondary: #059669;
    --theme-dark: #111827;
    --theme-light: #f9fafb;
    --theme-accent: #f59e0b; /* Amber */
    --nav-text: #4b5563;
    --nav-hover: #111827;
    --transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.custom-navbar {
    background: #ffffff !important;
    padding: 15px 0 !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    transition: var(--transition);
}

.custom-navbar .navbar-brand {
    font-size: 1.6rem !important;
    font-weight: 800 !important;
    font-family: 'Poppins', sans-serif;
    color: var(--theme-primary) !important;
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: -0.5px;
}

.custom-navbar .navbar-brand i {
    font-size: 1.8rem;
    color: var(--theme-accent);
}

.custom-navbar .nav-link {
    color: var(--nav-text) !important;
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    font-size: 15px;
    padding: 8px 16px !important;
    margin: 0 4px;
    border-radius: 8px;
    transition: var(--transition);
    position: relative;
}

.custom-navbar .nav-link:hover {
    color: var(--theme-primary) !important;
    background: rgba(4, 120, 87, 0.05);
}

.custom-navbar .nav-link.active {
    color: var(--theme-primary) !important;
    background: rgba(4, 120, 87, 0.08);
}

/* Cart Icon */
.nav-cart-link {
    position: relative;
    color: var(--theme-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: var(--theme-light);
    transition: var(--transition);
    text-decoration: none !important;
}

.nav-cart-link:hover {
    background: var(--theme-primary);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(4, 120, 87, 0.2);
}

.nav-cart-link i {
    font-size: 1.2rem;
}

.cart-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--theme-accent);
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    min-width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    box-shadow: 0 2px 5px rgba(245, 158, 11, 0.4);
    border: 2px solid #fff;
}

/* User Profile Badge */
.user-profile-badge {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px 16px 6px 6px;
    border-radius: 50px;
    background: var(--theme-light);
    border: 1px solid #e5e7eb;
    transition: var(--transition);
    text-decoration: none !important;
    color: var(--theme-dark) !important;
}

.user-profile-badge:hover {
    background: #fff;
    border-color: var(--theme-primary);
    box-shadow: 0 4px 12px rgba(4, 120, 87, 0.1);
}

.user-profile-badge img {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    background-color: #fff;
}

.user-profile-badge strong {
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    font-size: 14px;
}

/* Admin specific profile badge styles */
.admin-profile-badge {
    background: #f6f9ff;
    border-color: #e1e6f1;
}

.admin-profile-badge:hover {
    background: #fff;
    border-color: #4154f1; /* Admin theme blue */
    box-shadow: 0 4px 12px rgba(65, 84, 241, 0.1);
}

/* Dropdown Menu */
.custom-dropdown-menu {
    border: none;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    padding: 12px;
    margin-top: 15px;
    min-width: 220px;
    animation: fadeInDown 0.3s ease forwards;
}

@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.custom-dropdown-menu .dropdown-item {
    padding: 10px 16px;
    border-radius: 8px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 500;
    color: var(--nav-text);
    transition: 0.2s;
    display: flex;
    align-items: center;
    gap: 10px;
}

.custom-dropdown-menu .dropdown-item:hover {
    background: var(--theme-light);
    color: var(--theme-primary);
    transform: translateX(4px);
}

.custom-dropdown-menu .dropdown-item.text-danger:hover {
    background: #fef2f2;
    color: #ef4444 !important;
}

.custom-dropdown-menu .dropdown-divider {
    margin: 8px 0;
    border-color: #f3f4f6;
}

/* Auth Buttons */
.nav-auth-btn {
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    font-size: 14px;
    padding: 10px 24px;
    border-radius: 50px;
    transition: var(--transition);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.nav-auth-login {
    color: var(--theme-primary);
    background: transparent;
    border: 2px solid var(--theme-primary);
}
.nav-auth-login:hover {
    background: var(--theme-primary);
    color: #fff;
    box-shadow: 0 4px 12px rgba(4, 120, 87, 0.2);
}

.nav-auth-signup {
    background: var(--theme-primary);
    color: #fff;
    border: 2px solid var(--theme-primary);
}
.nav-auth-signup:hover {
    background: var(--theme-secondary);
    border-color: var(--theme-secondary);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(4, 120, 87, 0.3);
}

/* Mobile Toggler */
.custom-toggler {
    border: none;
    background: var(--theme-light);
    padding: 8px 12px;
    border-radius: 8px;
    color: var(--theme-dark);
}
.custom-toggler:focus {
    box-shadow: none;
    outline: 2px solid var(--theme-primary);
}

@media (max-width: 991px) {
    .navbar-collapse {
        background: #fff;
        padding: 20px;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        margin-top: 15px;
        position: absolute;
        width: calc(100% - 30px);
        z-index: 1000;
    }
    .user-profile-badge {
        justify-content: flex-start;
        padding: 10px;
    }
}
</style>

<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg sticky-top custom-navbar">
    <div class="container position-relative">

        <a class="navbar-brand" href="<?= BASE_URL ?>index.php">
            <i class="bi bi-basket-fill"></i>
            FarmMart
        </a>

        <button class="navbar-toggler custom-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list fs-4"></i>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'products.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>products.php">Shop</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'farmers.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>farmers.php">Our Farmers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'contact.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>contact.php">Contact</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
                
                <!-- Cart (Only for Users) -->
                <?php if(isset($_SESSION['user_id']) && $_SESSION['role']=="user"): ?>
                <a href="<?= BASE_URL ?>user/includes/cart.php" class="nav-cart-link" title="Shopping Cart">
                    <i class="bi bi-cart3"></i>
                    <?php if($cart_item_count > 0): ?>
                        <span class="cart-badge" id="cartBadge"><?= $cart_item_count ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>

                <!-- User Profile / Auth Actions -->
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <?php 
                        // For Admin, we don't need a profile image, just an icon.
                        if($_SESSION['role'] == "admin"): ?>
                            <a href="#" class="user-profile-badge admin-profile-badge dropdown-toggle text-decoration-none" data-bs-toggle="dropdown" aria-expanded="false">
                                <div style="width: 36px; height: 36px; border-radius: 50%; background: #4154f1; color: white; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                    <i class="bi bi-shield-lock-fill"></i>
                                </div>
                                <strong style="color: #012970;">Admin</strong>
                            </a>
                        <?php else: ?>
                            <a href="#" class="user-profile-badge dropdown-toggle text-decoration-none" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?= $profile_img ?>" alt="Profile" onerror="this.src='<?= BASE_URL ?>assets/default-user.png';">
                                <strong><?= htmlspecialchars(substr($_SESSION['name'], 0, 15)) ?></strong>
                            </a>
                        <?php endif; ?>

                        <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu">
                            <?php if($_SESSION['role']=="admin"): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/dashboard.php"><i class="bi bi-speedometer2"></i> Admin Panel</a></li>
                            <?php endif; ?>

                            <?php if($_SESSION['role']=="farmer"): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>vendor/includes/dashboard.php"><i class="bi bi-shop"></i> Vendor Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>vendor/includes/products.php"><i class="bi bi-box-seam"></i> My Products</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>chat/chat_list.php"><i class="bi bi-chat-dots"></i> Messages</a></li>
                            <?php endif; ?>

                            <?php if($_SESSION['role']=="user"): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>user/includes/dashboard.php"><i class="bi bi-person"></i> My Account</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>user/includes/orders.php"><i class="bi bi-bag-check"></i> My Orders</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>user/includes/messages.php"><i class="bi bi-chat-text"></i> Messages</a></li>
                            <?php endif; ?>

                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>login.php" class="nav-auth-btn nav-auth-login">
                        Log In
                    </a>
                    <a href="<?= BASE_URL ?>register.php" class="nav-auth-btn nav-auth-signup">
                        Sign Up <i class="bi bi-arrow-right-short"></i>
                    </a>
                <?php endif; ?>

            </div>
        </div>
    </div>
</nav>