<?php
session_start();
require_once '../../includes/config.php'; // Adjust path to config.php

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit;
}

if (!isset($_GET['order_id'])) {
    $response['message'] = 'Order ID missing.';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = intval($_GET['order_id']);

// Fetch order items for the given order_id and user_id
// Ensure the order belongs to the logged-in user for security
$stmt = $conn->prepare("
    SELECT
        oi.quantity,
        oi.price,
        p.name,
        p.image,
        p.unit,
        f.name as farmer_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN farmers f ON p.farmer_id = f.id
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.order_id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $row['image'] = !empty($row['image']) ? BASE_URL . 'uploads/products/' . htmlspecialchars($row['image']) : BASE_URL . 'assets/images/default-product.png';
        $items[] = $row;
    }
    $response['success'] = true;
    $response['items'] = $items;
} else {
    $response['message'] = 'No items found for this order or order does not belong to user.';
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>