<?php
include 'config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// manejar la consulta del formulario
$whereClause = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $filtro = $_POST['filtro'];
    $valor = $_POST['valor'];
    if (!empty($filtro) && !empty($valor)) {
        $whereClause = "WHERE $filtro LIKE '%$valor%'";
    }
}

// realizar la consulta
$sql = "SELECT ingresos.*, historiales.area_origen, historiales.area_destino 
        FROM ingresos 
        LEFT JOIN historiales ON ingresos.id_nuc = historiales.nuc_id 
        $whereClause";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Ingresos</title>
</head>
<body>
    <h1>Consulta de Ingresos</h1>
    <form method="post" action="">
        <label for="filtro">Filtrar por:</label>
        <select name="filtro" id="filtro">
            <option value="nuc">NUC</option>
            <option value="municipio">Municipio</option>
            <option value="localidad">Localidad</option>
            <option value="promovente">Promovente</option>
            <option value="referencia_pago">Referencia de Pago</option>
            <!-- Agrega más opciones según sea necesario -->
        </select>
        <input type="text" name="valor" id="valor">
        <input type="submit" value="Buscar">
    </form>
    <table border="1">
        <tr>
            <th>ID NUC</th>
            <th>Fecha</th>
            <th>NUC</th>
            <th>NUC SIM</th>
            <th>Municipio</th>
            <th>Localidad</th>
            <th>Promovente</th>
            <th>Referencia de Pago</th>
            <th>Tipo de Predio</th>
            <th>Tipo de Trámite</th>
            <th>Dirección</th>
            <th>Denominación</th>
            <th>Superficie Total</th>
            <th>Sup Has</th>
            <th>Superficie Construida</th>
            <th>Forma Valorada</th>
            <th>Estado</th>
            <th>Área Origen</th>
            <th>Área Destino</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['id_nuc']}</td>
                        <td>{$row['fecha']}</td>
                        <td>{$row['nuc']}</td>
                        <td>{$row['nuc_im']}</td>
                        <td>{$row['municipio']}</td>
                        <td>{$row['localidad']}</td>
                        <td>{$row['promovente']}</td>
                        <td>{$row['referencia_pago']}</td>
                        <td>{$row['tipo_predio']}</td>
                        <td>{$row['tipo_tramite']}</td>
                        <td>{$row['direccion']}</td>
                        <td>{$row['denominacion']}</td>
                        <td>{$row['superficie_total']}</td>
                        <td>{$row['sup_has']}</td>
                        <td>{$row['superficie_construida']}</td>
                        <td>{$row['forma_valorada']}</td>
                        <td>{$row['estado']}</td>
                        <td>{$row['area_origen']}</td>
                        <td>{$row['area_destino']}</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='19'>No se encontraron resultados</td></tr>";
        }
        $conn->close();
        ?>
    </table>
    <a href="dashboard.php">Volver al Dashboard</a>
</body>
</html>
