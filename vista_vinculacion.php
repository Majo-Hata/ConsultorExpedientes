<?php
session_start();
require 'config.php';

$user_id = $_SESSION['user_id'];

// Obtener el área del usuario
$area_query = $conn->prepare("SELECT area_id FROM users WHERE id = ?");
$area_query->bind_param("i", $user_id);
$area_query->execute();
$area_result = $area_query->get_result();
$area_id = $area_result->fetch_assoc()['area_id'];

// Obtener los NUCs que están en el área del usuario
$stmt = $conn->prepare("
    SELECT c.id_nuc, c.nuc, c.municipio, c.localidad 
    FROM historiales h
    JOIN cuartaentrega c ON h.nuc_id = c.id_nuc
    WHERE h.area_destino = ?
");
$stmt->bind_param("i", $area_id);
$stmt->execute();
$nucs = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vista Vinculación</title>
</head>
<body>
    <h2>Lista de NUCs - Área Vinculación</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>NUC</th>
            <th>Municipio</th>
            <th>Localidad</th>
        </tr>
        <?php while ($row = $nucs->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id_nuc']; ?></td>
                <td><?php echo $row['nuc']; ?></td>
                <td><?php echo $row['municipio']; ?></td>
                <td><?php echo $row['localidad']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
