<?php
// PRIMERO: Verificar autenticación ANTES de cualquier salida
require_once '../includes/check_auth.php';
require_login();

// LUEGO: Incluir header con HTML
require_once '../includes/header.php';

require_once '../classes/Item.php';
require_once '../classes/Documento.php';

$itemClass = new Item($db->getConnection());
$documentoClass = new Documento($db->getConnection());

$user_id = $current_user['id'] ?? null;

// Verificar mensajes
$success = '';
$error = '';

if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = 'Documento enviado correctamente. Pendiente de revisión.';
} elseif (isset($_GET['error'])) {
    $error_msg = $_GET['error'];
    if ($error_msg == '1') $error = 'Falta información requerida';
    elseif ($error_msg == '2') $error = 'No tiene acceso a este item';
    elseif ($error_msg == '3') $error = 'Error al guardar el documento';
    else $error = htmlspecialchars($error_msg);
}

// Obtener items asignados al usuario
$items_result = $itemClass->getItemsByUser($user_id);
$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}

// Agrupar items por periodicidad
$items_por_periodicidad = [
    'mensual' => [],
    'trimestral' => [],
    'semestral' => [],
    'anual' => [],
    'ocurrencia' => []
];

foreach ($items as $item) {
    $items_por_periodicidad[$item['periodicidad']][] = $item;
}

// Obtener documentos del usuario
$documentos_result = $documentoClass->getByUsuario($user_id);
$documentos_stats = [
    'total' => 0,
    'pendiente' => 0,
    'aprobado' => 0,
    'rechazado' => 0
];

while ($doc = $documentos_result->fetch_assoc()) {
    $documentos_stats['total']++;
    $documentos_stats[$doc['estado']]++;
}
$documentos_result->data_seek(0);
?>

<div class="page-header">
    <h1><i class="bi bi-person-fill"></i> Mi Panel</h1>
    <p class="text-white-50">Perfil: <?php echo $PROFILES[$current_profile] ?? $current_profile; ?></p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-file-text" style="font-size: 2rem; color: #3498db;"></i>
                <h5 class="mt-3">Items Asignados</h5>
                <p class="h3" style="color: #3498db;"><?php echo count($items); ?></p>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-file-pdf" style="font-size: 2rem; color: #e74c3c;"></i>
                <h5 class="mt-3">Documentos</h5>
                <p class="h3" style="color: #e74c3c;"><?php echo $documentos_stats['total']; ?></p>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-check-circle" style="font-size: 2rem; color: #27ae60;"></i>
                <h5 class="mt-3">Aprobados</h5>
                <p class="h3" style="color: #27ae60;"><?php echo $documentos_stats['aprobado']; ?></p>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-exclamation-circle" style="font-size: 2rem; color: #f39c12;"></i>
                <h5 class="mt-3">Pendientes</h5>
                <p class="h3" style="color: #f39c12;"><?php echo $documentos_stats['pendiente']; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Tabs para las diferentes periodicidades -->
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#mensual">
            <i class="bi bi-calendar-event"></i> Mensual
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#trimestral">
            <i class="bi bi-calendar-month"></i> Trimestral
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#semestral">
            <i class="bi bi-calendar2"></i> Semestral
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#anual">
            <i class="bi bi-calendar-day"></i> Anual
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#ocurrencia">
            <i class="bi bi-asterisk"></i> Ocurrencia
        </a>
    </li>
</ul>

<div class="tab-content">
    <!-- MENSUAL -->
    <div id="mensual" class="tab-pane fade show active">
        <?php echo renderItemsTab($items_por_periodicidad['mensual'], 'mensual', $documentoClass, $user_id, $MESES); ?>
    </div>

    <!-- TRIMESTRAL -->
    <div id="trimestral" class="tab-pane fade">
        <?php echo renderItemsTab($items_por_periodicidad['trimestral'], 'trimestral', $documentoClass, $user_id, $TRIMESTRES); ?>
    </div>

    <!-- SEMESTRAL -->
    <div id="semestral" class="tab-pane fade">
        <?php echo renderItemsTab($items_por_periodicidad['semestral'], 'semestral', $documentoClass, $user_id, []); ?>
    </div>

    <!-- ANUAL -->
    <div id="anual" class="tab-pane fade">
        <?php echo renderItemsTab($items_por_periodicidad['anual'], 'anual', $documentoClass, $user_id, []); ?>
    </div>

    <!-- OCURRENCIA -->
    <div id="ocurrencia" class="tab-pane fade">
        <?php echo renderItemsTab($items_por_periodicidad['ocurrencia'], 'ocurrencia', $documentoClass, $user_id, []); ?>
    </div>
