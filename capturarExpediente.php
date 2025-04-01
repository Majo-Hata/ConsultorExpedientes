<?php
    session_start();
    include 'config.php';

    // Verificar que existan los datos obligatorios en la sesión
    if (
        !isset($_SESSION['validacion_id']) || 
        // !isset($_SESSION['nuc']) || 
        !isset($_SESSION['nuc_im']) || 
        !isset($_SESSION['municipio_nombre']) || 
        !isset($_SESSION['tipo_predio'])
    ) {
        die("ERROR: Datos incompletos en la sesión. Por favor, inicie el proceso desde el Dashboard.");
    }
    

    $mensaje = "";

   // Inicializar las variables con valores de la sesión o predeterminados
    $superficie_total = $_SESSION['superficie_total'] ?? 0;
    $sup_has = $_SESSION['sup_has'] ?? 0;

    // Procesar el formulario de ingreso (método POST)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $fecha = date("Y-m-d");
        $nuc = $_SESSION['nuc'];
        $nuc_im = $_SESSION['nuc_im'];
        $municipio = $_SESSION['municipio_nombre'];
        $localidad = isset($_POST['localidad']) ? trim($_POST['localidad']) : "";
        $promovente = isset($_POST['promovente']) ? trim($_POST['promovente']) : "";
        $referencia_pago = isset($_POST['referencia_pago']) ? trim($_POST['referencia_pago']) : "";
        $tipo_predio = $_SESSION['tipo_predio'];
        $tipo_tramite = isset($_POST['tipo_tramite']) ? trim($_POST['tipo_tramite']) : "";
        $direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : "";
        $denominacion = isset($_POST['denominacion']) ? trim($_POST['denominacion']) : "";
        $superficie_total = isset($_POST['superficie_total']) ? floatval($_POST['superficie_total']) : 0;
        $sup_has = isset($_POST['sup_has']) ? trim($_POST['sup_has']) : "";
        $superficie_construida = isset($_POST['superficie_construida']) ? floatval($_POST['superficie_construida']) : 0;
        $forma_valorada = isset($_POST['forma_valorada']) ? trim($_POST['forma_valorada']) : "";
        $procedente = isset($_POST['procedente']) ? intval($_POST['procedente']) : 0;
        $estado = 1; // Estado por defecto (1 = Activo)
        
        // Obtener las claves foráneas desde sesión (crear_numero_id puede no existir en algunos casos, en cuyo caso se enviará NULL)
        $validacion_id = $_SESSION['validacion_id'];
        $crear_numero_id = isset($_SESSION['crear_numero_id']) ? $_SESSION['crear_numero_id'] : NULL;
        
        $superficie_total = ($_SESSION['tipo_predio'] === 'URBANO') ? (isset($_POST['superficie_total']) ? floatval($_POST['superficie_total']) : 0) : 0;
        $sup_has = ($_SESSION['tipo_predio'] === 'RURAL') ? (isset($_POST['sup_has']) ? floatval($_POST['sup_has']) : 0) : 0;

        // Inserción en la base de datos
        $stmt = $conn->prepare("INSERT INTO ingresos 
            (fecha, nuc, nuc_im, municipio, localidad, promovente, referencia_pago, tipo_predio, tipo_tramite, direccion, denominacion, superficie_total, sup_has, superficie_construida, forma_valorada, procedente, estado, validacion_id, crear_numero_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Error en la preparación de la consulta: " . $conn->error);
        }
        $stmt->bind_param(
            "sssssssssssssssiiss",
            $fecha, $nuc, $nuc_im, $municipio, $localidad, $promovente, $referencia_pago, 
            $tipo_predio, $tipo_tramite, $direccion, $denominacion, $superficie_total, 
            $sup_has, $superficie_construida, $forma_valorada, $procedente, $estado, 
            $validacion_id, $crear_numero_id
        );
        if ($stmt->execute()) {
            $mensaje = "Ingreso guardado correctamente.";
        } else {
            $mensaje = "Error al guardar el ingreso: " . $stmt->error;
        }
        $stmt->close();
    }
