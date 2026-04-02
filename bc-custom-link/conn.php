<?php
$server    = 'localhost';
$username    = '';
$password    = '';
$database    = '';
$time_secs   = 660;

$connect = null;

// Database is optional for link generation (used only for logging).
if ($server !== '' && $username !== '' && $database !== '') {
    $connect = @mysqli_connect($server, $username, $password, $database);
    if ($connect) {
        mysqli_set_charset($connect, 'utf8mb4');
    }
}
?>