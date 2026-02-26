<?php
require_once dirname(dirname(__DIR__)) . '/includes/check_auth.php';

// Verificar permisos
if ($current_profile !== 'administrativo' && $current_profile !== 'director_revisor' && $current_profile !== 'publicador') {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

require_once dirname(dirname(__DIR__)) . '/includes/header.php';

// Obtener mes y año seleccionado
$mesSeleccionado = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$anoSeleccionado = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');

// Validar mes y año
if ($mesSeleccionado < 1 || $mesSeleccionado > 12) $mesSeleccionado = (int)date('m');
if ($anoSeleccionado < 2000 || $anoSeleccionado > 2100) $anoSeleccionado = (int)date('Y');

$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
         'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

// Query principal: Obtener todos los items con sus documentos del período seleccionado
$query = "
    SELECT 
        i.id as item_id,
        i.numeracion,
        i.nombre as item_nombre,
        i.periodicidad,
        d.id as doc_id,
        d.titulo,
        d.archivo,
        d.estado as doc_estado,
        d.fecha_subida,
        ds.fecha_envio,
        ds.mes,
        ds.ano,
        u.id as usuario_id,
        u.nombre as usuario_nombre,
        vp.id as verificador_id,
        vp.archivo_verificador,
        vp.fecha_carga_portal
    FROM items_transparencia i
    LEFT JOIN documentos d ON i.id = d.item_id 
        AND d.estado IN ('pendiente', 'aprobado')
    LEFT JOIN documento_seguimiento ds ON d.id = ds.documento_id
    LEFT JOIN usuarios u ON d.usuario_id = u.id
    LEFT JOIN verificadores_publicador vp ON d.id = vp.documento_id
    WHERE i.activo = 1
        AND (
            -- Solo items con documento del período, o sin documento
            d.id IS NULL
            OR (i.periodicidad = 'mensual' AND ds.mes = ? AND ds.ano = ?)
            OR (i.periodicidad = 'trimestral' AND FLOOR((ds.mes-1)/3) = FLOOR((?-1)/3) AND ds.ano = ?)
            OR (i.periodicidad = 'semestral' AND FLOOR((ds.mes-1)/6) = FLOOR((?-1)/6) AND ds.ano = ?)
            OR (i.periodicidad = 'anual' AND ds.ano = ?)
            OR (i.periodicidad = 'ocurrencia' AND ds.mes = ? AND ds.ano = ?)
        )
    ORDER BY 
        FIELD(i.periodicidad, 'mensual', 'trimestral', 'semestral', 'anual', 'ocurrencia'),
        i.numeracion";

$stmt = $db->getConnection()->prepare($query);
$stmt->bind_param("iiiiiiiii", 
    $mesSeleccionado, $anoSeleccionado,  // Para mensual
    $mesSeleccionado, $anoSeleccionado,  // Para trimestral
    $mesSeleccionado, $anoSeleccionado,  // Para semestral
    $anoSeleccionado,                    // Para anual
    $mesSeleccionado, $anoSeleccionado   // Para ocurrencia
);
$stmt->execute();
$resultado = $stmt->get_result();

// Agrupar resultados por periodicidad
$itemsPorPeriodicidad = [
    'mensual' => [],
    'trimestral' => [],
    'semestral' => [],
    'anual' => [],
    'ocurrencia' => []
];

$totalSinVerificador = 0;
$totalConVerificador = 0;

while ($row = $resultado->fetch_assoc()) {
    $periodicidad = $row['periodicidad'] ?? 'ocurrencia';
    
    if ($row['doc_id']) {
        if ($row['verificador_id']) {
            $totalConVerificador++;
        } else {
            $totalSinVerificador++;
        }
    }
    
    $itemsPorPeriodicidad[$periodicidad][] = $row;
}
?>

<!-- HEADER CON DEGRADADO -->
<div class="page-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2rem; margin: -1rem -1rem 2rem -1rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
        <div style="background: rgba(255,255,255,0.2); width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-cloud-upload-fill" style="font-size: 1.5rem; color: white;"></i>
        </div>
        <div>
            <h1 style="color: white; font-size: 1.5rem; font-weight: 600; margin: 0;">
                Centro de Publicación y Verificación
            </h1>
            <p style="color: rgba(255,255,255,0.9); font-size: 0.875rem; margin: 0;">
                Gestión de documentos en Transparencia Activa
            </p>
        </div>
    </div>
</div>

<!-- CONTROLES Y ESTADÍSTICAS -->
<div class="row mb-4">
    <!-- Selector de período -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-title mb-3"><i class="bi bi-calendar3"></i> Período</h6>
                <form method="GET" class="d-flex gap-2">
                    <select name="mes" class="form-select" onchange="this.form.submit()">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo ($m === $mesSeleccionado) ? 'selected' : ''; ?>>
                                <?php echo $meses[$m]; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <select name="ano" class="form-select" onchange="this.form.submit()">
                        <?php for ($a = 2024; $a <= 2026; $a++): ?>
                            <option value="<?php echo $a; ?>" <?php echo ($a === $anoSeleccionado) ? 'selected' : ''; ?>>
                                <?php echo $a; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Estadísticas -->
    <div class="col-md-8">
        <div class="row">
            <div class="col-md-6">
                <?php if ($totalSinVerificador > 0): ?>
                <div class="alert alert-warning mb-2" style="border-left: 4px solid #ff9800;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.5rem; margin-right: 0.75rem;"></i>
                        <div>
                            <strong>Pendientes de Publicar</strong>
                            <div><?php echo $totalSinVerificador; ?> documento(s) sin verificador</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <?php if ($totalConVerificador > 0): ?>
                <div class="alert alert-success mb-2" style="border-left: 4px solid #28a745;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill" style="font-size: 1.5rem; margin-right: 0.75rem;"></i>
                        <div>
                            <strong>Ya Publicados</strong>
                            <div><?php echo $totalConVerificador; ?> documento(s) con verificador</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- PESTAÑAS: PENDIENTES / PUBLICADOS -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-pendientes" data-bs-toggle="tab" data-bs-target="#contenido-pendientes" type="button" role="tab">
            <i class="bi bi-clock-history"></i> Pendientes
            <?php if ($totalSinVerificador > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?php echo $totalSinVerificador; ?></span>
            <?php endif; ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-publicados" data-bs-toggle="tab" data-bs-target="#contenido-publicados" type="button" role="tab">
            <i class="bi bi-check-circle"></i> Publicados
            <?php if ($totalConVerificador > 0): ?>
                <span class="badge bg-success ms-1"><?php echo $totalConVerificador; ?></span>
            <?php endif; ?>
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- TAB PENDIENTES -->
    <div class="tab-pane fade show active" id="contenido-pendientes" role="tabpanel">

<!-- TABLAS POR PERIODICIDAD -->
<?php
$periodosNombres = [
    'mensual' => ['nombre' => 'Items Mensuales', 'icon' => 'calendar-month', 'color' => '#3498db'],
    'trimestral' => ['nombre' => 'Items Trimestrales', 'icon' => 'calendar3', 'color' => '#9b59b6'],
    'semestral' => ['nombre' => 'Items Semestrales', 'icon' => 'calendar2-range', 'color' => '#e74c3c'],
    'anual' => ['nombre' => 'Items Anuales', 'icon' => 'calendar-event', 'color' => '#f39c12'],
    'ocurrencia' => ['nombre' => 'Items por Ocurrencia', 'icon' => 'calendar-check', 'color' => '#1abc9c']
];

foreach ($itemsPorPeriodicidad as $periodicidad => $items):
    if (count($items) === 0) continue;
    
    // Filtrar: solo mostrar items CON documento Y SIN verificador (pendientes)
    $itemsPendientes = array_filter($items, function($item) {
        return $item['doc_id'] && !$item['verificador_id'];
    });
    
    if (count($itemsPendientes) === 0) continue;
    
    $config = $periodosNombres[$periodicidad];
?>

<div class="card shadow-sm mb-4">
    <div class="card-header" style="background: <?php echo $config['color']; ?>; color: white; font-weight: 600;">
        <i class="bi bi-<?php echo $config['icon']; ?>"></i> 
        <?php echo $config['nombre']; ?> - <?php echo $meses[$mesSeleccionado] . ' ' . $anoSeleccionado; ?>
        <span class="badge bg-light text-dark ms-2"><?php echo count($itemsPendientes); ?> pendiente(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th width="8%">Núm.</th>
                    <th width="25%">Item</th>
                    <th width="10%">Estado</th>
                    <th width="15%">Cargado Por</th>
                    <th width="12%">Fecha Envío</th>
                    <th width="30%">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itemsPendientes as $item): ?>
                <tr>
                    <td><small class="text-muted"><?php echo htmlspecialchars($item['numeracion']); ?></small></td>
                    <td><strong><?php echo htmlspecialchars($item['item_nombre']); ?></strong></td>
                    <td>
                        <?php if (!$item['doc_id']): ?>
                            <span class="badge bg-secondary"><i class="bi bi-dash-circle"></i> Sin Cargar</span>
                        <?php elseif ($item['verificador_id']): ?>
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Publicado</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($item['doc_id']): ?>
                            <small><?php echo htmlspecialchars($item['usuario_nombre']); ?></small>
                        <?php else: ?>
                            <small class="text-muted">-</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($item['fecha_envio']): ?>
                            <small><?php echo date('d/m/Y H:i', strtotime($item['fecha_envio'])); ?></small>
                        <?php else: ?>
                            <small class="text-muted">-</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Documento pendiente de verificador -->
                        <button class="btn btn-sm btn-info" onclick="verDocumento(<?php echo $item['doc_id']; ?>)">
                            <i class="bi bi-eye"></i> Ver Doc
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="abrirModalVerificador(<?php echo $item['doc_id']; ?>, <?php echo $item['item_id']; ?>, <?php echo $item['usuario_id']; ?>, '<?php echo htmlspecialchars($item['item_nombre'], ENT_QUOTES); ?>')">
                            <i class="bi bi-cloud-upload"></i> Cargar Verificador
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endforeach; ?>

    </div> <!-- Fin tab-pane pendientes -->
    
    <!-- TAB PUBLICADOS -->
    <div class="tab-pane fade" id="contenido-publicados" role="tabpanel">
        
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Mostrando todos los documentos publicados (con verificador cargado) de todos los períodos.
        </div>

<?php
// Para publicados, traer TODOS los documentos con verificador (sin filtro de período)
$queryPublicados = "
    SELECT 
        i.id as item_id,
        i.numeracion,
        i.nombre as item_nombre,
        i.periodicidad,
        d.id as doc_id,
        d.titulo,
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

$resultPublicados = $db->getConnection()->query($queryPublicados);
$itemsPublicadosTodos = [];

while ($row = $resultPublicados->fetch_assoc()) {
    $itemsPublicadosTodos[] = $row;
}

if (count($itemsPublicadosTodos) === 0) {
    echo '<div class="alert alert-warning"><i class="bi bi-inbox"></i> No hay documentos publicados aún.</div>';
} else {
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white">
        <i class="bi bi-check-circle"></i> Documentos Publicados
        <span class="badge bg-light text-dark ms-2"><?php echo count($itemsPublicadosTodos); ?> total</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th width="8%">Núm.</th>
                    <th width="22%">Item</th>
                    <th width="10%">Periodicidad</th>
                    <th width="8%">Período</th>
                    <th width="12%">Cargado Por</th>
                    <th width="12%">Fecha Envío</th>
                    <th width="10%">Publicación</th>
                    <th width="18%">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itemsPublicadosTodos as $item): ?>
                <tr>
                    <td><small class="text-muted"><?php echo htmlspecialchars($item['numeracion']); ?></small></td>
                    <td><strong><?php echo htmlspecialchars($item['item_nombre']); ?></strong></td>
                    <td><small><?php echo ucfirst($item['periodicidad']); ?></small></td>
                    <td>
                        <small class="text-primary">
                            <?php 
                            if ($item['periodicidad'] === 'anual') {
                                echo $item['ano'];
                            } else {
                                echo $meses[$item['mes']] . ' ' . $item['ano'];
                            }
                            ?>
                        </small>
                    </td>
                    <td>
                        <?php if ($item['usuario_nombre']): ?>
                            <small><?php echo htmlspecialchars($item['usuario_nombre']); ?></small>
                        <?php else: ?>
                            <small class="text-muted">-</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($item['fecha_envio']): ?>
                            <small><?php echo date('d/m/Y', strtotime($item['fecha_envio'])); ?></small>
                        <?php else: ?>
                            <small class="text-muted">-</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($item['fecha_carga_portal']): ?>
                            <small class="text-success"><?php echo date('d/m/Y', strtotime($item['fecha_carga_portal'])); ?></small>
                        <?php else: ?>
                            <small class="text-muted">-</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-info" onclick="verDocumento(<?php echo $item['doc_id']; ?>)">
                            <i class="bi bi-file-earmark-text"></i> Doc
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="verVerificador(<?php echo $item['verificador_id']; ?>)">
                            <i class="bi bi-patch-check"></i> Verif
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
}
?>

    </div> <!-- Fin tab-pane publicados -->
</div> <!-- Fin tab-content -->

<!-- MODAL: Ver Documento -->
<div class="modal fade" id="modalVerDocumento" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-file-earmark-text"></i> Documento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalVerDocumentoBody">
                <div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Ver Verificador -->
<div class="modal fade" id="modalVerVerificador" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-patch-check"></i> Verificador de Publicación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalVerVerificadorBody">
                <div class="text-center"><div class="spinner-border text-success" role="status"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Cargar Verificador -->
<div class="modal fade" id="modalCargarVerificador" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-cloud-upload"></i> Cargar Verificador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" action="cargar_verificador.php" id="formCargarVerificador">
                <div class="modal-body">
                    <input type="hidden" name="documento_id" id="inputDocumentoId">
                    <input type="hidden" name="item_id" id="inputItemId">
                    <input type="hidden" name="usuario_id" id="inputUsuarioId">
                    
                    <div class="alert alert-info">
                        <strong>Item:</strong> <span id="spanItemNombre">-</span>
                    </div>
                    
                    <!-- Área de Drop/Paste -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Imagen de Verificación <span class="text-danger">*</span></label>
                        <div id="dropZone" class="border border-2 border-dashed rounded p-4 text-center" style="background: #f8f9fa; cursor: pointer; min-height: 150px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <i class="bi bi-cloud-arrow-up" style="font-size: 3rem; color: #6c757d;"></i>
                            <p class="mt-2 mb-1"><strong>Arrastra o pega una imagen aquí</strong></p>
                            <p class="text-muted small mb-0">o haz clic para seleccionar archivo</p>
                        </div>
                        <input type="file" name="archivo_verificador" id="inputArchivoVerificador" accept="image/*,.pdf" class="d-none">
                        
                        <!-- Preview -->
                        <div id="previewContainer" class="mt-3 d-none">
                            <p class="fw-bold mb-2">Vista previa:</p>
                            <div class="position-relative d-inline-block">
                                <img id="previewImage" src="" alt="Preview" class="img-thumbnail" style="max-width: 100%; max-height: 300px;">
                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" onclick="limpiarArchivo()">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <p class="small text-muted mt-2" id="archivoNombre"></p>
                        </div>
                        
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-info-circle"></i> Formatos: JPG, PNG, GIF, PDF (máximo 5MB)
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="inputComentarios" class="form-label">Comentarios (opcional)</label>
                        <textarea class="form-control" id="inputComentarios" name="comentarios" rows="3" placeholder="Observaciones sobre la publicación..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitVerificador">
                        <i class="bi bi-cloud-upload"></i> Publicar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Ver documento
function verDocumento(docId) {
    const modal = new bootstrap.Modal(document.getElementById('modalVerDocumento'));
    document.getElementById('modalVerDocumentoBody').innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();
    
    fetch(`get_documento.php?doc_id=${docId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalVerDocumentoBody').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('modalVerDocumentoBody').innerHTML = '<div class="alert alert-danger">Error al cargar el documento</div>';
        });
}

// Ver verificador
function verVerificador(verifId) {
    const modal = new bootstrap.Modal(document.getElementById('modalVerVerificador'));
    document.getElementById('modalVerVerificadorBody').innerHTML = '<div class="text-center"><div class="spinner-border text-success" role="status"></div></div>';
    modal.show();
    
    fetch(`get_verificador.php?verif_id=${verifId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalVerVerificadorBody').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('modalVerVerificadorBody').innerHTML = '<div class="alert alert-danger">Error al cargar el verificador</div>';
        });
}

// Abrir modal cargar verificador
function abrirModalVerificador(docId, itemId, usuarioId, itemNombre) {
    document.getElementById('inputDocumentoId').value = docId;
    document.getElementById('inputItemId').value = itemId;
    document.getElementById('inputUsuarioId').value = usuarioId;
    document.getElementById('spanItemNombre').textContent = itemNombre;
    
    limpiarArchivo();
    document.getElementById('inputComentarios').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('modalCargarVerificador'));
    modal.show();
}

