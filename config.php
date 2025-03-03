<?php
// Verifica si la sesión no ha sido iniciada antes de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username = "root"; // Ajusta según tu configuración
$password = ""; // Si tienes una contraseña, agrégala aquí
$dbname = "direccion"; // Asegúrate de que el nombre de la base es correcto

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
