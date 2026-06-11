<?php
session_start();
require_once("includes/config.php");

// --- AJAX ADD TO CART LOGIC (Keep this at the very top) ---
if (isset($_POST['add_to_cart'])) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'not_authorized', 'message' => 'Please login first.', 'redirect_url' => 'login.php']);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $quantity = floatval($_POST['quantity']);
    $unit = $_POST['unit'];
    if ($unit == "g") { $quantity = $quantity / 1000; }

    // Check Stock
    $stmt = $conn->prepare("SELECT stock FROM products WHERE id=?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if ($quantity > $product['stock']) {
        echo json_encode(['status' => 'error', 'message' => 'Only ' . $product['stock'] . 'kg available.']);
        exit();
    }

    // Update or Insert Cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id=? AND product_id=?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $new_qty = $row['quantity'] + $quantity;
        $update = $conn->prepare("UPDATE cart SET quantity=? WHERE id=?");
        $update->bind_param("di", $new_qty, $row['id']);
        $update->execute();
    } else {
        $insert = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES(?,?,?)");
        $insert->bind_param("iid", $user_id, $product_id, $quantity);
        $insert->execute();
    }

    // Get New Count
    $stmt_count = $conn->prepare("SELECT SUM(quantity) total_qty FROM cart WHERE user_id=?");
    $stmt_count->bind_param("i", $user_id);
    $stmt_count->execute();
    $cart_item_count = $stmt_count->get_result()->fetch_assoc()['total_qty'] ?? 0;

    echo json_encode(['status' => 'success', 'total_cart_items' => $cart_item_count]);
    exit();
}

// --- FETCH DATA ---
$page_title = "Fresh Products - FarmMart";
require_once("includes/header.php");

$query = "
    SELECT p.*, f.name as farmer_name, c.name as category_name 
    FROM products p 
    JOIN farmers f ON p.farmer_id=f.id 
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status='active' 
    ORDER BY p.created_at DESC
";
$res = mysqli_query($conn, $query);
$products = mysqli_fetch_all($res, MYSQLI_ASSOC);
require_once("includes/navbar.php");
?>

<style>
/* Modern Premium Catalog Styling to Match Index */
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

/* Products Hero Banner */
.products-hero {
    min-height: 45vh;
    background: linear-gradient(rgba(17, 24, 39, 0.8), rgba(17, 24, 39, 0.6)), 
                url('https://images.unsplash.com/photo-1488459716781-31db52582fe9?auto=format&fit=crop&w=1920&q=80') no-repeat center center;
    background-size: cover;
    background-attachment: fixed;
    position: relative;
    display: flex;
    align-items: center;
}

.hero-title {
    font-size: 55px;
    font-weight: 800;
    color: #fff;
    margin-bottom: 20px;
}
.hero-title span {
    color: #34d399; /* Light emerald accent */
}

/* Category Filter Buttons */
.filter-bar {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
    margin-top: -40px;
    position: relative;
    z-index: 10;
}

.btn-filter {
    border: 1.5px solid #e5e7eb;
    background: transparent;
    color: #6b7280;
    font-weight: 600;
    padding: 8px 20px;
    border-radius: 30px;
    transition: var(--transition);
    font-size: 14px;
}
.btn-filter:hover, .btn-filter.active {
    background: var(--theme-primary);
    border-color: var(--theme-primary);
    color: #fff;
}

/* Premium Product Cards (Mirroring Index style) */
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
.p-tag-organic { background: #10b981; color: #fff; }
.p-tag-category { background: rgba(255,255,255,0.9); color: var(--theme-dark); }

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
  display: flex;
  flex-direction: column;
  flex: 1;
}

