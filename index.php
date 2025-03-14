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
        <label>Ingrese su email</label>
      </div>
      <div class="input-field">
        <input type="password" name="password" required>
        <label>Ingrese su contraseña</label>
      </div>
      <button type="submit">Iniciar sesión</button>
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
        'images/puebla3.jpeg',
        'images/puebla4.jpeg',
        'images/puebla5.jpg',
        'images/puebla6.jpg',
        'images/puebla7.jpeg',
        'images/puebla8.jpg',
        'images/puebla9.jpg',
        'images/puebla10.jpeg'
    ];

    let currentIndex = 0;

    // Crear dos capas para la transición
    const background1 = document.createElement("div");
    const background2 = document.createElement("div");

    background1.classList.add("background");
    background2.classList.add("background", "background-next");

    document.body.appendChild(background1);
    document.body.appendChild(background2);

    background1.style.backgroundImage = `url(${images[currentIndex]})`;

    function changeBackground() {
        currentIndex = (currentIndex + 1) % images.length;

        // La segunda capa cambia la imagen y aparece lentamente
        background2.style.backgroundImage = `url(${images[currentIndex]})`;
        background2.style.opacity = 1;

        // Después de la transición, la capa principal cambia y la secundaria desaparece
        setTimeout(() => {
            background1.style.backgroundImage = `url(${images[currentIndex]})`;
            background2.style.opacity = 0;
        }, 2000); // Duración de la transición
    }

    setInterval(changeBackground, 5000); // Cambia cada 5 segundos
</script>


</body>
</html>