</div>

<?php

function renderItemsTab($items, $periodicidad, $documentoClass, $user_id, $periodos = []) {
    global $PERIODICIDADES;
    
    if (empty($items)) {
        return '<div class="alert alert-info"><i class="bi bi-info-circle"></i> No hay items asignados con esta periodicidad</div>';
    }

    $html = '<div class="row">';

    foreach ($items as $item) {
        $html .= '<div class="col-md-6 mb-3">';
        $html .= '<div class="card h-100">';
        $html .= '<div class="card-header">';
        $html .= '<h5 class="mb-0">';
        $html .= '<strong>' . htmlspecialchars($item['numeracion']) . '</strong> - ' . htmlspecialchars($item['nombre']);
        $html .= '</h5>';
        $html .= '</div>';
        $html .= '<div class="card-body">';
        $html .= '<p class="text-muted"><small><i class="bi bi-building"></i> ' . htmlspecialchars($item['direccion_nombre'] ?? 'N/A') . '</small></p>';
        
        // Mostrar periodos según la periodicidad
        if ($periodicidad === 'mensual') {
            $html .= '<div class="mb-3">';
            $html .= '<label class="form-label">Seleccione el mes:</label>';
            $html .= '<select class="form-select form-select-sm" onchange="mostrarDocumentos(' . $item['id'] . ', this.value)">';
            $html .= '<option value="">-- Seleccionar --</option>';
            foreach ($periodos as $mes => $nombre) {
                $html .= '<option value="' . $mes . '">' . $nombre . '</option>';
            }
            $html .= '</select>';
            $html .= '</div>';
        } elseif ($periodicidad === 'trimestral') {
            $html .= '<div class="mb-3">';
            $html .= '<label class="form-label">Seleccione el trimestre:</label>';
            $html .= '<select class="form-select form-select-sm" onchange="mostrarDocumentos(' . $item['id'] . ', this.value)">';
            $html .= '<option value="">-- Seleccionar --</option>';
            foreach ($periodos as $trim => $nombre) {
                $html .= '<option value="' . $trim . '">' . $nombre . '</option>';
            }
            $html .= '</select>';
            $html .= '</div>';
        }
        
        $html .= '<div class="mb-3">';
        $html .= '<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#documentoModal" onclick="setItemId(' . $item['id'] . ')">';
        $html .= '<i class="bi bi-cloud-upload"></i> Enviar Documento';
        $html .= '</button>';
        $html .= '</div>';

        // Mostrar documentos del item
        $docs_result = $documentoClass->getByItem($item['id']);
        $docs_count = $docs_result->num_rows;
        
        if ($docs_count > 0) {
            $html .= '<div class="mt-3 pt-3 border-top">';
            $html .= '<p class="text-sm"><strong>Documentos (' . $docs_count . ')</strong></p>';
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-sm mb-0">';
            
            while ($doc = $docs_result->fetch_assoc()) {
                $estado_class = 'pending';
                if ($doc['estado'] === 'aprobado') $estado_class = 'approved';
                if ($doc['estado'] === 'rechazado') $estado_class = 'rejected';
                
                $html .= '<tr>';
                $html .= '<td><small>' . htmlspecialchars($doc['titulo']) . '</small></td>';
                $html .= '<td><span class="state-badge ' . $estado_class . '">' . ucfirst($doc['estado']) . '</span></td>';
                $html .= '<td><small class="text-muted">' . date('d/m/Y', strtotime($doc['fecha_subida'])) . '</small></td>';
                $html .= '</tr>';
            }
            
            $html .= '</table>';
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }

    $html .= '</div>';
    return $html;
}
?>

<!-- Modal para enviar documento -->
<div class="modal fade" id="documentoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enviar Documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="enviar_documento.php" enctype="multipart/form-data" id="documentoForm">
                <div class="modal-body">
                    <input type="hidden" name="item_id" id="itemId">

                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título del Documento</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="archivo" class="form-label">Seleccionar Archivo</label>
                        <input type="file" class="form-control" id="archivo" name="archivo" required>
                        <small class="text-muted">Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX, CSV, JPG, PNG (máx. 10MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-cloud-upload"></i> Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function setItemId(itemId) {
    document.getElementById('itemId').value = itemId;
}

function mostrarDocumentos(itemId, periodo) {
    // Función para filtrar documentos por periodo si es necesario
    console.log('Item:', itemId, 'Periodo:', periodo);
}
</script>

<?php require_once '../includes/footer.php'; ?>
