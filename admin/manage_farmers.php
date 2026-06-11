<?php

require_once("../includes/auth.php");

if($_SESSION['role']!="admin")
{
header("Location: ../login.php");
exit();

}
require_once("../includes/config.php");

// Protect the page: Ensure the user is logged in AND is an admin

$page_title = "Manage Farmers - Admin - FarmMart";
require_once("../includes/header.php");
require_once("../includes/navbar.php");

$farmers = [];
$res = $conn->query("SELECT * FROM farmers ORDER BY created_at DESC");
if ($res) {
    $farmers = $res->fetch_all(MYSQLI_ASSOC);
}

// Helper function for farmer status badge
function getFarmerStatusBadge($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'pending': return '<span class="badge bg-warning text-dark">Pending</span>';
        case 'approved': return '<span class="badge bg-success">Approved</span>';
        case 'rejected': return '<span class="badge bg-danger">Rejected</span>';
        default: return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}
?>

<main class="container py-5">
    <div class="row g-5">
        
        <!-- Include Admin Sidebar -->
        <?php require_once("../includes/admin_sidebar.php"); ?>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Manage Farmers</h3>
                <a href="add_farmer.php" class="btn btn-farmmart btn-sm"><i class="bi bi-plus-lg"></i> Add Farmer</a>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3">Farmer Name</th>
                                    <th class="py-3">Village</th>
                                    <th class="py-3">Email</th>
                                    <th class="py-3">Status</th>
                                    <th class="pe-4 py-3 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($farmers as $f): ?>
                                <tr>
                                    <td class="ps-4 fw-medium"><?= htmlspecialchars($f['name']) ?></td>
                                    <td><?= htmlspecialchars($f['village'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($f['email'] ?? 'N/A') ?></td>
                                    <td>
                                        <form action="update_farmer_status.php" method="POST" class="d-inline-block">
                                            <input type="hidden" name="farmer_id" value="<?= htmlspecialchars($f['id']) ?>">
                                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="pending" <?= $f['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="approved" <?= $f['status'] == 'approved' ? 'selected' : '' ?>>Approved</option>
                                                <option value="rejected" <?= $f['status'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="edit_farmer.php?id=<?= htmlspecialchars($f['id']) ?>" class="btn btn-sm btn-outline-primary me-1">
                                            Edit
                                        </a>
                                        <a href="../products.php?farmer_id=<?= htmlspecialchars($f['id']) ?>" class="btn btn-sm btn-outline-info">
                                            View Products
                                        </a>
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