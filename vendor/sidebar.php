<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Vendor Sidebar -->
<div class="card shadow-sm border-0" style="border-radius: 5px; position: sticky; top: 100px; z-index: 100; background-color: #f8f9fa;">
    <div class="card-body text-center p-4">
        <div class="position-relative d-inline-block mb-3">
            <?php
            // Determine the profile picture source for the vendor
            $vendor_profile_picture = $_SESSION['profile_picture'] ?? '';
            $vendor_img_src = !empty($vendor_profile_picture) ? BASE_URL . 'uploads/users/' . htmlspecialchars($vendor_profile_picture) : BASE_URL . 'assets/default-user.png';
            // Check if the file actually exists on the server
            if (!empty($vendor_profile_picture) && !file_exists(UPLOAD_PATH . 'users/' . $vendor_profile_picture)) {
                $vendor_img_src = BASE_URL . 'assets/default-user.png';
            }
            ?>
            <img src="<?= $vendor_img_src ?>"
                 class="rounded-circle shadow-sm"
                 alt="Vendor Profile"
                 style="width: 80px; height: 80px; object-fit: cover; border: 3px solid #e9ecef;">
        </div>
        <h5 class="fw-bold mb-0" style="color: #012970;"><?= htmlspecialchars($_SESSION['name'] ?? 'Vendor') ?></h5>
        <p class="text-muted small mb-0">Vendor / Farmer</p>
    </div>
    <div class="list-group list-group-flush border-top pt-2 px-2 pb-2" style="background-color: transparent;">
        <a href="<?= BASE_URL ?>vendor/includes/dashboard.php" class="list-group-item list-group-item-action <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" style="background-color: transparent;">
            <i class="bi bi-grid"></i> Dashboard
        </a>
        <a href="<?= BASE_URL ?>vendor/includes/products.php" class="list-group-item list-group-item-action <?= $current_page == 'products.php' ? 'active' : '' ?>" style="background-color: transparent;">
            <i class="bi bi-box-seam"></i> My Products
        </a>
        <a href="<?= BASE_URL ?>vendor/includes/orders.php" class="list-group-item list-group-item-action <?= $current_page == 'orders.php' ? 'active' : '' ?>" style="background-color: transparent;">
            <i class="bi bi-receipt"></i> Customer Orders
        </a>
        <a href="<?= BASE_URL ?>chat/chat_list.php" class="list-group-item list-group-item-action <?= $current_page == 'chat_list.php' ? 'active' : '' ?>" style="background-color: transparent;">
            <i class="bi bi-chat-left-text"></i> Messages
        </a>
        <a href="<?= BASE_URL ?>vendor/includes/add_product.php" class="list-group-item list-group-item-action <?= $current_page == 'add_product.php' ? 'active' : '' ?>" style="background-color: transparent;">
            <i class="bi bi-plus-circle"></i> Add New Product
        </a>
        <a href="<?= BASE_URL ?>vendor/includes/earnings.php" class="list-group-item list-group-item-action <?= $current_page == 'earnings.php' ? 'active' : '' ?>" style="background-color: transparent;">
            <i class="bi bi-currency-rupee"></i> Earnings
        </a>
        <a href="<?= BASE_URL ?>vendor/includes/profile.php" class="list-group-item list-group-item-action <?= $current_page == 'profile.php' ? 'active' : '' ?>" style="background-color: transparent;">
            <i class="bi bi-person"></i> Profile Settings
        </a>
        <a href="<?= BASE_URL ?>logout.php" class="list-group-item list-group-item-action text-danger mt-2" style="border-top: 1px solid #dee2e6; background-color: transparent;">
            <i class="bi bi-box-arrow-right text-danger"></i> Logout
        </a>
    </div>
</div>