<?php
// config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username = "root"; // Ajusta según tu configuración
$password = "";     // Si tienes contraseña, agrégala aquí
$dbname = "direccion"; // Asegúrate que el nombre de la base es correcto

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>