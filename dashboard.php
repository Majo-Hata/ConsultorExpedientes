<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'config.php';

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
                        WHERE role_id IN ($placeholders)";

    
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
                                <?php if ($area_id == NULL): // Super Administrador ?>
                                    <li><a href="#usuarios" id="usuarios-link"><span class="icon solid fa-home">Gestión de Usuarios</span></a></li>
                                    <li><a href="#areas" id="areas-link"><span class="icon solid fa-th">Gestión de Áreas</span></a></li>
                                    <li><a href="#administrarUsuarios" id="administrarUsuarios-link"><span class="icon solid fa-user">Crear nuevo usuario</span></a></li>
                                    <li><a href="asignar_Permisos.php" id="asignar_Permisos-link"><span class="icon solid fa-envelope">Permisos de usuario</span></a></li>
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
                                    <li><a href="#capturar" id="capturar-link"><span class="icon solid fa-envelope">Capturar datos</span></a></li>                                    
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
            <!-- Intro -->
            <?php if ($area_id != NULL): // Super Administrador ?>
                <section id="top" class="one dark cover">
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
                <?php if ($area_id == NULL): // Super Administrador ?>
                    <section id="historial" class="one dark cover">
                    <div class="container">
                        <header>
                            <h2>Historial de procesos</h2>
                        </header>
                        <?php
                            include 'config.php';

                            if (!isset($_SESSION['user_id'])) {
                                header("Location: login.php");
                                exit();
                            }
                            
                            // Obtener historial de movimientos
                            $search_nuc = isset($_POST['search_nuc']) ? trim($_POST['search_nuc']) : '';
                            
                            if ($search_nuc) {
                                $query = "SELECT h.id, h.nuc_id, h.area_origen, h.area_destino, h.comentario, h.fecha_movimiento, u.full_name 
                                          FROM historiales h
                                          JOIN users u ON h.usuario_id = u.id
                                          WHERE h.nuc_id = ?
                                          ORDER BY h.fecha_movimiento DESC";
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param("s", $search_nuc);
                                $stmt->execute();
                                $result = $stmt->get_result();
                            } else {
                                $query = "SELECT h.id, h.nuc_id, h.area_origen, h.area_destino, h.comentario, h.fecha_movimiento, u.full_name 
                                          FROM historiales h
                                          JOIN users u ON h.usuario_id = u.id
                                          ORDER BY h.fecha_movimiento DESC
                                          LIMIT 10";
                                $result = $conn->query($query);
                            }
                        ?>
                            
                        <form method="POST" action="historial.php">
                            <label for="search_nuc">Buscar por NUC:</label>
                            <input type="text" id="search_nuc" name="search_nuc" value="<?php echo htmlspecialchars($search_nuc); ?>">
                            <button type="submit">Buscar</button>
                            <button type="button" onclick="window.location.href='historial.php'">Borrar consulta</button>
                        </form>

                        <table border="1">
                            <tr>
                                <th>NUC</th>
                                <th>Área Origen</th>
                                <th>Área Destino</th>
                                <th>Comentario</th>
                                <th>Fecha</th>
                                <th>Usuario</th>
                            </tr>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['nuc_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['area_origen']); ?></td>
                                    <td><?php echo htmlspecialchars($row['area_destino']); ?></td>
                                    <td><?php echo htmlspecialchars($row['comentario']); ?></td>
                                    <td><?php echo htmlspecialchars($row['fecha_movimiento']); ?></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    </div>
                    </section>

                    <section id="usuarios" class="two">
                        <div class="container">
                            <header>
                                <h2>Usuarios</h2>
                            </header>
                            <?php
                                include 'config.php';
                                if (!isset($_SESSION['user_id'])) {
                                    header("Location: index.php");
                                    exit();
                                }

                                $sql = "SELECT u.id, u.username, u.email, u.status, GROUP_CONCAT(r.role_name SEPARATOR ', ') AS roles
                                        FROM users u
                                        LEFT JOIN user_roles ur ON u.id = ur.user_id
                                        LEFT JOIN roles r ON ur.role_id = r.role_id
                                        GROUP BY u.id";
                                $result = $conn->query($sql);
                            ?>
                            <table border="1">
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
                                            <a href="editar_usuario.php?id=<?= $row['id'] ?>">Editar</a> |
                                            <a href="desactivar.php?id=<?= $row['id'] ?>">Desactivar</a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </table>
                        </div>
                    </section>

                    <section id="areas" class="three">
                        <div class="container">
                            <header>
                                <h2>Gestión de áreas</h2>
                            </header>
                            <?php
                                include 'config.php';

                                if (!isset($_SESSION['user_id'])) {
                                    header("Location: login.php");
                                    exit();
                                }

                                // Agregar área nueva
                                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                                    $nombre_area = trim($_POST['nombre_area']);
                                    $descripcion = trim($_POST['descripcion']) ?: NULL; // Manejar valores vacíos como NULL

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
                                            $area_id = $row['area_id']; // Cambio de 'id' a 'area_id'
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
                        
                        

                        <section id="administrarUsuarios" class="two">
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
                                    include 'config.php';
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
                                </label><br><br>


                                <input type="submit" value="Crear Usuario">
                            </form>
                        </div>
                        </section>
                        <?php
                        include 'config.php';

                        if (!isset($_SESSION['user_id'])) {
                            header("Location: index.php");
                            exit();
                        }

                        // Obtener usuarios activos y roles disponibles
                        $users = $conn->query("SELECT id, username, role_id FROM users WHERE status='active'");
                        $roles = $conn->query("SELECT * FROM roles");

                        // Procesar cambio de rol
                        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
                            $user_id = $_POST['user_id'];
                            $new_role_id = $_POST['role_id'];

                            // Actualizar el rol del usuario
                            $update_user_role = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
                            $update_user_role->bind_param("ii", $new_role_id, $user_id);
                            $update_user_role->execute();
                        }

                        // Procesar asignación de permisos
                        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['role_id'])) {
                            $role_id = $_POST['role_id'];

                            // Permisos seleccionados
                            $permiso_consultar = isset($_POST['permiso_consultar']) ? 1 : 0;
                            $permiso_ingresar = isset($_POST['permiso_ingresar']) ? 1 : 0;
                            $permiso_capturar = isset($_POST['permiso_capturar']) ? 1 : 0;
                            $permiso_baja = isset($_POST['permiso_baja']) ? 1 : 0;

                            // Verificar si el rol ya tiene permisos asignados
                            $check_query = $conn->prepare("SELECT * FROM permisos WHERE role_id = ?");
                            $check_query->bind_param("i", $role_id);
                            $check_query->execute();
                            $result = $check_query->get_result();

                            if ($result->num_rows > 0) {
                                // Si existen permisos, actualizar
                                $update_query = $conn->prepare("
                                    UPDATE permisos 
                                    SET permiso_consultar = ?, permiso_ingresar = ?, permiso_capturar = ?, permiso_baja = ? 
                                    WHERE role_id = ?");
                                $update_query->bind_param("iiiii", $permiso_consultar, $permiso_ingresar, $permiso_capturar, $permiso_baja, $role_id);
                                $update_query->execute();
                            } else {
                                // Si no existen, insertarlos
                                $insert_query = $conn->prepare("
                                    INSERT INTO permisos (role_id, permiso_consultar, permiso_ingresar, permiso_capturar, permiso_baja) 
                                    VALUES (?, ?, ?, ?, ?)");
                                $insert_query->bind_param("iiiii", $role_id, $permiso_consultar, $permiso_ingresar, $permiso_capturar, $permiso_baja);
                                $insert_query->execute();
                            }
                        }

                        // Obtener permisos actuales para cada rol
                        $permisos_actuales = [];
                        $result = $conn->query("SELECT * FROM permisos");
                        while ($row = $result->fetch_assoc()) {
                            $permisos_actuales[$row['role_id']] = $row;
                        }
                        ?>
                        <section id="asignarPermisos" class="five">
                        <div class="container">
                            <header>
                                <h2>Permisos de usuario</h2>
                            </header>
                            
                            <!-- Formulario para cambiar el rol de un usuario -->
                            <h3>Cambiar Rol de Usuario</h3>
                            <form method="POST">
                                <label>Usuario:</label>
                                <select name="user_id" required>
                                    <?php while ($row = $users->fetch_assoc()) { ?>
                                        <option value="<?= $row['id'] ?>"><?= $row['username'] ?></option>
                                    <?php } ?>
                                </select>

                                <label>Nuevo Rol:</label>
                                <select name="role_id" required>
                                    <?php
                                    $roles->data_seek(0); // Reiniciar puntero de la consulta
                                    while ($row = $roles->fetch_assoc()) { ?>
                                        <option value="<?= $row['role_id'] ?>"><?= $row['role_name'] ?></option>
                                    <?php } ?>
                                </select>

                                <button type="submit">Actualizar Rol</button>
                            </form>

                            <!-- Formulario para asignar permisos a un rol -->
                            <h3>Asignar Permisos a un Rol</h3>
                            <form method="POST">
                                <label>Rol:</label>
                                <select name="role_id" required>
                                    <?php
                                    $roles->data_seek(0);
                                    while ($row = $roles->fetch_assoc()) { ?>
                                        <option value="<?= $row['role_id'] ?>"><?= $row['role_name'] ?></option>
                                    <?php } ?>
                                </select>

                                <h3>Permisos:</h3>
                                <label>
                                    <input type="checkbox" name="permiso_consultar" value="1"> Consultar
                                </label>
                                <label>
                                    <input type="checkbox" name="permiso_ingresar" value="1"> Ingresar
                                </label>
                                <label>
                                    <input type="checkbox" name="permiso_capturar" value="1"> Capturar
                                </label>
                                <label>
                                    <input type="checkbox" name="permiso_baja" value="1"> Baja
                                </label>

                                <button type="submit">Guardar Permisos</button>
                            </form>

                            <!-- Tabla con los permisos actuales -->
                            <br><h3>Permisos Actuales</h3>
                            <table border="1">
                                <tr>
                                    <th>Rol</th>
                                    <th>Consultar</th>
                                    <th>Ingresar</th>
                                    <th>Capturar</th>
                                    <th>Baja</th>
                                </tr>
                                <?php
                                $roles->data_seek(0);
                                while ($row = $roles->fetch_assoc()) {
                                    $role_id = $row['role_id'];
                                    $perm = $permisos_actuales[$role_id] ?? ['permiso_consultar' => 0, 'permiso_ingresar' => 0, 'permiso_capturar' => 0, 'permiso_baja' => 0];
                                ?>
                                    <tr>
                                        <td><?= $row['role_name'] ?></td>
                                        <td><?= $perm['permiso_consultar'] ? '✔' : '✖' ?></td>
                                        <td><?= $perm['permiso_ingresar'] ? '✔' : '✖' ?></td>
                                        <td><?= $perm['permiso_capturar'] ? '✔' : '✖' ?></td>
                                        <td><?= $perm['permiso_baja'] ? '✔' : '✖' ?></td>
                                    </tr>
                                <?php } ?>
                            </table>

                        </div>
                    </section>
                <?php endif; ?>
                <?php
                    include 'config.php';

                    $conn = new mysqli($servername, $username, $password, $dbname);

                    if ($conn->connect_error) {
                        die("Conexión fallida: " . $conn->connect_error);
                    }

                    // manejar la consulta del formulario
                    $whereClause = "";
                    if ($_SERVER["REQUEST_METHOD"] == "POST") {
                        $filtro = $_POST['filtro'];
                        $valor = $_POST['valor'];
                        if (!empty($filtro) && !empty($valor)) {
                            $whereClause = "WHERE $filtro LIKE '%$valor%'";
                        }
                    }

                    // realizar la consulta
                    $sql = "SELECT ingresos.*, historiales.area_origen, historiales.area_destino 
                            FROM ingresos 
                            LEFT JOIN historiales ON ingresos.id_nuc = historiales.nuc_id 
                            $whereClause";
                    $result = $conn->query($sql);
                ?>
                <?php if ($permisos['consultar']): ?>
                    <section id="consultar" class="two">
                    <div class="container">
                        <header>
                            <h2>Consulta de ingresos de expedientes</h2>
                        </header>
                        <form method="post" action="">
                            <label for="filtro">Filtrar por:</label>
                            <select name="filtro" id="filtro">
                                <option value="nuc">NUC</option>
                                <option value="municipio">Municipio</option>
                                <option value="localidad">Localidad</option>
                                <option value="promovente">Promovente</option>
                                <option value="referencia_pago">Referencia de Pago</option>
                            </select>
                            <input type="text" name="valor" id="valor">
                            <input type="submit" value="Buscar">
                        </form>
                        <table border="1">
                            <tr>
                                <th>ID NUC</th>
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
                                <th>Estado</th>
                                <th>Área Origen</th>
                                <th>Área Destino</th>
                            </tr>
                            <?php
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td>{$row['id_nuc']}</td>
                                            <td>{$row['fecha']}</td>
                                            <td>{$row['nuc']}</td>
                                            <td>{$row['nuc_sim']}</td>
                                            <td>{$row['municipio']}</td>
                                            <td>{$row['localidad']}</td>
                                            <td>{$row['promovente']}</td>
                                            <td>{$row['referencia_pago']}</td>
                                            <td>{$row['tipo_predio']}</td>
                                            <td>{$row['tipo_tramite']}</td>
                                            <td>{$row['direccion']}</td>
                                            <td>{$row['denominacion']}</td>
                                            <td>{$row['superficie_total']}</td>
                                            <td>{$row['sup_has']}</td>
                                            <td>{$row['superficie_construida']}</td>
                                            <td>{$row['forma_valorada']}</td>
                                            <td>{$row['estado']}</td>
                                            <td>{$row['area_origen']}</td>
                                            <td>{$row['area_destino']}</td>
                                        </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='19'>No se encontraron resultados</td></tr>";
                            }
                            $conn->close();
                            ?>
                        </table>
                    </div>
                    </section>
                    <?php if ($permisos['procesos']): ?>
                    <?php
                        include 'config.php';

                        if ($_SERVER["REQUEST_METHOD"] == "POST") {
                            $nuc_id = intval($_POST['nuc_id']);
                            $area_origen = trim($_POST['area_origen']);
                            $area_destino = trim($_POST['area_destino']);
                            $comentario = trim($_POST['comentario']);
                            $usuario_id = $_SESSION['user_id']; // Usuario autenticado
                            $fecha_movimiento = $_POST['fecha_movimiento'];

                            if (empty($fecha_movimiento)) {
                                $stmt = $conn->prepare("INSERT INTO historiales (nuc_id, area_origen, area_destino, comentario, usuario_id) VALUES (?, ?, ?, ?, ?)");
                                $stmt->bind_param("isssi", $nuc_id, $area_origen, $area_destino, $comentario, $usuario_id);
                            } else {
                                $stmt = $conn->prepare("INSERT INTO historiales (nuc_id, area_origen, area_destino, comentario, fecha_movimiento, usuario_id) VALUES (?, ?, ?, ?, ?, ?)");
                                $stmt->bind_param("issssi", $nuc_id, $area_origen, $area_destino, $comentario, $fecha_movimiento, $usuario_id);
                            }

                            if ($stmt->execute()) {
                                echo "Movimiento registrado correctamente.";
                            } else {
                                echo "Error al registrar el movimiento: " . $conn->error;
                            }
                        }

                        // Obtener NUCs disponibles
                        $nucs = $conn->query("SELECT id_nuc, nuc FROM ingresos");

                        // Obtener áreas disponibles (guardarlas en un array para reutilizarlo)
                        $areas_result = $conn->query("SELECT nombre_area FROM areas");
                        $areas = [];
                        while ($row = $areas_result->fetch_assoc()) {
                            $areas[] = $row['nombre_area'];
                        }
                        ?>
                        <section id="asignarMovimiento" class="four">
                        <div class="container">
                            <header>
                                <h2>Asignar tarea a expedientes</h2>
                            </header>
                            <form method="POST">
                                <label>NUC:</label>
                                <select name="nuc_id" required>
                                    <?php while ($row = $nucs->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id_nuc']; ?>"><?php echo $row['nuc']; ?></option>
                                    <?php endwhile; ?>
                                </select>

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
                                <input type="datetime-local" name="fecha_movimiento" required>

                                <button type="submit">Registrar Movimiento</button>
                            </form>
                        </div>
                        </section>
                <?php endif; ?>

                <?php endif; ?>
                <?php
                    include 'config.php';

                    if (!isset($_SESSION['user_id'])) { 
                        header("Location: index.php"); 
                        exit(); 
                    }

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
                <?php if ($permisos['ingresar']): ?>
                    <section id="validacion" class="three">
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
                    </head>
                    <body>
                        <h3>Validar CURP</h3>
                        <form method="POST">
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
                <?php
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
                <?php if ($permisos['capturar']): ?>
                    <section id="capturar" class="four">
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

                        <a href="dashboard.php">Volver</a>

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