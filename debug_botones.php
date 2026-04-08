<?php
/**
 * Debug: Muestra HTML generado para los botones
 */
require_once 'config/config.php';
require_once 'classes/Documento.php';
require_once 'classes/Verificador.php';
require_once 'classes/Item.php';
require_once 'classes/Usuario.php';

$conn = $db->getConnection();
$documentoClass = new Documento($conn);
$verificadorClass = new Verificador($conn);
$itemClass = new Item($conn);
$usuarioClass = new Usuario($conn);

// Parámetros de prueba (igual que en index.php)
$mesSeleccionado = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$anoSeleccionado = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');

echo "<h1>🔍 Debug: Botones de Publicador</h1>";
echo "<p><strong>Período:</strong> Mes {$mesSeleccionado} / Año {$anoSeleccionado}</p>";
echo "<hr>";

// Obtener items
$itemsResult = $itemClass->getAll(null);
$items = [];
while ($item = $itemsResult->fetch_assoc()) {
    $items[] = $item;
}

echo "<h2>Total items: " . count($items) . "</h2>";
echo "<hr>";

$contadorConVerif = 0;
$contadorSinVerif = 0;

foreach ($items as $item) {
    // Buscar documento para este item en el mes/año seleccionado
    if ($item['periodicidad'] === 'anual') {
        $mesParaKey = intval($item['mes_carga_anual'] ?? 1);
        $docsResult = $documentoClass->getByItemFollowUpCargados($item['id'], $mesParaKey, $anoSeleccionado);
    } else {
        $docsResult = $documentoClass->getByItemFollowUpCargados($item['id'], $mesSeleccionado, $anoSeleccionado);
    }
    
    if ($docsResult && $docsResult->num_rows > 0) {
        $documento = $docsResult->fetch_assoc();
        $verificador = $verificadorClass->getByDocumento($documento['documento_id']);
        
        if ($verificador) {
            $contadorConVerif++;
            
            echo "<div style='border: 2px solid green; padding: 15px; margin-bottom: 15px; background: #f0fff0;'>";
            echo "<h3 style='color: green;'>✅ Item con verificador (DEBE MOSTRAR BOTÓN ELIMINAR)</h3>";
            echo "<p><strong>Item:</strong> {$item['numeracion']} - {$item['nombre']}</p>";
            echo "<p><strong>Documento ID:</strong> {$documento['documento_id']}</p>";
            echo "<p><strong>Verificador ID:</strong> {$verificador['id']}</p>";
            echo "<p><strong>Estado:</strong> {$documento['estado']}</p>";
            
            // Generar el HTML EXACTO que se genera en index.php
            $itemNombreJs = json_encode($item['nombre'], JSON_HEX_APOS | JSON_HEX_QUOT);
            $botonesHTML = '<a href="#" class="btn btn-sm btn-info" data-bs-toggle="modal" 
                           data-bs-target="#modalVerDocumento" 
                           onclick="verDocumento(' . $documento['documento_id'] . ', \'' . htmlspecialchars($item['nombre']) . '\', \'' . htmlspecialchars($documento['titulo']) . '\');">
                            <i class="bi bi-eye"></i> Ver Doc
                        </a>
                        <a href="#" class="btn btn-sm btn-success" data-bs-toggle="modal" 
                           data-bs-target="#modalVerVerificador"
                           onclick="verVerificador(' . $verificador['id'] . ', \'' . htmlspecialchars($verificador['archivo_verificador']) . '\');">
                            <i class="bi bi-file-check"></i> Ver Verif
                        </a>
                        <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                           data-bs-target="#modalEliminarVerificador"
                           onclick="eliminarVerificador(' . $verificador['id'] . ', ' . $itemNombreJs . ');">
                            <i class="bi bi-trash"></i> Eliminar
                        </a>';
            
            echo "<h4>HTML Generado (esto debería aparecer en su tabla):</h4>";
            echo "<div style='background: #f8f9fa; padding: 10px; border: 1px solid #ddd; margin: 10px 0;'>";
            echo "<code>" . htmlspecialchars($botonesHTML) . "</code>";
            echo "</div>";
            
            echo "<h4>Vista previa (como se ve renderizado):</h4>";
            echo "<div style='padding: 10px; background: white; border: 1px solid #ddd;'>";
            echo $botonesHTML;
            echo "</div>";
            echo "</div>";
        } else {
            $contadorSinVerif++;
        }
    }
}

echo "<hr>";
echo "<h2>📊 Resumen:</h2>";
echo "<ul>";
echo "<li>✅ Items CON verificador (deben mostrar botón ELIMINAR): <strong style='color: green; font-size: 20px;'>{$contadorConVerif}</strong></li>";
echo "<li>⚠️ Items SIN verificador: <strong>{$contadorSinVerif}</strong></li>";
echo "</ul>";

if ($contadorConVerif === 0) {
    echo "<div style='background: #fff3cd; border: 2px solid #ffc107; padding: 20px; margin: 20px 0;'>";
    echo "<h3>⚠️ NO HAY DOCUMENTOS CON VERIFICADORES</h3>";
    echo "<p>Por eso no ve el botón 'Eliminar'. Para probarlo:</p>";
    echo "<ol>";
    echo "<li>Vaya a <code>admin/publicador/</code></li>";
    echo "<li>Busque un documento en estado 'Cargado' (sin verificador)</li>";
    echo "<li>Haga clic en 'Agregar Verificador'</li>";
    echo "<li>Cargue una imagen</li>";
    echo "<li>El documento pasará a 'Publicado'</li>";
    echo "<li><strong>Recargue la página (F5)</strong></li>";
    echo "<li>Ahora debería ver el botón rojo 'Eliminar'</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<form method='GET' style='background: #e9ecef; padding: 15px; border-radius: 5px;'>";
echo "<strong>Cambiar período de búsqueda:</strong><br><br>";
echo "Mes: <select name='mes'>";
for ($m = 1; $m <= 12; $m++) {
    $selected = ($m == $mesSeleccionado) ? 'selected' : '';
    echo "<option value='{$m}' {$selected}>{$m}</option>";
}
echo "</select> ";
echo "Año: <select name='ano'>";
for ($a = 2024; $a <= 2026; $a++) {
    $selected = ($a == $anoSeleccionado) ? 'selected' : '';
    echo "<option value='{$a}' {$selected}>{$a}</option>";
}
echo "</select> ";
echo "<button type='submit'>Buscar</button>";
echo "</form>";

echo "<hr>";
echo "<p><a href='admin/publicador/index.php?mes={$mesSeleccionado}&ano={$anoSeleccionado}'>← Ir al Panel de Publicador (mismo período)</a></p>";
?>
