<?php
include 'config.php';

if (isset($_GET['municipio'])) {
    $municipio = $_GET['municipio'];

    $stmt = $conn->prepare("SELECT localidad FROM ubicaciones WHERE municipio = ?");
    $stmt->bind_param("s", $municipio);
    $stmt->execute();
    $result = $stmt->get_result();

    $localidades = [];
    while ($row = $result->fetch_assoc()) {
        $localidades[] = $row['localidad'];
    }
    $stmt->close();

    echo json_encode($localidades);
}
?>
