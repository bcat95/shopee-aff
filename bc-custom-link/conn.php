<?php
$server    = 'localhost';
$username    = '';
$password    = '';
$database    = '';
$time_secs   = 660;

$connect = mysqli_connect($server, $username, $password,$database);
if (!$connect){  die("Can't conect to server" . mysqli_error());}

mysqli_query($connect, "SET NAMES 'utf8'");
?>