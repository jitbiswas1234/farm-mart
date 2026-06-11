<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__.'/PHPMailer-master/src/Exception.php';
require_once __DIR__.'/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__.'/PHPMailer-master/src/SMTP.php';


// CONFIG
define('MAIL_HOST','smtp.gmail.com');
define('MAIL_PORT',587);


function sendMail($to,$subject,$body)
{

$mail=new PHPMailer(true);

try{

$mail->isSMTP();

$mail->Host=MAIL_HOST;

$mail->SMTPAuth=true;

$mail->Username=MAIL_USER;

$mail->Password=MAIL_PASS;

$mail->SMTPSecure=PHPMailer::ENCRYPTION_STARTTLS;

$mail->Port=MAIL_PORT;


// IMPROVEMENTS

$mail->CharSet='UTF-8';

$mail->isHTML(true);

$mail->setFrom(

MAIL_USER,

'FarmMart'

);

$mail->addAddress($to);


// PROFESSIONAL EMAIL SETTINGS

$mail->SMTPKeepAlive=true;

$mail->Timeout=30;


// CONTENT

$mail->Subject=$subject;

$mail->Body=$body;

$mail->AltBody=strip_tags($body);


// SEND

$mail->send();

return true;

}
catch(Exception $e){

error_log($mail->ErrorInfo);

return false;

}

}