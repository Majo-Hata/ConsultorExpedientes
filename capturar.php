<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fecha = $_POST['fecha'];
    $nuc = $_POST['nuc'];
    $nuc_sim = $_POST['nuc_sim'];
    $municipio = $_POST['municipio'];
    $localidad = $_POST['localidad'];
    $promovente = $_POST['promovente'];
    $referencia_pago = $_POST['referencia_pago'];
    $tipo_predio = $_POST['tipo_predio'];
    $tipo_tramite = $_POST['tipo_tramite'];
    $direccion = $_POST['direccion'];
    $denominacion = $_POST['denominacion'];
    $superficie_total = $_POST['superficie_total'];
    $sup_has = $_POST['sup_has'];
    $superficie_construida = $_POST['superficie_construida'];
    $forma_valorada = $_POST['forma_valorada'];
    $estado = $_POST['estado'];

    // Insertar los datos en la tabla ingresos
    $stmt = $conn->prepare("INSERT INTO ingresos (fecha, nuc, nuc_sim, municipio, localidad, promovente, referencia_pago, tipo_predio, tipo_tramite, direccion, denominacion, superficie_total, sup_has, superficie_construida, forma_valorada, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssssssss", $fecha, $nuc, $nuc_sim, $municipio, $localidad, $promovente, $referencia_pago, $tipo_predio, $tipo_tramite, $direccion, $denominacion, $superficie_total, $sup_has, $superficie_construida, $forma_valorada, $estado);

    if ($stmt->execute()) {
        echo "Registro insertado correctamente.";
    } else {
        echo "Error al insertar el registro: " . $stmt->error;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Captura</title>
</head>
<body>
    <h2>Formulario de Captura</h2>
    <form action="capturar.php" method="post">
        <label for="fecha">Fecha:</label>
        <input type="date" id="fecha" name="fecha" required><br><br>

        <label for="nuc">NUC:</label>
        <input type="text" id="nuc" name="nuc" required><br><br>

        <label for="nuc_sim">NUC SIM:</label>
        <input type="text" id="nuc_sim" name="nuc_sim" required><br><br>

        <label for="municipio">Municipio:</label>
        <input type="text" id="municipio" name="municipio" required><br><br>

        <label for="localidad">Localidad:</label>
        <input type="text" id="localidad" name="localidad" required><br><br>

        <label for="promovente">Promovente:</label>
        <input type="text" id="promovente" name="promovente" required><br><br>

        <label for="referencia_pago">Referencia de Pago:</label>
        <input type="text" id="referencia_pago" name="referencia_pago" required><br><br>

        <label for="tipo_predio">Tipo de Predio:</label>
        <input type="text" id="tipo_predio" name="tipo_predio" required><br><br>

        <label for="tipo_tramite">Tipo de Trámite:</label>
        <input type="text" id="tipo_tramite" name="tipo_tramite" required><br><br>

        <label for="direccion">Dirección:</label>
        <input type="text" id="direccion" name="direccion" required><br><br>

        <label for="denominacion">Denominación:</label>
        <input type="text" id="denominacion" name="denominacion" required><br><br>

        <label for="superficie_total">Superficie Total:</label>
        <input type="text" id="superficie_total" name="superficie_total" required><br><br>

        <label for="sup_has">Superficie en Hectáreas:</label>
        <input type="text" id="sup_has" name="sup_has" required><br><br>

        <label for="superficie_construida">Superficie Construida:</label>
        <input type="text" id="superficie_construida" name="superficie_construida" required><br><br>

        <label for="forma_valorada">Forma Valorada:</label>
        <input type="text" id="forma_valorada" name="forma_valorada" required><br><br>

        <label for="estado">Estado:</label>
        <input type="text" id="estado" name="estado" required><br><br>

        <input type="submit" value="Enviar">
    </form>
</body>
</html>