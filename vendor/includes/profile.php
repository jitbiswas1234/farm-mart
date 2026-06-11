<?php

require_once("../../includes/auth.php");
require_once("../../includes/config.php");

if($_SESSION['role']!="farmer")
{
header("Location: ../../login.php");
exit();
}

$user_id=$_SESSION['user_id'];

$success_msg="";
$error_msg="";


// FETCH USER DATA

$query="SELECT name,email,phone,address,profile_picture 
FROM users 
WHERE id=?";

$stmt=$conn->prepare($query);

$stmt->bind_param("i",$user_id);

$stmt->execute();

$result=$stmt->get_result();

$user_data=$result->fetch_assoc();

$stmt->close();


if(!$user_data)
{

header("Location: ../../login.php");

exit();

}


// DEFAULT VALUES

$name=$user_data['name'];

$email=$user_data['email'];

$phone=$user_data['phone'];

$address=$user_data['address'] ?? '';

$profile_picture=$user_data['profile_picture'];



// UPDATE PROFILE

if($_SERVER['REQUEST_METHOD']=="POST")
{

$name=trim($_POST['name']);

$email=trim($_POST['email']);

$phone=trim($_POST['phone']);

$address=trim($_POST['address']);

$new_image=$profile_picture;



// VALIDATION

if(empty($name) || empty($email))
{

$error_msg="Name and Email required";

}

elseif(!filter_var($email,FILTER_VALIDATE_EMAIL))
{

$error_msg="Invalid email";

}



// EMAIL CHECK

if(empty($error_msg))
{

$check=$conn->prepare("

SELECT id FROM users

WHERE email=? AND id!=?

");

$check->bind_param("si",$email,$user_id);

$check->execute();

$check->store_result();

if($check->num_rows>0)
{

$error_msg="Email already exists";

}

$check->close();

}



// IMAGE UPLOAD

if(empty($error_msg) &&
isset($_FILES['profile_picture']) &&
$_FILES['profile_picture']['error']==0)
{

$ext=pathinfo(
$_FILES['profile_picture']['name'],
PATHINFO_EXTENSION
);

$new_image="user_".$user_id."_".time()."."
.$ext;

$upload=UPLOAD_PATH.'users/';

if(!is_dir($upload))
{

mkdir($upload,0755,true);

}

if(move_uploaded_file(

$_FILES['profile_picture']['tmp_name'],

$upload.$new_image

))
{

if(!empty($profile_picture) &&
file_exists($upload.$profile_picture))
{

unlink($upload.$profile_picture);

}

}
else
{

$error_msg="Upload failed";

}

}



// UPDATE DB

if(empty($error_msg))
{

$update=$conn->prepare("

UPDATE users

SET name=?,email=?,phone=?,
address=?,profile_picture=?

WHERE id=?

");

$update->bind_param(

"sssssi",

$name,
$email,
$phone,
$address,
$new_image,
$user_id

);

if($update->execute())
{

$success_msg="Profile updated";

$_SESSION['name']=$name;

$_SESSION['profile_picture']=$new_image;

$profile_picture=$new_image;

}
else
{

$error_msg="Update failed";

}

$update->close();

}

}


$page_title="Vendor Profile";

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
.profile-card img {
  max-width: 120px;
}
.profile-card h2 {
  font-size: 24px;
  font-weight: 700;
  color: #2c384e;
  margin: 10px 0 0 0;
}
.profile-card h3 {
  font-size: 18px;
}
.form-label {
    font-weight: 600;
    color: rgba(1, 41, 112, 0.6);
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
            <div class="pagetitle mb-4">
              <h1 class="fw-bold" style="color: #012970; font-size: 24px;">Profile Settings</h1>
              <nav>
                <ol class="breadcrumb" style="background: transparent; padding: 0;">
                  <li class="breadcrumb-item"><a href="dashboard.php" style="color: #899bbd; text-decoration: none;">Home</a></li>
                  <li class="breadcrumb-item active" style="color: #444444; font-weight: 600;">Profile</li>
                </ol>
              </nav>
            </div><!-- End Page Title -->

            <section class="section profile">
                <div class="row">
                    <div class="col-xl-4">
                        <div class="card profile-card pt-4 d-flex flex-column align-items-center">
                            <div class="card-body text-center">
                                <?php
                                $img=!empty($profile_picture)
                                ? BASE_URL.'uploads/users/'.$profile_picture
                                : BASE_URL.'assets/default-user.png';
                                ?>
                                <img src="<?= $img ?>" alt="Profile" class="rounded-circle shadow-sm" style="width:120px; height:120px; object-fit:cover; border: 4px solid #fff;">
                                <h2><?= htmlspecialchars($name) ?></h2>
                                <h3 class="text-muted small mt-1 mb-3">Vendor / Farmer</h3>
                                
                                <div class="social-links mt-2">
                                  <a href="#" class="twitter btn btn-sm btn-outline-secondary rounded-circle me-1"><i class="bi bi-twitter"></i></a>
                                  <a href="#" class="facebook btn btn-sm btn-outline-secondary rounded-circle me-1"><i class="bi bi-facebook"></i></a>
                                  <a href="#" class="instagram btn btn-sm btn-outline-secondary rounded-circle me-1"><i class="bi bi-instagram"></i></a>
                                  <a href="#" class="linkedin btn btn-sm btn-outline-secondary rounded-circle"><i class="bi bi-linkedin"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-8">
                        <?php if($success_msg): ?>
                        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                            <i class="bi bi-check-circle me-1"></i>
                            <?= $success_msg ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <?php if($error_msg): ?>
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                            <i class="bi bi-exclamation-octagon me-1"></i>
                            <?= $error_msg ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <div class="card">
                            <div class="card-body pt-3">
                                <ul class="nav nav-tabs nav-tabs-bordered" style="border-bottom: 2px solid #ebeef4;">
                                    <li class="nav-item">
                                        <button class="nav-link active" style="color: #4154f1; border-bottom: 2px solid #4154f1; background: none; font-weight: 600;">Edit Profile</button>
                                    </li>
                                </ul>
                                
                                <div class="tab-content pt-4">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="row mb-3">
                                            <label class="col-md-4 col-lg-3 col-form-label form-label">Profile Image</label>
                                            <div class="col-md-8 col-lg-9">
                                                <input type="file" name="profile_picture" class="form-control mb-2">
                                                <small class="text-muted">JPG, GIF or PNG. Max size of 800K</small>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label class="col-md-4 col-lg-3 col-form-label form-label">Full Name</label>
                                            <div class="col-md-8 col-lg-9">
                                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label class="col-md-4 col-lg-3 col-form-label form-label">Email</label>
                                            <div class="col-md-8 col-lg-9">
                                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label class="col-md-4 col-lg-3 col-form-label form-label">Phone</label>
                                            <div class="col-md-8 col-lg-9">
                                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone) ?>">
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label class="col-md-4 col-lg-3 col-form-label form-label">Address</label>
                                            <div class="col-md-8 col-lg-9">
                                                <textarea name="address" class="form-control" style="height: 100px"><?= htmlspecialchars($address ?? '') ?></textarea>
                                            </div>
                                        </div>

                                        <div class="text-end mt-4 pt-3 border-top">
                                            <button type="submit" class="btn btn-primary px-4 py-2" style="background-color: #4154f1; border: none;">
                                                <i class="bi bi-save me-1"></i> Save Changes
                                            </button>
                                        </div>
                                    </form>
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
                if(link.getAttribute('href').includes('profile.php')) {
                    link.classList.add('active');
                }
            });
        }
    });
</script>

<?php require_once("../../includes/footer.php"); ?>