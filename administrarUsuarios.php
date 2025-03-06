<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Usuarios</title>
</head>
<body>
    <h1>Crear Nuevo Usuario</h1>
    <form action="procesarUsuario.php" method="post">
        <label for="username">Nombre de Usuario:</label>
        <input type="text" id="username" name="username" required><br><br>

        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required><br><br>

        <label for="full_name">Nombre Completo:</label>
        <input type="text" id="full_name" name="full_name" required><br><br>

        <label for="email">Correo Electrónico:</label>
        <input type="email" id="email" name="email"><br><br>

        <label for="area_id">Área:</label>
        <select id="area_id" name="area_id" required>
            <?php
            include 'config.php';
            $result = $conn->query("SELECT area_id, nombre_area FROM areas");
            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . $row['area_id'] . "'>" . $row['nombre_area'] . "</option>";
            }
            ?>
        </select><br><br>
        
        <label for="role_id">Rol:</label>
        <select id="role_id" name="role_id" required>
            <?php
            $result = $conn->query("SELECT role_id, role_name FROM roles");
            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . $row['role_id'] . "'>" . $row['role_name'] . "</option>";
            }
            ?>
        </select><br><br>

        <h2>Permisos</h2>
        <label for="permiso_consultar">Consultar:</label>
        <input type="checkbox" id="permiso_consultar" name="permiso_consultar" value="1"><br>

        <label for="permiso_ingresar">Ingresar:</label>
        <input type="checkbox" id="permiso_ingresar" name="permiso_ingresar" value="1"><br>

        <label for="permiso_capturar">Capturar:</label>
        <input type="checkbox" id="permiso_capturar" name="permiso_capturar" value="1"><br>

        <label for="permiso_baja">Baja:</label>
        <input type="checkbox" id="permiso_baja" name="permiso_baja" value="1"><br><br>

        <input type="submit" value="Crear Usuario">
    </form>
</body>
</html>