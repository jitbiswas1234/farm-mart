<?php
session_start();
require_once("../includes/config.php");

if (!isset($_SESSION['user_id'])) exit;

$user_id = $_SESSION['user_id'];

$receiver_id = intval($_POST['receiver_id'] ?? 0);

// We remove the sender_type and receiver_type conditions for now to fix the display issue,
// as the previous db schema might not be storing these fields correctly or consistently.
$stmt = $conn->prepare("
SELECT * FROM messages
WHERE 
(sender_id=? AND receiver_id=?)
OR
(sender_id=? AND receiver_id=?)
ORDER BY created_at ASC
");

$stmt->bind_param(
    "iiii",
    $user_id, $receiver_id,
    $receiver_id, $user_id
);

$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "";
    exit;
}

while ($row = $res->fetch_assoc()) {
    $is_me = ($row['sender_id'] == $user_id);

    echo "<div class='msg ".($is_me?'sent':'received')."'>";
    
    if($row['message']){
        echo htmlspecialchars($row['message']);
    }

    if($row['file_path']){
        $fileName = htmlspecialchars(basename($row['file_path']));
        $fileLink = "../uploads/chat/" . $row['file_path'];
        
        $ext = strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION));
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        
        echo "<br>";
        if($isImage) {
            echo "<a href='$fileLink' target='_blank'><img src='$fileLink' style='max-width: 150px; border-radius: 8px; margin-top: 5px; border: 2px solid rgba(255,255,255,0.2);'></a>";
        } else {
            echo "<a href='$fileLink' target='_blank' style='color: inherit; text-decoration: underline;'><i class='bi bi-paperclip'></i> $fileName</a>";
        }
    }

    echo "<div class='msg-time'>".date('h:i A',strtotime($row['created_at']))."</div>";
    echo "</div>";
}

// Mark messages as read since they are being fetched
if ($receiver_id > 0) {
    $update_stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
    if($update_stmt) {
        $update_stmt->bind_param("ii", $receiver_id, $user_id);
        $update_stmt->execute();
        $update_stmt->close();
    }
}
?>