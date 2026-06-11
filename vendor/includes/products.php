<?php

require_once("../../includes/auth.php");
require_once("../../includes/config.php");

if($_SESSION['role']!="farmer")
{
header("Location: ../../login.php");
exit();
}

$page_title="My Products - Vendor - FarmMart";

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
.table thead th {
  border-bottom: 2px solid #e1e6f1;
  color: #012970;
  font-weight: 600;
  padding: 15px;
  background-color: #f6f9ff;
}
.table tbody td {
  padding: 15px;
  vertical-align: middle;
}
.pagination {
    margin-bottom: 0;
}
.page-link {
    color: #4154f1;
}
.page-item.active .page-link {
    background-color: #4154f1;
    border-color: #4154f1;
}
</style>

<?php require_once("../../includes/navbar.php"); ?>

<?php
// GET FARMER ID FROM USER ID

$user_id=$_SESSION['user_id'];

$farmer_query="SELECT id FROM farmers WHERE user_id='$user_id' LIMIT 1";

$farmer_result=mysqli_query($conn,$farmer_query);

$farmer=mysqli_fetch_assoc($farmer_result);

$farmer_id=$farmer['id'] ?? 0;


// PAGINATION SETUP
$limit = 10; // Number of products per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// GET TOTAL COUNT
$count_query = "SELECT COUNT(*) as total FROM products WHERE farmer_id='$farmer_id'";
$count_res = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / $limit);

// GET PRODUCTS WITH LIMIT AND OFFSET
$products=[];

$product_query="SELECT * FROM products 
WHERE farmer_id='$farmer_id'
ORDER BY created_at DESC
LIMIT $limit OFFSET $offset";

$res=mysqli_query($conn,$product_query);

if($res)
{
$products=mysqli_fetch_all($res,MYSQLI_ASSOC);
}

?>

<main class="container-fluid py-4 px-4">
    <div class="row g-4">
        
        <!-- Sidebar container overridden with niceadmin style class -->
        <div class="col-lg-3 dashboard-sidebar">
            <?php require_once("../sidebar.php"); ?>
        </div>

        <div class="col-lg-9">
            <div class="pagetitle mb-4 d-flex justify-content-between align-items-center">
              <div>
                  <h1 class="fw-bold" style="color: #012970; font-size: 24px;">My Products</h1>
                  <nav>
                    <ol class="breadcrumb" style="background: transparent; padding: 0;">
                      <li class="breadcrumb-item"><a href="dashboard.php" style="color: #899bbd; text-decoration: none;">Home</a></li>
                      <li class="breadcrumb-item active" style="color: #444444; font-weight: 600;">Products</li>
                    </ol>
                  </nav>
              </div>
              <a href="add_product.php" class="btn btn-primary px-4 py-2" style="background-color: #4154f1; border: none;">
                  <i class="bi bi-plus-circle me-1"></i> Add Product
              </a>
            </div><!-- End Page Title -->

            <section class="section">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Product Catalog</h5>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th class="ps-4">Product</th>
                                                <th>Price</th>
                                                <th>Stock</th>
                                                <th>Status</th>
                                                <th class="text-end pe-4">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($products)>0): ?>
                                                <?php foreach($products as $p): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?= !empty($p['image']) ? '../../uploads/products/'.htmlspecialchars($p['image']) : 'https://via.placeholder.com/40'; ?>" style="width:40px;height:40px;object-fit:cover" class="rounded me-3 shadow-sm">
                                                            <span class="fw-bold" style="color: #012970;"><?= htmlspecialchars($p['name']) ?></span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        ₹<?= number_format($p['price'],2) ?> / <span class="text-muted small"><?= htmlspecialchars($p['unit']) ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold"><?= $p['stock'] ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge" style="background-color: #2eca6a; font-size: 13px;">
                                                            <?= htmlspecialchars($p['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <a href="delete_product.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete product?')">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-5 text-muted">
                                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                                        No products found
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                <div class="d-flex justify-content-center mt-4 border-top pt-3">
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination">
                                            <!-- Previous button -->
                                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                            
                                            <!-- Page numbers -->
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                            </li>
                                            <?php endfor; ?>
                                            
                                            <!-- Next button -->
                                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                                <?php endif; ?>
                                <!-- End Pagination -->

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