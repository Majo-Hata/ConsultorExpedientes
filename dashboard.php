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
		<title>Prologue by HTML5 UP</title>
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
                                    <li><a href="Usuarios.php" id="usuarios-link"><span class="icon solid fa-home">Gestión de Usuarios</span></a></li>
                                    <li><a href="areas.php" id="areas-link"><span class="icon solid fa-th">Gestión de Áreas</span></a></li>
                                    <li><a href="historial.php" id="historial-link"><span class="icon solid fa-user">Historial de Movimientos</span></a></li>
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

                <?php if ($area_id == NULL): // Super Administrador ?>
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
                <?php endif; ?>
            </div>
</body>
</html>