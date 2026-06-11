<?php
// Configuration File
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'farmer');

if (!defined('BASE_URL')) define('BASE_URL', 'http://localhost/login1/');
if (!defined('UPLOAD_PATH')) define('UPLOAD_PATH', __DIR__ . '/../uploads/');
if (!defined('MAIL_USER')) define('MAIL_USER','bjit4225@gmail.com');

if (!defined('MAIL_PASS')) define('MAIL_PASS','ahhd yaoq elou kavy');

// Razorpay API Keys (Test Mode) - Replace with your actual keys from Razorpay Dashboard
if (!defined('RAZORPAY_KEY_ID')) define('RAZORPAY_KEY_ID', 'rzp_test_STui2tERIeLURC');
if (!defined('RAZORPAY_SECRET_KEY')) define('RAZORPAY_SECRET_KEY', 'J3PWrTq8mVSfAkTlpBj20gLg');

// Create uploads folders if not exists
if (!is_dir(UPLOAD_PATH . 'products')) mkdir(UPLOAD_PATH . 'products', 0755, true);
if (!is_dir(UPLOAD_PATH . 'farmers')) mkdir(UPLOAD_PATH . 'farmers', 0755, true);
if (!is_dir(UPLOAD_PATH . 'users')) mkdir(UPLOAD_PATH . 'users', 0755, true);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Kolkata');

// Establish Database Connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>