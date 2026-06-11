<?php
require_once("includes/config.php");

$farmer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Join with users table to get the profile_picture and user_id for chat
$query = "
    SELECT f.*, u.profile_picture, u.id as user_id
    FROM farmers f 
    JOIN users u ON f.email = u.email WHERE f.id = $farmer_id";
$result = mysqli_query($conn, $query);
$farmer = mysqli_fetch_assoc($result);

if (!$farmer) {
    header("Location: farmers.php");
    exit;
}

// Fetch farmer's products
$p_query = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.farmer_id = $farmer_id AND p.status = 'active' 
    ORDER BY p.created_at DESC";
$p_result = mysqli_query($conn, $p_query);
$products = mysqli_fetch_all($p_result, MYSQLI_ASSOC);

$page_title = htmlspecialchars($farmer['name']) . " - FarmMart Profile";
require_once("includes/header.php");
require_once("includes/navbar.php");
?>

<style>
/* Premium Farmer Profile Styling */
:root {
  --theme-primary: #047857; /* Deep emerald green */
  --theme-secondary: #059669;
  --theme-dark: #111827;
  --theme-light: #f9fafb;
  --theme-accent: #f59e0b; /* Amber */
  --font-heading: 'Poppins', sans-serif;
  --font-body: 'Inter', sans-serif;
  --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

body {
  background-color: var(--theme-light);
  font-family: var(--font-body);
  color: #4b5563;
}

h1, h2, h3, h4, h5, h6 {
  font-family: var(--font-heading);
  color: var(--theme-dark);
  font-weight: 700;
}

/* Cover Image Banner */
.profile-cover {
    height: 350px;
    background: linear-gradient(rgba(17, 24, 39, 0.5), rgba(4, 120, 87, 0.8)), 
                url('https://images.unsplash.com/photo-1595841696677-6479ff3f62eb?auto=format&fit=crop&w=1920&q=80') no-repeat center 60%;
    background-size: cover;
    border-radius: 0 0 40px 40px;
    margin-bottom: 80px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

/* Profile Header Section */
.profile-header-card {
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.06);
    padding: 40px;
    margin-top: -150px;
    position: relative;
    z-index: 10;
    text-align: center;
    border: 1px solid rgba(0,0,0,0.03);
}

.profile-avatar-wrap {
    position: relative;
    display: inline-block;
    margin-top: -90px;
    margin-bottom: 20px;
}

.profile-avatar {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    object-fit: cover;
    border: 6px solid #fff;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    background: #fff;
}

.verified-badge {
    position: absolute;
    bottom: 5px;
    right: 5px;
    background: var(--theme-primary);
    color: #fff;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    border: 3px solid #fff;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.farmer-name {
    font-size: 32px;
    margin-bottom: 5px;
}

.farmer-location {
    color: #6b7280;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-bottom: 25px;
}
.farmer-location i {
    color: var(--theme-primary);
}

/* Stats Row */
.stats-row {
    display: flex;
    justify-content: center;
    gap: 40px;
    padding-top: 25px;
    border-top: 1px solid #f3f4f6;
    margin-top: 10px;
}

.stat-block {
    text-align: center;
}

.stat-value {
    font-size: 24px;
    font-weight: 800;
    color: var(--theme-dark);
    line-height: 1.2;
}

.stat-label {
    font-size: 12px;
    text-transform: uppercase;
    color: #9ca3af;
    letter-spacing: 1px;
    font-weight: 600;
}

.rating-stars {
    color: var(--theme-accent);
    font-size: 18px;
    margin-bottom: 2px;
}

/* Action Buttons */
.profile-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 30px;
}

.btn-chat {
    background: var(--theme-primary);
    color: #fff;
    border: none;
    padding: 12px 30px;
    border-radius: 50px;
    font-weight: 600;
    font-family: var(--font-heading);
    transition: var(--transition);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 8px 20px rgba(4, 120, 87, 0.25);
}
.btn-chat:hover {
    background: var(--theme-secondary);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(4, 120, 87, 0.35);
}

