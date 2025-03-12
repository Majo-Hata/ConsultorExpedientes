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
    'baja' => false
];

if (!empty($roles)) {
    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $query = "SELECT permiso_consultar, permiso_ingresar, permiso_capturar, permiso_baja 
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
                                    <li><a href="#historial" id="historial-link"><span class="icon solid fa-user">Historial de Movimientos</span></a></li>
                                    <li><a href="asignar_movimiento.php" id="asignar_movimiento-link"><span class="icon solid fa-envelope">Asignar Movimiento</span></a></li>
                                    <li><a href="administrarUsuarios.php" id="administrarUsuarios-link"><span class="icon solid fa-user">Crear nuevo usuario</span></a></li>
                                    <li><a href="asignar_Permisos.php" id="asignar_Permisos-link"><span class="icon solid fa-envelope">Gestionar permisos</span></a></li>
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
                                    <li><a href="consultar.php" id="consultar-link"><span class="icon solid fa-envelope">Consultar ingresos</span></a></li>
                                <?php endif; ?>
                                
                                <?php if ($permisos['ingresar']): ?>
                                    <li><a href="validacion_curp.php" id="validacion_curp-link"><span class="icon solid fa-envelope">Ingresar nuevo expediente</span></a></li>
                                <?php endif; ?>
                                
                                <?php if ($permisos['capturar']): ?>
                                    <li><a href="capturar.php" id="capturar-link"><span class="icon solid fa-envelope">Capturar datos</span></a></li>                                    
                                <?php endif; ?>
                                
                                <?php if ($permisos['baja']): ?>
                                    <li><a href="baja.php" id="baja-link"><span class="icon solid fa-envelope">Dar de baja</span></a></li>    
                                <?php endif; ?>
                                <li><a href="logout.php"><span class="icon solid fa-envelope">Cerrar sesión</span></a></li>    
							</ul>
						</nav>

				</div>

				<div class="bottom">
					<!-- Social Icons -->
						<ul class="icons">
							<li><a href="#" class="icon brands fa-twitter"><span class="label">Twitter</span></a></li>
							<li><a href="#" class="icon brands fa-facebook-f"><span class="label">Facebook</span></a></li>
							<li><a href="#" class="icon brands fa-github"><span class="label">Github</span></a></li>
							<li><a href="#" class="icon brands fa-dribbble"><span class="label">Dribbble</span></a></li>
							<li><a href="#" class="icon solid fa-envelope"><span class="label">Email</span></a></li>
						</ul>
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

                    
                <?php endif; ?>

                <?php if ($otra_condicion): ?>
                    <section id="usuarios" class="two">
                    <div class="container">
                        <header>
                            <h2>Usuarios</h2>
                        </header>
                        
                    </div>
                    </section>
                <?php endif; ?>

                <?php if ($otra_condicion): ?>
                    <section id="usuarios" class="two">
                    <div class="container">
                        <header>
                            <h2>Usuarios</h2>
                        </header>
                        
                    </div>
                    </section>
                <?php endif; ?>
                <?php if ($otra_condicion): ?>
                    <section id="usuarios" class="two">
                    <div class="container">
                        <header>
                            <h2>Usuarios</h2>
                        </header>
                        
                    </div>
                    </section>
                <?php endif; ?>

                <?php if ($otra_condicion): ?>
                    <section id="usuarios" class="two">
                    <div class="container">
                        <header>
                            <h2>Usuarios</h2>
                        </header>
                        
                    </div>
                    </section>
                <?php endif; ?>

                <?php if ($otra_condicion): ?>
                    <section id="usuarios" class="two">
                    <div class="container">
                        <header>
                            <h2>Usuarios</h2>
                        </header>
                        
                    </div>
                    </section>
                <?php endif; ?>

                <?php if ($otra_condicion): ?>
                    <section id="usuarios" class="two">
                    <div class="container">
                        <header>
                            <h2>Usuarios</h2>
                        </header>
                        
                    </div>
                    </section>
                <?php endif; ?>

            </div>
</body>
</html>