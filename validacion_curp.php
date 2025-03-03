<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $curp = trim($_POST['curp']);
    $stmt = $conn->prepare("SELECT tipo_predio, SUM(superficie_total) AS total_superficie FROM validacion WHERE curp=? GROUP BY tipo_predio");
    $stmt->bind_param("s", $curp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $mensaje = "No se encontraron registros para este CURP.";
    } else {
        $permitido = true;
        while ($row = $result->fetch_assoc()) {
            if ((strcasecmp($row['tipo_predio'], 'urbano') == 0 && $row['total_superficie'] > 1) ||
                (strcasecmp($row['tipo_predio'], 'rural') == 0 && $row['total_superficie'] > 6)) {
                $permitido = false;
            }
        }
        $mensaje = $permitido ? "Validación exitosa, puede continuar." : "No cumple con los requisitos.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Validación CURP</title>
</head>
<body>
    <h2>Validar CURP</h2>
    <form method="POST">
        <label>CURP:</label>
        <input type="text" name="curp" required>
        <button type="submit">Validar</button>
    </form>
    <?php if (!empty($mensaje)) echo "<p>$mensaje</p>"; ?>
</body>
</html>
