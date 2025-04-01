<?php
session_start();
include 'config.php';

// Verificar que existan los datos necesarios en la sesión
if (
    !isset($_SESSION['curp_validado']) || 
    !isset($_SESSION['validacion_id']) || 
    !isset($_SESSION['nuc_im']) ||
    !isset($_SESSION['municipio_id'])
) {
    die("ERROR: Datos incompletos en la sesión. Por favor, complete el proceso en el Dashboard.");
}

$validacion_id = $_SESSION['validacion_id'];
$municipio_id  = $_SESSION['municipio_id'];
$nuc_im       = $_SESSION['nuc_im'];

// Obtener la clave del municipio
$stmt_municipio = $conn->prepare("SELECT clave_municipio FROM municipios WHERE municipio_id = ?");
$stmt_municipio->bind_param("i", $municipio_id);
$stmt_municipio->execute();
$stmt_municipio->bind_result($clave_municipio);
$stmt_municipio->fetch();
$stmt_municipio->close();

if (!$clave_municipio) {
    die("Error: No se encontró la clave del municipio.");
}
$clave_municipio = str_pad($clave_municipio, 3, '0', STR_PAD_LEFT);

// Obtener número incremental
$query_incremental = "SELECT COALESCE(MAX(numero_incremental), 0) + 1 AS nuevo_incremental FROM crear_numero";
$result_incremental = $conn->query($query_incremental);
$row_incremental = $result_incremental->fetch_assoc();
$numero_incremental = str_pad($row_incremental['nuevo_incremental'], 6, '0', STR_PAD_LEFT);
$nuevo_incremental = $row_incremental['nuevo_incremental'];

// Año en formato YY
$anio = date("y");

// Generar NUC
$nuc_generado = $clave_municipio . $numero_incremental . $anio;

// Insertar en la tabla crear_numero usando validacion_id
$stmt_insert_nuc = $conn->prepare("INSERT INTO crear_numero (validacion_id, numero_incremental, nuc) VALUES (?, ?, ?)");
if (!$stmt_insert_nuc) {
    die("Error en la preparación de la consulta: " . $conn->error);
}
$stmt_insert_nuc->bind_param("iis", $validacion_id, $nuevo_incremental, $nuc_generado);
if (!$stmt_insert_nuc->execute()) {
    die("Error al insertar NUC: " . $stmt_insert_nuc->error);
}
$crear_numero_id = $conn->insert_id;
$stmt_insert_nuc->close();

// Guardar NUC y crear_numero_id en sesión
$_SESSION['nuc'] = $nuc_generado;
$_SESSION['crear_numero_id'] = $crear_numero_id;

header("Location: capturarExpediente.php");
exit();
?>
