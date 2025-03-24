<?php
include 'config.php';

$search_nuc = isset($_POST['search_nuc']) ? trim($_POST['search_nuc']) : '';

if ($search_nuc) {
    $query = "SELECT h.id, h.nuc_id, i.nuc, h.area_origen, h.area_destino, h.comentario, h.fecha_movimiento, u.full_name 
                FROM historiales h
                JOIN users u ON h.usuario_id = u.id
                JOIN ingresos i ON h.nuc_id = i.id_nuc
                WHERE i.nuc = ?
                ORDER BY h.fecha_movimiento DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $search_nuc);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT h.id, h.nuc_id, i.nuc, h.area_origen, h.area_destino, h.comentario, h.fecha_movimiento, u.full_name 
                FROM historiales h
                JOIN users u ON h.usuario_id = u.id
                JOIN ingresos i ON h.nuc_id = i.id_nuc
                ORDER BY h.fecha_movimiento DESC
                LIMIT 10";
    $result = $conn->query($query);
}

echo '<table>
        <tr>
            <th>NUC</th>
            <th>Área Origen</th>
            <th>Área Destino</th>
            <th>Comentario</th>
            <th>Fecha</th>
            <th>Usuario</th>
        </tr>';
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<tr>
                <td>' . htmlspecialchars($row['nuc']) . '</td>
                <td>' . htmlspecialchars($row['area_origen']) . '</td>
                <td>' . htmlspecialchars($row['area_destino']) . '</td>
                <td>' . htmlspecialchars($row['comentario']) . '</td>
                <td>' . htmlspecialchars($row['fecha_movimiento']) . '</td>
                <td>' . htmlspecialchars($row['full_name']) . '</td>
              </tr>';
    }
} else {
    echo '<tr>
            <td colspan="6">No se encontraron registros</td>
          </tr>';
}
echo '</table>';
?>