<?php
session_start();
require 'config.php';

$user_id = $_SESSION['user_id'];
$area_query = $conn->prepare("SELECT nombre_area FROM areas WHERE id = (SELECT area_id FROM users WHERE id = ?)");
$area_query->bind_param("i", $user_id);
$area_query->execute();
$area_result = $area_query->get_result();
$area = $area_result->fetch_assoc()['nombre_area'];

// Obtener los NUCs que pertenecen a esta área
$stmt = $conn->prepare("SELECT * FROM cuartaentrega WHERE vinculacion = ?");
$stmt->bind_param("s", $area);
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
