<?php

require_once("../../includes/auth.php");
require_once("../../includes/config.php");

if($_SESSION['role']!="farmer")
{
header("Location: ../../login.php");
exit();
}

$page_title="Add Product - Vendor - FarmMart";

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

<?php
$msg="";
$msgType="";

$user_id=$_SESSION['user_id'];


// GET FARMER ID

$farm_query="SELECT id FROM farmers WHERE user_id='$user_id' LIMIT 1";

$farm_res=mysqli_query($conn,$farm_query);

$farm=mysqli_fetch_assoc($farm_res);

$farmer_id=$farm['id'] ?? 0;

if(!$farmer_id)
{
$msg="Farmer profile not found";
$msgType="danger";
}



// ADD PRODUCT

if($_SERVER['REQUEST_METHOD']=="POST" && isset($_POST['add_product']))
{

$category_id=intval($_POST['category_id']);

$name=trim($_POST['name']);

$price=floatval($_POST['price']);

$stock=floatval($_POST['stock']);

$unit=trim($_POST['unit']);

$description=trim($_POST['description']);

$is_organic=isset($_POST['is_organic']) ? 1 : 0;

$image="";


// IMAGE UPLOAD

if(isset($_FILES['image']) && $_FILES['image']['error']==0)
{

$allowed=['jpg','jpeg','png','webp'];

$ext=strtolower(pathinfo($_FILES['image']['name'],PATHINFO_EXTENSION));

if(in_array($ext,$allowed))
{

$image=time().'_'.rand(1000,9999).'.'.$ext;

move_uploaded_file(
$_FILES['image']['tmp_name'],
UPLOAD_PATH.'products/'.$image
);

}
else
{

$msg="Invalid image format";

$msgType="danger";

}

}



// INSERT

$stmt=$conn->prepare("

INSERT INTO products
(farmer_id,category_id,name,description,price,unit,stock,image,is_organic,status)

VALUES
(?,?,?,?,?,?,?,?,?,'active')

");

if($stmt)
{

$stmt->bind_param(
"iissdsdsi",
$farmer_id,
$category_id,
$name,
$description,
$price,
$unit,
$stock,
$image,
$is_organic
);

if($stmt->execute())
{

$msg="Product added successfully";

$msgType="success";

}
else
{

$msg="Insert failed";

$msgType="danger";

}

$stmt->close();

}

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
              <h1 class="fw-bold" style="color: #012970; font-size: 24px;">Add New Product</h1>
              <nav>
                <ol class="breadcrumb" style="background: transparent; padding: 0;">
                  <li class="breadcrumb-item"><a href="dashboard.php" style="color: #899bbd; text-decoration: none;">Home</a></li>
                  <li class="breadcrumb-item"><a href="products.php" style="color: #899bbd; text-decoration: none;">Products</a></li>
                  <li class="breadcrumb-item active" style="color: #444444; font-weight: 600;">Add Product</li>
                </ol>
              </nav>
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
                                        <!-- CATEGORY -->
                                        <div class="col-12">
                                            <label class="form-label">Category</label>
                                            <select name="category_id" class="form-select" required>
                                                <option value="">Select Category</option>
                                                <?php
                                                $cat=mysqli_query($conn,"SELECT id,name FROM categories ORDER BY name");
                                                while($row=mysqli_fetch_assoc($cat)) {
                                                ?>
                                                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <!-- NAME -->
                                        <div class="col-12">
                                            <label class="form-label">Product Name</label>
                                            <input type="text" name="name" class="form-control" required>
                                        </div>

                                        <!-- PRICE -->
                                        <div class="col-md-4">
                                            <label class="form-label">Price</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" step="0.01" name="price" class="form-control" required>
                                            </div>
                                        </div>

                                        <!-- UNIT -->
                                        <div class="col-md-4">
                                            <label class="form-label">Unit</label>
                                            <select name="unit" class="form-select">
                                                <option value="kg">kg</option>
                                                <option value="piece">piece</option>
                                                <option value="dozen">dozen</option>
                                                <option value="liter">liter</option>
                                            </select>
                                        </div>

                                        <!-- STOCK -->
                                        <div class="col-md-4">
                                            <label class="form-label">Stock</label>
                                            <input type="number" step="0.01" name="stock" class="form-control" required>
                                        </div>

                                        <!-- DESCRIPTION -->
                                        <div class="col-12">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="3"></textarea>
                                        </div>

                                        <!-- IMAGE -->
                                        <div class="col-12">
                                            <label class="form-label">Product Image</label>
                                            <input type="file" name="image" class="form-control">
                                        </div>

                                        <!-- ORGANIC -->
                                        <div class="col-12">
                                            <div class="form-check form-switch mt-2">
                                                <input type="checkbox" name="is_organic" class="form-check-input" id="organicSwitch" checked>
                                                <label class="form-check-label text-success fw-bold ms-2" for="organicSwitch">
                                                    Organic Product <i class="bi bi-patch-check-fill"></i>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-end mt-4 pt-3 border-top">
                                        <button type="submit" name="add_product" class="btn btn-primary px-4 py-2" style="background-color: #4154f1; border: none;">
                                            <i class="bi bi-save me-1"></i> Save Product
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
                if(link.getAttribute('href').includes('add_product.php')) {
                    link.classList.add('active');
                }
            });
        }
    });
</script>

<?php require_once("../../includes/footer.php"); ?>