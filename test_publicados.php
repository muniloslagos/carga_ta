<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Test Publicados</title>";
echo "<style>body{font-family:Arial;padding:20px} table{border-collapse:collapse;width:100%;margin:20px 0} th,td{border:1px solid #ddd;padding:8px;text-align:left} th{background:#4CAF50;color:white} .ok{background:#d4edda} .error{background:#f8d7da}</style>";
echo "</head><body>";

echo "<h1>🔍 Diagnóstico Pestaña Publicados</h1>";

// 1. Ver todos los verificadores
echo "<h2>1. Verificadores en la base de datos</h2>";
$sql1 = "SELECT v.*, d.titulo, i.nombre as item_nombre 
         FROM verificadores_publicador v 
         LEFT JOIN documentos d ON v.documento_id = d.id
         LEFT JOIN items_transparencia i ON d.item_id = i.id
         ORDER BY v.fecha_carga_portal DESC";
$result1 = $conn->query($sql1);

if ($result1->num_rows === 0) {
    echo "<div class='error'><strong>❌ No hay ningún verificador en la tabla verificadores_publicador</strong></div>";
} else {
    echo "<div class='ok'><strong>✅ Total verificadores: " . $result1->num_rows . "</strong></div>";
    echo "<table>";
    echo "<tr><th>ID Verif</th><th>Doc ID</th><th>Item</th><th>Título Doc</th><th>Fecha Carga</th><th>Archivo</th></tr>";
    while ($row = $result1->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['documento_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['item_nombre'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['titulo'] ?? 'N/A') . "</td>";
        echo "<td>" . $row['fecha_carga_portal'] . "</td>";
        echo "<td>" . htmlspecialchars($row['archivo_verificador']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 2. Ejecutar la misma query que usa index.php
echo "<h2>2. Query de la pestaña Publicados (mismo SQL que index.php)</h2>";
$queryPublicados = "
    SELECT 
        i.id as item_id,
        i.numeracion,
        i.nombre as item_nombre,
        i.periodicidad,
        d.id as doc_id,
        d.titulo,
        d.estado,
        ds.mes,
        ds.ano,
        ds.fecha_envio,
        u.nombre as usuario_nombre,
        vp.id as verificador_id,
        vp.fecha_carga_portal
    FROM items_transparencia i
    JOIN documentos d ON i.id = d.item_id 
    JOIN documento_seguimiento ds ON d.id = ds.documento_id
    JOIN verificadores_publicador vp ON d.id = vp.documento_id
    LEFT JOIN usuarios u ON d.usuario_id = u.id
    WHERE i.activo = 1
        AND d.estado IN ('pendiente', 'aprobado')
    ORDER BY vp.fecha_carga_portal DESC";

echo "<pre style='background:#f5f5f5;padding:10px;overflow:auto'>" . htmlspecialchars($queryPublicados) . "</pre>";

$result2 = $conn->query($queryPublicados);

if ($result2->num_rows === 0) {
    echo "<div class='error'><strong>❌ La query NO devuelve ningún resultado</strong></div>";
    
    // Diagnóstico detallado
    echo "<h3>Diagnóstico paso a paso:</h3>";
    
    // Paso A: items activos
    $sqlA = "SELECT COUNT(*) as total FROM items_transparencia WHERE activo = 1";
    $resA = $conn->query($sqlA);
    $rowA = $resA->fetch_assoc();
    echo "<p>A. Items activos: <strong>" . $rowA['total'] . "</strong></p>";
    
    // Paso B: documentos
    $sqlB = "SELECT COUNT(*) as total FROM documentos WHERE estado IN ('pendiente', 'aprobado')";
    $resB = $conn->query($sqlB);
    $rowB = $resB->fetch_assoc();
    echo "<p>B. Documentos pendientes/aprobados: <strong>" . $rowB['total'] . "</strong></p>";
    
    // Paso C: con seguimiento
    $sqlC = "SELECT COUNT(*) as total FROM documentos d 
             JOIN documento_seguimiento ds ON d.id = ds.documento_id 
             WHERE d.estado IN ('pendiente', 'aprobado')";
    $resC = $conn->query($sqlC);
    $rowC = $resC->fetch_assoc();
    echo "<p>C. Documentos con seguimiento: <strong>" . $rowC['total'] . "</strong></p>";
    
    // Paso D: con verificador
    $sqlD = "SELECT COUNT(*) as total FROM documentos d 
             JOIN verificadores_publicador vp ON d.id = vp.documento_id 
             WHERE d.estado IN ('pendiente', 'aprobado')";
    $resD = $conn->query($sqlD);
    $rowD = $resD->fetch_assoc();
    echo "<p>D. Documentos con verificador: <strong>" . $rowD['total'] . "</strong></p>";
    
    // Paso E: JOIN completo
    $sqlE = "SELECT COUNT(*) as total 
             FROM items_transparencia i
             JOIN documentos d ON i.id = d.item_id 
             JOIN documento_seguimiento ds ON d.id = ds.documento_id
             JOIN verificadores_publicador vp ON d.id = vp.documento_id
             WHERE i.activo = 1 AND d.estado IN ('pendiente', 'aprobado')";
    $resE = $conn->query($sqlE);
    $rowE = $resE->fetch_assoc();
    echo "<p>E. JOIN completo: <strong>" . $rowE['total'] . "</strong></p>";
    
    // Ver documentos con verificador pero que no pasan el filtro
    echo "<h3>Documentos con verificador que NO cumplen los criterios:</h3>";
    $sqlF = "SELECT d.id, d.titulo, d.estado, i.activo, i.nombre as item_nombre,
             vp.id as verif_id
             FROM documentos d
             JOIN verificadores_publicador vp ON d.id = vp.documento_id
             LEFT JOIN items_transparencia i ON d.item_id = i.id
             WHERE d.estado NOT IN ('pendiente', 'aprobado') OR i.activo = 0 OR i.id IS NULL";
    $resF = $conn->query($sqlF);
    if ($resF->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>Doc ID</th><th>Título</th><th>Estado</th><th>Item</th><th>Item Activo</th><th>Verif ID</th></tr>";
        while ($rowF = $resF->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $rowF['id'] . "</td>";
            echo "<td>" . htmlspecialchars($rowF['titulo']) . "</td>";
            echo "<td>" . $rowF['estado'] . "</td>";
            echo "<td>" . htmlspecialchars($rowF['item_nombre'] ?? 'NULL') . "</td>";
            echo "<td>" . ($rowF['activo'] ?? 'NULL') . "</td>";
            echo "<td>" . $rowF['verif_id'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Ninguno (todos los docs con verificador deberían aparecer)</p>";
    }
    
} else {
    echo "<div class='ok'><strong>✅ La query devuelve " . $result2->num_rows . " resultados</strong></div>";
    echo "<table>";
    echo "<tr><th>Item ID</th><th>Núm</th><th>Item</th><th>Doc ID</th><th>Estado</th><th>Mes/Año</th><th>Verif ID</th><th>Fecha Public</th></tr>";
    while ($row = $result2->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['item_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['numeracion']) . "</td>";
        echo "<td>" . htmlspecialchars($row['item_nombre']) . "</td>";
        echo "<td>" . $row['doc_id'] . "</td>";
        echo "<td>" . $row['estado'] . "</td>";
        echo "<td>" . $row['mes'] . "/" . $row['ano'] . "</td>";
        echo "<td>" . $row['verificador_id'] . "</td>";
        echo "<td>" . $row['fecha_carga_portal'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Ver estado de documentos recientes
echo "<h2>3. Últimos 10 documentos (con/sin verificador)</h2>";
$sql3 = "SELECT d.id, d.titulo, d.estado, d.item_id, i.nombre as item_nombre, i.activo,
         ds.mes, ds.ano, ds.fecha_envio,
         vp.id as verif_id, vp.fecha_carga_portal
         FROM documentos d
         LEFT JOIN items_transparencia i ON d.item_id = i.id
         LEFT JOIN documento_seguimiento ds ON d.id = ds.documento_id
         LEFT JOIN verificadores_publicador vp ON d.id = vp.documento_id
         ORDER BY d.id DESC
         LIMIT 10";
$result3 = $conn->query($sql3);

echo "<table>";
echo "<tr><th>Doc ID</th><th>Título</th><th>Estado</th><th>Item</th><th>Item Activo</th><th>Mes/Año</th><th>Verif ID</th><th>Fecha Verif</th></tr>";
while ($row = $result3->fetch_assoc()) {
    $class = $row['verif_id'] ? 'ok' : '';
    echo "<tr class='$class'>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['titulo']) . "</td>";
    echo "<td>" . $row['estado'] . "</td>";
    echo "<td>" . htmlspecialchars($row['item_nombre'] ?? 'N/A') . "</td>";
    echo "<td>" . ($row['activo'] ?? 'N/A') . "</td>";
    echo "<td>" . ($row['mes'] ?? 'N/A') . "/" . ($row['ano'] ?? 'N/A') . "</td>";
    echo "<td>" . ($row['verif_id'] ?? '-') . "</td>";
    echo "<td>" . ($row['fecha_carga_portal'] ?? '-') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h2>Conclusión</h2>";
echo "<ul>";
echo "<li>Si no hay verificadores en la primera tabla → <strong>El problema es que no se están guardando los verificadores</strong></li>";
echo "<li>Si hay verificadores pero la query no devuelve resultados → <strong>El problema es el filtro de estado o items inactivos</strong></li>";
echo "<li>Si la query devuelve resultados aquí pero no en index.php → <strong>El problema es en la renderización del HTML</strong></li>";
echo "</ul>";

echo "</body></html>";
?>
