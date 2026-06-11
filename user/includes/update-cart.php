<?php

session_start();

require_once '../../includes/config.php';

header("Content-Type: application/json");


/* SECURITY */

if(!isset($_SESSION['user_id']))
{

echo json_encode([
'success'=>false,
'message'=>'Login required'
]);

exit();

}


$user_id=$_SESSION['user_id'];


/* GET JSON DATA */

$input=json_decode(file_get_contents("php://input"),true);

if(!$input)
{

echo json_encode([
'success'=>false,
'message'=>'Invalid data'
]);

exit();

}


$cart_id=intval($input['cart_id'] ?? 0);

$action=$input['action'] ?? '';

if(!$cart_id || !$action)
{

echo json_encode([
'success'=>false,
'message'=>'Missing data'
]);

exit();

}



/* FETCH CART ITEM */

$stmt=$conn->prepare("

SELECT 
c.quantity,
c.product_id,

p.stock,
p.price

FROM cart c

JOIN products p 
ON c.product_id=p.id

WHERE c.id=?
AND c.user_id=?

LIMIT 1

");

$stmt->bind_param("ii",$cart_id,$user_id);

$stmt->execute();

$res=$stmt->get_result();

$item=$res->fetch_assoc();


if(!$item)
{

echo json_encode([
'success'=>false,
'message'=>'Item not found'
]);

exit();

}


$current_qty=intval($item['quantity']);

$stock=intval($item['stock']);

$price=floatval($item['price']);



/* UPDATE LOGIC */

$new_qty=$current_qty;


if($action=='inc')
{

if($current_qty>=$stock)
{

echo json_encode([
'success'=>false,
'message'=>'Stock limit reached'
]);

exit();

}

$new_qty++;

}


elseif($action=='dec')
{

if($current_qty<=1)
{

echo json_encode([
'success'=>false,
'message'=>'Minimum quantity is 1'
]);

exit();

}

$new_qty--;

}

else
{

echo json_encode([
'success'=>false,
'message'=>'Invalid action'
]);

exit();

}



/* UPDATE DATABASE */

$update=$conn->prepare("

UPDATE cart
SET quantity=?
WHERE id=?
AND user_id=?

");

$update->bind_param("iii",$new_qty,$cart_id,$user_id);

$update->execute();



/* CALCULATE SUMMARY */

$sum=$conn->prepare("

SELECT 

SUM(c.quantity*p.price) subtotal,

SUM(c.quantity) total_items

FROM cart c

JOIN products p 
ON c.product_id=p.id

WHERE c.user_id=?

");

$sum->bind_param("i",$user_id);

$sum->execute();

$summary=$sum->get_result()->fetch_assoc();



$subtotal=floatval($summary['subtotal'] ?? 0);

$total_items=intval($summary['total_items'] ?? 0);


$delivery_fee=$subtotal>=500 ? 0 : 40;

$total=$subtotal+$delivery_fee;



/* RESPONSE */

echo json_encode([

'success'=>true,

'quantity'=>$new_qty,

'item_total'=>number_format($new_qty*$price,2),

'summary'=>[

'subtotal'=>number_format($subtotal,2),

'total'=>number_format($total,2),

'delivery_fee'=>$delivery_fee,

'total_items'=>$total_items

]

]);

exit();

?>