/* Products Section */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e5e7eb;
}
.section-header h3 {
    margin: 0;
    font-size: 24px;
}

/* Premium Product Cards (Mirroring products.php style) */
.premium-card {
  background: #fff;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0,0,0,0.03);
  transition: var(--transition);
  position: relative;
  height: 100%;
  border: 1px solid rgba(0,0,0,0.02);
  display: flex;
  flex-direction: column;
}

.premium-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 20px 40px rgba(0,0,0,0.08);
}

.p-card-img-wrap {
  position: relative;
  height: 240px;
  overflow: hidden;
  padding: 15px;
}

.p-card-img-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 16px;
  transition: transform 0.6s ease;
}

.premium-card:hover .p-card-img-wrap img {
  transform: scale(1.08);
}

.p-tags {
  position: absolute;
  top: 30px;
  left: 30px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  z-index: 2;
}

.p-tag-badge {
  padding: 5px 12px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.p-tag-organic { background: #10b981; color: #fff; }
.p-tag-category { background: rgba(255,255,255,0.9); color: var(--theme-dark); }

.p-action-overlay {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) scale(0.9);
  display: flex;
  gap: 10px;
  opacity: 0;
  visibility: hidden;
  transition: var(--transition);
  z-index: 3;
}
.premium-card:hover .p-action-overlay {
  opacity: 1;
  visibility: visible;
  transform: translate(-50%, -50%) scale(1);
}
.p-action-btn {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  background: #fff;
  color: var(--theme-dark);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  border: none;
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
  transition: var(--transition);
  text-decoration: none;
}
.p-action-btn:hover {
  background: var(--theme-primary);
  color: #fff;
  transform: translateY(-3px);
}

.p-card-body {
  padding: 20px 25px 25px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.p-title {
  font-size: 18px;
  font-weight: 700;
  margin-bottom: 12px;
}
.p-title a {
  color: var(--theme-dark);
  text-decoration: none;
  transition: color 0.2s;
}
.p-title a:hover {
  color: var(--theme-primary);
}

.p-price-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.p-price {
  font-size: 20px;
  font-weight: 800;
  color: var(--theme-primary);
}
.p-price span {
  font-size: 13px;
  color: #9ca3af;
  font-weight: 500;
}

.btn-details {
    background: transparent;
    color: var(--theme-primary);
    border: 2px solid var(--theme-primary);
    padding: 10px;
    border-radius: 12px;
    font-weight: 600;
    transition: var(--transition);
    text-decoration: none;
    display: block;
    text-align: center;
    margin-top: auto;
}
.btn-details:hover {
    background: var(--theme-primary);
    color: #fff;
}

@media (max-width: 768px) {
    .profile-cover { height: 250px; border-radius: 0 0 20px 20px; margin-bottom: 60px; }
    .profile-header-card { margin-top: -100px; padding: 25px 15px; }
    .stats-row { gap: 20px; flex-wrap: wrap; }
}
</style>

<!-- Cover Image Banner -->
<div class="profile-cover"></div>

<main class="container pb-5">
    <!-- Farmer Header -->
    <div class="row justify-content-center mb-5" data-aos="fade-up">
        <div class="col-lg-9">
            <div class="profile-header-card">
                <div class="profile-avatar-wrap">
                    <img src="<?= $farmer['profile_picture'] ? BASE_URL . 'uploads/users/' . htmlspecialchars($farmer['profile_picture']) : BASE_URL . 'assets/default-user.png' ?>" alt="<?= htmlspecialchars($farmer['name']) ?>" class="profile-avatar">
                    <div class="verified-badge" title="Verified FarmMart Partner">
                        <i class="bi bi-check-lg"></i>
                    </div>
                </div>
                
                <h1 class="farmer-name"><?= htmlspecialchars($farmer['name']) ?></h1>
                
                <div class="farmer-location">
                    <i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($farmer['village'] ?? 'Local Agricultural Area') ?>
                </div>

                <div class="stats-row">
                    <div class="stat-block">
                        <div class="stat-value"><?= count($products) ?></div>
                        <div class="stat-label">Products</div>
                    </div>
                    <div class="stat-block">
                        <div class="rating-stars">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
                        </div>
                        <div class="stat-label">4.8 Rating</div>
                    </div>
                    <div class="stat-block">
                        <div class="stat-value text-success"><?= date('Y', strtotime($farmer['created_at'] ?? '2023-01-01')) ?></div>
                        <div class="stat-label">Joined</div>
                    </div>
                </div>

                <div class="profile-actions">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($_SESSION['user_id'] != $farmer['user_id']): ?>
                            <a href="chat/chat.php?user=<?= $farmer['user_id'] ?>" class="btn-chat">
                                <i class="bi bi-chat-text"></i> Message Farmer
                            </a>
                        <?php else: ?>
                            <button class="btn-chat bg-secondary text-white" disabled style="box-shadow: none;">This is your profile</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" class="btn-chat bg-secondary text-white" style="box-shadow: none;">
                            <i class="bi bi-box-arrow-in-right"></i> Login to Message
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Section -->
    <div class="section-header" data-aos="fade-up">
        <h3>Harvested by <?= htmlspecialchars(explode(' ', $farmer['name'])[0]) ?></h3>
        <span class="text-muted"><?= count($products) ?> items available</span>
    </div>

    <div class="row g-4">
        <?php if (count($products) > 0): ?>
            <?php foreach($products as $index => $p): ?>
                <div class="col-xl-3 col-lg-4 col-sm-6" data-aos="fade-up" data-aos-delay="<?= ($index % 8) * 50 ?>">
                    <div class="premium-card">
                        <div class="p-tags">
                            <?php if ($p['is_organic']): ?>
                                <span class="p-tag-badge p-tag-organic">Organic</span>
                            <?php endif; ?>
                            <?php if (!empty($p['category_name'])): ?>
                                <span class="p-tag-badge p-tag-category"><?= htmlspecialchars($p['category_name']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="p-card-img-wrap">
                            <img src="<?= !empty($p['image']) ? 'uploads/products/' . htmlspecialchars($p['image']) : 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?auto=format&fit=crop&w=600&q=80' ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                            
                            <div class="p-action-overlay">
                                <a href="product_details.php?id=<?= $p['id'] ?>" class="p-action-btn" data-bs-toggle="tooltip" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </div>

                        <div class="p-card-body">
                            <h3 class="p-title">
                                <a href="product_details.php?id=<?= $p['id'] ?>" class="text-truncate d-block" style="max-width:100%;"><?= htmlspecialchars($p['name']) ?></a>
                            </h3>

                            <div class="p-price-row">
                                <div class="p-price">
                                    ₹<?= number_format($p['price'], 2) ?> <span>/ <?= htmlspecialchars($p['unit']) ?></span>
                                </div>
                            </div>
                            
                            <div class="mb-3 text-<?= $p['stock'] > 0 ? 'success' : 'danger' ?>" style="font-size: 13px; font-weight: 600;">
                                <i class="bi <?= $p['stock'] > 0 ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i> 
                                <?= $p['stock'] > 0 ? $p['stock'] . ' ' . $p['unit'] . ' Available' : 'Out of Stock' ?>
                            </div>

                            <a href="product_details.php?id=<?= $p['id'] ?>" class="btn-details">
                                View Product
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center text-muted py-5 w-100">
                <i class="bi bi-inbox text-muted mb-3 d-block" style="font-size: 4rem; opacity: 0.3;"></i>
                <h5>This farmer currently has no active products.</h5>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});
</script>

<?php require_once("includes/footer.php"); ?>