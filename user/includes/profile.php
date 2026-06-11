<?php
session_start();
require_once '../../includes/config.php';
 
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$user_stmt = $conn->prepare("
    SELECT * FROM users WHERE id = ?
");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');
        
        // Handle profile picture upload
        $profile_picture = $user['profile_picture'];
        
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['profile_picture']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
                $target_dir = UPLOAD_PATH . 'users/';
                $upload_path = $target_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                    // Delete old picture
                    if ($profile_picture && file_exists($target_dir . $profile_picture)) {
                        unlink($target_dir . $profile_picture);
                    }
                    $profile_picture = $new_filename;
                } else {
                    $error_message = "Failed to upload new profile picture.";
                }
            } else {
                $error_message = "Invalid file type for profile picture. Allowed: JPG, PNG, GIF, WebP";
            }
        }
        
        if (empty($error_message)) {
            $update_stmt = $conn->prepare("
                UPDATE users SET 
                    name = ?, phone = ?, address = ?, city = ?, pincode = ?, profile_picture = ?
                WHERE id = ?
            ");
            $update_stmt->bind_param("ssssssi", $name, $phone, $address, $city, $pincode, $profile_picture, $user_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Profile updated successfully!";
                $_SESSION['profile_picture'] = $profile_picture; // Update session
                $_SESSION['name'] = $name; // Update session
                // Refresh user data from DB (though session is updated, this keeps $user array current)
                $user_stmt->execute(); 
                $user = $user_stmt->get_result()->fetch_assoc(); 
                header("Location: profile.php?updated=1"); // Force reload to show changes consistently
                exit();
            } else {
                $error_message = "Failed to update profile.";
            }
        }
    }
    
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (!password_verify($current_password, $user['password'])) {
            $error_message = "Current password is incorrect.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "New password must be at least 6 characters.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $pwd_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $pwd_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($pwd_stmt->execute()) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Failed to change password.";
            }
        }
    }
}

// Get order statistics
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as total_spent
    FROM orders WHERE user_id = ?
