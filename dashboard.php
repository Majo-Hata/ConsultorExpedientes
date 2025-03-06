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

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control</title>
</head>
<body>
    <h2>Bienvenido, <?php echo htmlspecialchars($user['full_name']); ?></h2>
    <p>Área: <?php echo htmlspecialchars($area_nombre); ?></p>
    <p>Rol: <?php echo htmlspecialchars(implode(', ', $roles)); ?></p>

    <ul>
        <?php if ($area_id == NULL): // Super Administrador ?>
            <li><a href="Usuarios.php">Gestión de Usuarios</a></li>
            <li><a href="areas.php">Gestión de Áreas</a></li>
            <li><a href="historial.php">Historial de Movimientos</a></li>
            <li><a href="asignar_movimiento.php">Asignar Movimiento</a></li>
            <li><a href="administrarUsuarios.php">Crear nuevo usuario</a></li>
            <li><a href="asignar_Permisos.php">Gestionar permisos</a></li>
        <?php endif; ?>

        <?php if ($area_id == 1): ?>
            <li><a href="#">Acciones para Informática</a></li>
        <?php elseif ($area_id == 2): ?>
            <li><a href="#">Acciones para Jurídico</a></li>
        <?php elseif ($area_id == 3): ?>
            <li><a href="#">Acciones para Dirección</a></li>
        <?php elseif ($area_id == 4): ?>
            <li><a href="#">Acciones para Vinculación</a></li>
        <?php elseif ($area_id == 5): ?>
            <li><a href="#">Acciones para Área Técnica</a></li>
        <?php endif; ?>

        <?php if ($permisos['consultar']): ?>
            <li><a href="consultar.php">Consultar ingresos</a></li>
        <?php endif; ?>
        
        <?php if ($permisos['ingresar']): ?>
            <li><a href="validacion_curp.php">Ingresar nuevo expediente</a></li>
        <?php endif; ?>
        
        <?php if ($permisos['capturar']): ?>
            <li><a href="capturar.php">Capturar datos</a></li>
        <?php endif; ?>
        
        <?php if ($permisos['baja']): ?>
            <li><a href="baja.php">Dar de baja</a></li>
        <?php endif; ?>
        
        <li><a href="logout.php">Cerrar Sesión</a></li>
    </ul>

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
</body>
</html>