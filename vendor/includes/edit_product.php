<?php
require_once("../../includes/auth.php");
require_once("../../includes/config.php");

// 1. Initial Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: ../../login.php");
    exit();
}

// --- Start Image Optimization Function ---
function optimizeImage($sourcePath, $targetPath, $mimeType, $maxFileSize) {
    list($width, $height) = getimagesize($sourcePath);
    $image = null;

    switch ($mimeType) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($sourcePath);
            imagealphablending($image, false);
            imagesavealpha($image, true);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }

    if (!$image) return false;

    $quality = 90;
    $scale = 1.0;
    $tempOptimizedPath = tempnam(sys_get_temp_dir(), 'optimg');

    while (true) {
        $currentImage = $image;
        if ($scale < 1.0) {
            $newWidth = (int)($width * $scale);
            $newHeight = (int)($height * $scale);
            $currentImage = imagescale($image, $newWidth, $newHeight);
            if ($mimeType === 'image/png') {
                imagealphablending($currentImage, false);
                imagesavealpha($currentImage, true);
            }
        }

        switch ($mimeType) {
            case 'image/jpeg': imagejpeg($currentImage, $tempOptimizedPath, $quality); break;
            case 'image/png': 
                $pngComp = round((100 - $quality) / 100 * 9);
                imagepng($currentImage, $tempOptimizedPath, $pngComp); 
                break;
            case 'image/gif': imagegif($currentImage, $tempOptimizedPath); break;
        }

        $currentSize = filesize($tempOptimizedPath);

        if ($currentSize <= $maxFileSize) {
            $success = rename($tempOptimizedPath, $targetPath);
            imagedestroy($image);
            if ($currentImage !== $image) imagedestroy($currentImage);
            return $success;
        }

        if ($mimeType === 'image/jpeg') {
            $quality -= 10;
            if ($quality < 10) { $scale -= 0.1; $quality = 90; }
        } else {
            $scale -= 0.1;
        }

        if ($scale < 0.1) {
            unlink($tempOptimizedPath);
            imagedestroy($image);
            if ($currentImage !== $image) imagedestroy($currentImage);
            return false;
        }
        if ($currentImage !== $image) imagedestroy($currentImage);
    }
}
// --- End Image Optimization Function ---

$user_id = $_SESSION['user_id'];
$page_title = "Edit Product - Vendor - FarmMart";

// 2. Fetch Farmer ID
$farm_query = "SELECT id FROM farmers WHERE user_id='$user_id' LIMIT 1";
$farm_res = mysqli_query($conn, $farm_query);
$farm = mysqli_fetch_assoc($farm_res);
$farmer_id = $farm['id'] ?? 0;

if ($farmer_id === 0) {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg = "";
$msgType = "";

// 3. Fetch Existing Product Data
$product = null;
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND farmer_id = ?");
$stmt->bind_param("ii", $product_id, $farmer_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $product = $result->fetch_assoc();
}
$stmt->close();

if (!$product) {
    die("Product not found or unauthorized.");
}

// 4. Handle Image Deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_image'])) {
    if (!empty($product['image'])) {
        $imagePath = UPLOAD_PATH . 'products/' . $product['image'];
        if (file_exists($imagePath)) unlink($imagePath);

        $upd = $conn->prepare("UPDATE products SET image = NULL WHERE id = ?");
        $upd->bind_param("i", $product_id);
        $upd->execute();
        $product['image'] = null;
        $msg = "Image deleted successfully!";
        $msgType = "success";
    }
}

// 5. Handle Update Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $unit = trim($_POST['unit']);
    $description = trim($_POST['description']);
    $is_organic = isset($_POST['is_organic']) ? 1 : 0;
    $image = $product['image']; 
    $upload_error = false;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($file_ext, $allowed)) {
            $msg = "Invalid file type.";
            $msgType = "danger";
            $upload_error = true;
        } else {
            $new_name = time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $target_path = UPLOAD_PATH . 'products/' . $new_name;
            $max_bytes = 20 * 1024;

            if ($_FILES['image']['size'] <= $max_bytes) {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    if ($product['image']) @unlink(UPLOAD_PATH . 'products/' . $product['image']);
                    $image = $new_name;
                }
            } else {
                if (optimizeImage($_FILES['image']['tmp_name'], $target_path, $_FILES['image']['type'], $max_bytes)) {
                    if ($product['image']) @unlink(UPLOAD_PATH . 'products/' . $product['image']);
                    $image = $new_name;
                } else {
                    $msg = "Image too large and could not be optimized under 20KB.";
                    $msgType = "danger";
                    $upload_error = true;
                }
            }
        }
    }

    if (!$upload_error) {
        $upd = $conn->prepare("UPDATE products SET name=?, description=?, price=?, unit=?, stock=?, image=?, is_organic=? WHERE id=? AND farmer_id=?");
        $upd->bind_param("ssdsisiii", $name, $description, $price, $unit, $stock, $image, $is_organic, $product_id, $farmer_id);
        if ($upd->execute()) {
            $msg = "Product updated successfully!";
            $msgType = "success";
            // Refresh local data
            $product['name'] = $name; $product['price'] = $price; $product['image'] = $image;
        }
        $upd->close();
    }
}

require_once("../../includes/header.php");
?>
<!-- NiceAdmin Dashboard Styles & Layout Tweaks -->
<style>
/* Dashboard Base Colors & Setup inspired by NiceAdmin */
:root {
  --nav-bg: #fff;
  --nav-color: #012970;
  --card-shadow: 0px 0 30px rgba(1, 41, 112, 0.1);
  --sidebar-bg: #fff;
  --sidebar-active: #f6f9ff;
  --sidebar-active-color: #4154f1;
}

