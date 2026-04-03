<?php
// Mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Diagnóstico: Sin Movimiento - Publicador</h3>";
echo "<hr>";

// Cargar config.php primero (tiene las credenciales de BD)
echo "Cargando config/config.php...<br>";
if (!file_exists('config/config.php')) {
    die("ERROR: No existe el archivo config/config.php<br>");
}
require_once 'config/config.php';
echo "✓ config.php cargado<br>";

// Intentar cargar Database.php
echo "Cargando config/Database.php...<br>";
if (!file_exists('config/Database.php')) {
    die("ERROR: No existe el archivo config/Database.php<br>");
}

require_once 'config/Database.php';
echo "✓ Database.php cargado<br>";

// Crear instancia de Database
try {
    $database = new Database();
    $conn = $database->getConnection();
    echo "✓ Conexión a BD establecida<br>";
} catch (Exception $e) {
    die("ERROR al conectar BD: " . $e->getMessage() . "<br>");
}

echo "<hr>";

// Buscar el ítem "Nómina Beneficiarios - Subsidio agua potable y alcantarillado - Urbano"
$itemNombre = 'Nómina Beneficiarios - Subsidio agua potable y alcantarillado - Urbano';
$mes = 3; // Marzo
$ano = 2026;

// 1. Buscar el item
$stmtItem = $conn->prepare("SELECT id, nombre, periodicidad FROM items WHERE nombre LIKE ?");
$busqueda = "%$itemNombre%";
$stmtItem->bind_param("s", $busqueda);
$stmtItem->execute();
$resultItem = $stmtItem->get_result();

if ($resultItem->num_rows > 0) {
    $item = $resultItem->fetch_assoc();
    echo "<strong>✓ Item encontrado:</strong><br>";
    echo "ID: " . $item['id'] . "<br>";
    echo "Nombre: " . htmlspecialchars($item['nombre']) . "<br>";
    echo "Periodicidad: " . $item['periodicidad'] . "<br>";
    echo "<hr>";
    
    $itemId = $item['id'];
    $periodicidad = $item['periodicidad'];
    
    // Si es anual, usar mes 1
    $mesParaConsulta = ($periodicidad === 'anual') ? 1 : $mes;
    
    echo "<strong>Parámetros de búsqueda:</strong><br>";
    echo "Mes a consultar: $mesParaConsulta (";
    echo ($periodicidad === 'anual') ? "anual usa mes 1" : "mensual usa mes seleccionado";
    echo ")<br>";
    echo "Año: $ano<br>";
    echo "Clave cache: {$itemId}_{$mesParaConsulta}_{$ano}<br>";
    echo "<hr>";
    
    // 2. Verificar si existe en observaciones_sin_movimiento
    $stmtSinMov = $conn->prepare("SELECT * FROM observaciones_sin_movimiento WHERE item_id = ? AND mes = ? AND ano = ? ORDER BY fecha_creacion DESC LIMIT 1");
    $stmtSinMov->bind_param("iii", $itemId, $mesParaConsulta, $ano);
    $stmtSinMov->execute();
    $resultSinMov = $stmtSinMov->get_result();
    
    if ($resultSinMov->num_rows > 0) {
        $sinMov = $resultSinMov->fetch_assoc();
        echo "<strong style='color: green;'>✓ REGISTRO SIN MOVIMIENTO ENCONTRADO:</strong><br>";
        echo "<pre>";
        print_r($sinMov);
        echo "</pre>";
        echo "<hr>";
        echo "<strong>Esto debería mostrarse en el publicador como:</strong><br>";
        echo "Estado: <span style='background: #198754; color: white; padding: 3px 8px; border-radius: 4px;'>Sin Movimiento</span><br>";
        echo "Fecha: " . date('d/m/Y H:i', strtotime($sinMov['fecha_creacion'])) . "<br>";
        echo "Botón: Ver Observación<br>";
    } else {
        echo "<strong style='color: red;'>✗ NO HAY REGISTRO EN observaciones_sin_movimiento</strong><br>";
        echo "Por eso aparece como 'Sin envío' y 'Pendiente'<br>";
        echo "<hr>";
        echo "<strong>Solución:</strong> El cargador debe declarar 'Sin Movimiento' desde su panel para este mes/año.<br>";
    }
    $stmtSinMov->close();
    
    // 3. Verificar si tiene documento cargado
    echo "<hr>";
    $stmtDoc = $conn->prepare("SELECT d.*, u.nombre as usuario_nombre 
                               FROM documentos d 
                               LEFT JOIN usuarios u ON d.usuario_id = u.id 
                               WHERE d.item_id = ? AND d.mes = ? AND d.ano = ? 
                               ORDER BY d.fecha_subida DESC LIMIT 1");
    $stmtDoc->bind_param("iii", $itemId, $mesParaConsulta, $ano);
    $stmtDoc->execute();
    $resultDoc = $stmtDoc->get_result();
    
    if ($resultDoc->num_rows > 0) {
        $doc = $resultDoc->fetch_assoc();
        echo "<strong style='color: blue;'>ℹ Tiene documento cargado:</strong><br>";
        echo "ID: " . $doc['id'] . "<br>";
        echo "Título: " . htmlspecialchars($doc['titulo']) . "<br>";
        echo "Usuario: " . htmlspecialchars($doc['usuario_nombre']) . "<br>";
        echo "Fecha subida: " . $doc['fecha_subida'] . "<br>";
        echo "<em>Nota: Cuando hay documento, no se muestra el Sin Movimiento (tiene prioridad el documento)</em><br>";
    } else {
        echo "<strong>No tiene documento cargado</strong><br>";
    }
    $stmtDoc->close();
    
} else {
    echo "<strong style='color: red;'>✗ Item NO encontrado</strong><br>";
    echo "Busqué: '$itemNombre'<br>";
}

$stmtItem->close();
$conn->close();
?>