");
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmMart | Profile Settings</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    
    <style>
    /* ShopWise Official Template Inspired Theme for Profile */
    :root {
        --shopwise-red: #FF324D;
        --shopwise-dark: #202325;
        --shopwise-text: #687188;
        --shopwise-light: #F7F8FB;
        --border-color: #eee;
        --bg-surface: #ffffff;
        --font-heading: 'Roboto', sans-serif;
        --font-body: 'Poppins', sans-serif;
        --transition: all 0.3s ease-in-out;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: var(--font-body);
        background-color: var(--shopwise-light);
        color: var(--shopwise-text);
        font-size: 15px;
        line-height: 28px;
        overflow-x: hidden;
    }

    h1, h2, h3, h4, h5, h6 {
        font-family: var(--font-heading);
        color: var(--shopwise-dark);
        font-weight: 700;
    }

    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }

    /* === LAYOUT === */
    .app-layout { display: flex; min-height: 100vh; position: relative; }

    .main-wrapper {
        flex: 1; display: flex; flex-direction: column;
        margin-left: 260px;
        transition: var(--transition);
        min-height: 100vh;
    }

    /* === HEADER === */
    .top-header {
        height: 70px; background: var(--bg-surface);
        border-bottom: 1px solid var(--border-color);
        display: flex; align-items: center; justify-content: space-between;
        padding: 0 32px; position: sticky; top: 0; z-index: 50;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
    }
    .header-left { display: flex; align-items: center; gap: 20px; }
    .header-greeting { font-size: 16px; font-weight: 600; color: var(--shopwise-dark); font-family: var(--font-heading); text-transform: uppercase;}
    .header-greeting span { color: var(--shopwise-red); font-weight: 700; }

    .header-right { display: flex; align-items: center; gap: 15px; }

    .header-icon {
        width: 40px; height: 40px; border-radius: 50%; background: var(--shopwise-light);
        color: var(--shopwise-dark);
        display: flex; align-items: center; justify-content: center; cursor: pointer;
        transition: var(--transition); position: relative; font-size: 1.2rem;
    }
    .header-icon:hover { background: var(--shopwise-red); color: #fff; }

    /* === CONTENT === */
    .main-content { padding: 30px; flex: 1; }

    /* === PANELS === */
    .panel {
        background: var(--bg-surface);
        border-radius: 0;
        padding: 30px;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        margin-bottom: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.03);
    }

    .panel-header {
        margin-bottom: 25px;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 15px;
    }
    .panel-title { 
        font-size: 18px; font-weight: 700; color: var(--shopwise-dark); 
        display: flex; align-items: center; gap: 10px; text-transform: uppercase;
    }
    .panel-title i { color: var(--shopwise-red); font-size: 1.2rem; }

    /* === PROFILE CARD === */
    .profile-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        padding: 30px;
        margin-bottom: 30px;
        display: flex;
        gap: 30px;
        align-items: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.03);
    }

    .profile-avatar-container {
        position: relative;
        flex-shrink: 0;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: var(--shopwise-light);
        border: 3px solid var(--border-color);
        object-fit: cover;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 2.5rem;
        color: var(--shopwise-red);
        font-family: var(--font-heading);
    }
    .profile-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

    .avatar-upload {
        position: absolute;
        bottom: 0px;
        right: 0px;
        width: 35px;
        height: 35px;
        background: var(--shopwise-red);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        cursor: pointer;
        border: 2px solid var(--bg-surface);
        transition: var(--transition);
    }
    .avatar-upload:hover { background: var(--shopwise-dark); }

    .profile-info { flex: 1; }
    .profile-name { font-size: 24px; font-weight: 700; color: var(--shopwise-dark); margin-bottom: 5px; font-family: var(--font-heading);}
    .profile-email { font-size: 14px; color: var(--shopwise-text); margin-bottom: 15px; }
    .profile-meta { display: flex; gap: 20px; flex-wrap: wrap; }
    .meta-item { font-size: 13px; color: var(--shopwise-text); display: flex; align-items: center; gap: 8px; font-weight: 500;}

    .profile-stats {
        display: flex;
        gap: 30px;
        margin-left: auto;
        padding-left: 30px;
        border-left: 1px solid var(--border-color);
    }
    .stat-box { text-align: center; }
    .stat-number { font-size: 24px; font-weight: 700; color: var(--shopwise-red); font-family: var(--font-heading);}
    .stat-label { font-size: 12px; color: var(--shopwise-text); text-transform: uppercase; font-weight: 600;}

    /* === FORMS === */
    .form-group { margin-bottom: 25px; }
    .form-label {
        font-size: 14px;
        font-weight: 600;
        color: var(--shopwise-dark);
        margin-bottom: 10px;
        display: block;
        text-transform: capitalize;
    }

    .form-control {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        color: var(--shopwise-text);
        padding: 12px 20px;
        border-radius: 0;
        font-family: var(--font-body);
        font-size: 14px;
        transition: var(--transition);
        height: 50px;
    }
    textarea.form-control { height: auto; }
    .form-control:focus {
        outline: none;
        border-color: var(--shopwise-red);
        box-shadow: none;
    }

    .form-control::placeholder { color: #aaa; }

    /* === BUTTONS === */
    .btn-shopwise-fill {
        background-color: var(--shopwise-red);
        color: #fff;
        border: 1px solid var(--shopwise-red);
        padding: 12px 30px;
        border-radius: 0;
        font-family: var(--font-heading);
        text-transform: uppercase;
        font-weight: 600;
        font-size: 14px;
        transition: var(--transition);
        display: inline-block;
        text-decoration: none;
        cursor: pointer;
    }

    .btn-shopwise-fill:hover {
        background-color: var(--shopwise-dark);
        border-color: var(--shopwise-dark);
        color: #fff;
    }
    
    .btn-shopwise {
        background-color: transparent;
        color: var(--shopwise-dark);
        border: 1px solid var(--shopwise-dark);
        padding: 12px 30px;
        border-radius: 0;
        font-family: var(--font-heading);
        text-transform: uppercase;
        font-weight: 600;
        font-size: 14px;
        transition: var(--transition);
        display: inline-block;
        text-decoration: none;
        cursor: pointer;
    }

    .btn-shopwise:hover {
        background-color: var(--shopwise-red);
        border-color: var(--shopwise-red);
        color: #fff;
    }

    .btn-danger-custom {
        background: transparent;
        border: 1px solid var(--shopwise-red);
        color: var(--shopwise-red);
        padding: 12px 20px;
        border-radius: 0;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: var(--transition);
        font-family: var(--font-heading);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        justify-content: center;
        text-transform: uppercase;
    }
    .btn-danger-custom:hover {
        background: var(--shopwise-red);
        color: #fff;
    }

    /* === ALERTS === */
    .alert-custom {
        padding: 15px 20px;
        border-radius: 0;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
        font-size: 14px;
    }
    .alert-custom.success {
        background: #e8f5e9;
        color: #2e7d32;
        border: 1px solid #c8e6c9;
    }
    .alert-custom.error {
        background: #ffebee;
        color: var(--shopwise-red);
        border: 1px solid #ffcdd2;
    }

    /* === GRID === */
    .grid-2-1 { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start;}

    /* Quick Links */
    .quick-link-item {
        display: flex; align-items: center; gap: 15px; padding: 15px; 
        background: var(--shopwise-light); border: 1px solid var(--border-color); 
        text-decoration: none; color: var(--shopwise-text); transition: var(--transition);
        margin-bottom: 15px;
    }
    .quick-link-item:hover { border-color: var(--shopwise-red); transform: translateX(5px); }
    .quick-link-icon {
        width: 40px; height: 40px; background: #fff; display: flex; 
        align-items: center; justify-content: center; color: var(--shopwise-dark); 
        font-size: 18px; border: 1px solid var(--border-color);
        transition: var(--transition);
    }
    .quick-link-item:hover .quick-link-icon { background: var(--shopwise-red); color: #fff; border-color: var(--shopwise-red); }
    .quick-link-text { flex: 1; }
    .quick-link-title { font-weight: 600; color: var(--shopwise-dark); font-family: var(--font-heading); margin-bottom: 2px;}
    .quick-link-desc { font-size: 12px; }

    /* === RESPONSIVE === */
    @media (max-width: 1200px) {
        .grid-2-1 { grid-template-columns: 1fr; }
        .profile-stats { margin-left: 0; padding-left: 0; border-left: none; margin-top: 20px; }
    }
    @media (max-width: 992px) {
        .main-wrapper { margin-left: 0; }
        .sidebar-wrapper { position: absolute; left: -260px; }
        .main-content { padding: 20px; }
        .top-header { padding: 0 20px; }
        .profile-card { flex-direction: column; text-align: center; }
    }
    @media (max-width: 768px) {
        .header-greeting { display: none; }
    }
    </style>
</head>
<body>

<div class="app-layout">
    
    <!-- Include the Sidebar -->
    <?php include '../../includes/user_sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-left">
                <div class="header-greeting">
                    Profile Settings <span>Manage your account</span>
                </div>
            </div>
            <div class="header-right">
                <a href="notifications.php" class="header-icon" title="Notifications">
                    <i class="bi bi-bell"></i>
                </a>
            </div>
        </header>

        <!-- Profile Content -->
        <main class="main-content">

            <!-- Alert Messages -->
            <?php if ($success_message): ?>
            <div class="alert-custom success">
                <i class="bi bi-check-circle-fill"></i>
                <span><?= htmlspecialchars($success_message) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="alert-custom error">
                <i class="bi bi-x-circle-fill"></i>
                <span><?= htmlspecialchars($error_message) ?></span>
            </div>
            <?php endif; ?>

            <!-- Profile Header Card -->
            <div class="profile-card">
                <div class="profile-avatar-container">
                    <div class="profile-avatar" id="avatarDisplay">
                        <?php 
                        if (!empty($user['profile_picture']) && file_exists('../../uploads/users/' . $user['profile_picture'])):
                        ?>
                            <img src="../../uploads/users/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile">
                        <?php else:
                            $name_parts = explode(' ', $user['name'] ?? 'U');
                            $initials = strtoupper(substr($name_parts[0], 0, 1));
                            if (isset($name_parts[1])) $initials .= strtoupper(substr($name_parts[1], 0, 1));
                            echo $initials;
                        endif;
                        ?>
                    </div>
                    <label for="profilePictureInput" class="avatar-upload">
                        <i class="bi bi-camera"></i>
                    </label>
                </div>

                <div class="profile-info">
                    <div class="profile-name"><?= htmlspecialchars($user['name'] ?? '') ?></div>
                    <div class="profile-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                    <div class="profile-meta">
                        <span class="meta-item"><i class="bi bi-person-badge"></i> <?= ucfirst($user['role'] ?? 'User') ?></span>
                        <span class="meta-item"><i class="bi bi-calendar-check"></i> Member since <?= date('M Y', strtotime($user['created_at'] ?? '')) ?></span>
                        <?php if ($user['is_verified'] ?? 0): ?>
                        <span class="meta-item" style="color: #38c823;"><i class="bi bi-patch-check"></i> Verified</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-stats">
                    <div class="stat-box">
                        <div class="stat-number"><?= $stats['total_orders'] ?? 0 ?></div>
                        <div class="stat-label">Orders</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?= $stats['completed_orders'] ?? 0 ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number">₹<?= number_format($stats['total_spent'] ?? 0, 0) ?></div>
                        <div class="stat-label">Spent</div>
                    </div>
                </div>
            </div>

            <!-- Settings Content -->
            <div class="grid-2-1">
                
                <!-- Left Column -->
                <div>

                    <!-- Personal Information -->
                    <div class="panel">
                        <div class="panel-header">
                            <div class="panel-title">Personal Information</div>
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_profile">
                            <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*" hidden>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" name="name" 
                                               value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" 
                                               value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                               placeholder="+91 xxxx xxxxxx" pattern="[0-9]{10}" maxlength="10">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" name="city" 
                                               value="<?= htmlspecialchars($user['city'] ?? '') ?>"
                                               placeholder="Enter your city">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="3" 
                                          placeholder="Enter your address"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group" style="max-width: 50%;">
                                <label class="form-label">PIN Code</label>
                                <input type="text" class="form-control" name="pincode" 
                                       value="<?= htmlspecialchars($user['pincode'] ?? '') ?>"
                                       placeholder="6-digit PIN code" pattern="[0-9]{6}" maxlength="6">
                            </div>

                            <div style="display: flex; gap: 15px; margin-top: 30px;">
                                <button type="submit" class="btn-shopwise-fill">Save Changes</button>
                                <button type="reset" class="btn-shopwise">Reset</button>
                            </div>
                        </form>
                    </div>

                    <!-- Change Password -->
                    <div class="panel">
                        <div class="panel-header">
                            <div class="panel-title">Change Password</div>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" name="new_password" 
                                               id="newPassword" required minlength="6"
                                               placeholder="At least 6 characters">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" name="confirm_password" required
                                               placeholder="Re-enter password">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn-shopwise-fill" style="margin-top: 15px;">Update Password</button>
                        </form>
                    </div>

                </div>

                <!-- Right Column -->
                <div>

                    <!-- Quick Links -->
                    <div class="panel">
                        <div class="panel-header">
                            <div class="panel-title">Quick Links</div>
                        </div>
                        <div>
                            <a href="orders.php" class="quick-link-item">
                                <div class="quick-link-icon">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                                <div class="quick-link-text">
                                    <div class="quick-link-title">My Orders</div>
                                    <div class="quick-link-desc">View order history</div>
                                </div>
                                <i class="bi bi-chevron-right" style="color: #ccc;"></i>
                            </a>
                            <a href="cart.php" class="quick-link-item">
                                <div class="quick-link-icon">
                                    <i class="bi bi-cart3"></i>
                                </div>
                                <div class="quick-link-text">
                                    <div class="quick-link-title">Shopping Cart</div>
                                    <div class="quick-link-desc">View cart items</div>
                                </div>
                                <i class="bi bi-chevron-right" style="color: #ccc;"></i>
                            </a>
                            <a href="notifications.php" class="quick-link-item" style="margin-bottom: 0;">
                                <div class="quick-link-icon">
                                    <i class="bi bi-bell"></i>
                                </div>
                                <div class="quick-link-text">
                                    <div class="quick-link-title">Notifications</div>
                                    <div class="quick-link-desc">Manage alerts</div>
                                </div>
                                <i class="bi bi-chevron-right" style="color: #ccc;"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div class="panel" style="border: 1px solid #ffcdd2; background: #fff;">
                        <div class="panel-header" style="border-bottom-color: #ffcdd2;">
                            <div class="panel-title" style="color: var(--shopwise-red);"><i class="bi bi-exclamation-triangle" style="color: var(--shopwise-red);"></i> Danger Zone</div>
                        </div>
                        <p style="font-size: 14px; color: var(--shopwise-text); margin-bottom: 20px;">Once you delete your account, there is no going back. Please be certain.</p>
                        <button type="button" class="btn-danger-custom" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            Delete Account
                        </button>
                    </div>

                </div>

            </div>

        </main>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 0; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid var(--border-color); padding: 20px 30px;">
                <h5 class="modal-title" style="color: var(--shopwise-red); font-weight: 700; font-family: var(--font-heading); text-transform: uppercase;">
                    <i class="bi bi-exclamation-triangle me-2"></i>Delete Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 30px; font-family: var(--font-body); font-size: 15px; color: var(--shopwise-text);">
                <p style="margin-bottom: 20px;">Are you sure you want to delete your account? This action cannot be undone and all your data will be permanently removed.</p>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size: 13px;">Type "DELETE" to confirm</label>
                    <input type="text" class="form-control" id="deleteConfirm" placeholder="DELETE">
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--border-color); padding: 20px 30px;">
                <button type="button" class="btn-shopwise" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-shopwise-fill" id="confirmDelete" disabled style="background: var(--shopwise-red); border-color: var(--shopwise-red);">
                    Delete My Account
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Avatar Preview
document.getElementById('profilePictureInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const display = document.getElementById('avatarDisplay');
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '50%';
            display.innerHTML = '';
            display.appendChild(img);
        };
        reader.readAsDataURL(file);
        
        // Auto submit to update immediately
        const form = document.querySelector('form[enctype="multipart/form-data"]');
        form?.submit();
    }
});

// Delete Confirmation
document.getElementById('deleteConfirm')?.addEventListener('input', function(e) {
    document.getElementById('confirmDelete').disabled = e.target.value !== 'DELETE';
});

document.getElementById('confirmDelete')?.addEventListener('click', function() {
    fetch('../../api/delete_account.php', {method: 'POST'})
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = '../../logout.php';
        } else {
            alert(data.message || 'Failed to delete account');
        }
    });
});
</script>

</body>
</html>