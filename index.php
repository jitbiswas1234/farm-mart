<?php
$page_title = "FarmMart - Fresh Organic Food From Local Farmers";
include("includes/header.php");
include("includes/config.php");

// --- Fetch Top Rated Products ---
$top_products = [];
$query = "
    SELECT p.*, f.name as farmer_name 
    FROM products p 
    JOIN farmers f ON p.farmer_id = f.id 
    WHERE p.status = 'active' 
    ORDER BY p.rating DESC, p.created_at DESC
    LIMIT 8
";
$res = $conn->query($query);
if ($res) {
    $top_products = $res->fetch_all(MYSQLI_ASSOC);
}
include("includes/navbar.php");
?>

<style>
/* Modern Premium Index Styling */
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
  color: #4b5563;
  font-family: var(--font-body);
  font-size: 15px;
  line-height: 1.6;
  background-color: var(--theme-light);
}

h1, h2, h3, h4, h5, h6 {
  font-family: var(--font-heading);
  color: var(--theme-dark);
  font-weight: 700;
  letter-spacing: -0.02em;
}

/* ================= HERO SECTION ================= */
.hero-wrapper {
  position: relative;
  height: 85vh;
  min-height: 600px;
  overflow: hidden;
  background-color: var(--theme-dark);
}

.hero-slider-img {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background-image: url('https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1920&q=80');
  background-position: center 30%;
  background-size: cover;
  opacity: 0.6;
  z-index: 1;
}

.hero-content-box {
  position: relative;
  z-index: 2;
  height: 100%;
  display: flex;
  align-items: center;
}

.hero-badge {
  display: inline-block;
  background: rgba(4, 120, 87, 0.2);
  color: #a7f3d0;
  border: 1px solid rgba(167, 243, 208, 0.2);
  padding: 8px 16px;
  border-radius: 30px;
  font-size: 13px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  margin-bottom: 25px;
}

.hero-title {
  color: #ffffff;
  font-size: 65px;
  line-height: 1.1;
  font-weight: 800;
  margin-bottom: 25px;
  text-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.hero-title span {
  color: #34d399; /* Light green accent */
}

.hero-desc {
  color: #e5e7eb;
  font-size: 18px;
  max-width: 500px;
  margin-bottom: 35px;
  font-weight: 300;
}

/* Primary Button Design */
.btn-premium {
  background: var(--theme-primary);
  color: #fff;
  padding: 14px 36px;
  border-radius: 50px;
  font-family: var(--font-heading);
  font-weight: 600;
  font-size: 15px;
  letter-spacing: 0.5px;
  transition: var(--transition);
  display: inline-flex;
  align-items: center;
  gap: 10px;
  border: none;
  box-shadow: 0 10px 20px rgba(4, 120, 87, 0.3);
  text-decoration: none;
}

.btn-premium:hover {
  background: var(--theme-secondary);
  color: #fff;
  transform: translateY(-3px);
  box-shadow: 0 15px 25px rgba(4, 120, 87, 0.4);
}

/* ================= FEATURES SECTION ================= */
.features-section {
  padding: 80px 0;
  background: #ffffff;
  position: relative;
  z-index: 10;
  margin-top: -50px;
  box-shadow: 0 -10px 40px rgba(0,0,0,0.05);
  border-radius: 30px 30px 0 0;
}

.feature-card {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 20px;
  border-radius: 16px;
  transition: var(--transition);
  border: 1px solid transparent;
}

.feature-card:hover {
  background: var(--theme-light);
  border-color: #e5e7eb;
  transform: translateY(-5px);
}

.feature-icon-wrapper {
  width: 65px;
  height: 65px;
  border-radius: 20px;
  background: rgba(4, 120, 87, 0.1);
  color: var(--theme-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  flex-shrink: 0;
  transition: var(--transition);
}

.feature-card:hover .feature-icon-wrapper {
  background: var(--theme-primary);
  color: #fff;
}

.feature-card h5 {
  font-size: 17px;
  margin-bottom: 5px;
}
.feature-card p {
  margin: 0;
  font-size: 13px;
  color: #6b7280;
}

/* ================= SECTION HEADERS ================= */
.section-header {
  text-align: center;
  margin-bottom: 50px;
}
.section-header h2 {
  font-size: 36px;
  font-weight: 800;
  position: relative;
  display: inline-block;
  padding-bottom: 15px;
}
.section-header h2::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 60px;
  height: 4px;
  background: var(--theme-primary);
  border-radius: 2px;
}
.section-header p {
  color: #6b7280;
  margin-top: 15px;
}

/* ================= PREMIUM PRODUCT CARDS ================= */
.premium-product-section {
  padding: 60px 0 100px;
  background-color: var(--theme-light);
}

.premium-card {
  background: #fff;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0,0,0,0.03);
  transition: var(--transition);
  position: relative;
  height: 100%;
  border: 1px solid rgba(0,0,0,0.02);
}

.premium-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 20px 40px rgba(0,0,0,0.08);
}

