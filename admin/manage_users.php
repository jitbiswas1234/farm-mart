<?php

require_once("../includes/auth.php");

if($_SESSION['role']!="admin")
{
header("Location: ../login.php");
exit();

}
require_once("../includes/config.php");

// Protect the page: Ensure the user is logged in AND is an admin

$page_title = "Manage Users - Admin - FarmMart";
require_once("../includes/header.php");
require_once("../includes/navbar.php");

$users = [];
$res = $conn->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC");
if ($res) {
    $users = $res->fetch_all(MYSQLI_ASSOC);
}
?>

<main class="container py-5">
    <div class="row g-5">
        
        <!-- Include Admin Sidebar -->
        <?php require_once("../includes/admin_sidebar.php"); ?>

        <!-- Main Content -->
        <div class="col-lg-9">
            <h3 class="fw-bold mb-4">Manage Users</h3>
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3">Name</th>
                                    <th class="py-3">Email</th>
                                    <th class="py-3">Phone</th>
                                    <th class="pe-4 py-3 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $u): ?>
                                <tr>
                                    <td class="ps-4 fw-medium"><?= htmlspecialchars($u['name']) ?></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td><?= htmlspecialchars($u['phone'] ?? 'N/A') ?></td>
                                    <td class="pe-4 text-end">
                                        <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require_once("../includes/footer.php"); ?>