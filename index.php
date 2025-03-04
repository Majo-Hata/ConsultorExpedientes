<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.password, u.area_id, r.role_name 
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.role_id
        WHERE u.email = ? AND u.password = ? AND u.status = 'active'
    ");
    
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['usuario'] = $user['username'];
        $_SESSION['rol'] = $user['role_name'];
        $_SESSION['area_id'] = $user['area_id']; // Guardamos el area_id

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Credenciales incorrectas o usuario inactivo.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Iniciar Sesión</title>
</head>
<body>
    <h2>Login</h2>
    <form method="POST">
        <label>Email:</label>
        <input type="email" name="email" required>
        <label>Contraseña:</label>
        <input type="password" name="password" required>
        <button type="submit">Iniciar sesión</button>
    </form>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
</body>
</html>