.p-card-img-wrap {
  position: relative;
  height: 260px;
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

/* Tags */
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
.p-tag-hot { background: #ef4444; color: #fff; }
.p-tag-organic { background: #10b981; color: #fff; }

/* Quick Actions Overlay */
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
}

.p-vendor {
  font-size: 12px;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  display: block;
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

.p-rating {
  color: var(--theme-accent);
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 4px;
}
.p-rating-count {
  color: #9ca3af;
  font-size: 12px;
}

/* ================= CTA BANNER ================= */
.cta-banner {
  background: linear-gradient(to right, #064e3b, #047857);
  padding: 70px 0;
  border-radius: 30px;
  margin: 0 20px 80px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 20px 40px rgba(4, 120, 87, 0.2);
}

.cta-banner::after {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 50%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
  transform: rotate(30deg);
}

.cta-content {
  position: relative;
  z-index: 2;
  text-align: center;
  color: #fff;
}
.cta-content h2 {
  color: #fff;
  font-size: 36px;
  margin-bottom: 15px;
}
.cta-content p {
  color: #d1fae5;
  font-size: 16px;
  margin-bottom: 30px;
  max-width: 600px;
  margin-inline: auto;
}

.newsletter-form {
  max-width: 500px;
  margin: 0 auto;
  position: relative;
  display: flex;
}
.newsletter-form input {
  width: 100%;
  padding: 16px 24px;
  border-radius: 50px;
  border: none;
  font-size: 15px;
  box-shadow: 0 10px 20px rgba(0,0,0,0.1);
  outline: none;
}
.newsletter-form button {
  position: absolute;
  right: 5px;
  top: 5px;
  bottom: 5px;
  background: var(--theme-dark);
  color: #fff;
  border: none;
  padding: 0 25px;
  border-radius: 40px;
  font-weight: 600;
  transition: var(--transition);
}
.newsletter-form button:hover {
  background: #374151;
}

@media (max-width: 768px) {
  .hero-title { font-size: 42px; }
  .features-section { padding: 40px 0; border-radius: 20px 20px 0 0; }
  .feature-card { flex-direction: column; text-align: center; }
  .cta-banner { margin: 0 10px 50px; padding: 50px 20px; }
  .section-header h2 { font-size: 28px; }
}
</style>

<!-- HERO SECTION -->
<section class="hero-wrapper">
    <div class="hero-slider-img"></div>
    <div class="container hero-content-box">
        <div class="row w-100">
            <div class="col-xl-7 col-lg-8" data-aos="fade-right" data-aos-duration="1000">
                <span class="hero-badge"><i class="bi bi-patch-check-fill me-1"></i> Certified 100% Organic</span>
                <h1 class="hero-title">Healthy & Fresh <br><span>Farm Produce</span> directly to your door</h1>
                <p class="hero-desc">Skip the middlemen. Support local farmers and enjoy premium, freshly harvested organic groceries delivered safely to your kitchen.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="products.php" class="btn-premium">
                        Shop Collection <i class="bi bi-arrow-right"></i>
                    </a>
                    <a href="farmers.php" class="btn-premium" style="background: #fff; color: var(--theme-dark); box-shadow: 0 10px 20px rgba(0,0,0,0.05);">
                        Meet Our Farmers
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES SECTION -->
<section class="features-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3 col-sm-6">
                <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-icon-wrapper">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div>
                        <h5>Fast Delivery</h5>
                        <p>Direct from farm to your door within 24 hours.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-icon-wrapper">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <h5>100% Secure</h5>
                        <p>Safe payments and quality guarantee.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-icon-wrapper">
                        <i class="bi bi-flower1"></i>
                    </div>
                    <div>
                        <h5>Fresh Organic</h5>
                        <p>No chemicals, completely natural produce.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="feature-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-icon-wrapper">
                        <i class="bi bi-headset"></i>
                    </div>
                    <div>
                        <h5>24/7 Support</h5>
                        <p>Dedicated customer service available always.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- EXCLUSIVE PRODUCTS -->
<section class="premium-product-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>Featured Harvest</h2>
            <p>Discover this week's highest-rated organic products.</p>
        </div>
        
        <div class="row g-4">
            <?php if (count($top_products) > 0): ?>
                <?php foreach($top_products as $index => $p): ?>
                    <div class="col-xl-3 col-lg-4 col-sm-6" data-aos="fade-up" data-aos-delay="<?= ($index % 4) * 100 ?>">
                        <div class="premium-card">
                            
                            <div class="p-tags">
                                <?php if($p['rating'] >= 4.5): ?>
                                    <span class="p-tag-badge p-tag-hot">Bestseller</span>
                                <?php endif; ?>
                                <?php if($p['is_organic']): ?>
                                    <span class="p-tag-badge p-tag-organic">Organic</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="p-card-img-wrap">
                                <img src="<?= $p['image'] ? BASE_URL . 'uploads/products/' . htmlspecialchars($p['image']) : 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?auto=format&fit=crop&w=600&q=80' ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                                
                                <div class="p-action-overlay">
                                    <button class="p-action-btn" onclick="addToCart(<?= $p['id'] ?>)" data-bs-toggle="tooltip" title="Add To Cart">
                                        <i class="bi bi-bag-plus"></i>
                                    </button>
                                    <a href="product_details.php?id=<?= $p['id'] ?>" class="p-action-btn" data-bs-toggle="tooltip" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="p-card-body">
                                <span class="p-vendor"><i class="bi bi-shop me-1"></i> <?= htmlspecialchars($p['farmer_name']) ?></span>
                                <h3 class="p-title">
                                    <a href="product_details.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a>
                                </h3>
                                
                                <div class="p-price-row">
                                    <div class="p-price">
                                        ₹<?= number_format($p['price'], 2) ?> <span>/ <?= htmlspecialchars($p['unit']) ?></span>
                                    </div>
                                    
                                    <div class="p-rating" data-bs-toggle="tooltip" title="<?= number_format($p['rating'], 1) ?> out of 5 stars">
                                        <i class="bi bi-star-fill"></i>
                                        <span class="p-rating-count">(<?= number_format($p['rating'], 1) ?>)</span>
                                    </div>
                                </div>
                                
                                <!-- <button onclick="addToCart(<?= $p['id'] ?>)" class="btn btn-outline-success w-100 fw-bold" style="border-radius: 12px; padding: 10px;">
                                    Add to Cart
                                </button> -->
                            </div>
                            
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-basket2 text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                    <h5 class="mt-3 text-muted">Harvesting in progress... Check back soon!</h5>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="products.php" class="btn-premium" style="background: transparent; color: var(--theme-primary); border: 2px solid var(--theme-primary); box-shadow: none;">
                View All Products <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- CTA BANNER -->
<section>
    <div class="cta-banner" data-aos="zoom-in" data-aos-duration="800">
        <div class="cta-content container">
            <h2>Join the Organic Revolution</h2>
            <p>Subscribe to our newsletter to receive weekly harvest updates, exclusive discounts, and farm-to-table recipes.</p>
            
            <form action="register.php" method="GET" class="newsletter-form">
                <input type="email" placeholder="Enter your email address..." name="email" required>
                <button type="submit">Subscribe</button>
            </form>
        </div>
    </div>
</section>

<!-- Initialize Tooltips -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});
</script>

<?php require_once("includes/add_to_cart_modal.php"); ?>

<?php include("includes/footer.php"); ?>