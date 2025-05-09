<?php
include 'config.php';

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    // Actualizar el estado del usuario a "inactive"
    $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        header("Location: dashboard.php#usuarios");
        exit();
    } else {
        echo "Error al desactivar el usuario.";
    }

    $stmt->close();
}
?>
