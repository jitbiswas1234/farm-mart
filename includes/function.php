<?php

function clean($data){

return htmlspecialchars(trim($data));

}

function redirect($page){

header("Location: ".$page);

exit;

}

function passwordEncrypt($password){

return password_hash($password,PASSWORD_DEFAULT);

}

function passwordVerify($password,$hash){

return password_verify($password,$hash);

}

?>