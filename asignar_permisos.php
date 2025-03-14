<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Obtener usuarios activos
$users = $conn->query("SELECT id, username FROM users WHERE status='active'");

// Manejo de peticiones AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == "get_permisos" && isset($_POST['user_id'])) {
            $user_id = $_POST['user_id'];
            $query = $conn->prepare("SELECT * FROM permisos WHERE user_id = ?");
            $query->bind_param("i", $user_id);
            $query->execute();
            $result = $query->get_result();
            $permisos = $result->fetch_assoc() ?? [
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
            $user_id = $_POST['user_id'];
            $permiso_consultar = isset($_POST['permiso_consultar']) ? 1 : 0;
            $permiso_ingresar = isset($_POST['permiso_ingresar']) ? 1 : 0;
            $permiso_capturar = isset($_POST['permiso_capturar']) ? 1 : 0;
            $permiso_baja = isset($_POST['permiso_baja']) ? 1 : 0;
            $procesos = isset($_POST['procesos']) ? 1 : 0;

            $check_query = $conn->prepare("SELECT * FROM permisos WHERE user_id = ?");
            $check_query->bind_param("i", $user_id);
            $check_query->execute();
            $result = $check_query->get_result();

            if ($result->num_rows > 0) {
                $update_query = $conn->prepare("
                    UPDATE permisos 
                    SET permiso_consultar = ?, permiso_ingresar = ?, permiso_capturar = ?, permiso_baja = ?, procesos = ? 
                    WHERE user_id = ?");
                $update_query->bind_param("iiiiii", $permiso_consultar, $permiso_ingresar, $permiso_capturar, $permiso_baja, $procesos, $user_id);
                $update_query->execute();
            } else {
                $insert_query = $conn->prepare("
                    INSERT INTO permisos (user_id, permiso_consultar, permiso_ingresar, permiso_capturar, permiso_baja, procesos) 
                    VALUES (?, ?, ?, ?, ?, ?)");
                $insert_query->bind_param("iiiiii", $user_id, $permiso_consultar, $permiso_ingresar, $permiso_capturar, $permiso_baja, $procesos);
                $insert_query->execute();
            }
            exit();
        }

        if ($_POST['action'] == "get_tabla_permisos") {
            $users = $conn->query("SELECT id, username FROM users WHERE status='active'");
            $tabla = "<table border='1'>
                        <tr>
                            <th>Usuario</th>
                            <th>Consultar</th>
                            <th>Ingresar</th>
                            <th>Capturar</th>
                            <th>Baja</th>
                            <th>Procesos</th>
                        </tr>";
            while ($row = $users->fetch_assoc()) {
                $user_id = $row['id'];
                $perm = $conn->query("SELECT * FROM permisos WHERE user_id = $user_id")->fetch_assoc() ?? [
                    'permiso_consultar' => 0,
                    'permiso_ingresar' => 0,
                    'permiso_capturar' => 0,
                    'permiso_baja' => 0,
                    'procesos' => 0
                ];

                $tabla .= "<tr>
                    <td>{$row['username']}</td>
                    <td>" . ($perm['permiso_consultar'] ? '✔' : '✖') . "</td>
                    <td>" . ($perm['permiso_ingresar'] ? '✔' : '✖') . "</td>
                    <td>" . ($perm['permiso_capturar'] ? '✔' : '✖') . "</td>
                    <td>" . ($perm['permiso_baja'] ? '✔' : '✖') . "</td>
                    <td>" . ($perm['procesos'] ? '✔' : '✖') . "</td>
                </tr>";
            }
            $tabla .= "</table>";
            echo $tabla;
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Permisos</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h2>Administrar Permisos por Usuario</h2>

    <form id="formPermisos">
        <label>Usuario:</label>
        <select id="user_id" name="user_id" required>
            <option value="">Seleccione un usuario</option>
            <?php while ($row = $users->fetch_assoc()) { ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['username']) ?></option>
            <?php } ?>
        </select>

        <h3>Permisos:</h3>
        <label><input type="checkbox" name="permiso_consultar" id="permiso_consultar"> Consultar</label>
        <label><input type="checkbox" name="permiso_ingresar" id="permiso_ingresar"> Ingresar</label>
        <label><input type="checkbox" name="permiso_capturar" id="permiso_capturar"> Capturar</label>
        <label><input type="checkbox" name="permiso_baja" id="permiso_baja"> Baja</label>
        <label><input type="checkbox" name="procesos" id="procesos"> Procesos</label>

        <button type="submit">Guardar Permisos</button>
    </form>

    <h2>Permisos por Usuario</h2>
    <div id="tablaPermisos"></div>

    <a href="dashboard.php">Volver</a>

    <script>
    $(document).ready(function() {
        function cargarPermisos(user_id) {
            $.post('', { action: "get_permisos", user_id: user_id }, function(response) {
                let permisos = JSON.parse(response);
                $("#permiso_consultar").prop('checked', permisos.permiso_consultar == 1);
                $("#permiso_ingresar").prop('checked', permisos.permiso_ingresar == 1);
                $("#permiso_capturar").prop('checked', permisos.permiso_capturar == 1);
                $("#permiso_baja").prop('checked', permisos.permiso_baja == 1);
                $("#procesos").prop('checked', permisos.procesos == 1);
            });
        }

        function cargarTabla() {
            $.post('', { action: "get_tabla_permisos" }, function(response) {
                $("#tablaPermisos").html(response);
            });
        }

        $("#user_id").change(function() {
            if ($(this).val()) {
                cargarPermisos($(this).val());
            }
        });

        $("#formPermisos").submit(function(e) {
            e.preventDefault();
            $.post('', $(this).serialize() + "&action=guardar_permisos", function() {
                cargarTabla();
            });
        });

        cargarTabla();
    });
    </script>
</body>
</html>
