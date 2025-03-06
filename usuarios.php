<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$sql = "SELECT u.id, u.username, u.email, u.status, GROUP_CONCAT(r.role_name SEPARATOR ', ') AS roles
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.role_id
        GROUP BY u.id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Administrar Usuarios</title>
</head>
<body>
    <h2>Usuarios Registrados</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Email</th>
            <th>Estado</th>
            <th>Roles</th>
            <th>Acciones</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['username'] ?></td>
                <td><?= $row['email'] ?></td>
                <td><?= $row['status'] ?></td>
                <td><?= $row['roles'] ?></td>
                <td>
                    <a href="editar_usuario.php?id=<?= $row['id'] ?>">Editar</a> |
                    <a href="desactivar.php?id=<?= $row['id'] ?>">Desactivar</a>
                </td>
            </tr>
        <?php } ?>
    </table>
    <a href="dashboard.php">Volver al Dashboard</a>
</body>
</html>
