<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Agregar área nueva
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_area = trim($_POST['nombre_area']);
    $descripcion = trim($_POST['descripcion']) ?: NULL; // Manejar valores vacíos como NULL

    if (!empty($nombre_area)) {
        $query = "INSERT INTO areas (nombre_area, descripcion) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $nombre_area, $descripcion);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>Área agregada correctamente.</p>";
        } else {
            echo "<p style='color: red;'>Error al agregar el área.</p>";
        }
    } else {
        echo "<p style='color: red;'>El nombre del área es obligatorio.</p>";
    }
}

// Obtener todas las áreas
$result = $conn->query("SELECT * FROM areas");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Áreas</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
    </style>
</head>
<body>
    <h2>Gestión de Áreas</h2>
    
    <form method="POST">
        <label>Nombre del Área:</label>
        <input type="text" name="nombre_area" required>
        <label>Descripción:</label>
        <input type="text" name="descripcion">
        <button type="submit">Agregar Área</button>
    </form>

    <h3>Áreas y Usuarios</h3>
    <table>
        <tr>
            <th>Área</th>
            <th>Usuarios</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['nombre_area'] ?? 'Sin nombre'); ?></td>

                <td>
                    <ul>
                        <?php
                        $area_id = $row['area_id']; // Cambio de 'id' a 'area_id'
                        $users_stmt = $conn->prepare("SELECT username, full_name, email FROM users WHERE area_id = ?");
                        $users_stmt->bind_param("i", $area_id);
                        $users_stmt->execute();
                        $users_result = $users_stmt->get_result();

                        if ($users_result->num_rows > 0) {
                            while ($user_row = $users_result->fetch_assoc()) {
                            ?>
                                <li>
                                <?php echo htmlspecialchars($user_row['username'] ?? 'Usuario desconocido'); ?> - 
                                <?php echo htmlspecialchars($user_row['full_name'] ?? 'Sin nombre'); ?> - 
                                <?php echo htmlspecialchars($user_row['email'] ?? 'Sin correo'); ?>
                                </li>
                            <?php                            
                            }
                        } else {
                            echo "<li>No hay usuarios en esta área</li>";
                        }
                        ?>
                    </ul>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <a href="dashboard.php">Volver al Dashboard</a>
</body>
</html>
