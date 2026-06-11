<?php
session_start(); // Ensure session is started for messages

require_once("../includes/auth.php");

if($_SESSION['role']!="admin") {
    header("Location: ../login.php");
    exit();
}

require_once("../includes/config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = trim($_POST['status']);

    // Validate status
    $allowed_statuses = ['pending', 'processing', 'delivered', 'cancelled'];
    if (!in_array($new_status, $allowed_statuses)) {
        $_SESSION['error_message'] = "Invalid status provided.";
        header("Location: view_order.php?id=" . $order_id);
        exit;
    }

    // First get the user_id to send the notification to
    $u_stmt = $conn->prepare("SELECT user_id FROM orders WHERE id = ?");
    $u_stmt->bind_param("i", $order_id);
    $u_stmt->execute();
    $res = $u_stmt->get_result();
    $order_data = $res->fetch_assoc();
    $u_stmt->close();

    if(!$order_data) {
        $_SESSION['error_message'] = "Order not found.";
        header("Location: all_orders.php");
        exit;
    }

    $customer_id = $order_data['user_id'];

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $new_status, $order_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Order status updated successfully to " . ucfirst($new_status) . ".";
            
            // SEND NOTIFICATION TO USER
            $notif_title = "Order Update";
            $notif_msg = "Your order #ORD-" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . " status has been updated to " . ucfirst($new_status) . ".";
            $user_type = "user"; // Matching the enum values
            
            // Include user_type and title in the INSERT statement
            $n_stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
            if($n_stmt) {
                $n_stmt->bind_param("isss", $customer_id, $user_type, $notif_title, $notif_msg);
                $n_stmt->execute();
                $n_stmt->close();
            }

        } else {
            $_SESSION['error_message'] = "Failed to update order status: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Database error preparing statement.";
    }
    header("Location: view_order.php?id=" . $order_id);
    exit;
} else {
    $_SESSION['error_message'] = "Invalid request to update order status.";
    header("Location: all_orders.php"); // Redirect to all orders if request is invalid
    exit;
}
?>