<?php
    session_start();
    include 'config.php';

    // Verificar que existan los datos obligatorios en la sesión
    if (
        !isset($_SESSION['validacion_id']) || 
        !isset($_SESSION['nuc']) || 
        !isset($_SESSION['nuc_im']) || 
        !isset($_SESSION['municipio_nombre']) || 
        !isset($_SESSION['tipo_predio'])
    ) {
        die("ERROR: Datos incompletos en la sesión. Por favor, inicie el proceso desde el Dashboard.");
    }


    $mensaje = "";

    // Inicializar variables (opcional)
    $superficie_total = $_SESSION['superficie_total'] ?? null;
    $sup_has = $_SESSION['sup_has'] ?? null;
    $tipo_tramite = $_SESSION['tipo_tramite'] ?? "No definido";

    // Procesar el formulario de ingreso (método POST)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $fecha = date("Y-m-d");
        $nuc = $_SESSION['nuc'];
        $nuc_im = $_SESSION['nuc_im'];
        $municipio = $_SESSION['municipio_nombre'];
        $localidad = isset($_POST['localidad']) ? trim($_POST['localidad']) : "";
        if (!empty($localidad)) {
            $_SESSION['localidad_guardada'] = $localidad;
        }
        $promovente = isset($_POST['promovente']) ? trim($_POST['promovente']) : "";
        $referencia_pago = isset($_POST['referencia_pago']) ? trim($_POST['referencia_pago']) : "";
        $tipo_predio = $_SESSION['tipo_predio'];
        $tipo_tramite = isset($_POST['tipo_tramite']) ? trim($_POST['tipo_tramite']) : "";
        $direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : "";
        $denominacion = isset($_POST['denominacion']) ? trim($_POST['denominacion']) : "";
        // Para URBANO: superficie_total
        $superficie_total = ($_SESSION['tipo_predio'] === 'URBANO'|| $_SESSION['tipo_predio'] === 'SUBURBANO')
        ? (isset($_POST['superficie_total']) && $_POST['superficie_total'] !== '' ? floatval($_POST['superficie_total']) : null)
        : null;
        // Para RURAL: sup_has
        $sup_has = ($_SESSION['tipo_predio'] === 'RURAL')
        ? (isset($_POST['sup_has']) && $_POST['sup_has'] !== '' ? floatval($_POST['sup_has']) : null)
        : null;
        $superficie_construida = isset($_POST['superficie_construida']) ? floatval($_POST['superficie_construida']) : 0;
        $forma_valorada = isset($_POST['forma_valorada']) ? trim($_POST['forma_valorada']) : "";
        $procedente = isset($_POST['procedente']) ? intval($_POST['procedente']) : 0;
        $estado = 1; // Estado por defecto (1 = Activo)
        
        // Obtener las claves foráneas desde sesión (crear_numero_id puede no existir en algunos casos, en cuyo caso se enviará NULL)
        $validacion_id = $_SESSION['validacion_id'];
        $crear_numero_id = isset($_SESSION['crear_numero_id']) ? $_SESSION['crear_numero_id'] : NULL;

        
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
        // Guardamos la localidad en sesión para usarla en la impresión
        $_SESSION['localidad_guardada'] = $localidad;
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
                <?php if (!empty($mensaje) && strpos($mensaje, "guardado correctamente") !== false): ?>
                    <button id="btnImprimir" onclick="imprimirFormulario()">Imprimir</button>
                <?php endif; ?>
                <form id="formulario" action="capturarExpediente.php" method="post">
                    <!-- Datos precargados (solo lectura) -->
                    <label>NUC:</label>
                    <input type="text" name="nuc" value="<?php echo htmlspecialchars($_SESSION['nuc']); ?>" readonly><br>
                    
                    <label>NUC_IM:</label>
                    <input type="text" name="nuc_im" value="<?php echo htmlspecialchars($_SESSION['nuc_im']); ?>" readonly><br>
                    
                    <label>Municipio:</label>
                    <input type="text" id="municipio" name="municipio" value="<?php echo htmlspecialchars($_SESSION['municipio_nombre']); ?>" readonly><br>
                    
                    <!-- Localidades se cargarán vía AJAX según el municipio -->
                    <label>Localidad:</label>
                    <select id="localidad" name="localidad" required>
                        <option value="">Seleccione una localidad</option>
                        <?php if (!empty($_SESSION['localidad_guardada'])): ?>
                            <option value="<?php echo htmlspecialchars($_SESSION['localidad_guardada']); ?>" selected>
                                <?php echo htmlspecialchars($_SESSION['localidad_guardada']); ?>
                            </option>
                        <?php endif; ?>
                    </select><br>

                    <label>Promovente:</label>
                    <input type="text" name="promovente" value="<?php echo htmlspecialchars($_POST['promovente'] ?? ''); ?>" required><br>
                    
                    <label>Referencia de Pago:</label>
                    <input type="text" name="referencia_pago" value="<?php echo htmlspecialchars($_POST['referencia_pago'] ?? ''); ?>" required><br>
                    
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
                    <input type="number" step="0.01" name="superficie_construida" value="<?php echo htmlspecialchars($_POST['superficie_construida'] ?? ''); ?>" required><br>
    
                    <label for="tipo_tramite">Tipo de Trámite:</label>
                    <input type="text" id="tipo_tramite" name="tipo_tramite" value="<?php echo htmlspecialchars($tipo_tramite); ?>" readonly>
                    <br>
                    
                    <label>Dirección:</label>
                    <input type="text" name="direccion" value="<?php echo htmlspecialchars($_POST['direccion'] ?? ''); ?>" required><br>
                    
                    <label>Denominación:</label>
                    <input type="text" name="denominacion" value="<?php echo htmlspecialchars($_POST['denominacion'] ?? ''); ?>" required><br>
                    
                    <label>Forma Valorada:</label>
                    <input type="text" name="forma_valorada" value="<?php echo htmlspecialchars($_POST['forma_valorada'] ?? ''); ?>" required><br>
                    
                    <label for="procedente">Procedente:</label>
                    <select name="procedente" id="procedente">
                        <option value="1" <?php echo (isset($_POST['procedente']) && $_POST['procedente'] == '1') ? 'selected' : ''; ?>>Procedente</option>
                        <option value="0" <?php echo (isset($_POST['procedente']) && $_POST['procedente'] == '0') ? 'selected' : ''; ?>>No Procedente</option>
                    </select>
                    <br>
                    
                    <button id="btnImprimir" class="no-print" onclick="imprimirFormulario()">Imprimir</button>
                </form>
            </div>
        </section>
        <script>
        console.log("Localidad guardada:", localidadGuardada);
        console.log("Formulario clonado:", formularioClone.outerHTML);
        </script>
    </div>
    <script>
        // Suponiendo que ya tienes definido un valor en PHP
        const localidadSeleccionada = "<?php echo isset($_SESSION['localidad_guardada']) ? addslashes($_SESSION['localidad_guardada']) : ''; ?>";

        function cargarLocalidades() {
            const municipio = document.getElementById('municipio').value;
            const localidadSelect = document.getElementById('localidad');
            localidadSelect.innerHTML = '<option value="">Seleccione una localidad</option>';
            if (municipio) {
                fetch(`obtener_localidades.php?municipio=${encodeURIComponent(municipio)}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.localidad;
                            option.textContent = item.localidad;
                            // Si coincide con la localidad previamente seleccionada, márcala
                            if (item.localidad === localidadSeleccionada) {
                                option.selected = true;
                            }
                            localidadSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error al cargar localidades:', error));
            }
        }

        document.addEventListener("DOMContentLoaded", cargarLocalidades);

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
            if (tipoPredio === 'URBANO' || tipoPredio === 'SUBURBANO') {
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

    // Función de impresión
   function imprimirFormulario() {
    const localidadGuardada = "<?php echo isset($_SESSION['localidad_guardada']) ? addslashes($_SESSION['localidad_guardada']) : ''; ?>";
    const selectLocalidad = formularioClone.querySelector('#localidad');
    if (selectLocalidad) {
        const inputLocalidad = document.createElement('input');
        inputLocalidad.type = 'text';
        inputLocalidad.name = 'localidad';
        inputLocalidad.value = localidadGuardada || "No definida";
        inputLocalidad.readOnly = true;
        inputLocalidad.style.width = '95%';
        inputLocalidad.style.fontSize = '12px';
        inputLocalidad.style.padding = '3px';
        inputLocalidad.style.marginBottom = '5px';

        selectLocalidad.parentNode.replaceChild(inputLocalidad, selectLocalidad);
    }

    // Abrir la ventana para impresión
    const ventanaImpresion = window.open('', '_blank');
    ventanaImpresion.document.write(`
        <html>
            <head>
                <title>Imprimir Formulario</title>
                <link rel="stylesheet" href="assets/css/main.css" />
                <style>
                    @media print {
                        body {
                            margin: 0;
                            padding: 0;
                            font-family: Arial, sans-serif;
                            font-size: 12px;
                            line-height: 1.2;
                        }
                        #formulario {
                            width: 100%;
                            margin: 0 auto;
                            padding: 10px;
                            box-sizing: border-box;
                        }
                        input, select, label {
                            display: block;
                            width: 95%;
                            font-size: 12px !important;
                            padding: 3px !important;
                            margin-bottom: 5px !important;
                            height: auto;
                        }
                        h2, h3 {
                            font-size: 14px;
                            margin-bottom: 10px;
                        }
                        button, .no-print {
                            display: none !important; /* Ocultar todos los botones y elementos con clase no-print */
                        }
                        @page {
                            size: A4;
                            margin: 10mm;
                        }
                    }
                </style>
            </head>
            <body>
                <p>Ingreso guardado correctamente.</p>
                ${formularioClone.outerHTML}
            </body>
        </html>
    `);
    ventanaImpresion.document.close();
    ventanaImpresion.print();
}
</script>


</body>
</html>

