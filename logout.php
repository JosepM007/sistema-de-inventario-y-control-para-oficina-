<?php
session_start();
$_SESSION = array();
session_destroy();
session_start();
$_SESSION['success'] = "Sesión cerrada";
header("Location: login.php");
exit;
?>