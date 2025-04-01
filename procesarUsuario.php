<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $area_id = isset($_POST['area_id']) ? (int) $_POST['area_id'] : 0;
    $role_id = isset($_POST['role_id']) ? (int) $_POST['role_id'] : 0;

     // Permisos
     $permiso_consultar = isset($_POST['permiso_consultar']) ? 1 : 0;
     $permiso_ingresar = isset($_POST['permiso_ingresar']) ? 1 : 0;
     $permiso_capturar = isset($_POST['permiso_capturar']) ? 1 : 0;
     $permiso_baja = isset($_POST['permiso_baja']) ? 1 : 0;
     $procesos = isset($_POST['procesos']) ? 1 : 0;
 
     // Inicia una transacción
     $conn->begin_transaction();

     try {
        // Inserta en la tabla `users`
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, area_id, role_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssii", $username, $password, $full_name, $email, $area_id, $role_id);
        $stmt->execute();
        $user_id = $stmt->insert_id; // Obtiene el ID del usuario recién creado
        $stmt->close();

        // Inserta en la tabla `user_roles`
        $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $role_id);
        $stmt->execute();
        $stmt->close();

        // Inserta en la tabla `permisos`
        $stmt = $conn->prepare("INSERT INTO permisos (user_id, permiso_consultar, permiso_ingresar, permiso_capturar, permiso_baja, procesos) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiii", $user_id, $permiso_consultar, $permiso_ingresar, $permiso_capturar, $permiso_baja, $procesos);
        $stmt->execute();
        $stmt->close();

        // Confirma la transacción
        $conn->commit();

        // Redirige con un mensaje de éxito
        header("Location: dashboard.php?mensaje=Usuario creado correctamente");
        exit();
    } catch (Exception $e) {
        // Revierte la transacción en caso de error
        $conn->rollback();
        echo "Error al crear el usuario: " . $e->getMessage();
    }

    if (empty($username) || empty($password) || empty($full_name) || $area_id == 0 || $role_id == 0) {
        header("Location: dashboard.php#administrarUsuarios" . urlencode("Error: Todos los campos son obligatorios."));
        exit;
    }

    if ($stmt->num_rows > 0) {
        header("Location: dashboard.php#administrarUsuarios" . urlencode("Error: El nombre de usuario o correo ya existen."));
        exit;
    }
    $stmt->close();

    $permiso_consultar = isset($_POST['permiso_consultar']) ? 1 : 0;
    $permiso_ingresar = isset($_POST['permiso_ingresar']) ? 1 : 0;
    $permiso_capturar = isset($_POST['permiso_capturar']) ? 1 : 0;
    $permiso_baja = isset($_POST['permiso_baja']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, area_id, role_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssii", $username, $password, $full_name, $email, $area_id, $role_id);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;

        $stmt = $conn->prepare("INSERT INTO permisos (user_id, permiso_consultar, permiso_ingresar, permiso_capturar, permiso_baja) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiii", $user_id, $permiso_consultar, $permiso_ingresar, $permiso_capturar, $permiso_baja);
        $stmt->execute();

        header("Location: dashboard.php#administrarUsuarios" . urlencode("Usuario creado con éxito."));
        exit;
    } else {
        header("Location: dashboard.php#administrarUsuarios" . urlencode("Error al crear usuario."));
        exit;
    }

    $stmt->close();
    $conn->close();
}
?>
