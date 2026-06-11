<?php
require_once("../includes/config.php");
$res = $conn->query("SELECT * FROM messages LIMIT 5");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
