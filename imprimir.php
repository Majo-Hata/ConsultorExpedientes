<?php
session_start();
include 'config.php';

// Función para formatear sup_has
function formatSupHas($value) {
    if (!is_numeric($value)) {
        return '00-00-00.00'; // Valor por defecto si no es válido
    }

    $value = number_format($value, 2, '.', '');
    $parts = explode('.', $value);
    $integerPart = str_pad($parts[0], 6, '0', STR_PAD_LEFT);
    $decimalPart = $parts[1] ?? '00';

    $formatted = substr($integerPart, 0, 2) . '-' . substr($integerPart, 2, 2) . '-' . substr($integerPart, 4, 2);
    return $formatted . '.' . $decimalPart;
}

// Obtener los datos de la sesión
$nuc = $_SESSION['nuc'] ?? '';
$nuc_im = $_SESSION['nuc_im'] ?? '';
$municipio = $_SESSION['municipio_nombre'] ?? '';
$localidad = $_SESSION['localidad'] ?? 'N/A';
$promovente = $_SESSION['promovente'] ?? 'N/A';
$referencia_pago = $_SESSION['referencia_pago'] ?? 'N/A';
$tipo_predio = $_SESSION['tipo_predio'] ?? '';
$superficie_total = $_SESSION['superficie_total'] ?? 'N/A';
$sup_has = isset($_SESSION['sup_has']) ? formatSupHas($_SESSION['sup_has']) : 'N/A';
$superficie_construida = $_SESSION['superficie_construida'] ?? 'N/A';
$tipo_tramite = $_SESSION['tipo_tramite'] ?? 'No definido';
$direccion = $_SESSION['direccion'] ?? 'N/A';
$denominacion = $_SESSION['denominacion'] ?? 'N/A';
$procedente = isset($_SESSION['procedente']) ? ($_SESSION['procedente'] ? 'Procedente' : 'No Procedente') : 'N/A';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Impresión de Expediente</title>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
                font-size: 12px;
                line-height: 1.2;
            }
            #formulario {
                width: 100%;
                margin: 0 auto;
                padding: 10px;
                box-sizing: border-box;
            }
            input, select, label {
                display: block;
                width: 95%;
                font-size: 12px !important;
                padding: 3px !important;
                margin-bottom: 5px !important;
                height: auto;
            }
            h2, h3 {
                font-size: 14px;
                margin-bottom: 10px;
            }
            button, .no-print {
                display: none !important;
            }
            @page {
                size: A4;
                margin: 10mm;
            }
        }
        /* Estilos para visualización en pantalla */
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        #formulario {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .campo {
            margin-bottom: 15px;
        }
        .campo label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        .campo div {
            padding: 5px;
            background: #e9e9e9;
            border: 1px solid #ccc;
        }
        button.no-print {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
        }
    </style>
    <script>
        // Abrir automáticamente la ventana de impresión al cargar la página
        window.onload = function() {
            window.print();
        };
    </script>
</head>
<body>
    <h2>Impresión de expediente guardado</h2>
    <div id="formulario">
        <div class="campo">
            <label>NUC:</label>
            <div><?php echo htmlspecialchars($nuc); ?></div>
        </div>
        <div class="campo">
            <label>NUC_IM:</label>
            <div><?php echo htmlspecialchars($nuc_im); ?></div>
        </div>
        <div class="campo">
            <label>Municipio:</label>
            <div><?php echo htmlspecialchars($municipio); ?></div>
        </div>
        <div class="campo">
            <label>Localidad:</label>
            <div><?php echo htmlspecialchars($localidad); ?></div>
        </div>
        <div class="campo">
            <label>Promovente:</label>
            <div><?php echo htmlspecialchars($promovente); ?></div>
        </div>
        <div class="campo">
            <label>Referencia de Pago:</label>
            <div><?php echo htmlspecialchars($referencia_pago); ?></div>
        </div>
        <div class="campo">
            <label>Tipo de Predio:</label>
            <div><?php echo htmlspecialchars($tipo_predio); ?></div>
        </div>
        <?php if ($tipo_predio === 'URBANO' || $tipo_predio === 'SUBURBANO'): ?>
        <div class="campo">
            <label>Superficie Total (m²):</label>
            <div><?php echo htmlspecialchars($superficie_total); ?></div>
        </div>
        <?php elseif ($tipo_predio === 'RUSTICO'): ?>
        <div class="campo">
            <label>Superficie (hectáreas):</label>
            <div><?php echo htmlspecialchars($sup_has); ?></div>
        </div>
        <?php endif; ?>
        <div class="campo">
            <label>Superficie Construida:</label>
            <div><?php echo htmlspecialchars($superficie_construida); ?></div>
        </div>
        <div class="campo">
            <label>Tipo de Trámite:</label>
            <div><?php echo htmlspecialchars($tipo_tramite); ?></div>
        </div>
        <div class="campo">
            <label>Dirección:</label>
            <div><?php echo htmlspecialchars($direccion); ?></div>
        </div>
        <div class="campo">
            <label>Denominación:</label>
            <div><?php echo htmlspecialchars($denominacion); ?></div>
        </div>
        <div class="campo">
            <label>Procedente:</label>
            <div><?php echo htmlspecialchars($procedente); ?></div>
        </div>
    </div>
    <button onclick="window.print()" class="no-print">Imprimir</button>
</body>
</html>