<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

$mensaje = "";

// Obtener lista de municipios
$municipios = $conn->query("SELECT municipio_id, nombre FROM municipios ORDER BY nombre ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $curp = isset($_POST['curp']) ? trim($_POST['curp']) : "";
    $municipio_id = isset($_POST['municipio_id']) ? intval($_POST['municipio_id']) : 0;
    $tipo_predio = isset($_POST['tipo_predio']) ? $_POST['tipo_predio'] : "";
    $superficie_total = ($tipo_predio === "rural" && isset($_POST['superficie_total'])) ? floatval($_POST['superficie_total']) : 0;
    $nuc_sim = isset($_POST['nuc_sim']) ? trim($_POST['nuc_sim']) : "";

    // Obtener nombre del municipio
    $stmt_mun = $conn->prepare("SELECT nombre FROM municipios WHERE municipio_id = ?");
    $stmt_mun->bind_param("i", $municipio_id);
    $stmt_mun->execute();
    $result_mun = $stmt_mun->get_result();
    $municipio_nombre = $result_mun->fetch_assoc()['nombre'] ?? '';
    $stmt_mun->close();

    if (empty($curp) || empty($municipio_nombre) || empty($tipo_predio) || empty($nuc_sim)) {
        $mensaje = "Todos los campos son obligatorios.";
    } else {
        // Verificar si el CURP ya tiene registros previos
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

        // Verificación de reglas
        if (($tipo_predio === "urbano" && $total_urbanos >= 1) || 
            ($tipo_predio === "rural" && ($total_superficie_rural + $superficie_total) > 6)) {
            $permitido = false;
        }

        if ($permitido) {
            $fecha_consulta = date("Y-m-d H:i:s");

            // Verificar si el registro ya existe en `validacion`
            $stmt_check_validacion = $conn->prepare("
                SELECT COUNT(*) FROM validacion 
                WHERE curp = ? AND municipio = ? AND tipo_predio = ? AND nuc_sim = ?
            ");
            $stmt_check_validacion->bind_param("ssss", $curp, $municipio_nombre, $tipo_predio, $nuc_sim);
            $stmt_check_validacion->execute();
            $stmt_check_validacion->bind_result($existe);
            $stmt_check_validacion->fetch();
            $stmt_check_validacion->close();

            if ($existe == 0) {
                $stmt_insert_validacion = $conn->prepare("
                    INSERT INTO validacion (nuc_sim, curp, fecha_consulta, municipio, tipo_predio, superficie_total) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt_insert_validacion->bind_param("sssssd", $nuc_sim, $curp, $fecha_consulta, $municipio_nombre, $tipo_predio, $superficie_total);
                $stmt_insert_validacion->execute();
                $stmt_insert_validacion->close();
            }

            // Verificar si ya existe un pre_registro
            $stmt_check_pre_registro = $conn->prepare("
                SELECT id FROM pre_registros WHERE curp = ? AND municipio_id = ?
            ");
            $stmt_check_pre_registro->bind_param("si", $curp, $municipio_id);
            $stmt_check_pre_registro->execute();
            $stmt_check_pre_registro->bind_result($pre_registro_id);
            $stmt_check_pre_registro->fetch();
            $stmt_check_pre_registro->close();

            if (!$pre_registro_id) {
                $stmt_insert_pre_registro = $conn->prepare("
                    INSERT INTO pre_registros (curp, municipio_id, fecha_pre_registro) 
                    VALUES (?, ?, ?)
                ");
                $fecha_pre_registro = date("Y-m-d");
                $stmt_insert_pre_registro->bind_param("sis", $curp, $municipio_id, $fecha_pre_registro);
                if ($stmt_insert_pre_registro->execute()) {
                    $pre_registro_id = $conn->insert_id;
                }
                $stmt_insert_pre_registro->close();
            }

            $_SESSION['curp_validado'] = $curp;
            $_SESSION['municipio_id'] = $municipio_id;
            $_SESSION['pre_registro_id'] = $pre_registro_id;
            $_SESSION['nuc_sim'] = $nuc_sim; 

            $_SESSION['municipio_nombre'] = $municipio_nombre; // Guardar en sesión
            header("Location: capturar.php");
            exit();

        } else {
            $mensaje = "No cumple con los requisitos.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Validación CURP</title>
    <script>
        function toggleSuperficie() {
            var tipoPredio = document.getElementById("tipo_predio").value;
            var superficieInput = document.getElementById("superficie_total");
            superficieInput.disabled = tipoPredio !== "rural";
            superficieInput.required = tipoPredio === "rural";
            if (tipoPredio !== "rural") {
                superficieInput.value = "";
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
                <option value="<?php echo $row['municipio_id']; ?>">
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

        <label>NUC_SIM:</label>
        <input type="text" name="nuc_sim" required>
        <br><br>

        <button type="submit">Validar y Guardar</button>
    </form>

    <?php if (!empty($mensaje)) echo "<p>$mensaje</p>"; ?>

    <a href="dashboard.php">Volver</a>
</body>
</html>
