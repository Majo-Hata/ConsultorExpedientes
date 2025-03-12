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
        $_SESSION['area_id'] = $user['area_id'];

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Correo o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="wrapper">
    <form action="index.php" method="POST">
      <h2>Login</h2>
        <div class="input-field">
        <input type="text" name="email" required>
        <label>Enter your email</label>
      </div>
      <div class="input-field">
        <input type="password" name="password" required>
        <label>Enter your password</label>
      </div>
      <button type="submit">Log In</button>
      <div class="register">
        <p>Si no cuenta con una cuenta, solicitala en el área de informática</p>
      </div>
    </form>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
  </div>
  <script>
        const images = [
            'images/puebla.jpg',
            'images/puebla2.jpg',
            'images/puebla3.jpg',
            'images/puebla4.jpg',
            'images/puebla5.jpg',
            'images/puebla6.jpg',
            'images/puebla7.jpg'
        ];

        let currentIndex = 0;

        function changeBackground() {
            currentIndex = (currentIndex + 1) % images.length;
            document.body.style.backgroundImage = `url(${images[currentIndex]})`;
        }

        setInterval(changeBackground, 5000); // Cambia cada 5 segundos
        document.body.style.backgroundImage = `url(${images[currentIndex]})`; // Inicializa con la primera imagen
    </script>
</body>
</html>