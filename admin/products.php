<?php

require_once("../includes/auth.php");

if($_SESSION['role']!="admin")
{
header("Location: ../login.php");
exit();

}
require_once("../includes/config.php");

// Protect the page: Ensure the user is logged in AND is an admin

// Get filter parameters
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Build where clause
$where = "1=1";
if ($category > 0) {
    $where .= " AND p.category_id = $category";
}
if ($search) {
    $search_escaped = $conn->real_escape_string($search);
    $where .= " AND (p.name LIKE '%$search_escaped%' OR f.name LIKE '%$search_escaped%')";
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Get total count
$count_query = "SELECT COUNT(*) as total FROM products p JOIN farmers f ON p.farmer_id = f.id WHERE $where";
$count_result = $conn->query($count_query);
$total_products = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $limit);

// Get products
$products_query = "SELECT p.*, f.name as farmer_name, c.name as category_name 
    FROM products p 
    JOIN farmers f ON p.farmer_id = f.id 
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE $where 
    ORDER BY p.created_at DESC 
    LIMIT $limit OFFSET $offset";
$products_result = $conn->query($products_query);

// Get all categories for filter
$cats = $conn->query("SELECT * FROM categories ORDER BY name ASC");


$page_title = "Manage Products - Admin - FarmMart";
require_once("../includes/header.php");
?>
<link rel="stylesheet" href="assets/css/admin.css">
<?php
require_once("../includes/navbar.php");
?>

<main class="container-fluid py-4 px-md-5">
    <div class="row g-4">
        
        <!-- Include Admin Sidebar -->
        <div class="col-lg-3">
            <?php require_once("../includes/admin_sidebar.php"); ?>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="pagetitle mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h1>Manage Products</h1>
                    <nav>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Products</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <section class="section">
                <div class="row">
                    <div class="col-12">
                        <!-- Filters -->
                        <div class="card admin-form-card mb-4">
                            <div class="card-body p-3">
                                <form method="GET" class="row g-3 align-items-end">
                                    <div class="col-md-5">
                                        <label class="admin-form-label mb-1">Search</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Product or farmer name..." value="<?= $search ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="admin-form-label mb-1">Filter by Category</label>
                                        <select name="category" class="form-select">
                                            <option value="0">All Categories</option>
                                            <?php while($c = $cats->fetch_assoc()): ?>
                                                <option value="<?= $c['id'] ?>" <?= $category == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-admin-primary w-100">
                                            <i class="bi bi-funnel me-1"></i> Filter
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card admin-table-card">
                            <div class="card-body">
                                <h5 class="card-title">Product Catalog (<?= $total_products ?>)</h5>
                                <div class="table-responsive">
                                    <table class="table admin-table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th class="ps-4">Product</th>
                                                <th>Category</th>
                                                <th>Farmer / Vendor</th>
                                                <th>Price & Stock</th>
                                                <th>Status</th>
                                                <th class="pe-4 text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($products_result->num_rows > 0): ?>
                                                <?php while($product = $products_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?= !empty($product['image']) ? BASE_URL . 'uploads/products/' . htmlspecialchars($product['image']) : 'https://via.placeholder.com/40' ?>" 
                                                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                                                 style="width:45px;height:45px;object-fit:cover" class="rounded shadow-sm me-3 border border-light">
                                                            <div>
                                                                <span class="fw-bold d-block" style="color: var(--admin-dark-blue);"><?= htmlspecialchars($product['name']) ?></span>
                                                                <?php if($product['is_organic']): ?>
                                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success" style="font-size: 10px;">Organic <i class="bi bi-patch-check-fill"></i></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></span></td>
                                                    <td>
                                                        <a href="manage_farmers.php?search=<?= urlencode($product['farmer_name']) ?>" class="text-decoration-none fw-semibold" style="color: var(--admin-primary);">
                                                            <?= htmlspecialchars($product['farmer_name']) ?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold text-success">₹<?= number_format($product['price'], 2) ?> <span class="text-muted small fw-normal">/ <?= htmlspecialchars($product['unit']) ?></span></div>
                                                        <div class="small <?= $product['stock'] > 10 ? 'text-muted' : 'text-danger fw-bold' ?>">Stock: <?= $product['stock'] ?></div>
                                                    </td>
                                                    <td>
                                                        <?php
                                                            $status = strtolower($product['status'] ?? 'active');
                                                            $badgeClass = $status == 'active' ? 'badge-approved' : 'badge-rejected';
                                                        ?>
                                                        <span class="admin-badge <?= $badgeClass ?>">
                                                            <?= ucfirst($status) ?>
                                                        </span>
                                                    </td>
                                                    <td class="pe-4 text-end">
                                                        <a href="../product_details.php?id=<?= $product['id'] ?>" target="_blank" class="btn btn-sm btn-admin-outline-secondary" title="View Public Page">
                                                            <i class="bi bi-box-arrow-up-right"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center py-5 text-muted">
                                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                                        No products found.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                <div class="d-flex justify-content-center mt-4 pt-3 border-top">
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination">
                                            <!-- Previous button -->
                                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= $category ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                            
                                            <!-- Page numbers -->
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $category ?>"><?= $i ?></a>
                                            </li>
                                            <?php endfor; ?>
                                            
                                            <!-- Next button -->
                                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= $category ?>" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>
<?php require_once("../includes/footer.php"); ?>