<?php
$conn = mysqli_connect('localhost', 'root', '', 'farmer');
if (!$conn) {
    die('DB connection failed: ' . mysqli_connect_error());
}
$result = mysqli_query($conn, 'SHOW COLUMNS FROM orders');
while ($row = mysqli_fetch_assoc($result)) {
    echo htmlspecialchars(json_encode($row)) . "<br>";
}
?>