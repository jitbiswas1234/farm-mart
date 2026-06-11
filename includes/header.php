<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/config.php';
$is_admin_page = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
// This file contains the opening HTML, head section, and the global UI design system styles.
// It should be included at the top of every public-facing page.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'FarmMart - Fresh Local Produce' ?></title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <?php if ($is_admin_page): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
    <?php endif; ?>

    <!-- Custom UI Design System (from Blueprint) -->
    <style>
        :root {
            --primary: #2E7D32;
            --secondary: #66BB6A;
            --accent: #FFA000;
            --bg-color: #F1F8E9;
            --text-dark: #263238;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--text-dark);
            background-color: #fdfdfd;
        }
        
        .text-primary-theme { color: var(--primary) !important; }
        .bg-primary-light { background-color: var(--bg-color); }
        
        /* Buttons */
        .btn-farmmart {
            background-color: var(--primary);
            color: white;
            border-radius: 8px;
            padding: 10px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-farmmart:hover {
            background-color: #1b491e;
            color: white;
            transform: translateY(-2px);
        }
        .btn-outline-farmmart {
            border: 2px solid var(--primary);
            color: var(--primary);
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-outline-farmmart:hover {
            background-color: var(--primary);
            color: white;
        }

        /* Product Cards (Core UI Feature) */
        .product-card {
            border: 1px solid #eee;
            border-radius: 12px;
            background: #fff;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        .product-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background-color: var(--accent);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            z-index: 2;
        }
        .product-img-wrapper {
            height: 220px;
            overflow: hidden;
        }
        .product-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .product-card:hover .product-img {
            transform: scale(1.05);
        }

        /* Hero Section Stats */
        .stat-divider {
            border-left: 3px solid var(--primary);
            padding-left: 15px;
        }

        /* Footer Styling */
        .footer-section {
            background-color: #212529; /* Dark background for a professional look */
            color: #fff;
            font-size: 0.95rem;
        }

        .footer-section a {
            color: rgba(255, 255, 255, 0.75); /* Lighter text for links */
            transition: color 0.3s ease;
        }

        .footer-section a:hover,
        .footer-section .hover-white:hover {
            color: #fff; /* White on hover */
        }

        .footer-section .navbar-brand .text-white {
            color: #fff !important; /* Ensure logo text is white */
        }

        .footer-section .social-links a {
            font-size: 1.3rem;
            margin-right: 15px;
        }

        .footer-section .social-links a:hover {
            color: var(--primary); /* Green on hover for social icons, using primary theme color */
        }

        .footer-section .list-unstyled li {
            margin-bottom: 0.5rem;
        }

        .footer-section .list-unstyled li i {
            color: var(--primary); /* Green icons for contact info, using primary theme color */
            width: 20px; /* Align icons */
        }

        .footer-section .form-control {
            border-color: rgba(255, 255, 255, 0.2);
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .footer-section .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .footer-section .form-control:focus {
            background-color: rgba(255, 255, 255, 0.2);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(46, 125, 50, 0.25); /* Using primary color for focus shadow */
        }

        .footer-section hr {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="<?= $is_admin_page ? 'admin-panel' : '' ?>">