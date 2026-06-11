<?php
session_start();
require_once("includes/db.php");

$msg = "";

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email='$email' AND role='farmer' AND status='active' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = 'farmer';
            $_SESSION['profile_picture'] = $user['profile_picture'];

            header("Location: vendor/includes/dashboard.php");
            exit();
        } else {
            $msg = "Wrong password";
        }
    } else {
        $msg = "Farmer account not found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Farmer Login - FarmMart</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        :root {
            --primary: #2E7D32;
            --secondary: #66BB6A;
            --accent: #FFA000;
            --bg-color: #F1F8E9;
            --text-dark: #263238;
        }

        body {
            background: linear-gradient(135deg, var(--bg-color) 0%, #ffffff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--text-dark);
        }

        .card {
            border-radius: 18px;
            border: none;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
            background: #fff;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: #fff;
            border-bottom: none;
            text-align: center;
            padding: 22px 24px 18px;
        }

        .card-header h4 {
            margin: 0;
            font-weight: 600;
            font-size: 1.4rem;
            margin-bottom: 6px;
        }

        .card-header small {
            display: block;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .card-header .badge {
            background-color: rgba(255, 255, 255, 0.3);
            font-size: 0.75rem;
        }

        .card-body {
            padding: 24px 24px 26px;
        }

        label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 6px;
        }

        .form-control {
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 0.95rem;
            height: 44px;
            border: 1px solid #dee2e6;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.08);
            outline: none;
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 14px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #adb5bd;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .alert {
            border-radius: 12px;
            font-size: 0.9rem;
            padding: 10px 12px;
            margin-bottom: 18px;
        }

        .btn-success.w-100 {
            border-radius: 12px;
            font-weight: 600;
            padding: 10px 0;
            margin-top: 6px;
            background-color: var(--primary);
            border-color: var(--primary);
            transition: all 0.3s ease;
        }

        .btn-success.w-100:hover {
            background-color: #1b491e;
            border-color: #1b491e;
            transform: translateY(-2px);
        }

        p.text-center a {
            color: var(--primary);
            font-weight: 500;
        }

        p.text-center a:hover {
            color: #1b491e;
        }

        .info-box {
            background: var(--bg-color);
            border-left: 4px solid var(--primary);
            padding: 12px 14px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 0.9rem;
        }

        .info-box i {
            color: var(--primary);
            margin-right: 8px;
        }
    </style>

</head>

<body>

    <div class="container mt-5">

        <div class="row justify-content-center">

            <div class="col-md-4">

                <div class="card">

                    <div class="card-header text-center">

                        <h4><i class="bi bi-shop"></i> Farmer Portal</h4>
                        <small>Manage your products & orders</small>

                    </div>

                    <div class="card-body">

                        <div class="info-box">
                            <i class="bi bi-info-circle-fill"></i>
                            <strong>Farmer Account Login</strong>
                            <br>
                            <small>Access your farmer dashboard and manage your farm business.</small>
                        </div>

                        <?php if ($msg != "") { ?>

                            <div class="alert alert-danger d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo htmlspecialchars($msg); ?>
                            </div>

                        <?php } ?>

                        <form method="POST">

                            <div class="mb-3">
                                <label>Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="your@farm.email" required>
                            </div>

                            <div class="mb-3">
                                <label>Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="password" id="passwordField" class="form-control" placeholder="Enter your password" required>
                                    <span class="password-toggle" id="togglePassword">
                                        <i class="bi bi-eye-slash" id="togglePasswordIcon"></i>
                                    </span>
                                </div>
                            </div>

                            <button type="submit" name="login" class="btn btn-success w-100">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login to Dashboard
                            </button>

                            <p class="text-center mt-3 mb-0">
                                Don't have a farmer account?
                                <a href="register.php?role=farmer" class="text-decoration-none">Register as Farmer</a>
                            </p>

                            <hr class="my-3">

                            <p class="text-center mb-0">
                                <small>
                                    <a href="login.php" class="text-decoration-none" style="color: #666;">Back to Customer Login</a>
                                </small>
                            </p>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Small JS to handle show/hide password -->
    <script>
        // Show / hide password
        const passwordField = document.getElementById('passwordField');
        const togglePassword = document.getElementById('togglePassword');
        const togglePasswordIcon = document.getElementById('togglePasswordIcon');

        if (togglePassword && passwordField && togglePasswordIcon) {
            togglePassword.addEventListener('click', function () {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                togglePasswordIcon.classList.toggle('bi-eye');
                togglePasswordIcon.classList.toggle('bi-eye-slash');
            });
        }
    </script>

</body>

</html>
