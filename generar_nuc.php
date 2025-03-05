<?php
session_start();
include 'config.php';
if (!isset($_SESSION['curp_validado']) || !isset($_SESSION['pre_registro_id']) || !isset($_SESSION['nuc_sim'])) {
    die("ERROR: No se encontró un CURP, municipio o NUC asociado.");
}
$curp = $_SESSION['curp_validado'];
$municipio_id = $_SESSION['municipio_id'];
$pre_registro_id = $_SESSION['pre_registro_id'];
$nuc_sim = $_SESSION['nuc_sim']; 
echo "CURP: " . htmlspecialchars($curp) . "<br>";
echo "Municipio ID: " . htmlspecialchars($municipio_id) . "<br>";
echo "Pre-registro ID: " . htmlspecialchars($pre_registro_id) . "<br>";
echo "NUC_SIM: " . htmlspecialchars($nuc_sim) . "<br>";

// Continuamos con la generación del nuc

$pre_registro_id = $_SESSION['pre_registro_id'];

// 1. Obtener datos del pre-registro
$query_curp = "SELECT curp, municipio_id FROM pre_registros WHERE id = ?";
$stmt_curp = $conn->prepare($query_curp);
$stmt_curp->bind_param("i", $pre_registro_id);
$stmt_curp->execute();
$stmt_curp->bind_result($curp, $municipio_id);
$stmt_curp->fetch();
$stmt_curp->close();

if (!$curp || !$municipio_id) {
    echo "Error: No se encontró un CURP o municipio asociado.";
    exit();
}

// 2. Obtener la clave del municipio desde la tabla municipios
$query_municipio = "SELECT clave_municipio FROM municipios WHERE id = ?";
$stmt_municipio = $conn->prepare($query_municipio);
$stmt_municipio->bind_param("i", $municipio_id);
$stmt_municipio->execute();
$stmt_municipio->bind_result($clave_municipio);
$stmt_municipio->fetch();
$stmt_municipio->close();

if (!$clave_municipio) {
    echo "Error: No se encontró la clave del municipio.";
    exit();
}

// Asegurar que la clave del municipio tenga siempre 3 dígitos
$clave_municipio = str_pad($clave_municipio, 3, '0', STR_PAD_LEFT);

// 3. Validar si la CURP ya tiene predios registrados en `validacion`
$query_validacion = "
    SELECT tipo_predio, SUM(superficie_total) AS total_superficie, COUNT(*) AS total_predios
    FROM validacion
    WHERE curp = ?
    GROUP BY tipo_predio";
$stmt_validacion = $conn->prepare($query_validacion);
$stmt_validacion->bind_param("s", $curp);
$stmt_validacion->execute();
$result_validacion = $stmt_validacion->get_result();

$permitido = true;
while ($row = $result_validacion->fetch_assoc()) {
    if (
        (strcasecmp($row['tipo_predio'], 'urbano') == 0 && $row['total_predios'] > 2) || 
        (strcasecmp($row['tipo_predio'], 'rural') == 0 && $row['total_superficie'] > 6)
    ) {
        $permitido = false;
    }
}
$stmt_validacion->close();

if (!$permitido) {
    echo "No se puede generar el NUC: Se excede el límite de predios permitidos.";
    exit();
}

// 4. Insertar en `validacion` antes de continuar
$fecha_consulta = date("Y-m-d H:i:s");
$nuc_sim = "SIM-" . uniqid(); // Generar un identificador simulado

$stmt_insert_validacion = $conn->prepare("
    INSERT INTO validacion (nuc_sim, curp, fecha_consulta, municipio, tipo_predio, superficie_total) 
    VALUES (?, ?, ?, (SELECT nombre FROM municipios WHERE id = ?), ?, ?)
");
$tipo_predio = "urbano"; // O se obtiene de un formulario
$superficie_total = 1.0; // Se ajusta según lo ingresado

$stmt_insert_validacion->bind_param("sssisd", $nuc_sim, $curp, $fecha_consulta, $municipio_id, $tipo_predio, $superficie_total);
$stmt_insert_validacion->execute();
$stmt_insert_validacion->close();

// 5. Obtener el número incremental para el NUC (máximo actual + 1)
$query_incremental = "SELECT COALESCE(MAX(numero_incremental), 0) + 1 AS nuevo_incremental FROM crear_numero";
$result_incremental = $conn->query($query_incremental);
$row_incremental = $result_incremental->fetch_assoc();
$numero_incremental = str_pad($row_incremental['nuevo_incremental'], 6, '0', STR_PAD_LEFT);

// 6. Obtener el año actual en formato YY
$anio = date("y");

// 7. Generar el NUC final
$nuc_generado = $clave_municipio . $numero_incremental . $anio;
echo "NUC a insertar: " . htmlspecialchars($nuc_generado) . "<br>";


// 8. Insertar en `crear_numero`
$stmt_insert_nuc = $conn->prepare("INSERT INTO crear_numero (pre_registro_id, numero_incremental, nuc) VALUES (?, ?, ?)");
$stmt_insert_nuc->bind_param("iis", $pre_registro_id, $row_incremental['nuevo_incremental'], $nuc_generado);

if ($stmt_insert_nuc->execute()) {
    echo "NUC generado correctamente: <strong>$nuc_generado</strong><br>";
} else {
    echo "Error al generar NUC: " . $conn->error;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Generar NUC</title>
</head>
<body>
    <h2>Generar NUC</h2>
    <p>NUC generado: <strong><?php echo htmlspecialchars($nuc_generado); ?></strong></p>
    <a href="dashboard.php">Volver al Dashboard</a>
</body>
</html>
