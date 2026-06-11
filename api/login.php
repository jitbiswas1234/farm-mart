<?php

session_start();
header('Content-Type: application/json');

require_once("../includes/db.php");

$response = ['success' => false, 'message' => '', 'redirect' => ''];

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $selected_role = $_POST['selected_role'] ?? 'user';

    $sql = "SELECT * FROM users WHERE email='$email' AND status='active' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            // Check if the selected role matches the user's actual role
            if (trim($user['role']) === $selected_role) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = trim($user['role']);
                $_SESSION['profile_picture'] = $user['profile_picture'];

                $role = $_SESSION['role'];

                if ($role == "admin") {
                    $response['redirect'] = BASE_URL . "admin/dashboard.php";
                } elseif ($role == "farmer") {
                    $response['redirect'] = BASE_URL . "vendor/includes/dashboard.php";
                } elseif ($role == "user") {
                    $response['redirect'] = BASE_URL . "user/includes/dashboard.php";
                }

                $response['success'] = true;
                $response['message'] = "Login successful!";
            } else {
                $response['message'] = "Role mismatch. You are registered as a " . ucfirst(trim($user['role'])) . ".";
                session_unset();
                session_destroy();
            }
        } else {
            $response['message'] = "Incorrect password.";
        }
    } else {
        $response['message'] = "Email not found.";
    }
} else {
    $response['message'] = "Invalid request.";
}

echo json_encode($response);
exit();
?>
