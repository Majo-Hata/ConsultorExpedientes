<?php
include 'config.php'; // Asegúrate de incluir la conexión a la base de datos

if (isset($_GET['municipio'])) {
    $municipio = $_GET['municipio'];

    // Consulta para obtener las localidades del municipio
    $stmt = $conn->prepare("SELECT localidad FROM ubicaciones WHERE municipio = ?");
    $stmt->bind_param("s", $municipio);
    $stmt->execute();
    $result = $stmt->get_result();

    $localidades = [];
    while ($row = $result->fetch_assoc()) {
        $localidades[] = $row;
    }

    echo json_encode(array_values($localidades));
    exit();
} else {
    echo json_encode(['error' => 'Municipio no especificado']);
    exit();
}
?>