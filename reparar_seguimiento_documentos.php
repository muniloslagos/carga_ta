<?php
/**
 * Script de Reparación: Crear entradas en documento_seguimiento para documentos huérfanos
 * 
 * PROBLEMA: Algunos documentos no tienen entrada en documento_seguimiento
 * CONSECUENCIA: No aparecen en el dashboard del revisor
 * SOLUCIÓN: Crear entradas faltantes basadas en fecha_subida del documento
 * 
 * IMPORTANTE: Este script es idempotente (se puede ejecutar múltiples veces sin duplicar datos)
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
    <title>Reparar Seguimiento de Documentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .pre-check { background-color: #f8f9fa; padding: 15px; border-radius: 5px; }
        .success-item { color: #28a745; }
        .error-item { color: #dc3545; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4"><i class="bi bi-tools"></i> Reparación de Seguimiento de Documentos</h1>
        <p class="text-muted">Ejecutado el: <?php echo date('d/m/Y H:i:s'); ?></p>

        <?php
        $ejecutar = isset($_POST['ejecutar']) && $_POST['ejecutar'] == '1';
        
        // 1. Identificar documentos sin seguimiento
        $sql = "SELECT d.id, d.titulo, d.item_id, d.usuario_id, d.fecha_subida, i.nombre as item_nombre
                FROM documentos d
                LEFT JOIN documento_seguimiento ds ON d.id = ds.documento_id
                INNER JOIN items_transparencia i ON d.item_id = i.id
                WHERE ds.documento_id IS NULL
                ORDER BY d.fecha_subida DESC";
        
        $result = $conn->query($sql);
        $documentos_sin_seguimiento = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $documentos_sin_seguimiento[] = $row;
            }
        }
        
        $total_sin_seguimiento = count($documentos_sin_seguimiento);
        
        // Card de resumen
        echo '<div class="card mb-4">';
        echo '<div class="card-header bg-' . ($total_sin_seguimiento > 0 ? 'warning' : 'success') . ' text-' . ($total_sin_seguimiento > 0 ? 'dark' : 'white') . '">';
        echo '<h5 class="mb-0"><i class="bi bi-clipboard-data"></i> Estado Actual</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        
        if ($total_sin_seguimiento > 0) {
            echo '<div class="alert alert-warning">';
            echo '<h4><i class="bi bi-exclamation-triangle"></i> Se encontraron ' . $total_sin_seguimiento . ' documento(s) sin seguimiento</h4>';
            echo '<p class="mb-0">Estos documentos no aparecen en el dashboard del revisor porque no tienen registro en <code>documento_seguimiento</code>.</p>';
            echo '</div>';
        } else {
            echo '<div class="alert alert-success">';
            echo '<h4><i class="bi bi-check-circle"></i> ¡Todos los documentos tienen seguimiento correcto!</h4>';
            echo '<p class="mb-0">No se encontraron documentos sin seguimiento. El sistema está funcionando correctamente.</p>';
            echo '</div>';
        }
        
        echo '</div></div>';
        
        if ($total_sin_seguimiento > 0) {
            // Mostrar documentos que se van a reparar
            echo '<div class="card mb-4">';
            echo '<div class="card-header bg-info text-white">';
            echo '<h5 class="mb-0"><i class="bi bi-list-ul"></i> Documentos que Serán Reparados</h5>';
            echo '</div>';
            echo '<div class="card-body">';
            echo '<div class="table-responsive">';
            echo '<table class="table table-sm table-striped">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>ID</th>';
            echo '<th>Título</th>';
            echo '<th>Item</th>';
            echo '<th>Fecha Subida</th>';
            echo '<th>Mes/Año Extraído</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($documentos_sin_seguimiento as $doc) {
                $fecha = new DateTime($doc['fecha_subida']);
                $mes = (int)$fecha->format('m');
                $ano = (int)$fecha->format('Y');
                
                $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                
                echo '<tr>';
                echo '<td>' . $doc['id'] . '</td>';
                echo '<td><small>' . htmlspecialchars(substr($doc['titulo'], 0, 50)) . '...</small></td>';
                echo '<td><small>' . htmlspecialchars(substr($doc['item_nombre'], 0, 40)) . '...</small></td>';
                echo '<td>' . date('d/m/Y H:i', strtotime($doc['fecha_subida'])) . '</td>';
                echo '<td><strong>' . $meses[$mes] . ' ' . $ano . '</strong></td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
            echo '</div></div>';
            
            if (!$ejecutar) {
                // Formulario de confirmación
                echo '<div class="card border-danger mb-4">';
                echo '<div class="card-header bg-danger text-white">';
                echo '<h5 class="mb-0"><i class="bi bi-exclamation-octagon"></i> Confirmar Reparación</h5>';
                echo '</div>';
                echo '<div class="card-body">';
                echo '<p><strong>Esta operación creará ' . $total_sin_seguimiento . ' nuevo(s) registro(s) en la tabla <code>documento_seguimiento</code>.</strong></p>';
                echo '<p>Los datos se extraerán de la columna <code>fecha_subida</code> de cada documento:</p>';
                echo '<ul>';
                echo '<li><strong>Mes:</strong> Extraído del mes de <code>fecha_subida</code></li>';
                echo '<li><strong>Año:</strong> Extraído del año de <code>fecha_subida</code></li>';
                echo '<li><strong>Estado:</strong> Se copiará del documento</li>';
                echo '<li><strong>fecha_envio:</strong> Se usará <code>fecha_subida</code></li>';
                echo '</ul>';
                echo '<div class="alert alert-info">';
                echo '<i class="bi bi-info-circle"></i> <strong>Esta operación es segura:</strong>';
                echo '<ul class="mb-0">';
                echo '<li>No modifica documentos existentes</li>';
                echo '<li>Solo crea registros faltantes en documento_seguimiento</li>';
                echo '<li>Es idempotente (se puede ejecutar múltiples veces)</li>';
                echo '<li>Se puede revertir eliminando los registros creados</li>';
                echo '</ul>';
                echo '</div>';
                echo '<form method="POST" onsubmit="return confirm(\'¿Está seguro de que desea reparar ' . $total_sin_seguimiento . ' documento(s)?\');">';
                echo '<input type="hidden" name="ejecutar" value="1">';
                echo '<button type="submit" class="btn btn-danger btn-lg">';
                echo '<i class="bi bi-tools"></i> Ejecutar Reparación Ahora';
                echo '</button>';
                echo ' ';
                echo '<a href="diagnostico_revisor.php" class="btn btn-secondary btn-lg">';
                echo '<i class="bi bi-arrow-left"></i> Cancelar';
                echo '</a>';
                echo '</form>';
                echo '</div></div>';
            } else {
                // Ejecutar reparación
                echo '<div class="card border-success">';
                echo '<div class="card-header bg-success text-white">';
                echo '<h5 class="mb-0"><i class="bi bi-gear"></i> Ejecutando Reparación...</h5>';
                echo '</div>';
                echo '<div class="card-body">';
                
                $conn->begin_transaction();
                $exitosos = 0;
                $errores = 0;
                $detalles = [];
                
                try {
                    $stmt = $conn->prepare("INSERT INTO documento_seguimiento 
                                           (documento_id, item_id, usuario_id, mes, ano, estado, fecha_envio) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?)");
                    
                    foreach ($documentos_sin_seguimiento as $doc) {
                        $fecha = new DateTime($doc['fecha_subida']);
                        $mes = (int)$fecha->format('m');
                        $ano = (int)$fecha->format('Y');
                        
                        // Estado por defecto: 'Pendiente' si no tiene estado
                        $estado = 'Pendiente';
                        
                        $stmt->bind_param("iiiiiss", 
                            $doc['id'], 
                            $doc['item_id'], 
                            $doc['usuario_id'], 
                            $mes, 
                            $ano, 
                            $estado, 
                            $doc['fecha_subida']
                        );
                        
                        if ($stmt->execute()) {
                            $exitosos++;
                            $detalles[] = [
                                'tipo' => 'success',
                                'mensaje' => 'Documento ID ' . $doc['id'] . ' → ' . $mes . '/' . $ano
                            ];
                        } else {
                            $errores++;
                            $detalles[] = [
                                'tipo' => 'error',
                                'mensaje' => 'Error en documento ID ' . $doc['id'] . ': ' . $stmt->error
                            ];
                        }
                    }
                    
                    if ($errores == 0) {
                        $conn->commit();
                        echo '<div class="alert alert-success">';
                        echo '<h4><i class="bi bi-check-circle-fill"></i> ¡Reparación Completada con Éxito!</h4>';
                        echo '<p><strong>' . $exitosos . ' registro(s)</strong> creados correctamente en <code>documento_seguimiento</code>.</p>';
                        echo '</div>';
                    } else {
                        $conn->rollback();
                        echo '<div class="alert alert-danger">';
                        echo '<h4><i class="bi bi-x-circle-fill"></i> Reparación Fallida</h4>';
                        echo '<p>Se encontraron <strong>' . $errores . ' error(es)</strong>. Los cambios fueron revertidos.</p>';
                        echo '</div>';
                    }
                    
                    // Mostrar detalles
                    echo '<h5 class="mt-4">Detalles de la Operación:</h5>';
                    echo '<div class="pre-check">';
                    foreach ($detalles as $detalle) {
                        $icon = $detalle['tipo'] == 'success' ? 'check-circle-fill' : 'x-circle-fill';
                        $class = $detalle['tipo'] == 'success' ? 'success-item' : 'error-item';
                        echo '<div class="' . $class . '">';
                        echo '<i class="bi bi-' . $icon . '"></i> ' . htmlspecialchars($detalle['mensaje']);
                        echo '</div>';
                    }
                    echo '</div>';
                    
                    if ($errores == 0) {
                        echo '<div class="mt-4">';
                        echo '<a href="diagnostico_revisor.php" class="btn btn-primary">';
                        echo '<i class="bi bi-clipboard-check"></i> Ver Diagnóstico Actualizado';
                        echo '</a>';
                        echo ' ';
                        echo '<a href="usuario/dashboard_revisor.php" class="btn btn-success">';
                        echo '<i class="bi bi-arrow-right"></i> Ir al Dashboard del Revisor';
                        echo '</a>';
                        echo '</div>';
                    }
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    echo '<div class="alert alert-danger">';
                    echo '<h4><i class="bi bi-x-circle-fill"></i> Error Fatal</h4>';
                    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '</div>';
                }
                
                echo '</div></div>';
            }
        }
        
        $conn->close();
        ?>

        <?php if ($total_sin_seguimiento == 0 || $ejecutar): ?>
        <div class="text-center mt-4">
            <a href="diagnostico_revisor.php" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Volver al Diagnóstico
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-house"></i> Ir al Inicio
            </a>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
