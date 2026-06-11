<?php

require_once("../../includes/auth.php");
require_once("../../includes/config.php");

if($_SESSION['role']!="farmer")
{
header("Location: ../../login.php");
exit();
}

$page_title="Customer Orders - Vendor - FarmMart";

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
</style>

<?php require_once("../../includes/navbar.php"); ?>

<?php
// GET FARMER ID

$user_id=$_SESSION['user_id'];

$farmer_query="SELECT id FROM farmers 
WHERE user_id='$user_id' LIMIT 1";

$farmer_result=mysqli_query($conn,$farmer_query);

$farmer=mysqli_fetch_assoc($farmer_result);

$farmer_id=$farmer['id'] ?? 0;


// GET ORDERS

$orders=[];

$query="
SELECT 

o.id as order_id,
o.created_at,
o.status as order_status,

u.name as customer_name,

oi.quantity,
oi.price,

p.name as product_name

FROM orders o

JOIN users u 
ON o.user_id=u.id

JOIN order_items oi 
ON o.id=oi.order_id

JOIN products p 
ON oi.product_id=p.id

WHERE p.farmer_id='$farmer_id'

ORDER BY o.created_at DESC
";

$result=mysqli_query($conn,$query);

if($result)
{
$orders=mysqli_fetch_all($result,MYSQLI_ASSOC);
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
              <h1 class="fw-bold" style="color: #012970; font-size: 24px;">Customer Orders</h1>
              <nav>
                <ol class="breadcrumb" style="background: transparent; padding: 0;">
                  <li class="breadcrumb-item"><a href="dashboard.php" style="color: #899bbd; text-decoration: none;">Home</a></li>
                  <li class="breadcrumb-item active" style="color: #444444; font-weight: 600;">Orders</li>
                </ol>
              </nav>
            </div><!-- End Page Title -->

            <section class="section">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Recent Orders</h5>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th class="ps-4">Order ID</th>
                                                <th>Customer</th>
                                                <th>Product</th>
                                                <th>Qty & Total</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($orders)>0): ?>
                                                <?php foreach($orders as $o): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <a href="#" class="fw-bold" style="color: #4154f1; text-decoration: none;">
                                                            #ORD-<?= $o['order_id'] ?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <span style="color: #444444;"><?= htmlspecialchars($o['customer_name']) ?></span>
                                                    </td>
                                                    <td>
                                                        <a href="#" class="text-primary fw-bold" style="text-decoration: none; color: #012970;">
                                                            <?= htmlspecialchars($o['product_name']) ?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <span class="text-muted small"><?= $o['quantity'] ?> × ₹<?= $o['price'] ?></span><br>
                                                        <span class="fw-bold text-success">₹<?= number_format($o['price']*$o['quantity'],2) ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="text-muted small">
                                                            <?= date('d M Y',strtotime($o['created_at'])) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                            $status = strtolower($o['order_status']);
                                                            $badgeClass = 'bg-secondary';
                                                            if($status == 'pending' || $status == 'processing') $badgeClass = 'bg-warning text-dark';
                                                            if($status == 'completed' || $status == 'delivered') $badgeClass = 'bg-success';
                                                            if($status == 'cancelled') $badgeClass = 'bg-danger';
                                                        ?>
                                                        <span class="badge <?= $badgeClass ?>" style="font-size: 13px;">
                                                            <?= htmlspecialchars($o['order_status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center py-5 text-muted">
                                                        <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary"></i>
                                                        No orders received yet
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
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
                if(link.getAttribute('href').includes('orders.php')) {
                    link.classList.add('active');
                }
            });
        }
    });
</script>

<?php require_once("../../includes/footer.php"); ?>