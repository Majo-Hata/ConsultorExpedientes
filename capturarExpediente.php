<?php
// capturarExpediente.php
session_start();
include 'config.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

// Recuperar datos de sesión usando nombres consistentes
$municipio_nombre = isset($_SESSION['municipio_nombre']) ? $_SESSION['municipio_nombre'] : '';
$nuc_generado    = isset($_SESSION['nuc']) ? $_SESSION['nuc'] : '';
$curp            = isset($_SESSION['curp_validado']) ? $_SESSION['curp_validado'] : '';
$tipo_predio     = isset($_SESSION['tipo_predio']) ? $_SESSION['tipo_predio'] : '';

// Opcional: puedes hacer un debug temporal de la sesión
// echo "<pre>";
// print_r($_SESSION);
// echo "</pre>";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger datos del formulario
    $fecha = $_POST['fecha'] ?? null;
    $nuc = $_POST['nuc'] ?? null;
    $nuc_sim = $_POST['nuc_sim'] ?? null;
    $municipio = $_POST['municipio'] ?? null;
    $localidad = $_POST['localidad'] ?? null;
    $promovente = $_POST['promovente'] ?? null;
    $referencia_pago = $_POST['referencia_pago'] ?? null;
    $tipo_predio_form = $_POST['tipo_predio'] ?? null;
    $tipo_tramite = $_POST['tipo_tramite'] ?? null;
    $direccion = $_POST['direccion'] ?? null;
    $denominacion = $_POST['denominacion'] ?? null;
    $superficie_total = $_POST['superficie_total'] ?? null;
    $sup_has = $_POST['sup_has'] ?? null;
    $superficie_construida = $_POST['superficie_construida'] ?? null;
    $forma_valorada = $_POST['forma_valorada'] ?? null;
    $procedente = $_POST['procedente'] ?? null;
    $estado = 1;

    // Validar que los campos obligatorios no estén vacíos
    if (
        $fecha && $nuc && $nuc_sim && $municipio && $localidad && $promovente &&
        $referencia_pago && $tipo_predio_form && $tipo_tramite && $direccion &&
        $denominacion && $superficie_total && $sup_has && $superficie_construida &&
        $forma_valorada && $procedente !== null
    ) {
        $stmt = $conn->prepare("INSERT INTO ingresos (fecha, nuc, nuc_sim, municipio, localidad, promovente, referencia_pago, tipo_predio, tipo_tramite, direccion, denominacion, superficie_total, sup_has, superficie_construida, forma_valorada, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssssss", $fecha, $nuc, $nuc_sim, $municipio, $localidad, $promovente, $referencia_pago, $tipo_predio_form, $tipo_tramite, $direccion, $denominacion, $superficie_total, $sup_has, $superficie_construida, $forma_valorada, $estado);
    
        if ($stmt->execute()) {
            echo "<script>alert('Registro guardado correctamente'); window.location.href='dashboard.php#validacion';</script>";
        } else {
            echo "<script>alert('Error al guardar los datos');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Todos los campos son obligatorios');</script>";
    }
    $conn->close();
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
                    <input type="text" id="tipo_predio" name="tipo_predio" value="<?php echo htmlspecialchars($tipo_predio); ?>" readonly><br><br>

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
