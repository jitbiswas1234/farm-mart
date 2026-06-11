<?php

session_start();
header('Content-Type: application/json');

require_once("../includes/config.php");
require_once("../includes/function.php");
require_once("../includes/mail.php");
require_once("../includes/db.php");

$response = ['success' => false, 'message' => '', 'redirect' => ''];

if (isset($_POST['register'])) {
    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = clean($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'user';

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($phone)) {
        $response['message'] = "All fields are required.";
        echo json_encode($response);
        exit();
    }

    if ($password !== $confirm_password) {
        $response['message'] = "Passwords do not match.";
        echo json_encode($response);
        exit();
    }

    if (strlen($password) < 8) {
        $response['message'] = "Password must be at least 8 characters.";
        echo json_encode($response);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Invalid email format.";
        echo json_encode($response);
        exit();
    }

    // Check allowed roles
    $allowed_roles = ['user', 'farmer'];
    if (!in_array($role, $allowed_roles)) {
        $role = 'user';
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $response['message'] = "Email already registered. Please login or use a different email.";
        $stmt->close();
        echo json_encode($response);
        exit();
    }
    $stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Generate OTP
    $otp = rand(100000, 999999);
    $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

    // Insert user
    $insert = $conn->prepare(
        "INSERT INTO users (name, email, password, phone, role, status, otp, otp_expiry, is_verified) 
         VALUES (?, ?, ?, ?, ?, 'active', ?, ?, 0)"
    );

    $insert->bind_param("sssssss", $name, $email, $hashed_password, $phone, $role, $otp, $expiry);
    
    if ($insert->execute()) {
        $user_id = $insert->insert_id;

        // Create farmer profile if role is farmer
        if ($role == 'farmer') {
            $farmer = $conn->prepare(
                "INSERT INTO farmers (name, email, phone, user_id, status) 
                 VALUES (?, ?, ?, ?, 'pending')"
            );
            $farmer->bind_param("ssis", $name, $email, $phone, $user_id);
            $farmer->execute();
            $farmer->close();
        }

        // Send OTP email
        $body = "
            <h2>FarmMart Email Verification</h2>
            <p>Hello <b>" . htmlspecialchars($name) . "</b>,</p>
            <p>Welcome to FarmMart! Your verification OTP is:</p>
            <h1 style='color: #2E7D32; font-weight: 800;'>" . $otp . "</h1>
            <p><strong>Valid for: 5 minutes</strong></p>
            <p>Do not share this code with anyone.</p>
            <hr>
            <p><em>If you didn't create this account, please ignore this email.</em></p>
        ";

        sendMail($email, "FarmMart - Email Verification (OTP: $otp)", $body);

        $response['success'] = true;
        $response['message'] = "Registration successful! Please verify your email.";
        $response['redirect'] = BASE_URL . "verify_otp.php?email=" . urlencode($email);
    } else {
        $response['message'] = "Registration failed. Please try again.";
    }

    $insert->close();
} else {
    $response['message'] = "Invalid request.";
}

echo json_encode($response);
exit();
?>
