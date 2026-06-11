<?php

require_once("../includes/auth.php");

if($_SESSION['role']!="admin")
{
header("Location: ../login.php");
exit();
}

require_once("../includes/config.php");


$farmer_id=isset($_GET['id']) ? intval($_GET['id']) : 0;

if($farmer_id<=0)
{
header("Location: manage_farmers.php");
exit();
}



$success="";
$error="";


// FETCH FARMER + USER DATA

$stmt=$conn->prepare("

SELECT f.*,u.name as user_name,
u.email as user_email,
u.phone as user_phone,
u.id as user_id

FROM farmers f

JOIN users u
ON f.user_id=u.id

WHERE f.id=?

");

$stmt->bind_param("i",$farmer_id);

$stmt->execute();

$result=$stmt->get_result();

$farmer=$result->fetch_assoc();

$stmt->close();


if(!$farmer)
{
header("Location: manage_farmers.php");
exit();
}



// UPDATE FORM

if($_SERVER['REQUEST_METHOD']=="POST")
{

$name=trim($_POST['name'] ?? '');

$phone=trim($_POST['phone'] ?? '');

$village=trim($_POST['village'] ?? '');

$status=$_POST['status'] ?? 'pending';

$rating=floatval($_POST['rating'] ?? 0);



// FORCE VALID STATUS

$allowed=['pending','approved','suspended'];

if(!in_array($status,$allowed,true))
{
$status='pending';
}



// VALIDATION

if(empty($name) || empty($village))
{
$error="Required fields missing";
}

elseif($rating<0 || $rating>5)
{
$error="Rating must be between 0 and 5";
}

else
{


// UPDATE USER TABLE

$user_update=$conn->prepare("

UPDATE users

SET name=?,phone=?

WHERE id=?

");

$user_update->bind_param(

"ssi",

$name,
$phone,
$farmer['user_id']

);

$user_update->execute();

$user_update->close();




// UPDATE FARMER TABLE

$farm_update=$conn->prepare("

UPDATE farmers

SET name=?,
phone=?,
village=?,
status=?,
rating=?

WHERE id=?

");

$farm_update->bind_param(

"ssssdi",

$name,
$phone,
$village,
$status,
$rating,
$farmer_id

);


if($farm_update->execute())
{

$success="Farmer updated successfully";


// ROLE MANAGEMENT

if($status=="approved")
{

$conn->query("

UPDATE users

SET role='farmer'

WHERE id=".$farmer['user_id']

);

}

elseif($status=="pending" || $status=="suspended")
{

$conn->query("

UPDATE users

SET role='user'

WHERE id=".$farmer['user_id']

);

}



// UPDATE LOCAL DATA

$farmer['name']=$name;

$farmer['phone']=$phone;

$farmer['village']=$village;

$farmer['status']=$status;

$farmer['rating']=$rating;

}
else
{

$error="Update failed";

}

$farm_update->close();

}

}



$page_title="Edit Farmer";

require_once("../includes/header.php");

require_once("../includes/navbar.php");

?>



<main class="container py-5">

<div class="row g-5">

<?php require_once("../includes/admin_sidebar.php"); ?>


<div class="col-lg-9">

<h3 class="fw-bold mb-4">

Edit Farmer

</h3>


<?php if($success): ?>

<div class="alert alert-success">

<?= $success ?>

</div>

<?php endif; ?>


<?php if($error): ?>

<div class="alert alert-danger">

<?= $error ?>

</div>

<?php endif; ?>



<div class="card shadow">

<div class="card-body p-4">

<form method="POST">


<div class="row">


<div class="col-md-6">

<label class="fw-bold">

Farmer Name

</label>

<input type="text"

name="name"

class="form-control"

value="<?= htmlspecialchars($farmer['name']) ?>"

required>

</div>



<div class="col-md-6">

<label class="fw-bold">

Email

</label>

<input type="email"

class="form-control"

value="<?= htmlspecialchars($farmer['user_email']) ?>"

readonly>

</div>


</div>



<div class="mt-3">

<label class="fw-bold">

Phone

</label>

<input type="text"

name="phone"

class="form-control"

value="<?= htmlspecialchars($farmer['phone']) ?>">

</div>



<div class="mt-3">

<label class="fw-bold">

Village

</label>

<input type="text"

name="village"

class="form-control"

value="<?= htmlspecialchars($farmer['village']) ?>"

required>

</div>



<div class="mt-3">

<label class="fw-bold">

Status

</label>

<select name="status"

class="form-select">

<option value="pending"
<?= $farmer['status']=='pending'?'selected':'' ?>>

Pending

</option>

<option value="approved"
<?= $farmer['status']=='approved'?'selected':'' ?>>

Approved

</option>

<option value="suspended"
<?= $farmer['status']=='suspended'?'selected':'' ?>>

Suspended

</option>

</select>

</div>



<div class="mt-3">

<label class="fw-bold">

Rating

</label>

<input type="number"

step="0.1"

min="0"

max="5"

name="rating"

class="form-control"

value="<?= $farmer['rating'] ?>">

</div>



<div class="text-end">

<button type="submit"

class="btn btn-success mt-4">

Update Farmer

</button>

</div>


</form>

</div>

</div>

</div>

</div>

</main>



<?php require_once("../includes/footer.php"); ?>