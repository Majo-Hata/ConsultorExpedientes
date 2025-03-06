<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Agregar área nueva
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_area = $_POST['nombre_area'];
    $descripcion = $_POST['descripcion'];
    
    $query = "INSERT INTO areas (nombre_area, descripcion) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $nombre_area, $descripcion);
    if ($stmt->execute()) {
        echo "Área agregada correctamente.";
    } else {
        echo "Error al agregar el área.";
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

    <h3>Áreas Existentes</h3>
    <ul>
        <?php while ($row = $result->fetch_assoc()): ?>
            <li><?php echo htmlspecialchars($row['nombre_area']); ?> - <?php echo htmlspecialchars($row['descripcion']); ?></li>
        <?php endwhile; ?>
    </ul>

    <a href="dashboard.php">Volver al Dashboard</a>
</body>
</html>
