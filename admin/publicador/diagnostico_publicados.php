<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';

header('Content-Type: text/html; charset=UTF-8');

$db_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Diagnóstico Publicados</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; background: white; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        .ok { color: green; }
        .error { color: red; }
    </style>
</head>
<body>";

echo "<h1>Diagnóstico: Documentos con Verificador</h1>";

// Verificar todos los verificadores
$sql = "SELECT 
            vp.id as verif_id,
            vp.documento_id,
            vp.fecha_carga_portal,
            d.titulo as doc_titulo,
            i.numeracion,
            i.nombre as item_nombre,
            i.periodicidad,
            ds.mes,
            ds.ano,
            u.nombre as usuario_nombre
        FROM verificadores_publicador vp
        JOIN documentos d ON vp.documento_id = d.id
        JOIN items_transparencia i ON d.item_id = i.id
        LEFT JOIN documento_seguimiento ds ON d.id = ds.documento_id
        LEFT JOIN usuarios u ON d.usuario_id = u.id
        ORDER BY vp.fecha_carga_portal DESC";

$result = $db_conn->query($sql);

echo "<h2>Verificadores Existentes</h2>";
echo "<table>";
echo "<tr><th>ID Verif</th><th>Doc ID</th><th>Título</th><th>Item</th><th>Periodicidad</th><th>Mes</th><th>Año</th><th>Fecha Publicación</th></tr>";

$total = 0;
while ($row = $result->fetch_assoc()) {
    $total++;
    echo "<tr>";
    echo "<td>" . $row['verif_id'] . "</td>";
    echo "<td>" . $row['documento_id'] . "</td>";
    echo "<td>" . htmlspecialchars(substr($row['doc_titulo'], 0, 40)) . "...</td>";
    echo "<td>" . htmlspecialchars($row['numeracion']) . " - " . htmlspecialchars(substr($row['item_nombre'], 0, 30)) . "...</td>";
    echo "<td>" . $row['periodicidad'] . "</td>";
    echo "<td class='" . ($row['mes'] ? 'ok' : 'error') . "'>" . ($row['mes'] ?? 'NULL') . "</td>";
    echo "<td class='" . ($row['ano'] ? 'ok' : 'error') . "'>" . ($row['ano'] ?? 'NULL') . "</td>";
    echo "<td>" . $row['fecha_carga_portal'] . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "<p><strong>Total verificadores: $total</strong></p>";

// Probar el query del publicador para Febrero 2026
$mesSeleccionado = 2;
$anoSeleccionado = 2026;

echo "<h2>Query Publicador para Febrero 2026</h2>";

$query = "
    SELECT 
        i.id as item_id,
        i.numeracion,
        i.nombre as item_nombre,
        i.periodicidad,
        d.id as doc_id,
        d.titulo,
        ds.mes,
        ds.ano,
        vp.id as verificador_id,
        vp.fecha_carga_portal
    FROM items_transparencia i
    LEFT JOIN documentos d ON i.id = d.item_id 
        AND d.estado IN ('pendiente', 'aprobado')
    LEFT JOIN documento_seguimiento ds ON d.id = ds.documento_id
    LEFT JOIN verificadores_publicador vp ON d.id = vp.documento_id
    WHERE i.activo = 1
        AND d.id IS NOT NULL
        AND (
            (i.periodicidad = 'mensual' AND ds.mes = ? AND ds.ano = ?)
            OR (i.periodicidad = 'trimestral' AND MOD(?, 3) = MOD(ds.mes, 3) AND ds.ano = ?)
            OR (i.periodicidad = 'semestral' AND MOD(?, 6) = MOD(ds.mes, 6) AND ds.ano = ?)
            OR (i.periodicidad = 'anual' AND ds.ano = ?)
            OR (i.periodicidad = 'ocurrencia' AND ds.mes = ? AND ds.ano = ?)
        )
    ORDER BY i.numeracion";

$stmt = $db_conn->prepare($query);
$stmt->bind_param("iiiiiiiii", 
    $mesSeleccionado, $anoSeleccionado,
    $mesSeleccionado, $anoSeleccionado,
    $mesSeleccionado, $anoSeleccionado,
    $anoSeleccionado,
    $mesSeleccionado, $anoSeleccionado
);
$stmt->execute();
$resultado = $stmt->get_result();

echo "<table>";
echo "<tr><th>Núm.</th><th>Item</th><th>Periodicidad</th><th>Doc ID</th><th>Mes</th><th>Año</th><th>Verif ID</th><th>Estado</th></tr>";

$conVerif = 0;
$sinVerif = 0;

while ($row = $resultado->fetch_assoc()) {
    if ($row['verificador_id']) {
        $conVerif++;
        $class = 'ok';
        $estado = 'PUBLICADO';
    } else {
        $sinVerif++;
        $class = '';
        $estado = 'PENDIENTE';
    }
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['numeracion']) . "</td>";
    echo "<td>" . htmlspecialchars(substr($row['item_nombre'], 0, 40)) . "...</td>";
    echo "<td>" . $row['periodicidad'] . "</td>";
    echo "<td>" . ($row['doc_id'] ?? '-') . "</td>";
    echo "<td>" . ($row['mes'] ?? '-') . "</td>";
    echo "<td>" . ($row['ano'] ?? '-') . "</td>";
    echo "<td class='$class'>" . ($row['verificador_id'] ?? '-') . "</td>";
    echo "<td class='$class'><strong>$estado</strong></td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Resumen</h3>";
echo "<p class='ok'>✅ Con verificador (publicados): <strong>$conVerif</strong></p>";
echo "<p>⏳ Sin verificador (pendientes): <strong>$sinVerif</strong></p>";

echo "</body></html>";

$db_conn->close();
?>