body {
  background: #f6f9ff;
  color: #444444;
  font-family: "Open Sans", sans-serif;
}

/* Override existing sidebar for this dashboard to match NiceAdmin */
.dashboard-sidebar .list-group-item {
    border: none;
    margin-bottom: 5px;
    border-radius: 4px;
    color: #012970;
    font-weight: 600;
    padding: 12px 15px;
    transition: 0.3s;
}

.dashboard-sidebar .list-group-item:hover,
.dashboard-sidebar .list-group-item.active {
    background-color: var(--sidebar-active);
    color: var(--sidebar-active-color);
}

.dashboard-sidebar .list-group-item i {
    font-size: 18px;
    margin-right: 10px;
    color: #899bbd;
}

.dashboard-sidebar .list-group-item.active i {
    color: var(--sidebar-active-color);
}

/* NiceAdmin Dashboard Cards & Tables */
.card {
  border: none;
  border-radius: 5px;
  box-shadow: var(--card-shadow);
  margin-bottom: 30px;
}
.card-title {
  padding: 20px 0 15px 0;
  font-size: 18px;
  font-weight: 500;
  color: #012970;
  font-family: "Poppins", sans-serif;
}
.form-label {
    font-weight: 600;
    color: #012970;
}
</style>

<?php require_once("../../includes/navbar.php"); ?>

<main class="container-fluid py-4 px-4">
    <div class="row g-4">
        
        <!-- Sidebar container overridden with niceadmin style class -->
        <div class="col-lg-3 dashboard-sidebar">
            <?php require_once("../sidebar.php"); ?>
        </div>

        <div class="col-lg-9">
            <div class="pagetitle mb-4 d-flex justify-content-between align-items-center">
              <div>
                  <h1 class="fw-bold" style="color: #012970; font-size: 24px;">Edit Product</h1>
                  <nav>
                    <ol class="breadcrumb" style="background: transparent; padding: 0;">
                      <li class="breadcrumb-item"><a href="dashboard.php" style="color: #899bbd; text-decoration: none;">Home</a></li>
                      <li class="breadcrumb-item"><a href="products.php" style="color: #899bbd; text-decoration: none;">Products</a></li>
                      <li class="breadcrumb-item active" style="color: #444444; font-weight: 600;">Edit Product</li>
                    </ol>
                  </nav>
              </div>
              <a href="products.php" class="btn btn-outline-secondary px-4 py-2">
                  <i class="bi bi-arrow-left me-1"></i> Back to Products
              </a>
            </div><!-- End Page Title -->

            <section class="section">
                <div class="row">
                    <div class="col-12">
                        <?php if($msg): ?>
                            <div class="alert alert-<?= $msgType ?> alert-dismissible fade show shadow-sm" role="alert">
                                <?= htmlspecialchars($msg) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="card">
                            <div class="card-body p-4">
                                <h5 class="card-title">Product Details</h5>

                                <form method="POST" enctype="multipart/form-data">
                                    <div class="row g-4">
                                        <div class="col-md-12">
                                            <label class="form-label">Product Name</label>
                                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($product['name']) ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Price</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" step="0.01" name="price" class="form-control" required value="<?= htmlspecialchars($product['price']) ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Unit</label>
                                            <select name="unit" class="form-select">
                                                <?php 
                                                $units = ['kg', 'gram', 'piece', 'dozen', 'liter'];
                                                foreach($units as $u) {
                                                    $sel = ($product['unit'] == $u) ? 'selected' : '';
                                                    echo "<option value='$u' $sel>Per $u</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Stock</label>
                                            <input type="number" name="stock" class="form-control" required value="<?= htmlspecialchars($product['stock']) ?>">
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description']) ?></textarea>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Product Image</label>
                                            <?php if($product['image']): ?>
                                                <div class="mb-3 d-flex align-items-center bg-light p-3 rounded border">
                                                    <img src="../../uploads/products/<?= htmlspecialchars($product['image']) ?>" class="rounded shadow-sm me-3" style="height: 100px; object-fit: cover; width: 100px;">
                                                    <div>
                                                        <h6 class="mb-2">Current Image</h6>
                                                        <button type="submit" name="delete_image" class="btn btn-outline-danger btn-sm">
                                                            <i class="bi bi-trash me-1"></i> Delete Image
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" name="image" class="form-control" accept="image/*">
                                            <small class="text-muted">Max 20KB. System will attempt to auto-shrink larger files.</small>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input" type="checkbox" name="is_organic" id="is_organic" value="1" <?= $product['is_organic'] ? 'checked' : '' ?>>
                                                <label class="form-check-label fw-bold text-success ms-2" for="is_organic">
                                                    Organic Product <i class="bi bi-patch-check-fill"></i>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end mt-4 pt-3 border-top">
                                        <button type="submit" name="update_product" class="btn btn-primary px-4 py-2" style="background-color: #4154f1; border: none;">
                                            <i class="bi bi-save me-1"></i> Update Product
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>

<!-- Adjust sidebar inner styles for NiceAdmin look -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let sidebarCard = document.querySelector('.dashboard-sidebar .card');
        if(sidebarCard) {
            sidebarCard.style.boxShadow = "none";
            sidebarCard.style.backgroundColor = "transparent";
            
            let links = sidebarCard.querySelectorAll('.list-group-item');
            links.forEach(link => {
                link.classList.remove('active', 'bg-success', 'text-white');
                if(link.getAttribute('href').includes('products.php')) {
                    link.classList.add('active');
                }
            });
        }
    });
</script>

<?php require_once("../../includes/footer.php"); ?>