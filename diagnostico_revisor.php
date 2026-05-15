<?php
/**
 * Diagnóstico del Sistema de Revisión
 * 
 * Este script ayuda a diagnosticar problemas con el dashboard del revisor
 * mostrando información sobre la configuración, datos existentes y estructura de las tablas.
 */

require_once 'config/config.php';
require_once 'config/Database.php';

$db = new Database();
$conn = $db->getConnection();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Sistema Revisor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .diagnostic-card { margin-bottom: 20px; }
        .table-sm td, .table-sm th { padding: 0.5rem; font-size: 0.875rem; }
        .status-ok { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4"><i class="bi bi-clipboard-check"></i> Diagnóstico Sistema de Revisión</h1>
        <p class="text-muted">Ejecutado el: <?php echo date('d/m/Y H:i:s'); ?></p>

        <?php
        // 1. Verificar configuración
        echo '<div class="card diagnostic-card">';
        echo '<div class="card-header bg-primary text-white">';
        echo '<h5 class="mb-0"><i class="bi bi-gear"></i> Configuración del Sistema</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        
        $sql = "SELECT * FROM configuracion WHERE clave IN ('activar_revision_previa', 'max_file_size_mb')";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            echo '<table class="table table-sm">';
            echo '<tr><th>Configuración</th><th>Valor</th><th>Estado</th></tr>';
            while ($row = $result->fetch_assoc()) {
                $status = '';
                if ($row['clave'] == 'activar_revision_previa') {
                    $status = $row['valor'] == '1' 
                        ? '<span class="status-ok"><i class="bi bi-check-circle-fill"></i> Activado</span>'
                        : '<span class="status-warning"><i class="bi bi-exclamation-triangle-fill"></i> Desactivado</span>';
                }
                echo '<tr>';
                echo '<td><strong>' . htmlspecialchars($row['clave']) . '</strong></td>';
                echo '<td>' . htmlspecialchars($row['valor']) . '</td>';
                echo '<td>' . $status . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="text-warning"><i class="bi bi-exclamation-triangle"></i> No se encontró la tabla de configuración o está vacía.</p>';
        }
        
        echo '</div></div>';

        // 2. Verificar estructura de tablas
        echo '<div class="card diagnostic-card">';
        echo '<div class="card-header bg-success text-white">';
        echo '<h5 class="mb-0"><i class="bi bi-table"></i> Estructura de Base de Datos</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        
        $tablas = ['documentos', 'documento_seguimiento', 'items_transparencia', 'revisiones_documentos', 'usuarios'];
        echo '<table class="table table-sm">';
        echo '<tr><th>Tabla</th><th>Estado</th><th>Registros</th></tr>';
        
        foreach ($tablas as $tabla) {
            $sql = "SHOW TABLES LIKE '$tabla'";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $countSql = "SELECT COUNT(*) as total FROM `$tabla`";
                $countResult = $conn->query($countSql);
                $count = $countResult ? $countResult->fetch_assoc()['total'] : 0;
                
                echo '<tr>';
                echo '<td><strong>' . htmlspecialchars($tabla) . '</strong></td>';
                echo '<td><span class="status-ok"><i class="bi bi-check-circle-fill"></i> Existe</span></td>';
                echo '<td>' . number_format($count) . ' registros</td>';
                echo '</tr>';
            } else {
                echo '<tr>';
                echo '<td><strong>' . htmlspecialchars($tabla) . '</strong></td>';
                echo '<td><span class="status-error"><i class="bi bi-x-circle-fill"></i> No existe</span></td>';
                echo '<td>—</td>';
                echo '</tr>';
            }
        }
        
        echo '</table>';
        echo '</div></div>';

        // 3. Documentos por año
        echo '<div class="card diagnostic-card">';
        echo '<div class="card-header bg-info text-white">';
        echo '<h5 class="mb-0"><i class="bi bi-calendar"></i> Documentos por Año</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        
        $sql = "SELECT ds.ano, COUNT(DISTINCT d.id) as total 
                FROM documentos d
                INNER JOIN documento_seguimiento ds ON d.id = ds.documento_id
                WHERE d.estado IN ('pendiente', 'aprobado')
                GROUP BY ds.ano
                ORDER BY ds.ano DESC";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            echo '<table class="table table-sm">';
            echo '<tr><th>Año</th><th>Total Documentos</th></tr>';
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td><strong>' . htmlspecialchars($row['ano']) . '</strong></td>';
                echo '<td>' . number_format($row['total']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="text-warning"><i class="bi bi-exclamation-triangle"></i> No hay documentos en el sistema.</p>';
        }
        
        echo '</div></div>';

        // 4. Documentos por mes (año actual)
        $anoActual = date('Y');
        echo '<div class="card diagnostic-card">';
        echo '<div class="card-header bg-warning">';
        echo '<h5 class="mb-0"><i class="bi bi-calendar3"></i> Documentos por Mes (' . $anoActual . ')</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        
        $sql = "SELECT ds.mes, COUNT(DISTINCT d.id) as total 
                FROM documentos d
                INNER JOIN documento_seguimiento ds ON d.id = ds.documento_id
                WHERE d.estado IN ('pendiente', 'aprobado')
                AND ds.ano = ?
                GROUP BY ds.mes
                ORDER BY ds.mes";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $anoActual);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                  'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        
        if ($result && $result->num_rows > 0) {
            echo '<table class="table table-sm">';
            echo '<tr><th>Mes</th><th>Total Documentos</th></tr>';
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td><strong>' . $meses[$row['mes']] . '</strong></td>';
                echo '<td>' . number_format($row['total']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="text-warning"><i class="bi bi-exclamation-triangle"></i> No hay documentos para el año ' . $anoActual . '.</p>';
            echo '<p class="text-muted">Esto es normal si el año acaba de comenzar. Intenta ver años anteriores en el dashboard del revisor.</p>';
        }
        
        echo '</div></div>';

        // 5. Estado de revisiones
        echo '<div class="card diagnostic-card">';
        echo '<div class="card-header bg-secondary text-white">';
        echo '<h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Estado de Revisiones</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        
        $sql = "SELECT 
                    COUNT(*) as total_documentos,
                    SUM(CASE WHEN rd.estado = 'aprobado' THEN 1 ELSE 0 END) as aprobados,
                    SUM(CASE WHEN rd.estado = 'observado' THEN 1 ELSE 0 END) as observados,
                    SUM(CASE WHEN rd.documento_id IS NULL THEN 1 ELSE 0 END) as sin_revisar
                FROM documentos d
                INNER JOIN documento_seguimiento ds ON d.id = ds.documento_id
                LEFT JOIN revisiones_documentos rd ON d.id = rd.documento_id
                WHERE d.estado IN ('pendiente', 'aprobado')";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo '<div class="row">';
            echo '<div class="col-md-3">';
            echo '<div class="text-center p-3 bg-light rounded">';
            echo '<h3>' . number_format($row['total_documentos']) . '</h3>';
            echo '<p class="mb-0 text-muted">Total Documentos</p>';
            echo '</div></div>';
            echo '<div class="col-md-3">';
            echo '<div class="text-center p-3 bg-success bg-opacity-10 rounded">';
            echo '<h3 class="text-success">' . number_format($row['aprobados']) . '</h3>';
            echo '<p class="mb-0 text-muted">Aprobados</p>';
            echo '</div></div>';
            echo '<div class="col-md-3">';
            echo '<div class="text-center p-3 bg-warning bg-opacity-10 rounded">';
            echo '<h3 class="text-warning">' . number_format($row['observados']) . '</h3>';
            echo '<p class="mb-0 text-muted">Observados</p>';
            echo '</div></div>';
            echo '<div class="col-md-3">';
            echo '<div class="text-center p-3 bg-secondary bg-opacity-10 rounded">';
            echo '<h3 class="text-secondary">' . number_format($row['sin_revisar']) . '</h3>';
            echo '<p class="mb-0 text-muted">Sin Revisar</p>';
            echo '</div></div>';
            echo '</div>';
        }
        
        echo '</div></div>';

        // 6. Muestra de documentos recientes
        echo '<div class="card diagnostic-card">';
        echo '<div class="card-header bg-dark text-white">';
        echo '<h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Últimos 10 Documentos</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        
        $sql = "SELECT d.id, d.titulo, ds.mes, ds.ano, i.nombre as item_nombre, 
                       d.estado, d.fecha_subida, rd.estado as estado_revision
                FROM documentos d
                INNER JOIN documento_seguimiento ds ON d.id = ds.documento_id
                INNER JOIN items_transparencia i ON d.item_id = i.id
                LEFT JOIN revisiones_documentos rd ON d.id = rd.documento_id
                ORDER BY d.fecha_subida DESC
                LIMIT 10";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            echo '<div class="table-responsive">';
            echo '<table class="table table-sm table-striped">';
            echo '<thead><tr><th>ID</th><th>Título</th><th>Item</th><th>Período</th><th>Estado Doc</th><th>Estado Revisión</th><th>Fecha</th></tr></thead>';
            echo '<tbody>';
            while ($row = $result->fetch_assoc()) {
                $estadoRevision = $row['estado_revision'] 
                    ? '<span class="badge bg-' . ($row['estado_revision'] == 'aprobado' ? 'success' : 'warning') . '">' 
                      . htmlspecialchars($row['estado_revision']) . '</span>'
                    : '<span class="badge bg-secondary">sin revisar</span>';
                
                echo '<tr>';
                echo '<td>' . $row['id'] . '</td>';
                echo '<td><small>' . htmlspecialchars(substr($row['titulo'], 0, 40)) . '...</small></td>';
                echo '<td><small>' . htmlspecialchars(substr($row['item_nombre'], 0, 30)) . '...</small></td>';
                echo '<td>' . $meses[$row['mes']] . ' ' . $row['ano'] . '</td>';
                echo '<td>' . htmlspecialchars($row['estado']) . '</td>';
                echo '<td>' . $estadoRevision . '</td>';
                echo '<td><small>' . date('d/m/Y', strtotime($row['fecha_subida'])) . '</small></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
        } else {
            echo '<p class="text-warning"><i class="bi bi-exclamation-triangle"></i> No hay documentos recientes.</p>';
        }
        
        echo '</div></div>';

        // 7. Recomendaciones
        echo '<div class="card diagnostic-card border-primary">';
        echo '<div class="card-header bg-primary text-white">';
        echo '<h5 class="mb-0"><i class="bi bi-lightbulb"></i> Recomendaciones</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        
        // Verificar configuración
        $sql = "SELECT valor FROM configuracion WHERE clave = 'activar_revision_previa'";
        $result = $conn->query($sql);
        $revision_activada = false;
        if ($result && $result->num_rows > 0) {
            $revision_activada = $result->fetch_assoc()['valor'] == '1';
        }
        
        if (!$revision_activada) {
            echo '<div class="alert alert-warning">';
            echo '<strong><i class="bi bi-exclamation-triangle"></i> Revisión Desactivada:</strong> ';
            echo 'Active la revisión previa desde Configuración del Sistema > General.';
            echo '</div>';
        }
        
        // Verificar si hay documentos
        $sql = "SELECT COUNT(*) as total FROM documentos";
        $result = $conn->query($sql);
        $total_docs = $result ? $result->fetch_assoc()['total'] : 0;
        
        if ($total_docs == 0) {
            echo '<div class="alert alert-info">';
            echo '<strong><i class="bi bi-info-circle"></i> Sin Documentos:</strong> ';
            echo 'No hay documentos cargados en el sistema. Use el perfil de Cargador de Información para subir documentos.';
            echo '</div>';
        }
        
        // Verificar año actual
        $sql = "SELECT COUNT(*) as total FROM documento_seguimiento WHERE ano = ?";
        $stmt = $conn->prepare($sql);
        $anoActual = date('Y');
        $stmt->bind_param("i", $anoActual);
        $stmt->execute();
        $result = $stmt->get_result();
        $docs_ano_actual = $result ? $result->fetch_assoc()['total'] : 0;
        
        if ($total_docs > 0 && $docs_ano_actual == 0) {
            echo '<div class="alert alert-info">';
            echo '<strong><i class="bi bi-info-circle"></i> Sin Documentos en ' . $anoActual . ':</strong> ';
            echo 'Hay documentos en el sistema pero ninguno para el año actual. En el dashboard del revisor, ';
            echo 'seleccione un año anterior (como ' . ($anoActual - 1) . ') para ver documentos.';
            echo '</div>';
        }
        
        echo '<div class="alert alert-success">';
        echo '<strong><i class="bi bi-check-circle"></i> Todo bien:</strong> ';
        echo 'Si ve documentos en la tabla de arriba y la revisión está activada, el sistema debería funcionar correctamente.';
        echo '</div>';
        
        echo '</div></div>';
        
        $conn->close();
        ?>

        <div class="text-center mt-4">
            <a href="usuario/dashboard_revisor.php" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Volver al Dashboard del Revisor
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-house"></i> Ir al Inicio
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
