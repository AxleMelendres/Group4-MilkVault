<?php
session_start();

$_SESSION = array(); 
session_destroy();   
 
header("Location: ../HTML/customerLogin.html");
exit();
?>
