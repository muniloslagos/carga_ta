<?php
/**
 * Resumen público municipal - Sin autenticación
 * Accesible mediante token generado al enviar correo de fin de proceso
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

// Validar token
$token = $_GET['token'] ?? '';
if (empty($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><title>Acceso Denegado</title></head><body style="font-family:Arial;text-align:center;padding:50px;"><h1>Acceso Denegado</h1><p>El enlace no es válido o ha expirado.</p></body></html>';
    exit;
}

$stmt = $conn->prepare("SELECT * FROM resumen_publico_tokens WHERE token = ?");
$stmt->bind_param('s', $token);
$stmt->execute();
$token_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$token_data) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>Enlace no encontrado</title></head><body style="font-family:Arial;text-align:center;padding:50px;"><h1>Enlace no encontrado</h1><p>Este enlace no existe o ha sido eliminado.</p></body></html>';
    exit;
}

// Verificar expiración
if ($token_data['fecha_expiracion'] && strtotime($token_data['fecha_expiracion']) < time()) {
    http_response_code(410);
    echo '<!DOCTYPE html><html><head><title>Enlace expirado</title></head><body style="font-family:Arial;text-align:center;padding:50px;"><h1>Enlace Expirado</h1><p>Este enlace ha expirado. Solicite uno nuevo al administrador.</p></body></html>';
    exit;
}

$mes = (int)$token_data['mes'];
$ano = (int)$token_data['ano'];

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
$nombre_mes = $meses[$mes] ?? '';

// Obtener todas las direcciones activas con su director
$direcciones = $conn->query("
    SELECT d.id, d.nombre, 
           CONCAT(dir.nombres, ' ', dir.apellidos) as director_nombre
    FROM direcciones d
    LEFT JOIN directores dir ON d.director_id = dir.id
    WHERE d.activa = 1
    ORDER BY d.nombre
");

// Obtener todos los items activos agrupados por dirección
$items_query = $conn->prepare("
    SELECT i.id, i.nombre, i.numeracion, i.periodicidad, i.direccion_id,
           i.mes_carga_anual, d.nombre as direccion_nombre
    FROM items_transparencia i
    LEFT JOIN direcciones d ON i.direccion_id = d.id
    WHERE i.activo = 1
    ORDER BY d.nombre, i.numeracion, i.nombre
");
$items_query->execute();
$all_items = $items_query->get_result();

// Agrupar items por dirección
$items_por_direccion = [];
$totales = ['total' => 0, 'publicados' => 0, 'cargados' => 0, 'pendientes' => 0];

while ($item = $all_items->fetch_assoc()) {
    $dir_id = $item['direccion_id'] ?? 0;
    $dir_nombre = $item['direccion_nombre'] ?? 'Sin Dirección';
    
    if (!isset($items_por_direccion[$dir_id])) {
        $items_por_direccion[$dir_id] = [
            'nombre' => $dir_nombre,
            'items' => [],
            'totales' => ['total' => 0, 'publicados' => 0, 'cargados' => 0, 'pendientes' => 0]
        ];
    }
    
    // Determinar mes de búsqueda según periodicidad
    $mes_busqueda = $mes;
    if ($item['periodicidad'] === 'anual') {
        $mes_busqueda = intval($item['mes_carga_anual'] ?? 1);
    } elseif ($item['periodicidad'] === 'trimestral') {
        $mes_busqueda = (int)(ceil($mes / 3) * 3);
    } elseif ($item['periodicidad'] === 'semestral') {
        $mes_busqueda = $mes <= 6 ? 1 : 7;
    }
    
    // Verificar Sin Movimiento
    $sinMovimiento = false;
    $sinMovFecha = null;
    $checkSinMov = $conn->query("SHOW TABLES LIKE 'observaciones_sin_movimiento'");
    if ($checkSinMov && $checkSinMov->num_rows > 0) {
        $stmtSM = $conn->prepare("SELECT id, fecha_creacion FROM observaciones_sin_movimiento 
            WHERE item_id = ? AND mes = ? AND ano = ? LIMIT 1");
        $stmtSM->bind_param('iii', $item['id'], $mes_busqueda, $ano);
        $stmtSM->execute();
        $smResult = $stmtSM->get_result();
        if ($smRow = $smResult->fetch_assoc()) {
            $sinMovimiento = true;
            $sinMovFecha = $smRow['fecha_creacion'];
        }
        $stmtSM->close();
    }
    
    // Buscar documento
    $documento = null;
    if ($sinMovimiento) {
        $stmtDoc = $conn->prepare("SELECT id, fecha_subida FROM documentos 
            WHERE item_id = ? AND mes_carga = ? AND ano_carga = ? AND titulo LIKE 'Sin Movimiento%'
            ORDER BY fecha_subida DESC LIMIT 1");
        $stmtDoc->bind_param('iii', $item['id'], $mes_busqueda, $ano);
        $stmtDoc->execute();
        $documento = $stmtDoc->get_result()->fetch_assoc();
        $stmtDoc->close();
    } else {
        $stmtDoc = $conn->prepare("SELECT id, fecha_subida FROM documentos 
            WHERE item_id = ? AND mes_carga = ? AND ano_carga = ?
            AND (titulo NOT LIKE 'Sin Movimiento%' OR titulo IS NULL)
            ORDER BY fecha_subida DESC LIMIT 1");
        $stmtDoc->bind_param('iii', $item['id'], $mes_busqueda, $ano);
        $stmtDoc->execute();
        $documento = $stmtDoc->get_result()->fetch_assoc();
        $stmtDoc->close();
    }
    
    // Buscar verificador
    $verificador = null;
    if ($documento) {
        $stmtVer = $conn->prepare("SELECT fecha_carga_portal FROM verificadores_publicador 
            WHERE documento_id = ? ORDER BY fecha_carga_portal DESC LIMIT 1");
        $stmtVer->bind_param('i', $documento['id']);
        $stmtVer->execute();
        $verificador = $stmtVer->get_result()->fetch_assoc();
        $stmtVer->close();
    }
    
    // Determinar estado
    $estado = '';
    $estado_clase = '';
    $fecha_envio = '';
    $fecha_publicacion = '';
    
    if ($verificador) {
        $estado = $sinMovimiento ? 'Sin Movimiento (Publicado)' : 'Publicado';
        $estado_clase = 'success';
        $fecha_envio = date('d/m/Y', strtotime($documento['fecha_subida']));
        $fecha_publicacion = date('d/m/Y', strtotime($verificador['fecha_carga_portal']));
        $totales['publicados']++;
        $items_por_direccion[$dir_id]['totales']['publicados']++;
    } elseif ($documento) {
        $estado = $sinMovimiento ? 'Sin Movimiento (Sin Publicar)' : 'Cargado (Sin Publicar)';
        $estado_clase = 'warning';
        $fecha_envio = date('d/m/Y', strtotime($documento['fecha_subida']));
        $fecha_publicacion = 'Pendiente';
        $totales['cargados']++;
        $items_por_direccion[$dir_id]['totales']['cargados']++;
    } elseif ($sinMovimiento) {
        $estado = 'Sin Movimiento (Sin Publicar)';
        $estado_clase = 'warning';
        $fecha_envio = $sinMovFecha ? date('d/m/Y', strtotime($sinMovFecha)) : '-';
        $fecha_publicacion = 'Pendiente';
        $totales['cargados']++;
        $items_por_direccion[$dir_id]['totales']['cargados']++;
    } else {
        $estado = 'Pendiente';
        $estado_clase = 'danger';
        $fecha_envio = '-';
        $fecha_publicacion = '-';
        $totales['pendientes']++;
        $items_por_direccion[$dir_id]['totales']['pendientes']++;
    }
    
    $totales['total']++;
    $items_por_direccion[$dir_id]['totales']['total']++;
    
    $items_por_direccion[$dir_id]['items'][] = [
        'nombre' => $item['nombre'],
        'numeracion' => $item['numeracion'],
        'periodicidad' => $item['periodicidad'],
        'estado' => $estado,
        'estado_clase' => $estado_clase,
        'fecha_envio' => $fecha_envio,
        'fecha_publicacion' => $fecha_publicacion
    ];
}

// Obtener directores por dirección
$dir_directores = [];
$direcciones->data_seek(0);
while ($d = $direcciones->fetch_assoc()) {
    $dir_directores[$d['id']] = $d['director_nombre'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen Municipal - Transparencia Activa - <?php echo htmlspecialchars($nombre_mes . ' ' . $ano); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f6fa; }
        .header-banner {
            background: linear-gradient(135deg, #1a3a5c 0%, #2c5282 100%);
            color: white;
            padding: 30px 0;
        }
        .stat-card { border-radius: 10px; text-align: center; padding: 20px; }
        .badge-periodicidad { font-size: 0.7rem; }
        @media print {
            .no-print { display: none !important; }
            .header-banner { background: #1a3a5c !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="header-banner">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-1"><i class="bi bi-shield-check"></i> Resumen Municipal - Transparencia Activa</h1>
                <h4 class="mb-0">Período: <?php echo htmlspecialchars($nombre_mes . ' ' . $ano); ?></h4>
            </div>
            <div class="text-end no-print">
                <button onclick="window.print()" class="btn btn-outline-light">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4">
    
    <!-- Resumen General -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card bg-white border shadow-sm">
                <h3 class="text-primary"><?php echo $totales['total']; ?></h3>
                <small class="text-muted">Total Ítems</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-white border shadow-sm">
                <h3 class="text-success"><?php echo $totales['publicados']; ?></h3>
                <small class="text-muted">Publicados</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-white border shadow-sm">
                <h3 class="text-warning"><?php echo $totales['cargados']; ?></h3>
                <small class="text-muted">Cargados (Pendientes)</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-white border shadow-sm">
                <h3 class="text-danger"><?php echo $totales['pendientes']; ?></h3>
                <small class="text-muted">Sin Cargar</small>
            </div>
        </div>
    </div>

    <!-- Barra de progreso general -->
    <div class="card mb-4">
        <div class="card-body">
            <h5>Progreso General del Municipio</h5>
            <?php 
            $pct_pub = $totales['total'] > 0 ? round($totales['publicados'] / $totales['total'] * 100) : 0;
            $pct_car = $totales['total'] > 0 ? round($totales['cargados'] / $totales['total'] * 100) : 0;
            $pct_pen = $totales['total'] > 0 ? round($totales['pendientes'] / $totales['total'] * 100) : 0;
            ?>
            <div class="progress" style="height: 30px;">
                <div class="progress-bar bg-success" style="width: <?php echo $pct_pub; ?>%"><?php echo $pct_pub; ?>% Publicados</div>
                <div class="progress-bar bg-warning" style="width: <?php echo $pct_car; ?>%"><?php echo $pct_car; ?>% Cargados</div>
                <div class="progress-bar bg-danger" style="width: <?php echo $pct_pen; ?>%"><?php echo $pct_pen; ?>% Pendientes</div>
            </div>
        </div>
    </div>

    <!-- Detalle por Dirección -->
    <?php foreach ($items_por_direccion as $dir_id => $dir_data): ?>
        <div class="card mb-4">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="bi bi-building"></i> <?php echo htmlspecialchars($dir_data['nombre']); ?>
                        </h5>
                        <?php if (!empty($dir_directores[$dir_id])): ?>
                            <small class="text-muted">
                                <i class="bi bi-person-badge"></i> Director/a: <?php echo htmlspecialchars($dir_directores[$dir_id]); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="badge bg-success"><?php echo $dir_data['totales']['publicados']; ?> publicados</span>
                        <span class="badge bg-warning text-dark"><?php echo $dir_data['totales']['cargados']; ?> cargados</span>
                        <span class="badge bg-danger"><?php echo $dir_data['totales']['pendientes']; ?> pendientes</span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ítem</th>
                                <th>Periodicidad</th>
                                <th>Estado</th>
                                <th>Fecha Carga</th>
                                <th>Fecha Publicación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dir_data['items'] as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary badge-periodicidad">
                                            <?php echo ucfirst(htmlspecialchars($item['periodicidad'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $item['estado_clase']; ?>">
                                            <?php echo htmlspecialchars($item['estado']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['fecha_envio']); ?></td>
                                    <td><?php echo htmlspecialchars($item['fecha_publicacion']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="text-center text-muted mb-4">
        <small>
            Generado el <?php echo date('d/m/Y H:i:s'); ?> — 
            Municipalidad de Los Lagos — Sistema de Transparencia Activa
        </small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
