<?php
/**
 * Diagnóstico de documentos Sin Movimiento para correos
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$conn = getDBConnection();

// Parámetros de prueba
$item_id = 42; // Cambiar por un item con Sin Movimiento
$mes = 3; // Marzo
$ano = 2026;

echo "=== DIAGNÓSTICO SIN MOVIMIENTO PARA CORREOS ===\n\n";
echo "Item ID: $item_id\n";
echo "Período: $mes/$ano\n\n";

// 1. Verificar Sin Movimiento
echo "1. VERIFICAR SIN MOVIMIENTO:\n";
$stmt = $conn->prepare("SELECT * FROM observaciones_sin_movimiento WHERE item_id = ? AND mes = ? AND ano = ?");
$stmt->bind_param('iii', $item_id, $mes, $ano);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo "✅ Existe Sin Movimiento\n";
    echo "   Observación: " . $row['observacion'] . "\n";
    echo "   Fecha: " . $row['fecha_creacion'] . "\n";
} else {
    echo "❌ NO existe Sin Movimiento\n";
}
$stmt->close();

// 2. Buscar documentos con diferentes columnas
echo "\n2. BUSCAR DOCUMENTOS (usando mes_carga/ano_carga):\n";
$stmt = $conn->prepare("SELECT id, titulo, fecha_subida, mes_carga, ano_carga 
    FROM documentos 
    WHERE item_id = ? AND mes_carga = ? AND ano_carga = ?
    ORDER BY fecha_subida DESC");
$stmt->bind_param('iii', $item_id, $mes, $ano);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($doc = $result->fetch_assoc()) {
        echo "   Documento ID: " . $doc['id'] . "\n";
        echo "   Título: " . $doc['titulo'] . "\n";
        echo "   Fecha subida: " . $doc['fecha_subida'] . "\n";
        echo "   Mes/Año: " . $doc['mes_carga'] . "/" . $doc['ano_carga'] . "\n";
        
        // Buscar verificador
        $docId = $doc['id'];
        $stmtV = $conn->prepare("SELECT * FROM verificadores_publicador WHERE documento_id = ?");
        $stmtV->bind_param('i', $docId);
        $stmtV->execute();
        $vResult = $stmtV->get_result();
        if ($verif = $vResult->fetch_assoc()) {
            echo "   ✅ TIENE VERIFICADOR:\n";
            echo "      Fecha carga portal: " . $verif['fecha_carga_portal'] . "\n";
            echo "      Archivo: " . $verif['archivo_verificador'] . "\n";
        } else {
            echo "   ❌ NO tiene verificador\n";
        }
        $stmtV->close();
        echo "\n";
    }
} else {
    echo "❌ No se encontraron documentos con mes_carga/ano_carga\n";
}
$stmt->close();

// 3. Buscar SOLO placeholders
echo "\n3. BUSCAR PLACEHOLDERS (titulo LIKE 'Sin Movimiento%'):\n";
$stmt = $conn->prepare("SELECT id, titulo, fecha_subida, mes_carga, ano_carga 
    FROM documentos 
    WHERE item_id = ? AND mes_carga = ? AND ano_carga = ? 
    AND titulo LIKE 'Sin Movimiento%'
    ORDER BY fecha_subida DESC");
$stmt->bind_param('iii', $item_id, $mes, $ano);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($doc = $result->fetch_assoc()) {
        echo "   ✅ Placeholder encontrado:\n";
        echo "   ID: " . $doc['id'] . "\n";
        echo "   Título: " . $doc['titulo'] . "\n";
        echo "   Fecha: " . $doc['fecha_subida'] . "\n";
        
        // Buscar verificador
        $docId = $doc['id'];
        $stmtV = $conn->prepare("SELECT * FROM verificadores_publicador WHERE documento_id = ?");
        $stmtV->bind_param('i', $docId);
        $stmtV->execute();
        $vResult = $stmtV->get_result();
        if ($verif = $vResult->fetch_assoc()) {
            echo "   ✅ CON VERIFICADOR - debería contar como PUBLICADO\n";
            echo "      Fecha: " . $verif['fecha_carga_portal'] . "\n";
        } else {
            echo "   ❌ SIN VERIFICADOR - debería contar como CARGADO\n";
        }
        $stmtV->close();
        echo "\n";
    }
} else {
    echo "❌ No se encontró placeholder\n";
}
$stmt->close();

echo "\n=== FIN DIAGNÓSTICO ===\n";
