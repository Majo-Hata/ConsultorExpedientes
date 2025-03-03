<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: usuarios.php");
    exit();
}

$user_id = $_GET['id'];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $roles = $_POST['roles']; 

    $stmt = $conn->prepare("UPDATE users SET username=?, email=? WHERE id=?");
    $stmt->bind_param("ssi", $username, $email, $user_id);
    $stmt->execute();

    $conn->query("DELETE FROM user_roles WHERE user_id=$user_id");
    foreach ($roles as $role_id) {
        $conn->query("INSERT INTO user_roles (user_id, role_id) VALUES ($user_id, $role_id)");
    }

    header("Location: usuarios.php");
}

$user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
$roles = $conn->query("SELECT * FROM roles");
$user_roles = array_column($conn->query("SELECT role_id FROM user_roles WHERE user_id=$user_id")->fetch_all(), 0);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Usuario</title>
</head>
<body>
    <h2>Editar Usuario</h2>
    <form method="POST">
        <label>Usuario:</label>
        <input type="text" name="username" value="<?= $user['username'] ?>" required>
        <label>Email:</label>
        <input type="email" name="email" value="<?= $user['email'] ?>" required>
        <label>Roles:</label>
        <select name="roles[]" multiple>
            <?php while ($row = $roles->fetch_assoc()) { ?>
                <option value="<?= $row['role_id'] ?>" <?= in_array($row['role_id'], $user_roles) ? 'selected' : '' ?>><?= $row['role_name'] ?></option>
            <?php } ?>
        </select>
        <button type="submit">Guardar Cambios</button>
    </form>
</body>
</html>