// Manejo de archivo
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('inputArchivoVerificador');
const previewContainer = document.getElementById('previewContainer');
const previewImage = document.getElementById('previewImage');
const archivoNombre = document.getElementById('archivoNombre');

// Click en dropZone
dropZone.addEventListener('click', () => fileInput.click());

// Drag & Drop
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = '#0d6efd';
    dropZone.style.backgroundColor = '#e7f3ff';
});

dropZone.addEventListener('dragleave', () => {
    dropZone.style.borderColor = '#dee2e6';
    dropZone.style.backgroundColor = '#f8f9fa';
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = '#dee2e6';
    dropZone.style.backgroundColor = '#f8f9fa';
    
    if (e.dataTransfer.files.length > 0) {
        procesarArchivo(e.dataTransfer.files[0]);
    }
});

// Paste
document.addEventListener('paste', (e) => {
    const modal = document.getElementById('modalCargarVerificador');
    if (modal && modal.classList.contains('show')) {
        const items = e.clipboardData.items;
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                e.preventDefault();
                const file = items[i].getAsFile();
                procesarArchivo(file);
                break;
            }
        }
    }
});

// Selección de archivo
fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        procesarArchivo(e.target.files[0]);
    }
});

// Procesar archivo
function procesarArchivo(file) {
    // Validar tamaño
    if (file.size > 5 * 1024 * 1024) {
        alert('El archivo es demasiado grande (máximo 5MB)');
        return;
    }
    
    // Validar tipo
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!allowedTypes.includes(file.type)) {
        alert('Formato no permitido. Solo: JPG, PNG, GIF, PDF');
        return;
    }
    
    // Mostrar preview
    if (file.type.indexOf('image') !== -1) {
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImage.src = e.target.result;
            previewContainer.classList.remove('d-none');
            dropZone.classList.add('d-none');
            archivoNombre.textContent = `📄 ${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
        };
        reader.readAsDataURL(file);
    } else {
        previewImage.src = '';
        previewContainer.classList.remove('d-none');
        dropZone.classList.add('d-none');
        archivoNombre.textContent = `📄 ${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
    }
    
    // Asignar al input
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(file);
    fileInput.files = dataTransfer.files;
}

// Limpiar archivo
function limpiarArchivo() {
    fileInput.value = '';
    previewImage.src = '';
    previewContainer.classList.add('d-none');
    dropZone.classList.remove('d-none');
    archivoNombre.textContent = '';
}

// Validar formulario
document.getElementById('formCargarVerificador').addEventListener('submit', (e) => {
    if (!fileInput.files || fileInput.files.length === 0) {
        e.preventDefault();
        alert('Debe seleccionar un archivo verificador');
        return false;
    }
});
</script>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
