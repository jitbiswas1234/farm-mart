<?php

// Start session only if it hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id']))
{

header("Location: ../login.php");
exit();

}

?>