?><!DOCTYPE HTML>
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
    <style>
        .hidden {
            display: none;
        }
    </style>
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
                <?php if (!empty($mensaje)) echo "<p>$mensaje</p>"; ?>
                <form id="formulario" action="capturarExpediente.php" method="post">
                    <!-- Datos precargados (solo lectura) -->
                    <label>NUC:</label>
                    <input type="text" name="nuc" value="<?php echo htmlspecialchars($_SESSION['nuc']); ?>" readonly><br>
                    
                    <label>NUC_IM:</label>
                    <input type="text" name="nuc_im" value="<?php echo htmlspecialchars($_SESSION['nuc_im']); ?>" readonly><br>
                    
                    <label>Municipio:</label>
                    <input type="text" id="municipio" name="municipio" value="<?php echo htmlspecialchars($_SESSION['municipio_nombre']); ?>" readonly><br>
                    
                    <!-- Localidades se cargarán vía AJAX según el municipio -->
                    <label for="localidad" class="required">Localidad:</label>
                    <select id="localidad" name="localidad" required>
                        <option value="">Seleccione una localidad</option>
                    </select><br>
                    
                    <label>Promovente:</label>
                    <input type="text" name="promovente" required><br>
                    
                    <label>Referencia de Pago:</label>
                    <input type="text" name="referencia_pago" required><br>
                    
                    <!-- Tipo de Predio precargado -->
                    <label>Tipo de Predio:</label>
                    <input type="text" id="tipo_predio" name="tipo_predio" value="<?php echo htmlspecialchars($_SESSION['tipo_predio']); ?>" readonly><br>

                    <!-- Campo para Superficie Total (URBANO) -->
                    <div id="campo_superficie_total" class="hidden">
                        <label for="superficie_total">Superficie Total (m²):</label>
                        <input type="number" id="superficie_total" name="superficie_total" min="0" step="0.01" value="<?php echo htmlspecialchars($superficie_total ?? ''); ?>">
                    </div><br>

                    <!-- Campo para Superficie en Hectáreas (RURAL) -->
                    <div id="campo_sup_has" class="hidden">
                        <label for="sup_has">Superficie (hectáreas):</label>
                        <input type="number" id="sup_has" name="sup_has" min="0" step="0.01" value="<?php echo htmlspecialchars($sup_has ?? ''); ?>">
                    </div><br>

                    <label>Superficie Construida:</label>
                    <input type="number" step="0.01" name="superficie_construida" required><br>
                    
                    <label>Tipo de Trámite:</label>
                    <select name="tipo_tramite" required>
                        <option value="">Seleccione una opción</option>
                        <option value="PARTICULAR">Particular</option>
                        <option value="ESCUELAS">Escuelas</option>
                        <option value="MIGRANTE">Migrante</option>
                        <option value="PERSONA JURIDICA">Persona jurídica</option>
                        <option value="SERVICIO PUBLICO">Servicio público</option>
                        <option value="DESCONOCIDO">Desconocido</option>
                    </select>
                    <br>
                    
                    <label>Dirección:</label>
                    <input type="text" name="direccion" required><br>
                    
                    <label>Denominación:</label>
                    <input type="text" name="denominacion" required><br>
                    
                    <label>Forma Valorada:</label>
                    <input type="text" name="forma_valorada" required><br>
                    
                    <label for="procedente">Procedente:</label>
                    <select name="procedente" id="procedente">
                        <option value="1">Procedente</option>
                        <option value="0">No Procedente</option>
                    </select>
                    <br>
                    
                    <button type="submit">Guardar</button>
                </form>
            </div>
        </section>
    </div>
    <script>
        // Función para cargar localidades vía AJAX según el municipio precargado
        function cargarLocalidades() {
            const municipio = document.getElementById('municipio').value;
            console.log("Municipio seleccionado:", municipio); // 👈 Verifica si el municipio se está enviando

            const localidadSelect = document.getElementById('localidad');
            if (municipio) {
                fetch(`obtener_localidades.php?municipio=${encodeURIComponent(municipio)}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log("Datos recibidos:", data); // 👈 Verifica si se están recibiendo datos

                        localidadSelect.innerHTML = '<option value="">Seleccione una localidad</option>';
                        data.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.localidad;
                            option.textContent = item.localidad;
                            localidadSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error al cargar localidades:', error));
            } else {
                localidadSelect.innerHTML = '<option value="">Seleccione una localidad</option>';
            }
        }

        // Cargar localidades al cargar la página
        document.addEventListener("DOMContentLoaded", function() {
            cargarLocalidades();
        });

                
        // Validar que se haya seleccionado una localidad antes de enviar
        document.getElementById('formulario').addEventListener('submit', function(e) {
            const localidad = document.getElementById('localidad').value;
            if (!localidad) {
                e.preventDefault();
                alert("Por favor, seleccione una localidad.");
            }
        });
        
        // Mostrar el campo correspondiente según el tipo de predio
        function mostrarCampoSuperficie() {
            const tipoPredio = document.getElementById('tipo_predio').value.trim().toUpperCase();
            console.log("Tipo de predio seleccionado:", tipoPredio);

            const campoSuperficieTotal = document.getElementById('campo_superficie_total');
            const campoSupHas = document.getElementById('campo_sup_has');

            // Oculta ambos campos inicialmente
            campoSuperficieTotal.classList.add('hidden');
            campoSupHas.classList.add('hidden');

            // Muestra el campo correspondiente
            if (tipoPredio === 'URBANO') {
                console.log("Mostrando campo de Superficie Total");
                campoSuperficieTotal.classList.remove('hidden');
            } else if (tipoPredio === 'RURAL') {
                console.log("Mostrando campo de Superficie en Hectáreas");
                campoSupHas.classList.remove('hidden');
            }
        }
        // Ejecutar la función al cargar la página
        document.addEventListener("DOMContentLoaded", function() {
            mostrarCampoSuperficie();
        });
    </script>
</body>
</html>

