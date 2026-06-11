<?php
$conn = mysqli_connect('localhost', 'root', '', 'farmer');
if (!$conn) {
    die('DB connection failed: ' . mysqli_connect_error());
}

$user_id = 1;
$title = 'Test Notification';
$message = 'Verification insert for checkout fix';

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, title, message, is_read, created_at) VALUES (?, 'user', ?, ?, 0, NOW())");
    if (!$stmt) {
        throw new Exception('prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('iss', $user_id, $title, $message);
    if (!$stmt->execute()) {
        throw new Exception('execute failed: ' . $stmt->error);
    }
    $insert_id = $stmt->insert_id;
    $stmt->close();

    $del = $conn->prepare('DELETE FROM notifications WHERE id = ?');
    $del->bind_param('i', $insert_id);
    $del->execute();
    $del->close();

    $conn->commit();
    echo 'PASS: notification insert/delete succeeded';
} catch (Throwable $e) {
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    echo 'FAIL: ' . $e->getMessage();
}
?>