<?php

require_once("includes/config.php");
require_once("includes/function.php");
require_once("includes/mail.php");

$msg="";
$msgType="info";

if(isset($_POST['register']))
{

$name=clean($_POST['name']);
$email=clean($_POST['email']);
$password=password_hash($_POST['password'],PASSWORD_DEFAULT);
$phone=clean($_POST['phone']);

$allowed_roles=['user','farmer'];

$role=in_array($_POST['role'],$allowed_roles)
? $_POST['role']
: 'user';


// CHECK EMAIL

$stmt=$conn->prepare(
"SELECT id FROM users WHERE email=?"
);

$stmt->bind_param("s",$email);

$stmt->execute();

$stmt->store_result();

if($stmt->num_rows>0)
{

$msg="Email already exists";
$msgType="danger";

}
else
{

// OTP

$otp=rand(100000,999999);

$expiry=date(
"Y-m-d H:i:s",
strtotime("+5 minutes")
);


// INSERT USER (NOT VERIFIED)

$insert=$conn->prepare(

"INSERT INTO users
(name,email,password,phone,role,status,otp,otp_expiry,is_verified)

VALUES(?,?,?,?,?,'active',?,?,0)"

);

$insert->bind_param(

"sssssss",

$name,
$email,
$password,
$phone,
$role,
$otp,
$expiry

);

$insert->execute();

$user_id=$insert->insert_id;


// CREATE FARMER PROFILE IF ROLE FARMER

if($role=='farmer')
{

$f=$conn->prepare(

"INSERT INTO farmers
(name,email,phone,user_id,status)

VALUES(?,?,?,?, 'pending')"

);

$f->bind_param(

"ssis",

$name,
$email,
$phone,
$user_id

);

$f->execute();

}


// SEND OTP MAIL

$body="

<h2>FarmMart Email Verification</h2>

<p>Hello <b>$name</b></p>

<p>Your verification OTP:</p>

<h1>$otp</h1>

<p>Valid for 5 minutes</p>

<p>Do not share this code.</p>

";

sendMail(

$email,

"FarmMart OTP Verification",

$body

);


// REDIRECT VERIFY PAGE

header(
"Location: verify_otp.php?email=".$email
);

exit();

}

}


$page_title="Register";

include("includes/header.php");

?>

<style>

:root {
    --primary: #2E7D32;
    --secondary: #66BB6A;
    --accent: #FFA000;
    --bg-color: #F1F8E9;
    --text-dark: #263238;
}

.register-main{
    background: linear-gradient(135deg, var(--bg-color) 0%, #ffffff 100%);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
}

.card{
    border-radius:18px;
    border:none;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
    background: #fff;
}

.card-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: #fff;
    border-bottom: none;
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
    padding: 24px;
}

.role-toggle{
    display:flex;
    gap:6px;
    background:#f1f3f5;
    padding:4px;
    border-radius:999px;
    margin-bottom:20px;
}

.role-toggle button{
    flex:1;
    border:none;
    background:none;
    padding:7px 0;
    border-radius:999px;
    cursor:pointer;
    font-weight: 500;
    font-size: 0.9rem;
    color: #495057;
    transition: all 0.2s ease;
}

.role-toggle button.active{
    background: var(--primary);
    color:white;
    box-shadow: 0 4px 12px rgba(46, 125, 50, 0.25);
}

.form-control {
    border-radius: 12px;
    border: 1px solid #dee2e6;
    padding: 10px 14px;
    font-size: 0.95rem;
    height: 44px;
    transition: all 0.2s ease;
}

.form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.08);
    outline: none;
}

label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 6px;
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
    text-decoration: none;
}

p.text-center a:hover {
    color: #1b491e;
}

</style>


<main class="register-main">

<div class="container">

<div class="row justify-content-center">

<div class="col-md-4">

<div class="card">

<div class="card-header text-center">

<h4>Create Account</h4>

<small>Email verification required</small>

</div>

<div class="card-body">


<div class="role-toggle">

<button 
type="button"
class="active"
data-role="user">

Customer

</button>


<button 
type="button"
data-role="farmer">

Farmer

</button>

</div>


<?php

if($msg!="")
echo "<div class='alert alert-$msgType'>$msg</div>";

?>


<form method="POST">

<input 
type="hidden"
name="role"
id="role"
value="user">


<div class="mb-3">

<label>Full Name</label>

<input 
type="text"
name="name"
class="form-control"
required>

</div>


<div class="mb-3">

<label>Email</label>

<input 
type="email"
name="email"
class="form-control"
required>

</div>


<div class="mb-3">

<label>Password</label>

<input 
type="password"
name="password"
class="form-control"
required>

</div>


<div class="mb-3">

<label>Phone</label>

<input 
type="text"
name="phone"
class="form-control">

</div>


<button 
name="register"
class="btn btn-success w-100">

Register

</button>


<p class="text-center mt-3">

Already have account?

<a href="login.php">

Login

</a>

</p>


</form>

</div>

</div>

</div>

</div>

</div>

</main>


<script>

const buttons=
document.querySelectorAll(".role-toggle button");

const role=
document.getElementById("role");

buttons.forEach(btn=>{

btn.onclick=()=>{

buttons.forEach(
b=>b.classList.remove("active")
);

btn.classList.add("active");

role.value=
btn.getAttribute("data-role");

};

});

</script>

<?php include("includes/footer.php"); ?>