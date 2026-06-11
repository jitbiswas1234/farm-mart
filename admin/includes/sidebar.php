<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_url = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmMart Admin Panel</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Animate CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-color: #4154f1; /* ModernAdmin blue */
            --secondary-color: #3142e0;
            --dark-bg: #1f2937;
            --darker-bg: #111827;
            --light-bg: #f9fafb;
            --border-color: #e5e7eb;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-primary);
            overflow-x: hidden;
        }

        /* ============ SIDEBAR STYLES ============ */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--darker-bg) 100%);
            padding: 0;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
            transition: background 0.3s;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        /* Sidebar Brand */
        .sidebar-brand {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 10px;
            background: rgba(0, 0, 0, 0.2);
        }

        .sidebar-brand-logo {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px;
            color: white;
            box-shadow: 0 4px 15px rgba(65, 84, 241, 0.3);
        }

        .sidebar-brand h4 {
            color: white;
            font-weight: 700;
            margin: 0 0 5px;
            font-size: 20px;
        }

        .sidebar-brand small {
            color: rgba(255, 255, 255, 0.6);
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.5px;
            display: block;
        }

        /* Navigation Menu */
        .nav-menu {
            list-style: none;
            padding: 15px 10px;
            margin: 0;
        }

        .nav-section {
            margin-bottom: 25px;
        }

        .nav-section-title {
            font-size: 11px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 20px 15px;
            margin: 0;
        }

        .nav-item {
            margin-bottom: 5px;
            position: relative;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: var(--primary-color);
            transform: scaleY(0);
            transform-origin: top;
            transition: transform 0.3s ease;
        }

        .nav-link:hover {
            background: rgba(65, 84, 241, 0.15);
            color: var(--primary-color);
            padding-left: 25px;
        }

        .nav-link:hover::before {
            transform: scaleY(1);
        }

        .nav-link.active {
            background: rgba(65, 84, 241, 0.2);
            color: var(--primary-color);
            font-weight: 600;
        }

        .nav-link.active::before {
            transform: scaleY(1);
        }

        .nav-link i {
            width: 20px;
            margin-right: 15px;
            font-size: 16px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .nav-link:hover i {
            transform: translateX(3px);
        }

        .nav-link .badge {
            margin-left: auto;
            background: var(--danger);
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        .nav-link .badge.success {
            background: var(--success);
        }

        .nav-link .badge.warning {
            background: var(--warning);
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        /* User Profile Section */
        .sidebar-footer {
            padding: 15px 10px;
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-profile-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .user-profile-card:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(65, 84, 241, 0.5);
        }

        .user-profile-card .avatar {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .user-profile-card .user-name {
            color: white;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 3px;
        }

        .user-profile-card .user-role {
            color: rgba(255, 255, 255, 0.6);
            font-size: 11px;
        }

        /* ============ MAIN CONTENT STYLES ============ */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        /* Header */
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .header p {
            margin: 5px 0 0;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .header-actions .date-time {
            color: var(--text-secondary);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-actions .notification-bell {
            position: relative;
            width: 40px;
            height: 40px;
            background: var(--light-bg);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .header-actions .notification-bell:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .header-actions .notification-bell .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }

        /* Stats Card */
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stats-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 15px;
        }

        .stats-card h3 {
            font-size: 32px;
            font-weight: 700;
            margin: 10px 0;
            color: var(--text-primary);
        }

        .stats-card p {
            color: var(--text-secondary);
            margin: 0;
            font-size: 14px;
        }

        .stats-card .change {
            font-size: 12px;
            margin-top: 10px;
        }

        /* Table Styles */
        .custom-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .custom-table:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background: linear-gradient(135deg, var(--dark-bg), var(--darker-bg));
            color: white;
        }

        .table thead th {
            border: none;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 18px 15px;
        }

        .table tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: var(--light-bg);
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            color: var(--text-primary);
        }

        /* Button Styles */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(65, 84, 241, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(65, 84, 241, 0.4);
            color: white;
        }

        /* Badge Styles */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 11px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            :root {
                --sidebar-width: 0px;
            }

            .sidebar {
                position: fixed;
                left: -260px;
                height: 100vh;
                width: 260px;
                z-index: 1100;
            }

            .sidebar.active {
                left: 0;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                padding: 15px 20px;
            }

            .header h1 {
                font-size: 20px;
            }

            .header-actions {
                width: 100%;
                justify-content: space-between;
            }

            .mobile-menu-btn {
                display: flex !important;
            }
        }

        .mobile-menu-btn {
            display: none;
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(65, 84, 241, 0.4);
            z-index: 999;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 24px;
            align-items: center;
            justify-content: center;
        }

        .mobile-menu-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(65, 84, 241, 0.5);
        }

        /* Alert Messages */
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--success);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--danger);
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: var(--warning);
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: var(--info);
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .sidebar-overlay.active {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <!-- Brand -->
        <div class="sidebar-brand">
            <div class="sidebar-brand-logo">
                <i class="fas fa-seedling"></i>
            </div>
            <h4>FarmMart</h4>
            <small>Admin Dashboard</small>
        </div>

        <!-- Main Navigation -->
        <ul class="nav-menu">
            <!-- Dashboard Section -->
            <div class="nav-section">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </div>

            <!-- Management Section -->
            <p class="nav-section-title">Management</p>
            <div class="nav-section">
                <li class="nav-item">
                    <a href="manage_users.php" class="nav-link <?= $current_page == 'manage_users.php' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="manage_farmers.php" class="nav-link <?= $current_page == 'manage_farmers.php' ? 'active' : '' ?>">
                        <i class="fas fa-tractor"></i>
                        <span>Farmers</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="products.php" class="nav-link <?= $current_page == 'products.php' ? 'active' : '' ?>">
                        <i class="fas fa-box"></i>
                        <span>Products</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="all_orders.php" class="nav-link <?= $current_page == 'all_orders.php' ? 'active' : '' ?>">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
                    </a>
                </li>
            </div>

            <!-- Analytics Section -->
            <p class="nav-section-title">Analytics</p>
            <div class="nav-section">
                <li class="nav-item">
                    <a href="reports.php" class="nav-link <?= $current_page == 'reports.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
            </div>

            <!-- Account Section -->
            <p class="nav-section-title">Account</p>
            <div class="nav-section">
                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link" data-bs-toggle="modal" data-bs-target="#settingsModal">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../logout.php" class="nav-link" style="color: rgba(255, 107, 107, 0.8);">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </div>
        </ul>

        <!-- User Profile Footer -->
        <div class="sidebar-footer">
            <div class="user-profile-card">
                <img src="../uploads/users/<?= isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : 'default-user.png' ?>" 
                     alt="Admin" class="avatar w-100">
                <div class="user-name"><?= isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Admin' ?></div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Settings Modal -->
    <div class="modal fade" id="settingsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Admin panel settings and preferences coming soon...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle Sidebar on Mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        // Close sidebar when clicking on a link (mobile)
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('sidebarOverlay');
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                }
            });
        });

        // Close sidebar when clicking overlay
        document.getElementById('sidebarOverlay').addEventListener('click', toggleSidebar);

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }
        });

        // Show alerts if they exist
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '1';
                }, 100);
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.3s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>