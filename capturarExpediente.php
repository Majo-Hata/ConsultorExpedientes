<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit(); 
}
// Obtener municipio desde sesión
$municipio_nombre = isset($_SESSION['municipio_nombre']) ? $_SESSION['municipio_nombre'] : '';

// Obtener el nuc_sim desde la sesión
$nuc_sim = isset($_SESSION['nuc_sim']) ? $_SESSION['nuc_sim'] : '';
$nuc_generado = isset($_SESSION['nuc_generado']) ? $_SESSION['nuc_generado'] : '';

// Generar NUC: Obtener el último NUC para continuar con el siguiente
$query = "SELECT nuc FROM ingresos ORDER BY nuc DESC LIMIT 1";
$result = $conn->query($query);
$nuc = 1; // Valor por defecto
if ($result && $row = $result->fetch_assoc()) {
    $nuc = $row['nuc'] + 1;  // Incrementar NUC
}

// Si el formulario se ha enviado
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
    $procedente = $_POST['procedente'];
    $estado = 1;

    // Insertar en la base de datos
    $stmt = $conn->prepare("INSERT INTO ingresos (fecha, nuc, nuc_sim, municipio, localidad, promovente, referencia_pago, tipo_predio, tipo_tramite, direccion, denominacion, superficie_total, sup_has, superficie_construida, forma_valorada, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssssssss", $fecha, $nuc, $nuc_sim, $municipio, $localidad, $promovente, $referencia_pago, $tipo_predio, $tipo_tramite, $direccion, $denominacion, $superficie_total, $sup_has, $superficie_construida, $forma_valorada, $estado);

    if ($stmt->execute()) {
        echo "<script>alert('Registro guardado correctamente'); window.location.href='capturar.php';</script>";
    } else {
        echo "<script>alert('Error al guardar los datos'); window.location.href='capturar.php';</script>";
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>
<!DOCTYPE HTML>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Captura de expediente</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <script>
        function cargarLocalidades() {
            var municipio = document.getElementById("municipio").value;
            var localidadSelect = document.getElementById("localidad");
            localidadSelect.innerHTML = "<option value=''>-- Seleccione una Localidad --</option>";
            if (municipio !== "") {
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "obtener_localidades.php?municipio=" + encodeURIComponent(municipio), true);
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        var localidades = JSON.parse(xhr.responseText);
                        localidades.forEach(function(localidad) {
                            var option = document.createElement("option");
                            option.value = localidad;
                            option.textContent = localidad;
                            localidadSelect.appendChild(option);
                        });
                    }
                };
                xhr.send();
            }
        }
    </script>
</head>
<body class="is-preload">
    <!-- Header -->
    <div id="header">
        <div class="top">
            <div id="logo">
                <span class="image avatar48"><img src="images/avatar.jpg" alt="" /></span>
                <h1 id="title">Captura de expediente</h1>
            </div>
            <nav id="nav">
                <ul>
                    <li><a href="dashboard.php#validacion" class="button">Regresar</a></li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Main -->
    <div id="main">
        <section class="two">
            <div class="container">
                <header>
                    <h2>Captura de expediente</h2>
                </header>
                <form method="post">
                    <label for="fecha">Fecha:</label>
                    <input type="date" id="fecha" name="fecha" required><br><br>

                    <label for="nuc">NUC:</label>
                    <input type="text" id="nuc" name="nuc" value="<?php echo htmlspecialchars($nuc_generado); ?>" readonly><br><br>

                    <label for="nuc_sim">NUC SIM:</label>
                    <input type="text" id="nuc_sim" name="nuc_sim" value="<?php echo htmlspecialchars($curp); ?>" readonly><br><br>

                    <label>Municipio:</label>
                    <input type="text" id="municipio" name="municipio" value="<?php echo htmlspecialchars($municipio_nombre); ?>" readonly><br><br>

                    <label>Localidad:</label>
                    <select name="localidad" id="localidad" required>
                        <option value="">-- Seleccione una Localidad --</option>
                    </select><br><br>

                    <label for="promovente">Promovente:</label>
                    <input type="text" id="promovente" name="promovente" required><br><br>

                    <label for="referencia_pago">Referencia de Pago:</label>
                    <input type="text" id="referencia_pago" name="referencia_pago" required><br><br>

                    <label for="tipo_predio">Tipo de Predio:</label>
                    <input type="text" id="tipo_predio" name="tipo_predio" value="<?php echo htmlspecialchars($_SESSION['tipo_predio']); ?>" readonly><br><br>

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
                    
                    <label for="procedente">Procedente:</label>
                    <select name="procedente" id="procedente">
                        <option value="1">Procedente</option>
                        <option value="0">No Procedente</option>
                    </select><br><br>
                    
                    <button type="submit">Guardar</button>
                </form>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            cargarLocalidades();
        });
    </script>
</body>
</html>
