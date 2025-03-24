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
        'capturar' => false,
        'baja' => false,
        'procesos' => false
    ];

    if (!empty($roles)) {
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $query = $query = "SELECT permiso_consultar, permiso_ingresar, permiso_capturar, permiso_baja, procesos 
                            FROM permisos 
                            WHERE user_id IN ($placeholders)";

        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(str_repeat("i", count($roles)), ...array_keys($roles));
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $permisos['consultar'] |= (bool) $row['permiso_consultar'];
            $permisos['ingresar'] |= (bool) $row['permiso_ingresar'];
            $permisos['capturar'] |= (bool) $row['permiso_capturar'];
            $permisos['baja'] |= (bool) $row['permiso_baja'];
            $permisos['procesos'] |= (bool) $row['procesos'];

        }
        $stmt->close();
    }


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
                        <?php if ($area_id == NULL): // Super Administrador ?>
                            <li><a href="#usuarios" id="usuarios-link"><span class="icon solid fa-home">Gestión de Usuarios</span></a></li>
                            <li><a href="#areas" id="areas-link"><span class="icon solid fa-th">Gestión de Áreas</span></a></li>
                            <li><a href="#administrarUsuarios" id="administrarUsuarios-link"><span class="icon solid fa-user">Crear nuevo usuario</span></a></li>
                            <li><a href="#asignarPermisos" id="asignar_Permisos-link"><span class="icon solid fa-envelope">Permisos de usuario</span></a></li>
                        <?php endif; ?>
                        <?php if ($area_id == 1): ?>
                            <li><a href="#" id="contact-link"><span class="icon solid fa-envelope">Acciones para Informática</span></a></li>
                        <?php elseif ($area_id == 2): ?>
                            <li><a href="#" id="contact-link"><span class="icon solid fa-envelope">Acciones para Jurídico</span></a></li>
                        <?php elseif ($area_id == 3): ?>
                            <li><a href="#" id="contact-link"><span class="icon solid fa-envelope">Acciones para Dirección</span></a></li>
                        <?php elseif ($area_id == 4): ?>
                            <li><a href="#" id="contact-link"><span class="icon solid fa-envelope">Acciones para Vinculación</span></a></li>
                        <?php elseif ($area_id == 5): ?>
                            <li><a href="#" id="contact-link"><span class="icon solid fa-envelope">Acciones para Área Técnica</span></a></li>
                        <?php endif; ?>

                        <?php if ($permisos['consultar']): ?>
                            <li><a href="#consultar" id="consultar-link"><span class="icon solid fa-envelope">Consulta de ingresos de expedientes</span></a></li>
                        <?php endif; ?>
                        
                        <?php if ($permisos['procesos']): ?>
                            <li><a href="#asignarMovimiento" id="asignar_movimiento-link"><span class="icon solid fa-envelope">Asignar tarea a expedientes</span></a></li>
                        <?php endif; ?>

                        <?php if ($permisos['ingresar']): ?>
                            <li><a href="#validacion" id="validacion_curp-link"><span class="icon solid fa-envelope">Ingresar nuevo expediente</span></a></li>
                        <?php endif; ?>
                        
                        <?php if ($permisos['capturar']): ?>
                            <li><a href="#capturar" id="capturar-link"><span class="icon solid fa-envelope">Captura de expediente</span></a></li>                                    
                        <?php endif; ?>
                        
                        <?php if ($permisos['baja']): ?>
                            <li><a href="#baja" id="baja-link"><span class="icon solid fa-envelope">Dar de baja</span></a></li>    
                        <?php endif; ?>

                        
                        <li><a href="logout.php"><span class="icon solid fa-envelope">Cerrar sesión</span></a></li>    
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
            <!-- Introducción -->
            <?php if ($area_id != NULL):?>
            <section class="one dark cover">
                <div class="container">
                    <header>
                        <h3>NUCs en tu área</h3>
                        <table border="1">
                            <tr>
                                <th>ID</th>
                                <th>NUC</th>
                                <th>Municipio</th>
                                <th>Localidad</th>
                            </tr>
                            <?php while ($row = $nucs->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id_nuc']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nuc']); ?></td>
                                    <td><?php echo htmlspecialchars($row['municipio']); ?></td>
                                    <td><?php echo htmlspecialchars($row['localidad']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    </header>
                </div>
            </section>
            <?php endif; ?>
            <!-- Gestion de historial de movimientos -->
            <?php if ($area_id == NULL): ?>
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
                                        WHERE i.nuc = ?
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
                                <td><?= $row['username'] ?></td>
                                <td><?= $row['email'] ?></td>
                                <td><?= $row['status'] ?></td>
                                <td><?= $row['roles'] ?></td>
                                <td>
                                    <a href="editarUsuario.php?id=<?= $row['id'] ?>">Editar</a> |
                                    <a href="desactivar.php?id=<?= $row['id'] ?>">Desactivar</a>
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
                    <form method="POST">
                        <label>Nombre del Área:</label>
                        <input type="text" name="nombre_area" required>
                        <label>Descripción:</label>
                        <input type="text" name="descripcion">
                        <button type="submit">Agregar Área</button>
                    </form>
                    <br><h3>Áreas y Usuarios</h3>
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
                        <input type="text" id="username" name="username" required><br><br>

                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" required><br><br>

                        <label for="full_name">Nombre Completo:</label>
                        <input type="text" id="full_name" name="full_name" required><br><br>

                        <label for="email">Correo Electrónico:</label>
                        <input type="email" id="email" name="email"><br><br>

                        <label for="area_id">Área:</label>
                        <select id="area_id" name="area_id" required>
                            <?php
                            $result = $conn->query("SELECT area_id, nombre_area FROM areas");
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row['area_id'] . "'>" . $row['nombre_area'] . "</option>";
                            }
                            ?>
                        </select><br><br>
                        
                        <label for="role_id">Rol:</label>
                        <select id="role_id" name="role_id" required>
                            <?php
                            $result = $conn->query("SELECT role_id, role_name FROM roles");
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row['role_id'] . "'>" . $row['role_name'] . "</option>";
                            }
                            ?>
                        </select><br><br>
                        <h3>Permisos</h3>
                        <label for="permiso_consultar">
                            <input type="checkbox" id="permiso_consultar" name="permiso_consultar" value="1">
                            Consultar
                        </label><br>

                        <label for="permiso_ingresar">
                        <input type="checkbox" id="permiso_ingresar" name="permiso_ingresar" value="1">
                        Ingresar
                        </label><br>

                        <label for="permiso_capturar">
                        <input type="checkbox" id="permiso_capturar" name="permiso_capturar" value="1">
                        Capturar
                        </label><br>

                        <label for="permiso_baja">
                        <input type="checkbox" id="permiso_baja" name="permiso_baja" value="1">
                        Baja
                        </label><br>
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

                    <style>
                        .message {
                            margin-top: 10px;
                            padding: 10px;
                            border-radius: 5px;
                            font-weight: bold;
                            text-align: center;
                            background-color: #d4edda;
                            color: #155724;
                        }
                        .error { background-color: #f8d7da; color: #721c24; }
                    </style>
                </div>
            </section>
            <!-- Seccion de permisos -->
            <?php
                // Obtener permisos de todos los usuarios activos
                $permisosUsuarios = $conn->query("
                    SELECT u.id, u.username, 
                        COALESCE(p.permiso_consultar, 0) AS permiso_consultar, 
                        COALESCE(p.permiso_ingresar, 0) AS permiso_ingresar, 
                        COALESCE(p.permiso_capturar, 0) AS permiso_capturar, 
                        COALESCE(p.permiso_baja, 0) AS permiso_baja, 
                        COALESCE(p.procesos, 0) AS procesos
                    FROM users u
                    LEFT JOIN permisos p ON u.id = p.user_id
                    WHERE u.status = 'active'
                    ORDER BY u.username ASC
                ");

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
                            'permiso_capturar' => 0,
                            'permiso_baja' => 0,
                            'procesos' => 0
                        ];
                        echo json_encode($permisos);
                        exit();
                    }
                    
                    if ($_POST['action'] == "guardar_permisos" && isset($_POST['user_id'])) {
                        $user_id = $_POST['user_id'];
                        $permiso_consultar = isset($_POST['permiso_consultar']) ? 1 : 0;
                        $permiso_ingresar = isset($_POST['permiso_ingresar']) ? 1 : 0;
                        $permiso_capturar = isset($_POST['permiso_capturar']) ? 1 : 0;
                        $permiso_baja = isset($_POST['permiso_baja']) ? 1 : 0;
                        $procesos = isset($_POST['procesos']) ? 1 : 0;
                
                        $check_query = $conn->prepare("SELECT * FROM permisos WHERE user_id = ?");
                        $check_query->bind_param("i", $user_id);
                        $check_query->execute();
                        $result = $check_query->get_result();
                        if ($result->num_rows > 0) {
                            $update_query = $conn->prepare("UPDATE permisos SET permiso_consultar = ?, permiso_ingresar = ?, permiso_capturar = ?, permiso_baja = ?, procesos = ? WHERE user_id = ?");
                            $update_query->bind_param("iiiiii", $permiso_consultar, $permiso_ingresar, $permiso_capturar, $permiso_baja, $procesos, $user_id);
                            $update_query->execute();
                        } else {
                            $insert_query = $conn->prepare("INSERT INTO permisos (user_id, permiso_consultar, permiso_ingresar, permiso_capturar, permiso_baja, procesos) VALUES (?, ?, ?, ?, ?, ?)");
                            $insert_query->bind_param("iiiiii", $user_id, $permiso_consultar, $permiso_ingresar, $permiso_capturar, $permiso_baja, $procesos);
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
                            <?php foreach ($usersArray as $row) { ?>
                                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['username']) ?></option>
                            <?php } ?>
                        </select>

                        <h3>Permisos:</h3>
                        <label for="permiso_consultar">
                            <input type="checkbox" id="permiso_consultar2" name="permiso_consultar" value="1">
                            Consultar
                        </label><br>

                        <label for="permiso_ingresar">
                        <input type="checkbox" id="permiso_ingresar2" name="permiso_ingresar" value="1">
                        Ingresar
                        </label><br>

                        <label for="permiso_capturar">
                        <input type="checkbox" id="permiso_capturar2" name="permiso_capturar" value="1">
                        Capturar
                        </label><br>

                        <label for="permiso_baja">
                        <input type="checkbox" id="permiso_baja2" name="permiso_baja" value="1">
                        Baja
                        </label><br>

                        <label for="procesos">
                            <input type="checkbox" id= "procesos2" name="procesos" value="!">
                            Procesos
                        </label> <br><br>

                        <button type="submit">Guardar Permisos</button>
                    </form>

                    <br><h3>Permisos por Usuario</h3>
                    <div id="tablaPermisos">
                        <table>
                            <tr>
                                <th>Usuario</th>
                                <th>Consultar</th>
                                <th>Ingresar</th>
                                <th>Capturar</th>
                                <th>Baja</th>
                                <th>Procesos</th>
                            </tr>
                            <?php while ($row = $permisosUsuarios->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo $row['permiso_consultar'] ? 'Sí' : 'No'; ?></td>
                                    <td><?php echo $row['permiso_ingresar'] ? 'Sí' : 'No'; ?></td>
                                    <td><?php echo $row['permiso_capturar'] ? 'Sí' : 'No'; ?></td>
                                    <td><?php echo $row['permiso_baja'] ? 'Sí' : 'No'; ?></td>
                                    <td><?php echo $row['procesos'] ? 'Sí' : 'No'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    </div>
                    <script>
                        $(document).ready(function() {
                        function cargarPermisos(user_id) {
                            $.post('', { action: "get_permisos", user_id: user_id }, function(response) {
                                let permisos = JSON.parse(response);
                                $("#permiso_consultar").prop('checked', permisos.permiso_consultar == 1);
                                $("#permiso_ingresar").prop('checked', permisos.permiso_ingresar == 1);
                                $("#permiso_capturar").prop('checked', permisos.permiso_capturar == 1);
                                $("#permiso_baja").prop('checked', permisos.permiso_baja == 1);
                                $("#procesos").prop('checked', permisos.procesos == 1);
                            });
                        }

                        $("#user_id_permisos").change(function() {
                            if ($(this).val()) {
                                cargarPermisos($(this).val());
                            }
                        });

                        $("#formPermisos").submit(function(e) {
                            e.preventDefault();
                            $.post('', $(this).serialize() + "&action=guardar_permisos", function() {
                                alert("Permisos guardados correctamente");
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

                // manejar la consulta del formulario
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
                    $whereClause = 'WHERE ' . implode(' AND ', $whereClauses);
                }

                // realizar la consulta
                $sql = "SELECT ingresos.*, historiales.area_origen, historiales.area_destino 
                        FROM ingresos 
                        LEFT JOIN historiales ON ingresos.id_nuc = historiales.nuc_id 
                        $whereClause";
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
                    <form method="post" action="">
                        <div style="margin-bottom: 10px;">
                            <label for="nuc">NUC:</label>
                            <input type="text" name="nuc" id="nuc">
                        </div>
                        <div style="margin-bottom: 10px;">
                            <label for="municipio">Municipio:</label>
                            <input type="text" name="municipio" id="municipio">
                        </div>
                        <div style="margin-bottom: 10px;">
                            <label for="localidad">Localidad:</label>
                            <input type="text" name="localidad" id="localidad">
                        </div>
                        <div style="margin-bottom: 10px;">
                            <label for="promovente">Promovente:</label>
                            <input type="text" name="promovente" id="promovente">
                        </div>
                        <div style="margin-bottom: 10px;">
                            <label for="fecha">Fecha:</label>
                            <input type="date" name="fecha" id="fecha">
                        </div>
                        <input type="submit" value="Buscar">
                    </form>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>NUC</th>
                                    <th>NUC SIM</th>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    if ($result->num_rows > 0) {
                                        while($row = $result->fetch_assoc()) {
                                            echo "<tr>
                                                    <td>" . htmlspecialchars($row['fecha'] ?? '') . "</td>
                                                    <td>" . htmlspecialchars($row['nuc'] ?? '') . "</td>
                                                    <td>" . htmlspecialchars($row['nuc_sim'] ?? '') . "</td>
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
                                                    <td>" . htmlspecialchars($row['area_destino'] ?? '') . "</td>
                                                </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='17'>No se encontraron resultados</td></tr>";
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            <?php endif; ?>
            <!-- Seccion de asignar movimiento -->
            <?php if ($permisos['procesos']): ?>
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
$nucs = $conn->query("SELECT id_nuc, nuc FROM ingresos");
$nuc_list = [];
while ($row = $nucs->fetch_assoc()) {
    $nuc_list[] = ['id' => $row['id_nuc'], 'nuc' => $row['nuc']];
}
?>

<section id="asignarMovimiento" class="four">
    <div class="container">
        <header>
            <h2>Asignar tarea a expedientes</h2>
        </header>
        <form method="POST">
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

<style>
.suggestions {
    position: absolute;
    background: white;
    border: 1px solid #ccc;
    list-style: none;
    padding: 0;
    margin: 0;
    width: 200px;
    max-height: 150px;
    overflow-y: auto;
    display: none;
    z-index: 1000; /* Asegura que esté por encima del formulario */
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1); /* Sombra para resaltar */
}

.suggestions li {
    padding: 8px;
    cursor: pointer;
    background: white;
}

.suggestions li:hover {
    background: #ddd;
}

</style>


            <?php endif; ?>
            <!-- Seccion de pre-registro -->
            <?php if ($permisos['ingresar']): ?>
            <?php
                $mensaje = "";

                // Obtener lista de municipios
                $municipios = $conn->query("SELECT municipio_id, nombre FROM municipios ORDER BY nombre ASC");
                
                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    $curp = isset($_POST['curp']) ? trim($_POST['curp']) : "";
                    $municipio_id = isset($_POST['municipio_id']) ? intval($_POST['municipio_id']) : 0;
                    $tipo_predio = isset($_POST['tipo_predio']) ? $_POST['tipo_predio'] : "";
                    $superficie_total = ($tipo_predio === "rural" && isset($_POST['superficie_total'])) ? floatval($_POST['superficie_total']) : 0;
                    $nuc_sim = isset($_POST['nuc_sim']) ? trim($_POST['nuc_sim']) : "";
                
                    // Obtener nombre del municipio
                    $stmt_mun = $conn->prepare("SELECT nombre FROM municipios WHERE municipio_id = ?");
                    $stmt_mun->bind_param("i", $municipio_id);
                    $stmt_mun->execute();
                    $result_mun = $stmt_mun->get_result();
                    $municipio_nombre = $result_mun->fetch_assoc()['nombre'] ?? '';
                    $stmt_mun->close();
                
                    if (empty($curp) || empty($municipio_nombre) || empty($tipo_predio) || empty($nuc_sim)) {
                        $mensaje = "Todos los campos son obligatorios.";
                    } else {
                        // Verificar si el CURP ya tiene registros previos
                        $stmt_validacion = $conn->prepare("
                            SELECT tipo_predio, SUM(superficie_total) AS total_superficie, COUNT(*) AS total_predios 
                            FROM validacion 
                            WHERE curp=? 
                            GROUP BY tipo_predio
                        ");
                        $stmt_validacion->bind_param("s", $curp);
                        $stmt_validacion->execute();
                        $result_validacion = $stmt_validacion->get_result();
                
                        $permitido = true;
                        $total_urbanos = 0;
                        $total_superficie_rural = 0;
                
                        while ($row = $result_validacion->fetch_assoc()) {
                            if (strcasecmp($row['tipo_predio'], 'urbano') == 0) {
                                $total_urbanos = $row['total_predios'];
                            }
                            if (strcasecmp($row['tipo_predio'], 'rural') == 0) {
                                $total_superficie_rural = $row['total_superficie'];
                            }
                        }
                        $stmt_validacion->close();
                
                        // Verificación de reglas
                        if (($tipo_predio === "urbano" && $total_urbanos >= 1) || 
                            ($tipo_predio === "rural" && ($total_superficie_rural + $superficie_total) > 6)) {
                            $permitido = false;
                        }
                
                        if ($permitido) {
                            $fecha_consulta = date("Y-m-d H:i:s");
                
                            // Verificar si el registro ya existe en `validacion`
                            $stmt_check_validacion = $conn->prepare("
                                SELECT COUNT(*) FROM validacion 
                                WHERE curp = ? AND municipio = ? AND tipo_predio = ? AND nuc_sim = ?
                            ");
                            $stmt_check_validacion->bind_param("ssss", $curp, $municipio_nombre, $tipo_predio, $nuc_sim);
                            $stmt_check_validacion->execute();
                            $stmt_check_validacion->bind_result($existe);
                            $stmt_check_validacion->fetch();
                            $stmt_check_validacion->close();
                
                            if ($existe == 0) {
                                $stmt_insert_validacion = $conn->prepare("
                                    INSERT INTO validacion (nuc_sim, curp, fecha_consulta, municipio, tipo_predio, superficie_total) 
                                    VALUES (?, ?, ?, ?, ?, ?)
                                ");
                                $stmt_insert_validacion->bind_param("sssssd", $nuc_sim, $curp, $fecha_consulta, $municipio_nombre, $tipo_predio, $superficie_total);
                                $stmt_insert_validacion->execute();
                                $stmt_insert_validacion->close();
                            }
                
                            // Verificar si ya existe un pre_registro
                            $stmt_check_pre_registro = $conn->prepare("
                                SELECT id FROM pre_registros WHERE curp = ? AND municipio_id = ?
                            ");
                            $stmt_check_pre_registro->bind_param("si", $curp, $municipio_id);
                            $stmt_check_pre_registro->execute();
                            $stmt_check_pre_registro->bind_result($pre_registro_id);
                            $stmt_check_pre_registro->fetch();
                            $stmt_check_pre_registro->close();
                
                            if (!$pre_registro_id) {
                                $stmt_insert_pre_registro = $conn->prepare("
                                    INSERT INTO pre_registros (curp, municipio_id, fecha_pre_registro) 
                                    VALUES (?, ?, ?)
                                ");
                                $fecha_pre_registro = date("Y-m-d");
                                $stmt_insert_pre_registro->bind_param("sis", $curp, $municipio_id, $fecha_pre_registro);
                                if ($stmt_insert_pre_registro->execute()) {
                                    $pre_registro_id = $conn->insert_id;
                                }
                                $stmt_insert_pre_registro->close();
                            }
                
                            $_SESSION['curp_validado'] = $curp;
                            $_SESSION['municipio_id'] = $municipio_id;
                            $_SESSION['pre_registro_id'] = $pre_registro_id;
                            $_SESSION['nuc_sim'] = $nuc_sim; 
                
                            $_SESSION['municipio_nombre'] = $municipio_nombre; // Guardar en sesión
                            header("Location: capturar.php");
                            exit();
                
                        } else {
                            $mensaje = "No cumple con los requisitos.";
                        }
                    }
                }
            ?>
            <section id="validacion" class="two">
                <div class="container">
                    <header>
                        <h2>Ingresar nuevo expediente</h2>
                    </header>
                    <script>
                        function toggleSuperficie() {
                            var tipoPredio = document.getElementById("tipo_predio").value;
                            var superficieInput = document.getElementById("superficie_total");
                            superficieInput.disabled = tipoPredio !== "rural";
                            superficieInput.required = tipoPredio === "rural";
                            if (tipoPredio !== "rural") {
                                superficieInput.value = "";
                            }
                        }
                    </script>
                    <h3>Validar CURP</h3>
                    <form method="POST" action="capturarExpediente.php">
                        <label>CURP:</label>
                        <input type="text" name="curp" required>
                        <br><br>

                        <label>Municipio:</label>
                        <select name="municipio_id" required>
                            <option value="">-- Seleccione un Municipio --</option>
                            <?php while ($row = $municipios->fetch_assoc()): ?>
                                <option value="<?php echo $row['municipio_id']; ?>">
                                    <?php echo htmlspecialchars($row['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <br><br>

                        <label>Tipo de Predio:</label>
                        <select name="tipo_predio" id="tipo_predio" onchange="toggleSuperficie()" required>
                            <option value="urbano">Urbano</option>
                            <option value="rural">Rural</option>
                        </select>
                        <br><br>

                        <label>Superficie Total (hectáreas):</label>
                        <input type="number" step="0.01" name="superficie_total" id="superficie_total" disabled>
                        <br><br>

                        <label>NUC_SIM:</label>
                        <input type="text" name="nuc_sim" required>
                        <br><br>

                        <button type="submit">Validar y Guardar</button>
                    </form>

                    <?php if (!empty($mensaje)) echo "<p>$mensaje</p>"; ?>
                </div>
            </section>
            <?php endif; ?>
            <!-- Seccion de captura de expediente -->
            <?php if ($permisos['capturar']): ?>
            <?php
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
                    $fecha = $_POST['fecha'] ?? null;
                    $nuc = $_POST['nuc'] ?? null;
                    $nuc_sim = $_POST['nuc_sim'] ?? null;
                    $municipio = $_POST['municipio'] ?? null;
                    $localidad = $_POST['localidad'] ?? null;
                    $promovente = $_POST['promovente'] ?? null;
                    $referencia_pago = $_POST['referencia_pago'] ?? null;
                    $tipo_predio = $_POST['tipo_predio'] ?? null;
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
                    if ($fecha && $nuc && $nuc_sim && $municipio && $localidad && $promovente && $referencia_pago && $tipo_predio && $tipo_tramite && $direccion && $denominacion && $superficie_total && $sup_has && $superficie_construida && $forma_valorada && $procedente !== null) {
                        // Insertar en la base de datos
                        $stmt = $conn->prepare("INSERT INTO ingresos (fecha, nuc, nuc_sim, municipio, localidad, promovente, referencia_pago, tipo_predio, tipo_tramite, direccion, denominacion, superficie_total, sup_has, superficie_construida, forma_valorada, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssssssssssssssss", $fecha, $nuc, $nuc_sim, $municipio, $localidad, $promovente, $referencia_pago, $tipo_predio, $tipo_tramite, $direccion, $denominacion, $superficie_total, $sup_has, $superficie_construida, $forma_valorada, $estado);
                
                        if ($stmt->execute()) {
                            echo "Registro guardado correctamente";
                        } else {
                            echo "Error al guardar los datos";
                        }
                
                        $stmt->close();
                    } else {
                        echo "Todos los campos son obligatorios";
                    }
                
                    $conn->close();
                    exit();
                }
            ?>
            <section id="capturar" class="three">
                <div class="container">
                    <header>
                        <h2>Captura de expediente</h2>
                    </header>
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
                    <form method="post">
                        <label for="fecha">Fecha:</label>
                        <input type="date" id="fecha" name="fecha" required><br><br>

                        <label for="nuc">NUC:</label>
                        <input type="text" id="nuc" name="nuc" value="<?php echo htmlspecialchars($nuc_generado); ?>" readonly><br><br>

                        <label for="nuc_sim">NUC SIM:</label>
                        <input type="text" id="nuc_sim" name="nuc_sim" value="<?php echo htmlspecialchars($nuc_sim); ?>" readonly><br><br>

                        <label>Municipio:</label>
                        <input type="text" id="municipio" name="municipio" value="<?php echo htmlspecialchars($municipio_nombre); ?>" readonly>
                        <br><br>

                        <label>Localidad:</label>
                        <select name="localidad" id="localidad" required>
                            <option value="">-- Seleccione una Localidad --</option>
                        </select>
                        <br><br>

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
                        
                        <label for="procedente">Procedente:</label>
                        <select name="procedente" id="procedente">
                        <option value="1">Procedente</option>
                        <option value="0">No Procedente</option>
                        </select>
                        
                        <button type="submit">Guardar</button>
                    </form>

                    <script>
                        document.addEventListener("DOMContentLoaded", function () {
                            cargarLocalidades();
                        });
                    </script>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </body>
</html>