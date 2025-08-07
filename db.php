<?php
// db.php - conexión básica
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'agenda';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
