<?php

session_start();

require_once("includes/db.php");

$msg = "";

if (isset($_POST['login'])) {

    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $selected_role = $_POST['selected_role'] ?? 'user'; // Default to 'user' if not set

    $sql = "SELECT * FROM users WHERE email='$email' AND status='active' LIMIT 1";

    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {

            // Check if the selected role matches the user's actual role in the database
            if (trim($user['role']) === $selected_role) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = trim($user['role']);
                $_SESSION['profile_picture'] = $user['profile_picture']; // Store profile picture in session

                $role = $_SESSION['role'];

                if ($role == "admin") {
                    header("Location: admin/dashboard.php");
                    exit();
                } elseif ($role == "farmer") {
                    header("Location: vendor/includes/dashboard.php");
                    exit();
                } elseif ($role == "user") {
                    header("Location: user/includes/dashboard.php");
                    exit();
                } else {
                    $msg = "Role error: Unknown role assigned.";
                }
            } else {
                // Role mismatch
                $msg = "You are trying to log in as a " . ucfirst($selected_role) . ", but your account is registered as a " . ucfirst(trim($user['role'])) . ". Please select the correct role or contact support.";
                // Clear any potential session data from failed attempt
                session_unset();
                session_destroy();
                // Re-initialize session for error message display if needed
                session_start();

            }

        } else {

            $msg = "Wrong password";

        }

    } else {

        $msg = "User not found";

    }

}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Multi Panel Login</title>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons (optional, for nicer UI) -->
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
        }

        .card-header small {
            display: block;
            margin-top: 6px;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .card-body {
            padding: 24px 24px 26px;
        }

        /* Role toggle: User / Farmer / Admin */
        .role-toggle {
            display: flex;
            gap: 6px;
            background: #f1f3f5;
            border-radius: 999px;
            padding: 4px;
            margin-bottom: 20px;
        }

        .role-toggle button {
            flex: 1;
            border-radius: 999px;
            border: none;
            background: transparent;
            padding: 7px 0;
            font-size: 0.9rem;
            font-weight: 500;
            color: #495057;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .role-toggle button.active {
            background-color: var(--primary);
            color: #fff;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.25);
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
    </style>

</head>

<body>

    <div class="container mt-5">

        <div class="row justify-content-center">

            <div class="col-md-4">

                <div class="card">

                    <div class="card-header text-center">

                        <h4>Sign In</h4>
                        <small>Welcome back</small>

                    </div>

                    <div class="card-body">

                        <!-- Role options: User / Farmer / Admin -->
                        <div class="role-toggle">
                            <button type="button" class="active" data-role="user">User</button>
                            <button type="button" data-role="farmer">Farmer</button>
                            <button type="button" data-role="admin">Admin</button>
                        </div>

                        <?php if ($msg != "") { ?>

                            <div class="alert alert-danger d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo $msg; ?>
                            </div>

                        <?php } ?>

                        <form method="POST">

                            <input type="hidden" name="selected_role" id="selected_role" value="user">

                            <div class="mb-3">
                                <label>Email Address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label>Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="password" id="passwordField" class="form-control" required>
                                    <span class="password-toggle" id="togglePassword">
                                        <i class="bi bi-eye-slash" id="togglePasswordIcon"></i>
                                    </span>
                                </div>
                            </div>

                            <!-- Button location unchanged -->
                            <button type="submit" name="login" class="btn btn-success w-100">
                                Login
                            </button>

                            <!-- New text under the button -->
                            <p class="text-center mt-3 mb-0">
                                Don’t have an account?
                                <a href="register.php" class="text-decoration-none">Register now</a>
                            </p>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Small JS to handle role toggle & show/hide password -->
    <script>
        // Role selector
        const roleButtons = document.querySelectorAll('.role-toggle button');
        const roleInput = document.getElementById('selected_role');

        roleButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                roleButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                if (roleInput) {
                    roleInput.value = btn.getAttribute('data-role');
                }
            });
        });

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