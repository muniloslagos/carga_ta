<?php
require_once '../includes/check_auth.php';
require_login();

// Solo revisor puede ver esta página
if ($current_profile !== 'revisor') {
    header('Location: ' . SITE_URL . 'usuario/dashboard.php');
    exit;
}

require_once '../includes/header.php';
require_once '../classes/Revisor.php';
require_once '../classes/Documento.php';

$conn = $db->getConnection();
$revisorClass = new Revisor($conn);
$documentoClass = new Documento($conn);

// Verificar si la funcionalidad de revisión está activada
$revision_activada = Revisor::estaActivado($conn);

if (!$revision_activada) {
    ?>
    <div class="container-fluid mt-5">
        <div class="alert alert-warning">
            <h4><i class="bi bi-exclamation-triangle"></i> Funcionalidad de Revisión Desactivada</h4>
            <p>La funcionalidad de revisión previa de documentos no está activada actualmente.</p>
            <p>El administrador puede activarla desde <strong>Configuración del Sistema > General</strong>.</p>
            <a href="<?php echo SITE_URL; ?>logout.php" class="btn btn-secondary">Cerrar Sesión</a>
        </div>
    </div>
    <?php
    require_once '../includes/footer.php';
    exit;
}

// Parámetros de filtro
$anoActual = (int)date('Y');
$mesActual = (int)date('m');
$anoSeleccionado = (isset($_GET['ano']) && $_GET['ano'] !== '') ? (int)$_GET['ano'] : null; // null = todos los años
$mesSeleccionado = (isset($_GET['mes']) && $_GET['mes'] !== '') ? (int)$_GET['mes'] : null; // null = todos los meses
$filtroEstado = isset($_GET['estado']) ? $_GET['estado'] : 'todos'; // 'todos', 'pendientes', 'revisados'

// Validaciones
if ($anoSeleccionado !== null && ($anoSeleccionado < 2020 || $anoSeleccionado > 2050)) $anoSeleccionado = null;
if ($mesSeleccionado !== null && ($mesSeleccionado < 1 || $mesSeleccionado > 12)) $mesSeleccionado = null;

$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// Obtener documentos según filtro
if ($filtroEstado === 'revisados') {
    $documentosResult = $revisorClass->getDocumentosRevisados($_SESSION['user_id'], $anoSeleccionado, $mesSeleccionado);
} elseif ($filtroEstado === 'pendientes') {
    // Solo documentos sin revisar
    $documentosResult = $revisorClass->getDocumentosPendientes($anoSeleccionado, $mesSeleccionado);
} else {
    // 'todos' - obtener todos los documentos (pendientes + revisados)
    $documentosResult = $revisorClass->getTodosDocumentos($anoSeleccionado, $mesSeleccionado);
}

// Obtener estadísticas
$estadisticas = $revisorClass->getEstadisticas($_SESSION['user_id'], $anoSeleccionado);

// Mensajes
$mensaje_success = isset($_SESSION['mensaje_success']) ? $_SESSION['mensaje_success'] : '';
$mensaje_error = isset($_SESSION['mensaje_error']) ? $_SESSION['mensaje_error'] : '';
unset($_SESSION['mensaje_success'], $_SESSION['mensaje_error']);
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="bi bi-clipboard-check" style="color:#17a2b8;"></i> Panel del Revisor</h1>
            <small class="text-muted">Revisión de documentos antes de publicación</small>
        </div>
        <div class="col-auto">
            <a href="<?php echo SITE_URL; ?>logout.php" class="btn btn-outline-secondary">
                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
            </a>
        </div>
    </div>
</div>

