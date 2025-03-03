<?php
include 'config.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pre_registro_id = intval($_POST['pre_registro_id']);
    
    $stmt = $conn->prepare("INSERT INTO crear_numero (pre_registro_id) VALUES (?)");
    $stmt->bind_param("i", $pre_registro_id);
    
    if ($stmt->execute()) {
        echo "NUC generado correctamente";
    } else {
        echo "Error al generar NUC: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Generar NUC</title>
</head>
<body>
    <h2>Generar NUC</h2>
    <form method="POST">
        <label>ID de Pre Registro:</label>
        <input type="number" name="pre_registro_id" required>
        <input type="submit" value="Generar NUC">
    </form>
</body>
</html>
