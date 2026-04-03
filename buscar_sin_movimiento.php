<?php
/**
 * Buscar items con Sin Movimiento que tienen verificador
 */

require_once __DIR__ . '/config/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : 3;
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : 2026;

$isWeb = php_sapi_name() !== 'cli';
if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<pre style="background:#f4f4f4; padding:20px; font-family:monospace;">';
}

echo "=== ITEMS CON SIN MOVIMIENTO EN $mes/$ano ===\n\n";

// Buscar todos los Sin Movimiento del período
$query = "SELECT osm.*, i.nombre as item_nombre, i.numeracion, u.nombre as usuario_nombre
    FROM observaciones_sin_movimiento osm
    JOIN items_transparencia i ON osm.item_id = i.id
    JOIN usuarios u ON osm.usuario_id = u.id
    WHERE osm.mes = ? AND osm.ano = ?
    ORDER BY u.nombre, i.numeracion";

$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $mes, $ano);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "❌ No hay items con Sin Movimiento en este período\n";
    exit;
}

echo "Total encontrados: " . $result->num_rows . "\n\n";

while ($row = $result->fetch_assoc()) {
    echo "----------------------------------------\n";
    echo "Item ID: " . $row['item_id'] . "\n";
    echo "Usuario: " . $row['usuario_nombre'] . " (ID: " . $row['usuario_id'] . ")\n";
    echo "Item: " . ($row['numeracion'] ?? '') . " - " . $row['item_nombre'] . "\n";
    echo "Observación: " . $row['observacion'] . "\n";
    echo "Fecha: " . $row['fecha_creacion'] . "\n";
    
    // Buscar documento placeholder
    $itemId = $row['item_id'];
    $stmtDoc = $conn->prepare("SELECT id, titulo, fecha_subida 
        FROM documentos 
        WHERE item_id = ? AND mes_carga = ? AND ano_carga = ? 
        AND titulo LIKE 'Sin Movimiento%'
        LIMIT 1");
    $stmtDoc->bind_param('iii', $itemId, $mes, $ano);
    $stmtDoc->execute();
    $docResult = $stmtDoc->get_result();
    
    if ($doc = $docResult->fetch_assoc()) {
        echo "📄 Placeholder: ID " . $doc['id'] . " - '" . $doc['titulo'] . "'\n";
        echo "   Fecha: " . $doc['fecha_subida'] . "\n";
        
        // Buscar verificador
        $docId = $doc['id'];
        $stmtVerif = $conn->prepare("SELECT * FROM verificadores_publicador WHERE documento_id = ?");
        $stmtVerif->bind_param('i', $docId);
        $stmtVerif->execute();
        $verifResult = $stmtVerif->get_result();
        
        if ($verif = $verifResult->fetch_assoc()) {
            echo "✅ CON VERIFICADOR (debería contar como PUBLICADO)\n";
            echo "   Fecha publicación: " . $verif['fecha_carga_portal'] . "\n";
            echo "   👉 Úsalo así: ?item_id=" . $itemId . "&mes=$mes&ano=$ano\n";
        } else {
            echo "⚠️  SIN VERIFICADOR (debería contar como CARGADO pendiente)\n";
        }
        $stmtVerif->close();
    } else {
        echo "❌ NO tiene documento placeholder creado\n";
    }
    $stmtDoc->close();
    echo "\n";
}

$stmt->close();
$conn->close();

echo "=== FIN ===\n";

if ($isWeb) {
    echo '</pre>';
}
