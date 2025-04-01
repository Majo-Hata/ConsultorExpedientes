<?php
session_start();
include 'config.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Obtener el ID del ingreso a editar
$ingreso_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener los datos del ingreso
$stmt = $conn->prepare("SELECT * FROM ingresos WHERE id_nuc = ?");
$stmt->bind_param("i", $ingreso_id);
$stmt->execute();
$result = $stmt->get_result();
$ingreso = $result->fetch_assoc();
$stmt->close();

if (!$ingreso) {
    die("El registro de ingreso no existe.");
}

// Procesar el formulario de edición
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $localidad = isset($_POST['localidad']) ? trim($_POST['localidad']) : "";
    $promovente = isset($_POST['promovente']) ? trim($_POST['promovente']) : "";
    $referencia_pago = isset($_POST['referencia_pago']) ? trim($_POST['referencia_pago']) : "";
    $tipo_tramite = isset($_POST['tipo_tramite']) ? trim($_POST['tipo_tramite']) : "";
    $direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : "";
    $denominacion = isset($_POST['denominacion']) ? trim($_POST['denominacion']) : "";
    $superficie_total = isset($_POST['superficie_total']) ? floatval($_POST['superficie_total']) : null;
    $sup_has = isset($_POST['sup_has']) ? floatval($_POST['sup_has']) : null;
    $superficie_construida = isset($_POST['superficie_construida']) ? floatval($_POST['superficie_construida']) : null;
    $forma_valorada = isset($_POST['forma_valorada']) ? trim($_POST['forma_valorada']) : "";

    // Actualizar los datos en la base de datos
    $stmt = $conn->prepare("
        UPDATE ingresos 
        SET localidad = ?, promovente = ?, referencia_pago = ?, tipo_tramite = ?, direccion = ?, 
            denominacion = ?, superficie_total = ?, sup_has = ?, superficie_construida = ?, forma_valorada = ?
        WHERE id_nuc = ?
    ");
    $stmt->bind_param(
        "ssssssdddi",
        $localidad,
        $promovente,
        $referencia_pago,
        $tipo_tramite,
        $direccion,
        $denominacion,
        $superficie_total,
        $sup_has,
        $superficie_construida,
        $forma_valorada,
        $ingreso_id
    );

    if ($stmt->execute()) {
        $mensaje = "Registro actualizado correctamente.";
    } else {
        $mensaje = "Error al actualizar el registro: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>Editar Ingreso</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
</head>
<body class="is-preload">
    <!-- Header -->
    <div id="header">
        <div class="top">
            <!-- Logo -->
            <div id="logo">
                <span class="image avatar48"><img src="images/avatar.jpg" alt="" /></span>
                <h1 id="title">Editar ingresos</h1>
            </div>
            <!-- Nav -->
            <nav id="nav">
                <ul>
                    <li><a href="dashboard.php#usuarios" class="button">Regresar</a></li>
                </ul>
            </nav>
        </div>
    </div>
    <div id="main">
        <section class="two">
            <div class="container">
                <header>
                    <h2>Editar Registro de Ingreso</h2>
                </header>
                <?php if (!empty($mensaje)) echo "<p>$mensaje</p>"; ?>
                <form method="POST" action="">
                    <label>NUC:</label>
                    <input type="text" value="<?php echo htmlspecialchars($ingreso['nuc']); ?>" readonly><br>

                    <label>Tipo de Predio:</label>
                    <input type="text" value="<?php echo htmlspecialchars($ingreso['tipo_predio']); ?>" readonly><br>

                    <label>Municipio:</label>
                    <input type="text" value="<?php echo htmlspecialchars($ingreso['municipio']); ?>" readonly><br>

                    <label>Fecha:</label>
                    <input type="text" value="<?php echo htmlspecialchars($ingreso['fecha']); ?>" readonly><br>

                    <label>Localidad:</label>
                    <input type="text" name="localidad" value="<?php echo htmlspecialchars($ingreso['localidad']); ?>"><br>

                    <label>Promovente:</label>
                    <input type="text" name="promovente" value="<?php echo htmlspecialchars($ingreso['promovente']); ?>"><br>

                    <label>Referencia de Pago:</label>
                    <input type="text" name="referencia_pago" value="<?php echo htmlspecialchars($ingreso['referencia_pago']); ?>"><br>

                    <label>Tipo de Trámite:</label>
                    <input type="text" name="tipo_tramite" value="<?php echo htmlspecialchars($ingreso['tipo_tramite']); ?>"><br>

                    <label>Dirección:</label>
                    <input type="text" name="direccion" value="<?php echo htmlspecialchars($ingreso['direccion']); ?>"><br>

                    <label>Denominación:</label>
                    <input type="text" name="denominacion" value="<?php echo htmlspecialchars($ingreso['denominacion']); ?>"><br>

                    <label>Superficie Total (m²):</label>
                    <input type="number" name="superficie_total" step="0.01" value="<?php echo htmlspecialchars($ingreso['superficie_total']); ?>"><br>

                    <label>Superficie en Hectáreas:</label>
                    <input type="number" name="sup_has" step="0.01" value="<?php echo htmlspecialchars($ingreso['sup_has']); ?>"><br>

                    <label>Superficie Construida:</label>
                    <input type="number" name="superficie_construida" step="0.01" value="<?php echo htmlspecialchars($ingreso['superficie_construida']); ?>"><br>

                    <label>Forma Valorada:</label>
                    <input type="text" name="forma_valorada" value="<?php echo htmlspecialchars($ingreso['forma_valorada']); ?>"><br>

                    <button type="submit">Guardar Cambios</button>
                </form>
            </div>
        </section>
    </div>
</body>
</html>