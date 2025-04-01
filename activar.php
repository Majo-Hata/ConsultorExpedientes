<?php
include 'config.php';

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    // Actualizar el estado del usuario a "active"
    $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        header("Location: dashboard.php#usuarios");
        exit();
    } else {
        echo "Error al activar el usuario.";
    }

    $stmt->close();
}
?>