<?php if ($mensaje_success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($mensaje_success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($mensaje_error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($mensaje_error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-primary">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Total Revisados</h6>
                        <h2 class="mb-0"><?php echo $estadisticas['total'] ?? 0; ?></h2>
                    </div>
                    <div class="fs-1 text-primary">
                        <i class="bi bi-clipboard-data"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-success">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Aprobados</h6>
                        <h2 class="mb-0 text-success"><?php echo $estadisticas['aprobados'] ?? 0; ?></h2>
                    </div>
                    <div class="fs-1 text-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-warning">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Observados</h6>
                        <h2 class="mb-0 text-warning"><?php echo $estadisticas['observados'] ?? 0; ?></h2>
                    </div>
                    <div class="fs-1 text-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label"><strong>Año</strong></label>
                <select name="ano" class="form-select">
                    <option value="" <?php echo $anoSeleccionado === null ? 'selected' : ''; ?>>Todos</option>
                    <?php for ($a = 2024; $a <= 2030; $a++): ?>
                        <option value="<?php echo $a; ?>" <?php echo $a == $anoSeleccionado ? 'selected' : ''; ?>>
                            <?php echo $a; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label"><strong>Mes</strong></label>
                <select name="mes" class="form-select">
                    <option value="">Todos</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $mesSeleccionado == $m ? 'selected' : ''; ?>>
                            <?php echo $meses[$m]; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label"><strong>Estado</strong></label>
                <select name="estado" class="form-select">
                    <option value="todos" <?php echo $filtroEstado == 'todos' ? 'selected' : ''; ?>>Todos</option>
                    <option value="pendientes" <?php echo $filtroEstado == 'pendientes' ? 'selected' : ''; ?>>Pendientes de Revisión</option>
                    <option value="revisados" <?php echo $filtroEstado == 'revisados' ? 'selected' : ''; ?>>Mis Revisiones</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filtrar
                </button>
            </div>
            <div class="col-md-3">
                <a href="?" class="btn btn-secondary w-100">
                    <i class="bi bi-x-circle"></i> Limpiar Filtros
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de documentos -->
<div class="card">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-list-check"></i> 
            <?php 
                echo $filtroEstado === 'revisados' ? 'Mis Revisiones' : 'Documentos Pendientes de Revisión';
                echo ' - ';
                if ($mesSeleccionado) {
                    echo $meses[$mesSeleccionado] . ' ';
                    echo $anoSeleccionado ? $anoSeleccionado : 'Todos los años';
                } else {
                    echo 'Todos los meses ';
                    echo $anoSeleccionado ? $anoSeleccionado : '(Todos los años)';
                }
            ?>
        </h5>
        <span class="badge bg-light text-dark">
            <?php echo $documentosResult->num_rows; ?> documento(s) encontrado(s)
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>Dirección</th>
                        <th>Cargador</th>
                        <th>Mes/Año</th>
                        <th>Fecha Carga</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $hay_documentos = false;
                    while ($doc = $documentosResult->fetch_assoc()):
                        $hay_documentos = true;
                        $estado_revision = $doc['estado_revision'] ?? null;
                        $observaciones = $doc['observaciones_revision'] ?? '';
                        
                        // Color de la fila según estado
                        $rowClass = '';
                        if ($estado_revision === 'aprobado') {
                            $rowClass = 'table-success';
                            $badgeEstado = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Aprobado</span>';
                        } elseif ($estado_revision === 'observado') {
                            $rowClass = 'table-warning';
                            $badgeEstado = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Observado</span>';
                        } else {
                            $badgeEstado = '<span class="badge bg-secondary">Sin revisar</span>';
                        }
                    ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td>
                                <small><strong><?php echo htmlspecialchars($doc['item_titulo']); ?></strong></small>
                            </td>
                            <td><small><?php echo htmlspecialchars($doc['direccion_nombre'] ?? '—'); ?></small></td>
                            <td><small><?php echo htmlspecialchars($doc['cargador_nombre'] ?? '—'); ?></small></td>
                            <td>
                                <small><?php echo $meses[$doc['mes']] . ' ' . $doc['ano']; ?></small>
                            </td>
                            <td><small><?php echo date('d/m/Y H:i', strtotime($doc['fecha_subida'])); ?></small></td>
                            <td class="text-center">
                                <?php echo $badgeEstado; ?>
                                <?php if ($estado_revision === 'aprobado'): ?>
                                    <i class="bi bi-check-circle-fill text-success ms-1" title="Tick verde visible"></i>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="descargar_documento.php?doc_id=<?php echo $doc['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Ver documento">
                                        <i class="bi bi-file-earmark-text"></i> Ver
                                    </a>
                                    <?php if ($estado_revision !== 'aprobado'): ?>
                                        <button type="button" class="btn btn-sm btn-success" 
                                                onclick="aprobarDocumento(<?php echo $doc['id']; ?>)" title="Aprobar">
                                            <i class="bi bi-check-circle"></i> Aprobar
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            onclick="observarDocumento(<?php echo $doc['id']; ?>, '<?php echo htmlspecialchars($doc['item_titulo']); ?>', '<?php echo htmlspecialchars($observaciones, ENT_QUOTES); ?>')" 
                                            title="Observar">
                                        <i class="bi bi-exclamation-triangle"></i> Observar
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php if ($observaciones && $estado_revision === 'observado'): ?>
                            <tr class="table-warning">
                                <td colspan="7">
                                    <small>
                                        <strong><i class="bi bi-chat-left-text"></i> Observaciones:</strong>
                                        <?php echo nl2br(htmlspecialchars($observaciones)); ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endwhile; ?>
                    
                    <?php if (!$hay_documentos): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <strong>No hay documentos para mostrar</strong><br>
                                <small>
                                    Filtros actuales: 
                                    <?php if ($anoSeleccionado): ?>
                                        Año <strong><?php echo $anoSeleccionado; ?></strong>
                                    <?php else: ?>
                                        <strong>Todos los años</strong>
                                    <?php endif; ?>
                                    <?php if ($mesSeleccionado): ?>
                                        - Mes <strong><?php echo $meses[$mesSeleccionado]; ?></strong>
                                    <?php else: ?>
                                        - <strong>Todos los meses</strong>
                                    <?php endif; ?>
                                    - Estado <strong><?php echo ucfirst($filtroEstado); ?></strong>
                                </small><br>
                                <small class="text-info mt-2 d-block">
                                    💡 Intenta cambiar el año, seleccionar "Todos los meses" o cambiar el filtro de estado
                                </small>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Aprobar Documento -->
<div class="modal fade" id="modalAprobar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="procesar_revision.php">
                <input type="hidden" name="accion" value="aprobar">
                <input type="hidden" name="documento_id" id="aprobar_documento_id">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-check-circle"></i> Aprobar Documento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de aprobar este documento?</p>
                    <p><small class="text-muted">El publicador podrá cargar el verificador y publicarlo.</small></p>
                    
                    <div class="mb-3">
                        <label class="form-label">Observaciones (opcional):</label>
                        <textarea class="form-control" name="observaciones" rows="3" 
                                  placeholder="Ej: Documento revisado y conforme"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Aprobar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Observar Documento -->
<div class="modal fade" id="modalObservar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="procesar_revision.php">
                <input type="hidden" name="accion" value="observar">
                <input type="hidden" name="documento_id" id="observar_documento_id">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Observar Documento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong id="observar_item_titulo"></strong></p>
                    <p><small class="text-muted">El publicador NO podrá cargar el verificador hasta que se corrija y re-apruebe.</small></p>
                    
                    <div class="mb-3">
                        <label class="form-label">Observaciones <span class="text-danger">*</span>:</label>
                        <textarea class="form-control" name="observaciones" id="observar_observaciones" rows="5" 
                                  placeholder="Describa las observaciones o correcciones necesarias..." required></textarea>
                        <small class="text-muted">Las observaciones son obligatorias</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-exclamation-triangle"></i> Observar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function aprobarDocumento(docId) {
    document.getElementById('aprobar_documento_id').value = docId;
    new bootstrap.Modal(document.getElementById('modalAprobar')).show();
}

function observarDocumento(docId, itemTitulo, observacionesActuales) {
    document.getElementById('observar_documento_id').value = docId;
    document.getElementById('observar_item_titulo').textContent = itemTitulo;
    document.getElementById('observar_observaciones').value = observacionesActuales || '';
    new bootstrap.Modal(document.getElementById('modalObservar')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
