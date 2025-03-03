<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'config.php';

// Obtener usuario actual
$user_id = $_SESSION['user_id'];
$query = "SELECT u.full_name, r.role_name FROM users u
          JOIN user_roles ur ON u.id = ur.user_id
          JOIN roles r ON ur.role_id = r.role_id
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Bienvenido, <?php echo htmlspecialchars($user['full_name']); ?></h2>
    <p>Rol: <?php echo htmlspecialchars($user['role_name']); ?></p>

    <ul>
        <li><a href="usuarios.php">Gestión de Usuarios</a></li>
        <li><a href="areas.php">Gestión de Áreas</a></li>
        <li><a href="historial.php">Historial de Movimientos</a></li>
        <li><a href="asignar_movimiento.php">Asignar Movimiento</a></li>
        <li><a href="logout.php">Cerrar Sesión</a></li>
    </ul>
</body>
</html>
