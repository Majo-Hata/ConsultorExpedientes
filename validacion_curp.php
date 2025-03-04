<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

// Variable para mensajes
$mensaje = "";

// Obtener lista de municipios
$municipios = $conn->query("SELECT id, nombre FROM municipios ORDER BY nombre ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $curp = trim($_POST['curp']);
    $municipio_id = intval($_POST['municipio_id']);
    $tipo_predio = $_POST['tipo_predio'];
    $superficie_total = ($tipo_predio === "rural") ? floatval($_POST['superficie_total']) : 0;
    $nuc_sim = trim($_POST['nuc_sim']);

    // Obtener nombre del municipio
    $stmt_mun = $conn->prepare("SELECT nombre FROM municipios WHERE id = ?");
    $stmt_mun->bind_param("i", $municipio_id);
    $stmt_mun->execute();
    $result_mun = $stmt_mun->get_result();
    $municipio_nombre = $result_mun->fetch_assoc()['nombre'] ?? '';
    $stmt_mun->close();

    // Validar si el CURP ya tiene registros previos
    $stmt_validacion = $conn->prepare("
        SELECT tipo_predio, SUM(superficie_total) AS total_superficie, COUNT(*) AS total_predios 
        FROM validacion 
        WHERE curp=? 
        GROUP BY tipo_predio
    ");
    $stmt_validacion->bind_param("s", $curp);
    $stmt_validacion->execute();
    $result_validacion = $stmt_validacion->get_result();

    $permitido = true;
    $total_urbanos = 0;
    $total_superficie_rural = 0;

    while ($row = $result_validacion->fetch_assoc()) {
        if (strcasecmp($row['tipo_predio'], 'urbano') == 0) {
            $total_urbanos = $row['total_predios'];
        }
        if (strcasecmp($row['tipo_predio'], 'rural') == 0) {
            $total_superficie_rural = $row['total_superficie'];
        }
    }
    $stmt_validacion->close();

    // Solo aplicar restricciones si ya hay registros previos para el CURP
    if ($total_urbanos > 0 || $total_superficie_rural > 0) {
        // Validar si el CURP excede los límites
        if (($tipo_predio === "urbano" && $total_urbanos >= 1) || 
            ($tipo_predio === "rural" && ($total_superficie_rural + $superficie_total) > 6)) {
            $permitido = false;
        }
    }

    // Si cumple con las restricciones, insertar en la tabla de validación
    if ($permitido) {
        $fecha_consulta = date("Y-m-d H:i:s");

        $stmt_insert_validacion = $conn->prepare("
            INSERT INTO validacion (nuc_sim, curp, fecha_consulta, municipio, tipo_predio, superficie_total) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt_insert_validacion->bind_param("sssssd", $nuc_sim, $curp, $fecha_consulta, $municipio_nombre, $tipo_predio, $superficie_total);

        if ($stmt_insert_validacion->execute()) {
            $_SESSION['curp_validado'] = $curp; // Guardamos la CURP validada en sesión
            $_SESSION['municipio_id'] = $municipio_id;
            header("Location: generar_nuc.php");
            exit();
        } else {
            $mensaje = "Error al registrar validación: " . $conn->error;
        }
        $stmt_insert_validacion->close();
    } else {
        $mensaje = "No cumple con los requisitos.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Validación CURP</title>
    <script>
        // Función para mostrar/ocultar el campo de superficie total según el tipo de predio
        function toggleSuperficie() {
            var tipoPredio = document.getElementById("tipo_predio").value;
            var superficieInput = document.getElementById("superficie_total");
            if (tipoPredio === "rural") {
                superficieInput.disabled = false;
                superficieInput.required = true;
            } else {
                superficieInput.disabled = true;
                superficieInput.value = "";
                superficieInput.required = false;
            }
        }
    </script>
</head>
<body>
    <h2>Validar CURP</h2>
    <form method="POST">
        <label>CURP:</label>
        <input type="text" name="curp" required>
        <br><br>

        <label>Municipio:</label>
        <select name="municipio_id" required>
            <option value="">-- Seleccione un Municipio --</option>
            <?php while ($row = $municipios->fetch_assoc()): ?>
                <option value="<?php echo $row['id']; ?>">
                    <?php echo htmlspecialchars($row['nombre']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <br><br>

        <label>Tipo de Predio:</label>
        <select name="tipo_predio" id="tipo_predio" onchange="toggleSuperficie()" required>
            <option value="urbano">Urbano</option>
            <option value="rural">Rural</option>
        </select>
        <br><br>

        <label>Superficie Total (hectáreas):</label>
        <input type="number" step="0.01" name="superficie_total" id="superficie_total" disabled>
        <br><br>

        <label>NUC Simulado:</label>
        <input type="text" name="nuc_sim" required>
        <br><br>

        <button type="submit">Validar y Guardar</button>
    </form>

    <?php if (!empty($mensaje)) echo "<p>$mensaje</p>"; ?>
</body>
</html>
