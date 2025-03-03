<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener historial de movimientos
$query = "SELECT h.id, h.nuc_id, h.area_origen, h.area_destino, h.comentario, h.fecha_movimiento, u.full_name 
          FROM historiales h
          JOIN users u ON h.usuario_id = u.id
          ORDER BY h.fecha_movimiento DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Movimientos</title>
</head>
<body>
    <h2>Historial de Movimientos</h2>

    <table border="1">
        <tr>
            <th>NUC</th>
            <th>Área Origen</th>
            <th>Área Destino</th>
            <th>Comentario</th>
            <th>Fecha</th>
            <th>Usuario</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['nuc_id']); ?></td>
                <td><?php echo htmlspecialchars($row['area_origen']); ?></td>
                <td><?php echo htmlspecialchars($row['area_destino']); ?></td>
                <td><?php echo htmlspecialchars($row['comentario']); ?></td>
                <td><?php echo htmlspecialchars($row['fecha_movimiento']); ?></td>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <a href="index.php">Volver</a>
</body>
</html>
