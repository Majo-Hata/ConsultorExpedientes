<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password =($_POST['password']);
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $area_id = $_POST['area_id'];
    $role_id = $_POST['role_id'];

    // Insertar el nuevo usuario en la tabla users
    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, status, area_id) VALUES (?, ?, ?, ?, 'active', ?)");
    $stmt->bind_param("ssssi", $username, $password, $full_name, $email, $area_id);
    $stmt->execute();
    $user_id = $stmt->insert_id;

    // Asignar rol al usuario
    $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $role_id);
    $stmt->execute();

    // Asignar permisos al rol del usuario
    $permiso_consultar = isset($_POST['permiso_consultar']) ? 1 : 0;
    $permiso_ingresar = isset($_POST['permiso_ingresar']) ? 1 : 0;
    $permiso_capturar = isset($_POST['permiso_capturar']) ? 1 : 0;
    $permiso_baja = isset($_POST['permiso_baja']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO permisos (role_id, permiso_consultar, permiso_ingresar, permiso_capturar, permiso_baja) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiii", $role_id, $permiso_consultar, $permiso_ingresar, $permiso_capturar, $permiso_baja);
    $stmt->execute();

    echo "Usuario creado exitosamente.";
}
?>