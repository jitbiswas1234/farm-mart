<?php

require_once("../../includes/auth.php");
require_once("../../includes/config.php");

if($_SESSION['role']!="farmer")
{
header("Location: ../../login.php");
exit();
}

$page_title="Vendor Dashboard - FarmMart";

// Note: For the dashboard we might want to include specific niceadmin styles
// But keeping existing header for layout consistency unless requested otherwise.
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

/* NiceAdmin Dashboard Cards */
.info-card {
  padding-bottom: 10px;
}

.info-card .card {
  border: none;
  border-radius: 5px;
  box-shadow: var(--card-shadow);
  margin-bottom: 30px;
}

.info-card h6 {
  font-size: 28px;
  color: #012970;
  font-weight: 700;
  margin: 0;
  padding: 0;
}

.card-icon {
  font-size: 32px;
  line-height: 0;
  width: 64px;
  height: 64px;
  flex-shrink: 0;
  flex-grow: 0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sales-card .card-icon {
  color: #4154f1;
  background: #f6f6fe;
}

.revenue-card .card-icon {
  color: #2eca6a;
  background: #e0f8e9;
}

.card-title {
  padding: 20px 0 15px 0;
  font-size: 18px;
  font-weight: 500;
  color: #012970;
  font-family: "Poppins", sans-serif;
}
.card-title span {
  color: #899bbd;
  font-size: 14px;
  font-weight: 400;
}
.card-body {
  padding: 0 20px 20px 20px;
}
</style>

<?php require_once("../../includes/navbar.php"); ?>

<?php
// GET FARMER ID FROM USER

$user_id=$_SESSION['user_id'];

$farmer_query="SELECT id FROM farmers 
WHERE user_id='$user_id' 
LIMIT 1";

$farmer_result=mysqli_query($conn,$farmer_query);

$farmer=mysqli_fetch_assoc($farmer_result);

$farmer_id=$farmer['id'] ?? 0;

// TOTAL PRODUCTS
$total_products=0;
$p_query="SELECT COUNT(id) as total 
FROM products 
WHERE farmer_id='$farmer_id'";

$p_res=mysqli_query($conn,$p_query);
if($p_res)
{
$row=mysqli_fetch_assoc($p_res);
$total_products=$row['total'];
}

// TOTAL SALES
$total_sales=0;
$s_query="
SELECT SUM(oi.price * oi.quantity) as total
FROM order_items oi
JOIN products p 
ON oi.product_id=p.id
JOIN orders o 
ON oi.order_id=o.id
WHERE p.farmer_id='$farmer_id'
AND o.status!='cancelled'
";

$s_res=mysqli_query($conn,$s_query);

if($s_res)
{
$row=mysqli_fetch_assoc($s_res);
$total_sales=$row['total'] ?? 0;
}
?>

<main class="container-fluid py-4 px-4">
    <div class="row g-4">
        
        <!-- Sidebar container overridden with niceadmin style class -->
        <div class="col-lg-3 dashboard-sidebar">
            <?php require_once("../sidebar.php"); ?>
        </div>

        <div class="col-lg-9">
            <div class="pagetitle mb-4">
              <h1 class="fw-bold" style="color: #012970; font-size: 24px;">Dashboard</h1>
              <nav>
                <ol class="breadcrumb" style="background: transparent; padding: 0;">
                  <li class="breadcrumb-item"><a href="dashboard.php" style="color: #899bbd; text-decoration: none;">Home</a></li>
                  <li class="breadcrumb-item active" style="color: #444444; font-weight: 600;">Dashboard</li>
                </ol>
              </nav>
            </div><!-- End Page Title -->

            <section class="section dashboard">
                <div class="row">
                    
                    <!-- Sales Card -->
                    <div class="col-xxl-4 col-md-6 info-card sales-card">
                        <div class="card info-card">
                            <div class="card-body">
                                <h5 class="card-title">Products <span>| Total</span></h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bi bi-cart"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6><?= $total_products ?></h6>
                                        <span class="text-success small pt-1 fw-bold">Active</span> <span class="text-muted small pt-2 ps-1">Items</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- End Sales Card -->

                    <!-- Revenue Card -->
                    <div class="col-xxl-4 col-md-6 info-card revenue-card">
                        <div class="card info-card">
                            <div class="card-body">
                                <h5 class="card-title">Revenue <span>| Total</span></h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bi bi-currency-rupee"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6>₹<?= number_format($total_sales, 2) ?></h6>
                                        <span class="text-success small pt-1 fw-bold">8%</span> <span class="text-muted small pt-2 ps-1">increase</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- End Revenue Card -->

                    <div class="col-12 mt-4">
                        <div class="card text-center py-5 border-0" style="box-shadow: var(--card-shadow); border-radius: 5px;">
                            <div class="card-body">
                                <h5 class="card-title pb-0" style="font-size: 20px;">Ready to sell more?</h5>
                                <p class="text-muted">Expand your catalog and reach more customers.</p>
                                <a href="add_product.php" class="btn btn-primary mt-2 px-4 py-2" style="background-color: #4154f1; border: none;">
                                    <i class="bi bi-plus-circle me-1"></i> Add Product
                                </a>
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
        // Find the sidebar container and remove Bootstrap card styles to match NiceAdmin better
        let sidebarCard = document.querySelector('.dashboard-sidebar .card');
        if(sidebarCard) {
            sidebarCard.style.boxShadow = "none";
            sidebarCard.style.backgroundColor = "transparent";
            
            // Re-style links
            let links = sidebarCard.querySelectorAll('.list-group-item');
            links.forEach(link => {
                link.classList.remove('active', 'bg-success', 'text-white'); // Remove existing farmmart active classes
                if(link.getAttribute('href').includes('dashboard.php')) {
                    link.classList.add('active'); // Add niceadmin active class
                }
            });
        }
    });
</script>

<?php require_once("../../includes/footer.php"); ?>