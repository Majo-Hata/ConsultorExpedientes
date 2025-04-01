<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'config.php';

// Obtener el ID del usuario a editar
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $role_id = isset($_POST['role_id']) ? intval($_POST['role_id']) : 0;
    $area_id = isset($_POST['area_id']) ? intval($_POST['area_id']) : 0;

    if ($username && $email && $full_name && $status && $role_id && $area_id) {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, status = ?, area_id = ? WHERE id = ?");
        $stmt->bind_param("ssssii", $username, $email, $full_name, $status, $area_id, $user_id);
        if ($stmt->execute()) {
            // Actualizar roles del usuario
            $stmt = $conn->prepare("DELETE FROM user_roles WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $role_id);
            $stmt->execute();

            header("Location: dashboard.php#usuarios");
            exit();
        } else {
            $error = "Error al actualizar el usuario.";
        }
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}

// Obtener datos del usuario
$stmt = $conn->prepare("SELECT u.username, u.email, u.full_name, u.status, ur.role_id, u.area_id 
                        FROM users u 
                        LEFT JOIN user_roles ur ON u.id = ur.user_id 
                        WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "Usuario no encontrado.";
    exit();
}

// Obtener roles disponibles
$roles = $conn->query("SELECT role_id, role_name FROM roles");
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Editar Usuario</title>
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
                <h1 id="title">Editar Usuario</h1>
            </div>
            <!-- Nav -->
            <nav id="nav">
                <ul>
                    <li><a href="dashboard.php#usuarios" class="button">Regresar</a></li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Main -->
    <div id="main">
        <section class="two">
            <div class="container">
                <header>
                    <h2>Editar Usuario</h2>
                </header>
                <?php if (isset($error)): ?>
                    <p style="color: red;"><?php echo $error; ?></p>
                <?php endif; ?>
                <form method="POST">
                    <label for="username">Nombre de Usuario:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required><br><br>

                    <label for="email">Correo Electrónico:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br><br>

                    <label for="full_name">Nombre Completo:</label>
<input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required><br><br>

                    <label for="status">Estado:</label>
                    <select id="status" name="status" required>
                        <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactive" <?php echo $user['status'] == 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                    </select><br><br>

                    <label for="area_id">Área:</label>
                    <select id="area_id" name="area_id" required>
                        <?php
                        $areas = $conn->query("SELECT area_id, nombre_area FROM areas");
                        while ($area = $areas->fetch_assoc()) {
                            $selected = $area['area_id'] == $user['area_id'] ? 'selected' : '';
                            echo "<option value='" . $area['area_id'] . "' $selected>" . $area['nombre_area'] . "</option>";
                        }
                        ?>
                    </select><br><br>

                    <label for="role_id">Rol:</label>
                    <select id="role_id" name="role_id" required>
                        <?php while ($row = $roles->fetch_assoc()): ?>
                            <option value="<?php echo $row['role_id']; ?>" <?php echo $user['role_id'] == $row['role_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['role_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select><br><br>

                    <button type="submit">Guardar Cambios</button>
                </form>
            </div>
        </section>
    </div>
</body>
</html>