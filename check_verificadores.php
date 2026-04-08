<?php
/**
 * Script simple para verificar verificadores (sin clases)
 */
require_once 'config/config.php';

// Conexión directa sin clase Database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$mesActual = (int)date('m');
$anoActual = (int)date('Y');

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Verificadores - Diagnóstico</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th { background: #007bff; color: white; padding: 12px; text-align: left; }
td { padding: 10px; border-bottom: 1px solid #ddd; }
tr:hover { background: #f8f9fa; }
.badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
.bg-success { background: #28a745; color: white; }
.bg-warning { background: #ffc107; color: #000; }
.bg-danger { background: #dc3545; color: white; }
.btn { display: inline-block; padding: 6px 12px; margin: 2px; border-radius: 4px; text-decoration: none; font-size: 13px; }
.btn-info { background: #17a2b8; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-danger { background: #dc3545; color: white; }
.alert { padding: 15px; margin: 20px 0; border-radius: 4px; }
.alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
.alert-warning { background: #fff3cd; border: 1px solid #ffc107; color: #856404; }
.alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔍 Diagnóstico: Verificadores y Botón Eliminar</h1>";
echo "<p><strong>Buscando en TODOS los períodos...</strong></p>";

// Primero buscar SOLO documentos CON verificadores (todos los períodos)
$sqlConVerif = "SELECT 
    d.id as documento_id,
    d.titulo,
    d.estado,
    ds.mes,
    ds.ano,
    i.id as item_id,
    i.numeracion,
    i.nombre as item_nombre,
    i.periodicidad,
    v.id as verificador_id,
    v.archivo_verificador,
    v.fecha_carga_portal,
    u.nombre as usuario_nombre
FROM documentos d
INNER JOIN documento_seguimiento ds ON d.id = ds.documento_id
INNER JOIN items i ON ds.item_id = i.id
INNER JOIN verificadores_publicador v ON d.id = v.documento_id
LEFT JOIN usuarios u ON d.usuario_id = u.id
WHERE d.estado = 'Publicado'
ORDER BY ds.ano DESC, ds.mes DESC, i.numeracion ASC
LIMIT 100";

// Luego buscar documentos sin verificadores del mes actual
$sql = "SELECT 
    d.id as documento_id,
    d.titulo,
    d.estado,
    ds.mes,
    ds.ano,
    i.id as item_id,
    i.numeracion,
    i.nombre as item_nombre,
    i.periodicidad,
    NULL as verificador_id,
    NULL as archivo_verificador,
    NULL as fecha_carga_portal,
    u.nombre as usuario_nombre
FROM documentos d
INNER JOIN documento_seguimiento ds ON d.id = ds.documento_id
INNER JOIN items i ON ds.item_id = i.id
LEFT JOIN usuarios u ON d.usuario_id = u.id
WHERE d.estado = 'Cargado' 
  AND ds.mes = {$mesActual} 
  AND ds.ano = {$anoActual}
ORDER BY i.numeracion ASC
LIMIT 50";

// Ejecutar query de documentos CON verificadores
$resultConVerif = $conn->query($sqlConVerif);

if (!$resultConVerif) {
    echo "<div class='alert alert-danger'>Error en query con verificadores: " . $conn->error . "</div>";
    echo "</div></body></html>";
    exit;
}

// Ejecutar query de documentos SIN verificadores
$resultSinVerif = $conn->query($sql);

if (!$resultSinVerif) {
    echo "<div class='alert alert-danger'>Error en query sin verificadores: " . $conn->error . "</div>";
    echo "</div></body></html>";
    exit;
}

$conVerificador = $resultConVerif->num_rows;
$sinVerificador = $resultSinVerif->num_rows;

echo "<div class='alert alert-info'>";
echo "<h3>📈 Resumen General:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Documentos CON verificador (todos los períodos):</strong> {$conVerificador}</li>";
echo "<li>⚠️ <strong>Documentos SIN verificador (mes {$mesActual}/{$anoActual}):</strong> {$sinVerificador}</li>";
echo "</ul>";
echo "</div>";

// Mostrar primero los que TIENEN verificador
if ($conVerificador > 0) {
    echo "<h2 style='color: green;'>✅ Documentos CON Verificador (debe mostrar botón ELIMINAR)</h2>";
    echo "<table>";
    echo "<tr>";
    echo "<th>Núm</th>";
    echo "<th>Item</th>";
    echo "<th>Mes/Año</th>";
    echo "<th>Estado</th>";
    echo "<th>Usuario</th>";
    echo "<th>Verificador ID</th>";
    echo "<th>Fecha Publicación</th>";
    echo "<th>Botones que DEBERÍAN aparecer</th>";
    echo "</tr>";

    while ($row = $resultConVerif->fetch_assoc()) {
        $estadoBadge = "<span class='badge bg-success'>Publicado</span>";
        $verifInfo = "ID: " . $row['verificador_id'];
        $fechaPublic = date('d/m/Y H:i', strtotime($row['fecha_carga_portal']));
        
        // Generar botones como en index.php
        $botones = "<a href='#' class='btn btn-info'>Ver Doc</a> " .
                   "<a href='#' class='btn btn-success'>Ver Verif</a> " .
                   "<strong><a href='#' class='btn btn-danger'>🗑️ Eliminar</a></strong>";
        
        echo "<tr style='background: #f0fff0;'>";
        echo "<td>" . htmlspecialchars($row['numeracion']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['item_nombre']) . "</strong></td>";
        echo "<td><strong>{$row['mes']}/{$row['ano']}</strong></td>";
        echo "<td>{$estadoBadge}</td>";
        echo "<td>" . htmlspecialchars($row['usuario_nombre']) . "</td>";
        echo "<td><strong style='color: green;'>{$verifInfo}</strong></td>";
        echo "<td>{$fechaPublic}</td>";
        echo "<td>{$botones}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Mostrar documentos sin verificador (mes actual)
if ($sinVerificador > 0) {
    echo "<h2 style='color: orange;'>⚠️ Documentos SIN Verificador (mes actual: {$mesActual}/{$anoActual})</h2>";
    echo "<table>";
    echo "<tr>";
    echo "<th>Núm</th>";
    echo "<th>Item</th>";
    echo "<th>Mes/Año</th>";
    echo "<th>Estado</th>";
    echo "<th>Usuario</th>";
    echo "<th>Botones disponibles</th>";
    echo "</tr>";

    while ($row = $resultSinVerif->fetch_assoc()) {
        $estadoBadge = "<span class='badge bg-warning'>Cargado</span>";
        $botones = "<a href='#' class='btn btn-info'>Ver</a> " .
                   "<a href='#' class='btn btn-info'>Agregar Verificador</a>";
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['numeracion']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['item_nombre']) . "</strong></td>";
        echo "<td>{$row['mes']}/{$row['ano']}</td>";
        echo "<td>{$estadoBadge}</td>";
        echo "<td>" . htmlspecialchars($row['usuario_nombre']) . "</td>";
        echo "<td>{$botones}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<div class='alert alert-info'>";
echo "<h3>📈 Resumen:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Documentos CON verificador:</strong> {$conVerificador} (deben mostrar botón rojo 'Eliminar')</li>";
echo "<li>⚠️ <strong>Documentos SIN verificador:</strong> {$sinVerificador} (solo botón 'Agregar Verificador')</li>";
echo "</ul>";
echo "</div>";

if ($conVerificador > 0) {
    echo "<div class='alert alert-success'>";
    echo "<h3>✅ SE ENCONTRARON {$conVerificador} DOCUMENTOS CON VERIFICADORES</h3>";
    echo "<p><strong style='font-size: 18px;'>El botón rojo 'Eliminar' DEBE estar visible en esos documentos.</strong></p>";
    echo "<p>Si ve los botones rojos 'Eliminar' en la tabla de arriba pero <strong>NO los ve en el panel de publicador</strong>, entonces:</p>";
    echo "<ol>";
    echo "<li><strong>Primero actualice el servidor de producción:</strong></li>";
    echo "<li style='margin-left: 30px;'>Conéctese por SSH: <code>ssh usuario@app.muniloslagos.cl</code></li>";
    echo "<li style='margin-left: 30px;'>Vaya a la carpeta: <code>cd /home/appmuniloslagos/public_html/carga_ta</code></li>";
    echo "<li style='margin-left: 30px;'>Actualice desde GitHub: <code>git pull origin main</code></li>";
    echo "<li style='margin-left: 30px;'>Verifique el commit: <code>git log --oneline -5</code> (debe ver d8925f2 o más reciente)</li>";
    echo "<li><strong>Luego limpie caché del navegador:</strong></li>";
    echo "<li style='margin-left: 30px;'>En la página del publicador, presione <kbd>Ctrl + F5</kbd></li>";
    echo "<li style='margin-left: 30px;'>O <kbd>Ctrl + Shift + R</kbd> en algunos navegadores</li>";
    echo "<li><strong>Vaya al período correcto:</strong></li>";
    echo "<li style='margin-left: 30px;'>Los verificadores arriba están en diferentes meses/años</li>";
    echo "<li style='margin-left: 30px;'>Seleccione el mes/año correspondiente en el panel de publicador</li>";
    echo "<li style='margin-left: 30px;'>Ejemplo: Si ve verificadores en Marzo/2026, seleccione ese período</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div class='alert alert-warning'>";
    echo "<h3>⚠️ NO HAY DOCUMENTOS CON VERIFICADORES EN LA BASE DE DATOS</h3>";
    echo "<p>Por eso no ve el botón 'Eliminar'. Para probarlo:</p>";
    echo "<ol>";
    echo "<li>Vaya a <a href='admin/publicador/'>admin/publicador/</a></li>";
    echo "<li>Busque un documento con badge amarillo 'Cargado' (tabla de abajo)</li>";
    echo "<li>Haga clic en 'Agregar Verificador'</li>";
    echo "<li>Cargue cualquier imagen de prueba</li>";
    echo "<li>El documento pasará a estado verde 'Publicado'</li>";
    echo "<li>Recargue esta página (F5)</li>";
    echo "<li>Ahora debería ver ese documento en la tabla verde de arriba con botón 'Eliminar'</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>🔧 Información Técnica:</h3>";
echo "<ul>";
echo "<li><strong>Servidor:</strong> " . gethostname() . "</li>";
echo "<li><strong>Último commit esperado:</strong> <code>d8925f2</code> o más reciente</li>";
echo "<li><strong>Archivos que deben estar actualizados:</strong></li>";
echo "<li style='margin-left: 30px;'>✓ classes/Verificador.php (método delete con retrotraer)</li>";
echo "<li style='margin-left: 30px;'>✓ admin/publicador/eliminar_verificador.php (endpoint nuevo)</li>";
echo "<li style='margin-left: 30px;'>✓ admin/publicador/index.php (botón Eliminar y modal)</li>";
echo "<li><strong>Query ejecutada:</strong> INNER JOIN con verificadores_publicador (busca en todos los períodos)</li>";
echo "<li><strong>Total encontrados:</strong> {$conVerificador} documentos con verificadores</li>";
echo "</ul>";

if ($conVerificador > 0) {
    echo "<div style='background: #fff3cd; padding: 15px; border: 2px solid #ffc107; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>⚠️ IMPORTANTE: Seleccione el período correcto</h4>";
    echo "<p>Los verificadores mostrados arriba pueden estar en <strong>diferentes meses/años</strong>.</p>";
    echo "<p>En el panel de publicador (<code>admin/publicador/</code>), debe:</p>";
    echo "<ul>";
    echo "<li>Seleccionar el <strong>mes y año</strong> correspondiente en los selectores</li>";
    echo "<li>El botón 'Eliminar' solo aparece en el período donde existe el verificador</li>";
    echo "<li>Ejemplo: Si en la tabla de arriba ve 'Marzo/2026', seleccione Marzo 2026 en el publicador</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='admin/publicador/'>← Ir al Panel de Publicador</a> | <a href='check_verificadores.php'>Recargar diagnóstico</a></p>";
echo "</div>";
echo "</body></html>";

$conn->close();
?>
