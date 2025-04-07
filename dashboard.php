<?php
session_start();
   
    include 'config.php';
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }

    // Obtener datos del usuario
    $user_id = $_SESSION['user_id'];
    $area_id = $_SESSION['area_id'] ?? null;
    $role_id = $_SESSION['role_id'] ?? null;

    $query = "SELECT full_name FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Obtener los roles del usuario
    $query = "SELECT r.role_id, r.role_name 
            FROM user_roles ur
            JOIN roles r ON ur.role_id = r.role_id
            WHERE ur.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $roles = [];
    while ($row = $result->fetch_assoc()) {
        $roles[$row['role_id']] = $row['role_name'];
    }
    $stmt->close();

    $permisos = [
        'consultar' => false,
        'ingresar' => false,
        'editar' => false,
        'baja' => false,
        'procesos' => false
    ];

  
    $query = "SELECT permiso_consultar, permiso_ingresar, permiso_editar, permiso_baja, procesos 
    FROM permisos 
    WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id); // Usa el user_id directamente
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $permisos['consultar'] = $permisos['consultar'] || (bool) $row['permiso_consultar'];
        $permisos['ingresar'] = $permisos['ingresar'] || (bool) $row['permiso_ingresar'];
        $permisos['editar'] = $permisos['editar'] || (bool) $row['permiso_editar'];
        $permisos['baja'] = $permisos['baja'] || (bool) $row['permiso_baja'];
        $permisos['procesos'] = $permisos['procesos'] || (bool) $row['procesos'];
    }
    $stmt->close();


    // Definir las opciones del menú según el área
    $menu = [
        NULL => "Menú Super Administrador",
        1 => "Menú Informática",
        2 => "Menú Jurídico",
        3 => "Menú Dirección",
        4 => "Menú Vinculación",
        5 => "Menú Área Técnica"
    ];

    $area_nombre = $menu[$area_id] ?? "Área Desconocida";

    // Obtener los NUCs que están en el área del usuario
    $stmt = $conn->prepare("
        SELECT c.id_nuc, c.nuc, c.municipio, c.localidad 
        FROM historiales h
        JOIN ingresos c ON h.nuc_id = c.id_nuc
        WHERE h.area_destino = ?
    ");
    $stmt->bind_param("i", $area_id);
    $stmt->execute();
    $nucs = $stmt->get_result();
    $stmt->close();
?>

<?php
    ob_start(); 
    $mensaje = "";

    // Obtener lista de municipios
    $municipios = $conn->query("SELECT municipio_id, nombre FROM municipios ORDER BY nombre ASC");
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $curp = isset($_POST['curp']) ? trim($_POST['curp']) : "";
        $municipio_id = isset($_POST['municipio_id']) ? intval($_POST['municipio_id']) : 0;
        $tipo_predio = isset($_POST['tipo_predio']) ? strtolower($_POST['tipo_predio']) : "";
        $tipo_tramite = isset($_POST['tipo_tramite']) ? trim($_POST['tipo_tramite']) : "";
        $superficie_total = isset($_POST['superficie_total']) ? floatval($_POST['superficie_total']) : 0;
        $sup_has = isset($_POST['sup_has']) ? floatval($_POST['sup_has']) : 0;
        $nuc_im = isset($_POST['nuc_im']) ? trim($_POST['nuc_im']) : "";

        // Obtener nombre del municipio
        $stmt_mun = $conn->prepare("SELECT nombre FROM municipios WHERE municipio_id = ?");
        $stmt_mun->bind_param("i", $municipio_id);
        $stmt_mun->execute();
        $result_mun = $stmt_mun->get_result();
        $municipio_row = $result_mun->fetch_assoc();
        $municipio_nombre = $municipio_row['nombre'] ?? '';
        $stmt_mun->close();
    
        if (empty($curp) || empty($municipio_nombre) || empty($tipo_predio) || empty($tipo_tramite) || empty($nuc_im)) {
            $mensaje = "Todos los campos son obligatorios. Por favor, complete: ";
            if (empty($curp)) $mensaje .= "CURP, ";
            if (empty($municipio_nombre)) $mensaje .= "Municipio, ";
            if (empty($tipo_predio)) $mensaje .= "Tipo de Predio, ";
            if (empty($tipo_tramite)) $mensaje .= "Tipo de Trámite, ";
            if (empty($nuc_im)) $mensaje .= "NUC_IM, ";
            $mensaje = rtrim($mensaje, ", ") . ".";
        } else {
            $permitido = true;

            // Si el tipo de trámite es "SERVICIO PUBLICO", omitir validaciones
            if ($tipo_tramite === "SERVICIO PUBLICO") {
                $stmt_check = $conn->prepare("SELECT id_validacion FROM validacion WHERE nuc_im = ?");
                $stmt_check->bind_param("s", $nuc_im);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                if ($result_check->num_rows > 0) {
                    $mensaje = "Ya existe un registro con el mismo NUC_IM.";
                    $permitido = false;
                }
                $stmt_check->close();

                if ($permitido) {
                    // Configurar las variables de sesión necesarias para generar_nuc.php
                    $_SESSION['curp_validado'] = $curp;
                    $_SESSION['municipio_id'] = $municipio_id;
                    $_SESSION['nuc_im'] = $nuc_im;
                    $_SESSION['municipio_nombre'] = $municipio_nombre;
                    $_SESSION['tipo_predio'] = strtoupper($tipo_predio);
                    $_SESSION['tipo_tramite'] = $tipo_tramite;

                    // Insertar un registro en la tabla validacion para mantener consistencia
                    $fecha_consulta = date("Y-m-d H:i:s");
                    $stmt_insert_validacion = $conn->prepare("
                        INSERT INTO validacion (nuc_im, curp, fecha_consulta, municipio, tipo_predio, tipo_tramite, superficie_total, sup_has) 
                        VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)
                    ");
                    $stmt_insert_validacion->bind_param(
                        "ssssss",
                        $nuc_im,
                        $curp,
                        $fecha_consulta,
                        $municipio_nombre,
                        strtoupper($tipo_predio),
                        $tipo_tramite
                    );

                    if ($stmt_insert_validacion->execute()) {
                        $id_validacion = $stmt_insert_validacion->insert_id;
                        $_SESSION['validacion_id'] = $id_validacion;

                        // Redirigir a generar_nuc.php
                        header("Location: generar_nuc.php");
                        exit();
                    } else {
                        $mensaje = "Error al insertar en validacion: " . $stmt_insert_validacion->error;
                    }
                    $stmt_insert_validacion->close();
                }
            } else {
                // Verificar registros previos de validación para este CURP
                $stmt_validacion = $conn->prepare("
                    SELECT id_validacion, tipo_predio, SUM(sup_has) AS total_sup_has, COUNT(*) AS total_predios 
                    FROM validacion 
                    WHERE curp=? 
                    GROUP BY tipo_predio
                ");
                $stmt_validacion->bind_param("s", $curp);
                $stmt_validacion->execute();
                $result_validacion = $stmt_validacion->get_result();

                $total_urbanos = 0;
                $total_sup_has_rural = 0;

                while ($row = $result_validacion->fetch_assoc()) {
                    if (strcasecmp($row['tipo_predio'], 'URBANO') == 0 || strcasecmp($row['tipo_predio'], 'SUBURBANO') == 0) {
                        $total_urbanos = $row['total_predios'];
                    }
                    if (strcasecmp($row['tipo_predio'], 'RUSTICO') == 0) {
                        $total_sup_has_rural = $row['total_sup_has'];
                    }
                }
                $stmt_validacion->close();

                // Reglas de validación
                if ((($tipo_predio === "urbano" || $tipo_predio === "suburbano") && $total_urbanos >= 1) || 
                ($tipo_predio === "rustico" && ($total_sup_has_rural + $sup_has) > 60000)) {
                    $permitido = false;
                    $mensaje = "No se puede completar la validación. Reglas no cumplidas: ";
                    if ($tipo_predio === "urbano" || $tipo_predio === "suburbano") {
                        $mensaje .= "Solo se permite un predio urbano o suburbano. ";
                    }
                    if ($tipo_predio === "rustico" && ($total_sup_has_rural + $sup_has) > 60000) {
                        $mensaje .= "La superficie total de predios rústicos no puede exceder 60000 hectáreas.";
                    }
                }

                if ($permitido) {
                    // Verificar duplicados antes de insertar
                    $stmt_check = $conn->prepare("SELECT id_validacion FROM validacion WHERE nuc_im = ?");
                    $stmt_check->bind_param("s", $nuc_im);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    if ($result_check->num_rows > 0) {
                        $mensaje = "Ya existe un registro con el mismo NUC_IM.";
                    } else {
                        // Insertar en la tabla validacion
                        $fecha_consulta = date("Y-m-d H:i:s");
                        $tipo_predio_upper = strtoupper($tipo_predio);

                        // Determinar qué campo usar según el tipo de predio
                        $superficie_total = ($tipo_predio === "urbano" || $tipo_predio === "suburbano") ? $superficie_total : null;
                        $sup_has = ($tipo_predio === "rustico") ? $sup_has : null;

                        $stmt_insert_validacion = $conn->prepare("
                            INSERT INTO validacion (nuc_im, curp, fecha_consulta, municipio, tipo_predio, tipo_tramite, superficie_total, sup_has) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt_insert_validacion->bind_param(
                            "ssssssdd",
                            $nuc_im,
                            $curp,
                            $fecha_consulta,
                            $municipio_nombre,
                            $tipo_predio_upper,
                            $tipo_tramite,
                            $superficie_total,
                            $sup_has
                        );

                        if ($stmt_insert_validacion->execute()) {
                            $id_validacion = $stmt_insert_validacion->insert_id;

                            // Guardar datos en sesión
                            $_SESSION['curp_validado'] = $curp;
                            $_SESSION['municipio_id'] = $municipio_id;
                            $_SESSION['nuc_im'] = $nuc_im;
                            $_SESSION['municipio_nombre'] = $municipio_nombre;
                            $_SESSION['tipo_predio'] = $tipo_predio_upper;
                            $_SESSION['tipo_tramite'] = $tipo_tramite;
                            $_SESSION['validacion_id'] = $id_validacion;
                            $_SESSION['superficie_total'] = $superficie_total;
                            $_SESSION['sup_has'] = $sup_has;

                            // Redirigir a generar_nuc.php
                            header("Location: generar_nuc.php");
                            exit();
                        } else {
                            $mensaje = "Error al insertar en validacion: " . $stmt_insert_validacion->error;
                        }
                        $stmt_insert_validacion->close();
                    }
                    $stmt_check->close();
                }
            }
        }
    }
?>

<!DOCTYPE HTML>
<!--
	Prologue by HTML5 UP
	html5up.net | @ajlkn
	Free for personal and commercial use under the CCA 3.0 license (html5up.net/license)
-->
<html>
	<head>
		<title>Consulta de NUC</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
		<link rel="stylesheet" href="assets/css/main.css" />
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                <!-- Logo -->
                <div id="logo">
                    <span class="image avatar48"><img src="images/avatar.jpg" alt="" /></span>
                    <h1 id="title">Bienvenido, <?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p>Área: <?php echo htmlspecialchars($area_nombre); ?></p>
                    <p>Rol: <?php echo htmlspecialchars(implode(', ', $roles)); ?></p>
                </div>
                <!-- Nav -->
                <nav id="nav">
                    <ul>
                    
                    <li><a href="dashboard.php" id="link"><span class="icon solid fa-home">Inicio</span></a></li>
                        <?php if ($area_id == 1): // Super Administrador ?>
                            <li><a href="#usuarios" id="usuarios-link"><span class="icon solid fa-user">Gestión de Usuarios</span></a></li>
                            <li><a href="#areas" id="areas-link"><span class="icon solid fa-th">Gestión de Áreas</span></a></li>
                            <li><a href="#administrarUsuarios" id="administrarUsuarios-link"><span class="icon solid fa-user">Crear nuevo usuario</span></a></li>
                            <li><a href="#asignarPermisos" id="asignar_Permisos-link"><span class="icon solid fa-user">Permisos de usuario</span></a></li>
                        <?php endif; ?>

                        <?php if ($permisos['consultar']): ?>
                            <li><a href="#consultar" id="consultar-link"><span class="icon solid fa-th">Consulta de expedientes</span></a></li>
                        <?php endif; ?>
                        
                        <?php if ($permisos['procesos']): ?>
                            <li><a href="#asignarMovimiento" id="asignar_movimiento-link"><span class="icon solid fa-th">Asignar tarea a expedientes</span></a></li>
                        <?php endif; ?>

                        <?php if ($permisos['ingresar']): ?>
                            <li><a href="#validacion" id="validacion_curp-link"><span class="icon solid fa-th">Ingresar nuevo expediente</span></a></li>
                        <?php endif; ?>

                        <?php if ($permisos['baja']): ?>
                            <li><a href="#darBajaExpediente" id="darBajaExpediente-link"><span class="icon solid fa-th">Dar de baja un expediente</span></a></li>
                        <?php endif; ?>

                        
                        <li><a href="logout.php"><span class="icon solid fa-user">Cerrar sesión</span></a></li>    
                    </ul>
                </nav>
            </div>
            <div class="bottom">
                <div class="logo">
                    <img src="images/logoa.jpeg" alt="Logo de la Empresa" style="max-width: 100%; height: auto;">
                </div>
            </div>
        </div>

        <!-- Main -->
        <div id="main">
            <!-- Gestion de historial de movimientos -->
                <section class="one dark cover">
                <div class="container">
                    <header>
                        <h2>Historial de procesos</h2>
                    </header>
                    <?php
                        // Inicializar la variable $search_nuc
                        $search_nuc = isset($_POST['search_nuc']) ? trim($_POST['search_nuc']) : '';

                        if ($search_nuc) {
                            $query = "SELECT h.id, h.nuc_id, i.nuc, h.area_origen, h.area_destino, h.comentario, h.fecha_movimiento, u.full_name 
                                    FROM historiales h
                                    JOIN users u ON h.usuario_id = u.id
                                    JOIN ingresos i ON h.nuc_id = i.id_nuc
                                    WHERE i.nuc = ? AND i.estado = 1 -- Agregar condición para registros activos
                                    ORDER BY h.fecha_movimiento DESC";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("s", $search_nuc);
                            $stmt->execute();
                            $result = $stmt->get_result();
                        } else {
                            $query = "SELECT h.id, h.nuc_id, i.nuc, h.area_origen, h.area_destino, h.comentario, h.fecha_movimiento, u.full_name 
                                    FROM historiales h
                                    JOIN users u ON h.usuario_id = u.id
                                    JOIN ingresos i ON h.nuc_id = i.id_nuc
                                    WHERE i.estado = 1 -- Agregar condición para registros activos
                                    ORDER BY h.fecha_movimiento DESC
                                    LIMIT 10";
                            $result = $conn->query($query);
                        }
                    ?>
                    <form id="search_form" method="POST" action="">
                        <label for="search_nuc">Buscar por NUC:</label>
                        <input type="text" id="search_nuc" name="search_nuc" value="<?php echo htmlspecialchars($search_nuc); ?>">
                        <button type="submit">Buscar</button>
                        <button type="button" id="clear_search">Borrar consulta</button>
                    </form>
                    <div id="historial_table">
                        <table>
                            <tr>
                                <th>NUC</th>
                                <th>Área Origen</th>
                                <th>Área Destino</th>
                                <th>Comentario</th>
                                <th>Fecha</th>
                                <th>Usuario</th>
                            </tr>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['nuc']); ?></td>
                                        <td><?php echo htmlspecialchars($row['area_origen']); ?></td>
                                        <td><?php echo htmlspecialchars($row['area_destino']); ?></td>
                                        <td><?php echo htmlspecialchars($row['comentario']); ?></td>
                                        <td><?php echo htmlspecialchars($row['fecha_movimiento']); ?></td>
                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">No se encontraron registros</td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </section>

            <script>
            document.getElementById('search_form').addEventListener('submit', function(event) {
                event.preventDefault(); // Evitar el envío del formulario

                var search_nuc = document.getElementById('search_nuc').value;
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'actualizar_historial.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        document.getElementById('historial_table').innerHTML = xhr.responseText;
                    }
                };
                xhr.send('search_nuc=' + encodeURIComponent(search_nuc));
            });

            document.getElementById('clear_search').addEventListener('click', function() {
                document.getElementById('search_nuc').value = '';
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'actualizar_historial.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        document.getElementById('historial_table').innerHTML = xhr.responseText;
                    }
                };
                xhr.send();
            });
            </script>
                        <?php if ($area_id == 1): ?>
            <!-- Gestion de usuarios -->
            <section id="usuarios" class="two">
                <div class="container">
                    <header>
                        <h2>Usuarios</h2>
                    </header>
                    <?php
                        $sql = "SELECT u.id, u.username, u.email, u.status, GROUP_CONCAT(r.role_name SEPARATOR ', ') AS roles
                                FROM users u
                                LEFT JOIN user_roles ur ON u.id = ur.user_id
                                LEFT JOIN roles r ON ur.role_id = r.role_id
                                GROUP BY u.id";
                        $result = $conn->query($sql);
                    ?>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Estado</th>
                            <th>Roles</th>
                            <th>Acciones</th>
                        </tr>
                        <?php while ($row = $result->fetch_assoc()) { ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                                <td><?= htmlspecialchars($row['roles'] ?? '') ?></td>
                                <td>
                                <a href="editarUsuario.php?id=<?= $row['id'] ?>">Editar</a>
                                    <?php if ($row['status'] === 'active'): ?>
                                        <a href="desactivar.php?id=<?= $row['id'] ?>">Desactivar</a>
                                    <?php else: ?>
                                        <a href="activar.php?id=<?= $row['id'] ?>">Activar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                </div>
            </section>
            <!-- Gestion de areas -->
            <section id="areas" class="three">
                <div class="container">
                    <header>
                        <h2>Gestión de áreas</h2>
                    </header>
                    <?php
                        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre_area']) && isset($_POST['descripcion'])) {
                            $nombre_area = isset($_POST['nombre_area']) ? trim($_POST['nombre_area']) : '';
                            $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : null; // Manejar valores vacíos como NULL

                            if (!empty($nombre_area)) {
                                $query = "INSERT INTO areas (nombre_area, descripcion) VALUES (?, ?)";
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param("ss", $nombre_area, $descripcion);
                                
                                if ($stmt->execute()) {
                                    echo "<p style='color: green;'>Área agregada correctamente.</p>";
                                } else {
                                    echo "<p style='color: red;'>Error al agregar el área.</p>";
                                }
                            } else {
                                echo "<p style='color: red;'>El nombre del área es obligatorio.</p>";
                            }
                        }

                        // Obtener todas las áreas
                        $result = $conn->query("SELECT * FROM areas");
                    ?>
                    <!-- <form method="POST">
                        <label>Nombre del Área:</label>
                        <input type="text" name="nombre_area" required>
                        <label>Descripción:</label>
                        <input type="text" name="descripcion">
                        <button type="submit">Agregar Área</button>
                    </form> -->
                    <h3>Áreas y Usuarios</h3>
                    <table>
                        <tr>
                            <th>Área</th>
                            <th>Usuarios</th>
                        </tr>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['nombre_area'] ?? 'Sin nombre'); ?></td>
                                <td>
                                    <ul>
                                        <?php
                                            $area_id = $row['area_id']; 
                                            $users_stmt = $conn->prepare("SELECT username, full_name, email FROM users WHERE area_id = ?");
                                            $users_stmt->bind_param("i", $area_id);
                                            $users_stmt->execute();
                                            $users_result = $users_stmt->get_result();

                                            if ($users_result->num_rows > 0) {
                                                while ($user_row = $users_result->fetch_assoc()) {
                                                ?>
                                                    <li>
                                                    <?php echo htmlspecialchars($user_row['username'] ?? 'Usuario desconocido'); ?> - 
                                                    <?php echo htmlspecialchars($user_row['full_name'] ?? 'Sin nombre'); ?> - 
                                                    <?php echo htmlspecialchars($user_row['email'] ?? 'Sin correo'); ?>
                                                    </li>
                                                <?php                            
                                                }
                                            } else {
                                                echo "<li>No hay usuarios en esta área</li>";
                                            }
                                        ?>
                                    </ul>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            </section>
            <!-- Crear nuevo usuario -->
            <section id="administrarUsuarios" class="four">
                <div class="container">
                    <header>
                        <h2>Administrar usuarios</h2>
                    </header>
                    <form action="procesarUsuario.php" method="post">
                        <label for="username">Nombre de Usuario:</label>
                        <input type="text" id="username" name="username" required>

                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" required>

                        <label for="full_name">Nombre Completo:</label>
                        <input type="text" id="full_name" name="full_name" required>

                        <label for="email">Correo Electrónico:</label>
                        <input type="email" id="email" name="email">

                        <label for="area_id">Área:</label>
                        <select id="area_id" name="area_id" required>
                            <?php
                            $result = $conn->query("SELECT area_id, nombre_area FROM areas");
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row['area_id'] . "'>" . $row['nombre_area'] . "</option>";
                            }
                            ?>
                        </select>
                        
                        <label for="role_id">Rol:</label>
                        <select id="role_id" name="role_id" required>
                            <?php
                            $result = $conn->query("SELECT role_id, role_name FROM roles");
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row['role_id'] . "'>" . $row['role_name'] . "</option>";
                            }
                            ?>
                        </select><br>
                        <h3>Permisos</h3>
                        <label for="permiso_consultar">
                        <input type="checkbox" id="permiso_consultar" name="permiso_consultar" value="1">
                        Consultar
                        </label>

                        <label for="permiso_ingresar">
                        <input type="checkbox" id="permiso_ingresar" name="permiso_ingresar" value="1">
                        Ingresar
                        </label>

                        <label for="permiso_editar">
                        <input type="checkbox" id="permiso_editar" name="permiso_editar" value="1">
                        Editar
                        </label>

                        <label for="permiso_baja">
                        <input type="checkbox" id="permiso_baja" name="permiso_baja" value="1">
                        Baja
                        </label>
                        <label for="procesos">
                        <input type="checkbox" id= "procesos" name="procesos" value="!">
                        Procesos
                        </label> <br><br>

                        <input type="submit" value="Crear Usuario">
                    </form>
                    <!-- Mensaje de respuesta -->
                    <div id="mensaje"></div>

                    <script>
                        // Recibir mensaje de procesarUsuario.php si existe
                        const params = new URLSearchParams(window.location.search);
                        if (params.has("mensaje")) {
                            document.getElementById("mensaje").innerHTML = `<div class="message">${params.get("mensaje")}</div>`;
                        }
                    </script>
                </div>
            </section>
            <!-- Seccion de permisos -->
            <?php
                // Obtener permisos de todos los usuarios activos
                $permisosUsuarios = $conn->query("
                    SELECT u.id, u.username, 
                        COALESCE(p.permiso_consultar, 0) AS permiso_consultar, 
                        COALESCE(p.permiso_ingresar, 0) AS permiso_ingresar, 
                        COALESCE(p.permiso_editar, 0) AS permiso_editar, 
                        COALESCE(p.permiso_baja, 0) AS permiso_baja, 
                        COALESCE(p.procesos, 0) AS procesos
                    FROM users u
                    LEFT JOIN permisos p ON u.id = p.user_id
                    WHERE u.status = 'active'
                    ORDER BY u.username ASC
                ");

                $usuariosPermisos = [];
                while ($row = $permisosUsuarios->fetch_assoc()) {
                    $usuariosPermisos[] = $row;
                }
                

                // Obtener roles disponibles
                $roles = $conn->query("SELECT role_id, role_name FROM roles");
                
                // Manejo de peticiones AJAX
                if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
                    header('Content-Type: application/json');
                    
                    if ($_POST['action'] == "get_permisos" && isset($_POST['user_id'])) {
                        $user_id = $_POST['user_id'];
                        $query = $conn->prepare("SELECT * FROM permisos WHERE user_id = ?");
                        $query->bind_param("i", $user_id);
                        $query->execute();
                        $result = $query->get_result();
                        $permisos = $result->fetch_assoc() ?: [
                            'permiso_consultar' => 0,
                            'permiso_ingresar' => 0,
                            'permiso_editar' => 0,
                            'permiso_baja' => 0,
                            'procesos' => 0
                        ];
                        error_log("Permisos devueltos para user_id $user_id: " . json_encode($permisos));

                        echo json_encode($permisos);
                        exit();
                    }
                    
                    if ($_POST['action'] == "guardar_permisos" && isset($_POST['user_id'])) {
                        $user_id = $_POST['user_id'];
                        $permiso_consultar = isset($_POST['permiso_consultar']) ? 1 : 0;
                        $permiso_ingresar = isset($_POST['permiso_ingresar']) ? 1 : 0;
                        $permiso_editar = isset($_POST['permiso_editar']) ? 1 : 0;
                        $permiso_baja = isset($_POST['permiso_baja']) ? 1 : 0;
                        $procesos = isset($_POST['procesos']) ? 1 : 0;
                
                        $check_query = $conn->prepare("SELECT * FROM permisos WHERE user_id = ?");
                        $check_query->bind_param("i", $user_id);
                        $check_query->execute();
                        $result = $check_query->get_result();
                        if ($result->num_rows > 0) {
                            $update_query = $conn->prepare("UPDATE permisos SET permiso_consultar = ?, permiso_ingresar = ?, permiso_editar = ?, permiso_baja = ?, procesos = ? WHERE user_id = ?");
                            $update_query->bind_param("iiiiii", $permiso_consultar, $permiso_ingresar, $permiso_editar, $permiso_baja, $procesos, $user_id);
                            $update_query->execute();
                        } else {
                            $insert_query = $conn->prepare("INSERT INTO permisos (user_id, permiso_consultar, permiso_ingresar, permiso_editar, permiso_baja, procesos) VALUES (?, ?, ?, ?, ?, ?)");
                            $insert_query->bind_param("iiiiii", $user_id, $permiso_consultar, $permiso_ingresar, $permiso_editar, $permiso_baja, $procesos);
                            $insert_query->execute();
                        }
                        exit();
                    }
                }
            ?>
            <section id="asignarPermisos" class="two">
                <div class="container">
                    <header>
                        <h2>Permisos de usuario</h2>
                    </header>
                    <!-- Formulario para cambiar el rol de un usuario -->

                    <form id="formPermisos">
                        <label>Usuario:</label>
                        <select id="user_id_permisos" name="user_id" required>
                            <option value="">Seleccione un usuario</option>
                            <?php foreach ($usuariosPermisos as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                            <?php endforeach; ?>
                        </select><br><br>

                        <div id="form-permisos">
                            <h3>Permisos</h3>
                            <label>
                                <input type="checkbox" id="permiso_consultar" name="permiso_consultar" value="1">
                                Consultar
                            </label>

                            <label>
                                <input type="checkbox" id="permiso_ingresar" name="permiso_ingresar" value="1">
                                Ingresar
                            </label>

                            <label>
                                <input type="checkbox" id="permiso_editar" name="permiso_editar" value="1">
                                Editar
                            </label>

                            <label>
                                <input type="checkbox" id="permiso_baja" name="permiso_baja" value="1">
                                Baja
                            </label>

                            <label>
                                <input type="checkbox" id="procesos" name="procesos" value="1">
                                Procesos
                            </label><br>
                        </div>
                        <button type="submit">Guardar Permisos</button>
                    </form>

                    <br><h3>Permisos por Usuario</h3>
                    <div id="tablaPermisos">
                    <table>
                        <tr>
                            <th>Usuario</th>
                            <th>Consultar</th>
                            <th>Ingresar</th>
                            <th>Editar</th>
                            <th>Baja</th>
                            <th>Procesos</th>
                        </tr>
                        <?php foreach ($usuariosPermisos as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo $row['permiso_consultar'] ? 'Sí' : 'No'; ?></td>
                                <td><?php echo $row['permiso_ingresar'] ? 'Sí' : 'No'; ?></td>
                                <td><?php echo $row['permiso_editar'] ? 'Sí' : 'No'; ?></td>
                                <td><?php echo $row['permiso_baja'] ? 'Sí' : 'No'; ?></td>
                                <td><?php echo $row['procesos'] ? 'Sí' : 'No'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    </div>
                    <script>
                       $(document).ready(function() {
                            // Cargar permisos cuando se selecciona un usuario
                            $("#user_id_permisos").change(function() {
                                let userId = $(this).val();

                                if (userId) {
                                    cargarPermisos(userId);
                                } else {
                                    // Si no hay usuario, limpiar checkboxes
                                    $("#permiso_consultar, #permiso_ingresar, #permiso_editar, #permiso_baja, #procesos").prop('checked', false);
                                }
                            });

                            // Función para cargar permisos del usuario seleccionado
                            function cargarPermisos(user_id) {
                                $.post('permisos_handler.php', { action: "get_permisos", user_id: user_id }, function(response) {
                                    console.log("Respuesta del servidor:", response);

                                    // Asegurar que se seleccionen los checkboxes dentro del div con ID 'form-permisos'
                                    $("#form-permisos #permiso_consultar").prop('checked', !!parseInt(response.permiso_consultar));
                                    $("#form-permisos #permiso_ingresar").prop('checked', !!parseInt(response.permiso_ingresar));
                                    $("#form-permisos #permiso_editar").prop('checked', !!parseInt(response.permiso_editar));
                                    $("#form-permisos #permiso_baja").prop('checked', !!parseInt(response.permiso_baja));
                                    $("#form-permisos #procesos").prop('checked', !!parseInt(response.procesos));
                                }, "json");
                            }

                           // Manejo del envío del formulario para guardar permisos
                            $("#formPermisos").submit(function(e) {
                                e.preventDefault();

                                let userId = $("#user_id_permisos").val();
                                if (!userId) {
                                    alert("Seleccione un usuario antes de guardar permisos.");
                                    return;
                                }

                                $.post('permisos_handler.php', $(this).serialize() + "&action=guardar_permisos", function(response) {
                                    alert("Permisos guardados correctamente");
                                    // Recargar la página para actualizar la tabla
                                    window.location.reload();
                                });
                            });

                        });
                    </script>
                </div>
            </section>
            <?php endif; ?>
            <!-- Seccion de consulta de ingresos -->
            <?php
                $conn = new mysqli($servername, $username, $password, $dbname);

                if ($conn->connect_error) {
                    die("Conexión fallida: " . $conn->connect_error);
                }

                // Manejar la consulta del formulario
                $whereClauses = [];
                $params = [];
                $types = '';

                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    if (!empty($_POST['nuc'])) {
                        $whereClauses[] = "ingresos.nuc LIKE ?";
                        $params[] = '%' . $_POST['nuc'] . '%';
                        $types .= 's';
                    }
                    if (!empty($_POST['municipio'])) {
                        $whereClauses[] = "ingresos.municipio LIKE ?";
                        $params[] = '%' . $_POST['municipio'] . '%';
                        $types .= 's';
                    }
                    if (!empty($_POST['localidad'])) {
                        $whereClauses[] = "ingresos.localidad LIKE ?";
                        $params[] = '%' . $_POST['localidad'] . '%';
                        $types .= 's';
                    }
                    if (!empty($_POST['promovente'])) {
                        $whereClauses[] = "ingresos.promovente LIKE ?";
                        $params[] = '%' . $_POST['promovente'] . '%';
                        $types .= 's';
                    }
                    if (!empty($_POST['fecha'])) {
                        $whereClauses[] = "ingresos.fecha = ?";
                        $params[] = $_POST['fecha'];
                        $types .= 's';
                    }
                }

                $whereClause = '';
                if (!empty($whereClauses)) {
                    $whereClause = ' AND ' . implode(' AND ', $whereClauses);
                }

                // Realizar la consulta. Se asume que solo se muestran registros activos (estado = 1)
                $sql = "SELECT ingresos.*, historiales.area_origen, historiales.area_destino 
                        FROM ingresos 
                        LEFT JOIN historiales ON ingresos.id_nuc = historiales.nuc_id 
                        WHERE ingresos.estado = 1" . $whereClause . " 
                        ORDER BY ingresos.id_nuc DESC"; // Los registros más recientes aparecerán primero

                $stmt = $conn->prepare($sql);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
            ?>

            <?php if ($permisos['consultar']): ?>
            <section id="consultar" class="three">
                <div class="container">
                    <header>
                        <h2>Consulta de ingresos de expedientes</h2>
                    </header>
                    <form method="post" action="#consultar">
                        <label for="nuc">NUC:</label>
                        <input type="text" name="nuc" id="nuc">

                        <label for="municipio">Municipio:</label>
                        <input type="text" name="municipio" id="municipio">

                        <label for="localidad">Localidad:</label>
                        <input type="text" name="localidad" id="localidad">

                        <label for="promovente">Promovente:</label>
                        <input type="text" name="promovente" id="promovente">

                        <label for="fecha">Fecha:</label>
                        <input type="date" name="fecha" id="fecha">
                        <input type="submit" value="Buscar">
                    </form>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>NUC</th>
                                    <th>NUCIM</th>
                                    <th>Municipio</th>
                                    <th>Localidad</th>
                                    <th>Promovente</th>
                                    <th>Referencia de Pago</th>
                                    <th>Tipo de Predio</th>
                                    <th>Tipo de Trámite</th>
                                    <th>Dirección</th>
                                    <th>Denominación</th>
                                    <th>Superficie Total</th>
                                    <th>Sup Has</th>
                                    <th>Superficie Construida</th>
                                    <th>Forma Valorada</th>
                                    <th>Área Origen</th>
                                    <th>Área Destino</th>
                                    <?php if ($permisos['editar']): ?>
                                        <th>Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                // Usamos un arreglo para asegurarnos de imprimir solo el último registro para cada NUC
                                $impresos = [];
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        // Si ya se imprimió un registro para este NUC, lo omitimos
                                        if (isset($impresos[$row['nuc']])) {
                                            continue;
                                        }
                                        $impresos[$row['nuc']] = true;
                                        echo "<tr>
                                                <td>" . htmlspecialchars($row['fecha'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['nuc'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['nuc_im'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['municipio'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['localidad'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['promovente'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['referencia_pago'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['tipo_predio'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['tipo_tramite'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['direccion'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['denominacion'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['superficie_total'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['sup_has'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['superficie_construida'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['forma_valorada'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['area_origen'] ?? '') . "</td>
                                                <td>" . htmlspecialchars($row['area_destino'] ?? '') . "</td>";
                                        if ($permisos['editar']) {
                                            echo "<td>
                                                    <a href='editarIngresos.php?id=" . htmlspecialchars($row['id_nuc']) . "'>Editar</a>
                                                </td>";
                                        }
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='18'>No se encontraron resultados</td></tr>";
                                }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>


            <?php endif; ?>
            <!-- Seccion de asignar movimiento -->
            <?php

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $nuc_id = isset($_POST['nuc_id']) ? intval($_POST['nuc_id']) : null;
                $area_origen = isset($_POST['area_origen']) ? trim($_POST['area_origen']) : null;
                $area_destino = isset($_POST['area_destino']) ? trim($_POST['area_destino']) : null;
                $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : null;
                $fecha_movimiento = !empty($_POST['fecha_movimiento']) ? $_POST['fecha_movimiento'] : null;
                $usuario_id = $_SESSION['user_id']; // Usuario autenticado

                if (!empty($nuc_id) && !empty($area_origen) && !empty($area_destino) && !empty($comentario)) {

                    // Preparar la consulta
                    if (is_null($fecha_movimiento)) {
                        $stmt = $conn->prepare("INSERT INTO historiales (nuc_id, area_origen, area_destino, comentario, usuario_id) 
                                                VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("isssi", $nuc_id, $area_origen, $area_destino, $comentario, $usuario_id);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO historiales (nuc_id, area_origen, area_destino, comentario, fecha_movimiento, usuario_id) 

                                                VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("issssi", $nuc_id, $area_origen, $area_destino, $comentario, $fecha_movimiento, $usuario_id);
                    }



                    if ($stmt->execute()) {
                        echo "<p style='color: green;'>Movimiento registrado correctamente.</p>";
                    } else {
                        echo "<p style='color: red;'>Error al registrar el movimiento: " . $stmt->error . "</p>";
                    }
                } else {

                    echo "<p style='color: red;'>Todos los campos son obligatorios.</p>";
                }
            }



            // Obtener áreas disponibles

            $areas_result = $conn->query("SELECT nombre_area FROM areas");
            $areas = [];
            while ($row = $areas_result->fetch_assoc()) {
                $areas[] = $row['nombre_area'];
            }



            // Obtener NUCs disponibles
            $nucs = $conn->query("SELECT id_nuc, nuc FROM ingresos WHERE estado = 1"); // Agregar esta condición
            $nuc_list = [];
            while ($row = $nucs->fetch_assoc()) {
                $nuc_list[] = ['id' => $row['id_nuc'], 'nuc' => $row['nuc']];
            }
        ?>

        <?php if ($permisos['procesos']): ?>
        <section id="asignarMovimiento" class="four">
            <div class="container">
                <header>
                    <h2>Asignar tarea a expedientes</h2>
                </header>

                <form method="POST" action="dashboard.php">
                    <label>NUC:</label>
                    <input type="text" id="nuc_input" name="nuc_text" placeholder="Ingrese NUC" required>
                    <input type="hidden" id="nuc_id" name="nuc_id">
                    <ul id="nuc_suggestions" class="suggestions"></ul>
                    <label>Área Origen:</label>
                    <select name="area_origen" required>

                        <?php foreach ($areas as $area): ?>
                            <option value="<?php echo $area; ?>"><?php echo $area; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Área Destino:</label>
                    <select name="area_destino" required>
                        <?php foreach ($areas as $area): ?>
                            <option value="<?php echo $area; ?>"><?php echo $area; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Comentario:</label>
                    <input type="text" name="comentario" required>
                    <label>Fecha Movimiento:</label>
                    <input type="datetime-local" name="fecha_movimiento">
                    <button type="submit">Registrar Movimiento</button>
                </form>
            </div>
        </section>
        <?php endif; ?>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const nucInput = document.getElementById("nuc_input");
                const nucIdInput = document.getElementById("nuc_id");
                const suggestionList = document.getElementById("nuc_suggestions");
                const historialBody = document.getElementById("historial_body");
                const nucs = <?php echo json_encode($nuc_list); ?>;

                function fetchHistorial(nucId = null) {
                    fetch("actualizar_historial.php?nuc_id=" + (nucId || ""))
                        .then(response => response.text())
                        .then(data => {
                            historialBody.innerHTML = data;
                        });
                }
                nucInput.addEventListener("input", function () {
                    const search = this.value.toLowerCase();
                    suggestionList.innerHTML = "";
                    if (search.length === 0) {
                        suggestionList.style.display = "none";
                        fetchHistorial(); // Si se borra el campo, se cargan los últimos 10 movimientos
                        return;
                    }

                    const filteredNucs = nucs.filter(nuc => nuc.nuc.toLowerCase().includes(search));

                    if (filteredNucs.length === 0) {
                        suggestionList.style.display = "none";
                        return;
                    }

                    filteredNucs.forEach(nuc => {
                        const li = document.createElement("li");
                        li.textContent = nuc.nuc;
                        li.dataset.id = nuc.id;
                        li.onclick = function () {
                            nucInput.value = nuc.nuc;
                            nucIdInput.value = nuc.id;
                            suggestionList.innerHTML = "";
                            suggestionList.style.display = "none";
                            fetchHistorial(nuc.id); // Filtrar historial por el NUC seleccionado
                        };
                        suggestionList.appendChild(li);
                    });
                    suggestionList.style.display = "block";
                });

                document.addEventListener("click", function (e) {
                    if (!nucInput.contains(e.target) && !suggestionList.contains(e.target)) {
                        suggestionList.style.display = "none";
                    }
                });
                fetchHistorial(); // Cargar los últimos 10 movimientos al cargar la página
            });
        </script>

            <!-- Seccion de pre-registro -->
            <?php if ($permisos['ingresar']): ?>
            <section id="validacion" class="two">
                <div class="container">
                    <header>
                        <h2>Ingresar nuevo expediente</h2>
                    </header>
                    <script>
                        function mostrarCampoSuperficie() {
                            const tipoPredio = document.getElementById('tipo_predio').value.trim().toUpperCase();
                            const campoSuperficieTotal = document.getElementById('campo_superficie_total');
                            const campoSupHas = document.getElementById('campo_sup_has');

                            // Oculta ambos campos inicialmente
                            campoSuperficieTotal.classList.add('hidden');
                            campoSupHas.classList.add('hidden');

                            // Muestra el campo correspondiente según el tipo de predio seleccionado
                            if (tipoPredio === 'URBANO' || tipoPredio === 'SUBURBANO') {
                                campoSuperficieTotal.classList.remove('hidden');
                            } else if (tipoPredio === 'RUSTICO') {
                                campoSupHas.classList.remove('hidden');
                            }
                        }
                    </script>
                    <h3>Validación</h3>
                    <form method="POST" action="#validacion">
                        <label>CURP O RFC:</label>
                        <input type="text" name="curp" required><br>
                        
                        <label>Municipio:</label>
                        <select name="municipio_id" required>
                            <option value="">-- Seleccione un Municipio --</option>
                            <?php while ($row = $municipios->fetch_assoc()): ?>
                                <option value="<?php echo $row['municipio_id']; ?>">
                                    <?php echo htmlspecialchars($row['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select><br>

                        <label>Tipo de Trámite:</label>
                        <select name="tipo_tramite" required>
                            <option value="">Seleccione una opción</option>
                            <option value="PARTICULAR">Particular</option>
                            <option value="ESCUELAS">Escuelas</option>
                            <option value="MIGRANTE">Migrante</option>
                            <option value="PERSONA JURIDICA">Persona jurídica</option>
                            <option value="SERVICIO PUBLICO">Servicio público</option>
                            <option value="DESCONOCIDO">Desconocido</option>
                        </select><br>
                        
                        <label for="tipo_predio">Tipo de Predio:</label>
                        <select id="tipo_predio" name="tipo_predio" required onchange="mostrarCampoSuperficie()">
                            <option value="">Seleccione una opción</option>
                            <option value="URBANO">Urbano</option>
                            <option value="SUBURBANO">Suburbano</option>
                            <option value="RUSTICO">Rústico</option>
                        </select>

                        <div id="campo_superficie_total" class="hidden">
                            <label for="superficie_total">Superficie Total (m²):</label>
                            <input type="number" id="superficie_total" name="superficie_total" min="0" step="0.01">
                        </div>

                        <div id="campo_sup_has" class="hidden">
                            <label for="sup_has">Superficie (hectáreas):</label>
                            <input type="number" id="sup_has" name="sup_has" min="0" step="0.01">
                        </div>
                        <label>NUC_IM:</label>
                        <input type="text" name="nuc_im" required><br><br>
                        
                        <button type="submit">Validar y Guardar</button>
                    </form>
                    <?php if (!empty($mensaje)): ?>
                        <p style="color: red;"><?php echo htmlspecialchars($mensaje); ?></p>
                    <?php endif; ?>
                </div>
                <?php
                    ob_end_flush(); // Envía la salida almacenada y finaliza el buffer
                ?>
            </section>
            <?php endif; ?>
            <!-- Seccion de baja de expedientes -->
            <?php if ($permisos['baja']): ?>
                <section id="darBajaExpediente" class="five">
                    <div class="container">
                        <header>
                            <h2>Dar de Baja un Expediente</h2>
                        </header>
                        <form method="POST" action="#darBajaExpediente">
                            <label for="nuc_baja">Ingrese el NUC del expediente:</label>
                            <input type="text" id="nuc_baja" name="nuc_baja" required>
                            <button type="submit">Buscar Expediente</button>
                        </form>

                        <?php
                        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nuc_baja'])) {
                            $nuc_baja = trim($_POST['nuc_baja']);

                            // Buscar el expediente por NUC
                            $stmt = $conn->prepare("SELECT * FROM ingresos WHERE nuc = ? AND estado = 1"); // Agregar esta condición
                            $stmt->bind_param("s", $nuc_baja);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $expediente = $result->fetch_assoc();
                            $stmt->close();
                            if ($expediente) {
                                // Mostrar información del expediente
                                echo "<h3>Información del Expediente</h3>";
                                echo "<p><strong>NUC:</strong> " . htmlspecialchars($expediente['nuc']) . "</p>";
                                echo "<p><strong>Municipio:</strong> " . htmlspecialchars($expediente['municipio']) . "</p>";
                                echo "<p><strong>Localidad:</strong> " . htmlspecialchars($expediente['localidad']) . "</p>";
                                echo "<p><strong>Promovente:</strong> " . htmlspecialchars($expediente['promovente']) . "</p>";
                                echo "<p><strong>Estado Actual:</strong> " . ($expediente['estado'] == 1 ? "Activo" : "Inactivo") . "</p>";

                                // Botón para dar de baja
                                if ($expediente['estado'] == 1) {
                                    echo '<form method="POST" action="#darBajaExpediente">';
                                    echo '<input type="hidden" name="nuc_dar_baja" value="' . htmlspecialchars($expediente['nuc']) . '">';
                                    echo '<button type="submit" style="background-color: red; color: white; padding: 10px; border: none; cursor: pointer;">Dar de Baja Expediente</button>';
                                    echo '</form>';
                                } else {
                                    echo "<p style='color: red;'>El expediente ya está dado de baja.</p>";
                                }
                            } else {
                                echo "<p style='color: red;'>No se encontró ningún expediente con el NUC proporcionado.</p>";
                            }
                        }

                        // Procesar la baja del expediente
                        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nuc_dar_baja'])) {
                            $nuc_dar_baja = trim($_POST['nuc_dar_baja']);

                            // Actualizar el estado del expediente a 0
                            $stmt = $conn->prepare("UPDATE ingresos SET estado = 0 WHERE nuc = ?");
                            $stmt->bind_param("s", $nuc_dar_baja);
                            if ($stmt->execute()) {
                                echo "<p style='color: green;'>El expediente con NUC " . htmlspecialchars($nuc_dar_baja) . " ha sido dado de baja correctamente.</p>";
                            } else {
                                echo "<p style='color: red;'>Error al dar de baja el expediente. Por favor, intente nuevamente.</p>";
                            }
                            $stmt->close();
                        }
                        ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </body>
</html>