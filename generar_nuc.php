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

// Obtener clave del municipio
$query_municipio = "SELECT clave_municipio FROM municipios WHERE municipio_id = ?";
$stmt_municipio = $conn->prepare($query_municipio);
$stmt_municipio->bind_param("i", $municipio_id);
$stmt_municipio->execute();
$stmt_municipio->bind_result($clave_municipio);
$stmt_municipio->fetch();
$stmt_municipio->close();

if (!$clave_municipio) {
    die("Error: No se encontró la clave del municipio.");
}

// Asegurar clave de municipio con 3 dígitos
$clave_municipio = str_pad($clave_municipio, 3, '0', STR_PAD_LEFT);

// Obtener número incremental
$query_incremental = "SELECT COALESCE(MAX(numero_incremental), 0) + 1 AS nuevo_incremental FROM crear_numero";
$result_incremental = $conn->query($query_incremental);
$row_incremental = $result_incremental->fetch_assoc();
$numero_incremental = str_pad($row_incremental['nuevo_incremental'], 6, '0', STR_PAD_LEFT);

// Año en formato YY
$anio = date("y");

// Generar NUC
$nuc_generado = $clave_municipio . $numero_incremental . $anio;

// Imprimir valores antes de insertar
echo "Pre-registro ID: " . htmlspecialchars($pre_registro_id) . "<br>";
echo "Número incremental: " . htmlspecialchars($numero_incremental) . "<br>";
echo "NUC generado: " . htmlspecialchars($nuc_generado) . "<br>";

// Insertar en `crear_numero`
$stmt_insert_nuc = $conn->prepare("
    INSERT INTO crear_numero (pre_registro_id, numero_incremental, nuc) 
    VALUES (?, ?, ?)
");
if (!$stmt_insert_nuc) {
    die("Error en la preparación de la consulta: " . $conn->error);
}

$stmt_insert_nuc->bind_param("iis", $pre_registro_id, $row_incremental['nuevo_incremental'], $nuc_generado);

if (!$stmt_insert_nuc->execute()) {
    die("Error al insertar NUC: " . $stmt_insert_nuc->error);
} else {
    echo "NUC insertado correctamente: <strong>$nuc_generado</strong><br>";
}

$stmt_insert_nuc->close();

// Guardar datos en sesión para prellenar en capturar.php
$_SESSION['nuc'] = $nuc_generado;
$_SESSION['nuc_sim'] = $nuc_sim;
$_SESSION['municipio'] = $clave_municipio;
$_SESSION['curp_validado'] = $curp;

// Redirigir a la página de captura
header("Location: capturar.php");
exit();

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
