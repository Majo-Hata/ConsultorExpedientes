<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $role_id = $_POST['role_id'];

    // Verificar si el usuario ya tiene asignado ese rol
    $check_query = $conn->prepare("SELECT * FROM user_roles WHERE user_id = ? AND role_id = ?");
    $check_query->bind_param("ii", $user_id, $role_id);
    $check_query->execute();
    $result = $check_query->get_result();

    if ($result->num_rows == 0) {
        // Si no existe, insertamos el nuevo rol
        $insert_query = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
        $insert_query->bind_param("ii", $user_id, $role_id);
        $insert_query->execute();
    }

    header("Location: usuarios.php");
    exit();
}

$users = $conn->query("SELECT * FROM users WHERE status='active'");
$roles = $conn->query("SELECT * FROM roles");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Asignar Permisos</title>
</head>
<body>
    <h2>Asignar Permisos</h2>
    <form method="POST">
        <label>Usuario:</label>
        <select name="user_id">
            <?php while ($row = $users->fetch_assoc()) { ?>
                <option value="<?= $row['id'] ?>"><?= $row['username'] ?></option>
            <?php } ?>
        </select>
        <label>Rol:</label>
        <select name="role_id">
            <?php while ($row = $roles->fetch_assoc()) { ?>
                <option value="<?= $row['role_id'] ?>"><?= $row['role_name'] ?></option>
            <?php } ?>
        </select>
        <button type="submit">Asignar</button>
    </form>

    <a href="dashboard.php">Volver</a>
</body>
</html>
