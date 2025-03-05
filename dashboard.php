<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'config.php';

// Obtener datos del usuario
$user_id = $_SESSION['user_id'];
$area_id = $_SESSION['area_id'];

$query = "SELECT full_name FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Definir las opciones del menú según el área
$menu = [
    NULL => "Menú Super Administrador",
    1 => "Menú Informática",
    2 => "Menú Jurídico",
    3 => "Menú Dirección",
    4 => "Menú Vinculación"
];

$area_nombre = $menu[$area_id] ?? "Área Desconocida";

// Obtener los NUCs que están en el área del usuario
$stmt = $conn->prepare("
    SELECT c.id_nuc, c.nuc, c.municipio, c.localidad 
    FROM historiales h
    JOIN ingresos c ON h.nuc_id = c.id_nuc
    WHERE h.area_destino = ?
");
$stmt->bind_param("i", $area_id);
$stmt->execute();
$nucs = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control</title>
</head>
<body>
    <h2>Bienvenido, <?php echo htmlspecialchars($user['full_name']); ?></h2>
    <p>Área: <?php echo htmlspecialchars($area_nombre); ?></p>

    <ul>
        <?php if ($area_id == NULL): ?>
            <li><a href="validacion_curp.php">Ingresar nuevo expediente</a></li>
            <li><a href="usuarios.php">Gestión de Usuarios</a></li>
            <li><a href="areas.php">Gestión de Áreas</a></li>
            <li><a href="historial.php">Historial de Movimientos</a></li>
            <li><a href="asignar_movimiento.php">Asignar Movimiento</a></li>
        <?php elseif ($area_id == 1): ?>
            <li><a href="#">Acciones para Informática</a></li>
        <?php elseif ($area_id == 2): ?>
            <li><a href="#">Acciones para Jurídico</a></li>
        <?php elseif ($area_id == 3): ?>
            <li><a href="#">Acciones para Dirección</a></li>
        <?php elseif ($area_id == 4): ?>
            <li><a href="#">Acciones para Vinculación</a></li>
        <?php endif; ?>
        
        <li><a href="logout.php">Cerrar Sesión</a></li>
    </ul>

    <h3>NUCs en tu área</h3>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>NUC</th>
            <th>Municipio</th>
            <th>Localidad</th>
        </tr>
        <?php while ($row = $nucs->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['id_nuc']); ?></td>
                <td><?php echo htmlspecialchars($row['nuc']); ?></td>
                <td><?php echo htmlspecialchars($row['municipio']); ?></td>
                <td><?php echo htmlspecialchars($row['localidad']); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
