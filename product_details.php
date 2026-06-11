<?php
require_once("includes/config.php");

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id === 0) {
    header("Location: products.php"); // Redirect if no product ID is provided
    exit;
}

// Fetch product details
$product = null;
$stmt = $conn->prepare("
    SELECT p.*, f.name as farmer_name, f.id as farmer_profile_id, u.id as farmer_user_id
    FROM products p
    JOIN farmers f ON p.farmer_id = f.id
    JOIN users u ON f.user_id = u.id
    WHERE p.id = ? AND p.status = 'active'
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
}
$stmt->close();

if (!$product) {
    // Product not found or not active
    $page_title = "Product Not Found - FarmMart";
    require_once("includes/header.php");
    require_once("includes/navbar.php");
    ?>
    <main class="container py-5">
        <div class="alert alert-danger text-center">
            <h4 class="alert-heading">Product Not Found!</h4>
            <p>The product you are looking for does not exist or is no longer available.</p>
            <a href="products.php" class="btn btn-farmmart mt-3">Browse All Products</a>
        </div>
    </main>
    <?php
    require_once("includes/footer.php");
    exit;
}

$page_title = htmlspecialchars($product['name']) . " - FarmMart";
require_once("includes/header.php");
require_once("includes/navbar.php");
?>

<main class="container py-5">
    <div class="row g-5">
        <div class="col-lg-6">
            <div class="product-details-image-wrapper shadow-sm rounded-4 overflow-hidden">
                <img src="<?= $product['image'] ? BASE_URL . 'uploads/products/' . htmlspecialchars($product['image']) : 'https://via.placeholder.com/600x400' ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>" 
                     class="img-fluid w-100" style="object-fit: cover; height: 450px;">
            </div>
        </div>
        <div class="col-lg-6">
            <h1 class="fw-bold mb-3"><?= htmlspecialchars($product['name']) ?></h1>
            <p class="lead text-primary-theme fw-bold fs-3 mb-3">
                ₹<?= number_format($product['price'], 2) ?><small class="text-muted fs-5">/<?= htmlspecialchars($product['unit']) ?></small>
            </p>
            
            <p class="text-muted mb-4"><?= htmlspecialchars($product['description']) ?></p>

            <div class="mb-4">
                <p class="mb-1"><i class="bi bi-person-fill text-success me-2"></i> <strong>Farmer:</strong> <a href="farmer_profile.php?id=<?= $product['farmer_profile_id'] ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($product['farmer_name']) ?></a></p>
                <?php if ($product['is_organic']): ?>
                    <p class="mb-1"><i class="bi bi-leaf-fill text-success me-2"></i> <span class="badge bg-success">Organic Product</span></p>
                <?php endif; ?>
                <?php if ($product['stock'] > 0): ?>
                    <p class="mb-1 text-success"><i class="bi bi-check-circle-fill me-2"></i> In Stock: <?= $product['stock'] ?> <?= htmlspecialchars($product['unit']) ?></p>
                <?php else: ?>
                    <p class="mb-1 text-danger"><i class="bi bi-x-circle-fill me-2"></i> Out of Stock</p>
                <?php endif; ?>
            </div>

           
                
            </div>
        </div>
    </div>
</main>

<?php
require_once("includes/add_to_cart_modal.php"); // Include the reusable modal
require_once("includes/footer.php");
?>