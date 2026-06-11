<?php
session_start(); // Ensure session is started for messages

require_once("../includes/auth.php");

if($_SESSION['role']!="admin") {
    header("Location: ../login.php");
    exit();
}

require_once("../includes/config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['farmer_id'], $_POST['status'])) {
    $farmer_id = intval($_POST['farmer_id']);
    $new_status = trim($_POST['status']);

    // Validate status
    $allowed_statuses = ['pending', 'approved', 'rejected'];
    if (!in_array($new_status, $allowed_statuses)) {
        $_SESSION['error_message'] = "Invalid status provided.";
        header("Location: manage_farmers.php");
        exit;
    }

    $stmt = $conn->prepare("UPDATE farmers SET status = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $new_status, $farmer_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Farmer status updated successfully to " . ucfirst($new_status) . ".";
        } else {
            $_SESSION['error_message'] = "Failed to update farmer status: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Database error preparing statement.";
    }
    header("Location: manage_farmers.php");
    exit;
} else {
    $_SESSION['error_message'] = "Invalid request to update farmer status.";
    header("Location: manage_farmers.php"); // Redirect to all farmers if request is invalid
    exit;
}
?>