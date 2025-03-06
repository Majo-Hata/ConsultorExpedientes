<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Obtener usuarios activos y roles disponibles
$users = $conn->query("SELECT id, username, role_id FROM users WHERE status='active'");
$roles = $conn->query("SELECT * FROM roles");

// Procesar cambio de rol
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $new_role_id = $_POST['role_id'];

    // Actualizar el rol del usuario
    $update_user_role = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
    $update_user_role->bind_param("ii", $new_role_id, $user_id);
    $update_user_role->execute();
}

// Procesar asignación de permisos
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['role_id'])) {
    $role_id = $_POST['role_id'];

    // Permisos seleccionados
    $permiso_consultar = isset($_POST['permiso_consultar']) ? 1 : 0;
    $permiso_ingresar = isset($_POST['permiso_ingresar']) ? 1 : 0;
    $permiso_capturar = isset($_POST['permiso_capturar']) ? 1 : 0;
    $permiso_baja = isset($_POST['permiso_baja']) ? 1 : 0;

    // Verificar si el rol ya tiene permisos asignados
    $check_query = $conn->prepare("SELECT * FROM permisos WHERE role_id = ?");
    $check_query->bind_param("i", $role_id);
    $check_query->execute();
    $result = $check_query->get_result();

    if ($result->num_rows > 0) {
        // Si existen permisos, actualizar
        $update_query = $conn->prepare("
            UPDATE permisos 
            SET permiso_consultar = ?, permiso_ingresar = ?, permiso_capturar = ?, permiso_baja = ? 
            WHERE role_id = ?");
        $update_query->bind_param("iiiii", $permiso_consultar, $permiso_ingresar, $permiso_capturar, $permiso_baja, $role_id);
        $update_query->execute();
    } else {
        // Si no existen, insertarlos
        $insert_query = $conn->prepare("
            INSERT INTO permisos (role_id, permiso_consultar, permiso_ingresar, permiso_capturar, permiso_baja) 
            VALUES (?, ?, ?, ?, ?)");
        $insert_query->bind_param("iiiii", $role_id, $permiso_consultar, $permiso_ingresar, $permiso_capturar, $permiso_baja);
        $insert_query->execute();
    }
}

// Obtener permisos actuales para cada rol
$permisos_actuales = [];
$result = $conn->query("SELECT * FROM permisos");
while ($row = $result->fetch_assoc()) {
    $permisos_actuales[$row['role_id']] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Permisos y Roles</title>
</head>
<body>
    <h2>Administrar Permisos y Cambio de Rol</h2>

    <!-- Formulario para cambiar el rol de un usuario -->
    <h3>Cambiar Rol de Usuario</h3>
    <form method="POST">
        <label>Usuario:</label>
        <select name="user_id" required>
            <?php while ($row = $users->fetch_assoc()) { ?>
                <option value="<?= $row['id'] ?>"><?= $row['username'] ?></option>
            <?php } ?>
        </select>

        <label>Nuevo Rol:</label>
        <select name="role_id" required>
            <?php
            $roles->data_seek(0); // Reiniciar puntero de la consulta
            while ($row = $roles->fetch_assoc()) { ?>
                <option value="<?= $row['role_id'] ?>"><?= $row['role_name'] ?></option>
            <?php } ?>
        </select>

        <button type="submit">Actualizar Rol</button>
    </form>

    <!-- Formulario para asignar permisos a un rol -->
    <h3>Asignar Permisos a un Rol</h3>
    <form method="POST">
        <label>Rol:</label>
        <select name="role_id" required>
            <?php
            $roles->data_seek(0);
            while ($row = $roles->fetch_assoc()) { ?>
                <option value="<?= $row['role_id'] ?>"><?= $row['role_name'] ?></option>
            <?php } ?>
        </select>

        <h3>Permisos:</h3>
        <label>
            <input type="checkbox" name="permiso_consultar" value="1"> Consultar
        </label>
        <label>
            <input type="checkbox" name="permiso_ingresar" value="1"> Ingresar
        </label>
        <label>
            <input type="checkbox" name="permiso_capturar" value="1"> Capturar
        </label>
        <label>
            <input type="checkbox" name="permiso_baja" value="1"> Baja
        </label>

        <button type="submit">Guardar Permisos</button>
    </form>

    <!-- Tabla con los permisos actuales -->
    <h2>Permisos Actuales</h2>
    <table border="1">
        <tr>
            <th>Rol</th>
            <th>Consultar</th>
            <th>Ingresar</th>
            <th>Capturar</th>
            <th>Baja</th>
        </tr>
        <?php
        $roles->data_seek(0);
        while ($row = $roles->fetch_assoc()) {
            $role_id = $row['role_id'];
            $perm = $permisos_actuales[$role_id] ?? ['permiso_consultar' => 0, 'permiso_ingresar' => 0, 'permiso_capturar' => 0, 'permiso_baja' => 0];
        ?>
            <tr>
                <td><?= $row['role_name'] ?></td>
                <td><?= $perm['permiso_consultar'] ? '✔' : '✖' ?></td>
                <td><?= $perm['permiso_ingresar'] ? '✔' : '✖' ?></td>
                <td><?= $perm['permiso_capturar'] ? '✔' : '✖' ?></td>
                <td><?= $perm['permiso_baja'] ? '✔' : '✖' ?></td>
            </tr>
        <?php } ?>
    </table>

    <a href="dashboard.php">Volver</a>
</body>
</html>
