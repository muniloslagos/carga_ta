<?php
/**
 * Script de diagnóstico: Verificar si el botón "Eliminar Verificador" debería aparecer
 */
require_once 'config/config.php';
require_once 'config/Database.php';
require_once 'classes/Documento.php';
require_once 'classes/Verificador.php';
require_once 'classes/Item.php';

$db = new Database();
$conn = $db->getConnection();
$documentoClass = new Documento($conn);
$verificadorClass = new Verificador($conn);
$itemClass = new Item($conn);

echo "<h1>Diagnóstico: Botón Eliminar Verificador</h1>";
echo "<p>Verificando documentos publicados con verificadores...</p>";
echo "<hr>";

// Obtener mes y año actual
$mesActual = (int)date('m');
$anoActual = (int)date('Y');

// Buscar documentos en estado "Publicado"
$sql = "SELECT d.id as documento_id, d.titulo, d.estado, d.usuario_id, ds.mes, ds.ano, ds.item_id
        FROM documentos d
        INNER JOIN documento_seguimiento ds ON d.id = ds.documento_id
        WHERE d.estado = 'Publicado'
        ORDER BY ds.ano DESC, ds.mes DESC
        LIMIT 20";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<h2>✅ Encontrados " . $result->num_rows . " documentos publicados (máximo 20 para prueba)</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID Doc</th>";
    echo "<th>Item</th>";
    echo "<th>Mes/Año</th>";
    echo "<th>Estado</th>";
    echo "<th>Tiene Verificador?</th>";
    echo "<th>ID Verificador</th>";
    echo "<th>¿Debería ver botón Eliminar?</th>";
    echo "</tr>";
    
    while ($doc = $result->fetch_assoc()) {
        // Obtener item
        $itemResult = $itemClass->getById($doc['item_id']);
        $item = $itemResult ? $itemResult->fetch_assoc() : null;
        $itemNombre = $item ? $item['nombre'] : 'Desconocido';
        
        // Buscar verificador
        $verificador = $verificadorClass->getByDocumento($doc['documento_id']);
        
        $tieneVerif = $verificador ? '✅ SÍ' : '❌ NO';
        $verifId = $verificador ? $verificador['id'] : '-';
        $deberiaVerBoton = $verificador ? '✅ <strong style="color: green;">SÍ DEBERÍA VER EL BOTÓN ROJO "ELIMINAR"</strong>' : '❌ No (aún no tiene verificador)';
        
        echo "<tr>";
        echo "<td>{$doc['documento_id']}</td>";
        echo "<td>" . htmlspecialchars($itemNombre) . "</td>";
        echo "<td>{$doc['mes']}/{$doc['ano']}</td>";
        echo "<td><span style='background: green; color: white; padding: 3px 8px; border-radius: 3px;'>{$doc['estado']}</span></td>";
        echo "<td>{$tieneVerif}</td>";
        echo "<td>{$verifId}</td>";
        echo "<td>{$deberiaVerBoton}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<hr>";
    echo "<h3>🔍 Interpretación:</h3>";
    echo "<ul>";
    echo "<li>Si un documento tiene estado <strong>Publicado</strong> y <strong>tiene verificador</strong>, entonces:</li>";
    echo "<li style='margin-left: 30px;'>✓ En <code>admin/publicador/index.php</code> deberían aparecer 3 botones:</li>";
    echo "<li style='margin-left: 60px;'>1. <span style='background: #17a2b8; color: white; padding: 2px 8px;'>Ver Doc</span> (botón azul)</li>";
    echo "<li style='margin-left: 60px;'>2. <span style='background: #28a745; color: white; padding: 2px 8px;'>Ver Verif</span> (botón verde)</li>";
    echo "<li style='margin-left: 60px;'>3. <span style='background: #dc3545; color: white; padding: 2px 8px;'>Eliminar</span> (botón rojo) ← <strong>ESTE ES EL NUEVO</strong></li>";
    echo "</ul>";
    echo "<hr>";
    echo "<h3>⚠️ Si NO ve el botón rojo 'Eliminar':</h3>";
    echo "<ol>";
    echo "<li><strong>Limpie caché del navegador:</strong> Presione <kbd>Ctrl + F5</kbd> en la página del publicador</li>";
    echo "<li><strong>Verifique sesión:</strong> Asegúrese de estar logueado como <strong>publicador</strong> o <strong>administrativo</strong></li>";
    echo "<li><strong>Revise consola JavaScript:</strong> Presione <kbd>F12</kbd> y busque errores en la pestaña 'Console'</li>";
    echo "<li><strong>Verifique actualización de archivos:</strong> El commit más reciente es <code>238649b</code></li>";
    echo "</ol>";
    
} else {
    echo "<h2>⚠️ No se encontraron documentos en estado 'Publicado'</h2>";
    echo "<p>Para probar el botón 'Eliminar', primero debe:</p>";
    echo "<ol>";
    echo "<li>Cargar un documento como usuario (perfil cargador_informacion)</li>";
    echo "<li>Como publicador, cargar un verificador para ese documento</li>";
    echo "<li>El documento pasará a estado 'Publicado'</li>";
    echo "<li>Entonces debería ver el botón rojo 'Eliminar' junto al botón verde 'Ver Verif'</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<h3>📋 Estado de archivos modificados:</h3>";
echo "<ul>";
echo "<li>✓ <code>classes/Verificador.php</code> - Método delete() actualizado</li>";
echo "<li>✓ <code>admin/publicador/eliminar_verificador.php</code> - Endpoint creado</li>";
echo "<li>✓ <code>admin/publicador/index.php</code> - Botón y modal agregados (commit 238649b)</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='admin/publicador/index.php'>← Volver al Panel de Publicador</a></p>";
?>
