<?php
/**
 * Resumen público municipal - Sin autenticación
 * Accesible mediante token generado al enviar correo de fin de proceso
 */

// Evitar caché del navegador para mostrar siempre datos actualizados
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

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

// CAMBIO: Token solo valida acceso, período se define por GET o mes anterior actual
// Obtener mes/año de parámetros GET o usar mes anterior al actual por defecto
if (isset($_GET['mes']) && is_numeric($_GET['mes'])) {
    $mes = (int)$_GET['mes'];
    $ano = isset($_GET['ano']) && is_numeric($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
} else {
    // Por defecto: mes anterior al actual (igual que en dashboard)
    $mes = (int)date('n') - 1;
    $ano = (int)date('Y');
    if ($mes < 1) {
        $mes = 12;
        $ano--;
    }
}

// Validar rango de mes y año
if ($mes < 1 || $mes > 12) {
    $mes = (int)date('n') - 1;
    if ($mes < 1) $mes = 12;
}
if ($ano < 2020 || $ano > 2050) {
    $ano = (int)date('Y');
}

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
$nombre_mes = $meses[$mes] ?? '';

// Obtener años configurados activos
$anosDisponibles = [];
$checkAnosTable = $conn->query("SHOW TABLES LIKE 'anos_configurados'");
if ($checkAnosTable && $checkAnosTable->num_rows > 0) {
    $stmtAnos = $conn->query("SELECT ano FROM anos_configurados WHERE activo = 1 ORDER BY ano DESC");
    while ($rowAno = $stmtAnos->fetch_assoc()) {
        $anosDisponibles[] = (int)$rowAno['ano'];
    }
}
// Fallback: si no hay años configurados, usar rango tradicional
if (empty($anosDisponibles)) {
    $currentYear = date('Y');
    for ($a = $currentYear - 2; $a <= $currentYear; $a++) {
        $anosDisponibles[] = $a;
    }
}

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
    
    // Filtrar items que no corresponden al período seleccionado
    $mesesTrimestral = [3, 6, 9, 12];
    $mesesSemestral = [6, 12];
    if ($item['periodicidad'] === 'trimestral' && !in_array($mes, $mesesTrimestral)) {
        continue;
    }
    if ($item['periodicidad'] === 'semestral' && !in_array($mes, $mesesSemestral)) {
        continue;
    }
    if ($item['periodicidad'] === 'anual' && intval($item['mes_carga_anual'] ?? 1) !== $mes) {
        continue;
    }
    
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
    
    // Buscar documento (con fallback para docs sin mes_carga/ano_carga)
    $documento = null;
    if ($sinMovimiento) {
        $stmtDoc = $conn->prepare("SELECT id, fecha_subida FROM documentos 
            WHERE item_id = ? 
            AND ((mes_carga = ? AND ano_carga = ?) OR (mes_carga IS NULL AND MONTH(fecha_subida) = ? AND YEAR(fecha_subida) = ?))
            AND titulo LIKE 'Sin Movimiento%'
            AND estado != 'reemplazado'
            ORDER BY fecha_subida DESC LIMIT 1");
        $stmtDoc->bind_param('iiiii', $item['id'], $mes_busqueda, $ano, $mes_busqueda, $ano);
        $stmtDoc->execute();
        $documento = $stmtDoc->get_result()->fetch_assoc();
        $stmtDoc->close();
    } else {
        $stmtDoc = $conn->prepare("SELECT id, fecha_subida FROM documentos 
            WHERE item_id = ? 
            AND ((mes_carga = ? AND ano_carga = ?) OR (mes_carga IS NULL AND MONTH(fecha_subida) = ? AND YEAR(fecha_subida) = ?))
            AND (titulo NOT LIKE 'Sin Movimiento%' OR titulo IS NULL)
            AND estado != 'reemplazado'
            ORDER BY fecha_subida DESC LIMIT 1");
        $stmtDoc->bind_param('iiiii', $item['id'], $mes_busqueda, $ano, $mes_busqueda, $ano);
        $stmtDoc->execute();
        $documento = $stmtDoc->get_result()->fetch_assoc();
        $stmtDoc->close();
    }
    
    // Buscar verificador
    $verificador = null;
    if ($documento) {
        $stmtVer = $conn->prepare("SELECT fecha_carga_portal, archivo_verificador FROM verificadores_publicador 
            WHERE documento_id = ? ORDER BY fecha_carga_portal DESC LIMIT 1");
        $stmtVer->bind_param('i', $documento['id']);
        $stmtVer->execute();
        $verificador = $stmtVer->get_result()->fetch_assoc();
        $stmtVer->close();
    }
    
    // Verificar si el documento tiene observaciones pendientes
    $tieneObservacion = false;
    if ($documento && !$verificador) {
        $checkTablaObs = $conn->query("SHOW TABLES LIKE 'observaciones_documentos'");
        if ($checkTablaObs && $checkTablaObs->num_rows > 0) {
            $stmtObs = $conn->prepare("SELECT id FROM observaciones_documentos WHERE documento_id = ? AND resuelta = 0 LIMIT 1");
            $stmtObs->bind_param('i', $documento['id']);
            $stmtObs->execute();
            $tieneObservacion = (bool)$stmtObs->get_result()->fetch_assoc();
            $stmtObs->close();
        }
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
        if ($tieneObservacion) {
            $estado = $sinMovimiento ? 'Sin Movimiento (Observado)' : 'Cargado (Observado)';
            $estado_clase = 'danger';
        } else {
            $estado = $sinMovimiento ? 'Sin Movimiento (Sin Publicar)' : 'Cargado (Sin Publicar)';
            $estado_clase = 'warning';
        }
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
        'fecha_publicacion' => $fecha_publicacion,
        'archivo_verificador' => $verificador ? ($verificador['archivo_verificador'] ?? null) : null
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
        .header-banner img {
            height: 80px;
            width: auto;
            max-width: 150px;
            object-fit: contain;
        }
        .stat-card { border-radius: 10px; text-align: center; padding: 20px; }
        .badge-periodicidad { font-size: 0.7rem; }
        @media print {
            .no-print { display: none !important; }
            .header-banner { background: #1a3a5c !important; -webkit-print-color-adjust: exact; }
            .header-banner img { height: 60px !important; }
        }
        @media (max-width: 768px) {
            .header-banner img { height: 50px; max-width: 100px; }
            .header-banner h1 { font-size: 1.5rem; }
            .header-banner h4 { font-size: 1rem; }
        }
    </style>
</head>
<body>

<div class="header-banner">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <img src="https://muniloslagos.cl/wp-content/uploads/2025/02/logo_blanco2025.png" 
                     alt="Logo Municipalidad de Los Lagos" 
                     style="height: 80px; width: auto;">
                <div>
                    <h1 class="mb-1"><i class="bi bi-shield-check"></i> Resumen Municipal - Transparencia Activa</h1>
                    <h4 class="mb-0">Período: <?php echo htmlspecialchars($nombre_mes . ' ' . $ano); ?></h4>
                </div>
            </div>
            <div class="text-end no-print d-flex gap-2 justify-content-end">
                <button onclick="descargarPDF()" class="btn btn-outline-light">
                    <i class="bi bi-file-earmark-pdf"></i> Descargar PDF
                </button>
                <button onclick="window.print()" class="btn btn-outline-light">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Selector de Período -->
<div class="container mt-3 no-print">
    <div class="card shadow-sm">
        <div class="card-body py-2">
            <form method="GET" class="d-flex align-items-center gap-3">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <span class="fw-bold text-muted"><i class="bi bi-calendar3"></i> Cambiar Período:</span>
                <select name="mes" class="form-select form-select-sm" style="max-width: 200px;">
                    <?php
                    // Para 2026, comenzar desde marzo (mes 3)
                    $mesInicio = ($ano == 2026) ? 3 : 1;
                    for ($m = $mesInicio; $m <= 12; $m++) {
                        $selected = ($mes == $m) ? 'selected' : '';
                        echo "<option value='$m' $selected>{$meses[$m]}</option>";
                    }
                    ?>
                </select>
                <select name="ano" class="form-select form-select-sm" style="max-width: 100px;">
                    <?php
                    foreach ($anosDisponibles as $a) {
                        $selected = ($ano == $a) ? 'selected' : '';
                        echo "<option value='$a' $selected>$a</option>";
                    }
                    ?>
                </select>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-search"></i> Ver
                </button>
            </form>
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
                                        <?php if ($item['estado_clase'] === 'success' && !empty($item['archivo_verificador'])): 
                                            $ext = strtolower(pathinfo($item['archivo_verificador'], PATHINFO_EXTENSION));
                                            $esPdf = ($ext === 'pdf');
                                            $urlVerif = SITE_URL . 'uploads/' . htmlspecialchars($item['archivo_verificador']);
                                            $nombreItem = htmlspecialchars(addslashes($item['nombre']));
                                        ?>
                                            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#modalVerificador" 
                                               onclick="mostrarVerificador('<?php echo $urlVerif; ?>', '<?php echo $nombreItem; ?>', <?php echo $esPdf ? 'true' : 'false'; ?>)">
                                                <span class="badge bg-success" style="cursor:pointer" title="Clic para ver verificador">
                                                    <i class="bi bi-eye"></i> <?php echo htmlspecialchars($item['estado']); ?>
                                                </span>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-<?php echo $item['estado_clase']; ?>">
                                                <?php echo htmlspecialchars($item['estado']); ?>
                                            </span>
                                        <?php endif; ?>
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

    <!-- Tabla resumen de items pendientes -->
    <?php
    $items_pendientes = [];
    foreach ($items_por_direccion as $did => $ddata) {
        foreach ($ddata['items'] as $it) {
            if ($it['estado_clase'] !== 'success') {
                $items_pendientes[] = [
                    'nombre' => $it['nombre'],
                    'numeracion' => $it['numeracion'],
                    'periodicidad' => $it['periodicidad'],
                    'estado' => $it['estado'],
                    'estado_clase' => $it['estado_clase'],
                    'director' => $dir_directores[$did] ?? 'Sin director asignado'
                ];
            }
        }
    }
    ?>
    <?php if (!empty($items_pendientes)): ?>
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">
                <i class="bi bi-exclamation-triangle-fill"></i> Ítems con Documentos No Cargados o Pendientes de Publicación
                <span class="badge bg-white text-danger ms-2"><?php echo count($items_pendientes); ?></span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ítem</th>
                            <th>Periodicidad</th>
                            <th>Estado</th>
                            <th>Director Responsable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items_pendientes as $pend): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pend['nombre']); ?></td>
                                <td>
                                    <span class="badge bg-secondary badge-periodicidad">
                                        <?php echo ucfirst(htmlspecialchars($pend['periodicidad'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $pend['estado_clase']; ?>">
                                        <?php echo htmlspecialchars($pend['estado']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($pend['director']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="text-center text-muted mb-4">
        <small>
            Generado el <?php echo date('d/m/Y H:i:s'); ?> — 
            Municipalidad de Los Lagos — Sistema de Transparencia Activa
        </small>
    </div>
</div>

<!-- Modal Verificador -->
<div class="modal fade" id="modalVerificador" tabindex="-1" aria-labelledby="modalVerificadorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" id="modalVerificadorDialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVerificadorLabel">Verificador</h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnToggleSize" onclick="toggleModalSize()" title="Maximizar">
                        <i class="bi bi-arrows-fullscreen" id="iconToggleSize"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
            </div>
            <div class="modal-body text-center">
                <img id="imgVerificador" src="" alt="Verificador" class="img-fluid rounded shadow" style="max-height: 70vh; display:none;">
                <iframe id="pdfVerificador" src="" style="width:100%; height:70vh; border:none; display:none;"></iframe>
            </div>
            <div class="modal-footer">
                <a id="linkDescargarVerif" href="" download class="btn btn-primary">
                    <i class="bi bi-download"></i> Descargar
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function mostrarVerificador(url, nombre, esPdf) {
    document.getElementById('modalVerificadorLabel').textContent = nombre;
    document.getElementById('linkDescargarVerif').href = url;
    // Resetear a tamaño normal al abrir
    const dialog = document.getElementById('modalVerificadorDialog');
    dialog.classList.remove('modal-fullscreen');
    dialog.classList.add('modal-lg');
    document.getElementById('iconToggleSize').className = 'bi bi-arrows-fullscreen';
    document.getElementById('btnToggleSize').title = 'Maximizar';
    const img = document.getElementById('imgVerificador');
    const pdf = document.getElementById('pdfVerificador');
    if (esPdf) {
        img.style.display = 'none';
        img.src = '';
        pdf.src = url;
        pdf.style.display = 'block';
    } else {
        pdf.style.display = 'none';
        pdf.src = '';
        img.src = url;
        img.style.display = 'block';
    }
}
// Limpiar modal al cerrar
document.getElementById('modalVerificador').addEventListener('hidden.bs.modal', function() {
    document.getElementById('imgVerificador').src = '';
    document.getElementById('pdfVerificador').src = '';
});

function toggleModalSize() {
    const dialog = document.getElementById('modalVerificadorDialog');
    const icon = document.getElementById('iconToggleSize');
    const btn = document.getElementById('btnToggleSize');
    if (dialog.classList.contains('modal-fullscreen')) {
        dialog.classList.remove('modal-fullscreen');
        dialog.classList.add('modal-lg');
        icon.className = 'bi bi-arrows-fullscreen';
        btn.title = 'Maximizar';
        document.getElementById('imgVerificador').style.maxHeight = '70vh';
        document.getElementById('pdfVerificador').style.height = '70vh';
    } else {
        dialog.classList.remove('modal-lg');
        dialog.classList.add('modal-fullscreen');
        icon.className = 'bi bi-fullscreen-exit';
        btn.title = 'Reducir';
        document.getElementById('imgVerificador').style.maxHeight = '85vh';
        document.getElementById('pdfVerificador').style.height = '85vh';
    }
}

function descargarPDF() {
    const btn = event.target.closest('button');
    const textoOriginal = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Generando...';
    btn.disabled = true;
    
    // Ocultar elementos no-print
    document.querySelectorAll('.no-print').forEach(el => el.style.display = 'none');
    
    const element = document.body;
    const opt = {
        margin: [10, 10, 10, 10],
        filename: 'Resumen_Transparencia_<?php echo htmlspecialchars($nombre_mes); ?>_<?php echo $ano; ?>.pdf',
        image: { type: 'jpeg', quality: 0.95 },
        html2canvas: { scale: 2, useCORS: true, letterRendering: true },
        jsPDF: { unit: 'mm', format: 'legal', orientation: 'landscape' },
        pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
    };
    
    html2pdf().set(opt).from(element).save().then(() => {
        document.querySelectorAll('.no-print').forEach(el => el.style.display = '');
        btn.innerHTML = textoOriginal;
        btn.disabled = false;
    }).catch(() => {
        document.querySelectorAll('.no-print').forEach(el => el.style.display = '');
        btn.innerHTML = textoOriginal;
        btn.disabled = false;
    });
}
</script>
</body>
</html>
