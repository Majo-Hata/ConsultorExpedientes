<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php#usuarios");
    exit();
}

$user_id = $_GET['id'];
$conn->query("UPDATE users SET status='inactive' WHERE id=$user_id");
header("Location: dashboard.php#usuarios");
?>
