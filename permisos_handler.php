<?php
session_start();
include 'config.php';

    if ($_POST['action'] == "get_permisos" && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        $query = $conn->prepare("SELECT * FROM permisos WHERE user_id = ?");
        $query->bind_param("i", $user_id);
        $query->execute();
        $result = $query->get_result();
        $permisos = $result->fetch_assoc() ?: [
            'permiso_consultar' => 0,
            'permiso_ingresar' => 0,
            'permiso_capturar' => 0,
            'permiso_baja' => 0,
            'procesos' => 0
        ];
        echo json_encode($permisos);
        exit();
    }

    if ($_POST['action'] == "guardar_permisos" && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        $permiso_consultar = isset($_POST['permiso_consultar']) ? 1 : 0;
        $permiso_ingresar = isset($_POST['permiso_ingresar']) ? 1 : 0;
        $permiso_capturar = isset($_POST['permiso_capturar']) ? 1 : 0;
        $permiso_baja = isset($_POST['permiso_baja']) ? 1 : 0;
        $procesos = isset($_POST['procesos']) ? 1 : 0;
    
        // Verificar si ya existen permisos para el usuario
        $check_query = $conn->prepare("SELECT * FROM permisos WHERE user_id = ?");
        $check_query->bind_param("i", $user_id);
        $check_query->execute();
        $result = $check_query->get_result();
    
        if ($result->num_rows > 0) {
            // Actualizar permisos existentes
            $update_query = $conn->prepare("UPDATE permisos SET permiso_consultar = ?, permiso_ingresar = ?, permiso_capturar = ?, permiso_baja = ?, procesos = ? WHERE user_id = ?");
            $update_query->bind_param("iiiiii", $permiso_consultar, $permiso_ingresar, $permiso_capturar, $permiso_baja, $procesos, $user_id);
            $update_query->execute();
        } else {
            // Insertar nuevos permisos
            $insert_query = $conn->prepare("INSERT INTO permisos (user_id, permiso_consultar, permiso_ingresar, permiso_capturar, permiso_baja, procesos) VALUES (?, ?, ?, ?, ?, ?)");
            $insert_query->bind_param("iiiiii", $user_id, $permiso_consultar, $permiso_ingresar, $permiso_capturar, $permiso_baja, $procesos);
            $insert_query->execute();
        }
        echo "Permisos guardados correctamente.";
        exit();
    }
?>