.p-vendor {
  font-size: 12px;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
  display: block;
}
.p-vendor a {
    color: inherit;
    text-decoration: none;
    transition: var(--transition);
}
.p-vendor a:hover {
    color: var(--theme-primary);
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

/* Quick Add to Cart Tools */
.cart-tools {
    margin-top: auto;
}
.qty-group {
    background: #f3f4f6;
    border-radius: 12px;
    padding: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}
.qty-group button {
    background: #fff;
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    transition: 0.2s;
}
.qty-group button:hover {
    background: #e5e7eb;
}
.qty-input-wrap {
    display: flex;
    align-items: center;
}
.qty-input {
    background: transparent;
    border: none;
    width: 45px;
    font-weight: 700;
    text-align: center;
    font-size: 14px;
}
.qty-input:focus { outline: none; }
.unit-select {
    border: none;
    background: transparent;
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
}
.unit-select:focus { outline: none; }

.btn-add-cart {
    background: var(--theme-primary);
    color: #fff;
    border: none;
    padding: 12px;
    border-radius: 12px;
    font-weight: 600;
    transition: var(--transition);
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
}
.btn-add-cart:hover {
    background: var(--theme-secondary);
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(4, 120, 87, 0.2);
}

/* Stock Status */
.stock-status {
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Animations */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<!-- Products Hero Banner -->
<section class="products-hero">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-8" data-aos="fade-up">
                <h1 class="hero-title">
                    Discover Fresh <br><span>Local Produce</span>
                </h1>
                <p class="lead text-white opacity-75 mb-0" style="font-weight: 300;">
                    Browse our curated selection of farm-fresh fruits, vegetables, and organic products 
                    directly from verified local farmers.
                </p>
            </div>
        </div>
    </div>
</section>

<div class="container pb-5">
    
    <!-- Category Filter Bar -->
    <div class="row mb-5 justify-content-center">
        <div class="col-xl-10">
            <div class="filter-bar d-flex flex-wrap gap-3 justify-content-center align-items-center" data-aos="fade-up">
                <span class="fw-bold text-dark me-2">Shop By:</span>
                <button class="btn-filter active" onclick="filterCategory('all')">All Harvest</button>
                <button class="btn-filter" onclick="filterCategory('organic')"><i class="bi bi-patch-check-fill text-success"></i> Organic Only</button>
                <?php
                // Get unique categories present in the products to build the filter buttons dynamically
                $unique_categories = [];
                foreach($products as $p) {
                    if(!empty($p['category_name']) && !in_array($p['category_name'], $unique_categories)) {
                        $unique_categories[] = $p['category_name'];
                    }
                }
                foreach($unique_categories as $cat):
                ?>
                    <button class="btn-filter" onclick="filterCategory('<?= htmlspecialchars($cat) ?>')"><?= htmlspecialchars($cat) ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="row g-4" id="productsGrid">
        <?php if(count($products) > 0): ?>
            <?php foreach ($products as $index => $p): 
                $category_attr = htmlspecialchars($p['category_name'] ?? 'uncategorized');
                $is_organic_attr = $p['is_organic'] ? 'organic' : '';
            ?>
                <div class="col-xl-3 col-lg-4 col-sm-6 product-item" 
                     data-category="<?= $category_attr ?>" 
                     data-organic="<?= $is_organic_attr ?>"
                     data-aos="fade-up" 
                     data-aos-delay="<?= ($index % 8) * 50 ?>">
                    
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
                            <span class="p-vendor">
                                <a href="farmer_profile.php?id=<?= $p['farmer_id'] ?>"><i class="bi bi-shop me-1"></i> <?= htmlspecialchars($p['farmer_name']) ?></a>
                            </span>
                            
                            <h3 class="p-title">
                                <a href="product_details.php?id=<?= $p['id'] ?>" class="text-truncate d-block" style="max-width:100%;"><?= htmlspecialchars($p['name']) ?></a>
                            </h3>

                            <div class="p-price-row">
                                <div class="p-price">
                                    ₹<?= number_format($p['price'], 2) ?> <span>/ <?= htmlspecialchars($p['unit']) ?></span>
                                </div>
                                <div class="p-rating" data-bs-toggle="tooltip" title="Rating: <?= number_format($p['rating'] ?? 0, 1) ?>">
                                    <i class="bi bi-star-fill"></i>
                                    <span class="p-rating-count">(<?= number_format($p['rating'] ?? 0, 1) ?>)</span>
                                </div>
                            </div>

                            <div class="stock-status text-<?= $p['stock'] > 0 ? 'success' : 'danger' ?>">
                                <i class="bi <?= $p['stock'] > 0 ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i> 
                                <?= $p['stock'] > 0 ? $p['stock'] . ' ' . $p['unit'] . ' Available' : 'Out of Stock' ?>
                            </div>

                            <div class="cart-tools mt-auto">
                                <?php if($p['stock'] > 0): ?>
                                    <div class="qty-group">
                                        <button type="button" onclick="qty(<?= $p['id'] ?>,-0.25)"><i class="bi bi-dash"></i></button>
                                        <div class="qty-input-wrap">
                                            <input id="qty<?= $p['id'] ?>" value="1" step="0.25" min="0.25" max="<?= $p['stock'] ?>" type="number" class="qty-input">
                                            <select id="unit<?= $p['id'] ?>" class="unit-select">
                                                <option value="kg">kg</option>
                                                <option value="g">g</option>
                                            </select>
                                        </div>
                                        <button type="button" onclick="qty(<?= $p['id'] ?>,0.25)"><i class="bi bi-plus"></i></button>
                                    </div>
                                    <button onclick="addCart(<?= $p['id'] ?>)" class="btn-add-cart">
                                        <i class="bi bi-bag-plus"></i> Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button class="btn-add-cart" style="background:#9ca3af; cursor:not-allowed;" disabled>
                                        <i class="bi bi-x-circle"></i> Out of Stock
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-basket2 text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                <h5 class="mt-3 text-muted">No products available at the moment.</h5>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Cart Success Modal -->
<div class="modal fade" id="cartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 24px;">
            <div class="modal-body text-center p-5">
                <div class="mb-4" style="font-size: 4rem; color: var(--theme-primary);">
                   <i class="bi bi-check2-circle"></i>
                </div>
                <h3 class="fw-bold mb-3" style="color: var(--theme-dark);">Added to Cart!</h3>
                <p class="text-muted mb-4">Your selected farm fresh product has been added to your cart.</p>
                <div class="d-flex gap-3 justify-content-center">
                    <button class="btn btn-light px-4 py-2 fw-bold" data-bs-dismiss="modal" style="border-radius: 50px;">Continue Shopping</button>
                    <a href="user/includes/cart.php" class="btn text-white px-4 py-2 fw-bold" style="background: var(--theme-primary); border-radius: 50px;">Go to Cart</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function qty(id, val) {
        let q = document.getElementById("qty" + id);
        let v = parseFloat(q.value);
        let max = parseFloat(q.getAttribute('max'));
        v += val;
        if (v < 0.25) v = 0.25;
        if (v > max) v = max;
        q.value = v.toFixed(2);
    }

    function addCart(id) {
        let qty = document.getElementById("qty" + id).value;
        let unit = document.getElementById("unit" + id).value;

        fetch("products.php", {
            method: "POST",
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `add_to_cart=1&product_id=${id}&quantity=${qty}&unit=${unit}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.status == "success") {
                let modal = new bootstrap.Modal(document.getElementById('cartModal'));
                modal.show();
                const cartBadge = document.getElementById('cartBadge'); // Ensure this ID exists in navbar.php
                if (cartBadge && data.total_cart_items !== undefined) {
                    cartBadge.textContent = data.total_cart_items;
                }
            } else if (data.status == "not_authorized") {
                window.location.href = data.redirect_url;
            } else {
                alert(data.message);
            }
        });
    }

    // Advanced Category Filtering
    function filterCategory(category) {
        const products = document.querySelectorAll('.product-item');
        const buttons = document.querySelectorAll('.btn-filter');

        // Update active button state
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');

        // Filter products with animation
        products.forEach(product => {
            product.style.animation = 'none'; // reset animation
            
            const cat = product.getAttribute('data-category');
            const isOrg = product.getAttribute('data-organic');

            if (category === 'all') {
                product.style.display = 'block';
                setTimeout(() => product.style.animation = 'fadeInUp 0.5s ease-out forwards', 10);
            } 
            else if (category === 'organic') {
                if (isOrg === 'organic') {
                    product.style.display = 'block';
                    setTimeout(() => product.style.animation = 'fadeInUp 0.5s ease-out forwards', 10);
                } else {
                    product.style.display = 'none';
                }
            }
            else {
                if (cat === category) {
                    product.style.display = 'block';
                    setTimeout(() => product.style.animation = 'fadeInUp 0.5s ease-out forwards', 10);
                } else {
                    product.style.display = 'none';
                }
            }
        });
    }

    // Initialize tooltips
    document.addEventListener("DOMContentLoaded", function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>

<?php require_once("includes/footer.php"); ?>