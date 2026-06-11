<?php
$current_page = basename($_SERVER['PHP_SELF']);
$admin_name = $_SESSION['name'] ?? 'Administrator';
$nav_items = [
    ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'href' => BASE_URL . 'admin/dashboard.php', 'page' => 'dashboard.php'],
    ['label' => 'Manage Users', 'icon' => 'bi-people', 'href' => BASE_URL . 'admin/manage_users.php', 'page' => 'manage_users.php'],
    ['label' => 'Manage Farmers', 'icon' => 'bi-shop', 'href' => BASE_URL . 'admin/manage_farmers.php', 'page' => 'manage_farmers.php'],
    ['label' => 'All Orders', 'icon' => 'bi-box-seam', 'href' => BASE_URL . 'admin/all_orders.php', 'page' => 'all_orders.php'],
    // ['label' => 'Reports', 'icon' => 'bi-bar-chart-line', 'href' => BASE_URL . 'admin/reports.php', 'page' => 'reports.php'],
];
?>
<style>
    .admin-sidebar {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #dbe3ef;
        border-radius: 24px;
        padding: 1.4rem;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        position: sticky;
        top: 1.25rem;
        min-height: calc(100vh - 3rem);
    }

    .admin-sidebar__brand {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        padding-bottom: 1rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .admin-sidebar__icon {
        width: 2.95rem;
        height: 2.95rem;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #10b981, #059669);
        color: #ffffff;
        font-size: 1.2rem;
        box-shadow: 0 16px 30px rgba(16, 185, 129, 0.2);
    }

    .admin-sidebar__eyebrow {
        margin: 0;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6b7280;
    }

    .admin-sidebar__profile {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        background: linear-gradient(90deg, rgba(16, 185, 129, 0.08), rgba(59, 130, 246, 0.06));
        padding: 0.9rem;
        border-radius: 16px;
        margin-bottom: 1.1rem;
    }

    .admin-sidebar__avatar {
        width: 2.7rem;
        height: 2.7rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #10b981;
        color: #ffffff;
        font-weight: 700;
    }

    .admin-nav {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .admin-nav__link {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        padding: 0.9rem 1rem;
        border-radius: 16px;
        color: #111827;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .admin-nav__link:hover,
    .admin-nav__link.is-active {
        background: linear-gradient(90deg, rgba(16, 185, 129, 0.12), rgba(59, 130, 246, 0.08));
        color: #047857;
        transform: translateX(2px);
    }

    .admin-sidebar__footer {
        margin-top: 1.2rem;
        padding-top: 1rem;
        border-top: 1px solid #e5e7eb;
    }

    .admin-sidebar__logout {
        display: inline-flex;
        align-items: center;
        font-weight: 700;
        color: #dc2626;
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .admin-sidebar__logout:hover {
        color: #b91c1c;
    }

    @media (max-width: 991.98px) {
        .admin-sidebar {
            position: static;
            min-height: auto;
            margin-bottom: 1rem;
        }

        .admin-sidebar__brand {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
<!-- Admin Sidebar -->
<div class="col-lg-3" style="flex: 0 0 280px; width: 280px;">
    <aside class="admin-sidebar">
        <div class="admin-sidebar__brand">
            <div class="admin-sidebar__icon">
                <i class="bi bi-shield-check"></i>
            </div>
            <div>
                <p class="admin-sidebar__eyebrow">FarmMart Admin</p>
                <h5 class="mb-0">Control Center</h5>
            </div>
        </div>

        <div class="admin-sidebar__profile">
            <div class="admin-sidebar__avatar">
                <?= strtoupper(substr($admin_name, 0, 1)) ?>
            </div>
            <div>
                <p class="mb-0 fw-semibold"><?= htmlspecialchars($admin_name) ?></p>
                <small class="text-muted">System Administrator</small>
            </div>
        </div>

        <nav class="admin-nav">
            <?php foreach ($nav_items as $nav_item): ?>
                <a href="<?= $nav_item['href'] ?>" class="admin-nav__link <?= $current_page == $nav_item['page'] ? 'is-active' : '' ?>">
                    <i class="bi <?= $nav_item['icon'] ?>"></i>
                    <span><?= $nav_item['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="admin-sidebar__footer">
            <p class="mb-1 text-muted">Need to leave the panel?</p>
            <a href="<?= BASE_URL ?>logout.php" class="admin-sidebar__logout">
                <i class="bi bi-box-arrow-right me-2"></i>
                Sign out
            </a>
        </div>
    </aside>
</div>