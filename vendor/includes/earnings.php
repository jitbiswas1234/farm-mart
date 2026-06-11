<?php

require_once("../../includes/auth.php");
require_once("../../includes/config.php");

if($_SESSION['role']!="farmer")
{
header("Location: ../../login.php");
exit();
}

$page_title="Earnings - Vendor - FarmMart";

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
.card-title span {
  color: #899bbd;
  font-size: 14px;
  font-weight: 400;
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

.info-card .card-icon {
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
  color: #2eca6a;
  background: #e0f8e9;
}
</style>

<?php require_once("../../includes/navbar.php"); ?>

<?php
// GET FARMER ID

$user_id=$_SESSION['user_id'];

$farm_query="SELECT id 
FROM farmers 
WHERE user_id='$user_id'
LIMIT 1";

$farm_res=mysqli_query($conn,$farm_query);

$farm=mysqli_fetch_assoc($farm_res);

$farmer_id=$farm['id'] ?? 0;



// TOTAL EARNINGS

$total_earnings=0;

$query="
SELECT SUM(oi.price * oi.quantity) as total
FROM order_items oi
JOIN products p 
ON oi.product_id=p.id
JOIN orders o 
ON oi.order_id=o.id
WHERE p.farmer_id='$farmer_id'
AND o.status!='cancelled'
";

$res=mysqli_query($conn,$query);

if($res)
{
$row=mysqli_fetch_assoc($res);

$total_earnings=$row['total'] ?? 0;
}



// RECENT TRANSACTIONS

$transactions=[];

$t_query="
SELECT 
o.id as order_id,
o.created_at,
u.name as customer_name,
p.name as product_name,
(oi.price * oi.quantity) as amount
FROM orders o
JOIN users u 
ON o.user_id=u.id
JOIN order_items oi 
ON o.id=oi.order_id
JOIN products p 
ON oi.product_id=p.id
WHERE p.farmer_id='$farmer_id'
AND o.status!='cancelled'
ORDER BY o.created_at DESC
LIMIT 15
";

$t_res=mysqli_query($conn,$t_query);

if($t_res)
{
$transactions=mysqli_fetch_all($t_res,MYSQLI_ASSOC);
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
              <h1 class="fw-bold" style="color: #012970; font-size: 24px;">Earnings Report</h1>
              <nav>
                <ol class="breadcrumb" style="background: transparent; padding: 0;">
                  <li class="breadcrumb-item"><a href="dashboard.php" style="color: #899bbd; text-decoration: none;">Home</a></li>
                  <li class="breadcrumb-item active" style="color: #444444; font-weight: 600;">Earnings</li>
                </ol>
              </nav>
            </div><!-- End Page Title -->

            <section class="section">
                <div class="row">
                    <!-- Revenue Info Card -->
                    <div class="col-xxl-6 col-md-8 info-card revenue-card">
                        <div class="card info-card">
                            <div class="card-body">
                                <h5 class="card-title">Revenue <span>| Lifetime</span></h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bi bi-currency-rupee"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6 class="mb-0" style="font-size: 32px; font-weight: 700; color: #012970;">₹<?= number_format($total_earnings,2) ?></h6>
                                        <span class="text-success small pt-1 fw-bold">Total</span> <span class="text-muted small pt-2 ps-1">Amount Earned</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Recent Transactions <span>| Latest 15</span></h5>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th class="ps-4">Date</th>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Product</th>
                                                <th class="text-end pe-4">Earned</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($transactions)>0): ?>
                                                <?php foreach($transactions as $t): ?>
                                                <tr>
                                                    <td class="ps-4 text-muted small">
                                                        <?= date('d M Y', strtotime($t['created_at'])) ?>
                                                    </td>
                                                    <td>
                                                        <a href="#" class="fw-bold" style="color: #4154f1; text-decoration: none;">
                                                            #ORD-<?= $t['order_id'] ?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <span style="color: #444444;"><?= htmlspecialchars($t['customer_name']) ?></span>
                                                    </td>
                                                    <td>
                                                        <span style="color: #444444;"><?= htmlspecialchars($t['product_name']) ?></span>
                                                    </td>
                                                    <td class="text-end pe-4 text-success fw-bold">
                                                        +₹<?= number_format($t['amount'],2) ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-5 text-muted">
                                                        <i class="bi bi-wallet2 fs-1 d-block mb-2 text-secondary"></i>
                                                        No earnings yet
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
                if(link.getAttribute('href').includes('earnings.php')) {
                    link.classList.add('active');
                }
            });
        }
    });
</script>

<?php require_once("../../includes/footer.php"); ?>