<?php
session_start();
require_once("../includes/config.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

$sender_id = $_SESSION['user_id'];
$sender_type = 'user';

$receiver_id = intval($_POST['receiver_id'] ?? 0);
$receiver_type = $_POST['receiver_type'] ?? 'user';

$message = trim($_POST['message'] ?? '');
$file_path = null;

/* ===== FILE UPLOAD ===== */
if (isset($_FILES['chat_file']) && $_FILES['chat_file']['error'] === 0) {
    $upload_dir = "../../uploads/chat/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = time() . "_" . basename($_FILES['chat_file']['name']);
    $target = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['chat_file']['tmp_name'], $target)) {
        $file_path = $file_name;
    }
}

/* ===== VALIDATION ===== */
if (empty($message) && empty($file_path)) {
    echo json_encode(['success' => false, 'msg' => 'Empty message']);
    exit;
}

/* ===== INSERT MESSAGE ===== */
$stmt = $conn->prepare("
INSERT INTO messages 
(sender_id, sender_type, receiver_id, receiver_type, message, file_path, created_at) 
VALUES (?, ?, ?, ?, ?, ?, NOW())
");

$stmt->bind_param(
    "isisss",
    $sender_id,
    $sender_type,
    $receiver_id,
    $receiver_type,
    $message,
    $file_path
);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'msg' => 'DB error']);
}