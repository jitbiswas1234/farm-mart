<?php

require_once("includes/config.php");

$msg="";

$email=$_GET['email'] ?? '';

if(isset($_POST['verify']))
{

$otp=$_POST['otp'];

$stmt=$conn->prepare(

"SELECT otp,otp_expiry 
FROM users 
WHERE email=?"

);

$stmt->bind_param("s",$email);

$stmt->execute();

$user=$stmt->get_result()->fetch_assoc();


if($user)
{

if($otp==$user['otp'])
{

if(strtotime($user['otp_expiry'])>time())
{

$conn->query("

UPDATE users SET
is_verified=1,
otp=NULL

WHERE email='$email'

");

header("Location: login.php?verified=1");

exit();

}
else
$msg="OTP expired";

}
else
$msg="Wrong OTP";

}

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify OTP - FarmMart</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

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
    padding: 20px;
}

.card {
    border-radius: 18px;
    border: none;
    overflow: hidden;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
    background: #fff;
    max-width: 420px;
    width: 100%;
}

.card-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: #fff;
    border-bottom: none;
    text-align: center;
    padding: 24px 24px 20px;
    font-weight: 600;
    font-size: 1.3rem;
}

.card-body {
    padding: 28px;
}

.email-info {
    background: var(--bg-color);
    border-radius: 12px;
    padding: 12px 14px;
    margin-bottom: 20px;
    font-size: 0.9rem;
}

.form-label {
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--text-dark);
    margin-bottom: 8px;
    display: block;
}

.form-control {
    border-radius: 12px;
    padding: 12px 14px;
    font-size: 1rem;
    height: 48px;
    border: 1.5px solid #dee2e6;
    transition: all 0.2s ease;
    letter-spacing: 0.15em;
    text-align: center;
    font-weight: 600;
}

.form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.08);
    outline: none;
}

.form-control::placeholder {
    letter-spacing: 0;
}

.alert {
    border-radius: 12px;
    font-size: 0.9rem;
    padding: 12px 14px;
    margin-bottom: 20px;
    border: none;
}

.alert-danger {
    background-color: #fdecec;
    color: #c3252c;
}

.btn-success {
    border-radius: 12px;
    font-weight: 600;
    padding: 12px 0;
    background-color: var(--primary);
    border-color: var(--primary);
    transition: all 0.3s ease;
    font-size: 1rem;
}

.btn-success:hover {
    background-color: #1b491e;
    border-color: #1b491e;
    transform: translateY(-2px);
}

.helper-text {
    color: #666;
    font-size: 0.85rem;
    margin-top: 8px;
    display: block;
}

.resend-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
}

.resend-link:hover {
    color: #1b491e;
    text-decoration: underline;
}
</style>
</head>
<body>

<div class="card">
<div class="card-header">
Verify Your Email
</div>
<div class="card-body">

<div class="email-info">
<i class="bi bi-info-circle me-2"></i>
We sent a verification code to<br>
<strong><?php echo htmlspecialchars($email); ?></strong>
</div>

<?php
if($msg!="") {
    echo "<div class='alert alert-danger'><i class='bi bi-exclamation-triangle-fill me-2'></i>" . htmlspecialchars($msg) . "</div>";
}
?>

<form method="POST">
<label class="form-label">Enter Verification Code</label>
<input 
type="text"
name="otp"
class="form-control mb-2"
placeholder="000000"
maxlength="6"
inputmode="numeric"
pattern="[0-9]{6}"
required>
<span class="helper-text">Check your email for the 6-digit code. Valid for 5 minutes.</span>

<button 
type="submit"
name="verify"
class="btn btn-success w-100 mt-4">
<i class="bi bi-check-circle me-2"></i>Verify Code
</button>
</form>

<p class="text-center mt-4 mb-0">
<small>
Didn't receive a code?<br>
<a href="register.php" class="resend-link">Register again</a>
</small>
</p>

</div>
</div>

</body>
</html>