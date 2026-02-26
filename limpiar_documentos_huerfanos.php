<?php
/**
 * LIMPIAR DOCUMENTOS HUÉRFANOS
 * Elimina documentos de la BD que no tienen archivo físico
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=UTF-8');

$db_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($db_conn->connect_error) {
    die("Error de conexión: " . $db_conn->connect_error);
}

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Limpiar Documentos Huérfanos</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #dc3545; color: white; }
        .btn { display: inline-block; padding: 10px 20px; margin: 10px 5px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; }
        .btn-secondary { background: #6c757d; }
    </style>
</head>
<body>";

echo "<h1>🗑️ LIMPIAR DOCUMENTOS HUÉRFANOS</h1>";

$accion = $_GET['accion'] ?? 'mostrar';

// Buscar documentos sin archivo físico
$sql = "SELECT 
            d.id,
            d.titulo,
            d.archivo,
            d.item_id,
            d.fecha_subida,
            i.numeracion,
            i.nombre as item_nombre
        FROM documentos d
        LEFT JOIN items_transparencia i ON d.item_id = i.id
        ORDER BY d.id";

$result = $db_conn->query($sql);

$uploadsDir = __DIR__ . '/uploads/';
$documentosHuerfanos = [];

while ($row = $result->fetch_assoc()) {
    $archivoPath = $uploadsDir . $row['archivo'];
    if (!file_exists($archivoPath)) {
        $documentosHuerfanos[] = $row;
    }
}

if ($accion === 'mostrar') {
    echo "<div class='section'>";
    echo "<h2>Documentos Sin Archivo Físico</h2>";
    
    if (count($documentosHuerfanos) === 0) {
        echo "<p class='ok'>✅ No hay documentos huérfanos</p>";
    } else {
        echo "<p class='error'>❌ Encontrados <strong>" . count($documentosHuerfanos) . "</strong> documentos sin archivo físico</p>";
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Título</th><th>Item</th><th>Archivo (BD)</th><th>Fecha Subida</th></tr>";
        
        foreach ($documentosHuerfanos as $doc) {
            echo "<tr>";
            echo "<td>" . $doc['id'] . "</td>";
            echo "<td>" . htmlspecialchars($doc['titulo']) . "</td>";
            echo "<td>" . htmlspecialchars($doc['numeracion']) . "</td>";
            echo "<td><code>" . htmlspecialchars($doc['archivo']) . "</code></td>";
            echo "<td>" . $doc['fecha_subida'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;'>";
        echo "<h3>⚠️ ADVERTENCIA</h3>";
        echo "<p>Esta acción eliminará permanentemente:</p>";
        echo "<ul>";
        echo "<li><strong>" . count($documentosHuerfanos) . "</strong> registros de la tabla <code>documentos</code></li>";
        echo "<li>Los registros relacionados en <code>documento_seguimiento</code></li>";
        echo "<li>Los registros relacionados en <code>verificadores_publicador</code> (si existen)</li>";
        echo "<li>Los registros relacionados en <code>historial</code> (si existen)</li>";
        echo "</ul>";
        echo "<p><strong>Esta acción NO se puede deshacer.</strong></p>";
        echo "</div>";
        
        echo "<form method='GET' onsubmit=\"return confirm('¿Estás SEGURO de eliminar " . count($documentosHuerfanos) . " documentos? Esta acción NO se puede deshacer.')\">";
        echo "<input type='hidden' name='accion' value='eliminar'>";
        echo "<button type='submit' class='btn'>🗑️ Eliminar Documentos Huérfanos</button>";
        echo "<a href='diagnostico_archivos.php' class='btn btn-secondary'>← Volver al Diagnóstico</a>";
        echo "</form>";
    }
    
    echo "</div>";
    
} elseif ($accion === 'eliminar') {
    echo "<div class='section'>";
    echo "<h2>Eliminando Documentos Huérfanos...</h2>";
    
    $eliminados = 0;
    $errores = 0;
    
    $db_conn->begin_transaction();
    
    try {
        foreach ($documentosHuerfanos as $doc) {
            $doc_id = $doc['id'];
            
            // Eliminar de historial
            $sql_hist = "DELETE FROM historial WHERE documento_id = ?";
            $stmt = $db_conn->prepare($sql_hist);
            $stmt->bind_param("i", $doc_id);
            $stmt->execute();
            
            // Eliminar verificadores
            $sql_verif = "DELETE FROM verificadores_publicador WHERE documento_id = ?";
            $stmt = $db_conn->prepare($sql_verif);
            $stmt->bind_param("i", $doc_id);
            $stmt->execute();
            
            // Eliminar seguimiento
            $sql_seg = "DELETE FROM documento_seguimiento WHERE documento_id = ?";
            $stmt = $db_conn->prepare($sql_seg);
            $stmt->bind_param("i", $doc_id);
            $stmt->execute();
            
            // Eliminar documento
            $sql_doc = "DELETE FROM documentos WHERE id = ?";
            $stmt = $db_conn->prepare($sql_doc);
            $stmt->bind_param("i", $doc_id);
            
            if ($stmt->execute()) {
                echo "<p class='ok'>✅ Eliminado Doc #$doc_id: " . htmlspecialchars($doc['titulo']) . "</p>";
                $eliminados++;
            } else {
                echo "<p class='error'>❌ Error al eliminar Doc #$doc_id: " . $stmt->error . "</p>";
                $errores++;
            }
        }
        
        $db_conn->commit();
        
        echo "<hr>";
        echo "<h3>Resumen</h3>";
        echo "<p class='ok'>✅ Eliminados exitosamente: <strong>$eliminados</strong></p>";
        if ($errores > 0) {
            echo "<p class='error'>❌ Errores: <strong>$errores</strong></p>";
        }
        
        echo "<p><a href='diagnostico_archivos.php' class='btn btn-secondary'>← Volver al Diagnóstico</a></p>";
        
    } catch (Exception $e) {
        $db_conn->rollback();
        echo "<p class='error'>❌ Error en transacción: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
}

echo "</body></html>";

$db_conn->close();
?>
