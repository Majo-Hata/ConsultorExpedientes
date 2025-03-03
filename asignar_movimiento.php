<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nuc_id = intval($_POST['nuc_id']);
    $area_origen = trim($_POST['area_origen']);
    $area_destino = trim($_POST['area_destino']);
    $comentario = trim($_POST['comentario']);
    $usuario_id = $_SESSION['user_id']; // Usuario autenticado
    $fecha_movimiento = $_POST['fecha_movimiento'];

    if (empty($fecha_movimiento)) {
        $stmt = $conn->prepare("INSERT INTO historiales (nuc_id, area_origen, area_destino, comentario, usuario_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $nuc_id, $area_origen, $area_destino, $comentario, $usuario_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO historiales (nuc_id, area_origen, area_destino, comentario, fecha_movimiento, usuario_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssi", $nuc_id, $area_origen, $area_destino, $comentario, $fecha_movimiento, $usuario_id);
    }

    if ($stmt->execute()) {
        echo "Movimiento registrado correctamente.";
    } else {
        echo "Error al registrar el movimiento: " . $conn->error;
    }
}

// Obtener NUCs disponibles
$nucs = $conn->query("SELECT id_nuc, nuc FROM cuartaentrega");

// Obtener áreas disponibles (guardarlas en un array para reutilizarlo)
$areas_result = $conn->query("SELECT nombre_area FROM areas");
$areas = [];
while ($row = $areas_result->fetch_assoc()) {
    $areas[] = $row['nombre_area'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Asignar Movimiento</title>
</head>
<body>
    <h2>Asignar Movimiento</h2>
    <form method="POST">
        <label>NUC:</label>
        <select name="nuc_id" required>
            <?php while ($row = $nucs->fetch_assoc()): ?>
                <option value="<?php echo $row['id_nuc']; ?>"><?php echo $row['nuc']; ?></option>
            <?php endwhile; ?>
        </select>

        <label>Área Origen:</label>
        <select name="area_origen" required>
            <?php foreach ($areas as $area): ?>
                <option value="<?php echo $area; ?>"><?php echo $area; ?></option>
            <?php endforeach; ?>
        </select>

        <label>Área Destino:</label>
        <select name="area_destino" required>
            <?php foreach ($areas as $area): ?>
                <option value="<?php echo $area; ?>"><?php echo $area; ?></option>
            <?php endforeach; ?>
        </select>

        <label>Comentario:</label>
        <input type="text" name="comentario" required>

        <label>Fecha Movimiento:</label>
        <input type="datetime-local" name="fecha_movimiento" required>

        <button type="submit">Registrar Movimiento</button>
    </form>
</body>
</html>
