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
echo "<p><strong>Período actual:</strong> Mes {$mesActual} / Año {$anoActual}</p>";

// Buscar documentos con verificadores
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
    v.id as verificador_id,
    v.archivo_verificador,
    v.fecha_carga_portal,
    u.nombre as usuario_nombre
FROM documentos d
INNER JOIN documento_seguimiento ds ON d.id = ds.documento_id
INNER JOIN items i ON ds.item_id = i.id
LEFT JOIN verificadores_publicador v ON d.id = v.documento_id
LEFT JOIN usuarios u ON d.usuario_id = u.id
WHERE d.estado IN ('Cargado', 'Publicado')
ORDER BY ds.ano DESC, ds.mes DESC, i.numeracion ASC
LIMIT 50";

$result = $conn->query($sql);

if (!$result) {
    echo "<div class='alert alert-danger'>Error en query: " . $conn->error . "</div>";
    echo "</div></body></html>";
    exit;
}

$conVerificador = 0;
$sinVerificador = 0;

echo "<h2>📊 Documentos Cargados y Publicados (máx. 50)</h2>";
echo "<table>";
echo "<tr>";
echo "<th>Núm</th>";
echo "<th>Item</th>";
echo "<th>Mes/Año</th>";
echo "<th>Estado</th>";
echo "<th>Usuario</th>";
echo "<th>Verificador ID</th>";
echo "<th>Botones que DEBERÍAN aparecer</th>";
echo "</tr>";

while ($row = $result->fetch_assoc()) {
    $tieneVerif = !empty($row['verificador_id']);
    
    if ($tieneVerif) {
        $conVerificador++;
        $estadoBadge = "<span class='badge bg-success'>Publicado</span>";
        $verifInfo = "ID: " . $row['verificador_id'];
        
        // Generar botones como en index.php
        $botones = "<a href='#' class='btn btn-info'>Ver Doc</a> " .
                   "<a href='#' class='btn btn-success'>Ver Verif</a> " .
                   "<a href='#' class='btn btn-danger'>🗑️ Eliminar</a>";
    } else {
        $sinVerificador++;
        $estadoBadge = "<span class='badge bg-warning'>Cargado</span>";
        $verifInfo = "—";
        $botones = "<a href='#' class='btn btn-info'>Ver</a> " .
                   "<a href='#' class='btn btn-info'>Agregar Verificador</a>";
    }
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['numeracion']) . "</td>";
    echo "<td><strong>" . htmlspecialchars($row['item_nombre']) . "</strong></td>";
    echo "<td>{$row['mes']}/{$row['ano']}</td>";
    echo "<td>{$estadoBadge}</td>";
    echo "<td>" . htmlspecialchars($row['usuario_nombre']) . "</td>";
    echo "<td>{$verifInfo}</td>";
    echo "<td>{$botones}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<div class='alert alert-info'>";
echo "<h3>📈 Resumen:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Documentos CON verificador:</strong> {$conVerificador} (deben mostrar botón rojo 'Eliminar')</li>";
echo "<li>⚠️ <strong>Documentos SIN verificador:</strong> {$sinVerificador} (solo botón 'Agregar Verificador')</li>";
echo "</ul>";
echo "</div>";

if ($conVerificador > 0) {
    echo "<div class='alert alert-success'>";
    echo "<h3>✅ HAY DOCUMENTOS CON VERIFICADORES</h3>";
    echo "<p>Si ve botones rojos 'Eliminar' arriba pero <strong>NO los ve en el panel de publicador</strong>, entonces:</p>";
    echo "<ol>";
    echo "<li><strong>Actualice el servidor de producción:</strong></li>";
    echo "<li style='margin-left: 30px;'>Conéctese por SSH al servidor</li>";
    echo "<li style='margin-left: 30px;'>Vaya a: <code>cd /home/appmuniloslagos/public_html/carga_ta</code></li>";
    echo "<li style='margin-left: 30px;'>Ejecute: <code>git pull origin main</code></li>";
    echo "<li style='margin-left: 30px;'>Commit esperado: <code>011a822</code> o más reciente</li>";
    echo "<li><strong>Limpie caché del navegador:</strong> Presione <kbd>Ctrl + F5</kbd></li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div class='alert alert-warning'>";
    echo "<h3>⚠️ NO HAY DOCUMENTOS CON VERIFICADORES</h3>";
    echo "<p>Por eso no ve el botón 'Eliminar'. Para probarlo:</p>";
    echo "<ol>";
    echo "<li>Vaya a <a href='admin/publicador/'>admin/publicador/</a></li>";
    echo "<li>Busque un documento con badge amarillo 'Cargado'</li>";
    echo "<li>Haga clic en 'Agregar Verificador'</li>";
    echo "<li>Cargue cualquier imagen de prueba</li>";
    echo "<li>El documento pasará a estado verde 'Publicado'</li>";
    echo "<li>Recargue esta página (F5)</li>";
    echo "<li>Ahora debería ver la fila con estado 'Publicado' y botón rojo 'Eliminar'</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>🔧 Información Técnica:</h3>";
echo "<ul>";
echo "<li><strong>Servidor:</strong> " . gethostname() . "</li>";
echo "<li><strong>Último commit local esperado:</strong> <code>011a822</code></li>";
echo "<li><strong>Archivos modificados:</strong></li>";
echo "<li style='margin-left: 30px;'>✓ classes/Verificador.php (método delete actualizado)</li>";
echo "<li style='margin-left: 30px;'>✓ admin/publicador/eliminar_verificador.php (nuevo)</li>";
echo "<li style='margin-left: 30px;'>✓ admin/publicador/index.php (botón y modal agregados)</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='admin/publicador/'>← Ir al Panel de Publicador</a></p>";
echo "</div>";
echo "</body></html>";

$conn->close();
?>
