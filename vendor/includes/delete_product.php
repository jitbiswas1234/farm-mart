<?php

require_once("../../includes/auth.php");

if($_SESSION['role']!="farmer")
{
header("../../login.php");
exit();
}

?>


<?php
require_once("../../includes/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: ../../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $farmer_id = $_SESSION['farmer_id'] ?? 0;

    // Fetch the image filename to delete it from the server
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ? AND farmer_id = ?");
    $stmt->bind_param("ii", $product_id, $farmer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['image']) && file_exists(UPLOAD_PATH . 'products/' . $row['image'])) {
            unlink(UPLOAD_PATH . 'products/' . $row['image']);
        }
    }
    $stmt->close();

    // Delete the product from the database
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND farmer_id = ?");
    $stmt->bind_param("ii", $product_id, $farmer_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: " . BASE_URL . "vendor/includes/products.php"); // Use absolute path
exit;