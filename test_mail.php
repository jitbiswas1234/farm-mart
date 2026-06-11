<?php

require_once("includes/mail.php");

if(sendMail(

"biswasjit862@gmail.com",

"Test Mail",

"Mail working"

))

echo "Sent";

else

echo "Failed";